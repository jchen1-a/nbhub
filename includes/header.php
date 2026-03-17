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
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;800&family=Noto+Serif+SC:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0a0a0c;
            --accent: #c91414;
            --text: #1a1a1a;
            --bg: #e2e4e9;
            --danger: #d32f2f;
            --success: #2e7d32;
            --warning: #f57f17;
        }
        
        * { box-sizing: border-box; }
        
        body { 
            margin: 0; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: var(--bg); 
            color: var(--text); 
            line-height: 1.6; 
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Cinzel', 'Noto Serif SC', serif;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: 0.5px;
        }

        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        
        a { 
            text-decoration: none; 
            color: var(--accent); 
            transition: all 0.3s ease; 
        }

        .card, .wiki-card, .post-card { 
            background: white; 
            border-radius: 2px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.04); 
            margin-bottom: 25px; 
            overflow: hidden; 
            border: 1px solid rgba(0,0,0,0.05); 
        }
        
        .card-header { 
            padding: 20px 25px; 
            border-bottom: 1px solid rgba(0,0,0,0.04); 
            background: #fff; 
            margin: 0; 
        }
        
        .card-body { 
            padding: 25px; 
        }
        
        .btn, .btn-primary { 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            gap: 8px; 
            padding: 10px 24px; 
            background: var(--accent); 
            color: white; 
            border: 1px solid var(--accent); 
            border-radius: 2px; 
            cursor: pointer; 
            font-family: 'Cinzel', 'Noto Serif SC', serif; 
            font-weight: 600; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            text-decoration: none; 
            font-size: 0.95em; 
            letter-spacing: 1px; 
            text-transform: uppercase;
        }
        
        .btn:hover, .btn-primary:hover { 
            background: #900a0a; 
            border-color: #900a0a;
            box-shadow: 0 4px 15px rgba(201, 20, 20, 0.3); 
            transform: translateY(-1px); 
        }
        
        .btn-outline { 
            background: transparent; 
            color: var(--accent); 
            border: 1px solid var(--accent); 
        }
        
        .btn-outline:hover { 
            background: var(--accent); 
            color: white; 
            box-shadow: 0 4px 15px rgba(201, 20, 20, 0.2);
        }

        .navbar { 
            background: rgba(10, 10, 12, 0.95); 
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 0 40px; 
            height: 75px;
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.3); 
            border-bottom: 1px solid rgba(255, 255, 255, 0.05); 
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-brand { 
            font-family: 'Cinzel', 'Noto Serif SC', serif; 
            font-size: 1.6em; 
            font-weight: 800; 
            color: #fff; 
            letter-spacing: 3px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: color 0.3s;
        }
        
        .nav-brand i {
            color: var(--accent);
            font-size: 0.9em;
        }
        
        .nav-brand:hover {
            color: #ddd;
        }
        
        .nav-links { 
            display: flex; 
            gap: 35px; 
            align-items: center; 
            height: 100%;
        }
        
        .nav-links > a { 
            color: #999; 
            text-decoration: none; 
            font-family: 'Cinzel', 'Noto Serif SC', serif; 
            font-weight: 600; 
            font-size: 0.85em; 
            letter-spacing: 1.5px; 
            text-transform: uppercase;
            display: flex; 
            align-items: center; 
            gap: 8px; 
            height: 100%;
            position: relative;
            transition: color 0.3s;
        }
        
        .nav-links > a i {
            font-size: 1.1em;
            margin-top: -2px;
            color: #777;
            transition: color 0.3s;
        }
        
        .nav-links > a:hover { 
            color: #fff; 
        }
        
        .nav-links > a:hover i {
            color: var(--accent);
        }
        
        .nav-links > a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--accent);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateX(-50%);
        }
        
        .nav-links > a:hover::after {
            width: 100%;
        }
        
        .user-menu { 
            position: relative; 
            display: inline-flex; 
            align-items: center;
            height: 100%;
            cursor: pointer; 
        }
        
        .user-menu::after { 
            content: ''; 
            position: absolute; 
            top: 100%; 
            left: 0; 
            width: 100%; 
            height: 20px; 
            background: transparent; 
            z-index: 999; 
        }

        .user-menu-btn { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            color: #fff; 
            background: transparent; 
            padding: 8px 16px; 
            border-radius: 2px; 
            transition: all 0.3s; 
            border: 1px solid rgba(255,255,255,0.1); 
            font-family: 'Cinzel', 'Noto Serif SC', serif;
            font-weight: 600;
            letter-spacing: 1px;
            font-size: 0.85em;
        }
        
        .user-menu:hover .user-menu-btn { 
            border-color: var(--accent);
            background: rgba(201, 20, 20, 0.05); 
        }
        
        .user-menu-btn.admin-glow { 
            border-color: rgba(245, 127, 23, 0.4); 
            color: #ffeb3b; 
        }
        
        .user-menu:hover .user-menu-btn.admin-glow { 
            background: rgba(245, 127, 23, 0.08); 
            border-color: #f57f17; 
        }
        
        .dropdown-content { 
            display: none; 
            position: absolute; 
            right: 0; 
            top: calc(100% - 5px); 
            background-color: rgba(12, 12, 14, 0.98); 
            backdrop-filter: blur(10px);
            min-width: 220px; 
            box-shadow: 0px 10px 30px rgba(0,0,0,0.5); 
            z-index: 1000; 
            border-radius: 2px; 
            border: 1px solid rgba(255,255,255,0.05);
            overflow: hidden; 
            animation: fadeIn 0.2s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .dropdown-content a { 
            color: #bbb; 
            padding: 14px 20px; 
            text-decoration: none; 
            display: block; 
            font-size: 0.9em; 
            border-bottom: 1px solid rgba(255,255,255,0.03); 
            transition: all 0.2s; 
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }
        
        .dropdown-content a i {
            margin-right: 10px;
            width: 16px;
            text-align: center;
            color: #666;
            transition: color 0.2s;
        }
        
        .dropdown-content a:hover { 
            background-color: rgba(201, 20, 20, 0.08); 
            color: #fff; 
            padding-left: 26px; 
            border-left: 2px solid var(--accent); 
        }
        
        .dropdown-content a:hover i {
            color: var(--accent);
        }
        
        .user-menu:hover .dropdown-content { 
            display: block; 
        }

        .nav-login-btn {
            background: transparent;
            color: var(--accent);
            border: 1px solid var(--accent);
            padding: 8px 24px;
            border-radius: 2px;
            font-family: 'Cinzel', 'Noto Serif SC', serif;
            font-weight: 600;
            letter-spacing: 1px;
            font-size: 0.85em;
            text-transform: uppercase;
            transition: all 0.3s ease;
        }

        .nav-login-btn:hover {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 0 15px rgba(201, 20, 20, 0.3);
        }
        
        .alert { 
            padding: 15px 20px; 
            margin-bottom: 25px; 
            border-radius: 2px; 
            font-weight: 600; 
            border-left: 4px solid; 
            animation: slideIn 0.3s ease-out; 
            font-family: 'Segoe UI', sans-serif;
        }
        
        @keyframes slideIn { 
            from { transform: translateX(-20px); opacity: 0; } 
            to { transform: translateX(0); opacity: 1; } 
        }
        
        .alert-success { 
            background: #fff; 
            color: var(--success); 
            border-left-color: var(--success); 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        }
        
        .alert-error { 
            background: #fff; 
            color: var(--accent); 
            border-left-color: var(--accent); 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        }

        @media (max-width: 900px) {
            .navbar { padding: 0 20px; height: 65px; }
            .nav-brand { font-size: 1.3em; letter-spacing: 2px; }
            .nav-links > a { display: none; }
            .nav-links > a.show-mobile { display: flex; }
        }
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
                        <i class="fas fa-chevron-down" style="font-size: 0.8em; margin-left: 4px;"></i>
                    </div>
                    <div class="dropdown-content">
                        <?php if ($is_admin): ?>
                            <a href="admin.php" style="color: #f57f17; border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <i class="fas fa-shield-alt"></i> Panel de Admin
                            </a>
                        <?php endif; ?>
                        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Panel de Usuario</a>
                        <a href="profile.php"><i class="fas fa-id-badge"></i> Mi Perfil</a>
                        <a href="edit-profile.php"><i class="fas fa-user-cog"></i> Editar Perfil</a>
                        <a href="logout.php" style="color: #ff4444;"><i class="fas fa-sign-out-alt" style="color: #ff4444;"></i> Cerrar Sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="nav-login-btn show-mobile">Iniciar Sesión</a>
            <?php endif; ?>
        </div>
    </nav>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="container">
            <div class="alert alert-success">
                <i class="fas fa-check-circle" style="margin-right: 8px;"></i> <?php echo $_SESSION['flash_message']; ?>
                <?php unset($_SESSION['flash_message']); ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="container">
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i> <?php echo $_SESSION['flash_error']; ?>
                <?php unset($_SESSION['flash_error']); ?>
            </div>
        </div>
    <?php endif; ?>