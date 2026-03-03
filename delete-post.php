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

    // 安全检查：确认帖子存在且作者是当前用户
    $check = $pdo->prepare("SELECT id FROM forum_posts WHERE id = ? AND user_id = ?");
    $check->execute([$id, $user_id]);
    
    if ($check->fetch()) {
        // 先删除该帖子的所有回复 (防止数据库外键约束报错或产生孤儿数据)
        $pdo->prepare("DELETE FROM forum_replies WHERE post_id = ?")->execute([$id]);
        
        // 再删除主贴
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