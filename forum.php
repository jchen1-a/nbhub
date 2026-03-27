<?php
// forum.php - Naraka Dark Wuxia (Steam 规整布局 + 官方暖暗配色卡)
require_once 'config.php';

// 辅助函数：正常的国旗emoji
function get_flag($country_code) {
    $flags = [
        'ES' => '🇪🇸', 'MX' => '🇲🇽', 'AR' => '🇦🇷', 'US' => '🇺🇸',
        'BR' => '🇧🇷', 'FR' => '🇫🇷', 'DE' => '🇩🇪', 'UK' => '🇬🇧',
        'IT' => '🇮🇹', 'JP' => '🇯🇵', 'KR' => '🇰🇷', 'CN' => '🇨🇳'
    ];
    return $flags[$country_code] ?? '🌐';
}

$current_page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15; 
$search = sanitize($_GET['search'] ?? '');
$category = sanitize($_GET['category'] ?? '');
$sort = sanitize($_GET['sort'] ?? 'newest');

$posts = [];
$categories = [];
$total_posts = 0;
$total_pages = 1;

try {
    $pdo = db_connect();
    
    $where = [];
    $params = [];
    if (!empty($search)) { $where[] = "(title LIKE ? OR content LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
    if (!empty($category) && $category !== 'all') { $where[] = "category = ?"; $params[] = $category; }
    $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $count_sql = "SELECT COUNT(*) as total FROM forum_posts $where_sql";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_posts = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_posts / $per_page);
    
    $offset = ($current_page - 1) * $per_page;
    $posts_sql = "
        SELECT p.*, u.username as author_name, u.country as author_country,
               (SELECT COUNT(*) FROM forum_replies WHERE post_id = p.id) as reply_count,
               (SELECT username FROM users WHERE id = p.last_reply_by) as last_replier,
               p.last_reply_at
        FROM forum_posts p LEFT JOIN users u ON p.user_id = u.id
        $where_sql ORDER BY p.is_pinned DESC, p.last_reply_at DESC
        LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;
    $posts_stmt = $pdo->prepare($posts_sql);
    $posts_stmt->execute($params);
    $posts = $posts_stmt->fetchAll();
    
    $categories = $pdo->query("SELECT category, COUNT(*) as count FROM forum_posts GROUP BY category ORDER BY count DESC")->fetchAll();
    
} catch (Exception $e) { $error = "Error de base de datos: " . $e->getMessage(); }
?>
<?php include 'includes/header.php'; ?>

<div class="nj-static-bg"></div>

