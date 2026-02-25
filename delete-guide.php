<?php
// delete-guide.php - 处理删除请求
require_once 'config.php';
require_login(); // 必须登录

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

if ($id <= 0) {
    $_SESSION['flash_error'] = 'ID de guía inválido.';
    header('Location: dashboard.php');
    exit;
}

try {
    $pdo = db_connect();

    // 1. 安全检查：确认这篇文章存在，且作者是当前登录用户
    // 我们在 DELETE 语句中直接加上 user_id = ? 条件，这样如果不是作者，就删不掉，无需多查一次
    $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);

    if ($stmt->rowCount() > 0) {
        // 删除成功
        $_SESSION['flash_message'] = '¡Guía eliminada correctamente!';
    } else {
        // 删除失败（可能是因为找不到文章，或者文章不是你的）
        $_SESSION['flash_error'] = 'No se pudo eliminar la guía o no tienes permiso.';
    }

} catch (Exception $e) {
    $_SESSION['flash_error'] = 'Error del sistema: ' . $e->getMessage();
}

// 返回仪表盘
header('Location: dashboard.php');
exit;
?>