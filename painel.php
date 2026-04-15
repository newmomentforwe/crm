<?php
/**
 * RosaPay – CRM Administrativo (painel.php)
 * Design: Dark #0a0a0a + Vermelho Neon #ff0000
 */
require_once __DIR__ . '/config.php';

session_start();

// ── Credenciais hard‑coded (fallback se DB offline) ────────
define('PAINEL_USER', 'admin');
define('PAINEL_PASS', 'rosapay2024');   // ← Troque!

// ── Logout ─────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: painel.php');
    exit;
}

// ── Login ──────────────────────────────────────────────────
$loginError = '';
if (!isset($_SESSION['crm_auth'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['senha'])) {
        $u = trim($_POST['usuario'] ?? '');
        $p = trim($_POST['senha']   ?? '');

        $ok = false;
        // Tenta verificar no banco de dados
        try {
            $stmt = db()->prepare('SELECT senha_hash FROM painel_usuarios WHERE usuario=? LIMIT 1');
            $stmt->execute([$u]);
            $row = $stmt->fetch();
            if ($row && password_verify($p, $row['senha_hash'])) {
                $ok = true;
            }
        } catch (Exception $e) {
            // Fallback: credenciais hard‑coded
            if ($u === PAINEL_USER && $p === PAINEL_PASS) $ok = true;
        }

        if ($ok) {
            $_SESSION['crm_auth'] = true;
            header('Location: painel.php');
            exit;
        } else {
            $loginError = 'Usuário ou senha incorretos.';
        }
    }

    // ── Tela de Login ──────────────────────────────────────
    ?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>RosaPay CRM – Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#0a0a0a;min-height:100vh;display:flex;align-items:center;justify-content:center;overflow:hidden}
/* Fundo animado */
.bg-glow{position:fixed;inset:0;pointer-events:none;z-index:0}
.bg-glow::before{content:'';position:absolute;top:-30%;left:50%;transform:translateX(-50%);width:700px;height:700px;background:radial-gradient(circle,rgba(255,0,0,.15) 0%,transparent 70%);animation:pulseGlow 4s ease-in-out infinite}
@keyframes pulseGlow{0%,100%{opacity:.6;transform:translateX(-50%) scale(1)}50%{opacity:1;transform:translateX(-50%) scale(1.08)}}
.login-card{position:relative;z-index:1;background:rgba(18,18,18,.95);border:1px solid rgba(255,0,0,.25);border-radius:20px;padding:48px 44px;width:100%;max-width:420px;box-shadow:0 0 60px rgba(255,0,0,.12),0 20px 60px rgba(0,0,0,.6)}
.logo-area{text-align:center;margin-bottom:36px}
.logo-icon{width:60px;height:60px;background:linear-gradient(135deg,#ff0000,#990000);border-radius:16px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;box-shadow:0 0 24px rgba(255,0,0,.4)}
.logo-icon svg{width:30px;height:30px;fill:#fff}
.logo-title{font-size:24px;font-weight:800;color:#fff;letter-spacing:-.5px}
.logo-sub{font-size:13px;color:rgba(255,255,255,.4);margin-top:4px}
label{display:block;font-size:11px;font-weight:600;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px}
.field{margin-bottom:18px}
input[type=text],input[type=password]{width:100%;height:48px;background:rgba(255,255,255,.05);border:1.5px solid rgba(255,255,255,.1);border-radius:10px;padding:0 16px;color:#fff;font-family:'Inter',sans-serif;font-size:14px;outline:none;transition:border-color .2s,box-shadow .2s}
input[type=text]:focus,input[type=password]:focus{border-color:rgba(255,0,0,.6);box-shadow:0 0 0 3px rgba(255,0,0,.12)}
input::placeholder{color:rgba(255,255,255,.2)}
.btn-login{width:100%;height:50px;background:linear-gradient(135deg,#ff0000,#cc0000);border:none;border-radius:10px;color:#fff;font-family:'Inter',sans-serif;font-size:15px;font-weight:700;cursor:pointer;letter-spacing:.02em;transition:transform .15s,box-shadow .2s;box-shadow:0 4px 20px rgba(255,0,0,.4);margin-top:8px}
.btn-login:hover{transform:translateY(-1px);box-shadow:0 6px 28px rgba(255,0,0,.55)}
.btn-login:active{transform:translateY(0)}
.error{background:rgba(255,0,0,.12);border:1px solid rgba(255,0,0,.3);border-radius:8px;padding:10px 14px;color:#ff6060;font-size:13px;margin-bottom:16px}
.footer-note{text-align:center;font-size:11px;color:rgba(255,255,255,.2);margin-top:24px}
</style>
</head>
<body>
<div class="bg-glow"></div>
<div class="login-card">
  <div class="logo-area">
    <div class="logo-icon">
      <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
    </div>
    <div class="logo-title">RosaPay CRM</div>
    <div class="logo-sub">Hub Central de Pagamentos</div>
  </div>

  <?php if ($loginError): ?>
  <div class="error">⚠ <?= htmlspecialchars($loginError) ?></div>
  <?php endif; ?>

  <form method="post" autocomplete="off">
    <div class="field">
      <label for="l-usuario">Usuário</label>
      <input id="l-usuario" type="text" name="usuario" placeholder="admin" required autocomplete="username">
    </div>
    <div class="field">
      <label for="l-senha">Senha</label>
      <input id="l-senha" type="password" name="senha" placeholder="••••••••••" required autocomplete="current-password">
    </div>
    <button type="submit" class="btn-login">Entrar no Painel</button>
  </form>
  <div class="footer-note">🔒 Acesso restrito a administradores</div>
</div>
</body>
</html>
    <?php
    exit;
}

// ════════════════════════════════════════════════════════════
//  USUÁRIO AUTENTICADO — Carrega dados do banco
// ════════════════════════════════════════════════════════════
$pdo = db();

// ── Funil de Vendas ─────────────────────────────────────────
$funil = $pdo->query('SELECT * FROM vw_funil')->fetch();

// ── Filtros ──────────────────────────────────────────────────
$filtroStatus = $_GET['status'] ?? '';
$busca        = trim($_GET['q'] ?? '');
$pagina       = max(1, (int)($_GET['p'] ?? 1));
$perPage      = 25;
$offset       = ($pagina - 1) * $perPage;

$where  = [];
$params = [];

if ($filtroStatus !== '') {
    $where[]  = 'status = ?';
    $params[] = $filtroStatus;
}
if ($busca !== '') {
    $where[]  = '(nome LIKE ? OR email LIKE ? OR cpf LIKE ? OR whatsapp LIKE ?)';
    $like     = "%$busca%";
    $params   = array_merge($params, [$like, $like, $like, $like]);
}

$whereSQL = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = (int) $pdo->prepare("SELECT COUNT(*) FROM crm_vendas $whereSQL")
                   ->execute($params) ? $pdo->query("SELECT COUNT(*) FROM crm_vendas $whereSQL")->fetchColumn() : 0;

// Reexecuta com params para o count
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM crm_vendas $whereSQL");
$stmtCount->execute($params);
$total = (int) $stmtCount->fetchColumn();

$totalPag = max(1, (int) ceil($total / $perPage));

$stmtLeads = $pdo->prepare(
    "SELECT * FROM crm_vendas $whereSQL ORDER BY id DESC LIMIT $perPage OFFSET $offset"
);
$stmtLeads->execute($params);
$leads = $stmtLeads->fetchAll();

// ── Receita formatada ─────────────────────────────────────────
function fmt_valor(int $centavos): string {
    return 'R$ ' . number_format($centavos / 100, 2, ',', '.');
}
function status_class(string $s): string {
    return match($s) {
        'Aprovado'   => 'badge-ok',
        'Pendente'   => 'badge-pend',
        'Abandonado' => 'badge-aban',
        'Recusado'   => 'badge-rec',
        default      => 'badge-init',
    };
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>RosaPay CRM – Painel</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ════════════════════════════════════════════════════════════
   DESIGN SYSTEM — Dark #0a0a0a + Neon Red #ff0000
════════════════════════════════════════════════════════════ */
:root{
  --bg:       #0a0a0a;
  --surface:  #111111;
  --surface2: #1a1a1a;
  --border:   rgba(255,255,255,.08);
  --red:      #ff0000;
  --red-dim:  rgba(255,0,0,.15);
  --text:     #f0f0f0;
  --muted:    rgba(255,255,255,.45);
  --green:    #00e676;
  --yellow:   #ffd740;
  --radius:   12px;
  --font:     'Inter', sans-serif;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--font);background:var(--bg);color:var(--text);min-height:100vh;font-size:14px}
a{color:inherit;text-decoration:none}
button{cursor:pointer}

/* ── Sidebar ─────────────────────────────────────────────── */
.layout{display:flex;min-height:100vh}
.sidebar{
  width:240px;flex-shrink:0;
  background:var(--surface);
  border-right:1px solid var(--border);
  display:flex;flex-direction:column;
  padding:28px 0;
  position:sticky;top:0;height:100vh;overflow-y:auto;
}
.sb-logo{padding:0 24px 28px;border-bottom:1px solid var(--border)}
.sb-logo-icon{width:44px;height:44px;background:linear-gradient(135deg,#ff0000,#990000);
  border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:10px;
  box-shadow:0 0 20px rgba(255,0,0,.35)}
.sb-logo-icon svg{width:22px;height:22px;fill:#fff}
.sb-logo h1{font-size:17px;font-weight:800;letter-spacing:-.4px}
.sb-logo span{font-size:11px;color:var(--muted)}
.sb-nav{padding:20px 12px;flex:1}
.sb-item{
  display:flex;align-items:center;gap:10px;
  padding:10px 14px;border-radius:9px;
  font-size:13px;font-weight:500;color:var(--muted);
  transition:background .15s,color .15s;margin-bottom:4px;
}
.sb-item:hover,.sb-item.active{background:var(--red-dim);color:var(--text)}
.sb-item.active{color:var(--red);font-weight:700}
.sb-item svg{width:17px;height:17px;flex-shrink:0}
.sb-section{font-size:9px;font-weight:700;color:rgba(255,255,255,.2);
  text-transform:uppercase;letter-spacing:.12em;padding:20px 14px 8px}
.sb-footer{padding:16px 24px;border-top:1px solid var(--border)}
.btn-logout{width:100%;height:38px;background:rgba(255,0,0,.12);border:1px solid rgba(255,0,0,.25);
  border-radius:8px;color:var(--red);font-family:var(--font);font-size:12px;font-weight:600;
  transition:background .15s}
.btn-logout:hover{background:rgba(255,0,0,.22)}

/* ── Main ────────────────────────────────────────────────── */
.main{flex:1;min-width:0;padding:32px 28px}

/* ── Topbar ──────────────────────────────────────────────── */
.topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px}
.topbar h2{font-size:22px;font-weight:800;letter-spacing:-.4px}
.topbar .ts{font-size:12px;color:var(--muted)}

/* ── Funil (Stat Cards) ──────────────────────────────────── */
.funnel-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:14px;margin-bottom:28px}
.stat-card{
  background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
  padding:20px 18px;position:relative;overflow:hidden;transition:border-color .2s
}
.stat-card:hover{border-color:rgba(255,0,0,.35)}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px}
.stat-card.c-total::before{background:var(--muted)}
.stat-card.c-addr::before{background:#00b0ff}
.stat-card.c-ok::before{background:var(--green)}
.stat-card.c-pend::before{background:var(--yellow)}
.stat-card.c-aban::before{background:#ff6d00}
.stat-card.c-receita::before{background:var(--red)}
.stat-card .sc-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:8px}
.stat-card .sc-val{font-size:28px;font-weight:800;letter-spacing:-.5px}
.stat-card.c-ok .sc-val{color:var(--green)}
.stat-card.c-pend .sc-val{color:var(--yellow)}
.stat-card.c-aban .sc-val{color:#ff6d00}
.stat-card.c-receita .sc-val{color:var(--red)}
.stat-card .sc-sub{font-size:11px;color:var(--muted);margin-top:4px}

/* ── Painel / Filtros ─────────────────────────────────────── */
.card-panel{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.cp-header{padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.cp-title{font-size:16px;font-weight:700}
.filters{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.filters form{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.inp-search{
  height:36px;background:var(--surface2);border:1px solid var(--border);border-radius:8px;
  padding:0 14px;color:var(--text);font-family:var(--font);font-size:13px;width:220px;outline:none;
  transition:border-color .2s
}
.inp-search:focus{border-color:rgba(255,0,0,.5)}
.inp-search::placeholder{color:var(--muted)}
.sel-status{
  height:36px;background:var(--surface2);border:1px solid var(--border);border-radius:8px;
  padding:0 12px;color:var(--text);font-family:var(--font);font-size:13px;outline:none;cursor:pointer
}
.btn-filter{height:36px;padding:0 18px;background:var(--red);border:none;border-radius:8px;
  color:#fff;font-family:var(--font);font-size:13px;font-weight:600;transition:background .15s}
.btn-filter:hover{background:#cc0000}
.btn-clear{height:36px;padding:0 14px;background:var(--surface2);border:1px solid var(--border);
  border-radius:8px;color:var(--muted);font-family:var(--font);font-size:12px;transition:color .15s}
.btn-clear:hover{color:var(--text)}

/* ── Tabela ───────────────────────────────────────────────── */
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:13px}
thead tr{border-bottom:1px solid var(--border)}
thead th{padding:12px 16px;text-align:left;font-size:10px;font-weight:700;
  text-transform:uppercase;letter-spacing:.1em;color:var(--muted);white-space:nowrap}
tbody tr{border-bottom:1px solid var(--border);transition:background .1s}
tbody tr:last-child{border-bottom:none}
tbody tr:hover{background:rgba(255,255,255,.03)}
td{padding:12px 16px;vertical-align:top}
.td-name{font-weight:600;color:var(--text);margin-bottom:2px}
.td-email{font-size:11px;color:var(--muted)}
.td-cpf{font-size:12px;color:var(--muted);white-space:nowrap}

/* Badges de status */
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;letter-spacing:.04em;white-space:nowrap}
.badge-ok  {background:rgba(0,230,118,.15);color:#00e676;border:1px solid rgba(0,230,118,.3)}
.badge-pend{background:rgba(255,215,64,.12);color:#ffd740;border:1px solid rgba(255,215,64,.3)}
.badge-aban{background:rgba(255,109,0,.12);color:#ff9e40;border:1px solid rgba(255,109,0,.3)}
.badge-rec {background:rgba(255,0,0,.12);color:#ff6060;border:1px solid rgba(255,0,0,.3)}
.badge-init{background:rgba(255,255,255,.06);color:var(--muted);border:1px solid var(--border)}

/* Coluna Valor */
.td-valor{font-weight:700;color:var(--green);white-space:nowrap}

/* Coluna Ações */
.btn-wpp{
  display:inline-flex;align-items:center;gap:6px;
  padding:6px 12px;background:rgba(0,230,118,.15);border:1px solid rgba(0,230,118,.3);
  border-radius:8px;color:#00e676;font-size:11px;font-weight:700;white-space:nowrap;
  transition:background .15s;text-decoration:none
}
.btn-wpp:hover{background:rgba(0,230,118,.25)}
.btn-wpp svg{width:14px;height:14px;fill:currentColor}

/* Coluna PIX code */
.pix-code-cell{max-width:200px}
.pix-snippet{
  background:var(--surface2);border:1px solid var(--border);border-radius:6px;
  padding:5px 8px;font-size:10px;color:var(--muted);word-break:break-all;
  cursor:pointer;transition:border-color .15s;position:relative;
}
.pix-snippet:hover{border-color:rgba(255,0,0,.4);color:var(--text)}
.pix-snippet::after{content:'Copiar';position:absolute;top:50%;right:6px;transform:translateY(-50%);
  font-size:9px;font-weight:700;color:var(--red);opacity:0;transition:opacity .15s}
.pix-snippet:hover::after{opacity:1}

/* Paginação */
.pagination{display:flex;align-items:center;justify-content:space-between;padding:16px 22px;border-top:1px solid var(--border);font-size:12px;color:var(--muted)}
.pag-links{display:flex;gap:6px}
.pag-btn{height:32px;min-width:32px;padding:0 10px;background:var(--surface2);border:1px solid var(--border);
  border-radius:7px;color:var(--muted);font-family:var(--font);font-size:12px;transition:all .15s;text-decoration:none;
  display:flex;align-items:center;justify-content:center}
.pag-btn:hover,.pag-btn.active{background:var(--red);border-color:var(--red);color:#fff}

/* Toast */
#toast{position:fixed;bottom:28px;right:28px;background:#1e1e1e;border:1px solid var(--border);
  border-radius:10px;padding:12px 20px;font-size:13px;color:var(--green);
  box-shadow:0 8px 30px rgba(0,0,0,.6);transform:translateY(80px);opacity:0;
  transition:all .3s;z-index:9999;pointer-events:none}
#toast.show{transform:translateY(0);opacity:1}

/* Responsive */
@media(max-width:900px){
  .sidebar{display:none}
  .main{padding:20px 14px}
}
</style>
</head>
<body>

<div class="layout">

  <!-- ── Sidebar ────────────────────────────────────────── -->
  <aside class="sidebar">
    <div class="sb-logo">
      <div class="sb-logo-icon">
        <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2zm0 14H4V6h16v12zM6 10h2v2H6zm0 4h8v2H6zm10 0h2v2h-2zm-6-4h8v2h-8z"/></svg>
      </div>
      <h1>RosaPay</h1>
      <span>Hub Central de Pagamentos</span>
    </div>
    <nav class="sb-nav">
      <div class="sb-section">Menu</div>
      <a href="painel.php" class="sb-item active">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
        Dashboard
      </a>
      <a href="painel.php?status=Aprovado" class="sb-item">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
        Aprovados
      </a>
      <a href="painel.php?status=Pendente" class="sb-item">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        Pendentes
      </a>
      <a href="painel.php?status=Abandonado" class="sb-item">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
        Abandonados
      </a>
      <div class="sb-section">Sistema</div>
      <a href="painel.php?status=Recusado" class="sb-item">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        Recusados
      </a>
    </nav>
    <div class="sb-footer">
      <form method="get"><button type="submit" name="logout" value="1" class="btn-logout">⏻ Sair do Painel</button></form>
    </div>
  </aside>

  <!-- ── Main Content ───────────────────────────────────── -->
  <main class="main">
    <div class="topbar">
      <h2>📊 Dashboard CRM</h2>
      <span class="ts"><?= date('d/m/Y H:i') ?></span>
    </div>

    <!-- Funil de Vendas -->
    <div class="funnel-grid">
      <div class="stat-card c-total">
        <div class="sc-label">Total de Leads</div>
        <div class="sc-val"><?= number_format((int)$funil['total_leads']) ?></div>
        <div class="sc-sub">Todos os contatos</div>
      </div>
      <div class="stat-card c-addr">
        <div class="sc-label">Com Endereço</div>
        <div class="sc-val"><?= number_format((int)$funil['com_endereco']) ?></div>
        <div class="sc-sub">Preencheram entrega</div>
      </div>
      <div class="stat-card c-ok">
        <div class="sc-label">Aprovados</div>
        <div class="sc-val"><?= number_format((int)$funil['aprovados']) ?></div>
        <div class="sc-sub">Pagamento confirmado</div>
      </div>
      <div class="stat-card c-pend">
        <div class="sc-label">Pendentes</div>
        <div class="sc-val"><?= number_format((int)$funil['pendentes']) ?></div>
        <div class="sc-sub">Aguardando pagamento</div>
      </div>
      <div class="stat-card c-aban">
        <div class="sc-label">Abandonados</div>
        <div class="sc-val"><?= number_format((int)$funil['abandonados']) ?></div>
        <div class="sc-sub">Não finalizaram</div>
      </div>
      <div class="stat-card c-receita">
        <div class="sc-label">Receita Total</div>
        <div class="sc-val" style="font-size:20px"><?= fmt_valor((int)$funil['receita_centavos']) ?></div>
        <div class="sc-sub">Apenas aprovados</div>
      </div>
    </div>

    <!-- Tabela de Leads -->
    <div class="card-panel">
      <div class="cp-header">
        <div class="cp-title">Leads &amp; Transações
          <span style="font-size:12px;font-weight:400;color:var(--muted);margin-left:8px">(<?= $total ?> registros)</span>
        </div>
        <div class="filters">
          <form method="get">
            <input class="inp-search" type="text" name="q" placeholder="🔍 Buscar nome, email, CPF..." value="<?= htmlspecialchars($busca) ?>">
            <select class="sel-status" name="status">
              <option value="">Todos os status</option>
              <?php foreach (['Iniciado','Pendente','Aprovado','Abandonado','Recusado'] as $s): ?>
              <option value="<?= $s ?>" <?= ($filtroStatus===$s)?'selected':'' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-filter">Filtrar</button>
            <a href="painel.php" class="btn-clear">Limpar</a>
          </form>
        </div>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Cliente</th>
              <th>CPF</th>
              <th>Endereço</th>
              <th>Origem</th>
              <th>Método</th>
              <th>Valor</th>
              <th>Status</th>
              <th>PIX Code</th>
              <th>WhatsApp</th>
              <th>Data</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($leads)): ?>
            <tr><td colspan="11" style="text-align:center;padding:40px;color:var(--muted)">Nenhum registro encontrado.</td></tr>
            <?php else: foreach ($leads as $l): ?>
            <tr>
              <td style="color:var(--muted);font-size:12px"><?= $l['id'] ?></td>
              <td>
                <div class="td-name"><?= htmlspecialchars($l['nome'] ?: '—') ?></div>
                <div class="td-email"><?= htmlspecialchars($l['email'] ?: '—') ?></div>
              </td>
              <td class="td-cpf"><?= htmlspecialchars($l['cpf'] ?: '—') ?></td>
              <td style="font-size:11px;color:var(--muted)">
                <?php if ($l['rua']): ?>
                  <?= htmlspecialchars($l['rua'] . ', ' . $l['numero']) ?><br>
                  <?= htmlspecialchars($l['cidade'] . ' – ' . $l['estado']) ?>
                <?php else: ?>
                  <span style="color:rgba(255,255,255,.2)">—</span>
                <?php endif; ?>
              </td>
              <td style="font-size:10px;color:var(--muted);max-width:120px;word-break:break-all">
                <?= $l['origem_url'] ? '<a href="'.htmlspecialchars($l['origem_url']).'" target="_blank" title="'.htmlspecialchars($l['origem_url']).'" style="color:var(--muted)">'.parse_url($l['origem_url'], PHP_URL_HOST).'</a>' : '—' ?>
              </td>
              <td>
                <?php if ($l['tronfy_metodo']): ?>
                  <span class="badge <?= $l['tronfy_metodo']==='PIX'?'badge-pend':'badge-ok' ?>"><?= $l['tronfy_metodo'] ?></span>
                <?php else: echo '—'; endif; ?>
              </td>
              <td class="td-valor">
                <?= $l['tronfy_valor'] ? fmt_valor((int)$l['tronfy_valor']) : '—' ?>
              </td>
              <td><span class="badge <?= status_class($l['status']) ?>"><?= $l['status'] ?></span></td>
              <td class="pix-code-cell">
                <?php if ($l['tronfy_pix_code']): ?>
                  <div class="pix-snippet" onclick="copyPix(this)" data-code="<?= htmlspecialchars($l['tronfy_pix_code']) ?>" title="Clique para copiar o código PIX">
                    <?= htmlspecialchars(substr($l['tronfy_pix_code'], 0, 40)) ?>…
                  </div>
                <?php else: echo '<span style="color:rgba(255,255,255,.2)">—</span>'; endif; ?>
              </td>
              <td>
                <?php
                $wpp = preg_replace('/\D/', '', $l['whatsapp']);
                if (strlen($wpp) >= 10):
                  $wppLink = 'https://wa.me/55' . $wpp . '?text=' . urlencode("Olá {$l['nome']}, tudo bem? Vi que você demonstrou interesse no nosso produto. Posso te ajudar a finalizar sua compra?");
                ?>
                <a href="<?= $wppLink ?>" target="_blank" class="btn-wpp">
                  <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                  WhatsApp
                </a>
                <?php else: echo '<span style="color:rgba(255,255,255,.2)">—</span>'; endif; ?>
              </td>
              <td style="font-size:11px;color:var(--muted);white-space:nowrap">
                <?= date('d/m/y H:i', strtotime($l['created_at'])) ?>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Paginação -->
      <?php if ($totalPag > 1): ?>
      <div class="pagination">
        <span>Página <?= $pagina ?> de <?= $totalPag ?></span>
        <div class="pag-links">
          <?php
          $qs = array_filter(['q'=>$busca,'status'=>$filtroStatus]);
          for ($i=1;$i<=$totalPag;$i++):
            $params2 = array_merge($qs,['p'=>$i]);
            $href='painel.php?'.http_build_query($params2);
          ?>
          <a href="<?= $href ?>" class="pag-btn <?= $i===$pagina?'active':'' ?>"><?= $i ?></a>
          <?php endfor; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<!-- Toast -->
<div id="toast">✓ Código PIX copiado!</div>

<script>
function copyPix(el) {
  const code = el.dataset.code;
  navigator.clipboard.writeText(code).then(() => {
    const t = document.getElementById('toast');
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2400);
  });
}
</script>
</body>
</html>
