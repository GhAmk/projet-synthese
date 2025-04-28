<?php
// Start session to store quiz data
session_start();

// Sample quiz data (hardcoded for demonstration)
$quiz = [
    'title' => 'Sample Quiz',
    'time_limit' => 300, // in seconds (5 minutes)
    'questions' => [
        1 => [
            'id' => 1,
            'question_text' => 'What is the capital of France?',
            'options' => [
                ['id' => 1, 'option_text' => 'London'],
                ['id' => 2, 'option_text' => 'Berlin'],
                ['id' => 3, 'option_text' => 'Paris'],
                ['id' => 4, 'option_text' => 'Madrid']
            ],
            'correct_option' => 3
        ],
        2 => [
            'id' => 2,
            'question_text' => 'Which planet is known as the Red Planet?',
            'options' => [
                ['id' => 5, 'option_text' => 'Jupiter'],
                ['id' => 6, 'option_text' => 'Mars'],
                ['id' => 7, 'option_text' => 'Venus'],
                ['id' => 8, 'option_text' => 'Mercury']
            ],
            'correct_option' => 6
        ],
        3 => [
            'id' => 3,
            'question_text' => 'What is 2 + 2?',
            'options' => [
                ['id' => 9, 'option_text' => '3'],
                ['id' => 10, 'option_text' => '4'],
                ['id' => 11, 'option_text' => '5'],
                ['id' => 12, 'option_text' => '22']
            ],
            'correct_option' => 10
        ],
        4 => [
            'id' => 4,
            'question_text' => 'Which of these is not a programming language?',
            'options' => [
                ['id' => 13, 'option_text' => 'Java'],
                ['id' => 14, 'option_text' => 'Python'],
                ['id' => 15, 'option_text' => 'Banana'],
                ['id' => 16, 'option_text' => 'PHP']
            ],
            'correct_option' => 15
        ],
        5 => [
            'id' => 5,
            'question_text' => 'Who painted the Mona Lisa?',
            'options' => [
                ['id' => 17, 'option_text' => 'Vincent Van Gogh'],
                ['id' => 18, 'option_text' => 'Pablo Picasso'],
                ['id' => 19, 'option_text' => 'Leonardo da Vinci'],
                ['id' => 20, 'option_text' => 'Michelangelo']
            ],
            'correct_option' => 19
        ]
    ]
];

// Store quiz in session if not already stored
if (!isset($_SESSION['quiz'])) {
    $_SESSION['quiz'] = $quiz;
    $_SESSION['user_answers'] = [];
    $_SESSION['start_time'] = time();
}

// Get current question number from URL parameter, default to 1
$current_question = isset($_GET['q']) ? intval($_GET['q']) : 1;
$total_questions = count($_SESSION['quiz']['questions']);

// Ensure question number is valid
if ($current_question < 1) {
    $current_question = 1;
} elseif ($current_question > $total_questions) {
    $current_question = $total_questions;
}

// Get the current question
$question = $_SESSION['quiz']['questions'][$current_question];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['selected_option'])) {
    $question_id = $_POST['question_id'];
    $selected_option = $_POST['selected_option'];
    
    // Save user's answer
    $_SESSION['user_answers'][$question_id] = $selected_option;
    
    // Redirect to next question or results
    $next_question = $current_question + 1;
    if ($next_question <= $total_questions) {
        header("Location: quiz.php?q=$next_question");
        exit();
    } else {
        // Calculate score
        $score = 0;
        foreach ($_SESSION['user_answers'] as $q_id => $selected_opt) {
            if (isset($_SESSION['quiz']['questions'][$q_id]) && 
                $_SESSION['quiz']['questions'][$q_id]['correct_option'] == $selected_opt) {
                $score++;
            }
        }
        
        // Store score and redirect to results
        $_SESSION['score'] = $score;
        header("Location: quiz_results.php");
        exit();
    }
}

