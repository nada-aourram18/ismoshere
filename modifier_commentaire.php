<?php
session_start();
include("dbconnexion.php");
if (!isset($_SESSION['id_utilisateur']) || !isset($_GET['id_com']) || !isset($_GET['id_ressource'])) {
    header("Location:commentaire.php?msge=Accès refusé");
    exit;
}
$id_com = intval($_GET['id_com']);
$id_ressource = intval($_GET['id_ressource']);
$msgerr = [];
// جلب بيانات التعليق
$req = $db->prepare("SELECT * FROM commentaire WHERE id_com=?");
$req->execute([$id_com]);
$com = $req->fetch(PDO::FETCH_ASSOC);
if (!$com || ($com['id_utilisateur'] != $_SESSION['id_utilisateur'] && (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'))) {
    header("Location:commentaire.php?id_ressource=$id_ressource&msge=Accès refusé");
    exit;
}
// تعديل التعليق
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['modifier'])) {
    $contenu = trim($_POST['contenu']);
    if (empty($contenu)) {
        $msgerr['contenu'] = "Veuillez écrire un commentaire.";
    }
    if (empty($msgerr)) {
        $stmt = $db->prepare("UPDATE commentaire SET contune=? WHERE id_com=?");
        $stmt->execute([$contenu, $id_com]);
        header("Location:commentaire.php?id_ressource=$id_ressource&msg=Commentaire modifié");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier commentaire</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container my-5">
    <h3>Modifier le commentaire</h3>
    <?php if(isset($msgerr['contenu'])) echo "<div class='text-danger'>{$msgerr['contenu']}</div>"; ?>
    <form method="POST">
        <textarea name="contenu" class="form-control mb-2"><?= htmlspecialchars($com['contune']) ?></textarea>
        <button type="submit" name="modifier" class="btn btn-success">Enregistrer</button>
        <a href="commentaire.php?id_ressource=<?= $id_ressource ?>" class="btn btn-secondary">Annuler</a>
    </form>
</div>
</body>
</html>