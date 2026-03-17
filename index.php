<?php
// index.php - 100% 完整版 (无副标题，新增论坛按钮)
require_once 'config.php';
?>
<?php include 'includes/header.php'; ?>

<div class="hero-section">
    <div class="hero-content">
        
        <img src="assets/logo.png?v=<?php echo time(); ?>" alt="Naraka Hub Logo" class="hero-image-logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
        <h1 class="brush-font fallback-title" style="display:none; font-size: 5em; margin: 0 0 10px 0; color: #fff; text-shadow: 3px 3px 0px var(--accent);">NARAKA HUB</h1>
        
        <div class="hero-buttons">
            <a href="wiki.php" class="btn-hero btn-hero-primary"><i class="fas fa-book-open"></i> Explorar Wiki</a>
            <a href="guides.php" class="btn-hero btn-hero-secondary"><i class="fas fa-graduation-cap"></i> Ver Guías</a>
            <a href="forum.php" class="btn-hero btn-hero-secondary"><i class="fas fa-comments"></i> Ir al Foro</a>
        </div>
    </div>
</div>

<style>
/* ================= 极简全屏大作视觉 CSS ================= */

/* 锁定主页的全局滚动，并强制隐藏底部的页脚 */
body {
    overflow: hidden !important; 
    background-color: #000 !important; 
}

footer, .site-footer {
    display: none !important;
}

.hero-section { 
    background: linear-gradient(135deg, rgba(10, 10, 12, 0.4) 0%, rgba(201, 20, 20, 0.3) 100%), 
                url('assets/cover.jpg?v=<?php echo time(); ?>') no-repeat center 20%; 
    background-size: cover;
    width: 100%;
    height: calc(100vh - 75px); 
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center; 
    border: none; 
    position: relative; 
    padding: 20px;
    box-shadow: inset 0 -80px 150px -20px rgba(0,0,0,0.9);
    margin: 0;
}

.hero-content {
    width: 100%;
    max-width: 1000px; /* 稍微加宽一点，以容纳三个按钮 */
    margin: 0 auto;
    transform: translateY(-20px);
}

.hero-image-logo {
    max-width: 100%;
    height: auto;
    max-height: 240px; 
    margin: 0 auto 50px auto; /* 底部增加 50px 间距，替代原本文字的空间 */
    display: block;
    filter: drop-shadow(0px 8px 25px rgba(0,0,0,0.9)); 
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.hero-image-logo:hover {
    transform: scale(1.05); 
}

.hero-buttons { 
    display: flex; 
    gap: 20px; /* 间距微调，防拥挤 */
    justify-content: center; 
    flex-wrap: wrap; /* 空间不够时自动换行 */
}

.btn-hero { 
    padding: 16px 30px; 
    font-size: 1.05em; 
    font-weight: 700; 
    text-decoration: none; 
    transition: all 0.3s ease; 
    display: inline-flex; 
    align-items: center; 
    gap: 12px; 
    border: none; 
    text-transform: uppercase; 
    font-family: 'Cinzel', 'Noto Serif SC', serif; 
    letter-spacing: 2px;
}

.btn-hero-primary { 
    background: rgba(10, 10, 12, 0.85); 
    color: white; 
    border: 1px solid rgba(255,255,255,0.15); 
    backdrop-filter: blur(5px);
}

.btn-hero-primary:hover { 
    background: #000; 
    color: var(--accent); 
    transform: translateY(-3px); 
    box-shadow: 0 10px 25px rgba(0,0,0,0.8); 
    border-color: var(--accent); 
}

.btn-hero-secondary { 
    background: rgba(201, 20, 20, 0.8); 
    color: white; 
    border: 1px solid var(--accent); 
    backdrop-filter: blur(5px); 
}

.btn-hero-secondary:hover { 
    background: var(--accent); 
    color: white; 
    transform: translateY(-3px); 
    box-shadow: 0 10px 25px rgba(201, 20, 20, 0.5); 
}

@media (max-width: 768px) {
    /* 手机端防溢出保护 */
    body { overflow: auto !important; }
    .hero-section { height: auto; min-height: calc(100vh - 75px); padding-top: 40px;}
    .hero-image-logo { max-height: 140px; margin-bottom: 30px; }
    .hero-buttons { flex-direction: column; gap: 15px; width: 100%; max-width: 300px; margin: 0 auto; }
    .btn-hero { width: 100%; justify-content: center; }
}
</style>

<?php include 'includes/footer.php'; ?>