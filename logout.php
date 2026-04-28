<?php
session_start(); // Démarre la session

// Supprime toutes les variables de session
$_SESSION = [];

// Détruit la session
session_destroy();

header('Location: login.php?msg=' . rawurlencode('Vous êtes déconnecté.'));

exit;
?>
