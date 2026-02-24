<?php
// profile.php - 公开用户资料页面
require_once 'config.php';

// 获取用户ID：优先从GET获取，如果没有则查看当前登录用户，如果都没有则重定向
$user_id = isset($_GET['user']) ? sanitize($_GET['user']) : (is_logged_in() ? current_user()['id'] : null);

if (!$user_id) {
    header('Location: login.php');
    exit;
}

try {
    $pdo = db_connect();
    
    // 1. 获取用户信息
    $user_stmt = $pdo->prepare("
        SELECT id, username, email, country, created_at,
               (SELECT COUNT(*) FROM articles WHERE user_id = users.id) as guide_count,
               (SELECT COUNT(*) FROM forum_posts WHERE user_id = users.id) as post_count
        FROM users 
        WHERE id = ?
    ");
    $user_stmt->execute([$user_id]);
    $profile_user = $user_stmt->fetch();
    
    if (!$profile_user) {
        $error = "Usuario no encontrado.";
    } else {
        // 2. 获取该用户的最近指南
        $guides_stmt = $pdo->prepare("
            SELECT id, title, category, views, created_at, difficulty
            FROM guides 
            WHERE user_id = ? AND is_published = TRUE
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $guides_stmt->execute([$user_id]);
        $recent_guides = $guides_stmt->fetchAll();
        
        // 3. 获取该用户的最近论坛帖子
        $posts_stmt = $pdo->prepare("
            SELECT id, title, category, reply_count, created_at
            FROM forum_posts
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $posts_stmt->execute([$user_id]);
        $recent_posts = $posts_stmt->fetchAll();
    }
    
} catch (Exception $e) {
    $error = "Error de sistema: " . $e->getMessage();
}

// 辅助函数：获取国旗 (复用 forum.php 的逻辑)
function get_flag_emoji($country_code) {
    $flags = [
        'ES' => '🇪🇸', 'MX' => '🇲🇽', 'AR' => '🇦🇷', 'US' => '🇺🇸', 
        'BR' => '🇧🇷', 'FR' => '🇫🇷', 'DE' => '🇩🇪', 'UK' => '🇬🇧', 
        'CN' => '🇨🇳', 'KR' => '🇰🇷', 'JP' => '🇯🇵'
    ];
    return $flags[$country_code] ?? '🌐';
}
?>
<?php include 'includes/header.php'; ?>

<div class="profile-container">
    <?php if (isset($error)): ?>
        <div class="error-container">
            <i class="fas fa-user-slash"></i>
            <h1><?php echo $error; ?></h1>
            <a href="index.php" class="btn-primary">Volver al Inicio</a>
        </div>
    <?php else: ?>

    <div class="profile-header">
        <div class="profile-cover"></div>
        <div class="profile-info-main">
            <div class="profile-avatar-large">
                <i class="fas fa-user"></i>
            </div>
            <div class="profile-details">
                <div class="name-row">
                    <h1><?php echo htmlspecialchars($profile_user['username']); ?></h1>
                    <?php if ($profile_user['country']): ?>
                        <span class="flag" title="País"><?php echo get_flag_emoji($profile_user['country']); ?></span>
                    <?php endif; ?>
                    
                    <?php if (is_logged_in() && current_user()['id'] == $user_id): ?>
                        <a href="edit-profile.php" class="btn-edit-small">
                            <i class="fas fa-pen"></i> Editar
                        </a>
                    <?php endif; ?>
                </div>
                <div class="meta-row">
                    <span><i class="fas fa-calendar-alt"></i> Miembro desde: <?php echo date('d/m/Y', strtotime($profile_user['created_at'])); ?></span>
                    <span><i class="fas fa-id-badge"></i> ID: #<?php echo $profile_user['id']; ?></span>
                </div>
            </div>
            
            <div class="profile-stats-bar">
                <div class="p-stat">
                    <span class="val"><?php echo $profile_user['guide_count']; ?></span>
                    <span class="lbl">Guías</span>
                </div>
                <div class="p-stat">
                    <span class="val"><?php echo $profile_user['post_count']; ?></span>
                    <span class="lbl">Posts</span>
                </div>
            </div>
        </div>
    </div>

    <div class="profile-content-grid">
        <div class="content-col">
            <div class="section-card">
                <div class="card-head">
                    <h3><i class="fas fa-scroll"></i> Guías Publicadas</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_guides)): ?>
                        <p class="empty-text">Este usuario no ha publicado guías aún.</p>
                    <?php else: ?>
                        <div class="list-items">
                        <?php foreach ($recent_guides as $guide): ?>
                            <a href="view-guide.php?id=<?php echo $guide['id']; ?>" class="list-item">
                                <div class="item-main">
                                    <h4><?php echo htmlspecialchars($guide['title']); ?></h4>
                                    <div class="item-meta">
                                        <span class="badge <?php echo $guide['difficulty']; ?>"><?php echo ucfirst($guide['difficulty']); ?></span>
                                        <span class="date"><?php echo date('d/m/Y', strtotime($guide['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="item-stat">
                                    <i class="fas fa-eye"></i> <?php echo $guide['views']; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="content-col">
            <div class="section-card">
                <div class="card-head">
                    <h3><i class="fas fa-comments"></i> Actividad en el Foro</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_posts)): ?>
                        <p class="empty-text">Sin actividad reciente en el foro.</p>
                    <?php else: ?>
                        <div class="list-items">
                        <?php foreach ($recent_posts as $post): ?>
                            <a href="view-post.php?id=<?php echo $post['id']; ?>" class="list-item">
                                <div class="item-main">
                                    <h4><?php echo htmlspecialchars($post['title']); ?></h4>
                                    <div class="item-meta">
                                        <span class="tag"><?php echo htmlspecialchars($post['category']); ?></span>
                                        <span class="date"><?php echo date('d/m/Y', strtotime($post['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="item-stat">
                                    <i class="fas fa-reply"></i> <?php echo $post['reply_count']; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<style>
.profile-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.error-container {
    text-align: center;
    padding: 100px 20px;
    background: white;
    border-radius: 15px;
}

.error-container i {
    font-size: 5em;
    color: #ddd;
    margin-bottom: 20px;
}

/* Header Styles */
.profile-header {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 30px;
    position: relative;
}

.profile-cover {
    height: 150px;
    background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 100%);
}

.profile-info-main {
    padding: 0 30px 30px 30px;
    display: flex;
    align-items: flex-end;
    gap: 30px;
    margin-top: -50px;
}

.profile-avatar-large {
    width: 120px;
    height: 120px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 60px;
    color: var(--accent);
    border: 5px solid white;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.profile-details {
    flex: 1;
    margin-bottom: 10px;
}

.name-row {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 5px;
}

.name-row h1 {
    margin: 0;
    font-size: 2em;
    color: #333;
}

.btn-edit-small {
    background: #eee;
    color: #333;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8em;
    text-decoration: none;
    transition: background 0.3s;
}

.btn-edit-small:hover {
    background: #ddd;
}

.meta-row {
    color: #666;
    font-size: 0.9em;
    display: flex;
    gap: 20px;
}

.profile-stats-bar {
    display: flex;
    gap: 30px;
    margin-bottom: 10px;
}

.p-stat {
    text-align: center;
}

.p-stat .val {
    display: block;
    font-size: 1.5em;
    font-weight: bold;
    color: var(--primary);
}

.p-stat .lbl {
    font-size: 0.8em;
    color: #888;
    text-transform: uppercase;
}

/* Content Grid */
.profile-content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.section-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    overflow: hidden;
    height: 100%;
}

.card-head {
    background: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
}

.card-head h3 {
    margin: 0;
    font-size: 1.1em;
    color: var(--primary);
}

.card-body {
    padding: 20px;
}

.list-items {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    border: 1px solid #eee;
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s;
}

.list-item:hover {
    border-color: var(--accent);
    background: #fcfcfc;
    transform: translateX(5px);
}

.item-main h4 {
    margin: 0 0 5px 0;
    font-size: 1em;
    color: #333;
}

.item-meta {
    font-size: 0.8em;
    color: #888;
    display: flex;
    gap: 10px;
    align-items: center;
}

.badge {
    padding: 2px 6px;
    border-radius: 4px;
    color: white;
    font-size: 0.9em;
}
.badge.beginner { background: var(--success); }
.badge.intermediate { background: var(--warning); color: #333; }
.badge.advanced { background: var(--danger); }

.tag {
    background: #e9ecef;
    padding: 2px 8px;
    border-radius: 10px;
    color: #666;
}

.item-stat {
    color: #aaa;
    font-size: 0.9em;
    display: flex;
    align-items: center;
    gap: 5px;
}

.empty-text {
    color: #999;
    font-style: italic;
    text-align: center;
    padding: 20px;
}

@media (max-width: 768px) {
    .profile-info-main {
        flex-direction: column;
        align-items: center;
        text-align: center;
        margin-top: -60px;
    }
    
    .name-row {
        justify-content: center;
    }
    
    .meta-row {
        justify-content: center;
    }
    
    .profile-content-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/footer.php'; ?>