<?php
// wiki.php - 游戏百科页面
require_once 'config.php';

$section = sanitize($_GET['section'] ?? 'characters');
$search = sanitize($_GET['search'] ?? '');

try {
    $pdo = db_connect();
    
    // 获取百科统计数据
    $stats = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM wiki_articles WHERE status = 'published') as articles,
            (SELECT COUNT(*) FROM wiki_categories) as categories,
            (SELECT COUNT(DISTINCT author_id) FROM wiki_articles) as authors,
            (SELECT COUNT(*) FROM wiki_media) as media
    ")->fetch();
    
    // 获取热门文章
    $popular = $pdo->query("
        SELECT id, title, views, updated_at
        FROM wiki_articles 
        WHERE status = 'published'
        ORDER BY views DESC 
        LIMIT 5
    ")->fetchAll();
    
    // 获取最新更新
    $recent = $pdo->query("
        SELECT id, title, updated_at, author_id,
               (SELECT username FROM users WHERE id = wiki_articles.author_id) as author_name
        FROM wiki_articles 
        WHERE status = 'published'
        ORDER BY updated_at DESC 
        LIMIT 5
    ")->fetchAll();
    
    // 获取分类
    $categories = $pdo->query("
        SELECT c.*, 
               COUNT(a.id) as article_count
        FROM wiki_categories c
        LEFT JOIN wiki_articles a ON c.id = a.category_id AND a.status = 'published'
        GROUP BY c.id
        ORDER BY c.name
    ")->fetchAll();
    
    // 如果搜索，获取搜索结果
    $search_results = [];
    if (!empty($search)) {
        $search_stmt = $pdo->prepare("
            SELECT id, title, content, category_id,
                   (SELECT name FROM wiki_categories WHERE id = wiki_articles.category_id) as category_name,
                   ts_rank_cd(to_tsvector('spanish', title || ' ' || content), plainto_tsquery('spanish', ?)) as relevance
            FROM wiki_articles
            WHERE status = 'published'
              AND to_tsvector('spanish', title || ' ' || content) @@ plainto_tsquery('spanish', ?)
            ORDER BY relevance DESC
            LIMIT 20
        ");
        $search_stmt->execute([$search, $search]);
        $search_results = $search_stmt->fetchAll();
    }
    
    // 获取指定部分的内容
    $section_content = [];
    switch ($section) {
        case 'characters':
            $section_content = $pdo->query("
                SELECT id, name, title, role, difficulty, lore
                FROM wiki_characters 
                ORDER BY name
            ")->fetchAll();
            break;
            
        case 'weapons':
            $section_content = $pdo->query("
                SELECT id, name, type, damage, attack_speed, description
                FROM wiki_weapons 
                ORDER BY type, name
            ")->fetchAll();
            break;
            
        case 'skills':
            $section_content = $pdo->query("
                SELECT id, name, character_id, cooldown, description,
                       (SELECT name FROM wiki_characters WHERE id = wiki_skills.character_id) as character_name
                FROM wiki_skills 
                ORDER BY character_id, name
            ")->fetchAll();
            break;
            
        case 'maps':
            $section_content = $pdo->query("
                SELECT id, name, size, players, description
                FROM wiki_maps 
                ORDER BY name
            ")->fetchAll();
            break;
    }
    
} catch (Exception $e) {
    $error = "Error al cargar la wiki: " . $e->getMessage();
}
?>
<?php include 'includes/header.php'; ?>

<div class="wiki-container">
    <!-- 百科头部 -->
    <div class="wiki-header">
        <div class="wiki-hero">
            <h1><i class="fas fa-book"></i> Enciclopedia de Naraka: Bladepoint</h1>
            <p class="subtitle">La fuente definitiva de información sobre personajes, armas, habilidades y mecánicas</p>
        </div>
        
        <div class="wiki-stats">
            <div class="stat-card">
                <i class="fas fa-file-alt"></i>
                <div>
                    <h3><?php echo $stats['articles'] ?? 0; ?></h3>
                    <p>Artículos</p>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-folder"></i>
                <div>
                    <h3><?php echo $stats['categories'] ?? 0; ?></h3>
                    <p>Categorías</p>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <div>
                    <h3><?php echo $stats['authors'] ?? 0; ?></h3>
                    <p>Contribuidores</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 搜索栏 -->
    <div class="wiki-search">
        <form method="GET" class="search-form">
            <div class="search-input-group">
                <i class="fas fa-search"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Buscar en la wiki...">
                <button type="submit" class="btn-search">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </div>
            <?php if (!empty($search)): ?>
            <a href="wiki.php" class="clear-search">
                <i class="fas fa-times"></i> Limpiar búsqueda
            </a>
            <?php endif; ?>
        </form>
    </div>
    
    <?php if (!empty($search) && !empty($search_results)): ?>
    <!-- 搜索结果 -->
    <div class="search-results">
        <h2><i class="fas fa-search"></i> Resultados de búsqueda para "<?php echo htmlspecialchars($search); ?>"</h2>
        <div class="results-grid">
            <?php foreach ($search_results as $result): ?>
            <div class="result-card">
                <h3>
                    <a href="wiki-article.php?id=<?php echo $result['id']; ?>">
                        <?php echo highlight_search($result['title'], $search); ?>
                    </a>
                </h3>
                <div class="result-meta">
                    <span class="category"><?php echo htmlspecialchars($result['category_name']); ?></span>
                    <span class="relevance">Relevancia: <?php echo number_format($result['relevance'], 2); ?></span>
                </div>
                <p class="result-excerpt">
                    <?php echo highlight_search(substr(strip_tags($result['content']), 0, 200), $search); ?>...
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php elseif (!empty($search)): ?>
    <!-- 无搜索结果 -->
    <div class="no-results">
        <i class="fas fa-search"></i>
        <h3>No se encontraron resultados para "<?php echo htmlspecialchars($search); ?>"</h3>
        <p>Intenta con otras palabras clave o navega por las categorías.</p>
        <a href="wiki.php" class="btn-primary">Ver toda la wiki</a>
    </div>
    
    <?php else: ?>
    <!-- 主内容区 -->
    <div class="wiki-main">
        <!-- 侧边导航 -->
        <nav class="wiki-sidebar">
            <div class="sidebar-section">
                <h3><i class="fas fa-bars"></i> Navegación</h3>
                <ul class="nav-menu">
                    <li class="<?php echo $section == 'characters' ? 'active' : ''; ?>">
                        <a href="?section=characters">
                            <i class="fas fa-users"></i> Personajes
                        </a>
                    </li>
                    <li class="<?php echo $section == 'weapons' ? 'active' : ''; ?>">
                        <a href="?section=weapons">
                            <i class="fas fa-fist-raised"></i> Armas
                        </a>
                    </li>
                    <li class="<?php echo $section == 'skills' ? 'active' : ''; ?>">
                        <a href="?section=skills">
                            <i class="fas fa-magic"></i> Habilidades
                        </a>
                    </li>
                    <li class="<?php echo $section == 'maps' ? 'active' : ''; ?>">
                        <a href="?section=maps">
                            <i class="fas fa-map"></i> Mapas
                        </a>
                    </li>
                    <li>
                        <a href="?section=items">
                            <i class="fas fa-box-open"></i> Objetos
                        </a>
                    </li>
                    <li>
                        <a href="?section=mechanics">
                            <i class="fas fa-cogs"></i> Mecánicas
                        </a>
                    </li>
                    <li>
                        <a href="?section=lore">
                            <i class="fas fa-scroll"></i> Historia y Lore
                        </a>
                    </li>
                    <li>
                        <a href="?section=updates">
                            <i class="fas fa-sync"></i> Actualizaciones
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="sidebar-section">
                <h3><i class="fas fa-fire"></i> Artículos Populares</h3>
                <div class="popular-list">
                    <?php foreach ($popular as $article): ?>
                    <a href="wiki-article.php?id=<?php echo $article['id']; ?>" class="popular-item">
                        <span class="title"><?php echo htmlspecialchars($article['title']); ?></span>
                        <span class="views"><i class="fas fa-eye"></i> <?php echo $article['views']; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="sidebar-section">
                <h3><i class="fas fa-clock"></i> Recientes</h3>
                <div class="recent-list">
                    <?php foreach ($recent as $article): ?>
                    <div class="recent-item">
                        <a href="wiki-article.php?id=<?php echo $article['id']; ?>" class="title">
                            <?php echo htmlspecialchars($article['title']); ?>
                        </a>
                        <div class="recent-meta">
                            <span class="author"><?php echo htmlspecialchars($article['author_name']); ?></span>
                            <span class="time"><?php echo time_ago($article['updated_at']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php if (is_logged_in()): ?>
            <div class="sidebar-section contribute-section">
                <h3><i class="fas fa-edit"></i> Contribuir</h3>
                <p>Ayuda a mejorar la wiki:</p>
                <a href="wiki-edit.php" class="btn-contribute">
                    <i class="fas fa-pen"></i> Editar Artículo
                </a>
                <a href="wiki-new.php" class="btn-contribute">
                    <i class="fas fa-plus"></i> Crear Nuevo
                </a>
            </div>
            <?php endif; ?>
        </nav>
        
        <!-- 内容区 -->
        <main class="wiki-content">
            <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <div class="content-header">
                <h2>
                    <?php
                    $section_titles = [
                        'characters' => 'Personajes',
                        'weapons' => 'Armas',
                        'skills' => 'Habilidades',
                        'maps' => 'Mapas',
                        'items' => 'Objetos',
                        'mechanics' => 'Mecánicas',
                        'lore' => 'Historia y Lore',
                        'updates' => 'Actualizaciones'
                    ];
                    echo $section_titles[$section] ?? 'Enciclopedia';
                    ?>
                </h2>
                <p class="section-description">
                    <?php
                    $descriptions = [
                        'characters' => 'Información detallada sobre todos los personajes jugables, incluyendo habilidades, estadísticas y lore.',
                        'weapons' => 'Catálogo completo de armas cuerpo a cuerpo y a distancia, con estadísticas y estrategias.',
                        'skills' => 'Todas las habilidades disponibles, sus efectos, tiempos de reutilización y combinaciones.',
                        'maps' => 'Guía de todos los mapas del juego, ubicaciones importantes y estrategias por zona.'
                    ];
                    echo $descriptions[$section] ?? 'Información completa y actualizada sobre Naraka: Bladepoint.';
                    ?>
                </p>
            </div>
            
            <?php if ($section == 'characters' && !empty($section_content)): ?>
            <!-- Personajes -->
            <div class="characters-grid">
                <?php foreach ($section_content as $character): ?>
                <div class="character-card">
                    <div class="character-header">
                        <h3><?php echo htmlspecialchars($character['name']); ?></h3>
                        <span class="character-title"><?php echo htmlspecialchars($character['title']); ?></span>
                    </div>
                    
                    <div class="character-info">
                        <div class="info-row">
                            <span class="label">Rol:</span>
                            <span class="value"><?php echo htmlspecialchars($character['role']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Dificultad:</span>
                            <span class="value difficulty-<?php echo strtolower($character['difficulty']); ?>">
                                <?php echo htmlspecialchars($character['difficulty']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="character-lore">
                        <p><?php echo htmlspecialchars(substr($character['lore'], 0, 150)); ?>...</p>
                    </div>
                    
                    <div class="character-actions">
                        <a href="wiki-character.php?id=<?php echo $character['id']; ?>" class="btn-view">
                            <i class="fas fa-eye"></i> Ver Detalles
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php elseif ($section == 'weapons' && !empty($section_content)): ?>
            <!-- Armas -->
            <div class="weapons-table">
                <table>
                    <thead>
                        <tr>
                            <th>Arma</th>
                            <th>Tipo</th>
                            <th>Daño</th>
                            <th>Velocidad</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($section_content as $weapon): ?>
                        <tr>
                            <td class="weapon-name">
                                <strong><?php echo htmlspecialchars($weapon['name']); ?></strong>
                            </td>
                            <td>
                                <span class="weapon-type"><?php echo htmlspecialchars($weapon['type']); ?></span>
                            </td>
                            <td>
                                <span class="damage-value"><?php echo $weapon['damage']; ?></span>
                            </td>
                            <td>
                                <div class="speed-bar">
                                    <div class="speed-fill" style="width: <?php echo min(100, $weapon['attack_speed'] * 20); ?>%"></div>
                                </div>
                                <span class="speed-value"><?php echo $weapon['attack_speed']; ?>/5</span>
                            </td>
                            <td class="weapon-desc">
                                <?php echo htmlspecialchars(substr($weapon['description'], 0, 100)); ?>...
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php elseif ($section == 'skills' && !empty($section_content)): ?>
            <!-- Habilidades -->
            <div class="skills-grid">
                <?php foreach ($section_content as $skill): ?>
                <div class="skill-card">
                    <div class="skill-header">
                        <h3><?php echo htmlspecialchars($skill['name']); ?></h3>
                        <span class="character-badge"><?php echo htmlspecialchars($skill['character_name']); ?></span>
                    </div>
                    
                    <div class="skill-info">
                        <div class="cooldown">
                            <i class="fas fa-clock"></i>
                            <span><?php echo $skill['cooldown']; ?>s</span>
                        </div>
                        
                        <div class="skill-description">
                            <p><?php echo htmlspecialchars(substr($skill['description'], 0, 120)); ?>...</p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php else: ?>
            <!-- 默认内容 -->
            <div class="wiki-intro">
                <div class="intro-content">
                    <h3><i class="fas fa-info-circle"></i> Bienvenido a la Enciclopedia</h3>
                    <p>Esta wiki es una fuente colaborativa de información sobre Naraka: Bladepoint. Aquí encontrarás:</p>
                    
                    <div class="features-list">
                        <div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Información actualizada con cada parche</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Datos verificados por la comunidad</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Guías detalladas y estrategias</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Imágenes y videos ilustrativos</span>
                        </div>
                    </div>
                    
                    <div class="cta-section">
                        <p>La wiki se mantiene gracias a contribuidores como tú.</p>
                        <?php if (is_logged_in()): ?>
                        <a href="wiki-new.php" class="btn-primary">
                            <i class="fas fa-plus"></i> Contribuir con un artículo
                        </a>
                        <?php else: ?>
                        <p><a href="register.php">Regístrate</a> para empezar a contribuir.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <?php endif; ?>
</div>

<style>
.wiki-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.wiki-header {
    background: linear-gradient(135deg, var(--primary) 0%, #2c3e50 100%);
    color: white;
    border-radius: 15px;
    padding: 40px;
    margin-bottom: 30px;
}

.wiki-hero h1 {
    font-size: 2.8em;
    margin-bottom: 15px;
}

.subtitle {
    font-size: 1.2em;
    opacity: 0.9;
    max-width: 800px;
}

.wiki-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 40px;
}

.stat-card {
    background: rgba(255,255,255,0.1);
    padding: 20px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 20px;
    backdrop-filter: blur(10px);
}

.stat-card i {
    font-size: 2.5em;
    color: var(--accent);
}

.stat-card h3 {
    font-size: 2.2em;
    margin-bottom: 5px;
}

.stat-card p {
    opacity: 0.8;
    font-size: 0.9em;
}

.wiki-search {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.search-input-group {
    display: flex;
    align-items: center;
    gap: 15px;
    background: #f8f9fa;
    padding: 0 20px;
    border-radius: 8px;
}

.search-input-group i {
    color: #666;
    font-size: 1.2em;
}

.search-input-group input {
    flex: 1;
    padding: 18px 0;
    border: none;
    background: transparent;
    font-size: 16px;
}

.search-input-group input:focus {
    outline: none;
}

.btn-search {
    background: var(--accent);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: background 0.3s;
}

.btn-search:hover {
    background: #00959c;
}

.clear-search {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--accent);
    text-decoration: none;
    margin-top: 15px;
    font-size: 0.9em;
}

.clear-search:hover {
    text-decoration: underline;
}

/* 搜索结果 */
.search-results {
    margin-top: 30px;
}

.search-results h2 {
    color: var(--primary);
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.result-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: transform 0.3s;
}

.result-card:hover {
    transform: translateY(-5px);
}

.result-card h3 {
    margin-bottom: 10px;
}

.result-card h3 a {
    color: var(--primary);
    text-decoration: none;
}

.result-card h3 a:hover {
    color: var(--accent);
}

.result-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    font-size: 0.9em;
    color: #666;
}

.result-excerpt {
    color: #666;
    line-height: 1.6;
}

.highlight {
    background: yellow;
    font-weight: bold;
}

.no-results {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.no-results i {
    font-size: 4em;
    color: #ddd;
    margin-bottom: 20px;
}

.no-results h3 {
    margin-bottom: 15px;
    color: var(--primary);
}

/* 主内容区 */
.wiki-main {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 30px;
}

/* 侧边栏 */
.wiki-sidebar {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.sidebar-section {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.sidebar-section h3 {
    color: var(--primary);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.nav-menu {
    list-style: none;
}

.nav-menu li {
    margin: 8px 0;
}

.nav-menu a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 15px;
    border-radius: 6px;
    color: #333;
    text-decoration: none;
    transition: all 0.3s;
}

.nav-menu a:hover {
    background: #f8f9fa;
    color: var(--accent);
}

.nav-menu li.active a {
    background: var(--accent);
    color: white;
}

.popular-list, .recent-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.popular-item, .recent-item {
    display: block;
    padding: 12px;
    border: 1px solid #eee;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s;
}

.popular-item:hover, .recent-item:hover {
    border-color: var(--accent);
    background: #f8f9fa;
}

.popular-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.popular-item .title {
    flex: 1;
    margin-right: 10px;
}

.popular-item .views {
    color: #666;
    font-size: 0.9em;
}

.recent-item .title {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.recent-meta {
    display: flex;
    justify-content: space-between;
    font-size: 0.85em;
    color: #666;
}

.contribute-section {
    background: linear-gradient(135deg, var(--accent) 0%, #00959c 100%);
    color: white;
}

.contribute-section h3, .contribute-section p {
    color: white;
}

.btn-contribute {
    display: block;
    width: 100%;
    padding: 12px;
    background: white;
    color: var(--accent);
    border: none;
    border-radius: 6px;
    text-decoration: none;
    text-align: center;
    margin-top: 10px;
    font-weight: bold;
    transition: all 0.3s;
}

.btn-contribute:hover {
    background: #f8f9fa;
    transform: translateY(-2px);
}

/* 内容区 */
.wiki-content {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.content-header {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.content-header h2 {
    color: var(--primary);
    margin-bottom: 15px;
    font-size: 2em;
}

.section-description {
    color: #666;
    font-size: 1.1em;
    line-height: 1.6;
}

/* 人物网格 */
.characters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 25px;
}

.character-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: transform 0.3s;
}

.character-card:hover {
    transform: translateY(-5px);
}

.character-header {
    background: linear-gradient(90deg, var(--primary), var(--dark));
    color: white;
    padding: 20px;
}

.character-header h3 {
    margin-bottom: 5px;
    font-size: 1.4em;
}

.character-title {
    opacity: 0.9;
    font-size: 0.9em;
}

.character-info {
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.info-row {
    display: flex;
    justify-content: space-between;
    margin: 10px 0;
}

.label {
    color: #666;
    font-weight: 500;
}

.value {
    color: var(--primary);
    font-weight: bold;
}

.difficulty-easy {
    color: var(--success);
}

.difficulty-medium {
    color: var(--warning);
}

.difficulty-hard {
    color: var(--danger);
}

.character-lore {
    padding: 20px;
    color: #666;
    line-height: 1.6;
}

.character-actions {
    padding: 20px;
    text-align: center;
}

.btn-view {
    display: inline-block;
    padding: 12px 25px;
    background: var(--accent);
    color: white;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    transition: background 0.3s;
}

.btn-view:hover {
    background: #00959c;
}

/* 武器表格 */
.weapons-table {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.weapons-table table {
    width: 100%;
    border-collapse: collapse;
}

.weapons-table th {
    background: var(--primary);
    color: white;
    padding: 15px;
    text-align: left;
    font-weight: 600;
}

.weapons-table td {
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.weapon-name {
    font-size: 1.1em;
}

.weapon-type {
    background: #e9ecef;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.9em;
}

.damage-value {
    font-weight: bold;
    color: var(--danger);
    font-size: 1.2em;
}

.speed-bar {
    width: 100px;
    height: 8px;
    background: #eee;
    border-radius: 4px;
    margin: 5px 0;
    overflow: hidden;
}

.speed-fill {
    height: 100%;
    background: var(--accent);
    border-radius: 4px;
}

.speed-value {
    font-size: 0.9em;
    color: #666;
}

/* 技能网格 */
.skills-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.skill-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: transform 0.3s;
}

.skill-card:hover {
    transform: translateY(-5px);
}

.skill-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.skill-header h3 {
    margin: 0;
}

.character-badge {
    background: var(--accent);
    color: white;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 0.8em;
}

.cooldown {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    margin-bottom: 15px;
}

/* 介绍部分 */
.wiki-intro {
    background: white;
    padding: 40px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.intro-content h3 {
    color: var(--primary);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.features-list {
    margin: 30px 0;
}

.feature {
    display: flex;
    align-items: center;
    gap: 15px;
    margin: 15px 0;
}

.feature i {
    color: var(--success);
}

.cta-section {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 1px solid #eee;
    text-align: center;
}

@media (max-width: 1024px) {
    .wiki-main {
        grid-template-columns: 1fr;
    }
    
    .wiki-stats {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .wiki-hero h1 {
        font-size: 2em;
    }
    
    .wiki-stats {
        grid-template-columns: 1fr;
    }
    
    .search-input-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-input-group input {
        padding: 15px;
    }
    
    .characters-grid,
    .skills-grid,
    .results-grid {
        grid-template-columns: 1fr;
    }
    
    .weapons-table {
        overflow-x: auto;
    }
    
    .weapons-table table {
        min-width: 600px;
    }
}
</style>

<script>
// 搜索建议
document.querySelector('.wiki-search input')?.addEventListener('input', async function(e) {
    const query = this.value.trim();
    if (query.length < 2) return;
    
    try {
        const response = await fetch(`wiki-search.php?q=${encodeURIComponent(query)}`);
        const suggestions = await response.json();
        showSearchSuggestions(suggestions);
    } catch (error) {
        // 静默失败
    }
});

function showSearchSuggestions(suggestions) {
    const container = document.createElement('div');
    container.className = 'search-suggestions';
    
    suggestions.forEach(suggestion => {
        const div = document.createElement('div');
        div.className = 'suggestion-item';
        div.textContent = suggestion.title;
        div.addEventListener('click', () => {
            window.location.href = `wiki-article.php?id=${suggestion.id}`;
        });
        container.appendChild(div);
    });
    
    const existing = document.querySelector('.search-suggestions');
    if (existing) existing.remove();
    
    document.querySelector('.wiki-search').appendChild(container);
}

// 点击外部关闭建议
document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-suggestions') && !e.target.closest('.wiki-search input')) {
        const suggestions = document.querySelector('.search-suggestions');
        if (suggestions) suggestions.remove();
    }
});

// 页面滚动效果
window.addEventListener('scroll', function() {
    const header = document.querySelector('.wiki-header');
    const scrollY = window.scrollY;
    
    if (scrollY > 100) {
        header.style.transform = 'translateY(-10px)';
        header.style.boxShadow = '0 10px 30px rgba(0,0,0,0.1)';
    } else {
        header.style.transform = 'translateY(0)';
        header.style.boxShadow = 'none';
    }
});
</script>

<?php 
// 辅助函数：高亮搜索词
function highlight_search($text, $search) {
    if (empty($search)) return htmlspecialchars($text);
    
    $words = explode(' ', $search);
    foreach ($words as $word) {
        $word = trim($word);
        if (!empty($word)) {
            $text = preg_replace(
                "/\b(" . preg_quote($word, '/') . ")\b/i",
                '<span class="highlight">$1</span>',
                htmlspecialchars($text)
            );
        }
    }
    return $text;
}
?>

<?php include 'includes/footer.php'; ?>