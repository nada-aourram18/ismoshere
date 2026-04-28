<?php
session_start();
require_once 'db.php';

$roles = ['admin', 'formateur', 'stagiaire'];
if (!isset($_SESSION['user_id'], $_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles, true)) {
    header('Location: login.php?msg=' . rawurlencode('Veuillez vous connecter.'));
    exit;
}
$id_utilisateur = (int) ($_SESSION['id_utilisateur'] ?? $_SESSION['user_id']);

$id_sujet = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id_sujet <= 0) {
    header('Location: forum.php');
    exit;
}

$stmtSujet = $pdo->prepare("SELECT s.*, u.nom, u.prenom FROM sujet s JOIN utilisateur u ON s.id_utilisateur = u.id_utilisateur WHERE s.id_sujet = ?");
$stmtSujet->execute([$id_sujet]);
$sujet = $stmtSujet->fetch(PDO::FETCH_ASSOC);

if (!$sujet) {
    echo "<div class='container my-4'><div class='alert alert-danger'>Sujet introuvable.</div></div>";
    exit;
}

$stmtReponses = $pdo->prepare("SELECT r.*, u.nom, u.prenom FROM reponse r JOIN utilisateur u ON r.id_utilisateur = u.id_utilisateur WHERE r.id_sujet = ? ORDER BY r.date_reponse DESC");
$stmtReponses->execute([$id_sujet]);
$reponses = $stmtReponses->fetchAll(PDO::FETCH_ASSOC);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contenu = trim($_POST['contenu'] ?? '');

    if ($contenu === '') {
        $errors[] = "Le contenu de la réponse ne peut pas être vide.";
    }

    if (mb_strlen($contenu) > 1000) {
        $errors[] = "Le contenu est trop long (maximum 1000 caractères).";
    }

    if (empty($errors)) {
        $stmtInsert = $pdo->prepare("INSERT INTO reponse (id_sujet, id_utilisateur, contenu, date_reponse) VALUES (?, ?, ?, NOW())");
        $stmtInsert->execute([$id_sujet, $id_utilisateur, $contenu]);
        header("Location: reponse.php?id=$id_sujet");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Discussion - Forum ISMO SHARE</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    <?php include 'style-forum.css'; ?>
  </style>
</head>
<body>
<div class="container my-4">
  <div class="row">
    <!-- Sidebar -->
   <div class="forum-container p-3 mb-3">
  <h5 class="mb-3"><i class="fas fa-search me-2"></i>Rechercher</h5>
  <form method="GET" action="recherche.php">
    <div class="input-group mb-3">
      <input type="text" name="q" class="form-control" placeholder="Mots-clés..." required />
      <button class="btn btn-outline-success" type="submit"><i class="fas fa-search"></i></button>
    </div>
  </form>
</div>


    <!-- Discussion -->
    <div class="col-lg-9">
      <div class="forum-container p-4">
        <h2 class="post-title mt-2"><?= htmlspecialchars($sujet['titre']) ?></h2>
        <p class="post-content"><?= nl2br(htmlspecialchars($sujet['contenu'])) ?></p>
        <div class="text-muted mb-3">
          Publié par <strong><?= htmlspecialchars($sujet['prenom'] . " " . $sujet['nom']) ?></strong>
          le <?= htmlspecialchars($sujet['date_creation']) ?>
        </div>

        <h4 class="mb-4"><i class="fas fa-reply me-2"></i>Réponses</h4>
        <?php if (count($reponses) === 0): ?>
          <p>Aucune réponse pour le moment.</p>
        <?php else: ?>
          <?php foreach ($reponses as $rep): ?>
            <div class="reponse mb-4 p-3">
              <div class="d-flex justify-content-between">
                <span class="post-author"><?= htmlspecialchars($rep['prenom'] . ' ' . $rep['nom']) ?></span>
                <span class="post-time"><i class="far fa-clock"></i> <?= htmlspecialchars($rep['date_reponse']) ?></span>
              </div>
              <p class="post-content mt-2"><?= nl2br(htmlspecialchars($rep['contenu'])) ?></p>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <!-- عرض الأخطاء -->
        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul>
              <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <div class="mt-5">
          <h4 class="mb-3"><i class="fas fa-edit me-2"></i>Ajouter une réponse</h4>
          <form method="POST" novalidate>
            <div class="mb-3">
              <textarea name="contenu" class="form-control" rows="5" placeholder="Votre réponse..." required><?= isset($contenu) ? htmlspecialchars($contenu) : '' ?></textarea>
            </div>
            <button type="submit" class="btn btn-success">
              <i class="fas fa-paper-plane me-1"></i> Publier
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
