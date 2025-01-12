<?php
// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = ""; // Remplace par le mot de passe de ton serveur local si nécessaire
$dbname = "exam_system";

// Connexion à MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Requête pour récupérer tous les utilisateurs
$sql = "SELECT id, password FROM users";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $plain_password = $row['password'];

        // Vérifier si le mot de passe est déjà crypté
        if (password_get_info($plain_password)['algo'] == 0) { // Si non crypté
            // Crypter le mot de passe
            $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

            // Mettre à jour le mot de passe crypté dans la base de données
            $update_sql = "UPDATE users SET password = '$hashed_password' WHERE id = $id";
            if ($conn->query($update_sql) === TRUE) {
                echo "Mot de passe pour l'utilisateur ID $id mis à jour.<br>";
            } else {
                echo "Erreur pour l'utilisateur ID $id : " . $conn->error . "<br>";
            }
        } else {
            echo "Mot de passe pour l'utilisateur ID $id est déjà crypté.<br>";
        }
    }
} else {
    echo "Aucun utilisateur trouvé.";
}

$conn->close();
?>