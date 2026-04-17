<?php
// article.php - 100% 完整版 (包含点赞系统、评论、视频播放、编辑删除、浅色水墨白灰红版)
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

    // 3. 处理点赞 / 取消点赞
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

<div class="light-theme-bg"></div>

<div class="article-container">
    
    <div class="breadcrumb">
        <a href="guides.php"><i class="fas fa-arrow-left"></i> 归阁 (Volver a Guías)</a>
    </div>

    <article class="article-card">
        
        <header class="article-header">
            <div class="header-tags">
                <span class="cat-badge"><?php echo htmlspecialchars(strtoupper($article['category'])); ?></span>
                <span class="difficulty-indicator <?php echo htmlspecialchars($article['difficulty']); ?>">
                    <?php echo htmlspecialchars(strtoupper($article['difficulty'])); ?>
                </span>
            </div>
            
            <h1 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h1>
            
            <div class="article-meta">
                <span title="Fecha"><i class="far fa-clock"></i> <?php echo date('d/m/Y', strtotime($article['created_at'])); ?></span>
                <span title="Vistas"><i class="fas fa-eye"></i> <?php echo $article['views']; ?></span>
                <span title="Comentarios"><i class="fas fa-comment-dots"></i> <?php echo count($comments); ?></span>
                <span title="Me gusta" class="meta-likes"><i class="fas fa-heart"></i> <?php echo $total_likes; ?></span>
            </div>
        </header>

        <div class="author-box">
            <?php if(!empty($article['avatar'])): ?>
                <img src="<?php echo htmlspecialchars($article['avatar']); ?>" class="author-avatar" alt="Avatar">
            <?php else: ?>
                <i class="fas fa-user-circle author-avatar-placeholder"></i>
            <?php endif; ?>
            <div class="author-info">
                <div class="author-name">
                    执笔 // <a href="profile.php?user=<?php echo $article['user_id']; ?>"><?php echo htmlspecialchars(strtoupper($article['username'])); ?></a>
                    <?php if($user_id == $article['user_id']) echo "<span class='author-is-you'>(Tú)</span>"; ?>
                </div>
            </div>
        </div>

        <?php if(!empty($article['video_path']) || !empty($article['video_url'])): ?>
        <div class="video-container">
            <?php if(!empty($article['video_path'])): ?>
                <video controls>
                    <source src="<?php echo htmlspecialchars($article['video_path']); ?>" type="video/mp4">
                    Tu navegador no soporta video.
                </video>
            <?php elseif($embed = getYoutubeEmbedUrl($article['video_url'])): ?>
                <div class="video-iframe-wrapper">
                    <iframe src="<?php echo $embed; ?>" allowfullscreen></iframe>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="wiki-content-body">
            <?php echo nl2br(htmlspecialchars($article['content'])); ?>
        </div>
        
        <div class="article-actions-bar">
            <div class="action-left">
                <?php if (is_logged_in()): ?>
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="toggle_like" value="1">
                        <button type="submit" class="btn-like <?php echo $user_has_liked ? 'liked' : ''; ?>">
                            <i class="<?php echo $user_has_liked ? 'fas' : 'far'; ?> fa-heart"></i> 
                            <?php echo $user_has_liked ? 'Te gusta' : 'Me gusta'; ?> (<?php echo $total_likes; ?>)
                        </button>
                    </form>
                <?php else: ?>
                    <a href="login.php" class="btn-like" title="Inicia sesión para dar me gusta">
                        <i class="far fa-heart"></i> Me gusta (<?php echo $total_likes; ?>)
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($user_id == $article['user_id']): ?>
                <div class="action-right">
                    <a href="edit-guide.php?id=<?php echo $article['id']; ?>" class="btn-outline">
                        <i class="fas fa-pen"></i> Modificar
                    </a>
                    <a href="delete-guide.php?id=<?php echo $article['id']; ?>" class="btn-outline btn-danger-outline" onclick="return confirm('¿Seguro que quieres borrar este registro?');">
                        <i class="fas fa-trash"></i> Borrar
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="comments-section">
            <h2 class="comments-title">留墨 (Comentarios) <span>[<?php echo count($comments); ?>]</span></h2>

            <?php if (is_logged_in()): ?>
                <div class="comment-form-box">
                    <form method="POST">
                        <textarea name="comment_content" rows="3" required placeholder="写下你的见解..."></textarea>
                        <div class="form-actions">
                            <button type="submit" class="btn-submit-comment"><i class="fas fa-stamp"></i> 落印</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="comment-login-prompt">
                    <p>需推门入阁方可留墨。</p>
                    <a href="login.php" class="btn-outline">推门入阁 (Iniciar Sesión)</a>
                </div>
            <?php endif; ?>

            <div class="comments-list">
                <?php if (empty($comments)): ?>
                    <div class="comments-empty">
                        <p>墨迹未干，暂无留墨。</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-avatar-box">
                                <?php if(!empty($comment['avatar'])): ?>
                                    <img src="<?php echo htmlspecialchars($comment['avatar']); ?>" class="comment-avatar">
                                <?php else: ?>
                                    <i class="fas fa-user-circle comment-avatar-placeholder"></i>
                                <?php endif; ?>
                            </div>
                            <div class="comment-content-box">
                                <div class="comment-meta">
                                    <a href="profile.php?user=<?php echo $comment['user_id']; ?>" class="comment-author">
                                        <?php echo htmlspecialchars(strtoupper($comment['username'])); ?>
                                    </a>
                                    <span class="comment-date"><?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></span>
                                </div>
                                <div class="comment-text">
                                    <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </article>
