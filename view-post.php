<?php
// view-post.php - 浏览帖子及回复
require_once 'config.php';

$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = is_logged_in() ? $_SESSION['user_id'] : 0;

try {
    $pdo = db_connect();

    // 1. 智能防刷浏览量统计
    if (!isset($_SESSION['viewed_posts'])) $_SESSION['viewed_posts'] = [];
    $should_count = true;
    
    // 2. 获取主贴内容
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.country 
        FROM forum_posts p 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        header("HTTP/1.0 404 Not Found");
        die("El tema no existe o ha sido eliminado.");
    }

    if ($user_id == $post['user_id'] || in_array($post_id, $_SESSION['viewed_posts'])) {
        $should_count = false;
    }
    
    if ($should_count) {
        $pdo->prepare("UPDATE forum_posts SET views = views + 1 WHERE id = ?")->execute([$post_id]);
        $post['views']++;
        $_SESSION['viewed_posts'][] = $post_id;
    }

    // 3. 处理回复提交
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
        $reply_content = trim($_POST['reply_content'] ?? '');
        
        if (!empty($reply_content)) {
            // 插入回复
            $replyStmt = $pdo->prepare("INSERT INTO forum_replies (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
            $replyStmt->execute([$post_id, $user_id, $reply_content]);
            
            // 更新主帖子的最后回复时间和回复人
            $updatePost = $pdo->prepare("UPDATE forum_posts SET last_reply_at = NOW(), last_reply_by = ? WHERE id = ?");
            $updatePost->execute([$user_id, $post_id]);
            
            $_SESSION['flash_message'] = 'Respuesta publicada.';
            header("Location: view-post.php?id=" . $post_id);
            exit();
        }
    }

    // 4. 获取所有回复
    $repliesStmt = $pdo->prepare("
        SELECT r.*, u.username, u.country 
        FROM forum_replies r 
        LEFT JOIN users u ON r.user_id = u.id 
        WHERE r.post_id = ? 
        ORDER BY r.created_at ASC
    ");
    $repliesStmt->execute([$post_id]);
    $replies = $repliesStmt->fetchAll();

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<?php include 'includes/header.php'; ?>

<div class="container" style="padding: 20px; max-width: 1000px;">
    
    <div style="margin-bottom: 20px; color: #666;">
        <a href="forum.php" style="color: var(--accent); text-decoration: none;"><i class="fas fa-home"></i> Foro</a> 
        &raquo; <?php echo htmlspecialchars(ucfirst($post['category'])); ?>
    </div>

    <div class="post-card original-post">
        <div class="post-user-info">
            <i class="fas fa-user-circle avatar"></i>
            <span class="username"><?php echo htmlspecialchars($post['username']); ?></span>
            <?php if($post['user_id'] == $user_id): ?><span class="badge" style="background:var(--accent);color:white;font-size:0.7em;">Tú</span><?php endif; ?>
        </div>
        <div class="post-body">
            <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            <div class="post-meta">
                <span><i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></span>
                <span><i class="fas fa-eye"></i> <?php echo $post['views']; ?> vistas</span>
            </div>
            <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">
            <div class="post-text">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>
        </div>
    </div>

    <?php if (count($replies) > 0): ?>
        <h3 style="margin: 30px 0 15px; color:#333;"><?php echo count($replies); ?> Respuestas</h3>
        <?php foreach ($replies as $index => $reply): ?>
            <div class="post-card reply">
                <div class="post-user-info">
                    <i class="fas fa-user-circle avatar"></i>
                    <span class="username"><?php echo htmlspecialchars($reply['username']); ?></span>
                    <?php if($reply['user_id'] == $post['user_id']): ?>
                        <span class="badge" style="background:#17a2b8;color:white;font-size:0.7em;">Autor</span>
                    <?php endif; ?>
                </div>
                <div class="post-body">
                    <div class="post-meta">
                        <span>#<?php echo $index + 1; ?></span>
                        <span><i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($reply['created_at'])); ?></span>
                    </div>
                    <div class="post-text">
                        <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="reply-form-container">
        <?php if (is_logged_in()): ?>
            <h3><i class="fas fa-reply"></i> Añadir Respuesta</h3>
            <form method="POST">
                <textarea name="reply_content" rows="4" required placeholder="Escribe tu respuesta aquí..." 
                          style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-family:inherit; margin-bottom: 15px;"></textarea>
                <button type="submit" style="padding: 10px 25px; background: var(--success); color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">Responder</button>
            </form>
        <?php else: ?>
            <div style="text-align:center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <i class="fas fa-lock" style="font-size:2em; color:#ddd; margin-bottom:10px;"></i>
                <p>Debes <a href="login.php" style="color:var(--accent);">iniciar sesión</a> o <a href="register.php" style="color:var(--accent);">registrarte</a> para responder.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<style>
/* 帖子专用样式，解决长文本排版问题 */
.post-card {
    display: flex;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 20px;
    overflow: hidden;
}

.original-post { border-left: 4px solid var(--primary); }
.reply { border-left: 4px solid #ddd; }

.post-user-info {
    width: 200px;
    background: #f8f9fa;
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    border-right: 1px solid #eee;
}

.avatar { font-size: 4em; color: #ccc; margin-bottom: 10px; }
.username { font-weight: bold; color: #333; text-align: center; }

.post-body {
    flex: 1;
    padding: 20px 30px;
}

.post-title { font-size: 1.8em; color: var(--primary); margin-top:0; }

.post-meta {
    font-size: 0.85em;
    color: #888;
    display: flex;
    gap: 15px;
}

.post-text {
    line-height: 1.6;
    color: #333;
    font-size: 1.05em;
    overflow-wrap: break-word; /* 防止长字符串炸版 */
    word-wrap: break-word;
    word-break: break-word;
    min-height: 100px;
}

.badge {
    padding: 2px 6px;
    border-radius: 4px;
    display: inline-block;
    margin-top: 5px;
}

.reply-form-container {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    margin-top: 30px;
}

@media (max-width: 768px) {
    .post-card { flex-direction: column; }
    .post-user-info { width: 100%; flex-direction: row; border-right: none; border-bottom: 1px solid #eee; padding: 15px; gap: 15px; }
    .avatar { font-size: 2.5em; margin-bottom: 0; }
    .post-body { padding: 15px; }
}
</style>

<?php include 'includes/footer.php'; ?>