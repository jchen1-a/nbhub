<?php
// reset-password.php - 100% 完整版 (重置新密码页面)
require_once 'config.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$token = $_GET['token'] ?? '';
$errors = [];
$is_valid_token = false;
$reset_email = '';

if (empty($token)) {
    $errors['general'] = "Enlace inválido o ausente.";
} else {
    try {
        $pdo = db_connect();
        // 验证 Token 是否存在且在1小时内有效
        $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND created_at >= NOW() - INTERVAL 1 HOUR");
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        
        if ($row) {
            $is_valid_token = true;
            $reset_email = $row['email'];
        } else {
            $errors['general'] = "El enlace de restablecimiento es inválido o ha caducado (válido por 1 hora). Por favor, solicita uno nuevo.";
        }
    } catch (Exception $e) {
        $errors['general'] = "Error del servidor: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_valid_token) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $errors['password'] = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = "Las contraseñas no coinciden.";
    }

    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // 更新密码
            $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update_stmt->execute([$hashed_password, $reset_email]);
            
            // 删除已使用的 Token
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$reset_email]);
            
            $_SESSION['flash_message'] = "¡Tu contraseña ha sido restablecida con éxito! Ya puedes iniciar sesión.";
            header("Location: login.php");
            exit;
            
        } catch (Exception $e) {
            $errors['general'] = "Error al actualizar la contraseña: " . $e->getMessage();
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container" style="padding: 60px 20px; display: flex; justify-content: center; min-height: 70vh; align-items: center;">
    <div class="card" style="width: 100%; max-width: 450px; padding: 40px; text-align: center; border-top: 5px solid var(--accent);">
        <i class="fas fa-key" style="font-size: 4em; color: var(--accent); margin-bottom: 20px;"></i>
        <h2 class="ink-black" style="margin-top: 0;">Nueva Contraseña</h2>

        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-error"><?php echo $errors['general']; ?></div>
            <div style="margin-top: 20px;">
                <a href="forgot-password.php" class="btn-outline">Solicitar nuevo enlace</a>
            </div>
        <?php elseif ($is_valid_token): ?>
            <p style="color: #666; margin-bottom: 30px;">Por favor, introduce tu nueva contraseña.</p>

            <form method="POST" style="text-align: left;">
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; display: block; margin-bottom: 8px; color: var(--text);">Nueva Contraseña</label>
                    <div style="position: relative;">
                        <i class="fas fa-lock" style="position: absolute; left: 15px; top: 14px; color: #888;"></i>
                        <input type="password" name="password" required placeholder="Mínimo 6 caracteres" style="width: 100%; padding: 12px 15px 12px 45px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px;">
                    </div>
                    <?php if(isset($errors['password'])) echo "<small style='color: var(--accent);'>{$errors['password']}</small>"; ?>
                </div>

                <div style="margin-bottom: 30px;">
                    <label style="font-weight: bold; display: block; margin-bottom: 8px; color: var(--text);">Confirmar Contraseña</label>
                    <div style="position: relative;">
                        <i class="fas fa-lock" style="position: absolute; left: 15px; top: 14px; color: #888;"></i>
                        <input type="password" name="confirm_password" required placeholder="Repite tu nueva contraseña" style="width: 100%; padding: 12px 15px 12px 45px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px;">
                    </div>
                    <?php if(isset($errors['confirm_password'])) echo "<small style='color: var(--accent);'>{$errors['confirm_password']}</small>"; ?>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; padding: 14px; font-size: 1.1em;">
                    <i class="fas fa-save"></i> Guardar Contraseña
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>