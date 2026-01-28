<?php
// register.php - 用户注册
require_once 'config.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit();
}

$errors = [];
$formData = [
    'username' => '',
    'email' => '',
    'country' => 'ES'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = array_map('sanitize', $_POST);
    
    // 验证
    if (empty($formData['username'])) {
        $errors['username'] = 'El nombre de usuario es obligatorio.';
    } elseif (strlen($formData['username']) < 3) {
        $errors['username'] = 'El nombre debe tener al menos 3 caracteres.';
    }
    
    if (empty($formData['email'])) {
        $errors['email'] = 'El correo electrónico es obligatorio.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Correo electrónico no válido.';
    }
    
    if (empty($formData['password'])) {
        $errors['password'] = 'La contraseña es obligatoria.';
    } elseif (strlen($formData['password']) < 6) {
        $errors['password'] = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($formData['password'] !== ($_POST['confirm_password'] ?? '')) {
        $errors['confirm_password'] = 'Las contraseñas no coinciden.';
    }
    
    // 如果没有错误，尝试注册
    if (empty($errors)) {
        try {
            $pdo = db_connect();
            
            // 检查用户名和邮箱是否已存在
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$formData['username'], $formData['email']]);
            
            if ($stmt->fetch()) {
                $errors['general'] = 'El nombre de usuario o correo ya está registrado.';
            } else {
                // 创建用户
                $password_hash = password_hash($formData['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password_hash, country, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $formData['username'],
                    $formData['email'],
                    $password_hash,
                    $formData['country']
                ]);
                
                // 自动登录
                $user_id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $formData['username'];
                $_SESSION['user_email'] = $formData['email'];
                
                // 设置成功消息并重定向
                $_SESSION['flash_message'] = '¡Registro exitoso! Bienvenido a Naraka Hub.';
                header('Location: dashboard.php');
                exit();
            }
        } catch (Exception $e) {
            $errors['general'] = 'Error del sistema. Por favor, intenta más tarde.';
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1><i class="fas fa-user-plus"></i> Crear Cuenta</h1>
            <p>Únete a la comunidad de Naraka: Bladepoint</p>
        </div>
        
        <?php if (isset($errors['general'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $errors['general']; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form" id="registerForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Nombre de Usuario *</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($formData['username']); ?>"
                           required minlength="3" maxlength="30"
                           placeholder="Ej: NarakaPlayer" autocomplete="username">
                    <?php if (isset($errors['username'])): ?>
                    <div class="form-error"><?php echo $errors['username']; ?></div>
                    <?php endif; ?>
                    <div class="form-hint">3-30 caracteres, letras y números</div>
                </div>
                
                <div class="form-group">
                    <label for="country"><i class="fas fa-globe"></i> País</label>
                    <select id="country" name="country" class="form-select">
                        <option value="ES" <?php echo $formData['country'] == 'ES' ? 'selected' : ''; ?>>España</option>
                        <option value="MX" <?php echo $formData['country'] == 'MX' ? 'selected' : ''; ?>>México</option>
                        <option value="AR" <?php echo $formData['country'] == 'AR' ? 'selected' : ''; ?>>Argentina</option>
                        <option value="US" <?php echo $formData['country'] == 'US' ? 'selected' : ''; ?>>Estados Unidos</option>
                        <option value="OTHER">Otro</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Correo Electrónico *</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($formData['email']); ?>"
                       required placeholder="tu@email.com" autocomplete="email">
                <?php if (isset($errors['email'])): ?>
                <div class="form-error"><?php echo $errors['email']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Contraseña *</label>
                    <input type="password" id="password" name="password" 
                           required minlength="6" autocomplete="new-password"
                           placeholder="Mínimo 6 caracteres">
                    <?php if (isset($errors['password'])): ?>
                    <div class="form-error"><?php echo $errors['password']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirmar Contraseña *</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           required autocomplete="new-password" placeholder="Repite tu contraseña">
                    <?php if (isset($errors['confirm_password'])): ?>
                    <div class="form-error"><?php echo $errors['confirm_password']; ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-checkbox">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">
                    Acepto los <a href="terms.php" target="_blank">Términos de Servicio</a> y 
                    <a href="privacy.php" target="_blank">Política de Privacidad</a>
                </label>
            </div>
            
            <div class="form-checkbox">
                <input type="checkbox" id="newsletter" name="newsletter" checked>
                <label for="newsletter">
                    Quiero recibir noticias sobre Naraka: Bladepoint y actualizaciones del sitio
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-auth btn-success">
                    <i class="fas fa-check-circle"></i> Crear Cuenta
                </button>
            </div>
        </form>
        
        <div class="auth-divider">
            <span>¿Ya tienes una cuenta?</span>
        </div>
        
        <div class="auth-footer">
            <a href="login.php" class="btn-auth btn-primary">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </a>
            <a href="index.php" class="btn-auth btn-outline">
                <i class="fas fa-arrow-left"></i> Volver al Inicio
            </a>
        </div>
    </div>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-select {
    width: 100%;
    padding: 15px;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    background: white;
}

.form-error {
    color: var(--danger);
    font-size: 14px;
    margin-top: 5px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.form-hint {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.form-checkbox {
    margin: 20px 0;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.form-checkbox input[type="checkbox"] {
    margin-top: 5px;
}

.form-checkbox label {
    font-size: 14px;
    line-height: 1.4;
}

.btn-success {
    background: var(--success);
    color: white;
}

.btn-success:hover {
    background: #218838;
}
</style>

<script>
// 密码强度检查
document.getElementById('password')?.addEventListener('input', function(e) {
    const password = this.value;
    const strength = checkPasswordStrength(password);
    updateStrengthIndicator(strength);
});

function checkPasswordStrength(password) {
    let score = 0;
    
    if (password.length >= 8) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    
    return Math.min(score, 3);
}

function updateStrengthIndicator(strength) {
    const indicator = document.getElementById('passwordStrength') || createStrengthIndicator();
    const labels = ['Débil', 'Moderada', 'Fuerte', 'Muy Fuerte'];
    const colors = ['#dc3545', '#ffc107', '#17a2b8', '#28a745'];
    
    indicator.innerHTML = `
        <div class="strength-bar">
            <div class="strength-fill" style="width: ${(strength + 1) * 25}%; background: ${colors[strength]};"></div>
        </div>
        <div class="strength-label">Seguridad: ${labels[strength]}</div>
    `;
}

function createStrengthIndicator() {
    const div = document.createElement('div');
    div.id = 'passwordStrength';
    div.className = 'password-strength';
    document.getElementById('password').parentNode.appendChild(div);
    return div;
}

// 表单验证
document.getElementById('registerForm')?.addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    
    if (password !== confirm) {
        e.preventDefault();
        alert('Las contraseñas no coinciden.');
        return false;
    }
    
    if (!document.getElementById('terms').checked) {
        e.preventDefault();
        alert('Debes aceptar los Términos de Servicio.');
        return false;
    }
    
    return true;
});
</script>

<?php include 'includes/footer.php'; ?>