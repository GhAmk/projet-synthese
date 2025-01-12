<?php
session_start();

// Vérifier si l'utilisateur est connecté et est un enseignant
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php"); // Rediriger vers la page de login s'il n'est pas connecté en tant qu'enseignant
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Formateur</title>
    <style>
        /* Style général pour le corps */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #DDDEE5;
        }

        /* Style pour la barre latérale */
        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: #0E1D35;
            display: flex;
            flex-direction: column;
            align-items: start;
            padding-top: 20px;
            box-sizing: border-box;
            position: fixed;
        }

        /* Style des éléments du menu */
        .menu-item {
            width: 100%;
            margin: 10px 0;
            padding: 15px 20px;
            color: white;
            background-color: #2B3D5B;
            text-decoration: none;
            border-radius: 30px;
            font-size: 16px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            font-weight: bold;
            box-sizing: border-box;
        }

        /* Style pour l'élément actif */
        .menu-item.active {
            background-color: #DDDEE5;
            color: #2B3D5B;
            font-weight: bold;
        }

        /* Effet au survol */
        .menu-item:hover {
            background-color: #C3C3C3;
            color: #1E2840;
        }

        /* Conteneur principal */
        .main-content {
            margin-left: 270px;
            padding: 20px;
        }

        /* Style pour les cartes */
        .cards-container {
            display: flex;
            justify-content: space-between;
            gap: 50px;
            margin-top: 50px;
        }

        .card {
            flex: 1;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            font-size: 18px;
            color: white;
        }

        .card h3 {
            margin: 0;
            margin-bottom: 6px;
        }

        .card p {
            font-size: 30px;
            font-weight: bold;
            margin: 0;
        }

        .card-blue {
            background-color:#1C124A;
        }

        .card-red {
            background-color:#271E2F;
        }

        .card-orange {
            background-color:#475292;
        }
    </style>
</head>
<body>
    <!-- Barre latérale -->
    <div class="sidebar">
        <a href="create_exam.php" class="menu-item active">
            <span>Créer un Examen</span>
        </a>
        <a href="add_students.php" class="menu-item">
            <span>Ajouter des Étudiants</span>
        </a>
        <a href="correct_exams.php" class="menu-item">
            <span>Corriger les Examens</span>
        </a>
    </div>

    <!-- Contenu principal -->
    <div class="main-content">
        <div class="cards-container">
            <!-- Carte Nombre d'étudiants -->
            <div class="card card-blue">
                <h3>Nombre d'étudiants</h3>
                <p>0</p>
            </div>

            <!-- Carte Nombre de professeurs -->
            <div class="card card-red">
                <h3>Nombre de professeurs</h3>
                <p>0</p>
            </div>

            <!-- Carte Nombre d'examens -->
            <div class="card card-orange">
                <h3>Nombre d'examens</h3>
                <p>0</p>
            </div>
        </div>
    </div>
</body>
</html>