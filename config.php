<?php
// config.php - 数据库配置文件
header('Content-Type: text/html; charset=utf-8');

/**
 * 安全地获取环境变量
 */
function getEnvVariable($key) {
    // 先从 $_SERVER 读取
    $value = $_SERVER[$key] ?? '';
    if (empty($value)) {
        $value = getenv($key) ?: '';
    }
    return $value;
}

/**
 * 建立数据库连接
 */
function getDBConnection() {
    // 从环境变量读取配置
    $dbHost = getEnvVariable('DB_HOST');
    $dbPort = getEnvVariable('DB_PORT') ?: '5432';
    $dbName = getEnvVariable('DB_NAME');
    $dbUser = getEnvVariable('DB_USER');
    $dbPass = getEnvVariable('DB_PASSWORD');
    
    if (empty($dbHost) || empty($dbUser) || empty($dbPass)) {
        die('错误：数据库配置不完整。请检查环境变量设置。');
    }
    
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName";
    
    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::PGSQL_ATTR_SSL_MODE => PDO::PGSQL_SSL_MODE_REQUIRE // Supabase需要SSL
        ];
        
        $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
        return $pdo;
    } catch (PDOException $e) {
        die('数据库连接失败: ' . $e->getMessage());
    }
}

// 启动会话（用于用户登录状态）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 设置时区
date_default_timezone_set('Europe/Madrid');
?>