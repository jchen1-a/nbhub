<?php
// edit-post.php - 编辑论坛帖子
require_once 'config.php';
require_login();

$id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];
$errors = [];

try {
    $pdo = db_connect();
    // 验证帖子是否存在，且当前用户是否是作者
    $stmt = $pdo->prepare("SELECT * FROM forum_posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $post = $stmt->fetch();
    
    if (!$post) {
        $_SESSION['flash_error'] = "Tema no encontrado o no tienes permiso para editarlo.";
        header("Location: forum.php");
        exit;
    }
} catch (Exception $e) { die("Error: " . $e->getMessage()); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $category = sanitize($_POST['category'] ?? 'general');
    $content = trim($_POST['content'] ?? '');

    if (empty($title)) $errors['title'] = 'El título es obligatorio.';
    if (empty($content)) $errors['content'] = 'El contenido es obligatorio.';

    if (empty($errors)) {
        $pdo->prepare("UPDATE forum_posts SET title=?, category=?, content=? WHERE id=?")->execute([$title, $category, $content, $id]);
        $_SESSION['flash_message'] = '¡Tema actualizado!';
        header("Location: view-post.php?id=" . $id);
        exit;
    }
}
$categories = ['general' => 'Discusión General', 'guias' => 'Guías y Consejos', 'equipos' => 'Búsqueda de Equipo', 'dudas' => 'Dudas y Preguntas', 'offtopic' => 'Off-Topic'];
?>
<?php include 'includes/header.php'; ?>
<div class="container" style="padding: 40px 20px; max-width: 800px; margin:0 auto;">
    <div style="background:white; padding:30px; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.05);">
        <h1 style="color:var(--primary); margin-bottom:20px;"><i class="fas fa-edit"></i> Editar Tema</h1>
        <form method="POST">
            <div style="margin-bottom:20px;">
                <label style="font-weight:bold; display:block;">Título</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? $post['title']); ?>" required style="width:100%; padding:12px; border:2px solid #ddd; border-radius:6px;">
            </div>
            <div style="margin-bottom:20px;">
                <label style="font-weight:bold; display:block;">Categoría</label>
                <select name="category" style="width:100%; padding:12px; border:2px solid #ddd; border-radius:6px;">
                    <?php 
                    $curr_cat = $_POST['category'] ?? $post['category'];
                    foreach($categories as $k => $v) echo "<option value='$k' ".($curr_cat==$k?'selected':'').">$v</option>"; 
                    ?>
                </select>
            </div>
            <div style="margin-bottom:20px;">
                <label style="font-weight:bold; display:block;">Mensaje</label>
                <textarea name="content" rows="10" required style="width:100%; padding:12px; border:2px solid #ddd; border-radius:6px; resize:vertical;"><?php echo htmlspecialchars($_POST['content'] ?? $post['content']); ?></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:15px;">
                <button type="button" onclick="history.back()" style="padding:10px 20px; border:1px solid #ccc; border-radius:6px; cursor:pointer;">Cancelar</button>
                <button type="submit" style="padding:10px 20px; background:var(--accent); color:white; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Actualizar Tema</button>
            </div>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>