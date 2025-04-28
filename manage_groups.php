<?php
session_start();

// Vérifier si l'utilisateur est connecté et est un enseignant
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit();
}

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "exam_system");

if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Récupérer l'ID de l'enseignant connecté
$teacher_id = $_SESSION['user_id'];

// Récupérer tous les étudiants disponibles
$students_query = "SELECT DISTINCT u.id, u.name, u.email, g.name as group_name, g.id as group_id
                  FROM users u 
                  LEFT JOIN exam_students es ON u.id = es.student_id 
                  LEFT JOIN exams e ON es.exam_id = e.id 
                  LEFT JOIN groups g ON e.group_id = g.id 
                  WHERE u.role = 'student' 
                  ORDER BY u.name ASC";

$students_result = $conn->query($students_query);

// Récupérer la liste des groupes pour le filtre
$groups_query = "SELECT DISTINCT g.id, g.name 
                FROM groups g 
                JOIN exams e ON g.id = e.group_id 
                JOIN exam_students es ON e.id = es.exam_id 
                JOIN users u ON es.student_id = u.id 
                WHERE u.role = 'student' 
                ORDER BY g.name";
$groups_result = $conn->query($groups_query);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Étudiants | Exam+</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #4338ca;
            --accent-color: #00d4ff;
            --dark-bg: #0f172a;
            --card-bg: #1e293b;
            --surface: #ffffff;
            --input-bg: #f8fafc;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --error-color: #ef4444;
            --warning-color: #f59e0b;
            --success-color: #10b981;
            --border: #e2e8f0;
            --hover-bg: rgba(0, 212, 255, 0.1);
        }

        [data-theme="dark"] {
            --primary-color: #4f46e5;
            --secondary-color: #4338ca;
            --accent-color: #00d4ff;
            --dark-bg: #0f172a;
            --card-bg: #1e293b;
            --surface: #1e293b;
            --input-bg: #0f172a;
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --error-color: #ef4444;
            --warning-color: #f59e0b;
            --success-color: #10b981;
            --border: #334155;
            --hover-bg: rgba(0, 212, 255, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background-color: var(--dark-bg);
            color: var(--text-primary);
            position: relative;
        }

        .page-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://source.unsplash.com/random/1920x1080/?classroom,education,students');
            background-size: cover;
            background-position: center;
            filter: brightness(0.3);
            z-index: -2;
        }

        .page-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.7), rgba(15, 23, 42, 0.9));
            z-index: -1;
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
            background: rgba(15, 23, 42, 0.8);
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

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--accent-color);
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 100px auto 40px;
            padding: 0 1rem;
        }

        .content-container {
            background-color: var(--surface);
            padding: 2.5rem;
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .container-glow {
            position: absolute;
            width: 100%;
            height: 6px;
            background: linear-gradient(to right, var(--accent-color), var(--primary-color));
            top: 0;
            left: 0;
            border-radius: 1.5rem 1.5rem 0 0;
        }

        h1 {
            font-size: 2.25rem;
            font-weight: 800;
            margin-bottom: 2.5rem;
            background: linear-gradient(to right, var(--text-primary), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
        }

        .search-box {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            background-color: var(--input-bg);
            padding: 1rem;
            border-radius: 1rem;
            border: 1px solid var(--border);
        }

        .search-box input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 0.5rem;
            background-color: transparent;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .search-box input:focus {
            outline: none;
        }

        .search-box select {
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 0.5rem;
            background-color: transparent;
            color: var(--text-primary);
            font-size: 1rem;
            cursor: pointer;
        }

        .search-box select:focus {
            outline: none;
        }

        .students-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1rem;
        }

        .students-table th,
        .students-table td {
            padding: 1.25rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .students-table th {
            background-color: var(--input-bg);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
        }

        .students-table tr {
            transition: all 0.3s ease;
        }

        .students-table tr:hover {
            background-color: var(--hover-bg);
            transform: translateX(5px);
        }

        .group-badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            background: linear-gradient(to right, var(--accent-color), var(--primary-color));
            color: white;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .group-badge i {
            font-size: 0.75rem;
        }

        .no-students {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .no-students i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--accent-color);
        }

        .no-students p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
        }

        .student-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(to right, var(--accent-color), var(--primary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .student-details {
            display: flex;
            flex-direction: column;
        }

        .student-name {
            font-weight: 600;
            color: var(--text-primary);
        }

        .student-email {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-direction: column;
                align-items: center;
                gap: 0.5rem;
            }

            .content-container {
                padding: 1.5rem;
                margin-top: 150px;
            }

            .search-box {
                flex-direction: column;
            }

            .students-table {
                display: block;
                overflow-x: auto;
            }

            .student-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="page-bg"></div>
    <div class="page-overlay"></div>
    
    <nav class="navbar">
        <a href="index.php" class="logo">Exam<span>+</span></a>
        <div class="nav-links">
            <a href="Teacher_dashboard.php">Tableau de bord</a>
            <a href="exams_list.php">Examens</a>
            <a href="groups_list.php" class="active">Groupes</a>
        </div>
    </nav>

    <div class="container">
        <div class="content-container">
            <div class="container-glow"></div>
            <h1>Liste des Étudiants</h1>

            <div class="search-box">
                <input type="text" id="searchStudent" placeholder="Rechercher un étudiant par nom ou email...">
                <select id="groupFilter">
                    <option value="">Tous les groupes</option>
                    <?php
                    while ($group = $groups_result->fetch_assoc()) {
                        echo "<option value='" . $group['id'] . "'>" . htmlspecialchars($group['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <table class="students-table">
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Groupe</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($students_result->num_rows > 0): ?>
                        <?php while ($student = $students_result->fetch_assoc()): ?>
                            <tr data-group-id="<?php echo $student['group_id'] ?? ''; ?>">
                                <td>
                                    <div class="student-info">
                                        <div class="student-avatar">
                                            <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                                        </div>
                                        <div class="student-details">
                                            <span class="student-name"><?php echo htmlspecialchars($student['name']); ?></span>
                                            <span class="student-email"><?php echo htmlspecialchars($student['email']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($student['group_name']): ?>
                                        <span class="group-badge">
                                            <i class="fas fa-users"></i>
                                            <?php echo htmlspecialchars($student['group_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--text-secondary);">Aucun groupe</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="no-students">
                                <i class="fas fa-user-graduate"></i>
                                <p>Aucun étudiant trouvé</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Filtrage des étudiants
        const searchInput = document.getElementById('searchStudent');
        const groupFilter = document.getElementById('groupFilter');
        const rows = document.querySelectorAll('.students-table tbody tr');

        function filterStudents() {
            const searchText = searchInput.value.toLowerCase();
            const selectedGroup = groupFilter.value;

            rows.forEach(row => {
                const name = row.querySelector('.student-name').textContent.toLowerCase();
                const email = row.querySelector('.student-email').textContent.toLowerCase();
                const groupId = row.dataset.groupId || '';

                const matchesSearch = name.includes(searchText) || email.includes(searchText);
                const matchesGroup = !selectedGroup || groupId === selectedGroup;

                row.style.display = matchesSearch && matchesGroup ? '' : 'none';
            });

            // Afficher un message si aucun résultat n'est trouvé
            const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
            const noResultsRow = document.querySelector('.no-students').parentElement;
            
            if (visibleRows.length === 0) {
                noResultsRow.style.display = '';
            } else {
                noResultsRow.style.display = 'none';
            }
        }

        searchInput.addEventListener('input', filterStudents);
        groupFilter.addEventListener('change', filterStudents);
    </script>
</body>
</html>