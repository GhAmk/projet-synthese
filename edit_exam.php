<?php
session_start();

// Vérifier si l'utilisateur est connecté et est un enseignant
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit();
}

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "exam_system");

if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Vérifier si un ID d'examen est passé en paramètre
if (!isset($_GET['id'])) {
    header("Location: Teacher_dashboard.php");
    exit();
}

$exam_id = $_GET['id'];
$teacher_id = $_SESSION['user_id'];

// Récupérer les informations de l'examen
$exam_query = "SELECT * FROM exams WHERE id = ? AND teacher_id = ?";
$stmt_exam = $conn->prepare($exam_query);
$stmt_exam->bind_param("ii", $exam_id, $teacher_id);
$stmt_exam->execute();
$exam_result = $stmt_exam->get_result();

if ($exam_result->num_rows === 0) {
    header("Location: Teacher_dashboard.php");
    exit();
}

$exam = $exam_result->fetch_assoc();

// Récupérer les questions de l'examen
$questions_query = "SELECT * FROM questions WHERE exam_id = ?";
$stmt_questions = $conn->prepare($questions_query);
$stmt_questions->bind_param("i", $exam_id);
$stmt_questions->execute();
$questions_result = $stmt_questions->get_result();

$questions = [];
while ($question = $questions_result->fetch_assoc()) {
    // Récupérer les options pour les questions QCM
    if ($question['question_type'] === 'qcm') {
        $options_query = "SELECT * FROM choices WHERE question_id = ?";
        $stmt_options = $conn->prepare($options_query);
        $stmt_options->bind_param("i", $question['id']);
        $stmt_options->execute();
        $options_result = $stmt_options->get_result();
        
        $question['options'] = [];
        while ($option = $options_result->fetch_assoc()) {
            $question['options'][] = $option;
        }
    }
    
    // Récupérer les points pour la question
    $points_query = "SELECT points FROM question_points WHERE question_id = ?";
    $stmt_points = $conn->prepare($points_query);
    $stmt_points->bind_param("i", $question['id']);
    $stmt_points->execute();
    $points_result = $stmt_points->get_result();
    
    if ($points_result->num_rows > 0) {
        $points = $points_result->fetch_assoc();
        $question['points'] = $points['points'];
    } else {
        $question['points'] = 1; // Valeur par défaut
    }
    
    $questions[] = $question;
}

// Récupérer les groupes disponibles
$groups_query = "SELECT * FROM groups";
$stmt_groups = $conn->prepare($groups_query);
$stmt_groups->execute();
$result_groups = $stmt_groups->get_result();

