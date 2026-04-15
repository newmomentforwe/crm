<?php
/**
 * ╔══════════════════════════════════════════════════════════╗
 * ║  RosaCRM — Painel Central de Leads e Pagamentos          ║
 * ║  Arquivo : rosacrm.php                                   ║
 * ║  Design  : Dark #0a0a0a + Vermelho Neon #ff0000          ║
 * ╚══════════════════════════════════════════════════════════╝
 */

// ── Configuração inline (edite aqui caso não use config.php) ─
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'rosapay');
define('DB_USER', 'root');     // ← seu usuário MySQL
define('DB_PASS', '');         // ← sua senha MySQL
define('DB_CHARSET', 'utf8mb4');

// Credenciais do painel (fallback se o banco estiver offline)
define('CRM_USER', 'admin');
define('CRM_PASS', 'rosapay2024');  // ← TROQUE após o primeiro acesso!

// ── Conexão PDO ─────────────────────────────────────────────
function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=".DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}

session_name('rosacrm_session');
session_start();

// ── Logout ───────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: rosacrm.php');
    exit;
}

// ═══════════════════════════════════════════════════════════
//  TELA DE LOGIN
// ═══════════════════════════════════════════════════════════
$loginError = '';
if (!isset($_SESSION['rosacrm_auth'])) {

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['senha'])) {
        $u = trim($_POST['usuario'] ?? '');
        $p = trim($_POST['senha']   ?? '');
        $ok = false;

        // Verifica no banco (tabela painel_usuarios)
        try {
            $stmt = db()->prepare('SELECT senha_hash FROM painel_usuarios WHERE usuario=? LIMIT 1');
            $stmt->execute([$u]);
            $row = $stmt->fetch();
            if ($row && password_verify($p, $row['senha_hash'])) $ok = true;
        } catch (Exception) {
            // Fallback hard-coded
            if ($u === CRM_USER && $p === CRM_PASS) $ok = true;
        }

        if ($ok) {
            $_SESSION['rosacrm_auth'] = true;
            header('Location: rosacrm.php');
            exit;
        }
        $loginError = 'Usuário ou senha incorretos. Tente novamente.';
    }

    // ───────────────── HTML LOGIN ─────────────────────────
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>RosaCRM — Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#0a0a0a;min-height:100vh;display:flex;align-items:center;justify-content:center;overflow:hidden}

/* ── Background animado ─── */
.bg{position:fixed;inset:0;z-index:0;overflow:hidden}
.bg-orb{position:absolute;border-radius:50%;filter:blur(80px);opacity:.5;animation:orbFloat 8s ease-in-out infinite}
.bg-orb.o1{width:500px;height:500px;background:radial-gradient(circle,rgba(255,0,0,.25),transparent);top:-100px;left:50%;transform:translateX(-50%);animation-delay:0s}
.bg-orb.o2{width:300px;height:300px;background:radial-gradient(circle,rgba(180,0,0,.15),transparent);bottom:0;right:-80px;animation-delay:3s}
.bg-orb.o3{width:200px;height:200px;background:radial-gradient(circle,rgba(80,0,0,.2),transparent);bottom:40px;left:0;animation-delay:5s}
@keyframes orbFloat{0%,100%{transform:translateX(-50%) translateY(0) scale(1)}50%{transform:translateX(-50%) translateY(-30px) scale(1.05)}}
.bg-grid{position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);background-size:40px 40px}

