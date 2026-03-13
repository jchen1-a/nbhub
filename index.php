<?php
// index.php - 100% 完整版 (洗净浑浊感，采用纯净的黑墨背景 + 曼珠沙华红点缀)
require_once 'config.php';

$stats = ['users' => 0, 'guides' => 0, 'posts' => 0];
$recent_guides = [];
$active_posts = [];

try {
    $pdo = db_connect();
    
    // 获取全站统计数据
    $stats['users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['guides'] = $pdo->query("SELECT COUNT(*) FROM articles WHERE is_published = 1")->fetchColumn();
    $stats['posts'] = $pdo->query("SELECT COUNT(*) FROM forum_posts")->fetchColumn();
    
    // 获取最新发布的4篇攻略
    $recent_guides = $pdo->query("
        SELECT a.id, a.title, a.views, a.difficulty, a.created_at, u.username 
        FROM articles a 
        LEFT JOIN users u ON a.user_id = u.id 
        WHERE a.is_published = 1
        ORDER BY a.created_at DESC 
        LIMIT 4
    ")->fetchAll();
    
    // 获取最活跃的4个论坛帖子
    $active_posts = $pdo->query("
        SELECT p.id, p.title, p.views, p.category, u.username,
               (SELECT COUNT(*) FROM forum_replies WHERE post_id = p.id) as reply_count
        FROM forum_posts p 
        LEFT JOIN users u ON p.user_id = u.id 
        ORDER BY p.last_reply_at DESC 
        LIMIT 4
    ")->fetchAll();

} catch (Exception $e) {
    $error_msg = "Error al conectar con la base de datos.";
}
?>
<?php include 'includes/header.php'; ?>

<div class="hero-section">
    <div class="hero-content container">
        <h1>Portal de Naraka: Bladepoint</h1>
        <p>Tu centro de información definitivo: wiki, guías detalladas y una comunidad activa.</p>
        <div class="hero-buttons">
            <a href="wiki.php" class="btn-hero btn-hero-primary"><i class="fas fa-book-open"></i> Explorar Wiki</a>
            <a href="guides.php" class="btn-hero btn-hero-primary"><i class="fas fa-graduation-cap"></i> Ver Estado</a>
        </div>
    </div>
</div>

<div class="container" style="max-width: 1200px; margin: -50px auto 50px auto; position: relative; z-index: 10;">
    
    <div class="stats-banner" style="margin-bottom: 40px;">
        <div class="stat-block">
            <i class="fas fa-user-friends"></i>
            <span class="stat-num"><?php echo $stats['users']; ?></span>
            <span class="stat-text">Jugadores Registrados</span>
        </div>
        <div class="stat-block">
            <i class="fas fa-scroll"></i>
            <span class="stat-num"><?php echo $stats['guides']; ?></span>
            <span class="stat-text">Guías Publicadas</span>
        </div>
        <div class="stat-block">
            <i class="fas fa-comments"></i>
            <span class="stat-num"><?php echo $stats['posts']; ?></span>
            <span class="stat-text">Temas Creados</span>
        </div>
    </div>

    <div class="features-grid">
        <div class="feature-card wiki-card">
            <div class="feature-icon"><i class="fas fa-book"></i></div>
            <h3>Wiki Actualizada</h3>
            <p>Información completa sobre personajes, armas, habilidades y mecánicas.</p>
            <a href="wiki.php" class="feature-link">Explorar <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="feature-card forum-card">
            <div class="feature-icon"><i class="fas fa-users"></i></div>
            <h3>Foro Activo</h3>
            <p>Estrategias, dudas y conecta con jugadores de todo el mundo.</p>
            <a href="forum.php" class="feature-link">Participar <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="feature-card guides-card">
            <div class="feature-icon"><i class="fas fa-graduation-cap"></i></div>
            <h3>Guías Expertas</h3>
            <p>Builds, combos y secretos para mejorar tu juego.</p>
            <a href="guides.php" class="feature-link">Aprender <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>

    <div class="content-showcase" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-top: 40px;">
        
        <div class="showcase-card card">
            <div class="showcase-header card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: none;">
                <h2 style="margin: 0; font-size: 1.4em; color: var(--primary); display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-scroll" style="color: var(--accent);"></i> Últimas Guías
                </h2>
                <a href="guides.php" class="btn-sm btn-outline">Ver todas</a>
            </div>
            <div class="showcase-list card-body" style="padding: 0;">
                <?php if (empty($recent_guides)): ?>
                    <p style="text-align:center; color:#999; padding:25px;">No hay guías publicadas.</p>
                <?php else: ?>
                    <?php foreach($recent_guides as $guide): ?>
                        <a href="article.php?id=<?php echo $guide['id']; ?>" class="list-item" style="border-bottom: 1px solid #f0f0f0;">
                            <div class="item-main">
                                <h4><?php echo htmlspecialchars($guide['title']); ?></h4>
                                <span class="item-meta">Por <?php echo htmlspecialchars($guide['username']); ?> | <i class="fas fa-eye"></i> <?php echo $guide['views']; ?></span>
                            </div>
                            <span class="diff-badge <?php echo $guide['difficulty']; ?>"><?php echo ucfirst($guide['difficulty']); ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="showcase-card card">
            <div class="showcase-header card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: none;">
                <h2 style="margin: 0; font-size: 1.4em; color: var(--primary); display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-comments" style="color: var(--accent);"></i> Temas Activos
                </h2>
                <a href="forum.php" class="btn-sm btn-outline">Ir al Foro</a>
            </div>
            <div class="showcase-list card-body" style="padding: 0;">
                <?php if (empty($active_posts)): ?>
                    <p style="text-align:center; color:#999; padding:25px;">No hay temas en el foro.</p>
                <?php else: ?>
                    <?php foreach($active_posts as $post): ?>
                        <a href="view-post.php?id=<?php echo $post['id']; ?>" class="list-item" style="border-bottom: 1px solid #f0f0f0;">
                            <div class="item-main">
                                <h4><?php echo htmlspecialchars($post['title']); ?></h4>
                                <span class="item-meta">Por <?php echo htmlspecialchars($post['username']); ?> | <i class="fas fa-reply"></i> <?php echo $post['reply_count']; ?> resp.</span>
                            </div>
                            <span class="category-badge"><?php echo ucfirst($post['category']); ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<style>
/* ================= 首页专属水墨武林精美 CSS ================= */
/* 纯黑墨色晕染背景，彻底去除浑浊的红色混合 */
.hero-section { background-color: var(--primary); background-image: radial-gradient(circle at 50% 0%, #2a2a2a 0%, #111111 80%); color: white; padding: 100px 20px 140px 20px; text-align: center; border-bottom: 4px solid var(--accent); position: relative; }
.hero-content h1 { font-size: 3.8em; margin: 0 0 15px 0; color: white; text-shadow: 0 4px 15px rgba(0,0,0,0.8); font-weight: 800; letter-spacing: 1px; }
.hero-content p { font-size: 1.25em; line-height: 1.6; color: #ccc; margin-bottom: 40px; }
.hero-buttons { display: flex; gap: 20px; justify-content: center; }
.btn-hero { padding: 15px 35px; border-radius: 35px; font-size: 1.1em; font-weight: bold; text-decoration: none; transition: 0.3s; display: inline-flex; align-items: center; gap: 10px; border: none; }
.btn-hero-primary { background: var(--accent); color: white; box-shadow: 0 4px 15px rgba(204,0,0,0.4); }
.btn-hero-primary:hover { background: #e60000; color: white; transform: translateY(-3px); box-shadow: 0 6px 20px rgba(204,0,0,0.6); }

/* 统计条 (深色墨砚背景) */
.stats-banner { background: #1a1a1a; border-radius: 12px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); text-align: center; color: white; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid #333; padding: 40px; }
.stat-block i { font-size: 2.5em; color: var(--accent); margin-bottom: 15px; display: block; }
.stat-num { font-size: 2.8em; font-weight: bold; display: block; line-height: 1; margin-bottom: 8px; color: white; }
.stat-text { color: #888; text-transform: uppercase; font-size: 0.9em; letter-spacing: 1px; font-weight: bold; }

/* 三大功能卡片 */
.features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
.feature-card { background: white; padding: 40px 30px; border-radius: 12px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: 0.3s; border-top: 4px solid #111; }
.feature-card:hover { transform: translateY(-8px); box-shadow: 0 15px 40px rgba(0,0,0,0.1); border-top-color: var(--accent); }
.feature-icon { font-size: 3em; color: #111; margin-bottom: 25px; transition: 0.3s; }
.feature-card:hover .feature-icon { color: var(--accent); transform: scale(1.1); }
.feature-card h3 { font-size: 1.5em; color: var(--primary); margin: 0 0 15px 0; font-weight: bold; }
.feature-card p { color: #666; margin-bottom: 30px; line-height: 1.6; }
.feature-link { color: #111; font-weight: bold; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
.feature-card:hover .feature-link { color: var(--accent); gap: 12px; }

/* 最新攻略与热门讨论列表美化 */
.list-item { display: flex; justify-content: space-between; align-items: center; padding: 18px 25px; text-decoration: none; transition: 0.2s; border-radius: 8px; }
.list-item:hover { background: #fafafa; transform: translateX(5px); border-left: 3px solid var(--accent); }
.list-item h4 { margin: 0 0 5px 0; color: var(--text); font-size: 1.15em; font-weight: bold; transition: 0.2s; }
.list-item:hover h4 { color: var(--accent); }
.item-meta { font-size: 0.85em; color: #888; display: block; }
.list-item .diff-badge, .list-item .category-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75em; font-weight: bold; text-transform: uppercase; border: 1px solid #eee; }
.diff-badge.beginner { background: #f1f8e9; color: #33691e; border-color: #c5e1a5; }
.diff-badge.intermediate { background: #fff8e1; color: #ff8f00; border-color: #ffe082; }
.diff-badge.advanced { background: #ffebee; color: var(--accent); border-color: #ffcdd2; }
.category-badge { background: #f5f5f5; color: #444; }

@media (max-width: 768px) {
    .hero-content h1 { font-size: 2.5em; }
    .hero-buttons { flex-direction: column; gap: 15px; }
    .btn-hero { width: 100%; }
    .container { margin-top: 20px; }
}
</style>

<?php include 'includes/footer.php'; ?>