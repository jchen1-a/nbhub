<?php
// includes/footer.php
?>
    </main> <!-- 关闭主内容区 -->

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-gamepad"></i> Naraka Hub</h3>
                    <p>Tu centro de información para Naraka: Bladepoint</p>
                    <p class="footer-stats">
                        <?php
                        try {
                            $pdo = db_connect();
                            $users = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
                            $posts = $pdo->query("SELECT COUNT(*) as count FROM articles")->fetch()['count'];
                            echo "<span><i class='fas fa-users'></i> $users usuarios</span>";
                            echo "<span><i class='fas fa-file-alt'></i> $posts guías</span>";
                        } catch (Exception $e) {
                            echo "<span><i class='fas fa-database'></i> Base de datos: No conectada</span>";
                        }
                        ?>
                    </p>
                </div>
                
                <div class="footer-section">
                    <h4>Enlaces Rápidos</h4>
                    <ul>
                        <li><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
                        <li><a href="wiki.php"><i class="fas fa-book"></i> Wiki</a></li>
                        <li><a href="guides.php"><i class="fas fa-graduation-cap"></i> Guías</a></li>
                        <li><a href="forum.php"><i class="fas fa-comments"></i> Foro</a></li>
                        <li><a href="test_db.php"><i class="fas fa-database"></i> Estado del sistema</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Comunidad</h4>
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
                <p>© <?php echo date('Y'); ?> Naraka Hub. Todos los derechos reservados.</p>
                <p class="debug-info">
                    <?php 
                    echo "Sesión: " . (is_logged_in() ? '✅ Activa' : '❌ Inactiva');
                    echo " | Servidor: " . date('H:i:s');
                    echo " | PHP: " . PHP_VERSION;
                    ?>
                </p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="js/main.js"></script>
    <script>
        // 用户下拉菜单
        document.querySelector('.user-dropdown')?.addEventListener('click', function(e) {
            this.querySelector('.dropdown-menu').classList.toggle('show');
        });
        
        // 点击外部关闭下拉菜单
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-dropdown')) {
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });
        
        // 自动隐藏系统消息
        setTimeout(() => {
            document.querySelectorAll('.system-alert').forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>