<?php
// includes/header.php
$current_page = basename($_SERVER['PHP_SELF']);
$user = current_user();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Naraka Hub - <?php 
        $titles = [
            'index.php' => 'Inicio',
            'forum.php' => 'Foro',
            'guides.php' => 'Guías',
            'wiki.php' => 'Wiki',
            'login.php' => 'Iniciar Sesión',
            'register.php' => 'Registro',
            'dashboard.php' => 'Panel'
        ];
        echo $titles[$current_page] ?? 'Naraka Hub';
    ?></title>
    
    <!-- 样式 -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    
    <style>
        :root {
            --primary: #1a1a2e;
            --secondary: #16213e;
            --accent: #00adb5;
            --light: #eeeeee;
            --success: #28a745;
            --danger: #dc3545;
        }
        
        .system-alert {
            padding: 12px 20px;
            margin: 15px auto;
            max-width: 1200px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid var(--success); }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 4px solid var(--danger); }
        .alert-info { background: #d1ecf1; color: #0c5460; border-left: 4px solid var(--accent); }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">
                <i class="fas fa-gamepad"></i>
                <span>Naraka Hub</span>
            </a>
            
            <div class="nav-main-links">
                <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Inicio
                </a>
                <a href="wiki.php" class="<?php echo $current_page == 'wiki.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i> Wiki
                </a>
                <a href="guides.php" class="<?php echo $current_page == 'guides.php' ? 'active' : ''; ?>">
                    <i class="fas fa-graduation-cap"></i> Guías
                </a>
                <a href="forum.php" class="<?php echo $current_page == 'forum.php' ? 'active' : ''; ?>">
                    <i class="fas fa-comments"></i> Foro
                </a>
            </div>
            
            <div class="nav-auth">
                <?php if (is_logged_in()): ?>
                    <div class="user-dropdown">
                        <span class="user-greeting">
                            <i class="fas fa-user-circle"></i> <?php echo $user['name']; ?>
                        </span>
                        <div class="dropdown-menu">
                            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Panel</a>
                            <a href="profile.php"><i class="fas fa-user-edit"></i> Perfil</a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </a>
                    <a href="register.php" class="btn-register">
                        <i class="fas fa-user-plus"></i> Registrarse
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- 系统消息显示区 -->
    <div id="system-messages">
        <?php
        if (isset($_SESSION['flash_message'])) {
            echo '<div class="system-alert alert-success container">';
            echo '<i class="fas fa-check-circle"></i> ' . $_SESSION['flash_message'];
            echo '</div>';
            unset($_SESSION['flash_message']);
        }
        
        if (isset($_SESSION['flash_error'])) {
            echo '<div class="system-alert alert-error container">';
            echo '<i class="fas fa-exclamation-circle"></i> ' . $_SESSION['flash_error'];
            echo '</div>';
            unset($_SESSION['flash_error']);
        }
        ?>
    </div>

    <!-- 主内容区开始 -->
    <main class="container">