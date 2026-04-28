<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

$roles = ['admin', 'formateur', 'stagiaire'];
if (!isset($_SESSION['user_id'], $_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles, true)) {
    header('Location: login.php?msg=' . rawurlencode('Veuillez vous connecter.'));
    exit;
}

$userId = (int) $_SESSION['user_id'];
$_SESSION['id_utilisateur'] = $_SESSION['id_utilisateur'] ?? $userId;
$_SESSION['role'] = $_SESSION['role'] ?? $_SESSION['user_role'];
$role = $_SESSION['user_role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like'])) {
    $sid = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($sid > 0) {
        $pdo->prepare('INSERT IGNORE INTO like_sujet (id_utilisateur, id_sujet) VALUES (?, ?)')
            ->execute([$userId, $sid]);
        header('Location: voir_sujet.php?id=' . $sid);
        exit;
    }
}

if (!isset($_GET['id'])) {
    header('Location: forum.php?msg=' . rawurlencode('Sujet introuvable.'));
    exit;
}

$id_sujet = (int) $_GET['id'];
$req = $pdo->prepare(
    'SELECT s.*, u.nom, u.prenom FROM sujet s JOIN utilisateur u ON s.id_utilisateur = u.id_utilisateur WHERE s.id_sujet = ?'
);
$req->execute([$id_sujet]);
$sujet = $req->fetch(PDO::FETCH_ASSOC);

if (!$sujet) {
    header('Location: forum.php?msg=' . rawurlencode('Sujet introuvable.'));
    exit;
}

$statut = $sujet['statut_validation'] ?? 'accepte';
if ($statut !== 'accepte') {
    $canModerate = in_array($role, ['admin', 'formateur'], true);
    $isAuthor = (int) $sujet['id_utilisateur'] === $userId;
    if (!$canModerate && !$isAuthor) {
        header('Location: forum.php?msg=' . rawurlencode('Ce sujet n\'est pas encore validé ou n\'est pas accessible.'));
        exit;
    }
}

$msgerr = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contenu']) && !isset($_POST['like'])) {
    $contenu = trim((string) ($_POST['contenu'] ?? ''));
    if ($contenu === '') {
        $msgerr['contenu'] = 'Veuillez écrire une réponse.';
    }
    $canReply = (($statut ?? '') === 'accepte') || in_array($role, ['admin', 'formateur'], true);
    if (!$canReply) {
        $msgerr['contenu'] = 'Réponses possibles après validation du sujet.';
    }
    if (empty($msgerr)) {
        $pdo->prepare('INSERT INTO reponse (contenu, id_sujet, id_utilisateur) VALUES (?, ?, ?)')
            ->execute([$contenu, $id_sujet, $userId]);
        header('Location: voir_sujet.php?id=' . $id_sujet . '&msgs=' . rawurlencode('Réponse ajoutée'));
        exit;
    }
}

$reqr = $pdo->prepare(
    'SELECT r.*, u.nom, u.prenom FROM reponse r JOIN utilisateur u ON r.id_utilisateur = u.id_utilisateur WHERE r.id_sujet = ? ORDER BY r.id_reponse ASC'
);
$reqr->execute([$id_sujet]);
$reponses = $reqr->fetchAll(PDO::FETCH_ASSOC);

$reqLike = $pdo->prepare('SELECT COUNT(*) FROM like_sujet WHERE id_sujet = ?');
$reqLike->execute([$id_sujet]);
$nbLikes = (int) $reqLike->fetchColumn();

$userLiked = false;
$chk = $pdo->prepare('SELECT 1 FROM like_sujet WHERE id_sujet = ? AND id_utilisateur = ? LIMIT 1');
$chk->execute([$id_sujet, $userId]);
$userLiked = (bool) $chk->fetchColumn();

$page_title = htmlspecialchars((string) $sujet['titre']) . ' — Forum';
$current_page = basename(__FILE__);
$extra_head = '<style>.voir-sujet-page .card-sujet { border-radius: 12px; }</style>';

include __DIR__ . '/includes/layout_head.php';
include __DIR__ . '/includes/app_nav.php';
?>

<div class="container py-4 voir-sujet-page ismo-main">
    <?php if (($statut ?? '') !== 'accepte') : ?>
        <div class="alert alert-warning">
            <?php if ($statut === 'en_attente') : ?>
                Ce sujet est <strong>en attente de validation</strong>. Il sera visible pour tous après acceptation par un administrateur ou un formateur.
            <?php else : ?>
                Ce sujet a été <strong>refusé</strong> et ne figure pas dans la liste publique du forum.
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4 card-sujet">
        <div class="card-body p-4">
            <h1 class="h3 text-success mb-2"><?= htmlspecialchars((string) $sujet['titre']) ?></h1>
            <p class="text-muted small mb-3">
                <?= htmlspecialchars($sujet['prenom'] . ' ' . $sujet['nom']) ?>
                · <?= htmlspecialchars((string) $sujet['date_creation']) ?>
                · <?= htmlspecialchars((string) $sujet['categorie']) ?>
            </p>
            <div class="forum-content"><?= nl2br(htmlspecialchars((string) $sujet['contenu'])) ?></div>
        </div>
    </div>

    <div class="mb-3">
        <form method="post" class="d-inline">
            <input type="hidden" name="like" value="1">
            <button type="submit" class="btn btn-outline-primary btn-sm" <?= $userLiked ? 'disabled' : '' ?>>
                J’aime (<?= $nbLikes ?>)
            </button>
        </form>
    </div>

    <h2 class="h5 mb-3">Réponses</h2>
    <?php foreach ($reponses as $rep) : ?>
        <div class="border rounded p-3 mb-2 bg-white">
            <strong><?= htmlspecialchars($rep['nom'] . ' ' . $rep['prenom']) ?></strong>
            <span class="text-muted small"><?= htmlspecialchars((string) $rep['date_reponse']) ?></span>
            <div class="mt-2"><?= nl2br(htmlspecialchars((string) $rep['contenu'])) ?></div>
            <?php if ($userId === (int) $rep['id_utilisateur'] || $role === 'admin') : ?>
                <a href="supprimer_reponse.php?id=<?= (int) $rep['id_reponse'] ?>&sujet=<?= $id_sujet ?>" class="btn btn-outline-danger btn-sm mt-2">Supprimer</a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <?php if (($statut ?? '') === 'accepte' || in_array($role, ['admin', 'formateur'], true)) : ?>
        <form method="POST" class="mt-4">
            <div class="mb-2">
                <label class="form-label">Votre réponse</label>
                <textarea name="contenu" class="form-control" rows="4" placeholder="Votre réponse..."><?= isset($_POST['contenu']) ? htmlspecialchars((string) $_POST['contenu']) : '' ?></textarea>
                <?php if (!empty($msgerr['contenu'])) : ?>
                    <div class="text-danger"><?= htmlspecialchars($msgerr['contenu']) ?></div>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-success">Répondre</button>
        </form>
    <?php endif; ?>

    <p class="mt-4 mb-0"><a href="forum.php" class="btn btn-outline-secondary">&larr; Retour au forum</a></p>
</div>

<?php include __DIR__ . '/includes/layout_foot.php'; ?>
