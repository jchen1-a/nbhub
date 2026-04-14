<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$header_username = 'Usuario';
$is_admin = false;

if (isset($_SESSION['user_id'])) {
    try {
        if (function_exists('db_connect')) {
            $pdo_header = db_connect();
            $stmt_header = $pdo_header->prepare("SELECT username, role FROM users WHERE id = ?");
            $stmt_header->execute([$_SESSION['user_id']]);
            $real_user = $stmt_header->fetch();
            
            if ($real_user) {
                $header_username = $real_user['username'];
                $is_admin = ($real_user['role'] === 'admin');
                $_SESSION['user_name'] = $header_username; 
            }
        }
    } catch (Exception $e) {
        $header_username = $_SESSION['user_name'] ?? 'Usuario';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Naraka Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* 强制覆盖及隔离页眉全局样式，同时排除对 <i> 标签（图标）的影响 */
        .nj-global-header, .nj-global-header * {
            box-sizing: border-box;
        }
        .nj-global-header *:not(i) {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
        }
        
        :root {
            --nj-bg: #0B0A0A;
            --nj-module: #161413;
            --nj-module-hover: #1E1B19;
            --nj-red: #D12323;
            --nj-gold: #CCA677;
            --nj-border: #2D2926;
            --nj-text-main: #E6E4DF;
            --nj-text-muted: #8F98A0;
        }

        body {
            background-color: var(--nj-bg);
            color: var(--nj-text-main);
            margin: 0;
            padding: 0;
        }
        
        /* 半透明磨砂页眉 */
        .nj-global-header {
            background-color: rgba(11, 10, 10, 0.85);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.5);
        }
        
        .nj-header-container {
            max-width: 1400px; 
            margin: 0 auto;
            padding: 0 30px;
            display: grid;
            grid-template-columns: 1fr auto 1fr; 
            align-items: center;
            height: 90px; 
        }
        
        /* Logo (已移除手柄) */
        .nj-logo {
            justify-self: start;
            font-size: 1.8em; 
            font-weight: 900;
            color: var(--nj-text-main);
            text-decoration: none;
            letter-spacing: 2px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nj-logo span {
            color: var(--nj-red);
        }
        
        /* 全局导航 */
        .nj-nav-links {
            justify-self: center;
            display: flex;
            gap: 40px; 
            align-items: center;
        }
        
        .nj-nav-links a {
            color: var(--nj-text-muted);
            text-decoration: none;
            font-size: 1.1em; 
            font-weight: 600;
            transition: color 0.2s;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
        }
        
        .nj-nav-links a:hover, .nj-nav-links a.active {
            color: var(--nj-text-main);
        }
        
        .nj-nav-links a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 0;
            background-color: var(--nj-red);
            transition: 0.2s ease;
        }
        
        .nj-nav-links a:hover::after {
            width: 100%;
        }
        
        /* 右侧用户控件 */
        .nj-user-menu {
            justify-self: end;
        }
        
        .nj-user-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .nj-user-btn {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: var(--nj-text-main);
            padding: 12px 24px; 
            border-radius: 4px;
            text-decoration: none;
            font-size: 1.05em; 
            font-weight: 600;
            transition: all 0.2s;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nj-user-btn:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--nj-gold);
        }
        
        .nj-btn-login {
            background: var(--nj-red);
            color: #fff;
            border-color: var(--nj-red);
        }
        
        .nj-btn-login:hover {
            background: #b81c1c;
            border-color: #b81c1c;
            transform: translateY(-1px);
        }

        /* 下拉菜单面板 */
        .nj-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 10px;
            background-color: var(--nj-module);
            min-width: 220px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.6);
            border: 1px solid var(--nj-border);
            border-radius: 6px;
            z-index: 1000;
        }
        
        /* 悬浮桥 (修复下拉丢失 Bug) */
        .nj-dropdown-content::before {
            content: '';
            position: absolute;
            top: -15px;
            left: 0;
            width: 100%;
            height: 15px;
            background: transparent;
        }

        .nj-user-dropdown:hover .nj-dropdown-content {
            display: block;
            animation: fadeIn 0.2s ease;
        }
        
        .nj-dropdown-content a {
            color: var(--nj-text-muted);
            padding: 15px 20px; 
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95em; 
            border-bottom: 1px solid var(--nj-border);
            transition: background 0.2s, color 0.2s;
        }
        
        .nj-dropdown-content a:last-child {
            border-bottom: none;
        }
        
        .nj-dropdown-content a:hover {
            background-color: var(--nj-module-hover);
            color: var(--nj-text-main);
        }
        
        /* 修复下拉菜单图标不显示的问题 */
        .nj-dropdown-content a i {
            width: 18px;
            text-align: center;
            color: var(--nj-text-main); /* 设为高亮度色 */
            transition: color 0.2s;
            display: inline-block;
        }
        
        .nj-dropdown-content a:hover i {
            color: var(--nj-gold);
        }
        
        .nj-dropdown-content a.danger-link:hover, 
        .nj-dropdown-content a.danger-link:hover i {
            color: var(--nj-red);
        }

        .nj-global-alert {
            max-width: 1200px;
            margin: 20px auto 0;
            padding: 15px 20px;
            border-radius: 6px;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nj-alert-success {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #4caf50;
        }
        
        .nj-alert-error {
            background: rgba(209, 35, 35, 0.1);
            border: 1px solid var(--nj-red);
            color: var(--nj-text-main);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 900px) {
            .nj-header-container {
                grid-template-columns: 1fr auto;
            }
            .nj-nav-links { display: none; }
        }
    </style>
</head>
<body>

<header class="nj-global-header">
    <div class="nj-header-container">
        <a href="index.php" class="nj-logo">NARAKA <span>HUB</span></a>
        
        <nav class="nj-nav-links">
            <a href="index.php">Inicio</a>
            <a href="wiki.php">Wiki</a>
            <a href="guides.php">Guías</a>
            <a href="forum.php">Foro</a>
        </nav>
        
        <div class="nj-user-menu">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="nj-user-dropdown">
                    <div class="nj-user-btn">
                        <i class="fas fa-user-circle" style="color: var(--nj-text-muted);"></i> 
                        <?php echo htmlspecialchars($header_username); ?>
                        <i class="fas fa-chevron-down" style="font-size: 0.8em; margin-left: 5px; color: var(--nj-text-muted);"></i>
                    </div>
                    <div class="nj-dropdown-content">
                        <?php if ($is_admin): ?>
                            <a href="admin.php"><i class="fas fa-shield-alt"></i> Panel de Admin</a>
                        <?php endif; ?>
                        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Panel de Usuario</a>
                        <a href="profile.php"><i class="fas fa-id-badge"></i> Mi Perfil</a>
                        <a href="edit-profile.php"><i class="fas fa-user-cog"></i> Editar Perfil</a>
                        <a href="logout.php" class="danger-link"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="nj-user-btn nj-btn-login">Iniciar Sesión</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="nj-global-alert nj-alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="nj-global-alert nj-alert-error">
        <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
    </div>
<?php endif; ?>