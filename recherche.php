<?php
require_once 'db.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$resultats = [];

if ($q !== '') {
    $searchTerm = "%$q%";
    $stmt = $pdo->prepare("SELECT s.*, u.nom, u.prenom FROM sujet s JOIN utilisateur u ON s.id_utilisateur = u.id_utilisateur WHERE s.titre LIKE ? OR s.contenu LIKE ? ORDER BY s.date_creation DESC");
    $stmt->execute([$searchTerm, $searchTerm]);
    $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Résultats de recherche - Forum ISMO SHARE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container my-4">
  <h2>Résultats de recherche pour : <?= htmlspecialchars($q) ?></h2>

  <?php if ($q === ''): ?>
    <div class="alert alert-warning">Veuillez entrer un mot-clé pour rechercher.</div>
  <?php elseif (count($resultats) === 0): ?>
    <div class="alert alert-info">Aucun résultat trouvé pour "<?= htmlspecialchars($q) ?>".</div>
  <?php else: ?>
    <ul class="list-group">
      <?php foreach ($resultats as $sujet): ?>
        <li class="list-group-item">
          <a href="reponse.php?id=<?= $sujet['id_sujet'] ?>">
            <?= htmlspecialchars($sujet['titre']) ?>
          </a>
          <br />
          <small>Publié par <?= htmlspecialchars($sujet['prenom'] . ' ' . $sujet['nom']) ?> le <?= htmlspecialchars($sujet['date_creation']) ?></small>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <a href="reponse.php?id=1" class="btn btn-secondary mt-4">Retour au forum</a>
</div>
</body>
</html>
