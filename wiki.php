<?php
// wiki.php - Página principal de la Wiki (Versión Completa y Estilizada)
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
            SELECT w.id, w.title, w.views, u.username
            FROM wiki_articles w
            LEFT JOIN users u ON w.author_id = u.id
            ORDER BY w.views DESC LIMIT 5
        ")->fetchAll();

        $recent_articles = $pdo->query("
            SELECT w.id, w.title, w.created_at, u.username
            FROM wiki_articles w
            LEFT JOIN users u ON w.author_id = u.id
            ORDER BY w.created_at DESC LIMIT 5
        ")->fetchAll();
    }
} catch (Exception $e) {
    $error = "Error al cargar la wiki: " . $e->getMessage();
}
?>
<?php include 'includes/header.php'; ?>

<div class="wiki-container">
    <div class="wiki-header">
        <h1><i class="fas fa-book"></i> Enciclopedia de Naraka: Bladepoint</h1>
        <p>La fuente definitiva de información sobre personajes, armas, habilidades y mecánicas</p>

        <div class="wiki-stats">
            <div class="stat-box">
                <i class="fas fa-file-alt"></i>
                <div class="stat-info">
                    <span class="stat-num"><?php echo $stats['articles']; ?></span>
                    <span class="stat-text">Artículos</span>
                </div>
            </div>
            <div class="stat-box">
                <i class="fas fa-folder"></i>
                <div class="stat-info">
                    <span class="stat-num"><?php echo $stats['categories']; ?></span>
                    <span class="stat-text">Categorías</span>
                </div>
            </div>
            <div class="stat-box">
                <i class="fas fa-users"></i>
                <div class="stat-info">
                    <span class="stat-num"><?php echo $stats['contributors']; ?></span>
                    <span class="stat-text">Contribuidores</span>
                </div>
            </div>
        </div>
    </div>

    <div class="wiki-layout">
        <aside class="wiki-sidebar">
            <div class="wiki-card search-card">
                <form method="GET" action="wiki.php" class="search-form">
                    <input type="text" name="search" placeholder="Buscar en la wiki..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i> Buscar</button>
                </form>
            </div>

            <div class="wiki-card contribute-card">
                <h3><i class="fas fa-edit"></i> Contribuir</h3>
                <p>Ayuda a mejorar la wiki:</p>
                <?php if (is_logged_in()): ?>
                    <a href="wiki-new.php" class="btn-block btn-outline"><i class="fas fa-plus"></i> Crear Nuevo</a>
                <?php else: ?>
                    <a href="login.php" class="btn-block btn-outline"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</a>
                <?php endif; ?>
            </div>

            <div class="wiki-card nav-card">
                <h3><i class="fas fa-bars"></i> Navegación</h3>
                <ul class="wiki-nav-list">
                    <li><a href="wiki.php" class="<?php echo empty($filter_category) ? 'active' : ''; ?>"><i class="fas fa-home"></i> Inicio Wiki</a></li>
                    <?php foreach($categories as $cat): ?>
                        <li>
                            <a href="wiki.php?category=<?php echo $cat['id']; ?>" class="<?php echo $filter_category == $cat['id'] ? 'active' : ''; ?>">
                                <i class="fas fa-chevron-right"></i> <?php echo htmlspecialchars($cat['name']); ?>
                            </a>
                        </li>
                    <?php endendforeach; ?>
                </ul>
            </div>
        </aside>

        <main class="wiki-content">
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($where)): ?>
                <div class="wiki-card">
                    <h2>Resultados de la búsqueda</h2>
                    <?php if (empty($search_results)): ?>
                        <p class="empty-text">No se encontraron artículos que coincidan con tu búsqueda.</p>
                        <a href="wiki.php" class="btn-primary" style="display:inline-block; margin-top:15px;">Volver al inicio</a>
                    <?php else: ?>
                        <ul class="article-list">
                            <?php foreach($search_results as $res): ?>
                                <li>
                                    <a href="wiki-article.php?id=<?php echo $res['id']; ?>" class="article-title"><?php echo htmlspecialchars($res['title']); ?></a>
                                    <div class="article-meta">
                                        <span class="badge category-badge"><?php echo htmlspecialchars($res['category_name'] ?? 'General'); ?></span>
                                        <i class="fas fa-eye" style="margin-left:10px;"></i> <?php echo $res['views']; ?> vistas
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="wiki-grid">
                    <div class="wiki-card">
                        <h2><i class="fas fa-fire"></i> Artículos Populares</h2>
                        <?php if(empty($popular_articles)): ?>
                            <p class="empty-text">Aún no hay artículos publicados.</p>
                        <?php else: ?>
                            <ul class="article-list">
                                <?php foreach($popular_articles as $art): ?>
                                    <li>
                                        <a href="wiki-article.php?id=<?php echo $art['id']; ?>" class="article-title"><?php echo htmlspecialchars($art['title']); ?></a>
                                        <div class="article-meta">
                                            <i class="fas fa-eye"></i> <?php echo $art['views']; ?> vistas | Por <span style="color:var(--accent);"><?php echo htmlspecialchars($art['username']); ?></span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <div class="wiki-card">
                        <h2><i class="fas fa-clock"></i> Añadidos Recientemente</h2>
                        <?php if(empty($recent_articles)): ?>
                            <p class="empty-text">Aún no hay artículos publicados.</p>
                        <?php else: ?>
                            <ul class="article-list">
                                <?php foreach($recent_articles as $art): ?>
                                    <li>
                                        <a href="wiki-article.php?id=<?php echo $art['id']; ?>" class="article-title"><?php echo htmlspecialchars($art['title']); ?></a>
                                        <div class="article-meta">
                                            <i class="far fa-calendar"></i> <?php echo date('d/m/Y', strtotime($art['created_at'])); ?> | Por <span style="color:var(--accent);"><?php echo htmlspecialchars($art['username']); ?></span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<style>
