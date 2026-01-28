<?php
// index.php - 动态首页
require_once 'config.php';
?>
<?php include 'includes/header.php'; ?>

<div class="hero">
    <div class="hero-content">
        <h1>Portal de Naraka: Bladepoint</h1>
        <p class="hero-subtitle">Todo lo que necesitas en un solo lugar: wiki, guías y comunidad activa</p>
        
        <div class="hero-buttons">
            <?php if (!is_logged_in()): ?>
                <a href="register.php" class="btn-primary btn-large">
                    <i class="fas fa-user-plus"></i> Únete Gratis
                </a>
            <?php endif; ?>
            <a href="wiki.php" class="btn-secondary">
                <i class="fas fa-book-open"></i> Explorar Wiki
            </a>
            <a href="test_db.php" class="btn-outline">
                <i class="fas fa-server"></i> Ver Estado
            </a>
        </div>
        
        <?php if (is_logged_in()): ?>
            <div class="welcome-back">
                <h3><i class="fas fa-check-circle"></i> ¡Bienvenido de nuevo, <?php echo current_user()['name']; ?>!</h3>
                <p>Tu última actividad fue hace 2 horas. <a href="dashboard.php">Ver tu panel</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<section class="features-section">
    <h2 class="section-title">¿Qué ofrece Naraka Hub?</h2>
    
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-database"></i>
            </div>
            <h3>Wiki Actualizada</h3>
            <p>Información completa y verificada sobre personajes, armas, habilidades y mecánicas del juego.</p>
            <a href="wiki.php" class="feature-link">Explorar <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-users"></i>
            </div>
            <h3>Foro Activo</h3>
            <p>Comparte estrategias, haz preguntas y conecta con jugadores de todo el mundo.</p>
            <a href="forum.php" class="feature-link">Participar <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3>Guías Expertas</h3>
            <p>Aprende técnicas avanzadas, builds óptimos y secretos del juego de jugadores experimentados.</p>
            <a href="guides.php" class="feature-link">Aprender <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</section>

<section class="stats-section">
    <div class="stats-container">
        <?php
        try {
            $pdo = db_connect();
            $stats = $pdo->query("
                SELECT 
                    (SELECT COUNT(*) FROM users) as users,
                    (SELECT COUNT(*) FROM articles) as guides,
                    (SELECT COUNT(*) FROM forum_posts) as posts
            ")->fetch();
        } catch (Exception $e) {
            $stats = ['users' => '?', 'guides' => '?', 'posts' => '?'];
        }
        ?>
        <div class="stat-item">
            <h3><?php echo $stats['users']; ?></h3>
            <p>Jugadores Registrados</p>
        </div>
        <div class="stat-item">
            <h3><?php echo $stats['guides']; ?></h3>
            <p>Guías Publicadas</p>
        </div>
        <div class="stat-item">
            <h3><?php echo $stats['posts']; ?></h3>
            <p>Discusiones Activas</p>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>