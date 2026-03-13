<?php
// index.php - 100% 完整版 (引入真实封面图背景与官方水墨图片Logo)
require_once 'config.php';

$stats = ['users' => 0, 'guides' => 0, 'posts' => 0];
$recent_guides = [];
$active_posts = [];

try {
    $pdo = db_connect();
    
    $stats['users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['guides'] = $pdo->query("SELECT COUNT(*) FROM articles WHERE is_published = 1")->fetchColumn();
    $stats['posts'] = $pdo->query("SELECT COUNT(*) FROM forum_posts")->fetchColumn();
    
    $recent_guides = $pdo->query("
        SELECT a.id, a.title, a.views, a.difficulty, a.created_at, u.username 
        FROM articles a 
        LEFT JOIN users u ON a.user_id = u.id 
        WHERE a.is_published = 1
        ORDER BY a.created_at DESC 
        LIMIT 4
    ")->fetchAll();
    
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
        
        <img src="assets/logo.png" alt="Naraka Hub Logo Oficial" class="hero-image-logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
        <h1 class="brush-font fallback-title" style="display:none; font-size: 5em; margin: 0 0 10px 0; color: #fff; text-shadow: 3px 3px 0px var(--accent);">NARAKA HUB</h1>
        
        <p style="font-family: 'Cinzel', serif; font-size: 1.4em; color: #fff; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; text-shadow: 1px 1px 5px rgba(0,0,0,0.8); margin-top: 20px;">Tu centro de información definitivo: Sangre, Acero y Honor.</p>
        
        <div class="hero-buttons" style="margin-top: 40px;">
            <a href="wiki.php" class="btn-hero btn-hero-primary"><i class="fas fa-book-open"></i> Explorar Wiki</a>
            <a href="guides.php" class="btn-hero btn-hero-secondary"><i class="fas fa-graduation-cap"></i> Ver Guías</a>
        </div>
    </div>
</div>

<div class="container" style="max-width: 1200px; margin: -50px auto 50px auto; position: relative; z-index: 10;">
    
    <div class="stats-banner" style="margin-bottom: 40px;">
        <div class="stat-block">
            <i class="fas fa-user-friends"></i>
            <span class="stat-num"><?php echo $stats['users']; ?></span>
            <span class="stat-text">Jugadores</span>
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
            <div class="showcase-header card-header" style="display: flex; justify-content: space-between; align-items: center; background: white;">
                <h2 style="margin: 0; font-size: 1.4em; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-scroll" style="color: var(--accent);"></i> Últimas Guías
                </h2>
                <a href="guides.php" class="btn-sm btn-outline" style="border-radius:4px; padding: 5px 10px;">Ver todas</a>
            </div>
            <div class="showcase-list card-body" style="padding: 0;">
                <?php if (empty($recent_guides)): ?>
                    <p style="text-align:center; color:#999; padding:25px;">No hay guías publicadas.</p>
                <?php else: ?>
                    <?php foreach($recent_guides as $guide): ?>
                        <a href="article.php?id=<?php echo $guide['id']; ?>" class="list-item" style="border-bottom: 1px solid #f0f0f0;">
                            <div class="item-main">
                                <h4 style="font-family: 'Segoe UI', sans-serif;"><?php echo htmlspecialchars($guide['title']); ?></h4>
                                <span class="item-meta">Por <?php echo htmlspecialchars($guide['username']); ?> | <i class="fas fa-eye"></i> <?php echo $guide['views']; ?></span>
                            </div>
                            <span class="diff-badge <?php echo $guide['difficulty']; ?>"><?php echo ucfirst($guide['difficulty']); ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="showcase-card card">
            <div class="showcase-header card-header" style="display: flex; justify-content: space-between; align-items: center; background: white;">
                <h2 style="margin: 0; font-size: 1.4em; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-comments" style="color: var(--accent);"></i> Temas Activos
                </h2>
                <a href="forum.php" class="btn-sm btn-outline" style="border-radius:4px; padding: 5px 10px;">Ir al Foro</a>
            </div>
            <div class="showcase-list card-body" style="padding: 0;">
                <?php if (empty($active_posts)): ?>
                    <p style="text-align:center; color:#999; padding:25px;">No hay temas en el foro.</p>
                <?php else: ?>
                    <?php foreach($active_posts as $post): ?>
                        <a href="view-post.php?id=<?php echo $post['id']; ?>" class="list-item" style="border-bottom: 1px solid #f0f0f0;">
                            <div class="item-main">
                                <h4 style="font-family: 'Segoe UI', sans-serif;"><?php echo htmlspecialchars($post['title']); ?></h4>
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
/* 引入本地封面图，并加上半透明水墨到猩红的滤镜，确保文字永远清晰 */
.hero-section { 
    background: linear-gradient(135deg, rgba(10, 10, 12, 0.6) 0%, rgba(201, 20, 20, 0.5) 100%), url('assets/cover.jpg') no-repeat center center; 
    background-size: cover;
    padding: 100px 20px 140px 20px; 
    text-align: center; 
    border-bottom: 5px solid var(--primary); 
    position: relative; 
}

/* 官方图片 Logo 的样式 */
.hero-image-logo {
    max-width: 100%;
    height: auto;
    max-height: 180px; /* 限制图片最大高度，防止撑爆屏幕 */
    margin: 0 auto;
    display: block;
    filter: drop-shadow(0px 5px 15px rgba(0,0,0,0.8)); /* 加上酷炫的阴影让它更立体 */
    transition: transform 0.3s;
}
.hero-image-logo:hover {
    transform: scale(1.05); /* 鼠标放上去微微放大 */
}

.hero-buttons { display: flex; gap: 20px; justify-content: center; }
.btn-hero { padding: 15px 35px; font-size: 1.1em; font-weight: bold; text-decoration: none; transition: 0.3s; display: inline-flex; align-items: center; gap: 10px; border: none; text-transform: uppercase; font-family: 'Cinzel', serif; letter-spacing: 1px;}
.btn-hero-primary { background: var(--primary); color: white; box-shadow: 0 6px 15px rgba(0,0,0,0.5); border-radius: 4px; border: 1px solid #333; }
.btn-hero-primary:hover { background: #000; color: var(--accent); transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.7); border-color: var(--accent); }
.btn-hero-secondary { background: rgba(0,0,0,0.6); color: white; border: 2px solid var(--accent); border-radius: 4px; backdrop-filter: blur(5px); }
.btn-hero-secondary:hover { background: var(--accent); color: white; transform: translateY(-3px); }

/* 统计条 (深色墨砚背景) */
.stats-banner { background: var(--primary); border-radius: 8px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); text-align: center; color: white; box-shadow: 0 10px 30px rgba(0,0,0,0.2); border: 2px solid #222; padding: 40px; }
.stat-block i { font-size: 2.5em; color: var(--accent); margin-bottom: 15px; display: block; }
.stat-num { font-size: 3em; font-family: 'Cinzel', serif; font-weight: 800; display: block; line-height: 1; margin-bottom: 8px; color: white; }
.stat-text { color: #888; text-transform: uppercase; font-size: 0.9em; letter-spacing: 1px; font-weight: bold; }

/* 三大功能卡片 */
.features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
.feature-card { background: white; padding: 40px 30px; border-radius: 8px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.08); transition: 0.3s; border-top: 4px solid var(--primary); border-bottom: 1px solid #ddd; border-left: 1px solid #ddd; border-right: 1px solid #ddd; }
.feature-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.12); border-top-color: var(--accent); }
.feature-icon { font-size: 3.5em; color: var(--primary); margin-bottom: 25px; transition: 0.3s; }
.feature-card:hover .feature-icon { color: var(--accent); transform: scale(1.1); }
.feature-card h3 { font-size: 1.6em; margin: 0 0 15px 0; }
.feature-card p { color: #555; margin-bottom: 30px; line-height: 1.6; font-family: 'Segoe UI', sans-serif; }
.feature-link { color: var(--primary); font-weight: bold; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; text-transform: uppercase; letter-spacing: 1px; font-size: 0.9em; }
.feature-card:hover .feature-link { color: var(--accent); gap: 12px; }

/* 列表项悬停效果 */
.list-item { display: flex; justify-content: space-between; align-items: center; padding: 18px 25px; text-decoration: none; transition: 0.2s; background: white; }
.list-item:hover { background: #fafafa; border-left: 4px solid var(--accent); padding-left: 21px; }
.list-item h4 { margin: 0 0 5px 0; color: var(--text); font-size: 1.1em; font-weight: bold; transition: 0.2s; }
.list-item:hover h4 { color: var(--accent); }
.item-meta { font-size: 0.85em; color: #888; display: block; font-family: 'Segoe UI', sans-serif;}
.list-item .diff-badge, .list-item .category-badge { padding: 4px 10px; border-radius: 4px; font-size: 0.75em; font-weight: bold; text-transform: uppercase; border: 1px solid #ddd; font-family: 'Segoe UI', sans-serif;}
.diff-badge.beginner { background: #f1f8e9; color: #33691e; border-color: #c5e1a5; }
.diff-badge.intermediate { background: #fff8e1; color: #f57f17; border-color: #ffe082; }
.diff-badge.advanced { background: #ffebee; color: var(--accent); border-color: #ffcdd2; }
.category-badge { background: #f5f5f5; color: #444; }

@media (max-width: 768px) {
    .hero-image-logo { max-height: 100px; }
    .hero-buttons { flex-direction: column; gap: 15px; }
    .btn-hero { width: 100%; }
    .container { margin-top: 20px; }
}
</style>

<?php include 'includes/footer.php'; ?>