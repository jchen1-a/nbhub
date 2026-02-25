<?php
// new-guide.php - 创建新攻略页面
require_once 'config.php';
require_login(); // 确保用户已登录

$errors = [];
$formData = [
    'title' => '',
    'category' => 'general',
    'difficulty' => 'beginner',
    'content' => ''
];

// 预定义分类（与 guides.php 筛选器配合）
$categories = [
    'general' => 'General',
    'combat' => 'Combate',
    'movement' => 'Movimiento',
    'heroes' => 'Héroes',
    'weapons' => 'Armas',
    'map' => 'Mapa'
];

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. 获取并清理输入
    $formData['title'] = sanitize($_POST['title'] ?? '');
    $formData['category'] = sanitize($_POST['category'] ?? 'general');
    $formData['difficulty'] = sanitize($_POST['difficulty'] ?? 'beginner');
    // 内容通常允许一些 HTML 或换行，这里简单清理，实际项目中可能需要更复杂的富文本过滤器
    $formData['content'] = trim($_POST['content'] ?? ''); 

    // 2. 验证
    if (empty($formData['title'])) {
        $errors['title'] = 'El título es obligatorio.';
    } elseif (strlen($formData['title']) < 5) {
        $errors['title'] = 'El título debe tener al menos 5 caracteres.';
    }

    if (empty($formData['content'])) {
        $errors['content'] = 'El contenido no puede estar vacío.';
    } elseif (strlen($formData['content']) < 50) {
        $errors['content'] = 'El contenido es muy corto. Escribe al menos 50 caracteres.';
    }

    // 3. 插入数据库
    if (empty($errors)) {
        try {
            $pdo = db_connect();
            
            // 确保 articles 表包含这些字段
            $sql = "INSERT INTO articles (user_id, title, category, difficulty, content, created_at, is_published, views) 
                    VALUES (?, ?, ?, ?, ?, NOW(), 1, 0)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_SESSION['user_id'],
                $formData['title'],
                $formData['category'],
                $formData['difficulty'],
                $formData['content']
            ]);
            
            // 获取新插入的 ID
            $newId = $pdo->lastInsertId();
            
            // 设置成功消息并重定向
            $_SESSION['flash_message'] = '¡Guía publicada con éxito!';
            
            // 如果你有 view-guide.php 或 article.php，可以重定向到那里
            // 目前先重定向回 dashboard
            header("Location: dashboard.php");
            exit();
            
        } catch (Exception $e) {
            $errors['general'] = 'Error al guardar la guía: ' . $e->getMessage();
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container" style="padding: 40px 20px; max-width: 900px;">
    <div class="card-form">
        <div class="form-header">
            <h1><i class="fas fa-pen-fancy"></i> Crear Nueva Guía</h1>
            <p>Comparte tu conocimiento con la comunidad de Naraka</p>
        </div>

        <?php if (isset($errors['general'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $errors['general']; ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="create-guide-form">
            <div class="form-group">
                <label for="title">Título de la Guía <span class="required">*</span></label>
                <input type="text" id="title" name="title" 
                       value="<?php echo htmlspecialchars($formData['title']); ?>"
                       placeholder="Ej: Guía avanzada de Katana para principiantes" required>
                <?php if (isset($errors['title'])): ?>
                    <div class="form-error"><?php echo $errors['title']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="category">Categoría</label>
                    <select id="category" name="category" class="form-select">
                        <?php foreach ($categories as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo $formData['category'] == $key ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group half">
                    <label for="difficulty">Dificultad</label>
                    <select id="difficulty" name="difficulty" class="form-select">
                        <option value="beginner" <?php echo $formData['difficulty'] == 'beginner' ? 'selected' : ''; ?>>Principiante</option>
                        <option value="intermediate" <?php echo $formData['difficulty'] == 'intermediate' ? 'selected' : ''; ?>>Intermedio</option>
                        <option value="advanced" <?php echo $formData['difficulty'] == 'advanced' ? 'selected' : ''; ?>>Avanzado</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="content">Contenido <span class="required">*</span></label>
                <textarea id="content" name="content" rows="15" 
                          placeholder="Escribe aquí tu guía detallada... Puedes usar Markdown simple si lo deseas." required><?php echo htmlspecialchars($formData['content']); ?></textarea>
                <?php if (isset($errors['content'])): ?>
                    <div class="form-error"><?php echo $errors['content']; ?></div>
                <?php endif; ?>
                <div class="form-hint">Mínimo 50 caracteres. Sé detallado y claro.</div>
            </div>

            <div class="form-actions">
                <a href="dashboard.php" class="btn-outline">Cancelar</a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-paper-plane"></i> Publicar Guía
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* 针对表单的简单内联样式，保持与 register.php 风格一致 */
.card-form {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.form-header {
    margin-bottom: 30px;
    text-align: center;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 20px;
}

.form-header h1 {
    color: var(--primary);
    font-size: 2em;
    margin-bottom: 10px;
}

.form-group {
    margin-bottom: 20px;
}

.form-row {
    display: flex;
    gap: 20px;
}

.form-group.half {
    flex: 1;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #333;
}

.required {
    color: var(--danger);
}

input[type="text"],
select,
textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    font-family: inherit;
    transition: border-color 0.3s;
}

input:focus,
select:focus,
textarea:focus {
    border-color: var(--accent);
    outline: none;
}

textarea {
    resize: vertical;
    line-height: 1.6;
}

.form-error {
    color: var(--danger);
    font-size: 0.9em;
    margin-top: 5px;
}

.form-hint {
    color: #666;
    font-size: 0.85em;
    margin-top: 5px;
    text-align: right;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #f0f0f0;
}

.btn-primary, .btn-outline {
    padding: 12px 25px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    text-decoration: none;
    font-size: 1em;
    border: none;
}

.btn-primary {
    background: var(--accent);
    color: white;
}

.btn-primary:hover {
    background: #00959c;
}

.btn-outline {
    background: transparent;
    border: 2px solid #ddd;
    color: #666;
}

.btn-outline:hover {
    border-color: #666;
    background: #f9f9f9;
}

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 0;
    }
}
</style>

<?php include 'includes/footer.php'; ?>