$groups_exist = $result_groups->num_rows > 0;

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'] ?? '';
    $duration = $_POST['duration'];
    $attempts = $_POST['attempts'];
    $total_points = $_POST['total_points'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $group_id = $_POST['group_id'];

    // Mettre à jour l'examen
    $update_exam = $conn->prepare("UPDATE exams SET title = ?, description = ?, start_time = ?, end_time = ?, duration = ?, attempts = ?, total_points = ?, group_id = ? WHERE id = ?");
    $update_exam->bind_param("ssssiiiii", $title, $description, $start_date, $end_date, $duration, $attempts, $total_points, $group_id, $exam_id);

    if ($update_exam->execute()) {
        // Supprimer les anciennes questions et options
        $delete_questions = $conn->prepare("DELETE FROM questions WHERE exam_id = ?");
        $delete_questions->bind_param("i", $exam_id);
        $delete_questions->execute();

        // Insérer les nouvelles questions
        if (!empty($_POST['questions'])) {
            $questions = $_POST['questions'];
            $question_points = $_POST['question_points'];
            $question_types = $_POST['question_type'];

            for ($i = 0; $i < count($questions); $i++) {
                $stmt_question = $conn->prepare("INSERT INTO questions (exam_id, question_text, question_type) VALUES (?, ?, ?)");
                $stmt_question->bind_param("iss", $exam_id, $questions[$i], $question_types[$i]);
                $stmt_question->execute();
                $question_id = $stmt_question->insert_id;
                
                // Insérer les points de la question
                if (isset($question_points[$i]) && $question_points[$i] > 0) {
                    $stmt_points = $conn->prepare("INSERT INTO question_points (question_id, points) VALUES (?, ?)");
                    $stmt_points->bind_param("ii", $question_id, $question_points[$i]);
                    $stmt_points->execute();
                }

                // Si c'est un QCM, insérer les options
                if ($question_types[$i] === 'qcm' && isset($_POST['options'][$i]['text'])) {
                    $options = $_POST['options'][$i]['text'];
                    $correct = isset($_POST['options'][$i]['correct']) ? $_POST['options'][$i]['correct'] : [];

                    for ($j = 0; $j < count($options); $j++) {
                        if (!empty($options[$j])) {
                            $is_correct = isset($correct[$j]) ? 1 : 0;
                            $stmt_option = $conn->prepare("INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)");
                            $stmt_option->bind_param("isi", $question_id, $options[$j], $is_correct);
                            $stmt_option->execute();
                        }
                    }
                }
            }
        }

        // Rediriger vers la page des examens avec un message de succès
        header("Location: Teacher_dashboard.php?success=exam_updated");
        exit();
    } else {
        $error = "Erreur lors de la mise à jour de l'examen: " . $update_exam->error;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'Examen</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --secondary-color: #6c757d;
            --success-color: #4caf50;
            --danger-color: #f44336;
            --warning-color: #ff9800;
            --info-color: #2196f3;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --background-light: #f9fafb;
            --background-dark: #121212;
            --text-light: #f8f9fa;
            --text-dark: #343a40;
            --border-light: #dee2e6;
            --border-dark: #495057;
            --shadow-light: rgba(0, 0, 0, 0.1);
            --shadow-dark: rgba(0, 0, 0, 0.5);
            --card-bg-light: #ffffff;
            --card-bg-dark: #2d2d2d;
            --input-bg-light: #ffffff;
            --input-bg-dark: #333333;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-light);
            color: var(--text-dark);
            min-height: 100vh;
            overflow-x: hidden;
            transition: all 0.3s ease;
        }

        body.dark-mode {
            background-color: var(--background-dark);
            color: var(--text-light);
        }

        /* Background Elements */
        .page-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.05) 0%, rgba(67, 97, 238, 0.1) 100%);
            z-index: -2;
        }

        .page-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('data:image/svg+xml;utf8,<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="none"/><circle cx="50" cy="50" r="1" fill="%234361ee20"/></svg>');
            background-size: 30px 30px;
            opacity: 0.5;
            z-index: -1;
        }

        .dark-mode .page-bg {
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.05) 0%, rgba(67, 97, 238, 0.1) 100%);
        }

        .dark-mode .page-overlay {
            background-image: url('data:image/svg+xml;utf8,<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="none"/><circle cx="50" cy="50" r="1" fill="%234361ee20"/></svg>');
            opacity: 0.2;
        }

        /* Floating Shapes */
        .floating-shape {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: -1;
        }

        .shape-1 {
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, rgba(67, 97, 238, 0.1), rgba(76, 175, 80, 0.1));
            top: -100px;
            left: -100px;
            animation: float 15s ease-in-out infinite;
        }

        .shape-2 {
            width: 400px;
            height: 400px;
            background: linear-gradient(45deg, rgba(244, 67, 54, 0.1), rgba(33, 150, 243, 0.1));
            bottom: -150px;
            right: -150px;
            animation: float 20s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(50px, 50px) rotate(5deg); }
            50% { transform: translate(0, 100px) rotate(0deg); }
            75% { transform: translate(-50px, 50px) rotate(-5deg); }
        }

        /* Navigation Bar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .dark-mode .navbar {
            background-color: rgba(33, 37, 41, 0.9);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }

        .logo::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--primary-color);
            transition: width 0.3s ease;
        }

        .logo:hover::after {
            width: 100%;
        }

        .logo span {
            color: var(--success-color);
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #43a047;
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            background-color: #d32f2f;
            transform: translateY(-2px);
        }

        .btn-back {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-back:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .theme-toggle {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.25rem;
            transition: transform 0.3s ease;
        }

        .theme-toggle:hover {
            transform: rotate(180deg);
        }

        /* Main Content */
        .exam-container {
            max-width: 1000px;
            margin: 2rem auto;
            background: var(--card-bg-light);
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .dark-mode .exam-container {
            background: var(--card-bg-dark);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }

        .container-glow {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--success-color), var(--info-color));
            animation: glowAnimation 2s linear infinite;
        }

        @keyframes glowAnimation {
            0% { background-position: 0% center; }
            100% { background-position: 200% center; }
        }

        h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 2rem;
            font-weight: 700;
            font-size: 2rem;
            position: relative;
            display: inline-block;
            left: 50%;
            transform: translateX(-50%);
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), transparent);
        }

        h3 {
            color: var(--primary-color);
            margin: 1.5rem 0;
            font-weight: 600;
            font-size: 1.3rem;
        }

        .section-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--border-light), transparent);
            margin: 2rem 0;
        }

        .dark-mode .section-divider {
            background: linear-gradient(90deg, transparent, var(--border-dark), transparent);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--primary-color);
        }

        input[type="text"],
        input[type="number"],
        input[type="datetime-local"],
        textarea,
        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-light);
            border-radius: 8px;
            font-family: inherit;
            background-color: var(--input-bg-light);
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        .dark-mode input[type="text"],
        .dark-mode input[type="number"],
        .dark-mode input[type="datetime-local"],
        .dark-mode textarea,
        .dark-mode select {
            border-color: var(--border-dark);
            background-color: var(--input-bg-dark);
            color: var(--text-light);
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        input[type="submit"],
        button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        input[type="submit"]:hover,
        button:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }

        /* Questions Container Styles */
        #questions-container {
            margin-top: 2rem;
        }

        .question {
            background-color: rgba(67, 97, 238, 0.05);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 3px solid var(--primary-color);
            position: relative;
            transition: all 0.3s ease;
        }

        .dark-mode .question {
            background-color: rgba(67, 97, 238, 0.1);
        }

        .question:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .dark-mode .question:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        #add-question-btn {
            background-color: var(--success-color);
            display: block;
            margin: 1rem auto 2rem;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        #add-question-btn:hover {
            background-color: #43a047;
            transform: translateY(-2px);
        }

        /* Options Styles */
        .options-container {
            margin-top: 1rem;
            background-color: rgba(255, 255, 255, 0.5);
            padding: 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .dark-mode .options-container {
            background-color: rgba(33, 37, 41, 0.5);
        }

        .option {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            gap: 0.75rem;
        }

        .option input[type="text"] {
            flex-grow: 1;
        }

        .option label {
            display: flex;
            align-items: center;
            margin-bottom: 0;
            color: var(--text-dark);
            gap: 0.5rem;
            cursor: pointer;
            white-space: nowrap;
        }

        .dark-mode .option label {
            color: var(--text-light);
        }

        .option button {
            background-color: var(--danger-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .option button:hover {
            background-color: #d32f2f;
            transform: translateY(-2px);
        }

        .add-option-btn {
            background-color: var(--info-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            margin-top: 0.5rem;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .add-option-btn:hover {
            background-color: #0b7dda;
            transform: translateY(-2px);
        }

        /* Error and Warning Messages */
        .error-message {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--danger-color);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 3px solid var(--danger-color);
        }

        .warning-message {
            background-color: rgba(255, 152, 0, 0.1);
            color: var(--warning-color);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 3px solid var(--warning-color);
        }

        /* Tooltip styles */
        [data-tooltip] {
            position: relative;
        }

        [data-tooltip]:before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-bottom: 5px;
            padding: 5px 10px;
            background-color: var(--dark-color);
            color: white;
            border-radius: 4px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        [data-tooltip]:after {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 5px;
            border-style: solid;
            border-color: var(--dark-color) transparent transparent transparent;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        [data-tooltip]:hover:before,
        [data-tooltip]:hover:after {
            opacity: 1;
            visibility: visible;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0.75rem;
            }

            .exam-container {
                padding: 1.5rem;
                margin: 1rem;
            }

            .navbar {
                padding: 0.75rem 1rem;
            }

            h2 {
                font-size: 1.5rem;
            }

            .option {
                flex-wrap: wrap;
            }

            .option label {
                margin-top: 0.5rem;
            }
        }

        /* Animation for add/remove */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .question, .option {
            animation: fadeIn 0.3s ease-out forwards;
        }
    </style>
</head>
<body>
    <!-- Background Elements -->
    <div class="page-bg"></div>
    <div class="page-overlay"></div>
    
    <!-- Navigation Bar -->
    <nav class="navbar">
    <div class="logo"><i class="fas fa-graduation-cap"></i> Exam+</div>
        
        <div class="auth-buttons">
            <button id="themeToggle" class="theme-toggle">🌙</button>
            <a href="Teacher_dashboard.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Retour</a>
        </div>
    </nav>

    <!-- Floating Shapes -->
    <div class="floating-shape shape-1"></div>
    <div class="floating-shape shape-2"></div>

    <!-- Main Content -->
    <div class="exam-container">
        <div class="container-glow"></div>
        <h2>Modifier l'Examen</h2>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!$groups_exist): ?>
            <div class="warning-message">
                <strong><i class="fas fa-exclamation-triangle"></i> Attention :</strong> Aucun groupe n'est disponible.
            </div>
        <?php endif; ?>
        
        <form method="POST" action="edit_exam.php?id=<?php echo $exam_id; ?>" id="exam-form">
            <!-- Titre et Description -->
            <div class="form-group">
                <label for="title"><i class="fas fa-heading"></i> Titre de l'examen:</label>
                <input type="text" name="title" id="title" required placeholder="Entrez le titre de l'examen" value="<?php echo htmlspecialchars($exam['title']); ?>">
            </div>

            <div class="form-group">
                <label for="description"><i class="fas fa-align-left"></i> Description (optionnelle):</label>
                <textarea name="description" id="description" placeholder="Ajoutez une description détaillée de l'examen" rows="3"><?php echo htmlspecialchars($exam['description']); ?></textarea>
            </div>

            <!-- Informations Groupe et Points -->
            <div class="form-row">
                <div class="form-group">
                    <label for="group_id"><i class="fas fa-users"></i> Groupe d'étudiants:</label>
                    <select name="group_id" id="group_id" required <?php echo !$groups_exist ? 'disabled' : ''; ?>>
                        <option value="">Sélectionnez un groupe</option>
                        <?php if ($groups_exist): 
                            $result_groups->data_seek(0); // Réinitialiser le pointeur
                            while ($group = $result_groups->fetch_assoc()): ?>
                            <option value="<?php echo $group['id']; ?>" <?php echo $group['id'] == $exam['group_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($group['name']); ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                    <?php if (!$groups_exist): ?>
                        <span class="error-message">Aucun groupe disponible</span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="total_points"><i class="fas fa-star"></i> Total des points:</label>
                    <input type="number" name="total_points" id="total_points" required placeholder="Total des points de l'examen" min="1" value="<?php echo $exam['total_points']; ?>">
                </div>
            </div>

            <!-- Durée et Tentatives -->
            <div class="form-row">
                <div class="form-group">
                    <label for="duration"><i class="fas fa-clock"></i> Durée (en minutes):</label>
                    <input type="number" name="duration" id="duration" required placeholder="Durée de l'examen" min="1" value="<?php echo $exam['duration']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="attempts">Nombre de tentatives autorisées:</label>
                    <input type="number" name="attempts" id="attempts" required placeholder="Tentatives autorisées" min="1" value="<?php echo $exam['attempts']; ?>">
                </div>
            </div>

            <!-- Date de début et de fin -->
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Date de début:</label>
                    <?php 
                    $start_date = new DateTime($exam['start_time']);
                    $formatted_start = $start_date->format('Y-m-d\TH:i');
                    ?>
                    <input type="datetime-local" name="start_date" id="start_date" required value="<?php echo $formatted_start; ?>">
                </div>
                
                <div class="form-group">
                    <label for="end_date">Date de fin:</label>
                    <?php 
                    $end_date = new DateTime($exam['end_time']);
                    $formatted_end = $end_date->format('Y-m-d\TH:i');
                    ?>
                    <input type="datetime-local" name="end_date" id="end_date" required value="<?php echo $formatted_end; ?>">
                </div>
            </div>

            <div class="section-divider"></div>

            <!-- Section pour modifier les questions -->
            <div id="questions-container">
                <h3>Questions de l'examen</h3>
                <button type="button" id="add-question-btn" data-tooltip="Ajouter une nouvelle question" onclick="addQuestion()">Ajouter une question</button>
                
                <?php foreach ($questions as $index => $question): ?>
                    <div class="question">
                        <div class="form-group">
                            <label for="question_<?php echo $index + 1; ?>">Question <?php echo $index + 1; ?>:</label>
                            <textarea name="questions[]" id="question_<?php echo $index + 1; ?>" required 
                                placeholder="Entrez le texte de la question" rows="3"><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="question_points_<?php echo $index + 1; ?>">Points:</label>
                            <input type="number" name="question_points[]" id="question_points_<?php echo $index + 1; ?>" 
                                required placeholder="Nombre de points" min="1" value="<?php echo $question['points']; ?>">
                        </div>
                        
                        <input type="hidden" name="question_type[]" value="<?php echo $question['question_type']; ?>">
                        
                        <?php if ($question['question_type'] === 'qcm' && isset($question['options'])): ?>
                            <div class="options-container" id="options_container_<?php echo $index + 1; ?>">
                                <label>Options de réponse:</label>
                                <?php foreach ($question['options'] as $opt_index => $option): ?>
                                    <div class="option">
                                        <input type="text" name="options[<?php echo $index; ?>][text][]" 
                                            placeholder="Option <?php echo $opt_index + 1; ?>" required value="<?php echo htmlspecialchars($option['choice_text']); ?>">
                                        <label>
                                            <input type="checkbox" name="options[<?php echo $index; ?>][correct][]" value="<?php echo $opt_index; ?>" <?php echo $option['is_correct'] ? 'checked' : ''; ?>>
                                            Correcte
                                        </label>
                                        <button type="button" onclick="removeOption(this)">Supprimer</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <button type="button" class="add-option-btn" onclick="addOption(<?php echo $index + 1; ?>)">
                                Ajouter une option
                            </button>
                        <?php endif; ?>
                        
                        <button type="button" class="btn-danger" onclick="removeQuestion(this)">
                            Supprimer cette question
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Bouton pour soumettre les modifications -->
            <div class="form-group" style="margin-top: 2.5rem; text-align: center;">
                <input type="submit" value="Enregistrer les modifications" data-tooltip="Enregistrer les modifications de l'examen">
            </div>
        </form>
    </div>

    <!-- Utilisez le même JavaScript que dans create_exam.php -->
    <script>
        // Variables globales
        let questionCounter = 0;
        let optionCounters = {};

        // Fonction pour ajouter une nouvelle question
        function addQuestion() {
            questionCounter++;
            const questionContainer = document.getElementById('questions-container');
            
            const questionDiv = document.createElement('div');
            questionDiv.className = 'question';
            
            // Type de question
            const questionType = 'qcm'; // Par défaut, on utilise QCM
            
            questionDiv.innerHTML = `
                <div class="form-group">
                    <label for="question_${questionCounter}">Question ${questionCounter}:</label>
                    <textarea name="questions[]" id="question_${questionCounter}" required 
                        placeholder="Entrez le texte de la question" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="question_points_${questionCounter}">Points:</label>
                    <input type="number" name="question_points[]" id="question_points_${questionCounter}" 
                        required placeholder="Nombre de points" min="1" value="1">
                </div>
                
                <input type="hidden" name="question_type[]" value="${questionType}">
                
                <div class="options-container" id="options_container_${questionCounter}">
                    <label>Options de réponse:</label>
                    <!-- Les options seront ajoutées ici -->
                </div>
                
                <button type="button" class="add-option-btn" onclick="addOption(${questionCounter})">
                    <i class="fas fa-plus-circle"></i> Ajouter une option
                </button>
                
                <button type="button" class="btn-danger" onclick="removeQuestion(this)">
                    <i class="fas fa-trash-alt"></i> Supprimer cette question
                </button>
            `;
            
            questionContainer.appendChild(questionDiv);
            
            // Initialiser le compteur d'options pour cette question
            optionCounters[questionCounter] = 0;
            
            // Ajouter deux options par défaut
            addOption(questionCounter);
            addOption(questionCounter);
            
            return questionDiv;
        }

        // Fonction pour supprimer une question
        function removeQuestion(button) {
            if (document.querySelectorAll('.question').length > 1) {
                const questionDiv = button.closest('.question');
                questionDiv.parentNode.removeChild(questionDiv);
                
                // Mettre à jour les numéros de question
                updateQuestionNumbers();
            } else {
                alert("Vous devez avoir au moins une question dans l'examen.");
            }
        }

        // Mettre à jour les numéros de question
        function updateQuestionNumbers() {
            const questions = document.querySelectorAll('.question');
            questions.forEach((question, index) => {
                const labelElement = question.querySelector('label[for^="question_"]');
                if (labelElement) {
                    labelElement.textContent = `Question ${index + 1}:`;
                }
            });
        }

        // Fonction pour ajouter une option à une question
        function addOption(questionNumber) {
            const optionsContainer = document.getElementById(`options_container_${questionNumber}`);
            
            if (!optionCounters[questionNumber]) {
                optionCounters[questionNumber] = 0;
            }
            
            optionCounters[questionNumber]++;
            const optionIndex = optionCounters[questionNumber];
            
            const optionDiv = document.createElement('div');
            optionDiv.className = 'option';
            
            // Calculer l'index de la question dans le DOM
            const questionIndex = Array.from(document.querySelectorAll('.question')).findIndex(
                q => q.querySelector(`#options_container_${questionNumber}`)
            );
            
            optionDiv.innerHTML = `
                <input type="text" name="options[${questionIndex}][text][]" 
                    placeholder="Option ${optionIndex}" required>
                <label>
                    <input type="checkbox" name="options[${questionIndex}][correct][]" value="${optionIndex - 1}">
                    Correcte
                </label>
                <button type="button" onclick="removeOption(this)" class="btn-danger">
                    <i class="fas fa-times"></i> Supprimer
                </button>
            `;
            
            optionsContainer.appendChild(optionDiv);
        }

        // Fonction pour supprimer une option
        function removeOption(button) {
            const optionDiv = button.closest('.option');
            const optionsContainer = optionDiv.parentNode;
            
            if (optionsContainer.querySelectorAll('.option').length > 2) {
                optionDiv.parentNode.removeChild(optionDiv);
                
                // Mettre à jour les indices d'options pour les cases à cocher
                updateOptionIndices(optionsContainer);
            } else {
                alert("Une question à choix multiples doit avoir au moins deux options.");
            }
        }

        // Mettre à jour les indices des options
        function updateOptionIndices(optionsContainer) {
            const options = optionsContainer.querySelectorAll('.option');
            options.forEach((option, index) => {
                const checkboxInput = option.querySelector('input[type="checkbox"]');
                if (checkboxInput) {
                    checkboxInput.value = index;
                }
            });
        }

        // Validation du formulaire
        document.getElementById('exam-form').addEventListener('submit', function(event) {
            // Vérifier si les dates sont valides
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(document.getElementById('end_date').value);
            
            if (endDate <= startDate) {
                event.preventDefault();
                alert("La date de fin doit être postérieure à la date de début.");
            }
            
            // Vérifier que chaque question QCM a au moins une option correcte
            const questions = document.querySelectorAll('.question');
            let valid = true;
            
            questions.forEach((question, index) => {
                const questionType = question.querySelector('input[name="question_type[]"]').value;
                
                if (questionType === 'qcm') {
                    const correctOptions = question.querySelectorAll(`input[name="options[${index}][correct][]"]:checked`);
                    
                    if (correctOptions.length === 0) {
                        valid = false;
                        alert(`La question ${index + 1} doit avoir au moins une option correcte.`);
                    }
                }
            });
            
            if (!valid) {
                event.preventDefault();
            }
        });

        // Basculer entre le mode clair et sombre
        document.getElementById('themeToggle').addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            this.textContent = document.body.classList.contains('dark-mode') ? '☀️' : '🌙';
        });

        // Initialiser avec les questions existantes
        window.onload = function() {
            // Si aucune question n'existe, en ajouter une par défaut
            if (document.querySelectorAll('.question').length === 0) {
                addQuestion();
            }
            
            // Initialiser le compteur de questions
            questionCounter = document.querySelectorAll('.question').length;
            
            // Initialiser les compteurs d'options pour les questions existantes
            document.querySelectorAll('.question').forEach((question, index) => {
                const questionId = index + 1;
                const optionsContainer = question.querySelector('.options-container');
                
                if (optionsContainer) {
                    optionCounters[questionId] = optionsContainer.querySelectorAll('.option').length;
                }
            });
            
            // Appliquer le thème sauvegardé (si présent)
            if (localStorage.getItem('darkMode') === 'true') {
                document.body.classList.add('dark-mode');
                document.getElementById('themeToggle').textContent = '☀️';
            }
        };

        // Sauvegarder le thème
        window.addEventListener('beforeunload', function() {
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        });
    </script>
</body>
</html>