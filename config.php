<?php
// config.php - InfinityFree专用版
session_start();
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Asia/Shanghai');

// 错误报告（开发环境开启）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==================== 数据库配置 ====================
// 使用你的实际信息（不要修改左侧，只确认右侧值正确）
define('DB_HOST', 'sql211.infinityfree.com');  // 你的主机
define('DB_USER', 'if0_41075202');             // 你的用户名
define('DB_PASSWORD', 'NBhub10086');       // 你登录InfinityFree的密码
define('DB_NAME', 'if0_41075202_Nbbase');      // 你的数据库名
define('DB_PORT', '3306');

// ==================== 数据库连接函数 ====================
function db_connect() {
    try {
        // MySQL连接字符串
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // 抛出异常
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // 返回关联数组
            PDO::ATTR_EMULATE_PREPARES => false,              // 禁用模拟预处理
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]);
        
        return $pdo;
        
    } catch (PDOException $e) {
        // 详细的错误信息
        $error_msg = "数据库连接失败！<br><br>";
        $error_msg .= "<strong>错误详情：</strong> " . $e->getMessage() . "<br><br>";
        $error_msg .= "<strong>检查以下信息：</strong><br>";
        $error_msg .= "1. 主机：'sql211.infinityfree.com'<br>";
        $error_msg .= "2. 用户名：'if0_41075202'<br>";
        $error_msg .= "3. 数据库名：'if0_41075202_Nbbase'<br>";
        $error_msg .= "4. 密码：是否正确（vPanel登录密码）<br>";
        $error_msg .= "5. 网络：能否访问 sql211.infinityfree.com<br>";
        
        die($error_msg);
    }
}

// ==================== 用户认证函数 ====================
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function current_user() {
    if (is_logged_in()) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['username'] ?? 'Usuario',
            'email' => $_SESSION['user_email'] ?? ''
        ];
    }
    return null;
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit();
    }
}

// ==================== 安全函数 ====================
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// ==================== 实用函数 ====================
function redirect($url, $delay = 0) {
    if ($delay > 0) {
        header("Refresh: $delay; URL=$url");
    } else {
        header("Location: $url");
    }
    exit();
}

function json_response($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// ==================== 数据库助手函数 ====================
function table_exists($table_name) {
    try {
        $pdo = db_connect();
        $stmt = $pdo->query("SHOW TABLES LIKE '$table_name'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

function get_db_stats() {
    try {
        $pdo = db_connect();
        $stats = [];
        
        // 检查用户表
        if (table_exists('users')) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $stats['users'] = $stmt->fetch()['count'];
        }
        
        // 检查文章表
        if (table_exists('articles')) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM articles");
            $stats['articles'] = $stmt->fetch()['count'];
        }
        
        return $stats;
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

// ==================== 会话安全 ====================
// 防止会话固定攻击
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) {
    // 30分钟后重新生成会话ID
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// 设置CSRF令牌（如果不存在）
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token() {
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return hash_equals($_SESSION['csrf_token'], $token);
}

// ==================== 文件上传配置 ====================
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// 确保上传目录存在
if (!file_exists(UPLOAD_DIR) && is_writable(dirname(UPLOAD_DIR))) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// ==================== 网站配置 ====================
define('SITE_NAME', 'Naraka Hub');
define('SITE_URL', 'https://' . $_SERVER['HTTP_HOST']);
define('ADMIN_EMAIL', 'admin@narakahub.com');
define('ITEMS_PER_PAGE', 10);

// 时区设置（根据你的位置调整）
date_default_timezone_set('Europe/Madrid'); // 西班牙时间

// ==================== 开发模式检测 ====================
define('IS_DEV', $_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], 'test') !== false);

if (IS_DEV) {
    // 开发环境设置
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // 生产环境设置
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}
?>