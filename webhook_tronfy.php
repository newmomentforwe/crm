<?php
/**
 * RosaPay – Webhook Tronfy (webhook_tronfy.php)
 * Recebe notificações de status da Tronfy e atualiza o banco.
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

$body = json_decode(file_get_contents('php://input'), true);

if (!$body || empty($body['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Payload inválido']);
    exit;
}

$txId   = $body['id']                  ?? '';
$status = $body['status']              ?? '';

// Mapeia status Tronfy → crm_vendas
$map = [
    'approved'       => 'Aprovado',
    'paid'           => 'Aprovado',
    'active'         => 'Aprovado',
    'completed'      => 'Aprovado',
    'pending'        => 'Pendente',
    'waiting_payment'=> 'Pendente',
    'processing'     => 'Pendente',
    'refused'        => 'Recusado',
    'failed'         => 'Recusado',
    'error'          => 'Recusado',
    'canceled'       => 'Abandonado',
    'cancelled'      => 'Abandonado',
    'expired'        => 'Abandonado',
];
$novoStatus = $map[strtolower($status)] ?? null;

if ($txId && $novoStatus) {
    $pdo = db();
    $pdo->prepare(
        'UPDATE crm_vendas SET status=? WHERE tronfy_transaction_id=?'
    )->execute([$novoStatus, $txId]);
}

// Tronfy espera HTTP 200 para confirmar recebimento
http_response_code(200);
echo json_encode(['received' => true]);
