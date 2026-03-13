<?php
// login.php - 100% 完整版 (终极兼容版：兼容明文/MD5密码，并补全所有Session变量)
require_once 'config.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitize($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier)) $errors['identifier'] = "El usuario o correo es obligatorio.";
    if (empty($password)) $errors['password'] = "La contraseña es obligatoria.";

    if (empty($errors)) {
        try {
            $pdo = db_connect();
            // 支持通过用户名或邮箱登录
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch();

            if ($user) {
                $login_success = false;

                // 1. 标准安全验证 (新注册用户或刚重置密码的用户)
                if (password_verify($password, $user['password'])) {
                    $login_success = true;
                } 
                // 2. 兼容旧版 MD5 加密
                elseif (md5($password) === $user['password']) {
                    $login_success = true;
                    // 无缝自动升级为更安全的哈希算法
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$new_hash, $user['id']]);
                } 
                // 3. 兼容最初期的明文密码
                elseif ($password === $user['password']) {
                    $login_success = true;
                    // 无缝自动升级为更安全的哈希算法
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$new_hash, $user['id']]);
                }

                if ($login_success) {
                    // 暴力补全所有可能用到的 Session 变量，绝对防止任何页面报错或踢人
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['user_name'] = $user['username'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['name']      = $user['username'];
                    $_SESSION['user']      = $user; // 整个数组存入，兼容性拉满
                    
                    $_SESSION['flash_message'] = "¡Bienvenido de nuevo, " . $user['username'] . "!";
                    
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $errors['general'] = "Credenciales incorrectas.";
                }
            } else {
                $errors['general'] = "Credenciales incorrectas.";
            }
        } catch (Exception $e) {
            $errors['general'] = "Error del servidor: " . $e->getMessage();
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container" style="padding: 60px 20px; display: flex; justify-content: center; min-height: 75vh; align-items: center;">
    <div class="card" style="width: 100%; max-width: 400px; padding: 40px; border-top: 5px solid var(--primary);">
        <div style="text-align: center; margin-bottom: 30px;">
            <i class="fas fa-user-ninja" style="font-size: 3.5em; color: var(--primary); margin-bottom: 15px;"></i>
            <h2 class="ink-black" style="margin: 0;">Iniciar Sesión</h2>
        </div>

        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-error"><?php echo $errors['general']; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom: 20px;">
                <label style="font-weight: bold; display: block; margin-bottom: 8px; color: var(--text);">Usuario o Correo</label>
                <div style="position: relative;">
                    <i class="fas fa-user" style="position: absolute; left: 15px; top: 14px; color: #888;"></i>
                    <input type="text" name="identifier" required value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>" placeholder="Tu apodo o email" style="width: 100%; padding: 12px 15px 12px 45px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px;">
                </div>
                <?php if(isset($errors['identifier'])) echo "<small style='color: var(--accent);'>{$errors['identifier']}</small>"; ?>
            </div>

            <div style="margin-bottom: 25px;">
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px;">
                    <label style="font-weight: bold; color: var(--text);">Contraseña</label>
                    <a href="forgot-password.php" style="font-size: 0.85em; font-weight: bold;">¿Olvidaste tu contraseña?</a>
                </div>
                <div style="position: relative;">
                    <i class="fas fa-lock" style="position: absolute; left: 15px; top: 14px; color: #888;"></i>
                    <input type="password" name="password" required placeholder="Tu clave secreta" style="width: 100%; padding: 12px 15px 12px 45px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px;">
                </div>
                <?php if(isset($errors['password'])) echo "<small style='color: var(--accent);'>{$errors['password']}</small>"; ?>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; padding: 14px; font-size: 1.1em;">
                <i class="fas fa-sign-in-alt"></i> Entrar al Hub
            </button>
        </form>

        <div style="text-align: center; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
            <span style="color: #666;">¿No tienes cuenta?</span> 
            <a href="register.php" style="font-weight: bold; color: var(--accent);">Regístrate aquí</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>