<?php
// login.php - 用户登录
require_once 'config.php';

// 如果已登录，重定向到首页
if (is_logged_in()) {
    header('Location: index.php');
    exit();
}

$error = '';
$email = '';

// 处理登录表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor, completa todos los campos.';
    } else {
        try {
            $pdo = db_connect();
            
            // 查询用户
            $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // 登录成功，设置会话
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_email'] = $user['email'];
                
                // 设置最后登录时间
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
                    ->execute([$user['id']]);
                
                // 重定向
                $redirect = $_GET['redirect'] ?? 'index.php';
                header("Location: $redirect");
                exit();
            } else {
                $error = 'Correo electrónico o contraseña incorrectos.';
            }
        } catch (Exception $e) {
            $error = 'Error del sistema. Por favor, intenta más tarde.';
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</h1>
            <p>Ingresa a tu cuenta de Naraka Hub</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Correo Electrónico</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" 
                       required placeholder="tu@email.com" autocomplete="email">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Contraseña</label>
                <input type="password" id="password" name="password" 
                       required placeholder="Tu contraseña" autocomplete="current-password">
                <div class="password-toggle">
                    <input type="checkbox" id="showPassword"> <label for="showPassword">Mostrar contraseña</label>
                </div>
            </div>
            
            <div class="form-options">
                <label class="checkbox">
                    <input type="checkbox" name="remember"> Recordarme
                </label>
                <a href="forgot-password.php" class="forgot-link">¿Olvidaste tu contraseña?</a>
            </div>
            
            <button type="submit" class="btn-auth btn-primary">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>
        
        <div class="auth-divider">
            <span>¿No tienes una cuenta?</span>
        </div>
        
        <div class="auth-footer">
            <a href="register.php" class="btn-auth btn-secondary">
                <i class="fas fa-user-plus"></i> Crear Cuenta Nueva
            </a>
            <a href="index.php" class="btn-auth btn-outline">
                <i class="fas fa-home"></i> Volver al Inicio
            </a>
        </div>
    </div>
</div>

<style>
.auth-container {
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
}

.auth-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    padding: 40px;
    width: 100%;
    max-width: 500px;
}

.auth-header {
    text-align: center;
    margin-bottom: 30px;
}

.auth-header h1 {
    color: var(--primary);
    margin-bottom: 10px;
}

.auth-header p {
    color: #666;
}

.auth-form {
    margin: 30px 0;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
    font-weight: bold;
    color: var(--primary);
}

.form-group input {
    width: 100%;
    padding: 15px;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s;
}

.form-group input:focus {
    outline: none;
    border-color: var(--accent);
}

.password-toggle {
    margin-top: 10px;
    font-size: 14px;
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 25px 0;
}

.checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
}

.forgot-link {
    color: var(--accent);
    text-decoration: none;
}

.forgot-link:hover {
    text-decoration: underline;
}

.btn-auth {
    width: 100%;
    padding: 15px;
    border-radius: 8px;
    border: none;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin: 10px 0;
    transition: all 0.3s;
}

.auth-divider {
    text-align: center;
    margin: 30px 0;
    position: relative;
}

.auth-divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #ddd;
}

.auth-divider span {
    background: white;
    padding: 0 20px;
    color: #666;
    position: relative;
}

.auth-footer {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
</style>

<script>
// 显示/隐藏密码
document.getElementById('showPassword')?.addEventListener('change', function(e) {
    const passwordInput = document.getElementById('password');
    passwordInput.type = this.checked ? 'text' : 'password';
});
</script>

<?php include 'includes/footer.php'; ?>