<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Vérifier que l'utilisateur est connecté et est un enseignant
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];

// Vérifier si la table triches_attempts existe, sinon la créer
$checkTableSql = "SHOW TABLES LIKE 'triches_attempts'";
$tableExists = $conn->query($checkTableSql)->num_rows > 0;

if (!$tableExists) {
    $createTableSql = "CREATE TABLE triches_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_id INT NOT NULL,
        student_id INT NOT NULL,
        description TEXT NOT NULL,
        status ENUM('new', 'reviewed') NOT NULL DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $conn->query($createTableSql);
}

// Fonction pour récupérer les tentatives de triches
function getTrichesAttempts($conn, $teacher_id) {
    $sql = "SELECT t.id, t.exam_id, t.student_id, t.description, t.created_at, t.status,
                   e.title as exam_title, u.name as student_name
            FROM triches_attempts t
            JOIN exams e ON t.exam_id = e.id
            JOIN users u ON t.student_id = u.id
            WHERE e.teacher_id = ?
            ORDER BY t.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $triches = [];
    while ($row = $result->fetch_assoc()) {
        $triches[] = $row;
    }
    
    return $triches;
}

// Traiter les actions
if (isset($_POST['action']) && isset($_POST['triche_id'])) {
    $triche_id = $_POST['triche_id'];
    $action = $_POST['action'];
    
    if ($action === 'mark_reviewed') {
        $sql = "UPDATE triches_attempts SET status = 'reviewed' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $triche_id);
        $stmt->execute();
    } elseif ($action === 'delete') {
        $sql = "DELETE FROM triches_attempts WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $triche_id);
        $stmt->execute();
    }
    
    // Rediriger pour éviter la soumission multiple du formulaire
    header('Location: tentatives_triches.php');
    exit;
}

// Récupérer les tentatives de triches
$triches = getTrichesAttempts($conn, $teacher_id);

// Initialiser les tableaux pour les statistiques
$examStats = [];
$studentStats = [];

// Calculer les statistiques
foreach ($triches as $triche) {
    // Statistiques par examen
    if (!isset($examStats[$triche['exam_id']])) {
        $examStats[$triche['exam_id']] = [
            'title' => $triche['exam_title'],
            'count' => 0
        ];
    }
    $examStats[$triche['exam_id']]['count']++;
    
    // Statistiques par étudiant
    if (!isset($studentStats[$triche['student_id']])) {
        $studentStats[$triche['student_id']] = [
            'name' => $triche['student_name'],
            'count' => 0
        ];
    }
    $studentStats[$triche['student_id']]['count']++;
}

// Trier les statistiques par nombre de triches décroissant
uasort($examStats, function($a, $b) {
    return $b['count'] - $a['count'];
});

uasort($studentStats, function($a, $b) {
    return $b['count'] - $a['count'];
});

// Fonction pour formater les dates (ajout de cette fonction manquante)
function formatDate($date, $showFullDate = false) {
    if ($showFullDate) {
        return date('d/m/Y H:i:s', strtotime($date));
    } else {
        return date('d/m/Y', strtotime($date));
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des tentatives de triches</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <style>
:root {
    --primary-color: #4361ee;
    --secondary-color: #3bc9db;
    --danger-color: #e63946;
    --warning-color: #ff9f1c;
    --success-color: #2ecc71;
    --dark-color: #2b2d42;
    --light-color: #f8f9fa;
    --border-radius: 12px;
    --box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
    --transition: all 0.3s ease;
}

body {
    font-family: 'Poppins', sans-serif;
    background-color: #f5f8ff;
    color: #333;
}

.navbar {
    box-shadow: var(--box-shadow);
    background: linear-gradient(135deg, var(--primary-color), #3a0ca3);
}

.navbar-brand {
    font-weight: 700;
    color: white !important;
}

.navbar .nav-link {
    color: rgba(255, 255, 255, 0.85) !important;
    transition: var(--transition);
    font-weight: 500;
    padding: 0.7rem 1rem !important;
    margin: 0 0.2rem;
    border-radius: 6px;
}

.navbar .nav-link:hover, .navbar .nav-link.active {
    color: white !important;
    background-color: rgba(255, 255, 255, 0.1);
}

.container {
    max-width: 1400px;
    padding: 0 1.5rem;
}

h1 {
    color: var(--dark-color);
    font-weight: 700;
    margin-bottom: 1.8rem;
    position: relative;
    padding-bottom: 12px;
    font-size: 2.2rem;
}

h1::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    border-radius: 20px;
}

.card {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    transition: var(--transition);
    margin-bottom: 28px;
    overflow: hidden;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.12);
}

.card-header {
    background-color: white;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    font-weight: 600;
    padding: 1.5rem;
    display: flex;
    align-items: center;
}

.card-header h5 {
    margin-bottom: 0;
    font-weight: 600;
    color: var(--dark-color);
    font-size: 1.15rem;
}

.card-header i {
    color: var(--primary-color);
    margin-right: 10px;
}

.card-body {
    padding: 1.5rem;
}

.table {
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0 8px;
}

.table thead th {
    border: none;
    font-weight: 600;
    color: var(--dark-color);
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    padding: 1rem 1rem;
    background-color: rgba(240, 242, 247, 0.8);
}

.table tbody tr {
    transition: var(--transition);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
    border-radius: 8px;
    background-color: white;
}

.table tbody td {
    padding: 1.2rem 1rem;
    vertical-align: middle;
    border: none;
}

.table tbody tr:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 12px rgba(0, 0, 0, 0.08);
}

