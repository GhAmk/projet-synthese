<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam+ | Accueil</title>
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #4338ca;
            --accent-color: #00d4ff;
            --dark-bg: #0f172a;
            --card-bg: #1e293b;
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --error-color: #ef4444;
            --feature-card-bg: rgba(255, 255, 255, 0.05);
            --feature-card-border: rgba(255, 255, 255, 0.1);
            --navbar-bg: rgba(15, 23, 42, 0.8);
            --hero-overlay: linear-gradient(135deg, rgba(79, 70, 229, 0.7), rgba(15, 23, 42, 0.9));
            --features-bg: #1e293b;
        }

        [data-theme="dark"] {
            --primary-color: #4f46e5;
            --secondary-color: #4338ca;
            --accent-color: #00d4ff;
            --dark-bg: #0f172a;
            --card-bg: #1e293b;
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --error-color: #ef4444;
            --feature-card-bg: rgba(255, 255, 255, 0.03);
            --feature-card-border: rgba(255, 255, 255, 0.05);
            --navbar-bg: rgba(15, 23, 42, 0.95);
            --hero-overlay: linear-gradient(135deg, rgba(67, 56, 202, 0.8), rgba(15, 23, 42, 0.95));
            --features-bg: #0f172a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        body {
            min-height: 100vh;
            background-color: var(--dark-bg);
            color: var(--text-primary);
            overflow-x: hidden;
        }

        .hero-container {
            position: relative;
            height: 100vh;
            width: 100%;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://source.unsplash.com/random/1920x1080/?classroom,education,technology');
            background-size: cover;
            background-position: center;
            filter: brightness(0.3);
            z-index: -1;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--hero-overlay);
            z-index: -1;
        }

        .navbar {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
            background: var(--navbar-bg);
            backdrop-filter: blur(10px);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-color);
            text-decoration: none;
        }

        .logo span {
            color: var(--text-primary);
        }

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--accent-color);
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .theme-toggle {
            background: transparent;
            border: none;
            color: var(--accent-color);
            font-size: 1.2rem;
            cursor: pointer;
            margin-right: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-login {
            background: transparent;
            border: 2px solid var(--accent-color);
            color: var(--accent-color);
        }

        .btn-register {
            background: linear-gradient(to right, var(--accent-color), var(--primary-color));
            color: var(--text-primary);
            border: none;
        }

        .btn-login:hover, .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 212, 255, 0.3);
        }

        .hero-content {
            text-align: center;
            max-width: 800px;
            padding: 0 2rem;
            z-index: 1;
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            background: linear-gradient(to right, var(--text-primary), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--accent-color), var(--primary-color));
            color: var(--text-primary);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 212, 255, 0.3);
        }

        .btn-secondary {
            background: transparent;
            border: 2px solid var(--accent-color);
            color: var(--accent-color);
        }

        .btn-secondary:hover {
            background: rgba(0, 212, 255, 0.1);
            transform: translateY(-2px);
        }

        .features-section {
            padding: 5rem 2rem;
            background: var(--features-bg);
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 4rem;
            color: var(--accent-color);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background: var(--feature-card-bg);
            border-radius: 15px;
            padding: 2rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid var(--feature-card-border);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--accent-color);
            margin-bottom: 1.5rem;
        }

        .feature-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .feature-description {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .floating-shape {
            position: absolute;
            background: linear-gradient(135deg, var(--accent-color), var(--primary-color));
            border-radius: 50%;
            filter: blur(50px);
            opacity: 0.15;
            z-index: -1;
        }

        .shape-1 {
            width: 300px;
            height: 300px;
            top: 10%;
            left: 10%;
            animation: float 20s infinite alternate ease-in-out;
        }

        .shape-2 {
            width: 200px;
            height: 200px;
            bottom: 10%;
            right: 10%;
            animation: float 15s infinite alternate-reverse ease-in-out;
        }

        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(50px, 50px) rotate(180deg); }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links, .auth-buttons {
                flex-direction: column;
                gap: 0.5rem;
                width: 100%;
                align-items: center;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .cta-buttons {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="hero-container">
        <div class="hero-bg"></div>
        <div class="hero-overlay"></div>
        
        <nav class="navbar">
            <a href="index.php" class="logo">Exam<span>+</span></a>
            <div class="nav-links">
                <a href="#features">Fonctionnalités</a>
                <a href="#about">À propos</a>
                <a href="#contact">Contact</a>
            </div>
            <div class="auth-buttons">
                <button id="themeToggle" class="theme-toggle">🌙</button>
                <a href="login.php" class="btn btn-login">Connexion</a>
                <a href="inscription.php" class="btn btn-register">Inscription</a>
            </div>
        </nav>

        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>

        <div class="hero-content">
            <h1 class="hero-title">Révolutionnez vos évaluations</h1>
            <p class="hero-subtitle">Exam+ est une plateforme moderne et sécurisée pour la création, la gestion et le passage d'examens en ligne. Simplifiez vos processus d'évaluation et offrez une expérience optimale à vos étudiants.</p>
            <div class="cta-buttons">
                <a href="inscription.php" class="btn btn-primary">S'inscrire maintenant</a>
                <a href="login.php" class="btn btn-secondary">Se connecter</a>
            </div>
        </div>
    </div>

    <section id="features" class="features-section">
        <h2 class="section-title">Nos fonctionnalités</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📝</div>
                <h3 class="feature-title">Création d'examens</h3>
                <p class="feature-description">Créez facilement des examens avec différents types de questions : QCM, questions ouvertes, réponses courtes.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔐</div>
                <h3 class="feature-title">Sécurité renforcée</h3>
                <p class="feature-description">Système sécurisé avec des sessions limitées dans le temps et un environnement contrôlé.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3 class="feature-title">Évaluation automatique</h3>
                <p class="feature-description">Notation automatique des QCM et outils d'évaluation assistée pour les questions ouvertes.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">👥</div>
                <h3 class="feature-title">Gestion des groupes</h3>
                <p class="feature-description">Organisez vos étudiants en groupes et assignez des examens collectifs facilement.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3 class="feature-title">Responsive design</h3>
                <p class="feature-description">Accédez à la plateforme depuis n'importe quel appareil, n'importe où, n'importe quand.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📈</div>
                <h3 class="feature-title">Statistiques détaillées</h3>
                <p class="feature-description">Suivez les performances et l'évolution des étudiants avec des analyses complètes.</p>
            </div>
        </div>
    </section>

    <script>
        // Animation de chargement de page
        document.addEventListener('DOMContentLoaded', function() {
            const navbar = document.querySelector('.navbar');
            const themeToggle = document.getElementById('themeToggle');
            const htmlElement = document.documentElement;
            
            // Vérifier le thème enregistré dans localStorage
            const savedTheme = localStorage.getItem('theme') || 'light';
            htmlElement.setAttribute('data-theme', savedTheme);
            updateThemeIcon(savedTheme);
            
            themeToggle.addEventListener('click', function() {
                const currentTheme = htmlElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                
                htmlElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                
                updateThemeIcon(newTheme);
            });
            
            function updateThemeIcon(theme) {
                themeToggle.textContent = theme === 'light' ? '🌙' : '☀️';
            }
            
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    navbar.style.background = 'var(--navbar-bg)';
                    navbar.style.backdropFilter = 'blur(10px)';
                } else {
                    navbar.style.background = 'var(--navbar-bg)';
                    navbar.style.backdropFilter = 'blur(10px)';
                }
            });
        });
    </script>
</body>
</html>