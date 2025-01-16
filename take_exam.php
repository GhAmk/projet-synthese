<?php
session_start();

// Vérification de la connexion
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "exam_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Vérifier si l'ID de l'examen est fourni
if (!isset($_GET['exam_id'])) {
    header("Location: view_exams.php");
    exit();
}

$exam_id = $_GET['exam_id'];
$student_id = $_SESSION['user_id'];

// Récupérer les informations de l'examen
$exam_query = "SELECT * FROM exams WHERE id = ?";
$exam_stmt = $conn->prepare($exam_query);
$exam_stmt->bind_param("i", $exam_id);
$exam_stmt->execute();
$exam_result = $exam_stmt->get_result();
$exam = $exam_result->fetch_assoc();

if (!$exam) {
    header("Location: view_exams.php");
    exit();
}

// Vérifier si l'étudiant a déjà commencé cet examen
$attempt_query = "SELECT * FROM exam_students 
                 WHERE exam_id = ? AND student_id = ?";
$attempt_stmt = $conn->prepare($attempt_query);
$attempt_stmt->bind_param("ii", $exam_id, $student_id);
$attempt_stmt->execute();
$attempt_result = $attempt_stmt->get_result();

// Si c'est un nouveau départ d'examen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_exam'])) {
    $start_time = date('Y-m-d H:i:s');
    $end_time = date('Y-m-d H:i:s', strtotime("+{$exam['duration']} minutes"));
    
    $insert_query = "INSERT INTO exam_students (exam_id, student_id, start_time, end_time) 
                     VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("iiss", $exam_id, $student_id, $start_time, $end_time);
    $insert_stmt->execute();
    
    header("Location: exam_questions.php?exam_id=" . $exam_id);
    exit();
}

// Récupérer les questions
$questions_query = "SELECT * FROM questions WHERE exam_id = ?";
$questions_stmt = $conn->prepare($questions_query);
$questions_stmt->bind_param("i", $exam_id);
$questions_stmt->execute();
$questions_result = $questions_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commencer l'examen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0"><?php echo htmlspecialchars($exam['title']); ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="exam-info mb-4">
                            <p><strong>Description:</strong> <?php echo htmlspecialchars($exam['description']); ?></p>
                            <p><strong>Durée:</strong> <?php echo htmlspecialchars($exam['duration']); ?> minutes</p>
                            <p><strong>Points totaux:</strong> <?php echo htmlspecialchars($exam['total_points']); ?></p>
                        </div>

                        <div class="alert alert-info">
                            <h4 class="alert-heading">Instructions:</h4>
                            <ul>
                                <li>Vous avez <?php echo htmlspecialchars($exam['duration']); ?> minutes pour compléter l'examen</li>
                                <li>Une fois commencé, le chronomètre ne peut pas être arrêté</li>
                                <li>Assurez-vous d'avoir une connexion internet stable</li>
                                <li>Ne fermez pas votre navigateur pendant l'examen</li>
                            </ul>
                        </div>

                        <form method="POST" class="text-center">
                            <input type="hidden" name="start_exam" value="1">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Commencer l'examen maintenant
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>