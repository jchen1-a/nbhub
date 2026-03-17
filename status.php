<?php
// status.php - 100% 完整版 (武林水墨风 - 服务器状态监控中心)
require_once 'config.php';

// 1. 测算数据库连接延迟
$db_status = 'Desconectado';
$db_ping = 0;
$start_time = microtime(true);

try {
    $pdo = db_connect();
    $end_time = microtime(true);
    $db_ping = round(($end_time - $start_time) * 1000); // 毫秒
    $db_status = 'Conectado';
    
    // 2. 获取数据脉络 (统计信息)
    $stats = [
        'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'articles' => $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn(),
        'forum_posts' => $pdo->query("SELECT COUNT(*) FROM forum_posts")->fetchColumn(),
        'comments' => $pdo->query("SELECT COUNT(*) FROM article_comments")->fetchColumn() + $pdo->query("SELECT COUNT(*) FROM forum_replies")->fetchColumn()
    ];
} catch (Exception $e) {
    $stats = ['users' => 0, 'articles' => 0, 'forum_posts' => 0, 'comments' => 0];
    $db_status = 'Error';
}

// 3. 获取系统核心参数
$server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido';
$php_version = phpversion();
$server_time = date('d/m/Y H:i:s');
$timezone = date_default_timezone_get();
$session_status = (session_status() === PHP_SESSION_ACTIVE) ? 'Activa' : 'Inactiva';

?>
<?php include 'includes/header.php'; ?>

<div class="hero-section" style="background: linear-gradient(135deg, rgba(10, 10, 12, 0.8) 0%, rgba(201, 20, 20, 0.7) 100%), url('assets/cover.jpg') no-repeat center 20%; background-size: cover; padding: 80px 20px 100px; border-bottom: 5px solid var(--primary); text-align: center;">
    <div class="container">
        <h1 style="font-family: 'Cinzel', serif; font-size: 3.5em; color: white; margin: 0 0 10px 0; text-shadow: 2px 2px 10px rgba(0,0,0,0.8); text-transform: uppercase;">Estado del Sistema</h1>
        <p style="font-family: 'Segoe UI', sans-serif; font-size: 1.2em; color: #eee; font-weight: bold; letter-spacing: 1px; text-shadow: 1px 1px 5px rgba(0,0,0,0.8);">
            Monitorización en tiempo real del núcleo de Naraka Hub.
        </p>
    </div>
</div>

