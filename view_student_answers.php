<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier que l'utilisateur est connecté et qu'il est un enseignant
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "exam_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Verify we're using the correct database
$db_name = $conn->query("SELECT DATABASE()")->fetch_row()[0];
if ($db_name !== 'exam_system') {
    die("Error: Connected to wrong database ($db_name). Should be 'exam_system'");
}

// Vérifier que les paramètres nécessaires sont présents
if (!isset($_GET['exam_id']) || !isset($_GET['student_id'])) {
    header("Location: teacher_dashboard.php");
    exit();
}

$exam_id = $_GET['exam_id'];
$student_id = $_GET['student_id'];

// Récupérer les informations sur l'examen
$exam_query = "SELECT e.title, e.description, u.name as student_name, e.total_points
               FROM exams e
               JOIN users u ON u.id = ?
               WHERE e.id = ?";
$exam_stmt = $conn->prepare($exam_query);
$exam_stmt->bind_param("ii", $student_id, $exam_id);
$exam_stmt->execute();
$exam_result = $exam_stmt->get_result();

if ($exam_result->num_rows === 0) {
    header("Location: teacher_dashboard.php");
    exit();
}

$exam_info = $exam_result->fetch_assoc();

// Récupérer le score actuel de l'étudiant
$score_query = "SELECT score FROM exam_students 
                WHERE exam_id = ? AND student_id = ? AND status = 'completed'";
$score_stmt = $conn->prepare($score_query);
$score_stmt->bind_param("ii", $exam_id, $student_id);
$score_stmt->execute();
$score_result = $score_stmt->get_result();
$student_score = $score_result->fetch_assoc();

// Récupérer les notes individuelles des questions si elles existent déjà
$question_scores_query = "SELECT question_id, score FROM question_scores 
                          WHERE exam_id = ? AND student_id = ?";
$question_scores_stmt = $conn->prepare($question_scores_query);
$question_scores_stmt->bind_param("ii", $exam_id, $student_id);
$question_scores_stmt->execute();
$question_scores_result = $question_scores_stmt->get_result();

$question_scores = [];
while ($row = $question_scores_result->fetch_assoc()) {
    $question_scores[$row['question_id']] = $row['score'];
}

// Récupérer les commentaires des enseignants s'ils existent
$comments_query = "SELECT question_id, comment FROM question_scores 
                  WHERE exam_id = ? AND student_id = ?";
