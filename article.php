<?php
// article.php - 显示单篇攻略详情
require_once 'config.php';

// 获取攻略 ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    $pdo = db_connect();
    
    // 1. 增加浏览量 (每次访问 +1)
    $updateViews = $pdo->prepare("UPDATE articles SET views = views + 1 WHERE id = ?");
    $updateViews->execute([$id]);
    
    // 2. 查询攻略详情
    // 关联 users 表获取作者名
    $stmt = $pdo->prepare("
        SELECT a.*, u.username, u.country 
        FROM articles a
        LEFT JOIN users u ON a.user_id = u.id 
        WHERE a.id = ?
    ");
    $stmt->execute([$id]);
    $article = $stmt->fetch();
    
    // 3. 检查是否存在或已发布
    if (!$article) {
        // 如果找不到文章
        header("HTTP/1.0 404 Not Found");
        $error = "La guía no existe o ha sido eliminada.";
    } elseif (!$article['is_published'] && (!is_logged_in() || $_SESSION['user_id'] != $article['user_id'])) {
        // 如果未发布且不是作者本人查看
        $error = "Esta guía está en revisión o borrador.";
    }
    
    // 4. 获取同类推荐 (可选功能)
    $related = [];
    if ($article) {
        $relStmt = $pdo->prepare("
            SELECT id, title, views 
            FROM articles 
            WHERE category = ? AND id != ? AND is_published = 1
            ORDER BY views DESC 
            LIMIT 3
        ");
        $relStmt->execute([$article['category'], $id]);
        $related = $relStmt->fetchAll();
    }

} catch (Exception $e) {
    $error = "Error de sistema: " . $e->getMessage();
}
?>
<?php include 'includes/header.php'; ?>

<div class="container" style="padding: 40px 20px;">
    <?php if (isset($error)): ?>
        <div class="alert alert-error" style="text-align:center; padding: 40px;">
            <i class="fas fa-exclamation-triangle" style="font-size: 3em; margin-bottom: 20px;"></i>
            <h2><?php echo $error; ?></h2>
            <p><a href="guides.php" class="btn-primary">Volver a Guías</a></p>
        </div>
    <?php else: ?>

        <article class="article-container">
            <div class="article-header">
                <div class="article-meta-top">
                    <span class="badge category-badge"><?php echo ucfirst($article['category']); ?></span>
                    <span class="badge difficulty-badge <?php echo $article['difficulty']; ?>">
                        <?php echo ucfirst($article['difficulty']); ?>
                    </span>
                    <span class="date"><i class="far fa-clock"></i> <?php echo date('d/m/Y', strtotime($article['created_at'])); ?></span>
                </div>
                
                <h1 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h1>
                
                <div class="author-bar">
                    <div class="author-info">
                        <i class="fas fa-user-circle avatar-icon"></i>
                        <div>
                            <span class="author-name">
                                Por <a href="profile.php?user=<?php echo $article['user_id']; ?>"><?php echo htmlspecialchars($article['username']); ?></a>
                            </span>
                            <?php if ($article['country']): ?>
                                <span class="flag-icon"><?php echo get_flag($article['country']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="view-count">
                        <i class="fas fa-eye"></i> <?php echo number_format($article['views']); ?> vistas
                    </div>
                </div>
            </div>

            <hr class="article-divider">

            <div class="article-content">
                <?php 
                // 注意：这里使用了 nl2br 来保留换行，如果你之后用了富文本编辑器，可以直接输出
                echo nl2br(htmlspecialchars($article['content'])); 
                ?>
            </div>
            
            <div class="article-footer">
                <a href="guides.php" class="btn-outline"><i class="fas fa-arrow-left"></i> Volver</a>
                <?php if (is_logged_in() && $_SESSION['user_id'] == $article['user_id']): ?>
                    <a href="#" class="btn-secondary"><i class="fas fa-edit"></i> Editar Guía</a>
                <?php endif; ?>
            </div>
        </article>
        
        <?php if (!empty($related)): ?>
        <div class="related-section">
            <h3><i class="fas fa-lightbulb"></i> También te puede interesar</h3>
            <div class="related-grid">
                <?php foreach ($related as $item): ?>
                <a href="article.php?id=<?php echo $item['id']; ?>" class="related-card">
                    <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                    <span><i class="fas fa-eye"></i> <?php echo $item['views']; ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<style>
/* 文章页专用样式 */
.article-container {
    background: white;
    border-radius: 15px;
    padding: 40px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    max-width: 900px;
    margin: 0 auto;
}

.article-meta-top {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    align-items: center;
}

.badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: bold;
    text-transform: uppercase;
}

.category-badge { background: #e9ecef; color: #555; }

.difficulty-badge.beginner { background: var(--success); color: white; }
.difficulty-badge.intermediate { background: var(--warning); color: #333; }
.difficulty-badge.advanced { background: var(--danger); color: white; }

.article-title {
    font-size: 2.5em;
    color: var(--primary);
    margin-bottom: 25px;
    line-height: 1.2;
}

.author-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #666;
}

.author-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.avatar-icon { font-size: 2.5em; color: #ddd; }
.author-name a { color: var(--primary); text-decoration: none; font-weight: bold; }
.author-name a:hover { color: var(--accent); }

.article-divider {
    border: 0;
    border-top: 1px solid #eee;
    margin: 30px 0;
}

.article-content {
    font-size: 1.1em;
    line-height: 1.8;
    color: #333;
    min-height: 200px;
}

.article-footer {
    margin-top: 50px;
    display: flex;
    justify-content: space-between;
}

.related-section {
    max-width: 900px;
    margin: 40px auto 0;
}

.related-section h3 { margin-bottom: 20px; color: var(--primary); }

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.related-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    text-decoration: none;
    color: #333;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    transition: transform 0.2s;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.related-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
.related-card h4 { margin: 0; font-size: 1em; }
.related-card span { color: #999; font-size: 0.9em; }

@media (max-width: 768px) {
    .article-container { padding: 20px; }
    .article-title { font-size: 1.8em; }
    .author-bar { flex-direction: column; align-items: flex-start; gap: 15px; }
}
</style>

<?php 
// 辅助函数：获取国旗 (如果没有在其他地方定义)
if (!function_exists('get_flag')) {
    function get_flag($code) {
        $flags = ['ES'=>'🇪🇸', 'MX'=>'🇲🇽', 'AR'=>'🇦🇷', 'US'=>'🇺🇸', 'CN'=>'🇨🇳'];
        return $flags[$code] ?? '🌐';
    }
}
?>
<?php include 'includes/footer.php'; ?>