<div class="nj-app-container">
    
    <header class="nj-app-header">
        <div class="nj-brand">
            <div class="nj-logo-icon">武</div>
            <div class="nj-logo-text">
                <h1>FORO DE LA COMUNIDAD</h1>
                <p>M A R T I A L &nbsp; A R C H I V E S</p>
            </div>
        </div>
        
        <div class="nj-search-box">
            <form method="GET" action="forum.php" class="nj-search-form">
                <?php if(!empty($category) && $category !== 'all'): ?>
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                <?php endif; ?>
                <input type="text" name="search" placeholder="Buscar discusiones..." value="<?php echo htmlspecialchars($search); ?>" class="nj-search-input">
                <button type="submit" class="nj-search-btn"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </header>

    <div class="nj-app-body">
        
        <aside class="nj-app-sidebar">
            <div class="nj-sidebar-section">
                <a href="new-post.php" class="nj-btn-primary action-bounce">
                    <i class="fas fa-pen"></i> NUEVO TEMA
                </a>
                <?php if (!is_logged_in()): ?>
                    <a href="login.php" class="nj-btn-secondary action-bounce" style="margin-top: 10px;">
                        <i class="fas fa-sign-in-alt"></i> INICIAR SESIÓN
                    </a>
                <?php endif; ?>
            </div>

            <div class="nj-sidebar-section">
                <h3 class="nj-sidebar-title">DISCUSIONES</h3>
                <nav class="nj-tree-nav">
                    <a href="forum.php" class="nj-tree-link <?php echo empty($category) ? 'active' : ''; ?>">
                        <span class="tree-icon"><i class="fas fa-layer-group"></i></span>
                        Todo
                    </a>
                    <?php foreach($categories as $cat): ?>
                        <a href="forum.php?category=<?php echo urlencode($cat['category']); ?>" class="nj-tree-link <?php echo $category == $cat['category'] ? 'active' : ''; ?>">
                            <span class="tree-icon"><i class="fas fa-hashtag"></i></span>
                            <?php echo htmlspecialchars(ucfirst(strtolower($cat['category']))); ?>
                            <span class="tree-count"><?php echo $cat['count']; ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
            
            <div class="nj-sidebar-section">
                <h3 class="nj-sidebar-title">FILTRAR POR</h3>
                <nav class="nj-tree-nav">
                    <a href="?category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>&sort=newest" class="nj-tree-link <?php echo $sort == 'newest' ? 'active' : ''; ?>">
                        <span class="tree-icon"><i class="fas fa-clock"></i></span> Más recientes
                    </a>
                    <a href="?category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>&sort=popular" class="nj-tree-link <?php echo $sort == 'popular' ? 'active' : ''; ?>">
                        <span class="tree-icon"><i class="fas fa-fire"></i></span> Populares
                    </a>
                </nav>
            </div>
        </aside>

        <main class="nj-app-main">
            
            <?php if (isset($error)): ?>
                <div class="nj-alert-box"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="nj-list-container">
                <div class="nj-list-header">
                    <div class="col-main">TEMA</div>
                    <div class="col-stats">ESTADÍSTICAS</div>
                    <div class="col-last">ÚLTIMO MENSAJE</div>
                </div>

                <?php if (empty($posts)): ?>
                    <div class="nj-empty-list">No se encontraron temas en esta sección.</div>
                <?php else: ?>
                    <div class="nj-post-list">
                        <?php 
                        $delay_index = 0;
                        foreach ($posts as $post): 
                        ?>
                            <a href="view-post.php?id=<?php echo $post['id']; ?>" class="nj-list-row <?php echo $post['is_pinned'] ? 'is-pinned' : ''; ?>" style="--i: <?php echo $delay_index++; ?>;">
                                
                                <div class="col-main row-content">
                                    <div class="row-icon">
                                        <?php if($post['is_pinned']): ?>
                                            <i class="fas fa-thumbtack pin-icon"></i>
                                        <?php else: ?>
                                            <i class="fas fa-file-alt default-icon"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="row-title-area">
                                        <h3 class="row-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                        <div class="row-meta">
                                            <span class="cat-badge"><?php echo htmlspecialchars(strtoupper($post['category'] ?? 'GENERAL')); ?></span>
                                            <span class="author-name"><?php echo htmlspecialchars($post['author_name']); ?></span>
                                            <span class="author-flag"><?php echo get_flag($post['author_country']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-stats row-stats">
                                    <div class="stat-line"><i class="fas fa-comment"></i> <?php echo $post['reply_count']; ?></div>
                                    <div class="stat-line"><i class="fas fa-eye"></i> <?php echo $post['views'] ?? 0; ?></div>
                                </div>
                                
                                <div class="col-last row-last">
                                    <?php if ($post['last_reply_at']): ?>
                                        <div class="last-time"><?php echo date('d M, H:i', strtotime($post['last_reply_at'])); ?></div>
                                        <div class="last-user">por <?php echo htmlspecialchars($post['last_replier']); ?></div>
                                    <?php else: ?>
                                        <div class="last-time"><?php echo date('d M, H:i', strtotime($post['created_at'])); ?></div>
                                        <div class="last-user">por <?php echo htmlspecialchars($post['author_name']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="nj-pagination-bar">
                <?php
                $start = max(1, $current_page - 2);
                $end = min($total_pages, $current_page + 2);
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>" 
                       class="nj-page-link <?php echo $i == $current_page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            
        </main>
    </div>
    
    <footer class="nj-app-footer">
        <p>NARAKA BLADEPOINT WUXIA ARCHIVES © 2026</p>
    </footer>
</div>

<style>
/* ================= Naraka Official Colors × Steam Layout ================= */
@import url('https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@700;900&display=swap');

:root {
    /* 提取自官方图的“暖暗色调” */
    --nj-bg: #0B0A0A;              /* 极深的碳黑 (带有一丝暖意) */
    --nj-module: #161413;          /* 暗岩灰/黑褐色 */
    --nj-module-hover: #211E1C;    /* 模块悬停色 (略亮的暖灰) */
    
    --nj-red: #D12323;             /* 官方殷红/血红 (高饱和明亮) */
    --nj-gold: #CCA677;            /* 黯金/青铜色 */
    
    --nj-border: #2D2926;          /* 暖色调的深灰边框 */
    
    --nj-text-main: #E6E4DF;       /* 骨白/宣纸白 (极其柔和的阅读色) */
    --nj-text-muted: #9C9791;      /* 偏泥土色的暗灰，替代原本偏冷的蓝灰 */
    
    --font-main: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    --font-deco: 'Noto Serif SC', serif;
}

body {
    background-color: var(--nj-bg) !important;
    color: var(--nj-text-main);
    font-family: var(--font-main);
    margin: 0; padding: 0;
    overflow-x: hidden;
}

/* 暖色环境光背景，彻底剔除高斯模糊，0性能损耗 */
.nj-static-bg {
    position: fixed; inset: 0; z-index: -10;
    background-color: var(--nj-bg);
    background-image: 
        radial-gradient(circle at 15% 50%, rgba(209, 35, 35, 0.05), transparent 40%),
        radial-gradient(circle at 85% 30%, rgba(204, 166, 119, 0.04), transparent 40%);
    background-blend-mode: screen;
}
.nj-static-bg::after {
    content: ''; position: absolute; inset: 0;
    background: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.04'/%3E%3C/svg%3E");
    pointer-events: none;
}

.nj-app-container { max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; min-height: 100vh; padding: 0 20px;}

/* ==== 顶部横向 (Logo + 搜索) ==== */
.nj-app-header { display: flex; align-items: center; justify-content: space-between; padding: 30px 0; border-bottom: 1px solid var(--nj-border); margin-bottom: 30px;}
.nj-brand { display: flex; align-items: center; gap: 15px;}
.nj-logo-icon { width: 40px; height: 40px; background: var(--nj-red); color: #fff; display: flex; justify-content: center; align-items: center; font-family: var(--font-deco); font-size: 1.4em; border-radius: 4px; font-weight: 900; box-shadow: 0 0 10px rgba(209, 35, 35, 0.3);}
.nj-logo-text h1 { font-size: 1.3em; margin: 0; color: var(--nj-text-main); font-weight: 700; letter-spacing: 1px;}
.nj-logo-text p { font-size: 0.75em; color: var(--nj-gold); margin: 0; letter-spacing: 2px; opacity: 0.8;}

.nj-search-box { width: 350px; }
.nj-search-form { display: flex; background: rgba(0,0,0,0.4); border: 1px solid var(--nj-border); border-radius: 4px; overflow: hidden; transition: 0.2s;}
.nj-search-form:focus-within { border-color: var(--nj-gold); }
.nj-search-input { flex: 1; background: transparent; border: none; padding: 10px 15px; color: var(--nj-text-main); outline: none; font-family: var(--font-main); font-size: 0.9em;}
.nj-search-btn { background: var(--nj-module); border: none; border-left: 1px solid var(--nj-border); color: var(--nj-text-muted); padding: 0 15px; cursor: pointer; transition: 0.2s;}
.nj-search-btn:hover { color: var(--nj-text-main); background: var(--nj-module-hover);}

/* ==== 主体网格布局 ==== */
.nj-app-body { display: flex; gap: 30px; align-items: flex-start; flex: 1; }

/* 左侧边栏 */
.nj-app-sidebar { width: 240px; flex-shrink: 0; position: sticky; top: 30px;}
.nj-sidebar-section { margin-bottom: 30px; }
.nj-sidebar-title { font-size: 0.8em; color: var(--nj-text-main); margin-bottom: 15px; padding-bottom: 5px; border-bottom: 1px solid var(--nj-border); font-weight: bold; letter-spacing: 1px;}

.nj-tree-nav { display: flex; flex-direction: column; gap: 4px; }
.nj-tree-link { display: flex; align-items: center; padding: 6px 10px; color: var(--nj-text-muted); text-decoration: none; font-size: 0.9em; border-radius: 3px; transition: background 0.2s, color 0.2s;}
.tree-icon { width: 20px; text-align: center; margin-right: 10px; font-size: 0.9em; opacity: 0.7;}
.tree-count { margin-left: auto; font-size: 0.8em; background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 10px; border: 1px solid var(--nj-border);}

.nj-tree-link:hover { background: var(--nj-module); color: var(--nj-text-main); }
.nj-tree-link.active { background: var(--nj-module-hover); color: var(--nj-text-main); border-left: 3px solid var(--nj-gold); padding-left: 7px; font-weight: 500;}
.nj-tree-link.active .tree-icon { color: var(--nj-gold); opacity: 1;}

.nj-btn-primary { display: block; width: 100%; text-align: center; padding: 10px 0; background: var(--nj-red); color: #fff; text-decoration: none; font-weight: 600; font-size: 0.9em; border-radius: 4px; box-sizing: border-box; transition: transform 0.1s, background 0.2s; box-shadow: inset 0 0 0 1px rgba(255,255,255,0.1);}
.nj-btn-primary:hover { background: #b01c1c; }
.nj-btn-secondary { display: block; width: 100%; text-align: center; padding: 10px 0; background: var(--nj-module); border: 1px solid var(--nj-border); color: var(--nj-text-main); text-decoration: none; font-weight: 600; font-size: 0.9em; border-radius: 4px; box-sizing: border-box; transition: transform 0.1s, background 0.2s;}
.nj-btn-secondary:hover { background: var(--nj-module-hover); }

.action-bounce:active { transform: scale(0.97); }

/* ==== 主体列表 ==== */
.nj-app-main { flex: 1; min-width: 0; background: var(--nj-module); border: 1px solid var(--nj-border); border-radius: 4px; padding: 15px;}

.nj-list-header { display: flex; padding: 10px 15px; font-size: 0.75em; color: var(--nj-text-muted); border-bottom: 2px solid var(--nj-border); font-weight: bold; letter-spacing: 1px;}
.col-main { flex: 1; }
.col-stats { width: 120px; text-align: right; }
.col-last { width: 160px; text-align: right; }

.nj-post-list { display: flex; flex-direction: column; }

.nj-list-row { 
    display: flex; padding: 15px; border-bottom: 1px solid var(--nj-border);
    text-decoration: none; transition: background 0.1s, border-left 0.1s;
    border-left: 3px solid transparent;
    
    opacity: 0; transform: translateY(10px);
    animation: cascadeIn 0.4s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
    animation-delay: calc(var(--i) * 0.04s);
    will-change: transform, opacity;
}

.nj-list-row:hover { background-color: var(--nj-module-hover); border-left-color: var(--nj-gold); }

/* 置顶特化色调 */
.is-pinned { background-color: rgba(204, 166, 119, 0.05); }
.is-pinned:hover { background-color: rgba(204, 166, 119, 0.1); }
.pin-icon { color: var(--nj-gold); font-size: 1.2em;}
.default-icon { color: var(--nj-text-muted); font-size: 1.2em; opacity: 0.6;}

.row-content { display: flex; align-items: center; gap: 15px;}
.row-icon { width: 30px; text-align: center; flex-shrink: 0;}
.row-title-area { display: flex; flex-direction: column; gap: 5px; }

.row-title { font-size: 1.1em; margin: 0; color: var(--nj-text-main); font-weight: 500; transition: color 0.2s; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 500px;}
.nj-list-row:hover .row-title { color: #fff; }

.row-meta { font-size: 0.8em; color: var(--nj-text-muted); display: flex; align-items: center; gap: 10px;}
.cat-badge { background: rgba(0,0,0,0.3); border: 1px solid var(--nj-border); padding: 2px 6px; border-radius: 3px; font-size: 0.9em; color: var(--nj-gold);}
.author-name { color: #B3AEA8; }

.row-stats { display: flex; flex-direction: column; justify-content: center; gap: 3px; font-size: 0.8em; color: var(--nj-text-muted);}
.row-last { display: flex; flex-direction: column; justify-content: center; gap: 3px; font-size: 0.85em; color: var(--nj-text-muted);}
.last-time { color: var(--nj-text-main); }
.last-user { font-size: 0.9em; }

/* 分页 */
.nj-pagination-bar { display: flex; justify-content: flex-end; gap: 5px; margin-top: 20px; padding: 10px 0;}
.nj-page-link { display: inline-block; padding: 5px 12px; background: rgba(0,0,0,0.3); border: 1px solid var(--nj-border); color: var(--nj-text-main); text-decoration: none; font-size: 0.85em; border-radius: 3px; transition: 0.2s;}
.nj-page-link:hover, .nj-page-link.active { background: var(--nj-gold); color: var(--nj-bg); border-color: var(--nj-gold); font-weight: bold;}

.nj-app-footer { margin-top: 50px; padding: 30px 0; border-top: 1px solid var(--nj-border); text-align: center; color: var(--nj-text-muted); font-size: 0.8em; letter-spacing: 1px;}
.nj-alert-box { padding: 15px; background: rgba(209, 35, 35, 0.1); border: 1px solid var(--nj-red); color: var(--nj-text-main); border-radius: 4px; margin-bottom: 15px; font-size: 0.9em;}
.nj-empty-list { padding: 80px 0; text-align: center; color: var(--nj-text-muted); font-size: 1em; background: rgba(0,0,0,0.2); border: 1px dashed var(--nj-border); margin: 20px; border-radius: 4px;}

@keyframes cascadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* 响应式降级 */
@media (max-width: 900px) {
    .nj-app-body { flex-direction: column; }
    .nj-app-sidebar { width: 100%; position: static; display: flex; flex-wrap: wrap; gap: 20px; justify-content: space-between;}
    .nj-sidebar-section { width: 100%; margin-bottom: 0;}
    .nj-sidebar-section:first-child { display: flex; gap: 10px; }
    .nj-tree-nav { flex-direction: row; flex-wrap: wrap; }
    .nj-app-main { width: 100%; box-sizing: border-box;}
    
    .col-stats, .col-last { display: none; }
    .row-title { max-width: 250px; }
}
@media (max-width: 600px) {
    .nj-app-header { flex-direction: column; align-items: flex-start; gap: 20px;}
    .nj-search-box { width: 100%; }
}
</style>

<?php include 'includes/footer.php'; ?>