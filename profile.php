<?php
// profile.php - 完整版 (支持头像与个人简介)
require_once 'config.php';

// 获取要查看的用户ID，默认查看自己
$user_id = isset($_GET['user']) ? intval($_GET['user']) : (is_logged_in() ? $_SESSION['user_id'] : null);

if (!$user_id) {
    header('Location: login.php');
    exit;
}

try {
    $pdo = db_connect();
    
    // 获取用户信息及统计 (包含 avatar, bio, gender)
    $stmt = $pdo->prepare("
        SELECT id, username, email, country, avatar, bio, gender, created_at,
               (SELECT COUNT(*) FROM articles WHERE user_id = users.id AND is_published = 1) as guide_count,
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
    $guides_stmt = $pdo->prepare("SELECT id, title, views, created_at FROM articles WHERE user_id = ? AND is_published = 1 ORDER BY created_at DESC LIMIT 5");
    $guides_stmt->execute([$user_id]);
    $recent_guides = $guides_stmt->fetchAll();

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// 检查是否是当前登录用户本人的主页
$is_own_profile = is_logged_in() && $_SESSION['user_id'] == $profile_user['id'];
?>
<?php include 'includes/header.php'; ?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-user"></i> Perfil de <?php echo htmlspecialchars($profile_user['username']); ?></h1>
    </div>
    
    <div class="dashboard-grid">
        <aside class="dashboard-sidebar">
            <div class="user-profile-card">
                
                <div class="profile-avatar" style="text-align: center; margin-bottom: 20px;">
                    <?php if (!empty($profile_user['avatar'])): ?>
                        <img src="<?php echo htmlspecialchars($profile_user['avatar']); ?>" alt="Avatar" style="width: 130px; height: 130px; border-radius: 50%; object-fit: cover; border: 4px solid #00adb5; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                    <?php else: ?>
                        <i class="fas fa-user-circle" style="font-size: 130px; color: #ccc;"></i>
                    <?php endif; ?>
                </div>
                
                <div class="profile-info" style="text-align: center;">
                    <h2 style="margin-bottom: 10px; color: var(--primary);"><?php echo htmlspecialchars($profile_user['username']); ?></h2>
                    
                    <?php if(!empty($profile_user['country'])): ?>
                        <p style="color: #666; margin-bottom: 5px;"><i class="fas fa-globe"></i> País: <?php echo htmlspecialchars($profile_user['country']); ?></p>
                    <?php endif; ?>
                    
                    <?php if(!empty($profile_user['gender']) && $profile_user['gender'] != 'unspecified'): ?>
                        <p style="color: #666; margin-bottom: 5px;"><i class="fas fa-venus-mars"></i> Género: <?php 
                            $gender_text = ['male'=>'Masculino', 'female'=>'Femenino', 'other'=>'Otro'];
                            echo $gender_text[$profile_user['gender']] ?? ucfirst($profile_user['gender']); 
                        ?></p>
                    <?php endif; ?>
                    
                    <?php if(!empty($profile_user['bio'])): ?>
                        <div style="margin-top: 15px; font-style: italic; color: #555; font-size: 0.95em; line-height: 1.5; background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 3px solid #00adb5; text-align: left;">
                            "<?php echo nl2br(htmlspecialchars($profile_user['bio'])); ?>"
                        </div>
                    <?php endif; ?>
                    
                    <p style="margin-top: 15px; font-size: 0.85em; color: #999;"><i class="fas fa-calendar-alt"></i> Miembro desde: <?php echo date('d/m/Y', strtotime($profile_user['created_at'])); ?></p>
                    
                    <?php if($is_own_profile): ?>
                        <a href="edit-profile.php" class="btn-primary" style="margin-top: 20px; display: inline-block; width: 100%; padding: 10px; border-radius: 8px;">
                            <i class="fas fa-edit"></i> Editar mi perfil
                        </a>
                    <?php endif; ?>
                </div>

                <div class="profile-stats" style="margin-top: 25px;">
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
                <div class="card-header">
                    <h3><i class="fas fa-scroll"></i> Guías Publicadas por <?php echo htmlspecialchars($profile_user['username']); ?></h3>
                </div>
                <div class="card-body">
                    <?php if ($recent_guides): ?>
                        <div class="article-list">
                        <?php foreach ($recent_guides as $g): ?>
                            <div class="article-item" style="display:flex; justify-content:space-between; align-items:center; padding:15px; border-bottom:1px solid #eee; transition: background 0.2s;">
                                <div class="art-info">
                                    <h4 style="margin:0 0 5px 0;">
                                        <a href="article.php?id=<?php echo $g['id']; ?>" style="color:#333; text-decoration:none; font-weight:bold; font-size:1.1em;">
                                            <?php echo htmlspecialchars($g['title']); ?>
                                        </a>
                                    </h4>
                                    <small style="color:#888;"><i class="far fa-clock"></i> <?php echo date('d/m/Y', strtotime($g['created_at'])); ?></small>
                                </div>
                                <div class="article-meta" style="background: #f8f9fa; padding: 5px 12px; border-radius: 20px;">
                                    <span style="font-weight:bold; color:#00adb5;"><i class="fas fa-eye"></i> <?php echo $g['views']; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:50px 20px; color:#666;">
                            <i class="fas fa-folder-open" style="font-size:3.5em; margin-bottom:15px; display:block; color:#ddd;"></i>
                            <p style="font-size: 1.1em;">Este usuario aún no ha publicado guías.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
/* Dashboard & Profile 样式 */
.dashboard-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
.dashboard-header { margin-bottom: 30px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; }
.dashboard-header h1 { color: var(--primary); }
.dashboard-grid { display: grid; grid-template-columns: 320px 1fr; gap: 30px; }
.user-profile-card, .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.06); }
.profile-stats { display: flex; gap: 15px; border-top: 1px solid #eee; padding-top: 20px; }
.stat-item { flex: 1; text-align: center; background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #eee; }
.stat-number { display: block; font-size: 1.6em; font-weight: bold; color: var(--accent); }
.stat-label { font-size: 0.9em; color: #666; text-transform: uppercase; letter-spacing: 1px;}
.card-header { border-bottom: 2px solid #f8f9fa; padding-bottom: 15px; margin-bottom: 20px; color: var(--primary); }
.article-item:hover { background: #f8f9fa; border-radius: 8px; }

@media (max-width: 768px) {
    .dashboard-grid { grid-template-columns: 1fr; }
}
</style>

<?php include 'includes/footer.php'; ?>