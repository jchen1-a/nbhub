<?php
// includes/header.php - 100% 完整版 (包含实时名称同步 & 管理员专属菜单入口)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$header_username = 'Usuario';
$is_admin = false; // 默认不是管理员

if (isset($_SESSION['user_id'])) {
    try {
        if (function_exists('db_connect')) {
            $pdo_header = db_connect();
            // 同时获取 username 和 role
            $stmt_header = $pdo_header->prepare("SELECT username, role FROM users WHERE id = ?");
            $stmt_header->execute([$_SESSION['user_id']]);
            $real_user = $stmt_header->fetch();
            
            if ($real_user) {
                $header_username = $real_user['username'];
                $is_admin = ($real_user['role'] === 'admin'); // 判断是否为管理员
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
        /* 基础全局变量 */
        :root {
            --primary: #1a1a2e;
            --accent: #00adb5;
            --text: #333;
            --bg: #f4f4f4;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
        }
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--bg); color: var(--text); }
        
        /* 导航栏样式 */
        .navbar { background: var(--primary); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .nav-brand { font-size: 1.5em; font-weight: bold; color: var(--accent); text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .nav-links { display: flex; gap: 20px; align-items: center; }
        .nav-links a { color: white; text-decoration: none; font-weight: 500; transition: color 0.3s; display: flex; align-items: center; gap: 5px; }
        .nav-links a:hover { color: var(--accent); }
        
        /* 用户下拉菜单样式 */
        .user-menu { position: relative; display: inline-block; cursor: pointer; }
        .user-menu-btn { display: flex; align-items: center; gap: 8px; color: white; background: rgba(255,255,255,0.1); padding: 8px 15px; border-radius: 20px; transition: background 0.3s; border: 1px solid transparent; }
        .user-menu-btn:hover { background: rgba(255,255,255,0.2); }
        .user-menu-btn.admin-glow { border-color: var(--warning); color: var(--warning); } /* 管理员发光特效 */
        
        .dropdown-content { display: none; position: absolute; right: 0; background-color: white; min-width: 200px; box-shadow: 0px 8px 20px rgba(0,0,0,0.2); z-index: 1000; border-radius: 8px; overflow: hidden; margin-top: 10px; }
        .dropdown-content a { color: var(--text); padding: 12px 16px; text-decoration: none; display: block; font-size: 0.95em; border-bottom: 1px solid #eee; transition: 0.2s; }
        .dropdown-content a:hover { background-color: #f8f9fa; color: var(--accent); padding-left: 20px; }
        .user-menu:hover .dropdown-content { display: block; }
        
        /* 闪存消息提示框 */
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 6px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
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
                            <a href="admin.php" style="background: #fffdf5; color: #d39e00; font-weight: bold; border-bottom: 2px solid #ffebba;">
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
                <a href="login.php" style="background: var(--accent); padding: 8px 20px; border-radius: 20px;">Iniciar Sesión</a>
            <?php endif; ?>
        </div>
    </nav>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div style="max-width: 1200px; margin: 20px auto; padding: 0 20px;">
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['flash_message']; ?>
                <?php unset($_SESSION['flash_message']); ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div style="max-width: 1200px; margin: 20px auto; padding: 0 20px;">
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['flash_error']; ?>
                <?php unset($_SESSION['flash_error']); ?>
            </div>
        </div>
    <?php endif; ?>