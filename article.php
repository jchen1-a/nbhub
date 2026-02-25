<?php
// article.php - 修复浏览量 Bug 版
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = is_logged_in() ? $_SESSION['user_id'] : 0;

try {
    $pdo = db_connect();
    
    // 1. 先查询文章信息 (而不是先增加浏览量)
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

    // 3. 智能浏览量控制逻辑
    $should_count_view = true;
    
    // 规则 A: 如果是作者本人，不增加浏览量
    if ($user_id == $article['user_id']) {
        $should_count_view = false;
    }
    
    // 规则 B: 如果在这个 Session 里已经看过这篇，不增加浏览量 (防止刷新刷数据)
    // 我们用 Session 记录看过的文章 ID
    if (!isset($_SESSION['viewed_articles'])) {
        $_SESSION['viewed_articles'] = [];
    }
    
    if (in_array($id, $_SESSION['viewed_articles'])) {
        $should_count_view = false;
    }
    
    // 4. 如果通过检查，执行增加浏览量
    if ($should_count_view) {
        $updateViews = $pdo->prepare("UPDATE articles SET views = views + 1 WHERE id = ?");
        $updateViews->execute([$id]);
        
        // 更新页面上显示的数字 (因为刚才查询的是旧数据)
        $article['views']++;
        
        // 记录到 Session，标记为已读
        $_SESSION['viewed_articles'][] = $id;
    }
    
    // 5. 获取同类推荐
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
/* 复用之前的样式，保持一致性 */
.article-container { background: white; border-radius: 15px; padding: 40px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); max-width: 900px; margin: 0 auto; }
.article-title { font-size: 2.5em; color: #1a1a2e; margin-bottom: 25px; }
.author-bar { display: flex; justify-content: space-between; align-items: center; color: #666; }
.author-info { display: flex; align-items: center; gap: 15px; }
.avatar-icon { font-size: 2.5em; color: #ddd; }
.article-divider { border: 0; border-top: 1px solid #eee; margin: 30px 0; }
.article-content { font-size: 1.1em; line-height: 1.8; color: #333; min-height: 200px; }
.badge { padding: 5px 12px; border-radius: 20px; font-size: 0.85em; font-weight: bold; text-transform: uppercase; margin-right: 10px; }
.category-badge { background: #e9ecef; color: #555; }
.difficulty-badge.beginner { background: #28a745; color: white; }
.difficulty-badge.intermediate { background: #ffc107; color: #333; }
.difficulty-badge.advanced { background: #dc3545; color: white; }
.related-section { max-width: 900px; margin: 40px auto 0; }
.related-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
.related-card { background: white; padding: 20px; border-radius: 10px; text-decoration: none; color: #333; box-shadow: 0 3px 10px rgba(0,0,0,0.05); transition: transform 0.2s; display: flex; justify-content: space-between; }
.related-card:hover { transform: translateY(-3px); }
</style>

<?php include 'includes/footer.php'; ?>