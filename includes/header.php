<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$header_username = 'Usuario';
$is_admin = false;
$unread_notifications = 0;

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
                if (function_exists('get_unread_notifications_count')) {
                    $unread_notifications = get_unread_notifications_count($_SESSION['user_id']);
                }
            }
        }
    } catch (Exception $e) { $header_username = $_SESSION['user_name'] ?? 'Usuario'; }
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
        :root { --nj-bg: #0B0A0A; --nj-module: #161413; --nj-red: #D12323; --nj-gold: #CCA677; --nj-border: #2D2926; --nj-text-main: #E6E4DF; --nj-text-muted: #8F98A0; }
        .nj-global-header { background: rgba(11, 10, 10, 0.95); backdrop-filter: blur(10px); border-bottom: 1px solid var(--nj-border); position: sticky; top: 0; z-index: 1000; padding: 12px 0; }
        .nj-nav-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; }
        .nj-logo { color: var(--nj-text-main); font-size: 1.4em; font-weight: 800; text-decoration: none; letter-spacing: 1px; flex-shrink: 0; }
        .nj-logo span { color: var(--nj-red); }

        /* 主导航：Wiki 和 Guías 归位 */
        .nj-main-nav { display: flex; gap: 25px; align-items: center; }
        .nj-main-nav a { color: var(--nj-text-muted); text-decoration: none; font-size: 0.9em; font-weight: 700; transition: 0.3s; letter-spacing: 1px; display: flex; align-items: center; gap: 8px; text-transform: uppercase;}
        .nj-main-nav a i { color: var(--nj-gold); font-size: 1em; }
        .nj-main-nav a:hover { color: var(--nj-text-main); text-shadow: 0 0 8px rgba(204, 166, 119, 0.4); }

        .nj-user-actions { display: flex; align-items: center; gap: 18px; }
        .nj-notif-bell { position: relative; color: var(--nj-text-muted); font-size: 1.2em; text-decoration: none; transition: 0.2s; padding: 5px; }
        .nj-notif-bell:hover { color: var(--nj-gold); }
        .nj-notif-dot { position: absolute; top: 0; right: 0; width: 8px; height: 8px; background: var(--nj-red); border-radius: 50%; border: 2px solid var(--nj-bg); animation: pulse-red 2s infinite; }
        @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(209, 35, 35, 0.7); } 70% { box-shadow: 0 0 0 6px rgba(209, 35, 35, 0); } 100% { box-shadow: 0 0 0 0 rgba(209, 35, 35, 0); } }

        .nj-dropdown { position: relative; display: inline-block; padding-bottom: 15px; margin-bottom: -15px; }
        .nj-user-btn { background: var(--nj-module); border: 1px solid var(--nj-border); color: var(--nj-text-main); padding: 6px 15px; border-radius: 4px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.85em; transition: 0.2s; }
        .nj-user-btn:hover { border-color: var(--nj-gold); }
        .nj-dropdown-content { display: none; position: absolute; right: 0; top: 100%; background: var(--nj-module); min-width: 180px; border: 1px solid var(--nj-border); border-radius: 4px; box-shadow: 0 8px 16px rgba(0,0,0,0.5); z-index: 1001; overflow: hidden; }
        .nj-dropdown:hover .nj-dropdown-content { display: block; }
        .nj-dropdown-content a { color: var(--nj-text-muted); padding: 12px 16px; text-decoration: none; display: block; font-size: 0.85em; transition: 0.2s; border-bottom: 1px solid rgba(45, 41, 38, 0.5); }
        .nj-dropdown-content a:hover { background: var(--nj-module-hover); color: var(--nj-text-main); padding-left: 20px; }
        .nj-btn-login { background: var(--nj-red); color: #fff; padding: 7px 18px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.85em; }

        .nj-global-alert { max-width: 1200px; margin: 15px auto 0 auto; padding: 15px 20px; border-radius: 4px; font-weight: bold; font-size: 0.9em; display: flex; align-items: center; gap: 10px; box-sizing: border-box; }
        .nj-alert-success { background: rgba(40, 167, 69, 0.1); border: 1px solid #28a745; color: var(--nj-text-main); }
        .nj-alert-error { background: rgba(209, 35, 35, 0.1); border: 1px solid var(--nj-red); color: var(--nj-text-main); }
    </style>
</head>
<body>
<header class="nj-global-header">
    <div class="nj-nav-container">
        <a href="index.php" class="nj-logo">NARAKA<span>HUB</span></a>
        
        <nav class="nj-main-nav">
            <a href="index.php"><i class="fas fa-home"></i> <span>Inicio</span></a>
            <a href="wiki.php"><i class="fas fa-book"></i> <span>Wiki</span></a>
            <a href="guides.php"><i class="fas fa-scroll"></i> <span>Guías</span></a>
            <a href="forum.php"><i class="fas fa-comments"></i> <span>Foro</span></a>
        </nav>
        
        <div class="nj-user-actions">
            <?php if (is_logged_in()): ?>
                <a href="notifications.php" class="nj-notif-bell"><i class="fas fa-bell"></i>
                    <?php if ($unread_notifications > 0): ?><span class="nj-notif-dot"></span><?php endif; ?>
                </a>
                <div class="nj-dropdown">
                    <div class="nj-user-btn">
                        <i class="fas fa-user-ninja" style="color: <?php echo $is_admin ? 'var(--nj-red)' : 'var(--nj-gold)'; ?>;"></i>
                        <span><?php echo htmlspecialchars($header_username); ?></span>
                        <i class="fas fa-chevron-down" style="font-size: 0.7em; opacity: 0.5;"></i>
                    </div>
                    <div class="nj-dropdown-content">
                        <?php if ($is_admin): ?><a href="admin.php"><i class="fas fa-shield-alt"></i> Admin</a><?php endif; ?>
                        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                        <a href="profile.php"><i class="fas fa-id-badge"></i> Mi Perfil</a>
                        <a href="edit-profile.php"><i class="fas fa-cog"></i> Ajustes</a>
                        <a href="logout.php" style="color:var(--nj-red)"><i class="fas fa-sign-out-alt"></i> Salir</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="nj-btn-login">LOGIN</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="nj-global-alert nj-alert-success"><i class="fas fa-check-circle"></i> <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="nj-global-alert nj-alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>