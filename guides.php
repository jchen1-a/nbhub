<?php
// guides.php - 100% 完整版 (修罗渊·狱血重置版 / The Abyssal Blood)
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
    return 'fa-skull-crossbones'; // 更加致命的骷髅
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
            WHERE a.is_published = 1 ORDER BY a.created_at DESC LIMIT 8
        ")->fetchAll();
    }
} catch (Exception $e) {
    $error = "Error al cargar las guías: " . $e->getMessage();
}
?>
<?php include 'includes/header.php'; ?>

<div class="abyss-global-bg">
    <div class="abyss-fog"></div>
    
    <div class="abyss-watermark" style="right: -2%; top: 5%;">狱 绝</div>
    <div class="abyss-watermark" style="left: -5%; bottom: 10%; font-size: 25vw; opacity: 0.03; transform: rotate(15deg);">无 回</div>

    <div class="blood-slash" style="top: 15%; left: 10%; transform: rotate(45deg) scale(1.5);"></div>
    <div class="blood-splat" style="top: 30%; right: 15%; transform: scale(2) rotate(-20deg);"></div>
    <div class="blood-splat" style="top: 70%; left: 20%; transform: scale(1.2) rotate(80deg); opacity:0.5;"></div>
    <div class="blood-slash" style="bottom: 10%; right: 10%; transform: rotate(-30deg) scale(2); opacity:0.7;"></div>

    <div class="ash-particles"></div>
</div>

