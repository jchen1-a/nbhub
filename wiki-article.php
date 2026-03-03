<?php
// wiki-article.php - 查看词条详情
require_once 'config.php';

$id = intval($_GET['id'] ?? 0);
$user_id = is_logged_in() ? $_SESSION['user_id'] : 0;

try {
    $pdo = db_connect();
    
    // 获取文章及作者信息
    $stmt = $pdo->prepare("
        SELECT w.*, u.username, c.name as category_name 
        FROM wiki_articles w
        LEFT JOIN users u ON w.author_id = u.id
        LEFT JOIN wiki_categories c ON w.category_id = c.id
        WHERE w.id = ?
    ");
    $stmt->execute([$id]);
    $article = $stmt->fetch();
    
    if (!$article) {
        header("HTTP/1.0 404 Not Found");
        die("El artículo no existe.");
    }

    // 智能防刷浏览量
    if (!isset($_SESSION['viewed_wiki'])) $_SESSION['viewed_wiki'] = [];
    if ($user_id != $article['author_id'] && !in_array($id, $_SESSION['viewed_wiki'])) {
        $pdo->prepare("UPDATE wiki_articles SET views = views + 1 WHERE id = ?")->execute([$id]);
        $article['views']++;
        $_SESSION['viewed_wiki'][] = $id;
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<?php include 'includes/header.php'; ?>

<div class="container" style="padding: 40px 20px; max-width: 900px;">
    <div style="margin-bottom: 20px; font-size: 0.9em;">
        <a href="wiki.php" style="color: var(--accent); text-decoration: none;"><i class="fas fa-book"></i> Wiki</a> 
        &raquo; <?php echo htmlspecialchars($article['category_name'] ?? 'General'); ?>
    </div>

    <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
        
        <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom: 2px solid #f0f0f0; padding-bottom: 20px; margin-bottom: 30px;">
            <div>
                <h1 style="color: var(--primary); font-size: 2.5em; margin: 0 0 15px 0;"><?php echo htmlspecialchars($article['title']); ?></h1>
                <div style="color: #666; font-size: 0.9em; display: flex; gap: 15px; align-items: center;">
                    <span><i class="far fa-calendar"></i> <?php echo date('d/m/Y', strtotime($article['created_at'])); ?></span>
                    <span><i class="fas fa-user"></i> Contribución de <strong><?php echo htmlspecialchars($article['username']); ?></strong></span>
                    <span><i class="fas fa-eye"></i> <?php echo $article['views']; ?> vistas</span>
                </div>
            </div>
            
            <?php if(is_logged_in()): ?>
                <a href="wiki-edit.php?id=<?php echo $article['id']; ?>" style="padding: 10px 20px; background: #f8f9fa; color: var(--primary); border: 1px solid #ddd; border-radius: 6px; text-decoration: none; font-weight: bold; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-edit"></i> Editar
                </a>
            <?php endif; ?>
        </div>

        <div class="wiki-content-body">
            <?php echo nl2br(htmlspecialchars($article['content'])); ?>
        </div>
        
    </div>
</div>

<style>
/* 核心修复：防止内容撑爆屏幕 */
.wiki-content-body {
    font-size: 1.1em;
    line-height: 1.8;
    color: #333;
    overflow-wrap: break-word; /* 强制换行 */
    word-wrap: break-word;     
    word-break: break-word;    
}
</style>

<?php include 'includes/footer.php'; ?>