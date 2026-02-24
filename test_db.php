<?php
// test_db.php - 数据库连接测试 (修正版)
require_once 'config.php';
?>
<?php include 'includes/header.php'; ?>

<div class="page-header" style="padding: 20px; max-width: 1000px; margin: 0 auto;">
    <h1><i class="fas fa-database"></i> Prueba de Conexión</h1>
</div>

<div class="test-container">
    <?php
    $start_time = microtime(true);
    
    try {
        $pdo = db_connect();
        $connect_time = round((microtime(true) - $start_time) * 1000, 2);
        
        echo '<div class="test-result success">';
        echo '<h2>✅ ¡Conexión Exitosa!</h2>';
        echo "<p>Conectado en <strong>{$connect_time}ms</strong></p>";
        
        // 修复：使用 MySQL 兼容的查询
        $info = $pdo->query("SELECT VERSION() as v, DATABASE() as d, USER() as u")->fetch();
        
        echo '<div class="info-grid">';
        echo '<div class="info-item"><strong>Versión:</strong><br>' . $info['v'] . '</div>';
        echo '<div class="info-item"><strong>Base de datos:</strong><br>' . $info['d'] . '</div>';
        echo '<div class="info-item"><strong>Usuario:</strong><br>' . $info['u'] . '</div>';
        echo '<div class="info-item"><strong>Host:</strong><br>' . DB_HOST . '</div>';
        echo '</div>';
        
        // 检查表
        echo '<h3><i class="fas fa-table"></i> Tablas</h3>';
        // 使用 SHOW TABLES 代替 information_schema，兼容性更好
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        if ($tables) {
            echo '<div class="tables-grid">';
            foreach ($tables as $table) {
                try {
                    // 安全获取行数
                    $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                    echo '<div class="table-card">';
                    echo '<h4>' . $table . '</h4>';
                    echo '<p class="table-count">' . $count . ' filas</p>';
                    echo '</div>';
                } catch (Exception $e) {
                    continue;
                }
            }
            echo '</div>';
        } else {
            echo '<div class="alert-info">Base de datos vacía.</div>';
        }
        echo '</div>'; // close success
        
    } catch (Exception $e) {
        echo '<div class="test-result error">';
        echo '<h2>❌ Conexión Fallida</h2>';
        echo "<p>Error: " . $e->getMessage() . "</p>";
        echo '</div>';
    }
    ?>
    
    <div style="margin-top: 20px;">
        <a href="index.php" class="btn-primary">Volver al Inicio</a>
    </div>
</div>

<style>
.test-container { max-width: 1000px; margin: 0 auto; padding: 20px; }
.test-result { padding: 20px; border-radius: 8px; margin-bottom: 20px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
.info-item { background: rgba(255,255,255,0.5); padding: 10px; border-radius: 5px; }
.tables-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; }
.table-card { background: white; padding: 15px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-align: center; }
.table-count { font-weight: bold; color: #00adb5; }
</style>

<?php include 'includes/footer.php'; ?>