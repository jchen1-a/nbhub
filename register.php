<?php
// register.php - 100% 完整版 (水墨武林风格美化版 + CSRF 防护)
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
    // P0-1: CSRF 安全校验
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors['general'] = 'Error de seguridad (CSRF). Por favor, recarga la página e inténtalo de nuevo.';
    } else {
        $formData['username'] = sanitize($_POST['username'] ?? '');
        $formData['email'] = sanitize($_POST['email'] ?? '');
        $formData['country'] = sanitize($_POST['country'] ?? 'ES');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($formData['username'])) {
            $errors['username'] = 'El nombre de usuario es obligatorio.';
        } elseif (strlen($formData['username']) < 3) {
            $errors['username'] = 'Mínimo 3 caracteres.';
        }
        
        if (empty($formData['email'])) {
            $errors['email'] = 'El correo es obligatorio.';
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Correo no válido.';
        }
        
        if (empty($password) || strlen($password) < 6) {
            $errors['password'] = 'Mínimo 6 caracteres.';
        } elseif ($password !== $confirm_password) {
            $errors['confirm_password'] = 'No coinciden.';
        }
        
        if (empty($errors)) {
            try {
                $pdo = db_connect();
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$formData['username'], $formData['email']]);
                
                if ($stmt->fetch()) {
                    $errors['general'] = 'El usuario o correo ya existe.';
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, email, password_hash, country, created_at) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$formData['username'], $formData['email'], $password_hash, $formData['country']]);
                    
                    $user_id = $pdo->lastInsertId();
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $formData['username'];
                    
                    $_SESSION['flash_message'] = "¡Bienvenido al Hub, " . $formData['username'] . "!";
                    header('Location: dashboard.php');
                    exit();
                }
            } catch (Exception $e) {
                $errors['general'] = 'Error del sistema.';
            }
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="auth-wrap">
    <div class="auth-box">
        <div class="auth-top">
            <h1 class="ink-title">UNIRSE AL HUB</h1>
            <p class="ink-subtitle">Escribe tu historia en Naraka</p>
        </div>

        <?php if (isset($errors['general'])): ?>
            <div class="ink-alert"><?php echo $errors['general']; ?></div>
        <?php endif; ?>

        <form method="POST" id="registerForm" class="ink-form">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <div class="ink-row">
                <div class="ink-group">
                    <label>Usuario</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($formData['username']); ?>" required>
                    <?php if (isset($errors['username'])): ?>
                        <span class="ink-err"><?php echo $errors['username']; ?></span>
                    <?php endif; ?>
                </div>
                <div class="ink-group">
                    <label>País</label>
                    <select name="country">
                        <option value="ES" selected>España</option>
                        <option value="MX">México</option>
                        <option value="AR">Argentina</option>
                        <option value="US">Estados Unidos</option>
                        <option value="OTHER">Otro</option>
                    </select>
                </div>
            </div>

            <div class="ink-group">
                <label>Correo Electrónico</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                <?php if (isset($errors['email'])): ?>
                        <span class="ink-err"><?php echo $errors['email']; ?></span>
                <?php endif; ?>
            </div>

            <div class="ink-row">
                <div class="ink-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" id="password" required>
                    <?php if (isset($errors['password'])): ?>
                        <span class="ink-err"><?php echo $errors['password']; ?></span>
                    <?php endif; ?>
                </div>
                <div class="ink-group">
                    <label>Confirmar</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <span class="ink-err"><?php echo $errors['confirm_password']; ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ink-footer">
                <button type="submit" class="ink-btn-main">FORJAR CUENTA</button>
                <p>¿Ya eres miembro? <a href="login.php">Entrar</a></p>
            </div>
        </form>
    </div>
</div>

<style>
/* 认证页面专用样式 */
.auth-wrap {
    min-height: calc(100vh - 75px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    background: radial-gradient(circle at center, rgba(201, 20, 20, 0.03) 0%, transparent 70%);
}

.auth-box {
    width: 100%;
    max-width: 500px;
    background: #fff;
    padding: 50px 40px;
    box-shadow: 0 15px 50px rgba(0,0,0,0.08);
    border: 1px solid rgba(0,0,0,0.03);
    position: relative;
}

/* 装饰性线条 */
.auth-box::before {
    content: '';
    position: absolute;
    top: 0; left: 0; width: 100%; height: 4px;
    background: var(--accent);
}

.auth-top {
    text-align: center;
    margin-bottom: 40px;
}

.ink-title {
    font-size: 2em;
    margin: 0;
    color: var(--primary);
    letter-spacing: 4px;
}

.ink-subtitle {
    font-family: 'Cinzel', serif;
    font-size: 0.8em;
    color: #888;
    text-transform: uppercase;
    margin-top: 5px;
    letter-spacing: 2px;
}

.ink-form .ink-group {
    margin-bottom: 25px;
}

.ink-form .ink-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.ink-form label {
    display: block;
    font-family: 'Cinzel', serif;
    font-weight: 700;
    font-size: 0.75em;
    color: #444;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.ink-form input, .ink-form select {
    width: 100%;
    padding: 12px 0;
    border: none;
    border-bottom: 1px solid #ddd;
    font-size: 1em;
    transition: all 0.3s;
    background: transparent;
    outline: none;
}

.ink-form input:focus {
    border-bottom-color: var(--accent);
}

.ink-err {
    display: block;
    color: var(--accent);
    font-size: 0.7em;
    margin-top: 5px;
    font-weight: 600;
}

.ink-alert {
    background: rgba(201, 20, 20, 0.05);
    color: var(--accent);
    padding: 12px;
    font-size: 0.85em;
    text-align: center;
    margin-bottom: 30px;
    border-left: 3px solid var(--accent);
}

.ink-footer {
    margin-top: 40px;
    text-align: center;
}

.ink-btn-main {
    width: 100%;
    padding: 15px;
    background: var(--primary);
    color: #fff;
    border: none;
    font-family: 'Cinzel', serif;
    font-weight: 800;
    letter-spacing: 2px;
    cursor: pointer;
    transition: all 0.3s;
    margin-bottom: 20px;
}

.ink-btn-main:hover {
    background: var(--accent);
    box-shadow: 0 5px 20px rgba(201, 20, 20, 0.2);
}

.ink-footer p {
    font-size: 0.9em;
    color: #888;
}

.ink-footer a {
    color: var(--accent);
    font-weight: 700;
    margin-left: 5px;
}

@media (max-width: 600px) {
    .ink-form .ink-row { grid-template-columns: 1fr; gap: 0; }
    .auth-box { padding: 40px 25px; }
}
</style>

<script>
document.getElementById('registerForm')?.addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    
    if (password.length < 6) {
        e.preventDefault();
        alert('La contraseña debe tener al menos 6 caracteres.');
    } else if (password !== confirm) {
        e.preventDefault();
        alert('Las contraseñas no coinciden.');
    }
});
</script>

<?php include 'includes/footer.php'; ?>