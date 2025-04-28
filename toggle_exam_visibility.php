<?php
// toggle_exam_visibility.php
session_start();

// Vérifier si l'utilisateur est connecté et est un enseignant
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

// Vérifier si la requête est de type POST et contient les données nécessaires
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['exam_id']) || !isset($_POST['is_visible'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit();
}

// Récupérer les données
$exam_id = intval($_POST['exam_id']);
$is_visible = intval($_POST['is_visible']) ? 1 : 0;
$teacher_id = $_SESSION['user_id'];

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "exam_system");

if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit();
}

// Vérifier que l'examen appartient bien à cet enseignant
$check_query = "SELECT id FROM exams WHERE id = ? AND teacher_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $exam_id, $teacher_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Examen non trouvé ou non autorisé']);
    exit();
}

// Mettre à jour la visibilité de l'examen
$update_query = "UPDATE exams SET is_visible = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("ii", $is_visible, $exam_id);

if ($update_stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => $is_visible ? 'Examen rendu visible' : 'Examen masqué',
        'is_visible' => $is_visible
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
}

$conn->close();
?>