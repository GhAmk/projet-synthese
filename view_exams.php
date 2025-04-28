<?php
session_start();

// Check if user is logged in as student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "exam_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current date and time
$current_datetime = date('Y-m-d H:i:s');

// Get available exams
$query = "SELECT e.*, u.name as teacher_name 
          FROM exams e 
          JOIN users u ON e.teacher_id = u.id 
          WHERE e.start_time <= ? AND e.end_time >= ?
          ORDER BY e.start_time ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $current_datetime, $current_datetime);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examens Disponibles - ExamEnLigne</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s, box-shadow 0.3s;
        }

        :root {
            /* Light Theme Variables */
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
            --info-icon-bg: rgba(67, 97, 238, 0.1);
            --card-header-bg: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(76, 201, 240, 0.1));
            --time-remaining-bg: rgba(67, 97, 238, 0.08);
            --breadcrumb-bg: rgba(255,255,255,0.1);
            --breadcrumb-color: rgba(255,255,255,0.8);
            --breadcrumb-divider: rgba(255,255,255,0.5);
            --empty-icon-color: var(--primary-color);
        }

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
        }

        .sidebar {
            width: 280px;
            background-color: var(--white);
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            z-index: 100;
        }

        .sidebar-header {
            padding: 25px;
            background: var(--primary-gradient);
            color: #ffffff;
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
            border-bottom: 1px solid rgba(127,127,127,0.1);
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
            color: #ffffff;
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
            transition: all 0.3s ease;
        }

        .menu-list a {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            text-decoration: none;
            color: var(--text-color);
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .menu-list a:hover {
            background-color: rgba(67, 97, 238, 0.08);
            color: var(--primary-color);
        }

        .menu-list a.active {
            background-color: var(--primary-color);
            color: #ffffff;
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
            color: #ffffff;
            text-align: center;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
            box-shadow: 0 3px 8px rgba(67, 97, 238, 0.3);
        }

        .logout-btn i {
            margin-right: 10px;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(67, 97, 238, 0.4);
        }

        .main-content {
            flex-grow: 1;
            padding: 0;
            overflow-y: auto;
        }

        .page-header {
            background: var(--primary-gradient);
            color: #ffffff;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
        }

        .page-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 50px;
            background: var(--bg-light);
            border-radius: 100% 100% 0 0;
            transform: translateY(50%);
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .breadcrumb {
            display: flex;
            list-style: none;
            padding: 8px 15px;
            background: var(--breadcrumb-bg);
            border-radius: 30px;
            display: inline-flex;
        }

        .breadcrumb-item {
            font-size: 14px;
            font-weight: 500;
        }

        .breadcrumb-item a {
            color: var(--breadcrumb-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .breadcrumb-item a:hover {
            color: #ffffff;
        }

        .breadcrumb-item.active {
            color: #ffffff;
        }

        .breadcrumb-item + .breadcrumb-item::before {
            content: "/";
            padding: 0 10px;
            color: var(--breadcrumb-divider);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .exams-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .exam-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
            opacity: 0;
            transform: translateY(20px);
        }

        .exam-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-hover);
        }

        .exam-card-header {
            background: var(--card-header-bg);
            padding: 20px;
            border-bottom: 1px solid rgba(127,127,127,0.1);
        }

        .exam-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .exam-description {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 0;
            line-height: 1.5;
        }

        .exam-card-body {
            padding: 20px;
        }

        .exam-info-list {
            list-style: none;
            margin-bottom: 20px;
        }

        .exam-info-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .exam-info-item:last-child {
            margin-bottom: 0;
        }

        .exam-info-icon {
            width: 32px;
            height: 32px;
            background: var(--info-icon-bg);
            color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 12px;
            font-size: 14px;
        }

        .exam-info-label {
            font-weight: 500;
            margin-right: 5px;
        }

        .exam-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: var(--primary-gradient);
            color: #ffffff;
            border: none;
            border-radius: 30px;
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 3px 8px rgba(67, 97, 238, 0.3);
        }

        .exam-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
        }

        .exam-btn:disabled {
            background: linear-gradient(135deg, #c5c5c5, #8d99ae);
            cursor: not-allowed;
            opacity: 0.7;
            box-shadow: none;
        }

        .exam-btn:disabled:hover {
            transform: none;
        }

        .empty-message {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 30px;
            text-align: center;
            box-shadow: var(--shadow);
        }

        .empty-icon {
            font-size: 60px;
            color: var(--empty-icon-color);
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .empty-text {
            font-size: 16px;
            color: var(--text-light);
            margin-bottom: 20px;
        }

        .time-remaining {
            background: var(--time-remaining-bg);
            border-radius: 30px;
            padding: 6px 12px;
            font-size: 13px;
            color: var(--primary-color);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            margin-top: 15px;
        }

        .time-remaining i {
            margin-right: 5px;
        }

        /* Theme toggle button */
        .theme-switch {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .theme-switch:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        }

        .theme-switch i {
            color: #ffffff;
            font-size: 22px;
            transition: all 0.3s ease;
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

            .main-content {
                margin-left: 80px;
                width: calc(100% - 80px);
            }
            
            .exams-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .page-header {
                padding: 20px;
            }
            
            .page-title {
                font-size: 22px;
            }
            
            .container {
                padding: 0 15px;
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
            <li><a href="Student_dashboard.php"><i class="fas fa-home"></i> <span>Tableau de bord</span></a></li>
            <li><a href="view_exams.php" class="active"><i class="fas fa-file-alt"></i> <span>Examens</span></a></li>
            <li><a href="quiz.php"><i class="fas fa-question-circle"></i> <span>Quiz</span></a></li>
            <li><a href="courses.php"><i class="fas fa-book"></i> <span>Cours</span></a></li>
            <li><a href="results.php"><i class="fas fa-chart-bar"></i> <span>Résultats</span></a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> <span>Profil</span></a></li>
        </ul>

        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Se déconnecter</span>
        </a>
    </div>

    <!-- Theme Toggle Button -->
    <div class="theme-switch" id="themeSwitch">
        <i class="fas fa-moon"></i>
    </div>

    <div class="main-content">
        <div class="page-header">
            <div class="container">
                <h1 class="page-title">Examens Disponibles</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="Student_dashboard.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Examens Disponibles</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="container" style="margin-top: -30px; position: relative; z-index: 10;">
            <?php if ($result->num_rows > 0): ?>
                <div class="exams-grid">
                    <?php while ($exam = $result->fetch_assoc()): 
                        // Calculate remaining time
                        $end_time = strtotime($exam['end_time']);
                        $current_time = time();
                        $time_remaining = $end_time - $current_time;
                        $hours_remaining = floor($time_remaining / 3600);
                        $minutes_remaining = floor(($time_remaining % 3600) / 60);
                        
                        // Check if student has already attempted this exam
                        $attempt_query = "SELECT COUNT(*) as attempts FROM exam_students 
                                        WHERE exam_id = ? AND student_id = ?";
                        $attempt_stmt = $conn->prepare($attempt_query);
                        $student_id = $_SESSION['user_id'];
                        $attempt_stmt->bind_param("ii", $exam['id'], $student_id);
                        $attempt_stmt->execute();
                        $attempt_result = $attempt_stmt->get_result();
                        $attempts = $attempt_result->fetch_assoc()['attempts'];
                    ?>
                        <div class="exam-card">
                            <div class="exam-card-header">
                                <h3 class="exam-title"><?php echo htmlspecialchars($exam['title']); ?></h3>
                                <?php if (!empty($exam['description'])): ?>
                                    <p class="exam-description"><?php echo htmlspecialchars($exam['description']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="exam-card-body">
                                <ul class="exam-info-list">
                                    <li class="exam-info-item">
                                        <div class="exam-info-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div>
                                            <span class="exam-info-label">Durée:</span>
                                            <span><?php echo htmlspecialchars($exam['duration']); ?> minutes</span>
                                        </div>
                                    </li>
                                    <li class="exam-info-item">
                                        <div class="exam-info-icon">
                                            <i class="fas fa-trophy"></i>
                                        </div>
                                        <div>
                                            <span class="exam-info-label">Points:</span>
                                            <span><?php echo htmlspecialchars($exam['total_points']); ?></span>
                                        </div>
                                    </li>
                                    <li class="exam-info-item">
                                        <div class="exam-info-icon">
                                            <i class="fas fa-user-tie"></i>
                                        </div>
                                        <div>
                                            <span class="exam-info-label">Professeur:</span>
                                            <span><?php echo htmlspecialchars($exam['teacher_name']); ?></span>
                                        </div>
                                    </li>
                                    <li class="exam-info-item">
                                        <div class="exam-info-icon">
                                            <i class="fas fa-calendar-check"></i>
                                        </div>
                                        <div>
                                            <span class="exam-info-label">Début:</span>
                                            <span><?php echo date('d/m/Y H:i', strtotime($exam['start_time'])); ?></span>
                                        </div>
                                    </li>
                                    <li class="exam-info-item">
                                        <div class="exam-info-icon">
                                            <i class="fas fa-calendar-times"></i>
                                        </div>
                                        <div>
                                            <span class="exam-info-label">Fin:</span>
                                            <span><?php echo date('d/m/Y H:i', strtotime($exam['end_time'])); ?></span>
                                        </div>
                                    </li>
                                    <li class="exam-info-item">
                                        <div class="exam-info-icon">
                                            <i class="fas fa-redo"></i>
                                        </div>
                                        <div>
                                            <span class="exam-info-label">Tentatives:</span>
                                            <span><?php echo $attempts; ?>/<?php echo htmlspecialchars($exam['attempts']); ?></span>
                                        </div>
                                    </li>
                                </ul>
                                
                                <div class="time-remaining">
                                    <i class="fas fa-hourglass-half"></i>
                                    Temps restant: <?php echo $hours_remaining; ?>h <?php echo $minutes_remaining; ?>m
                                </div>
                                
                                <?php if ($attempts < $exam['attempts']): ?>
                                    <a href="take_exam.php?exam_id=<?php echo $exam['id']; ?>" class="exam-btn">
                                        <i class="fas fa-play-circle"></i> Commencer l'examen
                                    </a>
                                <?php else: ?>
                                    <button class="exam-btn" disabled>
                                        <i class="fas fa-ban"></i> Nombre maximum de tentatives atteint
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-message">
                    <div class="empty-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3 class="empty-title">Aucun examen disponible</h3>
                    <p class="empty-text">Il n'y a actuellement aucun examen disponible pour vous. Veuillez vérifier ultérieurement.</p>
                    <a href="Student_dashboard.php" class="exam-btn">
                        <i class="fas fa-home"></i> Retour au tableau de bord
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Theme toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Check for saved theme preference or use preferred color scheme
            const savedTheme = localStorage.getItem('theme') || 
                               (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            
            // Apply saved theme
            document.documentElement.setAttribute('data-theme', savedTheme);
            updateThemeIcon(savedTheme);
            
            // Theme switch event listener
            const themeSwitch = document.getElementById('themeSwitch');
            themeSwitch.addEventListener('click', function() {
                let currentTheme = document.documentElement.getAttribute('data-theme');
                let newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateThemeIcon(newTheme);
            });
            
            // Update theme switch icon based on current theme
            function updateThemeIcon(theme) {
                const icon = document.querySelector('#themeSwitch i');
                if (theme === 'dark') {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
            }
            
            // Add animation to cards on load
            const examCards = document.querySelectorAll('.exam-card');
            examCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        });
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>""