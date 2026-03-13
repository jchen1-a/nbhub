<?php
// admin.php - 100% 完整版 (超级管理员控制面板)
require_once 'config.php';
require_login();

$user_id = $_SESSION['user_id'];

try {
    $pdo = db_connect();
    
    // 1. 严格的安全检查：验证当前用户是否真的是管理员
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_role = $stmt->fetchColumn();
    
    if ($current_role !== 'admin') {
        $_SESSION['flash_error'] = "Acceso denegado. No tienes permisos de administrador.";
        header("Location: index.php");
        exit;
    }

    // 2. 处理管理员的操作 (提升/降级/删除用户)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $target_id = intval($_POST['target_id'] ?? 0);
        
        // 防止管理员误删自己或修改自己的权限
        if ($target_id === $user_id) {
            $_SESSION['flash_error'] = "No puedes modificar tus propios permisos desde aquí.";
        } else {
            if ($_POST['action'] === 'make_admin') {
                $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$target_id]);
                $_SESSION['flash_message'] = "El usuario ha sido promovido a Administrador.";
            } 
            elseif ($_POST['action'] === 'remove_admin') {
                $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ?")->execute([$target_id]);
                $_SESSION['flash_message'] = "Se han revocado los permisos de Administrador del usuario.";
            }
            elseif ($_POST['action'] === 'delete_user') {
                // 删除用户前，先清理他产生的所有内容（级联删除，防止数据库报错）
                $pdo->prepare("DELETE FROM article_comments WHERE user_id = ?")->execute([$target_id]);
                $pdo->prepare("DELETE FROM articles WHERE user_id = ?")->execute([$target_id]);
                $pdo->prepare("DELETE FROM forum_replies WHERE user_id = ?")->execute([$target_id]);
                $pdo->prepare("DELETE FROM forum_posts WHERE user_id = ?")->execute([$target_id]);
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$target_id]);
                $_SESSION['flash_message'] = "El usuario y todo su contenido han sido eliminados del sistema.";
            }
            // 刷新页面以防表单重复提交
            header("Location: admin.php");
            exit;
        }
    }

    // 3. 获取全站统计数据
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_guides = $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
    $total_posts = $pdo->query("SELECT COUNT(*) FROM forum_posts")->fetchColumn();
    $total_comments = $pdo->query("SELECT COUNT(*) FROM article_comments")->fetchColumn();

    // 4. 获取所有用户列表 (供管理员管理)
    $users = $pdo->query("
        SELECT id, username, email, role, created_at, country 
        FROM users 
        ORDER BY role ASC, created_at DESC
    ")->fetchAll();

} catch (Exception $e) {
    die("Error del sistema: " . $e->getMessage());
}
?>
<?php include 'includes/header.php'; ?>

