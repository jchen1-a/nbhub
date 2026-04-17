<?php
// new-guide.php - 创建新攻略 (浅色水墨白灰红版，支持视频上传)
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

<div class="light-theme-bg"></div>

<div class="edit-container">
    
    <div class="edit-card">
        <h1 class="edit-title"><i class="fas fa-feather-alt" style="color: #9e1b1b;"></i> Escribir Nueva Guía (落笔成剑)</h1>
        <p class="edit-subtitle">Comparte tu sabiduría y tácticas con la comunidad de Morus.</p>

        <?php if (isset($errors['general'])): ?>
            <div class="alert-box alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $errors['general']; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label class="form-label">Título de la Guía (卷宗名称) <span style="color:#9e1b1b;">*</span></label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($formData['title']); ?>" required class="form-control" placeholder="Ej: Combos avanzados con Katana...">
                <?php if(isset($errors['title'])) echo "<small class='error-text'>{$errors['title']}</small>"; ?>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Categoría (所属分类)</label>
                    <select name="category" class="form-control">
                        <?php foreach ($categories as $k => $v) echo "<option value='$k' ".($formData['category']==$k?'selected':'').">$v</option>"; ?>
                    </select>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Dificultad (武学境界)</label>
                    <select name="difficulty" class="form-control">
                        <option value="beginner" <?php echo $formData['difficulty'] == 'beginner' ? 'selected' : ''; ?>>Principiante (初境)</option>
                        <option value="intermediate" <?php echo $formData['difficulty'] == 'intermediate' ? 'selected' : ''; ?>>Intermedio (入微)</option>
                        <option value="advanced" <?php echo $formData['difficulty'] == 'advanced' ? 'selected' : ''; ?>>Avanzado (化境)</option>
                    </select>
                </div>
            </div>
            
            <div class="video-upload-box">
                <h4 class="video-title"><i class="fas fa-video"></i> Añadir Evidencia Visual (Opcional)</h4>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label" style="font-size:0.85em; color:#666;">Enlace de YouTube</label>
                    <input type="text" name="video_url" placeholder="Ej: https://www.youtube.com/watch?v=..." value="<?php echo htmlspecialchars($formData['video_url']); ?>" class="form-control">
                </div>
                
                <div class="or-divider">- O -</div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-size:0.85em; color:#666;">Subir archivo MP4 (Máx 20MB)</label>
                    <input type="file" name="video" accept="video/mp4" class="form-control" style="padding: 10px; background: #fff;">
                    <?php if(isset($errors['video'])) echo "<small class='error-text'>{$errors['video']}</small>"; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Contenido de la Guía (卷宗内容) <span style="color:#9e1b1b;">*</span></label>
                <textarea name="content" rows="15" required class="form-control" style="resize:vertical; line-height: 1.6;" placeholder="Describe paso a paso tu estrategia..."><?php echo htmlspecialchars($formData['content']); ?></textarea>
                <?php if(isset($errors['content'])) echo "<small class='error-text'>{$errors['content']}</small>"; ?>
            </div>

            <div class="form-actions">
                <a href="dashboard.php" class="btn-cancel">Descartar</a>
                <button type="submit" class="btn-submit"><i class="fas fa-stamp"></i> Publicar Guía</button>
            </div>
            
        </form>
    </div>
</div>

<style>
/* ================= 浅色水墨风 (White > Gray > Red) ================= */
@import url('https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;700;900&display=swap');

body {
    background-color: #F7F7F7 !important;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.light-theme-bg {
    position: fixed; inset: 0; z-index: -10;
    background-color: #F7F7F7;
    background-image: radial-gradient(circle at 50% 0%, #FFFFFF 0%, transparent 70%);
}

/* 居中表单容器 */
.edit-container {
    flex: 1; 
    width: 100%;
    max-width: 900px;
    margin: 40px auto 80px auto; 
    padding: 0 20px;
    font-family: 'Noto Serif SC', serif;
    box-sizing: border-box;
}

/* 纯白主卡片 */
.edit-card {
    background: #FFFFFF;
    padding: 50px 60px;
    border-radius: 4px;
    border: 1px solid rgba(0,0,0,0.06);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.02);
    position: relative;
}

/* 顶部朱砂红细线 */
.edit-card::before {
    content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 3px;
    background: #9e1b1b;
}

