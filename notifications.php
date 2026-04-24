<?php
// notifications.php - 100% 完整版 (全局飞鸽传书 - 消息中心)
require_once 'config.php';
require_login();

$user_id = $_SESSION['user_id'];

try {
    $pdo = db_connect();

    // 1. 处理“全部标记为已读”
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
        if (isset($_POST['csrf_token']) && verify_csrf_token($_POST['csrf_token'])) {
            $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$user_id]);
            header("Location: notifications.php");
            exit;
        }
    }

    // 2. 处理单条跳转并标记已读
    if (isset($_GET['read_id']) && isset($_GET['redirect'])) {
        $read_id = intval($_GET['read_id']);
        $redirect = urldecode($_GET['redirect']);
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([$read_id, $user_id]);
        header("Location: " . $redirect);
        exit;
    }

    // 3. 获取最近 50 条消息列表
    $stmt = $pdo->prepare("
        SELECT n.*, u.username, u.avatar 
        FROM notifications n 
        LEFT JOIN users u ON n.sender_id = u.id 
        WHERE n.user_id = ? 
        ORDER BY n.created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// 辅助函数：根据类型解析文案和跳转链接
function get_notification_details($notif) {
    $sender = $notif['username'] ? htmlspecialchars($notif['username']) : 'El Sistema';
    $ref = intval($notif['reference_id']);
    
    switch ($notif['type']) {
        case 'like_post':
            return [
                'icon' => '<i class="fas fa-heart" style="color: var(--nj-red);"></i>',
                'text' => "<strong>{$sender}</strong> ha dado 'Me gusta' a tu tema en el foro.",
                'link' => "view-post.php?id={$ref}"
            ];
        case 'reply_post':
            return [
                'icon' => '<i class="fas fa-comment" style="color: var(--nj-gold);"></i>',
                'text' => "<strong>{$sender}</strong> ha respondido a tu tema.",
                'link' => "view-post.php?id={$ref}#replies"
            ];
        case 'like_article':
            return [
                'icon' => '<i class="fas fa-heart" style="color: var(--nj-red);"></i>',
                'text' => "<strong>{$sender}</strong> ha dado 'Me gusta' a tu guía marcial.",
                'link' => "article.php?id={$ref}"
            ];
        case 'comment_article':
            return [
                'icon' => '<i class="fas fa-comment-alt" style="color: var(--nj-gold);"></i>',
                'text' => "<strong>{$sender}</strong> ha comentado en tu guía.",
                'link' => "article.php?id={$ref}#comments"
            ];
        case 'follow':
            return [
                'icon' => '<i class="fas fa-user-plus" style="color: #28a745;"></i>',
                'text' => "<strong>{$sender}</strong> ha comenzado a seguirte.",
                'link' => "profile.php?user={$notif['sender_id']}"
            ];
        default:
            return [
                'icon' => '<i class="fas fa-bell" style="color: var(--nj-text-muted);"></i>',
                'text' => "Tienes un nuevo mensaje de las sombras.",
                'link' => "dashboard.php"
            ];
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="nj-static-bg"></div>

<div class="nj-container" style="padding: 40px 20px; max-width: 800px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2 style="color: var(--nj-text-main); margin: 0; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-envelope-open-text" style="color: var(--nj-gold);"></i> Centro de Mensajes
        </h2>
        
        <?php if (!empty($notifications)): ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="mark_all_read" value="1">
                <button type="submit" class="nj-btn-secondary" style="font-size: 0.85em; padding: 8px 15px;">
                    <i class="fas fa-check-double"></i> Marcar todo como leído
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="nj-sidebar-card" style="padding: 20px;">
        <?php if (empty($notifications)): ?>
            <div style="text-align: center; padding: 60px 20px; color: var(--nj-text-muted);">
                <i class="fas fa-wind" style="font-size: 3em; opacity: 0.3; margin-bottom: 20px; display: block;"></i>
                <p>No hay mensajes nuevos en tu buzón.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column;">
                <?php foreach ($notifications as $notif): 
                    $details = get_notification_details($notif);
                    $redirect_url = "notifications.php?read_id={$notif['id']}&redirect=" . urlencode($details['link']);
                    $is_unread = ($notif['is_read'] == 0);
                ?>
                    <a href="<?php echo $redirect_url; ?>" class="notif-item <?php echo $is_unread ? 'unread' : ''; ?>">
                        <div class="notif-icon">
                            <?php echo $details['icon']; ?>
                        </div>
                        <div class="notif-content">
                            <div class="notif-text"><?php echo $details['text']; ?></div>
                            <div class="notif-time"><?php echo date('d M Y, H:i', strtotime($notif['created_at'])); ?></div>
                        </div>
                        <?php if ($is_unread): ?>
                            <div class="notif-dot"></div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
:root {
    --nj-bg: #0B0A0A; --nj-module: #161413; --nj-module-hover: #1E1B19;    
    --nj-red: #D12323; --nj-gold: #CCA677; --nj-border: #2D2926;          
    --nj-text-main: #E6E4DF; --nj-text-muted: #8F98A0; 
}
body { background-color: var(--nj-bg) !important; color: var(--nj-text-main); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 0; }
.nj-static-bg { position: fixed; inset: 0; z-index: -10; background-color: var(--nj-bg); background-image: radial-gradient(circle at 10% 20%, rgba(209, 35, 35, 0.04), transparent 50%), radial-gradient(circle at 90% 80%, rgba(204, 166, 119, 0.03), transparent 50%); background-blend-mode: screen; }
.nj-container { margin: 0 auto; min-height: calc(100vh - 100px); }
.nj-sidebar-card { background: var(--nj-module); border: 1px solid var(--nj-border); border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);}
.nj-btn-secondary { display: inline-block; text-align: center; background: transparent; border: 1px solid var(--nj-border); color: var(--nj-text-main); text-decoration: none; border-radius: 4px; transition: 0.2s; cursor: pointer;}
.nj-btn-secondary:hover { background: var(--nj-module-hover); border-color: var(--nj-text-muted); }

.notif-item { display: flex; align-items: center; padding: 18px 15px; border-bottom: 1px solid rgba(45, 41, 38, 0.5); text-decoration: none; transition: background 0.2s; position: relative;}
.notif-item:last-child { border-bottom: none; }
.notif-item:hover { background: rgba(0,0,0,0.3); }
.notif-item.unread { background: rgba(204, 166, 119, 0.03); }
.notif-item.unread:hover { background: rgba(204, 166, 119, 0.08); }

.notif-icon { width: 40px; height: 40px; border-radius: 50%; background: rgba(0,0,0,0.5); border: 1px solid var(--nj-border); display: flex; justify-content: center; align-items: center; font-size: 1.1em; flex-shrink: 0; margin-right: 15px;}
.notif-content { flex: 1; }
.notif-text { color: var(--nj-text-main); font-size: 0.95em; line-height: 1.4; margin-bottom: 5px;}
.notif-time { color: var(--nj-text-muted); font-size: 0.8em; }
.notif-dot { width: 8px; height: 8px; background: var(--nj-red); border-radius: 50%; margin-left: 15px; box-shadow: 0 0 5px rgba(209, 35, 35, 0.5);}
</style>

<?php include 'includes/footer.php'; ?>