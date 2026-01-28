<?php
// test_db.php - 数据库连接测试
require_once 'config.php';
?>
<?php include 'includes/header.php'; ?>

<div class="page-header">
    <h1><i class="fas fa-database"></i> Prueba de Conexión a Base de Datos</h1>
    <p>Verifica que tu configuración de EdgeOne + Supabase esté funcionando correctamente</p>
</div>

<div class="test-container">
    <?php
    $start_time = microtime(true);
    
    try {
        $pdo = db_connect();
        $connect_time = round((microtime(true) - $start_time) * 1000, 2);
        
        echo '<div class="test-result success">';
        echo '<div class="result-header">';
        echo '<i class="fas fa-check-circle"></i>';
        echo '<h2>✅ ¡Conexión Exitosa!</h2>';
        echo '</div>';
        echo "<p class='result-detail'>Conexión establecida en <strong>{$connect_time}ms</strong></p>";
        
        // 显示数据库信息
        $info = $pdo->query("SELECT version(), current_database(), current_user, inet_server_addr() as host")->fetch();
        
        echo '<div class="info-grid">';
        echo '<div class="info-item"><strong>PostgreSQL:</strong><br><code>' . $info['version'] . '</code></div>';
        echo '<div class="info-item"><strong>Base de datos:</strong><br>' . $info['current_database'] . '</div>';
        echo '<div class="info-item"><strong>Usuario:</strong><br>' . $info['current_user'] . '</div>';
        echo '<div class="info-item"><strong>Host:</strong><br>' . ($info['host'] ?: env('DB_HOST')) . '</div>';
        echo '</div>';
        
        // 检查表
        $tables = $pdo->query("
            SELECT table_name, 
                   (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = t.table_name) as columns
            FROM information_schema.tables t 
            WHERE table_schema = 'public'
            ORDER BY table_name
        ")->fetchAll();
        
        if ($tables) {
            echo '<h3><i class="fas fa-table"></i> Tablas en la base de datos</h3>';
            echo '<div class="tables-grid">';
            foreach ($tables as $table) {
                $count = $pdo->query("SELECT COUNT(*) as c FROM " . $table['table_name'])->fetch()['c'];
                echo '<div class="table-card">';
                echo '<h4>' . $table['table_name'] . '</h4>';
                echo '<p>' . $table['columns'] . ' columnas</p>';
                echo '<p class="table-count">' . $count . ' registros</p>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<div class="alert-info">';
            echo '<h3><i class="fas fa-info-circle"></i> Base de datos vacía</h3>';
            echo '<p>No hay tablas en la base de datos. Necesitas ejecutar el script SQL de creación.</p>';
            echo '<a href="https://supabase.com/dashboard/project" class="btn-primary" target="_blank">';
            echo '<i class="fas fa-external-link-alt"></i> Ir a Supabase para crear tablas';
            echo '</a>';
            echo '</div>';
        }
        
        echo '</div>'; // 关闭 test-result
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        echo '<div class="test-result error">';
        echo '<div class="result-header">';
        echo '<i class="fas fa-times-circle"></i>';
        echo '<h2>❌ Conexión Fallida</h2>';
        echo '</div>';
        echo "<p class='result-detail'>Error: <code>$error</code></p>";
        
        echo '<div class="troubleshooting">';
        echo '<h3><i class="fas fa-tools"></i> Pasos para solucionar:</h3>';
        echo '<ol>';
        echo '<li><strong>Verifica las variables de entorno en EdgeOne:</strong><br>';
        echo 'DB_HOST, DB_USER, DB_PASSWORD deben estar configuradas correctamente.</li>';
        echo '<li><strong>Revisa la contraseña de Supabase:</strong><br>';
        echo 'Asegúrate de usar la contraseña correcta (distingue mayúsculas/minúsculas).</li>';
        echo '<li><strong>Verifica el estado de Supabase:</strong><br>';
        echo '<a href="https://supabase.com/dashboard/project" target="_blank">Ir al panel de Supabase</a> para verificar que el proyecto esté activo.</li>';
        echo '<li><strong>Revisa los logs de EdgeOne:</strong><br>';
        echo 'Consulta los logs de despliegue para ver si hay errores durante la carga.</li>';
        echo '</ol>';
        
        echo '<h4>Variables de entorno detectadas:</h4>';
        echo '<div class="env-vars">';
        $vars = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
        foreach ($vars as $var) {
            $value = env($var);
            echo '<div class="env-var">';
            echo '<strong>' . $var . ':</strong> ';
            echo $value ? ($var === 'DB_PASSWORD' ? '●●●●●●' : htmlspecialchars($value)) : '<span class="missing">NO CONFIGURADO</span>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>'; // troubleshooting
        echo '</div>'; // test-result
    }
    ?>
    
    <div class="test-actions">
        <a href="index.php" class="btn-primary">
            <i class="fas fa-home"></i> Volver al Inicio
        </a>
        <a href="https://supabase.com/dashboard/project" class="btn-secondary" target="_blank">
            <i class="fas fa-external-link-alt"></i> Ir a Supabase
        </a>
        <button onclick="window.location.reload()" class="btn-outline">
            <i class="fas fa-redo"></i> Probar de Nuevo
        </button>
    </div>
</div>

<style>
.test-container { max-width: 1000px; margin: 0 auto; }
.test-result { padding: 25px; border-radius: 10px; margin: 20px 0; }
.test-result.success { background: #d4edda; border: 2px solid #28a745; }
.test-result.error { background: #f8d7da; border: 2px solid #dc3545; }
.result-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
.result-header i { font-size: 2em; }
.result-detail { font-size: 1.1em; margin: 15px 0; }
.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 25px 0; }
.info-item { background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #00adb5; }
.tables-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0; }
.table-card { background: white; padding: 15px; border-radius: 8px; text-align: center; }
.table-count { font-size: 1.5em; font-weight: bold; color: #00adb5; }
.troubleshooting { background: white; padding: 20px; border-radius: 8px; margin-top: 20px; }
.env-vars { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px; margin: 15px 0; }
.env-var { background: #f8f9fa; padding: 10px; border-radius: 5px; }
.env-var .missing { color: #dc3545; font-weight: bold; }
.test-actions { display: flex; gap: 15px; margin-top: 30px; flex-wrap: wrap; }
</style>

<?php include 'includes/footer.php'; ?>