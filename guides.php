<?php
// guides.php - 100% 完整版 (极意·浅色水墨 / 右上角繁体印鉴版)
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
} catch (Exception $e) { $error = "天道生变: " . $e->getMessage(); }
?>
<?php include 'includes/header.php'; ?>

<div class="wd-global-bg">
    <div class="wd-ink-stain"></div>
    <div class="wd-watermark">無 拘</div>
</div>

<div class="wd-layout">
    
    <aside class="wd-sidebar">
        <ul class="wd-menu">
            <li>
                <a href="guides.php" class="wd-link <?php echo empty($filter_category) ? 'active' : ''; ?>">
                    <span class="wd-text">森罗万象</span>
                    <span class="wd-subtext">ALL</span>
                </a>
            </li>
            <?php foreach($categories as $cat): ?>
                <li>
                    <a href="guides.php?category=<?php echo urlencode($cat['category']); ?>" class="wd-link <?php echo $filter_category == $cat['category'] ? 'active' : ''; ?>">
                        <span class="wd-text"><?php echo htmlspecialchars(strtoupper($cat['category'])); ?></span>
                        <span class="wd-subtext">ARCHIVE</span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <main class="wd-main">
        
        <header class="wd-header">
            <div class="wd-hero-container">
                <div class="wd-quote-block">
                    <div class="wd-quote-line">我身无拘</div>
                    <div class="wd-quote-divider"></div>
                    <div class="wd-quote-line highlight">武道无穷</div>
                </div>
                <div class="wd-title-block">
                    <div class="wd-stamp">藏 经 阁</div>
                    <h1 class="wd-title">武 道 卷 宗</h1>
                    <p class="wd-subtitle">UNBOUND BODY. INFINITE PATH.</p>
                </div>
            </div>
        </header>

        <?php if (isset($error)): ?>
            <div class="wd-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="wd-toolbar">
            <form method="GET" action="guides.php" class="wd-search-form">
                <div class="wd-search-line">
                    <i class="fas fa-search wd-search-icon"></i>
                    <input type="text" name="search" placeholder="在无尽卷宗中寻道..." value="<?php echo htmlspecialchars($search); ?>">
                    <?php if(!empty($filter_category)): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($filter_category); ?>">
                    <?php endif; ?>
                    <select name="difficulty" class="wd-select">
                        <option value="">境界不限 (ALL)</option>
                        <option value="beginner" <?php echo $filter_difficulty == 'beginner' ? 'selected' : '';?>>初境 NOVICE</option>
                        <option value="intermediate" <?php echo $filter_difficulty == 'intermediate' ? 'selected' : ''; ?>>入微 EXPERT</option>
                        <option value="advanced" <?php echo $filter_difficulty == 'advanced' ? 'selected' : ''; ?>>化境 MASTER</option>
                    </select>
                    <button type="submit" class="wd-btn-icon">探 寻</button>
                </div>
            </form>
            
            <div class="wd-actions">
                <?php if (is_logged_in()): ?>
                    <a href="new-guide.php" class="wd-btn-primary"><span>落笔成剑</span> <i class="fas fa-feather-alt"></i></a>
                <?php else: ?>
                    <a href="login.php" class="wd-btn-outline"><span>推门入阁</span> <i class="fas fa-sign-in-alt"></i></a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($is_searching): ?>
            
            <section class="wd-section">
                <div class="wd-section-header">
                    <h2>寻道结果 <span style="font-size:0.5em; color:var(--w-muted); font-family:sans-serif;">// RESULTS</span></h2>
                    <a href="guides.php" class="wd-clear-btn">抹去痕迹 (CLEAR)</a>
                </div>
                
                <?php if(empty($search_results)): ?>
                    <div class="wd-empty">神识扫过，此处空无一物。</div>
                <?php else: ?>
                    <div class="wd-grid">
                        <?php foreach($search_results as $guide): ?>
                            <a href="article.php?id=<?php echo $guide['id']; ?>" class="wd-card">
                                <div class="wd-card-hover-line diff-bg-<?php echo $guide['difficulty']; ?>"></div>
                                <div class="wd-card-meta">
                                    <span class="wd-tag"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></span>
                                    <span class="wd-diff-dot diff-color-<?php echo $guide['difficulty']; ?>"></span>
                                </div>
                                <h3 class="wd-card-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                <div class="wd-card-footer">
                                    <span>执笔 // <?php echo htmlspecialchars(strtoupper($guide['username'])); ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        <?php else: ?>
            
            <section class="wd-section tabbed-module">
                
                <div class="wd-tab-controls">
                    <button class="wd-tab-btn active" onclick="switchTab('popular', this)">
                        <span class="tab-cn">武道极意</span>
                        <span class="tab-en">MASTERS</span>
                    </button>
                    <button class="wd-tab-btn" onclick="switchTab('recent', this)">
                        <span class="tab-cn">最新现世</span>
                        <span class="tab-en">NEWEST</span>
                    </button>
                </div>

                <div id="tab-popular" class="wd-tab-content active-tab">
                    <div class="wd-throne-grid">
                        <?php if(!empty($popular_guides)): ?>
                            <?php $rank = 1; foreach($popular_guides as $guide): ?>
                                
                                <?php if($rank == 1): ?>
                                <a href="article.php?id=<?php echo $guide['id']; ?>" class="wd-boss-card">
                                    <div class="wd-boss-bg-glow"></div>
                                    <div class="wd-rank-seal">壹</div>
                                    <div class="boss-content">
                                        <div class="boss-info">
                                            <div class="boss-tags">
                                                <span class="wd-tag"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></span>
                                                <span class="wd-tag-master">绝 顶 (MAESTRO)</span>
                                            </div>
                                            <h3 class="boss-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                            <div class="boss-meta">
                                                <span><i class="fas fa-eye"></i> 阅览 <?php echo $guide['views']; ?></span>
                                                <span>执笔 // <?php echo htmlspecialchars(strtoupper($guide['username'])); ?></span>
                                            </div>
                                        </div>
                                        <div class="boss-icon-wrap"><i class="fas <?php echo get_guide_icon($guide['category']); ?>"></i></div>
                                    </div>
                                </a>
                                
                                <?php else: ?>
                                <a href="article.php?id=<?php echo $guide['id']; ?>" class="wd-challenger-card">
                                    <div class="wd-card-hover-line diff-bg-<?php echo $guide['difficulty']; ?>"></div>
                                    <div class="challenger-rank"><?php echo $rank == 2 ? '貳' : '參'; ?></div>
                                    <div class="challenger-content">
                                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px;">
                                            <span class="wd-diff-dot diff-color-<?php echo $guide['difficulty']; ?>"></span>
                                            <span class="wd-tag" style="border:none; padding:0;"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></span>
                                        </div>
                                        <h3 class="challenger-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                    </div>
                                    <div class="challenger-author">执笔 // <?php echo htmlspecialchars(strtoupper($guide['username'])); ?></div>
                                </a>
                                <?php endif; ?>

                            <?php $rank++; endforeach; ?>
                        <?php else: ?>
                            <div class="wd-empty">武林沉寂，无人登顶。</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="tab-recent" class="wd-tab-content" style="display: none;">
                    <div class="wd-clean-list">
                        <?php if(!empty($recent_guides)): ?>
                            <?php foreach($recent_guides as $guide): ?>
                                <a href="article.php?id=<?php echo $guide['id']; ?>" class="wd-list-item">
                                    <div class="wd-list-hover-bg"></div>
                                    <div class="list-diff-mark diff-bg-<?php echo $guide['difficulty']; ?>"></div>
                                    <div class="list-icon"><i class="fas <?php echo get_guide_icon($guide['category']); ?>"></i></div>
                                    <div class="list-content">
                                        <h3 class="list-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                        <span class="wd-tag" style="border:none; padding:0;"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></span>
                                    </div>
                                    <div class="list-meta">
                                        <span class="list-author"><?php echo htmlspecialchars(strtoupper($guide['username'])); ?></span>
                                        <span class="list-date"><?php echo date('m-d Y', strtotime($guide['created_at'])); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="wd-empty">经阁封尘，暂无新卷。</div>
                        <?php endif; ?>
                    </div>
                </div>

            </section>

        <?php endif; ?>

    </main>