.badge {
    font-weight: 500;
    padding: 0.5em 0.8em;
    font-size: 0.75em;
    border-radius: 6px;
    letter-spacing: 0.3px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.bg-warning {
    background-color: var(--warning-color) !important;
}

.bg-success {
    background-color: var(--success-color) !important;
}

.bg-primary {
    background-color: var(--primary-color) !important;
}

.btn {
    border-radius: 6px;
    font-weight: 500;
    letter-spacing: 0.3px;
    transition: var(--transition);
}

.btn-sm {
    padding: 0.4rem 0.8rem;
    font-size: 0.8rem;
    border-radius: 6px;
}

.btn i {
    transition: transform 0.2s ease;
}

.btn:hover i {
    transform: translateX(2px);
}

.btn-success {
    background-color: var(--success-color);
    border-color: var(--success-color);
}

.btn-danger {
    background-color: var(--danger-color);
    border-color: var(--danger-color);
}

.btn-success:hover, .btn-success:focus {
    background-color: #27ae60;
    border-color: #27ae60;
    transform: translateY(-2px);
}

.btn-danger:hover, .btn-danger:focus {
    background-color: #c0392b;
    border-color: #c0392b;
    transform: translateY(-2px);
}

.table-warning {
    background-color: rgba(255, 159, 28, 0.08);
}

.stat-card {
    border-left: 5px solid var(--primary-color);
    overflow: hidden;
    position: relative;
}

.stat-card:nth-child(1) {
    border-left-color: #4361ee;
}

.stat-card:nth-child(2) {
    border-left-color: #ff9f1c;
}

.stat-card:nth-child(3) {
    border-left-color: #3bc9db;
}

.stat-card .card-body {
    position: relative;
    z-index: 2;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    background: linear-gradient(45deg, rgba(255, 255, 255, 0), rgba(67, 97, 238, 0.05));
    z-index: 1;
}

.stat-card .stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 5px;
    line-height: 1;
}

.stat-card .stat-label {
    font-size: 0.9rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
}

.stat-card i {
    color: rgba(0, 0, 0, 0.1);
    transition: var(--transition);
}

.stat-card:hover i {
    transform: scale(1.1);
    color: rgba(67, 97, 238, 0.2);
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 3.5rem;
    margin-bottom: 1.5rem;
    color: #d1d3e2;
    background: linear-gradient(135deg, #e6e9f0, #eef1f5);
    width: 100px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    margin: 0 auto 1.5rem;
    box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.06);
}

.empty-state h5 {
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--dark-color);
}

.empty-state p {
    font-size: 1.1rem;
    opacity: 0.7;
}

.action-buttons {
    white-space: nowrap;
}

.progress {
    height: 20px;
    border-radius: 10px;
    overflow: hidden;
    background-color: #f0f2f7;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
}

.progress-bar {
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.8rem;
    line-height: 20px;
    box-shadow: 0 1px 5px rgba(59, 201, 219, 0.3);
}

.progress-bar.bg-info {
    background: linear-gradient(90deg, var(--secondary-color), #4cc9f0) !important;
}

/* Animation on scroll */
.fade-in {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.5s ease, transform 0.5s ease;
}

.fade-in.visible {
    opacity: 1;
    transform: translateY(0);
}

/* Avatar Circle */
.avatar-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 16px;
    text-transform: uppercase;
}

