<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un enseignant
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php"); // Rediriger vers la page de login
    exit();
}

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "exam_system");

if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'] ?? '';
    $duration = $_POST['duration'];
    $attempts = $_POST['attempts'];
    $total_points = $_POST['total_points'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $teacher_id = $_SESSION['user_id'];

    // Insertion de l'examen dans la base de données
    $stmt = $conn->prepare("INSERT INTO exams (title, description, teacher_id, start_time, end_time, duration, attempts, total_points) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssissiii", $title, $description, $teacher_id, $start_date, $end_date, $duration, $attempts, $total_points);

    if ($stmt->execute()) {
        // Récupérer l'ID de l'examen créé
        $exam_id = $stmt->insert_id;

        // Insérer les questions
        if (!empty($_POST['questions'])) {
            $questions = $_POST['questions'];
            $question_points = $_POST['question_points'];
            $question_types = $_POST['question_type'];

            for ($i = 0; $i < count($questions); $i++) {
                $stmt_question = $conn->prepare("INSERT INTO questions (exam_id, question_text, question_type) VALUES (?, ?, ?)");
                $stmt_question->bind_param("iss", $exam_id, $questions[$i], $question_types[$i]);
                $stmt_question->execute();
                $question_id = $stmt_question->insert_id;

                // Si c'est un QCM, insérer les options
                if ($question_types[$i] === 'qcm' && !empty($_POST['options'][$i]['text'])) {
                    $options = $_POST['options'][$i]['text'];
                    $correct = $_POST['options'][$i]['correct'];

                    for ($j = 0; $j < count($options); $j++) {
                        $is_correct = isset($correct[$j]) ? 1 : 0;
                        $stmt_option = $conn->prepare("INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)");
                        $stmt_option->bind_param("isi", $question_id, $options[$j], $is_correct);
                        $stmt_option->execute();
                    }
                }
            }
        }

        // Rediriger vers la page des examens
        header("Location: Teacher_dashboard.php");
        exit();
    } else {
        $error = "Erreur lors de la création de l'examen.";
    }
}
?>
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Examen</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --danger: #dc2626;
            --success: #10b981;
            --background: #f8fafc;
            --surface: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --input-bg: #f8fafc;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--background);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--text-primary);
            line-height: 1.5;
            padding: 2rem 1rem;
            min-height: 100vh;
        }

        .exam-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            background-color: var(--surface);
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: var(--shadow-lg);
        }

        h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            text-align: center;
            margin-bottom: 2.5rem;
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

        input[type="text"],
        input[type="number"],
        input[type="datetime-local"],
        select {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid var(--border);
            background-color: var(--input-bg);
            font-size: 1rem;
            color: var(--text-primary);
            transition: all 0.2s ease;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background-color: var(--surface);
        }

        button,
        input[type="submit"] {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }

        button:hover,
        input[type="submit"]:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        #questions-container {
            margin-top: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .question {
            background-color: var(--input-bg);
            border: 1px solid var(--border);
            padding: 1.5rem;
            border-radius: 0.75rem;
            display: grid;
            gap: 1rem;
        }

        .options-container {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }

        .option {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 1rem;
            align-items: center;
        }

        .option button {
            background-color: var(--danger);
            padding: 0.5rem 1rem;
        }

        .option button:hover {
            background-color: #b91c1c;
        }

        .add-option-btn {
            background-color: var(--success);
            margin-top: 1rem;
        }

        .add-option-btn:hover {
            background-color: #059669;
        }

        .error-message {
            color: var(--danger);
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        @media (max-width: 640px) {
            .exam-container {
                padding: 1.5rem;
            }

            .option {
                grid-template-columns: 1fr;
            }
        }
    </style></head>
<body>

<div class="exam-container">
    <h2>Créer un Examen</h2>
    
    <form method="POST" action="create_exam.php" id="exam-form">
        <!-- Titre de l'examen -->
        <label for="title">Titre de l'examen:</label>
        <input type="text" name="title" id="title" required placeholder="Titre de l'examen">

        <!-- Durée de l'examen -->
        <label for="duration">Durée (en minutes):</label>
        <input type="number" name="duration" id="duration" required placeholder="Durée de l'examen">

        <!-- Nombre de tentatives autorisées -->
        <label for="attempts">Nombre de tentatives autorisées:</label>
        <input type="number" name="attempts" id="attempts" required placeholder="Tentatives autorisées">

        <!-- Total des points de l'examen -->
        <label for="total_points">Total des points de l'examen:</label>
        <input type="number" name="total_points" id="total_points" required placeholder="Total des points de l'examen">

        <!-- Date de début de l'examen -->
        <label for="start_date">Date de début:</label>
        <input type="datetime-local" name="start_date" id="start_date" required>

        <!-- Date de fin de l'examen -->
        <label for="end_date">Date de fin:</label>
        <input type="datetime-local" name="end_date" id="end_date" required>

        <!-- Section pour ajouter des questions -->
        <div id="questions-container">
            <button type="button" id="add-question-btn">Ajouter une question</button>
        </div>

        <!-- Bouton pour soumettre l'examen -->
        <input type="submit" value="Créer l'examen">
    </form>
</div>

<script>
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const form = document.getElementById('exam-form');

    // Vérification des dates
    function validateDates() {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);

        if (endDate <= startDate) {
            alert('La date de fin doit être après la date de début.');
            endDateInput.value = '';
        }
    }

    // Vérification globale lors de la soumission du formulaire
    form.addEventListener('submit', function (e) {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);

        if (endDate <= startDate) {
            e.preventDefault();
            alert('La date de fin doit être après la date de début.');
        }
    });

    startDateInput.addEventListener('change', validateDates);
    endDateInput.addEventListener('change', validateDates);

    // Ajout de nouvelles questions
    document.getElementById('add-question-btn').addEventListener('click', function () {
        const questionSection = document.createElement('div');
        questionSection.classList.add('question');

        // Texte de la question
        const questionText = document.createElement('input');
        questionText.setAttribute('type', 'text');
        questionText.setAttribute('name', 'questions[]');
        questionText.setAttribute('placeholder', 'Entrez votre question ici');
        questionText.required = true;

        // Points pour chaque question
        const questionPoints = document.createElement('input');
        questionPoints.setAttribute('type', 'number');
        questionPoints.setAttribute('name', 'question_points[]');
        questionPoints.setAttribute('placeholder', 'Points pour cette question');
        questionPoints.required = true;
        questionPoints.addEventListener('input', validateTotalPoints);

        // Type de question
        const questionType = document.createElement('select');
        questionType.setAttribute('name', 'question_type[]');
        const option1 = document.createElement('option');
        option1.value = 'open';
        option1.textContent = 'Question ouverte';
        const option2 = document.createElement('option');
        option2.value = 'qcm';
        option2.textContent = 'QCM';
        questionType.appendChild(option1);
        questionType.appendChild(option2);

        // Conteneur pour les options QCM
        const optionsContainer = document.createElement('div');
        optionsContainer.classList.add('options-container');
        optionsContainer.style.display = 'none';

        const addOptionBtn = document.createElement('button');
        addOptionBtn.type = 'button';
        addOptionBtn.textContent = 'Ajouter une option';
        addOptionBtn.style.display = 'none';

        addOptionBtn.addEventListener('click', function () {
            const optionDiv = document.createElement('div');
            optionDiv.classList.add('option');
            optionDiv.innerHTML = `
                <input type="text" name="options[][text][]" placeholder="Texte de l'option">
                <label>
                    Correct:
                    <input type="checkbox" name="options[][correct][]">
                </label>
                <button type="button" class="remove-option-btn">Supprimer</button>
            `;
            optionDiv.querySelector('.remove-option-btn').addEventListener('click', function () {
                optionDiv.remove();
            });
            optionsContainer.appendChild(optionDiv);
        });

        questionType.addEventListener('change', function () {
            if (this.value === 'qcm') {
                optionsContainer.style.display = 'block';
                addOptionBtn.style.display = 'inline-block';
            } else {
                optionsContainer.style.display = 'none';
                addOptionBtn.style.display = 'none';
            }
        });

        questionSection.appendChild(questionText);
        questionSection.appendChild(questionPoints);
        questionSection.appendChild(questionType);
        questionSection.appendChild(optionsContainer);
        questionSection.appendChild(addOptionBtn);

        document.getElementById('questions-container').appendChild(questionSection);
    });

    // Validation des points totaux
    function validateTotalPoints() {
        const totalPointsInput = document.getElementById('total_points');
        const totalPoints = parseInt(totalPointsInput.value, 10) || 0;

        let sumPoints = 0;
        document.querySelectorAll('[name="question_points[]"]').forEach(input => {
            sumPoints += parseInt(input.value, 10) || 0;
        });

        if (sumPoints > totalPoints) {
            alert('Le total des points pour toutes les questions dépasse le total défini pour l\'examen.');
            this.value = '';
        }
    }
</script>

</body>
</html>