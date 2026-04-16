<?php
// edit-guide.php - 编辑攻略 (时光机快照保存 + CSRF 防护 + 暗黑武侠UI)
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
$difficulties = [
    'beginner' => 'Principiante',
    'intermediate' => 'Intermedio',
    'advanced' => 'Avanzado'
];

try {
    $pdo = db_connect();
    
    // 1. 验证攻略是否存在，及权限
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $article = $stmt->fetch();
    
    if (!$article) {
        $_SESSION['flash_error'] = "La guía no existe o no tienes permiso para editarla.";
        header("Location: guides.php");
        exit;
    }
} catch (Exception $e) {
    die("Error de base de datos: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors['system'] = 'Error de seguridad (CSRF). Recarga la página.';
    } else {
        $title = sanitize($_POST['title'] ?? '');
        $category = sanitize($_POST['category'] ?? 'general');
        $difficulty = sanitize($_POST['difficulty'] ?? 'beginner');
        $content = trim($_POST['content'] ?? '');
        $video_url = sanitize($_POST['video_url'] ?? '');
        $edit_summary = sanitize($_POST['edit_summary'] ?? 'Edición general'); // 版本修改备注
        
        if (empty($title)) $errors['title'] = "El título es obligatorio.";
        if (empty($content)) $errors['content'] = "El contenido no puede estar vacío.";
        
        if (empty($errors)) {
            // P1-5: 核心逻辑 - 在更新前，将【旧版本】存入快照表
            $snapshot_stmt = $pdo->prepare("INSERT INTO wiki_article_versions (article_id, user_id, title, content, edit_summary, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $snapshot_stmt->execute([$id, $article['user_id'], $article['title'], $article['content'], $edit_summary]);

            // 执行真正的更新
            $update_stmt = $pdo->prepare("UPDATE articles SET title=?, category=?, difficulty=?, content=?, video_url=? WHERE id=?");
            $update_stmt->execute([$title, $category, $difficulty, $content, $video_url, $id]);
            
            $_SESSION['flash_message'] = "¡Guía actualizada y versión anterior guardada en el historial!";
            header("Location: article.php?id=" . $id);
            exit;
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="nj-static-bg"></div>

<div class="nj-container">
    <header class="nj-header">
        <div class="nj-header-titles">
            <h1>MARTIAL ARCHIVES</h1>
            <h2>Editar Guía</h2>
        </div>
    </header>

    <div class="nj-layout">
        <main class="nj-main" style="max-width: 900px; margin: 0 auto;">
            
            <?php if (!empty($errors)): ?>
                <div class="nj-alert">
                    <i class="fas fa-exclamation-triangle"></i> Por favor, corrige los errores del formulario.
                    <?php if(isset($errors['system'])) echo "<br><strong>".$errors['system']."</strong>"; ?>
                </div>
            <?php endif; ?>

            <div class="nj-sidebar-card">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                    <div style="margin-bottom: 25px;">
                        <label class="nj-label">Título de la Guía *</label>
                        <input type="text" name="title" class="nj-input" value="<?php echo htmlspecialchars($_POST['title'] ?? $article['title']); ?>" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                        <div>
                            <label class="nj-label">Categoría *</label>
                            <select name="category" class="nj-input">
                                <?php 
                                $curr_cat = $_POST['category'] ?? $article['category'];
                                foreach($categories as $k => $v) {
                                    $sel = ($curr_cat == $k) ? 'selected' : '';
                                    echo "<option value='$k' $sel>$v</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="nj-label">Dificultad *</label>
                            <select name="difficulty" class="nj-input">
                                <?php 
                                $curr_diff = $_POST['difficulty'] ?? $article['difficulty'];
                                foreach($difficulties as $k => $v) {
                                    $sel = ($curr_diff == $k) ? 'selected' : '';
                                    echo "<option value='$k' $sel>$v</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label class="nj-label">URL del Video (Opcional - YouTube)</label>
                        <input type="url" name="video_url" class="nj-input" placeholder="Ej: https://www.youtube.com/watch?v=..." value="<?php echo htmlspecialchars($_POST['video_url'] ?? $article['video_url']); ?>">
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label class="nj-label">Resumen de la Edición (Opcional)</label>
                        <input type="text" name="edit_summary" class="nj-input" placeholder="Ej: Corregidos errores de ortografía, añadida sección de combos..." maxlength="150">
                        <small style="color: var(--nj-text-muted); font-size: 0.8em; margin-top: 5px; display: block;">Esto ayudará a identificar esta versión en el historial.</small>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <label class="nj-label">Contenido de la Guía *</label>
                        <textarea name="content" class="nj-input" rows="15" required style="resize:vertical;"><?php echo htmlspecialchars($_POST['content'] ?? $article['content']); ?></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 15px;">
                        <a href="article.php?id=<?php echo $id; ?>" class="nj-btn-secondary" style="width: auto;">Cancelar</a>
                        <button type="submit" class="nj-btn-primary" style="width: auto;"><i class="fas fa-save"></i> Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<style>
:root {
    --nj-bg: #0B0A0A; --nj-module: #161413; --nj-module-hover: #1E1B19;    
    --nj-red: #D12323; --nj-gold: #CCA677; --nj-border: #2D2926;          
    --nj-text-main: #E6E4DF; --nj-text-muted: #8F98A0; 
    --font-main: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}
body { background-color: var(--nj-bg) !important; color: var(--nj-text-main); font-family: var(--font-main); margin: 0; padding: 0; }
.nj-static-bg { position: fixed; inset: 0; z-index: -10; background-color: var(--nj-bg); background-image: radial-gradient(circle at 10% 20%, rgba(209, 35, 35, 0.04), transparent 50%), radial-gradient(circle at 90% 80%, rgba(204, 166, 119, 0.03), transparent 50%); background-blend-mode: screen; }
.nj-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; min-height: 100vh; }
.nj-header { margin-top: 40px; margin-bottom: 30px; border-bottom: 1px solid var(--nj-border); padding-bottom:20px;}
.nj-header-titles h1 { font-size: 1em; color: var(--nj-text-muted); font-weight: normal; margin: 0 0 5px 0; letter-spacing: 1px;}
.nj-header-titles h2 { font-size: 1.6em; color: var(--nj-text-main); font-weight: 600; margin: 0;}
.nj-sidebar-card { background: var(--nj-module); border: 1px solid var(--nj-border); border-radius: 8px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);}
.nj-label { display: block; margin-bottom: 10px; color: var(--nj-gold); font-size: 0.85em; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;}
.nj-input { width: 100%; padding: 15px; background: rgba(0,0,0,0.4); border: 1px solid var(--nj-border); border-radius: 6px; color: var(--nj-text-main); font-family: var(--font-main); outline: none; transition: 0.2s; box-sizing: border-box; font-size: 1em;}
.nj-input:focus { border-color: var(--nj-gold); background: var(--nj-bg);}
.nj-btn-primary { display: inline-block; text-align: center; background: var(--nj-red); color: #fff; padding: 12px 25px; text-decoration: none; font-size: 0.95em; border-radius: 6px; font-weight: bold; transition: background 0.2s; border: none; cursor: pointer;}
.nj-btn-primary:hover { background: #b81c1c; }
.nj-btn-secondary { display: inline-block; text-align: center; background: transparent; border: 1px solid var(--nj-border); color: var(--nj-text-main); padding: 12px 25px; text-decoration: none; font-size: 0.95em; border-radius: 6px; transition: 0.2s; cursor: pointer;}
.nj-btn-secondary:hover { background: var(--nj-module-hover); border-color: var(--nj-text-muted); }
.nj-alert { padding: 15px; background: rgba(209, 35, 35, 0.1); border: 1px solid var(--nj-red); color: var(--nj-text-main); border-radius: 8px; margin-bottom: 20px; font-size: 0.9em;}
</style>

<?php include 'includes/footer.php'; ?>