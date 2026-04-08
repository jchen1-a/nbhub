<?php
// reset-password.php - 100% 完整版 (验证 Token 并修改密码)
require_once 'config.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$errors = [];
$valid_token = false;
$user_id = 0;

if (empty($token)) {
    $errors['general'] = "Enlace inválido o no proporcionado.";
} else {
    try {
        $pdo = db_connect();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            $valid_token = true;
            $user_id = $user['id'];
        } else {
            $errors['general'] = "El enlace de recuperación es inválido o ha caducado. Por favor, solicita uno nuevo.";
        }
    } catch (Exception $e) {
        $errors['general'] = "Error de base de datos.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors['general'] = "Error de seguridad (CSRF). Por favor, recarga e inténtalo de nuevo.";
    } else {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (empty($password) || strlen($password) < 6) {
            $errors['password'] = "La contraseña debe tener al menos 6 caracteres.";
        } elseif ($password !== $confirm) {
            $errors['confirm_password'] = "Las contraseñas no coinciden.";
        }
        
        if (empty($errors)) {
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?")->execute([$new_hash, $user_id]);
            
            $_SESSION['flash_message'] = "¡Tu contraseña ha sido actualizada con éxito! Ya puedes iniciar sesión.";
            header("Location: login.php");
            exit;
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="auth-wrap">
    <div class="auth-box">
        <div class="auth-top">
            <h1 class="ink-title">NUEVA CLAVE</h1>
            <p class="ink-subtitle">Asegura tu cuenta</p>
        </div>

        <?php if (isset($errors['general'])): ?>
            <div class="ink-alert"><?php echo $errors['general']; ?></div>
            <?php if (!$valid_token): ?>
                <div class="ink-footer">
                    <a href="forgot-password.php" class="ink-btn-main" style="text-decoration:none; display:inline-block; box-sizing:border-box;">Solicitar nuevo enlace</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($valid_token): ?>
            <form method="POST" class="ink-form">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="ink-group">
                    <label>Nueva Contraseña</label>
                    <input type="password" name="password" required>
                    <?php if (isset($errors['password'])): ?>
                        <span class="ink-err"><?php echo $errors['password']; ?></span>
                    <?php endif; ?>
                </div>

                <div class="ink-group">
                    <label>Confirmar Contraseña</label>
                    <input type="password" name="confirm_password" required>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <span class="ink-err"><?php echo $errors['confirm_password']; ?></span>
                    <?php endif; ?>
                </div>

                <div class="ink-footer">
                    <button type="submit" class="ink-btn-main"><i class="fas fa-key"></i> ACTUALIZAR CONTRASEÑA</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<style>
/* 复用 forgot-password.php 的样式 */
.auth-wrap { min-height: calc(100vh - 75px); display: flex; align-items: center; justify-content: center; padding: 40px 20px; background: radial-gradient(circle at center, rgba(201, 20, 20, 0.03) 0%, transparent 70%); }
.auth-box { width: 100%; max-width: 450px; background: var(--nj-module); padding: 50px 40px; box-shadow: 0 15px 50px rgba(0,0,0,0.5); border: 1px solid var(--nj-border); position: relative; border-radius: 6px; }
.auth-box::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--nj-red); border-radius: 6px 6px 0 0;}
.auth-top { text-align: center; margin-bottom: 40px; }
.ink-title { font-size: 1.8em; margin: 0; color: var(--nj-text-main); letter-spacing: 2px; }
.ink-subtitle { font-size: 0.8em; color: var(--nj-text-muted); text-transform: uppercase; margin-top: 5px; letter-spacing: 2px; }
.ink-form .ink-group { margin-bottom: 25px; }
.ink-form label { display: block; font-weight: 700; font-size: 0.8em; color: var(--nj-gold); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
.ink-form input { width: 100%; padding: 12px 15px; border: 1px solid var(--nj-border); font-size: 1em; background: rgba(0,0,0,0.4); color: var(--nj-text-main); outline: none; border-radius: 4px; box-sizing: border-box; }
.ink-form input:focus { border-color: var(--nj-gold); }
.ink-err { display: block; color: var(--nj-red); font-size: 0.8em; margin-top: 5px; }
.ink-alert { background: rgba(209, 35, 35, 0.1); color: var(--nj-text-main); padding: 15px; font-size: 0.9em; text-align: center; margin-bottom: 30px; border-left: 3px solid var(--nj-red); line-height: 1.5; }
.ink-footer { text-align: center; margin-top: 30px; }
.ink-btn-main { width: 100%; padding: 15px; background: var(--nj-red); color: #fff; border: none; font-weight: bold; letter-spacing: 1px; cursor: pointer; transition: all 0.2s; border-radius: 4px; }
.ink-btn-main:hover { background: #b81c1c; }
</style>

<?php include 'includes/footer.php'; ?>