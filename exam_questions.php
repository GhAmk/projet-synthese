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

// Vérifier si l'exam_id est fourni
if (!isset($_GET['exam_id'])) {
    header("Location: view_exams.php");
    exit();
}

$exam_id = $_GET['exam_id'];
$student_id = $_SESSION['user_id'];

// Récupérer les détails de l'examen
$exam_query = "SELECT e.*, es.start_time, es.end_time 
               FROM exams e 
               LEFT JOIN exam_students es ON e.id = es.exam_id AND es.student_id = ? 
               WHERE e.id = ?";
$exam_stmt = $conn->prepare($exam_query);
$exam_stmt->bind_param("ii", $student_id, $exam_id);
$exam_stmt->execute();
$exam_result = $exam_stmt->get_result();
$exam = $exam_result->fetch_assoc();

// Vérifier si l'examen existe
if (!$exam) {
    header("Location: view_exams.php");
    exit();
}

// Récupérer la durée de l'examen
$duration = $exam['duration'];

// Récupérer toutes les questions pour cet examen avec leurs points
$questions_query = "SELECT q.*, qp.points FROM questions q 
                   LEFT JOIN question_points qp ON q.id = qp.question_id 
                   WHERE q.exam_id = ? ORDER BY q.id";
$questions_stmt = $conn->prepare($questions_query);
$questions_stmt->bind_param("i", $exam_id);
$questions_stmt->execute();
$questions_result = $questions_stmt->get_result();

// Stocker les questions dans un tableau
$questions = [];
while ($question = $questions_result->fetch_assoc()) {
    // Si c'est une question QCM, récupérer les choix
    if ($question['question_type'] == 'qcm') {
        $choices_query = "SELECT * FROM choices WHERE question_id = ? ORDER BY id";
        $choices_stmt = $conn->prepare($choices_query);
        $choices_stmt->bind_param("i", $question['id']);
        $choices_stmt->execute();
        $choices_result = $choices_stmt->get_result();
        
        // Récupérer tous les choix
        $choices = [];
        while ($choice = $choices_result->fetch_assoc()) {
            $choices[] = $choice;
        }
        
        // Ajouter les choix à la question
        $question['choices'] = $choices;
    } else {
        // Pour les questions non QCM, initialiser un tableau vide
        $question['choices'] = [];
    }
    
    // Ajouter la question au tableau
    $questions[] = $question;
}

// Vérifier si l'étudiant a déjà commencé l'examen
$check_started = "SELECT * FROM exam_students WHERE exam_id = ? AND student_id = ?";
$started_stmt = $conn->prepare($check_started);
$started_stmt->bind_param("ii", $exam_id, $student_id);
$started_stmt->execute();
$started_result = $started_stmt->get_result();

