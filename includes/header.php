<?php
// includes/header.php - 100% 完整版 (苍月灰底色 + 彼岸花红 + 狂草毛笔字体)
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
    <link href="https://fonts.googleapis.com/css2?family=Permanent+Marker&family=Cinzel:wght@600;800&display=swap" rel="stylesheet">
    <style>
        /* ================= 核心全局排版 CSS (苍灰+浓墨+猩红) ================= */
        :root {
            --primary: #0a0a0c;  /* 极深浓墨黑 */
            --accent: #c91414;   /* 彼岸花猩红，纯正锐利 */
            --text: #1a1a1a;     /* 常规字体深黑 */
            --bg: #e2e4e9;       /* 苍月灰/天空灰 (完美契合图片背景) */
            --danger: #d32f2f;   /* 危险红 */
            --success: #2e7d32;  /* 沉稳绿 */
            --warning: #f57f17;  /* 琉璃金 */
        }
        
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--bg); color: var(--text); line-height: 1.6; }
        
        /* 狂草毛笔特效字体 (用于Logo和主标题) */
        .brush-font {
            font-family: 'Permanent Marker', cursive;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        /* 锐利武侠字体 (用于副标题和普通面板) */
        h1, h2, h3 {
            font-family: 'Cinzel', serif;
            font-weight: 800;
            color: var(--primary);
        }

        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        a { text-decoration: none; color: var(--accent); transition: 0.3s; }
        a:hover { color: #8a0b0b; }
        
        /* 卡片风格统一为白底+浅色阴影，凸显水墨质感 */
        .card, .wiki-card, .post-card { background: white; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px; overflow: hidden; border: 1px solid #d1d4d8; }
        .card-header { padding: 15px 20px; border-bottom: 1px solid #eee; background: #fafafa; margin: 0; }
        .card-body { padding: 20px; }
        
        /* 按钮使用纯正的猩红 */
        .btn, .btn-primary { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 20px; background: var(--accent); color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; transition: 0.3s; text-decoration: none; font-size: 1em; text-transform: uppercase; letter-spacing: 1px; }
        .btn:hover, .btn-primary:hover { background: #8a0b0b; color: white; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(201, 20, 20, 0.4); }
        .btn-outline { background: transparent; color: var(--accent); border: 2px solid var(--accent); }
        .btn-outline:hover { background: var(--accent); color: white; }

        /* ================= 导航栏样式 ================= */
        .navbar { background: var(--primary); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.4); border-bottom: 3px solid var(--accent); }
        .nav-brand { font-size: 2.2em; color: #fff; text-shadow: 2px 2px 0px var(--accent); } /* 白色毛笔字+猩红阴影 */
        .nav-links { display: flex; gap: 20px; align-items: center; }
        .nav-links a { color: #e2e4e9; text-decoration: none; font-weight: bold; transition: color 0.3s; display: flex; align-items: center; gap: 6px; text-transform: uppercase; font-family: 'Cinzel', serif; letter-spacing: 1px; }
        .nav-links a:hover { color: var(--accent); }
        
        .user-menu { position: relative; display: inline-block; cursor: pointer; }
        .user-menu::after { content: ''; position: absolute; top: 100%; left: 0; width: 100%; height: 15px; background: transparent; z-index: 999; }

        .user-menu-btn { display: flex; align-items: center; gap: 8px; color: white; background: rgba(255,255,255,0.08); padding: 8px 15px; border-radius: 4px; transition: 0.3s; border: 1px solid rgba(255,255,255,0.1); font-family: 'Segoe UI', sans-serif; }
        .user-menu-btn:hover { background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.3); }
        .user-menu-btn.admin-glow { border-color: var(--warning); color: var(--warning); }
        
        .dropdown-content { display: none; position: absolute; right: 0; top: calc(100% + 10px); background-color: white; min-width: 200px; box-shadow: 0px 8px 25px rgba(0,0,0,0.2); z-index: 1000; border-radius: 4px; overflow: hidden; border: 1px solid #ccc; font-family: 'Segoe UI', sans-serif; }
        .dropdown-content a { color: var(--text); padding: 12px 16px; text-decoration: none; display: block; font-size: 0.95em; border-bottom: 1px solid #eee; transition: 0.2s; text-transform: none; font-family: 'Segoe UI', sans-serif; font-weight: 500;}
        .dropdown-content a:hover { background-color: #f5f5f5; color: var(--accent); padding-left: 20px; border-left: 4px solid var(--accent); }
        .user-menu:hover .dropdown-content { display: block; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; border-left: 5px solid; }
        .alert-success { background: white; color: var(--success); border-left-color: var(--success); box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .alert-error { background: white; color: var(--accent); border-left-color: var(--accent); box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="nav-brand brush-font">Naraka Hub</a>
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
                            <a href="admin.php" style="background: rgba(245, 127, 23, 0.1); color: #f57f17; font-weight: bold; border-bottom: 1px solid #eee;">
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
                <a href="login.php" style="background: var(--accent); padding: 8px 20px; border-radius: 4px; color: white; font-family: 'Segoe UI', sans-serif;">Iniciar Sesión</a>
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