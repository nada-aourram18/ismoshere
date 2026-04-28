<?php 


session_start();
include 'db.php';

$roles = ['admin', 'formateur', 'stagiaire'];
if (!isset($_SESSION['user_id'], $_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles, true)) {
    header('Location: login.php?msg=' . rawurlencode('Veuillez vous connecter.'));
    exit;
}
if (!isset($_SESSION['id_utilisateur']) && isset($_SESSION['user_id'])) {
    $_SESSION['id_utilisateur'] = (int) $_SESSION['user_id'];
}
if (!isset($_SESSION['role']) && isset($_SESSION['user_role'])) {
    $_SESSION['role'] = $_SESSION['user_role'];
}
// INSERT INTO commentaire (id_com, contune, id_ressource) VALUES
// (1, 'Très bon document, merci !', 3),
// (2, 'Est-ce que vous pouvez ajouter plus d’exemples ?', 4),
// (3, 'Ressource très utile pour la révision.', 5);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: Ressources Pedagogiques.php');
    exit;
}
$stmt = $pdo->prepare('SELECT * FROM commentaire WHERE id_ressource = ?');
$stmt->execute([$id]);
    $commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
$nbCommentaires = count($commentaires);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Commentaires</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container my-5">
    <h2 class="section-title">
        <i class="far fa-comments"></i> Commentaires (<?= $nbCommentaires ?>)
    </h2>
    <ul class="comment-list">
    <?php 
    
    
    foreach($commentaires as $com): ?>
        <li class="comment-item" data-comment-id="<?= $com['id_com'] ?>">
            <div class="comment-header">
                <img src="<?= htmlspecialchars($com['photo_profil']) ?>" class="comment-avatar" style="width:45px;height:45px;border-radius:50%;">
                <span class="comment-author"><?= htmlspecialchars($com['nom']." ".$com['prenom']) ?></span>
                <span class="comment-date"><?= date('d/m/Y H:i', strtotime($com['date_com'])) ?></span>
            </div>
            <p class="comment-text"><?= nl2br(htmlspecialchars($com['contune'])) ?></p>
            <?php if ($_SESSION['id_utilisateur'] == $com['id_utilisateur'] || (isset($_SESSION['role']) && $_SESSION['role'] == 'admin')): ?>
                <a href="modifier_commentaire.php?id_com=<?= $com['id_com'] ?>&id_ressource=<?= $id_ressource ?>" class="btn btn-sm btn-warning">Modifier</a>
                <a href="supprimer_commentaire.php?id_com=<?= $com['id_com'] ?>&id_ressource=<?= $id_ressource ?>" class="btn btn-sm btn-danger" onclick="return confirm('Voulez-vous vraiment supprimer ce commentaire ?')">Supprimer</a>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
    </ul>
    <div class="comment-form mt-4">
        <h3 class="form-title">Ajouter un commentaire</h3>
        <?php if(isset($msgerr['contenu'])) echo "<div class='text-danger'>{$msgerr['contenu']}</div>"; ?>
        <form method="POST">
            <textarea name="contenu" class="form-control mb-2" placeholder="Partagez vos réflexions sur cette ressource..."></textarea>
            <button type="submit" name="ajouter" class="btn btn-success">
                <i class="far fa-paper-plane"></i> Publier le commentaire
            </button>
        </form>
    </div>
</div>
</body>
</html>