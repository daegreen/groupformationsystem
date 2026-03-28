Here is an HTML document that enhances the "About" modal with superior responsiveness across all device sizes and orientations.
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <title>Group Formation · University Connect</title>
    <!-- Font Awesome 6 (free) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts: Inter + Poppins for modern look -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Poppins:wght@500;600&display=swap" rel="stylesheet">
    <style>
        /* reset & base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-dark: #0b2a41;
            --primary-soft: #1e3a5f;
            --accent-bg: #fafcff;
            --card-border: #c9dff2;
            --footer-bg: #e9edf2;
            --text-dark: #0b1e2e;
            --shadow-sm: 0 8px 20px rgba(0, 20, 40, 0.08);
            --shadow-hover: 0 16px 28px rgba(0, 30, 60, 0.12);
            --radius-card: 28px;
            --radius-btn: 40px;
            --transition: all 0.25s ease;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #eef3fc;
            min-height: 100vh;
            margin: 0;
            line-height: 1.4;
            -webkit-font-smoothing: antialiased;
            display: flex;
            flex-direction: column;
        }

        /* ===== MODERN HEADER WITH MENU (sticky + glass) ===== */
        .main-header {
            background: var(--primary-dark);
            background: linear-gradient(135deg, #0a2a42 0%, #0f2f48 100%);
            border-bottom: 1px solid rgba(255,255,255,0.2);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(4px);
            box-shadow: 0 6px 14px rgba(0,0,0,0.08);
        }

        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0.9rem 2rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: -0.3px;
        }

        .logo-area i {
            font-size: 2rem;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        .logo-area span {
            background: linear-gradient(120deg, #fff, #c8e2ff);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            font-family: 'Poppins', sans-serif;
        }

        .nav-wrapper {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .home-link {
            color: #f0f6fe;
            text-decoration: none;
            font-weight: 600;
            padding: 0.7rem 1.5rem;
            border-radius: 40px;
            transition: all 0.2s;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(4px);
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.15);
        }

        .home-link i {
            font-size: 1.1rem;
        }

        .home-link:hover {
            background: rgba(255,255,255,0.25);
            color: white;
            transform: translateY(-2px);
            border-color: rgba(255,255,255,0.4);
        }

        .home-link.active-role {
            background: #ffb347;
            background: linear-gradient(135deg, #ffb347, #ff8c1a);
            color: #1e2f3f;
            border-color: #ffcd7e;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .nav-menu {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-menu li a {
            color: #f0f6fe;
            text-decoration: none;
            font-weight: 600;
            padding: 0.7rem 1.5rem;
            border-radius: 40px;
            transition: all 0.2s;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(4px);
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.15);
        }

        .nav-menu li a i {
            font-size: 1.1rem;
        }

        .nav-menu li a:hover {
            background: rgba(255,255,255,0.25);
            color: white;
            transform: translateY(-2px);
            border-color: rgba(255,255,255,0.4);
        }

        .nav-menu .active-role {
            background: #ffb347;
            background: linear-gradient(135deg, #ffb347, #ff8c1a);
            color: #1e2f3f;
            border-color: #ffcd7e;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .menu-toggle {
            display: none;
            background: rgba(255,255,255,0.15);
            border: none;
            color: white;
            font-size: 1.8rem;
            padding: 0.4rem 0.9rem;
            border-radius: 40px;
            cursor: pointer;
            transition: 0.2s;
        }

        .hero-section {
            background: linear-gradient(105deg, rgba(10, 40, 60, 0.82), rgba(20, 55, 85, 0.75)), 
                        url('https://images.pexels.com/photos/3184418/pexels-photo-3184418.jpeg?auto=compress&cs=tinysrgb&w=1600') center/cover no-repeat;
            background-size: cover;
            background-position: center 40%;
            background-attachment: fixed;
            text-align: center;
            color: white;
            padding: 6rem 2rem;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 140px);
        }

        .hero-content {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .hero-section h2 {
            font-size: clamp(2.5rem, 10vw, 5.5rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            text-shadow: 0 4px 20px rgba(0,0,0,0.4);
            margin-bottom: 1.5rem;
            line-height: 1.2;
            background: linear-gradient(135deg, #ffffff, #ffebc2);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .hero-section h2 i {
            background: none;
            -webkit-background-clip: unset;
            background-clip: unset;
            color: #ffd966;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .footer {
            text-align: center;
            padding: 1.3rem 1.5rem;
            background: #dee7f0;
            color: #1d374e;
            font-size: 1rem;
            font-weight: 500;
            border-top: 1px solid #b9cee4;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .footer-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .footer-tagline {
            font-size: 0.9rem;
            color: #2c5a7a;
            font-weight: 400;
            letter-spacing: 0.3px;
            border-top: 1px dashed #b9cee4;
            padding-top: 0.6rem;
            margin-top: 0.2rem;
            width: 100%;
        }

        .footer i {
            color: #0b5e23;
        }

        /* ========== ENHANCED ABOUT MODAL — ULTRA RESPONSIVE ========== */
        .about-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(12px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
            padding: 1rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .about-modal-content {
            background: linear-gradient(135deg, #ffffff, #fefeff);
            max-width: 620px;
            width: 100%;
            border-radius: 40px;
            padding: 2rem 2rem;
            position: relative;
            box-shadow: 0 30px 55px -20px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255,255,255,0.3);
            animation: slideUp 0.35s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            border: 1px solid rgba(255,255,255,0.5);
            max-height: 85vh;
            overflow-y: auto;
            transition: all 0.2s ease;
        }

        /* custom scrollbar for modal content */
        .about-modal-content::-webkit-scrollbar {
            width: 5px;
        }
        .about-modal-content::-webkit-scrollbar-track {
            background: #eef2f7;
            border-radius: 10px;
        }
        .about-modal-content::-webkit-scrollbar-thumb {
            background: #1e5a7a;
            border-radius: 10px;
        }

        @keyframes slideUp {
            from {
                transform: translateY(35px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .about-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #e9eff6;
            padding-bottom: 0.9rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .about-modal-header h3 {
            font-size: 1.85rem;
            font-weight: 750;
            background: linear-gradient(135deg, #0b2a41, #1f6390);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            letter-spacing: -0.3px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .close-modal {
            font-size: 2rem;
            cursor: pointer;
            color: #4a627a;
            transition: all 0.2s;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 60px;
            background: rgba(0, 0, 0, 0.04);
            backdrop-filter: blur(2px);
            font-weight: 300;
            line-height: 1;
        }

        .close-modal:hover {
            background: #eef2fa;
            color: #0f2f48;
            transform: scale(1.04);
        }

        .about-icon {
            text-align: center;
            font-size: 3.8rem;
            margin-bottom: 0.5rem;
            color: #1e5a7a;
            background: linear-gradient(145deg, #eef6fc, #ffffff);
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
            padding: 0.8rem 1.8rem;
            border-radius: 80px;
            box-shadow: 0 8px 18px rgba(0,0,0,0.05);
        }

        .about-info {
            text-align: center;
            margin: 0.8rem 0;
        }

        .about-info p {
            color: #1e2f3f;
            line-height: 1.55;
            margin: 0.8rem 0;
            font-size: 1rem;
        }

        .feature-list {
            background: #f8fbfe;
            border-radius: 28px;
            padding: 1.2rem;
            margin: 1.2rem 0;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.02), 0 6px 12px -8px rgba(0,0,0,0.08);
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 10px 0;
            color: #1e293b;
            font-size: 0.96rem;
            border-bottom: 1px solid #e6edf4;
        }
        .feature-item:last-child {
            border-bottom: none;
        }

        .feature-item i {
            width: 28px;
            margin-top: 2px;
            color: #2c6f94;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .modal-footer-note {
            margin-top: 1.3rem;
            padding-top: 0.9rem;
            border-top: 1px solid #e2eaf1;
            font-size: 0.88rem;
            color: #2c5a7a;
            text-align: center;
            background: #fefefe;
            border-radius: 28px;
        }

        /* ========== EXTREME RESPONSIVENESS FOR ABOUT MODAL ========== */
        @media (max-width: 680px) {
            .about-modal-content {
                max-width: 92%;
                padding: 1.5rem 1.4rem;
                border-radius: 32px;
            }
            .about-modal-header h3 {
                font-size: 1.55rem;
            }
            .about-icon {
                font-size: 2.8rem;
                padding: 0.5rem 1.2rem;
            }
            .feature-item {
                gap: 12px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 550px) {
            .about-modal {
                padding: 0.75rem;
                align-items: center;
            }
            .about-modal-content {
                padding: 1.3rem 1.2rem;
                border-radius: 28px;
                max-height: 88vh;
            }
            .about-modal-header h3 {
                font-size: 1.45rem;
            }
            .about-modal-header h3 i {
                font-size: 1.3rem;
            }
            .close-modal {
                width: 44px;
                height: 44px;
                font-size: 1.9rem;
            }
            .feature-list {
                padding: 0.9rem;
                margin: 0.9rem 0;
            }
            .feature-item {
                font-size: 0.85rem;
                padding: 8px 0;
                gap: 10px;
            }
            .feature-item i {
                width: 24px;
                font-size: 1rem;
            }
            .about-info p {
                font-size: 0.92rem;
            }
            .modal-footer-note {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 450px) {
            .about-modal-content {
                padding: 1.2rem 1rem;
                border-radius: 26px;
            }
            .about-modal-header {
                margin-bottom: 1rem;
            }
            .about-modal-header h3 {
                font-size: 1.3rem;
                gap: 6px;
            }
            .about-icon {
                font-size: 2.4rem;
                padding: 0.4rem 1rem;
                margin-bottom: 0.3rem;
            }
            .feature-item {
                font-size: 0.8rem;
                line-height: 1.4;
            }
            .feature-item i {
                font-size: 0.9rem;
                width: 22px;
            }
            .about-info p {
                font-size: 0.87rem;
                margin: 0.6rem 0;
            }
            .modal-footer-note {
                font-size: 0.75rem;
                padding-top: 0.7rem;
            }
        }

        @media (max-width: 380px) {
            .about-modal-content {
                padding: 1rem 0.9rem;
            }
            .about-modal-header h3 {
                font-size: 1.2rem;
            }
            .feature-item {
                flex-wrap: wrap;
                gap: 6px;
            }
            .feature-item i {
                align-self: center;
            }
            .feature-item span {
                flex: 1;
            }
        }

        /* Landscape mode & small height support */
        @media (max-height: 600px) and (orientation: landscape) {
            .about-modal {
                align-items: flex-start;
                padding-top: 1rem;
            }
            .about-modal-content {
                max-height: 88vh;
                margin: 0.5rem auto;
                overflow-y: auto;
            }
            .feature-list {
                margin: 0.7rem 0;
                padding: 0.7rem;
            }
            .about-icon {
                margin-top: 0;
                margin-bottom: 0.2rem;
                font-size: 2rem;
                padding: 0.2rem 1rem;
            }
            .about-modal-header {
                margin-bottom: 0.7rem;
            }
        }

        /* ensure modal text always readable */
        .about-info p strong, .feature-item strong {
            color: #0f3b55;
        }

        /* extra safety for long words */
        .about-modal-content, .feature-item span, .modal-footer-note {
            word-break: break-word;
        }

        /* responsive header & other elements untouched but polished */
        @media (max-width: 780px) {
            .nav-container {
                padding: 0.8rem 1.2rem;
                flex-direction: column;
                align-items: stretch;
            }
            .menu-toggle {
                display: block;
                align-self: flex-end;
                margin-top: -3rem;
            }
            .nav-wrapper {
                flex-direction: column;
                width: 100%;
                gap: 0.5rem;
            }
            .home-link {
                width: 100%;
                justify-content: center;
                order: 1;
            }
            .nav-menu {
                display: none;
                width: 100%;
                flex-direction: column;
                align-items: stretch;
                background: rgba(11, 42, 65, 0.98);
                backdrop-filter: blur(12px);
                border-radius: 28px;
                padding: 1rem;
                margin-top: 0.5rem;
                order: 2;
            }
            .nav-menu.open {
                display: flex;
            }
            .nav-menu li {
                width: 100%;
            }
            .nav-menu li a {
                width: 100%;
                padding: 0.9rem 1rem;
                justify-content: center;
            }
            .hero-section {
                padding: 4rem 1.5rem;
                min-height: calc(100vh - 120px);
                background-attachment: scroll;
            }
        }

        @media (max-width: 550px) {
            .hero-section {
                padding: 3rem 1rem;
            }
            .hero-section h2 {
                font-size: 2rem;
            }
            .footer-tagline {
                font-size: 0.8rem;
            }
        }
        @media (max-width: 360px) {
            .hero-section h2 {
                font-size: 1.8rem;
            }
        }

        .home-link, .nav-menu li a, .menu-toggle {
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body>

<!-- HEADER WITH HOME LEFT, OTHER MENU ITEMS RIGHT -->
<header class="main-header">
    <div class="nav-container">
        <div class="logo-area">
            <i class="fas fa-users"></i>
            <span>GroupFormation system</span>
        </div>
        <button class="menu-toggle" id="mobileMenuBtn" aria-label="Menu">
            <i class="fas fa-bars"></i>
        </button>
        <div class="nav-wrapper">
            <a href="#" class="home-link active-role" id="homeMenuBtn"><i class="fas fa-home"></i> Home</a>
            <ul class="nav-menu" id="navMenu">
                <li><a href="student_registration.php" id="studentMenuLink"><i class="fas fa-graduation-cap"></i> Student</a></li>
                <li><a href="teacher.php" id="teacherMenuLink"><i class="fas fa-chalkboard-user"></i> Teacher</a></li>
                <li><a href="admin_login.php" id="adminMenuLink"><i class="fas fa-user-cog"></i> Admin</a></li>
                <li><a href="#" id="aboutMenuLink"><i class="fas fa-info-circle"></i> About</a></li>
            </ul>
        </div>
    </div>
</header>

<!-- HERO SECTION - FULL WIDTH WITH BACKGROUND -->
<section class="hero-section">
    <div class="hero-content">
        <h2>Collaborate. Innovate. <i class="fas fa-handshake"></i></h2>
    </div>
</section>

<!-- FOOTER with tagline -->
<div class="footer">
    <div class="footer-content">
        <i class="fas fa-check-circle" style="color:#1f8b4c;"></i>
        <span>Student is public | Teacher & Admin require login</span>
    </div>
    <div class="footer-tagline">
        <i class="fas fa-users" style="color:#2c5a7a;"></i> Smart group formation for modern universities — connect with peers
    </div>
</div>

<!-- ========== ULTRA-RESPONSIVE ABOUT MODAL ========== -->
<div id="aboutModal" class="about-modal">
    <div class="about-modal-content">
        <div class="about-modal-header">
            <h3><i class="fas fa-info-circle"></i> About Group Formation</h3>
            <span class="close-modal" id="closeModalBtn" aria-label="Close modal">&times;</span>
        </div>
        <div class="about-icon">
            <i class="fas fa-users-viewfinder"></i>
        </div>
        <div class="about-info">
            <p><strong>Group Formation System</strong> is a comprehensive university platform designed to streamline collaboration and team building — now more adaptive than ever.</p>
            <div class="feature-list">
                <div class="feature-item">
                    <i class="fas fa-graduation-cap"></i>
                    <span><strong>Students:</strong> select teacher, register, and view teammates in real time</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-chalkboard-user"></i>
                    <span><strong>Teachers:</strong> set registration windows, create smart groups & generate PDF reports</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-user-cog"></i>
                    <span><strong>Admin:</strong> full system oversight, user analytics, and group audit logs</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-chart-line"></i>
                    <span><strong>Smart Matching:</strong> AI-powered group recommendations based on skills and interests</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-comments"></i>
                    <span><strong>Real-time Collaboration:</strong> chat, file sharing, and project tracking integrated</span>
                </div>
            </div>
            <p>📌 <strong>Version 2.0 (Ultra responsive)</strong> | Developed by Group 5</p>
            <p><i class="fas fa-envelope"></i> norbertmucyo@gmail.com | <i class="fas fa-globe"></i> www.groupformation.infinityfree.me</p>
        </div>
        <div class="modal-footer-note">
            <i class="fas fa-mobile-alt"></i> Fully responsive design — fluid on any device &nbsp;|&nbsp; <i class="fas fa-hand-peace"></i> Select your role above
        </div>
    </div>
</div>

<script>
    (function() {
        // Mobile menu toggle
        const toggleBtn = document.getElementById('mobileMenuBtn');
        const navMenu = document.getElementById('navMenu');
        if (toggleBtn && navMenu) {
            toggleBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                navMenu.classList.toggle('open');
            });
            document.addEventListener('click', function(event) {
                if (!navMenu.contains(event.target) && !toggleBtn.contains(event.target) && navMenu.classList.contains('open')) {
                    navMenu.classList.remove('open');
                }
            });
        }
        
        // Home button - scroll to top
        const homeBtn = document.getElementById('homeMenuBtn');
        if (homeBtn) {
            homeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
                document.querySelectorAll('.home-link, .nav-menu li a').forEach(link => {
                    link.classList.remove('active-role');
                });
                homeBtn.classList.add('active-role');
                if (navMenu && navMenu.classList.contains('open')) navMenu.classList.remove('open');
            });
        }
        
        // ===== ABOUT MODAL (fully enhanced) =====
        const aboutBtn = document.getElementById('aboutMenuLink');
        const aboutModal = document.getElementById('aboutModal');
        const closeModalBtn = document.getElementById('closeModalBtn');
        
        function openAboutModal() {
            if (aboutModal) {
                aboutModal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                // ensure any open mobile menu closes
                if (navMenu && navMenu.classList.contains('open')) navMenu.classList.remove('open');
            }
        }
        
        function closeAboutModal() {
            if (aboutModal) {
                aboutModal.style.display = 'none';
                document.body.style.overflow = '';
            }
        }
        
        if (aboutBtn) {
            aboutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                openAboutModal();
            });
        }
        
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', closeAboutModal);
        }
        
        if (aboutModal) {
            aboutModal.addEventListener('click', function(e) {
                if (e.target === aboutModal) {
                    closeAboutModal();
                }
            });
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && aboutModal && aboutModal.style.display === 'flex') {
                closeAboutModal();
            }
        });
        
        // Role menu active highlight
        const studentMenu = document.getElementById('studentMenuLink');
        const teacherMenu = document.getElementById('teacherMenuLink');
        const adminMenu = document.getElementById('adminMenuLink');
        
        function setActiveMenuItem(activeElement) {
            document.querySelectorAll('.home-link, .nav-menu li a').forEach(link => {
                link.classList.remove('active-role');
            });
            if (activeElement) activeElement.classList.add('active-role');
        }
        
        if (studentMenu) studentMenu.addEventListener('click', () => setActiveMenuItem(studentMenu));
        if (teacherMenu) teacherMenu.addEventListener('click', () => setActiveMenuItem(teacherMenu));
        if (adminMenu) adminMenu.addEventListener('click', () => setActiveMenuItem(adminMenu));
        
        function updateScrollActive() {
            if (window.scrollY < 100 && homeBtn) setActiveMenuItem(homeBtn);
        }
        window.addEventListener('scroll', updateScrollActive);
        updateScrollActive();
    })();
</script>

</body>
</html>
```
