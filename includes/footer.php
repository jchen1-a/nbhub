<?php
// includes/footer.php - 100% 完整版 (美化专业版，已适配水墨武林黑红白配色)
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
            <div class="footer-col footer-col-bio">
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
                    <li><a href="status.php"><i class="fas fa-network-wired"></i> Estado del sistema</a></li>
                </ul>
            </div>
            
            <div class="footer-col">
                <h3>Comunidad</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-discord"></i> Discord</a>
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
                Sesión: <span style="color:#388e3c;"><i class="fas fa-check-square"></i> Activa</span> | Servidor: <?php echo date('H:i:s'); ?> | PHP: <?php echo phpversion(); ?>
            </div>
        </div>
    </footer>

    <style>
    /* 页脚专属美化 CSS */
    .site-footer { background: var(--primary); color: #bbb; padding: 60px 20px 20px; margin-top: 60px; font-size: 0.95em; border-top: 5px solid var(--accent); }
    .footer-container { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 40px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 40px; }
    
    .footer-col h3 { color: white; margin-bottom: 25px; font-size: 1.25em; font-weight: bold; border-bottom: 2px solid var(--accent); padding-bottom: 12px; display: inline-block; }
    .footer-col-bio h3.footer-logo { color: var(--accent) !important; border: none !important; font-size: 1.7em !important; margin-bottom: 15px !important; }
    .footer-col p { margin: 10px 0; line-height: 1.6; }
    
    .footer-stats { display: flex; gap: 18px; margin-top: 20px; color: #888; font-size: 0.9em; }
    .footer-stats span { display: flex; align-items: center; gap: 6px; }
    
    .footer-col ul { list-style: none; padding: 0; margin: 0; }
    .footer-col ul li { margin-bottom: 15px; }
    .footer-col ul a { color: #aaa; text-decoration: none; transition: 0.3s; display: flex; align-items: center; gap: 10px; }
    .footer-col ul a i { font-size: 1em; color: rgba(255,255,255,0.2); transition: 0.3s; }
    .footer-col ul a:hover { color: var(--accent); transform: translateX(5px); }
    .footer-col ul a:hover i { color: var(--accent); }
    
    .social-links { display: flex; flex-direction: column; gap: 15px; }
    .social-links a { color: #aaa; text-decoration: none; transition: 0.3s; display: flex; align-items: center; gap: 12px; }
    .social-links a i { font-size: 1.3em; color: rgba(255,255,255,0.2); transition: 0.3s; }
    .social-links a:hover { color: white; transform: translateX(5px); }
    .social-links a:hover i { color: var(--accent); }
    
    .footer-bottom { text-align: center; padding-top: 30px; color: #777; font-size: 0.9em; }
    .footer-bottom p { margin: 6px 0; }
    .server-status { font-size: 0.85em; background: rgba(0,0,0,0.3); display: inline-block; padding: 10px 25px; border-radius: 25px; margin-top: 20px; border: 1px solid rgba(255,255,255,0.03); }
    </style>
</body>
</html>