// Si l'étudiant n'a pas encore commencé l'examen, enregistrer l'heure de début
if ($started_result->num_rows == 0) {
    $start_exam = "INSERT INTO exam_students (exam_id, student_id, start_time, status) VALUES (?, ?, NOW(), 'pending')";
    $start_stmt = $conn->prepare($start_exam);
    $start_stmt->bind_param("ii", $exam_id, $student_id);
    $start_stmt->execute();
} else {
    $exam_status = $started_result->fetch_assoc();
    // Si l'examen est déjà terminé, rediriger
    if ($exam_status['status'] == 'completed') {
        header("Location: student_dashboard.php?message=exam_already_completed");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examen</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/python/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/clike/clike.min.js"></script>
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #34495e;
            --background-color: #f4f6f7;
        }

        body {
            background-color: var(--background-color);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .exam-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .exam-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .exam-header {
            background: linear-gradient(135deg, var(--primary-color), #6a11cb);
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .timer {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            display: flex;
            align-items: center;
        }

        .timer i {
            margin-right: 10px;
        }

        .question-container {
            background-color: white;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .question-header {
            background-color: var(--secondary-color);
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .question-content {
            padding: 1.5rem;
        }

        .points {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .options-container {
            margin-top: 1rem;
        }

        .option {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding: 0.5rem;
            border-radius: 5px;
            transition: background-color 0.2s;
        }

        .option:hover {
            background-color: #f0f0f0;
        }

        .option input {
            margin-right: 10px;
            margin-top: 5px;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-finish {
            background: linear-gradient(135deg, var(--primary-color), #6a11cb);
            border: none;
            padding: 12px 30px;
            font-weight: bold;
            letter-spacing: 1px;
            transition: transform 0.2s;
            color: white;
            margin-top: 1rem;
        }

        .btn-finish:hover {
            transform: scale(1.05);
            background: linear-gradient(135deg, #6a11cb, var(--primary-color));
        }

        .no-questions-alert {
            background-color: #e9ecef;
            border-left: 5px solid var(--primary-color);
            padding: 1rem;
            margin: 1rem 0;
        }

        .question-image {
            max-width: 100%;
            max-height: 300px;
            margin: 1rem 0;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .code-editor-container {
            margin: 1rem 0;
            border: 1px solid #333;
            border-radius: 4px;
            overflow: hidden;
            background-color: #1e1e1e;
        }

        .code-editor-header {
            background-color: #252526;
            padding: 0.5rem;
            border-bottom: 1px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .language-selector {
            padding: 0.25rem 0.5rem;
            border: 1px solid #333;
            border-radius: 4px;
            background-color: #3c3c3c;
            color: #d4d4d4;
        }

        .CodeMirror {
            height: 200px;
            font-family: 'Fira Code', monospace;
            font-size: 14px;
            background-color: #1e1e1e;
            color: #d4d4d4;
        }

        .CodeMirror-gutters {
            background-color: #1e1e1e;
            border-right: 1px solid #333;
        }

        .CodeMirror-linenumber {
            color: #858585;
        }

        .code-editor-wrapper {
            position: relative;
            margin-bottom: 1rem;
        }

        .code-editor-wrapper textarea {
            display: none;
        }
        
        .open-answer-textarea {
            width: 100%;
            min-height: 150px;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 10px;
            font-family: 'Inter', sans-serif;
            margin-top: 1rem;
        }

        /* Styles pour les QCM - mise en évidence des options sélectionnables */
        .option {
            position: relative;
            padding-left: 30px;
            cursor: pointer;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }

        .option:hover {
            background-color: #f8f9fa;
            border-color: #e2e6ea;
        }

        .option input[type="checkbox"] {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
        }

        .option label {
            display: block;
            padding: 10px;
            cursor: pointer;
            margin-bottom: 0;
        }

        /* Texte d'aide pour les QCM */
        .qcm-help-text {
            font-style: italic;
            color: #6c757d;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container exam-container">
        <div class="row">
            <div class="col-12">
                <div class="card exam-card">
                    <div class="exam-header">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-file-alt me-2"></i>
                            Examen: <?php echo htmlspecialchars($exam['title']); ?>
                        </h3>
                        <div class="timer" id="timer">
                            <i class="fas fa-clock"></i>
                            <span>Temps restant: <?php echo $duration; ?>m 0s</span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <form action="correct_exam.php" method="POST" id="examForm">
                            <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                            
                            <?php 
                            if (!empty($questions)):
                                foreach ($questions as $index => $question): 
                            ?>
                                <div class="question-container">
                                    <div class="question-header">
                                        <h4>Question <?php echo $index + 1; ?></h4>
                                        <span class="points"><?php echo $question['points']; ?> points</span>
                                    </div>
                                    
                                    <div class="question-content">
                                        <p><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                                        
                                        <?php if (!empty($question['image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($question['image_path']); ?>" 
                                                 alt="Image de la question" 
                                                 class="question-image">
                                        <?php endif; ?>
                                        
                                        <?php if ($question['question_type'] === 'qcm'): ?>
                                            <?php if (!empty($question['choices'])): ?>
                                                <div class="qcm-help-text">
                                                    <i class="fas fa-info-circle"></i> 
                                                    Vous pouvez sélectionner plusieurs réponses si nécessaire.
                                                </div>
                                                <div class="options-container">
                                                    <?php foreach ($question['choices'] as $choice): ?>
                                                        <div class="option">
                                                            <input type="checkbox" class="form-check-input"
                                                                   id="choice-<?php echo $choice['id']; ?>"
                                                                   name="answers[<?php echo $question['id']; ?>][]" 
                                                                   value="<?php echo $choice['id']; ?>">
                                                            <label for="choice-<?php echo $choice['id']; ?>"><?php echo htmlspecialchars($choice['choice_text']); ?></label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-warning">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    Aucune option n'a été trouvée pour cette question.
                                                </div>
                                            <?php endif; ?>
                                        <?php elseif ($question['question_type'] === 'code'): ?>
                                            <div class="code-editor-container">
                                                <div class="code-editor-header">
                                                    <select class="language-selector" name="language[<?php echo $question['id']; ?>]">
                                                        <?php 
                                                        $languages = [
                                                            'javascript' => 'JavaScript',
                                                            'php' => 'PHP',
                                                            'python' => 'Python',
                                                            'java' => 'Java',
                                                            'cpp' => 'C++',
                                                            'csharp' => 'C#',
                                                            'ruby' => 'Ruby',
                                                            'sql' => 'SQL'
                                                        ];
                                                        
                                                        // Set default language if specified
                                                        $default_lang = !empty($question['programming_language']) ? 
                                                            $question['programming_language'] : 'javascript';
                                                        
                                                        foreach ($languages as $value => $label): 
                                                            $selected = ($value === $default_lang) ? 'selected' : '';
                                                        ?>
                                                            <option value="<?php echo $value; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="code-editor-wrapper">
                                                    <textarea name="answers[<?php echo $question['id']; ?>]" 
                                                              id="editor-<?php echo $question['id']; ?>" class="code-editor"></textarea>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <!-- Questions ouvertes (texte) -->
                                            <div class="form-group mt-3">
                                                <textarea name="answers[<?php echo $question['id']; ?>]" 
                                                          class="open-answer-textarea form-control" 
                                                          placeholder="Votre réponse..." rows="6"></textarea>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php 
                                endforeach;
                            else: 
                            ?>
                                <div class="alert no-questions-alert">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Aucune question n'a été trouvée pour cet examen.
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($questions)): ?>
                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-finish">
                                        <i class="fas fa-paper-plane me-2"></i>
                                        Terminer l'examen
                                    </button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales
        const duration = <?php echo $duration; ?>;
        const totalTime = duration * 60;
        let timeLeft = totalTime;
        let warningCount = 0;
        const maxWarnings = 3;
        const studentId = <?php echo $_SESSION['user_id']; ?>;
        const examId = <?php echo $exam_id; ?>;
        let ws;

        // Connexion WebSocket
        function connectWebSocket() {
            try {
                ws = new WebSocket('ws://localhost:8080');
                
                ws.onopen = function() {
                    console.log('Connecté au serveur de surveillance');
                };

                ws.onclose = function() {
                    console.log('Déconnecté du serveur de surveillance');
                    // Tentative de reconnexion après 5 secondes
                    setTimeout(connectWebSocket, 5000);
                };
            } catch(e) {
                console.log('Impossible de se connecter au serveur WebSocket');
            }
        }

        // Envoyer une alerte de triche
        function sendCheatingAlert(action) {
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({
                    type: 'cheating_alert',
                    student_id: studentId,
                    exam_id: examId,
                    action: action,
                    timestamp: new Date().toISOString()
                }));
            }
        }

        // Gérer les avertissements
        function handleWarning() {
            warningCount++;
            if (warningCount >= maxWarnings) {
                alert('Vous avez reçu trop d\'avertissements. L\'examen sera terminé.');
                document.getElementById('examForm').submit();
            } else {
                alert(`Attention ! Vous avez reçu ${warningCount}/${maxWarnings} avertissements.`);
            }
        }

        // Détecter le changement d'onglet
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                sendCheatingAlert('tab_switch');
                handleWarning();
            }
        });

        // Détecter la perte de focus de la fenêtre
        window.addEventListener('blur', function() {
            sendCheatingAlert('window_focus');
            handleWarning();
        });

        // Empêcher l'ouverture de nouvelles fenêtres
        window.addEventListener('beforeunload', function(e) {
            if (e.target.activeElement && 
                (e.target.activeElement.tagName === 'A' || 
                 e.target.activeElement.tagName === 'BUTTON')) {
                sendCheatingAlert('new_window');
                handleWarning();
            }
        });

        // Détecter les raccourcis clavier
        document.addEventListener('keydown', function(e) {
            // Détecter Alt+Tab, Win+Tab, etc.
            if (e.altKey || e.metaKey) {
                sendCheatingAlert('keyboard_shortcut');
                handleWarning();
            }
        });

        // Initialiser la connexion WebSocket
        try {
            connectWebSocket();
        } catch(e) {
            console.log('Erreur lors de la connexion WebSocket:', e);
        }

        // Initialiser les éditeurs de code
        document.addEventListener('DOMContentLoaded', function() {
            const codeEditors = document.querySelectorAll('.code-editor');
            const editors = [];

            codeEditors.forEach((textarea) => {
                const editor = CodeMirror.fromTextArea(textarea, {
                    mode: "javascript",
                    theme: "monokai",
                    lineNumbers: true,
                    indentUnit: 4,
                    smartIndent: true,
                    lineWrapping: true,
                    matchBrackets: true,
                    autoCloseBrackets: true,
                    extraKeys: {"Ctrl-Space": "autocomplete"},
                    gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
                    foldGutter: true,
                    styleActiveLine: true,
                    autoRefresh: true,
                    readOnly: false // S'assurer que l'éditeur n'est pas en lecture seule
                });
                
                editors.push(editor);

                // Force refresh de l'éditeur après l'affichage complet
                setTimeout(() => {
                    editor.refresh();
                }, 1);

                // Mettre à jour le mode de l'éditeur quand la langue change
                const languageSelector = textarea.closest('.code-editor-container').querySelector('.language-selector');
                languageSelector.addEventListener('change', function() {
                    const mode = this.value;
                    
                    // Mapper les langages aux modes CodeMirror
                    const modeMap = {
                        'javascript': 'javascript',
                        'php': 'php',
                        'python': 'python',
                        'java': 'text/x-java',
                        'cpp': 'text/x-c++src',
                        'csharp': 'text/x-csharp',
                        'ruby': 'ruby',
                        'sql': 'text/x-sql'
                    };
                    
                    editor.setOption("mode", modeMap[mode] || mode);
                    editor.refresh();
                });

                // Déclencher le changement initial pour initialiser le mode
                const event = new Event('change');
                languageSelector.dispatchEvent(event);

                // Sauvegarder le contenu dans le textarea avant la soumission du formulaire
                editor.on('change', function() {
                    editor.save();
                });
            });
        });

        // Mettre à jour le timer
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('timer').querySelector('span').textContent = 
                `Temps restant: ${minutes}m ${seconds}s`;
            
            if (timeLeft <= 0) {
                document.getElementById('examForm').submit();
            } else {
                timeLeft--;
                setTimeout(updateTimer, 1000);
            }
        }

        updateTimer();

        // Sauvegarder le contenu des éditeurs avant la soumission du formulaire
        document.getElementById('examForm').addEventListener('submit', function() {
            // S'assurer que le contenu des éditeurs de code est sauvegardé
            const editors = document.querySelectorAll('.CodeMirror');
            editors.forEach(function(editorElement) {
                if (editorElement.CodeMirror) {
                    editorElement.CodeMirror.save();
                }
            });
        });

        // Améliorer l'expérience avec les cases à cocher pour QCM
        document.querySelectorAll('.option').forEach(function(option) {
            option.addEventListener('click', function(e) {
                // S'assurer que le clic sur le label ou l'option active la case à cocher
                if (e.target !== this.querySelector('input[type="checkbox"]')) {
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    checkbox.checked = !checkbox.checked;
                }
            });
        });
    </script>
</body>
</html>