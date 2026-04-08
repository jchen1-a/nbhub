<?php
// new-post.php - 创建论坛新主题 (接入 CSRF 防护)
require_once 'config.php';
require_login();

$errors = [];
$formData = [
    'title' => '',
    'category' => 'general',
    'content' => ''
];

$categories = [
    'general' => 'Discusión General',
    'guias' => 'Guías y Consejos',
    'equipos' => 'Búsqueda de Equipo',
    'dudas' => 'Dudas y Preguntas',
    'offtopic' => 'Off-Topic'
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

<div class="nj-static-bg"></div>

<div class="nj-container">
    <header class="nj-header">
        <div class="nj-header-titles">
            <h1>MARTIAL ARCHIVES</h1>
            <h2>Iniciar nueva discusión</h2>
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
                        <label class="nj-label">Título del tema</label>
                        <input type="text" name="title" class="nj-input" value="<?php echo htmlspecialchars($formData['title']); ?>" required>
                        <?php if(isset($errors['title'])) echo "<div style='color:var(--nj-red); font-size:0.85em; margin-top:5px;'>{$errors['title']}</div>"; ?>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label class="nj-label">Categoría</label>
                        <select name="category" class="nj-input">
                            <?php foreach ($categories as $val => $label): ?>
                                <option value="<?php echo $val; ?>" <?php echo $formData['category'] == $val ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <label class="nj-label">Mensaje</label>
                        <textarea name="content" class="nj-input" rows="12" required style="resize:vertical;"><?php echo htmlspecialchars($formData['content']); ?></textarea>
                        <?php if(isset($errors['content'])) echo "<div style='color:var(--nj-red); font-size:0.85em; margin-top:5px;'>{$errors['content']}</div>"; ?>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 15px;">
                        <a href="forum.php" class="nj-btn-secondary" style="width: auto;">Cancelar</a>
                        <button type="submit" class="nj-btn-primary" style="width: auto;"><i class="fas fa-paper-plane"></i> Publicar Tema</button>
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