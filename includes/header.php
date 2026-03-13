<?php
// includes/header.php - 100% 完整版 (水墨武林风：纯正曼珠沙华红)
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
        /* ================= 核心全局排版 CSS ================= */
        :root {
            /* 主配色方案：纯净水墨黑 + 曼珠沙华红 */
            --primary: #111111;  /* 极深的水墨黑 */
            --accent: #cc0000;   /* 曼珠沙华红（彼岸花红），纯正锐利 */
            --text: #222222;     /* 常规字体黑 */
            --bg: #f5f5f5;       /* 干净的宣纸白 */
            --danger: #ff1744;   /* 亮红色，用于极其醒目的危险操作 */
            --success: #2e7d32;  /* 沉稳的绿色 */
            --warning: #ffb300;  /* 琉璃金 */
        }
        
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--bg); color: var(--text); line-height: 1.6; }
        
        /* 全局容器与布局 */
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        a { text-decoration: none; color: var(--accent); transition: 0.3s; }
        a:hover { color: #a30000; } /* 悬停时稍微加深如凝血 */
        
        /* 全局卡片样式 */
        .card, .wiki-card, .post-card { background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.06); margin-bottom: 25px; overflow: hidden; border: 1px solid rgba(0,0,0,0.03); }
        .card-header { padding: 15px 20px; border-bottom: 1px solid #eee; background: #fafafa; margin: 0; }
        .card-body { padding: 20px; }
        
        /* 全局按钮样式 */
        .btn, .btn-primary { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 20px; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.3s; text-decoration: none; font-size: 1em; }
        .btn:hover, .btn-primary:hover { background: #e60000; color: white; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(204, 0, 0, 0.4); }
        .btn-outline { background: white; color: var(--accent); border: 2px solid var(--accent); }
        .btn-outline:hover { background: var(--accent); color: white; }

        /* ================= 导航栏样式 ================= */
        .navbar { background: var(--primary); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.3); border-bottom: 2px solid #222; }
        .nav-brand { font-size: 1.5em; font-weight: bold; color: var(--accent); text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .nav-links { display: flex; gap: 20px; align-items: center; }
        .nav-links a { color: #eee; text-decoration: none; font-weight: 500; transition: color 0.3s; display: flex; align-items: center; gap: 5px; }
        .nav-links a:hover { color: var(--accent); }
        
        /* 用户下拉菜单样式 (已修复空隙Bug) */
        .user-menu { position: relative; display: inline-block; cursor: pointer; }
        
        /* 桥梁修复空隙 */
        .user-menu::after {
            content: ''; position: absolute; top: 100%; left: 0; width: 100%; height: 15px; background: transparent; z-index: 999;
        }

        .user-menu-btn { display: flex; align-items: center; gap: 8px; color: white; background: rgba(255,255,255,0.05); padding: 8px 15px; border-radius: 20px; transition: background 0.3s; border: 1px solid rgba(255,255,255,0.1); }
        .user-menu-btn:hover { background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.3); }
        .user-menu-btn.admin-glow { border-color: var(--warning); color: var(--warning); }
        
        .dropdown-content { display: none; position: absolute; right: 0; top: calc(100% + 10px); background-color: white; min-width: 200px; box-shadow: 0px 8px 25px rgba(0,0,0,0.15); z-index: 1000; border-radius: 8px; overflow: hidden; border: 1px solid #eee; }
        .dropdown-content a { color: var(--text); padding: 12px 16px; text-decoration: none; display: block; font-size: 0.95em; border-bottom: 1px solid #f5f5f5; transition: 0.2s; }
        .dropdown-content a:hover { background-color: #fafafa; color: var(--accent); padding-left: 20px; }
        .user-menu:hover .dropdown-content { display: block; }
        
        /* 闪存消息提示框 */
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 6px; font-weight: bold; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .alert-error { background: #ffebee; color: #cc0000; border: 1px solid #ffcdd2; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="nav-brand"><i class="fas fa-gamepad"></i> Naraka Hub</a>
        <div class="nav-links">
            <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
            <a href="wiki.php"><i class="fas fa-book"></i> Wiki</a>
            <a href="guides.php"><i class="fas fa-graduation-cap"></i> Guías</a>
            <a href="forum.php"><i class="fas fa-comments"></i> Foro</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-menu">
                    <div class="user-menu-btn <?php echo $is_admin ? 'admin-glow' : ''; ?>">
                        <i class="fas <?php echo $is_admin ? 'fa-user-shield' : 'fa-user-circle'; ?>"></i> 
                        <span><?php echo htmlspecialchars($header_username); ?></span>
                        <i class="fas fa-chevron-down" style="font-size: 0.8em;"></i>
                    </div>
                    <div class="dropdown-content">
                        <?php if ($is_admin): ?>
                            <a href="admin.php" style="background: rgba(255, 179, 0, 0.05); color: #d49a00; font-weight: bold; border-bottom: 2px solid #ffebba;">
                                <i class="fas fa-shield-alt"></i> Panel de Admin
                            </a>
                        <?php endif; ?>
                        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Panel de Usuario</a>
                        <a href="profile.php"><i class="fas fa-id-badge"></i> Mi Perfil</a>
                        <a href="edit-profile.php"><i class="fas fa-user-cog"></i> Editar Perfil</a>
                        <a href="logout.php" style="color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" style="background: var(--accent); padding: 8px 20px; border-radius: 20px; color: white;">Iniciar Sesión</a>
            <?php endif; ?>
        </div>
    </nav>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="container">
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['flash_message']; ?>
                <?php unset($_SESSION['flash_message']); ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="container">
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['flash_error']; ?>
                <?php unset($_SESSION['flash_error']); ?>
            </div>
        </div>
    <?php endif; ?>