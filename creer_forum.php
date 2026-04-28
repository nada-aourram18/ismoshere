<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'], $_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['stagiaire', 'formateur', 'admin'], true)) {
    header('Location: login.php?msg=' . rawurlencode('Veuillez vous connecter.'));
    exit;
}
if (!isset($_SESSION['id_utilisateur'])) {
    $_SESSION['id_utilisateur'] = (int) $_SESSION['user_id'];
}
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = $_SESSION['user_role'];
}
if($_SERVER['REQUEST_METHOD']=="POST"){
  extract($_POST);
  $msgerr = [];
  if(isset($Publier)){
    if(!isset($titre) || empty($titre)) $msgerr['titre'] = "Veuillez entrer le titre du sujet.";
    if(!isset($contenu) || empty($contenu)) $msgerr['contenu'] = "Veuillez entrer le contenu du sujet.";
    if(!isset($categorie) || empty($categorie)) $msgerr['categorie'] = "Veuillez choisir une catégorie.";

    if(empty($msgerr)){
      // include("dbconnexion.php");
      $titre = htmlspecialchars($titre);
      $contenu = htmlspecialchars($contenu);
      $categorie = htmlspecialchars($categorie);
      $date_creation = date('Y-m-d');
      try {
        $req = $pdo->prepare("INSERT INTO sujet (titre, contenu, categorie, id_utilisateur, date_creation, statut_validation) VALUES (?, ?, ?, ?, ?, 'en_attente')");
        $req->execute([$titre, $contenu, $categorie, $_SESSION['id_utilisateur'], $date_creation]);
        header('Location: forum.php?created_pending=1');
        exit;
      } catch (PDOException $e) {
        if (stripos($e->getMessage(), 'statut_validation') !== false || stripos($e->getMessage(), 'Unknown column') !== false) {
          header('Location: forum.php?msg=' . rawurlencode('Ajoutez la colonne forum : database/migration_forum_validation.sql'));
        } else {
          header('Location: forum.php?msg=' . rawurlencode('Erreur création sujet'));
        }
        exit;
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Créer un Forum</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body { background-color: #f0fff0; }
    .card { border: 1px solid #4CAF50; border-radius: 15px; }
    .btn-green { background-color: #4CAF50; color: white; }
  </style>
</head>
<body>
  <div class="container mt-5">
    <div class="card p-4 shadow">
      <h3 class="text-center text-success mb-4">Créer un nouveau sujet</h3>
      <form method="POST" action="">
        <div class="mb-3">
          <?php if(isset($msgerr['titre'])){echo "<div class='text-danger'>{$msgerr['titre']}</div>";}?>
          <label for="titre" class="form-label">Titre du sujet</label>
          <input type="text" class="form-control" name="titre" value="<?= isset($titre) ? htmlspecialchars($titre) : '' ?>" required>
        </div>
        <div class="mb-3">
          <?php if(isset($msgerr['categorie'])){echo "<div class='text-danger'>{$msgerr['categorie']}</div>";}?>
          <label for="categorie" class="form-label">Catégorie</label>
          <select class="form-select" name="categorie" required>
            <option value="">-- Choisir --</option>
            <option <?= (isset($categorie) && $categorie=="Entraide") ? "selected" : "" ?>>Entraide</option>
            <option <?= (isset($categorie) && $categorie=="Questions") ? "selected" : "" ?>>Questions</option>
            <option <?= (isset($categorie) && $categorie=="Astuce") ? "selected" : "" ?>>Astuce</option>
            <option <?= (isset($categorie) && $categorie=="Autre") ? "selected" : "" ?>>Autre</option>
          </select>
        </div>
        <div class="mb-3">
          <?php if(isset($msgerr['contenu'])){echo "<div class='text-danger'>{$msgerr['contenu']}</div>";}?>
          <label for="contenu" class="form-label">Contenu</label>
          <textarea class="form-control" name="contenu" rows="5" required><?= isset($contenu) ? htmlspecialchars($contenu) : '' ?></textarea>
        </div>
        <button type="submit" name="Publier" class="btn btn-green w-100">Publier</button>
      </form>
    </div>
  </div>
</body>
</html>