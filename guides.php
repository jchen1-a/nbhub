<?php
// guides.php - 攻略指南页面
require_once 'config.php';

$filter = sanitize($_GET['filter'] ?? 'all');
$difficulty = sanitize($_GET['difficulty'] ?? 'all');
$sort = sanitize($_GET['sort'] ?? 'newest');

try {
    $pdo = db_connect();
    
    // 构建查询
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
        'rating' => 'rating DESC',
        'difficulty' => 'difficulty_level ASC',
        default => 'created_at DESC'
    };
    
    // 获取攻略列表
    $guides_sql = "
        SELECT g.*, 
               u.username as author_name,
               u.country as author_country,
               (SELECT COUNT(*) FROM guide_comments WHERE guide_id = g.id) as comment_count,
               (SELECT AVG(rating) FROM guide_ratings WHERE guide_id = g.id) as avg_rating
        FROM guides g
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
        FROM guides 
        WHERE is_published = TRUE
        GROUP BY category 
        ORDER BY count DESC
    ")->fetchAll();
    
    // 获取难度统计
    $difficulties = $pdo->query("
        SELECT difficulty, COUNT(*) as count 
        FROM guides 
        WHERE is_published = TRUE
        GROUP BY difficulty 
        ORDER BY 
            CASE difficulty
                WHEN 'beginner' THEN 1
                WHEN 'intermediate' THEN 2
                WHEN 'advanced' THEN 3
                ELSE 4
            END
    ")->fetchAll();
    
    // 热门攻略（最近7天）
    $popular = $pdo->query("
        SELECT g.*, u.username
        FROM guides g
        LEFT JOIN users u ON g.user_id = u.id
        WHERE g.is_published = TRUE 
          AND g.created_at > NOW() - INTERVAL '7 days'
        ORDER BY g.views DESC
        LIMIT 5
    ")->fetchAll();
    
} catch (Exception $e) {
    $error = "Error al cargar las guías: " . $e->getMessage();
}
?>
<?php include 'includes/header.php'; ?>

<div class="guides-container">
    <!-- 页面头部 -->
    <div class="guides-header">
        <div class="guides-hero">
            <h1><i class="fas fa-graduation-cap"></i> Guías de Naraka: Bladepoint</h1>
            <p class="subtitle">Aprende técnicas avanzadas, builds óptimos y secretos del juego</p>
            
            <div class="hero-stats">
                <div class="stat">
                    <span class="stat-number">
                        <?php
                        try {
                            $total_guides = $pdo->query("SELECT COUNT(*) as c FROM guides WHERE is_published = TRUE")->fetch()['c'];
                            echo $total_guides;
                        } catch (Exception $e) {
                            echo '0';
                        }
                        ?>
                    </span>
                    <span class="stat-label">Guías publicadas</span>
                </div>
                <div class="stat">
                    <span class="stat-number">
                        <?php
                        try {
                            $total_authors = $pdo->query("SELECT COUNT(DISTINCT user_id) as c FROM guides WHERE is_published = TRUE")->fetch()['c'];
                            echo $total_authors;
                        } catch (Exception $e) {
                            echo '0';
                        }
                        ?>
                    </span>
                    <span class="stat-label">Autores</span>
                </div>
                <div class="stat">
                    <span class="stat-number">
                        <?php
                        try {
                            $total_views = $pdo->query("SELECT SUM(views) as c FROM guides WHERE is_published = TRUE")->fetch()['c'];
                            echo number_format($total_views ?? 0);
                        } catch (Exception $e) {
                            echo '0';
                        }
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
    
    <!-- 主要内容区 -->
    <div class="guides-main">
        <!-- 侧边栏 -->
        <aside class="guides-sidebar">
            <!-- 过滤选项 -->
            <div class="filter-card">
                <h3><i class="fas fa-filter"></i> Filtrar Guías</h3>
                
                <form method="GET" class="filter-form">
                    <div class="filter-section">
                        <h4>Categoría</h4>
                        <div class="filter-options">
                            <label class="filter-option">
                                <input type="radio" name="filter" value="all" 
                                    <?php echo $filter == 'all' ? 'checked' : ''; ?> 
                                    onchange="this.form.submit()">
                                <span>Todas</span>
                                <span class="count">
                                    <?php
                                    try {
                                        $all_count = $pdo->query("SELECT COUNT(*) as c FROM guides WHERE is_published = TRUE")->fetch()['c'];
                                        echo $all_count;
                                    } catch (Exception $e) {
                                        echo '0';
                                    }
                                    ?>
                                </span>
                            </label>
                            
                            <?php foreach ($categories as $cat): ?>
                            <label class="filter-option">
                                <input type="radio" name="filter" value="<?php echo htmlspecialchars($cat['category']); ?>"
                                    <?php echo $filter == $cat['category'] ? 'checked' : ''; ?>
                                    onchange="this.form.submit()">
                                <span><?php echo htmlspecialchars(ucfirst($cat['category'])); ?></span>
                                <span class="count"><?php echo $cat['count']; ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="filter-section">
                        <h4>Dificultad</h4>
                        <div class="filter-options">
                            <label class="filter-option">
                                <input type="radio" name="difficulty" value="all"
                                    <?php echo $difficulty == 'all' ? 'checked' : ''; ?>
                                    onchange="this.form.submit()">
                                <span>Todas</span>
                            </label>
                            
                            <?php foreach ($difficulties as $diff): ?>
                            <label class="filter-option">
                                <input type="radio" name="difficulty" value="<?php echo htmlspecialchars($diff['difficulty']); ?>"
                                    <?php echo $difficulty == $diff['difficulty'] ? 'checked' : ''; ?>
                                    onchange="this.form.submit()">
                                <span>
                                    <?php 
                                    $diff_names = [
                                        'beginner' => 'Principiante',
                                        'intermediate' => 'Intermedio', 
                                        'advanced' => 'Avanzado'
                                    ];
                                    echo $diff_names[$diff['difficulty']] ?? ucfirst($diff['difficulty']);
                                    ?>
                                </span>
                                <span class="count"><?php echo $diff['count']; ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="filter-section">
                        <h4>Ordenar por</h4>
                        <select name="sort" class="sort-select" onchange="this.form.submit()">
                            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Más recientes</option>
                            <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Más populares</option>
                            <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Mejor valoradas</option>
                            <option value="difficulty" <?php echo $sort == 'difficulty' ? 'selected' : ''; ?>>Dificultad</option>
                        </select>
                    </div>
                    
                    <button type="reset" class="btn-reset" onclick="window.location='guides.php'">
                        <i class="fas fa-redo"></i> Reiniciar filtros
                    </button>
                </form>
            </div>
            
            <!-- 热门攻略 -->
            <div class="popular-card">
                <h3><i class="fas fa-fire"></i> Guías Populares</h3>
                <div class="popular-list">
                    <?php foreach ($popular as $guide): ?>
                    <a href="view-guide.php?id=<?php echo $guide['id']; ?>" class="popular-item">
                        <div class="popular-content">
                            <h4><?php echo htmlspecialchars($guide['title']); ?></h4>
                            <div class="popular-meta">
                                <span class="author">por <?php echo htmlspecialchars($guide['username']); ?></span>
                                <span class="views"><i class="fas fa-eye"></i> <?php echo $guide['views']; ?></span>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- 投稿指引 -->
            <div class="contribute-card">
                <h3><i class="fas fa-edit"></i> ¿Quieres contribuir?</h3>
                <p>Comparte tus conocimientos con la comunidad:</p>
                <ul>
                    <li>Guías deben ser originales y detalladas</li>
                    <li>Incluye imágenes o videos si es posible</li>
                    <li>Sigue las guías de estilo</li>
                    <li>Recibirás crédito como autor</li>
                </ul>
                <a href="new-guide.php" class="btn-secondary">
                    <i class="fas fa-pen"></i> Escribir una guía
                </a>
            </div>
        </aside>
        
        <!-- 攻略列表 -->
        <main class="guides-list">
            <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if (empty($guides)): ?>
            <div class="empty-guides">
                <i class="fas fa-book-open"></i>
                <h3>No hay guías disponibles</h3>
                <p>No se encontraron guías con los filtros seleccionados.</p>
                <a href="guides.php" class="btn-primary">Ver todas las guías</a>
            </div>
            <?php else: ?>
            
            <div class="guides-grid">
                <?php foreach ($guides as $guide): ?>
                <div class="guide-card">
                    <div class="guide-header">
                        <?php if ($guide['is_featured']): ?>
                        <span class="featured-badge">
                            <i class="fas fa-star"></i> Destacado
                        </span>
                        <?php endif; ?>
                        
                        <span class="difficulty-badge <?php echo $guide['difficulty']; ?>">
                            <?php 
                            $diff_text = [
                                'beginner' => 'Principiante',
                                'intermediate' => 'Intermedio',
                                'advanced' => 'Avanzado'
                            ];
                            echo $diff_text[$guide['difficulty']] ?? ucfirst($guide['difficulty']);
                            ?>
                        </span>
                    </div>
                    
                    <div class="guide-content">
                        <h3 class="guide-title">
                            <a href="view-guide.php?id=<?php echo $guide['id']; ?>">
                                <?php echo htmlspecialchars($guide['title']); ?>
                            </a>
                        </h3>
                        
                        <p class="guide-excerpt">
                            <?php 
                            $excerpt = strip_tags($guide['content']);
                            echo mb_substr($excerpt, 0, 150) . (mb_strlen($excerpt) > 150 ? '...' : '');
                            ?>
                        </p>
                        
                        <div class="guide-meta">
                            <div class="meta-item">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($guide['author_name']); ?></span>
                                <?php if ($guide['author_country']): ?>
                                <span class="flag"><?php echo get_flag($guide['author_country']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo date('d/m/Y', strtotime($guide['created_at'])); ?></span>
                            </div>
                            
                            <div class="meta-item">
                                <i class="fas fa-eye"></i>
                                <span><?php echo number_format($guide['views']); ?></span>
                            </div>
                        </div>
                        
                        <div class="guide-stats">
                            <div class="stat">
                                <i class="fas fa-comment"></i>
                                <span><?php echo $guide['comment_count']; ?> comentarios</span>
                            </div>
                            
                            <?php if ($guide['avg_rating']): ?>
                            <div class="stat">
                                <i class="fas fa-star"></i>
                                <span><?php echo number_format($guide['avg_rating'], 1); ?> / 5.0</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="guide-tags">
                            <span class="tag"><?php echo htmlspecialchars($guide['category']); ?></span>
                            <?php
                            // 获取标签
                            try {
                                $tags_stmt = $pdo->prepare("SELECT tag FROM guide_tags WHERE guide_id = ? LIMIT 3");
                                $tags_stmt->execute([$guide['id']]);
                                $tags = $tags_stmt->fetchAll();
                                
                                foreach ($tags as $tag):
                            ?>
                            <span class="tag"><?php echo htmlspecialchars($tag['tag']); ?></span>
                            <?php 
                                endforeach;
                            } catch (Exception $e) {
                                // 忽略标签错误
                            }
                            ?>
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
.guides-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.guides-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--dark) 100%);
    color: white;
    border-radius: 15px;
    padding: 40px;
    margin-bottom: 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.guides-hero h1 {
    font-size: 2.5em;
    margin-bottom: 15px;
}

