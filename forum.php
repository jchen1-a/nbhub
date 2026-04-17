<?php
// forum.php - Splash Ink Wuxia (水墨白灰红·垂直对齐版 100% 完整版)
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
    
    // 计算总帖子数
    $count_sql = "SELECT COUNT(*) as total FROM forum_posts $where_sql";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_posts = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_posts / $per_page);
    
    $offset = ($current_page - 1) * $per_page;
    
    // 动态计算 SQL 排序规则 (包含热度公式)
    $order_by = "p.is_pinned DESC, p.last_reply_at DESC"; // 默认排序
    if ($sort === 'popular') {
        // 热度分数 = 浏览量 + (点赞 * 10) + (收藏 * 15) + (回复数 * 20)
        $order_by = "p.is_pinned DESC, heat_score DESC, p.last_reply_at DESC";
    }

    $posts_sql = "
        SELECT p.*, u.username as author_name, u.country as author_country,
               (SELECT COUNT(*) FROM forum_replies WHERE post_id = p.id) as reply_count,
               (SELECT username FROM users WHERE id = p.last_reply_by) as last_replier,
               p.last_reply_at,
               (p.views + (p.likes_count * 10) + (p.bookmarks_count * 15) + ((SELECT COUNT(*) FROM forum_replies WHERE post_id = p.id) * 20)) as heat_score
        FROM forum_posts p LEFT JOIN users u ON p.user_id = u.id
        $where_sql 
        ORDER BY $order_by
        LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;
        
    $posts_stmt = $pdo->prepare($posts_sql);
    $posts_stmt->execute($params);
    $posts = $posts_stmt->fetchAll();
    
    // 获取分类统计
    $categories = $pdo->query("SELECT category, COUNT(*) as count FROM forum_posts GROUP BY category ORDER BY count DESC")->fetchAll();
    
} catch (Exception $e) { $error = "Error de base de datos: " . $e->getMessage(); }
?>
<?php include 'includes/header.php'; ?>

<div class="splash-ink-bg">
    <div class="ink-drop ink-drop-1"></div>
    <div class="ink-drop ink-drop-2"></div>
</div>

