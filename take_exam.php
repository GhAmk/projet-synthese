<?php
session_start();

// Vérification de la connexion
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "exam_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Vérifier si l'ID de l'examen est fourni
if (!isset($_GET['exam_id'])) {
    header("Location: view_exams.php");
    exit();
}

$exam_id = $_GET['exam_id'];
$student_id = $_SESSION['user_id'];

// Récupérer les informations de l'examen
$exam_query = "SELECT * FROM exams WHERE id = ?";
$exam_stmt = $conn->prepare($exam_query);
$exam_stmt->bind_param("i", $exam_id);
$exam_stmt->execute();
$exam_result = $exam_stmt->get_result();
$exam = $exam_result->fetch_assoc();

if (!$exam) {
    header("Location: view_exams.php");
    exit();
}

// Vérifier si l'étudiant a déjà commencé cet examen
$attempt_query = "SELECT * FROM exam_students 
                 WHERE exam_id = ? AND student_id = ?";
$attempt_stmt = $conn->prepare($attempt_query);
$attempt_stmt->bind_param("ii", $exam_id, $student_id);
$attempt_stmt->execute();
$attempt_result = $attempt_stmt->get_result();

// Si c'est un nouveau départ d'examen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_exam'])) {
    $start_time = date('Y-m-d H:i:s');
    $end_time = date('Y-m-d H:i:s', strtotime("+{$exam['duration']} minutes"));
    
    $insert_query = "INSERT INTO exam_students (exam_id, student_id, start_time, end_time) 
                     VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("iiss", $exam_id, $student_id, $start_time, $end_time);
    $insert_stmt->execute();
    
    header("Location: exam_questions.php?exam_id=" . $exam_id);
    exit();
}

// Gérer les préférences de thème
if (isset($_COOKIE['theme'])) {
    $theme = $_COOKIE['theme'];
} else {
    $theme = 'light'; // Thème par défaut
}
?>

