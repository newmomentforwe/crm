<?php
/**
 * RosaPay – Motor de Captura Central (api_hub.php)
 * ─────────────────────────────────────────────────────────────────────────────
 * Endpoints disponíveis (via POST com JSON):
 *   ?action=lead          → Upsert de lead (por CPF ou email)
 *   ?action=charge        → Gera cobrança PIX ou Cartão na Tronfy
 *   ?action=update_status → Atualiza status por transaction_id
 *
 * Aceita chamadas de QUALQUER domínio (CORS aberto).
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/config.php';

// ── CORS (aceita chamadas de qualquer domínio de vendas) ───
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, x-api-key, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Método não permitido. Use POST.']);
}

// ── Lê body JSON ──────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    respond(400, ['error' => 'Body JSON inválido ou ausente.']);
}

$action = trim($_GET['action'] ?? $body['action'] ?? '');

switch ($action) {
    case 'lead':          handle_lead($body);          break;
    case 'charge':        handle_charge($body);        break;
    case 'update_status': handle_update_status($body); break;
    default:
        respond(400, ['error' => "Ação desconhecida: '$action'. Use: lead, charge, update_status"]);
}

// ═══════════════════════════════════════════════════════════
//  HANDLER: Criar / Atualizar Lead (Upsert)
// ═══════════════════════════════════════════════════════════
function handle_lead(array $d): void
{
    // Campos obrigatórios mínimos para criar um lead
    $cpf   = sanitize_cpf($d['cpf']   ?? '');
    $email = strtolower(trim($d['email'] ?? ''));

    if ($cpf === '' && $email === '') {
        respond(422, ['error' => 'Forneça CPF ou email para identificar o lead.']);
    }

    $pdo = db();

    // Tenta localizar lead existente (prioridade: CPF > email)
    $lead = null;
    if ($cpf !== '') {
        $stmt = $pdo->prepare('SELECT * FROM crm_vendas WHERE cpf = ? LIMIT 1');
        $stmt->execute([$cpf]);
        $lead = $stmt->fetch();
    }
    if (!$lead && $email !== '') {
        $stmt = $pdo->prepare('SELECT * FROM crm_vendas WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $lead = $stmt->fetch();
    }

    // Monta campos para inserção ou atualização
    $fields = [
        'nome'       => trim($d['nome']       ?? ($lead['nome']       ?? '')),
        'email'      => $email ?: ($lead['email']                      ?? ''),
        'cpf'        => $cpf   ?: ($lead['cpf']                        ?? ''),
        'whatsapp'   => sanitize_phone($d['whatsapp'] ?? $d['celular'] ?? ($lead['whatsapp'] ?? '')),
        'origem_url' => trim($d['origem_url'] ?? ($lead['origem_url']  ?? '')),
        'cep'        => trim($d['cep']        ?? ($lead['cep']         ?? '')),
        'rua'        => trim($d['rua']        ?? ($lead['rua']         ?? '')),
        'numero'     => trim($d['numero']     ?? ($lead['numero']      ?? '')),
        'complemento'=> trim($d['complemento']?? ($lead['complemento'] ?? '')),
        'bairro'     => trim($d['bairro']     ?? ($lead['bairro']      ?? '')),
        'cidade'     => trim($d['cidade']     ?? ($lead['cidade']      ?? '')),
        'estado'     => strtoupper(trim($d['estado'] ?? ($lead['estado'] ?? ''))),
        'status'     => map_status($d['status'] ?? ($lead['status']    ?? 'Iniciado')),
    ];

    if ($lead) {
        // UPDATE
        $sql = 'UPDATE crm_vendas SET
                  nome=:nome, email=:email, cpf=:cpf, whatsapp=:whatsapp,
                  origem_url=:origem_url,
                  cep=:cep, rua=:rua, numero=:numero, complemento=:complemento,
                  bairro=:bairro, cidade=:cidade, estado=:estado,
                  status=:status
                WHERE id=:id';
        $fields['id'] = $lead['id'];
        $pdo->prepare($sql)->execute($fields);
        respond(200, ['ok' => true, 'action' => 'updated', 'id' => (int)$lead['id']]);
    } else {
        // INSERT
        $sql = 'INSERT INTO crm_vendas
                  (nome, email, cpf, whatsapp, origem_url,
                   cep, rua, numero, complemento, bairro, cidade, estado, status)
                VALUES
                  (:nome,:email,:cpf,:whatsapp,:origem_url,
                   :cep,:rua,:numero,:complemento,:bairro,:cidade,:estado,:status)';
        $pdo->prepare($sql)->execute($fields);
        $newId = (int) $pdo->lastInsertId();
        respond(201, ['ok' => true, 'action' => 'created', 'id' => $newId]);
    }
}

// ═══════════════════════════════════════════════════════════
//  HANDLER: Gerar Cobrança na Tronfy
// ═══════════════════════════════════════════════════════════
function handle_charge(array $d): void
{
    // ── Validação básica ────────────────────────────────────
    $valor = floatval($d['valor'] ?? $d['total'] ?? 0);
    if ($valor <= 0) {
        respond(422, ['error' => 'Informe o valor da cobrança (campo "valor" ou "total").']);
    }

    $cpf   = sanitize_cpf($d['cpf']   ?? '');
    $email = strtolower(trim($d['email'] ?? ''));
    if ($cpf === '' && $email === '') {
        respond(422, ['error' => 'Forneça CPF ou email do cliente.']);
    }

    $isPix = !isset($d['card_token']); // sem token = PIX
    $url   = $isPix ? TRONFY_PIX_URL : TRONFY_CARD_URL;

    // ── Valor em centavos ───────────────────────────────────
    $amountCents = (int) round($valor * 100);

    // ── Monta payload para Tronfy ───────────────────────────
    $payload = [
        'amount'      => $amountCents,
        'description' => trim($d['descricao'] ?? $d['description'] ?? 'Pedido RosaPay'),
        'external_id' => 'ROSAPAY_' . time() . '_' . rand(100, 999),
        'customer'    => [
            'name'     => trim($d['nome']     ?? ''),
            'document' => preg_replace('/\D/', '', $cpf),
            'email'    => $email,
            'phone'    => preg_replace('/\D/', '', $d['whatsapp'] ?? $d['celular'] ?? ''),
        ],
    ];

    if (!$isPix) {
        $payload['card_token']   = $d['card_token'];
        $payload['installments'] = (int) ($d['installments'] ?? 1);
    }

    // ── Chama Tronfy ────────────────────────────────────────
    $tronfy = tronfy_request($url, $payload);

    if (!$tronfy['ok']) {
        respond(502, [
            'error'   => 'Falha ao processar pagamento na Tronfy.',
            'details' => $tronfy['body'],
        ]);
    }

    $resp    = $tronfy['body'];
    $txId    = $resp['id'] ?? ($resp['transaction_id'] ?? '');
    $pixCode = $resp['qr_code']      ?? ($resp['pix_qr_code_text'] ?? '');
    $pixQr   = $resp['qr_code_url']  ?? ($resp['pix_image_url']    ?? '');
    $metodo  = $isPix ? 'PIX' : 'CARTAO';
    $status  = map_tronfy_status($resp['status'] ?? '');

    // ── Atualiza o lead no banco ─────────────────────────────
    $pdo = db();
    if ($cpf !== '') {
        $where = 'cpf = ?'; $param = $cpf;
    } else {
        $where = 'email = ?'; $param = $email;
    }

    $pdo->prepare("UPDATE crm_vendas
                   SET tronfy_transaction_id=?, tronfy_metodo=?, tronfy_valor=?,
                       tronfy_pix_code=?, tronfy_pix_qr=?, status=?
                   WHERE $where
                   ORDER BY id DESC LIMIT 1")
        ->execute([$txId, $metodo, $amountCents, $pixCode, $pixQr, $status, $param]);

    respond(200, [
        'ok'             => true,
        'transaction_id' => $txId,
        'status'         => $status,
        'metodo'         => $metodo,
        'valor_centavos' => $amountCents,
        'pix_code'       => $pixCode,
        'pix_qr_url'     => $pixQr,
        'tronfy_response'=> $resp,
    ]);
}

// ═══════════════════════════════════════════════════════════
//  HANDLER: Atualizar Status por Transaction ID
// ═══════════════════════════════════════════════════════════
function handle_update_status(array $d): void
{
    $txId   = trim($d['transaction_id'] ?? '');
    $status = map_status($d['status']   ?? '');

    if ($txId === '' || $status === '') {
        respond(422, ['error' => 'Informe transaction_id e status.']);
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'UPDATE crm_vendas SET status=? WHERE tronfy_transaction_id=?'
    );
    $stmt->execute([$status, $txId]);

    respond(200, ['ok' => true, 'rows_affected' => $stmt->rowCount()]);
}

// ═══════════════════════════════════════════════════════════
//  HELPER: Requisição para a API Tronfy
// ═══════════════════════════════════════════════════════════
function tronfy_request(string $url, array $payload): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'x-api-key: '   . TRONFY_SK,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $raw       = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['ok' => false, 'body' => ['curl_error' => $curlError]];
    }

    $decoded = json_decode($raw, true) ?? ['raw' => $raw];
    $ok      = ($httpCode >= 200 && $httpCode < 300);

    return ['ok' => $ok, 'http_code' => $httpCode, 'body' => $decoded];
}

// ═══════════════════════════════════════════════════════════
//  HELPERS de Sanitização e Mapping
// ═══════════════════════════════════════════════════════════
function sanitize_cpf(string $v): string
{
    $digits = preg_replace('/\D/', '', $v);
    if (strlen($digits) === 11) {
        return substr($digits, 0, 3) . '.' .
               substr($digits, 3, 3) . '.' .
               substr($digits, 6, 3) . '-' .
               substr($digits, 9, 2);
    }
    return $digits;
}

function sanitize_phone(string $v): string
{
    $digits = preg_replace('/\D/', '', $v);
    // Remove DDI 55 se presente
    if (strlen($digits) === 13 && substr($digits, 0, 2) === '55') {
        $digits = substr($digits, 2);
    }
    if (strlen($digits) === 11) {
        return '(' . substr($digits, 0, 2) . ') ' .
               substr($digits, 2, 5) . '-' . substr($digits, 7);
    }
    return $v;
}

function map_status(string $v): string
{
    $map = [
        'iniciado'   => 'Iniciado',
        'pendente'   => 'Pendente',
        'aprovado'   => 'Aprovado',
        'abandonado' => 'Abandonado',
        'recusado'   => 'Recusado',
    ];
    return $map[strtolower(trim($v))] ?? (in_array($v, ['Iniciado','Pendente','Aprovado','Abandonado','Recusado']) ? $v : 'Iniciado');
}

function map_tronfy_status(string $v): string
{
    $v = strtolower(trim($v));
    return match($v) {
        'approved', 'paid', 'active', 'completed' => 'Aprovado',
        'pending', 'waiting_payment', 'processing' => 'Pendente',
        'refused', 'failed', 'error'               => 'Recusado',
        'canceled', 'cancelled', 'expired'         => 'Abandonado',
        default                                    => 'Pendente',
    };
}

function respond(int $code, array $data): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
