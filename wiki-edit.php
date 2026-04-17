<?php
// wiki-edit.php - 编辑Wiki词条 (浅色水墨白灰红版)
require_once 'config.php';
require_login();

$id = intval($_GET['id'] ?? 0);
$errors = [];
$article = null;
$categories = [];

try {
    $pdo = db_connect();
    $categories = $pdo->query("SELECT id, name FROM wiki_categories ORDER BY name")->fetchAll();
    
    // 获取当前文章
    $stmt = $pdo->prepare("SELECT * FROM wiki_articles WHERE id = ?");
    $stmt->execute([$id]);
    $article = $stmt->fetch();
    
    // 如果没有传 ID，或者文章不存在
    if (!$article) {
        $_SESSION['flash_error'] = "Artículo no encontrado. Por favor selecciona uno para editar.";
        header("Location: wiki.php");
        exit;
    }
} catch (Exception $e) {
    die("Error de base de datos: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    if (empty($title)) $errors['title'] = 'El título es obligatorio.';
    if (empty($content)) $errors['content'] = 'El contenido es obligatorio.';

    if (empty($errors)) {
        try {
            // 更新词条
            $stmt = $pdo->prepare("
                UPDATE wiki_articles 
                SET title = ?, content = ?, category_id = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$title, $content, $category_id, $id]);
            
            $_SESSION['flash_message'] = '¡Artículo actualizado con éxito!';
            // 更新成功后，直接跳转回该词条的详情页
            header("Location: wiki-article.php?id=" . $id); 
            exit();
            
        } catch (Exception $e) {
            $errors['general'] = 'Error al actualizar: ' . $e->getMessage();
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="light-theme-bg"></div>

<div class="edit-container">
    
    <div class="edit-card">
        <h1 class="edit-title"><i class="fas fa-pen-nib"></i> Modificar Rollo</h1>
        <p class="edit-subtitle">Editando el registro: <strong style="color:#222;"><?php echo htmlspecialchars($article['title']); ?></strong></p>

        <?php if (isset($errors['general'])): ?>
            <div class="alert-box alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $errors['general']; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Título del Artículo (词条名称)</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? $article['title']); ?>" required class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Categoría (所属分类)</label>
                <select name="category_id" required class="form-control">
                    <?php 
                    $current_cat = $_POST['category_id'] ?? $article['category_id'];
                    foreach ($categories as $cat): 
                    ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $current_cat == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Contenido (卷宗内容)</label>
                <textarea name="content" rows="18" required class="form-control" style="resize:vertical; line-height: 1.6;"><?php echo htmlspecialchars($_POST['content'] ?? $article['content']); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="button" onclick="history.back()" class="btn-cancel">Cancelar</button>
                <button type="submit" class="btn-submit"><i class="fas fa-stamp"></i> Actualizar</button>
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
    min-height: 100vh; /* 让底部 footer 乖乖待在最下面 */
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
.edit-title { color: #222222; font-size: 2.2em; margin: 0 0 10px 0; font-weight: 900; letter-spacing: 2px;}
.edit-subtitle { color: #888888; font-size: 0.95em; font-family: sans-serif; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px dashed #eee;}

/* 错误提示 */
.alert-box { padding: 15px; border-radius: 4px; margin-bottom: 25px; font-family: sans-serif; font-size: 0.9em; font-weight: bold;}
.alert-error { background: rgba(158,27,27,0.05); border: 1px solid #9e1b1b; color: #9e1b1b; }

/* 表单组 */
.form-group { margin-bottom: 25px; }
.form-label { display: block; font-weight: bold; color: #555; margin-bottom: 10px; font-size: 0.95em; letter-spacing: 1px; font-family: sans-serif;}

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

/* 操作按钮 */
.form-actions { display: flex; justify-content: flex-end; align-items: center; gap: 15px; margin-top: 40px; padding-top: 25px; border-top: 1px dashed #eee;}

.btn-cancel { 
    background: transparent; padding: 12px 25px; border: 1px solid #cccccc; color: #666; 
    border-radius: 2px; font-family: sans-serif; font-weight: bold; font-size: 0.95em; 
    cursor: pointer; transition: 0.3s; letter-spacing: 1px;
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
    .btn-cancel, .btn-submit { width: 100%; justify-content: center; }
}
</style>

<?php include 'includes/footer.php'; ?>