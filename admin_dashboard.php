<?php
session_start();
// Verify if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "exam_system";

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Success message
$success_message = "";
$error_message = "";

// Function to sanitize input
function sanitize_input($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

// Fonction de log pour le débogage
function debug_log($message) {
    error_log(print_r($message, true));
}

// Add new user (teacher or student)
if (isset($_POST['add_user'])) {
    $name = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : '';
    $role = isset($_POST['role']) ? $_POST['role'] : '';
    $groups = isset($_POST['groups']) ? $_POST['groups'] : [];

    if (empty($name) || empty($password) || empty($role)) {
        $error_message = "Tous les champs sont requis.";
    } else {
        // Insérer l'utilisateur
        $stmt = $conn->prepare("INSERT INTO users (name, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $password, $role);
        
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            
            // Ajouter les relations utilisateur-groupe
            if (!empty($groups)) {
                $stmt = $conn->prepare("INSERT INTO user_groups (user_id, group_id) VALUES (?, ?)");
                foreach ($groups as $group_id) {
                    $stmt->bind_param("ii", $user_id, $group_id);
                    $stmt->execute();
                }
            }
            
            header("Location: admin_dashboard.php?success=1");
            exit();
        } else {
            $error_message = "Erreur lors de l'ajout de l'utilisateur.";
        }
    }
}

// Update user password
if (isset($_POST['update_password'])) {
    $user_id = sanitize_input($_POST['user_id']);
    $new_password = sanitize_input($_POST['new_password']);
    $confirm_password = sanitize_input($_POST['confirm_password']);
    
    if ($new_password !== $confirm_password) {
        $error_message = "Les mots de passe ne correspondent pas.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";
        
        if ($conn->query($sql) === TRUE) {
            $success_message = "Mot de passe mis à jour avec succès.";
        } else {
            $error_message = "Erreur: " . $conn->error;
        }
    }
}

// Add new group
if (isset($_POST['add_group'])) {
    $group_name = sanitize_input($_POST['group_name']);
    $group_desc = sanitize_input($_POST['group_description']);
    
    $sql = "INSERT INTO groups (name, description) VALUES ('$group_name', '$group_desc')";
    
    if ($conn->query($sql) === TRUE) {
        $success_message = "Groupe ajouté avec succès.";
    } else {
        $error_message = "Erreur: " . $conn->error;
    }
}

// Add new subject
if (isset($_POST['add_subject'])) {
    $subject_name = sanitize_input($_POST['subject_name']);
    $subject_desc = sanitize_input($_POST['subject_description']);
    
    $sql = "INSERT INTO subjects (name, description) VALUES ('$subject_name', '$subject_desc')";
    
    if ($conn->query($sql) === TRUE) {
        $success_message = "Matière ajoutée avec succès.";
    } else {
        $error_message = "Erreur: " . $conn->error;
    }
}

// Delete user
if (isset($_POST['delete_user'])) {
    $user_id = sanitize_input($_POST['user_id']);
    
    $sql = "DELETE FROM users WHERE id = $user_id AND role != 'admin'";
    
    if ($conn->query($sql) === TRUE) {
        $success_message = "Utilisateur supprimé avec succès.";
    } else {
        $error_message = "Erreur: " . $conn->error;
    }
}

// Delete group
if (isset($_POST['delete_group'])) {
    $group_id = sanitize_input($_POST['group_id']);
    
    $sql = "DELETE FROM groups WHERE id = $group_id";
    
    if ($conn->query($sql) === TRUE) {
        $success_message = "Groupe supprimé avec succès.";
    } else {
        $error_message = "Erreur: " . $conn->error;
    }
}

// Delete subject
if (isset($_POST['delete_subject'])) {
    $subject_id = sanitize_input($_POST['subject_id']);
    
    $sql = "DELETE FROM subjects WHERE id = $subject_id";
    
    if ($conn->query($sql) === TRUE) {
        $success_message = "Matière supprimée avec succès.";
    } else {
        $error_message = "Erreur: " . $conn->error;
    }
}

// Update user info
if (isset($_POST['update_user'])) {
    $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : '';
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : '';
    $groups = isset($_POST['group_id']) ? $_POST['group_id'] : [];
    $subject_id = isset($_POST['subject_id']) ? $_POST['subject_id'] : null;
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($user_id) || empty($name) || empty($email) || empty($role)) {
        $error_message = "Tous les champs sont requis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "L'adresse email n'est pas valide.";
    } else {
        // Vérifier si l'email existe déjà pour un autre utilisateur
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_email->bind_param("si", $email, $user_id);
        $check_email->execute();
        $check_result = $check_email->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "Cette adresse email est déjà utilisée par un autre utilisateur.";
        } else {
            // Démarrer une transaction
            $conn->begin_transaction();
            
            try {
                // Préparer la requête de mise à jour
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $name, $email, $role, $hashed_password, $user_id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $name, $email, $role, $user_id);
                }
                
                if ($stmt->execute()) {
                    // Supprimer les anciennes relations utilisateur-groupe
                    $stmt = $conn->prepare("DELETE FROM user_groups WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    
                    // Ajouter les nouvelles relations utilisateur-groupe
                    if (!empty($groups)) {
                        $stmt = $conn->prepare("INSERT INTO user_groups (user_id, group_id) VALUES (?, ?)");
                        foreach ($groups as $group_id) {
                            $stmt->bind_param("ii", $user_id, $group_id);
                            $stmt->execute();
                        }
                    }
                    
                    // Gérer la matière pour les enseignants
                    if ($role === 'teacher' && $subject_id) {
                        // Supprimer les anciennes relations enseignant-matière
                        $stmt = $conn->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ?");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        
                        // Ajouter la nouvelle relation enseignant-matière
                        $stmt = $conn->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)");
                        $stmt->bind_param("ii", $user_id, $subject_id);
                        $stmt->execute();
                    }
                    
                    // Valider la transaction
                    $conn->commit();
                    $_SESSION['success_message'] = "Utilisateur mis à jour avec succès.";
                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    throw new Exception("Erreur lors de la mise à jour de l'utilisateur.");
                }
            } catch (Exception $e) {
                // Annuler la transaction en cas d'erreur
                $conn->rollback();
                $error_message = $e->getMessage();
            }
        }
    }
}