$comments_stmt = $conn->prepare($comments_query);
$comments_stmt->bind_param("ii", $exam_id, $student_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();

$teacher_comments = [];
while ($row = $comments_result->fetch_assoc()) {
    $teacher_comments[$row['question_id']] = $row['comment'];
}

// Traiter la mise à jour des scores si un formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_scores'])) {
    // Commencer une transaction
    $conn->begin_transaction();
    
    try {
        $total_score = 0;
        $total_possible = 0;
        
        // Parcourir toutes les questions et leurs scores attribués
        foreach ($_POST['question_score'] as $question_id => $score) {
            $points = $_POST['question_points'][$question_id];
            $total_possible += $points;
            
            // Valider le score
            if (!is_numeric($score) || $score < 0 || $score > $points) {
                throw new Exception("Score invalide pour la question ID $question_id. Doit être entre 0 et $points.");
            }
            
            $total_score += $score;
            
            // Vérifier si une note existe déjà pour cette question
            $check_query = "SELECT id FROM question_scores 
                            WHERE exam_id = ? AND student_id = ? AND question_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("iii", $exam_id, $student_id, $question_id);
            $check_stmt->execute();
            $exists = $check_stmt->get_result()->num_rows > 0;
            
            if ($exists) {
                // Mettre à jour le score existant
                $update_query = "UPDATE question_scores SET score = ?, comment = ?
                                WHERE exam_id = ? AND student_id = ? AND question_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $comment = isset($_POST['comment'][$question_id]) ? trim($_POST['comment'][$question_id]) : '';
                $update_stmt->bind_param("dsiii", $score, $comment, $exam_id, $student_id, $question_id);
                $update_stmt->execute();
            } else {
                // Insérer un nouveau score
                $insert_query = "INSERT INTO question_scores (exam_id, student_id, question_id, score, comment) 
                                VALUES (?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                $comment = isset($_POST['comment'][$question_id]) ? trim($_POST['comment'][$question_id]) : '';
                $insert_stmt->bind_param("iiids", $exam_id, $student_id, $question_id, $score, $comment);
                $insert_stmt->execute();
            }
        }
        
        // Calculer le pourcentage
        $percentage_score = ($total_possible > 0) ? ($total_score / $total_possible) * 100 : 0;
        
        // Mettre à jour le score global dans exam_students
        $update_total_query = "UPDATE exam_students SET score = ? 
                              WHERE exam_id = ? AND student_id = ?";
        $update_total_stmt = $conn->prepare($update_total_query);
        $update_total_stmt->bind_param("dii", $percentage_score, $exam_id, $student_id);
        $update_total_stmt->execute();
        
        // Tout s'est bien passé, on valide la transaction
        $conn->commit();
        $success_message = "Évaluation enregistrée avec succès. Score total: " . number_format($percentage_score, 1) . "%";
        $student_score['score'] = $percentage_score;
        
        // Mettre à jour le tableau des scores
        $question_scores_stmt->execute();
        $question_scores_result = $question_scores_stmt->get_result();
        $question_scores = [];
        while ($row = $question_scores_result->fetch_assoc()) {
            $question_scores[$row['question_id']] = $row['score'];
        }
        
        // Mettre à jour les commentaires
        $comments_stmt->execute();
        $comments_result = $comments_stmt->get_result();
        $teacher_comments = [];
        while ($row = $comments_result->fetch_assoc()) {
            $teacher_comments[$row['question_id']] = $row['comment'];
        }
        
    } catch (Exception $e) {
        // En cas d'erreur, on annule la transaction
        $conn->rollback();
        $error_message = "Erreur: " . $e->getMessage();
    }
}

// Récupérer toutes les questions de l'examen avec les réponses de l'étudiant
$questions_query = "SELECT q.id, q.question_text, q.question_type, qp.points, 
                   GROUP_CONCAT(DISTINCT sa.answer) as student_answers
                   FROM questions q
                   LEFT JOIN question_points qp ON q.id = qp.question_id
                   LEFT JOIN student_answers sa ON q.id = sa.question_id AND sa.student_id = ? AND sa.exam_id = ?
                   WHERE q.exam_id = ?
                   GROUP BY q.id
                   ORDER BY q.id";
$questions_stmt = $conn->prepare($questions_query);
$questions_stmt->bind_param("iii", $student_id, $exam_id, $exam_id);
$questions_stmt->execute();
$questions_result = $questions_stmt->get_result();

// Tableau pour stocker toutes les questions et réponses
$questions = [];
$total_points = 0;

while ($question = $questions_result->fetch_assoc()) {
    // Ajouter le score actuel de cette question si disponible
    $question['current_score'] = isset($question_scores[$question['id']]) ? $question_scores[$question['id']] : 0;
    
    // Ajouter le commentaire s'il existe
    $question['teacher_comment'] = isset($teacher_comments[$question['id']]) ? $teacher_comments[$question['id']] : '';
    
    // Ajouter le nombre de points au total
    $total_points += $question['points'];
    
    // Pour les questions QCM, récupérer tous les choix
    if ($question['question_type'] === 'qcm') {
        $choices_query = "SELECT c.id, c.choice_text, c.is_correct 
                         FROM choices c 
                         WHERE c.question_id = ?
                         ORDER BY c.id";
        $choices_stmt = $conn->prepare($choices_query);
        $choices_stmt->bind_param("i", $question['id']);
        $choices_stmt->execute();
        $choices_result = $choices_stmt->get_result();
        
        $choices = [];
        while ($choice = $choices_result->fetch_assoc()) {
            $choices[] = $choice;
        }
        
        $question['choices'] = $choices;
        
        // Convertir la chaîne des réponses en tableau
        if ($question['student_answers']) {
            $question['student_answers'] = explode(',', $question['student_answers']);
        } else {
            $question['student_answers'] = [];
        }
    }
    
    $questions[] = $question;
}

// Charger le mode d'affichage depuis la session
$darkMode = isset($_SESSION['dark_mode']) && $_SESSION['dark_mode'] === true;

// Traiter le changement de mode d'affichage
if (isset($_POST['toggle_theme'])) {
    $darkMode = !$darkMode;
    $_SESSION['dark_mode'] = $darkMode;
    
    // Rediriger pour éviter la résoumission du formulaire
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $darkMode ? 'dark' : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Évaluation des réponses de l'étudiant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <style>
    /* VARIABLES & THEMES */
:root {
    /* Palette de couleurs principale */
    --primary-color: #4f46e5;
    --primary-light: #818cf8;
    --primary-dark: #3730a3;
    --secondary-color: #64748b;
    --success-color: #10b981;
    --danger-color: #ef4444;
    --warning-color: #f59e0b;
    --info-color: #06b6d4;
    
    /* Couleurs de fond et texte */
    --body-bg: #f9fafb;
    --card-bg: #ffffff;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --text-light: #94a3b8;
    
    /* Ombres et transitions */
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    
    --transition-base: all 0.3s ease;
    --transition-bounce: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    
    /* Dimensions */
    --border-radius-sm: 0.25rem;
    --border-radius: 0.5rem;
    --border-radius-lg: 0.75rem;
    --border-radius-xl: 1rem;
    --border-radius-2xl: 1.5rem;
    --border-radius-full: 9999px;
    
    /* Espacement */
    --spacing-1: 0.25rem;
    --spacing-2: 0.5rem;
    --spacing-3: 0.75rem;
    --spacing-4: 1rem;
    --spacing-5: 1.25rem;
    --spacing-6: 1.5rem;
    --spacing-8: 2rem;
    --spacing-10: 2.5rem;
    --spacing-12: 3rem;
    --spacing-16: 4rem;
    
    /* Typographie */
    --font-sans: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    --font-heading: 'Manrope', var(--font-sans);
    --font-mono: 'SF Mono', SFMono-Regular, ui-monospace, Menlo, Monaco, Consolas, monospace;
    
    /* Header */
    --header-height: 64px;
}

[data-theme="dark"] {
    --primary-color: #6366f1;
    --primary-light: #a5b4fc;
    --primary-dark: #4f46e5;
    --secondary-color: #94a3b8;
    --success-color: #34d399;
    --danger-color: #f87171;
    --warning-color: #fbbf24;
    --info-color: #22d3ee;
    
    --body-bg: #0f172a;
    --card-bg: #1e293b;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-light: #94a3b8;
}

/* RESET & BASE STYLES */
*, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

html {
    font-size: 16px;
    scroll-behavior: smooth;
}

body {
    font-family: var(--font-sans);
    background-color: var(--body-bg);
    color: var(--text-primary);
    line-height: 1.6;
    overflow-x: hidden;
    position: relative;
    min-height: 100vh;
    font-weight: 400;
    /* Subtle pattern background */
    background-image: 
        radial-gradient(circle at 25px 25px, rgba(79, 70, 229, 0.03) 2px, transparent 0),
        radial-gradient(circle at 75px 75px, rgba(79, 70, 229, 0.03) 2px, transparent 0);
    background-size: 100px 100px;
}

/* TYPOGRAPHY */
h1, h2, h3, h4, h5, h6 {
    font-family: var(--font-heading);
    font-weight: 700;
    line-height: 1.2;
    color: var(--text-primary);
    margin-bottom: var(--spacing-4);
}

h1 { font-size: 2.25rem; /* 36px */ }
h2 { font-size: 1.875rem; /* 30px */ }
h3 { font-size: 1.5rem; /* 24px */ }
h4 { font-size: 1.25rem; /* 20px */ }
h5 { font-size: 1.125rem; /* 18px */ }
h6 { font-size: 1rem; /* 16px */ }

p {
    margin-bottom: var(--spacing-4);
}

a {
    color: var(--primary-color);
    text-decoration: none;
    transition: var(--transition-base);
}

a:hover {
    color: var(--primary-dark);
    text-decoration: underline;
}

/* LAYOUT */
.container {
    width: 100%;
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 var(--spacing-4);
}

.row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -var(--spacing-4);
}

.col {
    flex: 1 0 0%;
    padding: 0 var(--spacing-4);
}

/* Modern header with frosted glass effect */
.header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: var(--header-height);
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(229, 231, 235, 0.8);
    z-index: 1000;
    display: flex;
    align-items: center;
    padding: 0 var(--spacing-4);
    
    /* Subtle gradient overlay */
    background-image: linear-gradient(
        to right,
        rgba(79, 70, 229, 0.03),
        rgba(79, 70, 229, 0.05)
    );
}

