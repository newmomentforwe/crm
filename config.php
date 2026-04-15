<?php
/**
 * RosaPay – Config Central
 * Edite apenas este arquivo com as credenciais do seu servidor.
 */

// ── Banco de Dados ─────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'rosapay');
define('DB_USER', 'root');          // ← troque pelo usuário MySQL
define('DB_PASS', '');              // ← troque pela senha MySQL
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

// ── Chaves Tronfy ──────────────────────────────────────────
define('TRONFY_SK', 'sk_live_tronfy_4ab806158b27b2ae3be66b29d55ba7a3f20a0363c40d24af');
define('TRONFY_PK', 'pk_live_tronfy_a2cd7a95bcd6dfcea2457fa6963cb894724e3d2d399311b3');

// ── URLs da API Tronfy ─────────────────────────────────────
define('TRONFY_PIX_URL',  'https://api.tronfy.com.br/v1/pix-charges');
define('TRONFY_CARD_URL', 'https://api.tronfy.com.br/v1/card-charges');

// ── Configurações do Painel ────────────────────────────────
define('PAINEL_TIMEOUT', 3600);      // Sessão em segundos (1 hora)

// ── Conexão PDO (singleton) ─────────────────────────────────
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
