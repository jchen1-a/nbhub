<?php
// config.php - 数据库配置核心
session_start();
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Asia/Shanghai');

// 显示错误 (开发环境)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 环境变量读取
function env($key, $default = '') {
    $value = $_SERVER[$key] ?? getenv($key);
    return $value !== false ? $value : $default;
}

// 数据库连接
function db_connect() {
    $host = env('DB_HOST');
    $port = env('DB_PORT', '5432');
    $name = env('DB_NAME', 'postgres');
    $user = env('DB_USER', 'postgres');
    $pass = env('DB_PASSWORD');
    
    if (empty($host) || empty($pass)) {
        throw new Exception('❌ 数据库配置不完整。请检查EdgeOne环境变量。');
    }
    
    try {
        $dsn = "pgsql:host=$host;port=$port;dbname=$name";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::PGSQL_ATTR_SSL_MODE => PDO::PGSQL_SSL_MODE_REQUIRE
        ];
        
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        throw new Exception('数据库连接失败: ' . $e->getMessage());
    }
}

// 用户认证相关
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['username'] ?? 'Invitado',
        'email' => $_SESSION['user_email'] ?? null
    ];
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}

// 安全函数
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function json_response($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}
?>