[data-theme="dark"] .header {
    background: rgba(30, 41, 59, 0.8);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(51, 65, 85, 0.8);
}

.content-wrapper {
    margin-top: calc(var(--header-height) + var(--spacing-8));
    padding-bottom: var(--spacing-16);
}

/* CARDS */
.card {
    background-color: var(--card-bg);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    overflow: hidden;
    margin-bottom: var(--spacing-6);
    transition: var(--transition-base);
    border: 1px solid rgba(229, 231, 235, 0.2);
    position: relative;
}

.card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-3px);
}

.card-header {
    padding: var(--spacing-5) var(--spacing-6);
    background-color: rgba(79, 70, 229, 0.03);
    border-bottom: 1px solid rgba(229, 231, 235, 0.2);
    position: relative;
    overflow: hidden;
}

.card-body {
    padding: var(--spacing-6);
}

/* QUESTION CARD */
.question-card {
    border-radius: var(--border-radius-lg);
    margin-bottom: var(--spacing-8);
    overflow: hidden;
    position: relative;
}

.question-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--primary-color);
    border-radius: var(--border-radius-sm) 0 0 var(--border-radius-sm);
}

.question-header {
    padding: var(--spacing-5) var(--spacing-6);
    background: linear-gradient(to right, rgba(79, 70, 229, 0.07), rgba(79, 70, 229, 0.01));
    position: relative;
    border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0;
}

.question-content {
    padding: var(--spacing-6);
    background-color: var(--card-bg);
}

.question-text {
    font-weight: 500;
    font-size: 1.05rem;
    line-height: 1.7;
    margin-bottom: var(--spacing-5);
    color: var(--text-primary);
}

/* STUDENT ANSWER STYLING */
.student-answer {
    background-color: rgba(79, 70, 229, 0.03);
    border-radius: var(--border-radius);
    padding: var(--spacing-5);
    margin: var(--spacing-5) 0;
    position: relative;
    border-left: 3px solid var(--secondary-color);
}

.student-answer.correct {
    border-left-color: var(--success-color);
    background-color: rgba(16, 185, 129, 0.05);
}