</div>

<style>
/* ================= 浅色水墨风 (White > Gray > Red) ================= */
@import url('https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;700;900&display=swap');

body {
    background-color: #F7F7F7 !important;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.light-theme-bg {
    position: fixed; inset: 0; z-index: -10;
    background-color: #F7F7F7;
    background-image: radial-gradient(circle at 50% 0%, #FFFFFF 0%, transparent 70%);
}

/* 居中容器 */
.article-container {
    flex: 1; 
    width: 100%;
    max-width: 900px;
    margin: 40px auto 80px auto; 
    padding: 0 20px;
    font-family: 'Noto Serif SC', serif;
    box-sizing: border-box;
}

/* 顶部返回导航 */
.breadcrumb { margin-bottom: 25px; }
.breadcrumb a { color: #888; text-decoration: none; font-size: 0.95em; font-family: sans-serif; font-weight: bold; transition: color 0.3s; letter-spacing: 1px;}
.breadcrumb a:hover { color: #9e1b1b; }

/* 纯白主卡片 */
.article-card {
    background: #FFFFFF;
    padding: 50px 60px;
    border-radius: 4px;
    border: 1px solid rgba(0,0,0,0.06);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.02);
    position: relative;
}
/* 顶部朱砂红细线 */
.article-card::before {
    content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 3px;
    background: #9e1b1b;
}

/* ==== 头部信息 ==== */
.article-header { border-bottom: 1px dashed #E5E5E5; padding-bottom: 25px; margin-bottom: 35px; }
.header-tags { display: flex; gap: 10px; margin-bottom: 20px; align-items: center; }

/* 标签样式 */
.cat-badge { font-family: sans-serif; font-size: 0.75em; letter-spacing: 1px; color: #666; border: 1px solid #ddd; padding: 3px 8px; border-radius: 2px; font-weight: bold; }
.difficulty-indicator { font-family: 'Noto Serif SC', serif; font-size: 0.8em; font-weight: bold; color: #fff; padding: 3px 10px; border-radius: 2px; letter-spacing: 1px; }
/* 水墨难度三阶色: 浅灰 -> 深灰 -> 朱砂红 */
.difficulty-indicator.beginner { background: #AAAAAA; }
.difficulty-indicator.intermediate { background: #555555; }
.difficulty-indicator.advanced { background: #9e1b1b; }

.article-title { color: #222222; font-size: 2.6em; margin: 0 0 20px 0; font-weight: 900; line-height: 1.3; letter-spacing: 1px; word-break: break-word; }

.article-meta { color: #888888; font-size: 0.9em; display: flex; flex-wrap: wrap; gap: 20px; font-family: sans-serif; }
.article-meta i { color: #cccccc; margin-right: 5px; }
.meta-likes { color: #9e1b1b !important; font-weight: bold; }
.meta-likes i { color: #9e1b1b; }

/* ==== 作者框 ==== */
.author-box { display: flex; align-items: center; gap: 15px; margin-bottom: 35px; padding: 15px 20px; background: #fafafa; border-radius: 4px; border: 1px solid #f0f0f0; }
.author-avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd; }
.author-avatar-placeholder { font-size: 45px; color: #dddddd; }
.author-info { flex: 1; }
.author-name { font-size: 1.05em; font-weight: bold; color: #666; letter-spacing: 1px; }
.author-name a { color: #222; text-decoration: none; transition: 0.3s; }
.author-name a:hover { color: #9e1b1b; }
.author-is-you { font-size: 0.8em; color: #9e1b1b; font-family: sans-serif; font-weight: normal; margin-left: 5px;}

/* ==== 视频区 ==== */
.video-container { margin-bottom: 40px; border-radius: 4px; overflow: hidden; background: #000; border: 1px solid #eee; }
.video-container video { width: 100%; max-height: 500px; display: block; }
.video-iframe-wrapper { position: relative; padding-bottom: 56.25%; height: 0; }
.video-iframe-wrapper iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }

/* ==== 正文 ==== */
.wiki-content-body { font-size: 1.15em; line-height: 1.8; color: #333333; min-height: 150px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; margin-bottom: 50px; }

/* ==== 操作栏 ==== */
.article-actions-bar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; padding-top: 25px; border-top: 1px dashed #E5E5E5; margin-bottom: 50px; }
.action-right { display: flex; gap: 10px; }

/* 线框按钮系统 */
.btn-outline, .btn-like { background: transparent; padding: 8px 18px; border-radius: 2px; font-family: sans-serif; font-weight: bold; font-size: 0.9em; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; cursor: pointer; letter-spacing: 1px;}

.btn-outline { border: 1px solid #cccccc; color: #555555; }
.btn-outline:hover { border-color: #222; color: #222; background: rgba(0,0,0,0.03); }

.btn-danger-outline { border-color: #e2a8a8; color: #9e1b1b; }
.btn-danger-outline:hover { border-color: #9e1b1b; color: #fff; background: #9e1b1b; }

.btn-like { border: 1px solid #cccccc; color: #666666; }
.btn-like:hover { border-color: #9e1b1b; color: #9e1b1b; background: rgba(158,27,27,0.03); }
.btn-like.liked { border-color: #9e1b1b; color: #fff; background: #9e1b1b; }
.btn-like.liked:hover { background: #7a1515; border-color: #7a1515; }

/* ==== 评论区 ==== */
.comments-section { padding-top: 20px; }
.comments-title { font-size: 1.5em; color: #222; margin-bottom: 25px; font-weight: 700; letter-spacing: 2px; border-left: 4px solid #9e1b1b; padding-left: 15px; line-height: 1;}
.comments-title span { font-family: sans-serif; font-size: 0.7em; color: #888; font-weight: normal; }

.comment-form-box { margin-bottom: 40px; }
.comment-form-box textarea { width: 100%; box-sizing: border-box; padding: 15px; border: 1px solid #dddddd; border-radius: 2px; font-size: 1em; font-family: inherit; resize: vertical; margin-bottom: 10px; background: #fafafa; outline: none; transition: border-color 0.3s;}
.comment-form-box textarea:focus { border-color: #9e1b1b; background: #fff; }
.form-actions { text-align: right; }
.btn-submit-comment { background: #222; color: #fff; border: 1px solid #222; padding: 10px 25px; border-radius: 2px; font-family: 'Noto Serif SC', serif; font-weight: bold; font-size: 1em; letter-spacing: 2px; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px;}
.btn-submit-comment:hover { background: #9e1b1b; border-color: #9e1b1b; }

.comment-login-prompt { text-align: center; padding: 30px 20px; border: 1px dashed #ccc; background: #fafafa; margin-bottom: 40px; border-radius: 2px; }
.comment-login-prompt p { color: #888; margin-bottom: 15px; letter-spacing: 1px; }

.comments-empty { text-align: center; padding: 40px 0; color: #aaa; letter-spacing: 2px; border-top: 1px dashed #eee; }

.comments-list { display: flex; flex-direction: column; }
.comment-item { display: flex; gap: 20px; padding: 25px 0; border-bottom: 1px solid #f0f0f0; }
.comment-item:last-child { border-bottom: none; padding-bottom: 0; }
.comment-avatar-box { flex-shrink: 0; }
.comment-avatar { width: 45px; height: 45px; border-radius: 50%; border: 1px solid #eee; object-fit: cover; }
.comment-avatar-placeholder { font-size: 45px; color: #e5e5e5; }
.comment-content-box { flex-grow: 1; min-width: 0; }
.comment-meta { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px; }
.comment-author { font-weight: bold; color: #222; text-decoration: none; font-size: 0.9em; letter-spacing: 1px; transition: color 0.2s;}
.comment-author:hover { color: #9e1b1b; }
.comment-date { font-family: sans-serif; font-size: 0.8em; color: #999; }
.comment-text { color: #444; line-height: 1.6; font-size: 1.05em; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; }

/* 响应式调整 */
@media (max-width: 768px) {
    .article-container { margin-top: 20px; padding: 0 15px; }
    .article-card { padding: 30px 20px; }
    .article-title { font-size: 2em; }
    .article-meta { flex-direction: column; gap: 10px; align-items: flex-start; }
    .article-actions-bar { flex-direction: column; align-items: flex-start; }
    .action-right { flex-wrap: wrap; width: 100%; }
    .btn-outline, .btn-like { width: 100%; justify-content: center; box-sizing: border-box; }
    .comment-item { gap: 15px; }
    .comment-avatar, .comment-avatar-placeholder { width: 35px; height: 35px; font-size: 35px; }
}
</style>

<?php include 'includes/footer.php'; ?>