<!DOCTYPE html>
<html lang="fr" data-bs-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commencer l'examen</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #6a11cb;
            --secondary-color: #2575fc;
            --gradient: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        
        [data-bs-theme="light"] {
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --text-color: #333333;
            --border-color: rgba(0,0,0,0.1);
            --info-bg: rgba(106, 17, 203, 0.05);
            --shadow-color: rgba(0,0,0,0.1);
            --particle-color: rgba(106, 17, 203, 0.1);
            --wave-color-1: rgba(37, 117, 252, 0.05);
            --wave-color-2: rgba(106, 17, 203, 0.03);
        }
        
        [data-bs-theme="dark"] {
            --bg-color: #121212;
            --card-bg: #1e1e1e;
            --text-color: #e9ecef;
            --border-color: rgba(255,255,255,0.1);
            --info-bg: rgba(106, 17, 203, 0.15);
            --shadow-color: rgba(0,0,0,0.25);
            --particle-color: rgba(106, 17, 203, 0.2);
            --wave-color-1: rgba(37, 117, 252, 0.1);
            --wave-color-2: rgba(106, 17, 203, 0.07);
        }

        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }

        @keyframes wave {
            0% { transform: translateX(0) translateZ(0) scaleY(1); }
            50% { transform: translateX(-25%) translateZ(0) scaleY(0.8); }
            100% { transform: translateX(-50%) translateZ(0) scaleY(1); }
        }

        @keyframes moveInRight {
            0% { transform: translateX(100px); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes moveInLeft {
            0% { transform: translateX(-100px); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes moveInBottom {
            0% { transform: translateY(50px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }

        @keyframes particles {
            0% { transform: translateY(0) rotate(0deg); }
            100% { transform: translateY(-1000px) rotate(720deg); }
        }

        html, body {
            height: 100%;
            margin: 0;
            overflow-x: hidden;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.5s ease;
            position: relative;
        }

        /* Fond animé */
        .background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            background: var(--particle-color);
            pointer-events: none;
            animation: particles 60s linear infinite;
        }

        .waves {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 15vh;
            margin-bottom: -7px;
            min-height: 100px;
            max-height: 150px;
        }

        .wave {
            position: absolute;
            width: 200%;
            height: 100%;
            background-repeat: repeat no-repeat;
            background-position: 0 bottom;
            transform-origin: center bottom;
        }

        .wave-1 {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none"><path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V120H0V100C0,67,16.71,40.46,35.29,21.38A600.21,600.21,0,0,1,321.39,56.44Z" fill="%232575fc" opacity="0.1"></path></svg>');
            animation: wave 25s -3s linear infinite;
            z-index: 1;
            opacity: 0.7;
        }

        .wave-2 {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none"><path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V120H0V100C0,67,16.71,40.46,35.29,21.38A600.21,600.21,0,0,1,321.39,56.44Z" fill="%236a11cb" opacity="0.1"></path></svg>');
            animation: wave 30s -6s linear infinite;
            z-index: 2;
            opacity: 0.5;
        }

        /* Contenu principal */
        .exam-start-container {
            max-width: 650px;
            width: 100%;
            padding: 20px;
            z-index: 10;
            animation: fadeIn 1s ease-out;
        }

        .card {
            background-color: var(--card-bg);
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px var(--shadow-color);
            overflow: hidden;
            transition: all 0.5s ease;
            animation: moveInBottom 0.8s ease-out;
            backdrop-filter: blur(5px);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px var(--shadow-color);
        }

        .card-header {
            background: var(--gradient);
            color: white;
            padding: 1.75rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .card-header h3 {
            margin-bottom: 0;
            font-weight: 700;
            letter-spacing: -0.5px;
            position: relative;
            z-index: 2;
            animation: moveInLeft 0.8s ease-out;
        }

        .header-blob {
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            top: -150px;
            left: -100px;
            z-index: 1;
            animation: floating 15s ease-in-out infinite;
        }

        .header-blob:nth-child(2) {
            width: 200px;
            height: 200px;
            top: -50px;
            right: -80px;
            left: auto;
            background: rgba(255, 255, 255, 0.05);
            animation-delay: -7s;
        }

        .theme-toggle {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 5;
            animation: moveInRight 0.8s ease-out;
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1) rotate(15deg);
        }

        .card-body {
            padding: 2.5rem;
        }

        .exam-info {
            background-color: var(--info-bg);
            border-left: 4px solid var(--primary-color);
            padding: 1.25rem;
            margin-bottom: 2rem;
            border-radius: 0 10px 10px 0;
            transition: all 0.5s ease;
            animation: moveInLeft 1s ease-out;
            position: relative;
            overflow: hidden;
        }

        .exam-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: var(--gradient);
            opacity: 0.05;
            transition: width 0.5s ease;
            z-index: 1;
        }

        .exam-info:hover::before {
            width: 100%;
        }

        .exam-info p {
            margin-bottom: 0.75rem;
            color: var(--text-color);
            display: flex;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .icon-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            background: var(--gradient);
            border-radius: 50%;
            margin-right: 15px;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(106, 17, 203, 0.2);
        }

        .exam-info:hover .icon-wrapper {
            transform: rotate(360deg) scale(1.1);
        }

        .alert-info {
            background-color: var(--info-bg);
            border-color: var(--primary-color);
            color: var(--text-color);
            border-radius: 15px;
            padding: 1.5rem;
            transition: all 0.5s ease;
            animation: moveInRight 1s ease-out;
            position: relative;
            overflow: hidden;
        }

        .alert-info::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 0;
            height: 100%;
            background: var(--gradient);
            opacity: 0.05;
            transition: width 0.5s ease;
            z-index: 1;
        }

        .alert-info:hover::before {
            width: 100%;
            right: auto;
            left: 0;
        }

        .alert-info h4 {
            color: var(--primary-color);
            display: flex;
            align-items: center;
            font-weight: 600;
            margin-bottom: 1rem;
            position: relative;
            z-index: 2;
        }

        .alert-info ul {
            padding-left: 2rem;
            margin-bottom: 0;
            position: relative;
            z-index: 2;
        }

        .alert-info li {
            margin-bottom: 0.5rem;
            position: relative;
        }

        .alert-info li::before {
            content: '';
            position: absolute;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary-color);
            left: -15px;
            top: 8px;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .alert-info:hover li::before {
            opacity: 0.7;
        }

        .btn-primary-container {
            animation: moveInBottom 1.2s ease-out;
            margin-top: 2rem;
        }

        .btn-primary {
            background: var(--gradient);
            border: none;
            padding: 1rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 50px;
            transition: all 0.5s ease;
            box-shadow: 0 5px 15px rgba(106, 17, 203, 0.2);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 75%);
            transition: all 0.5s ease;
            z-index: -1;
        }

        .btn-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 25px rgba(106, 17, 203, 0.4);
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:active {
            transform: translateY(0) scale(0.98);
        }

        .btn i {
            margin-right: 8px;
            animation: pulse 2s infinite;
        }

        @media (max-width: 768px) {
            .exam-start-container {
                padding: 10px;
            }
            .card-body {
                padding: 1.5rem;
            }
            .waves {
                height: 10vh;
            }
        }
    </style>
</head>
<body>
    <!-- Arrière-plan animé -->
    <div class="background">
        <div class="particles">
            <?php for ($i = 0; $i < 50; $i++): ?>
                <div class="particle" style="
                    width: <?php echo rand(2, 10); ?>px;
                    height: <?php echo rand(2, 10); ?>px;
                    left: <?php echo rand(0, 100); ?>%;
                    top: <?php echo rand(0, 100); ?>%;
                    opacity: <?php echo rand(1, 10) / 10; ?>;
                    animation-duration: <?php echo rand(40, 80); ?>s;
                    animation-delay: -<?php echo rand(0, 40); ?>s;
                "></div>
            <?php endfor; ?>
        </div>
        <div class="waves">
            <div class="wave wave-1"></div>
            <div class="wave wave-2"></div>
        </div>
    </div>

    <div class="exam-start-container">
        <div class="card shadow">
            <div class="card-header">
                <div class="header-blob"></div>
                <div class="header-blob"></div>
                <h3><?php echo htmlspecialchars($exam['title']); ?></h3>
                <button id="themeToggle" class="theme-toggle" aria-label="Changer le thème">
                    <?php if ($theme === 'light'): ?>
                        <i class="bi bi-moon-fill"></i>
                    <?php else: ?>
                        <i class="bi bi-sun-fill"></i>
                    <?php endif; ?>
                </button>
            </div>
            <div class="card-body">
                <div class="exam-info">
                    <p>
                        <span class="icon-wrapper"><i class="bi bi-info-circle"></i></span>
                        <strong>Description:</strong> <?php echo htmlspecialchars($exam['description']); ?>
                    </p>
                    <p>
                        <span class="icon-wrapper"><i class="bi bi-clock"></i></span>
                        <strong>Durée:</strong> <?php echo htmlspecialchars($exam['duration']); ?> minutes
                    </p>
                    <p>
                        <span class="icon-wrapper"><i class="bi bi-trophy"></i></span>
                        <strong>Points totaux:</strong> <?php echo htmlspecialchars($exam['total_points']); ?>
                    </p>
                </div>

                <div class="alert alert-info">
                    <h4><span class="icon-wrapper"><i class="bi bi-exclamation-triangle"></i></span>Instructions:</h4>
                    <ul>
                        <li>Vous avez <?php echo htmlspecialchars($exam['duration']); ?> minutes pour compléter l'examen</li>
                        <li>Une fois commencé, le chronomètre ne peut pas être arrêté</li>
                        <li>Assurez-vous d'avoir une connexion internet stable</li>
                        <li>Ne fermez pas votre navigateur pendant l'examen</li>
                    </ul>
                </div>

                <div class="btn-primary-container">
                    <form method="POST" class="text-center">
                        <input type="hidden" name="start_exam" value="1">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-play-circle"></i>Commencer l'examen
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('themeToggle');
            const htmlElement = document.documentElement;
            
            // Animation de départ
            setTimeout(() => {
                document.querySelector('.card').style.opacity = '1';
            }, 300);
            
            themeToggle.addEventListener('click', function() {
                const currentTheme = htmlElement.getAttribute('data-bs-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                // Effet de transition
                document.body.style.opacity = '0.8';
                
                setTimeout(() => {
                    htmlElement.setAttribute('data-bs-theme', newTheme);
                    
                    // Mettre à jour l'icône
                    themeToggle.innerHTML = newTheme === 'dark' 
                        ? '<i class="bi bi-sun-fill"></i>' 
                        : '<i class="bi bi-moon-fill"></i>';
                    
                    // Enregistrer la préférence dans un cookie (expire dans 30 jours)
                    document.cookie = `theme=${newTheme}; max-age=${60*60*24*30}; path=/; SameSite=Strict`;
                    
                    document.body.style.opacity = '1';
                }, 300);
            });
            
            // Animation au survol des éléments
            const cardElements = document.querySelectorAll('.card, .exam-info, .alert-info');
            cardElements.forEach(element => {
                element.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                element.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>