.student-answer.incorrect {
    border-left-color: var(--danger-color);
    background-color: rgba(239, 68, 68, 0.05);
}

.answer-content {
    font-size: 0.95rem;
    line-height: 1.6;
    color: var(--text-secondary);
    margin-top: var(--spacing-3);
}

/* FORM ELEMENTS */
.form-control {
    display: block;
    width: 100%;
    padding: var(--spacing-3) var(--spacing-4);
    font-size: 1rem;
    line-height: 1.5;
    color: var(--text-primary);
    background-color: var(--card-bg);
    background-clip: padding-box;
    border: 1px solid rgba(229, 231, 235, 0.7);
    border-radius: var(--border-radius);
    transition: var(--transition-base);
}

.form-control:focus {
    border-color: var(--primary-light);
    outline: 0;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
}

.form-label {
    margin-bottom: var(--spacing-2);
    font-weight: 500;
    color: var(--text-primary);
    display: block;
}

textarea.form-control {
    min-height: 100px;
    resize: vertical;
}

.input-group {
    position: relative;
    display: flex;
    flex-wrap: wrap;
    align-items: stretch;
    width: 100%;
}

.input-group > .form-control {
    position: relative;
    flex: 1 1 auto;
    width: 1%;
    min-width: 0;
}

.input-group-text {
    display: flex;
    align-items: center;
    padding: var(--spacing-3) var(--spacing-4);
    font-weight: 500;
    line-height: 1.5;
    color: var(--text-secondary);
    text-align: center;
    white-space: nowrap;
    background-color: rgba(229, 231, 235, 0.3);
    border: 1px solid rgba(229, 231, 235, 0.7);
    border-radius: var(--border-radius);
}

.input-group > :not(:first-child) {
    margin-left: -1px;
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

.input-group > :not(:last-child) {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

/* SCORE COMPONENTS */
.score-container {
    background: linear-gradient(to right, rgba(79, 70, 229, 0.03), rgba(79, 70, 229, 0.01));
    border-radius: var(--border-radius);
    padding: var(--spacing-5) var(--spacing-6);
    margin-bottom: var(--spacing-5);
    transition: var(--transition-base);
    border: 1px solid rgba(79, 70, 229, 0.1);
}

.score-container:hover {
    box-shadow: var(--shadow-sm);
}

.score-input {
    font-weight: 600;
    text-align: center;
    width: 100px;
    font-size: 1.1rem;
    padding: var(--spacing-2) var(--spacing-3);
    color: var(--primary-color);
}

.radial-progress {
    position: relative;
    width: 140px;
    height: 140px;
}

.circle-bg {
    fill: none;
    stroke: rgba(203, 213, 225, 0.3);
    stroke-width: 8;
}

.circle {
    fill: none;
    stroke-width: 8;
    stroke-linecap: round;
    transition: stroke-dashoffset 1s ease;
    stroke: url(#gradient);
}

.percentage {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--primary-color);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.percentage span {
    font-size: 0.85rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.progress {
    display: flex;
    height: 0.8rem;
    overflow: hidden;
    font-size: 0.75rem;
    background-color: rgba(203, 213, 225, 0.3);
    border-radius: var(--border-radius-full);
}

.progress-bar {
    display: flex;
    flex-direction: column;
    justify-content: center;
    overflow: hidden;
    color: #fff;
    text-align: center;
    white-space: nowrap;
    background: linear-gradient(to right, var(--primary-color), var(--primary-light));
    transition: width 0.6s ease;
}

/* BUTTONS */
.btn {
    display: inline-block;
    font-weight: 500;
    text-align: center;
    vertical-align: middle;
    cursor: pointer;
    user-select: none;
    padding: var(--spacing-3) var(--spacing-6);
    font-size: 1rem;
    line-height: 1.5;
    border-radius: var(--border-radius);
    transition: var(--transition-base);
    border: none;
    position: relative;
    overflow: hidden;
}

.btn::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 150%;
    height: 150%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0) 70%);
    transform: translate(-50%, -50%) scale(0);
    opacity: 0;
    transition: transform 0.5s ease-out, opacity 0.5s ease-out;
    pointer-events: none;
}

.btn:active::after {
    transform: translate(-50%, -50%) scale(1);
    opacity: 1;
    transition: 0s;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
    box-shadow: 0 4px 6px rgba(79, 70, 229, 0.25);
}

.btn-primary:hover {
    background-color: var(--primary-dark);
    box-shadow: 0 7px 14px rgba(79, 70, 229, 0.3);
    transform: translateY(-2px);
}

.btn-success {
    background-color: var(--success-color);
    color: white;
    box-shadow: 0 4px 6px rgba(16, 185, 129, 0.25);
}

.btn-success:hover {
    background-color: #0da271;
    box-shadow: 0 7px 14px rgba(16, 185, 129, 0.3);
    transform: translateY(-2px);
}

.btn-danger {
    background-color: var(--danger-color);
    color: white;
    box-shadow: 0 4px 6px rgba(239, 68, 68, 0.25);
}

.btn-danger:hover {
    background-color: #dc2626;
    box-shadow: 0 7px 14px rgba(239, 68, 68, 0.3);
    transform: translateY(-2px);
}

.btn-light {
    background-color: #f9fafb;
    color: var(--text-primary);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(229, 231, 235, 0.7);
}

.btn-light:hover {
    background-color: #f3f4f6;
    box-shadow: 0 7px 14px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.btn-lg {
    padding: var(--spacing-4) var(--spacing-8);
    font-size: 1.125rem;
    border-radius: var(--border-radius-lg);
}

.btn-sm {
    padding: var(--spacing-2) var(--spacing-4);
    font-size: 0.875rem;
    border-radius: var(--border-radius-sm);
}

/* BADGES */
.badge {
    display: inline-block;
    padding: var(--spacing-1) var(--spacing-3);
    font-size: 0.75rem;
    font-weight: 500;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: var(--border-radius-full);
    color: #fff;
}

.badge-primary {
    background-color: var(--primary-color);
}

.badge-success {
    background-color: var(--success-color);
}

.badge-danger {
    background-color: var(--danger-color);
}

.badge-warning {
    background-color: var(--warning-color);
}

.badge-info {
    background-color: var(--info-color);
}

.badge-light {
    background-color: #f3f4f6;
    color: var(--text-primary);
}

.badge-points {
    display: inline-flex;
    align-items: center;
    padding: var(--spacing-2) var(--spacing-4);
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--primary-dark);
    background-color: rgba(79, 70, 229, 0.1);
    border-radius: var(--border-radius-full);
}

.badge-points i {
    color: var(--primary-color);
    margin-right: var(--spacing-2);
}

/* COMMENTS AREA */
.comments-area {
    background-color: rgba(79, 70, 229, 0.03);
    border-radius: var(--border-radius);
    padding: var(--spacing-5);
    margin-top: var(--spacing-4);
    border: 1px solid rgba(79, 70, 229, 0.1);
}

/* ALERTS */
.alert {
    position: relative;
    padding: var(--spacing-4) var(--spacing-5);
    margin-bottom: var(--spacing-5);
    border-radius: var(--border-radius);
    border-left: 4px solid transparent;
}

.alert-success {
    background-color: rgba(16, 185, 129, 0.1);
    color: var(--success-color);
    border-left-color: var(--success-color);
}

.alert-danger {
    background-color: rgba(239, 68, 68, 0.1);
    color: var(--danger-color);
    border-left-color: var(--danger-color);
}

/* CHOICES LIST */
.choices-list {
    margin-bottom: var(--spacing-5);
}

.form-check {
    display: flex;
    align-items: flex-start;
    margin-bottom: var(--spacing-3);
    padding-left: var(--spacing-6);
    position: relative;
}

.form-check-input {
    width: 1.25rem;
    height: 1.25rem;
    margin-top: 0.25rem;
    margin-left: -var(--spacing-6);
    background-color: var(--card-bg);
    border: 1px solid rgba(203, 213, 225, 0.7);
    border-radius: var(--spacing-1);
    transition: var(--transition-base);
    appearance: none;
    color-adjust: exact;
}

.form-check-input:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10l3 3l6-6'/%3e%3c/svg%3e");
    background-position: center;
    background-repeat: no-repeat;
    background-size: 75%;
}

