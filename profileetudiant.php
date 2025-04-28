<?php
// Démarrer la session
session_start();

// Simuler un utilisateur connecté si aucune session n'existe
// En production, vous devriez utiliser un vrai système de connexion
if (!isset($_SESSION['id_etudiant'])) {
    // Pour les besoins de démonstration, on simule un ID d'étudiant
    $_SESSION['id_etudiant'] = 1;
}

// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "app_exam_enlign";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // En cas d'erreur de connexion, on crée un étudiant fictif pour permettre l'affichage de la page
    $dbConnected = false;
    $etudiant = [
        'nom' => 'Étudiant Test',
        'email' => 'etudiant@test.com',
        'date_inscription' => date('Y-m-d')
    ];
}

// Si connecté à la base de données, récupérer les informations de l'étudiant
if (isset($dbConnected) && $dbConnected === false) {
    // Utiliser les données fictives déjà définies
} else {
    $id_etudiant = $_SESSION['id_etudiant'];
    
    // Vérifier si la table existe
    try {
        $stmt = $conn->prepare("SELECT * FROM etudiants WHERE id = :id_etudiant");
        $stmt->bindParam(':id_etudiant', $id_etudiant);
        $stmt->execute();
        $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si l'étudiant n'existe pas, créer un profil fictif
        if (!$etudiant) {
            $etudiant = [
                'nom' => 'Étudiant Test',
                'email' => 'etudiant@test.com',
                'date_inscription' => date('Y-m-d')
            ];
        }
    } catch(PDOException $e) {
        // Si la table n'existe pas, créer un profil fictif
        $etudiant = [
            'nom' => 'Étudiant Test',
            'email' => 'etudiant@test.com',
            'date_inscription' => date('Y-m-d')
        ];
    }
}

// Initialiser les variables de message
$message = "";
$error = "";

