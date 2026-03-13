<?php
// edit-guide.php - 编辑已有攻略 (完整版)
require_once 'config.php';
require_login();

$id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];
$errors = [];
$categories = [
    'general' => 'General', 
    'combat' => 'Combate', 
    'movement' => 'Movimiento', 
    'heroes' => 'Héroes', 
    'weapons' => 'Armas', 
    'map' => 'Mapa'
];

try {
    $pdo = db_connect();
    
    // 1. 验证攻略是否存在，以及当前登录用户是否是该攻略的作者
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $article = $stmt->fetch();
    
    if (!$article) {
        $_SESSION['flash_error'] = "La guía no existe o no tienes permiso para editarla.";
        header("Location: dashboard.php");
        exit;
    }
} catch (Exception $e) {
    die("Error de base de datos: " . $e->getMessage());
}

// 2. 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $category = sanitize($_POST['category'] ?? 'general');
    $difficulty = sanitize($_POST['difficulty'] ?? 'beginner');
    $content = trim($_POST['content'] ?? '');
    $video_url = sanitize($_POST['video_url'] ?? '');
    
    // 默认保留旧的本地视频路径，防止被清空
    $video_path = $article['video_path'] ?? null; 

    if (empty($title)) $errors['title'] = 'El título es obligatorio.';
    if (empty($content)) $errors['content'] = 'El contenido es obligatorio.';

    // 处理新上传的 MP4 视频 (如果用户选择更换视频)
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
                
                // 删除服务器上的旧视频文件，节省硬盘空间
                if (!empty($article['video_path']) && file_exists($article['video_path'])) {
                    unlink($article['video_path']);
                }
            } else {
                $errors['video'] = "Error al guardar el nuevo video.";
            }
        }
    }

    // 3. 更新数据库
    if (empty($errors)) {
        try {
            $updateStmt = $pdo->prepare("
                UPDATE articles 
                SET title = ?, category = ?, difficulty = ?, content = ?, video_url = ?, video_path = ? 
                WHERE id = ?
            ");
            $updateStmt->execute([$title, $category, $difficulty, $content, $video_url, $video_path, $id]);
            
            $_SESSION['flash_message'] = '¡Guía actualizada con éxito!';
            header("Location: article.php?id=" . $id);
            exit();
        } catch (Exception $e) {
            $errors['general'] = 'Error al actualizar: ' . $e->getMessage();
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container" style="padding: 40px 20px; max-width: 900px; margin: 0 auto;">
    <div style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
        <h1 style="color: var(--primary); margin-bottom: 20px;">
            <i class="fas fa-edit"></i> Editar Guía
        </h1>
        
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-error"><?php echo $errors['general']; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div style="margin-bottom: 20px;">
                <label style="font-weight:bold; display:block; margin-bottom:8px;">Título *</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? $article['title']); ?>" required 
                       style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size:16px;">
                <?php if(isset($errors['title'])) echo "<small style='color:red;'>{$errors['title']}</small>"; ?>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="font-weight:bold; display:block; margin-bottom:8px;">Categoría</label>
                    <select name="category" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size:16px;">
                        <?php 
                        $curr_cat = $_POST['category'] ?? $article['category'];
                        foreach ($categories as $k => $v) {
                            $selected = ($curr_cat == $k) ? 'selected' : '';
                            echo "<option value='$k' $selected>$v</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label style="font-weight:bold; display:block; margin-bottom:8px;">Dificultad</label>
                    <select name="difficulty" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size:16px;">
                        <?php $curr_diff = $_POST['difficulty'] ?? $article['difficulty']; ?>
                        <option value="beginner" <?php echo $curr_diff == 'beginner' ? 'selected' : ''; ?>>Principiante</option>
                        <option value="intermediate" <?php echo $curr_diff == 'intermediate' ? 'selected' : ''; ?>>Intermedio</option>
                        <option value="advanced" <?php echo $curr_diff == 'advanced' ? 'selected' : ''; ?>>Avanzado</option>
                    </select>
                </div>
            </div>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px dashed #ccc;">
                <h4 style="margin-top:0; color: var(--primary);"><i class="fas fa-video"></i> Multimedia (Opcional)</h4>
                
                <?php if(!empty($article['video_path'])): ?>
                    <div style="margin-bottom: 15px; padding: 10px; background: #e9ecef; border-radius: 6px; font-size: 0.9em;">
                        <i class="fas fa-check-circle" style="color: #28a745;"></i> Ya tienes un video local subido. Si subes uno nuevo, el anterior se borrará.
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: 10px;">
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Enlace de YouTube</label>
                    <input type="text" name="video_url" placeholder="Ej: https://www.youtube.com/watch?v=..." 
                           value="<?php echo htmlspecialchars($_POST['video_url'] ?? $article['video_url'] ?? ''); ?>" 
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size:15px;">
                </div>
                
                <div style="text-align:center; color:#888; margin: 15px 0; font-weight:bold;">- O -</div>
                
                <div>
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Reemplazar con nuevo archivo MP4 (Máx 20MB)</label>
                    <input type="file" name="video" accept="video/mp4" 
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; background: white; cursor:pointer;">
                    <?php if(isset($errors['video'])) echo "<small style='color:red; display:block; margin-top:5px;'>{$errors['video']}</small>"; ?>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="font-weight:bold; display:block; margin-bottom:8px;">Contenido de la Guía *</label>
                <textarea name="content" rows="15" required 
                          style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; font-family: inherit; resize: vertical; overflow-wrap: break-word;"><?php echo htmlspecialchars($_POST['content'] ?? $article['content']); ?></textarea>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 15px; border-top: 1px solid #eee; padding-top: 20px;">
                <a href="article.php?id=<?php echo $id; ?>" 
                   style="padding: 12px 25px; border: 2px solid #ddd; border-radius: 8px; text-decoration: none; color: #555; font-weight:bold; transition: all 0.3s;">
                    Cancelar
                </a>
                <button type="submit" 
                        style="padding: 12px 25px; background: var(--success, #28a745); color: white; border: none; border-radius: 8px; font-weight: bold; font-size: 1.05em; cursor: pointer; transition: all 0.3s;">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* 按钮悬停效果 */
button[type="submit"]:hover { background: #218838 !important; }
a[href^="article.php"]:hover { background: #f8f9fa; }
</style>

<?php include 'includes/footer.php'; ?>