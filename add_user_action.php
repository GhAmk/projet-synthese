<?php
// Connexion à la base de données
$db_host = 'localhost';
$db_user = 'root';  // Remplacez par votre nom d'utilisateur
$db_pass = '';      // Remplacez par votre mot de passe
$db_name = 'exam_system';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connexion échouée: " . $conn->connect_error);
}

// Récupérer les données du formulaire
$role = $_POST['role'];
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$email = $_POST['email'];
$password = $_POST['password'];
$name = $first_name . ' ' . $last_name;

// Vérifier si l'email existe déjà
$check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check_email->bind_param("s", $email);
$check_email->execute();
$result = $check_email->get_result();

if ($result->num_rows > 0) {
    // L'email existe déjà
    header("Location: add_students.php?status=error&message=email_exists");
    exit;
}

// Hashage du mot de passe (sécurité essentielle !)
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Préparer la requête pour ajouter l'utilisateur
$stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

// Exécuter la requête
if ($stmt->execute()) {
    $user_id = $conn->insert_id;
    
    // Si c'est un étudiant et qu'un groupe est sélectionné, l'associer au groupe
    if ($role === 'student' && isset($_POST['group_id'])) {
        $group_id = $_POST['group_id'];
        
        // Si "nouveau groupe" est sélectionné
        if ($group_id === 'new' && !empty($_POST['new_group_name'])) {
            $new_group_name = $_POST['new_group_name'];
            $new_group_description = $_POST['new_group_description'] ?? '';
            
            // Insérer le nouveau groupe
            $new_group_stmt = $conn->prepare("INSERT INTO groups (name, description) VALUES (?, ?)");
            $new_group_stmt->bind_param("ss", $new_group_name, $new_group_description);
            
            if ($new_group_stmt->execute()) {
                $group_id = $conn->insert_id;
                $new_group_stmt->close();
            } else {
                // Erreur lors de la création du groupe
                header("Location: add_students.php?status=error&message=group_creation_failed");
                exit;
            }
        }
        
        // Associer l'utilisateur au groupe si un groupe valide est sélectionné
        if (is_numeric($group_id)) {
            $user_group_stmt = $conn->prepare("INSERT INTO user_groups (user_id, group_id) VALUES (?, ?)");
            $user_group_stmt->bind_param("ii", $user_id, $group_id);
            
            if (!$user_group_stmt->execute()) {
                // Erreur lors de l'association au groupe, mais l'utilisateur a été créé
                header("Location: add_students.php?status=partial&message=user_created_group_failed");
                exit;
            }
            $user_group_stmt->close();
        }
    }
    
    // Redirection avec succès
    header("Location: add_students.php?status=success");
} else {
    // Erreur lors de l'ajout de l'utilisateur
    header("Location: add_students.php?status=error&message=user_creation_failed");
}

// Fermer les connexions
$stmt->close();
$conn->close();
?>