<?php
// dashboard.php - 用户仪表板
require_once 'config.php';
require_login();

$user = current_user();
$user_id = $user['id'];

try {
    $pdo = db_connect();
    
    // 获取用户统计数据
    $stats = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM articles WHERE user_id = ?) as articles,
            (SELECT COUNT(*) FROM forum_posts WHERE user_id = ?) as posts,
            (SELECT created_at FROM users WHERE id = ?) as joined_date
    ")->execute([$user_id, $user_id, $user_id]);
    $stats = $stmt->fetch();
    
    // 获取用户最近的文章
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
        <p class="user-greeting">Bienvenido, <strong><?php echo $user['name']; ?></strong></p>
    </div>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <div class="dashboard-grid">
        <!-- 侧边栏 -->
        <aside class="dashboard-sidebar">
            <div class="user-profile-card">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="profile-info">
                    <h3><?php echo $user['name']; ?></h3>
                    <p><i class="fas fa-envelope"></i> <?php echo $user['email']; ?></p>
                    <p><i class="fas fa-calendar"></i> Miembro desde: <?php 
                        echo isset($stats['joined_date']) ? date('d/m/Y', strtotime($stats['joined_date'])) : 'Recientemente';
                    ?></p>
                </div>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['articles'] ?? 0; ?></span>
                        <span class="stat-label">Guías</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['posts'] ?? 0; ?></span>
                        <span class="stat-label">Posts</span>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <a href="edit-profile.php" class="btn-profile">
                        <i class="fas fa-user-edit"></i> Editar Perfil
                    </a>
                    <a href="logout.php" class="btn-profile btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
            
            <nav class="dashboard-nav">
                <h3><i class="fas fa-bars"></i> Menú</h3>
                <ul>
                    <li class="nav-item active">
                        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Resumen</a>
                    </li>
                    <li class="nav-item">
                        <a href="my-articles.php"><i class="fas fa-file-alt"></i> Mis Guías</a>
                    </li>
                    <li class="nav-item">
                        <a href="my-posts.php"><i class="fas fa-comments"></i> Mis Posts</a>
                    </li>
                    <li class="nav-item">
                        <a href="notifications.php"><i class="fas fa-bell"></i> Notificaciones</a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php"><i class="fas fa-cog"></i> Configuración</a>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <!-- 主内容区 -->
        <main class="dashboard-content">
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-file-alt"></i>
                        <h3>Mis Guías Recientes</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($articles)): ?>
                        <div class="articles-list">
                            <?php foreach ($articles as $article): ?>
                            <div class="article-item">
                                <h4><a href="article.php?id=<?php echo $article['id']; ?>"><?php 
                                    echo htmlspecialchars($article['title']); 
                                ?></a></h4>
                                <div class="article-meta">
                                    <span><i class="fas fa-calendar"></i> <?php 
                                        echo date('d/m/Y', strtotime($article['created_at'])); 
                                    ?></span>
                                    <span><i class="fas fa-eye"></i> <?php echo $article['views']; ?> vistas</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <p>Aún no has creado ninguna guía.</p>
                            <a href="new-article.php" class="btn-primary">Crear mi Primera Guía</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-line"></i>
                        <h3>Actividad Reciente</h3>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <div class="activity-item">
                                <i class="fas fa-user-plus activity-icon"></i>
                                <div class="activity-content">
                                    <p>Te uniste a Naraka Hub</p>
                                    <span class="activity-time"><?php 
                                        echo isset($stats['joined_date']) 
                                            ? 'Hace ' . time_ago($stats['joined_date'])
                                            : 'Recientemente';
                                    ?></span>
                                </div>
                            </div>
                            <!-- 可以添加更多活动项目 -->
                            <div class="activity-item">
                                <i class="fas fa-book activity-icon"></i>
                                <div class="activity-content">
                                    <p>Leyste 3 guías hoy</p>
                                    <span class="activity-time">Hace 2 horas</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card quick-actions">
                    <div class="card-header">
                        <i class="fas fa-bolt"></i>
                        <h3>Acciones Rápidas</h3>
                    </div>
                    <div class="card-body">
                        <div class="actions-grid">
                            <a href="new-article.php" class="action-btn">
                                <i class="fas fa-plus-circle"></i>
                                <span>Nueva Guía</span>
                            </a>
                            <a href="forum.php?action=new" class="action-btn">
                                <i class="fas fa-comment-medical"></i>
                                <span>Nuevo Post</span>
                            </a>
                            <a href="wiki.php?edit=true" class="action-btn">
                                <i class="fas fa-edit"></i>
                                <span>Editar Wiki</span>
                            </a>
                            <a href="invite.php" class="action-btn">
                                <i class="fas fa-user-friends"></i>
                                <span>Invitar Amigos</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-section">
                <h2><i class="fas fa-trophy"></i> Logros y Progreso</h2>
                <div class="achievements">
                    <div class="achievement">
                        <i class="fas fa-seedling"></i>
                        <span>Novato</span>
                    </div>
                    <div class="achievement locked">
                        <i class="fas fa-pen-fancy"></i>
                        <span>Escritor</span>
                    </div>
                    <div class="achievement locked">
                        <i class="fas fa-crown"></i>
                        <span>Experto</span>
                    </div>
                    <!-- 更多成就 -->
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.dashboard-header {
    margin-bottom: 30px;
}

