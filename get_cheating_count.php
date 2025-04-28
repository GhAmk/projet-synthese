<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "exam_system");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de connexion à la base de données']);
    exit();
}

// Récupérer le nombre de tentatives de triche pour l'enseignant connecté
$query = "SELECT COUNT(*) as count 
          FROM cheating_attempts ca
          INNER JOIN exams e ON ca.exam_id = e.id
          WHERE e.teacher_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo json_encode(['count' => (int)$row['count']]);

$conn->close();
?> 