<div class="container" style="max-width: 1000px; margin: -50px auto 50px auto; position: relative; z-index: 10;">
    
    <div class="status-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px;">
        
        <div class="status-card">
            <div class="status-icon"><i class="fas fa-database"></i></div>
            <div class="status-info">
                <h3>Base de Datos</h3>
                <div class="status-value <?php echo $db_status === 'Conectado' ? 'text-success' : 'text-danger'; ?>">
                    <?php echo $db_status; ?>
                </div>
                <div class="status-meta">Latencia: <?php echo $db_ping; ?> ms</div>
            </div>
        </div>

        <div class="status-card">
            <div class="status-icon"><i class="fab fa-php"></i></div>
            <div class="status-info">
                <h3>Motor PHP</h3>
                <div class="status-value text-normal">v<?php echo $php_version; ?></div>
                <div class="status-meta">Intérprete principal</div>
            </div>
        </div>

        <div class="status-card">
            <div class="status-icon"><i class="fas fa-shield-alt"></i></div>
            <div class="status-info">
                <h3>Sesión Local</h3>
                <div class="status-value <?php echo $session_status === 'Activa' ? 'text-success' : 'text-danger'; ?>">
                    <?php echo $session_status; ?>
                </div>
                <div class="status-meta">Autenticación de usuario</div>
            </div>
        </div>

        <div class="status-card">
            <div class="status-icon"><i class="far fa-clock"></i></div>
            <div class="status-info">
                <h3>Reloj del Servidor</h3>
                <div class="status-value text-normal"><?php echo date('H:i:s'); ?></div>
                <div class="status-meta">Zona: <?php echo $timezone; ?></div>
            </div>
        </div>

    </div>

    <div class="card" style="border-top: 4px solid var(--primary);">
        <div class="card-header" style="background: white; border-bottom: 2px solid #eee;">
            <h2 style="margin: 0; font-family: 'Cinzel', serif; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-server" style="color: var(--accent);"></i> Detalles del Servidor
            </h2>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="detail-row">
                <span class="detail-label">Software del Servidor</span>
                <span class="detail-value"><?php echo htmlspecialchars($server_software); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Protocolo</span>
                <span class="detail-value"><?php echo htmlspecialchars($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Método de Petición Actual</span>
                <span class="detail-value"><?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'GET'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Fecha y Hora Exacta</span>
                <span class="detail-value"><?php echo $server_time; ?></span>
            </div>
        </div>
    </div>

    <div class="card" style="border-top: 4px solid var(--accent); margin-top: 30px;">
        <div class="card-header" style="background: white; border-bottom: 2px solid #eee;">
            <h2 style="margin: 0; font-family: 'Cinzel', serif; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-network-wired" style="color: var(--primary);"></i> Volumen de Datos
            </h2>
        </div>
        <div class="card-body" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; text-align: center; padding: 30px 20px;">
            <div class="data-block">
                <div class="data-num"><?php echo $stats['users']; ?></div>
                <div class="data-label">Usuarios</div>
            </div>
            <div class="data-block">
                <div class="data-num"><?php echo $stats['articles']; ?></div>
                <div class="data-label">Guías</div>
            </div>
            <div class="data-block">
                <div class="data-num"><?php echo $stats['forum_posts']; ?></div>
                <div class="data-label">Temas</div>
            </div>
            <div class="data-block">
                <div class="data-num"><?php echo $stats['comments']; ?></div>
                <div class="data-label">Interacciones</div>
            </div>
        </div>
    </div>

</div>

<style>
/* ================= 监控中心专属样式 ================= */
.status-card { background: var(--primary); color: white; padding: 25px 20px; border-radius: 8px; display: flex; align-items: center; gap: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border: 1px solid #222; border-bottom: 4px solid var(--accent); transition: 0.3s; }
.status-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(201, 20, 20, 0.2); }
.status-icon { font-size: 2.8em; color: var(--accent); opacity: 0.9; }
.status-info h3 { margin: 0 0 5px 0; font-size: 1em; font-family: 'Segoe UI', sans-serif; color: #aaa; text-transform: uppercase; letter-spacing: 1px; }
.status-value { font-size: 1.5em; font-weight: bold; font-family: 'Cinzel', serif; margin-bottom: 2px; }
.status-meta { font-size: 0.85em; color: #777; font-family: 'Segoe UI', sans-serif; }

/* 文字颜色状态 */
.text-success { color: #4caf50; }
.text-danger { color: #f44336; }
.text-normal { color: white; }

/* 详细信息列表 */
.detail-row { display: flex; justify-content: space-between; padding: 15px 25px; border-bottom: 1px solid #f0f0f0; transition: 0.2s; }
.detail-row:hover { background: #fafafa; border-left: 4px solid var(--primary); padding-left: 21px; }
.detail-row:last-child { border-bottom: none; }
.detail-label { font-weight: bold; color: #555; font-family: 'Segoe UI', sans-serif; }
.detail-value { color: var(--accent); font-family: 'Courier New', Courier, monospace; font-weight: bold; background: #f9f9f9; padding: 2px 8px; border-radius: 4px; border: 1px solid #eee; }

/* 数据量统计卡片 */
.data-block { background: #fbfbfb; padding: 25px; border-radius: 8px; border: 1px solid #eee; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
.data-num { font-size: 3em; font-weight: 800; font-family: 'Cinzel', serif; color: var(--primary); line-height: 1; margin-bottom: 10px; }
.data-label { font-weight: bold; color: #888; text-transform: uppercase; letter-spacing: 1px; font-size: 0.9em; }

@media (max-width: 768px) {
    .detail-row { flex-direction: column; gap: 5px; }
    .detail-value { align-self: flex-start; }
}
</style>

<?php include 'includes/footer.php'; ?>