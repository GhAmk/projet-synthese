<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam+ | Ajouter un Utilisateur</title>
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #4338ca;
            --accent-color: #00d4ff;
            --dark-bg: #0f172a;
            --card-bg: #1e293b;
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --error-color: #ef4444;
            --feature-card-bg: rgba(255, 255, 255, 0.05);
            --feature-card-border: rgba(255, 255, 255, 0.1);
            --navbar-bg: rgba(15, 23, 42, 0.8);
            --hero-overlay: linear-gradient(135deg, rgba(79, 70, 229, 0.7), rgba(15, 23, 42, 0.9));
            --features-bg: #1e293b;
        }

        [data-theme="dark"] {
            --primary-color: #4f46e5;
            --secondary-color: #4338ca;
            --accent-color: #00d4ff;
            --dark-bg: #0f172a;
            --card-bg: #1e293b;
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --error-color: #ef4444;
            --feature-card-bg: rgba(255, 255, 255, 0.03);
            --feature-card-border: rgba(255, 255, 255, 0.05);
            --navbar-bg: rgba(15, 23, 42, 0.95);
            --hero-overlay: linear-gradient(135deg, rgba(67, 56, 202, 0.8), rgba(15, 23, 42, 0.95));
            --features-bg: #0f172a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        body {
            min-height: 100vh;
            background-color: var(--dark-bg);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
            background: var(--navbar-bg);
            backdrop-filter: blur(10px);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-color);
            text-decoration: none;
        }

        .logo span {
            color: var(--text-primary);
        }

        .container {
            width: 100%;
            max-width: 600px;
            background: var(--card-bg);
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--feature-card-border);
            margin-top: 80px;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            color: var(--accent-color);
            font-size: 2rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 2rem;
            background: linear-gradient(to right, var(--text-primary), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        input,
        select {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            color: var(--text-primary);
            background-color: var(--feature-card-bg);
            border: 1px solid var(--feature-card-border);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
            background-color: var(--card-bg);
        }

        input::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
        }

        .btn {
            padding: 0.875rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1rem;
            width: 100%;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--accent-color), var(--primary-color));
            color: var(--text-primary);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 212, 255, 0.3);
        }

        .form-group small {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        /* Password strength indicator */
        .password-strength {
            height: 4px;
            margin-top: 0.5rem;
            border-radius: 2px;
            background-color: var(--feature-card-border);
            overflow: hidden;
        }

        .password-strength::after {
            content: '';
            display: block;
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }

        .password-strength.weak::after {
            width: 33.33%;
            background-color: var(--error-color);
        }

        .password-strength.medium::after {
            width: 66.66%;
            background-color: var(--primary-color);
        }

        .password-strength.strong::after {
            width: 100%;
            background-color: var(--accent-color);
        }

        /* Champ de groupe */
        .group-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }
        
        .new-group-input {
            margin-top: 0.75rem;
            display: none;
        }

        /* Styles pour les alertes */
        .alert {
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid #10b981;
            color: #10b981;
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--error-color);
            color: var(--error-color);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 2rem 1.5rem;
                margin-top: 70px;
            }

            h2 {
                font-size: 1.5rem;
            }
            
            .group-options {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 1.5rem 1rem;
                margin-top: 60px;
            }
        }
    </style>