.form-check-input:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.form-check-label {
    margin-bottom: 0;
    font-size: 0.95rem;
    color: var(--text-secondary);
}

/* THEME TOGGLE */
.theme-toggle {
    position: fixed;
    bottom: var(--spacing-6);
    right: var(--spacing-6);
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: var(--card-bg);
    color: var(--text-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: var(--shadow-lg);
    z-index: 100;
    border: none;
    transition: var(--transition-bounce);
}

.theme-toggle:hover {
    transform: translateY(-5px) rotate(10deg);
    box-shadow: var(--shadow-xl);
}

/* UTILITY CLASSES */
.mb-0 { margin-bottom: 0 !important; }
.mb-1 { margin-bottom: var(--spacing-1) !important; }
.mb-2 { margin-bottom: var(--spacing-2) !important; }
.mb-3 { margin-bottom: var(--spacing-3) !important; }
.mb-4 { margin-bottom: var(--spacing-4) !important; }
.mb-5 { margin-bottom: var(--spacing-5) !important; }

.mt-0 { margin-top: 0 !important; }
.mt-1 { margin-top: var(--spacing-1) !important; }
.mt-2 { margin-top: var(--spacing-2) !important; }
.mt-3 { margin-top: var(--spacing-3) !important; }
.mt-4 { margin-top: var(--spacing-4) !important; }
.mt-5 { margin-top: var(--spacing-5) !important; }

.me-1 { margin-right: var(--spacing-1) !important; }
.me-2 { margin-right: var(--spacing-2) !important; }
.me-3 { margin-right: var(--spacing-3) !important; }
.me-4 { margin-right: var(--spacing-4) !important; }

.ms-1 { margin-left: var(--spacing-1) !important; }
.ms-2 { margin-left: var(--spacing-2) !important; }
.ms-3 { margin-left: var(--spacing-3) !important; }
.ms-4 { margin-left: var(--spacing-4) !important; }

.p-0 { padding: 0 !important; }
.p-1 { padding: var(--spacing-1) !important; }
.p-2 { padding: var(--spacing-2) !important; }
.p-3 { padding: var(--spacing-3) !important; }
.p-4 { padding: var(--spacing-4) !important; }
.p-5 { padding: var(--spacing-5) !important; }

.text-center { text-align: center !important; }
.text-start { text-align: left !important; }
.text-end { text-align: right !important; }

.d-flex { display: flex !important; }
.flex-column { flex-direction: column !important; }
.justify-content-start { justify-content: flex-start !important; }
.justify-content-end { justify-content: flex-end !important; }
.justify-content-center { justify-content: center !important; }
.justify-content-between { justify-content: space-between !important; }
.align-items-start { align-items: flex-start !important; }
.align-items-center { align-items: center !important; }
.align-items-end { align-items: flex-end !important; }

.text-primary { color: var(--primary-color) !important; }
.text-success { color: var(--success-color) !important; }
.text-danger { color: var(--danger-color) !important; }
.text-warning { color: var(--warning-color) !important; }
.text-info { color: var(--info-color) !important; }
.text-secondary { color: var(--text-secondary) !important; }
.text-muted { color: var(--text-light) !important; }

/* ANIMATIONS */
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

.animate__fadeIn {
    animation: fadeIn 0.5s ease-out forwards;
}

/* Add this at the end of your CSS file */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}