/* Wiki 专属美化 CSS */
.wiki-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
.wiki-header { background: linear-gradient(135deg, var(--primary) 0%, #1a1a2e 100%); color: white; padding: 40px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
.wiki-header h1 { margin-bottom: 10px; color: #00adb5; font-size: 2.5em; }
.wiki-header p { font-size: 1.1em; opacity: 0.9; }

.wiki-stats { display: flex; gap: 20px; margin-top: 30px; flex-wrap: wrap; }
.stat-box { display: flex; align-items: center; gap: 15px; background: rgba(255,255,255,0.1); padding: 15px 25px; border-radius: 8px; flex: 1; min-width: 200px; border: 1px solid rgba(255,255,255,0.05); }
.stat-box i { font-size: 2.2em; color: #00adb5; }
.stat-info { display: flex; flex-direction: column; }
.stat-num { font-size: 1.8em; font-weight: bold; }
.stat-text { font-size: 0.9em; opacity: 0.8; text-transform: uppercase; letter-spacing: 1px; }

.wiki-layout { display: grid; grid-template-columns: 300px 1fr; gap: 30px; }

.wiki-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 25px; }
.wiki-card h2, .wiki-card h3 { color: var(--primary); margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; display: flex; align-items: center; gap: 10px; }

.search-form { display: flex; gap: 10px; }
.search-form input { flex: 1; padding: 12px; border: 2px solid #ddd; border-radius: 6px; font-size: 15px; }
.search-form button { padding: 12px 20px; background: #00adb5; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: background 0.3s; }
.search-form button:hover { background: #008f96; }

.contribute-card { background: #00adb5; color: white; }
.contribute-card h3 { color: white; border-bottom-color: rgba(255,255,255,0.3); }
.btn-block { display: block; text-align: center; margin-top: 15px; padding: 12px; border-radius: 6px; text-decoration: none; font-weight: bold; transition: all 0.3s; }
.btn-outline { background: white; color: #00adb5; border: 2px solid white; }
.btn-outline:hover { background: transparent; color: white; }

.wiki-nav-list { list-style: none; padding: 0; margin: 0; }
.wiki-nav-list li { margin-bottom: 5px; }
.wiki-nav-list a { display: block; padding: 12px 15px; color: #555; text-decoration: none; border-radius: 6px; transition: all 0.2s; font-weight: 500; }
.wiki-nav-list a:hover, .wiki-nav-list a.active { background: #f8f9fa; color: #00adb5; font-weight: bold; padding-left: 20px; }
.wiki-nav-list i { margin-right: 10px; color: #aaa; width: 20px; text-align: center; }
.wiki-nav-list a.active i { color: #00adb5; }

.wiki-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }

.article-list { list-style: none; padding: 0; margin: 0; }
    .article-list li { padding: 15px 0; border-bottom: 1px solid #eee; transition: transform 0.2s; }
.article-list li:hover { transform: translateX(5px); }
.article-list li:last-child { border-bottom: none; padding-bottom: 0; }
.article-title { font-size: 1.15em; font-weight: bold; color: var(--primary); text-decoration: none; display: block; margin-bottom: 8px; }
.article-title:hover { color: #00adb5; }
.article-meta { font-size: 0.85em; color: #888; display: flex; align-items: center; gap: 5px; }
.category-badge { background: #e9ecef; color: #555; padding: 3px 8px; border-radius: 4px; }

.empty-text { color: #999; font-style: italic; text-align: center; padding: 20px 0; }

@media (max-width: 900px) {
    .wiki-layout { grid-template-columns: 1fr; }
    .wiki-grid { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
    .wiki-stats { flex-direction: column; }
}
</style>

<?php include 'includes/footer.php'; ?>