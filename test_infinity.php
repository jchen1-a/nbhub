<?php
require_once 'config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>InfinityFree测试</title>";
echo "<style>body{font-family:Arial;padding:20px;max-width:800px;margin:0 auto;}</style></head><body>";
echo "<h1>🔧 InfinityFree数据库连接测试</h1>";

echo "<h3>配置信息：</h3>";
echo "<ul>";
echo "<li>主机: " . DB_HOST . "</li>";
echo "<li>数据库: " . DB_NAME . "</li>";
echo "<li>用户: " . DB_USER . "</li>";
echo "<li>端口: " . DB_PORT . "</li>";
echo "</ul>";

try {
    $start = microtime(true);
    $pdo = db_connect();
    $time = round((microtime(true) - $start) * 1000, 2);
    
    echo "<h2 style='color:green;'>✅ 连接成功！ ({$time}ms)</h2>";
    
    // 显示MySQL信息
    $version = $pdo->query("SELECT VERSION() as v")->fetch();
    echo "<p>MySQL版本: <strong>" . $version['v'] . "</strong></p>";
    
    // 显示所有表
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<div style='background:#fff3cd;padding:15px;border-radius:5px;margin:20px 0;'>";
        echo "<h3>⚠️ 数据库为空</h3>";
        echo "<p>需要创建数据表：</p>";
        echo "<pre style='background:#f8f9fa;padding:15px;border-radius:5px;overflow:auto;'>";
        echo htmlspecialchars("
-- 基本用户表
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARSET=utf8mb4;

-- 文章表
CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB CHARSET=utf8mb4;
        ");
        echo "</pre>";
        echo "<p><a href='phpmyadmin' target='_blank'>前往phpMyAdmin创建表</a></p>";
        echo "</div>";
    } else {
        echo "<h3>📊 数据库中的表：</h3>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li><strong>" . htmlspecialchars($table) . "</strong>";
            
            // 显示行数
            $count = $pdo->query("SELECT COUNT(*) as c FROM `$table`")->fetch()['c'];
            echo " - {$count} 行记录</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color:red;'>❌ 连接失败</h2>";
    echo "<div style='background:#f8d7da;padding:15px;border-radius:5px;margin:20px 0;'>";
    echo "<h4>错误信息：</h4>";
    echo "<pre style='background:#f1f1f1;padding:10px;border-radius:3px;'>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
    
    echo "<h3>🔍 故障排除：</h3>";
    echo "<ol>";
    echo "<li>确认密码正确（使用vPanel登录密码）</li>";
    echo "<li>确保数据库名完全正确：'if0_41075202_Nbbase'</li>";
    echo "<li>确保用户名完全正确：'if0_41075202'</li>";
    echo "<li>尝试直接登录phpMyAdmin验证信息</li>";
    echo "</ol>";
    
    echo "<h3>📋 验证步骤：</h3>";
    echo "<p>1. 访问 <a href='https://phpmyadmin.infinityfree.com' target='_blank'>phpMyAdmin</a></p>";
    echo "<p>2. 使用相同信息登录：</p>";
    echo "<pre>服务器: sql211.infinityfree.com
用户: if0_41075202
密码: 你的vPanel密码</pre>";
}

echo "<hr>";
echo "<p><a href='index.php'>返回首页</a> | <a href='phpinfo.php'>查看PHP信息</a></p>";
echo "</body></html>";
?>