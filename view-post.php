<?php
// view-post.php - 100% 完整版 (管理员特权 + 无刷新点赞/收藏 + 通知触发)
require_once 'config.php';

$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = is_logged_in() ? $_SESSION['user_id'] : 0;
// 判定管理员特权
$is_admin = (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin');

try {
    $pdo = db_connect();

    // 先获取帖子作者以便发通知
    $stmt_base = $pdo->prepare("SELECT user_id, title FROM forum_posts WHERE id = ?");
    $stmt_base->execute([$post_id]);
    $base_post = $stmt_base->fetch();
    
    if (!$base_post) {
        header("HTTP/1.0 404 Not Found");
        die("El tema no existe.");
    }

    // ========================================================
    // 1. 处理 AJAX 异步请求 (点赞/收藏 + 通知触发)
    // ========================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        header('Content-Type: application/json');
        
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'Inicia sesión para interactuar.']);
            exit;
        }

        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Error de seguridad.']);
            exit;
        }

        $action = $_POST['action'];

        if ($action === 'like') {
            $check = $pdo->prepare("SELECT 1 FROM forum_post_likes WHERE post_id = ? AND user_id = ?");
            $check->execute([$post_id, $user_id]);
            if ($check->fetch()) {
                $pdo->prepare("DELETE FROM forum_post_likes WHERE post_id = ? AND user_id = ?")->execute([$post_id, $user_id]);
                $pdo->prepare("UPDATE forum_posts SET likes_count = GREATEST(0, likes_count - 1) WHERE id = ?")->execute([$post_id]);
                $status = 'unliked';
            } else {
                $pdo->prepare("INSERT INTO forum_post_likes (post_id, user_id) VALUES (?, ?)")->execute([$post_id, $user_id]);
                $pdo->prepare("UPDATE forum_posts SET likes_count = likes_count + 1 WHERE id = ?")->execute([$post_id]);
                $status = 'liked';
                
                // ====== 通知触发：点赞 ======
                if (function_exists('send_notification')) {
                    send_notification($base_post['user_id'], $user_id, 'like_post', $post_id);
                }
            }
            $new_count = $pdo->query("SELECT likes_count FROM forum_posts WHERE id = $post_id")->fetchColumn();
            echo json_encode(['success' => true, 'status' => $status, 'new_count' => $new_count]);
            exit;
        }

        if ($action === 'bookmark') {
            $check = $pdo->prepare("SELECT 1 FROM user_bookmarks WHERE post_id = ? AND user_id = ?");
            $check->execute([$post_id, $user_id]);
            if ($check->fetch()) {
                $pdo->prepare("DELETE FROM user_bookmarks WHERE post_id = ? AND user_id = ?")->execute([$post_id, $user_id]);
                $pdo->prepare("UPDATE forum_posts SET bookmarks_count = GREATEST(0, bookmarks_count - 1) WHERE id = ?")->execute([$post_id]);
                $status = 'unbookmarked';
            } else {
                $pdo->prepare("INSERT INTO user_bookmarks (post_id, user_id) VALUES (?, ?)")->execute([$post_id, $user_id]);
                $pdo->prepare("UPDATE forum_posts SET bookmarks_count = bookmarks_count + 1 WHERE id = ?")->execute([$post_id]);
                $status = 'bookmarked';
            }
            $new_count = $pdo->query("SELECT bookmarks_count FROM forum_posts WHERE id = $post_id")->fetchColumn();
            echo json_encode(['success' => true, 'status' => $status, 'new_count' => $new_count]);
            exit;
        }
    }

    // ========================================================
    // 2. 处理常规回帖提交 (包含通知触发)
    // ========================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_content']) && is_logged_in()) {
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            $_SESSION['flash_error'] = "Error de seguridad (CSRF).";
        } else {
            $content = trim($_POST['reply_content']);
            if (!empty($content)) {
                $pdo->prepare("INSERT INTO forum_replies (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())")
                    ->execute([$post_id, $user_id, $content]);
                
                // 更新主帖的最后回复时间与人
                $pdo->prepare("UPDATE forum_posts SET last_reply_at = NOW(), last_reply_by = ? WHERE id = ?")
                    ->execute([$user_id, $post_id]);
                
                // ====== 通知触发：回复 ======
                if (function_exists('send_notification')) {
                    send_notification($base_post['user_id'], $user_id, 'reply_post', $post_id);
                }

                header("Location: view-post.php?id=" . $post_id . "#replies");
                exit;
            }
        }
    }

    // ========================================================
    // 3. 获取数据与权限判定
    // ========================================================
    $stmt = $pdo->prepare("SELECT p.*, u.username, u.avatar, u.country, u.role FROM forum_posts p LEFT JOIN users u ON p.user_id = u.id WHERE p.id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        header("HTTP/1.0 404 Not Found");
        die("El tema no existe.");
    }

    // 【核心特权判定】：作者本人或管理员
    $can_manage = ($user_id == $post['user_id'] || $is_admin);

    if ($user_id != $post['user_id'] && (!isset($_SESSION['viewed_posts']) || !in_array($post_id, $_SESSION['viewed_posts']))) {
        $pdo->prepare("UPDATE forum_posts SET views = views + 1 WHERE id = ?")->execute([$post_id]);
        $post['views']++;
        $_SESSION['viewed_posts'][] = $post_id;
    }

    $user_liked = false;
    $user_bookmarked = false;
    if ($user_id) {
        $user_liked = (bool)$pdo->query("SELECT 1 FROM forum_post_likes WHERE post_id = $post_id AND user_id = $user_id")->fetch();
        $user_bookmarked = (bool)$pdo->query("SELECT 1 FROM user_bookmarks WHERE post_id = $post_id AND user_id = $user_id")->fetch();
    }

    $replies_stmt = $pdo->prepare("SELECT r.*, u.username, u.avatar, u.role, u.country FROM forum_replies r LEFT JOIN users u ON r.user_id = u.id WHERE r.post_id = ? ORDER BY r.created_at ASC");
    $replies_stmt->execute([$post_id]);
    $replies = $replies_stmt->fetchAll();

} catch (Exception $e) { die("Error: " . $e->getMessage()); }
?>
<?php include 'includes/header.php'; ?>

