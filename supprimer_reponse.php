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

if (!isset($_SESSION['id_utilisateur']) || !isset($_GET['id']) || !isset($_GET['sujet'])) {
    header("Location:forum.php?msge=Accès refusé");
    exit;
}
if (!in_array($_SESSION['role'], ['stagiaire', 'formateur', 'admin'])) {
    header("Location:login.php");
    exit;
}
$req = $pdo->prepare("SELECT * FROM reponse WHERE id_reponse=?");
$req->execute([$_GET['id']]);
$rep = $req->fetch(PDO::FETCH_ASSOC);
if (!$rep) {
    header("Location:voir_sujet.php?id=".$_GET['sujet']."&msge=Réponse introuvable");
    exit;
}
if ($_SESSION['id_utilisateur'] != $rep['id_utilisateur'] && $_SESSION['role'] != 'admin') {
    header("Location:voir_sujet.php?id=".$_GET['sujet']."&msge=Accès refusé");
    exit;
}
$pdo->prepare("DELETE FROM reponse WHERE id_reponse=?")->execute([$_GET['id']]);
header("Location:voir_sujet.php?id=".$_GET['sujet']."&msgs=Réponse supprimée");
exit;
?>