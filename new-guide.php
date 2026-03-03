<?php
// new-guide.php - 创建新攻略 (支持视频上传)
require_once 'config.php';
require_login();

$errors = [];
$formData = ['title' => '', 'category' => 'general', 'difficulty' => 'beginner', 'content' => '', 'video_url' => ''];
$categories = ['general' => 'General', 'combat' => 'Combate', 'movement' => 'Movimiento', 'heroes' => 'Héroes', 'weapons' => 'Armas', 'map' => 'Mapa'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['title'] = sanitize($_POST['title'] ?? '');
    $formData['category'] = sanitize($_POST['category'] ?? 'general');
    $formData['difficulty'] = sanitize($_POST['difficulty'] ?? 'beginner');
    $formData['content'] = trim($_POST['content'] ?? '');
    $formData['video_url'] = sanitize($_POST['video_url'] ?? '');
    $video_path = null;

    if (empty($formData['title'])) $errors['title'] = 'El título es obligatorio.';
    if (empty($formData['content'])) $errors['content'] = 'El contenido es obligatorio.';

    // 处理 MP4 视频上传
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['video'];
        if ($file['type'] !== 'video/mp4') {
            $errors['video'] = "Solo se permiten videos en formato MP4.";
        } elseif ($file['size'] > 20 * 1024 * 1024) { // 限制 20MB
            $errors['video'] = "El video no debe superar los 20MB.";
        } else {
            $upload_dir = 'uploads/videos/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $new_filename = 'guide_' . time() . '_' . rand(1000, 9999) . '.mp4';
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
                $video_path = $upload_dir . $new_filename;
            } else {
                $errors['video'] = "Error al guardar el video.";
            }
        }
    }

    if (empty($errors)) {
        try {
            $pdo = db_connect();
            $stmt = $pdo->prepare("
                INSERT INTO articles (user_id, title, category, difficulty, content, video_url, video_path, created_at, is_published, views) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 1, 0)
            ");
            $stmt->execute([$_SESSION['user_id'], $formData['title'], $formData['category'], $formData['difficulty'], $formData['content'], $formData['video_url'], $video_path]);
            
            $_SESSION['flash_message'] = '¡Guía publicada con éxito!';
            header("Location: article.php?id=" . $pdo->lastInsertId());
            exit();
        } catch (Exception $e) {
            $errors['general'] = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<?php include 'includes/header.php'; ?>
<div class="container" style="padding: 40px 20px; max-width: 900px;">
    <div style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
        <h1 style="color: var(--primary); margin-bottom: 20px;"><i class="fas fa-pen-fancy"></i> Crear Nueva Guía</h1>
        <?php if (isset($errors['general'])): ?><div class="alert alert-error"><?php echo $errors['general']; ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div style="margin-bottom: 20px;">
                <label style="font-weight:bold; display:block;">Título *</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($formData['title']); ?>" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="font-weight:bold; display:block;">Categoría</label>
                    <select name="category" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
                        <?php foreach ($categories as $k => $v) echo "<option value='$k' ".($formData['category']==$k?'selected':'').">$v</option>"; ?>
                    </select>
                </div>
                <div>
                    <label style="font-weight:bold; display:block;">Dificultad</label>
                    <select name="difficulty" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
                        <option value="beginner">Principiante</option>
                        <option value="intermediate">Intermedio</option>
                        <option value="advanced">Avanzado</option>
                    </select>
                </div>
            </div>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px dashed #ccc;">
                <h4 style="margin-top:0;"><i class="fas fa-video"></i> Añadir Video (Opcional)</h4>
                <div style="margin-bottom: 10px;">
                    <label style="font-weight:bold; display:block;">Enlace de YouTube</label>
                    <input type="text" name="video_url" placeholder="Ej: https://www.youtube.com/watch?v=..." value="<?php echo htmlspecialchars($formData['video_url']); ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div style="text-align:center; color:#888; margin: 10px 0;">- O -</div>
                <div>
                    <label style="font-weight:bold; display:block;">Subir archivo MP4 (Máx 20MB)</label>
                    <input type="file" name="video" accept="video/mp4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; background: white;">
                    <?php if(isset($errors['video'])) echo "<small style='color:red;'>{$errors['video']}</small>"; ?>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="font-weight:bold; display:block;">Contenido *</label>
                <textarea name="content" rows="12" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; resize:vertical; overflow-wrap:break-word;"><?php echo htmlspecialchars($formData['content']); ?></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 15px;">
                <a href="dashboard.php" style="padding: 12px 25px; border: 2px solid #ddd; border-radius: 8px; text-decoration: none; color: #666; font-weight:bold;">Cancelar</a>
                <button type="submit" style="padding: 12px 25px; background: var(--accent); color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">Publicar Guía</button>
            </div>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>