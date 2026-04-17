<?php
// view-post.php - 100% 完整版 (点赞/收藏无刷新功能 + 浅色水墨白灰红版)
require_once 'config.php';

$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = is_logged_in() ? $_SESSION['user_id'] : 0;

try {
    $pdo = db_connect();

    // ========================================================
    // 1. 处理 AJAX 异步请求 (点赞/收藏)
    // ========================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        header('Content-Type: application/json');
        
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'Inicia sesión para interactuar.']);
            exit;
        }

        // AJAX CSRF 验证
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Error de seguridad.']);
            exit;
        }

        $action = $_POST['action'];

        if ($action === 'like') {
            $check = $pdo->prepare("SELECT id FROM forum_post_likes WHERE post_id = ? AND user_id = ?");
            $check->execute([$post_id, $user_id]);
            
            if ($check->fetch()) {
                $pdo->prepare("DELETE FROM forum_post_likes WHERE post_id = ? AND user_id = ?")->execute([$post_id, $user_id]);
                $pdo->prepare("UPDATE forum_posts SET likes_count = GREATEST(0, likes_count - 1) WHERE id = ?")->execute([$post_id]);
                $status = 'unliked';
            } else {
                $pdo->prepare("INSERT INTO forum_post_likes (post_id, user_id) VALUES (?, ?)")->execute([$post_id, $user_id]);
                $pdo->prepare("UPDATE forum_posts SET likes_count = likes_count + 1 WHERE id = ?")->execute([$post_id]);
                $status = 'liked';
            }
            
            $new_count = $pdo->query("SELECT likes_count FROM forum_posts WHERE id = $post_id")->fetchColumn();
            echo json_encode(['success' => true, 'new_count' => $new_count, 'status' => $status]);
            exit;
        }

        if ($action === 'bookmark') {
            $check = $pdo->prepare("SELECT id FROM user_bookmarks WHERE user_id = ? AND post_id = ?");
            $check->execute([$user_id, $post_id]);
            
            if ($check->fetch()) {
                $pdo->prepare("DELETE FROM user_bookmarks WHERE user_id = ? AND post_id = ?")->execute([$user_id, $post_id]);
                $pdo->prepare("UPDATE forum_posts SET bookmarks_count = GREATEST(0, bookmarks_count - 1) WHERE id = ?")->execute([$post_id]);
                $status = 'unbookmarked';
            } else {
                $pdo->prepare("INSERT INTO user_bookmarks (user_id, post_id) VALUES (?, ?)")->execute([$user_id, $post_id]);
                $pdo->prepare("UPDATE forum_posts SET bookmarks_count = bookmarks_count + 1 WHERE id = ?")->execute([$post_id]);
                $status = 'bookmarked';
            }
            
            $new_count = $pdo->query("SELECT bookmarks_count FROM forum_posts WHERE id = $post_id")->fetchColumn();
            echo json_encode(['success' => true, 'new_count' => $new_count, 'status' => $status]);
            exit;
        }
    }

    // ========================================================
    // 2. 加载基础页面数据
    // ========================================================
    $stmt = $pdo->prepare("SELECT p.*, u.username, u.avatar FROM forum_posts p LEFT JOIN users u ON p.user_id = u.id WHERE p.id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) die("El tema no existe.");

    // 增加浏览量
    if (!isset($_SESSION['viewed_posts'])) $_SESSION['viewed_posts'] = [];
    if ($user_id != $post['user_id'] && !in_array($post_id, $_SESSION['viewed_posts'])) {
        $pdo->prepare("UPDATE forum_posts SET views = views + 1 WHERE id = ?")->execute([$post_id]);
        $post['views']++;
        $_SESSION['viewed_posts'][] = $post_id;
    }

    // 处理回帖 (传统表单提交 + P0-1 CSRF 校验)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_content']) && is_logged_in()) {
        
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            $_SESSION['flash_error'] = 'Error de seguridad (CSRF). Por favor, inténtalo de nuevo.';
            header("Location: view-post.php?id=$post_id");
            exit;
        }

        $reply_content = trim($_POST['reply_content'] ?? '');
        if (!empty($reply_content)) {
            $pdo->prepare("INSERT INTO forum_replies (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())")->execute([$post_id, $user_id, $reply_content]);
            $pdo->prepare("UPDATE forum_posts SET last_reply_at = NOW(), last_reply_by = ? WHERE id = ?")->execute([$user_id, $post_id]);
            $_SESSION['flash_message'] = 'Respuesta publicada exitosamente.';
            header("Location: view-post.php?id=$post_id");
            exit;
        }
    }

    // 检查当前用户交互状态
    $has_liked = false;
    $has_bookmarked = false;
    if ($user_id) {
        $has_liked = (bool)$pdo->query("SELECT id FROM forum_post_likes WHERE post_id = $post_id AND user_id = $user_id")->fetch();
        $has_bookmarked = (bool)$pdo->query("SELECT id FROM user_bookmarks WHERE user_id = $user_id AND post_id = $post_id")->fetch();
    }

    $replies = $pdo->prepare("SELECT r.*, u.username, u.avatar FROM forum_replies r LEFT JOIN users u ON r.user_id = u.id WHERE r.post_id = ? ORDER BY r.created_at ASC");
    $replies->execute([$post_id]);
    $replies_list = $replies->fetchAll();

} catch (Exception $e) { die("Error: " . $e->getMessage()); }
?>
<?php include 'includes/header.php'; ?>

