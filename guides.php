<?php
// guides.php - 100% 完整版 (修罗血海·嘉豪弑神版 / The Crimson Asura)
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
    return 'fa-skull'; // 默认换成骷髅头，更加狂暴
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

<div class="crimson-global-bg">
    <div class="crimson-watermark">杀 戮</div>
    <div class="crimson-watermark" style="left:5%; top:60%; font-size:15vw; opacity:0.08;">弑 神</div>
    
    <div class="blood-drop" style="top: 15%; left: 20%; transform: scale(1.5) rotate(-30deg);"></div>
    <div class="blood-drop" style="top: 25%; left: 22%; transform: scale(0.8) rotate(-45deg);"></div>
    <div class="blood-drop" style="top: 60%; right: 15%; transform: scale(2) rotate(60deg);"></div>
    <div class="blood-drop" style="top: 65%; right: 18%; transform: scale(0.5) rotate(70deg);"></div>
    <div class="blood-splatter" style="top: 40%; right: 5%;"></div>
</div>

<div class="crimson-layout">
    
    <aside class="crimson-sidebar">
        <div class="crimson-sidebar-inner">
            <ul class="crimson-menu">
                <li>
                    <a href="guides.php" class="crimson-link <?php echo empty($filter_category) ? 'active' : ''; ?>">
                        <span class="crimson-text">TODO</span>
                    </a>
                </li>
                <?php foreach($categories as $cat): ?>
                    <li>
                        <a href="guides.php?category=<?php echo urlencode($cat['category']); ?>" class="crimson-link <?php echo $filter_category == $cat['category'] ? 'active' : ''; ?>">
                            <span class="crimson-text"><?php echo htmlspecialchars(strtoupper($cat['category'])); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="crimson-brand">血<br>染<br>秘<br>卷</div>
    </aside>

    <main class="crimson-main">
        
        <header class="crimson-header">
            <div class="crimson-quote-vertical" style="right: 5%; top: 0;">「凡遇吾者，皆为刀下亡魂」</div>
            <div class="crimson-quote-vertical" style="right: 12%; top: 100px; color:#8b0000; font-size:1.2em;">—— 剑神 · 嘉豪</div>

            <div class="crimson-huge-outline">SANGRE</div>
            <h1 class="crimson-title">武 道 秘 籍</h1>
            <p class="crimson-subtitle">// EL DÉBIL SANGRA. EL FUERTE GOBIERNA.</p>
            <div class="crimson-blade-line"></div>
        </header>

        <?php if (isset($error)): ?>
            <div class="crimson-error">LA MUERTE TE ALCANZÓ: <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="crimson-toolbar">
            <form method="GET" action="guides.php" class="crimson-search-form">
                <div class="crimson-search-slash">
                    <input type="text" name="search" placeholder="RASTREA EL OLOR A SANGRE..." value="<?php echo htmlspecialchars($search); ?>">
                    <?php if(!empty($filter_category)): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($filter_category); ?>">
                    <?php endif; ?>
                    <select name="difficulty" class="crimson-select">
                        <option value="">NIVEL (TODO)</option>
                        <option value="beginner" <?php echo $filter_difficulty == 'beginner' ? 'selected' : ''; ?>>蝼蚁 (NOVATO)</option>
                        <option value="intermediate" <?php echo $filter_difficulty == 'intermediate' ? 'selected' : ''; ?>>杀手 (EXPERTO)</option>
                        <option value="advanced" <?php echo $filter_difficulty == 'advanced' ? 'selected' : ''; ?>>修罗 (ASURA)</option>
                    </select>
                    <button type="submit" class="crimson-btn-icon"><i class="fas fa-skull"></i> 猎杀</button>
                </div>
            </form>
            
            <div class="crimson-actions">
                <div class="crimson-quote-small" style="position:absolute; top:-30px; right:0;">「拔剑吧，弱者不配提问。」</div>
                
                <?php if (is_logged_in()): ?>
                    <a href="new-guide.php" class="crimson-btn-blood">祭出长刀 <i class="fas fa-tint"></i></a>
                <?php else: ?>
                    <a href="login.php" class="crimson-btn-ghost">踏入死局 <i class="fas fa-sign-in-alt"></i></a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($is_searching): ?>
            
            <section class="crimson-section">
                <div class="crimson-section-header">
                    <h2>R E S U L T A D O S</h2>
                    <a href="guides.php" class="crimson-clear-btn">LAVAR LA SANGRE <i class="fas fa-tint-slash"></i></a>
                </div>
                
                <?php if(empty($search_results)): ?>
                    <div class="crimson-empty">连尸骨都未曾留下。</div>
                <?php else: ?>
                    <div class="crimson-grid">
                        <?php foreach($search_results as $guide): ?>
                            <a href="article.php?id=<?php echo $guide['id']; ?>" class="crimson-result-card diff-border-<?php echo $guide['difficulty']; ?>">
                                <div class="crimson-rc-meta">
                                    <span class="crimson-tag"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></span>
                                    <span class="crimson-tag diff-text-<?php echo $guide['difficulty']; ?>"><?php echo htmlspecialchars(strtoupper($guide['difficulty'])); ?></span>
                                </div>
                                <h3 class="crimson-rc-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                <div class="crimson-rc-footer">
                                    <span>POR <?php echo htmlspecialchars(strtoupper($guide['username'])); ?></span>
                                </div>
                                <div class="card-blood-stain"></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        <?php else: ?>
            
            <section class="crimson-section">
                <div class="crimson-section-header">
                    <h2>修 罗 血 座 // <span style="color:var(--c-red);">LÍDERES</span></h2>
                    <div class="crimson-quote-horizontal" style="margin-left:20px;">「唯有踩着尸体，方能登顶。」</div>
                </div>

                <div class="crimson-throne-grid">
                    <?php if(!empty($popular_guides)): ?>
                        <?php $rank = 1; foreach($popular_guides as $guide): ?>
                            
                            <?php if($rank == 1): ?>
                            <a href="article.php?id=<?php echo $guide['id']; ?>" class="crimson-boss-card diff-border-<?php echo $guide['difficulty']; ?>">
                                <div class="boss-rank-mark">01<br><span style="font-size:0.2em; color:var(--c-red);">EL DIOS</span></div>
                                <div class="boss-content">
                                    <i class="fas <?php echo get_guide_icon($guide['category']); ?> boss-icon"></i>
                                    <div class="boss-info">
                                        <span class="crimson-tag diff-text-<?php echo $guide['difficulty']; ?>" style="border-color:var(--c-red); color:var(--c-red) !important;"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></span>
                                        <h3 class="boss-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                        <div class="boss-meta">
                                            <span><i class="fas fa-eye"></i> <?php echo $guide['views']; ?></span>
                                            <span><i class="fas fa-skull"></i> <?php echo htmlspecialchars(strtoupper($guide['username'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-blood-stain" style="background: radial-gradient(circle at bottom right, rgba(139,0,0,0.5), transparent 70%);"></div>
                            </a>
                            
                            <?php else: ?>
                            <a href="article.php?id=<?php echo $guide['id']; ?>" class="crimson-challenger-card diff-border-<?php echo $guide['difficulty']; ?>">
                                <div class="challenger-rank">0<?php echo $rank; ?></div>
                                <div class="challenger-icon"><i class="fas <?php echo get_guide_icon($guide['category']); ?>"></i></div>
                                <div class="challenger-content">
                                    <span class="crimson-tag diff-text-<?php echo $guide['difficulty']; ?>" style="margin-bottom:15px; display:inline-block;"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></span>
                                    <h3 class="challenger-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                </div>
                                <div class="challenger-author">POR <?php echo htmlspecialchars(strtoupper($guide['username'])); ?></div>
                                <div class="card-blood-stain"></div>
                            </a>
                            <?php endif; ?>

                        <?php $rank++; endforeach; ?>
                    <?php else: ?>
                        <div class="crimson-empty">EL TRONO ESPERA SANGRE.</div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="crimson-section" style="margin-top: 150px; position:relative;">
                
                <div class="crimson-quote-vertical" style="left: -40px; top: 50px; font-size:2em; opacity:0.15; z-index:-1;">「斩业非斩人，杀生为护生」</div>

                <div class="crimson-section-header" style="justify-content: center; text-align: center; border:none; flex-direction:column;">
                    <h2>剑 痕 未 冷 // <span style="color:#555;">RECIENTES</span></h2>
                    <p style="color:var(--c-red); font-family:monospace; margin:10px 0 0 0;">NUEVAS VÍCTIMAS REGISTRADAS</p>
                </div>

                <div class="crimson-timeline">
                    <div class="timeline-center-line"></div>
                    
                    <?php if(!empty($recent_guides)): ?>
                        <?php $i = 0; foreach($recent_guides as $guide): ?>
                            
                            <a href="article.php?id=<?php echo $guide['id']; ?>" class="crimson-timeline-item <?php echo $i % 2 == 0 ? 'left-strike' : 'right-strike'; ?>">
                                <div class="timeline-dot diff-bg-<?php echo $guide['difficulty']; ?>"></div>
                                <div class="timeline-content">
                                    <div class="tl-meta">
                                        <span class="tl-cat diff-text-<?php echo $guide['difficulty']; ?>"><?php echo htmlspecialchars(strtoupper($guide['category'] ?? 'GENERAL')); ?></span>
                                        <span class="tl-date"><?php echo date('d.m.y', strtotime($guide['created_at'])); ?></span>
                                    </div>
                                    <h3 class="tl-title"><?php echo htmlspecialchars($guide['title']); ?></h3>
                                    <div class="tl-author">POR <?php echo htmlspecialchars(strtoupper($guide['username'])); ?></div>
                                    <div class="card-blood-stain"></div>
                                </div>
                            </a>
                        <?php $i++; endforeach; ?>
                    <?php else: ?>
                        <div class="crimson-empty">NADA NUEVO.</div>
                    <?php endif; ?>
                </div>
            </section>

        <?php endif; ?>

    </main>
</div>

<style>
/* ================= 嘉豪专属：修罗血海装逼排版 (Crimson Asura Style) ================= */
@import url('https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@700;900&family=Cinzel:wght@700;900&family=Oswald:wght@500;700&display=swap');

:root {
    --c-white: #ffffff;
    --c-bone: #f4f4f5;
    --c-dark: #111111;
    --c-black: #050505;
    --c-red: #990000;
    --c-darkred: #4d0000;
    --c-blood: rgba(153, 0, 0, 0.85);
    --c-muted: #888888;
    --c-ease: cubic-bezier(0.16, 1, 0.3, 1);
}

body { background-color: var(--c-bone) !important; color: var(--c-dark); font-family: 'Oswald', sans-serif; overflow-x: hidden; }
h1, h2, h3 { font-family: 'Cinzel', 'Noto Serif SC', serif; font-weight: 900; color: var(--c-dark) !important; }

/* 红白渐变与血污背景 */
.crimson-global-bg {
    position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
    /* 顶部惨白，向下渐渐变成绝望的暗红色 */
    background: linear-gradient(180deg, var(--c-white) 0%, #ffcccc 60%, var(--c-darkred) 100%);
    z-index: -10; pointer-events: none; 
}
/* 背景装逼巨大文字 */
.crimson-watermark {
    position: absolute; right: -5%; top: 5%;
    font-family: 'Noto Serif SC', serif; font-size: 45vw; font-weight: 900;
    color: rgba(153,0,0,0.06); line-height: 0.8; writing-mode: vertical-rl;
    user-select: none; white-space: nowrap; transform: rotate(-5deg);
}

/* ================= 纯 CSS 绘制的“血滴”与“血溅”效果 ================= */
.blood-drop {
    position: absolute; width: 30px; height: 30px; background: var(--c-red);
    border-radius: 50% 0 50% 50%; /* 泪滴/血滴形状 */
    box-shadow: inset -5px -5px 10px rgba(0,0,0,0.5), 0 5px 10px rgba(153,0,0,0.4);
    opacity: 0.8;
}
.blood-splatter {
    position: absolute; width: 100px; height: 100px;
    background: radial-gradient(circle at 30% 30%, var(--c-red) 10%, transparent 20%),
                radial-gradient(circle at 70% 60%, var(--c-darkred) 15%, transparent 25%),
                radial-gradient(circle at 40% 80%, var(--c-red) 5%, transparent 15%);
    filter: blur(1px); opacity: 0.6; transform: rotate(15deg);
}

/* ================= 嘉豪专属：装逼竖排台词 ================= */
.crimson-quote-vertical {
    position: absolute; writing-mode: vertical-rl; font-family: 'Noto Serif SC', serif;
    font-size: 1.8em; font-weight: 900; color: rgba(0,0,0,0.8); letter-spacing: 10px;
    text-shadow: 2px 2px 5px rgba(255,255,255,0.5); z-index: 5; pointer-events: none;
}
.crimson-quote-horizontal {
    font-family: 'Noto Serif SC', serif; font-size: 1.2em; font-weight: 900; 
    color: var(--c-red); letter-spacing: 5px; font-style: italic;
}
.crimson-quote-small {
    font-family: 'Noto Serif SC', serif; font-size: 0.9em; font-weight: 900; 
    color: var(--c-red); letter-spacing: 2px; opacity: 0.8;
}

/* ================= 绝对竖排侧边栏 ================= */
.crimson-layout { 
    display: flex; align-items: flex-start; min-height: calc(100vh - 100px); padding-bottom: 50px;
}

.crimson-sidebar {
    width: 100px; flex-shrink: 0; background: var(--c-white); border-right: 3px solid var(--c-red);
    position: sticky; top: 100px; height: calc(100vh - 120px); 
    display: flex; flex-direction: column; justify-content: space-between;
    padding: 60px 0; z-index: 50; box-shadow: 10px 0 30px rgba(153,0,0,0.15);
}
.crimson-sidebar-inner { flex: 1; display: flex; justify-content: center; align-items: center; }

.crimson-menu { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 40px; align-items: center; }
.crimson-link {
    display: flex; flex-direction: row; align-items: center; gap: 15px; text-decoration: none; 
    writing-mode: vertical-rl; transform: rotate(180deg);
    color: var(--c-muted); transition: color 0.3s var(--c-ease); position: relative;
}
.crimson-text { font-size: 1.4em; font-weight: 900; letter-spacing: 5px; font-family: 'Oswald', sans-serif; }

.crimson-link::before {
    content: ''; position: absolute; right: -15px; top: 0; width: 4px; height: 0; background: var(--c-red); transition: 0.4s var(--c-ease);
}
.crimson-link:hover, .crimson-link.active { color: var(--c-dark); }
.crimson-link.active::before { height: 100%; box-shadow: 0 0 15px var(--c-red); }

.crimson-brand { font-family: 'Noto Serif SC', serif; font-size: 2.2em; font-weight: 900; color: var(--c-red); text-align: center; line-height: 1.1; text-shadow: 2px 2px 0 rgba(0,0,0,0.1); }

/* ================= 狂傲主视界 ================= */
.crimson-main { flex: 1; padding: 40px 4vw 100px 4vw; max-width: 1600px; animation: kxFadeIn 1s var(--c-ease) forwards; position: relative;}
@keyframes kxFadeIn { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

/* 标题区 */
.crimson-header { position: relative; margin-bottom: 80px; display: flex; flex-direction: column; align-items: flex-start; }
.crimson-huge-outline { 
    font-size: 7em; font-weight: 900; color: transparent; -webkit-text-stroke: 2px rgba(153,0,0,0.15); 
    letter-spacing: 5px; line-height: 0.8; margin-left: -10px; font-family: 'Cinzel', serif;
}
.crimson-title { font-size: 4.5em; margin: 0 0 10px 0; color: var(--c-dark) !important; letter-spacing: 12px; z-index: 2; text-shadow: 4px 4px 0 rgba(255,255,255,0.8); }
.crimson-blade-line { width: 120px; height: 6px; background: var(--c-red); margin: 20px 0; box-shadow: 0 5px 10px rgba(153,0,0,0.3); }
.crimson-subtitle { color: var(--c-darkred); font-size: 1.2em; letter-spacing: 4px; margin: 0; font-weight: 900; }

/* 血腥搜索区 */
.crimson-toolbar { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 80px; flex-wrap: wrap; gap: 40px; position: relative; z-index: 10; }
.crimson-search-form { flex: 1; max-width: 700px; }
.crimson-search-slash {
    display: flex; align-items: center; border-bottom: 3px solid var(--c-dark); padding-bottom: 10px; transition: 0.4s var(--c-ease);
}
.crimson-search-slash:focus-within { border-bottom-color: var(--c-red); transform: translateX(15px); }
.crimson-search-slash input { flex: 1; background: transparent; border: none; color: var(--c-dark) !important; font-size: 1.3em; font-weight: 900; font-family: 'Oswald', sans-serif; outline: none; }
.crimson-search-slash input::placeholder { color: #888; }
.crimson-select { background: transparent; color: var(--c-dark); border: none; border-left: 3px solid var(--c-dark); padding: 0 15px; font-size: 1em; font-weight: 900; outline: none; cursor: pointer; text-transform: uppercase; font-family: 'Oswald', sans-serif; }
.crimson-select option { background: var(--c-white); color: var(--c-dark); }
.crimson-btn-icon { background: transparent; border: none; color: var(--c-dark); font-size: 1.2em; font-weight: 900; cursor: pointer; transition: 0.3s; margin-left: 20px; font-family: 'Noto Serif SC', serif;}
.crimson-btn-icon:hover { color: var(--c-red); letter-spacing: 5px; }

/* 按钮 (泣血之刃) */
.crimson-actions { display: flex; gap: 20px; position: relative; }
.crimson-btn-blood {
    display: inline-flex; align-items: center; gap: 12px; height: 55px; padding: 0 35px;
    background: var(--c-red); color: #fff; text-decoration: none; font-weight: 900; letter-spacing: 3px;
    transition: 0.3s; border: none; font-size: 1.1em; clip-path: polygon(15px 0, 100% 0, 100% calc(100% - 15px), calc(100% - 15px) 100%, 0 100%, 0 15px);
    box-shadow: inset 0 0 0 2px rgba(255,255,255,0.3);
}
.crimson-btn-blood:hover { background: var(--c-dark); color: var(--c-red); transform: scale(1.05); }
.crimson-btn-ghost {
    display: inline-flex; align-items: center; gap: 12px; height: 55px; padding: 0 35px;
    background: transparent; color: var(--c-dark); border: 3px solid var(--c-dark); text-decoration: none; font-weight: 900; letter-spacing: 3px; transition: 0.3s;
}
.crimson-btn-ghost:hover { border-color: var(--c-red); color: var(--c-red); background: rgba(153,0,0,0.05); }


/* ================= 👑 弑神王座 (打破统一性) ================= */
.crimson-section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; border-bottom: 2px solid var(--c-dark); padding-bottom: 10px;}
.crimson-section-header h2 { font-size: 1.8em; letter-spacing: 8px; margin: 0; color: var(--c-dark); }

.crimson-throne-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; }

/* 卡片基类：惨白色，渗血悬停特效 */
.crimson-card-base {
    background: rgba(255,255,255,0.9); border: 2px solid var(--c-dark); position: relative; overflow: hidden; text-decoration: none;
    transition: 0.4s var(--c-ease); box-shadow: 10px 10px 0 rgba(0,0,0,0.1);
}
.card-blood-stain {
    position: absolute; bottom: -50%; right: -50%; width: 100%; height: 100%;
    background: radial-gradient(circle, var(--c-blood) 0%, transparent 60%);
    opacity: 0; transition: 0.6s var(--c-ease); z-index: 0; pointer-events: none;
}
.crimson-card-base:hover { transform: translate(-5px, -5px); box-shadow: 15px 15px 0 var(--c-red); border-color: var(--c-red); }
.crimson-card-base:hover .card-blood-stain { bottom: -20%; right: -20%; opacity: 1; }
.crimson-card-base * { position: relative; z-index: 2; } /* 保证文字在血迹之上 */

/* 第一名：血色横向巨无霸 */
.crimson-boss-card {
    grid-column: 1 / -1; padding: 50px; border-left: 10px solid var(--c-red);
}
.boss-rank-mark { position: absolute; right: 30px; top: 10px; font-family: 'Cinzel', serif; font-size: 10em; font-weight: 900; color: rgba(0,0,0,0.05); line-height: 0.8; text-align: right; }
.boss-content { display: flex; align-items: center; gap: 40px; width: 100%; }
.boss-icon { font-size: 5em; color: var(--c-dark); transition: 0.4s; }
.crimson-boss-card:hover .boss-icon { color: var(--c-white); transform: scale(1.1) rotate(10deg); }
.boss-info { flex: 1; }
.boss-title { font-size: 2.8em; color: var(--c-dark) !important; margin: 10px 0 20px 0; font-family: 'Noto Serif SC', serif; line-height: 1.2; transition: 0.3s; font-weight: 900;}
.crimson-boss-card:hover .boss-title { color: var(--c-white) !important; }
.boss-meta { display: flex; gap: 30px; color: var(--c-muted); font-size: 1.2em; font-weight: 900; }
.crimson-boss-card:hover .boss-meta { color: rgba(255,255,255,0.8); }

/* 第二/三名：竖向残碑 */
.crimson-challenger-card { padding: 40px 30px; display: flex; flex-direction: column; }
.challenger-rank { position: absolute; right: 10px; top: -10px; font-family: 'Cinzel', serif; font-size: 6em; font-weight: 900; color: rgba(0,0,0,0.05); transition: 0.4s; pointer-events: none; }
.crimson-challenger-card:hover .challenger-rank { color: rgba(255,255,255,0.1); }
.challenger-icon { font-size: 3em; color: var(--c-dark); margin-bottom: 30px; transition: 0.4s; }
.crimson-challenger-card:hover .challenger-icon { color: var(--c-white); }
.challenger-content { flex: 1; }
.challenger-title { font-size: 1.6em; color: var(--c-dark) !important; margin: 0 0 20px 0; font-family: 'Noto Serif SC', serif; line-height: 1.4; transition: 0.3s; font-weight: 900;}
.crimson-challenger-card:hover .challenger-title { color: var(--c-white) !important; }
.challenger-author { color: var(--c-darkred); font-size: 1em; font-weight: 900; letter-spacing: 2px; border-top: 2px solid rgba(0,0,0,0.1); padding-top: 15px;}
.crimson-challenger-card:hover .challenger-author { color: #fff; border-color: rgba(255,255,255,0.2); }

/* 难度颜色核心 (鲜血版) */
.diff-border-beginner { border-bottom: 5px solid #2e7d32 !important; }
.diff-border-intermediate { border-bottom: 5px solid #d84315 !important; }
.diff-border-advanced { border-bottom: 5px solid var(--c-red) !important; }
.diff-text-beginner { color: #2e7d32 !important; }
.diff-text-intermediate { color: #d84315 !important; }
.diff-text-advanced { color: var(--c-red) !important; }
.diff-bg-beginner { background: #2e7d32 !important; }
.diff-bg-intermediate { background: #d84315 !important; }
.diff-bg-advanced { background: var(--c-red) !important; }

/* 通用极简标签 */
.crimson-tag { padding: 3px 12px; font-size: 0.85em; letter-spacing: 2px; border: 2px solid rgba(0,0,0,0.1); border-radius: 2px; font-weight: 900; color: var(--c-dark);}
.crimson-card-base:hover .crimson-tag { border-color: rgba(255,255,255,0.3); color: #fff !important; }

/* ================= ⚔️ 剑痕斩击时间轴 ================= */
.crimson-timeline { position: relative; max-width: 1100px; margin: 0 auto; padding: 40px 0; }
.timeline-center-line { position: absolute; left: 50%; top: 0; bottom: 0; width: 4px; background: var(--c-dark); transform: translateX(-50%); }

.crimson-timeline-item {
    display: flex; align-items: center; justify-content: flex-end; width: 50%; padding-right: 50px;
    position: relative; margin-bottom: 40px; text-decoration: none; transition: 0.3s;
}
.crimson-timeline-item.right-strike { align-self: flex-end; justify-content: flex-start; padding-right: 0; padding-left: 50px; margin-left: 50%; }

.timeline-dot {
    position: absolute; right: -10px; top: 50%; transform: translateY(-50%);
    width: 20px; height: 20px; border-radius: 50%; z-index: 2; transition: 0.3s; border: 4px solid var(--c-white);
}
.right-strike .timeline-dot { right: auto; left: -10px; }

.timeline-content {
    background: rgba(255,255,255,0.9); border: 2px solid var(--c-dark); padding: 30px 40px; width: 100%;
    transition: 0.4s var(--c-ease); position: relative; box-shadow: 8px 8px 0 rgba(0,0,0,0.1); overflow: hidden;
}
.crimson-timeline-item:hover .timeline-content { border-color: var(--c-red); transform: scale(1.02); box-shadow: 12px 12px 0 var(--c-red); }
.crimson-timeline-item:hover .timeline-dot { transform: translateY(-50%) scale(1.5); box-shadow: 0 0 15px var(--c-red); }

.tl-meta { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.9em; letter-spacing: 2px; }
.tl-cat { font-weight: 900; }
.tl-date { color: var(--c-muted); font-weight: bold; }
.tl-title { margin: 0 0 20px 0; font-size: 1.5em; color: var(--c-dark) !important; font-family: 'Noto Serif SC', serif; transition: 0.3s; font-weight: 900;}
.crimson-timeline-item:hover .tl-title { color: var(--c-white) !important; }
.tl-author { font-size: 0.9em; color: var(--c-darkred); font-weight: 900; letter-spacing: 2px; }
.crimson-timeline-item:hover .tl-author { color: rgba(255,255,255,0.8); }

/* ================= 搜索结果网格 ================= */
.crimson-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 40px; }
.crimson-result-card { @extend .crimson-card-base; background: rgba(255,255,255,0.9); border: 2px solid var(--c-dark); padding: 40px; text-decoration: none; display: flex; flex-direction: column; transition: 0.4s var(--c-ease); position: relative; overflow: hidden; box-shadow: 8px 8px 0 rgba(0,0,0,0.1);}
.crimson-result-card:hover { transform: translate(-5px, -5px); box-shadow: 12px 12px 0 var(--c-red); border-color: var(--c-red); }
.crimson-rc-meta { display: flex; justify-content: space-between; margin-bottom: 25px; }
.crimson-rc-title { font-size: 1.6em; margin: 0 0 30px 0; line-height: 1.4; font-family: 'Noto Serif SC', serif; transition: 0.3s; color: var(--c-dark) !important; font-weight: 900;}
.crimson-result-card:hover .crimson-rc-title { color: var(--c-white) !important; }
.crimson-rc-footer { color: var(--c-darkred); font-family: 'Oswald', sans-serif; font-weight: 900; font-size: 1.1em;}
.crimson-result-card:hover .crimson-rc-footer { color: rgba(255,255,255,0.8); }
.crimson-clear-btn { color: var(--c-dark); text-decoration: none; font-weight: 900; letter-spacing: 2px; font-size: 1.1em; transition: 0.3s; border-bottom: 2px solid var(--c-dark); }
.crimson-clear-btn:hover { color: var(--c-red); border-color: var(--c-red); }
.crimson-empty { font-size: 2em; color: var(--c-dark); font-family: 'Noto Serif SC', serif; font-weight: 900; letter-spacing: 10px; padding: 80px 0; text-align: center; }

/* 手机端防车祸适配 */
@media (max-width: 1000px) {
    .crimson-layout { flex-direction: column; }
    .crimson-sidebar { width: 100%; height: auto; position: static; flex-direction: row; justify-content: space-between; align-items: center; padding: 20px; border-right: none; border-bottom: 4px solid var(--c-red); }
    .crimson-brand { writing-mode: horizontal-tb; margin: 0; font-size: 1.8em; letter-spacing: 5px; }
    .crimson-menu { flex-direction: row; gap: 20px; flex-wrap: wrap; }
    .crimson-link { writing-mode: horizontal-tb; transform: none; }
    .crimson-link::before { display: none; }
    .crimson-link.active { border-bottom: 4px solid var(--c-red); }
    
    .crimson-main { padding: 40px 20px; }
    .crimson-quote-vertical { display: none; }
    .crimson-huge-outline { font-size: 4em; }
    .crimson-title { font-size: 3em; letter-spacing: 2px; }
    .crimson-toolbar { flex-direction: column; gap: 20px; align-items: stretch; }
    .crimson-search-slash { flex-direction: column; align-items: stretch; border: none; gap: 15px; }
    .crimson-search-slash input, .crimson-select { border-bottom: 3px solid var(--c-dark); padding: 15px 0; border-left: none; }
    .crimson-btn-icon { text-align: left; padding: 15px 0; margin: 0; }
    .crimson-actions { width: 100%; flex-direction: column; }
    
    .crimson-throne-grid { grid-template-columns: 1fr; }
    .crimson-boss-card { flex-direction: column; padding: 30px; }
    .boss-icon { margin-bottom: 20px; }
    .boss-title { font-size: 2em; }
    
    .timeline-center-line { left: 0; transform: none; }
    .crimson-timeline-item, .crimson-timeline-item.right-strike { width: 100%; padding-left: 40px; padding-right: 0; margin-left: 0; justify-content: flex-start; }
    .timeline-dot { left: -10px !important; right: auto !important; }
}
</style>

<?php include 'includes/footer.php'; ?>