// Get all groups for dropdown
$groups_query = "SELECT * FROM groups ORDER BY name";
$groups_result = $conn->query($groups_query);

// Get all subjects for dropdown
$subjects_query = "SELECT * FROM subjects ORDER BY name";
$subjects_result = $conn->query($subjects_query);

// Get all users with their current group and subject
$users_query = "SELECT 
    u.id,
    u.name,
    u.email,
    u.role,
    u.created_at,
    GROUP_CONCAT(DISTINCT g.id) as group_ids,
    GROUP_CONCAT(DISTINCT g.name) as group_names,
    s.id as subject_id,
    s.name as subject_name
    FROM users u
    LEFT JOIN user_groups ug ON u.id = ug.user_id
    LEFT JOIN groups g ON ug.group_id = g.id
    LEFT JOIN teacher_subjects ts ON u.id = ts.teacher_id
    LEFT JOIN subjects s ON ts.subject_id = s.id
    WHERE u.role != 'admin'
    GROUP BY u.id
    ORDER BY u.created_at DESC";
$users_result = $conn->query($users_query);

// Get all groups
$all_groups_query = "SELECT g.*, COUNT(ug.user_id) as user_count 
                    FROM groups g
                    LEFT JOIN user_groups ug ON g.id = ug.group_id
                    GROUP BY g.id
                    ORDER BY g.name";
$all_groups_result = $conn->query($all_groups_query);

// Get all subjects
$all_subjects_query = "SELECT s.*, COUNT(ts.teacher_id) as teacher_count 
                      FROM subjects s
                      LEFT JOIN teacher_subjects ts ON s.id = ts.subject_id
                      GROUP BY s.id
                      ORDER BY s.name";
$all_subjects_result = $conn->query($all_subjects_query);

// Au début du fichier HTML, après la navbar
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Exam+</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
    :root {
    --primary-color: #4f46e5;
    --primary-dark: #3730a3;
    --secondary-color: #4338ca;
    --accent-color: #00d4ff;
    --accent-dark: #00a3c4;
    --dark-bg: #0f172a;
    --card-bg: #1e293b;
    --card-bg-hover: #283548;
    --text-primary: #ffffff;
    --text-secondary: #94a3b8;
    --error-color: #ef4444;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --border-color: rgba(255, 255, 255, 0.1);
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', 'Poppins', sans-serif;
}

body {
    min-height: 100vh;
    background: linear-gradient(135deg, var(--dark-bg), #131f38);
    color: var(--text-primary);
    display: flex;
    flex-direction: column;
    line-height: 1.5;
}

.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 2rem;
    background: var(--card-bg);
    box-shadow: var(--shadow);
    position: sticky;
    top: 0;
    z-index: 100;
}

.navbar .logo {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--accent-color);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.navbar .logo i {
    color: var(--accent-color);
    font-size: 1.2rem;
}

