<?php
// delete-post.php - 删除论坛帖子及回复
require_once 'config.php';
require_login();

$id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($id <= 0) {
    header('Location: forum.php');
    exit;
}

try {
    $pdo = db_connect();

    // 权限校验：存在且归属当前用户，或是管理员 (若系统有 is_admin 函数可叠加)
    $check = $pdo->prepare("SELECT id FROM forum_posts WHERE id = ? AND user_id = ?");
    $check->execute([$id, $user_id]);
    
    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM forum_replies WHERE post_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM forum_posts WHERE id = ?")->execute([$id]);
        $_SESSION['flash_message'] = 'El tema y todas sus respuestas han sido eliminados.';
    } else {
        $_SESSION['flash_error'] = 'No tienes permiso para eliminar este tema.';
    }
} catch (Exception $e) {
    $_SESSION['flash_error'] = 'Error del sistema: ' . $e->getMessage();
}

header('Location: forum.php');
exit;
?>