.subtitle {
    font-size: 1.2em;
    opacity: 0.9;
    margin-bottom: 30px;
}

.hero-stats {
    display: flex;
    gap: 30px;
    margin-top: 30px;
}

.hero-stats .stat {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 2.5em;
    font-weight: bold;
    color: var(--accent);
}

.stat-label {
    font-size: 0.9em;
    opacity: 0.8;
}

.guides-actions .btn-large {
    padding: 15px 30px;
    font-size: 1.1em;
}

.guides-main {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 30px;
}

/* 侧边栏样式 */
.guides-sidebar {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.filter-card, .popular-card, .contribute-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.filter-card h3, .popular-card h3, .contribute-card h3 {
    color: var(--primary);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-section {
    margin-bottom: 25px;
}

.filter-section h4 {
    color: #666;
    margin-bottom: 12px;
    font-size: 0.95em;
    font-weight: 600;
}

.filter-options {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.3s;
}

.filter-option:hover {
    background: #f8f9fa;
}

.filter-option input[type="radio"] {
    margin-right: 10px;
}

.filter-option .count {
    background: #e9ecef;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.8em;
    font-weight: bold;
}

.sort-select {
    width: 100%;
    padding: 12px;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    background: white;
}

.btn-reset {
    width: 100%;
    padding: 12px;
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 20px;
    transition: background 0.3s;
}

.btn-reset:hover {
    background: #545b62;
}

.popular-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.popular-item {
    padding: 15px;
    border: 1px solid #eee;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s;
}

.popular-item:hover {
    border-color: var(--accent);
    transform: translateX(5px);
}

.popular-content h4 {
    margin-bottom: 10px;
    font-size: 1em;
}

.popular-meta {
    display: flex;
    justify-content: space-between;
    font-size: 0.85em;
    color: #666;
}

.contribute-card ul {
    margin: 15px 0;
    padding-left: 20px;
    color: #666;
}

.contribute-card li {
    margin-bottom: 8px;
}

.contribute-card .btn-secondary {
    width: 100%;
    text-align: center;
    margin-top: 15px;
}

/* 攻略列表样式 */
.guides-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.empty-guides {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
}

.empty-guides i {
    font-size: 4em;
    color: #ddd;
    margin-bottom: 20px;
}

.guides-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
}

.guide-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    transition: transform 0.3s, box-shadow 0.3s;
}

