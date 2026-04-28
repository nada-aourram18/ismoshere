<?php
session_start();
include 'db.php';

if (!isset($_SESSION['id_utilisateur']) && isset($_SESSION['user_id'])) {
    $_SESSION['id_utilisateur'] = (int) $_SESSION['user_id'];
}
if (!isset($_SESSION['role']) && isset($_SESSION['user_role'])) {
    $_SESSION['role'] = $_SESSION['user_role'];
}

if (!isset($_SESSION['id_utilisateur']) || !isset($_GET['id_com']) || !isset($_GET['id_ressource'])) {
    header("Location:commentaire.php?msge=Accès refusé");
    exit;
}
$id_com = intval($_GET['id_com']);
$id_ressource = intval($_GET['id_ressource']);
$req = $pdo->prepare("SELECT * FROM commentaire WHERE id_com=?");
$req->execute([$id_com]);
$com = $req->fetch(PDO::FETCH_ASSOC);
if (!$com || ($com['id_utilisateur'] != $_SESSION['id_utilisateur'] && (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'))) {
    header("Location:commentaire.php?id_ressource=$id_ressource&msge=Accès refusé");
    exit;
}
$pdo->prepare("DELETE FROM commentaire WHERE id_com=?")->execute([$id_com]);
header("Location:commentaire.php?id_ressource=$id_ressource&msg=Commentaire supprimé");
exit;
?>