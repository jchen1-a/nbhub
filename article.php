<?php
// article.php - 显示单篇攻略详情 (支持视频播放)
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = is_logged_in() ? $_SESSION['user_id'] : 0;

try {
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT a.*, u.username, u.country, u.avatar FROM articles a LEFT JOIN users u ON a.user_id = u.id WHERE a.id = ?");
    $stmt->execute([$id]);
    $article = $stmt->fetch();
    
    if (!$article) die("La guía no existe.");

    if ($user_id != $article['user_id'] && (!isset($_SESSION['viewed_articles']) || !in_array($id, $_SESSION['viewed_articles']))) {
        $pdo->prepare("UPDATE articles SET views = views + 1 WHERE id = ?")->execute([$id]);
        $article['views']++;
        $_SESSION['viewed_articles'][] = $id;
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// 辅助函数：转换YouTube链接为嵌入代码
function getYoutubeEmbedUrl($url) {
    if (preg_match('/[\\?\\&]v=([^\\?\\&]+)/', $url, $matches)) return "https://www.youtube.com/embed/" . $matches[1];
    if (preg_match('/youtu\\.be\\/([^\\?\\&]+)/', $url, $matches)) return "https://www.youtube.com/embed/" . $matches[1];
    return null;
}
?>
<?php include 'includes/header.php'; ?>

<div class="container" style="padding: 40px 20px; max-width: 900px; margin: 0 auto;">
    <div class="article-header" style="background: #f8f9fa; padding: 30px; border-radius: 12px; border-left: 5px solid var(--accent); margin-bottom: 30px;">
        <div style="display:flex; gap:10px; margin-bottom:15px;">
            <span style="background:#e9ecef; padding:5px 12px; border-radius:20px; font-size:0.85em; font-weight:bold; color:#555;"><?php echo ucfirst($article['category']); ?></span>
            <span class="difficulty-indicator <?php echo $article['difficulty']; ?>" style="padding:5px 12px; border-radius:20px; font-size:0.85em; font-weight:bold; color:white;">
                <?php echo ucfirst($article['difficulty']); ?>
            </span>
        </div>
        <h1 style="color: var(--primary); font-size: 2.5em; margin: 0 0 20px 0; line-height: 1.2;"><?php echo htmlspecialchars($article['title']); ?></h1>
        <div style="display:flex; gap:20px; color:#666; font-size:0.9em;">
            <span><i class="far fa-clock"></i> <?php echo date('d/m/Y', strtotime($article['created_at'])); ?></span>
            <span><i class="fas fa-eye"></i> <?php echo $article['views']; ?> vistas</span>
        </div>
    </div>

    <div class="author-card" style="display: flex; align-items: center; gap: 15px; background: white; padding: 15px 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
        <?php if($article['avatar']): ?>
            <img src="<?php echo htmlspecialchars($article['avatar']); ?>" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
        <?php else: ?>
            <i class="fas fa-user-circle" style="font-size: 50px; color: #ccc;"></i>
        <?php endif; ?>
        <div>
            <div style="font-weight:bold; font-size:1.1em; color:var(--primary);">
                Por <a href="profile.php?user=<?php echo $article['user_id']; ?>" style="color:var(--accent); text-decoration:none;"><?php echo htmlspecialchars($article['username']); ?></a>
                <?php if($user_id == $article['user_id']) echo "<span style='font-size:0.8em; color:#888;'>(Tú)</span>"; ?>
            </div>
        </div>
    </div>

    <?php if(!empty($article['video_path']) || !empty($article['video_url'])): ?>
    <div style="margin-bottom: 30px; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
        <?php if(!empty($article['video_path'])): ?>
            <video controls style="width: 100%; max-height: 500px; background: #000;">
                <source src="<?php echo htmlspecialchars($article['video_path']); ?>" type="video/mp4">
                Tu navegador no soporta video.
            </video>
        <?php elseif($embed = getYoutubeEmbedUrl($article['video_url'])): ?>
            <div style="position: relative; padding-bottom: 56.25%; height: 0;">
                <iframe src="<?php echo $embed; ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;" allowfullscreen></iframe>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); font-size: 1.1em; line-height: 1.8; color: #333; overflow-wrap: break-word; min-height: 200px;">
        <?php echo nl2br(htmlspecialchars($article['content'])); ?>
    </div>
    
    <div style="margin-top: 30px; display: flex; justify-content: space-between;">
        <a href="guides.php" style="padding: 10px 20px; border: 2px solid #ddd; border-radius: 8px; color: #333; text-decoration:none; font-weight:bold;"><i class="fas fa-arrow-left"></i> Volver</a>
        <?php if ($user_id == $article['user_id']): ?>
            <a href="delete-guide.php?id=<?php echo $article['id']; ?>" class="btn-danger" style="padding: 10px 20px; border: 1px solid #dc3545; color: #dc3545; border-radius: 8px; text-decoration:none; font-weight:bold;" onclick="return confirm('¿Eliminar esta guía?');"><i class="fas fa-trash"></i> Eliminar</a>
        <?php endif; ?>
    </div>
</div>

<style>
.difficulty-indicator.beginner { background: #28a745; }
.difficulty-indicator.intermediate { background: #ffc107; color: #333 !important; }
.difficulty-indicator.advanced { background: #dc3545; }
.btn-danger:hover { background: #dc3545; color: white !important; }
</style>

<?php include 'includes/footer.php'; ?>