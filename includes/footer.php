<?php
// includes/footer.php - 完美美化版
$stats_users = 0; 
$stats_guides = 0;
try {
    if(function_exists('db_connect')) {
        $pdo_footer = db_connect();
        $stats_users = $pdo_footer->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats_guides = $pdo_footer->query("SELECT COUNT(*) FROM articles")->fetchColumn();
    }
} catch(Exception $e) {}
?>
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-col">
                <h3 class="footer-logo"><i class="fas fa-gamepad"></i> Naraka Hub</h3>
                <p>Tu centro de información definitivo para Naraka: Bladepoint.</p>
                <div class="footer-stats">
                    <span><i class="fas fa-users"></i> <?php echo $stats_users; ?> usuarios</span>
                    <span><i class="fas fa-file-alt"></i> <?php echo $stats_guides; ?> guías</span>
                </div>
            </div>
            
            <div class="footer-col">
                <h3>Enlaces Rápidos</h3>
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
                    <li><a href="wiki.php"><i class="fas fa-book"></i> Wiki Actualizada</a></li>
                    <li><a href="guides.php"><i class="fas fa-graduation-cap"></i> Guías Expertas</a></li>
                    <li><a href="forum.php"><i class="fas fa-comments"></i> Foro Activo</a></li>
                </ul>
            </div>
            
            <div class="footer-col">
                <h3>Comunidad</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-discord"></i> Discord Oficial</a>
                    <a href="#"><i class="fab fa-twitter"></i> Twitter</a>
                    <a href="#"><i class="fab fa-youtube"></i> YouTube</a>
                    <a href="#"><i class="fab fa-github"></i> GitHub</a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>Proyecto desarrollado por <strong>Ming Liuzhang</strong> y <strong>Juncheng Chen</strong></p>
            <p>&copy; <?php echo date('Y'); ?> Naraka Hub. Todos los derechos reservados.</p>
            <div class="server-status">
                Sesión: <span style="color:#28a745;"><i class="fas fa-check-square"></i> Activa</span> | Servidor: <?php echo date('H:i:s'); ?> | PHP: <?php echo phpversion(); ?>
            </div>
        </div>
    </footer>

    <style>
    /* 页脚专属美化 CSS */
    .site-footer { background: var(--primary); color: #ccc; padding: 60px 20px 20px; margin-top: 60px; font-size: 0.95em; border-top: 5px solid var(--accent); }
    .footer-container { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 40px; }
    
    .footer-col h3 { color: white; margin-bottom: 20px; font-size: 1.2em; border-bottom: 2px solid var(--accent); padding-bottom: 10px; display: inline-block; }
    .footer-logo { color: var(--accent) !important; border: none !important; font-size: 1.5em !important; margin-bottom: 10px !important; }
    .footer-col p { margin: 10px 0; line-height: 1.6; }
    
    .footer-stats { display: flex; gap: 15px; margin-top: 15px; color: #888; font-size: 0.9em; }
    
    .footer-col ul { list-style: none; padding: 0; margin: 0; }
    .footer-col ul li { margin-bottom: 12px; }
    .footer-col ul a { color: #aaa; text-decoration: none; transition: 0.3s; display: flex; align-items: center; gap: 10px; }
    .footer-col ul a:hover { color: var(--accent); transform: translateX(5px); }
    
    .social-links { display: flex; flex-direction: column; gap: 12px; }
    .social-links a { color: #aaa; text-decoration: none; transition: 0.3s; display: flex; align-items: center; gap: 10px; }
    .social-links a:hover { color: white; transform: translateX(5px); }
    
    .footer-bottom { text-align: center; padding-top: 25px; color: #888; }
    .footer-bottom p { margin: 5px 0; }
    .server-status { font-size: 0.85em; background: rgba(0,0,0,0.2); display: inline-block; padding: 8px 20px; border-radius: 20px; margin-top: 15px; border: 1px solid rgba(255,255,255,0.05); }
    </style>
</body>
</html>