<div class="light-theme-bg"></div>

<div class="article-container">
    
    <div class="breadcrumb">
        <a href="forum.php"><i class="fas fa-arrow-left"></i> 归栈 (Volver a Discusiones)</a>
    </div>

    <article class="article-card">
        
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="alert-box alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert-box alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
            </div>
        <?php endif; ?>

        <?php if ($user_id == $post['user_id']): ?>
            <div class="author-actions-corner">
                <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="btn-corner" title="Editar"><i class="fas fa-edit"></i></a>
                <a href="delete-post.php?id=<?php echo $post['id']; ?>" onclick="return confirm('¿Eliminar tema?')" class="btn-corner btn-danger-corner" title="Borrar"><i class="fas fa-trash"></i></a>
            </div>
        <?php endif; ?>

        <header class="article-header">
            <div class="header-tags">
                <span class="cat-badge"><?php echo htmlspecialchars(strtoupper($post['category'])); ?></span>
            </div>
            
            <h1 class="article-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            
            <div class="article-meta">
                <span title="Autor"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($post['username']); ?></span>
                <span title="Fecha"><i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></span>
                <span title="Vistas"><i class="fas fa-eye"></i> <?php echo $post['views']; ?></span>
            </div>
        </header>

        <div class="wiki-content-body">
            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
        </div>

        <div class="article-actions-bar">
            <button id="like-btn" class="btn-interact <?php echo $has_liked ? 'active' : ''; ?>" data-post-id="<?php echo $post_id; ?>">
                <i class="<?php echo $has_liked ? 'fas' : 'far'; ?> fa-heart"></i> 
                Me gusta (<span id="like-count"><?php echo $post['likes_count']; ?></span>)
            </button>
            
            <button id="bookmark-btn" class="btn-interact <?php echo $has_bookmarked ? 'active' : ''; ?>" data-post-id="<?php echo $post_id; ?>">
                <i class="<?php echo $has_bookmarked ? 'fas' : 'far'; ?> fa-bookmark"></i> 
                <span><?php echo $has_bookmarked ? 'Guardado' : 'Guardar'; ?></span> 
                (<span id="bookmark-count"><?php echo $post['bookmarks_count']; ?></span>)
            </button>
        </div>
    </article>

    <div class="comments-section">
        <h2 class="comments-title">回帖 (Respuestas) <span>[<?php echo count($replies_list); ?>]</span></h2>

        <div class="comments-list">
            <?php if (empty($replies_list)): ?>
                <div class="comments-empty">
                    <p>尚未有人回应，且留第一笔。</p>
                </div>
            <?php else: ?>
                <?php foreach($replies_list as $index => $reply): ?>
                    <div class="comment-item">
                        <div class="comment-avatar-box">
                            <?php if(!empty($reply['avatar'])): ?>
                                <img src="<?php echo htmlspecialchars($reply['avatar']); ?>" class="comment-avatar">
                            <?php else: ?>
                                <i class="fas fa-user-circle comment-avatar-placeholder"></i>
                            <?php endif; ?>
                        </div>
                        <div class="comment-content-box">
                            <div class="comment-meta">
                                <div class="comment-author-info">
                                    <span class="comment-author"><?php echo htmlspecialchars(strtoupper($reply['username'])); ?></span>
                                    <span class="comment-date"><?php echo date('d/m/Y H:i', strtotime($reply['created_at'])); ?></span>
                                </div>
                                <div class="comment-floor">#<?php echo $index + 1; ?></div>
                            </div>
                            <div class="comment-text">
                                <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="reply-form-container">
            <?php if (is_logged_in()): ?>
                <h3 class="reply-form-title"><i class="fas fa-pen"></i> 留墨 (Añadir Respuesta)</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <textarea name="reply_content" class="reply-input" rows="4" required placeholder="写下你的见解..."></textarea>
                    <div class="form-actions">
                        <button type="submit" class="btn-submit-comment"><i class="fas fa-stamp"></i> 落印</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="comment-login-prompt">
                    <p>需推门入阁方可回帖。</p>
                    <a href="login.php" class="btn-outline">推门入阁 (Iniciar Sesión)</a>
                </div>
            <?php endif; ?>
        </div>

    </div>
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

