<?php
// view-post.php - 100% 完整版 (点赞/收藏无刷新功能 + 官方暖暗配色)
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

        // 验证 CSRF 令牌
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Error de seguridad.']);
            exit;
        }

        $action = $_POST['action'];

        if ($action === 'like') {
            // 检查是否已点赞
            $check = $pdo->prepare("SELECT id FROM forum_post_likes WHERE post_id = ? AND user_id = ?");
            $check->execute([$post_id, $user_id]);
            
            if ($check->fetch()) {
                // 取消点赞
                $pdo->prepare("DELETE FROM forum_post_likes WHERE post_id = ? AND user_id = ?")->execute([$post_id, $user_id]);
                $pdo->prepare("UPDATE forum_posts SET likes_count = GREATEST(0, likes_count - 1) WHERE id = ?")->execute([$post_id]);
                $status = 'unliked';
            } else {
                // 执行点赞
                $pdo->prepare("INSERT INTO forum_post_likes (post_id, user_id) VALUES (?, ?)")->execute([$post_id, $user_id]);
                $pdo->prepare("UPDATE forum_posts SET likes_count = likes_count + 1 WHERE id = ?")->execute([$post_id]);
                $status = 'liked';
            }
            
            $new_count = $pdo->query("SELECT likes_count FROM forum_posts WHERE id = $post_id")->fetchColumn();
            echo json_encode(['success' => true, 'new_count' => $new_count, 'status' => $status]);
            exit;
        }

        if ($action === 'bookmark') {
            // 检查是否已收藏
            $check = $pdo->prepare("SELECT id FROM user_bookmarks WHERE user_id = ? AND post_id = ?");
            $check->execute([$user_id, $post_id]);
            
            if ($check->fetch()) {
                // 取消收藏
                $pdo->prepare("DELETE FROM user_bookmarks WHERE user_id = ? AND post_id = ?")->execute([$user_id, $post_id]);
                $pdo->prepare("UPDATE forum_posts SET bookmarks_count = GREATEST(0, bookmarks_count - 1) WHERE id = ?")->execute([$post_id]);
                $status = 'unbookmarked';
            } else {
                // 执行收藏
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

    // 处理回帖 (传统表单提交)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_content']) && is_logged_in()) {
        $reply_content = trim($_POST['reply_content'] ?? '');
        if (!empty($reply_content)) {
            $pdo->prepare("INSERT INTO forum_replies (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())")->execute([$post_id, $user_id, $reply_content]);
            $pdo->prepare("UPDATE forum_posts SET last_reply_at = NOW(), last_reply_by = ? WHERE id = ?")->execute([$user_id, $post_id]);
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

<div class="nj-static-bg"></div>

<div class="nj-container">
    
    <header class="nj-header" style="border-bottom:none; margin-bottom: 10px;">
        <a href="forum.php" style="color:var(--nj-gold); text-decoration:none; font-size:0.9em; font-weight:bold;">
            <i class="fas fa-arrow-left"></i> Volver a Discusiones
        </a>
    </header>

    <div class="nj-layout">
        <main class="nj-main" style="max-width: 1000px; margin: 0 auto;">
            
            <article class="nj-sidebar-card" style="position: relative; padding: 35px;">
                <?php if ($user_id == $post['user_id']): ?>
                    <div style="position: absolute; right: 25px; top: 25px; display: flex; gap: 8px;">
                        <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="nj-btn-secondary" style="padding: 6px 12px; font-size: 0.8em;" title="Editar"><i class="fas fa-edit"></i></a>
                        <a href="delete-post.php?id=<?php echo $post['id']; ?>" onclick="return confirm('¿Eliminar tema?')" class="nj-btn-secondary" style="padding: 6px 12px; font-size: 0.8em; color: var(--nj-red); border-color: rgba(209,35,35,0.3);"><i class="fas fa-trash"></i></a>
                    </div>
                <?php endif; ?>

                <div style="border-bottom: 1px solid var(--nj-border); padding-bottom: 20px; margin-bottom: 25px;">
                    <div style="margin-bottom: 15px;">
                        <span class="nj-badge-cat"><?php echo strtoupper(htmlspecialchars($post['category'])); ?></span>
                    </div>
                    <h1 class="nj-post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                    <div class="nj-post-info">
                        <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($post['username']); ?></span>
                        <span><i class="fas fa-clock"></i> <?php echo date('d M Y, H:i', strtotime($post['created_at'])); ?></span>
                        <span><i class="fas fa-eye"></i> <?php echo $post['views']; ?> Vistas</span>
                    </div>
                </div>

                <div class="nj-post-content">
                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                </div>

                <div class="nj-interaction-bar">
                    <button id="like-btn" class="nj-interact-btn <?php echo $has_liked ? 'active' : ''; ?>" data-post-id="<?php echo $post_id; ?>">
                        <i class="<?php echo $has_liked ? 'fas' : 'far'; ?> fa-heart"></i> 
                        Me gusta (<span id="like-count"><?php echo $post['likes_count']; ?></span>)
                    </button>
                    
                    <button id="bookmark-btn" class="nj-interact-btn <?php echo $has_bookmarked ? 'active' : ''; ?>" data-post-id="<?php echo $post_id; ?>">
                        <i class="<?php echo $has_bookmarked ? 'fas' : 'far'; ?> fa-bookmark"></i> 
                        <span><?php echo $has_bookmarked ? 'Guardado' : 'Guardar'; ?></span> 
                        (<span id="bookmark-count"><?php echo $post['bookmarks_count']; ?></span>)
                    </button>
                </div>
            </article>

            <div class="nj-replies-divider">
                <h3>RESPUESTAS (<?php echo count($replies_list); ?>)</h3>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <?php foreach($replies_list as $index => $reply): ?>
                    <div class="nj-sidebar-card" style="padding: 25px;">
                        <div class="nj-reply-header">
                            <div class="nj-reply-user">
                                <i class="fas fa-user-circle"></i>
                                <strong><?php echo htmlspecialchars($reply['username']); ?></strong>
                                <span><i class="fas fa-clock"></i> <?php echo date('d M Y, H:i', strtotime($reply['created_at'])); ?></span>
                            </div>
                            <div class="nj-reply-num">#<?php echo $index + 1; ?></div>
                        </div>
                        <div class="nj-reply-body">
                            <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="nj-sidebar-card" style="margin-top: 40px; padding: 30px;">
                <?php if (is_logged_in()): ?>
                    <h3 class="nj-reply-title"><i class="fas fa-reply"></i> Añadir Respuesta</h3>
                    <form method="POST">
                        <textarea name="reply_content" class="nj-input" rows="5" required placeholder="Escribe tu respuesta aquí..."></textarea>
                        <div style="text-align: right; margin-top: 20px;">
                            <button type="submit" class="nj-btn-primary">Publicar Respuesta</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px 0;">
                        <p style="color: var(--nj-text-muted); margin-bottom: 20px;">Debes iniciar sesión para unirte a la discusión.</p>
                        <a href="login.php" class="nj-btn-primary">Iniciar sesión</a>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<style>
/* 继承 & 扩展样式 */
:root {
    --nj-bg: #0B0A0A; --nj-module: #161413; --nj-red: #D12323; 
    --nj-gold: #CCA677; --nj-border: #2D2926; --nj-text-main: #E6E4DF; 
    --nj-text-muted: #8F98A0; 
}

.nj-badge-cat { background: rgba(0,0,0,0.4); border: 1px solid var(--nj-border); padding: 4px 10px; border-radius: 4px; color: var(--nj-gold); font-size: 0.8em; font-weight: bold; letter-spacing: 1px; }
.nj-post-title { margin: 0 0 15px 0; color: var(--nj-text-main); font-size: 1.8em; line-height: 1.3; }
.nj-post-info { display: flex; gap: 20px; color: var(--nj-text-muted); font-size: 0.9em; }
.nj-post-info i { color: var(--nj-gold); margin-right: 5px; }
.nj-post-content { line-height: 1.8; color: var(--nj-text-main); font-size: 1.05em; margin-top: 25px; }

/* 交互栏 */
.nj-interaction-bar { display: flex; gap: 15px; margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--nj-border); }
.nj-interact-btn { 
    background: transparent; border: 1px solid var(--nj-border); 
    color: var(--nj-text-muted); padding: 8px 18px; border-radius: 4px; 
    cursor: pointer; transition: 0.2s; font-size: 0.9em; font-weight: 600; 
}
.nj-interact-btn:hover { background: var(--nj-border); color: var(--nj-text-main); }
.nj-interact-btn.active { border-color: var(--nj-red); color: var(--nj-red); background: rgba(209, 35, 35, 0.05); }
.nj-interact-btn#bookmark-btn.active { border-color: var(--nj-gold); color: var(--nj-gold); background: rgba(204, 166, 119, 0.05); }

/* 回复区 */
.nj-replies-divider { margin: 40px 0 20px 0; border-bottom: 1px solid var(--nj-border); padding-bottom: 10px; }
.nj-replies-divider h3 { color: var(--nj-text-main); font-size: 1.1em; margin: 0; }
.nj-reply-header { display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px dashed var(--nj-border); padding-bottom: 15px; }
.nj-reply-user { display: flex; gap: 10px; align-items: center; color: var(--nj-text-muted); font-size: 0.9em; }
.nj-reply-user i { color: #4A5056; }
.nj-reply-user strong { color: var(--nj-text-main); font-size: 1.1em; }
.nj-reply-num { color: var(--nj-border); font-weight: bold; }
.nj-reply-body { line-height: 1.7; color: var(--nj-text-main); }
.nj-reply-title { margin: 0 0 20px 0; color: var(--nj-gold); font-size: 1.1em; text-transform: uppercase; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?php echo csrf_token(); ?>';
    
    // 处理点赞与收藏
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
                // 更新计数
                document.getElementById(action + '-count').textContent = data.new_count;
                // 更新样式
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