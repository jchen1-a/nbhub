<?php
// wiki-article.php - 查看词条详情 (浅色水墨白灰红居中 + 底部页脚固定版)
require_once 'config.php';

$id = intval($_GET['id'] ?? 0);
$user_id = is_logged_in() ? $_SESSION['user_id'] : 0;

try {
    $pdo = db_connect();
    
    // 获取文章及作者信息
    $stmt = $pdo->prepare("
        SELECT w.*, u.username, c.name as category_name 
        FROM wiki_articles w
        LEFT JOIN users u ON w.author_id = u.id
        LEFT JOIN wiki_categories c ON w.category_id = c.id
        WHERE w.id = ?
    ");
    $stmt->execute([$id]);
    $article = $stmt->fetch();
    
    if (!$article) {
        header("HTTP/1.0 404 Not Found");
        die("El artículo no existe.");
    }

    // 智能防刷浏览量
    if (!isset($_SESSION['viewed_wiki'])) $_SESSION['viewed_wiki'] = [];
    if ($user_id != $article['author_id'] && !in_array($id, $_SESSION['viewed_wiki'])) {
        $pdo->prepare("UPDATE wiki_articles SET views = views + 1 WHERE id = ?")->execute([$id]);
        $article['views']++;
        $_SESSION['viewed_wiki'][] = $id;
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<?php include 'includes/header.php'; ?>

<div class="light-theme-bg"></div>

<div class="article-container">
    
    <div class="breadcrumb">
        <a href="wiki.php"><i class="fas fa-book"></i> Wiki</a> 
        <span class="separator">»</span> 
        <?php echo htmlspecialchars($article['category_name'] ?? 'General'); ?>
    </div>

    <article class="article-card">
        
        <header class="article-header">
            <div class="header-content">
                <h1 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h1>
                <div class="article-meta">
                    <span title="Fecha de publicación">
                        <i class="far fa-calendar"></i> <?php echo date('d/m/Y', strtotime($article['created_at'])); ?>
                    </span>
                    <span title="Autor">
                        <i class="fas fa-pen-nib"></i> Contribución de <strong><?php echo htmlspecialchars($article['username']); ?></strong>
                    </span>
                    <span title="Vistas">
                        <i class="fas fa-eye"></i> <?php echo $article['views']; ?> vistas
                    </span>
                </div>
            </div>
            
            <?php if(is_logged_in()): ?>
                <div class="header-actions">
                    <a href="wiki-edit.php?id=<?php echo $article['id']; ?>" class="btn-edit">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                </div>
            <?php endif; ?>
        </header>

        <div class="wiki-content-body">
            <?php echo nl2br(htmlspecialchars($article['content'])); ?>
        </div>
        
    </article>
</div>

<style>
/* ================= 浅色水墨风 (White > Gray > Red) ================= */
@import url('https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;700;900&display=swap');

/* 核心修复：将全局布局设为弹性盒子，高度最小为屏幕高度(100vh) */
body {
    background-color: #F7F7F7 !important;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.light-theme-bg {
    position: fixed; inset: 0; z-index: -10;
    background-color: #F7F7F7;
    background-image: radial-gradient(circle at 50% 0%, #FFFFFF 0%, transparent 70%);
}

/* 容器居中排版，并利用 flex: 1 撑开剩余空间，把页脚往下挤 */
.article-container {
    flex: 1; /* 占据剩余全部空间 */
    width: 100%;
    max-width: 900px;
    margin: 50px auto 60px auto; 
    padding: 0 20px;
    font-family: 'Noto Serif SC', serif;
    box-sizing: border-box;
}

/* 面包屑导航 */
.breadcrumb {
    margin-bottom: 20px;
    font-size: 0.95em;
    color: #666666;
    font-family: sans-serif;
}
.breadcrumb a {
    color: #9e1b1b; /* 朱砂红 */
    text-decoration: none;
    font-weight: bold;
    transition: color 0.3s;
}
.breadcrumb a:hover {
    color: #cc2929;
}
.breadcrumb .separator {
    margin: 0 8px;
    color: #aaaaaa;
}

/* 文章纯白卡片 */
.article-card {
    background: #FFFFFF;
    padding: 60px;
    border-radius: 4px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.04);
    border-top: 4px solid #9e1b1b; /* 顶部朱砂红点缀线 */
    position: relative;
}

/* 文章头部 */
.article-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 1px dashed #DDDDDD; /* 浅灰色虚线分割 */
    padding-bottom: 25px;
    margin-bottom: 40px;
}

.header-content {
    flex: 1;
    padding-right: 20px;
}

.article-title {
    color: #1A1A1A; /* 极深灰，接近黑 */
    font-size: 2.8em;
    margin: 0 0 15px 0;
    font-weight: 900;
    line-height: 1.3;
    letter-spacing: 1px;
}

/* 文章元数据 (作者、时间、浏览量) */
.article-meta {
    color: #777777; /* 中灰色 */
    font-size: 0.9em;
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: center;
    font-family: sans-serif;
}
.article-meta i {
    color: #bbbbbb; /* 图标更浅一点的灰 */
    margin-right: 5px;
}
.article-meta strong {
    color: #333333;
}

/* 编辑按钮 */
.btn-edit {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: transparent;
    color: #9e1b1b; /* 红色文字 */
    border: 1px solid #9e1b1b; /* 红色边框 */
    border-radius: 4px;
    text-decoration: none;
    font-weight: bold;
    font-family: sans-serif;
    font-size: 0.9em;
    transition: all 0.3s;
}
.btn-edit:hover {
    background: #9e1b1b;
    color: #FFFFFF;
    box-shadow: 0 4px 12px rgba(158, 27, 27, 0.2);
}

/* 正文排版 */
.wiki-content-body {
    font-size: 1.15em;
    line-height: 1.8;
    color: #333333; /* 正文深灰，保证阅读舒适度 */
    overflow-wrap: break-word; 
    word-wrap: break-word;     
    word-break: break-word;    
}

/* 响应式调整 (手机端) */
@media (max-width: 768px) {
    .article-container { margin-top: 30px; }
    .article-card { padding: 30px 20px; }
    .article-header { flex-direction: column; gap: 20px; }
    .article-title { font-size: 2em; }
    .article-meta { gap: 10px; flex-direction: column; align-items: flex-start; }
}
</style>

<?php include 'includes/footer.php'; ?>