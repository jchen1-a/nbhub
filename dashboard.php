<?php
// dashboard.php - 完整版 (强制从数据库实时读取用户名称，消除缓存Bug)
require_once 'config.php';
require_login();

$user_id = $_SESSION['user_id'];

try {
    $pdo = db_connect();
    
    // 【核心修复】实时查询数据库，获取最新用户名、邮箱等，完全不依赖缓存
    $stmt = $pdo->prepare("
        SELECT 
            username, 
            email, 
            created_at as joined_date,
            (SELECT COUNT(*) FROM articles WHERE user_id = ?) as articles_count,
            (SELECT COUNT(*) FROM forum_posts WHERE user_id = ?) as posts_count
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id, $user_id, $user_id]);
    $user_data = $stmt->fetch();
    
    // 获取最近文章
    $recentArticles = $pdo->prepare("
        SELECT id, title, created_at, views, is_published
        FROM articles 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $recentArticles->execute([$user_id]);
    $articles = $recentArticles->fetchAll();
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>
<?php include 'includes/header.php'; ?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-tachometer-alt"></i> Panel de Usuario</h1>
        <p class="user-greeting">Hola, <strong><?php echo htmlspecialchars($user_data['username']); ?></strong></p>
    </div>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="dashboard-grid">
        <aside class="dashboard-sidebar">
            <div class="user-profile-card">
                <div class="profile-avatar"><i class="fas fa-user-circle"></i></div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($user_data['username']); ?></h3>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user_data['email']); ?></p>
                    <p class="join-date"><i class="far fa-clock"></i> Desde: <?php echo date('d/m/Y', strtotime($user_data['joined_date'])); ?></p>
                </div>
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $user_data['articles_count'] ?? 0; ?></span>
                        <span class="stat-label">Guías</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $user_data['posts_count'] ?? 0; ?></span>
                        <span class="stat-label">Posts</span>
                    </div>
                </div>
                <div class="profile-actions">
                    <a href="new-guide.php" class="btn-primary" style="width:100%; margin-bottom:10px;">
                        <i class="fas fa-plus"></i> Nueva Guía
                    </a>
                    <a href="profile.php" class="btn-profile">Ver mi Perfil</a>
                    <a href="logout.php" class="btn-profile btn-danger">Cerrar Sesión</a>
                </div>
            </div>
        </aside>
        
        <main class="dashboard-content">
            <div class="card">
                <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <h3><i class="fas fa-file-alt"></i> Mis Guías Recientes</h3>
                    <a href="new-guide.php" class="btn-sm btn-primary"><i class="fas fa-plus"></i> Crear</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($articles)): ?>
                        <div class="article-list">
                        <?php foreach ($articles as $art): ?>
                        <div class="article-item" style="display:flex; justify-content:space-between; align-items:center; padding:15px; border-bottom:1px solid #eee;">
                            <div class="art-info">
                                <h4 style="margin:0 0 5px 0;">
                                    <a href="article.php?id=<?php echo $art['id']; ?>" style="color:#333; text-decoration:none; font-weight:bold;">
                                        <?php echo htmlspecialchars($art['title']); ?>
                                    </a>
                                </h4>
                                <small style="color:#888;">
                                    <?php echo date('d/m/Y', strtotime($art['created_at'])); ?> 
                                    <?php if(!$art['is_published']) echo ' <span style="color:orange;">(Borrador)</span>'; ?>
                                </small>
                            </div>
                            <div class="article-actions" style="display:flex; gap:10px; align-items:center;">
                                <span style="font-weight:bold; color:#00adb5; margin-right:5px;">
                                    <i class="fas fa-eye"></i> <?php echo $art['views']; ?>
                                </span>
                                <a href="article.php?id=<?php echo $art['id']; ?>" class="btn-sm btn-outline" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="delete-guide.php?id=<?php echo $art['id']; ?>" 
                                   class="btn-sm btn-danger" 
                                   title="Eliminar"
                                   onclick="return confirm('¿Estás seguro de que deseas eliminar esta guía? Esta acción no se puede deshacer.');">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:40px; color:#666;">
                            <i class="fas fa-folder-open" style="font-size:3em; margin-bottom:15px; display:block; color:#ddd;"></i>
                            <p>No tienes guías aún.</p>
                            <a href="new-guide.php" class="btn-primary">¡Crea tu primera guía!</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.btn-sm {
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.9em;
    text-decoration: none;
    border: 1px solid #ddd;
    display: inline-block;
}
.btn-outline { background: white; color: #333; }
.btn-outline:hover { background: #f0f0f0; }

.btn-danger { background: #fff; color: #dc3545; border-color: #dc3545; }
.btn-danger:hover { background: #dc3545; color: white; }
</style>

<?php include 'includes/footer.php'; ?>