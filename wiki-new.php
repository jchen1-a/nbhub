<?php
// wiki-new.php - 100% 完整版 (浅色水墨白灰红·毛玻璃保留背景版)
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

<div class="wiki-glass-container">
    
    <div class="glass-card">
        <h1 class="card-glass-title"><i class="fas fa-pen-nib" style="color: #9e1b1b;"></i> Forjar Nuevo Pergamino</h1>
        <p class="card-glass-subtitle">Plasma tu conocimiento en los archivos eternos de Morus.</p>

        <?php if (isset($errors['general'])): ?>
            <div class="glass-alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $errors['general']; ?></div>
        <?php endif; ?>

        <form method="POST" class="glass-form">
            
            <div class="form-group">
                <label><i class="fas fa-heading"></i> Título del Artículo (词条名称)</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($formData['title']); ?>" required placeholder="Ej: Guía definitiva de Viper Ning..." class="glass-input">
                <?php if(isset($errors['title'])) echo "<small class='error-text'>{$errors['title']}</small>"; ?>
            </div>

            <div class="form-group">
                <label><i class="fas fa-layer-group"></i> Disciplina (所属分类)</label>
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
                <label><i class="fas fa-scroll"></i> Contenido del Pergamino (卷宗内容)</label>
                <textarea name="content" rows="18" required placeholder="Escribe aquí toda tu sabiduría. Puedes usar saltos de línea para estructurar el texto..." class="glass-input glass-textarea"><?php echo htmlspecialchars($formData['content']); ?></textarea>
                <?php if(isset($errors['content'])) echo "<small class='error-text'>{$errors['content']}</small>"; ?>
            </div>

            <div class="form-actions">
                <a href="wiki.php" class="btn-ink-outline"><i class="fas fa-times"></i> Descartar</a>
                <button type="submit" class="btn-hero-primary"><i class="fas fa-stamp"></i> Sellar Pergamino</button>
            </div>
            
        </form>
    </div>
</div>

<style>
/* ================= 浅色水墨风·毛玻璃保留背景 ================= */
@import url('https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;700;900&family=Cinzel:wght@400;700&display=swap');

body {
    background-color: #F7F7F7 !important;
    color: #333;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* 固定的全屏模糊底层 - 浅色化处理 */
.fixed-blurred-bg {
    position: fixed;
    top: 0; left: 0; width: 100vw; height: 100vh;
    /* 保留你的原背景图 */
    background: url('assets/cover.jpg?v=<?php echo time(); ?>') no-repeat center 20%;
    background-size: cover;
    /* 核心修改：通过大模糊、降低不透明度和灰度，将其变为浅色底纹 */
    filter: blur(25px) grayscale(40%) opacity(0.12);
    z-index: -10; 
    pointer-events: none !important; 
}

/* 居中容器 */
.wiki-glass-container {
    flex: 1;
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 900px;
    margin: 50px auto 80px auto;
    padding: 0 20px;
    box-sizing: border-box;
}

/* 纯白半透明毛玻璃卡片 */
.glass-card {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: 4px;
    padding: 50px 60px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.03);
    border-top: 4px solid #9e1b1b; /* 朱砂红细线 */
}

/* 标题区 */
.card-glass-title {
    font-family: 'Noto Serif SC', serif;
    font-size: 2.2em;
    color: #222;
    margin: 0 0 10px 0;
    font-weight: 900;
    letter-spacing: 1px;
}
.card-glass-subtitle {
    color: #888;
    margin-bottom: 40px;
    font-size: 0.95em;
    font-family: sans-serif;
    letter-spacing: 1px;
    padding-bottom: 20px;
    border-bottom: 1px dashed #eee;
}

/* 表单组样式 */
.form-group {
    margin-bottom: 25px;
}
.form-group label {
    display: block;
    font-family: sans-serif;
    font-weight: bold;
    color: #555;
    margin-bottom: 10px;
    font-size: 0.95em;
    letter-spacing: 1px;
}
.form-group label i {
    color: #9e1b1b;
    margin-right: 6px;
}

/* 毛玻璃输入框核心样式 */
.glass-input {
    width: 100%;
    background: rgba(250, 250, 250, 0.8);
    border: 1px solid #dddddd;
    border-radius: 2px;
    color: #333;
    padding: 15px 18px;
    font-size: 1.05em;
    outline: none;
    transition: all 0.3s;
    font-family: inherit;
    box-sizing: border-box;
}
.glass-input:focus {
    border-color: #9e1b1b;
    box-shadow: 0 0 0 3px rgba(158, 27, 27, 0.08);
    background: #fff;
}
.glass-input::placeholder {
    color: #aaa;
}

/* 下拉菜单特殊处理：使用深色SVG箭头 */
.glass-select {
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    background-image: url('data:image/svg+xml;utf8,<svg fill="%23888888" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/><path d="M0 0h24v24H0z" fill="none"/></svg>');
    background-repeat: no-repeat;
    background-position: right 15px top 50%;
}
.glass-select option {
    background-color: #fff;
    color: #333;
    padding: 10px;
}

/* 文本域 */
.glass-textarea {
    resize: vertical;
    line-height: 1.6;
    min-height: 250px;
}

/* 错误提示 */
.error-text {
    color: #9e1b1b;
    display: block;
    margin-top: 8px;
    font-weight: bold;
    font-size: 0.9em;
}
.glass-alert {
    background: rgba(158, 27, 27, 0.05);
    border-left: 4px solid #9e1b1b;
    color: #9e1b1b;
    padding: 15px 20px;
    font-weight: bold;
    border-radius: 2px;
    margin-bottom: 30px;
}

/* 底部操作区 */
.form-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 15px;
    margin-top: 40px;
    padding-top: 25px;
    border-top: 1px dashed #eee;
}

/* 按钮通用 */
.btn-hero-primary { 
    background: #222; 
    color: #fff; 
    border: 1px solid #222; 
    padding: 12px 30px;
    font-size: 1.05em;
    font-weight: bold;
    font-family: 'Noto Serif SC', serif;
    letter-spacing: 2px;
    border-radius: 2px;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-hero-primary:hover { 
    background: #9e1b1b; 
    border-color: #9e1b1b; 
}

.btn-ink-outline { 
    background: transparent; 
    color: #666; 
    border: 1px solid #ccc; 
    padding: 12px 25px; 
    text-decoration: none; 
    font-weight: bold; 
    font-family: sans-serif;
    letter-spacing: 1px;
    border-radius: 2px; 
    transition: 0.3s; 
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-ink-outline:hover { 
    border-color: #222; 
    color: #222; 
    background: rgba(0,0,0,0.03);
}

@media (max-width: 768px) {
    .glass-card { padding: 30px 20px; }
    .card-glass-title { font-size: 1.8em; }
    .form-actions { flex-direction: column-reverse; gap: 15px; }
    .form-actions > * { width: 100%; text-align: center; justify-content: center; }
}
</style>

<?php include 'includes/footer.php'; ?>