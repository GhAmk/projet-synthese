<?php
session_start();

// Vérifier si l'utilisateur est connecté et est un étudiant
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: login.php"); // Rediriger vers la page de login s'il n'est pas connecté en tant qu'étudiant
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Étudiant</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Bienvenue, <?php echo htmlspecialchars($_SESSION['name']); ?> !</h1>
    <ul>
        <li><a href="view_exams.php">Voir les Examens</a></li>
    </ul>
    <a href="logout.php">Se Déconnecter</a>
</body>
</html>