/* Dark Mode */
.dark-mode {
    background-color: #121212;
    color: #e6e6e6;
}

.dark-mode .card {
    background-color: #1e1e1e;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
}

.dark-mode .card-header {
    background-color: #252525;
    border-bottom: 1px solid #333;
}

.dark-mode h1, .dark-mode .card-header h5 {
    color: #fff;
}

.dark-mode .table thead th {
    background-color: #252525;
    color: #fff;
}

.dark-mode .table tbody tr {
    background-color: #2d2d2d;
}

.dark-mode .stat-card .stat-value {
    color: #fff;
}

.dark-mode .stat-card .stat-label {
    color: #aaa;
}

.dark-mode .empty-state {
    color: #aaa;
}

.dark-mode .empty-state i {
    background: linear-gradient(135deg, #333, #444);
    color: #666;
}

.dark-mode .empty-state h5 {
    color: #eee;
}

.dark-mode .progress {
    background-color: #333;
}

.dark-mode .table-warning {
    background-color: rgba(255, 159, 28, 0.15);
}

/* Pulse animation for badges */
.pulse {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

@media (max-width: 768px) {
    h1 {
        font-size: 1.8rem;
    }
    
    .card-header {
        padding: 1.2rem;
    }
    
    .action-buttons {
        white-space: normal;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .action-buttons .btn {
        width: 100%;
    }
    
    .stat-card .stat-value {
        font-size: 1.6rem;
    }
    
    .table thead th, .table tbody td {
        padding: 0.8rem;
    }
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animated {
    animation-duration: 0.6s;
    animation-fill-mode: both;
}

.fadeInUp {
    animation-name: fadeInUp;
}

.delay-1 {
    animation-delay: 0.1s;
}

.delay-2 {
    animation-delay: 0.2s;
}

.delay-3 {
    animation-delay: 0.3s;
}

/* Tooltips */
[data-tooltip] {
    position: relative;
    cursor: pointer;
}

[data-tooltip]:before {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    padding: 5px 10px;
    background: var(--dark-color);
    color: white;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 10;
}

[data-tooltip]:hover:before {
    opacity: 1;
    visibility: visible;
    bottom: calc(100% + 10px);
}

/* Theme Toggle Button */
.theme-toggle {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: var(--primary-color);
    color: white;
    border: none;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: var(--transition);
    z-index: 1000;
}

.theme-toggle:hover {
    transform: translateY(-3px) rotate(10deg);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-12 mb-4">
                <h1 class="animated fadeInUp"><i class="fas fa-user-shield me-3"></i>Gestion des tentatives de triches</h1>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stat-card animated fadeInUp delay-1">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-value"><?php echo count($triches); ?></div>
                                <div class="stat-label">Tentatives totales</div>
                            </div>
                            <i class="fas fa-clipboard-list fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card animated fadeInUp delay-2">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-value"><?php echo count(array_filter($triches, function($t) { return $t['status'] === 'new'; })); ?></div>
                                <div class="stat-label">Nouvelles alertes</div>
                            </div>
                            <i class="fas fa-exclamation-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card animated fadeInUp delay-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-value"><?php echo count($examStats); ?></div>
                                <div class="stat-label">Examens concernés</div>
                            </div>
                            <i class="fas fa-book fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-lg-8">
                <div class="card fade-in">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Liste des tentatives de triches</h5>
                        <span class="badge bg-primary"><?php echo count($triches); ?> enregistrements</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($triches)): ?>
                            <div class="empty-state">
                                <i class="fas fa-check-circle"></i>
                                <h5>Aucune tentative de triche détectée</h5>
                                <p>Tout semble en ordre pour le moment.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover custom-table">
                                    <thead>
                                        <tr>
                                            <th>Examen</th>
                                            <th>Étudiant</th>
                                            <th>Description</th>
                                            <th>Date</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($triches as $triche): ?>
                                            <tr class="<?php echo $triche['status'] === 'new' ? 'table-warning' : ''; ?>">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-file-alt me-2 text-primary"></i>
                                                        <span><?php echo htmlspecialchars($triche['exam_title']); ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-circle me-2">
                                                            <?php echo substr(htmlspecialchars($triche['student_name']), 0, 1); ?>
                                                        </div>
                                                        <span><?php echo htmlspecialchars($triche['student_name']); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($triche['description']); ?></td>
                                                <td>
                                                    <div data-tooltip="<?php echo formatDate($triche['created_at'], true); ?>">
                                                        <i class="far fa-clock me-1"></i> <?php echo formatDate($triche['created_at']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $triche['status'] === 'new' ? 'bg-warning' : 'bg-success'; ?>">
                                                        <?php echo $triche['status'] === 'new' ? 'Nouveau' : 'Examiné'; ?>
                                                    </span>
                                                </td>
                                                <td class="action-buttons">
                                                    <form method="post" style="display: inline;">
                                                        <input type="hidden" name="triche_id" value="<?php echo $triche['id']; ?>">
                                                        <?php if ($triche['status'] === 'new'): ?>
                                                            <button type="submit" name="action" value="mark_reviewed" class="btn btn-sm btn-success me-1">
                                                                <i class="fas fa-check me-1"></i>Examiné
                                                            </button>
                                                        <?php endif; ?>
                                                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" 
                                                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette alerte ?');">
                                                            <i class="fas fa-trash me-1"></i>Supprimer
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Statistiques des triches par examen -->
                <div class="card mb-4 fade-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Statistiques par examen</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($examStats)): ?>
                            <div class="empty-state py-3">
                                <i class="fas fa-chart-bar"></i>
                                <p>Aucune donnée disponible</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table custom-table">
                                    <thead>
                                        <tr>
                                            <th>Examen</th>
                                            <th>Alertes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($examStats as $exam_id => $exam): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-book-open me-2 text-primary"></i>
                                                        <?php echo htmlspecialchars($exam['title']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-info" role="progressbar" 
                                                             style="width: <?php echo min(100, ($exam['count'] / max(1, max(array_column($examStats, 'count'))) * 100)); ?>%" 
                                                             aria-valuenow="<?php echo $exam['count']; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="<?php echo max(array_column($examStats, 'count')); ?>">
                                                            <?php echo $exam['count']; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Statistiques des triches par étudiant -->
                <div class="card fade-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Statistiques par étudiant</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($studentStats)): ?>
                            <div class="empty-state py-3">
                                <i class="fas fa-user-graduate"></i>
                                <p>Aucune donnée disponible</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table custom-table">
                                    <thead>
                                        <tr>
                                            <th>Étudiant</th>
                                            <th>Alertes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($studentStats as $student_id => $student): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-circle me-2" style="background-color: <?php echo sprintf('#%06X', crc32($student['name']) & 0xFFFFFF); ?>">
                                                            <?php echo substr(htmlspecialchars($student['name']), 0, 1); ?>
                                                        </div>
                                                        <?php echo htmlspecialchars($student['name']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-info" role="progressbar" 
                                                             style="width: <?php echo min(100, ($student['count'] / max(1, max(array_column($studentStats, 'count'))) * 100)); ?>%" 
                                                             aria-valuenow="<?php echo $student['count']; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="<?php echo max(array_column($studentStats, 'count')); ?>">
                                                            <?php echo $student['count']; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Theme Toggle Button -->
    <button class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon"></i>
    </button>

    <!-- Bootstrap & jQuery Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        // Fade-in animation on scroll
        $(document).ready(function() {
            // Check if element is in viewport
            function isElementInViewport(el) {
                var rect = el.getBoundingClientRect();
                return (
                    rect.top >= 0 &&
                    rect.left >= 0 &&
                    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
                );
            }
            
            // Add visible class to elements in viewport
            function handleVisibility() {
                $('.fade-in').each(function() {
                    if (isElementInViewport(this)) {
                        $(this).addClass('visible');
                    }
                });
            }
            
            // Run on page load
            handleVisibility();
            
            // Run on scroll and resize
            $(window).on('scroll resize', handleVisibility);
            
            // Theme toggle functionality
            $('#themeToggle').click(function() {
                $('body').toggleClass('dark-mode');
                $(this).find('i').toggleClass('fa-moon fa-sun');
                
                // Save preference to localStorage
                if ($('body').hasClass('dark-mode')) {
                    localStorage.setItem('theme', 'dark');
                } else {
                    localStorage.setItem('theme', 'light');
                }
            });
            
            // Check for saved theme preference
            if (localStorage.getItem('theme') === 'dark') {
                $('body').addClass('dark-mode');
                $('#themeToggle').find('i').removeClass('fa-moon').addClass('fa-sun');
            }
            
            // Set random colors for avatar circles
            $('.avatar-circle').each(function() {
                var randomColor = '#' + Math.floor(Math.random()*16777215).toString(16);
                $(this).css('background-color', randomColor);
            });
        });
    </script>
</body>
</html>