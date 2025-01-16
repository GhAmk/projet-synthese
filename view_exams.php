view_exams.php
<?php
session_start();

// Check if user is logged in as student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "exam_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current date and time
$current_datetime = date('Y-m-d H:i:s');

// Get available exams
$query = "SELECT e.*, u.name as teacher_name 
          FROM exams e 
          JOIN users u ON e.teacher_id = u.id 
          WHERE e.start_time <= ? AND e.end_time >= ?
          ORDER BY e.start_time ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $current_datetime, $current_datetime);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examens Disponibles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .exam-card {
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .exam-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col">
                <h1 class="display-4">Examens Disponibles</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Examens Disponibles</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($exam = $result->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card exam-card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($exam['title']); ?></h5>
                                <p class="card-text">
                                    <?php if (!empty($exam['description'])): ?>
                                        <?php echo htmlspecialchars($exam['description']); ?>
                                    <?php endif; ?>
                                </p>
                                <ul class="list-unstyled">
                                    <li><i class="bi bi-clock"></i> Durée: <?php echo htmlspecialchars($exam['duration']); ?> minutes</li>
                                    <li><i class="bi bi-trophy"></i> Points: <?php echo htmlspecialchars($exam['total_points']); ?></li>
                                    <li><i class="bi bi-person"></i> Professeur: <?php echo htmlspecialchars($exam['teacher_name']); ?></li>
                                    <li><i class="bi bi-calendar"></i> Début: <?php echo date('d/m/Y H:i', strtotime($exam['start_time'])); ?></li>
                                    <li><i class="bi bi-calendar"></i> Fin: <?php echo date('d/m/Y H:i', strtotime($exam['end_time'])); ?></li>
                                </ul>
                                
                                <?php
                                // Check if student has already attempted this exam
                                $attempt_query = "SELECT COUNT(*) as attempts FROM exam_students 
                                                WHERE exam_id = ? AND student_id = ?";
                                $attempt_stmt = $conn->prepare($attempt_query);
                                $student_id = $_SESSION['user_id'];
                                $attempt_stmt->bind_param("ii", $exam['id'], $student_id);
                                $attempt_stmt->execute();
                                $attempt_result = $attempt_stmt->get_result();
                                $attempts = $attempt_result->fetch_assoc()['attempts'];
                                ?>

                                <?php if ($attempts < $exam['attempts']): ?>
                                    <a href="take_exam.php?exam_id=<?php echo $exam['id']; ?>" 
                                       class="btn btn-primary">
                                        Commencer l'examen
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled>
                                        Nombre maximum de tentatives atteint
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col">
                    <div class="alert alert-info" role="alert">
                        Aucun examen n'est disponible pour le moment.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>