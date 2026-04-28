<?php
session_start();
// include("dbconnexion.php");
include 'db.php';

if (!isset($_SESSION['id_utilisateur']) && isset($_SESSION['user_id'])) {
    $_SESSION['id_utilisateur'] = (int) $_SESSION['user_id'];
}
if (!isset($_SESSION['role']) && isset($_SESSION['user_role'])) {
    $_SESSION['role'] = $_SESSION['user_role'];
}

if (!isset($_SESSION['id_utilisateur']) || !isset($_GET['id'])) {
    header("Location:forum.php?msge=Accès refusé");
    exit;
}
if (!in_array($_SESSION['role'], ['stagiaire', 'formateur', 'admin'])) {
    header("Location:login.php");
    exit;
}
$req = $pdo->prepare("SELECT * FROM sujet WHERE id_sujet=?");
$req->execute([$_GET['id']]);
$sujet = $req->fetch(PDO::FETCH_ASSOC);
if (!$sujet) {
    header("Location:forum.php?msge=Sujet introuvable");
    exit;
}
if ($_SESSION['id_utilisateur'] != $sujet['id_utilisateur'] && $_SESSION['role'] != 'admin') {
    header("Location:forum.php?msge=Accès refusé");
    exit;
}
// Supprimer les réponses d'abord (clé étrangère)
$pdo->prepare("DELETE FROM reponse WHERE id_sujet=?")->execute([$_GET['id']]);
$pdo->prepare("DELETE FROM sujet WHERE id_sujet=?")->execute([$_GET['id']]);
header("Location:forum.php?msgs=Sujet supprimé avec succès");
exit;
?>