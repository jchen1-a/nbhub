<?php
// view-post.php - 浏览帖子 (包含编辑/删除入口)
require_once 'config.php';

$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = is_logged_in() ? $_SESSION['user_id'] : 0;

try {
    $pdo = db_connect();

    // 1. 获取主贴内容
    $stmt = $pdo->prepare("SELECT p.*, u.username, u.avatar FROM forum_posts p LEFT JOIN users u ON p.user_id = u.id WHERE p.id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) die("El tema no existe.");

    // 2. 增加浏览量
    if (!isset($_SESSION['viewed_posts'])) $_SESSION['viewed_posts'] = [];
    if ($user_id != $post['user_id'] && !in_array($post_id, $_SESSION['viewed_posts'])) {
        $pdo->prepare("UPDATE forum_posts SET views = views + 1 WHERE id = ?")->execute([$post_id]);
        $post['views']++;
        $_SESSION['viewed_posts'][] = $post_id;
    }

    // 3. 处理回复
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
        $reply_content = trim($_POST['reply_content'] ?? '');
        if (!empty($reply_content)) {
            $pdo->prepare("INSERT INTO forum_replies (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())")->execute([$post_id, $user_id, $reply_content]);
            $pdo->prepare("UPDATE forum_posts SET last_reply_at = NOW(), last_reply_by = ? WHERE id = ?")->execute([$user_id, $post_id]);
            $_SESSION['flash_message'] = 'Respuesta publicada.';
            header("Location: view-post.php?id=" . $post_id);
            exit();
        }
    }

    // 4. 获取回复
    $replies = $pdo->prepare("SELECT r.*, u.username, u.avatar FROM forum_replies r LEFT JOIN users u ON r.user_id = u.id WHERE r.post_id = ? ORDER BY r.created_at ASC");
    $replies->execute([$post_id]);
    $replies = $replies->fetchAll();

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<?php include 'includes/header.php'; ?>
<div class="container" style="padding: 20px; max-width: 1000px; margin:0 auto;">
    
    <div style="margin-bottom: 20px; color: #666;">
        <a href="forum.php" style="color: var(--accent); text-decoration: none;"><i class="fas fa-home"></i> Foro</a> &raquo; <?php echo htmlspecialchars(ucfirst($post['category'])); ?>
    </div>

    <div class="post-card" style="display:flex; background:white; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.05); margin-bottom:20px; border-left:4px solid var(--primary);">
        <div style="width:180px; background:#f8f9fa; padding:20px; text-align:center; border-right:1px solid #eee;">
            <?php if($post['avatar']): ?>
                <img src="<?php echo htmlspecialchars($post['avatar']); ?>" style="width:80px; height:80px; border-radius:50%; object-fit:cover; margin-bottom:10px;">
            <?php else: ?>
                <i class="fas fa-user-circle" style="font-size:4em; color:#ccc; margin-bottom:10px;"></i>
            <?php endif; ?>
            <div style="font-weight:bold; color:#333;"><?php echo htmlspecialchars($post['username']); ?></div>
        </div>
        <div style="flex:1; padding:20px 30px; display:flex; flex-direction:column;">
            <h1 style="font-size:1.8em; color:var(--primary); margin:0 0 10px 0;"><?php echo htmlspecialchars($post['title']); ?></h1>
            <div style="font-size:0.85em; color:#888; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:15px;">
                <i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?> | <i class="fas fa-eye"></i> <?php echo $post['views']; ?> vistas
            </div>
            <div style="flex:1; line-height:1.6; font-size:1.05em; overflow-wrap:break-word; color:#333;">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>
            
            <?php if($user_id == $post['user_id']): ?>
            <div style="margin-top:20px; text-align:right; border-top:1px dashed #eee; padding-top:15px;">
                <a href="edit-post.php?id=<?php echo $post['id']; ?>" style="color:var(--accent); text-decoration:none; margin-right:15px; font-weight:bold;"><i class="fas fa-edit"></i> Editar</a>
                <a href="delete-post.php?id=<?php echo $post['id']; ?>" style="color:#dc3545; text-decoration:none; font-weight:bold;" onclick="return confirm('¿Seguro que quieres eliminar este tema? Se borrarán todas las respuestas.');"><i class="fas fa-trash"></i> Eliminar</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php foreach ($replies as $index => $reply): ?>
        <div class="post-card" style="display:flex; background:white; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.05); margin-bottom:15px; border-left:4px solid #ddd;">
            <div style="width:180px; background:#f8f9fa; padding:20px; text-align:center; border-right:1px solid #eee;">
                <?php if($reply['avatar']): ?>
                    <img src="<?php echo htmlspecialchars($reply['avatar']); ?>" style="width:60px; height:60px; border-radius:50%; object-fit:cover; margin-bottom:10px;">
                <?php else: ?>
                    <i class="fas fa-user-circle" style="font-size:3.5em; color:#ccc; margin-bottom:10px;"></i>
                <?php endif; ?>
                <div style="font-weight:bold; color:#333; font-size:0.9em;"><?php echo htmlspecialchars($reply['username']); ?></div>
                <?php if($reply['user_id'] == $post['user_id']) echo "<span style='background:#17a2b8; color:white; font-size:0.7em; padding:2px 5px; border-radius:3px;'>Autor</span>"; ?>
            </div>
            <div style="flex:1; padding:20px;">
                <div style="font-size:0.8em; color:#888; margin-bottom:10px;">#<?php echo $index + 1; ?> - <?php echo date('d/m/Y H:i', strtotime($reply['created_at'])); ?></div>
                <div style="line-height:1.6; overflow-wrap:break-word; color:#333;"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></div>
            </div>
        </div>
    <?php endforeach; ?>

    <div style="background:white; padding:30px; border-radius:10px; margin-top:30px; box-shadow:0 5px 15px rgba(0,0,0,0.05);">
        <?php if (is_logged_in()): ?>
            <h3 style="margin-top:0;"><i class="fas fa-reply"></i> Añadir Respuesta</h3>
            <form method="POST">
                <textarea name="reply_content" rows="4" required placeholder="Escribe tu respuesta aquí..." style="width:100%; padding:15px; border:2px solid #ddd; border-radius:8px; resize:vertical; margin-bottom:15px;"></textarea>
                <button type="submit" style="padding:10px 25px; background:var(--success,#28a745); color:white; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Responder</button>
            </form>
        <?php else: ?>
            <div style="text-align:center; padding:20px;">
                <p>Debes <a href="login.php" style="color:var(--accent);">iniciar sesión</a> para responder.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>