/* RESPONSIVE */
@media (max-width: 992px) {
    html {
        font-size: 15px;
    }
    
    .radial-progress {
        width: 120px;
        height: 120px;
    }
    
    .percentage {
        font-size: 1.5rem;
    }
}

@media (max-width: 768px) {
    .card {
        margin-bottom: var(--spacing-5);
    }
    
    .question-header, .card-body {
        padding: var(--spacing-4);
    }
    
    .student-answer {
        padding: var(--spacing-4);
    }
    
    .score-container {
        padding: var(--spacing-4);
    }
    
    .total-score {
        font-size: 1.75rem;
    }
    
    .container {
        padding: 0 var(--spacing-3);
    }
    
    .theme-toggle {
        bottom: var(--spacing-4);
        right: var(--spacing-4);
        width: 45px;
        height: 45px;
    }
}

@media (max-width: 576px) {
    html {
        font-size: 14px;
    }
    
    .badge-points {
        font-size: 0.75rem;
        padding: var(--spacing-1) var(--spacing-3);
    }
    
    .question-card {
        margin-bottom: var(--spacing-5);
    }
    
    .percentage {
        font-size: 1.3rem;
    }
    
    .percentage span {
        font-size: 0.7rem;
    }
    
    .btn {
        padding: var(--spacing-2) var(--spacing-4);
    }
    
    .btn-lg {
        padding: var(--spacing-3) var(--spacing-5);
    }
    
    bottom: var(--spacing-3);
        right: var(--spacing-3);
        width: 40px;
        height: 40px;
    }
    
    .input-group > * {
        font-size: 0.875rem;
    }
    
    h1 { font-size: 1.875rem; }
    h2 { font-size: 1.5rem; }
    h3 { font-size: 1.25rem; }
    h4 { font-size: 1.125rem; }
    h5 { font-size: 1rem; }
    h6 { font-size: 0.875rem; }
}

/* ACCESSIBILITY IMPROVEMENTS */
:focus {
    outline: 3px solid rgba(79, 70, 229, 0.5);
    outline-offset: 2px;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}

/* HIGH CONTRAST MODE SUPPORT */
@media (forced-colors: active) {
    .btn {
        border: 1px solid;
    }
    
    .progress-bar {
        background: CanvasText;
    }
    
    .badge {
        border: 1px solid;
    }
}

/* PRINT STYLES */
@media print {
    body {
        background: none;
        color: #000;
    }
    
    .header, .theme-toggle, .btn {
        display: none;
    }
    
    .card, .question-card {
        box-shadow: none;
        break-inside: avoid;
        border: 1px solid #ddd;
    }
    
    .content-wrapper {
        margin-top: var(--spacing-4);
    }
}

/* LOADING INDICATORS */
.skeleton {
    background: linear-gradient(
        90deg,
        rgba(226, 232, 240, 0.3),
        rgba(226, 232, 240, 0.5),
        rgba(226, 232, 240, 0.3)
    );
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    border-radius: var(--border-radius);
    height: 16px;
    margin-bottom: var(--spacing-2);
}

