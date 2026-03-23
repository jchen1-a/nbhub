<?php
// guides.php - 100% 完整版 (Arknights 深色模式特化版 // 夜间战术 // 完美边框)
require_once 'config.php';

$search = sanitize($_GET['search'] ?? '');
$filter_category = sanitize($_GET['category'] ?? '');
$filter_difficulty = sanitize($_GET['difficulty'] ?? '');

$categories = [];
$popular_guides = [];
$recent_guides = [];
$search_results = [];

function get_guide_icon($cat_name) {
    $name = strtolower($cat_name);
    if (in_array($name, ['weapons', 'armas'])) return 'fa-khanda';
    if (in_array($name, ['heroes', 'personajes'])) return 'fa-user-ninja';
    if (in_array($name, ['map', 'mapas'])) return 'fa-map-marked-alt';
    if (in_array($name, ['mechanics', 'mecánicas'])) return 'fa-cogs';
    return 'fa-scroll'; 
}

try {
    $pdo = db_connect();
    $categories = $pdo->query("SELECT category, COUNT(*) as count FROM articles WHERE is_published = 1 GROUP BY category ORDER BY category ASC")->fetchAll();
    $where = ["a.is_published = 1"];
    $params = [];
    if (!empty($search)) { $where[] = "(a.title LIKE ? OR a.content LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
    if (!empty($filter_category)) { $where[] = "a.category = ?"; $params[] = $filter_category; }
    if (!empty($filter_difficulty)) { $where[] = "a.difficulty = ?"; $params[] = $filter_difficulty; }
    $where_sql = "WHERE " . implode(" AND ", $where);
    $is_searching = !empty($search) || !empty($filter_category) || !empty($filter_difficulty);

    if ($is_searching) {
        $stmt = $pdo->prepare("SELECT a.id, a.title, a.views, a.difficulty, a.category, a.created_at, u.username FROM articles a LEFT JOIN users u ON a.user_id = u.id $where_sql ORDER BY a.views DESC");
        $stmt->execute($params);
        $search_results = $stmt->fetchAll();
    } else {
        $popular_guides = $pdo->query("SELECT a.id, a.title, a.views, a.difficulty, a.category, u.username FROM articles a LEFT JOIN users u ON a.user_id = u.id WHERE a.is_published = 1 ORDER BY a.views DESC LIMIT 3")->fetchAll();
        $recent_guides = $pdo->query("SELECT a.id, a.title, a.created_at, a.views, a.difficulty, a.category, u.username FROM articles a LEFT JOIN users u ON a.user_id = u.id WHERE a.is_published = 1 ORDER BY a.created_at DESC LIMIT 10")->fetchAll();
    }
} catch (Exception $e) { $error = "SYS_ERR_DEEP: " . $e->getMessage(); }
?>
<?php include 'includes/header.php'; ?>

<div class="ak-global-bg">
    <div class="ak-bg-grid"></div>
    <div class="ak-watermark">
        <span>MARTIAL</span><br><span>ARCHIVES</span>
    </div>
</div>

<div class="ak-layout">
    
    <aside class="ak-sidebar">
        <div class="ak-sidebar-inner">
            <div class="ak-nav-indicator"></div>
            <ul class="ak-menu">
                <li>
                    <a href="guides.php" class="ak-link <?php echo empty($filter_category) ? 'active' : ''; ?>">
                        <span class="ak-text">ALL_DATA</span>
                        <div class="ak-link-deco"></div>
                    </a>
                </li>
                <?php foreach($categories as $cat): ?>
                    <li>
                        <a href="guides.php?category=<?php echo urlencode($cat['category']); ?>" class="ak-link <?php echo $filter_category == $cat['category'] ? 'active' : ''; ?>">
                            <span class="ak-text"><?php echo htmlspecialchars(strtoupper($cat['category'])); ?></span>
                            <div class="ak-link-deco"></div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>

    <main class="ak-main">
        
        <header class="ak-header">
            <div class="ak-quote-container-wrap">
                <div class="ak-border-fix-layer">
                    <div class="ak-quote-panel">
                        <div class="ak-quote-text-container">
                            <span class="ak-quote-line">我身无拘</span>
                            <span class="ak-quote-line" style="color: var(--ak-red);">武道无穷</span>
                        </div>
                        <div class="ak-quote-sys">// SYS_NIGHT_OVERRIDE_ENABLED</div>
                    </div>
                </div>
            </div>

            <div class="ak-title-wrapper">
                <div class="ak-title-prefix">NIGHT // DB_ACCESS</div>
                <h1 class="ak-title">武道卷宗</h1>
            </div>
            <div class="ak-blade-line"></div>
        </header>

        <?php if (isset($error)): ?>
            <div class="ak-error">SYS_ERR_CRITICAL: <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="ak-toolbar">
            <form method="GET" action="guides.php" class="ak-search-form">
                <div class="ak-border-fix-layer">
                    <div class="ak-search-slash">
                        <span class="ak-search-icon"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" placeholder="INPUT QUERY STR..." value="<?php echo htmlspecialchars($search); ?>">
                        <?php if(!empty($filter_category)): ?>
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($filter_category); ?>">
                        <?php endif; ?>
                        <select name="difficulty" class="ak-select">
                            <option value="">CLASS: ALL</option>
                            <option value="beginner" <?php echo $filter_difficulty == 'beginner' ? 'selected' : '';?>>初境 // LV.1</option>
                            <option value="intermediate" <?php echo $filter_difficulty == 'intermediate' ? 'selected' : ''; ?>>入微 // LV.2</option>
                            <option value="advanced" <?php echo $filter_difficulty == 'advanced' ? 'selected' : ''; ?>>化境 // LV.3</option>
                        </select>
                        <button type="submit" class="ak-btn-icon-submit">SEARCH</button>
                    </div>
                </div>
            </form>
            
            <div class="ak-actions">
                <div class="ak-quote-small"><span class="ak-red-dot"></span>「兵无常势，水无常形」</div>
                <?php if (is_logged_in()): ?>
                    <a href="new-guide.php" class="ak-btn-primary"><span>编撰卷宗</span> <i class="fas fa-chevron-right"></i></a>
                <?php else: ?>
                    <a href="login.php" class="ak-btn-outline"><span>步入武林</span> <i class="fas fa-sign-in-alt"></i></a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($is_searching): ?>
            
            <section class="ak-section">
                <div class="ak-section-header">
                    <h2>QUERY_RESULT_DINK</h2>
                    <a href="guides.php" class="ak-clear-btn">CLEAR_FILTERS [X]</a>
                </div>
                
                <?php if(empty($search_results)): ?>
                    <div class="ak-empty">// NULL_REFERENCE: 未寻得夜间记录。</div>
                <?php else: ?>
                    <div class="ak-grid">
                        <?php foreach($search_results as $guide): ?>
                            <a href="article.php?id=<?php echo $guide['id']; ?>" class="ak-result-card-wrap diff-hover-<?php echo $guide['difficulty']; ?>">
                                <div class="ak-border-fix-layer">
                                    <div class="ak-result-card">
                                        <div class="ak-rc-meta">
                                            <span class="ak-tag"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></span>
                                            <span class="ak-tag-diff diff-bg-<?php echo $guide['difficulty']; ?>"><?php echo htmlspecialchars(strtoupper($guide['difficulty'])); ?></span>
                                        </div>
                                        <h3 class="ak-rc-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                        <div class="ak-rc-footer">
                                            <span>OP. <?php echo htmlspecialchars(strtoupper($guide['username'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        <?php else: ?>
            
            <section class="ak-section tabbed-module">
                
                <div class="ak-tab-controls">
                    <button class="ak-tab-btn active" onclick="switchTab('popular', this)">
                        <div class="ak-tab-inner">
                            <span class="tab-cn">武道巅峰</span>
                            <span class="tab-es">LÍDERES</span>
                        </div>
                    </button>
                    <button class="ak-tab-btn" onclick="switchTab('recent', this)">
                        <div class="ak-tab-inner">
                            <span class="tab-cn">最新阅览</span>
                            <span class="tab-es">RECIENTES</span>
                        </div>
                    </button>
                </div>

                <div id="tab-popular" class="ak-tab-content active-tab">
                    <div class="ak-throne-grid">
                        <?php if(!empty($popular_guides)): ?>
                            <?php $rank = 1; foreach($popular_guides as $guide): ?>
                                
                                <?php if($rank == 1): ?>
                                <a href="article.php?id=<?php echo $guide['id']; ?>" class="ak-boss-card-wrap">
                                    <div class="ak-border-fix-layer">
                                        <div class="ak-boss-card diff-border-<?php echo $guide['difficulty']; ?>">
                                            <div class="ak-boss-bg-text">01</div>
                                            <div class="ak-boss-stripes"></div>
                                            <div class="boss-content">
                                                <div class="boss-icon-wrap"><i class="fas <?php echo get_guide_icon($guide['category']); ?> boss-icon"></i></div>
                                                <div class="boss-info">
                                                    <div class="boss-tags">
                                                        <span class="ak-tag-diff diff-bg-<?php echo $guide['difficulty']; ?>"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></span>
                                                        <span class="ak-tag-maestro">MAESTRO</span>
                                                    </div>
                                                    <h3 class="boss-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                                    <div class="boss-meta">
                                                        <span><i class="fas fa-eye"></i> <?php echo $guide['views']; ?></span>
                                                        <span>AUTHOR // <strong><?php echo htmlspecialchars(strtoupper($guide['username'])); ?></strong></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                
                                <?php else: ?>
                                <a href="article.php?id=<?php echo $guide['id']; ?>" class="ak-challenger-card-wrap">
                                    <div class="ak-border-fix-layer">
                                        <div class="ak-challenger-card diff-border-<?php echo $guide['difficulty']; ?>">
                                            <div class="ak-rank-badge">0<?php echo $rank; ?></div>
                                            <div class="challenger-icon"><i class="fas <?php echo get_guide_icon($guide['category']); ?>"></i></div>
                                            <div class="challenger-content">
                                                <span class="ak-tag-diff diff-bg-<?php echo $guide['difficulty']; ?>" style="margin-bottom:10px; display:inline-block;"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></span>
                                                <h3 class="challenger-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                            </div>
                                            <div class="challenger-author">OP. <?php echo htmlspecialchars(strtoupper($guide['username'])); ?></div>
                                        </div>
                                    </div>
                                </a>
                                <?php endif; ?>

                            <?php $rank++; endforeach; ?>
                        <?php else: ?>
                            <div class="ak-empty">// NULL_REFERENCE: SYSTEM IDLE NIGHT.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="tab-recent" class="ak-tab-content" style="display: none;">
                    <div class="ak-clean-list">
                        <?php if(!empty($recent_guides)): ?>
                            <?php foreach($recent_guides as $guide): ?>
                                <a href="article.php?id=<?php echo $guide['id']; ?>" class="ak-list-item-wrap diff-hover-<?php echo $guide['difficulty']; ?>">
                                    <div class="ak-border-fix-layer">
                                        <div class="ak-list-item">
                                            <div class="list-item-accent diff-bg-<?php echo $guide['difficulty']; ?>"></div>
                                            <div class="list-item-icon">
                                                <i class="fas <?php echo get_guide_icon($guide['category']); ?>"></i>
                                            </div>
                                            <div class="list-item-content">
                                                <h3 class="list-item-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                                <span class="ak-tag" style="background:var(--ak-muted); color:#fff; border:none; padding: 2px 8px; width: max-content; font-size: 0.7em;"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></span>
                                            </div>
                                            <div class="list-item-meta">
                                                <div class="list-author"><?php echo htmlspecialchars(strtoupper($guide['username'])); ?></div>
                                                <div class="list-date"><?php echo date('d / m / Y', strtotime($guide['created_at'])); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="ak-empty">// NO NEW ENTRIES_NIGHT.</div>
                        <?php endif; ?>
                    </div>
                </div>

            </section>

        <?php endif; ?>

    </main>
</div>

<style>
/* ================= 明日方舟·夜间机能风 (Arknights Overt Dark Tech) ================= */
@import url('https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@700;900&family=Oswald:wght@400;500;700&family=Roboto+Mono:wght@400;700&display=swap');

:root {
    /* 彻底切换为深色配色体系 */
    --ak-bg: #111111;          /* 纯黑 */
    --ak-bg-terminal: #1a1a1a; /* 极暗灰终端 */
    --ak-card: #222222;        /* 暗灰卡片 */
    --ak-text: #eaeaea;        /* 亮白文字 */
    --ak-muted: #5a5a5a;       /* 暗灰文字 */
    --ak-red: #d32f2f;         /* 方舟朱砂红 */
    --ak-border: #333333;      /* 深灰边框 */
    --ak-shadow: 6px 6px 0 rgba(0, 0, 0, 0.2); 
    --ak-shadow-hover: 12px 12px 0 rgba(0, 0, 0, 0.4); 
    --ak-ease: cubic-bezier(0.19, 1, 0.22, 1);
}

body { background-color: var(--ak-bg) !important; color: var(--ak-text); font-family: 'Roboto Mono', 'Oswald', sans-serif; overflow-x: hidden; }
h1, h2, h3 { font-family: 'Noto Serif SC', serif; font-weight: 900; color: var(--ak-text) !important; letter-spacing: 1px; }

/* ==== 背景层 (深色网格与巨型标尺) ==== */
.ak-global-bg { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -10; pointer-events: none; }
.ak-bg-grid { 
    width: 100%; height: 100%; opacity: 0.1; /* 降低网格透明度 */
    background-image: linear-gradient(var(--ak-border) 1px, transparent 1px), linear-gradient(90deg, var(--ak-border) 1px, transparent 1px);
    background-size: 30px 30px;
}
.ak-watermark {
    position: absolute; top: 15%; left: 5%;
    font-family: 'Oswald', sans-serif; font-size: 15vw; font-weight: 900; line-height: 0.8;
    color: rgba(255,255,255,0.015); transform: rotate(-5deg); white-space: nowrap;
}

/* ================= ⚔️ 边框修复核心技巧 (The Clip-path Border Fix) ================= */
/* 当我们使用 clip-path 时，默认的 native border 会被无情裁剪。
   我们将原本在 .ak-quote-panel 上使用的裁剪、背景和边框，全部转移到 .ak-border-fix-layer 上。
   我们利用 filter: drop-shadow() 的特性（可以应用在被 clip-path 裁剪后的不规则形状上），
   通过叠加四个硬阴影，完美模拟出被裁剪掉的“边框”。
*/
.ak-border-fix-layer {
    position: relative;
    /* 应用裁剪 */
    clip-path: var(--ak-cut-path, none);
    /* 用背景色（黑色边框色）撑满整个层 */
    background: var(--ak-dark-border, var(--ak-text)); 
    /* 应用滤镜链模拟不规则斜边边框 */
    filter: var(--ak-filter-fix, none);
    display: flex; /* 撑满父容器 */
    flex: 1;
}

/* 内部真实面板：向内缩进 2px (边框厚度) */
.ak-quote-panel, .ak-search-slash, .ak-boss-card, .ak-challenger-card, .ak-result-card, .ak-list-item {
    background: var(--ak-card);
    position: relative;
    z-index: 2;
    /* 应用同样的裁剪 */
    clip-path: var(--ak-cut-path, none);
    /* 向内缩进：1px 的 margin 加上原本 ak-cut-path 撑出的空间，完美露出外层背景色 */
    margin: 1px; /* 边框厚度 */
    flex: 1;
}

/* ========================================================================= */

/* ==== 侧边终端导航 (深色特化) ==== */
.ak-layout { display: flex; align-items: flex-start; min-height: calc(100vh - 100px); padding-bottom: 50px; max-width: 1600px; margin: 0 auto;}
.ak-sidebar { width: 120px; flex-shrink: 0; position: sticky; top: 100px; height: calc(100vh - 120px); display: flex; padding: 60px 0; z-index: 50; border-right: 2px solid var(--ak-border); }
.ak-sidebar-inner { width: 100%; position: relative; }
.ak-nav-indicator { position: absolute; right: -2px; top: 0; width: 4px; height: 30px; background: var(--ak-red); transition: 0.3s; }
.ak-menu { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 40px; align-items: center; }
.ak-link { display: flex; flex-direction: column; align-items: center; text-decoration: none; writing-mode: vertical-rl; transform: rotate(180deg); color: var(--ak-muted); transition: 0.3s; }
.ak-text { font-size: 1.2em; font-weight: 700; letter-spacing: 5px; font-family: 'Oswald', sans-serif; }
.ak-link-deco { width: 10px; height: 2px; background: transparent; margin-top: 10px; transition: 0.3s; }
.ak-link:hover, .ak-link.active { color: var(--ak-text); }
.ak-link.active .ak-link-deco { background: var(--ak-red); width: 20px; }

/* ==== 核心夜间视界 ==== */
.ak-main { flex: 1; padding: 40px 5vw; animation: slideUp 0.8s var(--ak-ease) forwards; }
@keyframes slideUp { 0% { opacity: 0; transform: translateY(40px); } 100% { opacity: 1; transform: translateY(0); } }

/* ==== 战术台词面板 (深色 + 边框修复) ==== */
.ak-header { position: relative; margin-bottom: 60px; }
.ak-quote-container-wrap { position: absolute; right: 0; top: 0; box-shadow: 8px 8px 0 rgba(0,0,0,0.2); z-index: 10;}
/* 台词框裁剪路径：切除左上角 
   --ak-filter-fix 利用 filter 特性模拟被 clip-path 切掉的不规则黑色硬边框 
*/
.ak-quote-container-wrap .ak-border-fix-layer {
    --ak-cut-path: polygon(15px 0, 100% 0, 100% 100%, 0 100%, 0 15px);
    --ak-dark-border: var(--ak-text); /* 边框颜色 */
    --ak-filter-fix: 
        drop-shadow(-1px -1px 0 var(--ak-text))
        drop-shadow(1px 1px 0 var(--ak-text))
        drop-shadow(1px -1px 0 var(--ak-text))
        drop-shadow(-1px 1px 0 var(--ak-text));
}
.ak-quote-panel {
    padding: 15px 20px; 
    background: var(--ak-bg-terminal); 
    border-left: 4px solid var(--ak-red); /* 战术指示红线 */
}
.ak-quote-text-container { display: flex; flex-direction: row-reverse; gap: 20px; margin-bottom: 10px; }
.ak-quote-line { writing-mode: vertical-rl; text-orientation: upright; font-family: 'Noto Serif SC', serif; font-size: 1.4em; font-weight: 900; letter-spacing: 8px; color: var(--ak-text); }
.ak-quote-sys { font-family: 'Roboto Mono', monospace; font-size: 0.7em; color: var(--ak-muted); border-top: 1px solid var(--z-border); padding-top: 5px; text-align: right;}

/* 大标题 */
.ak-title-wrapper { position: relative; z-index: 2; margin-top: 40px;}
.ak-title-prefix { font-family: 'Oswald', sans-serif; font-size: 1.2em; font-weight: 700; color: var(--ak-red); margin-bottom: 5px; }
.ak-title { font-size: 4.5em; margin: 0; color: var(--ak-text) !important; letter-spacing: 5px; line-height: 1; }
.ak-blade-line { width: 120px; height: 8px; background: var(--ak-red); margin-top: 20px; clip-path: polygon(0 0, 100% 0, 95% 100%, 0 100%);}

/* ==== 深色搜索控制台 (边框修复) ==== */
.ak-toolbar { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 60px; flex-wrap: wrap; gap: 40px; border-bottom: 2px solid var(--ak-text); padding-bottom: 20px; }
.ak-search-form { flex: 1; max-width: 700px; z-index: 10;}
/* 裁剪路径：切除右下角 ( Arknights 标志性切角 ) */
.ak-search-form .ak-border-fix-layer {
    --ak-cut-path: polygon(0 0, 100% 0, 100% 0, calc(100% - 15px) 100%, 0 100%);
    --ak-dark-border: var(--ak-text); /* 黑色边框色 */
    --ak-filter-fix: drop-shadow(-1px -1px 0 var(--ak-text)) drop-shadow(1px 1px 0 var(--ak-text)) drop-shadow(1px -1px 0 var(--ak-text)) drop-shadow(-1px 1px 0 var(--ak-text));
}
.ak-search-slash {
    display: flex; align-items: center; 
    padding-right: 25px;
}
.ak-search-icon { padding: 15px 20px; background: var(--ak-text); color: var(--ak-bg); }
.ak-search-slash input { flex: 1; background: transparent; border: none; padding: 15px; color: var(--ak-text) !important; font-size: 1.1em; font-weight: 700; font-family: 'Roboto Mono', monospace; outline: none; }
.ak-search-slash input::placeholder { color: var(--ak-muted); font-weight: 300; }
.ak-select { background: transparent; color: var(--ak-text); border: none; border-left: 2px dashed var(--ak-border); padding: 0 15px; font-size: 1em; font-weight: 700; outline: none; cursor: pointer; font-family: 'Oswald', sans-serif; }
.ak-select option { background: var(--ak-bg-terminal); color: var(--ak-text); }
.ak-btn-icon-submit { background: var(--ak-red); color: #fff; border: none; padding: 10px 20px; font-weight: 700; font-family: 'Oswald', sans-serif; cursor: pointer; clip-path: polygon(10px 0, 100% 0, 100% 100%, 0 100%); transition: 0.3s; margin-left: 10px;}
.ak-btn-icon-submit:hover { background: var(--ak-muted); }

/* 动作区 */
.ak-actions { display: flex; align-items: center; gap: 30px; }
.ak-quote-small { margin: 0; font-family: 'Noto Serif SC', serif; font-size: 0.95em; font-weight: 700; color: var(--ak-text); display: flex; align-items: center; gap: 8px;}
.ak-red-dot { width: 8px; height: 8px; background: var(--ak-red); display: inline-block; }

/* 深色特化战术按钮 (同样修复边框) */
.ak-btn-primary { display: inline-flex; align-items: center; gap: 15px; height: 45px; padding: 0 25px; background: var(--ak-text); color: var(--ak-bg); border: 2px solid var(--ak-text); text-decoration: none; font-weight: 700; font-family: 'Oswald', sans-serif; letter-spacing: 1px; transition: 0.3s; clip-path: polygon(0 0, 100% 0, calc(100% - 10px) 100%, 0 100%); }
.ak-btn-primary:hover { background: var(--ak-red); border-color: var(--ak-red); color: #fff; transform: translateX(5px); }
.ak-btn-outline { display: inline-flex; align-items: center; gap: 15px; height: 45px; padding: 0 25px; background: var(--ak-bg); color: var(--ak-text); border: 2px solid var(--ak-text); text-decoration: none; font-weight: 700; font-family: 'Oswald', sans-serif; transition: 0.3s; clip-path: polygon(0 0, 100% 0, calc(100% - 10px) 100%, 0 100%);}
.ak-btn-outline:hover { background: var(--ak-red); border-color: var(--ak-red); color: #fff; }

/* ==== 倾斜机能风 Tabs (深色特化) ==== */
.tabbed-module { width: 100%; }
.ak-tab-controls { display: flex; gap: 10px; margin-bottom: 40px; border-bottom: 2px solid var(--ak-border); padding-bottom: 0; }
.ak-tab-btn { 
    background: var(--ak-card); border: 2px solid var(--ak-border); border-bottom: none;
    color: var(--ak-muted); cursor: pointer; transition: 0.3s; padding: 15px 30px; 
    transform: skew(-15deg); transform-origin: bottom; margin-bottom: -2px;
}
.ak-tab-inner { transform: skew(15deg); display: flex; flex-direction: column; align-items: flex-start; gap: 4px; }
.ak-tab-btn .tab-cn { font-family: 'Noto Serif SC', serif; font-size: 1.3em; font-weight: 900; line-height: 1; color: inherit; }
.ak-tab-btn .tab-es { font-family: 'Roboto Mono', monospace; font-size: 0.75em; font-weight: 700; line-height: 1; letter-spacing: 1px;}
.ak-tab-btn:hover { background: var(--ak-bg-terminal); }
.ak-tab-btn.active { background: var(--ak-red); color: #fff; border-color: var(--ak-red); }
.ak-tab-content { animation: akFade 0.4s var(--ak-ease) forwards; }
@keyframes akFade { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: translateX(0); } }

/* ================= 👑 明日方舟风卡片设计 (完美修复被切掉的边框) ================= */
/* 所有卡片裁剪路径：切除右下角 20px */
.ak-boss-card-wrap, .ak-challenger-card-wrap, .ak-result-card-wrap {
    text-decoration: none; display: flex;
    transition: all 0.3s var(--ak-ease); 
    box-shadow: var(--ak-shadow);
}
.ak-boss-card-wrap:hover, .ak-challenger-card-wrap:hover, .ak-result-card-wrap:hover {
    transform: translateY(-5px) scale(1.02); box-shadow: var(--ak-shadow-hover);
}

.ak-result-card-wrap .ak-border-fix-layer, .ak-boss-card-wrap .ak-border-fix-layer, .ak-challenger-card-wrap .ak-border-fix-layer {
    --ak-cut-path: polygon(0 0, 100% 0, 100% calc(100% - 20px), calc(100% - 20px) 100%, 0 100%);
    --ak-dark-border: var(--ak-border); /* 深色边框色 */
    --ak-filter-fix: drop-shadow(-1px -1px 0 var(--ak-border)) drop-shadow(1px 1px 0 var(--ak-border)) drop-shadow(1px -1px 0 var(--ak-border)) drop-shadow(-1px 1px 0 var(--ak-border));
}

.ak-boss-card *, .ak-challenger-card *, .ak-result-card * { position: relative; z-index: 2; transition: color 0.3s; color: var(--ak-text) !important;} 

/* 第一名主推宽卡片 (深色) */
.ak-throne-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; }
.ak-boss-card { grid-column: 1 / -1; padding: 40px; border-left: 6px solid var(--ak-red); }
.ak-boss-bg-text { position: absolute; right: -5%; bottom: -15%; font-family: 'Oswald', sans-serif; font-size: 14em; font-weight: 900; color: rgba(255,255,255,0.015); pointer-events: none; line-height: 0.8; z-index: 0; }
.ak-boss-stripes { position: absolute; top: 0; right: 0; width: 100px; height: 100%; background: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,0.01) 10px, rgba(255,255,255,0.01) 20px); z-index: 1;}
.boss-content { display: flex; align-items: center; gap: 40px; width: 100%; position: relative; z-index: 2; }
.boss-icon-wrap { background: var(--ak-red); padding: 30px; clip-path: polygon(0 0, 100% 0, calc(100% - 15px) 100%, 0 100%); }
.boss-icon { font-size: 3.5em; color: #fff; transition: 0.4s; }
.ak-boss-card:hover .boss-icon { transform: scale(1.1); }
.boss-info { flex: 1; }
.boss-tags { margin-bottom: 15px; display: flex; gap: 10px; }
.ak-tag-maestro { background: var(--ak-text); color: var(--ak-bg) !important; padding: 4px 10px; font-size: 0.75em; font-weight: 700; font-family: 'Roboto Mono', monospace; }
.boss-title { font-size: 2.2em; color: var(--ak-text) !important; margin: 10px 0 15px 0; font-family: 'Noto Serif SC', serif; font-weight: 900; }
.boss-meta { display: flex; gap: 30px; color: var(--ak-muted); font-size: 0.9em; font-weight: 700; font-family: 'Roboto Mono', monospace; }

/* 小档案卡 (深色) */
.ak-challenger-card { padding: 30px; flex-direction: column; }
.ak-rank-badge { position: absolute; top: 0; right: 0; background: var(--ak-text); color: var(--ak-bg) !important; padding: 5px 15px; font-family: 'Oswald', sans-serif; font-size: 1.5em; font-weight: 700; clip-path: polygon(0 0, 100% 0, 100% 100%, 15px 100%); }
.challenger-icon { font-size: 2.5em; color: var(--ak-text); margin-bottom: 20px; transition: 0.4s; }
.ak-challenger-card:hover .challenger-icon { color: var(--ak-red); }
.challenger-content { flex: 1; }
.challenger-title { font-size: 1.4em; color: var(--ak-text) !important; margin: 10px 0 20px 0; font-family: 'Noto Serif SC', serif; font-weight: 900;}
.challenger-author { color: var(--ak-muted); font-size: 0.8em; font-weight: 700; font-family: 'Roboto Mono', monospace; border-top: 2px dashed var(--ak-border); padding-top: 15px;}

/* 搜索网格 */
.ak-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 30px; }
.ak-result-card { padding: 30px; flex-direction: column; } 
.ak-rc-meta { display: flex; justify-content: space-between; margin-bottom: 20px; }
.ak-rc-title { font-size: 1.4em; margin: 0 0 25px 0; line-height: 1.4; font-family: 'Noto Serif SC', serif; font-weight: 900; color: var(--ak-text) !important;}
.ak-result-card:hover .ak-rc-title { color: var(--ak-red) !important; }
.ak-rc-footer { margin-top: auto; color: var(--ak-muted); font-family: 'Roboto Mono', monospace; font-size: 0.8em; font-weight: 700; border-top: 2px dashed var(--ak-border); padding-top: 15px;}

/* 通用难度色块组件 */
.ak-tag { padding: 4px 10px; font-size: 0.75em; letter-spacing: 1px; border: 1px solid var(--ak-border); font-weight: 700; color: var(--ak-muted) !important; font-family: 'Roboto Mono', monospace;}
.ak-tag-diff { padding: 4px 10px; font-size: 0.75em; font-weight: 700; color: #fff !important; font-family: 'Oswald', sans-serif; letter-spacing: 1px; clip-path: polygon(5px 0, 100% 0, 100% 100%, 0 100%); }
.diff-bg-beginner { background: #3d793f !important; } /* 暗绿 */
.diff-bg-intermediate { background: #ab661f !important; } /* 暗橙 */
.diff-bg-advanced { background: var(--ak-red) !important; } /* 红 */
.ak-result-card-wrap:hover .ak-result-card, .ak-challenger-card-wrap:hover .ak-challenger-card {
    border-color: var(--ak-text); /* 悬停时所有边框变亮 */
}
.ak-result-card-wrap.diff-hover-advanced .ak-result-card, .ak-boss-card-wrap:hover .ak-boss-card, .ak-challenger-card-wrap:hover .ak-challenger-card {
    border-left-color: var(--ak-red); /* 特殊悬停 */
}


/* ==== 最新阅览 (横向) (边框修复) ==== */
.ak-clean-list { display: flex; flex-direction: column; gap: 15px; }
.ak-list-item-wrap { text-decoration: none; display: flex; transition: 0.3s var(--ak-ease); }
/* 裁剪路径：切除右下角 (条目) */
.ak-list-item-wrap .ak-border-fix-layer {
    --ak-cut-path: polygon(0 0, 100% 0, 100% 0, calc(100% - 15px) 100%, 0 100%);
    --ak-dark-border: var(--ak-border);
    --ak-filter-fix: drop-shadow(-1px -1px 0 var(--ak-border)) drop-shadow(1px 1px 0 var(--ak-border)) drop-shadow(1px -1px 0 var(--ak-border)) drop-shadow(-1px 1px 0 var(--ak-border));
}
.ak-list-item-wrap:hover { transform: translateX(5px); }
.ak-list-item-wrap:hover .ak-border-fix-layer { --ak-dark-border: var(--ak-text); } /* 悬停亮边框 */

.ak-list-item { display: flex; align-items: center; gap: 20px; padding: 20px;}
.list-item-accent { position: absolute; left: 0; top: 0; bottom: 0; width: 4px; transition: 0.3s; }
.ak-list-item-wrap:hover .list-item-accent { width: 8px; }
.list-item-icon { font-size: 1.5em; color: var(--ak-text); width: 40px; text-align: center; }
.ak-list-item-wrap:hover .list-item-icon { color: var(--ak-red); }
.list-item-content { flex: 1; display: flex; flex-direction: column; gap: 6px; }
.list-item-title { margin: 0; font-size: 1.2em; color: var(--ak-text) !important; font-family: 'Noto Serif SC', serif; font-weight: 900; }
.list-item-meta { text-align: right; display: flex; flex-direction: column; gap: 5px; font-family: 'Roboto Mono', monospace; }
.list-author { font-size: 0.9em; font-weight: 700; color: var(--ak-text); }
.list-date { font-size: 0.8em; color: var(--ak-muted); }

/* ==== 通用组件 ==== */
.ak-section-header { margin-bottom: 20px; border-bottom: 2px dashed var(--ak-border); padding-bottom: 10px;}
.ak-section-header h2 { font-family: 'Oswald', sans-serif; font-size: 1.5em; color: var(--ak-muted); font-weight: 700; margin: 0; display: inline-block;}
.ak-clear-btn { color: var(--ak-red); text-decoration: none; font-size: 0.8em; font-family: 'Roboto Mono', monospace; margin-left: 20px; transition: 0.3s;}
.ak-clear-btn:hover { color: var(--ak-text); background: var(--ak-red); padding: 2px 5px; }
.ak-empty { font-size: 1.3em; color: var(--ak-muted); font-family: 'Noto Serif SC', serif; font-weight: 700; letter-spacing: 5px; padding: 80px 0; text-align: center; }

/* 手机端适配 */
@media (max-width: 1000px) {
    .ak-layout { flex-direction: column; }
    .ak-sidebar { width: 100%; height: auto; position: static; padding: 20px; border-right: none; border-bottom: 2px solid var(--ak-border); background: var(--ak-bg-terminal); }
    .ak-nav-indicator { width: 30px; height: 4px; bottom: 0; top: auto; right: auto; }
    .ak-menu { flex-direction: row; gap: 20px; flex-wrap: wrap; }
    .ak-link { writing-mode: horizontal-tb; transform: none; flex-direction: row; gap: 10px; }
    .ak-link-deco { display: none; }
    
    .ak-quote-container-wrap { display: none; }
    .ak-title { font-size: 3em; }
    .ak-toolbar { flex-direction: column; align-items: stretch; gap: 20px; }
    .ak-actions { flex-direction: column; align-items: stretch; }
    
    .ak-boss-bg-text { display: none; }
    
    /* 核心修复：移动端移除斜边模拟，回归工整，防止重叠和渲染车祸 */
    .ak-search-form .ak-border-fix-layer, .ak-list-item-wrap .ak-border-fix-layer, .ak-result-card-wrap .ak-border-fix-layer, .ak-boss-card-wrap .ak-border-fix-layer, .ak-challenger-card-wrap .ak-border-fix-layer {
        filter: none !important;
        clip-path: none !important;
        background: transparent !important;
    }
    .ak-boss-card, .ak-challenger-card, .ak-result-card, .ak-list-item, .ak-search-slash {
        margin: 0 !important;
        clip-path: none !important;
        border: 2px solid var(--ak-border) !important;
    }
    
    .ak-throne-grid { grid-template-columns: 1fr; }
    .ak-boss-card { flex-direction: column; align-items: flex-start; gap: 20px; }
    .ak-search-icon { display: none; }
    .ak-search-slash { flex-direction: column; padding-right: 0;}
    .ak-search-slash input, .ak-select { border-bottom: 1px solid var(--z-border) !important; width: 100%;}
    .ak-btn-icon-submit { margin: 10px auto; clip-path: none !important;}
}
</style>

<script>
function switchTab(tabId, btnElement) {
    document.querySelectorAll('.ak-tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.ak-tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tabId).style.display = 'block';
    btnElement.classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>