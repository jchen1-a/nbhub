<?php
// profile.php - 完整版
require_once 'config.php';

// 获取要查看的用户ID，默认查看自己
$user_id = isset($_GET['user']) ? sanitize($_GET['user']) : (is_logged_in() ? current_user()['id'] : null);

if (!$user_id) {
    header('Location: login.php');
    exit;
}

try {
    $pdo = db_connect();
    
    // 获取用户信息及统计
    $stmt = $pdo->prepare("
        SELECT id, username, email, country, created_at,
               (SELECT COUNT(*) FROM articles WHERE user_id = users.id) as guide_count,
               (SELECT COUNT(*) FROM forum_posts WHERE user_id = users.id) as post_count
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $profile_user = $stmt->fetch();

    if (!$profile_user) {
        die("Usuario no encontrado.");
    }

    // 获取最近发布的攻略
    $guides_stmt = $pdo->prepare("SELECT id, title, views, created_at FROM articles WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $guides_stmt->execute([$user_id]);
    $recent_guides = $guides_stmt->fetchAll();

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<?php include 'includes/header.php'; ?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-user"></i> Perfil de <?php echo htmlspecialchars($profile_user['username']); ?></h1>
    </div>
    
    <div class="dashboard-grid">
        <aside class="dashboard-sidebar">
            <div class="user-profile-card">
                <div class="profile-avatar"><i class="fas fa-user-circle"></i></div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($profile_user['username']); ?></h3>
                    <p><i class="fas fa-globe"></i> País: <?php echo htmlspecialchars($profile_user['country'] ?? 'N/A'); ?></p>
                </div>
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $profile_user['guide_count']; ?></span>
                        <span class="stat-label">Guías</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $profile_user['post_count']; ?></span>
                        <span class="stat-label">Posts</span>
                    </div>
                </div>
            </div>
        </aside>

        <main class="dashboard-content">
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-scroll"></i> Guías Publicadas</h3></div>
                <div class="card-body">
                    <?php if ($recent_guides): ?>
                        <?php foreach ($recent_guides as $g): ?>
                        <div class="article-item" style="border-bottom:1px solid #eee; padding:10px 0;">
                            <a href="article.php?id=<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['title']); ?></a>
                            <small>(<?php echo $g['views']; ?> vistas)</small>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Este usuario aún no ha publicado guías.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include 'includes/footer.php'; ?>