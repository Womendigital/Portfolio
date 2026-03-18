<?php
session_start();
$currentLang = $_GET['lang'] ?? 'fr';
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentLang === 'fr' ? 'Suivi d\'Alerte - Alerte Sénégal' : 'Alert Tracking - Alert Senegal'; ?></title>
    <meta name="description" content="<?php echo $currentLang === 'fr' ? 'Suivez l\'état de traitement de votre signalement en toute sécurité et anonymat' : 'Track your report status securely and anonymously'; ?>">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg: #0f1724;
            --card: #0b1220;
            --accent: #441c8a;
            --accent-light: #6d28d9;
            --muted: #94a3b8;
            --text: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --glass: rgba(255,255,255,0.04);
            --transition: all 0.3s ease;
        }
         .nav-links a:hover {
      color: white;
      background: rgba(124,58,237,0.1);
      transform: translateY(-2px);
    }
    .nav-links a::after {
      content: '';
      position: absolute;
      bottom: -2px;
      left: 50%;
      width: 0;
      height: 2px;
      background: linear-gradient(90deg,var(--accent),#06b6d4);
      transition: var(--transition);
      transform: translateX(-50%);
    }
    .nav-links a:hover::after {
      width: 80%;
    }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        html, body {
            height: 100%;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: radial-gradient(1200px 600px at 10% 10%, rgba(124,58,237,0.08), transparent),
                       linear-gradient(180deg, #0d1422 0%, #131b2e 100%);
            color: var(--text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        
        /* Header */
        .site-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(15, 23, 36, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 1rem 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo {
            width: 120px;
            height: 60px;
            transition: var(--transition);
        }
        
        .logo:hover {
            transform: scale(1.05);
        }
        
        .brand-text {
            display: flex;
            flex-direction: column;
        }
        
        .brand-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
        }
        
        .brand-subtitle {
            font-size: 0.75rem;
            color: var(--muted);
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-links a {
            color: var(--muted);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: var(--transition);
            font-size: 0.9rem;
        }
        
        .nav-links a:hover {
            color: white;
            background: rgba(68, 28, 138, 0.1);
        }
        
        .nav-links a.active {
            background: rgba(68, 28, 138, 0.2);
            color: white;
        }
        
        /* Language Selector */
        .language-selector {
            display: flex;
            gap: 0.5rem;
            margin-left: 1rem;
        }
        
        .lang-btn {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--muted);
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        
        .lang-btn.active {
            background: rgba(68, 28, 138, 0.2);
            color: white;
            border-color: var(--accent);
        }
        
        .lang-btn:hover {
            background: rgba(68, 28, 138, 0.1);
            color: white;
        }
        
        .flag {
            width: 16px;
            height: 12px;
            border-radius: 2px;
            background-size: cover;
        }
        
        .flag-fr {
            background: linear-gradient(90deg, #002395 33%, white 33%, white 66%, #ED2939 66%);
        }
        
        .flag-en {
            background: linear-gradient(0deg, 
                #B22234 0%,
                #B22234 7.7%,
                white 7.7%,
                white 15.4%,
                #B22234 15.4%,
                #B22234 23.1%,
                white 23.1%,
                white 30.8%,
                #B22234 30.8%,
                #B22234 38.5%,
                white 38.5%,
                white 46.2%,
                #B22234 46.2%,
                #B22234 53.9%,
                white 53.9%,
                white 61.6%,
                #B22234 61.6%,
                #B22234 69.3%,
                white 69.3%,
                white 77%,
                #B22234 77%,
                #B22234 84.7%,
                white 84.7%,
                white 92.4%,
                #B22234 92.4%
            ), #3C3B6E;
            background-blend-mode: normal;
            background-position: 0 0, 0 0;
            background-size: 40% 100%, 100% 100%;
            background-repeat: no-repeat;
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--muted);
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Main Content */
        .main-content {
            padding: 140px 0 4rem;
            min-height: calc(100vh - 200px);
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .page-title {
            font-size: 3rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1rem;
            line-height: 1.1;
        }
        
        .page-description {
            font-size: 1.1rem;
            color: var(--muted);
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Tracking Section */
        .tracking-section {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .tracking-card {
            background: var(--glass);
            padding: 3rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .form-group {
            margin-bottom: 2rem;
        }
        
        .form-label {
            display: block;
            color: white;
            font-weight: 500;
            margin-bottom: 0.8rem;
            font-size: 1rem;
        }
        
        .form-input {
            width: 100%;
            padding: 1rem 1.5rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--accent-light);
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 0 3px rgba(109, 40, 217, 0.1);
        }
        
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        
        .submit-btn {
            width: 100%;
            padding: 1.25rem;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 1rem;
            position: relative;
            overflow: hidden;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(68, 28, 138, 0.4);
        }
        
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .submit-btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .submit-btn:hover::after {
            left: 100%;
        }
        
        /* Alert Info */
        .alert-info {
            background: var(--glass);
            padding: 3rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 2rem;
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .status-badge {
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .status-nouveau { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .status-en_cours { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .status-traite { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .status-cloture { background: rgba(107, 114, 128, 0.2); color: #9ca3af; }
        .status-rejete { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        
        .tracking-code-display {
            font-family: monospace;
            font-size: 1.2rem;
            color: var(--accent-light);
            background: rgba(68, 28, 138, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid rgba(68, 28, 138, 0.2);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .info-card {
            background: rgba(255, 255, 255, 0.03);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.03);
        }
        
        .info-label {
            font-size: 0.9rem;
            color: var(--muted);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .info-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: white;
        }
        
        .gravite-stars {
            display: flex;
            gap: 4px;
            align-items: center;
        }
        
        .star {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .star.active {
            background: #f59e0b;
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.5);
        }
        
        .blockchain-proof {
            background: rgba(68, 28, 138, 0.1);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(68, 28, 138, 0.2);
            margin-top: 2rem;
        }
        
        .hash-display {
            font-family: monospace;
            font-size: 0.9rem;
            color: var(--accent-light);
            word-break: break-all;
            margin: 0.5rem 0;
        }
        
        /* Messages */
        .message {
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .message-error {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }
        
        .message-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
        }
        
        .security-notice {
            background: rgba(68, 28, 138, 0.1);
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid rgba(68, 28, 138, 0.2);
            margin-top: 3rem;
            text-align: center;
        }
        
        /* Footer */
        .site-footer {
            background: rgba(11, 18, 32, 0.8);
            padding: 3rem 0 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            margin-top: 4rem;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 3rem;
            align-items: start;
        }
        
        .footer-brand {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .footer-logo {
            width: 180px;
            height: auto;
        }
        
        .footer-links {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }
        
        .footer-column h4 {
            color: white;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .footer-column a {
            color: var(--muted);
            text-decoration: none;
            display: block;
            margin-bottom: 0.5rem;
            transition: var(--transition);
            font-size: 0.9rem;
        }
        
        .footer-column a:hover {
            color: white;
        }
        
        .footer-bottom {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            text-align: center;
            color: var(--muted);
            font-size: 0.8rem;
        }
        
        /* Loading Spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .footer-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--card);
                flex-direction: column;
                padding: 1rem;
                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
                gap: 1rem;
            }
            
            .nav-links.active {
                display: flex;
            }
            
            .language-selector {
                margin-left: 0;
                justify-content: center;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .page-description {
                font-size: 1rem;
            }
            
            .tracking-card, .alert-info {
                padding: 1.5rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-links {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .footer-logo {
                width: 120px;
            }
        }
        
        /* Animation for floating effect */
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <nav class="nav">
                <div class="brand">
                    <div class="logo">
                        <img src="image/logoofficielle.png" alt="Alerte Sénégal" style="height: 100%; width: auto;">
                    </div>
                    <div class="brand-text">
                        <div class="brand-name">Alerte Sénégal</div>
                        <div class="brand-subtitle">Signalement Éthique Sécurisé</div>
                    </div>
                </div>
                
                <button class="menu-toggle" id="menuToggle">☰</button>
                
                <div class="nav-links" id="navLinks">
                    <a href="index.html">Accueil</a>
                    <a href="soumettre.php">Soumettre une alerte</a>
                  
                    <a href="apropos.html">À propos</a>
                    <a href="confidentialite.html">Confidentialité</a>
                    <div class="language-selector">
                        <button type="button" class="lang-btn <?php echo $currentLang === 'fr' ? 'active' : ''; ?>" data-lang="fr">
                            <div class="flag flag-fr"></div>
                            FR
                        </button>
                        <button type="button" class="lang-btn <?php echo $currentLang === 'en' ? 'active' : ''; ?>" data-lang="en">
                            <div class="flag flag-en"></div>
                            EN
                        </button>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">
                    <?php echo $currentLang === 'fr' ? 'Suivi d\'Alerte' : 'Alert Tracking'; ?>
                </h1>
                <p class="page-description">
                    <?php echo $currentLang === 'fr' 
                        ? 'Consultez l\'état de traitement de votre signalement en toute sécurité et anonymat' 
                        : 'Check the status of your report securely and anonymously'; ?>
                </p>
            </div>

            <div class="tracking-section">
                <!-- Search Form -->
                <div class="tracking-card">
                    <h2 style="color: white; margin-bottom: 2rem; font-size: 1.5rem;">
                        <?php echo $currentLang === 'fr' ? 'Vérifier une alerte' : 'Check an Alert'; ?>
                    </h2>
                    
                    <form id="trackingForm">
                        <div class="form-group">
                            <label for="trackingCode" class="form-label">
                                <?php echo $currentLang === 'fr' ? 'Code de suivi' : 'Tracking Code'; ?> *
                            </label>
                            <input type="text" id="trackingCode" name="tracking_code" 
                                   class="form-input" 
                                   placeholder="<?php echo $currentLang === 'fr' ? 'Ex: AS-ABC12345' : 'Ex: AS-ABC12345'; ?>"
                                   required
                                   pattern="[A-Z0-9\-]+"
                                   style="text-transform: uppercase;">
                            <small style="color: var(--muted); margin-top: 0.5rem; display: block;">
                                <?php echo $currentLang === 'fr' 
                                    ? 'Saisissez le code de suivi unique fourni lors de votre soumission'
                                    : 'Enter the unique tracking code provided when you submitted your alert'; ?>
                            </small>
                        </div>
                        
                        <button type="submit" class="submit-btn" id="submitBtn">
                            <?php echo $currentLang === 'fr' ? 'Vérifier le statut' : 'Check Status'; ?>
                        </button>
                    </form>
                </div>

                <!-- Results Area -->
                <div id="resultsArea"></div>

                <!-- Security Notice -->
                <div class="security-notice">
                    <h3 style="color: white; margin-bottom: 1rem;">
                        <?php echo $currentLang === 'fr' ? 'Sécurité garantie' : 'Security Guaranteed'; ?>
                    </h3>
                    <p style="color: var(--muted);">
                        <?php echo $currentLang === 'fr' 
                            ? 'Votre consultation est entièrement anonyme et sécurisée. Tous les accès sont chiffrés et journalisés pour assurer la traçabilité.'
                            : 'Your consultation is completely anonymous and secure. All accesses are encrypted and logged to ensure traceability.'; ?>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <img src="image/logoofficielle.png" alt="Alerte Sénégal" class="footer-logo">
                    <div>
                        <div style="color: white; font-weight: 600; margin-bottom: 0.5rem;">Alerte Sénégal</div>
                        <div style="color: var(--muted); font-size: 0.875rem;">
                            <?php echo $currentLang === 'fr' ? 'Plateforme officielle de signalement éthique' : 'Official ethical reporting platform'; ?>
                        </div>
                    </div>
                </div>
                
                <div class="footer-links">
                    <div class="footer-column">
                        <h4><?php echo $currentLang === 'fr' ? 'Navigation' : 'Navigation'; ?></h4>
                        <a href="index.php"><?php echo $currentLang === 'fr' ? 'Accueil' : 'Home'; ?></a>
                        <a href="soumettre.php"><?php echo $currentLang === 'fr' ? 'Soumettre une alerte' : 'Submit alert'; ?></a>
                        <a href="suivi.php"><?php echo $currentLang === 'fr' ? 'Suivre une alerte' : 'Track alert'; ?></a>
                    </div>
                    
                    <div class="footer-column">
                        <h4><?php echo $currentLang === 'fr' ? 'À propos' : 'About'; ?></h4>
                        <a href="apropos.php"><?php echo $currentLang === 'fr' ? 'Notre mission' : 'Our mission'; ?></a>
                        <a href="confidentialite.php"><?php echo $currentLang === 'fr' ? 'Confidentialité' : 'Privacy'; ?></a>
                        <a href="mentions-legales.php"><?php echo $currentLang === 'fr' ? 'Mentions légales' : 'Legal notices'; ?></a>
                    </div>
                    
                    <div class="footer-column">
                        <h4><?php echo $currentLang === 'fr' ? 'Contact' : 'Contact'; ?></h4>
                        <a href="contact.php"><?php echo $currentLang === 'fr' ? 'Nous contacter' : 'Contact us'; ?></a>
                        <a href="faq.php">FAQ</a>
                        <a href="support.php"><?php echo $currentLang === 'fr' ? 'Support' : 'Support'; ?></a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>© 2025 Alerte Sénégal - <?php echo $currentLang === 'fr' 
                    ? 'Conforme à la loi sénégalaise n° 2008-12 sur la protection des données personnelles' 
                    : 'Compliant with Senegalese law n° 2008-12 on personal data protection'; ?></p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const navLinks = document.getElementById('navLinks');
        
        if (menuToggle && navLinks) {
            menuToggle.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });
        }

        // Language switching
        const langButtons = document.querySelectorAll('.lang-btn');
        langButtons.forEach(button => {
            button.addEventListener('click', () => {
                const lang = button.getAttribute('data-lang');
                window.location.href = `suivi.php?lang=${lang}`;
            });
        });

        // Tracking form submission
        const trackingForm = document.getElementById('trackingForm');
        const resultsArea = document.getElementById('resultsArea');
        const submitBtn = document.getElementById('submitBtn');
        
        if (trackingForm) {
            trackingForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const trackingCode = formData.get('tracking_code').toUpperCase().trim();
                
                if (!trackingCode) {
                    showError('<?php echo $currentLang === "fr" ? "Veuillez saisir un code de suivi." : "Please enter a tracking code."; ?>');
                    return;
                }
                
                // Disable submit button and show loading
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<?php echo $currentLang === "fr" ? "<span class=\"spinner\"></span> Recherche en cours..." : "<span class=\"spinner\"></span> Searching..."; ?>';
                
                try {
                    const response = await fetch('traitement-suivi.php', {
                        method: 'POST',
                        body: new URLSearchParams({ tracking_code: trackingCode, lang: '<?php echo $currentLang; ?>' })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        displayAlertInfo(result.data);
                    } else {
                        showError(result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showError('<?php echo $currentLang === "fr" ? "Erreur de connexion. Veuillez réessayer." : "Connection error. Please try again."; ?>');
                } finally {
                    // Re-enable submit button
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<?php echo $currentLang === "fr" ? "Vérifier le statut" : "Check Status"; ?>';
                }
            });
        }

        // Display alert information
        function displayAlertInfo(data) {
            const currentLang = '<?php echo $currentLang; ?>';
            
            // Format date
            const dateCreation = new Date(data.date_creation);
            const formattedDate = dateCreation.toLocaleDateString(currentLang === 'fr' ? 'fr-FR' : 'en-US', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // Status text
            const statusText = {
                'nouveau': currentLang === 'fr' ? 'Nouveau' : 'New',
                'en_cours': currentLang === 'fr' ? 'En cours' : 'In Progress',
                'traite': currentLang === 'fr' ? 'Traité' : 'Processed',
                'cloture': currentLang === 'fr' ? 'Clôturé' : 'Closed',
                'rejete': currentLang === 'fr' ? 'Rejeté' : 'Rejected'
            };
            
            // Status message
            const statusMessages = {
                'nouveau': currentLang === 'fr' 
                    ? 'Votre signalement est en attente de traitement par notre équipe.' 
                    : 'Your report is awaiting processing by our team.',
                'en_cours': currentLang === 'fr' 
                    ? 'Votre signalement est actuellement en cours d\'examen.' 
                    : 'Your report is currently under review.',
                'traite': currentLang === 'fr' 
                    ? 'Votre signalement a été traité avec succès.' 
                    : 'Your report has been successfully processed.',
                'cloture': currentLang === 'fr' 
                    ? 'Ce signalement a été clôturé.' 
                    : 'This report has been closed.',
                'rejete': currentLang === 'fr' 
                    ? 'Ce signalement a été rejeté après analyse.' 
                    : 'This report has been rejected after analysis.'
            };
            
            // Generate stars for severity
            let starsHtml = '';
            for (let i = 1; i <= 5; i++) {
                starsHtml += `<div class="star ${i <= data.niveau_gravite ? 'active' : ''}"></div>`;
            }
            
            const html = `
                <div class="alert-info">
                    <div class="status-header">
                        <div class="status-badge status-${data.statut}">
                            ${statusText[data.statut] || data.statut}
                        </div>
                        <div class="tracking-code-display">
                            ${data.code_tracking}
                        </div>
                    </div>
                    
                    <div class="message message-success">
                        <div style="font-size: 1.5rem;">✅</div>
                        <div>
                            <strong>${currentLang === 'fr' ? 'Signalement trouvé' : 'Report Found'}</strong>
                            <p style="margin-top: 0.5rem; color: rgba(255, 255, 255, 0.7);">
                                ${statusMessages[data.statut] || ''}
                            </p>
                        </div>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-card">
                            <div class="info-label">${currentLang === 'fr' ? 'Date de création' : 'Creation Date'}</div>
                            <div class="info-value">${formattedDate}</div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-label">${currentLang === 'fr' ? 'Type d\'alerte' : 'Alert Type'}</div>
                            <div class="info-value">${data.type_alerte_nom || (currentLang === 'fr' ? 'Non spécifié' : 'Not specified')}</div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-label">${currentLang === 'fr' ? 'Niveau de gravité' : 'Severity Level'}</div>
                            <div class="info-value">
                                <div class="gravite-stars">
                                    ${starsHtml}
                                    <span style="margin-left: 8px; font-size: 0.9rem;">(${data.niveau_gravite}/5)</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <div class="info-label">${currentLang === 'fr' ? 'Canal de soumission' : 'Submission Channel'}</div>
                            <div class="info-value">${data.canal_soumission.charAt(0).toUpperCase() + data.canal_soumission.slice(1)}</div>
                        </div>
                    </div>
                    
                    ${data.blockchain_hash ? `
                        <div class="blockchain-proof">
                            <div class="info-label">${currentLang === 'fr' ? 'Preuve blockchain' : 'Blockchain Proof'}</div>
                            <div class="hash-display">${data.blockchain_hash.substring(0, 32)}...</div>
                            <small style="color: var(--muted);">
                                ${currentLang === 'fr' 
                                    ? 'Horodatage certifié sur la blockchain pour garantir l\'intégrité des données' 
                                    : 'Timestamp certified on blockchain to ensure data integrity'}
                            </small>
                        </div>
                    ` : ''}
                    
                    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.05);">
                        <small style="color: var(--muted);">
                            ${currentLang === 'fr' 
                                ? `Dernier accès: ${data.dernier_acces ? new Date(data.dernier_acces).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'Jamais'} | Accès totaux: ${data.nombre_acces || 0}`
                                : `Last access: ${data.dernier_acces ? new Date(data.dernier_acces).toLocaleDateString('en-US', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'Never'} | Total accesses: ${data.nombre_acces || 0}`}
                        </small>
                    </div>
                </div>
            `;
            
            resultsArea.innerHTML = html;
            resultsArea.scrollIntoView({ behavior: 'smooth' });
        }

        // Show error message
        function showError(message) {
            const html = `
                <div class="message message-error">
                    <div style="font-size: 1.5rem;">⚠️</div>
                    <div>${message}</div>
                </div>
            `;
            
            resultsArea.innerHTML = html;
        }

        // Auto-uppercase tracking code
        const trackingCodeInput = document.getElementById('trackingCode');
        if (trackingCodeInput) {
            trackingCodeInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        }
    </script>
</body>
</html>