<?php
session_start();
// Détruire toutes les variables de session
$_SESSION = [];
// Détruire la session elle-même
session_destroy();
// Rediriger vers la page de connexion
header("Location: login.php");
exit();
?>