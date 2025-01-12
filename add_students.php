<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Formateur</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --primary-light: #3b82f6;
            --background: #f8fafc;
            --surface: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --input-bg: #f8fafc;
            --success: #10b981;
            --error: #ef4444;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--background);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            line-height: 1.5;
        }

        .container {
            width: 100%;
            max-width: 32rem;
            background-color: var(--surface);
            border-radius: 1rem;
            box-shadow: var(--shadow-lg);
            padding: 2.5rem;
        }

        h2 {
            color: var(--primary);
            font-size: 1.875rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 2rem;
            letter-spacing: -0.025em;
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
            line-height: 1.5;
            color: var(--text-primary);
            background-color: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background-color: var(--surface);
        }

        input::placeholder {
            color: var(--text-secondary);
        }

        button {
            width: 100%;
            padding: 0.875rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            color: white;
            background-color: var(--primary);
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        button:active {
            transform: translateY(0);
        }

        .form-group small {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        /* Password strength indicator */
        .password-strength {
            height: 4px;
            margin-top: 0.5rem;
            border-radius: 2px;
            background-color: var(--border);
            overflow: hidden;
        }

        .password-strength::after {
            content: '';
            display: block;
            height: 100%;
            width: 0;
            background-color: var(--error);
            transition: all 0.3s ease;
        }

        .password-strength.weak::after {
            width: 33.33%;
            background-color: var(--error);
        }

        .password-strength.medium::after {
            width: 66.66%;
            background-color: var(--primary);
        }

        .password-strength.strong::after {
            width: 100%;
            background-color: var(--success);
        }

        /* Responsive adjustments */
        @media (max-width: 640px) {
            .container {
                padding: 1.5rem;
            }

            h2 {
                font-size: 1.5rem;
                margin-bottom: 1.5rem;
            }

            input,
            select,
            button {
                padding: 0.75rem 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Ajouter un Utilisateur</h2>
        <form action="add_user_action.php" method="POST">
            <div class="form-group">
                <label for="role">Choisir un Rôle</label>
                <select id="role" name="role" required>
                    <option value="" disabled selected>Sélectionnez un rôle</option>
                    <option value="professor">Professeur</option>
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

            <div class="form-group">
                <label for="password">Mot de Passe</label>
                <input type="password" id="password" name="password" required 
                       placeholder="••••••••">
                <div class="password-strength"></div>
                <small>Le mot de passe doit contenir au moins 8 caractères</small>
            </div>

            <div class="form-group">
                <button type="submit">Ajouter l'Utilisateur</button>
            </div>
        </form>
    </div>

    <script>
        // Simple password strength indicator
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
    </script>
</body>
</html>