// Traitement des formulaires
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mise à jour du nom
    if (isset($_POST['update_nom'])) {
        $nouveau_nom = trim($_POST['nouveau_nom']);
        
        if (!empty($nouveau_nom)) {
            if (isset($dbConnected) && $dbConnected === false) {
                $message = "Mode démo: Votre nom a été mis à jour avec succès (simulation).";
                $etudiant['nom'] = $nouveau_nom;
            } else {
                try {
                    $update_stmt = $conn->prepare("UPDATE etudiants SET nom = :nom WHERE id = :id_etudiant");
                    $update_stmt->bindParam(':nom', $nouveau_nom);
                    $update_stmt->bindParam(':id_etudiant', $id_etudiant);
                    $update_stmt->execute();
                    
                    $message = "Votre nom a été mis à jour avec succès.";
                    $etudiant['nom'] = $nouveau_nom;
                } catch(PDOException $e) {
                    $error = "Mode démo: Modification simulée.";
                    $etudiant['nom'] = $nouveau_nom;
                }
            }
        } else {
            $error = "Le nom ne peut pas être vide.";
        }
    }
    
    // Mise à jour de l'email
    if (isset($_POST['update_email'])) {
        $nouveau_email = trim($_POST['nouveau_email']);
        
        if (!empty($nouveau_email) && filter_var($nouveau_email, FILTER_VALIDATE_EMAIL)) {
            if (isset($dbConnected) && $dbConnected === false) {
                $message = "Mode démo: Votre email a été mis à jour avec succès (simulation).";
                $etudiant['email'] = $nouveau_email;
            } else {
                try {
                    $update_stmt = $conn->prepare("UPDATE etudiants SET email = :email WHERE id = :id_etudiant");
                    $update_stmt->bindParam(':email', $nouveau_email);
                    $update_stmt->bindParam(':id_etudiant', $id_etudiant);
                    $update_stmt->execute();
                    
                    $message = "Votre email a été mis à jour avec succès.";
                    $etudiant['email'] = $nouveau_email;
                } catch(PDOException $e) {
                    $error = "Mode démo: Modification simulée.";
                    $etudiant['email'] = $nouveau_email;
                }
            }
        } else {
            $error = "Veuillez entrer une adresse email valide.";
        }
    }
    
    // Mise à jour du mot de passe
    if (isset($_POST['update_password'])) {
        $ancien_password = $_POST['ancien_password'];
        $nouveau_password = $_POST['nouveau_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($nouveau_password === $confirm_password) {
            if (strlen($nouveau_password) >= 8) {
                $message = "Mode démo: Votre mot de passe a été mis à jour avec succès (simulation).";
            } else {
                $error = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
            }
        } else {
            $error = "Les nouveaux mots de passe ne correspondent pas.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Étudiant | App Exam Enlign</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #4e54c8;
            --secondary-color: #8f94fb;
            --accent-color: #6c63ff;
            --light-color: #f5f7ff;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(to right, var(--light-color), #ffffff);
            color: #333;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated background */
        .area {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            width: 100%;
            height: 300px;
            position: absolute;
            top: 0;
            left: 0;
            z-index: -1;
        }
        
        .circles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .circles li {
            position: absolute;
            display: block;
            list-style: none;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.2);
            animation: animate 25s linear infinite;
            bottom: -150px;
            border-radius: 50%;
        }
        
        .circles li:nth-child(1) {
            left: 25%;
            width: 80px;
            height: 80px;
            animation-delay: 0s;
        }
        
        .circles li:nth-child(2) {
            left: 10%;
            width: 20px;
            height: 20px;
            animation-delay: 2s;
            animation-duration: 12s;
        }
        
        .circles li:nth-child(3) {
            left: 70%;
            width: 20px;
            height: 20px;
            animation-delay: 4s;
        }
        
        .circles li:nth-child(4) {
            left: 40%;
            width: 60px;
            height: 60px;
            animation-delay: 0s;
            animation-duration: 18s;
        }
        
        .circles li:nth-child(5) {
            left: 65%;
            width: 20px;
            height: 20px;
            animation-delay: 0s;
        }
        
        .circles li:nth-child(6) {
            left: 75%;
            width: 110px;
            height: 110px;
            animation-delay: 3s;
        }
        
        .circles li:nth-child(7) {
            left: 35%;
            width: 150px;
            height: 150px;
            animation-delay: 7s;
        }
        
        .circles li:nth-child(8) {
            left: 50%;
            width: 25px;
            height: 25px;
            animation-delay: 15s;
            animation-duration: 45s;
        }
        
        .circles li:nth-child(9) {
            left: 20%;
            width: 15px;
            height: 15px;
            animation-delay: 2s;
            animation-duration: 35s;
        }
        
        .circles li:nth-child(10) {
            left: 85%;
            width: 150px;
            height: 150px;
            animation-delay: 0s;
            animation-duration: 11s;
        }
        
        @keyframes animate {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
                border-radius: 0;
            }
            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
                border-radius: 50%;
            }
        }
        
        .container {
            position: relative;
            z-index: 1;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            padding: 15px 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
            transition: all 0.3s ease;
        }
        
        .navbar-brand:hover {
            transform: translateY(-2px);
        }
        
        .navbar-nav .nav-link {
            color: var(--dark-color) !important;
            position: relative;
            margin: 0 10px;
            transition: all 0.3s ease;
        }
        
        .navbar-nav .nav-link:before {
            content: "";
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: var(--primary-color);
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .navbar-nav .nav-link:hover:before {
            visibility: visible;
            width: 100%;
        }
        
        .navbar-nav .nav-link:hover {
            color: var(--primary-color) !important;
            transform: translateY(-2px);
        }
        
        .navbar-nav .active .nav-link {
            color: var(--primary-color) !important;
            font-weight: 600;
        }
        
        .navbar-nav .active .nav-link:before {
            visibility: visible;
            width: 100%;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            margin-top: 30px;
            margin-bottom: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(78, 84, 200, 0.3);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .profile-header:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(78, 84, 200, 0.4);
        }
        
        .profile-header:after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transition: all 0.5s ease;
        }
        
        .profile-header:hover:after {
            transform: scale(1.5);
        }
        
        .profile-section {
            margin-bottom: 30px;
            padding: 30px;
            border-radius: 15px;
            background-color: white;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            border-top: 5px solid transparent;
            overflow: hidden;
        }
        
        .profile-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            border-top: 5px solid var(--primary-color);
        }
        
        .profile-section h3 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-color);
            transition: all 0.3s ease;
        }
        
        .profile-section:hover h3 {
            transform: translateX(5px);
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            background: white;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            border-bottom: none;
            padding: 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .card-body p {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-color);
        }
        
        .card-body p:last-child {
            border-bottom: none;
        }
        
        .btn {
            border-radius: 50px;
            padding: 10px 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .btn:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
        }
        
        .btn:before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0%;
            height: 100%;
            background: rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            z-index: -1;
        }
        
        .btn:hover:before {
            width: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            box-shadow: 0 5px 15px rgba(78, 84, 200, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(78, 84, 200, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #757F9A, #D7DDE8);
            border: none;
            box-shadow: 0 5px 15px rgba(117, 127, 154, 0.3);
        }
        
        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(117, 127, 154, 0.4);
        }
        
        .form-control {
            border-radius: 50px;
            padding: 12px 20px;
            border: 2px solid var(--light-color);
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 10px rgba(78, 84, 200, 0.1);
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 10px;
            display: block;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 25px;
            animation: fadeIn 0.5s ease;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #43cea2, #185a9d);
            color: white;
            box-shadow: 0 5px 15px rgba(67, 206, 162, 0.3);
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #ff7e5f, #feb47b);
            color: white;
            box-shadow: 0 5px 15px rgba(255, 126, 95, 0.3);
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-icon {
            position: absolute;
            right: 15px;
            top: 45px;
            color: var(--primary-color);
        }
        
        .profile-pic {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--primary-color);
            margin: 0 auto 20px;
            border: 5px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .profile-pic:hover {
            transform: scale(1.1) rotate(5deg);
        }
        
        .badge-status {
            background: linear-gradient(135deg, #43cea2, #185a9d);
            color: white;
            padding: 5px 15px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        /* Password strength indicator */
        .password-strength {
            height: 5px;
            margin-top: 10px;
            transition: all 0.3s ease;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .password-strength-meter {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon .form-control {
            padding-right: 40px;
        }
        
        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            cursor: pointer;
        }
        
        /* Floating labels effect */
        .float-label {
            position: relative;
        }
        
        .float-label label {
            position: absolute;
            left: 20px;
            top: 10px;
            color: #999;
            transition: all 0.3s ease;
            pointer-events: none;
            font-size: 14px;
        }
        
        .float-label .form-control:focus ~ label,
        .float-label .form-control:not(:placeholder-shown) ~ label {
            top: -12px;
            left: 15px;
            font-size: 12px;
            padding: 0 5px;
            background-color: white;
            color: var(--primary-color);
            font-weight: bold;
        }
        
        /* Success checkmark animation */
        @keyframes checkmark {
            0% {
                stroke-dashoffset: 100;
            }
            100% {
                stroke-dashoffset: 0;
            }
        }
        
        .checkmark {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: block;
            stroke-width: 2;
            stroke: #43cea2;
            stroke-miterlimit: 10;
            margin: 10% auto;
            box-shadow: 0 0 0 rgba(67, 206, 162, 0.4);
            animation: pulse 2s infinite;
        }
        
        .checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 0;
            stroke-width: 2;
            stroke-miterlimit: 10;
            stroke: #43cea2;
            fill: none;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }
        
        .checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: checkmark 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }
        
        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(67, 206, 162, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(67, 206, 162, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(67, 206, 162, 0);
            }
        }
        
        /* Tooltip styles */
        .tooltip-custom {
            position: relative;
            display: inline-block;
        }
        
        .tooltip-custom .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 10px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.8rem;
        }
        
        .tooltip-custom:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
        
        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="area">
        <ul class="circles">
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
        </ul>
    </div>

    <!-- Barre de navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand animate__animated animate__fadeIn" href="#">
                <i class="fas fa-graduation-cap mr-2"></i>App Exam Enlign
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item animate__animated animate__fadeIn" style="animation-delay: 0.1s">
                        <a class="nav-link" href="student_dashboard.php"><i class="fas fa-home mr-1"></i> Accueil</a>
                    </li>
                    
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item active animate__animated animate__fadeIn" style="animation-delay: 0.4s">
                        <a class="nav-link" href="#"><i class="fas fa-user-circle mr-1"></i> Mon Profil</a>
                    </li>
                    <li class="nav-item animate__animated animate__fadeIn" style="animation-delay: 0.5s">
                        <a class="nav-link" href="#"><i class="fas fa-sign-out-alt mr-1"></i> Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="profile-header animate__animated animate__fadeInDown">
            <h1 class="mb-3"><i class="fas fa-user-edit mr-3"></i>Mon Profil</h1>
            <p class="mb-0">Gérez vos informations personnelles et paramètres de compte</p>
        </div>
        
        <!-- Message alerts -->
        <?php if (!empty($message)): ?>
        <div class="alert alert-success animate__animated animate__fadeIn" id="success-alert">
            <div class="d-flex align-items-center">
                <div class="mr-3">
                    <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                        <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                        <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                    </svg>
                </div>
                <div>
                    <h5 class="mb-0">Succès!</h5>
                    <p class="mb-0"><?php echo $message; ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger animate__animated animate__fadeIn" id="error-alert">
            <div class="d-flex align-items-center">
                <div class="mr-3">
                    <i class="fas fa-exclamation-circle fa-2x"></i>
                </div>
                <div>
                    <h5 class="mb-0">Attention!</h5>
                    <p class="mb-0"><?php echo $error; ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4 animate__animated animate__fadeInLeft">
                    <div class="card-header text-center">
                        <h5 class="card-title mb-0">Informations personnelles</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="profile-pic mb-4">
                            <i class="fas fa-user"></i>
                        </div>
                        <h4><?php echo htmlspecialchars($etudiant['nom']); ?></h4>
                        <p class="text-muted mb-3"><?php echo htmlspecialchars($etudiant['email']); ?></p>
                        
                        <div class="mb-3">
                            <span class="badge badge-status">
                                <i class="fas fa-check-circle mr-1"></i> Étudiant Actif
                            </span>
                        </div>
                        
                        <?php if (isset($etudiant['date_inscription'])): ?>
                            <p class="small text-muted">
                                <i class="fas fa-calendar-alt mr-1"></i> Inscrit depuis: 
                                <strong><?php echo date('d/m/Y', strtotime($etudiant['date_inscription'])); ?></strong>
                            </p>
                        <?php endif; ?>
                        
                        <a href="#" class="btn btn-secondary btn-block mt-4 animated-btn">
                            <i class="fas fa-tachometer-alt mr-2"></i>Tableau de bord
                        </a>
                        
                        <div class="mt-4 pt-3 border-top">
                            <div class="row">
                                <div class="col-4 text-center">
                                    <div class="icon-stat">
                                        <i class="fas fa-book text-primary"></i>
                                        <h5 class="mt-2 mb-0">12</h5>
                                        <small class="text-muted">Cours</small>
                                    </div>
                                </div>
                                <div class="col-4 text-center">
                                    <div class="icon-stat">
                                        <i class="fas fa-clipboard-check text-success"></i>
                                        <h5 class="mt-2 mb-0">8</h5>
                                        <small class="text-muted">Examens</small>
                                    </div>
                                </div>
                                <div class="col-4 text-center">
                                    <div class="icon-stat">
                                        <i class="fas fa-medal text-warning"></i>
                                        <h5 class="mt-2 mb-0">4</h5>
                                        <small class="text-muted">Badges</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4 animate__animated animate__fadeInLeft" style="animation-delay: 0.2s">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-shield-alt mr-2"></i>Sécurité du compte</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="security-icon mr-3">
                                <i class="fas fa-lock text-success"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Mot de passe</h6>
                                <small class="text-muted">Dernière modification: Il y a 30 jours</small>
                            </div>
                            <div class="ml-auto">
                                <span class="badge badge-pill badge-success">Bon</span>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center mb-3">
                            <div class="security-icon mr-3">
                                <i class="fas fa-envelope text-warning"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Email de secours</h6>
                                <small class="text-muted">Non configuré</small>
                            </div>
                            <div class="ml-auto">
                                <span class="badge badge-pill badge-warning">À configurer</span>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center">
                            <div class="security-icon mr-3">
                                <i class="fas fa-mobile-alt text-info"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Double authentification</h6>
                                <small class="text-muted">Non activée</small>
                            </div>
                            <div class="ml-auto">
                                <span class="badge badge-pill badge-secondary">Inactive</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <!-- Section pour modifier le nom -->
                <div class="profile-section mb-4 animate__animated animate__fadeInRight">
                    <h3><i class="fas fa-user-edit mr-2"></i>Modifier mon nom</h3>
                    <form method="post" action="" id="nomForm">
                        <div class="form-group float-label">
                            <div class="input-with-icon">
                                <input type="text" class="form-control" id="nouveau_nom" name="nouveau_nom" value="<?php echo htmlspecialchars($etudiant['nom']); ?>" placeholder=" " required>
                                <label for="nouveau_nom">Nouveau nom</label>
                                <i class="fas fa-user form-icon"></i>
                            </div>
                        </div>
                        <button type="submit" name="update_nom" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Mettre à jour le nom
                        </button>
                    </form>
                </div>
                
                <!-- Section pour modifier l'email -->
                <div class="profile-section mb-4 animate__animated animate__fadeInRight" style="animation-delay: 0.2s">
                    <h3><i class="fas fa-envelope mr-2"></i>Modifier mon email</h3>
                    <form method="post" action="" id="emailForm">
                        <div class="form-group float-label">
                            <div class="input-with-icon">
                                <input type="email" class="form-control" id="nouveau_email" name="nouveau_email" value="<?php echo htmlspecialchars($etudiant['email']); ?>" placeholder=" " required>
                                <label for="nouveau_email">Nouvel email</label>
                                <i class="fas fa-envelope form-icon"></i>
                            </div>
                        </div>
                        <div class="form-text small text-muted mb-3">
                            <i class="fas fa-info-circle mr-1"></i> Cet email sera utilisé pour les notifications et la récupération de compte.
                        </div>
                        <button type="submit" name="update_email" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Mettre à jour l'email
                        </button>
                    </form>
                </div>
                
                <!-- Section pour modifier le mot de passe -->
                <div class="profile-section animate__animated animate__fadeInRight" style="animation-delay: 0.4s">
                    <h3><i class="fas fa-key mr-2"></i>Modifier mon mot de passe</h3>
                    <form method="post" action="" id="passwordForm">
                        <div class="form-group float-label">
                            <div class="input-with-icon">
                                <input type="password" class="form-control" id="ancien_password" name="ancien_password" placeholder=" " required>
                                <label for="ancien_password">Ancien mot de passe</label>
                                <i class="fas fa-lock form-icon"></i>
                                <span class="input-icon toggle-password" data-target="ancien_password">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        
                        <div class="form-group float-label">
                            <div class="input-with-icon">
                                <input type="password" class="form-control" id="nouveau_password" name="nouveau_password" placeholder=" " required>
                                <label for="nouveau_password">Nouveau mot de passe</label>
                                <i class="fas fa-key form-icon"></i>
                                <span class="input-icon toggle-password" data-target="nouveau_password">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <div class="password-strength mt-2">
                                <div class="password-strength-meter" id="password-strength-meter"></div>
                            </div>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i> Le mot de passe doit contenir au moins 8 caractères.
                            </small>
                            <div id="password-feedback" class="mt-2"></div>
                        </div>
                        
                        <div class="form-group float-label">
                            <div class="input-with-icon">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder=" " required>
                                <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                                <i class="fas fa-check-double form-icon"></i>
                                <span class="input-icon toggle-password" data-target="confirm_password">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        
                        <div class="password-tips mb-4">
                            <h6 class="text-muted mb-2"><i class="fas fa-lightbulb mr-1"></i> Conseils pour un mot de passe fort :</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="small text-muted pl-3">
                                        <li>Au moins 8 caractères</li>
                                        <li>Mélange de majuscules et minuscules</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="small text-muted pl-3">
                                        <li>Au moins un chiffre</li>
                                        <li>Au moins un caractère spécial</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_password" class="btn btn-primary" id="password-submit">
                            <i class="fas fa-save mr-2"></i>Mettre à jour le mot de passe
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Animation au défilement
            function animateOnScroll() {
                $('.profile-section').each(function() {
                    var position = $(this).offset().top;
                    var scroll = $(window).scrollTop();
                    var windowHeight = $(window).height();
                    
                    if (scroll > position - windowHeight + 100) {
                        $(this).addClass('animate__animated animate__fadeInUp');
                    }
                });
            }
            
            $(window).scroll(function() {
                animateOnScroll();
            });
            
            // Animation initiale
            animateOnScroll();
            
            // Affichage/masquage du mot de passe
            $('.toggle-password').click(function() {
                const targetId = $(this).data('target');
                const input = $('#' + targetId);
                const icon = $(this).find('i');
                
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
            
            // Vérification que les mots de passe correspondent
            $('#confirm_password').on('input', function() {
                const nouveauPassword = $('#nouveau_password').val();
                const confirmPassword = $(this).val();
                
                if (nouveauPassword === confirmPassword) {
                    $(this).css('border-color', '#43cea2');
                    $(this).setCustomValidity('');
                } else {
                    $(this).css('border-color', '#ff7e5f');
                    $(this).setCustomValidity('Les mots de passe ne correspondent pas');
                }
            });
            
            // Évaluation de la force du mot de passe
            $('#nouveau_password').on('input', function() {
                const password = $(this).val();
                let strength = 0;
                let feedback = [];
                
                // Longueur
                if (password.length >= 8) {
                    strength += 25;
                } else {
                    feedback.push('<span class="text-danger"><i class="fas fa-times-circle mr-1"></i>Au moins 8 caractères</span>');
                }
                
                // Lettres majuscules
                if (/[A-Z]/.test(password)) {
                    strength += 25;
                } else {
                    feedback.push('<span class="text-danger"><i class="fas fa-times-circle mr-1"></i>Au moins une majuscule</span>');
                }
                
                // Chiffres
                if (/[0-9]/.test(password)) {
                    strength += 25;
                } else {
                    feedback.push('<span class="text-danger"><i class="fas fa-times-circle mr-1"></i>Au moins un chiffre</span>');
                }
                
                // Caractères spéciaux
                if (/[^A-Za-z0-9]/.test(password)) {
                    strength += 25;
                } else {
                    feedback.push('<span class="text-danger"><i class="fas fa-times-circle mr-1"></i>Au moins un caractère spécial</span>');
                }
                
                // Mise à jour de l'indicateur de force
                const meter = $('#password-strength-meter');
                meter.css('width', strength + '%');
                
                // Couleur selon la force
                if (strength < 50) {
                    meter.css('background', '#ff7e5f');
                } else if (strength < 75) {
                    meter.css('background', '#feb47b');
                } else {
                    meter.css('background', '#43cea2');
                }
                
                // Affichage du feedback
                $('#password-feedback').html(feedback.join('<br>'));
            });
            
            // Animation des formulaires lors de la soumission
            $('form').submit(function(e) {
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalBtnText = submitBtn.html();
                
                submitBtn.html('<span class="spinner mr-2"></span>Traitement en cours...');
                submitBtn.prop('disabled', true);
                
                // Simulation d'une soumission (à retirer en production)
                setTimeout(function() {
                    submitBtn.html(originalBtnText);
                    submitBtn.prop('disabled', false);
                }, 1500);
                
                // Retirez cette ligne pour permettre la soumission réelle du formulaire
                // e.preventDefault();
            });
            
            // Auto-disparition des alertes
            setTimeout(function() {
                $('#success-alert, #error-alert').fadeOut('slow');
            }, 5000);
            
            // Effet hover sur les boutons avec ripple effect
            $('.btn').on('mouseenter', function(e) {
                const parentOffset = $(this).offset();
                const relX = e.pageX - parentOffset.left;
                const relY = e.pageY - parentOffset.top;
                
                $(this).find('.ripple').remove();
                $(this).append('<span class="ripple"></span>');
                
                const ripple = $(this).find('.ripple');
                ripple.css({
                    top: relY,
                    left: relX
                });
            });
            
            // Effet sur le scroll
            $(window).scroll(function() {
                const scroll = $(window).scrollTop();
                
                if (scroll >= 50) {
                    $('.navbar').addClass('navbar-shrink');
                } else {
                    $('.navbar').removeClass('navbar-shrink');
                }
            });
            
            // Tooltips d'initialisation
            $('[data-toggle="tooltip"]').tooltip();
            
            // Animation des icônes sur hover
            $('.icon-stat').hover(
                function() {
                    $(this).find('i').addClass('animate__animated animate__heartBeat');
                },
                function() {
                    $(this).find('i').removeClass('animate__animated animate__heartBeat');
                }
            );
            
            // Validation en live des champs
            $('#nouveau_email').on('input', function() {
                const email = $(this).val();
                const regex = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/;
                
                if (regex.test(email)) {
                    $(this).css('border-color', '#43cea2');
                } else {
                    $(this).css('border-color', '#ff7e5f');
                }
            });
            
            // Effet de zoom sur la photo de profil
            $('.profile-pic').hover(
                function() {
                    $(this).addClass('animate__animated animate__pulse');
                },
                function() {
                    $(this).removeClass('animate__animated animate__pulse');
                }
            );
            
            // Effet de parallaxe sur l'arrière-plan
            $(window).scroll(function() {
                const scroll = $(window).scrollTop();
                $('.area').css({
                    'transform': 'translateY(' + (scroll * 0.3) + 'px)'
                });
            });
        });
    </script>
</body>
</html>