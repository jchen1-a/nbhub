<?php
// article.php - 完整版 (含删除功能与样式修复)
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = is_logged_in() ? $_SESSION['user_id'] : 0;

try {
    $pdo = db_connect();
    
    // 1. 先查询文章信息
    $stmt = $pdo->prepare("
        SELECT a.*, u.username, u.country 
        FROM articles a
        LEFT JOIN users u ON a.user_id = u.id 
        WHERE a.id = ?
    ");
    $stmt->execute([$id]);
    $article = $stmt->fetch();
    
    // 2. 检查文章是否存在
    if (!$article) {
        header("HTTP/1.0 404 Not Found");
        die("La guía no existe.");
    }

    // 3. 智能浏览量控制
    $should_count_view = true;
    
    // 如果是作者本人，不增加浏览量
    if ($user_id == $article['user_id']) {
        $should_count_view = false;
    }
    
    // 如果 Session 里记录已读，不增加浏览量
    if (!isset($_SESSION['viewed_articles'])) {
        $_SESSION['viewed_articles'] = [];
    }
    
    if (in_array($id, $_SESSION['viewed_articles'])) {
        $should_count_view = false;
    }
    
    if ($should_count_view) {
        $updateViews = $pdo->prepare("UPDATE articles SET views = views + 1 WHERE id = ?");
        $updateViews->execute([$id]);
        $article['views']++;
        $_SESSION['viewed_articles'][] = $id;
    }
    
    // 4. 获取推荐
    $related = [];
    $relStmt = $pdo->prepare("
        SELECT id, title, views 
        FROM articles 
        WHERE category = ? AND id != ? AND is_published = 1
        ORDER BY views DESC 
        LIMIT 3
    ");
    $relStmt->execute([$article['category'], $id]);
    $related = $relStmt->fetchAll();

} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>
<?php include 'includes/header.php'; ?>

<div class="container" style="padding: 40px 20px;">
    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <h2>Error</h2>
            <p><?php echo $error; ?></p>
            <a href="guides.php" class="btn-primary">Volver</a>
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
                            <?php if($user_id == $article['user_id']): ?>
                                <span style="font-size:0.8em; color:#00adb5; margin-left:5px;">(Eres el autor)</span>
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
                <?php echo nl2br(htmlspecialchars($article['content'])); ?>
            </div>
            
            <div class="article-footer">
                <a href="guides.php" class="btn-outline"><i class="fas fa-arrow-left"></i> Volver</a>
                
                <?php if (is_logged_in() && $_SESSION['user_id'] == $article['user_id']): ?>
                    <div class="author-actions">
                        <a href="delete-guide.php?id=<?php echo $article['id']; ?>" 
                           class="btn-danger"
                           style="padding: 10px 20px; border-radius: 6px; text-decoration: none; border: 1px solid #dc3545; display:inline-flex; align-items:center; gap:5px;"
                           onclick="return confirm('¿Estás seguro de que deseas eliminar esta guía?');">
                            <i class="fas fa-trash-alt"></i> Eliminar
                        </a>
                    </div>
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
/* 复用样式 */
.article-container { background: white; border-radius: 15px; padding: 40px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); max-width: 900px; margin: 0 auto; }
.article-title { font-size: 2.5em; color: #1a1a2e; margin-bottom: 25px; line-height: 1.2; }
.author-bar { display: flex; justify-content: space-between; align-items: center; color: #666; }
.author-info { display: flex; align-items: center; gap: 15px; }
.avatar-icon { font-size: 2.5em; color: #ddd; }
.article-divider { border: 0; border-top: 1px solid #eee; margin: 30px 0; }

/* 修复长文本炸版问题的关键 CSS */
.article-content { 
    font-size: 1.1em; 
    line-height: 1.8; 
    color: #333; 
    min-height: 200px;
    overflow-wrap: break-word; /* 关键 */
    word-wrap: break-word;     /* 兼容旧版 */
    word-break: break-word;    /* 暴力换行 */
}

.article-footer { margin-top: 50px; display: flex; justify-content: space-between; align-items: center; }
.badge { padding: 5px 12px; border-radius: 20px; font-size: 0.85em; font-weight: bold; text-transform: uppercase; margin-right: 10px; }
.category-badge { background: #e9ecef; color: #555; }
.difficulty-badge.beginner { background: #28a745; color: white; }
.difficulty-badge.intermediate { background: #ffc107; color: #333; }
.difficulty-badge.advanced { background: #dc3545; color: white; }
.related-section { max-width: 900px; margin: 40px auto 0; }
.related-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
.related-card { background: white; padding: 20px; border-radius: 10px; text-decoration: none; color: #333; box-shadow: 0 3px 10px rgba(0,0,0,0.05); transition: transform 0.2s; display: flex; justify-content: space-between; }
.related-card:hover { transform: translateY(-3px); }

@media (max-width: 768px) {
    .author-bar { flex-direction: column; align-items: flex-start; gap: 10px; }
    .article-footer { flex-direction: column; gap: 20px; align-items: stretch; }
    .author-actions { text-align: center; }
}
</style>

<?php include 'includes/footer.php'; ?>