<?php
// guides.php - 100% 完整版 (死亡笔记 / The Shinigami's Rule - 极简哥特法则风)
require_once 'config.php';

$search = sanitize($_GET['search'] ?? '');
$filter_category = sanitize($_GET['category'] ?? '');
$filter_difficulty = sanitize($_GET['difficulty'] ?? '');

$categories = [];
$popular_guides = [];
$recent_guides = [];
$search_results = [];

// 死神的印记
function get_guide_icon($cat_name) {
    $name = strtolower($cat_name);
    if (in_array($name, ['weapons', 'armas'])) return 'fa-crosshairs';
    if (in_array($name, ['heroes', 'personajes'])) return 'fa-chess-king';
    if (in_array($name, ['map', 'mapas'])) return 'fa-globe-asia';
    if (in_array($name, ['mechanics', 'mecánicas'])) return 'fa-link';
    return 'fa-skull'; 
}

try {
    $pdo = db_connect();

    $categories = $pdo->query("SELECT category, COUNT(*) as count FROM articles WHERE is_published = 1 GROUP BY category ORDER BY category ASC")->fetchAll();

    $where = ["a.is_published = 1"];
    $params = [];
    
    if (!empty($search)) {
        $where[] = "(a.title LIKE ? OR a.content LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if (!empty($filter_category)) {
        $where[] = "a.category = ?";
        $params[] = $filter_category;
    }
    if (!empty($filter_difficulty)) {
        $where[] = "a.difficulty = ?";
        $params[] = $filter_difficulty;
    }

    $where_sql = "WHERE " . implode(" AND ", $where);
    $is_searching = !empty($search) || !empty($filter_category) || !empty($filter_difficulty);

    if ($is_searching) {
        $stmt = $pdo->prepare("
            SELECT a.id, a.title, a.views, a.difficulty, a.category, a.created_at, u.username 
            FROM articles a LEFT JOIN users u ON a.user_id = u.id
            $where_sql ORDER BY a.views DESC
        ");
        $stmt->execute($params);
        $search_results = $stmt->fetchAll();
    } else {
        $popular_guides = $pdo->query("
            SELECT a.id, a.title, a.views, a.difficulty, a.category, u.username 
            FROM articles a LEFT JOIN users u ON a.user_id = u.id
            WHERE a.is_published = 1 ORDER BY a.views DESC LIMIT 3
        ")->fetchAll();

        $recent_guides = $pdo->query("
            SELECT a.id, a.title, a.created_at, a.views, a.difficulty, a.category, u.username 
            FROM articles a LEFT JOIN users u ON a.user_id = u.id
            WHERE a.is_published = 1 ORDER BY a.created_at DESC LIMIT 10
        ")->fetchAll();
    }
} catch (Exception $e) {
    $error = "Error al cargar las guías: " . $e->getMessage();
}
?>
<?php include 'includes/header.php'; ?>

<div class="dn-global-bg">
    <div class="dn-rule-watermark">
        The human whose name is written in this note shall die.<br>
        This note will not take effect unless the writer has the person's face in their mind when writing his/her name.
    </div>
</div>

<div class="dn-judgement-line"></div>

<div class="dn-layout">
    
    <aside class="dn-sidebar">
        <div class="dn-logo-k">G</div> <ul class="dn-menu">
            <li>
                <a href="guides.php" class="dn-link <?php echo empty($filter_category) ? 'active' : ''; ?>">
                    <span class="dn-text">ALL</span>
                </a>
            </li>
            <?php foreach($categories as $cat): ?>
                <li>
                    <a href="guides.php?category=<?php echo urlencode($cat['category']); ?>" class="dn-link <?php echo $filter_category == $cat['category'] ? 'active' : ''; ?>">
                        <span class="dn-text"><?php echo htmlspecialchars(strtoupper($cat['category'])); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="dn-author-sign">
            <span>[ SYSTEM BY JIAHAO ]</span>
        </div>
    </aside>

    <main class="dn-main">
        
        <header class="dn-header">
            <h1 class="dn-title">RULES OF SURVIVAL</h1>
            <p class="dn-subtitle">Only the one who controls the rules, controls the world.</p>
        </header>

        <?php if (isset($error)): ?>
            <div class="dn-error">[ RULE ERROR ] <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="dn-toolbar">
            <form method="GET" action="guides.php" class="dn-search-form">
                <div class="dn-search-slash">
                    <span class="dn-search-label">TARGET:</span>
                    <input type="text" name="search" placeholder="Type a name or tactic..." value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                    <?php if(!empty($filter_category)): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($filter_category); ?>">
                    <?php endif; ?>
                    <select name="difficulty" class="dn-select">
                        <option value="">CLASS (ALL)</option>
                        <option value="beginner" <?php echo $filter_difficulty == 'beginner' ? 'selected' : ''; ?>>PAWN</option>
                        <option value="intermediate" <?php echo $filter_difficulty == 'intermediate' ? 'selected' : ''; ?>>KNIGHT</option>
                        <option value="advanced" <?php echo $filter_difficulty == 'advanced' ? 'selected' : ''; ?>>KIRA</option>
                    </select>
                    <button type="submit" class="dn-btn-write">EXECUTE</button>
                </div>
            </form>
            
            <div class="dn-actions">
                <?php if (is_logged_in()): ?>
                    <a href="new-guide.php" class="dn-btn-blood">WRITE NAME</a>
                <?php else: ?>
                    <a href="login.php" class="dn-btn-ghost">ACCESS DENIED</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($is_searching): ?>
            
            <section class="dn-section">
                <div class="dn-section-header">
                    <h2><i class="fas fa-search"></i> INVESTIGATION RESULTS</h2>
                    <a href="guides.php" class="dn-clear-btn">ERASE [X]</a>
                </div>
                
                <?php if(empty($search_results)): ?>
                    <div class="dn-empty">TARGET NOT FOUND.</div>
                <?php else: ?>
                    <div class="dn-list">
                        <?php foreach($search_results as $guide): ?>
                            <a href="article.php?id=<?php echo $guide['id']; ?>" class="dn-list-item">
                                <div class="dn-item-class diff-<?php echo $guide['difficulty']; ?>"></div>
                                <div class="dn-item-content">
                                    <div class="dn-item-cat"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></div>
                                    <h3 class="dn-item-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                </div>
                                <div class="dn-item-author">
                                    <span style="font-size:0.7em; color:#555;">IDENTIFIED BY</span><br>
                                    <?php echo htmlspecialchars(strtoupper($guide['username'])); ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        <?php else: ?>
            
            <section class="dn-section">
                <div class="dn-section-header">
                    <h2>CLASSIFIED FILES</h2>
                    <span style="color:var(--dn-red); font-family:monospace; letter-spacing:2px;">[ TOP PRIORITY ]</span>
                </div>

                <div class="dn-classified-grid">
                    <?php if(!empty($popular_guides)): ?>
                        <?php $rank = 1; foreach($popular_guides as $guide): ?>
                            <a href="article.php?id=<?php echo $guide['id']; ?>" class="dn-classified-card" style="animation-delay: <?php echo $rank * 0.15; ?>s;">
                                <div class="cf-rank">Nº <?php echo $rank; ?></div>
                                <div class="cf-top-line diff-bg-<?php echo $guide['difficulty']; ?>"></div>
                                <div class="cf-content">
                                    <div class="cf-cat">#<?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></div>
                                    <h3 class="cf-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                    <div class="cf-meta">
                                        <span><i class="fas fa-eye"></i> <?php echo $guide['views']; ?></span>
                                        <span class="cf-author"><?php echo htmlspecialchars(strtoupper($guide['username'])); ?></span>
                                    </div>
                                </div>
                            </a>
                        <?php $rank++; endforeach; ?>
                    <?php else: ?>
                        <div class="dn-empty">NO FILES FOUND.</div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="dn-section" style="margin-top: 100px;">
                <div class="dn-section-header">
                    <h2>RECENT JUDGEMENTS</h2>
                </div>

                <div class="dn-death-list">
                    <?php if(!empty($recent_guides)): ?>
                        <?php foreach($recent_guides as $guide): ?>
                            <a href="article.php?id=<?php echo $guide['id']; ?>" class="dn-death-item">
                                <div class="dl-time"><?php echo date('H:i:s / d.m', strtotime($guide['created_at'])); ?></div>
                                <div class="dl-line diff-bg-<?php echo $guide['difficulty']; ?>"></div>
                                <div class="dl-content">
                                    <h3 class="dl-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                </div>
                                <div class="dl-cat"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></div>
                                <div class="dl-author"><?php echo htmlspecialchars(strtoupper($guide['username'])); ?></div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="dn-empty">NO NEW ENTRIES.</div>
                    <?php endif; ?>
                </div>
            </section>

        <?php endif; ?>

    </main>
</div>

<style>
/* ================= 死亡笔记·KIRA法则风 (The Shinigami's Rule Style) ================= */
/* 引入类似 Death Note 中的哥特字体 (Cloister Black 替代品) 和 极简机能等宽字体 */
@import url('https://fonts.googleapis.com/css2?family=UnifrakturMaguntia&family=Share+Tech+Mono&family=Oswald:wght@400;700&display=swap');

:root {
    --dn-bg: #090909;        /* 极致的漆黑底色 */
    --dn-paper: #121212;     /* 极暗的纸张灰 */
    --dn-white: #e0e0e0;     /* 冰冷的惨白字 */
    --dn-red: #c00000;       /* 刺眼的死神红 */
    --dn-blue: #4a90e2;      /* L 的智斗蓝 */
    --dn-muted: #555555;     
    --dn-ease: cubic-bezier(0.2, 0, 0, 1);
}

body { background-color: var(--dn-bg) !important; color: var(--dn-white); font-family: 'Share Tech Mono', monospace; overflow-x: hidden; }
h1, h2, h3 { font-family: 'Oswald', sans-serif; font-weight: 700; color: var(--dn-white) !important; text-transform: uppercase;}

/* 死亡笔记法则背景 */
.dn-global-bg {
    position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
    background: radial-gradient(circle at center, rgba(255,255,255,0.02) 0%, transparent 80%), var(--dn-bg);
    z-index: -10; pointer-events: none; 
}
.dn-rule-watermark {
    position: absolute; left: 5%; top: 5%;
    font-family: 'UnifrakturMaguntia', cursive; /* 哥特字体 */
    font-size: 3vw; color: rgba(255,255,255,0.03); line-height: 1.5;
    user-select: none; max-width: 80%;
}

/* 贯穿全屏的裁决红线 */
.dn-judgement-line {
    position: fixed; left: 140px; top: 0; bottom: 0; width: 1px; background: var(--dn-red);
    z-index: 10; opacity: 0.5; box-shadow: 0 0 10px rgba(192,0,0,0.8);
}

/* ================= L 风格的绝对冷静侧边栏 ================= */
.dn-layout { display: flex; align-items: flex-start; min-height: calc(100vh - 100px); padding-bottom: 50px; position:relative; z-index: 20;}

.dn-sidebar {
    width: 140px; flex-shrink: 0; background: transparent; 
    position: sticky; top: 100px; height: calc(100vh - 120px); 
    display: flex; flex-direction: column; justify-content: space-between;
    padding: 0 0 40px 0; z-index: 50; align-items: center;
}

/* 哥特式标志大写字母 (类似 L 的标志) */
.dn-logo-k {
    font-family: 'UnifrakturMaguntia', cursive;
    font-size: 6em; color: var(--dn-white); text-shadow: 0 0 20px rgba(255,255,255,0.5);
    line-height: 1; margin-bottom: 50px;
}

.dn-menu { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 30px; align-items: center; width: 100%;}
.dn-link {
    display: block; text-decoration: none; color: var(--dn-muted); transition: color 0.3s;
    font-size: 1.2em; letter-spacing: 3px; position: relative; width: 100%; text-align: center;
}
/* 悬停时的红色中划线 (仿佛名字被划掉) */
.dn-link::after {
    content: ''; position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);
    width: 0; height: 2px; background: var(--dn-red); transition: 0.3s var(--dn-ease);
}
.dn-link:hover, .dn-link.active { color: var(--dn-white); }
.dn-link.active::after { width: 60%; box-shadow: 0 0 10px var(--dn-red); }

.dn-author-sign { font-size: 0.75em; color: var(--dn-muted); text-align: center; letter-spacing: 1px; opacity:0.5;}

/* ================= 审判庭主视界 ================= */
.dn-main { flex: 1; padding: 40px 4vw 100px 4vw; max-width: 1500px; animation: kxFadeIn 1s var(--dn-ease) forwards; position: relative;}
@keyframes kxFadeIn { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

/* 冰冷机能风标题 */
.dn-header { margin-bottom: 60px; border-bottom: 1px solid #222; padding-bottom: 30px; }
.dn-title { font-size: 4em; margin: 0 0 10px 0; color: var(--dn-white) !important; letter-spacing: 5px; }
.dn-subtitle { color: var(--dn-muted); font-size: 1.1em; letter-spacing: 2px; margin: 0; }

/* 调查系统搜索区 (极简命令行风格) */
.dn-toolbar { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 80px; flex-wrap: wrap; gap: 40px; }
.dn-search-form { flex: 1; max-width: 800px; }
.dn-search-slash {
    display: flex; align-items: center; border: 1px solid #333; background: #050505; padding: 5px 5px 5px 20px; transition: 0.3s;
}
.dn-search-slash:focus-within { border-color: var(--dn-red); box-shadow: 0 0 20px rgba(192,0,0,0.2); }
.dn-search-label { color: var(--dn-red); font-weight: bold; margin-right: 15px; letter-spacing: 2px; }
.dn-search-slash input { flex: 1; background: transparent; border: none; color: var(--dn-white) !important; font-size: 1.1em; font-family: 'Share Tech Mono', monospace; outline: none; }
.dn-search-slash input::placeholder { color: #444; }
.dn-select { background: #111; color: var(--dn-white); border: none; padding: 12px 20px; font-size: 1em; outline: none; cursor: pointer; font-family: 'Share Tech Mono', monospace; }
.dn-btn-write { background: var(--dn-white); color: #000; border: none; padding: 12px 30px; font-weight: bold; font-family: 'Share Tech Mono', monospace; cursor: pointer; transition: 0.3s; margin-left: 5px;}
.dn-btn-write:hover { background: var(--dn-red); color: #fff; }

/* 按钮区 */
.dn-actions { display: flex; gap: 15px; }
.dn-btn-blood {
    display: inline-flex; align-items: center; justify-content: center; height: 50px; padding: 0 30px;
    background: var(--dn-red); color: #fff; text-decoration: none; font-weight: bold; letter-spacing: 2px;
    transition: 0.3s; border: none; font-size: 1.1em;
}
.dn-btn-blood:hover { background: #fff; color: var(--dn-red); box-shadow: 0 0 20px rgba(192,0,0,0.5); }
.dn-btn-ghost {
    display: inline-flex; align-items: center; justify-content: center; height: 50px; padding: 0 30px;
    background: transparent; color: var(--dn-muted); border: 1px solid var(--dn-muted); text-decoration: none; font-weight: bold; letter-spacing: 2px; transition: 0.3s;
}
.dn-btn-ghost:hover { border-color: var(--dn-red); color: var(--dn-red); }


/* ================= 绝密档案卡片 (Classified Files) ================= */
.dn-section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.dn-section-header h2 { font-size: 1.5em; letter-spacing: 3px; margin: 0; color: var(--dn-white); }

.dn-classified-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; }

.dn-classified-card {
    background: var(--dn-paper); border: 1px solid #222; position: relative; overflow: hidden; text-decoration: none;
    transition: 0.4s var(--dn-ease); display: flex; flex-direction: column; padding: 30px;
    animation: kxFadeIn 0.8s var(--dn-ease) forwards; opacity: 0;
}
.cf-rank { font-size: 0.8em; color: var(--dn-muted); margin-bottom: 15px; letter-spacing: 2px; }
.cf-top-line { position: absolute; top: 0; left: 0; width: 100%; height: 3px; background: #333; transition: 0.4s; }
.dn-classified-card:hover { transform: translateY(-5px); background: #1a1a1a; border-color: #444; }
.dn-classified-card:hover .cf-top-line { height: 6px; }

.cf-content { flex: 1; }
.cf-cat { color: var(--dn-blue); font-size: 0.9em; margin-bottom: 10px; } /* L 的蓝色点缀 */
.cf-title { font-size: 1.4em; color: var(--dn-white) !important; margin: 0 0 30px 0; line-height: 1.4; transition: 0.3s;}
.dn-classified-card:hover .cf-title { color: var(--dn-red) !important; }
.cf-meta { display: flex; justify-content: space-between; color: var(--dn-muted); font-size: 0.85em; border-top: 1px dashed #333; padding-top: 15px;}
.cf-author { font-weight: bold; color: #888; }


/* ================= 死亡裁决名单列表 (Death List) ================= */
.dn-death-list { display: flex; flex-direction: column; border-top: 1px solid #222; }
.dn-death-item {
    display: flex; align-items: center; padding: 20px 0; border-bottom: 1px solid #1a1a1a;
    text-decoration: none; transition: 0.3s; position: relative;
}
.dn-death-item:hover { background: rgba(255,255,255,0.02); padding-left: 15px; border-bottom-color: #333; }

.dl-time { width: 120px; color: var(--dn-muted); font-size: 0.9em; }
.dl-line { width: 3px; height: 20px; background: #333; margin-right: 25px; transition: 0.3s; }
.dn-death-item:hover .dl-line { transform: scaleY(1.5); }
.dl-content { flex: 1; }
.dl-title { margin: 0; font-size: 1.2em; color: var(--dn-white); transition: 0.3s; font-family: 'Share Tech Mono', monospace; font-weight: normal; }
.dn-death-item:hover .dl-title { color: var(--dn-red); text-decoration: line-through; } /* 名字被划掉的特效 */

.dl-cat { width: 150px; color: #666; font-size: 0.85em; text-align: center; }
.dl-author { width: 150px; color: var(--dn-blue); font-weight: bold; text-align: right; }


/* 难度颜色 (机能风) */
.diff-bg-beginner { background: #5bc265 !important; }
.diff-bg-intermediate { background: #f09530 !important; }
.diff-bg-advanced { background: var(--dn-red) !important; }
.diff-beginner { border-left: 3px solid #5bc265 !important; }
.diff-intermediate { border-left: 3px solid #f09530 !important; }
.diff-advanced { border-left: 3px solid var(--dn-red) !important; }

/* 搜索结果列表 */
.dn-list { display: flex; flex-direction: column; gap: 15px; }
.dn-list-item { display: flex; align-items: center; background: var(--dn-paper); border: 1px solid #222; padding: 25px; text-decoration: none; transition: 0.3s; }
.dn-list-item:hover { border-color: var(--dn-red); transform: translateX(10px); }
.dn-item-class { width: 4px; height: 40px; margin-right: 20px; }
.dn-item-content { flex: 1; }
.dn-item-cat { color: var(--dn-muted); font-size: 0.8em; margin-bottom: 5px; }
.dn-item-title { margin: 0; font-size: 1.3em; color: var(--dn-white); }
.dn-list-item:hover .dn-item-title { color: var(--dn-red); }
.dn-item-author { text-align: right; color: var(--dn-white); font-weight: bold; }
.dn-clear-btn { color: var(--dn-red); text-decoration: none; border: 1px solid var(--dn-red); padding: 5px 15px; font-size: 0.9em; transition: 0.3s; }
.dn-clear-btn:hover { background: var(--dn-red); color: #fff; }
.dn-empty { padding: 40px; text-align: center; color: var(--dn-muted); font-size: 1.2em; border: 1px dashed #333; }

/* 手机端适配 */
@media (max-width: 1000px) {
    .dn-judgement-line { display: none; }
    .dn-layout { flex-direction: column; }
    .dn-sidebar { width: 100%; height: auto; position: static; flex-direction: row; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 1px solid #222; }
    .dn-logo-k { margin: 0; font-size: 3em; }
    .dn-menu { flex-direction: row; gap: 20px; width: auto; }
    .dn-link { width: auto; font-size: 1em; }
    .dn-author-sign { display: none; }
    
    .dn-main { padding: 40px 20px; }
    .dn-title { font-size: 2.5em; }
    .dn-toolbar { flex-direction: column; align-items: stretch; gap: 20px; }
    .dn-search-slash { flex-direction: column; align-items: stretch; background: transparent; border: none; padding: 0; gap: 10px;}
    .dn-search-label { display: none; }
    .dn-search-slash input, .dn-select, .dn-btn-write { border: 1px solid #333; background: var(--dn-paper); padding: 15px; margin: 0; }
    .dn-actions { width: 100%; }
    .dn-btn-blood, .dn-btn-ghost { width: 100%; }
    
    .dn-death-item { flex-direction: column; align-items: flex-start; gap: 10px; padding: 20px; border: 1px solid #222; margin-bottom: 10px; background: var(--dn-paper); }
    .dn-death-item:hover { padding-left: 20px; }
    .dl-line, .dl-cat { display: none; }
    .dl-author { text-align: left; }
}
</style>

<?php include 'includes/footer.php'; ?>