.navbar .user-info {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.navbar .user-info .user-name {
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.navbar .user-info .user-name i {
    color: var(--accent-color);
    font-size: 1.2rem;
}

.navbar .user-info .logout-btn {
    color: var(--text-primary);
    background: rgba(239, 68, 68, 0.2);
    padding: 0.5rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.navbar .user-info .logout-btn i {
    color: var(--error-color);
}

.navbar .user-info .logout-btn:hover {
    background: rgba(239, 68, 68, 0.4);
    transform: translateY(-1px);
}

.container {
    flex: 1;
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
    width: 100%;
}

.dashboard-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.dashboard-header h1 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    font-weight: 700;
    background: linear-gradient(to right, var(--accent-color), var(--primary-color));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dashboard-header h1 i {
    color: var(--accent-color);
    font-size: 1.8rem;
}

.dashboard-header p {
    color: var(--text-secondary);
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.dashboard-header p i {
    color: var(--primary-color);
}

.tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 1rem;
    flex-wrap: wrap;
}

.tab-btn {
    background: transparent;
    color: var(--text-secondary);
    border: none;
    padding: 0.75rem 1.25rem;
    font-size: 0.95rem;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.2s ease;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tab-btn i {
    font-size: 1rem;
}

.tab-btn.active {
    background: var(--primary-color);
    color: var(--text-primary);
    box-shadow: var(--shadow-sm);
}

.tab-btn:hover:not(.active) {
    background: rgba(79, 70, 229, 0.1);
    color: var(--text-primary);
    transform: translateY(-1px);
}

.tab-content {
    display: none;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.tab-content.active {
    display: block;
}

.card {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
    border: 1px solid var(--border-color);
}

.card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
    border-color: rgba(255, 255, 255, 0.15);
}

.card h2 {
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
    color: var(--accent-color);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 600;
}

.card h2 i {
    color: var(--accent-color);
    font-size: 1.3rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.75rem;
    color: var(--text-secondary);
    font-weight: 500;
    font-size: 0.95rem;
}

.form-group input, 
.form-group select, 
.form-group textarea {
    width: 100%;
    padding: 0.85rem 1rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-group input:focus, 
.form-group select:focus, 
.form-group textarea:focus {
    outline: none;
    border-color: var(--accent-color);
    box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.15);
}

.form-group input:hover, 
.form-group select:hover, 
.form-group textarea:hover {
    border-color: rgba(255, 255, 255, 0.2);
}

.form-row {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1rem;
}

.form-row .form-group {
    flex: 1;
}

button[type="submit"] {
    background: linear-gradient(to right, var(--accent-color), var(--primary-color));
    color: var(--text-primary);
    border: none;
    padding: 0.85rem 1.75rem;
    border-radius: 8px;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    box-shadow: var(--shadow-sm);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

button[type="submit"] i {
    font-size: 1.1rem;
}

button[type="submit"]:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
    background: linear-gradient(to right, var(--accent-dark), var(--primary-dark));
}

.alert {
    padding: 1.25rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    animation: slideInDown 0.4s ease;
}

@keyframes slideInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.alert-success {
    background: rgba(16, 185, 129, 0.15);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: var(--success-color);
}

.alert-success i {
    color: var(--success-color);
    font-size: 1.2rem;
}

.alert-error {
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: var(--error-color);
}

.alert-error i {
    color: var(--error-color);
    font-size: 1.2rem;
}

.table-container {
    overflow-x: auto;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    background: rgba(255, 255, 255, 0.02);
}

table {
    width: 100%;
    border-collapse: collapse;
}

table th, table td {
    padding: 1.25rem 1rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

table th {
    color: var(--accent-color);
    font-weight: 600;
    background: rgba(0, 212, 255, 0.05);
    position: sticky;
    top: 0;
    z-index: 10;
}

table tr:last-child td {
    border-bottom: none;
}

table tr {
    transition: all 0.2s ease;
}

table tr:hover {
    background: var(--card-bg-hover);
}

.badge {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.75rem;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 600;
    transition: all 0.2s ease;
}

.badge-teacher {
    background: rgba(79, 70, 229, 0.15);
    color: var(--primary-color);
}

.badge-teacher i {
    color: var(--primary-color);
    margin-right: 0.3rem;
    font-size: 0.9rem;
}

.badge-student {
    background: rgba(16, 185, 129, 0.15);
    color: var(--success-color);
}

.badge-student i {
    color: var(--success-color);
    margin-right: 0.3rem;
    font-size: 0.9rem;
}

.badge-count {
    background: rgba(245, 158, 11, 0.15);
    color: var(--warning-color);
    margin-left: 0.5rem;
}

.delete-btn {
    background: rgba(239, 68, 68, 0.2);
    color: var(--error-color);
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.delete-btn i {
    color: var(--error-color);
    font-size: 1rem;
}

.delete-btn:hover {
    background: rgba(239, 68, 68, 0.4);
    transform: translateY(-1px);
}

.edit-btn {
    background: rgba(79, 70, 229, 0.2);
    color: var(--primary-color);
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.edit-btn i {
    color: var(--primary-color);
    font-size: 1rem;
}

.edit-btn:hover {
    background: rgba(79, 70, 229, 0.4);
    transform: translateY(-1px);
}

.actions-cell {
    display: flex;
    flex-direction: column;
}

.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.modal-backdrop.active {
    opacity: 1;
    visibility: visible;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: var(--card-bg);
    margin: 15% auto;
    padding: 2rem;
    border-radius: 8px;
    width: 80%;
    max-width: 500px;
    position: relative;
}

.close {
    position: absolute;
    right: 1rem;
    top: 1rem;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-secondary);
}

.close:hover {
    color: var(--accent-color);
}

.password-strength {
    margin-top: 0.5rem;
    height: 5px;
    border-radius: 5px;
    background: #333;
    overflow: hidden;
}

.password-strength-bar {
    height: 100%;
    width: 0;
    transition: width 0.3s ease;
}

.password-strength-text {
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

.password-toggle {
    position: absolute;
    right: 15px;
    top: 40px;
    background: transparent;
    border: none;
    cursor: pointer;
    color: var(--text-secondary);
    font-size: 1rem;
    transition: all 0.2s ease;
}

.password-toggle:hover {
    color: var(--accent-color);
}

/* User actions */
.user-actions {
    display: flex;
    gap: 0.5rem;
}

/* Stats cards */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(255, 255, 255, 0.2);
}

.stat-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.stat-card-title {
    color: var(--text-secondary);
    font-size: 0.9rem;
    font-weight: 500;
}

.stat-card-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
}

.stat-card-users .stat-card-icon {
    background: rgba(79, 70, 229, 0.15);
    color: var(--primary-color);
}

.stat-card-groups .stat-card-icon {
    background: rgba(16, 185, 129, 0.15);
    color: var(--success-color);
}

.stat-card-subjects .stat-card-icon {
    background: rgba(245, 158, 11, 0.15);
    color: var(--warning-color);
}

.stat-card-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stat-card-users .stat-card-value {
    color: var(--primary-color);
}

.stat-card-groups .stat-card-value {
    color: var(--success-color);
}

.stat-card-subjects .stat-card-value {
    color: var(--warning-color);
}

.stat-card-change {
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.stat-card-change.positive {
    color: var(--success-color);
}

.stat-card-change.negative {
    color: var(--error-color);
}

/* Action buttons */
.action-buttons {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
}

.action-btn-primary {
    background: var(--primary-color);
    color: white;
}

.action-btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
}

.action-btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
}

.action-btn-secondary:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .container {
        padding: 1rem;
    }
    
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    
    .navbar {
        padding: 1rem;
        flex-direction: column;
        gap: 1rem;
    }
    
    .tabs {
        overflow-x: auto;
        padding-bottom: 0.5rem;
        justify-content: flex-start;
        gap: 0.25rem;
    }
    
    .tab-btn {
        padding: 0.5rem 0.75rem;
        font-size: 0.85rem;
        white-space: nowrap;
    }
    
    .card {
        padding: 1.5rem;
    }
    
    .dashboard-header h1 {
        font-size: 1.75rem;
    }
    
    .stats-container {
        grid-template-columns: 1fr;
    }
}

/* Customized Scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    transition: all 0.3s ease;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Dark Mode Toggle */
.dark-mode-toggle {
    background: transparent;
    border: none;
    color: var(--text-secondary);
    font-size: 1.2rem;
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 0.5rem;
    border-radius: 5px;
}

.dark-mode-toggle:hover {
    color: var(--accent-color);
    background: rgba(255, 255, 255, 0.05);
}

/* Empty state */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
    text-align: center;
    color: var(--text-secondary);
}

.empty-state i {
    font-size: 3rem;
    color: var(--border-color);
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.empty-state p {
    max-width: 500px;
    margin-bottom: 1.5rem;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.pagination-btn {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.2s ease;
}

.pagination-btn:hover {
    background: var(--card-bg-hover);
    transform: translateY(-2px);
}

.pagination-btn.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

/* Tooltip */
.tooltip {
    position: relative;
    display: inline-block;
}

.tooltip .tooltip-text {
    visibility: hidden;
    background-color: var(--card-bg);
    color: var(--text-primary);
    text-align: center;
    border-radius: 6px;
    padding: 0.5rem 1rem;
    position: absolute;
    z-index: 1;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    opacity: 0;
    transition: opacity 0.3s;
    font-size: 0.85rem;
    white-space: nowrap;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
}

.tooltip:hover .tooltip-text {
    visibility: visible;
    opacity: 1;
}

/* Footer */
.footer {
    margin-top: 2rem;
    padding: 1.5rem 0;
    border-top: 1px solid var(--border-color);
    text-align: center;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.footer a {
    color: var(--accent-color);
    text-decoration: none;
    transition: all 0.2s ease;
}

.footer a:hover {
    text-decoration: underline;
}

/* Filter and Search */
.filter-container {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.search-input {
    flex: 1;
    min-width: 200px;
    position: relative;
}

.search-input input {
    width: 100%;
    padding: 0.85rem 1rem 0.85rem 3rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 1rem;
    transition: all 0.3s ease;
}

.search-input i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
}

.search-input input:focus {
    outline: none;
    border-color: var(--accent-color);
    box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.15);
}

.filter-dropdown {
    position: relative;
    min-width: 150px;
}

.filter-dropdown select {
    width: 100%;
    padding: 0.85rem 2.5rem 0.85rem 1rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 1rem;
    appearance: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-dropdown::after {
    content: '\f107';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
    pointer-events: none;
}

/* Loading state */
.loading {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    border-top-color: var(--accent-color);
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Responsive improvements */
@media (max-width: 576px) {
    .card {
        padding: 1.25rem;
    }
    
    .dashboard-header h1 {
        font-size: 1.5rem;
    }
    
    .table-container {
        margin: 0 -1.25rem;
        width: calc(100% + 2.5rem);
        border-radius: 0;
        border-left: none;
        border-right: none;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-btn {
        width: 100%;
        justify-content: center;
    }
    
    .navbar .logo span {
        display: none;
    }
}

/* Styles pour les boutons d'action */
.btn-icon {
    background: none;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    padding: 0.5rem;
    transition: color 0.3s;
}

.btn-icon:hover {
    color: var(--accent-color);
}

/* Styles pour le modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: var(--card-bg);
    margin: 15% auto;
    padding: 2rem;
    border-radius: 8px;
    width: 80%;
    max-width: 500px;
    position: relative;
}

.close {
    position: absolute;
    right: 1rem;
    top: 1rem;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-secondary);
}

.close:hover {
    color: var(--accent-color);
}

/* Styles pour Select2 et badges */
.select2-container {
    width: 100% !important;
}

.select2-container--default .select2-selection--multiple {
    background-color: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 4px;
    color: var(--text-primary);
}

.badge-group {
    background: rgba(79, 70, 229, 0.1);
    color: var(--primary-color);
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    margin-right: 0.3rem;
    display: inline-block;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: var(--primary-color);
    border: none;
    color: white;
    padding: 0.2rem 0.5rem;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: var(--primary-color);
}

.select2-dropdown {
    background-color: var(--card-bg);
    border: 1px solid var(--border-color);
}

.select2-container--default .select2-results__option[aria-selected=true] {
    background-color: rgba(79, 70, 229, 0.1);
}
</style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <span>Exam+</span>
        </div>
        <div class="user-info">
            <div class="user-name">
                <i class="fas fa-user-shield"></i>
                Admin
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Déconnexion
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1><i class="fas fa-tachometer-alt"></i> Tableau de Bord Admin</h1>
            <p><i class="fas fa-info-circle"></i> Gérez les utilisateurs, groupes et matières du système.</p>
        </div>

        <?php if(!empty($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <?php if(!empty($error_message)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <div class="stats-container">
            <div class="stat-card stat-card-users">
                <div class="stat-card-header">
                    <div class="stat-card-title">Total Utilisateurs</div>
                    <div class="stat-card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-card-value"><?php echo $users_result->num_rows; ?></div>
                <div class="stat-card-change positive">
                    <i class="fas fa-arrow-up"></i> 12% ce mois
                </div>
            </div>
            <div class="stat-card stat-card-groups">
                <div class="stat-card-header">
                    <div class="stat-card-title">Total Groupes</div>
                    <div class="stat-card-icon">
                        <i class="fas fa-user-friends"></i>
                    </div>
                </div>
                <div class="stat-card-value"><?php echo $all_groups_result->num_rows; ?></div>
                <div class="stat-card-change positive">
                    <i class="fas fa-arrow-up"></i> 5% ce mois
                </div>
            </div>
            <div class="stat-card stat-card-subjects">
                <div class="stat-card-header">
                    <div class="stat-card-title">Total Matières</div>
                    <div class="stat-card-icon">
                        <i class="fas fa-book"></i>
                    </div>
                </div>
                <div class="stat-card-value"><?php echo $all_subjects_result->num_rows; ?></div>
                <div class="stat-card-change positive">
                    <i class="fas fa-arrow-up"></i> 8% ce mois
                </div>
            </div>
        </div>

        <div class="tabs">
            <button class="tab-btn active" data-tab="users">
                <i class="fas fa-users"></i> Utilisateurs
            </button>
            <button class="tab-btn" data-tab="groups">
                <i class="fas fa-user-friends"></i> Groupes
            </button>
            <button class="tab-btn" data-tab="subjects">
                <i class="fas fa-book"></i> Matières
            </button>
            <button class="tab-btn" data-tab="add-user">
                <i class="fas fa-user-plus"></i> Ajouter Utilisateur
            </button>
            <button class="tab-btn" data-tab="add-group">
                <i class="fas fa-plus-circle"></i> Ajouter Groupe
            </button>
            <button class="tab-btn" data-tab="add-subject">
                <i class="fas fa-plus-circle"></i> Ajouter Matière
            </button>
        </div>

        <!-- Users Tab -->
        <div class="tab-content active" id="users">
            <div class="card">
                <h2><i class="fas fa-users"></i> Gestion des Utilisateurs</h2>
                
                <div class="filter-container">
                    <div class="search-input">
                        <i class="fas fa-search"></i>
                        <input type="text" id="user-search" placeholder="Rechercher un utilisateur...">
                    </div>
                    <div class="filter-dropdown">
                        <select id="role-filter">
                            <option value="all">Tous les rôles</option>
                            <option value="teacher">Enseignants</option>
                            <option value="student">Étudiants</option>
                        </select>
                    </div>
                </div>
                
                <div class="table-container">
                    <table id="users-table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Groupe</th>
                                <th>Matière</th>
                                <th>Date Création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($users_result->num_rows > 0): ?>
                                <?php while($user = $users_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?php if($user['role'] == 'teacher'): ?>
                                                <span class="badge badge-teacher">
                                                    <i class="fas fa-chalkboard-teacher"></i> Enseignant
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-student">
                                                    <i class="fas fa-user-graduate"></i> Étudiant
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php 
                                            if ($user['group_names']) {
                                                $group_names = explode(',', $user['group_names']);
                                                foreach ($group_names as $group_name) {
                                                    echo '<span class="badge badge-group">' . htmlspecialchars($group_name) . '</span> ';
                                                }
                                            } else {
                                                echo '-';
                                            }
                                        ?></td>
                                        <td><?php echo $user['subject_name'] ? htmlspecialchars($user['subject_name']) : '-'; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                        <td class="actions">
                                            <button onclick="openEditModal(
                                                <?php echo $user['id']; ?>,
                                                '<?php echo htmlspecialchars($user['name']); ?>',
                                                '<?php echo htmlspecialchars($user['email']); ?>',
                                                '<?php echo $user['role']; ?>',
                                                '<?php echo $user['group_ids']; ?>',
                                                <?php echo $user['subject_id'] ? $user['subject_id'] : 'null'; ?>
                                            )" class="btn-icon">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="delete_user" class="btn-icon" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fas fa-users-slash"></i>
                                            <h3>Aucun utilisateur trouvé</h3>
                                            <p>Il n'y a actuellement aucun utilisateur dans le système.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Groups Tab -->
        <div class="tab-content" id="groups">
            <div class="card">
                <h2><i class="fas fa-user-friends"></i> Gestion des Groupes</h2>
                
                <div class="filter-container">
                    <div class="search-input">
                        <i class="fas fa-search"></i>
                        <input type="text" id="group-search" placeholder="Rechercher un groupe...">
                    </div>
                </div>
                
                <div class="table-container">
                    <table id="groups-table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Nombre d'étudiants</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($all_groups_result->num_rows > 0): ?>
                                <?php while($group = $all_groups_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($group['name']); ?></td>
                                        <td><?php echo htmlspecialchars($group['description']); ?></td>
                                        <td>
                                            <span class="badge badge-count">
                                                <?php echo $group['user_count']; ?> étudiants
                                            </span>
                                        </td>
                                        <td>
                                            <form method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce groupe?');">
                                                <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                                                <button type="submit" name="delete_group" class="delete-btn">
                                                    <i class="fas fa-trash-alt"></i> Supprimer
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">
                                        <div class="empty-state">
                                            <i class="fas fa-user-friends"></i>
                                            <h3>Aucun groupe trouvé</h3>
                                            <p>Il n'y a actuellement aucun groupe dans le système.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Subjects Tab -->
        <div class="tab-content" id="subjects">
            <div class="card">
                <h2><i class="fas fa-book"></i> Gestion des Matières</h2>
                
                <div class="filter-container">
                    <div class="search-input">
                        <i class="fas fa-search"></i>
                        <input type="text" id="subject-search" placeholder="Rechercher une matière...">
                    </div>
                </div>
                
                <div class="table-container">
                    <table id="subjects-table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Nombre d'ensignants</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($all_subjects_result->num_rows > 0): ?>
                                <?php while($subject = $all_subjects_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['description']); ?></td>
                                        <td>
                                            <span class="badge badge-count">
                                                <?php echo $subject['teacher_count']; ?> enseignants
                                            </span>
                                        </td>
                                        <td>
                                            <form method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette matière?');">
                                                <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                                <button type="submit" name="delete_subject" class="delete-btn">
                                                    <i class="fas fa-trash-alt"></i> Supprimer
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">
                                        <div class="empty-state">
                                            <i class="fas fa-book"></i>
                                            <h3>Aucune matière trouvée</h3>
                                            <p>Il n'y a actuellement aucune matière dans le système.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add User Tab -->
        <div class="tab-content" id="add-user">
            <div class="card">
                <h2><i class="fas fa-user-plus"></i> Ajouter un Utilisateur</h2>
                <form method="post">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Nom d'utilisateur</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="role">Rôle</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="">Sélectionner un rôle</option>
                                <option value="student">Étudiant</option>
                                <option value="teacher">Enseignant</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="groups">Groupes</label>
                            <select class="form-control select2" id="groups" name="groups[]" multiple>
                                <?php
                                $groups_result->data_seek(0);
                                while ($group = $groups_result->fetch_assoc()) {
                                    echo "<option value='" . $group['id'] . "'>" . htmlspecialchars($group['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_user" class="btn btn-primary">Ajouter l'utilisateur</button>
                </form>
            </div>
        </div>

        <!-- Add Group Tab -->
        <div class="tab-content" id="add-group">
            <div class="card">
                <h2><i class="fas fa-plus-circle"></i> Ajouter un Groupe</h2>
                <form method="post">
                    <div class="form-group">
                        <label for="group_name">Nom du Groupe</label>
                        <input type="text" id="group_name" name="group_name" required>
                    </div>
                    <div class="form-group">
                        <label for="group_description">Description</label>
                        <textarea id="group_description" name="group_description" rows="3"></textarea>
                    </div>
                    <button type="submit" name="add_group">
                        <i class="fas fa-plus-circle"></i> Ajouter Groupe
                    </button>
                </form>
            </div>
        </div>

        <!-- Add Subject Tab -->
        <div class="tab-content" id="add-subject">
            <div class="card">
                <h2><i class="fas fa-plus-circle"></i> Ajouter une Matière</h2>
                <form method="post">
                    <div class="form-group">
                        <label for="subject_name">Nom de la Matière</label>
                        <input type="text" id="subject_name" name="subject_name" required>
                    </div>
                    <div class="form-group">
                        <label for="subject_description">Description</label>
                        <textarea id="subject_description" name="subject_description" rows="3"></textarea>
                    </div>
                    <button type="submit" name="add_subject">
                        <i class="fas fa-plus-circle"></i> Ajouter Matière
                    </button>
                </form>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Modifier l'utilisateur</h2>
                <form method="POST" id="editUserForm">
                    <input type="hidden" name="user_id" id="editUserId">
                    
                    <div class="form-group">
                        <label for="editName">Nom:</label>
                        <input type="text" id="editName" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editEmail">Email:</label>
                        <input type="email" id="editEmail" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editRole">Rôle:</label>
                        <select id="editRole" name="role" required onchange="toggleFields()">
                            <option value="student">Étudiant</option>
                            <option value="teacher">Enseignant</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="editPassword">Nouveau mot de passe (optionnel):</label>
                        <input type="password" id="editPassword" name="password">
                        <small>Laissez vide pour ne pas modifier le mot de passe</small>
                    </div>
                    
                    <div class="form-group" id="groupField">
                        <label for="editGroup">Groupe(s):</label>
                        <select id="editGroup" name="group_id[]" multiple class="select2">
                            <?php
                            $groups_result->data_seek(0);
                            while($group = $groups_result->fetch_assoc()) {
                                echo "<option value='" . $group['id'] . "'>" . htmlspecialchars($group['name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group" id="subjectField">
                        <label for="editSubject">Matière:</label>
                        <select id="editSubject" name="subject_id">
                            <option value="">Sélectionner une matière</option>
                            <?php
                            $subjects_result->data_seek(0);
                            while($subject = $subjects_result->fetch_assoc()) {
                                echo "<option value='" . $subject['id'] . "'>" . htmlspecialchars($subject['name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <button type="submit" name="update_user" class="btn btn-primary">Mettre à jour</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-tasks"></i>
                    Gestion des examens
                </h5>
                <div class="d-grid gap-2">
                    <a href="create_exam.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Créer un examen
                    </a>
                    <a href="view_exams.php" class="btn btn-secondary">
                        <i class="fas fa-list"></i> Voir les examens
                    </a>
                    <a href="teacher_monitor.php" class="btn btn-warning">
                        <i class="fas fa-shield-alt"></i> Surveiller les examens
                    </a>
                </div>
            </div>
        </div>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> Exam+ | Système de gestion d'examens en ligne | 
            <a href="#">Documentation</a>
        </div>
    </div>

    <script>
        // Tab Navigation
        document.querySelectorAll('.tab-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                button.classList.add('active');
                const tabId = button.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Fonction pour ouvrir le modal d'édition
        function openEditModal(userId, name, email, role, groupIds, subjectId) {
            const modal = document.getElementById('editModal');
            const form = document.getElementById('editUserForm');
            
            // Remplir les champs
            document.getElementById('editUserId').value = userId;
            document.getElementById('editName').value = name;
            document.getElementById('editEmail').value = email;
            document.getElementById('editRole').value = role;
            
            // Réinitialiser les sélections
            $('#editGroup').val(null).trigger('change');
            $('#editSubject').val(null).trigger('change');
            
            // Définir les valeurs si elles existent
            if (groupIds) {
                const groupIdArray = groupIds.split(',');
                $('#editGroup').val(groupIdArray).trigger('change');
            }
            if (subjectId) {
                $('#editSubject').val(subjectId).trigger('change');
            }
            
            // Afficher/masquer les champs appropriés
            toggleFields();
            
            // Afficher le modal
            modal.style.display = 'block';
        }

        function toggleFields() {
            const role = document.getElementById('editRole').value;
            const groupField = document.getElementById('groupField');
            const subjectField = document.getElementById('subjectField');
            
            if (role === 'teacher') {
                subjectField.style.display = 'block';
            } else {
                subjectField.style.display = 'none';
            }
            // Les groupes sont toujours visibles pour tous les rôles
            groupField.style.display = 'block';
        }

        // Fermer le modal
        document.querySelector('.close').onclick = function() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Fermer le modal en cliquant en dehors
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Gérer la soumission du formulaire
        document.getElementById('editUserForm').onsubmit = function(e) {
            // Ne pas empêcher la soumission par défaut
            return true;
        }

        // Filter tables
        document.getElementById('user-search').addEventListener('keyup', function() {
            const value = this.value.toLowerCase();
            const table = document.getElementById('users-table');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length; j++) {
                    const cell = cells[j];
                    if (cell.textContent.toLowerCase().indexOf(value) > -1) {
                        found = true;
                        break;
                    }
                }
                
                if (found) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
        
        // Role filter
        document.getElementById('role-filter').addEventListener('change', function() {
            const value = this.value;
            const table = document.getElementById('users-table');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                if (value === 'all') {
                    row.style.display = '';
                } else {
                    const roleCell = row.getElementsByTagName('td')[2];
                    if (roleCell.textContent.toLowerCase().includes(value)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            }
        });
        
        // Fade out success messages
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);

        // Initialiser Select2
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'default',
                placeholder: 'Sélectionnez les groupes',
                allowClear: true
            });
        });

        // WebSocket connection for cheating attempts monitoring
        const ws = new WebSocket('ws://localhost:8080');
        const cheatingAttemptsElement = document.createElement('div');
        cheatingAttemptsElement.id = 'cheating-attempts';
        cheatingAttemptsElement.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #ff4444;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            display: none;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        `;
        document.body.appendChild(cheatingAttemptsElement);

        let cheatingAttempts = 0;

        ws.onmessage = function(event) {
            const data = JSON.parse(event.data);
            if (data.type === 'cheating_attempt') {
                cheatingAttempts++;
                cheatingAttemptsElement.textContent = `Tentatives de triche: ${cheatingAttempts}`;
                cheatingAttemptsElement.style.display = 'block';
                
                // Animation de notification
                cheatingAttemptsElement.style.animation = 'shake 0.5s';
                setTimeout(() => {
                    cheatingAttemptsElement.style.animation = '';
                }, 500);
            }
        };

        ws.onopen = function() {
            console.log('Connecté au serveur de surveillance');
        };

        ws.onerror = function(error) {
            console.error('Erreur WebSocket:', error);
        };

        // Style pour l'animation
        const styleSheet = document.createElement('style');
        styleSheet.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(styleSheet);
    </script>
</body>
</html>