</div>

<style>
/* ================= 极意·浅色水墨 (White > Gray > Red) ================= */
@import url('https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;700;900&family=Cinzel:wght@400;700&family=Inter:wght@300;400;700&display=swap');

:root {
    --w-bg: #F7F7F7;           /* 宣纸白 */
    --w-surface: #FFFFFF;      /* 纯白 (卡片底色) */
    --w-text: #222222;         /* 浓墨黑 */
    --w-muted: #888888;        /* 淡墨灰 */
    --w-red: #9e1b1b;          /* 朱砂红 */
    --w-border: rgba(0, 0, 0, 0.08); /* 极弱的水墨边框 */
    --w-ease: cubic-bezier(0.25, 0.8, 0.25, 1);
}

body { background-color: var(--w-bg) !important; color: var(--w-text); font-family: 'Inter', sans-serif; overflow-x: hidden; }
h1, h2, h3 { font-family: 'Noto Serif SC', serif; font-weight: 700; margin: 0; color: var(--w-text) !important;}

/* ==== 宣纸背景与印章水印 ==== */
.wd-global-bg { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -10; pointer-events: none; background: #F7F7F7; }
.wd-ink-stain { position: absolute; top: -10%; right: -5%; width: 50vw; height: 50vw; background: radial-gradient(circle, rgba(158, 27, 27, 0.04) 0%, transparent 60%); filter: blur(60px); }
.wd-watermark { position: absolute; bottom: 5%; right: 5%; font-family: 'Noto Serif SC', serif; font-size: 25vw; font-weight: 900; color: rgba(0,0,0,0.02); line-height: 0.8; user-select: none; }

/* ==== 整体布局 ==== */
.wd-layout { display: flex; align-items: flex-start; max-width: 1500px; margin: 0 auto; min-height: calc(100vh - 80px); padding: 60px 2vw;}

/* 侧边留白导航 */
.wd-sidebar { width: 150px; flex-shrink: 0; position: sticky; top: 100px; padding: 40px 0; }
.wd-menu { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 35px; }
.wd-link { display: flex; flex-direction: column; text-decoration: none; color: var(--w-muted); transition: all 0.4s var(--w-ease); position: relative; padding-left: 15px; border-left: 1px solid transparent; }
.wd-text { font-family: 'Noto Serif SC', serif; font-size: 1.3em; font-weight: 700; letter-spacing: 2px; transition: 0.3s;}
.wd-subtext { font-family: 'Cinzel', serif; font-size: 0.7em; letter-spacing: 1px; margin-top: 4px; opacity: 0.6; }
.wd-link:hover, .wd-link.active { color: var(--w-text); border-left: 1px solid var(--w-red); }
.wd-link.active .wd-text { color: var(--w-red); }

/* 主视界 */
.wd-main { flex: 1; padding-left: 6vw; animation: fadeFloat 1s var(--w-ease) forwards; }
@keyframes fadeFloat { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

/* ==== 电影级哲理头部 ==== */
.wd-header { margin-bottom: 80px; position: relative; }
.wd-hero-container { display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 40px; }

/* 核心：竖排台词对联设计 */
.wd-quote-block { display: flex; gap: 15px; align-items: center; }
.wd-quote-line { writing-mode: vertical-rl; text-orientation: upright; font-family: 'Noto Serif SC', serif; font-size: 1.6em; font-weight: 400; letter-spacing: 10px; color: var(--w-muted); }
.wd-quote-line.highlight { color: var(--w-text); font-weight: 700; }
.wd-quote-divider { width: 1px; height: 120px; background: linear-gradient(to bottom, transparent, var(--w-red), transparent); }

.wd-title-block { text-align: right; }
.wd-stamp { display: inline-block; font-family: 'Noto Serif SC', serif; color: var(--w-red); border: 1px solid var(--w-red); padding: 4px 10px; font-size: 0.9em; font-weight: 700; letter-spacing: 3px; margin-bottom: 20px; border-radius: 2px;}
.wd-title { font-size: 5em; letter-spacing: 15px; margin-bottom: 10px; line-height: 1.1; text-shadow: 0 10px 30px rgba(0,0,0,0.05);}
.wd-subtitle { font-family: 'Cinzel', serif; font-size: 1.1em; color: var(--w-muted); letter-spacing: 5px; margin: 0; }

/* ==== 破空剑意搜索栏 ==== */
.wd-toolbar { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 70px; flex-wrap: wrap; gap: 40px; }
.wd-search-form { flex: 1; max-width: 800px; }
.wd-search-line { display: flex; align-items: center; border-bottom: 1px solid var(--w-border); padding-bottom: 15px; transition: 0.4s; position: relative; }
.wd-search-line::after { content: ''; position: absolute; bottom: -1px; left: 0; width: 0; height: 1px; background: var(--w-red); transition: width 0.6s var(--w-ease); }
.wd-search-line:focus-within::after { width: 100%; }
.wd-search-icon { color: var(--w-muted); font-size: 1.2em; margin-right: 15px; }
.wd-search-line input { flex: 1; background: transparent; border: none; color: var(--w-text) !important; font-size: 1.2em; font-family: 'Noto Serif SC', serif; outline: none; }
.wd-search-line input::placeholder { color: #aaa; }
.wd-select { background: transparent; color: var(--w-muted); border: none; border-left: 1px solid var(--w-border); padding: 0 20px; font-size: 1em; outline: none; cursor: pointer; font-family: 'Cinzel', serif; }
.wd-select option { background: var(--w-surface); color: var(--w-text); }
.wd-btn-icon { background: transparent; border: none; color: var(--w-text); font-size: 1.1em; font-weight: 700; font-family: 'Noto Serif SC', serif; cursor: pointer; margin-left: 20px; letter-spacing: 2px; transition: 0.3s;}
.wd-btn-icon:hover { color: var(--w-red); }

/* 动作按钮 */
.wd-actions { display: flex; gap: 20px; }
.wd-btn-primary, .wd-btn-outline { display: inline-flex; align-items: center; gap: 10px; height: 45px; padding: 0 25px; text-decoration: none; font-weight: 700; font-family: 'Noto Serif SC', serif; letter-spacing: 2px; transition: 0.4s; border-radius: 2px; font-size: 0.95em;}
.wd-btn-primary { background: var(--w-red); color: #fff; border: 1px solid var(--w-red); box-shadow: 0 4px 20px rgba(158, 27, 27, 0.2); }
.wd-btn-primary:hover { background: transparent; color: var(--w-red); box-shadow: none; }
.wd-btn-outline { background: transparent; color: var(--w-text); border: 1px solid var(--w-border); }
.wd-btn-outline:hover { border-color: var(--w-red); color: var(--w-red); background: rgba(158,27,27,0.03); }

/* ==== 流沙 Tabs 控制器 ==== */
.tabbed-module { width: 100%; }
.wd-tab-controls { display: flex; gap: 40px; margin-bottom: 50px; border-bottom: 1px solid var(--w-border); padding-bottom: 0; }
.wd-tab-btn { background: transparent; border: none; color: var(--w-muted); cursor: pointer; transition: 0.4s; padding: 10px 0 20px 0; display: flex; flex-direction: column; align-items: flex-start; gap: 5px; position: relative; }
.wd-tab-btn .tab-cn { font-family: 'Noto Serif SC', serif; font-size: 1.4em; font-weight: 700; letter-spacing: 2px; transition: 0.3s; }
.wd-tab-btn .tab-en { font-family: 'Cinzel', serif; font-size: 0.8em; letter-spacing: 2px; }
.wd-tab-btn:hover { color: var(--w-text); }
.wd-tab-btn.active { color: var(--w-text); }
.wd-tab-btn.active .tab-cn { color: var(--w-red); }
.wd-tab-btn::after { content: ''; position: absolute; bottom: -1px; left: 0; width: 0; height: 1px; background: var(--w-red); transition: width 0.5s var(--w-ease); }
.wd-tab-btn.active::after { width: 100%; }

.wd-tab-content { animation: fadeFloat 0.6s var(--w-ease) forwards; }

/* ================= 👑 无拘无束的浅色卡片 ================= */
.wd-card, .wd-boss-card, .wd-challenger-card, .wd-list-item {
    background: var(--w-surface); position: relative; overflow: hidden; text-decoration: none; 
    transition: all 0.5s var(--w-ease); display: flex; border: 1px solid var(--w-border); border-radius: 4px;
}
.wd-card:hover, .wd-boss-card:hover, .wd-challenger-card:hover {
    background: #FFFFFF; border-color: rgba(158, 27, 27, 0.2); transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.06);
}
.wd-card *, .wd-boss-card *, .wd-challenger-card *, .wd-list-item * { position: relative; z-index: 2; transition: color 0.3s; } 

/* 悬停时如剑光划过的锐利细线 */
.wd-card-hover-line { position: absolute; top: 0; left: 0; width: 0; height: 2px; transition: width 0.6s var(--w-ease); z-index: 3; }
.wd-card:hover .wd-card-hover-line, .wd-challenger-card:hover .wd-card-hover-line { width: 100%; }

/* ==== 古法印章标签与难度点 ==== */
.wd-tag { font-family: 'Inter', sans-serif; font-size: 0.75em; letter-spacing: 1px; color: var(--w-muted); border: 1px solid var(--w-border); padding: 3px 8px; border-radius: 2px; font-weight: 700; }
.wd-tag-master { font-family: 'Noto Serif SC', serif; font-size: 0.8em; font-weight: 700; color: #fff; background: var(--w-red); padding: 3px 10px; border-radius: 2px; letter-spacing: 1px;}
.wd-diff-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; box-shadow: 0 0 10px currentColor; }
.diff-color-beginner { color: #528c62; background: #528c62; } /* 幽绿 */
.diff-color-intermediate { color: #a37c4d; background: #a37c4d; } /* 枯黄 */
.diff-color-advanced { color: var(--w-red); background: var(--w-red); } /* 血红 */
.diff-bg-beginner { background: #528c62 !important; }
.diff-bg-intermediate { background: #a37c4d !important; }
.diff-bg-advanced { background: var(--w-red) !important; }

/* --- 绝顶：大画幅留白卡片 --- */
.wd-throne-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; }
.wd-boss-card { grid-column: 1 / -1; padding: 60px; min-height: 250px; }
.wd-boss-bg-glow { position: absolute; top: 0; right: 0; width: 50%; height: 100%; background: radial-gradient(circle at 80% 50%, rgba(158, 27, 27, 0.03) 0%, transparent 70%); z-index: 0; transition: 0.5s;}
.wd-boss-card:hover .wd-boss-bg-glow { background: radial-gradient(circle at 80% 50%, rgba(158, 27, 27, 0.08) 0%, transparent 70%); }

/* 修复：绝顶（壹）缩放并定位在右上角 */
.wd-rank-seal { 
    position: absolute; 
    right: 25px; 
    top: 15px; 
    font-family: 'Noto Serif SC', serif; 
    font-size: 4em; 
    font-weight: 900; 
    color: rgba(0,0,0,0.05); 
    line-height: 1; 
    pointer-events: none; 
    z-index: 1; 
    transition: 0.5s;
}
.wd-boss-card:hover .wd-rank-seal { color: rgba(158,27,27,0.1); transform: scale(1.05); }

.boss-content { display: flex; justify-content: space-between; align-items: center; width: 100%; height: 100%; }
.boss-info { flex: 1; max-width: 70%; }
.boss-tags { display: flex; gap: 15px; margin-bottom: 20px; align-items: center; }
.boss-title { font-size: 2.8em; margin: 0 0 25px 0; line-height: 1.3; letter-spacing: 2px; }
.wd-boss-card:hover .boss-title { color: var(--w-red) !important; }
.boss-meta { display: flex; gap: 30px; color: var(--w-muted); font-size: 0.9em; font-weight: 400; letter-spacing: 1px; }
.boss-icon-wrap { font-size: 6em; color: rgba(0,0,0,0.05); transition: 0.5s; }
.wd-boss-card:hover .boss-icon-wrap { color: var(--w-red); transform: rotate(-5deg) scale(1.1); filter: drop-shadow(0 0 20px rgba(158,27,27,0.15));}

/* --- 榜眼/探花：残影卡片 --- */
.wd-challenger-card { padding: 40px; flex-direction: column; justify-content: flex-end; }

/* 修复：其他排名（貳/參）缩放并定位在右上角 */
.challenger-rank { 
    position: absolute; 
    top: 15px; 
    right: 20px; 
    font-family: 'Noto Serif SC', serif; 
    font-size: 3em; 
    font-weight: 900; 
    color: rgba(0,0,0,0.05); 
    line-height: 1; 
    transition: 0.4s;
    pointer-events: none;
    z-index: 1;
}
.wd-challenger-card:hover .challenger-rank { color: rgba(158,27,27,0.1); }

.challenger-content { flex: 1; margin-bottom: 30px;}
.challenger-title { font-size: 1.6em; line-height: 1.4; letter-spacing: 1px; }
.wd-challenger-card:hover .challenger-title { color: var(--w-red) !important; }
.challenger-author { font-size: 0.85em; color: var(--w-muted); letter-spacing: 1px; border-top: 1px solid var(--w-border); padding-top: 15px; }

/* ==== 寻道网格 ==== */
.wd-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px; }
.wd-card { padding: 40px; flex-direction: column; }
.wd-card-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.wd-card-title { font-size: 1.5em; line-height: 1.5; margin-bottom: 30px; letter-spacing: 1px;}
.wd-card:hover .wd-card-title { color: var(--w-red) !important; }
.wd-card-footer { margin-top: auto; font-size: 0.85em; color: var(--w-muted); letter-spacing: 1px; border-top: 1px solid var(--w-border); padding-top: 15px; }

/* ==== 墨迹横向列表 ==== */
.wd-clean-list { display: flex; flex-direction: column; gap: 10px; }
.wd-list-item { align-items: center; padding: 25px 40px; background: transparent; border-bottom: 1px solid var(--w-border); border-radius: 0; border: none;}
.wd-list-hover-bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: #F9F9F9; transform: scaleY(0); transform-origin: center; transition: 0.4s var(--w-ease); z-index: 0;}
.wd-list-item:hover .wd-list-hover-bg { transform: scaleY(1); }
.list-diff-mark { position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 2px; height: 0; transition: 0.4s var(--w-ease); z-index: 2;}
.wd-list-item:hover .list-diff-mark { height: 60%; }
.list-icon { font-size: 1.5em; color: var(--w-muted); width: 60px; transition: 0.4s; z-index: 2;}
.wd-list-item:hover .list-icon { color: var(--w-red); }
.list-content { flex: 1; display: flex; flex-direction: column; gap: 8px; z-index: 2;}
.list-title { font-size: 1.3em; letter-spacing: 1px; transition: 0.3s; color: var(--w-text) !important;}
.wd-list-item:hover .list-title { color: var(--w-red) !important; }
.list-meta { text-align: right; display: flex; flex-direction: column; gap: 6px; z-index: 2;}
.list-author { font-size: 0.9em; font-weight: 500; color: var(--w-text); letter-spacing: 1px;}
.list-date { font-size: 0.8em; color: var(--w-muted); font-family: 'Cinzel', serif;}

/* ==== 通用组件 ==== */
.wd-section-header { display: flex; justify-content: space-between; align-items: baseline; border-bottom: 1px solid var(--w-border); padding-bottom: 15px; margin-bottom: 40px; }
.wd-section-header h2 { font-size: 1.8em; letter-spacing: 2px; }
.wd-clear-btn { color: var(--w-muted); text-decoration: none; font-size: 0.85em; letter-spacing: 1px; transition: 0.3s;}
.wd-clear-btn:hover { color: var(--w-red); }
.wd-empty { font-family: 'Noto Serif SC', serif; font-size: 1.2em; color: var(--w-muted); letter-spacing: 5px; padding: 100px 0; text-align: center; font-weight: 300;}
.wd-error { background: rgba(158, 27, 27, 0.05); border: 1px solid var(--w-red); color: var(--w-red); padding: 15px 20px; margin-bottom: 40px; border-radius: 2px; font-weight: 400; letter-spacing: 1px;}

/* 响应式降级 */
@media (max-width: 1100px) {
    .wd-layout { flex-direction: column; padding: 20px; }
    .wd-sidebar { width: 100%; position: static; padding: 20px 0; border-bottom: 1px solid var(--w-border); margin-bottom: 40px;}
    .wd-menu { flex-direction: row; gap: 20px; flex-wrap: wrap; }
    .wd-link { padding-left: 0; padding-bottom: 10px; border-left: none; border-bottom: 1px solid transparent; }
    .wd-link:hover, .wd-link.active { border-left: none; border-bottom: 1px solid var(--w-red); }
    
    .wd-main { padding-left: 0; }
    .wd-hero-container { flex-direction: column-reverse; align-items: flex-start; gap: 30px; }
    .wd-quote-line { writing-mode: horizontal-tb; text-orientation: mixed; }
    .wd-quote-divider { width: 100px; height: 1px; background: linear-gradient(to right, transparent, var(--w-red), transparent); }
    .wd-title-block { text-align: left; }
    .wd-title { font-size: 3.5em; }
    
    .wd-toolbar { flex-direction: column; align-items: stretch; gap: 30px; }
    .wd-search-line { flex-wrap: wrap; gap: 15px; }
    .wd-search-line input { min-width: 100%; margin-bottom: 10px;}
    .wd-select, .wd-btn-icon { margin-left: 0; padding-left: 0; border-left: none; }
    
    .wd-throne-grid { grid-template-columns: 1fr; }
    .wd-boss-card { padding: 40px; }
    .boss-icon-wrap { display: none; }
    
    .wd-list-item { padding: 20px; flex-wrap: wrap; }
    .list-meta { text-align: left; align-items: flex-start; width: 100%; border-top: 1px solid var(--w-border); padding-top: 10px; margin-top: 10px;}
}
</style>

<script>
function switchTab(tabId, btnElement) {
    document.querySelectorAll('.wd-tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.wd-tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tabId).style.display = 'block';
    btnElement.classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>