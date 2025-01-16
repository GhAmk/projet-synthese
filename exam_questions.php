exam_questions.php
<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "exam_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$exam_id = $_GET['exam_id'];
$student_id = $_SESSION['user_id'];

$attempt_query = "SELECT * FROM exam_students 
                 WHERE exam_id = ? AND student_id = ? 
                 ORDER BY start_time DESC LIMIT 1";
$attempt_stmt = $conn->prepare($attempt_query);
$attempt_stmt->bind_param("ii", $exam_id, $student_id);
$attempt_stmt->execute();
$attempt_result = $attempt_stmt->get_result();
$attempt = $attempt_result->fetch_assoc();

$questions_query = "SELECT q.*, c.choice_text, c.id as choice_id, c.is_correct 
                   FROM questions q 
                   LEFT JOIN choices c ON q.id = c.question_id 
                   WHERE q.exam_id = ?
                   ORDER BY q.id ASC";
$questions_stmt = $conn->prepare($questions_query);
$questions_stmt->bind_param("i", $exam_id);
$questions_stmt->execute();
$questions_result = $questions_stmt->get_result();

$questions = [];
while ($row = $questions_result->fetch_assoc()) {
    $question_id = $row['id'];
    if (!isset($questions[$question_id])) {
        $questions[$question_id] = [
            'id' => $question_id,
            'question_text' => $row['question_text'],
            'question_type' => $row['question_type'],
            'choices' => []
        ];
    }
    if ($row['choice_id']) {
        $questions[$question_id]['choices'][] = [
            'id' => $row['choice_id'],
            'text' => $row['choice_text'],
            'is_correct' => $row['is_correct']
        ];
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questions d'examen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <form method="POST" id="exam-form">
                    <?php 
                    $question_num = 1;
                    foreach ($questions as $question): 
                    ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4>Question <?php echo $question_num; ?></h4>
                            </div>
                            <div class="card-body">
                                <p class="lead"><?php echo htmlspecialchars($question['question_text']); ?></p>
                                
                                <?php if ($question['question_type'] === 'qcm'): ?>
                                    <?php foreach ($question['choices'] as $choice): ?>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" 
                                                   type="radio" 
                                                   name="answers[<?php echo $question['id']; ?>]" 
                                                   value="<?php echo $choice['id']; ?>" 
                                                   required>
                                            <label class="form-check-label">
                                                <?php echo htmlspecialchars($choice['text']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <textarea class="form-control" 
                                              name="answers[<?php echo $question['id']; ?>]" 
                                              rows="3" 
                                              required></textarea>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php 
                    $question_num++;
                    endforeach; 
                    ?>
                    
                    <div class="text-center mt-4">
                        <button type="submit" name="submit_exam" class="btn btn-primary btn-lg">
                            Terminer l'examen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>