// Calculate remaining time
$elapsed_time = time() - $_SESSION['start_time'];
$remaining_time = max(0, $_SESSION['quiz']['time_limit'] - $elapsed_time);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Quiz Application</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #a855f7;
            --accent-color: #ec4899;
            --text-color: #334155;
            --light-text: #94a3b8;
            --dark-bg: #1e293b;
            --light-bg: #f8fafc;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            background-attachment: fixed;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            transform: translateY(0);
            transition: var(--transition);
            animation: fadeIn 0.6s ease-out;
        }

        .container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .quiz-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f1f5f9;
            position: relative;
        }

        h1 {
            color: var(--primary-color);
            font-size: 2.2rem;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        h1::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 60%;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: 2px;
            animation: widthPulse 2s infinite;
        }

        @keyframes widthPulse {
            0% { width: 30%; }
            50% { width: 70%; }
            100% { width: 30%; }
        }

        .timer {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        .timer i {
            color: var(--accent-color);
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .progress-container {
            margin-bottom: 25px;
        }

        .progress {
            height: 8px;
            background-color: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color), var(--accent-color));
            background-size: 200% 100%;
            border-radius: 4px;
            transition: width 0.6s ease;
            animation: gradientMove 3s linear infinite;
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .question-counter {
            font-size: 1rem;
            color: var(--light-text);
            margin-top: 8px;
            animation: fadeInUp 0.5s;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .question {
            margin-bottom: 30px;
            padding: 25px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border-left: 4px solid var(--primary-color);
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .question:hover {
            box-shadow: var(--shadow-md);
            transform: translateX(5px);
        }

        .question h3 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            color: var(--dark-bg);
            display: flex;
            align-items: center;
        }

        .question h3::before {
            content: '\f059';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-right: 10px;
            color: var(--primary-color);
            font-size: 1.4rem;
        }

        .options {
            list-style-type: none;
            padding: 0;
        }

        .options li {
            margin-bottom: 12px;
        }

        .option-container {
            position: relative;
            cursor: pointer;
        }

        .option-label {
            display: flex;
            align-items: center;
            padding: 15px;
            background-color: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            transition: var(--transition);
            overflow: hidden;
        }

        .option-label:hover {
            background-color: #f1f5f9;
            border-color: #cbd5e1;
            transform: translateX(5px);
        }

        .option-input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .option-input:checked + .option-label {
            border-color: var(--primary-color);
            background-color: rgba(99, 102, 241, 0.1);
        }

        .option-input:checked + .option-label::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background-color: var(--primary-color);
            animation: slideRight 0.3s forwards;
        }

        @keyframes slideRight {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }

        .option-text {
            font-size: 1rem;
            margin-left: 10px;
        }

        .option-input:checked + .option-label .option-checkmark {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .option-input:checked + .option-label .option-checkmark::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: white;
            font-size: 0.8rem;
            display: block;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: scaleIn 0.2s;
        }

        @keyframes scaleIn {
            from { transform: translate(-50%, -50%) scale(0); }
            to { transform: translate(-50%, -50%) scale(1); }
        }

        .option-checkmark {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid #cbd5e1;
            background-color: white;
            position: relative;
            transition: var(--transition);
        }

        .btn-submit {
            display: block;
            width: 100%;
            padding: 16px;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: var(--transition);
            box-shadow: 0 4px 6px rgba(99, 102, 241, 0.25);
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255,255,255,0.2), rgba(255,255,255,0));
            transform: translateX(-100%);
            transition: transform 0.6s;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(99, 102, 241, 0.3);
        }

        .btn-submit:hover::before {
            transform: translateX(100%);
        }

        .btn-submit:active {
            transform: translateY(1px);
        }

        .btn-submit i {
            margin-left: 8px;
            transition: transform 0.3s;
        }

        .btn-submit:hover i {
            transform: translateX(5px);
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            h1 {
                font-size: 1.8rem;
            }
            
            .question {
                padding: 20px;
            }
            
            .question h3 {
                font-size: 1.1rem;
            }
        }

        /* Confetti effect for last question */
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background-color: #f00;
            opacity: 0;
            top: 0;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="quiz-header">
            <h1><?php echo htmlspecialchars($_SESSION['quiz']['title']); ?></h1>
            <div class="timer">
                <i class="fas fa-clock"></i>
                <span id="time">00:00</span>
            </div>
            <div class="progress-container">
                <div class="progress">
                    <div class="progress-bar" style="width: <?php echo ($current_question / $total_questions) * 100; ?>%"></div>
                </div>
                <p class="question-counter">Question <?php echo $current_question; ?> of <?php echo $total_questions; ?></p>
            </div>
        </div>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?q=$current_question"); ?>" id="quiz-form">
            <div class="question">
                <h3><?php echo htmlspecialchars($question['question_text']); ?></h3>
                <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                
                <ul class="options">
                    <?php foreach ($question['options'] as $option): ?>
                    <li>
                        <div class="option-container">
                            <input type="radio" name="selected_option" value="<?php echo $option['id']; ?>" required
                                id="option-<?php echo $option['id']; ?>" class="option-input"
                                <?php if (isset($_SESSION['user_answers'][$question['id']]) && 
                                        $_SESSION['user_answers'][$question['id']] == $option['id']) echo 'checked'; ?>>
                            <label for="option-<?php echo $option['id']; ?>" class="option-label">
                                <div class="option-checkmark"></div>
                                <span class="option-text"><?php echo htmlspecialchars($option['option_text']); ?></span>
                            </label>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <button type="submit" class="btn-submit" id="submit-btn">
                <?php if ($current_question == $total_questions): ?>
                    Finish Quiz <i class="fas fa-flag-checkered"></i>
                <?php else: ?>
                    Next Question <i class="fas fa-arrow-right"></i>
                <?php endif; ?>
            </button>
        </form>
    </div>

    <script>
        // Timer functionality
        let timeInSeconds = <?php echo $remaining_time; ?>;
        let timerInterval;

        function startTimer() {
            updateTimer(); // Update immediately
            timerInterval = setInterval(updateTimer, 1000);
        }

        function updateTimer() {
            if (timeInSeconds <= 0) {
                clearInterval(timerInterval);
                // Automatically submit form when time is up
                document.querySelector('form').submit();
                return;
            }
            
            const minutes = Math.floor(timeInSeconds / 60);
            const seconds = timeInSeconds % 60;
            
            document.getElementById('time').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            // Add visual indicator when time is low
            if (timeInSeconds <= 30) {
                document.getElementById('time').style.color = '#ef4444';
                document.getElementById('time').style.animation = 'pulse 1s infinite';
            }
            
            timeInSeconds--;
        }

        // Add smooth transitions between questions
        document.getElementById('quiz-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Fade out the container
            document.querySelector('.container').style.opacity = '0';
            document.querySelector('.container').style.transform = 'translateY(20px)';
            
            // Wait for animation to complete before submitting
            setTimeout(() => {
                this.submit();
            }, 300);
        });

        // Option selection animation
        const optionInputs = document.querySelectorAll('.option-input');
        optionInputs.forEach(input => {
            input.addEventListener('change', function() {
                // Reset all options
                optionInputs.forEach(opt => {
                    if (opt !== this) {
                        opt.parentElement.classList.remove('selected');
                    }
                });
                
                // Add selected class
                this.parentElement.classList.add('selected');
            });
        });

        // Create confetti effect for the last question
        <?php if ($current_question == $total_questions): ?>
        function createConfetti() {
            const colors = ['#6366f1', '#a855f7', '#ec4899', '#3b82f6', '#10b981', '#f59e0b'];
            const confettiCount = 150;
            
            for (let i = 0; i < confettiCount; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.opacity = '0';
                document.body.appendChild(confetti);
                
                const size = Math.random() * 10 + 5;
                confetti.style.width = size + 'px';
                confetti.style.height = size + 'px';
                
                const animation = confetti.animate([
                    { transform: 'translateY(0) rotate(0)', opacity: 1 },
                    { transform: `translateY(${window.innerHeight}px) rotate(${Math.random() * 720}deg)`, opacity: 0 }
                ], {
                    duration: Math.random() * 3000 + 2000,
                    easing: 'cubic-bezier(0.25, 0.8, 0.25, 1)',
                    delay: Math.random() * 1000
                });
                
                animation.onfinish = () => {
                    confetti.remove();
                };
            }
        }

        document.getElementById('submit-btn').addEventListener('click', function() {
            createConfetti();
        });
        <?php endif; ?>

        // Start the timer when the page loads
        window.onload = function() {
            startTimer();
            
            // Animate the container on page load
            document.querySelector('.container').style.opacity = '1';
        };
    </script>
</body>
</html>