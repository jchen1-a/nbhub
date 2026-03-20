<?php
// guides.php - 100% 完整版 (剑宗·丹霞绝篇 - 纯净高雅无特效 & 完美修复排版 Bug)
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

<div class="crane-global-bg">
    <div class="crane-watermark">剑 宗</div>
</div>

<div class="crane-layout">
    
    <aside class="crane-sidebar">
        <div class="crane-sidebar-inner">
            <ul class="crane-menu">
                <li>
                    <a href="guides.php" class="crane-link <?php echo empty($filter_category) ? 'active' : ''; ?>">
                        <span class="crane-text">TODO</span>
                    </a>
                </li>
                <?php foreach($categories as $cat): ?>
                    <li>
                        <a href="guides.php?category=<?php echo urlencode($cat['category']); ?>" class="crane-link <?php echo $filter_category == $cat['category'] ? 'active' : ''; ?>">
                            <span class="crane-text"><?php echo htmlspecialchars(strtoupper($cat['category'])); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>

    <main class="crane-main">
        
        <header class="crane-header">
            <div class="crane-quote-vertical" style="right: 5%; top: 0;">「大道至简，大音希声」</div>
            <div class="crane-quote-vertical" style="right: 12%; top: 120px; color:var(--e-red); font-size:1.2em;">—— 藏经阁</div>

            <div class="crane-huge-outline">SABIDURÍA</div>
            <h1 class="crane-title">武 道 秘 籍</h1>
            <p class="crane-subtitle">// EL CONOCIMIENTO ES LA HOJA MÁS AFILADA.</p>
            <div class="crane-blade-line"></div>
        </header>

        <?php if (isset($error)): ?>
            <div class="crane-error">Aviso del sistema: <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="crane-toolbar">
            <form method="GET" action="guides.php" class="crane-search-form">
                <div class="crane-search-slash">
                    <input type="text" name="search" placeholder="Busca en los pergaminos..." value="<?php echo htmlspecialchars($search); ?>">
                    <?php if(!empty($filter_category)): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($filter_category); ?>">
                    <?php endif; ?>
                    <select name="difficulty" class="crane-select">
                        <option value="">NIVEL (TODO)</option>
                        <option value="beginner" <?php echo $filter_difficulty == 'beginner' ? 'selected' : ''; ?>>初境 (NOVATO)</option>
                        <option value="intermediate" <?php echo $filter_difficulty == 'intermediate' ? 'selected' : ''; ?>>入微 (EXPERTO)</option>
                        <option value="advanced" <?php echo $filter_difficulty == 'advanced' ? 'selected' : ''; ?>>化境 (MAESTRO)</option>
                    </select>
                    <button type="submit" class="crane-btn-icon"><i class="fas fa-search"></i> 寻道</button>
                </div>
            </form>
            
            <div class="crane-actions">
                <div class="crane-quote-small">「落子无悔，剑出无欺」</div>
                <?php if (is_logged_in()): ?>
                    <a href="new-guide.php" class="crane-btn-primary">撰写剑谱 <i class="fas fa-pen-fancy"></i></a>
                <?php else: ?>
                    <a href="login.php" class="crane-btn-outline">步入剑阁 <i class="fas fa-sign-in-alt"></i></a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($is_searching): ?>
            
            <section class="crane-section">
                <div class="crane-section-header">
                    <h2>R E S U L T A D O S</h2>
                    <a href="guides.php" class="crane-clear-btn">Limpiar Filtros <i class="fas fa-times"></i></a>
                </div>
                
                <?php if(empty($search_results)): ?>
                    <div class="crane-empty">风过无痕，未寻得卷宗。</div>
                <?php else: ?>
                    <div class="crane-grid">
                        <?php foreach($search_results as $guide): ?>
                            <a href="article.php?id=<?php echo $guide['id']; ?>" class="crane-result-card diff-border-<?php echo $guide['difficulty']; ?>">
                                <div class="crane-rc-meta">
                                    <span class="crane-tag"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></span>
                                    <span class="crane-tag diff-text-<?php echo $guide['difficulty']; ?>" style="border:none; padding:0;"><?php echo htmlspecialchars(strtoupper($guide['difficulty'])); ?></span>
                                </div>
                                <h3 class="crane-rc-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                <div class="crane-rc-footer">
                                    <span>POR <?php echo htmlspecialchars(strtoupper($guide['username'])); ?></span>
                                </div>
                                <div class="card-ink-bloom"></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        <?php else: ?>
            
            <section class="crane-section tabbed-module">
                
                <div class="crane-tab-controls">
                    <button class="crane-tab-btn active" onclick="switchTab('popular', this)">
                        <span class="tab-cn">剑宗绝顶</span><br>LÍDERES
                    </button>
                    <button class="crane-tab-btn" onclick="switchTab('recent', this)">
                        <span class="tab-cn">新入剑阁</span><br>RECIENTES
                    </button>
                </div>

                <div id="tab-popular" class="crane-tab-content active-tab">
                    <div class="crane-throne-grid">
                        <?php if(!empty($popular_guides)): ?>
                            <?php $rank = 1; foreach($popular_guides as $guide): ?>
                                
                                <?php if($rank == 1): ?>
                                <a href="article.php?id=<?php echo $guide['id']; ?>" class="crane-boss-card diff-border-<?php echo $guide['difficulty']; ?>">
                                    <div class="crane-rank-mark">01</div>
                                    <div class="boss-content">
                                        <i class="fas <?php echo get_guide_icon($guide['category']); ?> boss-icon"></i>
                                        <div class="boss-info">
                                            <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                                <span class="crane-tag diff-text-<?php echo $guide['difficulty']; ?>" style="border-color:var(--e-red); color:var(--e-red) !important;"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></span>
                                                <span class="maestro-badge"><i class="fas fa-crown"></i> EL MAESTRO</span>
                                            </div>
                                            <h3 class="boss-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                            <div class="boss-meta">
                                                <span><i class="fas fa-eye"></i> <?php echo $guide['views']; ?></span>
                                                <span><i class="fas fa-feather-alt"></i> <?php echo htmlspecialchars(strtoupper($guide['username'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-ink-bloom" style="background: radial-gradient(circle at bottom right, rgba(192,53,53,0.06), transparent 70%);"></div>
                                </a>
                                
                                <?php else: ?>
                                <a href="article.php?id=<?php echo $guide['id']; ?>" class="crane-challenger-card diff-border-<?php echo $guide['difficulty']; ?>">
                                    <div class="crane-rank-mark">0<?php echo $rank; ?></div>
                                    <div class="challenger-icon"><i class="fas <?php echo get_guide_icon($guide['category']); ?>"></i></div>
                                    <div class="challenger-content">
                                        <span class="crane-tag diff-text-<?php echo $guide['difficulty']; ?>" style="margin-bottom:15px; display:inline-block; border:none; padding:0;"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></span>
                                        <h3 class="challenger-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                    </div>
                                    <div class="challenger-author">POR <?php echo htmlspecialchars(strtoupper($guide['username'])); ?></div>
                                    <div class="card-ink-bloom"></div>
                                </a>
                                <?php endif; ?>

                            <?php $rank++; endforeach; ?>
                        <?php else: ?>
                            <div class="crane-empty">EL SALÓN ESTÁ EN SILENCIO.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="tab-recent" class="crane-tab-content" style="display: none;">
                    <div class="crane-clean-list">
                        <?php if(!empty($recent_guides)): ?>
                            <?php foreach($recent_guides as $guide): ?>
                                <a href="article.php?id=<?php echo $guide['id']; ?>" class="crane-list-item diff-hover-<?php echo $guide['difficulty']; ?>">
                                    <div class="list-item-accent diff-bg-<?php echo $guide['difficulty']; ?>"></div>
                                    <div class="list-item-icon">
                                        <i class="fas <?php echo get_guide_icon($guide['category']); ?>"></i>
                                    </div>
                                    <div class="list-item-content">
                                        <h3 class="list-item-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                        <span class="crane-tag diff-text-<?php echo $guide['difficulty']; ?>" style="border:none; padding:0; font-size: 0.8em;"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></span>
                                    </div>
                                    <div class="list-item-meta">
                                        <div class="list-author"><?php echo htmlspecialchars(strtoupper($guide['username'])); ?></div>
                                        <div class="list-date"><?php echo date('d / m / Y', strtotime($guide['created_at'])); ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="crane-empty">NO HAY NUEVOS ESCRITOS.</div>
                        <?php endif; ?>
                    </div>
                </div>

            </section>

        <?php endif; ?>

    </main>
</div>

<style>
/* ================= 剑宗·丹霞绝篇 (The Crimson Crane Style) ================= */
@import url('https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;700;900&family=Cinzel:wght@400;700&family=Oswald:wght@300;500;700&display=swap');

:root {
    --e-bg: #fafafa;          
    --e-card: #ffffff;        
    --e-text: #2c2c2c;        
    --e-muted: #888888;       
    --e-red: #c03535;         
    --e-red-light: #f9eaea;   
    --e-border: #eaeaea;      
    --e-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); 
    --e-shadow-hover: 0 15px 35px rgba(192, 53, 53, 0.08); 
    --e-ease: cubic-bezier(0.25, 0.8, 0.25, 1);
}

body { background-color: var(--e-bg) !important; color: var(--e-text); font-family: 'Oswald', sans-serif; overflow-x: hidden; }
h1, h2, h3 { font-family: 'Cinzel', 'Noto Serif SC', serif; font-weight: 700; color: var(--e-text) !important; }

/* 背景 */
.crane-global-bg {
    position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
    background: radial-gradient(circle at 70% 30%, rgba(255,255,255,1) 0%, rgba(250,250,250,1) 60%), var(--e-bg);
    z-index: -10; pointer-events: none; 
}
.crane-watermark {
    position: absolute; right: -2%; top: 10%;
    font-family: 'Noto Serif SC', serif; font-size: 40vw; font-weight: 900;
    color: rgba(0,0,0,0.015); line-height: 0.8; writing-mode: vertical-rl;
    user-select: none; white-space: nowrap; transform: rotate(-2deg);
}

/* 诗意台词 */
.crane-quote-vertical {
    position: absolute; writing-mode: vertical-rl; font-family: 'Noto Serif SC', serif;
    font-size: 1.6em; font-weight: 700; color: rgba(0,0,0,0.15); letter-spacing: 10px;
    z-index: 5; pointer-events: none;
}
.crane-quote-small {
    position: absolute; top: -30px; right: 0;
    font-family: 'Noto Serif SC', serif; font-size: 0.95em; font-weight: 700; 
    color: var(--e-muted); letter-spacing: 2px; white-space: nowrap;
}

/* 侧边栏 */
.crane-layout { display: flex; align-items: flex-start; min-height: calc(100vh - 100px); padding-bottom: 50px; }

.crane-sidebar {
    width: 100px; flex-shrink: 0; background: transparent; border-right: 1px solid var(--e-border);
    position: sticky; top: 100px; height: calc(100vh - 120px); 
    display: flex; flex-direction: column; justify-content: center; 
    padding: 60px 0; z-index: 50; 
}
.crane-sidebar-inner { display: flex; justify-content: center; align-items: center; width: 100%; }

.crane-menu { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 40px; align-items: center; }
.crane-link {
    display: flex; flex-direction: row; align-items: center; gap: 15px; text-decoration: none; 
    writing-mode: vertical-rl; transform: rotate(180deg);
    color: var(--e-muted); transition: color 0.3s var(--e-ease); position: relative;
}
.crane-text { font-size: 1.2em; font-weight: 700; letter-spacing: 5px; font-family: 'Oswald', sans-serif; }
.crane-link::before { content: ''; position: absolute; right: -15px; top: 0; width: 3px; height: 0; background: var(--e-red); transition: 0.4s var(--e-ease); }
.crane-link:hover, .crane-link.active { color: var(--e-text); }
.crane-link.active::before { height: 100%; box-shadow: 0 0 10px rgba(192,53,53,0.3); }

/* 主视界 */
.crane-main { flex: 1; padding: 40px 4vw 100px 4vw; max-width: 1600px; animation: kxFadeIn 1s var(--e-ease) forwards; position: relative;}
@keyframes kxFadeIn { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

.crane-header { position: relative; margin-bottom: 80px; display: flex; flex-direction: column; align-items: flex-start; }
/* SABIDURÍA 高级浅灰色 */
.crane-huge-outline { font-size: 6.5em; font-weight: 900; color: var(--e-muted); opacity: 0.15; letter-spacing: 8px; line-height: 0.8; margin-left: -5px; font-family: 'Cinzel', serif; }
.crane-title { font-size: 4em; margin: 0 0 10px 0; color: var(--e-text) !important; letter-spacing: 12px; z-index: 2; }
.crane-blade-line { width: 80px; height: 4px; background: var(--e-red); margin: 20px 0; }
.crane-subtitle { color: var(--e-muted); font-size: 1.1em; letter-spacing: 4px; margin: 0; font-weight: 500; }

/* 搜索区 */
.crane-toolbar { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 80px; flex-wrap: wrap; gap: 40px; position: relative; z-index: 10; }
.crane-search-form { flex: 1; max-width: 700px; }
.crane-search-slash { display: flex; align-items: center; border-bottom: 2px solid var(--e-border); padding-bottom: 10px; transition: 0.4s var(--e-ease); }
.crane-search-slash:focus-within { border-bottom-color: var(--e-red); transform: translateX(10px); }
.crane-search-slash input { flex: 1; background: transparent; border: none; color: var(--e-text) !important; font-size: 1.2em; font-weight: 500; font-family: 'Oswald', sans-serif; outline: none; }
.crane-search-slash input::placeholder { color: #aaa; font-weight: 300;}
.crane-select { background: transparent; color: var(--e-text); border: none; border-left: 2px solid var(--e-border); padding: 0 15px; font-size: 1em; font-weight: 500; outline: none; cursor: pointer; text-transform: uppercase; font-family: 'Oswald', sans-serif; }
.crane-select option { background: var(--e-card); color: var(--e-text); }
.crane-btn-icon { background: transparent; border: none; color: var(--e-text); font-size: 1.1em; font-weight: 700; cursor: pointer; transition: 0.3s; margin-left: 20px; font-family: 'Noto Serif SC', serif;}
.crane-btn-icon:hover { color: var(--e-red); letter-spacing: 3px; }

/* 按钮 */
.crane-actions { display: flex; gap: 20px; position: relative; padding-top: 10px; }
.crane-btn-primary { display: inline-flex; align-items: center; gap: 10px; height: 50px; padding: 0 30px; background: var(--e-red); color: #fff; text-decoration: none; font-weight: 700; letter-spacing: 2px; transition: 0.3s; border: none; font-size: 1em; border-radius: 2px; box-shadow: 0 4px 15px rgba(192,53,53,0.2); }
.crane-btn-primary:hover { background: #a62b2b; transform: translateY(-3px); box-shadow: 0 8px 25px rgba(192,53,53,0.3); }
.crane-btn-outline { display: inline-flex; align-items: center; gap: 10px; height: 50px; padding: 0 30px; background: var(--e-card); color: var(--e-text); border: 1px solid var(--e-border); text-decoration: none; font-weight: 700; letter-spacing: 2px; transition: 0.3s; border-radius: 2px; }
.crane-btn-outline:hover { border-color: var(--e-red); color: var(--e-red); background: var(--e-red-light); }

/* Tab 控制器 */
.tabbed-module { max-width: 100%; margin: 0; }
.crane-tab-controls { display: flex; justify-content: flex-start; gap: 50px; margin-bottom: 50px; border-bottom: 1px solid var(--e-border); padding-bottom: 10px; }
.crane-tab-btn { background: transparent; border: none; color: var(--e-muted); cursor: pointer; transition: 0.4s ease; text-align: left; padding: 10px 0; position: relative; }
.crane-tab-btn .tab-cn { font-family: 'Noto Serif SC', serif; font-size: 1.5em; font-weight: 900; letter-spacing: 3px; display: block; margin-bottom: 5px; transition: 0.3s; }
.crane-tab-btn:hover { color: var(--e-text); }
.crane-tab-btn.active { color: var(--e-text); }
.crane-tab-btn.active .tab-cn { color: var(--e-red); }
.crane-tab-btn::after { content: ''; position: absolute; bottom: -11px; left: 0; width: 0; height: 3px; background: var(--e-red); transition: width 0.4s ease; }
.crane-tab-btn.active::after { width: 100%; }

.crane-tab-content { animation: dinkFadeIn 0.5s ease; }
@keyframes dinkFadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }


/* ================= 👑 统一的卡片基础架构 (彻底消灭重叠 Bug) ================= */
/* 所有卡片共享同一套物理属性，赋予 Flex 结构与相对定位防溢出 */
.crane-boss-card, 
.crane-challenger-card, 
.crane-result-card {
    background: var(--e-card); 
    border: 1px solid var(--e-border); 
    position: relative; /* 核心：锁住内层的所有 absolute 元素（如 01 水印） */
    overflow: hidden; 
    text-decoration: none;
    transition: 0.4s var(--e-ease); 
    box-shadow: var(--e-shadow); 
    border-radius: 4px; 
    display: flex;
}

.crane-boss-card:hover, 
.crane-challenger-card:hover, 
.crane-result-card:hover {
    transform: translateY(-8px); 
    box-shadow: var(--e-shadow-hover); 
    border-color: rgba(192,53,53,0.2); 
}

/* 丹砂晕染特效 */
.card-ink-bloom {
    position: absolute; bottom: -50%; right: -50%; width: 100%; height: 100%;
    background: radial-gradient(circle at bottom right, rgba(192,53,53,0.04) 0%, transparent 60%);
    opacity: 0; transition: 0.6s var(--e-ease); z-index: 0; pointer-events: none;
}
.crane-boss-card:hover .card-ink-bloom, 
.crane-challenger-card:hover .card-ink-bloom, 
.crane-result-card:hover .card-ink-bloom { 
    bottom: -10%; right: -10%; opacity: 1; 
}
.crane-boss-card *, .crane-challenger-card *, .crane-result-card * { 
    position: relative; z-index: 2; transition: color 0.3s;
} 

/* 统一的高雅数字防伪水印（01, 02, 03） */
.crane-rank-mark { 
    position: absolute; right: 15px; top: -10px; font-family: 'Cinzel', serif; font-size: 6em; font-weight: 900; 
    color: rgba(0,0,0,0.03); pointer-events: none; transition: 0.3s; line-height: 1; z-index: 1;
}
.crane-boss-card:hover .crane-rank-mark, 
.crane-challenger-card:hover .crane-rank-mark { 
    color: rgba(192,53,53,0.06); 
}

/* --- 第一名：高雅横向巨无霸 --- */
.crane-throne-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; }
.crane-boss-card { grid-column: 1 / -1; padding: 50px; border-left: 6px solid var(--e-red); }
.boss-content { display: flex; align-items: center; gap: 40px; width: 100%; }
.boss-icon { font-size: 4em; color: var(--e-red); opacity: 0.8; transition: 0.4s; }
.crane-boss-card:hover .boss-icon { transform: scale(1.1); opacity: 1; }
.boss-info { flex: 1; }
.boss-title { font-size: 2.5em; color: var(--e-text) !important; margin: 10px 0 20px 0; font-family: 'Noto Serif SC', serif; line-height: 1.3; font-weight: 900;}
.crane-boss-card:hover .boss-title { color: var(--e-red) !important; }
.boss-meta { display: flex; gap: 30px; color: var(--e-muted); font-size: 1.1em; font-weight: 500; }

/* 王座专属勋章 (已不再重叠) */
.maestro-badge {
    background: var(--e-red) !important; color: white !important; border: none !important; 
    padding: 4px 12px !important; border-radius: 20px !important; font-size: 0.8em; font-weight: 700;
}

/* --- 第二/三名：纯白立轴 --- */
.crane-challenger-card { padding: 40px 30px; flex-direction: column; }
.challenger-icon { font-size: 2.5em; color: var(--e-muted); margin-bottom: 30px; transition: 0.4s; }
.crane-challenger-card:hover .challenger-icon { color: var(--e-red); }
.challenger-content { flex: 1; }
.challenger-title { font-size: 1.5em; color: var(--e-text) !important; margin: 0 0 20px 0; font-family: 'Noto Serif SC', serif; line-height: 1.4; font-weight: 700;}
.crane-challenger-card:hover .challenger-title { color: var(--e-red) !important; }
.challenger-author { color: var(--e-muted); font-size: 0.9em; font-weight: 500; letter-spacing: 2px; border-top: 1px solid var(--e-border); padding-top: 15px;}

/* --- 搜索与标签过滤卡片 (完美修复网格布局) --- */
.crane-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 30px; }
.crane-result-card { padding: 35px; flex-direction: column; } 
.crane-rc-meta { display: flex; justify-content: space-between; margin-bottom: 20px; position:relative; z-index:2;}
.crane-rc-title { font-size: 1.4em; margin: 0 0 25px 0; line-height: 1.4; font-family: 'Noto Serif SC', serif; font-weight: 700; color: var(--e-text) !important; position:relative; z-index:2; transition: 0.3s;}
.crane-result-card:hover .crane-rc-title { color: var(--e-red) !important; }
.crane-rc-footer { margin-top: auto; color: var(--e-muted); font-family: 'Oswald', sans-serif; font-weight: 500; font-size: 0.9em; position:relative; z-index:2; border-top: 1px solid var(--e-border); padding-top: 15px;}

/* 通用颜色组件 */
.diff-border-beginner { border-bottom: 3px solid #619b6e !important; }
.diff-border-intermediate { border-bottom: 3px solid #d49a5b !important; }
.diff-border-advanced { border-bottom: 3px solid var(--e-red) !important; }
.diff-text-beginner { color: #53855e !important; }
.diff-text-intermediate { color: #c28746 !important; }
.diff-text-advanced { color: var(--e-red) !important; }
.diff-bg-beginner { background: #619b6e !important; }
.diff-bg-intermediate { background: #d49a5b !important; }
.diff-bg-advanced { background: var(--e-red) !important; }

.crane-tag { padding: 3px 10px; font-size: 0.8em; letter-spacing: 1px; border: 1px solid var(--e-border); border-radius: 2px; font-weight: 700; color: var(--e-muted);}
.crane-boss-card:hover .crane-tag, .crane-challenger-card:hover .crane-tag, .crane-result-card:hover .crane-tag { border-color: rgba(192,53,53,0.2); color: var(--e-red); }


/* ================= 护眼极简横向列表 (最新攻略) ================= */
.crane-clean-list { display: flex; flex-direction: column; gap: 20px; }

.crane-list-item {
    display: flex; align-items: center; gap: 25px; background: var(--e-card);
    padding: 25px 35px; border: 1px solid var(--e-border); border-radius: 4px;
    text-decoration: none; position: relative; overflow: hidden;
    transition: 0.4s var(--e-ease); box-shadow: var(--e-shadow);
}
.list-item-accent { position: absolute; left: 0; top: 0; bottom: 0; width: 0; transition: 0.4s var(--e-ease); }
.crane-list-item:hover { transform: translateX(10px); box-shadow: var(--e-shadow-hover); border-color: rgba(192,53,53,0.2); }
.crane-list-item:hover .list-item-accent { width: 6px; }

.list-item-icon { font-size: 1.8em; color: var(--e-muted); transition: 0.3s; width: 40px; text-align: center; }
.crane-list-item:hover .list-item-icon { color: var(--e-red); transform: scale(1.1); }

.list-item-content { flex: 1; display: flex; flex-direction: column; gap: 8px; }
.list-item-title { margin: 0; font-size: 1.3em; color: var(--e-text) !important; font-family: 'Noto Serif SC', serif; font-weight: 700; transition: 0.3s; }
.crane-list-item:hover .list-item-title { color: var(--e-red) !important; }

.list-item-meta { text-align: right; display: flex; flex-direction: column; gap: 5px; }
.list-author { font-size: 1em; font-weight: 700; color: var(--e-text); font-family: 'Cinzel', serif;}
.list-date { font-size: 0.85em; color: var(--e-muted); letter-spacing: 1px; }

.crane-clear-btn { color: var(--e-muted); text-decoration: none; font-weight: 700; letter-spacing: 1px; font-size: 0.9em; transition: 0.3s; }
.crane-clear-btn:hover { color: var(--e-red); }
.crane-empty { font-size: 1.5em; color: var(--e-muted); font-family: 'Noto Serif SC', serif; font-weight: 700; letter-spacing: 5px; padding: 80px 0; text-align: center; }

/* 手机端防车祸适配 */
@media (max-width: 1000px) {
    .crane-layout { flex-direction: column; }
    .crane-sidebar { width: 100%; height: auto; position: static; flex-direction: row; justify-content: center; align-items: center; padding: 20px; border-right: none; border-bottom: 1px solid var(--e-border); }
    .crane-menu { flex-direction: row; gap: 20px; flex-wrap: wrap; }
    .crane-link { writing-mode: horizontal-tb; transform: none; }
    .crane-link::before { display: none; }
    .crane-link.active { border-bottom: 2px solid var(--e-red); }
    
    .crane-main { padding: 40px 20px; }
    .crane-quote-vertical { display: none; }
    .crane-huge-outline { font-size: 3em; }
    .crane-title { font-size: 2.5em; letter-spacing: 2px; }
    .crane-toolbar { flex-direction: column; gap: 20px; align-items: stretch; }
    .crane-search-slash { flex-direction: column; align-items: stretch; border: none; gap: 15px; }
    .crane-search-slash input, .crane-select { border-bottom: 1px solid var(--e-border); padding: 15px 0; border-left: none; }
    .crane-btn-icon { text-align: left; padding: 15px 0; margin: 0; }
    .crane-actions { width: 100%; flex-direction: column; }
    
    .crane-throne-grid { grid-template-columns: 1fr; }
    .crane-boss-card { flex-direction: column; padding: 30px; border-left: none; border-top: 6px solid var(--e-red); }
    .boss-icon { margin-bottom: 20px; }
    .boss-title { font-size: 1.8em; }
    
    .crane-list-item { flex-direction: column; align-items: flex-start; }
    .list-item-meta { text-align: left; }
}
</style>

<script>
// Tab 切换逻辑
function switchTab(tabId, btnElement) {
    document.querySelectorAll('.crane-tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.crane-tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tabId).style.display = 'block';
    btnElement.classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>