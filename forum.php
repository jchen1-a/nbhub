<?php
// forum.php - 论坛页面
require_once 'config.php';

$current_page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$search = sanitize($_GET['search'] ?? '');
$category = sanitize($_GET['category'] ?? '');

try {
    $pdo = db_connect();
    
    // 构建查询条件
    $where = [];
    $params = [];
    
    if (!empty($search)) {
        $where[] = "(title ILIKE ? OR content ILIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if (!empty($category) && $category !== 'all') {
        $where[] = "category = ?";
        $params[] = $category;
    }
    
    $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // 获取帖子总数
    $count_sql = "SELECT COUNT(*) as total FROM forum_posts $where_sql";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_posts = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_posts / $per_page);
    
    // 获取帖子列表
    $offset = ($current_page - 1) * $per_page;
    $posts_sql = "
        SELECT p.*, 
               u.username as author_name,
               u.country as author_country,
               (SELECT COUNT(*) FROM forum_replies WHERE post_id = p.id) as reply_count,
               (SELECT username FROM users WHERE id = p.last_reply_by) as last_replier,
               p.last_reply_at
        FROM forum_posts p
        LEFT JOIN users u ON p.user_id = u.id
        $where_sql
        ORDER BY p.is_pinned DESC, p.last_reply_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $posts_params = array_merge($params, [$per_page, $offset]);
    $posts_stmt = $pdo->prepare($posts_sql);
    $posts_stmt->execute($posts_params);
    $posts = $posts_stmt->fetchAll();
    
    // 获取分类
    $categories = $pdo->query("
        SELECT category, COUNT(*) as count 
        FROM forum_posts 
        GROUP BY category 
        ORDER BY count DESC
    ")->fetchAll();
    
} catch (Exception $e) {
    $error = "Error al cargar el foro: " . $e->getMessage();
}
?>
<?php include 'includes/header.php'; ?>

