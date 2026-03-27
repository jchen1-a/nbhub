<?php
// edit-post.php - 编辑论坛帖子 (适配官方暖暗色调)
require_once 'config.php';
require_login();

$id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];
$errors = [];

$categories = [
    'general' => 'Discusión General',
    'guias' => 'Guías y Consejos',
    'equipos' => 'Búsqueda de Equipo',
    'dudas' => 'Dudas y Preguntas',
    'offtopic' => 'Off-Topic'
];

try {
    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT * FROM forum_posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $post = $stmt->fetch();
    
    if (!$post) {
        $_SESSION['flash_error'] = "Tema no encontrado o no tienes permiso para editarlo.";
        header("Location: forum.php");
        exit;
    }
} catch (Exception $e) { 
    die("Error: " . $e->getMessage()); 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $category = sanitize($_POST['category'] ?? 'general');
    $content = trim($_POST['content'] ?? '');

    if (empty($title)) $errors['title'] = 'El título es obligatorio.';
    if (empty($content)) $errors['content'] = 'El contenido es obligatorio.';

    if (empty($errors)) {
        $pdo->prepare("UPDATE forum_posts SET title=?, category=?, content=? WHERE id=?")->execute([$title, $category, $content, $id]);
        $_SESSION['flash_message'] = '¡Tema actualizado exitosamente!';
        header("Location: view-post.php?id=" . $id);
        exit;
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="nj-static-bg"></div>

<div class="nj-container">
    <header class="nj-header">
        <div class="nj-header-titles">
            <h1>MARTIAL ARCHIVES</h1>
            <h2>Editar discusión</h2>
        </div>
    </header>

    <div class="nj-layout">
        <main class="nj-main" style="max-width: 900px; margin: 0 auto;">
            
            <?php if (!empty($errors)): ?>
                <div class="nj-alert"><i class="fas fa-exclamation-triangle"></i> Revisa los campos requeridos.</div>
            <?php endif; ?>

            <div class="nj-sidebar-card">
                <form method="POST">
                    <div style="margin-bottom: 25px;">
                        <label class="nj-label">Título del tema</label>
                        <input type="text" name="title" class="nj-input" value="<?php echo htmlspecialchars($_POST['title'] ?? $post['title']); ?>" required>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label class="nj-label">Categoría</label>
                        <select name="category" class="nj-input">
                            <?php 
                            $curr_cat = $_POST['category'] ?? $post['category'];
                            foreach($categories as $k => $v) {
                                $selected = ($curr_cat == $k) ? 'selected' : '';
                                echo "<option value='{$k}' {$selected}>{$v}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <label class="nj-label">Mensaje</label>
                        <textarea name="content" class="nj-input" rows="12" required style="resize:vertical;"><?php echo htmlspecialchars($_POST['content'] ?? $post['content']); ?></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 15px;">
                        <button type="button" onclick="history.back()" class="nj-btn-secondary" style="width: auto;">Cancelar</button>
                        <button type="submit" class="nj-btn-primary" style="width: auto;"><i class="fas fa-save"></i> Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <footer class="nj-footer">
        <p>NARAKA BLADEPOINT WUXIA ARCHIVES © 2026</p>
    </footer>
</div>

<style>
/* 与 new-post.php 共用样式代码块 */
:root {
    --nj-bg: #0B0A0A; --nj-module: #161413; --nj-module-hover: #1E1B19;    
    --nj-red: #D12323; --nj-gold: #CCA677; --nj-border: #2D2926;          
    --nj-text-main: #E6E4DF; --nj-text-muted: #8F98A0; 
    --font-main: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}
body { background-color: var(--nj-bg) !important; color: var(--nj-text-main); font-family: var(--font-main); margin: 0; padding: 0; overflow-x: hidden; }
.nj-static-bg { position: fixed; inset: 0; z-index: -10; background-color: var(--nj-bg); background-image: radial-gradient(circle at 10% 20%, rgba(209, 35, 35, 0.04), transparent 50%), radial-gradient(circle at 90% 80%, rgba(204, 166, 119, 0.03), transparent 50%); background-blend-mode: screen; }
.nj-static-bg::after { content: ''; position: absolute; inset: 0; background: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.04'/%3E%3C/svg%3E"); pointer-events: none; }
.nj-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; min-height: 100vh; display: flex; flex-direction: column;}
.nj-header { margin-top: 40px; margin-bottom: 30px; border-bottom: 1px solid var(--nj-border); padding-bottom:20px;}
.nj-header-titles h1 { font-size: 1em; color: var(--nj-text-muted); font-weight: normal; margin: 0 0 5px 0; letter-spacing: 1px;}
.nj-header-titles h2 { font-size: 1.6em; color: var(--nj-text-main); font-weight: 600; margin: 0;}
.nj-layout { display: flex; flex: 1; }
.nj-main { flex: 1; width: 100%; }
.nj-sidebar-card { background: var(--nj-module); border: 1px solid var(--nj-border); border-radius: 8px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);}
.nj-label { display: block; margin-bottom: 10px; color: var(--nj-gold); font-size: 0.85em; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;}
.nj-input { width: 100%; padding: 15px; background: rgba(0,0,0,0.4); border: 1px solid var(--nj-border); border-radius: 6px; color: var(--nj-text-main); font-family: var(--font-main); outline: none; transition: 0.2s; box-sizing: border-box; font-size: 1em;}
.nj-input:focus { border-color: var(--nj-gold); background: var(--nj-bg);}
.nj-btn-primary { display: inline-block; text-align: center; background: var(--nj-red); color: #fff; padding: 12px 25px; text-decoration: none; font-size: 0.95em; border-radius: 6px; font-weight: bold; transition: background 0.2s; border: none; cursor: pointer;}
.nj-btn-primary:hover { background: #b81c1c; }
.nj-btn-secondary { display: inline-block; text-align: center; background: transparent; border: 1px solid var(--nj-border); color: var(--nj-text-main); padding: 12px 25px; text-decoration: none; font-size: 0.95em; border-radius: 6px; transition: 0.2s; cursor: pointer;}
.nj-btn-secondary:hover { background: var(--nj-module-hover); border-color: var(--nj-text-muted); }
.nj-alert { padding: 15px; background: rgba(209, 35, 35, 0.1); border: 1px solid var(--nj-red); color: var(--nj-text-main); border-radius: 8px; margin-bottom: 20px; font-size: 0.9em;}
.nj-footer { margin-top: 60px; padding: 40px 0; border-top: 1px solid var(--nj-border); text-align: center; color: var(--nj-text-muted); font-size: 0.8em; letter-spacing: 1px;}
</style>

<?php include 'includes/footer.php'; ?>