<?php
// wiki.php - 100% 完整版 (完美套用主页的固定模糊背景与毛玻璃模块设计)
require_once 'config.php';

$stats = ['articles' => 0, 'categories' => 0, 'contributors' => 0];
$categories = [];
$popular_articles = [];
$recent_articles = [];
$search = sanitize($_GET['search'] ?? '');
$filter_category = sanitize($_GET['category'] ?? '');

try {
    $pdo = db_connect();

    // 1. 获取统计数据
    $stats['articles'] = $pdo->query("SELECT COUNT(*) FROM wiki_articles")->fetchColumn();
    $stats['categories'] = $pdo->query("SELECT COUNT(*) FROM wiki_categories")->fetchColumn();
    $stats['contributors'] = $pdo->query("SELECT COUNT(DISTINCT author_id) FROM wiki_articles")->fetchColumn();

    // 2. 获取分类列表
    $categories = $pdo->query("SELECT id, name FROM wiki_categories ORDER BY name")->fetchAll();

    // 3. 构建查询条件
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

    // 4. 获取文章列表
    if (!empty($where)) {
        // 搜索或筛选结果
        $stmt = $pdo->prepare("
            SELECT w.id, w.title, w.views, w.created_at, u.username, c.name as category_name
            FROM wiki_articles w
            LEFT JOIN users u ON w.author_id = u.id
            LEFT JOIN wiki_categories c ON w.category_id = c.id
            $where_sql
            ORDER BY w.views DESC
        ");
        $stmt->execute($params);
        $search_results = $stmt->fetchAll();
    } else {
        // 默认显示：热门与最新
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

<div class="fixed-blurred-bg"></div>

<div class="home-container">
    
    <div class="home-header" style="padding-top: 20px;">
        <h1 class="brush-font fallback-title" style="font-size: 4em; margin: 0 0 10px 0; color: #fff; text-shadow: 3px 3px 0px var(--accent);">EL GRAN ARCHIVO</h1>
        <p class="hero-subtitle" style="margin: 10px 0 35px 0;">La fuente definitiva de conocimiento: Héroes, Armas y Secretos de Morus</p>
        
        <div class="hero-buttons">
            <span class="btn-hero btn-hero-secondary" style="cursor: default;"><i class="fas fa-scroll" style="color: var(--accent);"></i> <?php echo $stats['articles']; ?> Pergaminos</span>
            <span class="btn-hero btn-hero-secondary" style="cursor: default;"><i class="fas fa-layer-group" style="color: var(--accent);"></i> <?php echo $stats['categories']; ?> Disciplinas</span>
            <span class="btn-hero btn-hero-secondary" style="cursor: default;"><i class="fas fa-user-ninja" style="color: var(--accent);"></i> <?php echo $stats['contributors']; ?> Eruditos</span>
        </div>
    </div>

    <div class="category-navbar">
        <span style="color: #888; font-family: 'Cinzel', serif; font-weight: bold; margin-right: 10px;">SECCIONES WIKI:</span>
        <a href="wiki.php" class="cat-link <?php echo empty($filter_category) ? 'active' : ''; ?>">Todo</a>
        <?php foreach($categories as $cat): ?>
            <a href="wiki.php?category=<?php echo $cat['id']; ?>" class="cat-link <?php echo $filter_category == $cat['id'] ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($cat['name']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 40px;" class="wiki-top-actions">
        
        <section class="wiki-module" style="margin-bottom: 0; padding: 20px 30px;">
            <form method="GET" action="wiki.php" style="display: flex; gap: 15px; align-items: center; height: 100%;">
                <i class="fas fa-search" style="color: var(--accent); font-size: 1.5em;"></i>
                <input type="text" name="search" placeholder="Buscar conocimiento en la enciclopedia..." value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                <button type="submit" class="btn-hero btn-hero-primary" style="padding: 10px 25px; font-size: 0.9em; cursor: pointer;">Buscar</button>
            </form>
        </section>
        
        <section class="wiki-module" style="margin-bottom: 0; padding: 20px 30px; display: flex; align-items: center; justify-content: center;">
            <?php if (is_logged_in()): ?>
                <a href="wiki-new.php" class="btn-hero btn-hero-primary" style="width: 100%; justify-content: center;"><i class="fas fa-pen-nib"></i> Redactar Pergamino</a>
            <?php else: ?>
                <a href="login.php" class="btn-hero btn-hero-secondary" style="width: 100%; justify-content: center; border-color: #555;"><i class="fas fa-sign-in-alt"></i> Entrar para Aportar</a>
            <?php endif; ?>
        </section>
    </div>

    <?php if (!empty($where)): ?>
        <section class="wiki-module">
            <div class="module-header">
                <h2 style="font-size: 1.5em;"><i class="fas fa-search"></i> Resultados de la Búsqueda</h2>
                <a href="wiki.php" class="view-all">Limpiar Filtros <i class="fas fa-times"></i></a>
            </div>
            <div class="article-grid-col">
                <?php if(empty($search_results)): ?>
                    <p style="color: #888; text-align: center; padding: 20px;">El viento se ha llevado las palabras... No hay resultados.</p>
                <?php else: ?>
                    <?php foreach($search_results as $res): ?>
                        <a href="wiki-article.php?id=<?php echo $res['id']; ?>" class="article-strip">
                            <div class="strip-info">
                                <span class="strip-cat" style="background: #333; color: #ccc;"><?php echo htmlspecialchars($res['category_name'] ?? 'General'); ?></span>
                                <span class="strip-title"><?php echo htmlspecialchars($res['title']); ?></span>
                            </div>
                            <div class="strip-meta"><i class="fas fa-eye"></i> <?php echo $res['views']; ?> | Por <?php echo htmlspecialchars($res['username']); ?></div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-top: 40px;">
            
            <section class="wiki-module" style="margin-bottom: 0;">
                <div class="module-header">
                    <h2 style="font-size: 1.3em;"><i class="fas fa-fire" style="color: var(--accent);"></i> Textos Sagrados</h2>
                </div>
                <div class="article-grid-col">
                    <?php if(!empty($popular_articles)): ?>
                        <?php foreach($popular_articles as $art): ?>
                            <a href="wiki-article.php?id=<?php echo $art['id']; ?>" class="article-strip">
                                <div class="strip-info">
                                    <span class="strip-cat diff-advanced"><?php echo htmlspecialchars($art['category_name'] ?? 'Wiki'); ?></span>
                                    <span class="strip-title"><?php echo htmlspecialchars($art['title']); ?></span>
                                </div>
                                <div class="strip-meta"><i class="fas fa-eye"></i> <?php echo $art['views']; ?> | <?php echo htmlspecialchars($art['username']); ?></div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #888; text-align: center; padding: 20px;">La biblioteca está en silencio.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="wiki-module" style="margin-bottom: 0;">
                <div class="module-header">
                    <h2 style="font-size: 1.3em;"><i class="fas fa-hourglass-half" style="color: #aaa;"></i> Recién Descubiertos</h2>
                </div>
                <div class="article-grid-col">
                    <?php if(!empty($recent_articles)): ?>
                        <?php foreach($recent_articles as $art): ?>
                            <a href="wiki-article.php?id=<?php echo $art['id']; ?>" class="article-strip">
                                <div class="strip-info">
                                    <span class="strip-cat" style="background: #333; color: #ccc;"><?php echo htmlspecialchars($art['category_name'] ?? 'Wiki'); ?></span>
                                    <span class="strip-title"><?php echo htmlspecialchars($art['title']); ?></span>
                                </div>
                                <div class="strip-meta"><i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($art['created_at'])); ?> | <?php echo htmlspecialchars($art['username']); ?></div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #888; text-align: center; padding: 20px;">La biblioteca está en silencio.</p>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    <?php endif; ?>

</div>

<style>
/* ================= 首页模块化 Wiki 大作 CSS ================= */

/* 强制恢复滚动和底色 */
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
    filter: blur(15px) brightness(0.25) contrast(1.1);
    z-index: -10; 
    pointer-events: none !important; 
}

/* 主内容容器 */
.home-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 20px 20px 80px 20px;
    position: relative;
    z-index: 10;
    pointer-events: auto !important; 
}