<div class="nj-static-bg"></div>

<div class="nj-container">
    <header class="nj-header" style="border-bottom:none; margin-bottom: 10px; margin-top: 30px;">
        <a href="forum.php" style="color:var(--nj-gold); text-decoration:none; font-size:0.9em; font-weight:bold;">
            <i class="fas fa-arrow-left"></i> Volver al Foro
        </a>
    </header>

    <div class="nj-layout">
        <main class="nj-main" style="max-width: 1000px; margin: 0 auto;">

            <article class="nj-sidebar-card" style="position: relative; padding: 40px;">
                <?php if ($can_manage): ?>
                    <div style="position: absolute; right: 30px; top: 30px; display: flex; gap: 10px; align-items: center;">
                        <?php if ($is_admin && $user_id != $post['user_id']): ?>
                            <span style="color: var(--nj-red); font-size: 0.75em; font-weight: bold; border: 1px solid var(--nj-red); padding: 2px 6px; border-radius: 3px;">MODO ADMIN</span>
                        <?php endif; ?>
                        <a href="edit-post.php?id=<?php echo $post_id; ?>" class="nj-btn-secondary" style="padding: 8px 15px; font-size: 0.85em;"><i class="fas fa-edit"></i> Editar</a>
                        <a href="delete-post.php?id=<?php echo $post_id; ?>" onclick="return confirm('¿Seguro que deseas eliminar este tema por completo?');" class="nj-btn-secondary" style="padding: 8px 15px; font-size: 0.85em; color: var(--nj-red); border-color: rgba(209,35,35,0.3);"><i class="fas fa-trash"></i> Eliminar</a>
                    </div>
                <?php endif; ?>

                <div style="border-bottom: 1px solid var(--nj-border); padding-bottom: 25px; margin-bottom: 30px;">
                    <div style="display: flex; gap: 10px; margin-bottom: 15px; align-items: center;">
                        <span class="nj-badge-cat"><?php echo htmlspecialchars(strtoupper($post['category'])); ?></span>
                        <?php if ($post['is_pinned']): ?>
                            <span style="color: var(--nj-gold); font-size: 0.85em; font-weight: bold;"><i class="fas fa-thumbtack"></i> Fijado</span>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="nj-post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                    
                    <div class="nj-post-info">
                        <span>
                            <i class="fas fa-user-ninja"></i> 
                            <strong style="color: <?php echo ($post['role'] === 'admin') ? 'var(--nj-red)' : 'var(--nj-text-main)'; ?>;">
                                <?php echo htmlspecialchars($post['username']); ?>
                            </strong>
                        </span>
                        <span><i class="fas fa-calendar-alt"></i> <?php echo date('d M Y, H:i', strtotime($post['created_at'])); ?></span>
                        <span><i class="fas fa-eye"></i> <?php echo $post['views']; ?> Vistas</span>
                    </div>
                </div>

                <div class="nj-post-content">
                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                </div>

                <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--nj-border); display: flex; gap: 15px;">
                    <input type="hidden" id="ajax_csrf" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    
                    <button type="button" id="like-btn" data-post-id="<?php echo $post_id; ?>" class="nj-interact-btn <?php echo $user_liked ? 'active' : ''; ?>">
                        <i class="<?php echo $user_liked ? 'fas' : 'far'; ?> fa-heart"></i> 
                        <?php echo $user_liked ? 'Te gusta' : 'Me gusta'; ?> (<span id="like-count"><?php echo $post['likes_count']; ?></span>)
                    </button>
                    
                    <button type="button" id="bookmark-btn" data-post-id="<?php echo $post_id; ?>" class="nj-interact-btn <?php echo $user_bookmarked ? 'active' : ''; ?>">
                        <i class="<?php echo $user_bookmarked ? 'fas' : 'far'; ?> fa-bookmark"></i> 
                        <span><?php echo $user_bookmarked ? 'Guardado' : 'Guardar'; ?></span> (<span id="bookmark-count"><?php echo $post['bookmarks_count']; ?></span>)
                    </button>
                </div>
            </article>

            <div class="nj-replies-divider" id="replies">
                <h3><i class="fas fa-comments"></i> RESPUESTAS (<?php echo count($replies); ?>)</h3>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <?php foreach($replies as $reply): ?>
                    <div class="nj-sidebar-card" style="padding: 20px;">
                        <div class="nj-reply-header" style="margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                            <div class="nj-reply-user">
                                <i class="fas fa-user-circle"></i>
                                <strong style="color: <?php echo ($reply['role'] === 'admin') ? 'var(--nj-red)' : 'var(--nj-text-main)'; ?>;">
                                    <?php echo htmlspecialchars($reply['username']); ?>
                                </strong>
                                <?php if ($reply['role'] === 'admin'): ?>
                                    <span style="background: rgba(209,35,35,0.1); color: var(--nj-red); font-size: 0.75em; padding: 2px 6px; border-radius: 3px; font-weight: bold; border: 1px solid var(--nj-red);">ADMIN</span>
                                <?php endif; ?>
                                <span><i class="fas fa-clock"></i> <?php echo date('d M Y, H:i', strtotime($reply['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="nj-reply-body">
                            <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="nj-sidebar-card" style="margin-top: 30px; padding: 30px;">
                <?php if (is_logged_in()): ?>
                    <h3 style="margin: 0 0 20px 0; color: var(--nj-gold); font-size: 1em;"><i class="fas fa-pen"></i> Escribir una respuesta</h3>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <textarea name="reply_content" class="nj-input" rows="4" required placeholder="Comparte tu opinión..."></textarea>
                        <div style="text-align: right; margin-top: 15px;">
                            <button type="submit" class="nj-btn-primary">Responder</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px;">
                        <p style="color: var(--nj-text-muted); margin-bottom: 15px;">Debes iniciar sesión para responder.</p>
                        <a href="login.php" class="nj-btn-primary">Iniciar sesión</a>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<style>
:root {
    --nj-bg: #0B0A0A; --nj-module: #161413; --nj-module-hover: #1E1B19;    
    --nj-red: #D12323; --nj-gold: #CCA677; --nj-border: #2D2926;          
    --nj-text-main: #E6E4DF; --nj-text-muted: #8F98A0; 
    --font-main: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}
body { background-color: var(--nj-bg) !important; color: var(--nj-text-main); font-family: var(--font-main); margin: 0; padding: 0; }
.nj-static-bg { position: fixed; inset: 0; z-index: -10; background-color: var(--nj-bg); background-image: radial-gradient(circle at 10% 20%, rgba(209, 35, 35, 0.04), transparent 50%), radial-gradient(circle at 90% 80%, rgba(204, 166, 119, 0.03), transparent 50%); background-blend-mode: screen; }
.nj-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; min-height: 100vh; }
.nj-sidebar-card { background: var(--nj-module); border: 1px solid var(--nj-border); border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);}
.nj-input { width: 100%; padding: 15px; background: rgba(0,0,0,0.4); border: 1px solid var(--nj-border); border-radius: 6px; color: var(--nj-text-main); font-family: var(--font-main); outline: none; transition: 0.2s; box-sizing: border-box; font-size: 1em;}
.nj-input:focus { border-color: var(--nj-gold); background: var(--nj-bg);}
.nj-btn-primary { display: inline-block; text-align: center; background: var(--nj-red); color: #fff; padding: 10px 20px; text-decoration: none; font-size: 0.9em; border-radius: 4px; font-weight: bold; transition: background 0.2s; border: none; cursor: pointer;}
.nj-btn-primary:hover { background: #b81c1c; }
.nj-btn-secondary { display: inline-block; text-align: center; background: transparent; border: 1px solid var(--nj-border); color: var(--nj-text-main); padding: 10px 20px; text-decoration: none; font-size: 0.9em; border-radius: 4px; transition: 0.2s; cursor: pointer;}
.nj-btn-secondary:hover { background: var(--nj-module-hover); border-color: var(--nj-text-muted); }
.nj-alert { padding: 15px; background: rgba(209, 35, 35, 0.1); border: 1px solid var(--nj-red); color: var(--nj-text-main); border-radius: 8px; margin-bottom: 20px; font-size: 0.9em;}
.nj-badge-cat { background: rgba(0,0,0,0.4); border: 1px solid var(--nj-border); padding: 4px 10px; border-radius: 4px; color: var(--nj-gold); font-size: 0.8em; font-weight: bold; letter-spacing: 1px; }
.nj-post-title { margin: 0 0 15px 0; color: var(--nj-text-main); font-size: 2em; line-height: 1.3; font-weight: 700; }
.nj-post-info { display: flex; flex-wrap: wrap; gap: 20px; color: var(--nj-text-muted); font-size: 0.9em; align-items: center;}
.nj-post-info i { color: var(--nj-gold); margin-right: 5px; }
.nj-post-content { line-height: 1.8; color: var(--nj-text-main); font-size: 1.05em; }
.nj-interact-btn { background: transparent; border: 1px solid var(--nj-border); color: var(--nj-text-muted); padding: 8px 18px; border-radius: 4px; cursor: pointer; transition: 0.2s; font-size: 0.9em; font-weight: 600; }
.nj-interact-btn:hover { background: var(--nj-border); color: var(--nj-text-main); }
.nj-interact-btn.active { border-color: var(--nj-red); color: var(--nj-red); background: rgba(209, 35, 35, 0.05); }
.nj-replies-divider { margin: 40px 0 20px 0; border-bottom: 1px solid var(--nj-border); padding-bottom: 10px; }
.nj-replies-divider h3 { color: var(--nj-text-main); font-size: 1em; margin: 0; letter-spacing: 1px; }
.nj-reply-user { display: flex; gap: 10px; align-items: center; color: var(--nj-text-muted); font-size: 0.9em; }
.nj-reply-body { line-height: 1.6; color: var(--nj-text-main); font-size: 0.95em; margin-top: 10px;}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfInput = document.getElementById('ajax_csrf');
    const csrfToken = csrfInput ? csrfInput.value : '';
    
    const interact = async (btn, action) => {
        if (!csrfToken) {
            alert('Debes iniciar sesión para interactuar.');
            window.location.href = 'login.php';
            return;
        }

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