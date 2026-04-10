<?php
// forgot-password.php - 最终完美版 (24h有效期 + Session 60秒防连点 + 前端防狂点)
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'includes/PHPMailer/Exception.php';
require 'includes/PHPMailer/PHPMailer.php';
require 'includes/PHPMailer/SMTP.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors['general'] = "Error de seguridad (CSRF). Por favor, recarga e inténtalo de nuevo.";
    } else {
        $email = sanitize($_POST['email'] ?? '');
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Introduce un correo electrónico válido.";
        }
        
        // 核心防御：利用 Session 实行 60 秒冷却，完全避开数据库时区问题
        if (isset($_SESSION['last_reset_request']) && (time() - $_SESSION['last_reset_request']) < 60) {
            $success_msg = "Procesando... Por favor, revisa tu bandeja de entrada o espera un minuto para intentar de nuevo.";
        } elseif (empty($errors)) {
            try {
                $pdo = db_connect();
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    $token = bin2hex(random_bytes(32));
                    // 24小时有效期，彻底解决时区问题
                    $expiry_string = date('Y-m-d H:i:s', time() + 86400); 
                    
                    $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?")->execute([$token, $expiry_string, $user['id']]);
                    
                    $reset_link = SITE_URL . "/reset-password.php?token=" . $token;
                    
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USER;
                    $mail->Password   = SMTP_PASS;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = SMTP_PORT;
                    $mail->CharSet    = 'UTF-8';
                    
                    $mail->setFrom(SMTP_USER, SITE_NAME);
                    $mail->addAddress($email);
                    
                    $mail->isHTML(true);
                    $mail->Subject = 'Restablece tu contraseña - ' . SITE_NAME;
                    $mail->Body    = "
                        <div style='background:#161413; color:#E6E4DF; padding:30px; font-family:sans-serif; text-align:center;'>
                            <h2 style='color:#D12323;'>Recuperación de Contraseña</h2>
                            <p>Has solicitado restablecer tu contraseña en el Hub de Naraka.</p>
                            <p>Haz clic en el siguiente botón para crear una nueva:</p>
                            <a href='{$reset_link}' style='display:inline-block; padding:12px 25px; background:#D12323; color:#ffffff; text-decoration:none; font-weight:bold; border-radius:4px; margin:20px 0;'>Restablecer Contraseña</a>
                        </div>
                    ";
                    
                    $mail->send();
                }
                
                // 无论邮箱是否存在，都记录本次请求的时间戳，并显示成功信息
                $_SESSION['last_reset_request'] = time();
                $success_msg = "Se ha enviado un enlace de recuperación a tu correo.";
                
            } catch (Exception $e) {
                $errors['general'] = "Error al enviar el correo. Por favor, contacta con el administrador."; 
            }
        }
    }
}
?>
<?php include 'includes/header.php'; ?>
<div class="auth-wrap">
    <div class="auth-box">
        <div class="auth-top">
            <h1 class="ink-title">RECUPERAR ACCESO</h1>
            <p class="ink-subtitle">Forja una nueva clave secreta</p>
        </div>
        <?php if (isset($errors['general'])): ?>
            <div class="ink-alert"><?php echo $errors['general']; ?></div>
        <?php endif; ?>
        <?php if ($success_msg): ?>
            <div class="ink-alert" style="border-left-color: #CCA677; color: #CCA677; background: rgba(204, 166, 119, 0.05);">
                <i class="fas fa-envelope" style="margin-right:8px;"></i> <?php echo $success_msg; ?>
            </div>
            <div class="ink-footer">
                <a href="login.php" class="ink-btn-main" style="text-decoration:none; display:inline-block; box-sizing:border-box;">Volver al Inicio de Sesión</a>
            </div>
        <?php else: ?>
            <form method="POST" class="ink-form" id="forgotForm">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <div class="ink-group" style="margin-bottom: 30px;">
                    <label>Correo Electrónico de la Cuenta</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="Introduce tu correo">
                    <?php if (isset($errors['email'])): ?>
                        <span class="ink-err"><?php echo $errors['email']; ?></span>
                    <?php endif; ?>
                </div>
                <div class="ink-footer">
                    <button type="submit" id="submitBtn" class="ink-btn-main"><i class="fas fa-paper-plane"></i> ENVIAR ENLACE</button>
                    <p><a href="login.php"><i class="fas fa-arrow-left"></i> Volver</a></p>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<style>
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
.ink-footer { text-align: center; }
.ink-btn-main { width: 100%; padding: 15px; background: var(--nj-red); color: #fff; border: none; font-weight: bold; letter-spacing: 1px; cursor: pointer; transition: all 0.2s; border-radius: 4px; }
.ink-btn-main:hover { background: #b81c1c; }
.ink-footer p { margin-top: 20px; font-size: 0.9em; }
.ink-footer a { color: var(--nj-text-muted); text-decoration: none; transition: 0.2s; }
.ink-footer a:hover { color: var(--nj-text-main); }
</style>

<script>
// 前端防连点机制
document.getElementById('forgotForm')?.addEventListener('submit', function() {
    var btn = document.getElementById('submitBtn');
    // 改变文字并添加旋转的 loading 图标 (FontAwesome 默认支持 fa-spin)
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ENVIANDO...';
    // 降低透明度并彻底禁用鼠标点击事件
    btn.style.opacity = '0.7';
    btn.style.pointerEvents = 'none';
});
</script>

<?php include 'includes/footer.php'; ?>