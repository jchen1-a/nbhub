<?php
// wiki-new.php - 创建Wiki新词条
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
    if (empty($formData['category_id'])) $errors['category_id'] = 'Selecciona una categoría.';
    if (empty($formData['content'])) $errors['content'] = 'El contenido es obligatorio.';

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
            
            $_SESSION['flash_message'] = '¡Artículo de la wiki creado con éxito!';
            header("Location: wiki.php"); // 创建成功后返回 wiki 首页
            exit();
            
        } catch (Exception $e) {
            $errors['general'] = 'Error al guardar: ' . $e->getMessage();
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container" style="padding: 40px 20px; max-width: 900px;">
    <div style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
        <h1 style="color: var(--primary); margin-bottom: 20px;"><i class="fas fa-plus-circle"></i> Crear Artículo Wiki</h1>
        <p style="color: #666; margin-bottom: 30px;">Contribuye al conocimiento de la comunidad de Naraka.</p>

        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-error"><?php echo $errors['general']; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom: 20px;">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Título del Artículo</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($formData['title']); ?>" required
                       style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                <?php if(isset($errors['title'])) echo "<small style='color:red;'>{$errors['title']}</small>"; ?>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Categoría</label>
                <select name="category_id" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                    <option value="">-- Selecciona una categoría --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $formData['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if(isset($errors['category_id'])) echo "<small style='color:red;'>{$errors['category_id']}</small>"; ?>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Contenido</label>
                <textarea name="content" rows="15" required
                          style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; font-family:inherit; resize:vertical;"><?php echo htmlspecialchars($formData['content']); ?></textarea>
                <?php if(isset($errors['content'])) echo "<small style='color:red;'>{$errors['content']}</small>"; ?>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 15px;">
                <a href="wiki.php" style="padding: 12px 25px; border: 2px solid #ddd; color: #666; text-decoration: none; border-radius: 8px; font-weight: bold;">Cancelar</a>
                <button type="submit" style="padding: 12px 25px; background: #00adb5; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">Guardar Artículo</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>