/* 标题区域 */
.home-header {
    text-align: center;
    padding: 30px 0 40px 0;
}
.hero-subtitle {
    font-family: 'Cinzel', serif;
    font-size: 1.1em;
    color: #ccc;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 2px;
    text-shadow: 2px 2px 5px rgba(0,0,0,0.9);
}

/* 核心大按钮 */
.hero-buttons { 
    display: flex; 
    gap: 20px; 
    justify-content: center; 
    flex-wrap: wrap; 
}
.btn-hero { 
    padding: 14px 28px; 
    font-size: 1em; 
    font-weight: 700; 
    text-decoration: none; 
    transition: all 0.3s ease; 
    display: inline-flex; 
    align-items: center; 
    gap: 10px; 
    border: none; 
    text-transform: uppercase; 
    font-family: 'Cinzel', 'Noto Serif SC', serif; 
    letter-spacing: 1px;
}
.btn-hero-primary { 
    background: rgba(10, 10, 12, 0.85); 
    color: white; 
    border: 1px solid rgba(255,255,255,0.15); 
    backdrop-filter: blur(5px);
}
.btn-hero-primary:hover { 
    background: #000; color: var(--accent); transform: translateY(-3px); border-color: var(--accent); box-shadow: 0 8px 20px rgba(0,0,0,0.5);
}
.btn-hero-secondary { 
    background: rgba(201, 20, 20, 0.8); 
    color: white; 
    border: 1px solid var(--accent); 
    backdrop-filter: blur(5px); 
}
.btn-hero-secondary:hover { 
    background: var(--accent); color: white; transform: translateY(-3px); box-shadow: 0 8px 20px rgba(204,0,0,0.4);
}