/* ── Card de login ─── */
.login-wrap{position:relative;z-index:1;width:100%;max-width:420px;padding:0 16px}
.login-card{
  background:rgba(14,14,14,.96);
  border:1px solid rgba(255,0,0,.2);
  border-radius:24px;
  padding:52px 44px 44px;
  box-shadow:0 0 0 1px rgba(255,255,255,.03),0 32px 80px rgba(0,0,0,.7),0 0 60px rgba(255,0,0,.08);
  backdrop-filter:blur(20px);
}
.logo-area{text-align:center;margin-bottom:40px}
.logo-mark{
  width:68px;height:68px;
  background:linear-gradient(135deg,#ff0000 0%,#8b0000 100%);
  border-radius:20px;
  display:inline-flex;align-items:center;justify-content:center;
  margin-bottom:16px;
  box-shadow:0 0 0 1px rgba(255,0,0,.3),0 8px 32px rgba(255,0,0,.4);
  animation:logoPulse 3s ease-in-out infinite;
}
@keyframes logoPulse{0%,100%{box-shadow:0 0 0 1px rgba(255,0,0,.3),0 8px 32px rgba(255,0,0,.4)}50%{box-shadow:0 0 0 1px rgba(255,0,0,.5),0 8px 48px rgba(255,0,0,.6)}}
.logo-mark svg{width:34px;height:34px;fill:#fff}
.logo-name{font-size:28px;font-weight:900;color:#fff;letter-spacing:-1px}
.logo-name span{color:#ff0000}
.logo-sub{font-size:12px;color:rgba(255,255,255,.35);margin-top:6px;letter-spacing:.04em}

/* ── Formulário ─── */
.field{margin-bottom:20px}
.field label{display:block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:rgba(255,255,255,.4);margin-bottom:8px}
.field input{
  width:100%;height:50px;
  background:rgba(255,255,255,.04);
  border:1.5px solid rgba(255,255,255,.08);
  border-radius:12px;
  padding:0 18px;
  color:#fff;font-family:'Inter',sans-serif;font-size:14px;font-weight:500;
  outline:none;transition:border-color .2s,box-shadow .2s,background .2s;
}
.field input:focus{border-color:rgba(255,0,0,.5);box-shadow:0 0 0 3px rgba(255,0,0,.1);background:rgba(255,255,255,.06)}
.field input::placeholder{color:rgba(255,255,255,.18)}

.btn-login{
  width:100%;height:52px;
  background:linear-gradient(135deg,#ff0000 0%,#c00000 100%);
  border:none;border-radius:12px;
  color:#fff;font-family:'Inter',sans-serif;font-size:15px;font-weight:700;
  cursor:pointer;letter-spacing:.02em;
  transition:filter .15s,transform .1s,box-shadow .2s;
  box-shadow:0 4px 20px rgba(255,0,0,.45);
  margin-top:4px;
}
.btn-login:hover{filter:brightness(1.1);box-shadow:0 6px 32px rgba(255,0,0,.6)}
.btn-login:active{transform:scale(.98)}

.error-msg{
  background:rgba(255,0,0,.1);
  border:1px solid rgba(255,0,0,.25);
  border-radius:10px;padding:12px 16px;
  color:#ff8080;font-size:13px;margin-bottom:20px;
  display:flex;align-items:center;gap:8px;
}
.footer-note{text-align:center;font-size:11px;color:rgba(255,255,255,.18);margin-top:24px}
</style>
</head>
<body>
<div class="bg">
  <div class="bg-grid"></div>
  <div class="bg-orb o1"></div>
  <div class="bg-orb o2"></div>
  <div class="bg-orb o3"></div>
</div>
<div class="login-wrap">
  <div class="login-card">
    <div class="logo-area">
      <div class="logo-mark">
        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/></svg>
      </div>
      <div class="logo-name">Rosa<span>CRM</span></div>
      <div class="logo-sub">PAINEL DE CONTROLE CENTRAL</div>
    </div>

    <?php if ($loginError): ?>
    <div class="error-msg">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>
      <?= htmlspecialchars($loginError) ?>
    </div>
    <?php endif; ?>

    <form method="post">
      <div class="field">
        <label for="usr">Usuário</label>
        <input id="usr" type="text" name="usuario" placeholder="admin" required autocomplete="username">
      </div>
      <div class="field">
        <label for="pwd">Senha</label>
        <input id="pwd" type="password" name="senha" placeholder="••••••••••••" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn-login">Entrar no RosaCRM</button>
    </form>
    <div class="footer-note">🔒 Acesso restrito · Ambiente seguro</div>
  </div>
</div>
</body>
</html>
    <?php
    exit;
}

// ═══════════════════════════════════════════════════════════
//  PAINEL AUTENTICADO
// ═══════════════════════════════════════════════════════════
$pdo = db();

// ── Funil ────────────────────────────────────────────────────
try {
    $funil = $pdo->query('SELECT * FROM vw_funil')->fetch();
} catch (Exception) {
    $funil = ['total_leads'=>0,'com_endereco'=>0,'aprovados'=>0,'pendentes'=>0,'abandonados'=>0,'recusados'=>0,'receita_centavos'=>0];
}

// ── Filtros e paginação ──────────────────────────────────────
$filtroStatus = $_GET['status'] ?? '';
$filtroMetodo = $_GET['metodo'] ?? '';
$busca        = trim($_GET['q'] ?? '');
$pagina       = max(1, (int)($_GET['p'] ?? 1));
$perPage      = 30;
$offset       = ($pagina - 1) * $perPage;

$where = []; $params = [];
if ($filtroStatus !== '') { $where[] = 'status = ?';          $params[] = $filtroStatus; }
if ($filtroMetodo !== '') { $where[] = 'tronfy_metodo = ?';   $params[] = $filtroMetodo; }
if ($busca !== '') {
    $where[] = '(nome LIKE ? OR email LIKE ? OR cpf LIKE ? OR whatsapp LIKE ? OR tronfy_transaction_id LIKE ?)';
    $like = "%$busca%";
    $params = array_merge($params, [$like,$like,$like,$like,$like]);
}
$ws = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmtC = $pdo->prepare("SELECT COUNT(*) FROM crm_vendas $ws");
$stmtC->execute($params);
$total    = (int)$stmtC->fetchColumn();
$totalPag = max(1, (int)ceil($total / $perPage));

$stmtL = $pdo->prepare("SELECT * FROM crm_vendas $ws ORDER BY id DESC LIMIT $perPage OFFSET $offset");
$stmtL->execute($params);
$leads = $stmtL->fetchAll();

// ── Helpers ──────────────────────────────────────────────────
function fmtValor(int $c): string { return 'R$ '.number_format($c/100,2,',','.'); }
function statusBadge(string $s): string {
    return match($s) {
        'Aprovado'   => 'b-ok',
        'Pendente'   => 'b-pend',
        'Abandonado' => 'b-aban',
        'Recusado'   => 'b-rec',
        default      => 'b-init',
    };
}
function qLink(array $extra = []): string {
    $base = array_filter(['q'=>$GLOBALS['busca'],'status'=>$GLOBALS['filtroStatus'],'metodo'=>$GLOBALS['filtroMetodo']]);
    return 'rosacrm.php?' . http_build_query(array_merge($base,$extra));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>RosaCRM — Painel</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ══════════════════════════════════════════════════════════
   DESIGN SYSTEM
══════════════════════════════════════════════════════════ */
:root{
  --bg:      #0a0a0a;
  --s1:      #111111;
  --s2:      #181818;
  --s3:      #222222;
  --border:  rgba(255,255,255,.07);
  --border2: rgba(255,255,255,.04);
  --red:     #ff0000;
  --red-lo:  rgba(255,0,0,.08);
  --red-md:  rgba(255,0,0,.18);
  --text:    #f2f2f2;
  --muted:   rgba(255,255,255,.42);
  --light:   rgba(255,255,255,.22);
  --green:   #00e676;
  --yellow:  #ffd740;
  --orange:  #ff9e40;
  --blue:    #448aff;
  --font:    'Inter',sans-serif;
  --r:       12px;
  --r-lg:    18px;
  --shadow:  0 2px 12px rgba(0,0,0,.4);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--font);background:var(--bg);color:var(--text);min-height:100vh;font-size:14px;line-height:1.5}
a{color:inherit;text-decoration:none}
button{cursor:pointer;font-family:var(--font)}
::-webkit-scrollbar{width:6px;height:6px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:3px}

/* ── Layout ─── */
.shell{display:flex;min-height:100vh}

/* ── Sidebar ─── */
.sidebar{
  width:248px;flex-shrink:0;
  background:var(--s1);
  border-right:1px solid var(--border);
  display:flex;flex-direction:column;
  position:sticky;top:0;height:100vh;overflow-y:auto;
}
.sb-hd{padding:28px 22px 22px;border-bottom:1px solid var(--border)}
.sb-logo{display:flex;align-items:center;gap:12px;margin-bottom:6px}
.sb-icon{
  width:42px;height:42px;
  background:linear-gradient(135deg,#ff0000,#8b0000);
  border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;
  box-shadow:0 0 20px rgba(255,0,0,.35);
}
.sb-icon svg{width:20px;height:20px;fill:#fff}
.sb-name{font-size:19px;font-weight:900;letter-spacing:-.5px}
.sb-name span{color:var(--red)}
.sb-tagline{font-size:10px;color:var(--light);letter-spacing:.06em;margin-top:2px}

.sb-nav{padding:16px 12px;flex:1}
.sb-section{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.14em;color:rgba(255,255,255,.18);padding:16px 10px 8px}
.sb-section:first-child{padding-top:0}
.nav-item{
  display:flex;align-items:center;gap:10px;
  padding:10px 12px;border-radius:10px;
  font-size:13px;font-weight:500;color:var(--muted);
  transition:background .15s,color .15s;margin-bottom:2px;
  position:relative;
}
.nav-item:hover{background:var(--red-lo);color:var(--text)}
.nav-item.active{background:var(--red-md);color:var(--red);font-weight:700}
.nav-item.active::before{content:'';position:absolute;left:0;top:25%;bottom:25%;width:3px;background:var(--red);border-radius:0 3px 3px 0}
.nav-item svg{width:16px;height:16px;flex-shrink:0}
.nav-badge{margin-left:auto;background:var(--red);color:#fff;font-size:10px;font-weight:800;border-radius:6px;padding:1px 7px}

.sb-ft{padding:16px 22px;border-top:1px solid var(--border)}
.sb-user{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.sb-avatar{width:34px;height:34px;border-radius:10px;background:var(--red-md);border:1px solid rgba(255,0,0,.3);display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:800;color:var(--red)}
.sb-uname{font-size:13px;font-weight:600}
.sb-urole{font-size:10px;color:var(--muted)}
.btn-logout{
  width:100%;height:36px;
  background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;
  color:var(--muted);font-size:12px;font-weight:600;
  transition:background .15s,color .15s,border-color .15s;
}
.btn-logout:hover{background:rgba(255,0,0,.12);border-color:rgba(255,0,0,.3);color:var(--red)}

/* ── Main ─── */
.main{flex:1;min-width:0;padding:32px 28px 48px;display:flex;flex-direction:column;gap:24px}

/* ── Topbar ─── */
.topbar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.topbar-left h2{font-size:24px;font-weight:900;letter-spacing:-.5px}
.topbar-left p{font-size:13px;color:var(--muted);margin-top:2px}
.topbar-right{display:flex;align-items:center;gap:10px}
.pill-date{
  background:var(--s2);border:1px solid var(--border);border-radius:8px;
  padding:6px 14px;font-size:12px;color:var(--muted);
}
.pill-live{
  display:flex;align-items:center;gap:6px;
  background:rgba(0,230,118,.08);border:1px solid rgba(0,230,118,.2);border-radius:8px;
  padding:6px 12px;font-size:11px;font-weight:700;color:var(--green);
}
.live-dot{width:7px;height:7px;border-radius:50%;background:var(--green);animation:livePulse 2s ease-in-out infinite}
@keyframes livePulse{0%,100%{opacity:1;box-shadow:0 0 0 0 rgba(0,230,118,.4)}50%{opacity:.7;box-shadow:0 0 0 4px rgba(0,230,118,.0)}}

/* ── Funil Cards ─── */
.funnel{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px}
.fc{
  background:var(--s1);border:1px solid var(--border);border-radius:var(--r-lg);
  padding:20px 18px;position:relative;overflow:hidden;
  transition:border-color .2s,transform .2s;cursor:default;
}
.fc:hover{transform:translateY(-2px)}
.fc::after{content:'';position:absolute;top:0;left:0;right:0;height:2.5px;border-radius:var(--r-lg) var(--r-lg) 0 0}
.fc.c-leads::after{background:var(--muted)}
.fc.c-addr::after{background:var(--blue)}
.fc.c-ok::after{background:var(--green)}
.fc.c-pend::after{background:var(--yellow)}
.fc.c-aban::after{background:var(--orange)}
.fc.c-rec::after{background:var(--red)}
.fc.c-rec:hover{border-color:rgba(255,0,0,.3)}
.fc.c-ok:hover{border-color:rgba(0,230,118,.3)}
.fc.c-receita{background:linear-gradient(135deg,rgba(255,0,0,.05),rgba(0,0,0,0));border-color:rgba(255,0,0,.2)}
.fc.c-receita::after{background:linear-gradient(90deg,#ff0000,#ff6060)}
.fc.c-receita:hover{border-color:rgba(255,0,0,.45)}
.fc-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:10px}
.fc-val{font-size:30px;font-weight:900;letter-spacing:-1px;line-height:1}
.fc.c-ok   .fc-val{color:var(--green)}
.fc.c-pend .fc-val{color:var(--yellow)}
.fc.c-aban .fc-val{color:var(--orange)}
.fc.c-rec  .fc-val{color:var(--red)}
.fc.c-receita .fc-val{color:#ff6060;font-size:22px}
.fc-sub{font-size:11px;color:var(--light);margin-top:6px}
.fc-ico{position:absolute;bottom:14px;right:16px;opacity:.08;font-size:36px;line-height:1}

/* ── Card panel ─── */
.panel{background:var(--s1);border:1px solid var(--border);border-radius:var(--r-lg);overflow:hidden}
.panel-hd{
  display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;
  padding:18px 22px;border-bottom:1px solid var(--border);
}
.panel-title{font-size:16px;font-weight:800}
.panel-count{font-size:12px;color:var(--muted);font-weight:400}

/* ── Filtros ─── */
.filters{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.inp-search{
  height:36px;width:220px;
  background:var(--s2);border:1px solid var(--border);border-radius:8px;
  padding:0 14px;color:var(--text);font-family:var(--font);font-size:13px;
  outline:none;transition:border-color .2s;
}
.inp-search:focus{border-color:rgba(255,0,0,.45)}
.inp-search::placeholder{color:var(--light)}
.sel{
  height:36px;background:var(--s2);border:1px solid var(--border);border-radius:8px;
  padding:0 12px;color:var(--text);font-family:var(--font);font-size:13px;outline:none;cursor:pointer;
}
.btn-f{height:36px;padding:0 18px;background:var(--red);border:none;border-radius:8px;color:#fff;font-family:var(--font);font-size:13px;font-weight:700;transition:filter .15s}
.btn-f:hover{filter:brightness(1.1)}
.btn-cl{height:36px;padding:0 12px;background:var(--s2);border:1px solid var(--border);border-radius:8px;color:var(--muted);font-size:12px;transition:color .15s}
.btn-cl:hover{color:var(--text)}

/* ── Tabela ─── */
.tbl-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:13px}
thead th{
  padding:11px 16px;text-align:left;
  font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;
  color:var(--muted);white-space:nowrap;
  border-bottom:1px solid var(--border);
  background:rgba(255,255,255,.015);
}
tbody tr{border-bottom:1px solid var(--border2);transition:background .1s}
tbody tr:last-child{border-bottom:none}
tbody tr:hover{background:rgba(255,255,255,.025)}
td{padding:12px 16px;vertical-align:middle}

/* cols */
.c-id{color:var(--light);font-size:11px;font-variant-numeric:tabular-nums}
.c-nome strong{font-size:13px;font-weight:700;display:block;margin-bottom:1px}
.c-nome span{font-size:11px;color:var(--muted)}
.c-cpf{font-size:11px;color:var(--muted);white-space:nowrap;font-variant-numeric:tabular-nums}
.c-addr{font-size:11px;color:var(--muted);line-height:1.5;max-width:160px}
.c-orig{font-size:10px;color:var(--light);max-width:110px;word-break:break-all}
.c-orig a{color:var(--blue);text-decoration:none}
.c-val{font-weight:800;color:var(--green);white-space:nowrap}
.c-date{font-size:11px;color:var(--muted);white-space:nowrap;font-variant-numeric:tabular-nums}
.c-pix{max-width:180px}
.pix-pill{
  background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:7px;
  padding:5px 9px;font-size:10px;color:var(--muted);word-break:break-all;
  cursor:pointer;transition:border-color .15s,color .15s;user-select:none;
  display:block;position:relative;overflow:hidden;
}
.pix-pill:hover{border-color:rgba(255,0,0,.4);color:var(--text)}
.pix-pill::after{content:'📋 Copiar';position:absolute;bottom:4px;right:6px;font-size:9px;font-weight:700;color:var(--red);opacity:0;transition:opacity .15s}
.pix-pill:hover::after{opacity:1}

/* badges */
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:800;letter-spacing:.04em;white-space:nowrap}
.b-ok  {background:rgba(0,230,118,.1);color:var(--green);border:1px solid rgba(0,230,118,.25)}
.b-pend{background:rgba(255,215,64,.1);color:var(--yellow);border:1px solid rgba(255,215,64,.25)}
.b-aban{background:rgba(255,158,64,.1);color:var(--orange);border:1px solid rgba(255,158,64,.25)}
.b-rec {background:rgba(255,0,0,.1);color:#ff6060;border:1px solid rgba(255,0,0,.25)}
.b-init{background:rgba(255,255,255,.05);color:var(--muted);border:1px solid var(--border)}
.badge-method{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:800;white-space:nowrap}
.m-pix  {background:rgba(68,138,255,.1);color:var(--blue);border:1px solid rgba(68,138,255,.25)}
.m-card {background:rgba(0,230,118,.1);color:var(--green);border:1px solid rgba(0,230,118,.2)}

/* wpp btn */
.btn-wpp{
  display:inline-flex;align-items:center;gap:6px;
  padding:7px 13px;
  background:rgba(0,230,118,.1);border:1px solid rgba(0,230,118,.25);
  border-radius:9px;color:var(--green);font-size:11px;font-weight:800;
  white-space:nowrap;transition:background .15s,border-color .15s;
}
.btn-wpp:hover{background:rgba(0,230,118,.2);border-color:rgba(0,230,118,.45)}
.btn-wpp svg{width:14px;height:14px;fill:currentColor;flex-shrink:0}
.no-wpp{color:var(--border);font-size:18px}

/* ── Empty ─── */
.tbl-empty{padding:60px 20px;text-align:center;color:var(--muted)}
.tbl-empty .ico{font-size:40px;margin-bottom:12px}
.tbl-empty p{font-size:15px;font-weight:600}
.tbl-empty small{display:block;margin-top:6px;font-size:12px;opacity:.6}

/* ── Paginação ─── */
.pag{display:flex;align-items:center;justify-content:space-between;padding:14px 22px;border-top:1px solid var(--border);font-size:12px;color:var(--muted)}
.pag-links{display:flex;gap:6px}
.pag-a{
  height:32px;min-width:32px;padding:0 10px;
  background:var(--s2);border:1px solid var(--border);border-radius:8px;
  color:var(--muted);font-size:12px;font-weight:600;
  display:flex;align-items:center;justify-content:center;transition:all .15s;
}
.pag-a:hover,.pag-a.act{background:var(--red);border-color:var(--red);color:#fff}

/* ── Toast ─── */
#toast{
  position:fixed;bottom:28px;right:28px;z-index:9999;
  background:var(--s2);border:1px solid var(--border);border-radius:12px;
  padding:12px 20px;font-size:13px;color:var(--green);font-weight:600;
  box-shadow:0 8px 32px rgba(0,0,0,.7);
  transform:translateY(20px);opacity:0;pointer-events:none;
  transition:all .3s cubic-bezier(.34,1.56,.64,1);
}
#toast.show{transform:translateY(0);opacity:1}

/* ── Modal PIX Completo ─── */
.modal-bg{
  position:fixed;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(6px);
  z-index:8000;display:none;align-items:center;justify-content:center;padding:16px;
}
.modal-bg.open{display:flex}
.modal{
  background:var(--s1);border:1px solid var(--border);border-radius:20px;
  padding:32px;max-width:520px;width:100%;
  box-shadow:0 24px 64px rgba(0,0,0,.6);
  position:relative;max-height:90vh;overflow-y:auto;
}
.modal h3{font-size:18px;font-weight:800;margin-bottom:16px}
.modal-close{
  position:absolute;top:18px;right:18px;
  width:30px;height:30px;background:var(--s2);border:1px solid var(--border);
  border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:16px;
  cursor:pointer;transition:color .15s;
}
.modal-close:hover{color:var(--text)}
.pix-full-code{
  background:var(--s2);border:1px solid var(--border);border-radius:10px;
  padding:14px;font-size:11px;color:var(--muted);word-break:break-all;margin-bottom:16px;line-height:1.6;
}
.modal-btn-copy{
  width:100%;height:44px;background:var(--red);border:none;border-radius:10px;
  color:#fff;font-family:var(--font);font-size:14px;font-weight:700;transition:filter .15s;
}
.modal-btn-copy:hover{filter:brightness(1.1)}

/* ── Responsive ─── */
@media(max-width:900px){
  .sidebar{display:none}
  .main{padding:20px 14px 40px}
  .funnel{grid-template-columns:repeat(2,1fr)}
}
</style>
</head>
<body>
<div class="shell">

  <!-- ════════ SIDEBAR ════════ -->
  <aside class="sidebar">
    <div class="sb-hd">
      <div class="sb-logo">
        <div class="sb-icon">
          <svg viewBox="0 0 24 24"><path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z"/></svg>
        </div>
        <div>
          <div class="sb-name">Rosa<span>CRM</span></div>
        </div>
      </div>
      <div class="sb-tagline">PAINEL DE CONTROLE CENTRAL</div>
    </div>

    <nav class="sb-nav">
      <div class="sb-section">Dashboard</div>
      <a href="rosacrm.php" class="nav-item <?= (!$filtroStatus&&!$filtroMetodo&&!$busca)?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
        Dashboard Geral
      </a>

      <div class="sb-section">Por Status</div>
      <a href="rosacrm.php?status=Aprovado" class="nav-item <?= $filtroStatus==='Aprovado'?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
        Aprovados
        <?php if($funil['aprovados']>0): ?><span class="nav-badge"><?= $funil['aprovados'] ?></span><?php endif?>
      </a>
      <a href="rosacrm.php?status=Pendente" class="nav-item <?= $filtroStatus==='Pendente'?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        Pendentes
        <?php if($funil['pendentes']>0): ?><span class="nav-badge"><?= $funil['pendentes'] ?></span><?php endif?>
      </a>
      <a href="rosacrm.php?status=Abandonado" class="nav-item <?= $filtroStatus==='Abandonado'?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        Abandonados
      </a>
      <a href="rosacrm.php?status=Recusado" class="nav-item <?= $filtroStatus==='Recusado'?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>
        Recusados
      </a>

      <div class="sb-section">Por Método</div>
      <a href="rosacrm.php?metodo=PIX" class="nav-item <?= $filtroMetodo==='PIX'?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.06 13.18l-1.12-1.12a3.71 3.71 0 01-1.03.43c.18.26.28.57.28.9 0 .88-.71 1.59-1.59 1.59-.45 0-.86-.18-1.16-.48l-1.12-1.12a3.7 3.7 0 00-.43 1.03l1.12 1.12c.3.3.48.71.48 1.16 0 .88-.71 1.59-1.59 1.59a2.23 2.23 0 01-1.58-.66L7.97 14.76A6.005 6.005 0 006 18c0 3.31 2.69 6 6 6s6-2.69 6-6c0-1.73-.74-3.29-1.94-4.38z"/></svg>
        PIX
      </a>
      <a href="rosacrm.php?metodo=CARTAO" class="nav-item <?= $filtroMetodo==='CARTAO'?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
        Cartão
      </a>
    </nav>

    <div class="sb-ft">
      <div class="sb-user">
        <div class="sb-avatar">A</div>
        <div>
          <div class="sb-uname">Administrador</div>
          <div class="sb-urole">RosaCRM</div>
        </div>
      </div>
      <form method="get">
        <button type="submit" name="logout" value="1" class="btn-logout">⏻ Sair</button>
      </form>
    </div>
  </aside>

  <!-- ════════ MAIN ════════ -->
  <main class="main">

    <!-- Topbar -->
    <div class="topbar">
      <div class="topbar-left">
        <h2>Dashboard</h2>
        <p>Visão geral de leads e transações</p>
      </div>
      <div class="topbar-right">
        <div class="pill-date"><?= date('d/m/Y · H:i') ?></div>
        <div class="pill-live"><div class="live-dot"></div>AO VIVO</div>
      </div>
    </div>

    <!-- ── Funil de Vendas ── -->
    <div class="funnel">
      <div class="fc c-leads">
        <div class="fc-label">Total de Leads</div>
        <div class="fc-val"><?= number_format((int)$funil['total_leads']) ?></div>
        <div class="fc-sub">Todos os contatos</div>
        <div class="fc-ico">👥</div>
      </div>
      <div class="fc c-addr">
        <div class="fc-label">Com Endereço</div>
        <div class="fc-val"><?= number_format((int)$funil['com_endereco']) ?></div>
        <div class="fc-sub">Preencheram entrega</div>
        <div class="fc-ico">📍</div>
      </div>
      <div class="fc c-ok">
        <div class="fc-label">Aprovados</div>
        <div class="fc-val"><?= number_format((int)$funil['aprovados']) ?></div>
        <div class="fc-sub">Pagamento confirmado</div>
        <div class="fc-ico">✅</div>
      </div>
      <div class="fc c-pend">
        <div class="fc-label">Pendentes</div>
        <div class="fc-val"><?= number_format((int)$funil['pendentes']) ?></div>
        <div class="fc-sub">Aguardando pagamento</div>
        <div class="fc-ico">⏳</div>
      </div>
      <div class="fc c-aban">
        <div class="fc-label">Abandonados</div>
        <div class="fc-val"><?= number_format((int)$funil['abandonados']) ?></div>
        <div class="fc-sub">Não finalizaram</div>
        <div class="fc-ico">🚪</div>
      </div>
      <div class="fc c-rec">
        <div class="fc-label">Recusados</div>
        <div class="fc-val"><?= number_format((int)$funil['recusados']) ?></div>
        <div class="fc-sub">Pagamento falhou</div>
        <div class="fc-ico">❌</div>
      </div>
      <div class="fc c-receita" style="grid-column:span 2">
        <div class="fc-label">💰 Receita Total Aprovada</div>
        <div class="fc-val"><?= fmtValor((int)$funil['receita_centavos']) ?></div>
        <div class="fc-sub">Somente transações aprovadas</div>
      </div>
    </div>

    <!-- ── Tabela de Leads ── -->
    <div class="panel">
      <div class="panel-hd">
        <div>
          <div class="panel-title">
            Leads &amp; Transações
            <span class="panel-count">&ensp;<?= $total ?> registro<?= $total!==1?'s':'' ?></span>
          </div>
        </div>
        <div class="filters">
          <form method="get">
            <input class="inp-search" type="text" name="q" placeholder="🔍 Nome, e-mail, CPF…" value="<?= htmlspecialchars($busca) ?>">
            <select class="sel" name="status">
              <option value="">Status</option>
              <?php foreach(['Iniciado','Pendente','Aprovado','Abandonado','Recusado'] as $s): ?>
              <option value="<?=$s?>" <?=$filtroStatus===$s?'selected':''?>><?=$s?></option>
              <?php endforeach ?>
            </select>
            <select class="sel" name="metodo">
              <option value="">Método</option>
              <option value="PIX"    <?=$filtroMetodo==='PIX'?'selected':''?>>PIX</option>
              <option value="CARTAO" <?=$filtroMetodo==='CARTAO'?'selected':''?>>Cartão</option>
            </select>
            <button type="submit" class="btn-f">Filtrar</button>
            <a href="rosacrm.php" class="btn-cl">Limpar</a>
          </form>
        </div>
      </div>

      <div class="tbl-wrap">
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
            <tr><td colspan="11">
              <div class="tbl-empty">
                <div class="ico">🔍</div>
                <p>Nenhum registro encontrado</p>
                <small>Tente ajustar os filtros de busca</small>
              </div>
            </td></tr>
          <?php else: foreach ($leads as $l): ?>
            <tr>
              <td class="c-id"><?= $l['id'] ?></td>
              <td class="c-nome">
                <strong><?= htmlspecialchars($l['nome'] ?: '—') ?></strong>
                <span><?= htmlspecialchars($l['email'] ?: '—') ?></span>
              </td>
              <td class="c-cpf"><?= htmlspecialchars($l['cpf'] ?: '—') ?></td>
              <td class="c-addr">
                <?php if ($l['rua']): ?>
                  <?= htmlspecialchars($l['rua'].', '.$l['numero']) ?>
                  <?php if($l['complemento']): ?><br><?= htmlspecialchars($l['complemento']) ?><?php endif ?>
                  <br><span style="color:var(--light)"><?= htmlspecialchars($l['cidade'].' – '.$l['estado']) ?></span>
                <?php else: ?><span style="color:var(--border)">—</span><?php endif ?>
              </td>
              <td class="c-orig">
                <?php if($l['origem_url']): ?>
                  <a href="<?= htmlspecialchars($l['origem_url']) ?>" target="_blank" title="<?= htmlspecialchars($l['origem_url']) ?>">
                    <?= htmlspecialchars(parse_url($l['origem_url'], PHP_URL_HOST) ?: $l['origem_url']) ?>
                  </a>
                <?php else: ?>—<?php endif ?>
              </td>
              <td>
                <?php if($l['tronfy_metodo']): ?>
                  <span class="badge-method <?= $l['tronfy_metodo']==='PIX'?'m-pix':'m-card' ?>">
                    <?= $l['tronfy_metodo']==='PIX'?'🔵':'💳' ?>
                    <?= $l['tronfy_metodo'] ?>
                  </span>
                <?php else: echo '<span style="color:var(--border)">—</span>'; endif ?>
              </td>
              <td class="c-val"><?= $l['tronfy_valor'] ? fmtValor((int)$l['tronfy_valor']) : '<span style="color:var(--border)">—</span>' ?></td>
              <td><span class="badge <?= statusBadge($l['status']) ?>"><?= $l['status'] ?></span></td>
              <td class="c-pix">
                <?php if($l['tronfy_pix_code']): ?>
                  <div class="pix-pill" onclick="openPixModal(<?= $l['id'] ?>, <?= htmlspecialchars(json_encode($l['tronfy_pix_code']),ENT_QUOTES) ?>)">
                    <?= htmlspecialchars(substr($l['tronfy_pix_code'],0,32)) ?>…
                  </div>
                <?php else: echo '<span style="color:var(--border)">—</span>'; endif ?>
              </td>
              <td>
                <?php
                $wpp = preg_replace('/\D/','',$l['whatsapp']);
                if(strlen($wpp)>=10):
                  $msg = urlencode("Olá {$l['nome']}! Vi que você demonstrou interesse. Posso te ajudar a finalizar?");
                  $href = "https://wa.me/55{$wpp}?text={$msg}";
                ?>
                <a href="<?= $href ?>" target="_blank" class="btn-wpp">
                  <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                  Chamar
                </a>
                <?php else: echo '<span class="no-wpp">—</span>'; endif ?>
              </td>
              <td class="c-date">
                <?= date('d/m/y', strtotime($l['created_at'])) ?><br>
                <span style="opacity:.6"><?= date('H:i', strtotime($l['created_at'])) ?></span>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Paginação -->
      <?php if($totalPag > 1): ?>
      <div class="pag">
        <span>Página <?= $pagina ?> de <?= $totalPag ?></span>
        <div class="pag-links">
          <?php if($pagina > 1): ?>
          <a href="<?= qLink(['p'=>$pagina-1]) ?>" class="pag-a">←</a>
          <?php endif ?>
          <?php
          $start = max(1, $pagina-2); $end = min($totalPag, $pagina+2);
          for($i=$start;$i<=$end;$i++): ?>
          <a href="<?= qLink(['p'=>$i]) ?>" class="pag-a <?= $i===$pagina?'act':'' ?>"><?= $i ?></a>
          <?php endfor ?>
          <?php if($pagina < $totalPag): ?>
          <a href="<?= qLink(['p'=>$pagina+1]) ?>" class="pag-a">→</a>
          <?php endif ?>
        </div>
      </div>
      <?php endif ?>
    </div>

  </main>
</div><!-- /shell -->

<!-- ════ Modal PIX Completo ════ -->
<div class="modal-bg" id="pix-modal" onclick="closeModal(event)">
  <div class="modal">
    <button class="modal-close" onclick="closePixModal()">✕</button>
    <h3>📋 Código PIX Completo</h3>
    <p style="font-size:12px;color:var(--muted);margin-bottom:14px">Lead #<span id="modal-lid"></span></p>
    <div class="pix-full-code" id="modal-code"></div>
    <button class="modal-btn-copy" onclick="copyModalCode()">Copiar código PIX completo</button>
  </div>
</div>

<!-- Toast -->
<div id="toast">✅ Copiado com sucesso!</div>

<script>
// ── Modal PIX ───────────────────────────────────────────────
let _modalCode = '';
function openPixModal(id, code) {
  _modalCode = code;
  document.getElementById('modal-lid').textContent  = id;
  document.getElementById('modal-code').textContent = code;
  document.getElementById('pix-modal').classList.add('open');
}
function closePixModal() {
  document.getElementById('pix-modal').classList.remove('open');
}
function closeModal(e) {
  if (e.target === document.getElementById('pix-modal')) closePixModal();
}
function copyModalCode() {
  navigator.clipboard.writeText(_modalCode).then(showToast);
}

// ── Toast ───────────────────────────────────────────────────
function showToast(msg) {
  const t = document.getElementById('toast');
  if (msg) t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2500);
}

// ── Auto-refresh a cada 60 s (mantém dados ao vivo) ────────
setTimeout(() => location.reload(), 60000);
</script>
</body>
</html>
