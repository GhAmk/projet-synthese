<?php
session_start();

// Verify user is logged in as a teacher
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit();
}

// Check if exam_id is provided
if (!isset($_GET['exam_id'])) {
    header("Location: teacher_dashboard.php");
    exit();
}

$exam_id = $_GET['exam_id'];

// Connect to database
$conn = new mysqli("localhost", "root", "", "exam_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Verify the exam belongs to this teacher
$stmt = $conn->prepare("SELECT id FROM exams WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $exam_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Exam doesn't exist or doesn't belong to this teacher
    header("Location: teacher_dashboard.php");
    exit();
}

// Delete the exam (cascading will handle related records due to foreign key constraints)
$stmt = $conn->prepare("DELETE FROM exams WHERE id = ?");
$stmt->bind_param("i", $exam_id);

if ($stmt->execute()) {
    // Redirect with success message
    header("Location: teacher_dashboard.php?deletion=success");
} else {
    // Redirect with error message
    header("Location: teacher_dashboard.php?deletion=error");
}

$conn->close();
?>