<div class="splash-container">
    
    <aside class="splash-sidebar">
        
        <div class="splash-brand-v2">
            <div class="brand-en-top">FORO</div>
            <div class="brand-divider"></div>
            <h1 class="brand-cn-bottom">客栈</h1>
        </div>

        <div class="splash-actions">
            <a href="new-post.php" class="btn-brush">
                <span class="btn-text">提笔发帖 / REDACTAR</span>
                <span class="btn-ink-hover"></span>
            </a>
            <?php if (!is_logged_in()): ?>
                <a href="login.php" class="btn-outline">
                    客官留步 / ENTRAR
                </a>
            <?php endif; ?>
        </div>

        <nav class="splash-nav">
            <h3 class="nav-title">江湖卷宗 / SECCIONES</h3>
            <a href="forum.php" class="nav-link <?php echo empty($category) ? 'active' : ''; ?>">
                <span class="nav-idx">〇</span> 纵览天下 (Todo)
            </a>
            <?php 
            $cn_numbers = ['一', '二', '三', '四', '五', '六', '七', '八', '九', '十'];
            $i = 0; foreach($categories as $cat): ?>
                <a href="forum.php?category=<?php echo urlencode($cat['category']); ?>" class="nav-link <?php echo $category == $cat['category'] ? 'active' : ''; ?>">
                    <span class="nav-idx"><?php echo $cn_numbers[$i] ?? str_pad($i+1, 2, '0', STR_PAD_LEFT); ?></span> 
                    <?php echo htmlspecialchars(mb_strtoupper($cat['category'], 'UTF-8')); ?>
                    <span class="nav-count"><?php echo $cat['count']; ?></span>
                </a>
            <?php $i++; endforeach; ?>
        </nav>

        <nav class="splash-nav" style="margin-top: 40px;">
            <h3 class="nav-title">查阅方式 / FILTROS</h3>
            <a href="?category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>&sort=newest" class="nav-link <?php echo $sort == 'newest' ? 'active' : ''; ?>">
                <span class="nav-idx">新</span> 最新回信 (Recientes)
            </a>
            <a href="?category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>&sort=popular" class="nav-link <?php echo $sort == 'popular' ? 'active' : ''; ?>">
                <span class="nav-idx">热</span> 名震八方 (Populares)
            </a>
        </nav>
        
        <div class="splash-seal">
            <div class="seal-inner">聚贤<br>客栈</div>
        </div>

    </aside>

    <main class="splash-main">
        
        <header class="splash-header">
            <div class="header-text">
                <h2 class="main-title">风 云 榜</h2>
                <p class="main-desc">Aquí se cruzan las espadas y las palabras. Discute, busca aliados y deja tu marca en Morus.</p>
            </div>
            
            <form method="GET" action="forum.php" class="splash-search">
                <input type="text" name="search" placeholder="寻人或寻事..." value="<?php echo htmlspecialchars($search); ?>">
                <?php if(!empty($category) && $category !== 'all'): ?>
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                <?php endif; ?>
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </header>

        <?php if (isset($error)): ?>
            <div class="splash-alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="splash-board">
            <?php if (empty($posts)): ?>
                <div class="splash-empty">
                    风过无痕，笔落无声。<br>
                    <span>No se encontraron discusiones en esta sección.</span>
                </div>
            <?php else: ?>
                <div class="board-labels">
                    <div class="l-topic">传闻 / TEMA</div>
                    <div class="l-stats">阅览 / STATS</div>
                    <div class="l-last">绝笔 / ÚLTIMO</div>
                </div>

                <?php 
                $delay_index = 0;
                foreach ($posts as $post): 
                ?>
                    <a href="view-post.php?id=<?php echo $post['id']; ?>" class="ink-row <?php echo $post['is_pinned'] ? 'is-pinned' : ''; ?>" style="--i: <?php echo $delay_index++; ?>;">
                        
                        <div class="ink-sweep"></div>
                        
                        <div class="row-inner">
                            <div class="col-topic">
                                <?php if($post['is_pinned']): ?>
                                    <div class="pin-mark">顶</div>
                                <?php endif; ?>
                                <div class="topic-info">
                                    <h3 class="topic-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                    <div class="topic-meta">
                                        <span class="meta-cat">【<?php echo htmlspecialchars(mb_strtoupper($post['category'] ?? 'GENERAL', 'UTF-8')); ?>】</span>
                                        <span class="meta-author">执笔: <?php echo htmlspecialchars($post['author_name']); ?> <?php echo get_flag($post['author_country']); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-stats">
                                <span><i class="fas fa-comment-dots"></i> <?php echo $post['reply_count']; ?></span>
                                <span><i class="fas fa-eye"></i> <?php echo $post['views'] ?? 0; ?></span>
                            </div>
                            
                            <div class="col-last">
                                <?php if ($post['last_reply_at']): ?>
                                    <div class="last-time"><?php echo date('d M, H:i', strtotime($post['last_reply_at'])); ?></div>
                                    <div class="last-user">由 <?php echo htmlspecialchars($post['last_replier']); ?></div>
                                <?php else: ?>
                                    <div class="last-time"><?php echo date('d M, H:i', strtotime($post['created_at'])); ?></div>
                                    <div class="last-user">由 <?php echo htmlspecialchars($post['author_name']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="splash-pagination">
            <?php
            $start = max(1, $current_page - 2);
            $end = min($total_pages, $current_page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>" 
                   class="page-brush <?php echo $i == $current_page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        
    </main>
</div>

<style>
/* ================= Splash Ink Wuxia (极致水墨·垂直居中版) ================= */
@import url('https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@300;400;700;900&family=Cinzel:wght@400;700&display=swap');

:root {
    --ink-black: #0a0a0a;
    --ink-charcoal: #2a2a2a;
    --ink-gray: #7a7a7a;
    --ink-wash: rgba(0, 0, 0, 0.04); 
    --ink-paper: #F7F6F2; 
    --ink-white: #ffffff;
    --ink-red: #9e1b1b; 
    --ease-ink: cubic-bezier(0.2, 0.8, 0.2, 1);
}

body {
    background-color: var(--ink-paper) !important;
    color: var(--ink-black);
    font-family: 'Noto Serif SC', serif;
    margin: 0; padding: 0;
    overflow-x: hidden;
}

h1, h2, h3, h4, h5, h6 { font-family: 'Noto Serif SC', serif; margin: 0; font-weight: 900; }

.splash-ink-bg {
    position: fixed; inset: 0; z-index: -10;
    background: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.03'/%3E%3C/svg%3E");
    pointer-events: none;
}
.ink-drop { position: absolute; border-radius: 50%; filter: blur(80px); background: #000; opacity: 0.05; }
.ink-drop-1 { width: 500px; height: 500px; top: -100px; right: -100px; }
.ink-drop-2 { width: 700px; height: 700px; bottom: -200px; left: -200px; }

.splash-container {
    max-width: 1350px; margin: 50px auto 100px; padding: 0 40px;
    display: flex; gap: 80px; align-items: flex-start;
}

/* ====== 左侧导航 ====== */
.splash-sidebar { width: 280px; flex-shrink: 0; position: sticky; top: 120px; }

/* === 核心重构：垂直居中品牌排版 (FORO + 线 + 客栈) === */
.splash-brand-v2 { 
    display: flex; 
    flex-direction: column; 
    align-items: center; /* 水平居中 */
    margin-bottom: 60px;
    padding: 0 20px;
}
.brand-en-top { 
    font-family: 'Cinzel', serif; 
    font-size: 1.3em; 
    color: var(--ink-red); 
    letter-spacing: 12px; 
    font-weight: 700;
    margin-bottom: 12px;
    text-indent: 12px; /* 抵消字母间距带来的偏移，确保视觉绝对居中 */
}
.brand-divider { 
    width: 60px; 
    height: 1px; 
    background: #cccccc; /* 灰色横线 */
    margin-bottom: 18px;
}
.brand-cn-bottom { 
    font-size: 3.5em; 
    line-height: 1; 
    letter-spacing: 20px; 
    color: var(--ink-black); 
    margin: 0;
    text-indent: 20px; /* 抵消字间距偏移 */
    text-shadow: 2px 2px 8px rgba(0,0,0,0.05);
}

.splash-actions { display: flex; flex-direction: column; gap: 15px; margin-bottom: 50px; }
.btn-brush {
    position: relative; display: block; text-align: center; padding: 15px 0;
    color: var(--ink-white); text-decoration: none; font-weight: 700;
    letter-spacing: 2px; overflow: hidden; border: 1px solid var(--ink-black);
}
.btn-ink-hover {
    position: absolute; inset: 0; background: var(--ink-black); z-index: 1;
    transition: transform 0.5s var(--ease-ink); transform-origin: left;
}
.btn-text { position: relative; z-index: 2; transition: color 0.3s; }
.btn-brush:hover .btn-ink-hover { transform: scaleX(0); }
.btn-brush:hover .btn-text { color: var(--ink-black); }

.btn-outline {
    display: block; text-align: center; padding: 12px 0; color: var(--ink-black);
    text-decoration: none; font-weight: 700; border: 1px solid var(--ink-gray);
    letter-spacing: 2px; transition: 0.3s;
}
.btn-outline:hover { border-color: var(--ink-black); background: var(--ink-wash); }

.splash-nav { display: flex; flex-direction: column; gap: 8px; }
.nav-title { font-size: 0.9em; color: var(--ink-gray); margin-bottom: 15px; letter-spacing: 3px; border-bottom: 1px solid var(--ink-wash); padding-bottom: 10px; }
.nav-link {
    display: flex; align-items: center; padding: 8px 10px;
    color: var(--ink-charcoal); text-decoration: none; font-size: 1.05em;
    transition: all 0.4s var(--ease-ink); border-radius: 4px; position: relative;
}
.nav-idx { font-family: 'Cinzel', serif; color: var(--ink-gray); width: 30px; font-weight: bold; opacity: 0.6; }
.nav-count { margin-left: auto; font-size: 0.8em; color: var(--ink-gray); font-family: sans-serif; }

.nav-link::before {
    content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 0;
    background: var(--ink-wash); z-index: -1; transition: width 0.4s var(--ease-ink);
}
.nav-link:hover::before { width: 100%; }
.nav-link:hover { transform: translateX(8px); }
.nav-link.active { font-weight: 900; color: var(--ink-black); }
.nav-link.active .nav-idx { color: var(--ink-red); opacity: 1; }

.splash-seal { margin-top: 80px; display: flex; justify-content: flex-end; }
.seal-inner {
    width: 48px; height: 48px; border: 2px solid var(--ink-red); color: var(--ink-red);
    display: flex; justify-content: center; align-items: center; text-align: center;
    font-weight: 900; font-size: 0.95em; line-height: 1.1; border-radius: 6px;
    transform: rotate(-3deg); opacity: 0.85; mix-blend-mode: multiply;
}

/* ====== 右侧布告区 ====== */
.splash-main { flex: 1; min-width: 0; }

.splash-header { margin-bottom: 50px; display: flex; justify-content: space-between; align-items: flex-end; }
.main-title { font-size: 3.5em; letter-spacing: 12px; color: var(--ink-black); margin-bottom: 15px; }
.main-desc { color: var(--ink-gray); font-size: 1.1em; letter-spacing: 1px; max-width: 500px; }

.splash-search { display: flex; align-items: center; border-bottom: 2px solid var(--ink-black); padding-bottom: 5px; width: 280px; }
.splash-search input { flex: 1; border: none; outline: none; background: transparent; font-family: inherit; font-size: 1.1em; color: var(--ink-black); }
.splash-search input::placeholder { color: var(--ink-gray); font-style: italic; }
.splash-search button { background: transparent; border: none; color: var(--ink-black); font-size: 1.2em; cursor: pointer; transition: transform 0.3s; }
.splash-search button:hover { transform: scale(1.1); color: var(--ink-red); }

.splash-alert { border: 1px solid var(--ink-black); padding: 15px; margin-bottom: 30px; font-weight: bold; text-align: center; background: var(--ink-wash); }

/* 布告列表 */
.splash-board { display: flex; flex-direction: column; }
.board-labels { display: flex; padding: 0 20px 15px; border-bottom: 2px solid var(--ink-black); font-weight: 900; color: var(--ink-gray); letter-spacing: 2px; font-size: 0.85em; }

.l-topic { flex: 1; }
.l-stats { width: 140px; text-align: center; }
.l-last { width: 160px; text-align: right; }

.ink-row {
    position: relative; display: block; text-decoration: none; color: var(--ink-black);
    border-bottom: 1px solid rgba(0,0,0,0.08); overflow: hidden;
    opacity: 0; transform: translateY(15px);
    animation: inkFadeUp 0.6s var(--ease-ink) forwards;
    animation-delay: calc(var(--i) * 0.06s);
}

.ink-sweep {
    position: absolute; inset: 0; z-index: 0;
    background: linear-gradient(90deg, var(--ink-black) 0%, #1a1a1a 80%, transparent 100%);
    transform: translateX(-100%); transition: transform 0.5s var(--ease-ink);
}
.ink-row:hover .ink-sweep { transform: translateX(0); }

.row-inner { position: relative; z-index: 1; display: flex; padding: 25px 20px; align-items: center; transition: color 0.3s; }
.ink-row:hover .row-inner { color: var(--ink-white); }

.col-topic { flex: 1; display: flex; align-items: center; gap: 20px; min-width: 0; }
.pin-mark { 
    width: 35px; height: 35px; border: 1px solid var(--ink-red); color: var(--ink-red);
    display: flex; justify-content: center; align-items: center; border-radius: 50%;
    font-weight: bold; font-size: 0.9em; flex-shrink: 0;
}
.ink-row:hover .pin-mark { border-color: var(--ink-white); color: var(--ink-white); }

.topic-info { display: flex; flex-direction: column; gap: 8px; flex: 1; min-width: 0; }
.topic-title { font-size: 1.35em; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.topic-meta { font-size: 0.85em; display: flex; gap: 15px; color: var(--ink-gray); font-family: sans-serif; transition: color 0.3s; }
.ink-row:hover .topic-meta { color: rgba(255,255,255,0.7); }
.meta-cat { font-family: 'Noto Serif SC', serif; font-weight: bold; color: var(--ink-charcoal); }
.ink-row:hover .meta-cat { color: var(--ink-white); }

.col-stats { width: 140px; display: flex; gap: 15px; justify-content: center; font-family: sans-serif; font-size: 0.9em; color: var(--ink-gray); transition: 0.3s; }
.ink-row:hover .col-stats { color: rgba(255,255,255,0.7); }

.col-last { width: 160px; text-align: right; display: flex; flex-direction: column; gap: 4px; }
.last-time { font-size: 0.95em; font-weight: bold; font-family: sans-serif; }
.last-user { font-size: 0.85em; color: var(--ink-gray); transition: 0.3s; }
.ink-row:hover .last-user { color: rgba(255,255,255,0.7); }

.splash-empty { padding: 100px 0; text-align: center; color: var(--ink-gray); font-size: 1.2em; line-height: 2; border-bottom: 1px solid rgba(0,0,0,0.08); }

/* 分页 */
.splash-pagination { display: flex; gap: 8px; margin-top: 50px; justify-content: flex-end; }
.page-brush { 
    width: 40px; height: 40px; display: flex; justify-content: center; align-items: center;
    color: var(--ink-black); text-decoration: none; font-family: 'Cinzel', serif; font-weight: bold;
    position: relative; z-index: 1; transition: color 0.3s;
}
.page-brush::before {
    content: ''; position: absolute; inset: 0; background: var(--ink-black); z-index: -1;
    border-radius: 50%; transform: scale(0); transition: transform 0.4s var(--ease-ink);
}
.page-brush:hover::before, .page-brush.active::before { transform: scale(1); }
.page-brush:hover, .page-brush.active { color: var(--ink-white); }

/* 响应式 */
@media (max-width: 1000px) {
    .splash-container { flex-direction: column; padding: 0 20px; gap: 30px; }
    .splash-sidebar { width: 100%; position: static; border-bottom: 1px solid var(--ink-black); padding-bottom: 30px; }
    .splash-brand-v2 { align-items: flex-start; padding: 0; }
    .brand-en-top, .brand-cn-bottom { text-indent: 0; letter-spacing: 5px; }
    .brand-divider { width: 40px; }
    .splash-header { flex-direction: column; align-items: flex-start; gap: 20px; }
    .splash-search { width: 100%; }
    .col-stats, .col-last { display: none; }
}

@keyframes inkFadeUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
</style>

<?php include 'includes/footer.php'; ?>