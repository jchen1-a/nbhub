<?php
// article.php - 100% 完整版 (包含点赞系统、评论、视频播放、编辑删除、防炸版CSS)
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = is_logged_in() ? $_SESSION['user_id'] : 0;

try {
    $pdo = db_connect();
    
    // 1. 获取攻略主体信息
    $stmt = $pdo->prepare("SELECT a.*, u.username, u.country, u.avatar FROM articles a LEFT JOIN users u ON a.user_id = u.id WHERE a.id = ?");
    $stmt->execute([$id]);
    $article = $stmt->fetch();
    
    if (!$article) {
        header("HTTP/1.0 404 Not Found");
        die("La guía no existe.");
    }

    // 2. 智能防刷浏览量
    if ($user_id != $article['user_id'] && (!isset($_SESSION['viewed_articles']) || !in_array($id, $_SESSION['viewed_articles']))) {
        $pdo->prepare("UPDATE articles SET views = views + 1 WHERE id = ?")->execute([$id]);
        $article['views']++;
        $_SESSION['viewed_articles'][] = $id;
    }

    // 3. 处理点赞 / 取消点赞 (新功能)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_like']) && is_logged_in()) {
        $check_like = $pdo->prepare("SELECT 1 FROM article_likes WHERE article_id = ? AND user_id = ?");
        $check_like->execute([$id, $user_id]);
        if ($check_like->fetch()) {
            // 已经点赞则取消
            $pdo->prepare("DELETE FROM article_likes WHERE article_id = ? AND user_id = ?")->execute([$id, $user_id]);
        } else {
            // 未点赞则添加
            $pdo->prepare("INSERT IGNORE INTO article_likes (article_id, user_id, created_at) VALUES (?, ?, NOW())")->execute([$id, $user_id]);
        }
        // 重定向防止表单重复提交
        header("Location: article.php?id=" . $id);
        exit();
    }

    // 4. 处理用户提交的新评论
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content']) && is_logged_in()) {
        $comment_content = trim($_POST['comment_content']);
        if (!empty($comment_content)) {
            $insert_comment = $pdo->prepare("INSERT INTO article_comments (article_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
            $insert_comment->execute([$id, $user_id, $comment_content]);
            $_SESSION['flash_message'] = "¡Comentario publicado con éxito!";
            header("Location: article.php?id=" . $id);
            exit();
        }
    }

    // 5. 获取所有评论
    $comments_stmt = $pdo->prepare("
        SELECT c.*, u.username, u.avatar 
        FROM article_comments c 
        LEFT JOIN users u ON c.user_id = u.id 
        WHERE c.article_id = ? 
        ORDER BY c.created_at DESC
    ");
    $comments_stmt->execute([$id]);
    $comments = $comments_stmt->fetchAll();

    // 6. 获取点赞总数及当前用户是否已点赞
    $likes_count = $pdo->prepare("SELECT COUNT(*) FROM article_likes WHERE article_id = ?");
    $likes_count->execute([$id]);
    $total_likes = $likes_count->fetchColumn();

    $user_has_liked = false;
    if (is_logged_in()) {
        $like_check_stmt = $pdo->prepare("SELECT 1 FROM article_likes WHERE article_id = ? AND user_id = ?");
        $like_check_stmt->execute([$id, $user_id]);
        $user_has_liked = (bool)$like_check_stmt->fetch();
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// 辅助函数：转换YouTube链接为嵌入代码
function getYoutubeEmbedUrl($url) {
    if (preg_match('/[\\?\\&]v=([^\\?\\&]+)/', $url, $matches)) return "https://www.youtube.com/embed/" . $matches[1];
    if (preg_match('/youtu\\.be\\/([^\\?\\&]+)/', $url, $matches)) return "https://www.youtube.com/embed/" . $matches[1];
    return null;
}
?>
<?php include 'includes/header.php'; ?>

<div class="container" style="padding: 40px 20px; max-width: 900px; margin: 0 auto;">
    
    <div class="article-header" style="background: #f8f9fa; padding: 30px; border-radius: 12px; border-left: 5px solid var(--accent); margin-bottom: 30px;">
        <div style="display:flex; gap:10px; margin-bottom:15px;">
            <span style="background:#e9ecef; padding:5px 12px; border-radius:20px; font-size:0.85em; font-weight:bold; color:#555; text-transform: uppercase;">
                <?php echo htmlspecialchars($article['category']); ?>
            </span>
            <span class="difficulty-indicator <?php echo htmlspecialchars($article['difficulty']); ?>" style="padding:5px 12px; border-radius:20px; font-size:0.85em; font-weight:bold; color:white; text-transform: uppercase;">
                <?php echo htmlspecialchars($article['difficulty']); ?>
            </span>
        </div>
        
        <h1 style="color: var(--primary); font-size: 2.5em; margin: 0 0 20px 0; line-height: 1.2; word-break: break-word; overflow-wrap: anywhere;">
            <?php echo htmlspecialchars($article['title']); ?>
        </h1>
        
        <div style="display:flex; flex-wrap:wrap; gap:20px; color:#666; font-size:0.9em; align-items: center;">
            <span><i class="far fa-clock"></i> <?php echo date('d/m/Y', strtotime($article['created_at'])); ?></span>
            <span><i class="fas fa-eye"></i> <?php echo $article['views']; ?> vistas</span>
            <span><i class="fas fa-comment"></i> <?php echo count($comments); ?> comentarios</span>
            <span style="color: var(--danger); font-weight: bold;"><i class="fas fa-heart"></i> <?php echo $total_likes; ?> me gusta</span>
        </div>
    </div>

    <div class="author-card" style="display: flex; align-items: center; gap: 15px; background: white; padding: 15px 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
        <?php if(!empty($article['avatar'])): ?>
            <img src="<?php echo htmlspecialchars($article['avatar']); ?>" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
        <?php else: ?>
            <i class="fas fa-user-circle" style="font-size: 50px; color: #ccc;"></i>
        <?php endif; ?>
        <div>
            <div style="font-weight:bold; font-size:1.1em; color:var(--primary);">
                Por <a href="profile.php?user=<?php echo $article['user_id']; ?>" style="color:var(--accent); text-decoration:none;"><?php echo htmlspecialchars($article['username']); ?></a>
                <?php if($user_id == $article['user_id']) echo "<span style='font-size:0.8em; color:#00adb5; margin-left: 5px;'>(Eres el autor)</span>"; ?>
            </div>
        </div>
    </div>

    <?php if(!empty($article['video_path']) || !empty($article['video_url'])): ?>
    <div style="margin-bottom: 30px; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
        <?php if(!empty($article['video_path'])): ?>
            <video controls style="width: 100%; max-height: 500px; background: #000;">
                <source src="<?php echo htmlspecialchars($article['video_path']); ?>" type="video/mp4">
                Tu navegador no soporta video.
            </video>
        <?php elseif($embed = getYoutubeEmbedUrl($article['video_url'])): ?>
            <div style="position: relative; padding-bottom: 56.25%; height: 0;">
                <iframe src="<?php echo $embed; ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;" allowfullscreen></iframe>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="article-content-box">
        <?php echo nl2br(htmlspecialchars($article['content'])); ?>
    </div>
    
    <div style="margin-top: 30px; margin-bottom: 50px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        
        <a href="guides.php" style="padding: 10px 20px; border: 2px solid #ddd; border-radius: 8px; color: #333; text-decoration:none; font-weight:bold;"><i class="fas fa-arrow-left"></i> Volver a Guías</a>
        
        <div style="display: flex; gap: 10px; align-items: center;">
            <?php if (is_logged_in()): ?>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="toggle_like" value="1">
                    <button type="submit" class="btn-like <?php echo $user_has_liked ? 'liked' : ''; ?>">
                        <i class="<?php echo $user_has_liked ? 'fas' : 'far'; ?> fa-heart"></i> 
                        <?php echo $user_has_liked ? 'Te gusta' : 'Me gusta'; ?> (<?php echo $total_likes; ?>)
                    </button>
                </form>
            <?php else: ?>
                <a href="login.php" class="btn-like" title="Inicia sesión para dar me gusta"><i class="far fa-heart"></i> Me gusta (<?php echo $total_likes; ?>)</a>
            <?php endif; ?>

            <?php if ($user_id == $article['user_id']): ?>
                <a href="edit-guide.php?id=<?php echo $article['id']; ?>" class="btn-edit">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="delete-guide.php?id=<?php echo $article['id']; ?>" class="btn-danger" onclick="return confirm('¿Seguro que quieres eliminar esta guía?');">
                    <i class="fas fa-trash"></i> Eliminar
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="comments-section" style="border-top: 2px solid #eee; padding-top: 40px;">
        <h2 style="color: var(--primary); margin-bottom: 25px;"><i class="fas fa-comments"></i> Comentarios (<?php echo count($comments); ?>)</h2>

        <?php if (is_logged_in()): ?>
            <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 40px;">
                <form method="POST">
                    <textarea name="comment_content" rows="3" required placeholder="¿Qué opinas de esta guía? Únete a la discusión..." style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 15px; font-family: inherit; resize: vertical; margin-bottom: 15px;"></textarea>
                    <div style="text-align: right;">
                        <button type="submit" style="padding: 10px 25px; background: var(--accent); color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s;"><i class="fas fa-paper-plane"></i> Publicar Comentario</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 40px; border: 1px dashed #ccc;">
                <p style="color: #666; margin-bottom: 10px;">Debes iniciar sesión para dejar un comentario.</p>
                <a href="login.php" style="display: inline-block; padding: 8px 20px; background: var(--accent); color: white; text-decoration: none; border-radius: 20px; font-weight: bold;">Iniciar Sesión</a>
            </div>
        <?php endif; ?>

        <div class="comments-list">
            <?php if (empty($comments)): ?>
                <div style="text-align: center; padding: 30px; color: #999;">
                    <i class="far fa-comment-dots" style="font-size: 3em; margin-bottom: 15px; color: #ddd;"></i>
                    <p>Aún no hay comentarios. ¡Sé el primero en compartir tu opinión!</p>
                </div>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div style="display: flex; gap: 15px; margin-bottom: 25px; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                        <div style="flex-shrink: 0;">
                            <?php if(!empty($comment['avatar'])): ?>
                                <img src="<?php echo htmlspecialchars($comment['avatar']); ?>" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-user-circle" style="font-size: 45px; color: #ccc;"></i>
                            <?php endif; ?>
                        </div>
                        <div style="flex-grow: 1;">
                            <div style="display: flex; justify-content: space-between; align-items:baseline; margin-bottom: 8px;">
                                <a href="profile.php?user=<?php echo $comment['user_id']; ?>" style="font-weight: bold; color: var(--primary); text-decoration: none; font-size: 1.05em;">
                                    <?php echo htmlspecialchars($comment['username']); ?>
                                </a>
                                <span style="font-size: 0.85em; color: #888;"><i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></span>
                            </div>
                            <div style="color: #444; line-height: 1.6; word-break: break-word; overflow-wrap: anywhere;">
                                <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.article-content-box { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); font-size: 1.1em; line-height: 1.8; color: #333; min-height: 200px; white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-all; word-wrap: break-word; }
.difficulty-indicator.beginner { background: #28a745; }
.difficulty-indicator.intermediate { background: #ffc107; color: #333 !important; }
.difficulty-indicator.advanced { background: #dc3545; }

.btn-edit { padding: 10px 20px; background: #00adb5; color: white; border: 1px solid #00adb5; border-radius: 8px; text-decoration:none; font-weight:bold; transition: 0.2s; display: flex; align-items: center; gap: 8px; cursor:pointer;}
.btn-edit:hover { background: #008f96; color: white; }
.btn-danger { padding: 10px 20px; background: white; border: 1px solid #dc3545; color: #dc3545; border-radius: 8px; text-decoration:none; font-weight:bold; transition: 0.2s; display: flex; align-items: center; gap: 8px; cursor:pointer;}
.btn-danger:hover { background: #dc3545; color: white; }

/* 点赞按钮样式 */
.btn-like { padding: 10px 20px; background: white; color: var(--danger); border: 2px solid var(--danger); border-radius: 8px; text-decoration:none; font-weight:bold; transition: 0.2s; display: flex; align-items: center; gap: 8px; cursor:pointer; font-size: 1em; }
.btn-like:hover { background: #fff5f5; }
.btn-like.liked { background: var(--danger); color: white; }
.btn-like.liked:hover { background: #c82333; border-color: #c82333; }

button[type="submit"]:hover { background: #008f96 !important; }
.btn-like[type="submit"]:hover { background: #fff5f5 !important; }
.btn-like.liked[type="submit"]:hover { background: #c82333 !important; }
</style>

<?php include 'includes/footer.php'; ?>