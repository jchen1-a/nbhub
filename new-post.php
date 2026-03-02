<?php
// new-post.php - 创建论坛新主题
require_once 'config.php';
require_login(); // 必须登录

$errors = [];
$formData = [
    'title' => '',
    'category' => 'general',
    'content' => ''
];

$categories = [
    'general' => 'Discusión General',
    'guias' => 'Guías y Consejos',
    'equipos' => 'Búsqueda de Equipo',
    'dudas' => 'Dudas y Preguntas',
    'offtopic' => 'Off-Topic'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['title'] = sanitize($_POST['title'] ?? '');
    $formData['category'] = sanitize($_POST['category'] ?? 'general');
    $formData['content'] = trim($_POST['content'] ?? '');

    if (empty($formData['title'])) {
        $errors['title'] = 'El título es obligatorio.';
    } elseif (strlen($formData['title']) < 5) {
        $errors['title'] = 'El título debe ser más descriptivo.';
    }

    if (empty($formData['content'])) {
        $errors['content'] = 'El contenido es obligatorio.';
    }

    if (empty($errors)) {
        try {
            $pdo = db_connect();
            
            // 插入新帖子 (自动将 last_reply_at 设置为当前时间)
            $stmt = $pdo->prepare("
                INSERT INTO forum_posts (user_id, title, content, category, created_at, last_reply_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $formData['title'],
                $formData['content'],
                $formData['category']
            ]);
            
            $new_post_id = $pdo->lastInsertId();
            
            $_SESSION['flash_message'] = '¡Tema publicado con éxito!';
            header("Location: view-post.php?id=" . $new_post_id);
            exit();
            
        } catch (Exception $e) {
            $errors['general'] = 'Error del sistema: ' . $e->getMessage();
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container" style="padding: 40px 20px; max-width: 800px;">
    <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
        <h1 style="color: var(--primary); margin-bottom: 20px;"><i class="fas fa-edit"></i> Nuevo Tema</h1>
        
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-error"><?php echo $errors['general']; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom: 20px;">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Título del Tema</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($formData['title']); ?>" required
                       style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 6px; font-size: 16px;">
                <?php if(isset($errors['title'])) echo "<small style='color:red;'>{$errors['title']}</small>"; ?>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Categoría</label>
                <select name="category" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 6px; font-size: 16px;">
                    <?php foreach ($categories as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo $formData['category'] == $val ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Mensaje</label>
                <textarea name="content" rows="10" required
                          style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 6px; font-size: 16px; font-family:inherit; resize:vertical;"><?php echo htmlspecialchars($formData['content']); ?></textarea>
                <?php if(isset($errors['content'])) echo "<small style='color:red;'>{$errors['content']}</small>"; ?>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 15px;">
                <a href="forum.php" style="padding: 12px 25px; background: #eee; color: #333; text-decoration: none; border-radius: 6px;">Cancelar</a>
                <button type="submit" style="padding: 12px 25px; background: var(--accent); color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">Publicar Tema</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>