@keyframes shimmer {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid rgba(79, 70, 229, 0.2);
    border-radius: 50%;
    border-top-color: var(--primary-color);
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* TOOLTIPS */
.tooltip {
    position: relative;
    display: inline-block;
}

.tooltip-text {
    visibility: hidden;
    position: absolute;
    z-index: 1;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    background-color: var(--card-bg);
    color: var(--text-primary);
    text-align: center;
    border-radius: var(--border-radius);
    padding: var(--spacing-2) var(--spacing-3);
    font-size: 0.75rem;
    box-shadow: var(--shadow-md);
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.3s;
    border: 1px solid rgba(229, 231, 235, 0.5);
}

.tooltip:hover .tooltip-text {
    visibility: visible;
    opacity: 1;
}

/* FEEDBACK INDICATORS */
.feedback-indicator {
    margin-left: var(--spacing-2);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    color: white;
}

.feedback-indicator.correct {
    background-color: var(--success-color);
}

.feedback-indicator.incorrect {
    background-color: var(--danger-color);
}

/* CUSTOM SCROLLBAR */
::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

::-webkit-scrollbar-track {
    background: rgba(203, 213, 225, 0.2);
    border-radius: var(--border-radius);
}

::-webkit-scrollbar-thumb {
    background: rgba(129, 140, 248, 0.3);
    border-radius: var(--border-radius);
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(129, 140, 248, 0.5);
}

/* CODE HIGHLIGHTING */
code {
    font-family: var(--font-mono);
    background-color: rgba(79, 70, 229, 0.08);
    color: var(--primary-color);
    padding: 0.2em 0.4em;
    border-radius: var(--border-radius-sm);
    font-size: 0.9em;
}

pre {
    background-color: rgba(30, 41, 59, 0.95);
    color: #f8fafc;
    padding: var(--spacing-4);
    border-radius: var(--border-radius);
    overflow-x: auto;
    margin-bottom: var(--spacing-5);
}

pre code {
    background-color: transparent;
    color: inherit;
    padding: 0;
    font-size: 0.9em;
}

/* FOOTNOTES & COMMENTS */
.footnote {
    font-size: 0.75rem;
    color: var(--text-light);
    margin-top: var(--spacing-2);
    padding-top: var(--spacing-2);
    border-top: 1px solid rgba(203, 213, 225, 0.3);
}

.comment-bubble {
    position: relative;
    background-color: rgba(79, 70, 229, 0.05);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-4);
    margin-bottom: var(--spacing-4);
}

.comment-bubble::before {
    content: '';
    position: absolute;
    top: 15px;
    left: -10px;
    border-width: 10px 10px 10px 0;
    border-style: solid;
    border-color: transparent rgba(79, 70, 229, 0.05) transparent transparent;
}

/* ANIMATIONS FOR UI ELEMENTS */
@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

.pulse {
    animation: pulse 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-20px);
    }
    60% {
        transform: translateY(-10px);
    }
}

.bounce {
    animation: bounce 2s;
}

/* DARK MODE ADJUSTMENTS */
[data-theme="dark"] .form-control {
    background-color: var(--card-bg);
    border-color: rgba(71, 85, 105, 0.7);
}

[data-theme="dark"] .btn-light {
    background-color: #334155;
    color: #f1f5f9;
    border-color: #475569;
}

[data-theme="dark"] .btn-light:hover {
    background-color: #475569;
}

[data-theme="dark"] .form-check-input {
    background-color: #334155;
    border-color: #475569;
}

[data-theme="dark"] code {
    background-color: rgba(99, 102, 241, 0.15);
}

[data-theme="dark"] .skeleton {
    background: linear-gradient(
        90deg,
        rgba(51, 65, 85, 0.5),
        rgba(71, 85, 105, 0.7),
        rgba(51, 65, 85, 0.5)
    );
}

/* FOCUS STATES */
.btn:focus, .form-control:focus, .form-check-input:focus {
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.4);
    outline: none;
}

/* END OF CSS */
    </style>
