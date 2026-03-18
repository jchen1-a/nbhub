<?php
// index.php - 100% 完整版 (左下到右上阶梯式上升排版，气势如虹)
require_once 'config.php';
?>
<?php include 'includes/header.php'; ?>

<div class="ink-hero-section">
    <div class="ink-hero-bg"></div>

    <div class="ink-logo-container">
        <img src="assets/logo.png?v=<?php echo time(); ?>" alt="Naraka Hub Logo" class="ink-hero-logo" onerror="this.style.display='none';">
    </div>
    
    <div class="ink-hero-content">
        <div class="ink-scroll-container">
            
            <a href="wiki.php" class="ink-scroll-card card-step-1" style="animation-delay: 0.4s;">
                <div class="card-top-accent"></div>
                <i class="fas fa-book-open card-icon"></i>
                <div class="card-cn">万象宗卷</div>
                <div class="card-sp">EXPLORAR WIKI</div>
                <div class="card-hover-bg"></div>
            </a>

            <a href="guides.php" class="ink-scroll-card card-step-2" style="animation-delay: 0.6s;">
                <div class="card-top-accent"></div>
                <i class="fas fa-graduation-cap card-icon"></i>
                <div class="card-cn">武道秘籍</div>
                <div class="card-sp">VER GUÍAS</div>
                <div class="card-hover-bg"></div>
            </a>

            <a href="forum.php" class="ink-scroll-card card-step-3" style="animation-delay: 0.8s;">
                <div class="card-top-accent"></div>
                <i class="fas fa-comments card-icon"></i>
                <div class="card-cn">论剑客栈</div>
                <div class="card-sp">IR AL FORO</div>
                <div class="card-hover-bg"></div>
            </a>

        </div>
    </div>
</div>

<style>
/* ================= 东方水墨大作视觉 CSS ================= */
@import url('https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;700;900&family=Cinzel:wght@400;700&display=swap');

/* 锁定主页的全局滚动，并强制隐藏底部的页脚 */
body {
    overflow: hidden !important; 
    background-color: #000 !important; 
    font-family: 'Noto Serif SC', 'Cinzel', serif;
}

footer, .site-footer {
    display: none !important;
}

/* 核心容器 */
.ink-hero-section { 
    width: 100%;
    height: calc(100vh - 75px); 
    position: relative; 
    overflow: hidden;
    margin: 0;
}

/* 带有缓慢呼吸感的水墨背景层 */
.ink-hero-bg {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(135deg, rgba(15, 15, 18, 0.4) 0%, rgba(201, 20, 20, 0.15) 100%), 
                url('assets/cover.jpg?v=<?php echo time(); ?>') no-repeat center 20%; 
    background-size: cover;
    z-index: 1;
    animation: backgroundBreathe 25s infinite alternate ease-in-out;
}

@keyframes backgroundBreathe {
    0% { transform: scale(1); }
    100% { transform: scale(1.08); }
}

/* ================= 1. Logo 电影级显影动效 ================= */
@keyframes cinematicLogo {
    0% { opacity: 0; filter: blur(15px); transform: scale(1.15) translate3d(0, -20px, 0); }
    100% { opacity: 1; filter: blur(0); transform: scale(1) translate3d(0, 0, 0); }
}

.ink-logo-container {
    position: absolute;
    top: 50px;
    left: 60px;
    z-index: 10;
    opacity: 0;
    animation: cinematicLogo 2.5s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
}

.ink-hero-logo {
    max-width: 100%;
    height: auto;
    max-height: 220px; 
    display: block;
    filter: drop-shadow(0px 10px 20px rgba(0,0,0,0.8)); 
    transition: transform 0.5s ease;
}

.ink-hero-logo:hover {
    transform: scale(1.03);
}

/* ================= 2. 画卷导航区布局 ================= */
.ink-hero-content {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    display: flex;
    align-items: center; /* 垂直居中 */
    justify-content: flex-start; /* 水平靠左，避开右侧人物 */
    padding-left: 8vw; /* 距离左侧边距 */
    z-index: 10;
}

.ink-scroll-container {
    display: flex;
    gap: 40px; /* 画卷间距 */
    align-items: flex-start; /* 顶部对齐，方便实现阶梯效果 */
    margin-top: 15vh; /* 整体往下压一点，避开上方的 Logo */
}

/* 核心错落美学：阶梯式上升 (左下到右上) */
.card-step-1 { margin-top: 80px; } /* 左侧降到最低 */
.card-step-2 { margin-top: 40px; } /* 中间居中 */
.card-step-3 { margin-top: 0; }    /* 右侧升到最高 */


