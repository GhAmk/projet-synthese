<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "exam_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Variables communes
$exam_id = isset($_POST['exam_id']) ? $_POST['exam_id'] : (isset($_GET['exam_id']) ? $_GET['exam_id'] : null);

if (!$exam_id) {
    header("Location: " . ($_SESSION['role'] === 'teacher' ? 'teacher_dashboard.php' : 'student_dashboard.php'));
    exit();
}

// Traitement différent selon le rôle
if ($_SESSION['role'] === 'student') {
    if (!isset($_POST['answers'])) {
        header("Location: view_exams.php");
        exit();
    }

    // Récupération explicite de l'ID étudiant depuis la session
    $student_id = $_SESSION['user_id'];
    $answers = $_POST['answers'];

    // Journalisation pour débogage
    error_log("Examen ID: " . $exam_id . ", Étudiant ID: " . $student_id);
    error_log("Réponses reçues: " . print_r($answers, true));
    
    // Calculer le score
    $score = 0;
    $total_questions = 0;
    
    foreach ($answers as $question_id => $answer) {
        // Vérifier si la question est à choix multiple
        $question_type_query = "SELECT question_type FROM questions WHERE id = ?";
        $type_stmt = $conn->prepare($question_type_query);
        $type_stmt->bind_param("i", $question_id);
        $type_stmt->execute();
        $question_type_result = $type_stmt->get_result();
        
        if ($question_type_result->num_rows > 0) {
            $question_type = $question_type_result->fetch_assoc()['question_type'];

            // Pour les questions à choix multiple
            if ($question_type === 'qcm') {
                // Vérifier si la réponse est un tableau (multiple choix)
                if (is_array($answer)) {
                    // Sauvegarder chaque réponse sélectionnée
                    foreach ($answer as $choice_id) {
                        $save_answer = "INSERT INTO student_answers (student_id, exam_id, question_id, answer) VALUES (?, ?, ?, ?)";
                        $stmt = $conn->prepare($save_answer);
                        $stmt->bind_param("iiis", $student_id, $exam_id, $question_id, $choice_id);
                        $stmt->execute();
                        
                        error_log("Sauvegarde QCM (multiple): étudiant=$student_id, examen=$exam_id, question=$question_id, choix=$choice_id");
                    }
                    
                    // Récupérer tous les choix corrects pour cette question
                    $correct_choices_query = "SELECT id FROM choices WHERE question_id = ? AND is_correct = 1";
                    $correct_stmt = $conn->prepare($correct_choices_query);
                    $correct_stmt->bind_param("i", $question_id);
                    $correct_stmt->execute();
                    $correct_result = $correct_stmt->get_result();
                    
                    // Récupérer les IDs des choix corrects
                    $correct_choices = [];
                    while ($row = $correct_result->fetch_assoc()) {
                        $correct_choices[] = $row['id'];
                    }
                    
                    // Calculer le score en fonction des choix de l'étudiant
                    $student_choices = $answer;
                    
                    // Vérifier si les choix de l'étudiant correspondent exactement aux choix corrects
                    $correct_count = count(array_intersect($student_choices, $correct_choices));
                    $incorrect_count = count(array_diff($student_choices, $correct_choices));
                    
                    // L'étudiant obtient un point uniquement si tous ses choix sont corrects
                    // et qu'il a sélectionné tous les choix corrects
                    if ($correct_count == count($correct_choices) && $incorrect_count == 0) {
                        $score++;
                        error_log("Point accordé pour question QCM $question_id");
                    }
                } else {
                    // Pour le cas où une seule réponse est envoyée (compatibilité avec l'ancienne version)
                    $save_answer = "INSERT INTO student_answers (student_id, exam_id, question_id, answer) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($save_answer);
                    $stmt->bind_param("iiis", $student_id, $exam_id, $question_id, $answer);
                    $result_save = $stmt->execute();
                    
                    error_log("Sauvegarde QCM (simple): étudiant=$student_id, examen=$exam_id, question=$question_id, réponse=$answer, résultat=" . ($result_save ? "succès" : "échec"));
                    
                    // Vérifier si la réponse est correcte
                    $check_answer = "SELECT is_correct FROM choices WHERE question_id = ? AND id = ?";
                    $check_stmt = $conn->prepare($check_answer);
                    $check_stmt->bind_param("ii", $question_id, $answer);
                    $check_stmt->execute();
                    $result = $check_stmt->get_result();
                    
                    if ($row = $result->fetch_assoc()) {
                        if ($row['is_correct']) {
                            $score++;
                            error_log("Point accordé pour question QCM simple $question_id");
                        }
                    }
                }
            } else {
                // Pour les questions à réponse ouverte
                $save_answer = "INSERT INTO student_answers (student_id, exam_id, question_id, answer) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($save_answer);
                $stmt->bind_param("iiis", $student_id, $exam_id, $question_id, $answer);
                $result_save = $stmt->execute();
                
                error_log("Sauvegarde question ouverte: étudiant=$student_id, examen=$exam_id, question=$question_id, résultat=" . ($result_save ? "succès" : "échec"));
                
                // Pour les questions ouvertes, pas de notation automatique
                // On peut implémenter un système où l'enseignant note manuellement ces réponses plus tard
            }
            $total_questions++;
        } else {
            error_log("Question ID $question_id non trouvée dans la base de données");
        }
    }

    // Calculer le pourcentage
    $percentage = ($total_questions > 0) ? ($score / $total_questions) * 100 : 0;
    error_log("Score final: $score/$total_questions = $percentage%");

    // Vérifier si l'élève existe déjà dans exam_students
    $check_entry = "SELECT id FROM exam_students WHERE exam_id = ? AND student_id = ?";
    $check_stmt = $conn->prepare($check_entry);
    $check_stmt->bind_param("ii", $exam_id, $student_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Mettre à jour le statut de l'examen
        $update_status = "UPDATE exam_students SET 
                        status = 'completed',
                        score = ?,
                        end_time = NOW()
                        WHERE exam_id = ? AND student_id = ?";
        $status_stmt = $conn->prepare($update_status);
        $status_stmt->bind_param("dii", $percentage, $exam_id, $student_id);
        $result_update = $status_stmt->execute();
        
        error_log("Mise à jour statut: étudiant=$student_id, examen=$exam_id, score=$percentage%, résultat=" . ($result_update ? "succès" : "échec"));
    } else {
        // Créer une nouvelle entrée
        $insert_status = "INSERT INTO exam_students (exam_id, student_id, status, score, end_time) 
                          VALUES (?, ?, 'completed', ?, NOW())";
        $insert_stmt = $conn->prepare($insert_status);
        $insert_stmt->bind_param("iid", $exam_id, $student_id, $percentage);
        $result_insert = $insert_stmt->execute();
        
        error_log("Insertion statut: étudiant=$student_id, examen=$exam_id, score=$percentage%, résultat=" . ($result_insert ? "succès" : "échec"));
    }

    // Afficher la page de confirmation sans montrer le score
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Examen Terminé</title>
        <!-- CSS Libraries -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
        <style>
            :root {
                --primary-color: #4e73df;
                --primary-dark: #3a56b0;
                --secondary-color: #858796;
                --success-color: #1cc88a;
                --info-color: #36b9cc;
                --light-bg: #f8f9fc;
                --white: #fff;
            }
            
            body {
                font-family: 'Nunito', 'Segoe UI', sans-serif;
                background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                padding: 20px 0;
            }
            
            .card {
                border: none;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(78, 115, 223, 0.1);
                overflow: hidden;
                transition: all 0.3s ease;
            }
            
            .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 35px rgba(78, 115, 223, 0.15);
            }
            
            .card-body {
                padding: 3rem 2rem;
            }
            
            .success-icon {
                height: 110px;
                width: 110px;
                background-color: var(--success-color);
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                margin: 0 auto 2rem;
                color: white;
                font-size: 4rem;
                box-shadow: 0 5px 20px rgba(28, 200, 138, 0.3);
            }
            
            .btn-primary {
                background-color: var(--primary-color);
                border-color: var(--primary-color);
                border-radius: 30px;
                padding: 12px 30px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 1px;
                transition: all 0.3s ease;
            }
            
            .btn-primary:hover {
                background-color: var(--primary-dark);
                border-color: var(--primary-dark);
                box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
                transform: translateY(-2px);
            }
            
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .animate-fade-in {
                animation: fadeInUp 0.5s ease-out forwards;
            }
            
            .delay-1 {
                animation-delay: 0.2s;
            }
            
            .delay-2 {
                animation-delay: 0.4s;
            }
            
            .delay-3 {
                animation-delay: 0.6s;
            }
        </style>
    </head>
    <body>
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card shadow animate-fade-in">
                        <div class="card-body text-center">
                            <div class="success-icon animate-fade-in delay-1">
                                <i class="fas fa-check"></i>
                            </div>
                            <h2 class="card-title mb-4 animate-fade-in delay-2">Examen Terminé avec Succès!</h2>
                            
                            <p class="animate-fade-in delay-2">
                                Vos réponses ont été enregistrées correctement. Les résultats seront disponibles ultérieurement.
                            </p>
                            
                            <div class="mt-4 animate-fade-in delay-3">
                                <a href="student_dashboard.php" class="btn btn-primary">
                                    <i class="fas fa-home me-2"></i>Retour au tableau de bord
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Scripts -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    </body>
    </html>
    <?php

} else if ($_SESSION['role'] === 'teacher') {
    
    // Get the exam title
    $exam_query = "SELECT title FROM exams WHERE id = ?";
    $exam_stmt = $conn->prepare($exam_query);
    $exam_stmt->bind_param("i", $exam_id);
    $exam_stmt->execute();
    $exam_result = $exam_stmt->get_result();
    $exam_title = ($exam_result->num_rows > 0) ? $exam_result->fetch_assoc()['title'] : "Examen";

    // Get student results
    $query = "SELECT e.title, u.name as student_name, u.id as student_id, es.score, es.end_time,
              COUNT(q.id) as total_questions
              FROM exams e
              JOIN exam_students es ON e.id = es.exam_id
              JOIN users u ON es.student_id = u.id
              LEFT JOIN questions q ON e.id = q.exam_id
              WHERE e.id = ? AND es.status = 'completed'
              GROUP BY es.student_id
              ORDER BY es.score DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Calculate average score
    $total_score = 0;
    $student_count = 0;
    
    $scores = [];
    $students = [];
    $student_data = [];
    
    // Collect data for chart
    while ($row = $result->fetch_assoc()) {
        $scores[] = $row['score'];
        $students[] = $row['student_name'];
        $student_data[] = $row;
        $total_score += $row['score'];
        $student_count++;
    }
    
    $average_score = ($student_count > 0) ? $total_score / $student_count : 0;
    
    // Reset result pointer
    $result->data_seek(0);
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Correction d'Examen - <?php echo htmlspecialchars($exam_title); ?></title>
        <!-- CSS Libraries -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
        <style>
            :root {
                --primary-color: #4e73df;
                --primary-dark: #3a56b0;
                --secondary-color: #858796;
                --success-color: #1cc88a;
                --warning-color: #f6c23e;
                --danger-color: #e74a3b;
                --info-color: #36b9cc;
                --light-bg: #f8f9fc;
                --white: #fff;
            }
            
            body {
                font-family: 'Nunito', 'Segoe UI', sans-serif;
                background-color: var(--light-bg);
                min-height: 100vh;
                padding-top: 20px;
                padding-bottom: 40px;
            }
            
            .navbar {
                background-color: var(--white);
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
                padding: 0.8rem 1rem;
            }
            
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: 250px;
                padding: 1rem;
                background: var(--primary-color);
                background: linear-gradient(180deg, var(--primary-color) 10%, var(--primary-dark) 100%);
                color: var(--white);
                z-index: 999;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                overflow-y: auto;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .sidebar-header {
                padding: 1.5rem 1rem;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }
            
            .sidebar-menu {
                padding: 1rem 0;
            }
            
            .sidebar-menu a {
                display: block;
                color: rgba(255,255,255,0.8);
                padding: 0.8rem 1rem;
                border-radius: 5px;
                margin-bottom: 0.3rem;
                text-decoration: none;
                transition: all 0.2s ease;
            }
            
            .sidebar-menu a:hover {
                background-color: rgba(255,255,255,0.1);
                color: var(--white);
            }
            
            .sidebar-menu a i {
                margin-right: 0.8rem;
                width: 20px;
                text-align: center;
            }
            
            .page-header {
                background-color: var(--white);
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
                padding: 1.5rem;
                margin-bottom: 2rem;
            }
            
            .card {
                border: none;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
                margin-bottom: 2rem;
                transition: all 0.3s ease;
            }
            
            .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            }
            
            .card-header {
                background-color: var(--white);
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                font-weight: 600;
                padding: 1.25rem 1.5rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .table {
                box-shadow: none;
            }
            
            .table th {
                font-weight: 600;
                background-color: rgba(0, 0, 0, 0.02);
                border-bottom: 2px solid rgba(0, 0, 0, 0.05);
            }
            
            .table td {
                vertical-align: middle;
            }
            
            .btn {
                border-radius: 5px;
                padding: 0.5rem 1rem;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            
            .btn-primary {
                background-color: var(--primary-color);
                border-color: var(--primary-color);
            }
            
            .btn-primary:hover {
                background-color: var(--primary-dark);
                border-color: var(--primary-dark);
                box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
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
            
            .fade-in {
                animation: fadeIn 0.5s ease forwards;
            }
        </style>
    </head>
    <body>
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-light sticky-top">
            <div class="container-fluid">
                <span id="sidebar-toggle" class="me-3">
                    <i class="fas fa-bars fa-lg"></i>
                </span>
                <a class="navbar-brand fw-bold text-primary" href="teacher_dashboard.php">
                    <i class="fas fa-graduation-cap me-2"></i>ExamSystem
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i> Professeur
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profil</a></li>
                                <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Paramètres</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h4 class="mb-0">Tableau de bord</h4>
            </div>
            <div class="sidebar-menu">
                <a href="teacher_dashboard.php" class="active">
                    <i class="fas fa-home"></i> Accueil
                </a>
                <a href="exams.php">
                    <i class="fas fa-file-alt"></i> Examens
                </a>
                <a href="students.php">
                    <i class="fas fa-users"></i> Étudiants
                </a>
                <a href="questions.php">
                    <i class="fas fa-question-circle"></i> Questions
                </a>
                <a href="results.php">
                    <i class="fas fa-chart-bar"></i> Résultats
                </a>
                <a href="settings.php">
                    <i class="fas fa-cog"></i> Paramètres
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="container py-4">
            <div class="row">
                <div class="col-lg-12">
                    <!-- Page Header -->
                    <div class="page-header d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1"><?php echo htmlspecialchars($exam_title); ?></h2>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="teacher_dashboard.php">Tableau de bord</a></li>
                                    <li class="breadcrumb-item"><a href="exams.php">Examens</a></li>
                                    <li class="breadcrumb-item active">Résultats</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Results Table -->
            <div class="row mt-4">
                <div class="col-lg-12 fade-in">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-list me-2"></i>
                                    Liste des participants
                                </div>
                                <div class="search-box">
                                    <i class="fas fa-search"></i>
                                    <input type="text" id="searchStudents" class="form-control" placeholder="Rechercher un étudiant...">
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="resultsTable">
                                    <thead>
                                        <tr>
                                            <th>Étudiant</th>
                                            <th>Statut</th>
                                            <th>Date de soumission</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        while ($row = $result->fetch_assoc()):
                                        ?>
                                        <tr class="student-row" data-student="<?php echo htmlspecialchars(strtolower($row['student_name'])); ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar me-3 bg-light rounded-circle text-center" style="width: 40px; height: 40px; line-height: 40px;">
                                                        <?php echo substr($row['student_name'], 0, 1); ?>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($row['student_name']); ?></h6>
                                                        <small class="text-muted">ID: <?php echo $row['student_id']; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">Terminé</span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($row['end_time'])); ?></td>
                                            <td>
                                                <div class="d-flex">
                                                    <a href="view_student_answers.php?exam_id=<?php echo $exam_id; ?>&student_id=<?php echo $row['student_id']; ?>" 
                                                       class="btn btn-sm btn-primary me-2">
                                                        <i class="fas fa-eye"></i> Voir les réponses
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Scripts -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script>
            // Sidebar toggle
            document.getElementById('sidebar-toggle').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('show');
            });
            
            // Close sidebar when clicking outside
            document.addEventListener('click', function(event) {
                const sidebar = document.getElementById('sidebar');
                const sidebarToggle = document.getElementById('sidebar-toggle');
                
                if (sidebar.classList.contains('show') && 
                    !sidebar.contains(event.target) && 
                    !sidebarToggle.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            });
            
            // Search functionality
// Search functionality
document.getElementById('searchStudents').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const studentRows = document.querySelectorAll('.student-row');
    
    studentRows.forEach(row => {
        const studentName = row.getAttribute('data-student');
        if (studentName.includes(searchValue)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Add statistics and chart initialization
document.addEventListener('DOMContentLoaded', function() {
    // Add any chart initialization code here if you want to display exam statistics
    // For example, you could use Chart.js to show score distributions
    
    // Highlight row on hover for better UX
    const tableRows = document.querySelectorAll('#resultsTable tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseover', function() {
            this.classList.add('bg-light');
        });
        row.addEventListener('mouseout', function() {
            this.classList.remove('bg-light');
        });
    });
    
    // Add export functionality if needed
    const exportButton = document.getElementById('exportResults');
    if (exportButton) {
        exportButton.addEventListener('click', function() {
            // Code to export results to CSV or Excel
            alert('Exporting results...');
            // Implementation would depend on your preferred export method
        });
    }
});
// Pagination functionality
const itemsPerPage = 10;
   const studentRows = document.querySelectorAll('.student-row');
   const totalPages = Math.ceil(studentRows.length / itemsPerPage);
   let currentPage = 1;
   
   function showPage(page) {
       // Hide all rows
       studentRows.forEach((row, index) => {
           row.style.display = 'none';
           
           // Show only rows for current page
           if (index >= (page - 1) * itemsPerPage && index < page * itemsPerPage) {
               row.style.display = '';
           }
       });
       
       // Update pagination controls
       updatePaginationControls();
   }
   
   function updatePaginationControls() {
       const paginationElement = document.getElementById('resultsPagination');
       if (paginationElement) {
           paginationElement.innerHTML = '';
           
           // Previous button
           const prevLi = document.createElement('li');
           prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
           const prevLink = document.createElement('a');
           prevLink.className = 'page-link';
           prevLink.href = '#';
           prevLink.innerText = 'Précédent';
           prevLink.addEventListener('click', (e) => {
               e.preventDefault();
               if (currentPage > 1) {
                   currentPage--;
                   showPage(currentPage);
               }
           });
           prevLi.appendChild(prevLink);
           paginationElement.appendChild(prevLi);
           
           // Page numbers
           for (let i = 1; i <= totalPages; i++) {
               const pageLi = document.createElement('li');
               pageLi.className = `page-item ${i === currentPage ? 'active' : ''}`;
               const pageLink = document.createElement('a');
               pageLink.className = 'page-link';
               pageLink.href = '#';
               pageLink.innerText = i;
               pageLink.addEventListener('click', (e) => {
                   e.preventDefault();
                   currentPage = i;
                   showPage(currentPage);
               });
               pageLi.appendChild(pageLink);
               paginationElement.appendChild(pageLi);
           }
           
           // Next button
           const nextLi = document.createElement('li');
           nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
           const nextLink = document.createElement('a');
           nextLink.className = 'page-link';
           nextLink.href = '#';
           nextLink.innerText = 'Suivant';
           nextLink.addEventListener('click', (e) => {
               e.preventDefault();
               if (currentPage < totalPages) {
                   currentPage++;
                   showPage(currentPage);
               }
           });
           nextLi.appendChild(nextLink);
           paginationElement.appendChild(nextLi);
       }
   }
   
   // Initialize pagination if there are enough rows
   if (studentRows.length > itemsPerPage) {
       // Create pagination container if it doesn't exist
       if (!document.getElementById('resultsPagination')) {
           const paginationContainer = document.createElement('div');
           paginationContainer.className = 'mt-4 d-flex justify-content-center';
           
           const paginationNav = document.createElement('nav');
           paginationNav.setAttribute('aria-label', 'Results navigation');
           
           const paginationUl = document.createElement('ul');
           paginationUl.className = 'pagination';
           paginationUl.id = 'resultsPagination';
           
           paginationNav.appendChild(paginationUl);
           paginationContainer.appendChild(paginationNav);
           
           // Append after the table
           const tableContainer = document.querySelector('.table-responsive');
           tableContainer.parentNode.insertBefore(paginationContainer, tableContainer.nextSibling);
       }
       
       // Show first page and initialize controls
       showPage(currentPage);
   }
   
   // Analytics chart for exam performance
   function initializeChart() {
       const chartCanvas = document.getElementById('examStatsChart');
       if (chartCanvas) {
           // Check if Chart.js is loaded
           if (typeof Chart !== 'undefined') {
               // Sample data - replace with actual data from server
               const labels = <?php echo json_encode($students); ?>;
               const scores = <?php echo json_encode($scores); ?>;
               
               new Chart(chartCanvas, {
                   type: 'bar',
                   data: {
                       labels: labels,
                       datasets: [{
                           label: 'Score (%)',
                           data: scores,
                           backgroundColor: 'rgba(78, 115, 223, 0.8)',
                           borderColor: 'rgba(78, 115, 223, 1)',
                           borderWidth: 1
                       }]
                   },
                   options: {
                       responsive: true,
                       maintainAspectRatio: false,
                       scales: {
                           y: {
                               beginAtZero: true,
                               max: 100,
                               title: {
                                   display: true,
                                   text: 'Score (%)'
                               }
                           },
                           x: {
                               title: {
                                   display: true,
                                   text: 'Étudiants'
                               }
                           }
                       },
                       plugins: {
                           title: {
                               display: true,
                               text: 'Performance des étudiants',
                               font: {
                                   size: 16
                               }
                           }
                       }
                   }
               });
           } else {
               console.error('Chart.js is not loaded');
               chartCanvas.parentNode.innerHTML = '<div class="alert alert-warning">Impossible de charger le graphique. Chart.js n\'est pas disponible.</div>';
           }
       }
   }
   
   // Initialize analytics if chart element exists
   if (document.getElementById('examStatsChart')) {
       initializeChart();
   }
   
   // Enable tooltips
   const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
   tooltipTriggerList.map(function (tooltipTriggerEl) {
       return new bootstrap.Tooltip(tooltipTriggerEl);
   });
   
   // Print functionality
   const printButton = document.getElementById('printResults');
   if (printButton) {
       printButton.addEventListener('click', function() {
           window.print();
       });
   }
});
</script>

<!-- Add the following HTML markup for analytics section -->
<div class="row mt-4 fade-in">
   <div class="col-lg-12">
       <div class="card">
           <div class="card-header">
               <div class="d-flex justify-content-between align-items-center">
                   <div>
                       <i class="fas fa-chart-bar me-2"></i>
                       Statistiques de l'examen
                   </div>
                   <div>
                       <button id="printResults" class="btn btn-sm btn-outline-secondary me-2">
                           <i class="fas fa-print me-1"></i> Imprimer
                       </button>
                       <button id="exportResults" class="btn btn-sm btn-outline-primary">
                           <i class="fas fa-download me-1"></i> Exporter
                       </button>
                   </div>
               </div>
           </div>
           <div class="card-body">
               <div class="row">
                   <div class="col-md-4">
                       <div class="stats-card bg-primary text-white p-4 rounded mb-3">
                           <div class="d-flex justify-content-between">
                               <div>
                                   <h6 class="mb-1">Moyenne de la classe</h6>
                                   <h3 class="mb-0"><?php echo number_format($average_score, 1); ?>%</h3>
                               </div>
                               <div>
                                   <i class="fas fa-calculator fa-3x opacity-50"></i>
                               </div>
                           </div>
                       </div>
                   </div>
                   <div class="col-md-4">
                       <div class="stats-card bg-success text-white p-4 rounded mb-3">
                           <div class="d-flex justify-content-between">
                               <div>
                                   <h6 class="mb-1">Nombre d'étudiants</h6>
                                   <h3 class="mb-0"><?php echo $student_count; ?></h3>
                               </div>
                               <div>
                                   <i class="fas fa-users fa-3x opacity-50"></i>
                               </div>
                           </div>
                       </div>
                   </div>
                   <div class="col-md-4">
                       <div class="stats-card bg-info text-white p-4 rounded mb-3">
                           <div class="d-flex justify-content-between">
                               <div>
                                   <h6 class="mb-1">Questions</h6>
                                   <h3 class="mb-0"><?php echo isset($student_data[0]) ? $student_data[0]['total_questions'] : 0; ?></h3>
                               </div>
                               <div>
                                   <i class="fas fa-question-circle fa-3x opacity-50"></i>
                               </div>
                           </div>
                       </div>
                   </div>
               </div>
               
               <div class="chart-container" style="position: relative; height:350px; margin-top: 20px;">
                   <canvas id="examStatsChart"></canvas>
               </div>
           </div>
       </div>
   </div>
</div>

<!-- Add the following HTML for no results message -->
<?php if ($student_count == 0): ?>
<div class="row mt-4">
   <div class="col-lg-12">
       <div class="alert alert-info text-center p-5">
           <i class="fas fa-info-circle fa-3x mb-3"></i>
           <h4>Aucun résultat disponible</h4>
           <p class="mb-0">Aucun étudiant n'a encore terminé cet examen ou il n'y a pas d'étudiants inscrits.</p>
       </div>
   </div>
</div>
<?php endif; ?>

</body>
</html>
<?php
}

$conn->close();
?>