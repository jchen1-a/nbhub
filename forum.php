<?php
// forum.php - 100% 完整版 (极简高级版：移除多余统计，统一霸气标题排版)
require_once 'config.php';

// 辅助函数：获取国旗emoji
function get_flag($country_code) {
    $flags = [
        'ES' => '🇪🇸', 'MX' => '🇲🇽', 'AR' => '🇦🇷', 'US' => '🇺🇸',
        'BR' => '🇧🇷', 'FR' => '🇫🇷', 'DE' => '🇩🇪', 'UK' => '🇬🇧',
        'IT' => '🇮🇹', 'JP' => '🇯🇵', 'KR' => '🇰🇷', 'CN' => '🇨🇳'
    ];
    return $flags[$country_code] ?? '🌐';
}

$current_page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$search = sanitize($_GET['search'] ?? '');
$category = sanitize($_GET['category'] ?? '');
$sort = sanitize($_GET['sort'] ?? 'newest');

$posts = [];
$categories = [];
$total_posts = 0;
$total_pages = 1;

try {
    $pdo = db_connect();
    
    // 构建查询条件
    $where = [];
    $params = [];
    
    if (!empty($search)) {
        $where[] = "(title LIKE ? OR content LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if (!empty($category) && $category !== 'all') {
        $where[] = "category = ?";
        $params[] = $category;
    }
    
    $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // 获取帖子总数
    $count_sql = "SELECT COUNT(*) as total FROM forum_posts $where_sql";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_posts = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_posts / $per_page);
    
    // 获取帖子列表
    $offset = ($current_page - 1) * $per_page;
    
    $posts_sql = "
        SELECT p.*, 
               u.username as author_name,
               u.country as author_country,
               (SELECT COUNT(*) FROM forum_replies WHERE post_id = p.id) as reply_count,
               (SELECT username FROM users WHERE id = p.last_reply_by) as last_replier,
               p.last_reply_at
        FROM forum_posts p
        LEFT JOIN users u ON p.user_id = u.id
        $where_sql
        ORDER BY p.is_pinned DESC, p.last_reply_at DESC
        LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;
    
    $posts_stmt = $pdo->prepare($posts_sql);
    $posts_stmt->execute($params);
    $posts = $posts_stmt->fetchAll();
    
    // 获取分类统计
    $categories = $pdo->query("
        SELECT category, COUNT(*) as count 
        FROM forum_posts 
        GROUP BY category 
        ORDER BY count DESC
    ")->fetchAll();
    
} catch (Exception $e) {
    $error = "Error al cargar el foro: " . $e->getMessage();
}
?>
<?php include 'includes/header.php'; ?>

<div class="fixed-blurred-bg"></div>