</head>
<body>
<nav class="navbar">
<div class="logo"><i class="fas fa-graduation-cap"></i> Exam+</div>
<div class="nav-links">
            <a href="Teacher_dashboard.php">Tableau de bord</a>
            
        </div>
       
    </nav>

    <div class="container">
        <h2>Ajouter un Utilisateur</h2>
        
        <?php
        // Afficher des messages d'erreur ou de succès si nécessaire
        if (isset($_GET['status'])) {
            if ($_GET['status'] == 'success') {
                echo '<div class="alert alert-success">Utilisateur ajouté avec succès!</div>';
            } elseif ($_GET['status'] == 'error') {
                echo '<div class="alert alert-error">Erreur lors de l\'ajout de l\'utilisateur.</div>';
            }
        }

        // Connexion à la base de données
        $db_host = 'localhost';
        $db_user = 'root';
        $db_pass = '';
        $db_name = 'exam_system';

        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

        // Vérifier la connexion
        if ($conn->connect_error) {
            die("Connexion échouée: " . $conn->connect_error);
        }

        // Récupérer tous les groupes existants
        $groups = [];
        $query = "SELECT id, name FROM groups ORDER BY name";
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $groups[] = $row;
            }
        }
        ?>

        <form action="add_user_action.php" method="POST">
            <div class="form-group">
                <label for="role">Choisir un Rôle</label>
                <select id="role" name="role" required>
                    <option value="" disabled selected>Sélectionnez un rôle</option>
                    <option value="teacher">Professeur</option>
                    <option value="student">Étudiant</option>
                </select>
            </div>

            <div class="form-group">
                <label for="first_name">Prénom</label>
                <input type="text" id="first_name" name="first_name" required 
                       placeholder="Entrez le prénom">
            </div>

            <div class="form-group">
                <label for="last_name">Nom</label>
                <input type="text" id="last_name" name="last_name" required 
                       placeholder="Entrez le nom">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required 
                       placeholder="exemple@domain.com">
            </div>

            <div class="form-group" id="group-section">
                <label for="group_id">Groupe</label>
                <select id="group_id" name="group_id">
                    <option value="" selected>Sélectionnez un groupe</option>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['name']); ?></option>
                    <?php endforeach; ?>
                    <option value="new">Nouveau groupe...</option>
                </select>
                
                <div class="new-group-input" id="new-group-container">
                    <label for="new_group_name">Nom du nouveau groupe</label>
                    <input type="text" id="new_group_name" name="new_group_name" placeholder="Entrez le nom du nouveau groupe">
                    
                    <label for="new_group_description">Description (optionnelle)</label>
                    <input type="text" id="new_group_description" name="new_group_description" placeholder="Description du groupe">
                </div>
                <small>Sélectionnez un groupe existant ou créez-en un nouveau</small>
            </div>

            <div class="form-group">
                <label for="password">Mot de Passe</label>
                <input type="password" id="password" name="password" required 
                       placeholder="••••••••">
                <div class="password-strength"></div>
                <small>Le mot de passe doit contenir au moins 8 caractères</small>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Ajouter l'Utilisateur</button>
            </div>
        </form>
    </div>

    <script>
        // Gestion du thème
        const themeToggle = document.getElementById('themeToggle');
        const htmlElement = document.documentElement;
        
        // Vérifier le thème enregistré dans localStorage
        const savedTheme = localStorage.getItem('theme') || 'light';
        htmlElement.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);
        
        themeToggle.addEventListener('click', function() {
            const currentTheme = htmlElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            htmlElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            updateThemeIcon(newTheme);
        });
        
        function updateThemeIcon(theme) {
            themeToggle.textContent = theme === 'light' ? '🌙' : '☀️';
        }

        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthIndicator = document.querySelector('.password-strength');

        passwordInput.addEventListener('input', (e) => {
            const value = e.target.value;
            if (value.length === 0) {
                strengthIndicator.className = 'password-strength';
            } else if (value.length < 8) {
                strengthIndicator.className = 'password-strength weak';
            } else if (value.length < 12) {
                strengthIndicator.className = 'password-strength medium';
            } else {
                strengthIndicator.className = 'password-strength strong';
            }
        });

        // Gestion du groupe
        const roleSelect = document.getElementById('role');
        const groupSection = document.getElementById('group-section');
        const groupSelect = document.getElementById('group_id');
        const newGroupContainer = document.getElementById('new-group-container');
        
        roleSelect.addEventListener('change', function() {
            if (this.value === 'student') {
                groupSection.style.display = 'block';
            } else {
                groupSection.style.display = 'none';
                groupSelect.value = '';
                document.getElementById('new_group_name').value = '';
                document.getElementById('new_group_description').value = '';
            }
        });
        
        groupSelect.addEventListener('change', function() {
            if (this.value === 'new') {
                newGroupContainer.style.display = 'block';
            } else {
                newGroupContainer.style.display = 'none';
                document.getElementById('new_group_name').value = '';
                document.getElementById('new_group_description').value = '';
            }
        });

        // Initialiser l'état des champs au chargement
        window.addEventListener('DOMContentLoaded', function() {
            groupSection.style.display = 'none';
            newGroupContainer.style.display = 'none';
        });
    </script>
</body>
</html>