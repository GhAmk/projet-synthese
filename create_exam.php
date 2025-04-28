<?php
session_start();

// Vérifier si l'utilisateur est connecté et est un enseignant
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php"); // Rediriger vers la page de login
    exit();
}

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "exam_system");

if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Récupérer les groupes disponibles - Simplifié pour trouver tous les groupes
$teacher_id = $_SESSION['user_id'];
$groups_query = "SELECT * FROM groups"; // Récupère tous les groupes pour simplifier

$stmt_groups = $conn->prepare($groups_query);
$stmt_groups->execute();
$result_groups = $stmt_groups->get_result();

// Vérifier si des groupes existent
$groups_exist = $result_groups->num_rows > 0;

// Debug - Afficher les groupes disponibles
$debug_groups = [];
if ($groups_exist) {
    $result_groups_copy = $result_groups; // Copie pour ne pas affecter l'original
    while ($group = $result_groups_copy->fetch_assoc()) {
        $debug_groups[] = $group;
    }
    // Réinitialiser le pointeur de résultat pour l'utilisation ultérieure
    $result_groups->data_seek(0);
}

// Si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'] ?? '';
    $duration = $_POST['duration'];
    $attempts = $_POST['attempts'];
    $total_points = $_POST['total_points'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $teacher_id = $_SESSION['user_id'];
    $group_id = $_POST['group_id']; // Récupérer l'ID du groupe

    // Vérifier que la colonne group_id existe dans la table exams
    $column_check = $conn->query("SHOW COLUMNS FROM exams LIKE 'group_id'");
    $column_exists = $column_check->num_rows > 0;

    if (!$column_exists) {
        $error = "La colonne group_id n'existe pas dans la table exams. Veuillez mettre à jour votre structure de base de données.";
    } else {
        // Insertion de l'examen dans la base de données
        $stmt = $conn->prepare("INSERT INTO exams (title, description, teacher_id, start_time, end_time, duration, attempts, total_points, group_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissiiii", $title, $description, $teacher_id, $start_date, $end_date, $duration, $attempts, $total_points, $group_id);

        if ($stmt->execute()) {
            // Récupérer l'ID de l'examen créé
            $exam_id = $stmt->insert_id;

            // Créer le dossier pour les images si nécessaire
            $upload_dir = "uploads/exam_$exam_id/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Insérer les questions
            if (!empty($_POST['questions'])) {
                $questions = $_POST['questions'];
                $question_points = $_POST['question_points'];
                $question_types = $_POST['question_type'];

                for ($i = 0; $i < count($questions); $i++) {
                    $image_path = null;
                    
                    // Gérer l'upload de l'image si elle existe
                    if (isset($_FILES['question_images']['name'][$i]) && $_FILES['question_images']['name'][$i] != '') {
                        $image_name = $_FILES['question_images']['name'][$i];
                        $image_tmp = $_FILES['question_images']['tmp_name'][$i];
                        $image_ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
                        
                        // Vérifier le type de fichier
                        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                        if (in_array($image_ext, $allowed_extensions)) {
                            $new_image_name = uniqid() . '.' . $image_ext;
                            $image_path = $upload_dir . $new_image_name;
                            move_uploaded_file($image_tmp, $image_path);
                        }
                    }

                    $stmt_question = $conn->prepare("INSERT INTO questions (exam_id, question_text, question_type, image_path) VALUES (?, ?, ?, ?)");
                    $stmt_question->bind_param("isss", $exam_id, $questions[$i], $question_types[$i], $image_path);
                    $stmt_question->execute();
                    $question_id = $stmt_question->insert_id;
                    
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

            // Ajouter automatiquement les étudiants du groupe à l'examen
            if ($group_id) {
                $stmt_students = $conn->prepare("SELECT user_id FROM user_groups WHERE group_id = ? AND user_id IN (SELECT id FROM users WHERE role = 'student')");
                $stmt_students->bind_param("i", $group_id);
                $stmt_students->execute();
                $result_students = $stmt_students->get_result();
                
                while ($student = $result_students->fetch_assoc()) {
                    $student_id = $student['user_id'];
                    $stmt_enroll = $conn->prepare("INSERT INTO exam_students (exam_id, student_id, status) VALUES (?, ?, 'pending')");
                    $stmt_enroll->bind_param("ii", $exam_id, $student_id);
                    $stmt_enroll->execute();
                }
            }

            // Rediriger vers la page des examens
            header("Location: Teacher_dashboard.php");
            exit();
        } else {
            $error = "Erreur lors de la création de l'examen: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Examen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --secondary-color: #60a5fa;
            --accent-color: #10b981;
            --accent-hover: #059669;
            --dark-bg: #0f172a;
            --card-bg: #1e293b;
            --surface: #ffffff;
            --input-bg: #f8fafc;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --error-color: #ef4444;
            --warning-color: #f59e0b;
            --success-color: #10b981;
            --border: #e2e8f0;
            --feature-card-bg: rgba(255, 255, 255, 0.05);
            --feature-card-border: rgba(255, 255, 255, 0.1);
            --navbar-bg: rgba(15, 23, 42, 0.95);
            --hero-overlay: linear-gradient(135deg, rgba(79, 70, 229, 0.7), rgba(15, 23, 42, 0.9));
            --glow-effect: 0 0 15px rgba(79, 70, 229, 0.5);
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        [data-theme="dark"] {
            --primary-color: #6366f1;
            --primary-hover: #4f46e5;
            --secondary-color: #60a5fa;
            --accent-color: #10b981;
            --accent-hover: #059669;
            --dark-bg: #0f172a;
            --card-bg: #1e293b;
            --surface: #1e293b;
            --input-bg: #0f172a;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --error-color: #f87171;
            --warning-color: #fbbf24;
            --success-color: #34d399;
            --border: #334155;
            --feature-card-bg: rgba(255, 255, 255, 0.03);
            --feature-card-border: rgba(255, 255, 255, 0.05);
            --navbar-bg: rgba(15, 23, 42, 0.95);
            --hero-overlay: linear-gradient(135deg, rgba(67, 56, 202, 0.8), rgba(15, 23, 42, 0.95));
            --glow-effect: 0 0 20px rgba(99, 102, 241, 0.6);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
        }

        body {
            min-height: 100vh;
            background-color: var(--dark-bg);
            color: var(--text-primary);
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background */
        .page-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            filter: brightness(0.3);
            z-index: -2;
            animation: backgroundPan 30s ease infinite alternate;
        }

        @keyframes backgroundPan {
            0% { transform: scale(1) rotate(0deg); }
            100% { transform: scale(1.1) rotate(1deg); }
        }

        .page-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--hero-overlay);
            z-index: -1;
        }

        /* Particle Effects */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            pointer-events: none;
        }

        /* Navbar with Glassmorphism */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 1.25rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
            background: var(--navbar-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: var(--shadow-md);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-color);
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }

        .logo:before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(to right, var(--accent-color), var(--primary-color));
            transition: width 0.3s ease;
        }

        .logo:hover:before {
            width: 100%;
        }

        .logo span {
            color: var(--text-primary);
        }

        .logo i {
            font-size: 1.25rem;
            margin-right: 0.25rem;
            color: var(--accent-color);
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
            position: relative;
            padding: 0.5rem 0;
        }

        .nav-links a:before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(to right, var(--accent-color), var(--primary-color));
            transition: width 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--accent-color);
        }

        .nav-links a:hover:before {
            width: 100%;
        }

        .nav-links a i {
            margin-right: 0.5rem;
            font-size: 1rem;
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .theme-toggle {
            background: transparent;
            border: none;
            color: var(--text-primary);
            font-size: 1.2rem;
            cursor: pointer;
            margin-right: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease, color 0.3s ease;
        }

        .theme-toggle:hover {
            transform: rotate(15deg) scale(1.1);
            color: var(--accent-color);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.7s ease;
            z-index: -1;
        }

        .btn:hover:before {
            left: 100%;
        }

        .btn-back {
            background: transparent;
            border: 2px solid var(--accent-color);
            color: var(--accent-color);
        }

        .btn-back:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
            background-color: rgba(16, 185, 129, 0.1);
        }

        /* Main Container with Glass Effect */
        .exam-container {
            width: 100%;
            max-width: 900px;
            margin: 120px auto 60px;
            background-color: var(--surface);
            padding: 2.75rem;
            border-radius: 1.5rem;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--feature-card-border);
            animation: fadeIn 0.8s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .container-glow {
            position: absolute;
            width: 100%;
            height: 6px;
            background: linear-gradient(to right, var(--accent-color), var(--primary-color));
            top: 0;
            left: 0;
            border-radius: 1.5rem 1.5rem 0 0;
            box-shadow: var(--glow-effect);
        }

        /* Animated Floating Shapes */
        .floating-shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.15;
            z-index: -1;
        }

        .shape-1 {
            width: 300px;
            height: 300px;
            top: 10%;
            right: -150px;
            background: linear-gradient(135deg, var(--accent-color), var(--primary-color));
            animation: float1 20s infinite alternate ease-in-out;
        }

        .shape-2 {
            width: 250px;
            height: 250px;
            bottom: 10%;
            left: -100px;
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            animation: float2 18s infinite alternate-reverse ease-in-out;
        }

        .shape-3 {
            width: 200px;
            height: 200px;
            top: 30%;
            left: -80px;
            background: linear-gradient(135deg, var(--primary-color), #a855f7);
            animation: float3 15s infinite alternate ease-in-out;
        }

        @keyframes float1 {
            0% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(30px, 20px) rotate(180deg); }
            100% { transform: translate(50px, 50px) rotate(360deg); }
        }

        @keyframes float2 {
            0% { transform: translate(0, 0) scale(1) rotate(0deg); }
            50% { transform: translate(40px, -30px) scale(1.1) rotate(180deg); }
            100% { transform: translate(-20px, 60px) scale(0.9) rotate(360deg); }
        }

        @keyframes float3 {
            0% { transform: translate(0, 0) scale(0.8) rotate(0deg); }
            50% { transform: translate(-30px, 50px) scale(1) rotate(180deg); }
            100% { transform: translate(30px, -30px) scale(1.2) rotate(360deg); }
        }

        h2 {
            font-size: 2.25rem;
            font-weight: 800;
            margin-bottom: 2.5rem;
            background: linear-gradient(to right, var(--text-primary), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
            position: relative;
            display: inline-block;
            left: 50%;
            transform: translateX(-50%);
        }

        h2:after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(to right, var(--accent-color), var(--primary-color));
            border-radius: 3px;
            transform: scaleX(0);
            transform-origin: left;
            animation: borderAnimation 1s ease forwards 0.5s;
        }

        @keyframes borderAnimation {
            to { transform: scaleX(1); }
        }

        .form-group {
            margin-bottom: 1.75rem;
            position: relative;
        }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        input[type="text"],
        input[type="number"],
        input[type="datetime-local"],
        select {
            width: 100%;
            padding: 0.875rem 1.25rem;
            border-radius: 0.75rem;
            border: 1px solid var(--border);
            background-color: var(--input-bg);
            font-size: 1rem;
            color: var(--text-primary);
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
            transform: translateY(-2px);
        }

        input:hover,
        select:hover {
            border-color: var(--primary-hover);
        }

        /* Form Elements with Icons */
        .input-with-icon {
            position: relative;
        }

        .input-icon {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 1rem;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .input-with-icon input,
        .input-with-icon select {
            padding-left: 2.75rem;
        }

        .input-with-icon input:focus + .input-icon,
        .input-with-icon select:focus + .input-icon {
            color: var(--primary-color);
        }

        /* Enhanced Buttons */
        button,
        input[type="submit"] {
            background: linear-gradient(to right, var(--accent-color), var(--primary-color));
            color: white;
            font-weight: 600;
            padding: 0.875rem 1.75rem;
            border-radius: 0.75rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            z-index: 1;
        }

        button:before,
        input[type="submit"]:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.7s ease;
            z-index: -1;
        }

        button:hover:before,
        input[type="submit"]:hover:before {
            left: 100%;
        }

        button:hover,
        input[type="submit"]:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }

        button:active,
        input[type="submit"]:active {
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        button i,
        input[type="submit"] i {
            font-size: 1.1rem;
        }

        input[type="submit"] {
            background: linear-gradient(to right, #3b82f6, var(--primary-color));
            margin-top: 1rem;
            width: fit-content;
            align-self: center;
        }

        input[type="submit"]:hover {
            background: linear-gradient(to right, #2563eb, var(--primary-hover));
        }

        /* Questions Container Styling */
        #questions-container {
            margin-top: 2.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.75rem;
        }

        #questions-container h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
            position: relative;
            display: inline-block;
        }

        #questions-container h3:after {
            content: "";
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(to right, var(--accent-color), var(--primary-color));
            border-radius: 3px;
        }

        #add-question-btn {
            margin-top: 1.5rem;
            background: linear-gradient(to right, var(--primary-color), #4f46e5);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1rem 1.75rem;
            font-weight: 600;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        #add-question-btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.7s ease;
        }

        #add-question-btn:hover:before {
            left: 100%;
        }

        #add-question-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }

        /* Question Card Styling */
        .question {
            background-color: var(--input-bg);
            border: 1px solid var(--border);
            padding: 1.75rem;
            border-radius: 1rem;
            display: grid;
            gap: 1.25rem;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            animation: fadeIn 0.4s ease;
        }

        .question:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .question::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--accent-color), var(--primary-color));
            border-radius: 4px 0 0 4px;
        }

        .question-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
        }

        .question-number {
            font-weight: 700;
            font-size: 0.875rem;
            color: var(--primary-color);
            background-color: rgba(79, 70, 229, 0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Options Container Styling */
        .options-container {
            display: flex;
            flex-direction: column;
            gap: 0.875rem;
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border);
            animation: fadeIn 0.4s ease;
        }

        .option {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 1.25rem;
            align-items: center;
            background-color: rgba(255, 255, 255, 0.05);
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }

        .option:hover {
            background-color: rgba(79, 70, 229, 0.05);
            border-color: var(--primary-color);
            transform: translateX(5px);
        }

        .option button {
            background: linear-gradient(to right, var(--error-color), #b91c1c);
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .option button:hover {
            background: linear-gradient(to right, #b91c1c, var(--error-color));
        }

        .add-option-btn {
    background: linear-gradient(to right, var(--success-color), #059669);
    margin-top: 0.75rem;
    font-size: 0.875rem;
    align-self: flex-start;
    padding: 0.625rem 1.25rem;
}

.add-option-btn:hover {
    background: linear-gradient(to right, #059669, var(--success-color));
}

.option-input {
    flex: 1;
}

/* Form Row Styling */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

.remove-question-btn {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: linear-gradient(to right, var(--error-color), #b91c1c);
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    font-size: 1rem;
}

.remove-question-btn:hover {
    transform: rotate(90deg) scale(1.1);
}

.question-type-select {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.type-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

textarea {
    width: 100%;
    padding: 0.875rem 1.25rem;
    border-radius: 0.75rem;
    border: 1px solid var(--border);
    background-color: var(--input-bg);
    min-height: 100px;
    resize: vertical;
    font-size: 1rem;
    color: var(--text-primary);
    transition: all 0.3s ease;
    box-shadow: var(--shadow-sm);
}

textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
    transform: translateY(-2px);
}

textarea:hover {
    border-color: var(--primary-hover);
}

/* Form Footer */
.form-footer {
    margin-top: 3rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border);
}

/* Error Message Styling */
.error-message {
    background-color: rgba(239, 68, 68, 0.1);
    border: 1px solid var(--error-color);
    color: var(--error-color);
    padding: 1rem;
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    animation: shake 0.5s ease;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}

/* Success Message Styling */
.success-message {
    background-color: rgba(16, 185, 129, 0.1);
    border: 1px solid var(--success-color);
    color: var(--success-color);
    padding: 1rem;
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    animation: fadeIn 0.5s ease;
}

/* No Groups Message */
.no-groups-message {
    background-color: rgba(245, 158, 11, 0.1);
    border: 1px solid var(--warning-color);
    color: var(--warning-color);
    padding: 1rem;
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    text-align: center;
}

/* Loading Spinner */
.spinner {
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top: 3px solid var(--accent-color);
    width: 1.5rem;
    height: 1.5rem;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Debug Info */
.debug-info {
    margin-top: 2rem;
    padding: 1rem;
    background-color: rgba(0, 0, 0, 0.2);
    border-radius: 0.5rem;
    font-family: monospace;
    font-size: 0.8rem;
    color: var(--text-secondary);
    max-height: 200px;
    overflow-y: auto;
}

/* Responsive Adjustments */
@media (max-width: 900px) {
    .exam-container {
        width: 90%;
        padding: 2rem 1.5rem;
        margin-top: 100px;
    }
    
    h2 {
        font-size: 1.75rem;
    }
    
    .navbar {
        padding: 1rem;
    }
    
    .nav-links {
        display: none;
    }
}

@media (max-width: 480px) {
    .exam-container {
        padding: 1.75rem 1.25rem;
    }
    
    h2 {
        font-size: 1.5rem;
    }
    
    .logo {
        font-size: 1.25rem;
    }
    
    .form-group {
        margin-bottom: 1.25rem;
    }
    
    button, input[type="submit"] {
        padding: 0.75rem 1.25rem;
    }
}

.question-image-input {
    margin: 10px 0;
    padding: 8px;
    border: 1px solid var(--border);
    border-radius: 4px;
    background-color: var(--input-bg);
    color: var(--text-primary);
    width: 100%;
}

.question-image-preview {
    margin: 10px 0;
    max-width: 300px;
    max-height: 200px;
    display: none;
}

.question-image-preview img {
    max-width: 100%;
    max-height: 100%;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.image-upload-container {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.remove-image-btn {
    padding: 8px 12px;
    background-color: var(--error-color);
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.3s ease;
}

.remove-image-btn:hover {
    background-color: #dc2626;
    transform: translateY(-2px);
}

.remove-image-btn i {
    font-size: 14px;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const addQuestionBtn = document.getElementById('add-question-btn');
    const questionsContainer = document.getElementById('questions-container');
    let questionCount = 0;
    
    // Function to create a new question
    function addQuestion() {
        questionCount++;
        const questionDiv = document.createElement('div');
        questionDiv.className = 'question';
        questionDiv.dataset.questionId = questionCount;
        
        questionDiv.innerHTML = `
            <div class="question-header">
                <div class="question-number">
                    <i class="fas fa-question-circle"></i>
                    Question ${questionCount}
                </div>
                <select name="question_type[${questionCount-1}]" class="question-type-selector" onchange="handleQuestionTypeChange(this)">
                    <option value="text">Réponse Texte</option>
                    <option value="qcm">QCM</option>
                    <option value="code">Code</option>
                </select>
            </div>
            <div class="form-group">
                <label for="question-${questionCount}">Texte de la question</label>
                <textarea id="question-${questionCount}" name="questions[${questionCount-1}]" required></textarea>
            </div>
            <div class="form-group">
                <label for="points-${questionCount}">Points</label>
                <input type="number" id="points-${questionCount}" name="question_points[${questionCount-1}]" value="1" min="1" required>
            </div>
            <div class="form-group">
                <label for="image-${questionCount}">Image (optionnelle)</label>
                <div class="image-upload-container">
                    <input type="file" id="image-${questionCount}" name="question_images[]" accept="image/*" class="question-image-input">
                    <button type="button" class="remove-image-btn" style="display: none;">
                        <i class="fas fa-times"></i> Supprimer l'image
                    </button>
                </div>
            </div>
            <div class="options-container" style="display: none;" data-for-question="${questionCount}">
                <h4>Options de réponse</h4>
                <button type="button" class="add-option-btn" onclick="addOption(${questionCount})">
                    <i class="fas fa-plus"></i> Ajouter une option
                </button>
            </div>
            <button type="button" class="remove-question-btn" onclick="removeQuestion(this)">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        questionsContainer.appendChild(questionDiv);
    }
    
    // Add first question on page load
    addQuestion();
    
    // Add question button click handler
    addQuestionBtn.addEventListener('click', function() {
        addQuestion();
    });
    
    // Global functions for handling questions and options
    window.handleQuestionTypeChange = function(selectElement) {
        const questionDiv = selectElement.closest('.question');
        const optionsContainer = questionDiv.querySelector('.options-container');
        
        // Réinitialiser tous les conteneurs
        optionsContainer.style.display = 'none';
        
        if (selectElement.value === 'qcm') {
            optionsContainer.style.display = 'block';
            // Add initial options if there are none
            if (optionsContainer.querySelectorAll('.option').length === 0) {
                addOption(questionDiv.dataset.questionId);
                addOption(questionDiv.dataset.questionId);
                addOption(questionDiv.dataset.questionId);
            }
        }
    };
    
    window.addOption = function(questionId) {
        const optionsContainer = document.querySelector(`.options-container[data-for-question="${questionId}"]`);
        const optionCount = optionsContainer.querySelectorAll('.option').length;
        
        const optionDiv = document.createElement('div');
        optionDiv.className = 'option';
        
        optionDiv.innerHTML = `
            <input type="text" class="option-input" name="options[${questionId-1}][text][${optionCount}]" placeholder="Option de réponse" required>
            <label class="checkbox-container">
                <input type="checkbox" name="options[${questionId-1}][correct][${optionCount}]" value="1">
                <span class="checkmark"></span>
                Correcte
            </label>
            <button type="button" class="remove-option-btn" onclick="removeOption(this)">
                <i class="fas fa-trash-alt"></i>
            </button>
        `;
        
        // Insert before the add option button
        optionsContainer.insertBefore(optionDiv, optionsContainer.querySelector('.add-option-btn'));
    };
    
    window.removeOption = function(button) {
        const optionDiv = button.closest('.option');
        optionDiv.remove();
    };
    
    window.removeQuestion = function(button) {
        const questionDiv = button.closest('.question');
        
        // Animation before removal
        questionDiv.style.opacity = '0';
        questionDiv.style.transform = 'scale(0.8)';
        
        setTimeout(function() {
            questionDiv.remove();
            
            // Update question numbers
            const questions = document.querySelectorAll('.question');
            questions.forEach((q, index) => {
                q.querySelector('.question-number').textContent = `Question ${index + 1}`;
            });
        }, 300);
    };
    
    // Theme toggle functionality et autres fonctions JS...
});
</script>
</head>
<body>
    <div class="page-bg"></div>
    <div class="page-overlay"></div>
    
    <!-- Floating shapes for background effect -->
    <div class="floating-shape shape-1"></div>
    <div class="floating-shape shape-2"></div>
    <div class="floating-shape shape-3"></div>
    
    <!-- Navbar -->
    <nav class="navbar">
        <a href="Teacher_dashboard.php" class="logo">
            <i class="fas fa-graduation-cap"></i>
            Exam<span>+</span>
        </a>
        
        <div class="nav-links">
            <a href="Teacher_dashboard.php"><i class="fas fa-home"></i> Accueil</a>
            <a href="create_exam.php" class="active"><i class="fas fa-plus-circle"></i> Créer Examen</a>
            <a href="view_results.php"><i class="fas fa-chart-bar"></i> Résultats</a>
        </div>
        
        <div class="auth-buttons">
            <button class="theme-toggle">
                <i class="fas fa-sun"></i>
            </button>
            <a href="logout.php" class="btn btn-back">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </nav>
    
    <div class="exam-container">
        <div class="container-glow"></div>
        <h2>Créer un Nouvel Examen</h2>
        
        <?php if(isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if(!$groups_exist): ?>
            <div class="no-groups-message">
                <i class="fas fa-exclamation-circle"></i> Aucun groupe n'est disponible. Veuillez d'abord créer un groupe.
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label for="title">Titre de l'examen</label>
                    <div class="input-with-icon">
                        <input type="text" id="title" name="title" required>
                        <i class="fas fa-heading input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="group_id">Groupe d'étudiants</label>
                    <div class="input-with-icon">
                        <select id="group_id" name="group_id" required>
                            <?php if($groups_exist): ?>
                                <?php while($group = $result_groups->fetch_assoc()): ?>
                                    <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['name']); ?></option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="" disabled>Aucun groupe disponible</option>
                            <?php endif; ?>
                        </select>
                        <i class="fas fa-users input-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description (optionnelle)</label>
                <textarea id="description" name="description"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Date de début</label>
                    <div class="input-with-icon">
                        <input type="datetime-local" id="start_date" name="start_date" required>
                        <i class="fas fa-calendar-alt input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="end_date">Date de fin</label>
                    <div class="input-with-icon">
                        <input type="datetime-local" id="end_date" name="end_date" required>
                        <i class="fas fa-calendar-check input-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="duration">Durée (minutes)</label>
                    <div class="input-with-icon">
                        <input type="number" id="duration" name="duration" min="1" value="60" required>
                        <i class="fas fa-clock input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="attempts">Nombre de tentatives autorisées</label>
                    <div class="input-with-icon">
                        <input type="number" id="attempts" name="attempts" min="1" value="1" required>
                        <i class="fas fa-redo input-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="total_points">Points totaux</label>
                <div class="input-with-icon">
                    <input type="number" id="total_points" name="total_points" min="1" value="100" required>
                    <i class="fas fa-star input-icon"></i>
                </div>
            </div>
            
            <h3>Questions</h3>
            <div id="questions-container">
                <!-- Les questions seront ajoutées ici dynamiquement via JavaScript -->
            </div>
            
            <button type="button" id="add-question-btn">
                <i class="fas fa-plus"></i> Ajouter une question
            </button>
            
            <div class="form-footer">
                <a href="Teacher_dashboard.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Annuler
                </a>
                <input type="submit" value="Créer l'examen" id="submit-btn">
            </div>
        </form>
    </div>
</body>
</html>