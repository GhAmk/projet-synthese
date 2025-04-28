<?php
session_start();
$servername = "localhost";
$username = "root";
$password = ""; // Remplacez par le mot de passe de votre base de données
$dbname = "exam_system";

// Connexion à la base de données
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = "";
$success_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $conn->real_escape_string($_POST['role']);

    // Vérification si l'e-mail existe déjà
    $check_email = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($check_email);

    if ($result->num_rows > 0) {
        $error_message = "Cet e-mail est déjà utilisé.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Les mots de passe ne correspondent pas.";
    } else {
        // Hashage du mot de passe
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insertion dans la base de données
        $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$hashed_password', '$role')";
        
        if ($conn->query($sql) === TRUE) {
            $success_message = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
        } else {
            $error_message = "Erreur: " . $sql . "<br>" . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam+ Inscription</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #4f46e5;
            --accent-color: #3b82f6;
            --accent-hover: #2563eb;
            --success-color: #10b981;
            --error-color: #ef4444;
            --dark-bg: #111827;
            --dark-card: #1f2937;
            --light-bg: #f1f5f9;
            --light-card: #ffffff;
            --dark-text-primary: #f1f5f9;
            --dark-text-secondary: #9ca3af;
            --light-text-primary: #1e293b;
            --light-text-secondary: #64748b;
            --input-bg-light: #ffffff;
            --input-bg-dark: #374151;
            --shadow-light: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-dark: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-theme="light"] {
            --bg-color: var(--light-bg);
            --card-bg: var(--light-card);
            --text-primary: var(--light-text-primary);
            --text-secondary: var(--light-text-secondary);
            --input-bg: var(--input-bg-light);
            --shadow: var(--shadow-light);
        }

        [data-theme="dark"] {
            --bg-color: var(--dark-bg);
            --card-bg: var(--dark-card);
            --text-primary: var(--dark-text-primary);
            --text-secondary: var(--dark-text-secondary);
            --input-bg: var(--input-bg-dark);
            --shadow: var(--shadow-dark);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: var(--transition);
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: var(--bg-color);
            font-family: 'Montserrat', sans-serif;
            overflow-x: hidden;
            position: relative;
        }

        /* Animated background elements */
        .bg-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .bg-element {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.5;
            z-index: -1;
        }

        .bg-element:nth-child(1) {
            background: linear-gradient(90deg, var(--accent-color), var(--primary-color));
            width: 500px;
            height: 500px;
            top: -250px;
            left: -100px;
            animation: float-1 15s infinite alternate ease-in-out;
        }

        .bg-element:nth-child(2) {
            background: linear-gradient(90deg, var(--secondary-color), var(--primary-color));
            width: 400px;
            height: 400px;
            bottom: -200px;
            right: -100px;
            animation: float-2 20s infinite alternate ease-in-out;
        }

        .bg-element:nth-child(3) {
            background: linear-gradient(90deg, var(--accent-hover), var(--accent-color));
            width: 300px;
            height: 300px;
            bottom: 100px;
            left: -150px;
            animation: float-3 18s infinite alternate ease-in-out;
        }

        .bg-element:nth-child(4) {
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-hover));
            width: 350px;
            height: 350px;
            top: 100px;
            right: -150px;
            animation: float-4 25s infinite alternate ease-in-out;
        }

        @keyframes float-1 {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(50px, 50px); }
        }

        @keyframes float-2 {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(-70px, -30px); }
        }

        @keyframes float-3 {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(30px, -50px); }
        }

        @keyframes float-4 {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(-40px, 40px); }
        }

        .container {
            display: flex;
            width: 100%;
            max-width: 1100px;
            min-height: 600px;
            margin: 2rem;
            background: var(--card-bg);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow);
            position: relative;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 10;
        }

        .welcome-section {
            flex: 1;
            padding: 3rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.8), rgba(59, 130, 246, 0.8));
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
            color: white;
        }

        .welcome-content {
            position: relative;
            z-index: 2;
            max-width: 90%;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cpath d='M15 15L85 85M85 15L15 85' stroke='rgba(255,255,255,0.1)' stroke-width='2'/%3E%3C/svg%3E");
            background-size: 30px 30px;
            opacity: 0.5;
        }

        .orbs {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            overflow: hidden;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }

        .orb:nth-child(1) {
            width: 200px;
            height: 200px;
            top: -100px;
            left: -100px;
            animation: orb-float-1 8s infinite ease-in-out;
        }

        .orb:nth-child(2) {
            width: 150px;
            height: 150px;
            bottom: -50px;
            right: -50px;
            animation: orb-float-2 10s infinite ease-in-out;
        }

        .orb:nth-child(3) {
            width: 100px;
            height: 100px;
            bottom: 100px;
            left: 50px;
            animation: orb-float-3 12s infinite ease-in-out;
        }

        @keyframes orb-float-1 {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(50px, 50px); }
        }

        @keyframes orb-float-2 {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(-30px, -30px); }
        }

        @keyframes orb-float-3 {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(20px, -20px); }
        }

        .welcome-section h1 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            background: linear-gradient(45deg, #ffffff, #e0e7ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-family: 'Poppins', sans-serif;
            position: relative;
        }

        .welcome-section p {
            font-size: 1.2rem;
            line-height: 1.8;
            margin-bottom: 2rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .feature-list {
            list-style: none;
            text-align: left;
            margin-top: 1rem;
        }

        .feature-list li {
            margin: 1rem 0;
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
        }

        .feature-list li i {
            margin-right: 10px;
            color: rgba(255, 255, 255, 0.95);
            font-size: 1.2rem;
        }

        .register-section {
            flex: 1;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .theme-toggle {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: transparent;
            border: none;
            color: var(--primary-color);
            font-size: 1.2rem;
            cursor: pointer;
            z-index: 10;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            background: rgba(99, 102, 241, 0.1);
        }

        .theme-toggle:hover {
            transform: rotate(45deg);
            background: rgba(99, 102, 241, 0.2);
        }

        .register-section h2 {
            color: var(--accent-color);
            font-size: 2.2rem;
            margin-bottom: 2rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            position: relative;
            display: inline-block;
        }

        .register-section h2::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 50px;
            height: 4px;
            background: linear-gradient(to right, var(--accent-color), var(--primary-color));
            border-radius: 2px;
        }

        .input-box {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-box input,
        .input-box select {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background: var(--input-bg);
            border: 2px solid rgba(99, 102, 241, 0.1);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: var(--transition);
            font-family: 'Montserrat', sans-serif;
        }

        .input-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent-color);
            font-size: 1.1rem;
            transition: var(--transition);
        }

        .input-box input:focus,
        .input-box select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            outline: none;
        }

        .input-box input:focus + i,
        .input-box select:focus + i {
            color: var(--accent-hover);
            transform: translateY(-50%) scale(1.1);
        }

        button[type="submit"] {
            background: linear-gradient(90deg, var(--accent-color), var(--primary-color));
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2);
            position: relative;
            overflow: hidden;
            z-index: 1;
            font-family: 'Montserrat', sans-serif;
        }

        button[type="submit"]::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: var(--transition);
            z-index: -1;
        }

        button[type="submit"]:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(99, 102, 241, 0.3);
        }

        button[type="submit"]:hover::before {
            left: 100%;
            transition: 0.7s;
        }

        .links {
            margin-top: 1.5rem;
            text-align: center;
        }

        .links a {
            color: var(--accent-color);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: var(--transition);
            position: relative;
            padding-bottom: 2px;
        }

        .links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(to right, var(--accent-color), var(--primary-color));
            transition: var(--transition);
        }

        .links a:hover {
            color: var(--accent-hover);
        }

        .links a:hover::after {
            width: 100%;
        }

        .error-message {
            color: var(--error-color);
            text-align: center;
            margin-top: 1rem;
            padding: 10px;
            border-radius: 8px;
            background: rgba(239, 68, 68, 0.1);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-message i {
            margin-right: 8px;
        }

        .success-message {
            color: var(--success-color);
            text-align: center;
            margin-top: 1rem;
            padding: 10px;
            border-radius: 8px;
            background: rgba(16, 185, 129, 0.1);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-message i {
            margin-right: 8px;
        }

        /* Responsive design */
        @media (max-width: 992px) {
            .container {
                max-width: 90%;
                flex-direction: column;
            }

            .welcome-section {
                padding: 2rem;
            }

            .welcome-section h1 {
                font-size: 2.2rem;
            }

            .welcome-section p {
                font-size: 1rem;
            }

            .register-section {
                padding: 2rem;
            }

            .register-section h2 {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 576px) {
            .container {
                margin: 1rem;
                min-height: auto;
            }

            .welcome-section {
                padding: 1.5rem;
            }

            .welcome-section h1 {
                font-size: 1.8rem;
                margin-bottom: 1rem;
            }

            .welcome-section p {
                font-size: 0.9rem;
                margin-bottom: 1rem;
            }

            .register-section {
                padding: 1.5rem;
            }

            .register-section h2 {
                font-size: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .input-box {
                margin-bottom: 1rem;
            }

            .feature-list li {
                font-size: 0.9rem;
                margin: 0.7rem 0;
            }
        }

        /* Card flip animation on theme toggle */
        .container.flip {
            transform: rotateY(180deg);
        }

        /* Loading animation for submit button */
        @keyframes loading {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: loading 1.5s infinite;
        }

        /* Input focus effects */
        .input-box input:focus::placeholder,
        .input-box select:focus::placeholder {
            opacity: 0.7;
            transform: translateX(10px);
            transition: var(--transition);
        }

        /* Dropdown custom styling */
        .input-box select {
            appearance: none;
            cursor: pointer;
        }

        .input-box.select::after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent-color);
            pointer-events: none;
            font-size: 0.8rem;
        }

        /* Password strength indicator */
        .password-strength {
            height: 4px;
            margin-top: 5px;
            border-radius: 2px;
            transition: var(--transition);
            width: 0;
            background: #ef4444;
        }

        .password-strength.weak {
            width: 30%;
            background: #ef4444; /* Red */
        }

        .password-strength.medium {
            width: 60%;
            background: #f59e0b; /* Amber */
        }

        .password-strength.strong {
            width: 100%;
            background: #10b981; /* Green */
        }

        /* Password toggle */
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: var(--accent-color);
            cursor: pointer;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="bg-elements">
        <div class="bg-element"></div>
        <div class="bg-element"></div>
        <div class="bg-element"></div>
        <div class="bg-element"></div>
    </div>

    <div class="container" id="container">
        <div class="welcome-section">
            <div class="orbs">
                <div class="orb"></div>
                <div class="orb"></div>
                <div class="orb"></div>
            </div>
            <div class="welcome-content">
                <h1>Bienvenue sur Exam+</h1>
                <p>La plateforme moderne qui transforme l'expérience d'apprentissage en ligne</p>
                
                <ul class="feature-list">
                    <li><i class="fas fa-check-circle"></i> Accès à des examens interactifs</li>
                    <li><i class="fas fa-check-circle"></i> Suivi de progression personnalisé</li>
                    <li><i class="fas fa-check-circle"></i> Interface intuitive et moderne</li>
                    <li><i class="fas fa-check-circle"></i> Espace collaboratif étudiant-enseignant</li>
                </ul>
            </div>
        </div>

        <div class="register-section">
            <button class="theme-toggle" id="themeToggle"><i class="fas fa-moon"></i></button>
            <h2>Créez votre compte</h2>
            <form method="POST" action="" id="registerForm">
                <div class="input-box">
                    <input type="text" name="name" id="name" placeholder="Votre nom complet" required>
                    <i class="fas fa-user"></i>
                </div>
                <div class="input-box">
                    <input type="email" name="email" id="email" placeholder="Votre adresse email" required>
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password" id="password" placeholder="Votre mot de passe" required>
                    <i class="fas fa-lock"></i>
                    <button type="button" class="password-toggle" id="passwordToggle"><i class="fas fa-eye"></i></button>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>
                <div class="input-box">
                    <input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirmez votre mot de passe" required>
                    <i class="fas fa-lock"></i>
                </div>
                <div class="input-box select">
                    <select name="role" id="role" required>
                        <option value="" disabled selected>Sélectionnez votre rôle</option>
                        <option value="student">Étudiant</option>
                        <option value="teacher">Enseignant</option>
                    </select>
                    <i class="fas fa-user-graduate"></i>
                </div>
                <button type="submit" id="submitBtn">S'inscrire</button>
            </form>
            <div class="links">
                <a href="login.php">Déjà membre ? Connectez-vous ici</a>
            </div>
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Basculement du thème
        const themeToggle = document.getElementById('themeToggle');
        const htmlElement = document.documentElement;
        const container = document.getElementById('container');
        const toggleIcon = themeToggle.querySelector('i');
        
        // Vérifier le thème enregistré dans localStorage
        const savedTheme = localStorage.getItem('theme') || 'light';
        htmlElement.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);
        
        themeToggle.addEventListener('click', function() {
            const currentTheme = htmlElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            // Ajouter l'animation de flip
            container.classList.add('flip');
            
            // Attendre un court instant puis changer le thème
            setTimeout(() => {
                htmlElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateThemeIcon(newTheme);
                
                // Supprimer l'animation
                setTimeout(() => {
                    container.classList.remove('flip');
                }, 300);
            }, 150);
        });
        
        function updateThemeIcon(theme) {
            toggleIcon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        }
        
        // Vérification de la force du mot de passe
        const passwordInput = document.getElementById('password');
        const passwordStrength = document.getElementById('passwordStrength');
        
        passwordInput.addEventListener('input', function() {
            const value = passwordInput.value;
            let strength = 0;
            
            if (value.length >= 8) strength += 1;
            if (value.match(/[A-Z]/)) strength += 1;
            if (value.match(/[0-9]/)) strength += 1;
            if (value.match(/[^A-Za-z0-9]/)) strength += 1;
            
            passwordStrength.className = 'password-strength';
            
            if (strength === 0) {
                passwordStrength.className = 'password-strength';
            } else if (strength <= 2) {
                passwordStrength.className = 'password-strength weak';
            } else if (strength === 3) {
                passwordStrength.className = 'password-strength medium';
            } else {
                passwordStrength.className = 'password-strength strong';
            }
        });
        
        // Toggle de visibilité du mot de passe
        const passwordToggle = document.getElementById('passwordToggle');
        
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type');
            passwordInput.setAttribute('type', type === 'password' ? 'text' : 'password');
            
            const icon = passwordToggle.querySelector('i');
            icon.className = type === 'password' ? 'fas fa-eye-slash' : 'fas fa-eye';
        });
        
        // Animation du bouton au submit
        const registerForm = document.getElementById('registerForm');
        const submitBtn = document.getElementById('submitBtn');
        
        registerForm.addEventListener('submit', function() {
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        });
        
        // Animation au focus des champs
        const inputs = document.querySelectorAll('.input-box input, .input-box select');
        
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                const icon = this.previousElementSibling || this.nextElementSibling;
                if (icon.tagName === 'I') {
                    icon.style.transform = 'translateY(-50%) scale(1.1)';
                }
            });
            
            input.addEventListener('blur', function() {
                const icon = this.previousElementSibling || this.nextElementSibling;
                if (icon.tagName === 'I') {
                    icon.style.transform = 'translateY(-50%)';
                }
            });
            // Vérification des mots de passe correspondants
        const confirmPasswordInput = document.getElementById('confirmPassword');
        
        confirmPasswordInput.addEventListener('input', function() {
            if (passwordInput.value !== confirmPasswordInput.value) {
                confirmPasswordInput.style.borderColor = 'var(--error-color)';
                confirmPasswordInput.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.1)';
            } else {
                confirmPasswordInput.style.borderColor = 'var(--success-color)';
                confirmPasswordInput.style.boxShadow = '0 0 0 4px rgba(16, 185, 129, 0.1)';
            }
        });
        
        // Animation des éléments d'arrière-plan lors du défilement ou du mouvement de la souris
        document.addEventListener('mousemove', function(e) {
            const mouseX = e.clientX / window.innerWidth;
            const mouseY = e.clientY / window.innerHeight;
            
            const bgElements = document.querySelectorAll('.bg-element');
            const orbs = document.querySelectorAll('.orb');
            
            bgElements.forEach((element, index) => {
                const offsetX = (index + 1) * 10;
                const offsetY = (index + 1) * 10;
                element.style.transform = `translate(${mouseX * offsetX}px, ${mouseY * offsetY}px)`;
            });
            
            orbs.forEach((orb, index) => {
                const offsetX = (index + 1) * 15;
                const offsetY = (index + 1) * 15;
                orb.style.transform = `translate(${mouseX * offsetX}px, ${mouseY * offsetY}px)`;
            });
        });
        
        // Effets d'entrée animés
        document.addEventListener('DOMContentLoaded', function() {
            const welcomeContent = document.querySelector('.welcome-content');
            const registerForm = document.querySelector('form');
            const inputs = document.querySelectorAll('.input-box');
            
            welcomeContent.style.opacity = '0';
            welcomeContent.style.transform = 'translateY(20px)';
            
            registerForm.style.opacity = '0';
            registerForm.style.transform = 'translateX(20px)';
            
            inputs.forEach((input, index) => {
                input.style.opacity = '0';
                input.style.transform = 'translateY(20px)';
            });
            
            // Animation d'entrée par séquence
            setTimeout(() => {
                welcomeContent.style.opacity = '1';
                welcomeContent.style.transform = 'translateY(0)';
                
                setTimeout(() => {
                    registerForm.style.opacity = '1';
                    registerForm.style.transform = 'translateX(0)';
                    
                    inputs.forEach((input, index) => {
                        setTimeout(() => {
                            input.style.opacity = '1';
                            input.style.transform = 'translateY(0)';
                        }, 100 * index);
                    });
                }, 300);
            }, 300);
        });
        
        // Animation des boutons
        const buttons = document.querySelectorAll('button');
        
        buttons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = this.id === 'submitBtn' ? 'translateY(-3px)' : 'scale(1.05)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
        
        // Effet de focus amélioré pour les champs
        inputs.forEach(input => {
            const inputElement = input.querySelector('input, select');
            if (!inputElement) return;
            
            inputElement.addEventListener('focus', function() {
                this.parentElement.style.boxShadow = '0 0 0 4px rgba(99, 102, 241, 0.1)';
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            inputElement.addEventListener('blur', function() {
                this.parentElement.style.boxShadow = 'none';
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
        
        // Masquer les messages d'erreur/succès après un délai
        const messages = document.querySelectorAll('.error-message, .success-message');
        
        if (messages.length > 0) {
            messages.forEach(message => {
                message.style.opacity = '0';
                message.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    message.style.opacity = '1';
                    message.style.transform = 'translateY(0)';
                    
                    // Faire disparaître le message après 5 secondes
                    setTimeout(() => {
                        message.style.opacity = '0';
                        message.style.transform = 'translateY(-10px)';
                    }, 5000);
                }, 300);
            });
        }
        
        // Vérification de l'email en temps réel
        const emailInput = document.getElementById('email');
        
        emailInput.addEventListener('blur', function() {
            const email = this.value;
            if (email && !isValidEmail(email)) {
                this.style.borderColor = 'var(--error-color)';
                this.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.1)';
            } else if (email) {
                this.style.borderColor = 'var(--success-color)';
                this.style.boxShadow = '0 0 0 4px rgba(16, 185, 129, 0.1)';
            }
        });
        
        function isValidEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }
        
        // Ajout d'un effet de pulsation sur le bouton d'inscription
        function pulseButton() {
            submitBtn.classList.add('pulse');
            setTimeout(() => {
                submitBtn.classList.remove('pulse');
            }, 1000);
        }
        
        // Déclencher la pulsation périodiquement
        setInterval(pulseButton, 5000);
        
        // Animation de transition entre les pages
        document.querySelector('.links a').addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            
            document.body.style.opacity = '0';
            document.body.style.transform = 'scale(0.9)';
            
            setTimeout(() => {
                window.location.href = href;
            }, 500);
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>