<?php
// delete-post.php - 100% 完整版 (包含管理员全局删帖特权与级联清理)
require_once 'config.php';
require_login();

$id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];
// 判定管理员特权
$is_admin = (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin');

if ($id <= 0) {
    header('Location: forum.php');
    exit;
}

try {
    $pdo = db_connect();

    // 1. 获取帖子信息
    $stmt = $pdo->prepare("SELECT user_id FROM forum_posts WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    
    if ($post) {
        // 2. 权限校验：存在且归属当前用户，或是管理员
        if ($post['user_id'] == $user_id || $is_admin) {
            
            // 级联清理：先清理关联表的数据，最后删主体
            $pdo->prepare("DELETE FROM forum_replies WHERE post_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM forum_post_likes WHERE post_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM user_bookmarks WHERE post_id = ?")->execute([$id]);
            
            $pdo->prepare("DELETE FROM forum_posts WHERE id = ?")->execute([$id]);
            
            $_SESSION['flash_message'] = 'El tema y todas sus interacciones han sido eliminados por completo.';
        } else {
            $_SESSION['flash_error'] = 'No tienes permiso para eliminar este tema.';
        }
    } else {
        $_SESSION['flash_error'] = 'El tema no existe o ya fue eliminado.';
    }
} catch (Exception $e) {
    $_SESSION['flash_error'] = 'Error del sistema: ' . $e->getMessage();
}

header('Location: forum.php');
exit;