/* 横向毛玻璃分类菜单 */
.category-navbar {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    background: rgba(10, 10, 12, 0.65);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    padding: 15px 30px;
    border-radius: 4px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-bottom: 2px solid var(--accent);
    margin-bottom: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}
.cat-link {
    color: #ccc;
    text-transform: uppercase;
    font-family: 'Segoe UI', sans-serif;
    font-weight: bold;
    font-size: 0.85em;
    padding: 6px 15px;
    border-radius: 2px;
    transition: all 0.3s ease;
    text-decoration: none;
}
.cat-link:hover, .cat-link.active {
    color: var(--accent);
    background: rgba(201, 20, 20, 0.1);
}

/* 搜索输入框 */
.search-input {
    flex: 1;
    background: transparent;
    border: none;
    border-bottom: 2px solid rgba(255,255,255,0.2);
    color: #fff;
    font-size: 1.1em;
    padding: 10px;
    outline: none;
    transition: 0.3s;
}
.search-input:focus { border-bottom-color: var(--accent); }
.search-input::placeholder { color: #777; }

/* Wiki 模块通用样式 */
.wiki-module {
    background: rgba(15, 15, 18, 0.75);
    backdrop-filter: blur(10px);
    padding: 30px;
    border-radius: 4px;
    border: 1px solid rgba(255, 255, 255, 0.05);
    margin-bottom: 30px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.6);
}
.module-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 25px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.module-header h2 {
    margin: 0;
    font-family: 'Cinzel', serif;
    font-size: 1.5em;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 2px;
}
.module-header h2 i {
    color: var(--accent);
    margin-right: 10px;
}
.view-all {
    color: #888;
    font-size: 0.85em;
    font-weight: bold;
    text-transform: uppercase;
    text-decoration: none;
    transition: color 0.3s;
}
.view-all:hover { color: var(--accent); }

/* 文章条目列表 */
.article-grid-col {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.article-strip {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(255, 255, 255, 0.02);
    padding: 15px 20px;
    border-radius: 2px;
    border: 1px solid rgba(255, 255, 255, 0.05);
    text-decoration: none;
    transition: all 0.2s;
}
.article-strip:hover {
    background: rgba(255, 255, 255, 0.08);
    border-left: 3px solid var(--accent);
    transform: translateX(5px);
}
.strip-cat {
    padding: 3px 8px;
    font-size: 0.7em;
    text-transform: uppercase;
    margin-right: 10px;
    border-radius: 2px;
    font-family: 'Segoe UI', sans-serif;
    font-weight: bold;
}
.diff-beginner { background: rgba(76, 175, 80, 0.15); color: #4caf50; }
.diff-intermediate { background: rgba(255, 152, 0, 0.15); color: #ff9800; }
.diff-advanced { background: rgba(201, 20, 20, 0.15); color: var(--accent); }

.strip-title {
    color: #ddd;
    font-weight: bold;
    font-size: 1.1em;
    transition: color 0.2s;
}
.article-strip:hover .strip-title { color: var(--accent); }
.strip-meta {
    color: #666;
    font-size: 0.85em;
    font-family: 'Segoe UI', sans-serif;
}

@media (max-width: 768px) {
    .wiki-top-actions { grid-template-columns: 1fr !important; }
    .hero-buttons { flex-direction: column; width: 100%; max-width: 300px; margin: 0 auto; }
    .btn-hero { width: 100%; justify-content: center; }
}
</style>

<?php include 'includes/footer.php'; ?>