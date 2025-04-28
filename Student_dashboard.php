<?php 
session_start(); 
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') { 
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Étudiant</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        :root {
            /* Mode clair (par défaut) */
            --primary-color: #4361ee;
            --primary-gradient: linear-gradient(135deg, #4361ee, #3a0ca3);
            --secondary-color: #4cc9f0;
            --text-color: #2b2d42;
            --text-light: #8d99ae;
            --bg-light: #f8f9fa;
            --white: #ffffff;
            --shadow: 0 5px 15px rgba(0,0,0,0.05);
            --shadow-hover: 0 10px 20px rgba(0,0,0,0.1);
            --border-radius: 12px;
            --card-bg: #ffffff;
            --sidebar-bg: #ffffff;
            --border-color: rgba(0,0,0,0.05);
            --transition: all 0.3s ease;
        }

        /* Variables du mode sombre */
        [data-theme="dark"] {
            --primary-color: #4361ee;
            --primary-gradient: linear-gradient(135deg, #4361ee, #3a0ca3);
            --secondary-color: #4cc9f0;
            --text-color: #f8f9fa;
            --text-light: #d1d1d1;
            --bg-light: #121212;
            --white: #1e1e1e;
            --shadow: 0 5px 15px rgba(0,0,0,0.2);
            --shadow-hover: 0 10px 20px rgba(0,0,0,0.3);
            --card-bg: #252525;
            --sidebar-bg: #1e1e1e;
            --border-color: rgba(255,255,255,0.05);
        }

        body {
            background-color: var(--bg-light);
            min-height: 100vh;
            display: flex;
            color: var(--text-color);
            transition: var(--transition);
        }

        .sidebar {
            width: 280px;
            background-color: var(--sidebar-bg);
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            z-index: 100;
        }

        .sidebar-header {
            padding: 25px;
            background: var(--primary-gradient);
            color: var(--white);
            border-radius: 0 0 20px 20px;
            margin-bottom: 10px;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
        }

        .logo i {
            margin-right: 10px;
            font-size: 28px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            margin: 10px 0;
        }

        .user-icon {
            width: 55px;
            height: 55px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--white);
            font-weight: 600;
            font-size: 22px;
            margin-right: 15px;
            box-shadow: 0 3px 8px rgba(67, 97, 238, 0.3);
        }

        .user-info h2 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .user-info p {
            font-size: 13px;
            color: var(--text-light);
            font-weight: 500;
        }

        .menu-list {
            list-style: none;
            flex-grow: 1;
            padding: 10px 0;
        }

        .menu-list li {
            margin: 5px 15px;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .menu-list a {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            text-decoration: none;
            color: var(--text-color);
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-weight: 500;
        }

        .menu-list a:hover {
            background-color: rgba(67, 97, 238, 0.08);
            color: var(--primary-color);
        }

        .menu-list a.active {
            background-color: var(--primary-color);
            color: var(--white);
            box-shadow: 0 3px 8px rgba(67, 97, 238, 0.3);
        }

        .menu-list a i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
            font-size: 18px;
        }

        .logout-btn {
            margin: 15px 15px 25px;
            padding: 14px;
            background: var(--primary-gradient);
            color: var(--white);
            text-align: center;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: var(--border-radius);
            transition: var(--transition);
            box-shadow: 0 3px 8px rgba(67, 97, 238, 0.3);
        }

        .logout-btn i {
            margin-right: 10px;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(67, 97, 238, 0.4);
        }

        /* Bouton de changement de thème */
        .theme-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 5px 15px 15px;
            padding: 10px;
            background-color: rgba(67, 97, 238, 0.08);
            color: var(--primary-color);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            font-size: 14px;
        }

        .theme-toggle:hover {
            background-color: rgba(67, 97, 238, 0.15);
        }

        .theme-toggle i {
            margin-right: 10px;
            font-size: 16px;
        }

        .main-content {
            flex-grow: 1;
            padding: 30px;
            overflow-y: auto;
            transition: var(--transition);
        }

        .page-title {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 30px;
            color: var(--text-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .dashboard-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 25px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary-gradient);
        }

        .card-icon {
            margin-bottom: 15px;
            display: inline-block;
            width: 45px;
            height: 45px;
            line-height: 45px;
            text-align: center;
            border-radius: 50%;
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            font-size: 18px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--text-color);
        }

        .card-description {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .card-action {
            display: inline-block;
            padding: 8px 20px;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            border-radius: 30px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
        }

        .card-action:hover {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 20px;
            display: flex;
            align-items: center;
            transition: var(--transition);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            line-height: 50px;
            text-align: center;
            border-radius: 50%;
            margin-right: 15px;
            font-size: 20px;
        }

        .stat-info h3 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-info p {
            font-size: 13px;
            color: var(--text-light);
        }

        .recent-activity {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 25px;
            transition: var(--transition);
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .section-title a {
            font-size: 13px;
            color: var(--primary-color);
            text-decoration: none;
        }

        .section-title a:hover {
            text-decoration: underline;
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: flex-start;
        }

        .activity-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .activity-content {
            flex-grow: 1;
        }

        .activity-title {
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        .activity-time {
            font-size: 12px;
            color: var(--text-light);
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
                position: fixed;
                height: 100%;
            }

            .sidebar-header .logo {
                display: none;
            }

            .sidebar-header {
                justify-content: center;
                padding: 20px 10px;
            }

            .user-profile {
                justify-content: center;
                padding: 15px;
            }

            .user-icon {
                margin-right: 0;
            }

            .user-info {
                display: none;
            }

            .menu-list a span {
                display: none;
            }

            .menu-list a {
                justify-content: center;
                padding: 15px;
            }

            .menu-list a i {
                margin-right: 0;
                font-size: 20px;
            }

            .logout-btn span {
                display: none;
            }

            .logout-btn i {
                margin-right: 0;
            }
            
            .theme-toggle span {
                display: none;
            }
            
            .theme-toggle i {
                margin-right: 0;
            }

            .main-content {
                margin-left: 80px;
                width: calc(100% - 80px);
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <span>ExamEnLigne</span>
            </div>
        </div>

        <div class="user-profile">
            <div class="user-icon">
                <?php 
                $initials = strtoupper(substr($_SESSION['name'], 0, 1)); 
                echo htmlspecialchars($initials); 
                ?>
            </div>
            <div class="user-info">
                <h2><?php echo htmlspecialchars($_SESSION['name']); ?></h2>
                <p>Étudiant</p>
            </div>
        </div>
        
        <ul class="menu-list">
            <li><a href="Student_dashboard.php" class="active"><i class="fas fa-home"></i> <span>Tableau de bord</span></a></li>
            <li><a href="view_exams.php"><i class="fas fa-file-alt"></i> <span>Examens</span></a></li>
            <li><a href="quiz.php"><i class="fas fa-question-circle"></i> <span>Quiz</span></a></li>
            <li><a href="courses.php"><i class="fas fa-book"></i> <span>Cours</span></a></li>
            <li><a href="results.php"><i class="fas fa-chart-bar"></i> <span>Résultats</span></a></li>
            <li><a href="profileetudiant.php"><i class="fas fa-user"></i> <span>Profil</span></a></li>
        </ul>

        <div class="theme-toggle" id="theme-toggle">
            <i class="fas fa-moon"></i>
            <span>Mode sombre</span>
        </div>

        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Se déconnecter</span>
        </a>
    </div>

    <div class="main-content">
        <h1 class="page-title">Tableau de Bord</h1>
        
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(67, 97, 238, 0.1); color: #4361ee;">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-info">
                    <h3>4</h3>
                    <p>Cours en cours</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(76, 201, 240, 0.1); color: #4cc9f0;">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-info">
                    <h3>2</h3>
                    <p>Examens à venir</p>
                </div>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-icon"><i class="fas fa-file-alt"></i></div>
                <h2 class="card-title">Mes Examens</h2>
                <p class="card-description">Consultez vos examens à venir et passés. Gardez une trace de vos délais importants.</p>
                <a href="view_exams.php" class="card-action">Voir les examens <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon"><i class="fas fa-book"></i></div>
                <h2 class="card-title">Mes Cours</h2>
                <p class="card-description">Suivez votre progression académique et accédez à vos ressources pédagogiques.</p>
                <a href="courses.php" class="card-action">Voir les cours <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon"><i class="fas fa-chart-bar"></i></div>
                <h2 class="card-title">Résultats</h2>
                <p class="card-description">Consultez vos derniers résultats et suivez votre performance académique.</p>
                <a href="results.php" class="card-action">Voir les résultats <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
        
        <div class="recent-activity">
            <h2 class="section-title">
                Activités Récentes
                <a href="activities.php">Voir tout</a>
            </h2>
            <ul class="activity-list">
                <li class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="activity-content">
                        <h3 class="activity-title">Quiz de Laravel complété</h3>
                        <p class="activity-time">Il y a 2 heures</p>
                    </div>
                </li>
                <li class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="activity-content">
                        <h3 class="activity-title">Nouvel examen programmé: React</h3>
                        <p class="activity-time">Hier, 14:30</p>
                    </div>
                </li>
                <li class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="activity-content">
                        <h3 class="activity-title">Nouveau document ajouté au cours Cloude</h3>
                        <p class="activity-time">27 Mars, 09:15</p>
                    </div>
                </li>
            </ul>
        </div>
    </div>

    <script>
        // Récupérer le thème sauvegardé dans le localStorage ou utiliser le mode clair par défaut
        const currentTheme = localStorage.getItem('theme') || 'light';
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = themeToggle.querySelector('i');
        const themeText = themeToggle.querySelector('span');
        
        // Appliquer le thème sauvegardé au chargement de la page
        if (currentTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
            themeText.textContent = 'Mode clair';
        }
        
        // Fonction pour basculer entre les thèmes
        function toggleTheme() {
            if (document.documentElement.getAttribute('data-theme') === 'dark') {
                // Passer au mode clair
                document.documentElement.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
                themeText.textContent = 'Mode sombre';
            } else {
                // Passer au mode sombre
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
                themeText.textContent = 'Mode clair';
            }
        }
        
        // Ajouter l'événement de clic au bouton de changement de thème
        themeToggle.addEventListener('click', toggleTheme);
    </script>
</body>
</html>