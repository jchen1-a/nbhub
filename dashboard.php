<?php
// dashboard.php - 100% 完整版 (彻底修复排版崩坏，硬派武侠风格)
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
        <h1><i class="fas fa-tachometer-alt" style="color: var(--accent);"></i> Panel de Usuario</h1>
        <p class="user-greeting" style="font-family: 'Segoe UI', sans-serif; font-size: 1.1em; color: #555;">Hola, <strong style="color: var(--accent); font-family: 'Cinzel', serif; font-size: 1.2em;"><?php echo htmlspecialchars($user_data['username']); ?></strong></p>
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
                    <p style="font-family: 'Segoe UI', sans-serif;"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user_data['email']); ?></p>
                    <p class="join-date" style="font-family: 'Segoe UI', sans-serif;"><i class="far fa-clock"></i> Desde: <?php echo date('d/m/Y', strtotime($user_data['joined_date'])); ?></p>
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
                    <h3 style="margin:0;"><i class="fas fa-scroll" style="color: var(--accent);"></i> Mis Guías Recientes</h3>
                    <a href="new-guide.php" class="btn-sm btn-primary" style="border-radius:4px;"><i class="fas fa-plus"></i> Crear</a>
                </div>
                <div class="card-body" style="padding: 0;">
                    <?php if (!empty($articles)): ?>
                        <div class="article-list">
                        <?php foreach ($articles as $art): ?>
                        <div class="article-item" style="display:flex; justify-content:space-between; align-items:center; padding:15px 20px; border-bottom:1px solid #eee; transition: background 0.2s;">
                            <div class="art-info">
                                <h4 style="margin:0 0 5px 0; font-family: 'Segoe UI', sans-serif;">
                                    <a href="article.php?id=<?php echo $art['id']; ?>" style="color:var(--text); text-decoration:none; font-weight:bold;">
                                        <?php echo htmlspecialchars($art['title']); ?>
                                    </a>
                                </h4>
                                <small style="color:#888; font-family: 'Segoe UI', sans-serif;"><i class="far fa-clock"></i> <?php echo date('d/m/Y', strtotime($art['created_at'])); ?></small>
                            </div>
                            <div class="article-actions" style="display:flex; gap:10px; align-items:center;">
                                <span style="font-weight:bold; color:var(--accent); margin-right:10px; font-family: 'Segoe UI', sans-serif;"><i class="fas fa-eye"></i> <?php echo $art['views']; ?></span>
                                <a href="article.php?id=<?php echo $art['id']; ?>" class="btn-sm btn-outline" title="Ver"><i class="fas fa-eye"></i></a>
                                <a href="edit-guide.php?id=<?php echo $art['id']; ?>" class="btn-sm btn-outline" title="Editar"><i class="fas fa-edit"></i></a>
                                <a href="delete-guide.php?id=<?php echo $art['id']; ?>" class="btn-sm btn-danger-icon" title="Eliminar" onclick="return confirm('¿Eliminar esta guía?');"><i class="fas fa-trash-alt"></i></a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:50px; color:#999;">
                            <i class="fas fa-folder-open" style="font-size:4em; margin-bottom:15px; display:block; color:#ddd;"></i>
                            <p style="font-family: 'Segoe UI', sans-serif; font-size: 1.1em;">No tienes guías aún.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
/* ================= 仪表盘彻底修复排版 CSS ================= */
.dashboard-container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
.dashboard-header { margin-bottom: 30px; border-bottom: 2px solid #ccc; padding-bottom: 15px; }
.dashboard-header h1 { margin: 0; font-size: 2.2em; }
.dashboard-grid { display: grid; grid-template-columns: 320px 1fr; gap: 30px; }

/* 侧边栏卡片 */
.user-profile-card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; border-top: 5px solid var(--primary); border-bottom: 1px solid #ddd; border-left: 1px solid #ddd; border-right: 1px solid #ddd; }
.profile-avatar { font-size: 90px; color: #ddd; margin-bottom: 15px; }
.profile-info h3 { margin: 0 0 5px 0; font-size: 1.8em; color: var(--primary); }
.profile-info p { margin: 5px 0; color: #666; font-size: 0.95em; }

/* 统计数据 */
.profile-stats { display: flex; gap: 15px; margin: 25px 0; border-top: 1px dashed #ccc; border-bottom: 1px dashed #ccc; padding: 20px 0; }
.stat-item { flex: 1; background: #fafafa; padding: 15px; border-radius: 4px; border: 1px solid #eee; }
.stat-number { display: block; font-size: 2em; font-weight: 800; color: var(--accent); line-height: 1; font-family: 'Cinzel', serif; }
.stat-label { font-size: 0.85em; color: #888; text-transform: uppercase; font-weight: bold; margin-top: 5px; display: block; font-family: 'Segoe UI', sans-serif; }

/* 操作按钮 */
.profile-actions { display: flex; flex-direction: column; gap: 12px; }
.btn-profile { display: block; padding: 12px; text-decoration: none; color: #333; border: 2px solid #ddd; border-radius: 4px; font-weight: bold; transition: 0.3s; background: white; text-transform: uppercase; font-size: 0.9em; letter-spacing: 1px; }
.btn-profile:hover { background: #f0f0f0; border-color: #bbb; color: var(--primary); }
.btn-profile.btn-danger { color: var(--accent); border-color: #ffcdd2; }
.btn-profile.btn-danger:hover { background: var(--accent); color: white; border-color: var(--accent); }

/* 列表内小按钮 */
.btn-sm { padding: 6px 12px; border-radius: 4px; font-size: 0.9em; text-decoration: none; border: 1px solid #ccc; display: inline-flex; align-items: center; justify-content: center; background: white; color: #333; transition: 0.2s; }
.btn-sm:hover { background: #f0f0f0; }
.btn-danger-icon { padding: 6px 12px; border-radius: 4px; font-size: 0.9em; text-decoration: none; border: 1px solid #ffcdd2; display: inline-flex; align-items: center; justify-content: center; background: white; color: var(--accent); transition: 0.2s; }
.btn-danger-icon:hover { background: var(--accent); color: white; border-color: var(--accent); }

.article-item:hover { background: #fafafa; border-left: 4px solid var(--accent); padding-left: 16px; }
.article-item:hover h4 a { color: var(--accent) !important; }

@media (max-width: 768px) {
    .dashboard-grid { grid-template-columns: 1fr; }
}
</style>

<?php include 'includes/footer.php'; ?>