<?php
// new-post.php - 创建论坛新主题 (浅色水墨白灰红·居中版 + CSRF 防护)
require_once 'config.php';
require_login();

$errors = [];
$formData = [
    'title' => '',
    'category' => 'general',
    'content' => ''
];

$categories = [
    'general' => 'Discusión General (江湖传闻)',
    'guias' => 'Guías y Consejos (武道心得)',
    'equipos' => 'Búsqueda de Equipo (寻觅知音)',
    'dudas' => 'Dudas y Preguntas (疑难杂症)',
    'offtopic' => 'Off-Topic (客栈闲谈)'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // P0-1: CSRF 安全校验
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors['system'] = 'Error de seguridad (CSRF). Por favor, recarga la página e inténtalo de nuevo.';
    } else {
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
                $stmt = $pdo->prepare("INSERT INTO forum_posts (user_id, title, content, category, created_at, last_reply_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$_SESSION['user_id'], $formData['title'], $formData['content'], $formData['category']]);
                $new_post_id = $pdo->lastInsertId();
                
                $_SESSION['flash_message'] = 'Tema creado exitosamente.';
                header("Location: view-post.php?id=" . $new_post_id);
                exit;
            } catch (Exception $e) {
                $errors['system'] = 'Error de base de datos: ' . $e->getMessage();
            }
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="light-theme-bg"></div>

<div class="edit-container">
    
    <div class="edit-card">
        <h1 class="edit-title"><i class="fas fa-scroll" style="color: #9e1b1b;"></i> Iniciar Nueva Discusión (张贴布告)</h1>
        <p class="edit-subtitle">Deja tu mensaje en el muro de la posada para que otros guerreros lo vean.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert-box alert-error">
                <i class="fas fa-exclamation-triangle"></i> Por favor, corrige los errores del formulario.
                <?php if(isset($errors['system'])) echo "<br><span style='margin-top: 5px; display:inline-block;'>".$errors['system']."</span>"; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <div class="form-group">
                <label class="form-label">Título del tema (布告标题) <span style="color:#9e1b1b;">*</span></label>
                <input type="text" name="title" class="form-control" placeholder="Ej: Busco equipo para subir a Asura..." value="<?php echo htmlspecialchars($formData['title']); ?>" required>
                <?php if(isset($errors['title'])) echo "<small class='error-text'>{$errors['title']}</small>"; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Categoría (所属分类)</label>
                <select name="category" class="form-control">
                    <?php foreach ($categories as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo $formData['category'] == $val ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Mensaje (布告内容) <span style="color:#9e1b1b;">*</span></label>
                <textarea name="content" class="form-control" rows="12" required style="resize:vertical; line-height: 1.6;" placeholder="Escribe aquí los detalles de tu tema..."><?php echo htmlspecialchars($formData['content']); ?></textarea>
                <?php if(isset($errors['content'])) echo "<small class='error-text'>{$errors['content']}</small>"; ?>
            </div>

            <div class="form-actions">
                <a href="forum.php" class="btn-cancel">Cancelar (放弃)</a>
                <button type="submit" class="btn-submit"><i class="fas fa-stamp"></i> Publicar Tema (落印张贴)</button>
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
    max-width: 850px; /* 论坛发帖框不需要太宽 */
    margin: 50px auto 80px auto; 
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
.edit-title i { margin-right: 8px; }
.edit-subtitle { color: #888888; font-size: 0.95em; font-family: sans-serif; margin-bottom: 35px; padding-bottom: 20px; border-bottom: 1px dashed #eee; letter-spacing: 1px;}

/* 错误提示 */
.alert-box { padding: 15px; border-radius: 2px; margin-bottom: 25px; font-family: sans-serif; font-size: 0.9em; font-weight: bold;}
.alert-error { background: rgba(158,27,27,0.05); border-left: 4px solid #9e1b1b; color: #9e1b1b; }
.error-text { color: #9e1b1b; display: block; margin-top: 8px; font-weight: bold; font-size: 0.85em; font-family: sans-serif;}

/* 表单组 */
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
.form-control:focus { border-color: #9e1b1b; background: #fff; box-shadow: 0 0 0 3px rgba(158,27,27,0.08); }
.form-control::placeholder { color: #aaa; font-style: italic;}

select.form-control {
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    background-image: url('data:image/svg+xml;utf8,<svg fill="%23888888" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/><path d="M0 0h24v24H0z" fill="none"/></svg>');
    background-repeat: no-repeat;
    background-position: right 15px top 50%;
}

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
    .form-actions { flex-direction: column-reverse; align-items: stretch; }
    .btn-cancel, .btn-submit { width: 100%; justify-content: center; text-align: center; box-sizing: border-box;}
}
</style>

<?php include 'includes/footer.php'; ?>