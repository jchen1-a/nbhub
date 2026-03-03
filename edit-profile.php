<?php
// edit-profile.php - 编辑个人资料与头像上传
require_once 'config.php';
require_login(); // 必须登录

$user_id = $_SESSION['user_id'];
$errors = [];

try {
    $pdo = db_connect();
    
    // 获取当前用户最新信息
    $stmt = $pdo->prepare("SELECT username, email, country, bio, gender, avatar FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_user = $stmt->fetch();
    
} catch (Exception $e) {
    die("Error de base de datos: " . $e->getMessage());
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $country = sanitize($_POST['country'] ?? '');
    $bio = sanitize($_POST['bio'] ?? '');
    $gender = sanitize($_POST['gender'] ?? 'unspecified');
    $avatar_path = $current_user['avatar']; // 默认保留原头像

    // 1. 验证用户名
    if (empty($username)) {
        $errors['username'] = "El nombre de usuario es obligatorio.";
    } else {
        // 检查用户名是否被别人占用
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check->execute([$username, $user_id]);
        if ($check->fetch()) {
            $errors['username'] = "Este nombre de usuario ya está en uso.";
        }
    }

    // 2. 处理头像上传
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $errors['avatar'] = "Solo se permiten imágenes JPG, PNG o GIF.";
        } elseif ($file['size'] > $max_size) {
            $errors['avatar'] = "La imagen no debe superar los 2MB.";
        } else {
            // 生成唯一文件名，防止重名覆盖
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $extension;
            $upload_dir = 'uploads/avatars/';
            
            // 确保目录存在
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
                $avatar_path = $upload_dir . $new_filename;
                // 可选：删除旧头像以节省空间
                if ($current_user['avatar'] && file_exists($current_user['avatar'])) {
                    unlink($current_user['avatar']);
                }
            } else {
                $errors['avatar'] = "Error al subir la imagen. Verifica los permisos de carpeta.";
            }
        }
    }

    // 3. 更新数据库
    if (empty($errors)) {
        try {
            $update = $pdo->prepare("
                UPDATE users 
                SET username = ?, country = ?, bio = ?, gender = ?, avatar = ? 
                WHERE id = ?
            ");
            $update->execute([$username, $country, $bio, $gender, $avatar_path, $user_id]);
            
            // 更新 session 中的名字
            $_SESSION['user_name'] = $username;
            
            $_SESSION['flash_message'] = "¡Perfil actualizado con éxito!";
            header("Location: profile.php");
            exit;
            
        } catch (Exception $e) {
            $errors['general'] = "Error al guardar: " . $e->getMessage();
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container" style="padding: 40px 20px; max-width: 800px;">
    <div style="background: white; padding: 40px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
        <h1 style="color: var(--primary); margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 15px;">
            <i class="fas fa-user-edit"></i> Editar Perfil
        </h1>

        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-error"><?php echo $errors['general']; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="profile-form">
            
            <div class="avatar-section" style="text-align: center; margin-bottom: 30px;">
                <div class="current-avatar">
                    <?php if ($current_user['avatar']): ?>
                        <img src="<?php echo htmlspecialchars($current_user['avatar']); ?>" alt="Avatar" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid #eee;">
                    <?php else: ?>
                        <i class="fas fa-user-circle" style="font-size: 120px; color: #ccc;"></i>
                    <?php endif; ?>
                </div>
                <div style="margin-top: 15px;">
                    <label for="avatar" class="btn-outline" style="cursor: pointer; display: inline-block; padding: 8px 15px; border-radius: 6px;">
                        <i class="fas fa-camera"></i> Cambiar Foto
                    </label>
                    <input type="file" id="avatar" name="avatar" accept="image/jpeg, image/png, image/gif" style="display: none;" onchange="updateFileName(this)">
                    <div id="file-name" style="margin-top: 10px; font-size: 0.85em; color: #666;"></div>
                    <?php if(isset($errors['avatar'])) echo "<div style='color:red; font-size:0.9em; margin-top:5px;'>{$errors['avatar']}</div>"; ?>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Nickname (Usuario)</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? $current_user['username']); ?>" required
                           style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                    <?php if(isset($errors['username'])) echo "<small style='color:red;'>{$errors['username']}</small>"; ?>
                </div>
                
                <div>
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Correo (No se puede cambiar)</label>
                    <input type="email" value="<?php echo htmlspecialchars($current_user['email']); ?>" disabled
                           style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; background: #f8f9fa; color:#999;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Género</label>
                    <select name="gender" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                        <?php $gen = $_POST['gender'] ?? $current_user['gender']; ?>
                        <option value="unspecified" <?php echo $gen == 'unspecified' ? 'selected' : ''; ?>>Prefiero no decirlo</option>
                        <option value="male" <?php echo $gen == 'male' ? 'selected' : ''; ?>>Masculino</option>
                        <option value="female" <?php echo $gen == 'female' ? 'selected' : ''; ?>>Femenino</option>
                        <option value="other" <?php echo $gen == 'other' ? 'selected' : ''; ?>>Otro</option>
                    </select>
                </div>
                
                <div>
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">País</label>
                    <select name="country" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                        <?php 
                        $countries = ['ES'=>'España', 'MX'=>'México', 'AR'=>'Argentina', 'CL'=>'Chile', 'CO'=>'Colombia', 'PE'=>'Perú', 'US'=>'Estados Unidos'];
                        $user_country = $_POST['country'] ?? $current_user['country'];
                        echo "<option value=''>-- Seleccionar --</option>";
                        foreach($countries as $code => $name) {
                            $sel = ($user_country == $code) ? 'selected' : '';
                            echo "<option value=\"$code\" $sel>$name</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 30px;">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Perfil Personal (Bio)</label>
                <textarea name="bio" rows="4" placeholder="Cuéntanos un poco sobre ti, tus héroes favoritos, tu estilo de juego..."
                          style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; font-family:inherit; resize:vertical;"><?php echo htmlspecialchars($_POST['bio'] ?? $current_user['bio']); ?></textarea>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 2px solid #eee; padding-top: 20px;">
                <a href="profile.php" style="color: #666; text-decoration: none; font-weight: bold;">Cancelar</a>
                <button type="submit" style="padding: 12px 30px; background: var(--accent); color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size:1.1em;">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// 显示选择的文件名
function updateFileName(input) {
    var fileName = input.files[0] ? input.files[0].name : '';
    document.getElementById('file-name').textContent = fileName ? 'Archivo seleccionado: ' + fileName : '';
}
</script>

<style>
.btn-outline { background: white; color: #333; border: 2px solid #ddd; transition: all 0.3s; }
.btn-outline:hover { background: #f8f9fa; border-color: #bbb; }
@media (max-width: 600px) {
    .profile-form > div[style*="grid-template-columns"] { grid-template-columns: 1fr !important; }
}
</style>

<?php include 'includes/footer.php'; ?>