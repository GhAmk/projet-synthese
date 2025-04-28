<?php
// Database configuration
$host = 'localhost'; // Database host (usually localhost)
$dbname = 'exam_system'; // Database name (from your SQL file)
$username = 'root'; // Default XAMPP username
$password = ''; // Default XAMPP password is empty

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to UTF-8
$conn->set_charset("utf8mb4");

// Define site constants
define('SITE_URL', 'http://localhost/app-exam-enlign/app-exam-enlign/');
define('SITE_NAME', 'Système d\'Examen en Ligne');
?>