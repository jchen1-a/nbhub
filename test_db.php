<?php
// test_db.php - 数据库连接测试
require_once __DIR__ . '/config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>数据库测试</title>";
echo "<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; }</style></head><body>";
echo "<h2>Naraka Hub - 数据库连接测试</h2>";

try {
    $pdo = getDBConnection();
    echo "<p class='success'>✅ <strong>成功！</strong> 数据库连接正常。</p>";
    
    // 显示连接信息（隐藏密码）
    echo "<h3>当前配置：</h3>";
    echo "<ul>";
    echo "<li>DB_HOST: " . htmlspecialchars(getEnvVariable('DB_HOST')) . "</li>";
    echo "<li>DB_PORT: " . htmlspecialchars(getEnvVariable('DB_PORT') ?: '5432') . "</li>";
    echo "<li>DB_NAME: " . htmlspecialchars(getEnvVariable('DB_NAME')) . "</li>";
    echo "<li>DB_USER: " . htmlspecialchars(getEnvVariable('DB_USER')) . "</li>";
    echo "<li>DB_PASSWORD: " . (getEnvVariable('DB_PASSWORD') ? '****** (已设置)' : '<span class="error">未设置</span>') . "</li>";
    echo "</ul>";
    
    // 测试查询
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p>用户表记录数：" . $result['count'] . "</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ <strong>连接失败！</strong></p>";
    echo "<p><strong>错误信息：</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>排查步骤：</strong></p>";
    echo "<ol>";
    echo "<li>检查EdgeOne环境变量是否正确设置</li>";
    echo "<li>确认Supabase数据库处于活动状态</li>";
    echo "<li>核对数据库密码是否正确</li>";
    echo "<li>检查网络连接</li>";
    echo "</ol>";
}

echo "</body></html>";
?>