.guide-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.12);
}

.guide-header {
    background: linear-gradient(90deg, var(--primary), var(--dark));
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.featured-badge {
    background: var(--warning);
    color: #333;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 5px;
}

.difficulty-badge {
    padding: 5px 12px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
    color: white;
}

.difficulty-badge.beginner {
    background: var(--success);
}

.difficulty-badge.intermediate {
    background: var(--warning);
    color: #333;
}

.difficulty-badge.advanced {
    background: var(--danger);
}

.guide-content {
    padding: 25px;
}

.guide-title {
    margin-bottom: 15px;
    font-size: 1.3em;
}

.guide-title a {
    color: var(--primary);
    text-decoration: none;
}

.guide-title a:hover {
    color: var(--accent);
}

.guide-excerpt {
    color: #666;
    line-height: 1.6;
    margin-bottom: 20px;
}

.guide-meta {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
    font-size: 0.9em;
    color: #666;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.guide-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.guide-stats .stat {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9em;
    color: #666;
}

.guide-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.guide-tags .tag {
    background: #e9ecef;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.8em;
    color: #666;
}

@media (max-width: 1024px) {
    .guides-main {
        grid-template-columns: 1fr;
    }
    
    .guides-header {
        flex-direction: column;
        text-align: center;
        gap: 30px;
    }
    
    .hero-stats {
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .guides-grid {
        grid-template-columns: 1fr;
    }
    
    .hero-stats {
        flex-direction: column;
        gap: 15px;
    }
    
    .guide-meta {
        flex-direction: column;
        gap: 10px;
    }
}
</style>

<script>
// 保存过滤状态到localStorage
document.querySelectorAll('.filter-form input, .filter-form select').forEach(input => {
    input.addEventListener('change', function() {
        // 保存状态
        const formData = new FormData(document.querySelector('.filter-form'));
        const filters = {};
        formData.forEach((value, key) => {
            filters[key] = value;
        });
        localStorage.setItem('guide_filters', JSON.stringify(filters));
    });
});

// 加载保存的过滤状态
window.addEventListener('load', function() {
    const savedFilters = localStorage.getItem('guide_filters');
    if (savedFilters) {
        const filters = JSON.parse(savedFilters);
        // 这里可以设置表单值，但需要小心处理以免影响当前页面状态
    }
});

// 平滑滚动到攻略
document.querySelectorAll('.guide-card').forEach(card => {
    card.addEventListener('click', function(e) {
        if (!e.target.closest('a')) {
            window.location = this.querySelector('.guide-title a').href;
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>