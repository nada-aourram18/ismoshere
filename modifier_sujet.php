<?php
session_start();
if (!in_array($_SESSION['role'], ['stagiaire', 'formateur', 'admin'])) {
    header("Location:login.php");
    exit;
}
include("dbconnexion.php");

// جلب بيانات الموضوع
if (isset($_GET["id"])) {
    $reqm = $db->prepare("SELECT * FROM sujet WHERE id_sujet=?");
    $reqm->execute([$_GET["id"]]);
    $sujet = $reqm->fetch(PDO::FETCH_ASSOC);
    if (!$sujet) {
        header("Location:forum.php?msge=Ce sujet n'existe pas");
        exit;
    }
    // فقط صاحب الموضوع أو الأدمن يمكنهم التعديل
    if ($sujet['id_utilisateur'] != $_SESSION['id_utilisateur'] && $_SESSION['role'] != 'admin') {
        header("Location:forum.php?msge=Accès refusé");
        exit;
    }
} else {
    header("Location:forum.php?msge=ID sujet manquant");
    exit;
}

$msgerr = [];
$success = "";

// معالجة الفورم
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    extract($_POST);
    if (!isset($titre) || empty(trim($titre)))
        $msgerr['titre'] = "Veuillez entrer le titre du sujet.";
    if (!isset($categorie) || empty(trim($categorie)))
        $msgerr['categorie'] = "Veuillez choisir une catégorie.";
    if (!isset($contenu) || empty(trim($contenu)))
        $msgerr['contenu'] = "Veuillez écrire le contenu du sujet.";

    if (empty($msgerr)) {
        $titre = htmlspecialchars($titre);
        $categorie = htmlspecialchars($categorie);
        $contenu = htmlspecialchars($contenu);

        try {
            $requp = $db->prepare("UPDATE sujet SET titre=?, contenu=?, categorie=? WHERE id_sujet=?");
            $r = $requp->execute([$titre, $contenu, $categorie, $_GET["id"]]);
            if ($r) {
                $success = "Sujet modifié avec succès !";
                // تحديث بيانات الموضوع بعد التعديل
                $sujet['titre'] = $titre;
                $sujet['contenu'] = $contenu;
                $sujet['categorie'] = $categorie;
            } else {
                $msgerr['global'] = "Erreur lors de la modification du sujet.";
            }
        } catch (PDOException $e) {
            $msgerr['global'] = "Erreur BDD: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Sujet</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <div class="card p-4 shadow">
        <h3 class="text-center text-success mb-4">Modifier le sujet</h3>
        <?php
        if (!empty($success)) echo "<div class='alert alert-success'>$success</div>";
        if (!empty($msgerr['global'])) echo "<div class='alert alert-danger'>{$msgerr['global']}</div>";
        ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Titre du sujet</label>
                <input type="text" class="form-control" name="titre" value="<?= isset($titre) ? htmlspecialchars($titre) : htmlspecialchars($sujet['titre']) ?>">
                <?php if (isset($msgerr['titre'])) echo "<div class='text-danger'>{$msgerr['titre']}</div>"; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Catégorie</label>
                <select class="form-select" name="categorie">
                    <option value="">-- Choisir --</option>
                    <option <?= (isset($categorie) && $categorie=="Entraide") || (isset($sujet['categorie']) && $sujet['categorie']=="Entraide") ? "selected" : "" ?>>Entraide</option>
                    <option <?= (isset($categorie) && $categorie=="Questions") || (isset($sujet['categorie']) && $sujet['categorie']=="Questions") ? "selected" : "" ?>>Questions</option>
                    <option <?= (isset($categorie) && $categorie=="Astuce") || (isset($sujet['categorie']) && $sujet['categorie']=="Astuce") ? "selected" : "" ?>>Astuce</option>
                    <option <?= (isset($categorie) && $categorie=="Autre") || (isset($sujet['categorie']) && $sujet['categorie']=="Autre") ? "selected" : "" ?>>Autre</option>
                </select>
                <?php if (isset($msgerr['categorie'])) echo "<div class='text-danger'>{$msgerr['categorie']}</div>"; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Contenu</label>
                <textarea class="form-control" name="contenu" rows="5"><?= isset($contenu) ? htmlspecialchars($contenu) : htmlspecialchars($sujet['contenu']) ?></textarea>
                <?php if (isset($msgerr['contenu'])) echo "<div class='text-danger'>{$msgerr['contenu']}</div>"; ?>
            </div>
            <button type="submit" class="btn btn-success w-100">Enregistrer les modifications</button>
        </form>
        <a href="forum.php" class="btn btn-secondary mt-3">Retour au forum</a>
    </div>
</div>
</body>
</html>