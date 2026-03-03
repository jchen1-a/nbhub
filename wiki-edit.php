<?php
// wiki-edit.php - 编辑Wiki词条
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
            // 如果你有 wiki-article.php 可以跳转过去，暂时先跳回首页
            header("Location: wiki.php"); 
            exit();
            
        } catch (Exception $e) {
            $errors['general'] = 'Error al actualizar: ' . $e->getMessage();
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container" style="padding: 40px 20px; max-width: 900px;">
    <div style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
        <h1 style="color: var(--primary); margin-bottom: 20px;"><i class="fas fa-edit"></i> Editar: <?php echo htmlspecialchars($article['title']); ?></h1>
        <p style="color: #666; margin-bottom: 30px;">Mejora esta página de la wiki. Todo el historial de cambios se guarda.</p>

        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-error"><?php echo $errors['general']; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom: 20px;">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Título del Artículo</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? $article['title']); ?>" required
                       style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Categoría</label>
                <select name="category_id" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
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

            <div style="margin-bottom: 20px;">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Contenido</label>
                <textarea name="content" rows="15" required
                          style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; font-family:inherit; resize:vertical; overflow-wrap: break-word;"><?php echo htmlspecialchars($_POST['content'] ?? $article['content']); ?></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 15px;">
                <button type="button" onclick="history.back()" style="padding: 12px 25px; border: 2px solid #ddd; background: transparent; color: #666; border-radius: 8px; font-weight: bold; cursor: pointer;">Cancelar</button>
                <button type="submit" style="padding: 12px 25px; background: var(--success, #28a745); color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">Actualizar Artículo</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>