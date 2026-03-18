<?php
// wiki-new.php - 100% 完整版 (暗黑毛玻璃高级美化版，功能完全保留)
require_once 'config.php';
require_login(); // 必须登录才能做贡献

$errors = [];
$formData = [
    'title' => '',
    'category_id' => '',
    'content' => ''
];
$categories = [];

try {
    $pdo = db_connect();
    // 获取可用的分类
    $categories = $pdo->query("SELECT id, name FROM wiki_categories ORDER BY name")->fetchAll();
} catch (Exception $e) {
    $errors['general'] = "Error al cargar categorías: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['title'] = sanitize($_POST['title'] ?? '');
    $formData['category_id'] = intval($_POST['category_id'] ?? 0);
    $formData['content'] = trim($_POST['content'] ?? '');

    if (empty($formData['title'])) $errors['title'] = 'El título es obligatorio.';
    if (empty($formData['category_id'])) $errors['category_id'] = 'Selecciona una disciplina.';
    if (empty($formData['content'])) $errors['content'] = 'El contenido no puede estar vacío.';

    if (empty($errors)) {
        try {
            // 插入 wiki_articles 表
            $stmt = $pdo->prepare("
                INSERT INTO wiki_articles (title, content, category_id, author_id, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $formData['title'],
                $formData['content'],
                $formData['category_id'],
                $_SESSION['user_id']
            ]);
            
            $_SESSION['flash_message'] = '¡Pergamino forjado con éxito! Tu sabiduría ha sido registrada.';
            header("Location: wiki.php"); // 创建成功后返回 wiki 首页
            exit();
            
        } catch (Exception $e) {
            $errors['general'] = 'Error al sellar el pergamino: ' . $e->getMessage();
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="fixed-blurred-bg"></div>

<div class="wiki-glass-container" style="max-width: 900px; margin: 60px auto 80px auto;">
    
    <div class="glass-card">
        <h1 class="card-glass-title" style="font-size: 2em; margin-bottom: 10px;"><i class="fas fa-pen-nib" style="color: var(--accent);"></i> Forjar Nuevo Pergamino</h1>
        <p style="color: #aaa; margin-bottom: 35px; font-size: 1.1em; font-family: 'Cinzel', serif; text-transform: uppercase; letter-spacing: 1px;">Plasma tu conocimiento en los archivos eternos de Morus.</p>

        <?php if (isset($errors['general'])): ?>
            <div class="glass-alert alert-error" style="margin-bottom: 25px;"><i class="fas fa-exclamation-triangle"></i> <?php echo $errors['general']; ?></div>
        <?php endif; ?>

        <form method="POST" class="glass-form">
            
            <div class="form-group">
                <label><i class="fas fa-heading"></i> Título del Artículo</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($formData['title']); ?>" required placeholder="Ej: Guía definitiva de Viper Ning..." class="glass-input">
                <?php if(isset($errors['title'])) echo "<small class='error-text'>{$errors['title']}</small>"; ?>
            </div>

            <div class="form-group">
                <label><i class="fas fa-layer-group"></i> Disciplina (Categoría)</label>
                <select name="category_id" required class="glass-input glass-select">
                    <option value="" style="color: #888;">-- Elige el camino de este texto --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $formData['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if(isset($errors['category_id'])) echo "<small class='error-text'>{$errors['category_id']}</small>"; ?>
            </div>

            <div class="form-group">
                <label><i class="fas fa-scroll"></i> Contenido del Pergamino</label>
                <textarea name="content" rows="18" required placeholder="Escribe aquí toda tu sabiduría. Puedes usar saltos de línea para estructurar el texto..." class="glass-input glass-textarea"><?php echo htmlspecialchars($formData['content']); ?></textarea>
                <?php if(isset($errors['content'])) echo "<small class='error-text'>{$errors['content']}</small>"; ?>
            </div>

            <div class="form-actions">
                <a href="wiki.php" class="btn-ink-outline"><i class="fas fa-times"></i> Descartar</a>
                <button type="submit" class="btn-hero-primary"><i class="fas fa-save"></i> Sellar Pergamino</button>
            </div>
            
        </form>
    </div>
</div>

<style>
/* ================= 全局模糊底层与毛玻璃表单样式 ================= */

body {
    background-color: #0a0a0c !important;
    color: #fff;
    overflow: auto !important; 
}

/* 固定的全屏模糊底层 */
.fixed-blurred-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: url('assets/cover.jpg?v=<?php echo time(); ?>') no-repeat center 20%;
    background-size: cover;
    filter: blur(15px) brightness(0.25) contrast(1.2);
    z-index: -10; 
    pointer-events: none !important; 
}

/* 容器与卡片 */
.wiki-glass-container {
    position: relative;
    z-index: 10;
    padding: 0 20px;
}

.glass-card {
    background: rgba(15, 15, 18, 0.75);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 4px;
    padding: 40px 50px;
    box-shadow: 0 15px 50px rgba(0,0,0,0.6);
    border-top: 4px solid var(--accent);
}

.card-glass-title {
    font-family: 'Cinzel', serif;
    color: #fff;
    letter-spacing: 1px;
}

/* 表单组样式 */
.form-group {
    margin-bottom: 25px;
}
.form-group label {
    display: block;
    font-family: 'Cinzel', serif;
    font-weight: bold;
    color: #ccc;
    margin-bottom: 10px;
    font-size: 1.1em;
    letter-spacing: 1px;
}
.form-group label i {
    color: var(--accent);
    margin-right: 8px;
    font-size: 0.9em;
}

/* 毛玻璃输入框核心样式 */
.glass-input {
    width: 100%;
    background: rgba(0, 0, 0, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 2px;
    color: #fff;
    padding: 15px 18px;
    font-size: 1.05em;
    outline: none;
    transition: all 0.3s;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
.glass-input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 15px rgba(201, 20, 20, 0.3);
    background: rgba(0, 0, 0, 0.7);
}
.glass-input::placeholder {
    color: #666;
}

/* 下拉菜单特殊处理 */
.glass-select {
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    background-image: url('data:image/svg+xml;utf8,<svg fill="%23ffffff" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/><path d="M0 0h24v24H0z" fill="none"/></svg>');
    background-repeat: no-repeat;
    background-position: right 15px top 50%;
}
.glass-select option {
    background-color: #1a1a1a;
    color: #fff;
    padding: 10px;
}

/* 文本域 */
.glass-textarea {
    resize: vertical;
    line-height: 1.6;
    min-height: 200px;
}

/* 错误提示 */
.error-text {
    color: var(--accent);
    display: block;
    margin-top: 8px;
    font-weight: bold;
    font-size: 0.9em;
}
.glass-alert {
    background: rgba(201, 20, 20, 0.1);
    border-left: 4px solid var(--accent);
    color: #ff9999;
    padding: 15px 20px;
    font-weight: bold;
}

/* 底部操作区 */
.form-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 20px;
    margin-top: 40px;
    padding-top: 25px;
    border-top: 1px solid rgba(255,255,255,0.1);
}

/* 按钮通用 */
.btn-hero-primary { 
    background: rgba(201, 20, 20, 0.8); 
    color: white; 
    border: 1px solid var(--accent); 
    padding: 14px 30px;
    font-size: 1.05em;
    font-weight: bold;
    text-transform: uppercase;
    font-family: 'Cinzel', serif;
    letter-spacing: 1px;
    border-radius: 2px;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-hero-primary:hover { 
    background: var(--accent); 
    transform: translateY(-2px); 
    box-shadow: 0 5px 20px rgba(204,0,0,0.5); 
}

.btn-ink-outline { 
    background: transparent; 
    color: #aaa; 
    border: 1px solid #555; 
    padding: 12px 25px; 
    text-decoration: none; 
    font-weight: bold; 
    text-transform: uppercase;
    font-family: 'Cinzel', serif;
    letter-spacing: 1px;
    border-radius: 2px; 
    transition: 0.3s; 
}
.btn-ink-outline:hover { 
    border-color: #fff; 
    color: #fff; 
}

@media (max-width: 768px) {
    .glass-card { padding: 30px 20px; }
    .form-actions { flex-direction: column-reverse; gap: 15px; }
    .form-actions > * { width: 100%; text-align: center; justify-content: center; }
}
</style>

<?php include 'includes/footer.php'; ?>