/* ================= 3. 画卷展开动效与卡片设计 ================= */
@keyframes scrollUnroll {
    0% { opacity: 0; clip-path: polygon(0 0, 100% 0, 100% 0, 0 0); transform: translateY(40px); }
    100% { opacity: 1; clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%); transform: translateY(0); }
}

.ink-scroll-card {
    position: relative;
    width: 170px; 
    height: 480px;
    background: rgba(250, 250, 250, 0.08); 
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.15);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    padding: 50px 20px;
    text-decoration: none;
    overflow: hidden;
    /* 画卷展开动画 */
    opacity: 0;
    clip-path: polygon(0 0, 100% 0, 100% 0, 0 0);
    animation: scrollUnroll 1.5s cubic-bezier(0.25, 1, 0.25, 1) forwards;
    transition: transform 0.5s cubic-bezier(0.25, 0.8, 0.25, 1), 
                border-color 0.5s ease, 
                box-shadow 0.5s ease,
                background 0.5s ease;
}

/* 顶部朱砂红点缀线 */
.card-top-accent {
    position: absolute; top: 0; left: 0; width: 100%; height: 4px;
    background: var(--accent, #8b0000);
    transform: scaleX(0); transition: transform 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.ink-scroll-card:hover {
    transform: translateY(-20px) !important; /* 悬停时明显上浮 */
    border-color: rgba(201, 20, 20, 0.6);
    background: rgba(250, 250, 250, 0.15);
    box-shadow: 0 25px 50px rgba(0,0,0,0.5), 0 0 30px rgba(201,20,20,0.1);
}
.ink-scroll-card:hover .card-top-accent { transform: scaleX(1); }

/* 顶部图标 */
.card-icon {
    font-size: 2.2em;
    color: rgba(255,255,255,0.8);
    z-index: 2;
    transition: all 0.5s ease;
}
.ink-scroll-card:hover .card-icon {
    color: #ff4d4d;
    transform: scale(1.2);
}

/* 竖排书法字体 */
.card-cn {
    writing-mode: vertical-rl; 
    font-family: 'Noto Serif SC', serif;
    font-size: 2.4em;
    font-weight: 900;
    color: rgba(255,255,255,0.9);
    letter-spacing: 20px;
    text-shadow: 2px 2px 10px rgba(0,0,0,0.8);
    z-index: 2;
    transition: color 0.5s ease;
}
.ink-scroll-card:hover .card-cn {
    color: #fff;
    text-shadow: 0 0 15px rgba(255,255,255,0.5);
}

/* 底部西班牙语标签 */
.card-sp {
    font-family: 'Cinzel', serif;
    font-size: 0.9em;
    font-weight: 700;
    color: rgba(255,255,255,0.5);
    text-align: center;
    letter-spacing: 3px;
    z-index: 2;
    transition: color 0.5s;
}
.ink-scroll-card:hover .card-sp {
    color: #ff4d4d;
}

/* 悬停时的底部水墨红光晕 */
.card-hover-bg {
    position: absolute;
    bottom: 0; left: 0; width: 100%; height: 0%;
    background: linear-gradient(to top, rgba(201,20,20,0.2), transparent);
    z-index: 1;
    transition: height 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.ink-scroll-card:hover .card-hover-bg {
    height: 60%;
}

/* 移动端适配 (手机屏幕小，恢复居中和横向) */
@media (max-width: 768px) {
    body { overflow: auto !important; }
    .ink-hero-section { height: auto; min-height: calc(100vh - 75px); padding-bottom: 40px; }
    .ink-logo-container { position: static; text-align: center; margin-top: 40px; margin-bottom: 20px; animation: none; opacity: 1; }
    .ink-hero-logo { max-height: 150px; margin: 0 auto; }
    
    .ink-hero-content { position: static; justify-content: center; padding-left: 0; }
    .ink-scroll-container { flex-direction: column; gap: 20px; margin-top: 20px; align-items: center; }
    
    /* 手机端取消错落阶梯效果 */
    .card-step-1, .card-step-2, .card-step-3 { margin-top: 0; }
    
    /* 移动端改回横向排版防止太长 */
    .ink-scroll-card { width: 90%; max-width: 320px; height: 100px; flex-direction: row; padding: 0 30px; animation: none; opacity: 1; clip-path: none; }
    .card-cn { writing-mode: horizontal-tb; font-size: 1.5em; letter-spacing: 5px; }
    .card-sp { display: none; } 
}
</style>

<?php include 'includes/footer.php'; ?>