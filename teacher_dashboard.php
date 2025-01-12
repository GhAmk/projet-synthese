//teacher_dashboard.php
<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Formateur</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
            --danger-color: #dc2626;
            --warning-color: #f59e0b;
            --success-color: #10b981;
            --background-light: #f8fafc;
            --text-dark: #1e293b;
            --text-light: #f8fafc;
            --sidebar-width: 280px;
            --transition-speed: 0.3s;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', system-ui, sans-serif;
            background-color: var(--background-light);
            color: var(--text-dark);
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(to bottom, var(--primary-color), var(--secondary-color));
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            align-items: start;
            padding: 2rem 1rem;
            box-sizing: border-box;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .menu-item {
            width: 100%;
            margin: 0.5rem 0;
            padding: 1rem 1.5rem;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 0.75rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all var(--transition-speed) ease;
            opacity: 0.8;
        }

        .menu-item.active {
            background-color: rgba(255, 255, 255, 0.1);
            opacity: 1;
            font-weight: 600;
        }

        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.15);
            opacity: 1;
            transform: translateX(5px);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            background-color: var(--background-light);
            min-height: 100vh;
        }
        .sidebar h1 {
    color: var(--text-light);
    font-size: 1.5rem;
    margin: 0 0 2rem 1.5rem;
    font-weight: 600;
    opacity: 0.9;
}

        /* New enhanced card styles */
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
            padding: 1rem;
        }

        .card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            transition: all 0.3s ease;
        }

        .card-blue::before {
            background: linear-gradient(90deg, #2563eb, #3b82f6);
        }

        .card-red::before {
            background: linear-gradient(90deg, #dc2626, #ef4444);
        }

        .card-orange::before {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .card h3 {
            color: #64748b;
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .card p {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(90deg, #1e293b, #334155);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 3rem;
            opacity: 0.1;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
                padding: 1rem 0.5rem;
            }

            .menu-item span {
                display: none;
            }

            .main-content {
                margin-left: 80px;
            }

            .cards-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
    <h1 class="sidebar-title">
    <i class="fas fa-chalkboard-teacher"></i> Bord Teacher
</h1>
        <a href="create_exam.php" class="menu-item active">
            <i class="fas fa-file-alt"></i>
            <span>Créer un Examen</span>
        </a>
        <a href="add_students.php" class="menu-item">
            <i class="fas fa-user-plus"></i>
            <span>Ajouter des Étudiants</span>
        </a>
        <a href="correct_exams.php" class="menu-item">
            <i class="fas fa-pencil-alt"></i>
            <span>Corriger les Examens</span>
        </a>
    </div>

    <div class="main-content">
        <div class="cards-container">
            <div class="card card-blue">
                <i class="fas fa-users card-icon"></i>
                <h3>Nombre d'étudiants</h3>
                <p class="counter" data-target="150">0</p>
            </div>

            <div class="card card-red">
                <i class="fas fa-chalkboard-teacher card-icon"></i>
                <h3>Nombre de professeurs</h3>
                <p class="counter" data-target="12">0</p>
            </div>

            <div class="card card-orange">
                <i class="fas fa-file-alt card-icon"></i>
                <h3>Nombre d'examens</h3>
                <p class="counter" data-target="45">0</p>
            </div>
        </div>
    </div>

    <script>
        // Animation for counter
        const counters = document.querySelectorAll('.counter');
        const speed = 200; // Plus le nombre est bas, plus c'est rapide

        counters.forEach(counter => {
            const animate = () => {
                const value = +counter.getAttribute('data-target');
                const data = +counter.innerText;
                
                const time = value / speed;
                
                if (data < value) {
                    counter.innerText = Math.ceil(data + time);
                    setTimeout(animate, 1);
                } else {
                    counter.innerText = value;
                }
            }
            animate();
        });
    </script>
</body>
</html>