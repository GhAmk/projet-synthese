<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit();
}

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "exam_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Récupérer les examens complétés qui nécessitent une correction
$query = "SELECT DISTINCT e.id, e.title, e.start_time, e.end_time,
          COUNT(DISTINCT es.student_id) as submitted_count,
          COUNT(DISTINCT q.id) as question_count
          FROM exams e
          JOIN exam_students es ON e.id = es.exam_id
          LEFT JOIN questions q ON e.id = q.exam_id
          WHERE es.status = 'completed'
          AND e.teacher_id = ?
          GROUP BY e.id
          ORDER BY e.end_time DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$exams_result = $stmt->get_result();

// Compter le nombre total d'étudiants
$student_query = "SELECT COUNT(*) as student_count FROM users WHERE role = 'student'";
$student_result = $conn->query($student_query);
$student_data = $student_result->fetch_assoc();
$student_count = $student_data['student_count'];

// Compter le nombre total de professeurs
$teacher_query = "SELECT COUNT(*) as teacher_count FROM users WHERE role = 'teacher'";
$teacher_result = $conn->query($teacher_query);
$teacher_data = $teacher_result->fetch_assoc();
$teacher_count = $teacher_data['teacher_count'];

// Compter le nombre total d'examens
$exam_query = "SELECT COUNT(*) as exam_count FROM exams";
$exam_result = $conn->query($exam_query);
$exam_data = $exam_result->fetch_assoc();
$exam_count = $exam_data['exam_count'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Formateur</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        :root {
            /* Light theme (default) */
            --primary-color: #4361ee;
            --primary-gradient: linear-gradient(135deg, #4361ee, #3a0ca3);
            --secondary-color: #7209b7;
            --accent-color: #4cc9f0;
            --danger-color: #f72585;
            --warning-color: #fca311;
            --success-color: #2dc653;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --text-primary: #2b2d42;
            --text-secondary: #6c757d;
            --border-color: #e9ecef;
            --sidebar-bg: linear-gradient(180deg,rgb(197, 178, 241), #4361ee);
            --box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
            --hover-bg: rgba(67, 97, 238, 0.05);
            --card-border: rgba(0, 0, 0, 0.05);
            --icon-opacity: 0.12;
            --sidebar-hover: rgba(255, 255, 255, 0.15);
            --sidebar-active: rgba(255, 255, 255, 0.2);
            --sidebar-text: #ffffff;
            --sidebar-width: 280px;
            --transition-speed: 0.3s;
        }

        [data-theme="dark"] {
            /* Dark theme */
            --primary-color: #4cc9f0;
            --primary-gradient: linear-gradient(135deg, #4cc9f0, #4361ee);
            --secondary-color:rgb(0, 0, 0);
            --accent-color:rgb(0, 0, 0);
            --danger-color: #f72585;
            --warning-color: #fca311;
            --success-color: #2dc653;
            --bg-color:rgb(18, 18, 18);
            --card-bg: #1e1e1e;
            --text-primary: #f8f9fa;
            --text-secondary: #adb5bd;
            --border-color: #333333;
            --sidebar-bg: linear-gradient(180deg,rgb(80, 76, 76),rgb(40, 40, 44));
            --box-shadow: 0 10px 20px rgba(0, 0, 0, 0.25);
            --hover-bg: rgba(67, 97, 238, 0.1);
            --card-border: rgba(255, 255, 255, 0.05);
            --icon-opacity: 0.2;
            --sidebar-hover: rgba(255, 255, 255, 0.1);
            --sidebar-active: rgba(255, 255, 255, 0.15);
            --sidebar-text: #f8f9fa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s, box-shadow 0.3s;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(var(--hover-bg) 1px, transparent 1px),
                radial-gradient(var(--hover-bg) 1px, transparent 1px);
            background-size: 50px 50px;
            background-position: 0 0, 25px 25px;
            color: var(--text-primary);
            overflow-x: hidden;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            align-items: start;
            padding: 2rem 1rem;
            box-sizing: border-box;
            box-shadow: var(--box-shadow);
            z-index: 100;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .logo-container {
            width: 100%;
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding: 0 1.5rem;
        }

        .logo {
            color: var(--sidebar-text);
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .menu-item {
            width: 100%;
            margin: 0.5rem 0;
            padding: 1rem 1.5rem;
            color: var(--sidebar-text);
            text-decoration: none;
            border-radius: 0.75rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            opacity: 0.85;
            position: relative;
            overflow: hidden;
        }

        .menu-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 3px;
            height: 100%;
            background: var(--accent-color);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .menu-item.active {
            background-color: var(--sidebar-active);
            opacity: 1;
            font-weight: 600;
        }

        .menu-item.active::before {
            transform: scaleY(1);
        }

        .menu-item:hover {
            background-color: var(--sidebar-hover);
            opacity: 1;
            transform: translateX(5px);
        }

        .menu-item i {
            transition: transform 0.3s ease;
        }

        .menu-item:hover i {
            transform: scale(1.2);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            width: calc(100% - var(--sidebar-width));
            transition: margin-left var(--transition-speed);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .welcome-message {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .user-welcome {
            font-weight: 400;
            color: var(--text-secondary);
            position: relative;
        }

        .user-welcome::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-gradient);
            transition: width 0.3s ease;
        }

        .user-welcome:hover::after {
            width: 100%;
        }

        .theme-switch-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .theme-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }

        .theme-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
            overflow: hidden;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 2;
        }

        .slider::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--primary-gradient);
            border-radius: 34px;
            transform: translateX(-100%);
            transition: .4s;
        }

        input:checked + .slider::after {
            transform: translateX(0);
        }

        input:checked + .slider:before {
            transform: translateX(30px);
        }

        .theme-icon {
            font-size: 1.2rem;
            color: var(--text-primary);
            transition: transform 0.5s ease, opacity 0.5s ease;
        }

        .theme-icon:hover {
            transform: rotate(360deg);
            opacity: 0.8;
        }

        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .card {
            background: var(--card-bg);
            background-image: radial-gradient(var(--hover-bg) 1px, transparent 1px);
            background-size: 20px 20px;
            border-radius: 1.25rem;
            padding: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid var(--border-color);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            transition: all 0.3s ease;
        }

        .card-blue::before {
            background: var(--primary-gradient);
        }

        .card-red::before {
            background: linear-gradient(90deg, var(--danger-color), #f87171);
        }

        .card-orange::before {
            background: linear-gradient(90deg, var(--warning-color), #fbbf24);
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 30px rgba(0, 0, 0, 0.12);
            border-color: var(--primary-color);
        }

        .card:hover::before {
            height: 8px;
        }

        .card h3 {
            color: var(--text-secondary);
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .card p {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease;
        }

        .card:hover p {
            transform: scale(1.1);
        }

        .card-icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 3rem;
            opacity: var(--icon-opacity);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .card:hover .card-icon {
            transform: scale(1.1) rotate(5deg);
            opacity: var(--icon-opacity) * 1.5;
        }

        .exams-card {
            background: var(--card-bg);
            border-radius: 1.25rem;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: var(--box-shadow);
            border: 1px solid var(--border-color);
            display: none;
            transform-origin: top;
            animation: slideIn 0.5s forwards;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .exams-card h2 {
            color: var(--text-primary);
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .exams-card h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--primary-gradient);
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .exams-card:hover h2::after {
            width: 100px;
        }

        .exams-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1rem;
            overflow: hidden;
            border-radius: 0.75rem;
        }

        .exams-table th,
        .exams-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .exams-table th {
            font-weight: 600;
            color: var(--text-primary);
            background: var(--hover-bg);
            position: relative;
        }

        .exams-table th::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .exams-table th:hover::after {
            transform: scaleX(1);
        }

        .exams-table tr {
            transition: all 0.3s ease;
        }

        .exams-table tr:hover {
            background-color: var(--hover-bg);
            transform: translateX(5px);
        }

        .exams-table tr:last-child td {
            border-bottom: none;
        }

        .exam-btn {
            background: var(--primary-gradient);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .exam-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 1;
            }
            100% {
                transform: scale(20, 20);
                opacity: 0;
            }
        }

        .exam-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(0, 0, 0, 0.15);
        }

        .exam-btn:active::after {
            animation: ripple 0.6s ease-out;
        }

        .delete-btn {
            background: linear-gradient(90deg, var(--danger-color), #f87171);
            margin-left: 10px;
        }

        .delete-btn:hover {
            background: linear-gradient(90deg, #e11d48, #f87171);
        }

        .exam-count {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }

        .exam-count:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 5px rgba(0, 0, 0, 0.1);
        }

        .no-exams {
            text-align: center; 
            padding: 3rem 2rem; 
            color: var(--text-secondary);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .no-exams i {
            font-size: 4rem;
            color: var(--primary-color);
            opacity: 0.7;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.7;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.9;
            }
            100% {
                transform: scale(1);
                opacity: 0.7;
            }
        }

        .menu-toggle {
            display: none;
            cursor: pointer;
            font-size: 1.5rem;
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .menu-toggle:hover {
            transform: rotate(90deg);
        }

        @media (max-width: 992px) {
            .menu-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .cards-container {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .theme-switch-container {
                align-self: flex-end;
            }

            .exams-table {
                display: block;
                overflow-x: auto;
            }
        }

        .user-actions {
            margin-top: auto;
            width: 100%;
            padding-top: 2rem;
            border-top: 1px solid var(--sidebar-hover);
        }

        .logout-btn {
            color: var(--sidebar-text);
            opacity: 0.8;
        }

        .logout-btn:hover {
            opacity: 1;
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            animation: bounce 1s infinite;
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-5px);
            }
        }

        /* Loading spinner for counters */
        .counter-container {
            position: relative;
        }

        .counter {
            transition: all 0.3s ease;
        }

        /* Glowing effect for active elements */
        .glow-effect {
            position: relative;
            transition: all 0.3s ease;
        }

        .glow-effect::before {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            border-radius: 1.5rem;
            background: var(--primary-gradient);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .glow-effect:hover::before {
            opacity: 0.7;
            animation: glow 1.5s infinite alternate;
        }

        @keyframes glow {
            0% {
                opacity: 0.3;
                box-shadow: 0 0 5px var(--primary-color);
            }
            100% {
                opacity: 0.7;
                box-shadow: 0 0 20px var(--primary-color);
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">
            <div class="logo-container">
                <div class="logo"><i class="fas fa-graduation-cap"></i> Exam+</div>
            </div>
            <a href="teacher_dashboard.php" class="menu-item active">
                <i class="fas fa-chart-line"></i>
                <span>Tableau de bord</span>
            </a>
            <a href="create_exam.php" class="menu-item">
                <i class="fas fa-file-alt"></i>
                <span>Créer un Examen</span>
            </a>
            <a href="add_students.php" class="menu-item">
                <i class="fas fa-user-plus"></i>
                <span>Ajouter des Étudiants</span>
            </a>
            
           
<a href="listexam.php" class="menu-item">
    <i class="fas fa-clipboard-list"></i>
    <span>Liste des Examens</span>
</a>
            <a href="#" class="menu-item" id="correctExamsLink">
                <i class="fas fa-pencil-alt"></i>
                <span>Corriger les Examens</span>
                <?php if ($exams_result->num_rows > 0): ?>
                <span class="notification-badge"><?php echo $exams_result->num_rows; ?></span>
                <?php endif; ?>
            </a>
            <a href="manage_groups.php" class="menu-item">
    <i class="fas fa-users"></i>
    <span>Groupes</span>
</a>

            <div class="user-actions">
                <a href="logout.php" class="menu-item logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Déconnexion</span>
                </a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <div>
                    <span class="menu-toggle" id="menu-toggle">
                        <i class="fas fa-bars"></i>
                    </span>
                    <h1 class="welcome-message">Bienvenue, <span class="user-welcome"><?php echo htmlspecialchars($_SESSION['name']); ?></span></h1>
                </div>
                <div class="theme-switch-container">
                    <span class="theme-icon" id="theme-icon">☀️</span>
                    <label class="theme-switch">
                        <input type="checkbox" id="theme-toggle">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <div class="cards-container">
                <div class="card card-blue animate__animated animate__fadeIn animate__delay-1s">
                    <i class="fas fa-users card-icon"></i>
                    <h3>Nombre d'étudiants</h3>
                    <div class="counter-container">
                        <p class="counter" data-target="<?php echo $student_count; ?>">0</p>
                    </div>
                </div>

                <div class="card card-red animate__animated animate__fadeIn animate__delay-2s">
                    <i class="fas fa-chalkboard-teacher card-icon"></i>
                    <h3>Nombre de professeurs</h3>
                    <div class="counter-container">
                        <p class="counter" data-target="<?php echo $teacher_count; ?>">0</p>
                    </div>
                </div>

                <div class="card card-orange animate__animated animate__fadeIn animate__delay-3s">
                    <i class="fas fa-file-alt card-icon"></i>
                    <h3>Nombre d'examens</h3>
                    <div class="counter-container">
                        <p class="counter" data-target="<?php echo $exam_count; ?>">0</p>
                    </div>
                </div>
            </div>

            <div class="exams-card" id="exams-card">
                <h2><i class="fas fa-tasks"></i> Examens à Corriger</h2>
                <?php if ($exams_result->num_rows > 0): ?>
                <table class="exams-table">
                    <thead>
                        <tr>
                            <th>Titre de l'examen</th>
                            <th>Date de fin</th>
                            <th>Copies rendues</th>
                            <th>Questions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($exam = $exams_result->fetch_assoc()): ?>
                        <tr class="animate__animated animate__fadeIn">
                            <td><?php echo htmlspecialchars($exam['title']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($exam['end_time'])); ?></td>
                            <td><span class="exam-count"><i class="fas fa-user-graduate"></i> <?php echo $exam['submitted_count']; ?></span></td>
                            <td><span class="exam-count"><i class="fas fa-question-circle"></i> <?php echo $exam['question_count']; ?></span></td>
                            <td>
                                <a href="correct_exam.php?exam_id=<?php echo $exam['id']; ?>" class="exam-btn glow-effect">
                                    <i class="fas fa-check-circle"></i> Corriger
                                </a>
                                <a href="delete_exam.php?exam_id=<?php echo $exam['id']; ?>" class="exam-btn delete-btn" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet examen ?');">
                                    <i class="fas fa-trash-alt"></i> Supprimer
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-exams animate__animated animate__fadeIn">
                    <i class="fas fa-clipboard-check"></i>
                    <p>Aucun examen à corriger pour le moment.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
       function animateCounter() {
    const counters = document.querySelectorAll('.counter');
    const speed = 200;
    
    counters.forEach(counter => {
        const target = +counter.getAttribute('data-target');
        const increment = target / speed;
        
        const updateCount = () => {
            const count = +counter.innerText;
            if (count < target) {
                counter.innerText = Math.ceil(count + increment);
                setTimeout(updateCount, 1);
            } else {
                counter.innerText = target;
            }
        };
        
        updateCount();
    });
}

// Thème sombre/clair
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si un thème est enregistré dans localStorage
    const currentTheme = localStorage.getItem('theme');
    if (currentTheme) {
        document.documentElement.setAttribute('data-theme', currentTheme);
        
        if (currentTheme === 'dark') {
            document.getElementById('theme-toggle').checked = true;
            document.getElementById('theme-icon').innerText = '🌙';
        }
    }
    
    // Activer le panneau des examens à corriger
    document.getElementById('correctExamsLink').addEventListener('click', function(e) {
        e.preventDefault();
        const examsCard = document.getElementById('exams-card');
        examsCard.style.display = examsCard.style.display === 'none' || examsCard.style.display === '' ? 'block' : 'none';
    });
    
    // Toggle du thème
    document.getElementById('theme-toggle').addEventListener('change', function() {
        if (this.checked) {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.getElementById('theme-icon').innerText = '🌙';
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            document.getElementById('theme-icon').innerText = '☀️';
            localStorage.setItem('theme', 'light');
        }
    });
    
    // Menu mobile
    document.getElementById('menu-toggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });
    
    // Animation des compteurs
    animateCounter();
    
    // Effet d'apparition au scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate__fadeIn');
                entry.target.style.opacity = 1;
            }
        });
    }, { threshold: 0.1 });
    
    document.querySelectorAll('.card').forEach(card => {
        observer.observe(card);
    });
    
    // Initialiser l'affichage des examens à corriger
    const notificationCount = document.querySelector('.notification-badge');
    if (notificationCount && parseInt(notificationCount.innerText) > 0) {
        document.getElementById('exams-card').style.display = 'block';
    }
    
    // Effet de ripple pour les boutons
    const buttons = document.querySelectorAll('.exam-btn');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const x = e.clientX - e.target.getBoundingClientRect().left;
            const y = e.clientY - e.target.getBoundingClientRect().top;
            
            const ripple = document.createElement('span');
            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(function() {
                ripple.remove();
            }, 600);
        });
    });
});