</head>
<body>
    <div class="fixed-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="mb-0">
                        <i class="fas fa-clipboard-check text-primary me-2"></i> 
                        Évaluation: <?php echo htmlspecialchars($exam_info['title']); ?>
                    </h2>
                </div>
                <div class="col-auto">
                    <span class="badge bg-primary">
                        <i class="fas fa-user me-1"></i> 
                        <?php echo htmlspecialchars($exam_info['student_name']); ?>
                    </span>
                </div>
                <div class="col-auto">
                    <a href="teacher_dashboard.php" class="btn btn-sm btn-light">
                        <i class="fas fa-arrow-left me-1"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="container">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success animate__animated animate__fadeIn">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger animate__animated animate__fadeIn">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="card mb-4 fade-in">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-lg-3 text-center">
                            <div class="radial-progress mx-auto">
                                <svg viewBox="0 0 100 100">
                                    <defs>
                                        <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                            <stop offset="0%" stop-color="#5e72e4" />
                                            <stop offset="100%" stop-color="#825ee4" />
                                        </linearGradient>
                                    </defs>
                                    <circle class="circle-bg" cx="50" cy="50" r="45"></circle>
                                    <circle class="circle" cx="50" cy="50" r="45" 
                                          stroke-dasharray="283" 
                                          stroke-dashoffset="<?php echo 283 - (283 * (isset($student_score['score']) ? $student_score['score'] : 0) / 100); ?>"></circle>
                                </svg>
                                <div class="percentage">
                                    <?php echo isset($student_score['score']) ? number_format($student_score['score'], 1) : "0"; ?>%
                                    <span>Score</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-9">
                            <h2 class="mb-3"><?php echo htmlspecialchars($exam_info['title']); ?></h2>
                            <p class="text-muted"><?php echo htmlspecialchars($exam_info['description']); ?></p>
                            
                            <div class="progress mb-2">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?php echo isset($student_score['score']) ? $student_score['score'] : 0; ?>%" 
                                     aria-valuenow="<?php echo isset($student_score['score']) ? $student_score['score'] : 0; ?>" 
                                     aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>0%</span>
                                <span>50%</span>
                                <span>100%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                <?php foreach ($questions as $question): ?>
                <div class="question-card fade-in">
                    <div class="question-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">Question #<?php echo $question['id']; ?></h3>
                            <div class="badge-points">
                                <i class="fas fa-star"></i> <?php echo $question['points']; ?> points
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div class="question-text mb-4">
                            <?php echo nl2br(htmlspecialchars($question['question_text'])); ?>
                        </div>
                        
                        <?php if ($question['question_type'] === 'qcm'): ?>
                            <div class="choices-list">
                                <h5><i class="fas fa-list-ul text-primary me-2"></i> Choix disponibles:</h5>
                                <div class="row">
                                    <?php foreach ($question['choices'] as $choice): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check d-flex align-items-center">
                                            <input class="form-check-input me-2" type="checkbox" 
                                                   value="<?php echo $choice['id']; ?>" 
                                                   <?php echo in_array($choice['id'], $question['student_answers']) ? 'checked' : ''; ?> 
                                                   disabled>
                                            <label class="form-check-label d-flex align-items-center">
                                                <?php echo htmlspecialchars($choice['choice_text']); ?>
                                                <?php if ($choice['is_correct']): ?>
                                                    <span class="ms-2 badge bg-success">
                                                        <i class="fas fa-check"></i> Correct
                                                    </span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="student-answer mb-4 <?php echo isset($question['current_score']) && $question['current_score'] > 0 ? 'correct' : 'incorrect'; ?>">
                                <h5><i class="fas fa-comment-alt text-primary me-2"></i> Réponse de l'étudiant:</h5>
                                <div class="answer-content">
                                    <?php if (!empty($question['student_answers'])): ?>
                                        <?php echo nl2br(htmlspecialchars($question['student_answers'])); ?>
                                    <?php else: ?>
                                        <em class="text-muted">Aucune réponse fournie.</em>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="score-container mb-3">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5><i class="fas fa-award text-info me-2"></i> Attribuer une note:</h5>
                                    <div class="input-group">
                                        <input type="hidden" name="question_points[<?php echo $question['id']; ?>]" value="<?php echo $question['points']; ?>">
                                        <input type="number" class="form-control score-input" 
                                               name="question_score[<?php echo $question['id']; ?>]" 
                                               value="<?php echo $question['current_score']; ?>" 
                                               min="0" max="<?php echo $question['points']; ?>" step="0.1" required>
                                        <span class="input-group-text">/ <?php echo $question['points']; ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-md-end mt-3 mt-md-0">
                                        <span class="me-3">
                                            <i class="fas fa-percentage text-primary"></i> 
                                            <?php echo $question['points'] > 0 ? number_format(($question['current_score'] / $question['points']) * 100, 1) : 0; ?>%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="comments-area">
                            <label for="comment-<?php echo $question['id']; ?>" class="form-label">
                                <i class="fas fa-comment-dots text-primary me-2"></i> Commentaires:
                            </label>
                            <textarea id="comment-<?php echo $question['id']; ?>" 
                                      name="comment[<?php echo $question['id']; ?>]" 
                                      class="form-control"><?php echo htmlspecialchars($question['teacher_comment']); ?></textarea>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="d-flex justify-content-between mt-4 mb-5">
    <a href="export_student_results.php?exam_id=<?php echo $exam_id; ?>&student_id=<?php echo $student_id; ?>" class="btn btn-success btn-lg">
        <i class="fas fa-file-export me-2"></i> Exporter les résultats
    </a>
    <button type="submit" name="save_scores" class="btn btn-primary btn-lg">
        <i class="fas fa-save me-2"></i> Enregistrer l'évaluation
    </button>
</div>
            </form>
        </div>
    </div>
    
    <form method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
        <button type="submit" name="toggle_theme" class="theme-toggle">
            <?php if ($darkMode): ?>
                <i class="fas fa-sun"></i>
            <?php else: ?>
                <i class="fas fa-moon"></i>
            <?php endif; ?>
        </button>
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enable Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Animate elements when they come into view
        document.addEventListener('DOMContentLoaded', function() {
            const fadeElems = document.querySelectorAll('.fade-in');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1
            });
            
            fadeElems.forEach(elem => {
                elem.style.opacity = '0';
                elem.style.transform = 'translateY(20px)';
                elem.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(elem);
            });
        });
    </script>
</body>
</html>