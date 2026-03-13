<?php
// index.php - 100% 完整版 (包含完美首页排版与动画CSS)
require_once 'config.php';

$stats = ['users' => 0, 'guides' => 0, 'posts' => 0];
$recent_guides = [];
$active_posts = [];

try {
    $pdo = db_connect();
    
    // 获取全站统计数据
    $stats['users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['guides'] = $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
    $stats['posts'] = $pdo->query("SELECT COUNT(*) FROM forum_posts")->fetchColumn();
    
    // 获取最新发布的4篇攻略
    $recent_guides = $pdo->query("
        SELECT a.id, a.title, a.views, a.difficulty, a.created_at, u.username 
        FROM articles a 
        LEFT JOIN users u ON a.user_id = u.id 
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
    <div class="hero-content">
        <h1>Portal de Naraka: Bladepoint</h1>
        <p>Todo lo que necesitas en un solo lugar: wiki, guías detalladas y una comunidad activa de jugadores listos para el combate.</p>
        <div class="hero-buttons">
            <a href="wiki.php" class="btn-hero btn-hero-primary"><i class="fas fa-book-open"></i> Explorar Wiki</a>
            <a href="forum.php" class="btn-hero btn-hero-secondary"><i class="fas fa-users"></i> Unirse al Foro</a>
        </div>
    </div>
</div>

<div class="container" style="max-width: 1200px; margin: -40px auto 40px auto; position: relative; z-index: 10;">
    
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-book"></i></div>
            <h3>Wiki Actualizada</h3>
            <p>Información completa y verificada sobre personajes, armas, habilidades y mecánicas del juego.</p>
            <a href="wiki.php" class="feature-link">Explorar <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-users"></i></div>
            <h3>Foro Activo</h3>
            <p>Comparte estrategias, busca equipo, haz preguntas y conecta con jugadores de todo el mundo.</p>
            <a href="forum.php" class="feature-link">Participar <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-graduation-cap"></i></div>
            <h3>Guías Expertas</h3>
            <p>Aprende técnicas avanzadas, builds óptimos y secretos del juego de jugadores experimentados.</p>
            <a href="guides.php" class="feature-link">Aprender <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>

    <div class="content-showcase" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-top: 40px;">
        
        <div class="showcase-card">
            <div class="showcase-header">
                <h2><i class="fas fa-fire"></i> Últimas Guías</h2>
                <a href="guides.php" class="btn-sm btn-outline">Ver todas</a>
            </div>
            <div class="showcase-list">
                <?php if (empty($recent_guides)): ?>
                    <p style="text-align:center; color:#999; padding:20px;">No hay guías publicadas aún.</p>
                <?php else: ?>
                    <?php foreach($recent_guides as $guide): ?>
                        <a href="article.php?id=<?php echo $guide['id']; ?>" class="list-item">
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

        <div class="showcase-card">
            <div class="showcase-header">
                <h2><i class="fas fa-comments"></i> Temas Activos</h2>
                <a href="forum.php" class="btn-sm btn-outline">Ir al Foro</a>
            </div>
            <div class="showcase-list">
                <?php if (empty($active_posts)): ?>
                    <p style="text-align:center; color:#999; padding:20px;">No hay temas en el foro aún.</p>
                <?php else: ?>
                    <?php foreach($active_posts as $post): ?>
                        <a href="view-post.php?id=<?php echo $post['id']; ?>" class="list-item">
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
    
    <div class="stats-banner" style="margin-top: 40px;">
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
</div>

<style>
/* ================= 首页专属精美 CSS ================= */
.hero-section { background: linear-gradient(135deg, var(--primary) 0%, #2a2a4a 100%); color: white; padding: 100px 20px 120px 20px; text-align: center; }
.hero-content { max-width: 800px; margin: 0 auto; }
.hero-content h1 { font-size: 3.5em; margin: 0 0 20px 0; color: var(--accent); text-shadow: 0 2px 10px rgba(0,0,0,0.3); }
.hero-content p { font-size: 1.2em; line-height: 1.6; color: #ddd; margin-bottom: 30px; }
.hero-buttons { display: flex; gap: 20px; justify-content: center; }
.btn-hero { padding: 15px 30px; border-radius: 30px; font-size: 1.1em; font-weight: bold; text-decoration: none; transition: 0.3s; display: inline-flex; align-items: center; gap: 10px; }
.btn-hero-primary { background: var(--accent); color: white; box-shadow: 0 4px 15px rgba(0,173,181,0.4); }
.btn-hero-primary:hover { background: #008f96; transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,173,181,0.6); }
.btn-hero-secondary { background: rgba(255,255,255,0.1); color: white; border: 2px solid rgba(255,255,255,0.2); }
.btn-hero-secondary:hover { background: rgba(255,255,255,0.2); transform: translateY(-3px); }

/* 三大功能卡片 */
.features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
.feature-card { background: white; padding: 40px 30px; border-radius: 15px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: 0.3s; border-top: 5px solid var(--accent); }
.feature-card:hover { transform: translateY(-10px); box-shadow: 0 15px 40px rgba(0,0,0,0.12); }
.feature-icon { font-size: 3em; color: var(--accent); margin-bottom: 20px; }
.feature-card h3 { font-size: 1.5em; color: var(--primary); margin: 0 0 15px 0; }
.feature-card p { color: #666; margin-bottom: 25px; line-height: 1.6; }
.feature-link { color: var(--accent); font-weight: bold; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
.feature-link:hover { gap: 12px; color: #008f96; }

/* 动态内容区 */
.showcase-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
.showcase-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; margin-bottom: 20px; }
.showcase-header h2 { margin: 0; font-size: 1.4em; color: var(--primary); display: flex; align-items: center; gap: 10px; }
.showcase-header h2 i { color: var(--accent); }

.list-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #eee; text-decoration: none; transition: 0.2s; border-radius: 8px; }
.list-item:hover { background: #f8f9fa; transform: translateX(5px); }
.list-item:last-child { border-bottom: none; }
.item-main h4 { margin: 0 0 5px 0; color: var(--text); font-size: 1.1em; }
.list-item:hover .item-main h4 { color: var(--accent); }
.item-meta { font-size: 0.85em; color: #888; display: block; }

.diff-badge, .category-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.75em; font-weight: bold; text-transform: uppercase; }
.diff-badge.beginner { background: #d4edda; color: #155724; }
.diff-badge.intermediate { background: #fff3cd; color: #856404; }
.diff-badge.advanced { background: #f8d7da; color: #721c24; }
.category-badge { background: #e9ecef; color: #555; }

/* 统计条 */
.stats-banner { background: var(--primary); border-radius: 15px; padding: 40px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; text-align: center; color: white; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
.stat-block i { font-size: 2.5em; color: var(--accent); margin-bottom: 15px; display: block; }
.stat-num { font-size: 2.5em; font-weight: bold; display: block; line-height: 1; margin-bottom: 5px; }
.stat-text { color: #aaa; text-transform: uppercase; font-size: 0.9em; letter-spacing: 1px; }

@media (max-width: 768px) {
    .hero-content h1 { font-size: 2.5em; }
    .hero-buttons { flex-direction: column; }
    .container { margin-top: 20px; }
}
</style>

<?php include 'includes/footer.php'; ?>