<?php
// logout.php - 处理用户注销
require_once 'config.php';

// 如果会话尚未启动，则启动它（config.php 通常会处理，但为了安全起见）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. 清空所有会话变量
$_SESSION = array();

// 2. 销毁会话 Cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. 销毁会话
session_destroy();

// 4. 重定向回首页
header("Location: index.php");
exit;
?>