/* 标题区 */
.edit-title { color: #222222; font-size: 2.2em; margin: 0 0 10px 0; font-weight: 900; letter-spacing: 1px;}
.edit-subtitle { color: #888888; font-size: 0.95em; font-family: sans-serif; margin-bottom: 35px; padding-bottom: 20px; border-bottom: 1px dashed #eee;}

/* 错误提示 */
.alert-box { padding: 15px; border-radius: 4px; margin-bottom: 25px; font-family: sans-serif; font-size: 0.9em; font-weight: bold;}
.alert-error { background: rgba(158,27,27,0.05); border: 1px solid #9e1b1b; color: #9e1b1b; }
.error-text { color: #9e1b1b; display: block; margin-top: 8px; font-weight: bold; font-size: 0.85em; font-family: sans-serif;}

/* 表单组 */
.form-row { display: flex; gap: 25px; margin-bottom: 5px; }
.form-group { margin-bottom: 25px; }
.form-label { display: block; font-weight: bold; color: #444; margin-bottom: 10px; font-size: 0.95em; letter-spacing: 1px; font-family: sans-serif;}

.form-control { 
    width: 100%; 
    padding: 15px; 
    border: 1px solid #dddddd; 
    border-radius: 2px; 
    font-size: 1.05em; 
    font-family: inherit; 
    background: #fafafa; 
    transition: all 0.3s;
    box-sizing: border-box;
    outline: none;
    color: #333;
}
.form-control:focus { border-color: #9e1b1b; background: #fff; box-shadow: 0 0 0 3px rgba(158,27,27,0.1); }
.form-control::placeholder { color: #aaa; }

select.form-control {
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    background-image: url('data:image/svg+xml;utf8,<svg fill="%23888888" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/><path d="M0 0h24v24H0z" fill="none"/></svg>');
    background-repeat: no-repeat;
    background-position: right 15px top 50%;
}

/* 视频上传区块 (特殊虚线美化) */
.video-upload-box {
    background: #fbfbfb;
    padding: 25px;
    border-radius: 4px;
    margin-bottom: 30px;
    border: 1px dashed #cccccc;
    transition: 0.3s;
}
.video-upload-box:hover { border-color: #aaa; background: #f5f5f5; }
.video-title { margin: 0 0 20px 0; color: #555; font-size: 1.05em; font-family: sans-serif; letter-spacing: 1px;}
.video-title i { color: #9e1b1b; margin-right: 5px;}
.or-divider { text-align: center; color: #aaa; font-size: 0.9em; font-weight: bold; margin: 15px 0; font-family: sans-serif;}

/* 操作按钮 */
.form-actions { display: flex; justify-content: flex-end; align-items: center; gap: 15px; margin-top: 40px; padding-top: 25px; border-top: 1px dashed #eee;}

.btn-cancel { 
    background: transparent; padding: 12px 25px; border: 1px solid #cccccc; color: #666; 
    border-radius: 2px; font-family: sans-serif; font-weight: bold; font-size: 0.95em; 
    cursor: pointer; transition: 0.3s; letter-spacing: 1px; text-decoration: none;
}
.btn-cancel:hover { border-color: #222; color: #222; background: rgba(0,0,0,0.03); }

.btn-submit { 
    background: #222; color: #fff; border: 1px solid #222; padding: 12px 30px; 
    border-radius: 2px; font-family: 'Noto Serif SC', serif; font-weight: bold; 
    font-size: 1.05em; letter-spacing: 2px; cursor: pointer; transition: 0.3s; 
    display: inline-flex; align-items: center; gap: 8px;
}
.btn-submit:hover { background: #9e1b1b; border-color: #9e1b1b; }

/* 响应式调整 */
@media (max-width: 768px) {
    .edit-container { margin-top: 20px; padding: 0 15px; }
    .edit-card { padding: 30px 20px; }
    .edit-title { font-size: 1.8em; }
    .form-row { flex-direction: column; gap: 5px; }
    .form-actions { flex-direction: column-reverse; align-items: stretch; }
    .btn-cancel, .btn-submit { width: 100%; justify-content: center; text-align: center; box-sizing: border-box;}
}
</style>

<?php include 'includes/footer.php'; ?>