<?php
// dashboard.php - 完美恢复排版版
require_once 'config.php';
require_login();

$user_id = $_SESSION['user_id'];

try {
    $pdo = db_connect();
    
    $stmt = $pdo->prepare("
        SELECT username, email, created_at as joined_date,
               (SELECT COUNT(*) FROM articles WHERE user_id = ?) as articles_count,
               (SELECT COUNT(*) FROM forum_posts WHERE user_id = ?) as posts_count
        FROM users WHERE id = ?
    ");
    $stmt->execute([$user_id, $user_id, $user_id]);
    $user_data = $stmt->fetch();
    
    if (!$user_data) {
        $user_data = ['username' => 'Usuario', 'email' => '', 'joined_date' => date('Y-m-d'), 'articles_count' => 0, 'posts_count' => 0];
    }
    
    $recentArticles = $pdo->prepare("SELECT id, title, created_at, views, is_published FROM articles WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
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
                        <span class="stat-number"><?php echo $user_data['articles_count']; ?></span>
                        <span class="stat-label">Guías</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $user_data['posts_count']; ?></span>
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
                    <h3 style="margin:0;"><i class="fas fa-file-alt"></i> Mis Guías Recientes</h3>
                    <a href="new-guide.php" class="btn-sm btn-primary"><i class="fas fa-plus"></i> Crear</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($articles)): ?>
                        <div class="article-list">
                        <?php foreach ($articles as $art): ?>
                        <div class="article-item" style="display:flex; justify-content:space-between; align-items:center; padding:15px; border-bottom:1px solid #eee;">
                            <div class="art-info">
                                <h4 style="margin:0 0 5px 0;">
                                    <a href="article.php?id=<?php echo $art['id']; ?>" style="color:var(--primary); text-decoration:none; font-weight:bold;">
                                        <?php echo htmlspecialchars($art['title']); ?>
                                    </a>
                                </h4>
                                <small style="color:#888;"><?php echo date('d/m/Y', strtotime($art['created_at'])); ?></small>
                            </div>
                            <div class="article-actions" style="display:flex; gap:10px; align-items:center;">
                                <span style="font-weight:bold; color:var(--accent); margin-right:5px;"><i class="fas fa-eye"></i> <?php echo $art['views']; ?></span>
                                <a href="article.php?id=<?php echo $art['id']; ?>" class="btn-sm btn-outline" title="Ver"><i class="fas fa-eye"></i></a>
                                <a href="edit-guide.php?id=<?php echo $art['id']; ?>" class="btn-sm btn-outline" title="Editar"><i class="fas fa-edit"></i></a>
                                <a href="delete-guide.php?id=<?php echo $art['id']; ?>" class="btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Eliminar esta guía?');"><i class="fas fa-trash-alt"></i></a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:40px; color:#666;">
                            <i class="fas fa-folder-open" style="font-size:3em; margin-bottom:15px; display:block; color:#ddd;"></i>
                            <p>No tienes guías aún.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
/* ================= 仪表盘专属排版 CSS ================= */
.dashboard-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
.dashboard-header { margin-bottom: 30px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; }
.dashboard-header h1 { margin: 0; color: var(--primary); }
.dashboard-grid { display: grid; grid-template-columns: 320px 1fr; gap: 30px; }

.user-profile-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); text-align: center; }
.profile-avatar { font-size: 90px; color: #e9ecef; margin-bottom: 15px; }
.profile-info h3 { margin: 0 0 5px 0; color: var(--primary); font-size: 1.5em; }
.profile-info p { margin: 5px 0; color: #666; font-size: 0.95em; }

.profile-stats { display: flex; gap: 15px; margin: 25px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee; padding: 20px 0; }
.stat-item { flex: 1; background: #f8f9fa; padding: 15px; border-radius: 8px; }
.stat-number { display: block; font-size: 1.8em; font-weight: bold; color: var(--accent); line-height: 1; }
.stat-label { font-size: 0.85em; color: #888; text-transform: uppercase; font-weight: bold; margin-top: 5px; display: block; }

.profile-actions { display: flex; flex-direction: column; gap: 12px; }
.btn-profile { display: block; padding: 12px; text-decoration: none; color: #555; border: 2px solid #eee; border-radius: 8px; font-weight: bold; transition: 0.3s; }
.btn-profile:hover { background: #f8f9fa; border-color: #ddd; color: var(--primary); }
.btn-danger { color: #dc3545; border-color: #f5c6cb; }
.btn-danger:hover { background: #dc3545; color: white; border-color: #dc3545; }

.btn-sm { padding: 8px 12px; border-radius: 6px; font-size: 0.9em; text-decoration: none; border: 1px solid #ddd; display: inline-flex; align-items: center; justify-content: center; }

@media (max-width: 768px) {
    .dashboard-grid { grid-template-columns: 1fr; }
}
</style>

<?php include 'includes/footer.php'; ?>