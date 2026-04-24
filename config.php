<?php
// config.php - InfinityFree专用版 (融合 P3 通知系统引擎)
session_start();
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Asia/Shanghai');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==================== 数据库配置 ====================
define('DB_HOST', 'sql211.infinityfree.com');
define('DB_USER', 'if0_41075202');
define('DB_PASSWORD', 'NBhub10086');
define('DB_NAME', 'if0_41075202_Nbbase');
define('DB_PORT', '3306');

// ==================== 数据库连接函数 ====================
function db_connect() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión a la base de datos.");
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

// ==================== 会话安全 ====================
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token() {
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return hash_equals($_SESSION['csrf_token'], $token);
}

// ==================== P3 通知系统引擎 (飞鸽传书) ====================
function send_notification($user_id, $sender_id, $type, $reference_id) {
    // 防止自己给自己发通知（比如自己赞了自己）
    if ($user_id == $sender_id) return; 
    
    try {
        $pdo = db_connect();
        // 避免重复通知（比如取消赞又重新赞，短时间内不再触发）
        $check = $pdo->prepare("SELECT id FROM notifications WHERE user_id = ? AND sender_id = ? AND type = ? AND reference_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $check->execute([$user_id, $sender_id, $type, $reference_id]);
        
        if (!$check->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, reference_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $sender_id, $type, $reference_id]);
        }
    } catch (Exception $e) {
        // 静默失败，不影响主流程
    }
}

function get_unread_notifications_count($user_id) {
    if (!$user_id) return 0;
    try {
        $pdo = db_connect();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

// ==================== 文件上传配置 ====================
define('MAX_AVATAR_SIZE', 2 * 1024 * 1024);  
define('MAX_VIDEO_SIZE', 20 * 1024 * 1024);  
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4']);
define('UPLOAD_DIR', __DIR__ . '/uploads/');

if (!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!file_exists(UPLOAD_DIR . 'avatars/')) mkdir(UPLOAD_DIR . 'avatars/', 0755, true);
if (!file_exists(UPLOAD_DIR . 'videos/')) mkdir(UPLOAD_DIR . 'videos/', 0755, true);

// ==================== 网站配置 ====================
define('SITE_NAME', 'Naraka Hub');
define('SITE_URL', 'https://' . $_SERVER['HTTP_HOST']);
define('ADMIN_EMAIL', 'admin@narakahub.com');

// ==================== 开发与 SMTP 配置 ====================
define('IS_DEV', $_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], 'test') !== false);
if (IS_DEV) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587); 
define('SMTP_USER', 'chji351327@gmail.com');
define('SMTP_PASS', 'incenhbqtsviqbug'); // 请在真实环境中注意保密
define('SITE_EMAIL', 'no-reply@narakahub.com');