<div class="container" style="padding: 40px 20px; max-width: 1200px; margin: 0 auto;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 15px;">
        <h1 style="color: var(--primary); margin: 0;">
            <i class="fas fa-shield-alt" style="color: var(--danger);"></i> Panel de Administración
        </h1>
        <span style="background: var(--danger); color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 0.9em;">Zona de Alto Riesgo</span>
    </div>

    <div class="admin-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
        <div class="stat-card">
            <i class="fas fa-users"></i>
            <div class="stat-info">
                <h3>Usuarios</h3>
                <span class="num"><?php echo $total_users; ?></span>
            </div>
        </div>
        <div class="stat-card">
            <i class="fas fa-book-open"></i>
            <div class="stat-info">
                <h3>Guías</h3>
                <span class="num"><?php echo $total_guides; ?></span>
            </div>
        </div>
        <div class="stat-card">
            <i class="fas fa-comments"></i>
            <div class="stat-info">
                <h3>Temas en Foro</h3>
                <span class="num"><?php echo $total_posts; ?></span>
            </div>
        </div>
        <div class="stat-card">
            <i class="fas fa-reply"></i>
            <div class="stat-info">
                <h3>Comentarios</h3>
                <span class="num"><?php echo $total_comments; ?></span>
            </div>
        </div>
    </div>

    <div style="background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); overflow: hidden;">
        <div style="background: var(--primary); color: white; padding: 20px; display: flex; align-items: center; justify-content: space-between;">
            <h2 style="margin: 0; font-size: 1.4em;"><i class="fas fa-users-cog"></i> Gestión de Usuarios</h2>
        </div>
        
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>País</th>
                        <th>Rol</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr class="<?php echo $u['role'] === 'admin' ? 'row-admin' : ''; ?>">
                        <td style="font-weight: bold; color: #888;">#<?php echo $u['id']; ?></td>
                        <td>
                            <a href="profile.php?user=<?php echo $u['id']; ?>" style="color: var(--primary); font-weight: bold; text-decoration: none;">
                                <?php echo htmlspecialchars($u['username']); ?>
                            </a>
                            <?php if ($u['id'] == $user_id) echo "<span style='color:var(--accent); font-size:0.8em; margin-left:5px;'>(Tú)</span>"; ?>
                        </td>
                        <td style="color: #666;"><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['country'] ?? '-'); ?></td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="badge badge-admin"><i class="fas fa-star"></i> Admin</span>
                            <?php else: ?>
                                <span class="badge badge-user">Usuario</span>
                            <?php endif; ?>
                        </td>
                        <td style="color: #666; font-size: 0.9em;"><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                        <td>
                            <?php if ($u['id'] !== $user_id): ?>
                                <div style="display: flex; gap: 8px;">
                                    <?php if ($u['role'] === 'user'): ?>
                                        <form method="POST" style="margin: 0;">
                                            <input type="hidden" name="action" value="make_admin">
                                            <input type="hidden" name="target_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn-action btn-promote" title="Hacer Administrador" onclick="return confirm('¿Otorgar permisos de Administrador a este usuario?');">
                                                <i class="fas fa-arrow-up"></i> Admin
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="margin: 0;">
                                            <input type="hidden" name="action" value="remove_admin">
                                            <input type="hidden" name="target_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn-action btn-demote" title="Revocar Administrador" onclick="return confirm('¿Quitar permisos de Administrador a este usuario?');">
                                                <i class="fas fa-arrow-down"></i> Revocar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="target_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="btn-action btn-delete" title="Eliminar Usuario" onclick="return confirm('¡ADVERTENCIA! ¿Seguro que quieres eliminar a este usuario de forma permanente? Se borrarán todas sus guías y mensajes.');">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span style="color: #ccc; font-style: italic;">Sin acciones</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* 统计卡片样式 */
.stat-card { background: white; padding: 20px; border-radius: 12px; display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-bottom: 4px solid var(--accent); }
.stat-card:nth-child(1) { border-color: #007bff; }
.stat-card:nth-child(2) { border-color: #28a745; }
.stat-card:nth-child(3) { border-color: #fd7e14; }
.stat-card:nth-child(4) { border-color: #6f42c1; }
.stat-card i { font-size: 2.5em; color: #ddd; }
.stat-info h3 { margin: 0 0 5px 0; font-size: 1em; color: #666; text-transform: uppercase; letter-spacing: 1px; }
.stat-info .num { font-size: 2em; font-weight: bold; color: var(--primary); line-height: 1; }

/* 表格样式 */
.admin-table { width: 100%; border-collapse: collapse; text-align: left; }
.admin-table th { background: #f8f9fa; padding: 15px 20px; color: #555; font-weight: bold; font-size: 0.9em; text-transform: uppercase; border-bottom: 2px solid #eee; }
.admin-table td { padding: 15px 20px; border-bottom: 1px solid #eee; vertical-align: middle; }
.admin-table tbody tr:hover { background: #fdfdfd; }
.row-admin { background: #fdfaf0 !important; }

/* 徽章和按钮 */
.badge { padding: 5px 10px; border-radius: 20px; font-size: 0.8em; font-weight: bold; display: inline-block; }
.badge-admin { background: #ffc107; color: #856404; }
.badge-user { background: #e9ecef; color: #666; }

.btn-action { padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.85em; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; }
.btn-promote { background: #e0f8e9; color: #198754; }
.btn-promote:hover { background: #198754; color: white; }
.btn-demote { background: #fff3cd; color: #ffc107; }
.btn-demote:hover { background: #ffc107; color: white; }
.btn-delete { background: #f8d7da; color: #dc3545; }
.btn-delete:hover { background: #dc3545; color: white; }
</style>

<?php include 'includes/footer.php'; ?>