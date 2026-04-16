<?php
// article.php - 100% 完整版 (包含历史版本回滚、点赞、评论、暗黑武侠UI)
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

    // ==========================================
    // P1-5: 处理版本回滚请求 (时光机)
    // ==========================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revert_version_id'])) {
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            $_SESSION['flash_error'] = "Error de CSRF.";
        } elseif ($user_id == $article['user_id']) { // 仅作者可回滚
            $vid = intval($_POST['revert_version_id']);
            $v_stmt = $pdo->prepare("SELECT * FROM wiki_article_versions WHERE id = ? AND article_id = ?");
            $v_stmt->execute([$vid, $id]);
            $history_data = $v_stmt->fetch();
            
            if ($history_data) {
                // 回滚前，将当前错误的状态也保存一份快照，防止手滑
                $pdo->prepare("INSERT INTO wiki_article_versions (article_id, user_id, title, content, edit_summary, created_at) VALUES (?, ?, ?, ?, ?, NOW())")
                    ->execute([$id, $article['user_id'], $article['title'], $article['content'], 'Auto-save antes de revertir']);
                
                // 执行回滚
                $pdo->prepare("UPDATE articles SET title = ?, content = ? WHERE id = ?")
                    ->execute([$history_data['title'], $history_data['content'], $id]);
                
                $_SESSION['flash_message'] = "La guía ha sido revertida a una versión anterior con éxito.";
                header("Location: article.php?id=" . $id);
                exit;
            }
        }
    }

    // 3. 处理点赞 / 取消点赞 
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_like']) && is_logged_in()) {
        if (isset($_POST['csrf_token']) && verify_csrf_token($_POST['csrf_token'])) {
            $check_like = $pdo->prepare("SELECT id FROM article_likes WHERE article_id = ? AND user_id = ?");
            $check_like->execute([$id, $user_id]);
            if ($check_like->fetch()) {
                $pdo->prepare("DELETE FROM article_likes WHERE article_id = ? AND user_id = ?")->execute([$id, $user_id]);
            } else {
                $pdo->prepare("INSERT INTO article_likes (article_id, user_id) VALUES (?, ?)")->execute([$id, $user_id]);
            }
            header("Location: article.php?id=" . $id);
            exit;
        }
    }

    // 4. 处理发表评论
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content']) && is_logged_in()) {
        if (isset($_POST['csrf_token']) && verify_csrf_token($_POST['csrf_token'])) {
            $content = trim($_POST['comment_content']);
            if (!empty($content)) {
                $stmt = $pdo->prepare("INSERT INTO article_comments (article_id, user_id, content) VALUES (?, ?, ?)");
                $stmt->execute([$id, $user_id, $content]);
                header("Location: article.php?id=" . $id . "#comments");
                exit;
            }
        }
    }

    // 5. 获取统计和列表数据
    $likes = $pdo->prepare("SELECT COUNT(*) FROM article_likes WHERE article_id = ?");
    $likes->execute([$id]);
    $likes_count = $likes->fetchColumn();

    $user_liked = false;
    if ($user_id) {
        $check = $pdo->prepare("SELECT id FROM article_likes WHERE article_id = ? AND user_id = ?");
        $check->execute([$id, $user_id]);
        $user_liked = (bool)$check->fetch();
    }

    $comments = $pdo->prepare("SELECT c.*, u.username, u.avatar FROM article_comments c LEFT JOIN users u ON c.user_id = u.id WHERE c.article_id = ? ORDER BY c.created_at DESC");
    $comments->execute([$id]);
    $comments_list = $comments->fetchAll();

    // P1-5: 获取历史版本列表
    $versions_stmt = $pdo->prepare("SELECT v.*, u.username FROM wiki_article_versions v LEFT JOIN users u ON v.user_id = u.id WHERE v.article_id = ? ORDER BY v.created_at DESC");
    $versions_stmt->execute([$id]);
    $versions_list = $versions_stmt->fetchAll();

    // 解析 YouTube 视频
    function get_youtube_id($url) {
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
        return $match[1] ?? false;
    }
    $youtube_id = !empty($article['video_url']) ? get_youtube_id($article['video_url']) : false;

} catch (Exception $e) { die("Error: " . $e->getMessage()); }
?>
<?php include 'includes/header.php'; ?>

<div class="nj-static-bg"></div>

