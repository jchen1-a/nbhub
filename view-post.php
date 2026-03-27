<?php
// view-post.php - 浏览帖子 (适配卡片化分层布局)
require_once 'config.php';

$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = is_logged_in() ? $_SESSION['user_id'] : 0;

try {
    $pdo = db_connect();

    // 1. 获取主贴内容
    $stmt = $pdo->prepare("SELECT p.*, u.username, u.avatar FROM forum_posts p LEFT JOIN users u ON p.user_id = u.id WHERE p.id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        die("El tema no existe.");
    }

    // 2. 增加浏览量 (Session隔离)
    if (!isset($_SESSION['viewed_posts'])) $_SESSION['viewed_posts'] = [];
    if ($user_id != $post['user_id'] && !in_array($post_id, $_SESSION['viewed_posts'])) {
        $pdo->prepare("UPDATE forum_posts SET views = views + 1 WHERE id = ?")->execute([$post_id]);
        $post['views']++;
        $_SESSION['viewed_posts'][] = $post_id;
    }

    // 3. 处理回帖请求
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
        $reply_content = trim($_POST['reply_content'] ?? '');
        if (!empty($reply_content)) {
            $pdo->prepare("INSERT INTO forum_replies (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())")->execute([$post_id, $user_id, $reply_content]);
            $pdo->prepare("UPDATE forum_posts SET last_reply_at = NOW(), last_reply_by = ? WHERE id = ?")->execute([$user_id, $post_id]);
            header("Location: view-post.php?id=$post_id");
            exit;
        }
    }

    // 4. 获取回帖列表
    $replies_stmt = $pdo->prepare("SELECT r.*, u.username, u.avatar FROM forum_replies r LEFT JOIN users u ON r.user_id = u.id WHERE r.post_id = ? ORDER BY r.created_at ASC");
    $replies_stmt->execute([$post_id]);
    $replies = $replies_stmt->fetchAll();

} catch (Exception $e) {
    die("Error del sistema: " . $e->getMessage());
}
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
            
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="nj-alert" style="border-color: #28a745; background: rgba(40,167,69,0.1); color:#E6E4DF;">
                    <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
                </div>
            <?php endif; ?>

            <article class="nj-sidebar-card" style="position: relative; padding: 35px;">
                <?php if ($user_id == $post['user_id']): ?>
                    <div style="position: absolute; right: 25px; top: 25px; display: flex; gap: 8px;">
                        <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="nj-btn-secondary" style="padding: 6px 12px; font-size: 0.8em;" title="Editar"><i class="fas fa-edit"></i></a>
                        <a href="delete-post.php?id=<?php echo $post['id']; ?>" onclick="return confirm('¿Eliminar este tema y todas sus respuestas?')" class="nj-btn-secondary" style="padding: 6px 12px; font-size: 0.8em; color: var(--nj-red); border-color: rgba(209,35,35,0.3);" title="Eliminar"><i class="fas fa-trash"></i></a>
                    </div>
                <?php endif; ?>

                <div style="border-bottom: 1px solid var(--nj-border); padding-bottom: 20px; margin-bottom: 25px;">
                    <div style="margin-bottom: 15px;">
                        <span style="background: rgba(0,0,0,0.4); border: 1px solid var(--nj-border); padding: 4px 10px; border-radius: 4px; color: var(--nj-gold); font-size: 0.8em; font-weight: bold; letter-spacing: 1px;"><?php echo strtoupper(htmlspecialchars($post['category'])); ?></span>
                    </div>
                    <h1 style="margin: 0 0 15px 0; color: var(--nj-text-main); font-size: 1.8em; max-width: 85%; word-break: break-word; line-height: 1.3;">
                        <?php echo htmlspecialchars($post['title']); ?>
                    </h1>
                    <div style="display: flex; gap: 20px; color: var(--nj-text-muted); font-size: 0.9em; align-items: center;">
                        <span><i class="fas fa-user-circle" style="color: var(--nj-gold); margin-right:5px;"></i> <?php echo htmlspecialchars($post['username']); ?></span>
                        <span><i class="fas fa-clock" style="margin-right:5px;"></i> <?php echo date('d M Y, H:i', strtotime($post['created_at'])); ?></span>
                        <span><i class="fas fa-eye" style="margin-right:5px;"></i> <?php echo $post['views']; ?> Vistas</span>
                    </div>
                </div>

                <div class="nj-post-content" style="line-height: 1.8; color: var(--nj-text-main); font-size: 1.05em; white-space: pre-wrap; word-break: break-word;">
                    <?php echo htmlspecialchars($post['content']); ?>
                </div>
            </article>

            <div style="margin: 40px 0 20px 0; border-bottom: 1px solid var(--nj-border); padding-bottom: 10px; display:flex; justify-content:space-between; align-items:flex-end;">
                <h3 style="color: var(--nj-text-main); font-size: 1.1em; letter-spacing: 1px; margin: 0;">RESPUESTAS (<?php echo count($replies); ?>)</h3>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <?php foreach($replies as $index => $reply): ?>
                    <div class="nj-sidebar-card" style="padding: 25px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px dashed var(--nj-border); padding-bottom: 15px;">
                            <div style="display: flex; gap: 10px; align-items: center; color: var(--nj-text-muted); font-size: 0.9em;">
                                <i class="fas fa-user-circle" style="color: #4A5056; font-size: 1.8em;"></i>
                                <span style="color: var(--nj-text-main); font-weight: 500; font-size: 1.1em;"><?php echo htmlspecialchars($reply['username']); ?></span>
                                <span style="margin-left: 10px;"><i class="fas fa-clock" style="margin-right:5px; font-size:0.8em;"></i><?php echo date('d M Y, H:i', strtotime($reply['created_at'])); ?></span>
                            </div>
                            <div style="color: var(--nj-border); font-size: 1em; font-weight: bold; user-select: none;">#<?php echo $index + 1; ?></div>
                        </div>
                        <div style="line-height: 1.7; color: var(--nj-text-main); white-space: pre-wrap; word-break: break-word;">
                            <?php echo htmlspecialchars($reply['content']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="nj-sidebar-card" style="margin-top: 40px; padding: 30px;">
                <?php if (is_logged_in()): ?>
                    <h3 style="margin: 0 0 20px 0; color: var(--nj-gold); font-size: 1.1em; text-transform: uppercase; letter-spacing: 1px;"><i class="fas fa-reply"></i> Añadir Respuesta</h3>
                    <form method="POST">
                        <textarea name="reply_content" class="nj-input" rows="5" required placeholder="Escribe tu respuesta aquí..." style="resize:vertical;"></textarea>
                        <div style="text-align: right; margin-top: 20px;">
                            <button type="submit" class="nj-btn-primary" style="width: auto;"><i class="fas fa-paper-plane"></i> Publicar Respuesta</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px 0;">
                        <p style="color: var(--nj-text-muted); margin-bottom: 20px; font-size: 1.1em;">Debes iniciar sesión para unirte a la discusión.</p>
                        <a href="login.php" class="nj-btn-primary" style="display: inline-block; width: auto;">Iniciar sesión</a>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
    
    <footer class="nj-footer">
        <p>NARAKA BLADEPOINT WUXIA ARCHIVES © 2026</p>
    </footer>
</div>

<style>
/* 继承统一基准体系 */
:root {
    --nj-bg: #0B0A0A; --nj-module: #161413; --nj-module-hover: #1E1B19;    
    --nj-red: #D12323; --nj-gold: #CCA677; --nj-border: #2D2926;          
    --nj-text-main: #E6E4DF; --nj-text-muted: #8F98A0; 
    --font-main: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}
body { background-color: var(--nj-bg) !important; color: var(--nj-text-main); font-family: var(--font-main); margin: 0; padding: 0; overflow-x: hidden; }
.nj-static-bg { position: fixed; inset: 0; z-index: -10; background-color: var(--nj-bg); background-image: radial-gradient(circle at 10% 20%, rgba(209, 35, 35, 0.04), transparent 50%), radial-gradient(circle at 90% 80%, rgba(204, 166, 119, 0.03), transparent 50%); background-blend-mode: screen; }
.nj-static-bg::after { content: ''; position: absolute; inset: 0; background: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.04'/%3E%3C/svg%3E"); pointer-events: none; }
.nj-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; min-height: 100vh; display: flex; flex-direction: column;}
.nj-header { margin-top: 40px; margin-bottom: 30px; }
.nj-layout { display: flex; flex: 1; }
.nj-main { flex: 1; width: 100%; }
.nj-sidebar-card { background: var(--nj-module); border: 1px solid var(--nj-border); border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);}
.nj-input { width: 100%; padding: 15px; background: rgba(0,0,0,0.4); border: 1px solid var(--nj-border); border-radius: 6px; color: var(--nj-text-main); font-family: var(--font-main); outline: none; transition: 0.2s; box-sizing: border-box; font-size: 1em;}
.nj-input:focus { border-color: var(--nj-gold); background: var(--nj-bg);}
.nj-btn-primary { display: inline-block; text-align: center; background: var(--nj-red); color: #fff; padding: 12px 25px; text-decoration: none; font-size: 0.95em; border-radius: 6px; font-weight: bold; transition: background 0.2s; border: none; cursor: pointer;}
.nj-btn-primary:hover { background: #b81c1c; }
.nj-btn-secondary { display: inline-block; text-align: center; background: transparent; border: 1px solid var(--nj-border); color: var(--nj-text-main); padding: 12px 25px; text-decoration: none; font-size: 0.95em; border-radius: 6px; transition: 0.2s; cursor: pointer;}
.nj-btn-secondary:hover { background: var(--nj-module-hover); border-color: var(--nj-text-muted); }
.nj-alert { padding: 15px; background: rgba(209, 35, 35, 0.1); border: 1px solid var(--nj-red); color: var(--nj-text-main); border-radius: 8px; margin-bottom: 20px; font-size: 0.9em;}
.nj-footer { margin-top: 60px; padding: 40px 0; border-top: 1px solid var(--nj-border); text-align: center; color: var(--nj-text-muted); font-size: 0.8em; letter-spacing: 1px;}
</style>

<?php include 'includes/footer.php'; ?>