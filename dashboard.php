<?php
// dashboard.php - 修正版
require_once 'config.php';
require_login();

$user = current_user();
$user_id = $user['id'];

try {
    $pdo = db_connect();
    
    // 修正：将预处理语句赋值给 $stmt，并确保表名使用 articles
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM articles WHERE user_id = ?) as articles_count,
            (SELECT COUNT(*) FROM forum_posts WHERE user_id = ?) as posts_count,
            (SELECT created_at FROM users WHERE id = ?) as joined_date
    ");
    $stmt->execute([$user_id, $user_id, $user_id]);
    $stats = $stmt->fetch();
    
    // 获取最近文章
    $recentArticles = $pdo->prepare("
        SELECT id, title, created_at, views 
        FROM articles 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentArticles->execute([$user_id]);
    $articles = $recentArticles->fetchAll();
    
} catch (Exception $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}
?>
<?php include 'includes/header.php'; ?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-tachometer-alt"></i> Panel de Usuario</h1>
        <p class="user-greeting">Bienvenido, <strong><?php echo htmlspecialchars($user['name']); ?></strong></p>
    </div>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="dashboard-grid">
        <aside class="dashboard-sidebar">
            <div class="user-profile-card">
                <div class="profile-avatar"><i class="fas fa-user-circle"></i></div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><i class="fas fa-calendar"></i> Miembro desde: <?php echo isset($stats['joined_date']) ? date('d/m/Y', strtotime($stats['joined_date'])) : '---'; ?></p>
                </div>
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['articles_count'] ?? 0; ?></span>
                        <span class="stat-label">Guías</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['posts_count'] ?? 0; ?></span>
                        <span class="stat-label">Posts</span>
                    </div>
                </div>
                <div class="profile-actions">
                    <a href="profile.php" class="btn-profile">Ver mi Perfil</a>
                    <a href="logout.php" class="btn-profile btn-danger">Cerrar Sesión</a>
                </div>
            </div>
        </aside>
        
        <main class="dashboard-content">
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-file-alt"></i> Mis Guías Recientes</h3></div>
                <div class="card-body">
                    <?php if (!empty($articles)): ?>
                        <?php foreach ($articles as $art): ?>
                        <div class="article-item">
                            <h4><a href="article.php?id=<?php echo $art['id']; ?>"><?php echo htmlspecialchars($art['title']); ?></a></h4>
                            <div class="article-meta"><span>Vistas: <?php echo $art['views']; ?></span></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No tienes guías aún. <a href="new-guide.php">Crea una aquí</a></p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include 'includes/footer.php'; ?>