<div class="abyss-layout">
    
    <aside class="abyss-sidebar">
        <div class="abyss-brand">血<br>祭</div>
        <div class="abyss-sidebar-inner">
            <ul class="abyss-menu">
                <li>
                    <a href="guides.php" class="abyss-link <?php echo empty($filter_category) ? 'active' : ''; ?>">
                        <span class="abyss-text">TODO</span>
                    </a>
                </li>
                <?php foreach($categories as $cat): ?>
                    <li>
                        <a href="guides.php?category=<?php echo urlencode($cat['category']); ?>" class="abyss-link <?php echo $filter_category == $cat['category'] ? 'active' : ''; ?>">
                            <span class="abyss-text"><?php echo htmlspecialchars(strtoupper($cat['category'])); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="abyss-sidebar-bot">死<br>局</div>
    </aside>

    <main class="abyss-main">
        
        <header class="abyss-header">
            <div class="abyss-quote-float" style="right: 5%; top: 20px;">「黄泉路冷，借你项上人头一用。」</div>
            <div class="abyss-quote-float" style="right: 10%; top: 120px; color:#550000; font-size:1.5em; filter:blur(1px);">—— 剑魔 · 嘉豪</div>

            <div class="abyss-huge-outline">CARNAGE</div>
            <h1 class="abyss-title">武 道 秘 籍</h1>
            <p class="abyss-subtitle">// EL CONOCIMIENTO ES UN ARMA. ÚSALA PARA MATAR.</p>
            <div class="abyss-blade-line"></div>
        </header>

        <?php if (isset($error)): ?>
            <div class="abyss-error">[SANGRE DERRAMADA]: <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="abyss-toolbar">
            <form method="GET" action="guides.php" class="abyss-search-form">
                <div class="abyss-search-box">
                    <input type="text" name="search" placeholder="HUELE EL MIEDO DE TUS ENEMIGOS..." value="<?php echo htmlspecialchars($search); ?>">
                    <?php if(!empty($filter_category)): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($filter_category); ?>">
                    <?php endif; ?>
                    <select name="difficulty" class="abyss-select">
                        <option value="">RANGO (TODO)</option>
                        <option value="beginner" <?php echo $filter_difficulty == 'beginner' ? 'selected' : ''; ?>>蝼蚁 (FÁCIL)</option>
                        <option value="intermediate" <?php echo $filter_difficulty == 'intermediate' ? 'selected' : ''; ?>>刺客 (MEDIO)</option>
                        <option value="advanced" <?php echo $filter_difficulty == 'advanced' ? 'selected' : ''; ?>>死神 (DIFÍCIL)</option>
                    </select>
                    <button type="submit" class="abyss-btn-icon">
                        <span class="blood-burst"></span>
                        <i class="fas fa-skull"></i>
                    </button>
                </div>
            </form>
            
            <div class="abyss-actions">
                <div class="abyss-quote-small" style="position:absolute; top:-35px; right:10px;">「弱者，连直视我的资格都没有。」</div>
                
                <?php if (is_logged_in()): ?>
                    <a href="new-guide.php" class="abyss-btn-blood">
                        <span class="blood-burst"></span>
                        <span style="position:relative; z-index:2;">祭出杀招 <i class="fas fa-burn"></i></span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="abyss-btn-ghost">
                        <span class="blood-burst"></span>
                        <span style="position:relative; z-index:2;">步入深渊 <i class="fas fa-door-open"></i></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($is_searching): ?>
            
            <section class="abyss-section">
                <div class="abyss-section-header">
                    <h2>R E S U L T A D O S</h2>
                    <a href="guides.php" class="abyss-clear-btn">BORRAR RASTROS <i class="fas fa-tint-slash"></i></a>
                </div>
                
                <?php if(empty($search_results)): ?>
                    <div class="abyss-empty">SÓLO QUEDAN CENIZAS.</div>
                <?php else: ?>
                    <div class="abyss-grid">
                        <?php foreach($search_results as $guide): ?>
                            <a href="article.php?id=<?php echo $guide['id']; ?>" class="abyss-result-card diff-border-<?php echo $guide['difficulty']; ?>">
                                <div class="card-blood-overlay"></div> <div class="abyss-rc-meta">
                                    <span class="abyss-tag"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></span>
                                    <span class="abyss-tag diff-text-<?php echo $guide['difficulty']; ?>"><?php echo htmlspecialchars(strtoupper($guide['difficulty'])); ?></span>
                                </div>
                                <h3 class="abyss-rc-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                <div class="abyss-rc-footer">
                                    <span>POR <?php echo htmlspecialchars(strtoupper($guide['username'])); ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        <?php else: ?>
            
            <section class="abyss-section">
                <div class="abyss-section-header">
                    <h2>深 渊 霸 主 // <span style="color:var(--a-red);">LÍDERES</span></h2>
                    <div class="abyss-quote-horizontal" style="margin-left:30px;">「尸山血海，铸我王座」</div>
                </div>

                <div class="abyss-throne-grid">
                    <?php if(!empty($popular_guides)): ?>
                        <?php $rank = 1; foreach($popular_guides as $guide): ?>
                            
                            <?php if($rank == 1): ?>
                            <a href="article.php?id=<?php echo $guide['id']; ?>" class="abyss-boss-card diff-border-<?php echo $guide['difficulty']; ?>">
                                <div class="card-blood-overlay" style="opacity: 0.2;"></div> <div class="boss-rank-mark">01<br><span style="font-size:0.25em; color:var(--a-red); letter-spacing:15px;">EL DIOS</span></div>
                                <div class="boss-content">
                                    <i class="fas <?php echo get_guide_icon($guide['category']); ?> boss-icon"></i>
                                    <div class="boss-info">
                                        <span class="abyss-tag diff-text-<?php echo $guide['difficulty']; ?>" style="border-color:var(--a-red);"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></span>
                                        <h3 class="boss-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                        <div class="boss-meta">
                                            <span><i class="fas fa-eye"></i> <?php echo $guide['views']; ?></span>
                                            <span><i class="fas fa-skull"></i> <?php echo htmlspecialchars(strtoupper($guide['username'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            
                            <?php else: ?>
                            <a href="article.php?id=<?php echo $guide['id']; ?>" class="abyss-challenger-card diff-border-<?php echo $guide['difficulty']; ?>">
                                <div class="card-blood-overlay"></div>
                                <div class="challenger-rank">0<?php echo $rank; ?></div>
                                <div class="challenger-icon"><i class="fas <?php echo get_guide_icon($guide['category']); ?>"></i></div>
                                <div class="challenger-content">
                                    <span class="abyss-tag diff-text-<?php echo $guide['difficulty']; ?>" style="margin-bottom:15px; display:inline-block;"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></span>
                                    <h3 class="challenger-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                </div>
                                <div class="challenger-author">POR <?php echo htmlspecialchars(strtoupper($guide['username'])); ?></div>
                            </a>
                            <?php endif; ?>

                        <?php $rank++; endforeach; ?>
                    <?php else: ?>
                        <div class="abyss-empty">EL TRONO ESPERA SANGRE.</div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="abyss-section" style="margin-top: 180px; position:relative;">
                
                <div class="abyss-quote-vertical" style="left: -60px; top: 100px; font-size:3em; color:rgba(255,255,255,0.02); z-index:-1;">「一息若存，杀戮不止」</div>

                <div class="abyss-section-header" style="justify-content: center; text-align: center; border:none; flex-direction:column;">
                    <h2 style="letter-spacing: 15px;">剑 痕 未 冷</h2>
                    <p style="color:var(--a-red); font-family:monospace; margin:15px 0 0 0; font-size:1.2em;">NUEVAS VÍCTIMAS REGISTRADAS</p>
                </div>

                <div class="abyss-timeline">
                    <div class="timeline-center-blade"></div>
                    
                    <?php if(!empty($recent_guides)): ?>
                        <?php $i = 0; foreach($recent_guides as $guide): ?>
                            
                            <a href="article.php?id=<?php echo $guide['id']; ?>" class="abyss-timeline-item <?php echo $i % 2 == 0 ? 'left-strike' : 'right-strike'; ?>">
                                <div class="timeline-spark diff-bg-<?php echo $guide['difficulty']; ?>"></div>
                                <div class="timeline-content">
                                    <div class="card-blood-overlay"></div> <div class="tl-meta">
                                        <span class="tl-cat diff-text-<?php echo $guide['difficulty']; ?>"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></span>
                                        <span class="tl-date"><?php echo date('d.m.y', strtotime($guide['created_at'])); ?></span>
                                    </div>
                                    <h3 class="tl-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                    <div class="tl-author">POR <?php echo htmlspecialchars(strtoupper($guide['username'])); ?></div>
                                </div>
                            </a>
                        <?php $i++; endforeach; ?>
                    <?php else: ?>
                        <div class="abyss-empty">NADA NUEVO.</div>
                    <?php endif; ?>
                </div>
            </section>

        <?php endif; ?>

    </main>
</div>

<style>
/* ================= 嘉豪专属：修罗渊·狱血重置版 (The Abyssal Blood Style) ================= */
@import url('https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@700;900&family=Cinzel:wght@700;900&family=Oswald:wght@500;700&display=swap');

:root {
    --a-black: #000000;
    --a-dark: #050303;      /* 极深的暗红黑底 */
    --a-bone: #e5e0d8;      /* 骨白色文字 */
    --a-red: #8a0303;       /* 浓稠干涸的血红 */
    --a-bright-red: #d10000;/* 新鲜刺眼的血红 */
    --a-muted: #555555;
    --a-ease: cubic-bezier(0.16, 1, 0.3, 1);
}

body { background-color: var(--a-black) !important; color: var(--a-bone); font-family: 'Oswald', sans-serif; overflow-x: hidden; }
h1, h2, h3 { font-family: 'Cinzel', 'Noto Serif SC', serif; font-weight: 900; color: var(--a-bone) !important; }

/* 1. 终极神秘背景：深渊冥雾 + 灰烬 */
.abyss-global-bg {
    position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
    background: var(--a-black); z-index: -10; pointer-events: none; overflow: hidden;
}
.abyss-fog {
    position: absolute; width: 200%; height: 200%; top: -50%; left: -50%;
    background: radial-gradient(circle at 50% 50%, rgba(138,3,3,0.08) 0%, transparent 60%);
    animation: fogDrift 20s infinite alternate ease-in-out;
}
@keyframes fogDrift { 0% { transform: scale(1) translate(0, 0); opacity: 0.5; } 100% { transform: scale(1.2) translate(5%, 5%); opacity: 1; } }

/* 飘落的灰烬 */
.ash-particles {
    position: absolute; top:0; left:0; width:100%; height:100%;
    background-image: 
        radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px),
        radial-gradient(circle, rgba(138,3,3,0.2) 2px, transparent 2px);
    background-size: 150px 150px, 250px 250px;
    background-position: 0 0, 50px 50px;
    animation: ashFall 30s linear infinite;
}
@keyframes ashFall { 0% { background-position: 0 0, 50px 50px; } 100% { background-position: 150px 300px, 300px 500px; } }

.abyss-watermark {
    position: absolute; font-family: 'Noto Serif SC', serif; font-size: 30vw; font-weight: 900;
    color: rgba(255,255,255,0.01); line-height: 0.8; writing-mode: vertical-rl; user-select: none; white-space: nowrap;
}

/* ================= 2. 纯CSS 暴戾血迹 (Blood Splatters) ================= */
.blood-slash {
    position: absolute; width: 300px; height: 8px; background: var(--a-red);
    border-radius: 50%; filter: blur(2px) drop-shadow(0 0 10px rgba(209,0,0,0.5));
    clip-path: polygon(0 50%, 20% 0, 100% 40%, 80% 100%);
}
.blood-splat {
    position: absolute; width: 100px; height: 100px;
    background: var(--a-red);
    /* 极致不规则喷溅形状 */
    clip-path: polygon(44% 4%, 60% 17%, 95% 10%, 75% 36%, 98% 63%, 67% 65%, 72% 98%, 47% 76%, 17% 93%, 29% 62%, 2% 47%, 29% 36%, 14% 11%, 38% 22%);
    filter: blur(1px) drop-shadow(0 0 15px rgba(209,0,0,0.4));
}

/* ================= 3. 按键炸血特效 (Blood Burst Hover) ================= */
.blood-burst {
    position: absolute; top: 50%; left: 50%; width: 10px; height: 10px;
    background: var(--a-bright-red);
    transform: translate(-50%, -50%) scale(0) rotate(0deg); opacity: 0;
    border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%; /* 不规则血浆 */
    transition: transform 0.4s var(--a-ease), opacity 0.3s; z-index: 0;
    pointer-events: none;
}
/* 卡片悬停内部渗血 */
.card-blood-overlay {
    position: absolute; bottom: 0; right: 0; width: 100%; height: 100%;
    background: radial-gradient(circle at bottom right, rgba(138,3,3,0.6) 0%, transparent 60%);
    opacity: 0; transition: 0.5s var(--a-ease); z-index: 1; pointer-events: none;
}

/* ================= 装逼台词排版 ================= */
.abyss-quote-float {
    position: absolute; writing-mode: vertical-rl; font-family: 'Noto Serif SC', serif;
    font-size: 2em; font-weight: 900; color: rgba(255,255,255,0.03); letter-spacing: 15px;
    z-index: 5; pointer-events: none; text-shadow: 0 0 10px rgba(0,0,0,0.8);
}
.abyss-quote-vertical {
    position: absolute; writing-mode: vertical-rl; font-family: 'Noto Serif SC', serif;
    font-weight: 900;
}
.abyss-quote-horizontal {
    font-family: 'Noto Serif SC', serif; font-size: 1.3em; font-weight: 900; 
    color: var(--a-red); letter-spacing: 5px; font-style: italic; text-shadow: 0 0 10px rgba(138,3,3,0.5);
}
.abyss-quote-small {
    font-family: 'Noto Serif SC', serif; font-size: 0.95em; font-weight: 900; 
    color: var(--a-muted); letter-spacing: 2px;
}

/* ================= 极黑侧边栏 ================= */
.abyss-layout { display: flex; align-items: flex-start; min-height: calc(100vh - 100px); padding-bottom: 50px;}

.abyss-sidebar {
    width: 80px; flex-shrink: 0; background: #020202; border-right: 1px solid #111;
    position: sticky; top: 100px; height: calc(100vh - 120px); 
    display: flex; flex-direction: column; justify-content: space-between; align-items:center;
    padding: 40px 0; z-index: 50; box-shadow: 5px 0 20px rgba(0,0,0,0.9);
}
.abyss-sidebar-inner { flex: 1; display: flex; justify-content: center; align-items: center; }

.abyss-brand, .abyss-sidebar-bot { font-family: 'Noto Serif SC', serif; font-size: 1.5em; font-weight: 900; color: var(--a-red); text-align: center; line-height: 1.5; text-shadow: 0 0 15px rgba(138,3,3,0.8); }
.abyss-sidebar-bot { color: #222; text-shadow: none; }

.abyss-menu { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 40px; align-items: center; }
.abyss-link {
    display: flex; flex-direction: row; align-items: center; gap: 15px; text-decoration: none; 
    writing-mode: vertical-rl; transform: rotate(180deg);
    color: var(--a-muted); transition: color 0.3s var(--a-ease); position: relative;
}
.abyss-text { font-size: 1.2em; font-weight: 900; letter-spacing: 5px; font-family: 'Oswald', sans-serif; }

.abyss-link::before { content: ''; position: absolute; right: -25px; top: 0; width: 2px; height: 0; background: var(--a-red); transition: 0.4s var(--a-ease); }
.abyss-link:hover, .abyss-link.active { color: var(--a-bone); }
.abyss-link.active::before { height: 100%; box-shadow: 0 0 15px var(--a-red); }

/* ================= 狂气主视界 ================= */
.abyss-main { flex: 1; padding: 40px 5vw 100px 5vw; max-width: 1600px; animation: kxFadeIn 1.2s var(--a-ease) forwards; position: relative;}
@keyframes kxFadeIn { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

/* 标题区 */
.abyss-header { position: relative; margin-bottom: 80px; display: flex; flex-direction: column; align-items: flex-start; }
.abyss-huge-outline { 
    font-size: 8em; font-weight: 900; color: transparent; -webkit-text-stroke: 1px rgba(255,255,255,0.03); 
    letter-spacing: 10px; line-height: 0.8; margin-left: -10px; font-family: 'Cinzel', serif;
}
.abyss-title { font-size: 5em; margin: 0 0 10px 0; color: var(--a-bone) !important; letter-spacing: 15px; z-index: 2; text-shadow: 0 5px 20px rgba(0,0,0,0.9); }
.abyss-blade-line { width: 150px; height: 3px; background: var(--a-red); margin: 20px 0; box-shadow: 0 0 15px var(--a-red); }
.abyss-subtitle { color: var(--a-muted); font-size: 1.1em; letter-spacing: 5px; margin: 0; font-weight: 900; font-family: monospace;}

/* 搜索区 */
.abyss-toolbar { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 80px; flex-wrap: wrap; gap: 40px; position: relative; z-index: 10; }
.abyss-search-form { flex: 1; max-width: 800px; }
.abyss-search-box {
    display: flex; align-items: center; border-bottom: 2px solid #222; padding-bottom: 15px; transition: 0.4s var(--a-ease); background: rgba(0,0,0,0.5); padding-left: 20px;
}
.abyss-search-box:focus-within { border-bottom-color: var(--a-red); transform: translateX(10px); background: #000;}
.abyss-search-box input { flex: 1; background: transparent; border: none; color: var(--a-bone) !important; font-size: 1.4em; font-weight: 900; font-family: 'Oswald', sans-serif; outline: none; }
.abyss-search-box input::placeholder { color: #444; }
.abyss-select { background: transparent; color: var(--a-muted); border: none; border-left: 2px solid #222; padding: 0 20px; font-size: 1em; font-weight: 900; outline: none; cursor: pointer; text-transform: uppercase; font-family: 'Oswald', sans-serif; }
.abyss-select option { background: var(--a-black); color: var(--a-bone); }

/* 炸血按钮 */
.abyss-btn-icon, .abyss-btn-blood, .abyss-btn-ghost {
    position: relative; overflow: hidden; /* 必须加 overflow:hidden 限制血液喷发 */
}
.abyss-btn-icon { background: transparent; border: none; color: var(--a-muted); font-size: 1.5em; font-weight: 900; cursor: pointer; transition: 0.3s; margin-left: 20px; padding: 10px 20px;}
.abyss-btn-icon:hover { color: #fff; }
.abyss-btn-icon:hover .blood-burst { transform: translate(-50%, -50%) scale(15) rotate(45deg); opacity: 1; }

.abyss-actions { display: flex; gap: 20px; position: relative; }
.abyss-btn-blood {
    display: inline-flex; align-items: center; gap: 15px; height: 55px; padding: 0 35px;
    background: #0a0a0a; color: var(--a-red); text-decoration: none; font-weight: 900; letter-spacing: 3px;
    transition: 0.3s; border: 1px solid var(--a-red); font-size: 1.1em;
}
.abyss-btn-blood:hover { color: #fff; border-color: var(--a-bright-red); box-shadow: 0 0 30px rgba(209,0,0,0.5); }
.abyss-btn-blood:hover .blood-burst { transform: translate(-50%, -50%) scale(30) rotate(-30deg); opacity: 1; }

.abyss-btn-ghost {
    display: inline-flex; align-items: center; gap: 15px; height: 55px; padding: 0 35px;
    background: transparent; color: var(--a-muted); border: 2px solid #333; text-decoration: none; font-weight: 900; letter-spacing: 3px; transition: 0.3s;
}
.abyss-btn-ghost:hover { color: #fff; border-color: #555; }
.abyss-btn-ghost:hover .blood-burst { background: #222; transform: translate(-50%, -50%) scale(30) rotate(90deg); opacity: 1; }


/* ================= 👑 修罗王座 ================= */
.abyss-section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; border-bottom: 1px solid #111; padding-bottom: 10px;}
.abyss-section-header h2 { font-size: 2em; letter-spacing: 8px; margin: 0; color: #aaa !important; }

.abyss-throne-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; }

/* 卡片基类 */
.abyss-card-base {
    background: rgba(10,10,10,0.8); border: 1px solid #1a1a1a; position: relative; overflow: hidden; text-decoration: none;
    transition: 0.5s var(--a-ease); backdrop-filter: blur(5px);
}
.abyss-card-base:hover { transform: translateY(-10px); border-color: var(--a-red); box-shadow: 0 20px 50px rgba(0,0,0,0.9), 0 0 30px rgba(138,3,3,0.2); }
.abyss-card-base:hover .card-blood-overlay { opacity: 1; }
.abyss-card-base * { position: relative; z-index: 2; } 

/* 第一名：血色巨无霸 */
.abyss-boss-card { grid-column: 1 / -1; padding: 60px; border-left: 6px solid var(--a-red); @extend .abyss-card-base; }
.boss-rank-mark { position: absolute; right: 40px; top: 20px; font-family: 'Cinzel', serif; font-size: 12em; font-weight: 900; color: rgba(255,255,255,0.02); line-height: 0.8; text-align: right; }
.boss-content { display: flex; align-items: center; gap: 50px; width: 100%; }
.boss-icon { font-size: 6em; color: #111; transition: 0.4s; text-shadow: 0 0 20px rgba(0,0,0,0.8); }
.abyss-boss-card:hover .boss-icon { color: var(--a-red); transform: scale(1.1) rotate(10deg); text-shadow: 0 0 30px rgba(209,0,0,0.5); }
.boss-info { flex: 1; }
.boss-title { font-size: 3em; color: var(--a-bone) !important; margin: 15px 0 25px 0; font-family: 'Noto Serif SC', serif; line-height: 1.2; transition: 0.3s; font-weight: 900;}
.abyss-boss-card:hover .boss-title { color: #fff !important; text-shadow: 0 0 15px rgba(255,255,255,0.3); }
.boss-meta { display: flex; gap: 40px; color: var(--a-muted); font-size: 1.2em; font-weight: 900; letter-spacing:2px; }

/* 第二/三名：暗影残碑 */
.abyss-challenger-card { padding: 40px 30px; display: flex; flex-direction: column; @extend .abyss-card-base;}
.challenger-rank { position: absolute; right: 10px; top: -10px; font-family: 'Cinzel', serif; font-size: 8em; font-weight: 900; color: rgba(255,255,255,0.01); transition: 0.4s; pointer-events: none; }
.abyss-challenger-card:hover .challenger-rank { color: rgba(138,3,3,0.05); }
.challenger-icon { font-size: 4em; color: #111; margin-bottom: 30px; transition: 0.4s; }
.abyss-challenger-card:hover .challenger-icon { color: var(--a-bone); }
.challenger-content { flex: 1; }
.challenger-title { font-size: 1.8em; color: var(--a-muted) !important; margin: 0 0 20px 0; font-family: 'Noto Serif SC', serif; line-height: 1.4; transition: 0.3s; font-weight: 900;}
.abyss-challenger-card:hover .challenger-title { color: var(--a-bone) !important; }
.challenger-author { color: #444; font-size: 1em; font-weight: 900; letter-spacing: 2px; border-top: 1px dashed #222; padding-top: 15px;}

/* 难度颜色核心 (暗黑血污版) */
.diff-border-beginner { border-bottom: 4px solid #1b4520 !important; }
.diff-border-intermediate { border-bottom: 4px solid #824204 !important; }
.diff-border-advanced { border-bottom: 4px solid var(--a-red) !important; }
.diff-text-beginner { color: #35873e !important; }
.diff-text-intermediate { color: #b8620d !important; }
.diff-text-advanced { color: var(--a-red) !important; }
.diff-bg-beginner { background: #1b4520 !important; }
.diff-bg-intermediate { background: #824204 !important; }
.diff-bg-advanced { background: var(--a-red) !important; }

/* 通用极简标签 */
.abyss-tag { padding: 4px 12px; font-size: 0.85em; letter-spacing: 3px; border: 1px solid #222; border-radius: 2px; font-weight: 900; color: var(--a-muted); background: #000;}
.abyss-card-base:hover .abyss-tag { border-color: rgba(255,255,255,0.2); color: #fff !important; }

/* ================= ⚔️ 锁喉斩击时间轴 ================= */
.abyss-timeline { position: relative; max-width: 1100px; margin: 0 auto; padding: 40px 0; }
.timeline-center-blade { position: absolute; left: 50%; top: 0; bottom: 0; width: 2px; background: linear-gradient(to bottom, transparent, #333, transparent); transform: translateX(-50%); }

.abyss-timeline-item {
    display: flex; align-items: center; justify-content: flex-end; width: 50%; padding-right: 60px;
    position: relative; margin-bottom: 50px; text-decoration: none; transition: 0.4s var(--a-ease);
}
.abyss-timeline-item.right-strike { align-self: flex-end; justify-content: flex-start; padding-right: 0; padding-left: 60px; margin-left: 50%; }

.timeline-spark {
    position: absolute; right: -6px; top: 50%; transform: translateY(-50%) rotate(45deg);
    width: 12px; height: 12px; z-index: 2; transition: 0.4s var(--a-ease); box-shadow: 0 0 10px rgba(0,0,0,0.8);
}
.right-strike .timeline-spark { right: auto; left: -6px; }

.timeline-content {
    background: #050505; border: 1px solid #111; padding: 35px 40px; width: 100%;
    transition: 0.5s var(--a-ease); position: relative; overflow: hidden;
}
.abyss-timeline-item:hover .timeline-content { border-color: #333; transform: scale(1.03); box-shadow: 0 15px 30px rgba(0,0,0,0.9); }
.abyss-timeline-item:hover .card-blood-overlay { opacity: 1; }
.abyss-timeline-item:hover .timeline-spark { transform: translateY(-50%) scale(2) rotate(135deg); background: var(--a-bright-red) !important; box-shadow: 0 0 20px var(--a-bright-red); }

.tl-meta { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 0.9em; letter-spacing: 2px; }
.tl-cat { font-weight: 900; }
.tl-date { color: #444; font-weight: bold; }
.tl-title { margin: 0 0 25px 0; font-size: 1.6em; color: var(--a-muted) !important; font-family: 'Noto Serif SC', serif; transition: 0.3s; font-weight: 900;}
.abyss-timeline-item:hover .tl-title { color: var(--a-bone) !important; }
.tl-author { font-size: 0.9em; color: #333; font-weight: 900; letter-spacing: 2px; transition: 0.3s;}
.abyss-timeline-item:hover .tl-author { color: var(--a-red); }

/* ================= 搜索结果网格 ================= */
.abyss-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 40px; }
.abyss-result-card { @extend .abyss-card-base; background: #080808; border: 1px solid #111; padding: 40px; text-decoration: none; display: flex; flex-direction: column; transition: 0.5s var(--a-ease); position: relative; overflow: hidden;}
.abyss-result-card:hover { transform: translateY(-10px); border-color: #333; box-shadow: 0 20px 40px rgba(0,0,0,0.9); }
.abyss-rc-meta { display: flex; justify-content: space-between; margin-bottom: 25px; }
.abyss-rc-title { font-size: 1.6em; margin: 0 0 30px 0; line-height: 1.4; font-family: 'Noto Serif SC', serif; transition: 0.3s; color: var(--a-muted) !important; font-weight: 900;}
.abyss-result-card:hover .abyss-rc-title { color: var(--a-bone) !important; }
.abyss-rc-footer { color: #444; font-family: 'Oswald', sans-serif; font-weight: 900; font-size: 1.1em;}
.abyss-clear-btn { color: var(--a-muted); text-decoration: none; font-weight: 900; letter-spacing: 2px; font-size: 1.1em; transition: 0.3s; }
.abyss-clear-btn:hover { color: var(--a-red); }
.abyss-empty { font-size: 2.5em; color: #111; font-family: 'Noto Serif SC', serif; font-weight: 900; letter-spacing: 15px; padding: 100px 0; text-align: center; }

/* 手机端适配 */
@media (max-width: 1000px) {
    .abyss-layout { flex-direction: column; }
    .abyss-sidebar { width: 100%; height: auto; position: static; flex-direction: row; justify-content: space-between; align-items: center; padding: 20px; border-right: none; border-bottom: 2px solid #222; }
    .abyss-brand { writing-mode: horizontal-tb; margin: 0; font-size: 2em; letter-spacing: 5px; text-shadow: none;}
    .abyss-sidebar-bot { display: none; }
    .abyss-menu { flex-direction: row; gap: 20px; flex-wrap: wrap; }
    .abyss-link { writing-mode: horizontal-tb; transform: none; }
    .abyss-link::before { display: none; }
    .abyss-link.active { border-bottom: 2px solid var(--a-red); }
    
    .abyss-main { padding: 40px 20px; }
    .abyss-quote-float { display: none; }
    .abyss-huge-outline { font-size: 4em; }
    .abyss-title { font-size: 3.5em; letter-spacing: 5px; }
    .abyss-toolbar { flex-direction: column; gap: 20px; align-items: stretch; }
    .abyss-search-box { flex-direction: column; align-items: stretch; border: none; gap: 15px; background: transparent; padding: 0;}
    .abyss-search-box input, .abyss-select { border-bottom: 2px solid #333; padding: 15px 0; border-left: none; }
    .abyss-btn-icon { text-align: center; padding: 20px 0; margin: 0; background: #111; }
    .abyss-actions { width: 100%; flex-direction: column; margin-top: 30px;}
    .abyss-quote-small { position: static !important; text-align: center; margin-bottom: 10px; }
    
    .abyss-throne-grid { grid-template-columns: 1fr; }
    .abyss-boss-card { flex-direction: column; padding: 30px; }
    .boss-icon { margin-bottom: 20px; }
    .boss-title { font-size: 2.2em; }
    
    .timeline-center-blade { left: 0; transform: none; }
    .abyss-timeline-item, .abyss-timeline-item.right-strike { width: 100%; padding-left: 40px; padding-right: 0; margin-left: 0; justify-content: flex-start; }
    .timeline-spark { left: -6px !important; right: auto !important; }
}
</style>

<?php include 'includes/footer.php'; ?>