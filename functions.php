<?php
// Format date/time for display
function formatDate($dateTime) {
    $date = new DateTime($dateTime);
    return $date->format('d/m/Y H:i');
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is a teacher
function isTeacher() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
}

// Check if user is a student
function isStudent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

// Check if user is an admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Get user info by ID
function getUserById($conn, $userId) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Get exams by teacher ID
function getExamsByTeacher($conn, $teacherId) {
    $sql = "SELECT * FROM exams WHERE teacher_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $exams = [];
    while ($row = $result->fetch_assoc()) {
        $exams[] = $row;
    }
    
    return $exams;
}

// Get exam by ID
function getExamById($conn, $examId) {
    $sql = "SELECT * FROM exams WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $examId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Create or update cheating attempt record
function recordCheatingAttempt($conn, $examId, $studentId, $description) {
    $sql = "INSERT INTO triches_attempts (exam_id, student_id, description, status) VALUES (?, ?, ?, 'new')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $examId, $studentId, $description);
    return $stmt->execute();
}
?>