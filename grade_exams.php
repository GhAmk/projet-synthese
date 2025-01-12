<?php
session_start();

// Vérifier si l'utilisateur est connecté et si c'est un formateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header('Location: login.php');
    exit;
}

// Connexion à la base de données
$pdo = new PDO('mysql:host=localhost;dbname=exams', 'root', '');

// Récupérer les examens à corriger
$stmt = $pdo->query("SELECT * FROM exams");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corriger les examens</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Corriger les examens</h2>

        <div class="exam-list">
            <?php while ($exam = $stmt->fetch()): ?>
                <div class="exam">
                    <h3><?php echo $exam['title']; ?></h3>
                    <a href="correct_exam.php?exam_id=<?php echo $exam['id']; ?>">Corriger cet examen</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>