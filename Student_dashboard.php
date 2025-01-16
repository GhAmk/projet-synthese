<?php 
session_start(); 
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') { 
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Étudiant</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            min-height: 100vh;
        }

        .navbar {
            background-color: #2c3e50;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar h1 {
            font-size: 1.5rem;
            font-weight: 500;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .dashboard-menu {
            background-color: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .menu-list {
            list-style: none;
            margin-bottom: 2rem;
        }

        .menu-list li {
            margin-bottom: 1rem;
        }

        .menu-list a {
            display: block;
            padding: 1rem;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .menu-list a:hover {
            background-color: #2980b9;
        }

        .logout-btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .logout-btn:hover {
            background-color: #c0392b;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
            }

            .navbar h1 {
                margin-bottom: 1rem;
            }

            .dashboard-menu {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>Bienvenue, <?php echo htmlspecialchars($_SESSION['name']); ?> !</h1>
        <a href="logout.php" class="logout-btn">Se Déconnecter</a>
    </nav>

    <div class="container">
        <div class="dashboard-menu">
            <ul class="menu-list">
                <li><a href="view_exams.php">Voir les Examens</a></li>
                <!-- Vous pouvez ajouter d'autres liens ici -->
            </ul>
        </div>
    </div>
</body>
</html>