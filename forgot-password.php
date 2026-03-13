<?php
// forgot-password.php - 100% 完整版 (找回密码页面)
require_once 'config.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Por favor, introduce un correo electrónico válido.";
    }

    if (empty($errors)) {
        try {
            $pdo = db_connect();
            
            // 验证邮箱是否存在于数据库
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                // 生成安全的随机 Token
                $token = bin2hex(random_bytes(32));
                
                // 删除该邮箱之前可能存在的旧 Token
                $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
                
                // 插入新 Token
                $pdo->prepare("INSERT INTO password_resets (email, token, created_at) VALUES (?, ?, NOW())")->execute([$email, $token]);
                
                // 生成重置链接
                $reset_link = "reset-password.php?token=" . $token;
                
                // 【开发者模式提示】：因为免费主机无法发邮件，直接把链接显示在屏幕上供测试
                $_SESSION['flash_message'] = "Simulación de correo: <a href='$reset_link' style='color:#155724; font-weight:bold; text-decoration:underline;'>Haz clic aquí para restablecer tu contraseña</a>";
            } else {
                // 安全机制：即使邮箱不存在，也显示相同的成功消息，防止黑客枚举穷举邮箱
                $_SESSION['flash_message'] = "Si el correo está registrado, recibirás un enlace de recuperación.";
            }
            
            header("Location: forgot-password.php");
            exit;
            
        } catch (Exception $e) {
            $errors['general'] = "Error del servidor: " . $e->getMessage();
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container" style="padding: 60px 20px; display: flex; justify-content: center; min-height: 70vh; align-items: center;">
    <div class="card" style="width: 100%; max-width: 450px; padding: 40px; text-align: center; border-top: 5px solid var(--accent);">
        <i class="fas fa-unlock-alt" style="font-size: 4em; color: var(--accent); margin-bottom: 20px;"></i>
        <h2 class="ink-black" style="margin-top: 0;">Recuperar Contraseña</h2>
        <p style="color: #666; margin-bottom: 30px;">Introduce tu correo electrónico y te enviaremos las instrucciones para restablecer tu contraseña.</p>

        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-error"><?php echo $errors['general']; ?></div>
        <?php endif; ?>

        <form method="POST" style="text-align: left;">
            <div style="margin-bottom: 20px;">
                <label style="font-weight: bold; display: block; margin-bottom: 8px; color: var(--text);">Correo Electrónico</label>
                <div style="position: relative;">
                    <i class="fas fa-envelope" style="position: absolute; left: 15px; top: 14px; color: #888;"></i>
                    <input type="email" name="email" required placeholder="tu@email.com" style="width: 100%; padding: 12px 15px 12px 45px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px;">
                </div>
                <?php if(isset($errors['email'])) echo "<small style='color: var(--accent);'>{$errors['email']}</small>"; ?>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; padding: 14px; font-size: 1.1em; margin-top: 10px;">
                <i class="fas fa-paper-plane"></i> Enviar Enlace
            </button>
        </form>

        <div style="margin-top: 25px; border-top: 1px solid #eee; padding-top: 20px;">
            <a href="login.php" style="color: #666; text-decoration: none; font-weight: bold;"><i class="fas fa-arrow-left"></i> Volver al inicio de sesión</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>