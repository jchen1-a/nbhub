<?php
// wiki.php - 100% 完整版 (精准修复 Sticky 滑移，0延迟瞬间锁定，修复重音字母大写)
require_once 'config.php';

$categories = [];
$popular_articles = [];
$recent_articles = [];
$search = sanitize($_GET['search'] ?? '');
$filter_category = sanitize($_GET['category'] ?? '');

try {
    $pdo = db_connect();

    $categories = $pdo->query("SELECT id, name FROM wiki_categories ORDER BY name")->fetchAll();

    $where = [];
    $params = [];
    
    if (!empty($search)) {
        $where[] = "(w.title LIKE ? OR w.content LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if (!empty($filter_category)) {
        $where[] = "w.category_id = ?";
        $params[] = $filter_category;
    }

    $where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";
    $is_searching = !empty($where);

    if ($is_searching) {
        $stmt = $pdo->prepare("
            SELECT w.id, w.title, w.views, w.created_at, u.username, c.name as category_name
            FROM wiki_articles w
            LEFT JOIN users u ON w.author_id = u.id
            LEFT JOIN wiki_categories c ON w.category_id = c.id
            $where_sql ORDER BY w.views DESC
        ");
        $stmt->execute($params);
        $search_results = $stmt->fetchAll();
    } else {
        $popular_articles = $pdo->query("
            SELECT w.id, w.title, w.views, u.username, c.name as category_name
            FROM wiki_articles w
            LEFT JOIN users u ON w.author_id = u.id
            LEFT JOIN wiki_categories c ON w.category_id = c.id
            ORDER BY w.views DESC LIMIT 6
        ")->fetchAll();

        $recent_articles = $pdo->query("
            SELECT w.id, w.title, w.created_at, u.username, c.name as category_name
            FROM wiki_articles w
            LEFT JOIN users u ON w.author_id = u.id
            LEFT JOIN wiki_categories c ON w.category_id = c.id
            ORDER BY w.created_at DESC LIMIT 6
        ")->fetchAll();
    }
} catch (Exception $e) {
    $error = "Error al cargar la wiki: " . $e->getMessage();
}
?>
<?php include 'includes/header.php'; ?>

<div class="ink-global-bg"></div>

<div class="ink-container">
    
    <aside class="ink-sidebar">
        <div class="ink-brand">
            <span class="ink-brand-en">ARCHIVE</span>
            <span class="ink-brand-cn">藏 经 阁</span>
        </div>
        
        <ul class="ink-menu">
            <li>
                <a href="wiki.php" class="ink-menu-item <?php echo empty($filter_category) ? 'active' : ''; ?>">
                    <span class="idx">〇</span>
                    <span class="name">TODO / 纵览</span>
                </a>
            </li>
            <?php 
            $cn_numbers = ['一', '二', '三', '四', '五', '六', '七', '八', '九', '十'];
            $i = 0; foreach($categories as $cat): ?>
                <li>
                    <a href="wiki.php?category=<?php echo $cat['id']; ?>" class="ink-menu-item <?php echo $filter_category == $cat['id'] ? 'active' : ''; ?>">
                        <span class="idx"><?php echo $cn_numbers[$i] ?? str_pad($i+1, 2, '0', STR_PAD_LEFT); ?></span>
                        <span class="name"><?php echo htmlspecialchars(mb_strtoupper($cat['name'], 'UTF-8')); ?></span>
                    </a>
                </li>
            <?php $i++; endforeach; ?>
        </ul>
        
        <div class="ink-sidebar-seal">
            <div class="seal-box">聚窟<br>洲印</div>
            <div class="seal-text">Naraka Hub<br>Vol. 2.0</div>
        </div>
    </aside>

    <main class="ink-main">
        
        <header class="ink-header">
            <div class="ink-header-sub">/ EL GRAN ARCHIVO /</div>
            <h1 class="ink-title">万 象 宗 卷</h1>
            <p class="ink-desc">La fuente definitiva de conocimiento: Héroes, Armas y Secretos de Morus.</p>
        </header>

        <?php if (isset($error)): ?>
            <div style="background: rgba(201,20,20,0.1); border-left: 4px solid var(--ink-red); color: var(--ink-red); padding: 20px; font-size: 1.1em; font-weight: bold; margin-bottom: 30px;">
                Error: <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="ink-toolbar">
            <form method="GET" action="wiki.php" class="ink-search-form">
                <div class="ink-search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" placeholder="Buscar conocimiento en la enciclopedia..." value="<?php echo htmlspecialchars($search); ?>">
                    <?php if(!empty($filter_category)): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($filter_category); ?>">
                    <?php endif; ?>
                    <button type="submit" class="ink-btn-ghost">BUSCAR</button>
                </div>
            </form>
            
            <div class="ink-actions">
                <?php if (is_logged_in()): ?>
                    <a href="wiki-new.php" class="ink-btn-solid"><i class="fas fa-pen-fancy"></i> REDACTAR</a>
                <?php else: ?>
                    <a href="login.php" class="ink-btn-ghost">INICIAR SESIÓN</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($is_searching): ?>
            <section class="ink-section">
                <div class="ink-section-title">
                    <h2><i class="fas fa-leaf" style="color:var(--ink-red); margin-right:15px; opacity:0.8;"></i> RESULTADOS</h2>
                    <span class="count"><?php echo count($search_results); ?> hallazgos</span>
                </div>
                
                <?php if(empty($search_results)): ?>
                    <div class="ink-empty">El viento se ha llevado las palabras... No hay resultados.</div>
                <?php else: ?>
                    <div class="ink-grid">
                        <?php foreach($search_results as $res): ?>
                            <a href="wiki-article.php?id=<?php echo $res['id']; ?>" class="ink-card">
                                <div class="ink-card-meta">
                                    <span class="ink-card-cat"><?php echo htmlspecialchars(mb_strtoupper($res['category_name'] ?? 'GENERAL', 'UTF-8')); ?></span>
                                    <span class="ink-date"><?php echo date('d / m / Y', strtotime($res['created_at'])); ?></span>
                                </div>
                                <h3 class="ink-card-title"><?php echo htmlspecialchars($res['title']); ?></h3>
                                <div class="ink-card-footer">
                                    <span>Escrito por <?php echo htmlspecialchars($res['username']); ?></span>
                                    <span><i class="fas fa-eye"></i> <?php echo $res['views']; ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <section class="ink-section">
                <div class="ink-section-title">
                    <h2><span class="section-num">壹</span> TEXTOS SAGRADOS (POPULARES)</h2>
                </div>
                <div class="ink-grid">
                    <?php if(!empty($popular_articles)): ?>
                        <?php foreach($popular_articles as $art): ?>
                            <a href="wiki-article.php?id=<?php echo $art['id']; ?>" class="ink-card">
                                <div class="ink-card-meta">
                                    <span class="ink-card-cat"><?php echo htmlspecialchars(mb_strtoupper($art['category_name'] ?? 'WIKI', 'UTF-8')); ?></span>
                                </div>
                                <h3 class="ink-card-title"><?php echo htmlspecialchars($art['title']); ?></h3>
                                <div class="ink-card-footer">
                                    <span>Por <?php echo htmlspecialchars($art['username']); ?></span>
                                    <span><i class="fas fa-eye"></i> <?php echo $art['views']; ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="ink-empty">La biblioteca está en silencio.</div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="ink-section delay-section" style="margin-top: 60px;">
                <div class="ink-section-title">
                    <h2><span class="section-num">贰</span> RECIÉN DESCUBIERTOS (NUEVOS)</h2>
                </div>
                <div class="ink-grid">
                    <?php if(!empty($recent_articles)): ?>
                        <?php foreach($recent_articles as $art): ?>
                            <a href="wiki-article.php?id=<?php echo $art['id']; ?>" class="ink-card">
                                <div class="ink-card-meta">
                                    <span class="ink-card-cat"><?php echo htmlspecialchars(mb_strtoupper($art['category_name'] ?? 'WIKI', 'UTF-8')); ?></span>
                                    <span class="ink-date"><?php echo date('d / m / Y', strtotime($art['created_at'])); ?></span>
                                </div>
                                <h3 class="ink-card-title"><?php echo htmlspecialchars($art['title']); ?></h3>
                                <div class="ink-card-footer">
                                    <span>Por <?php echo htmlspecialchars($art['username']); ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="ink-empty">La biblioteca está en silencio.</div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

    </main>
</div>

<style>
/* ================= 东方水墨武侠风 UI (Ink Wash / Wuxia Style) ================= */
@import url('https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;700;900&family=Cinzel:wght@400;700&display=swap');

:root {
    --ink-black: #1a1a1a;
    --ink-dark-grey: #333333;
    --ink-grey: #777777;
    --ink-light-grey: #eeeeee;
    --ink-white: #fafafa;
    --ink-red: #8b0000; 
    --ink-bg: #f5f5f7;
    --ink-border: #dcdcdc;
    --ink-ease: cubic-bezier(0.25, 0.8, 0.25, 1);
}

body {
    background-color: var(--ink-bg) !important;
    color: var(--ink-dark-grey);
    font-family: 'Noto Serif SC', 'Cinzel', serif; 
    overflow-x: hidden;
}

h1, h2, h3, h4, h5, h6 { color: var(--ink-black) !important; font-family: 'Noto Serif SC', 'Cinzel', serif; }

.ink-global-bg {
    position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
    background: 
        radial-gradient(circle at 100% 0%, rgba(139,0,0,0.03) 0%, transparent 40%),
        radial-gradient(circle at 0% 100%, rgba(0,0,0,0.04) 0%, transparent 50%),
        var(--ink-bg);
    z-index: -10; pointer-events: none; 
}

/* ================= 核心修复：精准消灭滑移 Bug ================= */
.ink-container { 
    max-width: 1400px; 
    /* 将 margin-top 改为 0，防止侧边栏初始位置错位 */
    margin: 0 auto 100px auto; 
    /* 将间距换算成 padding-top: 40px */
    padding: 40px 40px 0 40px; 
    display: flex; gap: 80px; align-items: flex-start; 
}

/* 侧边卷轴导航 */
.ink-sidebar { 
    width: 280px; flex-shrink: 0; position: sticky; 
    /* 75px(导航栏高度) + 40px(容器内边距) = 绝对精准的 115px */
    top: 115px; 
    border-right: 1px solid var(--ink-border); padding-right: 40px; 
}

.ink-brand { margin-bottom: 50px; }
.ink-brand-en { display: block; font-size: 1.6em; font-weight: 700; letter-spacing: 4px; color: var(--ink-black) !important; }
.ink-brand-cn { display: block; font-size: 2em; color: var(--ink-grey); letter-spacing: 8px; margin-top: 10px; font-weight: 900; }

.ink-menu { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 15px; }
.ink-menu-item {
    display: flex; align-items: center; padding: 10px 0;
    color: var(--ink-grey); text-decoration: none; font-size: 1.1em;
    position: relative; transition: all 0.4s ease;
}
.ink-menu-item .idx { 
    font-size: 1.2em; margin-right: 15px; color: var(--ink-border); transition: 0.4s ease; 
    font-weight: 900;
}
.ink-menu-item:hover { color: var(--ink-black); transform: translateX(10px); }
.ink-menu-item:hover .idx { color: var(--ink-red); }
.ink-menu-item.active { color: var(--ink-black); font-weight: 700; }
.ink-menu-item.active .idx { color: var(--ink-red); }
.ink-menu-item.active::after {
    content: ''; position: absolute; right: -40px; top: 50%; transform: translateY(-50%);
    width: 4px; height: 100%; background: var(--ink-red); border-radius: 2px;
}

/* 印章落款 */
.ink-sidebar-seal { margin-top: 80px; display: flex; align-items: center; gap: 15px; opacity: 0.8; }
.seal-box { 
    width: 45px; height: 45px; border: 2px solid var(--ink-red); color: var(--ink-red); 
    display: flex; justify-content: center; align-items: center; text-align: center;
    font-family: 'Noto Serif SC', serif; font-weight: 900; font-size: 0.9em; line-height: 1.1;
    border-radius: 4px;
}
.seal-text { font-size: 0.85em; color: var(--ink-grey); font-family: sans-serif; letter-spacing: 1px; }

/* ====== 右侧主内容区 ====== */
.ink-main { flex: 1; min-width: 0; }

.ink-header { margin-bottom: 60px; position: relative; padding-bottom: 30px; }
.ink-header::after { content: ''; position: absolute; bottom: 0; left: 0; width: 100px; height: 2px; background: var(--ink-black); }
.ink-header-sub { color: var(--ink-grey); font-size: 1.1em; margin-bottom: 20px; letter-spacing: 4px; text-transform: uppercase; }
.ink-title { font-size: 4.5em; font-weight: 900; margin: 0 0 20px 0; letter-spacing: 10px; color: var(--ink-black) !important; }
.ink-desc { color: var(--ink-grey); font-size: 1.1em; letter-spacing: 1px; max-width: 600px; line-height: 1.6; }

/* 工具栏 (搜索+按钮) */
.ink-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 60px; gap: 30px; }
.ink-search-form { flex: 1; max-width: 500px; }
.ink-search-box {
    display: flex; align-items: center; border-bottom: 1px solid var(--ink-black); padding: 10px 0; transition: border-color 0.3s;
}
.search-icon { color: var(--ink-grey); font-size: 1.2em; margin-right: 15px; }
.ink-search-box input {
    flex: 1; background: transparent; border: none; color: var(--ink-black) !important; 
    font-size: 1.1em; outline: none; font-family: 'Noto Serif SC', serif;
}
.ink-search-box input::placeholder { color: #aaa; font-style: italic; }
.ink-search-box:focus-within { border-bottom-color: var(--ink-red); }
.ink-search-box:focus-within .search-icon { color: var(--ink-red); }

.ink-btn-ghost {
    background: transparent; color: var(--ink-black); border: 1px solid var(--ink-black); 
    padding: 10px 25px; font-size: 0.9em; font-weight: 700; letter-spacing: 2px; cursor: pointer; transition: all 0.3s;
    text-transform: uppercase; text-decoration: none; display: inline-block;
}
.ink-btn-ghost:hover { background: var(--ink-black); color: var(--ink-white); }

.ink-btn-solid {
    background: var(--ink-red); color: var(--ink-white); border: 1px solid var(--ink-red); 
    padding: 12px 35px; font-size: 1em; font-weight: 700; letter-spacing: 2px; cursor: pointer; transition: all 0.3s;
    text-transform: uppercase; text-decoration: none; display: inline-flex; align-items: center; gap: 10px;
}
.ink-btn-solid:hover { background: #600000; border-color: #600000; box-shadow: 0 8px 20px rgba(139,0,0,0.2); transform: translateY(-2px); }

/* 数据块区域 */
.ink-section { margin-bottom: 80px; }
.ink-section-title { margin-bottom: 40px; display: flex; justify-content: space-between; align-items: flex-end; }
.ink-section-title h2 { margin: 0; font-size: 1.6em; font-weight: 900; letter-spacing: 2px; color: var(--ink-dark-grey) !important; display: flex; align-items: center; }
.section-num { 
    display: inline-flex; justify-content: center; align-items: center; width: 35px; height: 35px;
    background: var(--ink-red); color: white; border-radius: 50%; font-size: 0.7em; margin-right: 15px; font-family: 'Noto Serif SC', serif;
}
.ink-section-title .count { color: var(--ink-grey); font-style: italic; font-size: 1.1em; }
.ink-empty { font-size: 1.2em; color: var(--ink-grey); font-style: italic; padding: 40px 0; border-top: 1px dashed var(--ink-border); }

/* 水墨档案卡片 */
.ink-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 40px; }
.ink-card {
    background: var(--ink-white); border: 1px solid var(--ink-border); padding: 40px 30px; 
    text-decoration: none; display: flex; flex-direction: column; position: relative; 
    transition: all 0.5s var(--ink-ease); min-height: 180px; box-shadow: 0 5px 15px rgba(0,0,0,0.03);
}
.ink-card:hover { 
    border-color: var(--ink-dark-grey); transform: translateY(-10px); 
    box-shadow: 0 20px 40px rgba(0,0,0,0.08); 
}
.ink-card::before {
    content: ''; position: absolute; top: 0; left: 0; width: 0; height: 3px;
    background: var(--ink-red); transition: width 0.5s var(--ink-ease);
}
.ink-card:hover::before { width: 100%; }

.ink-card-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; font-size: 0.9em; color: var(--ink-grey); font-family: sans-serif; letter-spacing: 1px; }
.ink-card-cat { color: var(--ink-red); font-weight: bold; letter-spacing: 2px; }
.ink-date { font-style: italic; }

.ink-card-title { font-size: 1.6em; color: var(--ink-black) !important; margin: 0 0 25px 0; font-weight: 700; line-height: 1.5; transition: 0.3s; }
.ink-card:hover .ink-card-title { color: var(--ink-red) !important; }

.ink-card-footer { margin-top: auto; display: flex; justify-content: space-between; font-size: 0.9em; color: var(--ink-grey); border-top: 1px solid var(--ink-border); padding-top: 15px; font-family: sans-serif;}
.ink-card-footer i { margin-right: 5px; }

/* 响应式调整 */
@media (max-width: 1000px) {
    .ink-container { flex-direction: column; gap: 50px; padding: 40px 20px 0 20px; }
    .ink-sidebar { width: 100%; position: static; border-right: none; border-bottom: 1px solid var(--ink-border); padding-right: 0; padding-bottom: 30px; }
    .ink-menu { flex-direction: row; flex-wrap: wrap; gap: 20px; }
    .ink-menu-item.active::after { display: none; }
    .ink-menu-item.active { border-bottom: 2px solid var(--ink-red); }
    .ink-toolbar { flex-direction: column; align-items: stretch; }
    .ink-title { font-size: 3em; letter-spacing: 5px; }
}

/* 入场淡入动画 */
@keyframes inkFadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.ink-sidebar { animation: inkFadeIn 1s var(--ink-ease) forwards; }
.ink-header { opacity: 0; animation: inkFadeIn 1s var(--ink-ease) 0.2s forwards; }
.ink-toolbar { opacity: 0; animation: inkFadeIn 1s var(--ink-ease) 0.4s forwards; }
.ink-section { opacity: 0; animation: inkFadeIn 1s var(--ink-ease) 0.6s forwards; }
.delay-section { animation-delay: 0.8s; }
</style>

<?php include 'includes/footer.php'; ?>