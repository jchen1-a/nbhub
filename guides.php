<?php
// guides.php - 攻略指南页面 (修正版)
require_once 'config.php';

$filter = sanitize($_GET['filter'] ?? 'all');
$difficulty = sanitize($_GET['difficulty'] ?? 'all');
$sort = sanitize($_GET['sort'] ?? 'newest');

try {
    $pdo = db_connect();
    
    // 构建查询
    // 注意：如果你的 articles 表里没有 is_published 字段，请删除 "is_published = TRUE"
    // 这里假设你已经按我之前的建议加了 is_published 字段
    $where = ["is_published = TRUE"]; 
    $params = [];
    
    if ($filter !== 'all') {
        $where[] = "category = ?";
        $params[] = $filter;
    }
    
    if ($difficulty !== 'all') {
        $where[] = "difficulty = ?";
        $params[] = $difficulty;
    }
    
    $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // 排序
    $order_by = match($sort) {
        'popular' => 'views DESC',
        'difficulty' => 'difficulty DESC', // 使用枚举值或 difficulty_level
        default => 'created_at DESC'
    };
    
    // 获取攻略列表 (修正表名为 articles)
    $guides_sql = "
        SELECT g.*, 
               u.username as author_name,
               u.country as author_country
               -- 暂时移除评论和评分统计，除非你有 guide_comments 表
               -- ,(SELECT COUNT(*) FROM guide_comments WHERE guide_id = g.id) as comment_count
               -- ,(SELECT AVG(rating) FROM guide_ratings WHERE guide_id = g.id) as avg_rating
        FROM articles g
        LEFT JOIN users u ON g.user_id = u.id
        $where_sql
        ORDER BY $order_by
        LIMIT 20
    ";
    
    $guides_stmt = $pdo->prepare($guides_sql);
    $guides_stmt->execute($params);
    $guides = $guides_stmt->fetchAll();
    
    // 获取分类统计
    $categories = $pdo->query("
        SELECT category, COUNT(*) as count 
        FROM articles 
        WHERE is_published = TRUE
        GROUP BY category 
        ORDER BY count DESC
    ")->fetchAll();
    
    // 获取难度统计
    $difficulties = $pdo->query("
        SELECT difficulty, COUNT(*) as count 
        FROM articles 
        WHERE is_published = TRUE
        GROUP BY difficulty 
        ORDER BY difficulty ASC
    ")->fetchAll();
    
    // 热门攻略（最近7天）
    $popular = $pdo->query("
        SELECT g.*, u.username
        FROM articles g
        LEFT JOIN users u ON g.user_id = u.id
        WHERE g.is_published = TRUE 
          AND g.created_at > NOW() - INTERVAL 7 DAY
        ORDER BY g.views DESC
        LIMIT 5
    ")->fetchAll();
    
} catch (Exception $e) {
    $error = "Error al cargar las guías: " . $e->getMessage();
}
?>
<?php include 'includes/header.php'; ?>

<div class="guides-container">
    <div class="guides-header">
        <div class="guides-hero">
            <h1><i class="fas fa-graduation-cap"></i> Guías de Naraka: Bladepoint</h1>
            <p class="subtitle">Aprende técnicas avanzadas, builds óptimos y secretos del juego</p>
            
            <div class="hero-stats">
                <div class="stat">
                    <span class="stat-number">
                        <?php
                        try {
                            $total_guides = $pdo->query("SELECT COUNT(*) as c FROM articles WHERE is_published = TRUE")->fetch()['c'];
                            echo $total_guides;
                        } catch (Exception $e) { echo '0'; }
                        ?>
                    </span>
                    <span class="stat-label">Guías publicadas</span>
                </div>
                <div class="stat">
                    <span class="stat-number">
                        <?php
                        try {
                            $total_authors = $pdo->query("SELECT COUNT(DISTINCT user_id) as c FROM articles WHERE is_published = TRUE")->fetch()['c'];
                            echo $total_authors;
                        } catch (Exception $e) { echo '0'; }
                        ?>
                    </span>
                    <span class="stat-label">Autores</span>
                </div>
                <div class="stat">
                    <span class="stat-number">
                        <?php
                        try {
                            $total_views = $pdo->query("SELECT SUM(views) as c FROM articles WHERE is_published = TRUE")->fetch()['c'];
                            echo number_format($total_views ?? 0);
                        } catch (Exception $e) { echo '0'; }
                        ?>
                    </span>
                    <span class="stat-label">Vistas totales</span>
                </div>
            </div>
        </div>
        
        <?php if (is_logged_in()): ?>
        <div class="guides-actions">
            <a href="new-guide.php" class="btn-primary btn-large">
                <i class="fas fa-plus-circle"></i> Crear Nueva Guía
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="guides-main">
        <aside class="guides-sidebar">
            <div class="filter-card">
                <h3><i class="fas fa-filter"></i> Filtrar Guías</h3>
                <form method="GET" class="filter-form">
                    <div class="filter-section">
                        <h4>Categoría</h4>
                        <div class="filter-options">
                            <label class="filter-option">
                                <input type="radio" name="filter" value="all" <?php echo $filter == 'all' ? 'checked' : ''; ?> onchange="this.form.submit()">
                                <span>Todas</span>
                            </label>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $cat): ?>
                                <label class="filter-option">
                                    <input type="radio" name="filter" value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $filter == $cat['category'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                                    <span><?php echo htmlspecialchars(ucfirst($cat['category'])); ?></span>
                                    <span class="count"><?php echo $cat['count']; ?></span>
                                </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="filter-section">
                        <h4>Dificultad</h4>
                        <div class="filter-options">
                            <label class="filter-option">
                                <input type="radio" name="difficulty" value="all" <?php echo $difficulty == 'all' ? 'checked' : ''; ?> onchange="this.form.submit()">
                                <span>Todas</span>
                            </label>
                            <?php if (!empty($difficulties)): ?>
                                <?php foreach ($difficulties as $diff): ?>
                                <label class="filter-option">
                                    <input type="radio" name="difficulty" value="<?php echo htmlspecialchars($diff['difficulty']); ?>" <?php echo $difficulty == $diff['difficulty'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                                    <span><?php echo ucfirst($diff['difficulty']); ?></span>
                                    <span class="count"><?php echo $diff['count']; ?></span>
                                </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="popular-card">
                <h3><i class="fas fa-fire"></i> Populares</h3>
                <div class="popular-list">
                    <?php if (!empty($popular)): ?>
                        <?php foreach ($popular as $guide): ?>
                        <a href="view-guide.php?id=<?php echo $guide['id']; ?>" class="popular-item">
                            <div class="popular-content">
                                <h4><?php echo htmlspecialchars($guide['title']); ?></h4>
                                <div class="popular-meta">
                                    <span class="views"><i class="fas fa-eye"></i> <?php echo $guide['views']; ?></span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:#999;font-size:0.9em;">No hay guías populares recientes.</p>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
        
        <main class="guides-list">
            <?php if (isset($error)): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (empty($guides)): ?>
            <div class="empty-guides">
                <i class="fas fa-book-open"></i>
                <h3>No hay guías disponibles</h3>
                <p>Sé el primero en publicar una guía.</p>
                <?php if (is_logged_in()): ?>
                <a href="new-guide.php" class="btn-primary">Crear Guía</a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="guides-grid">
                <?php foreach ($guides as $guide): ?>
                <div class="guide-card">
                    <div class="guide-header">
                        <?php if (!empty($guide['is_featured'])): ?>
                        <span class="featured-badge"><i class="fas fa-star"></i> Destacado</span>
                        <?php endif; ?>
                        <span class="difficulty-badge <?php echo $guide['difficulty']; ?>">
                            <?php echo ucfirst($guide['difficulty']); ?>
                        </span>
                    </div>
                    <div class="guide-content">
                        <h3 class="guide-title">
                            <a href="article.php?id=<?php echo $guide['id']; ?>">
                                <?php echo htmlspecialchars($guide['title']); ?>
                            </a>
                        </h3>
                        <div class="guide-meta">
                            <div class="meta-item">
                                <i class="fas fa-user"></i> <span><?php echo htmlspecialchars($guide['author_name']); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-eye"></i> <span><?php echo $guide['views']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<style>
/* 复用你之前的 CSS，这里只保留最核心的布局样式以节省字符 */
.guides-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
.guides-header { background: #1a1a2e; color: white; padding: 40px; border-radius: 12px; margin-bottom: 30px; }
.guides-main { display: grid; grid-template-columns: 280px 1fr; gap: 30px; }
.guides-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
.guide-card { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
.guide-header { padding: 10px 20px; background: #f8f9fa; display: flex; justify-content: space-between; }
.guide-content { padding: 20px; }
.guide-title a { color: #333; text-decoration: none; font-weight: bold; font-size: 1.2em; }
.hero-stats { display: flex; gap: 40px; margin-top: 20px; }
.stat-number { font-size: 2em; font-weight: bold; color: #00adb5; }
@media (max-width: 768px) { .guides-main { grid-template-columns: 1fr; } }
</style>

<?php include 'includes/footer.php'; ?>