<div class="wiki-glass-container">
    
    <div class="home-header" style="padding-top: 30px; padding-bottom: 25px; text-align: center;">
        <h1 class="brush-font fallback-title" style="font-size: 4em; margin: 0; color: #fff; text-shadow: 3px 3px 0px var(--accent); font-family: 'Cinzel', serif; text-transform: uppercase;">Salón de la Comunidad</h1>
    </div>

    <div style="display: flex; justify-content: center; margin-bottom: 50px;">
        <?php if (is_logged_in()): ?>
            <a href="new-post.php" class="btn-hero btn-hero-primary"><i class="fas fa-plus"></i> Iniciar Discusión</a>
        <?php else: ?>
            <a href="login.php" class="btn-hero btn-hero-secondary" style="border-color: #555;"><i class="fas fa-sign-in-alt"></i> Entrar para Discutir</a>
        <?php endif; ?>
    </div>

    <div class="wiki-layout">
        
        <aside class="wiki-sidebar">
            
            <div class="glass-card" style="margin-bottom: 25px;">
                <h3 class="card-glass-title"><i class="fas fa-search"></i> Explorar Foro</h3>
                
                <form method="GET" class="forum-filter-form">
                    <div class="ink-search-form" style="margin-bottom: 20px;">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar tema...">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label class="filter-subtitle" style="display:block;">Categoría</label>
                        <select name="category" class="glass-select" onchange="this.form.submit()">
                            <option value="all">Todas las categorías</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category == $cat['category'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($cat['category'])); ?> (<?php echo $cat['count']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="filter-subtitle" style="display:block;">Ordenar Por</label>
                        <select name="sort" class="glass-select" onchange="this.form.submit()">
                            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Más recientes</option>
                            <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Más populares</option>
                            <option value="unanswered" <?php echo $sort == 'unanswered' ? 'selected' : ''; ?>>Sin respuesta</option>
                        </select>
                    </div>
                </form>
            </div>

            <div class="glass-card" style="margin-bottom: 25px;">
                <h3 class="card-glass-title"><i class="fas fa-tags"></i> Etiquetas Populares</h3>
                <div class="glass-tags-list">
                    <?php
                    try {
                        $tags = $pdo->query("SELECT tag, COUNT(*) as count FROM forum_tags GROUP BY tag ORDER BY count DESC LIMIT 10")->fetchAll();
                        if($tags) {
                            foreach ($tags as $tag):
                    ?>
                    <a href="?search=<?php echo urlencode($tag['tag']); ?>" class="glass-tag">
                        <?php echo htmlspecialchars($tag['tag']); ?>
                        <span class="tag-count"><?php echo $tag['count']; ?></span>
                    </a>
                    <?php 
                            endforeach;
                        } else {
                            echo "<span style='color:#777;font-size:0.9em;'>Aún no hay etiquetas.</span>";
                        }
                    } catch (Exception $e) {
                        echo "<span style='color:#777;font-size:0.9em;'>Aún no hay etiquetas.</span>";
                    }
                    ?>
                </div>
            </div>

            <div class="glass-card">
                <h3 class="card-glass-title"><i class="fas fa-gavel"></i> Leyes de Morus</h3>
                <ul class="glass-rules-list">
                    <li><i class="fas fa-check"></i> Respeta a todos los guerreros.</li>
                    <li><i class="fas fa-check"></i> Nada de spam ni contenido oscuro.</li>
                    <li><i class="fas fa-check"></i> Usa las categorías correctas.</li>
                    <li><i class="fas fa-check"></i> Busca antes de crear un tema.</li>
                </ul>
            </div>
            
        </aside>

        <main class="wiki-main-content">
            
            <?php if (isset($error)): ?>
                <div class="glass-alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="glass-card" style="padding: 0; overflow: hidden;">
                <?php if (empty($posts)): ?>
                    <div class="empty-glass-state">
                        <i class="fas fa-comments-slash"></i>
                        <p>El silencio domina este lugar... No hay temas de discusión.</p>
                        <?php if (is_logged_in()): ?>
                            <a href="new-post.php" class="btn-ink-outline" style="margin-top: 15px; display: inline-block;">Ser el Primero</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="glass-post-list">
                        <?php foreach ($posts as $post): ?>
                            <div class="glass-post-row <?php echo $post['is_pinned'] ? 'pinned-row' : ''; ?>">
                                
                                <div class="post-main-col">
                                    <div style="display: flex; gap: 8px; margin-bottom: 8px; align-items: center; flex-wrap: wrap;">
                                        <?php if ($post['is_pinned']): ?>
                                            <span class="glass-badge warning-badge"><i class="fas fa-thumbtack"></i> Fijado</span>
                                        <?php endif; ?>
                                        <?php if ($post['reply_count'] == 0): ?>
                                            <span class="glass-badge success-badge"><i class="fas fa-star"></i> Nuevo</span>
                                        <?php endif; ?>
                                        <span class="glass-badge category-badge"><?php echo htmlspecialchars($post['category'] ?? 'General'); ?></span>
                                    </div>
                                    
                                    <h4 class="post-title">
                                        <a href="view-post.php?id=<?php echo $post['id']; ?>">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </a>
                                    </h4>
                                    
                                    <div class="post-meta">
                                        <span>Por <a href="profile.php?user=<?php echo $post['user_id']; ?>" class="author-link"><?php echo htmlspecialchars($post['author_name']); ?></a></span>
                                        <?php if ($post['author_country']): ?>
                                            <span><?php echo get_flag($post['author_country']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="post-stats-col">
                                    <div class="stat-mini" title="Respuestas"><i class="fas fa-reply"></i> <?php echo $post['reply_count']; ?></div>
                                    <div class="stat-mini" title="Vistas"><i class="fas fa-eye"></i> <?php echo $post['views'] ?? 0; ?></div>
                                </div>

                                <div class="post-last-col">
                                    <?php if ($post['last_reply_at']): ?>
                                        <div class="last-author"><i class="fas fa-user-edit"></i> <?php echo htmlspecialchars($post['last_replier']); ?></div>
                                        <div class="last-time"><?php echo date('d/m/Y H:i', strtotime($post['last_reply_at'])); ?></div>
                                    <?php else: ?>
                                        <span style="color:#777; font-style:italic; font-size: 0.9em;">Sin respuestas</span>
                                    <?php endif; ?>
                                </div>
                                
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="glass-pagination">
                <?php if ($current_page > 1): ?>
                    <a href="?page=1&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>" class="glass-page-link"><i class="fas fa-angle-double-left"></i></a>
                    <a href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>" class="glass-page-link"><i class="fas fa-angle-left"></i></a>
                <?php endif; ?>
                
                <?php
                $start = max(1, $current_page - 2);
                $end = min($total_pages, $current_page + 2);
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>" 
                       class="glass-page-link <?php echo $i == $current_page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>" class="glass-page-link"><i class="fas fa-angle-right"></i></a>
                    <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>" class="glass-page-link"><i class="fas fa-angle-double-right"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<style>
/* ================= 全局模糊底层与毛玻璃 Wiki 样式 ================= */

body {
    background-color: #0a0a0c !important;
    color: #fff;
    overflow: auto !important; 
}

/* 固定的全屏模糊底层 */
.fixed-blurred-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: url('assets/cover.jpg?v=<?php echo time(); ?>') no-repeat center 20%;
    background-size: cover;
    filter: blur(15px) brightness(0.25) contrast(1.2);
    z-index: -10; 
    pointer-events: none !important; 
}

/* 容器限制 */
.wiki-glass-container {
    max-width: 1200px;
    margin: 0 auto 80px auto;
    padding: 0 20px;
    position: relative;
    z-index: 10;
}

/* 毛玻璃卡片通用类 */
.glass-card {
    background: rgba(15, 15, 18, 0.65);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 4px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}

/* 两列核心布局 */
.wiki-layout {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 30px;
    align-items: start;
}

/* 左侧栏样式 */
.card-glass-title {
    font-family: 'Cinzel', serif;
    color: #fff;
    margin-top: 0;
    margin-bottom: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    padding-bottom: 10px;
    font-size: 1.2em;
    display: flex;
    align-items: center;
    gap: 10px;
}
.card-glass-title i { color: var(--accent); }

/* 下拉菜单 */
.glass-select {
    width: 100%;
    background: rgba(0, 0, 0, 0.4);
    color: #ddd;
    border: 1px solid rgba(255,255,255,0.1);
    padding: 12px;
    border-radius: 2px;
    font-size: 0.9em;
    outline: none;
    transition: 0.3s;
}
.glass-select:focus { border-color: var(--accent); }
.glass-select option { background: #1a1a1a; color: #fff; }

.filter-subtitle {
    color: #aaa;
    font-family: 'Segoe UI', sans-serif;
    font-size: 0.85em;
    margin-bottom: 8px;
    text-transform: uppercase;
    font-weight: bold;
}

/* 搜索框 */
.ink-search-form {
    display: flex;
    background: rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 2px;
    overflow: hidden;
    transition: 0.3s;
}
.ink-search-form:focus-within { border-color: var(--accent); }
.ink-search-form input {
    flex: 1;
    padding: 12px;
    border: none;
    background: transparent;
    color: #fff;
    outline: none;
}
.ink-search-form button {
    padding: 0 15px;
    background: var(--accent);
    color: white;
    border: none;
    cursor: pointer;
    transition: 0.3s;
}
.ink-search-form button:hover { background: #a30000; }

/* 标签列表 */
.glass-tags-list { display: flex; flex-wrap: wrap; gap: 8px; }
.glass-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.05);
    color: #ccc;
    padding: 6px 12px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 0.85em;
    border: 1px solid rgba(255,255,255,0.05);
    transition: all 0.3s;
}
.glass-tag:hover {
    background: rgba(201, 20, 20, 0.2);
    border-color: var(--accent);
    color: #fff;
}
.tag-count {
    background: rgba(0,0,0,0.5);
    color: var(--accent);
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 0.8em;
    font-weight: bold;
}

/* 论坛规则 */
.glass-rules-list { list-style: none; padding: 0; margin: 0; color: #aaa; font-size: 0.9em; line-height: 1.6; }
.glass-rules-list li { margin-bottom: 10px; display: flex; align-items: flex-start; gap: 10px; }
.glass-rules-list li i { color: var(--accent); margin-top: 4px; font-size: 0.8em; }

/* 帖子行样式 */
.glass-post-row {
    display: grid;
    grid-template-columns: 1fr 120px 200px;
    gap: 20px;
    padding: 20px 30px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    transition: background 0.2s;
    align-items: center;
}
.glass-post-row:last-child { border-bottom: none; }
.glass-post-row:hover { background: rgba(255,255,255,0.03); }
.pinned-row { background: rgba(245, 127, 23, 0.03); border-left: 3px solid #f57f17; }

.glass-badge {
    padding: 3px 8px;
    border-radius: 2px;
    font-size: 0.7em;
    font-weight: bold;
    text-transform: uppercase;
    font-family: 'Segoe UI', sans-serif;
    border: 1px solid rgba(255,255,255,0.1);
    background: rgba(0,0,0,0.4);
    color: #ddd;
}
.warning-badge { color: #f57f17; border-color: rgba(245, 127, 23, 0.4); }
.success-badge { color: #4caf50; border-color: rgba(76, 175, 80, 0.4); }
.category-badge { color: #aaa; }

.post-title { margin: 0 0 8px 0; font-size: 1.15em; line-height: 1.4; }
.post-title a { color: #eee; text-decoration: none; transition: color 0.2s; }
.post-title a:hover { color: var(--accent); }

.post-meta { font-size: 0.85em; color: #777; display: flex; align-items: center; gap: 10px; }
.author-link { color: #aaa; text-decoration: none; font-weight: bold; transition: 0.2s; }
.author-link:hover { color: var(--accent); }

.post-stats-col { display: flex; gap: 15px; justify-content: flex-end; color: #888; font-size: 0.9em; }
.stat-mini { display: flex; align-items: center; gap: 6px; }

.post-last-col { text-align: right; }
.last-author { color: #ccc; font-size: 0.9em; margin-bottom: 4px; }
.last-author i { color: var(--accent); font-size: 0.8em; margin-right: 5px; }
.last-time { color: #666; font-size: 0.8em; }

/* 分页模块 */
.glass-pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 30px;
}
.glass-page-link {
    padding: 8px 14px;
    background: rgba(15, 15, 18, 0.8);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 2px;
    color: #ccc;
    text-decoration: none;
    transition: all 0.3s;
    font-weight: bold;
    font-family: 'Segoe UI', sans-serif;
}
.glass-page-link:hover {
    background: rgba(201, 20, 20, 0.2);
    border-color: var(--accent);
    color: #fff;
}
.glass-page-link.active {
    background: var(--accent);
    color: #fff;
    border-color: var(--accent);
}

/* 空状态 */
.empty-glass-state { text-align: center; padding: 60px 20px; color: #666; }
.empty-glass-state i { font-size: 3.5em; margin-bottom: 20px; opacity: 0.5; }

/* 按钮通用 */
.btn-hero { 
    padding: 12px 25px; 
    font-size: 0.95em; 
    font-weight: 700; 
    text-decoration: none; 
    transition: all 0.3s ease; 
    display: inline-flex; 
    align-items: center; 
    gap: 10px; 
    border: none; 
    text-transform: uppercase; 
    font-family: 'Cinzel', serif; 
    letter-spacing: 1px;
    border-radius: 2px;
}
.btn-hero-primary { background: rgba(201, 20, 20, 0.8); color: white; border: 1px solid var(--accent); backdrop-filter: blur(5px); }
.btn-hero-primary:hover { background: var(--accent); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(204,0,0,0.4); }
.btn-hero-secondary { background: rgba(0, 0, 0, 0.5); color: white; border: 1px solid var(--accent); backdrop-filter: blur(5px); }
.btn-hero-secondary:hover { background: var(--accent); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(204,0,0,0.4); }
.btn-ink-outline { background: transparent; color: var(--accent); border: 1px solid var(--accent); padding: 10px 20px; text-decoration: none; font-weight: bold; border-radius: 2px; transition: 0.3s; }
.btn-ink-outline:hover { background: var(--accent); color: white; }

@media (max-width: 900px) {
    .wiki-layout { grid-template-columns: 1fr; }
    .glass-post-row { grid-template-columns: 1fr; gap: 15px; padding: 20px; }
    .post-stats-col, .post-last-col { justify-content: flex-start; text-align: left; }
}
</style>

<script>
// 标记已读交互
document.querySelectorAll('.glass-post-row').forEach(row => {
    row.addEventListener('click', function(e) {
        if (!e.target.closest('a')) {
            this.style.opacity = '0.7';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>