<div class="nj-container">
    <header class="nj-header" style="border-bottom:none; margin-bottom: 10px; margin-top: 30px;">
        <a href="guides.php" style="color:var(--nj-gold); text-decoration:none; font-size:0.9em; font-weight:bold;">
            <i class="fas fa-arrow-left"></i> Volver a Guías
        </a>
    </header>

    <div class="nj-layout">
        <main class="nj-main" style="max-width: 1000px; margin: 0 auto;">

            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="nj-alert" style="border-color: #28a745; background: rgba(40,167,69,0.1); color:#E6E4DF;">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
                </div>
            <?php endif; ?>

            <article class="nj-sidebar-card" style="position: relative; padding: 40px;">
                <?php if ($user_id == $article['user_id']): ?>
                    <div style="position: absolute; right: 30px; top: 30px; display: flex; gap: 10px;">
                        <a href="edit-guide.php?id=<?php echo $id; ?>" class="nj-btn-secondary" style="padding: 8px 15px; font-size: 0.85em;"><i class="fas fa-edit"></i> Editar</a>
                        <a href="delete-guide.php?id=<?php echo $id; ?>" onclick="return confirm('¿Seguro que deseas eliminar esta guía?');" class="nj-btn-secondary" style="padding: 8px 15px; font-size: 0.85em; color: var(--nj-red); border-color: rgba(209,35,35,0.3);"><i class="fas fa-trash"></i></a>
                    </div>
                <?php endif; ?>

                <div style="border-bottom: 1px solid var(--nj-border); padding-bottom: 25px; margin-bottom: 30px;">
                    <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                        <span class="nj-badge-cat"><?php echo htmlspecialchars(strtoupper($article['category'])); ?></span>
                        <?php 
                            $diff_color = ['beginner'=>'#28a745', 'intermediate'=>'#ffc107', 'advanced'=>'#dc3545'];
                            $diff_label = ['beginner'=>'Principiante', 'intermediate'=>'Intermedio', 'advanced'=>'Avanzado'];
                            $bg = $diff_color[$article['difficulty']] ?? '#ccc';
                            $lbl = $diff_label[$article['difficulty']] ?? 'N/A';
                            $text_c = ($article['difficulty'] == 'intermediate') ? '#000' : '#fff';
                        ?>
                        <span style="background: <?php echo $bg; ?>; color: <?php echo $text_c; ?>; padding: 4px 10px; border-radius: 4px; font-size: 0.8em; font-weight: bold;"><?php echo $lbl; ?></span>
                    </div>
                    
                    <h1 class="nj-post-title"><?php echo htmlspecialchars($article['title']); ?></h1>
                    
                    <div class="nj-post-info">
                        <span><i class="fas fa-user-ninja"></i> <?php echo htmlspecialchars($article['username']); ?></span>
                        <span><i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($article['created_at'])); ?></span>
                        <span><i class="fas fa-eye"></i> <?php echo $article['views']; ?> Vistas</span>
                        <span><i class="fas fa-heart" style="color:var(--nj-red);"></i> <?php echo $likes_count; ?> Likes</span>
                    </div>
                </div>

                <?php if ($youtube_id): ?>
                    <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 8px; margin-bottom: 30px; border: 1px solid var(--nj-border);">
                        <iframe src="https://www.youtube.com/embed/<?php echo $youtube_id; ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" frameborder="0" allowfullscreen></iframe>
                    </div>
                <?php endif; ?>

                <div class="nj-post-content">
                    <?php echo nl2br(htmlspecialchars($article['content'])); ?>
                </div>

                <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--nj-border);">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="toggle_like" value="1">
                        <button type="submit" class="nj-interact-btn <?php echo $user_liked ? 'active' : ''; ?>">
                            <i class="<?php echo $user_liked ? 'fas' : 'far'; ?> fa-heart"></i> 
                            <?php echo $user_liked ? 'Te gusta' : 'Me gusta'; ?> (<?php echo $likes_count; ?>)
                        </button>
                    </form>
                </div>
            </article>

            <?php if (!empty($versions_list)): ?>
                <div class="nj-replies-divider">
                    <h3><i class="fas fa-history"></i> HISTORIAL DE VERSIONES</h3>
                </div>
                <div class="nj-sidebar-card" style="padding: 25px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9em; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--nj-border); color: var(--nj-text-muted);">
                                <th style="padding: 10px;">Fecha</th>
                                <th style="padding: 10px;">Editor</th>
                                <th style="padding: 10px;">Resumen de Edición</th>
                                <?php if ($user_id == $article['user_id']): ?>
                                    <th style="padding: 10px; text-align: right;">Acción</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($versions_list as $v): ?>
                                <tr style="border-bottom: 1px dashed rgba(45,41,38,0.5);">
                                    <td style="padding: 12px 10px; color: var(--nj-gold);"><?php echo date('d M Y, H:i', strtotime($v['created_at'])); ?></td>
                                    <td style="padding: 12px 10px;"><i class="fas fa-user-edit" style="color: var(--nj-text-muted);"></i> <?php echo htmlspecialchars($v['username']); ?></td>
                                    <td style="padding: 12px 10px; color: var(--nj-text-muted);">
                                        <em><?php echo $v['edit_summary'] ? htmlspecialchars($v['edit_summary']) : 'Sin resumen'; ?></em>
                                    </td>
                                    <?php if ($user_id == $article['user_id']): ?>
                                        <td style="padding: 12px 10px; text-align: right;">
                                            <form method="POST" onsubmit="return confirm('¿Seguro que quieres revertir la guía a esta versión del <?php echo date('d M, H:i', strtotime($v['created_at'])); ?>? Se sobrescribirá el contenido actual.');">
                                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                <input type="hidden" name="revert_version_id" value="<?php echo $v['id']; ?>">
                                                <button type="submit" class="nj-btn-secondary" style="padding: 4px 10px; font-size: 0.8em;"><i class="fas fa-undo"></i> Revertir</button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="nj-replies-divider" id="comments">
                <h3><i class="fas fa-comments"></i> COMENTARIOS (<?php echo count($comments_list); ?>)</h3>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <?php foreach($comments_list as $comment): ?>
                    <div class="nj-sidebar-card" style="padding: 20px;">
                        <div class="nj-reply-header" style="margin-bottom: 10px;">
                            <div class="nj-reply-user">
                                <i class="fas fa-user-circle"></i>
                                <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                <span><i class="fas fa-clock"></i> <?php echo date('d M Y, H:i', strtotime($comment['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="nj-reply-body">
                            <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="nj-sidebar-card" style="margin-top: 30px; padding: 30px;">
                <?php if (is_logged_in()): ?>
                    <h3 style="margin: 0 0 20px 0; color: var(--nj-gold); font-size: 1em;"><i class="fas fa-pen"></i> Deja un comentario</h3>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <textarea name="comment_content" class="nj-input" rows="4" required placeholder="¿Qué opinas de esta guía?"></textarea>
                        <div style="text-align: right; margin-top: 15px;">
                            <button type="submit" class="nj-btn-primary">Publicar</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px;">
                        <p style="color: var(--nj-text-muted); margin-bottom: 15px;">Debes iniciar sesión para comentar.</p>
                        <a href="login.php" class="nj-btn-primary">Iniciar sesión</a>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<style>
/* 保持高度统一的 Dark Wuxia 样式 */
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
.nj-post-info { display: flex; flex-wrap: wrap; gap: 20px; color: var(--nj-text-muted); font-size: 0.9em; }
.nj-post-info i { color: var(--nj-gold); margin-right: 5px; }
.nj-post-content { line-height: 1.8; color: var(--nj-text-main); font-size: 1.05em; }
.nj-interact-btn { background: transparent; border: 1px solid var(--nj-border); color: var(--nj-text-muted); padding: 8px 18px; border-radius: 4px; cursor: pointer; transition: 0.2s; font-size: 0.9em; font-weight: 600; }
.nj-interact-btn:hover { background: var(--nj-border); color: var(--nj-text-main); }
.nj-interact-btn.active { border-color: var(--nj-red); color: var(--nj-red); background: rgba(209, 35, 35, 0.05); }
.nj-replies-divider { margin: 40px 0 20px 0; border-bottom: 1px solid var(--nj-border); padding-bottom: 10px; }
.nj-replies-divider h3 { color: var(--nj-text-main); font-size: 1em; margin: 0; letter-spacing: 1px; }
.nj-reply-user { display: flex; gap: 10px; align-items: center; color: var(--nj-text-muted); font-size: 0.9em; }
.nj-reply-user strong { color: var(--nj-text-main); font-size: 1.1em; }
.nj-reply-body { line-height: 1.6; color: var(--nj-text-main); font-size: 0.95em; }
</style>

<?php include 'includes/footer.php'; ?>