<div class="forum-container">
    <!-- 论坛头部 -->
    <div class="forum-header">
        <div class="forum-header-content">
            <h1><i class="fas fa-comments"></i> Foro de la Comunidad</h1>
            <p>Comparte estrategias, haz preguntas y conecta con otros jugadores</p>
        </div>
        
        <?php if (is_logged_in()): ?>
        <div class="forum-header-actions">
            <a href="new-post.php" class="btn-primary">
                <i class="fas fa-plus"></i> Nuevo Tema
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- 搜索和过滤 -->
    <div class="forum-filters">
        <form method="GET" class="search-form">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Buscar en el foro...">
                <button type="submit" class="btn-search">Buscar</button>
            </div>
            
            <div class="filter-options">
                <select name="category" onchange="this.form.submit()">
                    <option value="all">Todas las categorías</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                        <?php echo $category == $cat['category'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst($cat['category'])); ?> (<?php echo $cat['count']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="sort" onchange="this.form.submit()">
                    <option value="newest">Más recientes</option>
                    <option value="popular">Más populares</option>
                    <option value="unanswered">Sin respuesta</option>
                </select>
            </div>
        </form>
    </div>
    
    <!-- 统计信息 -->
    <div class="forum-stats">
        <div class="stat-item">
            <i class="fas fa-file-alt"></i>
            <div>
                <span class="stat-number"><?php echo $total_posts; ?></span>
                <span class="stat-label">Temas</span>
            </div>
        </div>
        <div class="stat-item">
            <i class="fas fa-comment"></i>
            <div>
                <span class="stat-number">
                    <?php
                    try {
                        $total_replies = $pdo->query("SELECT COUNT(*) as c FROM forum_replies")->fetch()['c'];
                        echo $total_replies;
                    } catch (Exception $e) {
                        echo '0';
                    }
                    ?>
                </span>
                <span class="stat-label">Respuestas</span>
            </div>
        </div>
        <div class="stat-item">
            <i class="fas fa-users"></i>
            <div>
                <span class="stat-number">
                    <?php
                    try {
                        $active_users = $pdo->query("
                            SELECT COUNT(DISTINCT user_id) as c 
                            FROM forum_posts 
                            WHERE created_at > NOW() - INTERVAL '30 days'
                        ")->fetch()['c'];
                        echo $active_users;
                    } catch (Exception $e) {
                        echo '0';
                    }
                    ?>
                </span>
                <span class="stat-label">Usuarios activos</span>
            </div>
        </div>
    </div>
    
    <!-- 帖子列表 -->
    <div class="posts-table">
        <div class="table-header">
            <div class="col-topic">Tema</div>
            <div class="col-replies">Respuestas</div>
            <div class="col-views">Vistas</div>
            <div class="col-last">Última respuesta</div>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (empty($posts)): ?>
        <div class="empty-forum">
            <i class="fas fa-comments-slash"></i>
            <h3>No hay temas en el foro</h3>
            <p>Sé el primero en crear un tema de discusión.</p>
            <?php if (is_logged_in()): ?>
            <a href="new-post.php" class="btn-primary">Crear primer tema</a>
            <?php else: ?>
            <p><a href="login.php">Inicia sesión</a> para publicar en el foro.</p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        
        <?php foreach ($posts as $post): ?>
        <div class="post-row <?php echo $post['is_pinned'] ? 'pinned' : ''; ?>">
            <div class="col-topic">
                <div class="topic-main">
                    <?php if ($post['is_pinned']): ?>
                    <span class="badge pinned-badge">
                        <i class="fas fa-thumbtack"></i> Fijado
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($post['reply_count'] == 0): ?>
                    <span class="badge new-badge">
                        <i class="fas fa-star"></i> Nuevo
                    </span>
                    <?php endif; ?>
                    
                    <h4 class="topic-title">
                        <a href="view-post.php?id=<?php echo $post['id']; ?>">
                            <?php echo htmlspecialchars($post['title']); ?>
                        </a>
                    </h4>
                    
                    <div class="topic-meta">
                        <span class="topic-category"><?php echo htmlspecialchars($post['category']); ?></span>
                        <span class="topic-author">
                            por <a href="profile.php?user=<?php echo $post['user_id']; ?>">
                                <?php echo htmlspecialchars($post['author_name']); ?>
                            </a>
                            <?php if ($post['author_country']): ?>
                            <span class="flag"><?php echo get_flag($post['author_country']); ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="col-replies">
                <span class="replies-count"><?php echo $post['reply_count']; ?></span>
            </div>
            
            <div class="col-views">
                <span class="views-count"><?php echo $post['views'] ?? 0; ?></span>
            </div>
            
            <div class="col-last">
                <?php if ($post['last_reply_at']): ?>
                <div class="last-reply">
                    <span class="last-replier"><?php echo htmlspecialchars($post['last_replier']); ?></span>
                    <span class="last-time"><?php echo time_ago($post['last_reply_at']); ?></span>
                </div>
                <?php else: ?>
                <span class="no-replies">Sin respuestas</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php endif; ?>
    </div>
    
    <!-- 分页 -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($current_page > 1): ?>
        <a href="?page=1<?php echo $search ? "&search=$search" : ''; ?><?php echo $category ? "&category=$category" : ''; ?>" 
           class="page-link first">
            <i class="fas fa-angle-double-left"></i>
        </a>
        <a href="?page=<?php echo $current_page - 1; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $category ? "&category=$category" : ''; ?>" 
           class="page-link prev">
            <i class="fas fa-angle-left"></i>
        </a>
        <?php endif; ?>
        
        <?php
        $start = max(1, $current_page - 2);
        $end = min($total_pages, $current_page + 2);
        
        for ($i = $start; $i <= $end; $i++):
        ?>
        <a href="?page=<?php echo $i; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $category ? "&category=$category" : ''; ?>" 
           class="page-link <?php echo $i == $current_page ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>
        
        <?php if ($current_page < $total_pages): ?>
        <a href="?page=<?php echo $current_page + 1; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $category ? "&category=$category" : ''; ?>" 
           class="page-link next">
            <i class="fas fa-angle-right"></i>
        </a>
        <a href="?page=<?php echo $total_pages; ?><?php echo $search ? "&search=$search" : ''; ?><?php echo $category ? "&category=$category" : ''; ?>" 
           class="page-link last">
            <i class="fas fa-angle-double-right"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- 热门标签 -->
    <div class="popular-tags">
        <h3><i class="fas fa-tags"></i> Etiquetas Populares</h3>
        <div class="tags-list">
            <?php
            try {
                $tags = $pdo->query("
                    SELECT tag, COUNT(*) as count 
                    FROM forum_tags 
                    GROUP BY tag 
                    ORDER BY count DESC 
                    LIMIT 15
                ")->fetchAll();
                
                foreach ($tags as $tag):
            ?>
            <a href="?search=<?php echo urlencode($tag['tag']); ?>" class="tag">
                <?php echo htmlspecialchars($tag['tag']); ?>
                <span class="tag-count"><?php echo $tag['count']; ?></span>
            </a>
            <?php 
                endforeach;
            } catch (Exception $e) {
                // 忽略标签错误
            }
            ?>
        </div>
    </div>
    
    <!-- 论坛规则 -->
    <div class="forum-rules">
        <h3><i class="fas fa-gavel"></i> Reglas del Foro</h3>
        <ol>
            <li>Respeta a todos los miembros de la comunidad</li>
            <li>No publicar contenido ofensivo o inapropiado</li>
            <li>Usa las categorías correctas para tus temas</li>
            <li>No hacer spam o publicidad no solicitada</li>
            <li>Busca antes de crear un tema nuevo</li>
        </ol>
    </div>
</div>

<style>
.forum-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.forum-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid var(--accent);
}

.forum-header h1 {
    color: var(--primary);
    margin-bottom: 10px;
}

.forum-filters {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}

.search-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.search-box {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 0 15px;
}

.search-box i {
    color: #666;
}

.search-box input {
    flex: 1;
    padding: 15px 0;
    border: none;
    background: transparent;
    font-size: 16px;
}

.search-box input:focus {
    outline: none;
}

.btn-search {
    background: var(--accent);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.3s;
}

.btn-search:hover {
    background: #00959c;
}

.filter-options {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.filter-options select {
    padding: 10px 15px;
    border: 2px solid #ddd;
    border-radius: 6px;
    background: white;
    min-width: 200px;
}

.forum-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.stat-item {
    background: white;
    padding: 20px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
}

.stat-item i {
    font-size: 2em;
    color: var(--accent);
}

.stat-number {
    display: block;
    font-size: 2em;
    font-weight: bold;
    color: var(--primary);
}

.stat-label {
    color: #666;
    font-size: 0.9em;
}

.posts-table {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.table-header {
    display: grid;
    grid-template-columns: 2fr 0.5fr 0.5fr 1fr;
    background: var(--primary);
    color: white;
    padding: 15px 20px;
    font-weight: bold;
}

.post-row {
    display: grid;
    grid-template-columns: 2fr 0.5fr 0.5fr 1fr;
    padding: 20px;
    border-bottom: 1px solid #eee;
    transition: background 0.3s;
}

.post-row:hover {
    background: #f8f9fa;
}

.post-row.pinned {
    background: #fff9e6;
}

.col-topic {
    padding-right: 20px;
}

.topic-main {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.badge {
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
}

.pinned-badge {
    background: var(--warning);
    color: #333;
}

.new-badge {
    background: var(--success);
    color: white;
}

.topic-title {
    margin: 0;
    flex: 1;
}

.topic-title a {
    color: var(--primary);
    text-decoration: none;
}

.topic-title a:hover {
    color: var(--accent);
}

.topic-meta {
    display: flex;
    gap: 15px;
    font-size: 0.9em;
    color: #666;
}

.topic-category {
    background: #e9ecef;
    padding: 2px 8px;
    border-radius: 4px;
}

.topic-author a {
    color: var(--accent);
    text-decoration: none;
}

.topic-author a:hover {
    text-decoration: underline;
}

.flag {
    font-size: 1.2em;
}

.col-replies, .col-views {
    display: flex;
    align-items: center;
    justify-content: center;
}

.replies-count, .views-count {
    font-weight: bold;
    color: var(--primary);
}

.col-last {
    display: flex;
    align-items: center;
    padding-left: 20px;
}

.last-reply {
    display: flex;
    flex-direction: column;
}

.last-replier {
    font-weight: 500;
    margin-bottom: 5px;
}

.last-time {
    font-size: 0.9em;
    color: #666;
}

.no-replies {
    color: #999;
    font-style: italic;
}

.empty-forum {
    text-align: center;
    padding: 60px 20px;
}

.empty-forum i {
    font-size: 4em;
    color: #ddd;
    margin-bottom: 20px;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin: 30px 0;
}

.page-link {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    text-decoration: none;
    color: var(--primary);
    transition: all 0.3s;
    min-width: 40px;
    text-align: center;
}

.page-link:hover {
    background: #f8f9fa;
    border-color: var(--accent);
}

.page-link.active {
    background: var(--accent);
    color: white;
    border-color: var(--accent);
}

.popular-tags, .forum-rules {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    margin-bottom: 30px;
}

.popular-tags h3, .forum-rules h3 {
    color: var(--primary);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 15px;
    background: #e9ecef;
    border-radius: 20px;
    text-decoration: none;
    color: var(--primary);
    transition: all 0.3s;
    font-size: 0.9em;
}

.tag:hover {
    background: var(--accent);
    color: white;
}

.tag-count {
    background: white;
    color: var(--accent);
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.8em;
    font-weight: bold;
}

.forum-rules ol {
    margin-left: 20px;
    color: #666;
}

.forum-rules li {
    margin-bottom: 10px;
    line-height: 1.5;
}

@media (max-width: 768px) {
    .forum-header {
        flex-direction: column;
        align-items: stretch;
        gap: 20px;
    }
    
    .table-header, .post-row {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .col-replies, .col-views, .col-last {
        justify-content: flex-start;
        padding-left: 0;
    }
    
    .filter-options {
        flex-direction: column;
    }
    
    .filter-options select {
        min-width: 100%;
    }
}
</style>

<script>
// 自动完成搜索
document.querySelector('.search-box input')?.addEventListener('input', async function(e) {
    const query = this.value.trim();
    if (query.length < 2) return;
    
    // 这里可以添加搜索建议功能
    // const suggestions = await fetchSuggestions(query);
    // showSuggestions(suggestions);
});

// 标记已读
document.querySelectorAll('.post-row').forEach(row => {
    row.addEventListener('click', function(e) {
        if (!e.target.closest('a')) {
            this.classList.add('read');
        }
    });
});
</script>

<?php 
// 辅助函数：获取国旗emoji
function get_flag($country_code) {
    $flags = [
        'ES' => '🇪🇸',
        'MX' => '🇲🇽',
        'AR' => '🇦🇷',
        'US' => '🇺🇸',
        'BR' => '🇧🇷',
        'FR' => '🇫🇷',
        'DE' => '🇩🇪',
        'UK' => '🇬🇧',
        'IT' => '🇮🇹',
        'JP' => '🇯🇵',
        'KR' => '🇰🇷',
        'CN' => '🇨🇳'
    ];
    return $flags[$country_code] ?? '🌐';
}
?>

<?php include 'includes/footer.php'; ?>