<?php
session_start();

// Vérifier que l'utilisateur est connecté et qu'il est un enseignant
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

// Vérifier que les paramètres nécessaires sont présents
if (!isset($_GET['exam_id']) || !isset($_GET['student_id'])) {
    header("Location: teacher_dashboard.php");
    exit();
}

$exam_id = $_GET['exam_id'];
$student_id = $_GET['student_id'];

$conn = new mysqli("localhost", "root", "", "exam_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Récupérer les informations sur l'examen et l'étudiant
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

// Récupérer le score de l'étudiant
$score_query = "SELECT score FROM exam_students 
                WHERE exam_id = ? AND student_id = ? AND status = 'completed'";
$score_stmt = $conn->prepare($score_query);
$score_stmt->bind_param("ii", $exam_id, $student_id);
$score_stmt->execute();
$score_result = $score_stmt->get_result();
$student_score = $score_result->fetch_assoc();

// Récupérer les questions, réponses et scores
$questions_query = "SELECT q.id, q.question_text, q.question_type, qp.points, 
                   qs.score as student_score, qs.comment as teacher_comment,
                   GROUP_CONCAT(DISTINCT sa.answer) as student_answers
                   FROM questions q
                   LEFT JOIN question_points qp ON q.id = qp.question_id
                   LEFT JOIN student_answers sa ON q.id = sa.question_id AND sa.student_id = ? AND sa.exam_id = ?
                   LEFT JOIN question_scores qs ON q.id = qs.question_id AND qs.student_id = ? AND qs.exam_id = ?
                   WHERE q.exam_id = ?
                   GROUP BY q.id
                   ORDER BY q.id";
$questions_stmt = $conn->prepare($questions_query);
$questions_stmt->bind_param("iiiii", $student_id, $exam_id, $student_id, $exam_id, $exam_id);
$questions_stmt->execute();
$questions_result = $questions_stmt->get_result();

$questions = [];
while ($question = $questions_result->fetch_assoc()) {
    // Pour les questions QCM, récupérer les choix corrects
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
        $correct_choices = [];
        while ($choice = $choices_result->fetch_assoc()) {
            $choices[] = $choice;
            if ($choice['is_correct']) {
                $correct_choices[] = $choice['choice_text'];
            }
        }
        
        $question['choices'] = $choices;
        $question['correct_answers'] = implode(", ", $correct_choices);
        
        // Convertir la chaîne des réponses en tableau
        if ($question['student_answers']) {
            $question['student_answers'] = explode(',', $question['student_answers']);
        } else {
            $question['student_answers'] = [];
        }
    }
    
    $questions[] = $question;
}

// Générer le fichier CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=resultats_' . $exam_info['title'] . '_' . $exam_info['student_name'] . '.csv');

$output = fopen('php://output', 'w');

// En-tête du CSV avec les informations principales
fputcsv($output, ['Examen:', $exam_info['title']]);
fputcsv($output, ['Description:', $exam_info['description']]);
fputcsv($output, ['Étudiant:', $exam_info['student_name']]);
fputcsv($output, ['Score global:', isset($student_score['score']) ? number_format($student_score['score'], 1) . '%' : 'Non noté']);
fputcsv($output, []); // Ligne vide

// En-tête des questions
fputcsv($output, ['ID Question', 'Question', 'Type', 'Points', 'Score obtenu', 'Pourcentage', 'Réponses correctes', 'Réponses étudiant', 'Commentaires']);

// Données des questions
foreach ($questions as $question) {
    $percentage = ($question['points'] > 0 && isset($question['student_score'])) ? 
                 ($question['student_score'] / $question['points']) * 100 : 0;
    
    // Formatage des réponses étudiant
    $student_answers = '';
    if ($question['question_type'] === 'qcm') {
        $student_answers = [];
        foreach ($question['choices'] as $choice) {
            if (in_array($choice['id'], $question['student_answers'])) {
                $student_answers[] = $choice['choice_text'];
            }
        }
        $student_answers = implode(", ", $student_answers);
    } else {
        $student_answers = $question['student_answers'] ?? 'Aucune réponse';
    }
    
    fputcsv($output, [
        $question['id'],
        $question['question_text'],
        $question['question_type'],
        $question['points'],
        $question['student_score'] ?? 'Non noté',
        $question['points'] > 0 ? number_format($percentage, 1) . '%' : 'N/A',
        $question['question_type'] === 'qcm' ? $question['correct_answers'] : 'N/A',
        $student_answers,
        $question['teacher_comment'] ?? ''
    ]);
}

fclose($output);
exit();