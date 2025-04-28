<?php
session_start();
$servername = "localhost";
$username = "root";
$password = ""; // Remplacez par le mot de passe de votre base de données
$dbname = "exam_system";

// Connexion à la base de données
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['name'] = $row['name'];

            if ($row['role'] == 'teacher') {
                header('Location: teacher_dashboard.php');
                exit();
            } elseif ($row['role'] == 'student') {
                header('Location: student_dashboard.php');
                exit();
            } elseif ($row['role'] == 'admin') {
                header('Location: admin_dashboard.php');
                exit();
            } else {
                $error_message = "Rôle utilisateur inconnu.";
            }
        } else {
            $error_message = "Mot de passe incorrect.";
        }
    } else {
        $error_message = "Utilisateur non trouvé.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam+ Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Light theme (default) */
            --primary-color: #4f46e5;
            --secondary-color: #7c3aed;
            --accent-color: #06b6d4;
            --gradient-start: #4f46e5;
            --gradient-end: #7c3aed;
            --bg-pattern: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%234f46e5' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            --bg-gradient-from: #f8fafc;
            --bg-gradient-to: #e2e8f0;
            --card-bg: #ffffff;
            --card-border: rgba(79, 70, 229, 0.1);
            --card-shadow-1: rgba(79, 70, 229, 0.05);
            --card-shadow-2: rgba(0, 0, 0, 0.05);
            --welcome-bg: linear-gradient(135deg, rgba(79, 70, 229, 0.03), rgba(124, 58, 237, 0.05));
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --input-bg: #f8fafc;
            --input-border: #e2e8f0;
            --input-shadow: rgba(79, 70, 229, 0.05);
            --input-focus-border: rgba(79, 70, 229, 0.5);
            --input-focus-shadow: rgba(79, 70, 229, 0.2);
            --input-text: #1e293b;
            --button-text: #ffffff;
            --button-bg: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            --button-shadow: rgba(79, 70, 229, 0.3);
            --button-hover-shadow: rgba(79, 70, 229, 0.4);
            --switch-bg: #e2e8f0;
            --icon-bg: rgba(79, 70, 229, 0.1);
            --error-bg: rgba(239, 68, 68, 0.05);
            --error-border: #ef4444;
            --error-text: #ef4444;
        }

        [data-theme="dark"] {
            /* Dark theme */
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --accent-color: #0ea5e9;
            --gradient-start: #6366f1;
            --gradient-end: #8b5cf6;
            --bg-pattern: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%236366f1' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            --bg-gradient-from: #0f172a;
            --bg-gradient-to: #1e293b;
            --card-bg: #1e293b;
            --card-border: rgba(99, 102, 241, 0.15);
            --card-shadow-1: rgba(0, 0, 0, 0.3);
            --card-shadow-2: rgba(0, 0, 0, 0.3);
            --welcome-bg: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(139, 92, 246, 0.12));
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --input-bg: #334155;
            --input-border: #475569;
            --input-shadow: rgba(0, 0, 0, 0.2);
            --input-focus-border: rgba(99, 102, 241, 0.5);
            --input-focus-shadow: rgba(99, 102, 241, 0.3);
            --input-text: #f8fafc;
            --button-text: #ffffff;
            --button-bg: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            --button-shadow: rgba(99, 102, 241, 0.3);
            --button-hover-shadow: rgba(99, 102, 241, 0.5);
            --switch-bg: #334155;
            --icon-bg: rgba(99, 102, 241, 0.15);
            --error-bg: rgba(239, 68, 68, 0.1);
            --error-border: #ef4444;
            --error-text: #f87171;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, var(--bg-gradient-from), var(--bg-gradient-to));
            background-image: var(--bg-pattern);
            padding: 2rem;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background Elements */
        body::before, body::after {
            content: '';
            position: absolute;
            width: 40vw;
            height: 40vw;
            border-radius: 50%;
            z-index: -1;
            filter: blur(70px);
            opacity: 0.3;
            animation: floatBubble 15s infinite alternate ease-in-out;
        }

        body::before {
            background: linear-gradient(to right, var(--gradient-start), var(--accent-color));
            top: -20%;
            right: -10%;
            animation-delay: 0s;
        }

        body::after {
            background: linear-gradient(to right, var(--accent-color), var(--gradient-end));
            bottom: -20%;
            left: -10%;
            animation-delay: -7.5s;
        }

        @keyframes floatBubble {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(3%, 5%) scale(1.05); }
            100% { transform: translate(-3%, -5%) scale(0.95); }
        }

        .container {
            display: flex;
            width: 100%;
            max-width: 1200px;
            background: var(--card-bg);
            border-radius: 24px;
            box-shadow: 0 20px 50px var(--card-shadow-1),
                      0 10px 20px var(--card-shadow-2);
            overflow: hidden;
            border: 1px solid var(--card-border);
            position: relative;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .welcome-section {
            flex: 1.2;
            padding: 4.5rem;
            background: var(--welcome-bg);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        /* Interactive Particles */
        .particles-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            opacity: 0.6;
            pointer-events: none;
            z-index: 1;
        }

        /* Floating shapes */
        .shape {
            position: absolute;
            opacity: 0.15;
            pointer-events: none;
            z-index: 0;
            background: var(--primary-color);
        }

        .shape-1 {
            width: 150px;
            height: 150px;
            border-radius: 72% 28% 70% 30% / 53% 68% 32% 47%;
            top: 10%;
            left: 10%;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            animation: float 15s ease-in-out infinite;
        }

        .shape-2 {
            width: 100px;
            height: 100px;
            border-radius: 24% 76% 35% 65% / 27% 35% 65% 73%;
            bottom: 15%;
            right: 15%;
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            animation: float 18s ease-in-out infinite reverse;
        }

        .shape-3 {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            bottom: 30%;
            left: 20%;
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(15px, -15px) rotate(5deg); }
            50% { transform: translate(0, 15px) rotate(0deg); }
            75% { transform: translate(-15px, -5px) rotate(-5deg); }
        }

        .logo {
            margin-bottom: 2.5rem;
            font-size: 3.5rem;
            color: var(--primary-color);
            position: relative;
            z-index: 2;
            width: 80px;
            height: 80px;
            background-color: var(--icon-bg);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.2);
            animation: logoEnter 1s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        @keyframes logoEnter {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .logo i {
            animation: pulse 3s infinite ease-in-out;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .welcome-section h1 {
            font-size: 2.8rem;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            position: relative;
            z-index: 2;
            font-weight: 700;
            animation: titleEnter 1.2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            animation-delay: 0.2s;
            opacity: 0;
        }

        @keyframes titleEnter {
            from { transform: translateY(-15px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .welcome-section h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 70px;
            height: 4px;
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
            border-radius: 2px;
        }

        .welcome-section p {
            color: var(--text-secondary);
            font-size: 1.2rem;
            max-width: 85%;
            line-height: 1.7;
            margin-top: 1.5rem;
            position: relative;
            z-index: 2;
            animation: textEnter 1.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            animation-delay: 0.4s;
            opacity: 0;
        }

        @keyframes textEnter {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .features {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            margin-top: 3rem;
            width: 100%;
            position: relative;
            z-index: 2;
        }

        .feature {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.8rem;
            color: var(--text-secondary);
            font-size: 1rem;
            background: var(--card-bg);
            padding: 1.2rem;
            border-radius: 16px;
            width: calc(50% - 1rem);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--card-border);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            animation: featureEnter 1.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            opacity: 0;
        }

        .feature:nth-child(1) { animation-delay: 0.5s; }
        .feature:nth-child(2) { animation-delay: 0.65s; }
        .feature:nth-child(3) { animation-delay: 0.8s; }
        .feature:nth-child(4) { animation-delay: 0.95s; }

        @keyframes featureEnter {
            from { transform: translateY(10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .feature:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }

        .feature i {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            padding: 12px;
            background-color: var(--icon-bg);
            border-radius: 12px;
        }

        .feature span {
            font-weight: 500;
            text-align: center;
        }

        .login-section {
            flex: 1;
            padding: 4rem 3.5rem;
            background: var(--card-bg);
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            z-index: 2;
        }

        .login-section h2 {
            color: var(--primary-color);
            font-size: 2.4rem;
            margin-bottom: 0.8rem;
            text-align: center;
            font-weight: 700;
            animation: slideInRight 1s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
            opacity: 0;
        }

        @keyframes slideInRight {
            from { transform: translateX(30px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .login-subtitle {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 2.5rem;
            font-size: 1.1rem;
            animation: slideInRight 1s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
            animation-delay: 0.2s;
            opacity: 0;
        }

        .input-box {
            position: relative;
            margin-bottom: 2rem;
            animation: slideInRight 1s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
            opacity: 0;
        }

        .input-box:nth-of-type(1) { animation-delay: 0.4s; }
        .input-box:nth-of-type(2) { animation-delay: 0.6s; }

        .input-box input {
            width: 100%;
            padding: 1.2rem 1.2rem 1.2rem 3.2rem;
            background: var(--input-bg);
            border: 2px solid var(--input-border);
            border-radius: 16px;
            color: var(--input-text);
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            box-shadow: 0 4px 12px var(--input-shadow);
        }

        .input-box i {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-size: 1.2rem;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .input-box input:focus {
            border-color: var(--input-focus-border);
            box-shadow: 0 4px 20px var(--input-focus-shadow);
            outline: none;
        }

        .input-box input:focus + i {
            color: var(--accent-color);
        }

        .input-box input::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            font-size: 0.95rem;
            animation: slideInRight 1s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
            animation-delay: 0.8s;
            opacity: 0;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
        }

        .remember input[type="checkbox"] {
            appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid var(--input-border);
            border-radius: 6px;
            background: var(--input-bg);
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
        }

        .remember input[type="checkbox"]:checked {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .remember input[type="checkbox"]:checked::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 12px;
        }

        .remember input[type="checkbox"]:hover {
            border-color: var(--primary-color);
        }

        .remember label {
            cursor: pointer;
            user-select: none;
        }

        .forgot-password {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        .forgot-password::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }

        .forgot-password:hover::after {
            width: 100%;
        }

        button {
            background: var(--button-bg);
            color: var(--button-text);
            padding: 1.2rem;
            border: none;
            border-radius: 16px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            box-shadow: 0 8px 20px var(--button-shadow);
            position: relative;
            overflow: hidden;
            animation: slideInRight 1s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
            animation-delay: 1s;
            opacity: 0;
            z-index: 1;
        }

        button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.6s ease;
            z-index: -1;
        }

        button::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: all 0.6s ease;
            z-index: -1;
        }

        transform: translateY(-5px);
            box-shadow: 0 12px 30px var(--button-hover-shadow);
        }

        button:hover::before {
            left: 100%;
        }

        button:active {
            transform: translateY(2px);
            box-shadow: 0 5px 15px var(--button-shadow);
        }

        button:focus {
            outline: none;
        }

        button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        button i {
            margin-right: 8px;
        }

        .register-link {
            margin-top: 2rem;
            text-align: center;
            color: var(--text-secondary);
            font-size: 1rem;
            animation: slideInRight 1s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
            animation-delay: 1.2s;
            opacity: 0;
        }

        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }

        .register-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }

        .register-link a:hover::after {
            width: 100%;
        }

        .error-message {
            background-color: var(--error-bg);
            color: var(--error-text);
            text-align: center;
            margin-top: 1.5rem;
            padding: 1rem;
            border-radius: 12px;
            font-size: 0.95rem;
            border-left: 4px solid var(--error-border);
            animation: shakeError 0.5s ease-in-out, fadeIn 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.1);
        }

        @keyframes shakeError {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Theme switch */
        .theme-switch-container {
            position: absolute;
            top: 25px;
            right: 25px;
            display: flex;
            align-items: center;
            z-index: 10;
        }

        .theme-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }

        .theme-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--switch-bg);
            transition: .4s;
            border-radius: 34px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        input:checked + .slider {
            background-color: var(--primary-color);
        }

        input:focus + .slider {
            box-shadow: 0 0 2px var(--primary-color);
        }

        input:checked + .slider:before {
            transform: translateX(30px);
        }

        .theme-icon {
            margin-left: 10px;
            font-size: 1.2rem;
            color: var(--text-primary);
            animation: rotate 1s ease forwards;
            transition: all 0.5s ease;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Loader animation for button */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Button animation for loading state */
        button.loading {
            pointer-events: none;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        }

        button.loading i {
            animation: spin 1s linear infinite;
        }

        /* Responsive Styles */
        @media (max-width: 1100px) {
            .container {
                max-width: 90%;
            }
            
            .welcome-section {
                padding: 3rem;
            }
            
            .login-section {
                padding: 3rem;
            }
        }

        @media (max-width: 920px) {
            .container {
                flex-direction: column;
                max-width: 95%;
                height: auto;
            }

            .welcome-section,
            .login-section {
                padding: 3rem 2.5rem;
            }

            .welcome-section {
                padding-top: 4rem;
                padding-bottom: 3rem;
            }

            .login-section {
                padding-top: 2rem;
            }

            .welcome-section h1 {
                font-size: 2.2rem;
            }
            
            .theme-switch-container {
                top: 15px;
                right: 15px;
            }
            
            .features {
                grid-template-columns: 1fr 1fr;
                margin-top: 2rem;
            }

            .logo {
                margin-bottom: 2rem;
            }

            /* Adjust animations for mobile */
            .welcome-section h1,
            .welcome-section p,
            .feature,
            .login-section h2,
            .login-subtitle,
            .input-box,
            .remember-forgot,
            button,
            .register-link {
                animation-name: fadeIn;
            }
        }

        @media (max-width: 640px) {
            .welcome-section,
            .login-section {
                padding: 2.5rem 1.5rem;
            }
            
            .welcome-section h1 {
                font-size: 1.8rem;
            }

            .welcome-section p {
                font-size: 1rem;
            }
            
            .features {
                flex-direction: column;
                gap: 1rem;
            }

            .feature {
                width: 100%;
                padding: 1rem;
            }

            .feature i {
                padding: 8px;
                font-size: 1.2rem;
            }
            
            .remember-forgot {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .input-box input {
                padding: 1rem 1rem 1rem 2.8rem;
            }

            .logo {
                width: 70px;
                height: 70px;
            }

            .button {
                padding: 1rem;
            }
        }

        /* Interactive Animation for Page Load */
        @keyframes pageLoad {
            0% { opacity: 0; transform: scale(0.9); }
            100% { opacity: 1; transform: scale(1); }
        }

        .container {
            animation: pageLoad 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome-section">
            <!-- Decorative Shapes -->
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>

            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1>Bienvenue sur Exam+</h1>
            <p>Votre plateforme sécurisée pour la gestion des examens et évaluations en ligne</p>
            
            <div class="features">
                <div class="feature">
                    <i class="fas fa-lock"></i>
                    <span>Sécurité renforcée</span>
                </div>
                <div class="feature">
                    <i class="fas fa-chart-line"></i>
                    <span>Suivi des performances</span>
                </div>
                <div class="feature">
                    <i class="fas fa-clock"></i>
                    <span>Accès 24/7</span>
                </div>
                <div class="feature">
                    <i class="fas fa-mobile-alt"></i>
                    <span>Interface responsive</span>
                </div>
            </div>
            
            <div class="theme-switch-container">
                <label class="theme-switch">
                    <input type="checkbox" id="theme-toggle">
                    <span class="slider"></span>
                </label>
                <span class="theme-icon" id="theme-icon">☀️</span>
            </div>
        </div>

        <div class="login-section">
            <h2>Connexion</h2>
            <p class="login-subtitle">Accédez à votre espace personnel</p>
            
            <form method="POST" action="" id="login-form">
                <div class="input-box">
                    <input type="email" name="email" placeholder="Adresse email" required autocomplete="email">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Mot de passe" required autocomplete="current-password">
                    <i class="fas fa-lock"></i>
                </div>
                
                <div class="remember-forgot">
                    <div class="remember">
                        <input type="checkbox" id="remember">
                        <label for="remember">Se souvenir de moi</label>
                    </div>
                    <a href="#" class="forgot-password">Mot de passe oublié?</a>
                </div>
                
                <button type="submit" id="login-button">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </form>
            
            <div class="register-link">
                Pas encore de compte? <a href="inscription.php">S'inscrire</a>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Create interactive particle background effect
        document.addEventListener('DOMContentLoaded', function() {
            // Create particles container in welcome section
            const welcomeSection = document.querySelector('.welcome-section');
            const particlesContainer = document.createElement('div');
            particlesContainer.className = 'particles-container';
            welcomeSection.appendChild(particlesContainer);
            
            // Create floating particles
            const particleCount = 20;
            const colors = ['#4f46e5', '#7c3aed', '#06b6d4', '#8b5cf6'];
            
            for (let i = 0; i < particleCount; i++) {
                createParticle(particlesContainer, colors);
            }
            
            function createParticle(container, colors) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Random properties
                const size = Math.random() * 8 + 3;
                const color = colors[Math.floor(Math.random() * colors.length)];
                
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.background = color;
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                
                // Animation
                const duration = Math.random() * 20 + 10;
                particle.style.animation = `float ${duration}s infinite ease-in-out`;
                particle.style.animationDelay = `${Math.random() * 5}s`;
                
                container.appendChild(particle);
            }
        });

        // Theme switching functionality
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        
        // Check if user has a theme preference stored
        const currentTheme = localStorage.getItem('theme');
        
        // If the user has previously selected a theme
        if (currentTheme) {
            document.documentElement.setAttribute('data-theme', currentTheme);
            
            if (currentTheme === 'dark') {
                themeToggle.checked = true;
                themeIcon.textContent = '🌙';
            }
        } else {
            // Check for system preference
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-theme', 'dark');
                themeToggle.checked = true;
                themeIcon.textContent = '🌙';
                localStorage.setItem('theme', 'dark');
            }
        }
        
        // When the user changes the theme
        themeToggle.addEventListener('change', function() {
            if (this.checked) {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                themeIcon.textContent = '🌙';
                themeIcon.style.animation = 'rotate 1s ease forwards';
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
                themeIcon.textContent = '☀️';
                themeIcon.style.animation = 'rotate 1s ease forwards';
            }
        });

        // Animation for form submission
        const loginForm = document.getElementById('login-form');
        const loginButton = document.getElementById('login-button');
        
        loginForm.addEventListener('submit', function(e) {
            loginButton.classList.add('loading');
            loginButton.innerHTML = '<i class="fas fa-spinner"></i> Chargement...';
        });

        // Input focus animation
        const inputs = document.querySelectorAll('.input-box input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-5px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });

        // Interactive hover effect on features
        const features = document.querySelectorAll('.feature');
        features.forEach(feature => {
            feature.addEventListener('mouseover', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            feature.addEventListener('mouseout', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>