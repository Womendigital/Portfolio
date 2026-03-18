<?php
// soumettre.php - Page de soumission d'alerte
session_start();

// Déterminer la langue active
$currentLang = $_GET['lang'] ?? 'fr';
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title><?php echo $currentLang === 'fr' ? 'Soumettre une Alerte - Alerte Sénégal' : 'Submit Alert - Alert Senegal'; ?></title>
    <meta name="description" content="<?php echo $currentLang === 'fr' ? 'Soumettez votre alerte de manière sécurisée et anonyme' : 'Submit your alert securely and anonymously'; ?>" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f1724;
            --card: #1a2332;
            --accent: #441c8a;
            --accent-light: #6d28d9;
            --muted: #94a3b8;
            --text: #e2e8f0;
            --transition: all 0.3s ease;
            --success: #059669;
            --danger: #dc2626;
            --warning: #d97706;
            --glass: rgba(255,255,255,0.04);
            --gradient: linear-gradient(135deg, #59656d 0%, #2c5aa0 100%);
            --form-text: #1a202c;
            --form-bg: #ffffff;
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
            max-width: 1400px;
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
        
        /* Main Layout */
        .alert-container {
            padding-top: 140px;
            padding-bottom: 4rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: start;
            min-height: calc(100vh - 200px);
        }
        
        /* Left Column - Messages */
        .left-column {
            padding-right: 2rem;
        }
        
        .alert-header {
            margin-bottom: 3rem;
        }
        
        .alert-title {
            font-size: 3rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1rem;
            line-height: 1.1;
        }
        
        .alert-subtitle {
            font-size: 1.1rem;
            color: var(--muted);
            margin-bottom: 2rem;
        }
        
        .info-box {
            background: var(--glass);
            padding: 2rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }
        
        .info-box:hover {
            border-color: rgba(68, 28, 138, 0.2);
            transform: translateY(-2px);
        }
        
        .info-box h3 {
            color: white;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-box p {
            color: var(--muted);
            line-height: 1.5;
            font-size: 0.95rem;
        }
        
        /* Security Banner */
        .security-banner {
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.1), rgba(220, 38, 38, 0.05));
            border: 1px solid rgba(220, 38, 38, 0.2);
        }
        
        /* Right Column - Form */
        .right-column {
            position: relative;
        }
        
        .form-mirror {
            background: var(--gradient);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-mirror::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
            z-index: 0;
        }
        
        .alert-form {
            position: relative;
            z-index: 1;
        }
        
        .form-section {
            margin-bottom: 2.5rem;
        }
        
        .form-section-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            color: white;
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .form-required {
            color: var(--danger);
        }
        
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 1rem;
            background: var(--form-bg);
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: var(--form-text) !important;
            font-size: 1rem;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
        }
        
        .form-input::placeholder,
        .form-textarea::placeholder {
            color: #718096 !important;
            opacity: 1;
        }
        
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--accent-light);
            background: var(--form-bg);
            box-shadow: 0 0 0 3px rgba(109, 40, 217, 0.1);
            color: var(--form-text) !important;
        }
        
        .form-select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23441c8a' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 16px;
            appearance: none;
            padding-right: 3rem;
            color: var(--form-text) !important;
        }
        
        /* Style pour les options du select */
        .form-select option {
            color: var(--form-text) !important;
            background: var(--form-bg);
            padding: 10px;
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .file-upload {
            border: 2px dashed rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            background: rgba(59, 130, 246, 0.05);
        }
        
        .file-upload:hover {
            border-color: var(--accent-light);
            background: rgba(68, 28, 138, 0.1);
        }
        
        .file-input {
            display: none;
        }
        
        .file-list {
            margin-top: 1rem;
        }
        
        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            margin-bottom: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.85rem;
            color: white;
        }
        
        .file-remove {
            color: var(--danger);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            transition: var(--transition);
            font-size: 1rem;
        }
        
        .file-remove:hover {
            background: rgba(220, 38, 38, 0.1);
        }
        
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin: 2rem 0;
            padding: 1.5rem;
            background: rgba(5, 150, 105, 0.05);
            border-radius: 8px;
            border: 1px solid rgba(5, 150, 105, 0.1);
        }
        
        .form-checkbox {
            width: 20px;
            height: 20px;
            margin-top: 0.25rem;
            flex-shrink: 0;
            accent-color: var(--accent);
        }
        
        .checkbox-label {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .checkbox-label a {
            color: var(--accent-light);
            text-decoration: none;
            font-weight: 600;
        }
        
        .checkbox-label a:hover {
            text-decoration: underline;
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
        
        /* Success Message */
        .success-message {
            background: linear-gradient(135deg, rgba(5, 150, 105, 0.1), rgba(5, 150, 105, 0.05));
            padding: 3rem;
            border-radius: 20px;
            border: 1px solid rgba(5, 150, 105, 0.2);
            text-align: center;
            margin-top: 2rem;
            animation: fadeIn 0.6s ease-out;
            grid-column: 1 / -1;
            max-width: 800px;
            margin: 0 auto;
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
        
        .tracking-code {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--success);
            margin: 2rem 0;
            font-family: monospace;
            letter-spacing: 2px;
            background: rgba(0, 0, 0, 0.2);
            padding: 1rem;
            border-radius: 10px;
            display: inline-block;
            word-break: break-all;
        }
        
        /* Error Message */
        .error-message {
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.1), rgba(220, 38, 38, 0.05));
            border: 1px solid rgba(220, 38, 38, 0.2);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            color: #fca5a5;
            display: none;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
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
        
        /* Responsive */
        @media (max-width: 1024px) {
            .alert-container {
                grid-template-columns: 1fr;
                gap: 3rem;
            }
            
            .left-column {
                padding-right: 0;
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
            
            .alert-title {
                font-size: 2rem;
            }
            
            .alert-subtitle {
                font-size: 1rem;
            }
            
            .form-mirror {
                padding: 1.5rem;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .footer-links {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .footer-logo {
                width: 120px;
            }
            
            .tracking-code {
                font-size: 1.5rem;
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
        
        /* Loading spinner */
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
    </style>
</head>
<body>
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
                    <a href="suivi.php">Suivre une alerte</a>
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

    <main class="container">
        <div class="alert-container" id="mainContent">
            <!-- Le contenu sera chargé dynamiquement -->
        </div>
    </main>

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
                window.location.href = `soumettre.php?lang=${lang}`;
            });
        });

        // Fonction pour charger le contenu de la page
        function loadPageContent() {
            const currentLang = '<?php echo $currentLang; ?>';
            const mainContent = document.getElementById('mainContent');
            
            const content = `
                <!-- Left Column - Messages -->
                <div class="left-column">
                    <div class="alert-header">
                        <h1 class="alert-title">
                            ${currentLang === 'fr' ? 'Soumettre une Alerte' : 'Submit an Alert'}
                        </h1>
                        <p class="alert-subtitle">
                            ${currentLang === 'fr' 
                                ? 'Signalez de manière sécurisée et anonyme tout cas de corruption, harcèlement ou manquement éthique' 
                                : 'Securely and anonymously report any case of corruption, harassment or ethical misconduct'}
                        </p>
                    </div>

                    <div id="errorMessage" class="error-message"></div>

                    <div class="info-box security-banner">
                        <h3>🛡️ ${currentLang === 'fr' ? 'Sécurité maximale garantie' : 'Maximum security guaranteed'}</h3>
                        <p>
                            ${currentLang === 'fr' 
                                ? 'Votre signalement est chiffré de bout en bout et protégé par blockchain. Aucune information personnelle n\'est collectée.' 
                                : 'Your report is end-to-end encrypted and protected by blockchain. No personal information is collected.'}
                        </p>
                    </div>

                    <div class="info-box">
                        <h3>💡 ${currentLang === 'fr' ? 'Comment rédiger un bon signalement' : 'How to write a good report'}</h3>
                        <p>
                            ${currentLang === 'fr' 
                                ? 'Pour que votre alerte soit traitée efficacement, veuillez fournir des informations précises : dates, lieux, personnes impliquées, montants concernés et toute preuve disponible.' 
                                : 'For your alert to be processed effectively, please provide accurate information: dates, locations, people involved, amounts concerned and any available evidence.'}
                        </p>
                    </div>

                    <div class="info-box">
                        <h3>🔒 ${currentLang === 'fr' ? 'Protection des données' : 'Data Protection'}</h3>
                        <p>
                            ${currentLang === 'fr' 
                                ? 'Votre signalement est entièrement anonyme. Aucune information personnelle n\'est collectée et toutes les données sont chiffrées de bout en bout.' 
                                : 'Your report is completely anonymous. No personal information is collected and all data is end-to-end encrypted.'}
                        </p>
                    </div>
                </div>

                <!-- Right Column - Form -->
                <div class="right-column">
                    <div class="form-mirror">
                        <form method="POST" enctype="multipart/form-data" class="alert-form" id="alertForm" action="traitement-alerte.php">
                            <input type="hidden" name="lang" value="${currentLang}">
                            
                            <!-- Main Information -->
                            <div class="form-section">
                                <h3 class="form-section-title">
                                    ${currentLang === 'fr' ? 'Informations principales' : 'Main Information'}
                                </h3>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        ${currentLang === 'fr' ? 'Type d\'alerte' : 'Alert Type'} <span class="form-required">*</span>
                                    </label>
                                    <select class="form-select" name="alertType" required style="color: #1a202c !important;">
                                        <option value="" style="color: #718096 !important;">${currentLang === 'fr' ? 'Sélectionnez un type' : 'Select a type'}</option>
                                        <option value="corruption" style="color: #1a202c !important;">${currentLang === 'fr' ? 'Corruption' : 'Corruption'}</option>
                                        <option value="harcelement" style="color: #1a202c !important;">${currentLang === 'fr' ? 'Harcèlement' : 'Harassment'}</option>
                                        <option value="discrimination" style="color: #1a202c !important;">${currentLang === 'fr' ? 'Discrimination' : 'Discrimination'}</option>
                                        <option value="fraude" style="color: #1a202c !important;">${currentLang === 'fr' ? 'Fraude' : 'Fraud'}</option>
                                        <option value="detournement" style="color: #1a202c !important;">${currentLang === 'fr' ? 'Détournement de fonds' : 'Embezzlement'}</option>
                                        <option value="conflit" style="color: #1a202c !important;">${currentLang === 'fr' ? 'Conflit d\'intérêts' : 'Conflict of interest'}</option>
                                        <option value="autre" style="color: #1a202c !important;">${currentLang === 'fr' ? 'Autre manquement éthique' : 'Other ethical misconduct'}</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        ${currentLang === 'fr' ? 'Institution/organisme concerné' : 'Concerned institution/organization'} <span class="form-required">*</span>
                                    </label>
                                    <input type="text" class="form-input" name="institution" required 
                                           placeholder="${currentLang === 'fr' 
                                             ? 'Ex: Ministère des Finances, Entreprise X...' 
                                             : 'Ex: Ministry of Finance, Company X...'}"
                                           style="color: #1a202c !important;">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        ${currentLang === 'fr' ? 'Localisation' : 'Location'} <span class="form-required">*</span>
                                    </label>
                                    <select class="form-select" name="location" required style="color: #1a202c !important;">
                                        <option value="" style="color: #718096 !important;">${currentLang === 'fr' ? 'Sélectionnez une région' : 'Select a region'}</option>
                                        <option value="dakar" style="color: #1a202c !important;">Dakar</option>
                                        <option value="thies" style="color: #1a202c !important;">Thiès</option>
                                        <option value="diourbel" style="color: #1a202c !important;">Diourbel</option>
                                        <option value="fatick" style="color: #1a202c !important;">Fatick</option>
                                        <option value="kaffrine" style="color: #1a202c !important;">Kaffrine</option>
                                        <option value="kaolack" style="color: #1a202c !important;">Kaolack</option>
                                        <option value="kedougou" style="color: #1a202c !important;">Kédougou</option>
                                        <option value="kolda" style="color: #1a202c !important;">Kolda</option>
                                        <option value="louga" style="color: #1a202c !important;">Louga</option>
                                        <option value="matam" style="color: #1a202c !important;">Matam</option>
                                        <option value="saint-louis" style="color: #1a202c !important;">Saint-Louis</option>
                                        <option value="sedhiou" style="color: #1a202c !important;">Sédhiou</option>
                                        <option value="tambacounda" style="color: #1a202c !important;">Tambacounda</option>
                                        <option value="ziguinchor" style="color: #1a202c !important;">Ziguinchor</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Alert Details -->
                            <div class="form-section">
                                <h3 class="form-section-title">
                                    ${currentLang === 'fr' ? 'Détails de l\'alerte' : 'Alert Details'}
                                </h3>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        ${currentLang === 'fr' ? 'Description détaillée' : 'Detailed Description'} <span class="form-required">*</span>
                                    </label>
                                    <textarea class="form-textarea" name="description" required 
                                              placeholder="${currentLang === 'fr' 
                                                ? 'Décrivez la situation de manière précise, avec dates, lieux, personnes impliquées, montants concernés...' 
                                                : 'Describe the situation precisely, with dates, locations, people involved, amounts concerned...'}"
                                              style="color: #1a202c !important;"></textarea>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        ${currentLang === 'fr' ? 'Preuves (optionnel)' : 'Evidence (optional)'}
                                    </label>
                                    <div class="file-upload" id="fileUpload">
                                        <div style="font-size: 2rem; margin-bottom: 0.5rem; color: white;">📎</div>
                                        <div style="color: white;">
                                            ${currentLang === 'fr' ? 'Glissez-déposez vos fichiers ici' : 'Drag and drop your files here'}<br>
                                            <span style="font-size: 0.9rem; color: rgba(255, 255, 255, 0.6);">
                                                ${currentLang === 'fr' 
                                                    ? 'ou cliquez pour parcourir' 
                                                    : 'or click to browse'}
                                            </span>
                                        </div>
                                        <div style="font-size: 0.8rem; color: rgba(255, 255, 255, 0.5); margin-top: 1rem;">
                                            ${currentLang === 'fr' 
                                                ? 'Formats acceptés : PDF, JPG, PNG, DOC, MP4 (max 10MB)' 
                                                : 'Accepted formats: PDF, JPG, PNG, DOC, MP4 (max 10MB)'}
                                        </div>
                                    </div>
                                    <input type="file" class="file-input" id="fileInput" name="evidence[]" multiple 
                                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.mp4,.mov,.avi">
                                    <div class="file-list" id="fileList"></div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        ${currentLang === 'fr' ? 'Informations supplémentaires' : 'Additional Information'}
                                    </label>
                                    <textarea class="form-textarea" name="additionalInfo" 
                                              placeholder="${currentLang === 'fr' 
                                                ? 'Autres informations utiles, témoins potentiels, contexte...' 
                                                : 'Other useful information, potential witnesses, context...'}"
                                              style="color: #1a202c !important;"></textarea>
                                </div>
                            </div>

                            <!-- Consent -->
                            <div class="checkbox-group">
                                <input type="checkbox" class="form-checkbox" id="consentCheckbox" name="consent" required>
                                <label class="checkbox-label" for="consentCheckbox">
                                    ${currentLang === 'fr' 
                                        ? 'Je certifie que les informations fournies sont exactes à ma connaissance et j\'accepte que ce signalement soit traité de manière anonyme conformément à la <a href="confidentialite.php">politique de confidentialité</a>.' 
                                        : 'I certify that the information provided is accurate to my knowledge and I accept that this report be processed anonymously in accordance with the <a href="confidentialite.php">privacy policy</a>.'}
                                </label>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="submit-btn" id="submitBtn">
                                ${currentLang === 'fr' ? 'Soumettre l\'alerte' : 'Submit Alert'}
                            </button>
                        </form>
                    </div>
                </div>
            `;
            
            mainContent.innerHTML = content;
            
            // Initialiser les événements après l'insertion du contenu
            initFormEvents();
        }

        // Initialiser les événements du formulaire
        function initFormEvents() {
            // File upload handling
            const fileUpload = document.getElementById('fileUpload');
            const fileInput = document.getElementById('fileInput');
            const fileList = document.getElementById('fileList');
            
            if (fileUpload && fileInput && fileList) {
                const files = [];
                
                fileUpload.addEventListener('click', () => fileInput.click());
                
                // Drag and drop events
                fileUpload.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    fileUpload.style.borderColor = 'var(--accent-light)';
                    fileUpload.style.background = 'rgba(68, 28, 138, 0.1)';
                });
                
                fileUpload.addEventListener('dragleave', () => {
                    fileUpload.style.borderColor = 'rgba(255, 255, 255, 0.2)';
                    fileUpload.style.background = 'rgba(59, 130, 246, 0.05)';
                });
                
                fileUpload.addEventListener('drop', (e) => {
                    e.preventDefault();
                    fileUpload.style.borderColor = 'rgba(255, 255, 255, 0.2)';
                    fileUpload.style.background = 'rgba(59, 130, 246, 0.05)';
                    
                    if (e.dataTransfer.files.length > 0) {
                        handleFiles(e.dataTransfer.files);
                    }
                });

                fileInput.addEventListener('change', (e) => {
                    if (e.target.files.length > 0) {
                        handleFiles(e.target.files);
                    }
                });

                function handleFiles(newFiles) {
                    for (let file of newFiles) {
                        // Check file size (10MB limit)
                        if (file.size > 10 * 1024 * 1024) {
                            alert(`Fichier trop volumineux: "${file.name}" (max 10MB)`);
                            continue;
                        }
                        
                        // Check file type
                        const allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'mp4', 'mov', 'avi'];
                        const fileExtension = file.name.split('.').pop().toLowerCase();
                        
                        if (!allowedTypes.includes(fileExtension)) {
                            alert(`Format non supporté: "${file.name}"`);
                            continue;
                        }
                        
                        files.push(file);
                        renderFileList();
                    }
                    fileInput.value = '';
                }

                function renderFileList() {
                    fileList.innerHTML = '';
                    files.forEach((file, index) => {
                        const fileItem = document.createElement('div');
                        fileItem.className = 'file-item';
                        fileItem.innerHTML = `
                            <span>${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                            <button type="button" class="file-remove" data-index="${index}">✕</button>
                        `;
                        fileList.appendChild(fileItem);
                    });

                    // Add remove functionality
                    fileList.querySelectorAll('.file-remove').forEach(button => {
                        button.addEventListener('click', (e) => {
                            const index = parseInt(e.target.getAttribute('data-index'));
                            files.splice(index, 1);
                            renderFileList();
                        });
                    });
                }
            }

            // Gestion de la soumission du formulaire
            const form = document.getElementById('alertForm');
            if (form) {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const submitBtn = this.querySelector('#submitBtn');
                    const errorMessage = document.getElementById('errorMessage');
                    const currentLang = '<?php echo $currentLang; ?>';
                    
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = currentLang === 'fr' 
                            ? '<span class="spinner"></span> Traitement en cours...' 
                            : '<span class="spinner"></span> Processing...';
                    }
                    
                    if (errorMessage) {
                        errorMessage.style.display = 'none';
                        errorMessage.textContent = '';
                    }
                    
                    try {
                        const formData = new FormData(this);
                        
                        console.log('Envoi du formulaire...');
                        
                        const response = await fetch('traitement-alerte.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        console.log('Statut de la réponse:', response.status);
                        const result = await response.json();
                        console.log('Réponse du serveur:', result);
                        
                        if (result.success) {
                            // Afficher le message de succès
                            const mainContent = document.getElementById('mainContent');
                            mainContent.innerHTML = `
                                <div class="success-message">
                                    <div style="font-size: 4rem; margin-bottom: 1rem;">✅</div>
                                    <h1 style="color: white; margin-bottom: 1rem; font-size: 2.5rem;">
                                        ${currentLang === 'fr' ? 'Alerte soumise avec succès' : 'Alert Submitted Successfully'}
                                    </h1>
                                    <p style="color: rgba(255, 255, 255, 0.8); margin-bottom: 2rem; font-size: 1.1rem;">
                                        ${result.message}
                                    </p>
                                    <div class="tracking-code floating">${result.trackingCode}</div>
                                    <p style="color: rgba(255, 255, 255, 0.6); margin: 2rem 0; font-size: 0.9rem;">
                                        ${currentLang === 'fr' 
                                            ? 'Conservez précieusement ce code pour suivre l\'avancement de votre alerte de manière anonyme.' 
                                            : 'Keep this code safe to track the progress of your alert anonymously.'}
                                    </p>
                                    <a href="suivi.php?code=${result.trackingCode}" style="display: inline-block; padding: 1rem 2rem; background: linear-gradient(135deg, var(--accent), var(--accent-light)); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: var(--transition); margin: 1rem;">
                                        ${currentLang === 'fr' ? 'Suivre mon alerte' : 'Track my alert'}
                                    </a>
                                    <p style="color: rgba(255, 255, 255, 0.5); margin-top: 2rem; font-size: 0.85rem;">
                                        ${currentLang === 'fr' 
                                            ? 'Votre signalement sera traité dans les plus brefs délais par notre équipe.' 
                                            : 'Your report will be processed as soon as possible by our team.'}
                                    </p>
                                </div>
                            `;
                        } else {
                            if (errorMessage) {
                                errorMessage.textContent = result.message || (currentLang === 'fr' 
                                    ? 'Une erreur est survenue. Veuillez vérifier les champs obligatoires.' 
                                    : 'An error occurred. Please check required fields.');
                                errorMessage.style.display = 'flex';
                            }
                            
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = currentLang === 'fr' 
                                    ? 'Soumettre l\'alerte' : 'Submit Alert';
                            }
                        }
                    } catch (error) {
                        console.error('Erreur détaillée:', error);
                        if (errorMessage) {
                            errorMessage.textContent = currentLang === 'fr' 
                                ? 'Erreur de connexion. Veuillez réessayer.' 
                                : 'Connection error. Please try again.';
                            errorMessage.style.display = 'flex';
                        }
                        
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = currentLang === 'fr' 
                                ? 'Soumettre l\'alerte' : 'Submit Alert';
                        }
                    }
                });
            }

            // Validation en temps réel
            const requiredFields = document.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                field.addEventListener('invalid', (e) => {
                    e.preventDefault();
                    field.style.borderColor = 'var(--danger)';
                    field.style.boxShadow = '0 0 0 2px rgba(220, 38, 38, 0.2)';
                });
                
                field.addEventListener('input', () => {
                    field.style.borderColor = '';
                    field.style.boxShadow = '';
                });
            });
        }

        // Charger le contenu au démarrage
        document.addEventListener('DOMContentLoaded', loadPageContent);
    </script>
</body>
</html>