/* 通知样式 */
.alert-box { padding: 15px; border-radius: 4px; margin-bottom: 25px; font-family: sans-serif; font-size: 0.9em; font-weight: bold;}
.alert-error { background: rgba(158,27,27,0.05); border: 1px solid #9e1b1b; color: #9e1b1b; }
.alert-success { background: rgba(40,167,69,0.05); border: 1px solid #28a745; color: #28a745; }

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

/* 角落作者操作按钮 */
.author-actions-corner { position: absolute; right: 30px; top: 30px; display: flex; gap: 10px; }
.btn-corner { display: flex; justify-content: center; align-items: center; width: 32px; height: 32px; border: 1px solid #ddd; color: #888; border-radius: 4px; text-decoration: none; transition: 0.3s; }
.btn-corner:hover { border-color: #222; color: #222; background: #fafafa; }
.btn-danger-corner { color: #e2a8a8; border-color: #f5d6d6; }
.btn-danger-corner:hover { border-color: #9e1b1b; color: #fff; background: #9e1b1b; }

/* ==== 头部信息 ==== */
.article-header { border-bottom: 1px dashed #E5E5E5; padding-bottom: 25px; margin-bottom: 35px; }
.header-tags { margin-bottom: 20px; }

.cat-badge { font-family: sans-serif; font-size: 0.75em; letter-spacing: 1px; color: #666; border: 1px solid #ddd; padding: 3px 8px; border-radius: 2px; font-weight: bold; background: #fafafa;}

.article-title { color: #222222; font-size: 2.4em; margin: 0 0 20px 0; font-weight: 900; line-height: 1.3; letter-spacing: 1px; word-break: break-word; }

.article-meta { color: #888888; font-size: 0.9em; display: flex; flex-wrap: wrap; gap: 20px; font-family: sans-serif; }
.article-meta i { color: #cccccc; margin-right: 5px; }

/* ==== 正文 ==== */
.wiki-content-body { font-size: 1.15em; line-height: 1.8; color: #333333; min-height: 100px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; margin-bottom: 50px; }

/* ==== 交互操作栏 ==== */
.article-actions-bar { display: flex; gap: 15px; padding-top: 25px; border-top: 1px dashed #E5E5E5; }
.btn-interact { 
    background: transparent; border: 1px solid #cccccc; color: #666666; 
    padding: 8px 18px; border-radius: 2px; font-family: sans-serif; font-weight: bold; 
    font-size: 0.9em; cursor: pointer; transition: all 0.3s; letter-spacing: 1px;
}
.btn-interact i { margin-right: 5px; }
.btn-interact:hover { border-color: #9e1b1b; color: #9e1b1b; background: rgba(158,27,27,0.03); }
.btn-interact.active { border-color: #9e1b1b; color: #fff; background: #9e1b1b; }
.btn-interact.active:hover { background: #7a1515; border-color: #7a1515; }

/* ==== 评论区 ==== */
.comments-section { padding-top: 40px; }
.comments-title { font-size: 1.5em; color: #222; margin-bottom: 25px; font-weight: 700; letter-spacing: 2px; border-left: 4px solid #9e1b1b; padding-left: 15px; line-height: 1;}
.comments-title span { font-family: sans-serif; font-size: 0.7em; color: #888; font-weight: normal; }

.comments-empty { text-align: center; padding: 40px 0; color: #aaa; letter-spacing: 2px; border-top: 1px dashed #eee; }

.comments-list { display: flex; flex-direction: column; margin-bottom: 40px; }
.comment-item { display: flex; gap: 20px; padding: 25px 30px; background: #fff; border: 1px solid rgba(0,0,0,0.06); border-radius: 4px; margin-bottom: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.01);}
.comment-avatar-box { flex-shrink: 0; }
.comment-avatar { width: 45px; height: 45px; border-radius: 50%; border: 1px solid #eee; object-fit: cover; }
.comment-avatar-placeholder { font-size: 45px; color: #e5e5e5; }
.comment-content-box { flex-grow: 1; min-width: 0; }
.comment-meta { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 10px; border-bottom: 1px dashed #eee; padding-bottom: 10px;}
.comment-author-info { display: flex; align-items: center; gap: 15px; }
.comment-author { font-weight: bold; color: #222; text-decoration: none; font-size: 0.95em; letter-spacing: 1px;}
.comment-date { font-family: sans-serif; font-size: 0.8em; color: #999; }
.comment-floor { font-family: 'Cinzel', serif; font-weight: bold; color: #ccc; }
.comment-text { color: #444; line-height: 1.6; font-size: 1.05em; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; }

/* ==== 回复表单 ==== */
.reply-form-container { background: #fff; padding: 30px; border-radius: 4px; border: 1px solid rgba(0,0,0,0.06); }
.reply-form-title { margin: 0 0 20px 0; color: #222; font-size: 1.1em; letter-spacing: 1px; }
.reply-input { width: 100%; box-sizing: border-box; padding: 15px; border: 1px solid #dddddd; border-radius: 2px; font-size: 1em; font-family: inherit; resize: vertical; margin-bottom: 15px; background: #fafafa; outline: none; transition: border-color 0.3s;}
.reply-input:focus { border-color: #9e1b1b; background: #fff; }

.form-actions { text-align: right; }
.btn-submit-comment { background: #222; color: #fff; border: 1px solid #222; padding: 10px 25px; border-radius: 2px; font-family: 'Noto Serif SC', serif; font-weight: bold; font-size: 1em; letter-spacing: 2px; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px;}
.btn-submit-comment:hover { background: #9e1b1b; border-color: #9e1b1b; }

.comment-login-prompt { text-align: center; padding: 30px 20px; border: 1px dashed #ccc; background: #fafafa; border-radius: 2px; }
.comment-login-prompt p { color: #888; margin-bottom: 15px; letter-spacing: 1px; }
.btn-outline { border: 1px solid #cccccc; color: #555555; background: transparent; padding: 8px 18px; border-radius: 2px; font-family: sans-serif; font-weight: bold; font-size: 0.9em; text-decoration: none; display: inline-block; transition: 0.3s; letter-spacing: 1px;}
.btn-outline:hover { border-color: #222; color: #222; background: rgba(0,0,0,0.03); }

/* 响应式调整 */
@media (max-width: 768px) {
    .article-container { margin-top: 20px; padding: 0 15px; }
    .article-card { padding: 30px 20px; }
    .article-title { font-size: 1.8em; }
    .article-meta { flex-direction: column; gap: 10px; align-items: flex-start; }
    .article-actions-bar { flex-direction: column; align-items: stretch; }
    .btn-interact { width: 100%; justify-content: center; }
    .comment-item { gap: 15px; padding: 20px 15px; }
    .comment-avatar, .comment-avatar-placeholder { width: 35px; height: 35px; font-size: 35px; }
    .author-actions-corner { position: static; margin-bottom: 20px; justify-content: flex-end; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?php echo csrf_token(); ?>';
    
    const interact = async (btn, action) => {
        const postId = btn.dataset.postId;
        const formData = new FormData();
        formData.append('action', action);
        formData.append('csrf_token', csrfToken);
        
        try {
            const response = await fetch('view-post.php?id=' + postId, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                document.getElementById(action + '-count').textContent = data.new_count;
                btn.classList.toggle('active');
                const icon = btn.querySelector('i');
                
                if (action === 'like') {
                    icon.className = data.status === 'liked' ? 'fas fa-heart' : 'far fa-heart';
                } else {
                    icon.className = data.status === 'bookmarked' ? 'fas fa-bookmark' : 'far fa-bookmark';
                    btn.querySelector('span').textContent = data.status === 'bookmarked' ? 'Guardado' : 'Guardar';
                }
            } else {
                alert(data.message);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    };

    document.getElementById('like-btn')?.addEventListener('click', function() { interact(this, 'like'); });
    document.getElementById('bookmark-btn')?.addEventListener('click', function() { interact(this, 'bookmark'); });
});
</script>

<?php include 'includes/footer.php'; ?>