// Protection contre la déconnexion accidentelle
window.addEventListener('beforeunload', function(e) {
    // Ne pas afficher le message si l'utilisateur clique sur le bouton de déconnexion
    if (document.activeElement.classList.contains('logout-btn')) {
        return;
    }
    
    // Message de confirmation avant de quitter la page
    e.preventDefault();
    e.returnValue = '';
});

// Fonction pour rechercher des examens dans le tableau
function searchExams() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toUpperCase();
    const table = document.querySelector('.exams-table');
    const tr = table.getElementsByTagName('tr');
    
    for (let i = 1; i < tr.length; i++) {
        const td = tr[i].getElementsByTagName('td')[0];
        if (td) {
            const txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = '';
            } else {
                tr[i].style.display = 'none';
            }
        }
    }
}

// Fonction pour trier le tableau d'examens
function sortTable(n) {
    const table = document.querySelector('.exams-table');
    let rows, switching = true;
    let i, x, y, shouldSwitch, dir = 'asc';
    let switchcount = 0;
    
    while (switching) {
        switching = false;
        rows = table.rows;
        
        for (i = 1; i < (rows.length - 1); i++) {
            shouldSwitch = false;
            x = rows[i].getElementsByTagName('td')[n];
            y = rows[i + 1].getElementsByTagName('td')[n];
            
            if (dir === 'asc') {
                if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            } else if (dir === 'desc') {
                if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            }
        }
        
        if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            switchcount++;
        } else {
            if (switchcount === 0 && dir === 'asc') {
                dir = 'desc';
                switching = true;
            }
        }
    }
    
    // Mettre à jour les indicateurs de tri dans l'en-tête
    const headers = table.querySelectorAll('th');
    headers.forEach((header, index) => {
        header.classList.remove('sort-asc', 'sort-desc');
        if (index === n) {
            header.classList.add(dir === 'asc' ? 'sort-asc' : 'sort-desc');
        }
    });
}

// Effet de glissement lors du hover sur les cartes
document.querySelectorAll('.card').forEach(card => {
    card.addEventListener('mousemove', function(e) {
        const rect = this.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        const midCardWidth = rect.width / 2;
        const midCardHeight = rect.height / 2;
        
        const angleY = -(x - midCardWidth) * 0.01;
        const angleX = (y - midCardHeight) * 0.01;
        
        this.style.transform = `rotateX(${angleX}deg) rotateY(${angleY}deg) translateY(-10px)`;
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = '';
    });
});
</script>
</body>
</html>