.dashboard-header h1 {
    color: var(--primary);
    margin-bottom: 10px;
}

.user-greeting {
    font-size: 1.2em;
    color: #666;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 30px;
}

.dashboard-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.user-profile-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    text-align: center;
}

.profile-avatar {
    font-size: 80px;
    color: var(--accent);
    margin-bottom: 15px;
}

.profile-info h3 {
    margin-bottom: 10px;
    color: var(--primary);
}

.profile-info p {
    color: #666;
    margin: 8px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.profile-stats {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin: 20px 0;
    padding: 20px 0;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 2em;
    font-weight: bold;
    color: var(--accent);
}

.stat-label {
    color: #666;
    font-size: 0.9em;
}

.profile-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.btn-profile {
    padding: 12px;
    border-radius: 6px;
    text-decoration: none;
    text-align: center;
    transition: all 0.3s;
}

.btn-profile:first-child {
    background: var(--accent);
    color: white;
}

.btn-profile:first-child:hover {
    background: #00959c;
}

.btn-danger {
    background: var(--danger);
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

.dashboard-nav {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.dashboard-nav h3 {
    color: var(--primary);
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.dashboard-nav ul {
    list-style: none;
}

.nav-item {
    margin: 8px 0;
}

.nav-item a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    border-radius: 6px;
    color: #333;
    text-decoration: none;
    transition: all 0.3s;
}

.nav-item a:hover {
    background: #f8f9fa;
    color: var(--accent);
}

.nav-item.active a {
    background: var(--accent);
    color: white;
}

.dashboard-content {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
}

.card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.card-header {
    background: var(--primary);
    color: white;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.card-header i {
    font-size: 1.5em;
}

.card-body {
    padding: 20px;
}

.articles-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.article-item {
    padding: 15px;
    border: 1px solid #eee;
    border-radius: 8px;
    transition: all 0.3s;
}

.article-item:hover {
    border-color: var(--accent);
    box-shadow: 0 3px 10px rgba(0,173,181,0.1);
}

.article-item h4 {
    margin-bottom: 10px;
}

.article-item h4 a {
    color: var(--primary);
    text-decoration: none;
}

.article-item h4 a:hover {
    color: var(--accent);
}

.article-meta {
    display: flex;
    gap: 15px;
    font-size: 0.9em;
    color: #666;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
}

.empty-state i {
    font-size: 3em;
    color: #ddd;
    margin-bottom: 20px;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px;
    border-radius: 8px;
    transition: background 0.3s;
}

.activity-item:hover {
    background: #f8f9fa;
}

.activity-icon {
    font-size: 1.5em;
    color: var(--accent);
    width: 40px;
    text-align: center;
}

.activity-content p {
    margin: 0;
    font-weight: 500;
}

.activity-time {
    font-size: 0.9em;
    color: #666;
}

.quick-actions .card-body {
    padding: 25px;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    text-decoration: none;
    color: var(--primary);
    transition: all 0.3s;
    gap: 10px;
}

.action-btn:hover {
    background: var(--accent);
    color: white;
    transform: translateY(-3px);
}

.action-btn i {
    font-size: 2em;
}

.dashboard-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.achievements {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-top: 20px;
}

.achievement {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    width: 100px;
    opacity: 1;
    transition: all 0.3s;
}

.achievement.locked {
    opacity: 0.5;
    filter: grayscale(1);
}

.achievement i {
    font-size: 2.5em;
    margin-bottom: 10px;
    color: var(--accent);
}

@media (max-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-cards {
        grid-template-columns: 1fr;
    }
    
    .actions-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 768px) {
    .actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* 辅助函数样式 */
.password-strength {
    margin-top: 10px;
}

.strength-bar {
    height: 5px;
    background: #eee;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 5px;
}

.strength-fill {
    height: 100%;
    transition: width 0.3s, background 0.3s;
}

.strength-label {
    font-size: 12px;
    color: #666;
}
</style>

<?php 
// 辅助函数：计算时间差
function time_ago($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'hace un momento';
    if ($diff < 3600) return 'hace ' . floor($diff/60) . ' minutos';
    if ($diff < 86400) return 'hace ' . floor($diff/3600) . ' horas';
    if ($diff < 604800) return 'hace ' . floor($diff/86400) . ' días';
    return 'hace ' . floor($diff/604800) . ' semanas';
}
?>

<?php include 'includes/footer.php'; ?>