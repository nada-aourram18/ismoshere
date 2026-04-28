<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

$roles_ok = ['admin', 'formateur'];
if (!isset($_SESSION['user_id'], $_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles_ok, true)) {
    header('Location: forum.php?msg=' . rawurlencode('Accès réservé aux administrateurs et formateurs.'));
    exit;
}

$role = $_SESSION['user_role'];
$page_title = 'Validation des forums - ISMOShare';
$current_page = basename(__FILE__);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vid = isset($_POST['accept_id']) ? (int) $_POST['accept_id'] : (isset($_POST['refuse_id']) ? (int) $_POST['refuse_id'] : 0);
    if ($vid > 0) {
        if (isset($_POST['accept_id'])) {
            $pdo->prepare("UPDATE sujet SET statut_validation = 'accepte' WHERE id_sujet = ?")->execute([$vid]);
            $_SESSION['forum_flash'] = 'Le sujet a été accepté et est visible dans le forum.';
        } elseif (isset($_POST['refuse_id'])) {
            $pdo->prepare("UPDATE sujet SET statut_validation = 'refuse' WHERE id_sujet = ?")->execute([$vid]);
            $_SESSION['forum_flash'] = 'Le sujet a été refusé.';
        }
    }
    header('Location: validation_forum.php');
    exit;
}

$sql = <<<SQL
SELECT s.id_sujet, s.titre, s.contenu, s.categorie, s.date_creation, s.statut_validation,
       u.nom, u.prenom, u.role AS role_auteur
FROM sujet s
JOIN utilisateur u ON s.id_utilisateur = u.id_utilisateur
WHERE s.statut_validation = 'en_attente'
ORDER BY s.date_creation DESC, s.id_sujet DESC
SQL;

$schema_error = false;
try {
    $pending = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pending = [];
    $schema_error = true;
}

$flash = $_SESSION['forum_flash'] ?? '';
unset($_SESSION['forum_flash']);

$extra_head = <<<'HTML'
<style>
.validation-forum-page .ismo-panel { padding: 1.25rem; }
.validation-forum-page .pending-card {
  border: 1px solid rgba(15, 118, 110, 0.15);
  border-radius: 12px;
  padding: 1rem 1.25rem;
  margin-bottom: 1rem;
  background: #fff;
}
.validation-forum-page .pending-meta { font-size: 0.88rem; color: #64748b; }
</style>
HTML;

include __DIR__ . '/includes/layout_head.php';
include __DIR__ . '/includes/app_nav.php';
?>

<div class="container py-4 validation-forum-page ismo-main">
    <h1 class="ismo-page-title">Validation des sujets forum</h1>
    <p class="ismo-page-sub">Acceptez ou refusez les nouveaux sujets avant qu’ils n’apparaissent dans la liste publique du forum.</p>

    <?php if (!empty($flash)) : ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <?php if (!empty($schema_error)) : ?>
        <div class="alert alert-danger">
            La colonne <code>statut_validation</code> est absente. Exécutez le script SQL :
            <code>database/migration_forum_validation.sql</code>
        </div>
    <?php elseif (empty($pending)) : ?>
        <div class="ismo-panel">
            <p class="mb-0 text-muted">Aucun sujet en attente de validation.</p>
        </div>
    <?php else : ?>
        <?php foreach ($pending as $row) : ?>
            <div class="pending-card">
                <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start mb-2">
                    <strong class="fs-5"><?= htmlspecialchars($row['titre']) ?></strong>
                    <span class="badge bg-warning text-dark">En attente</span>
                </div>
                <p class="pending-meta mb-2">
                    Par <?= htmlspecialchars($row['prenom'] . ' ' . $row['nom']) ?>
                    (<?= htmlspecialchars($row['role_auteur']) ?>)
                    · <?= htmlspecialchars($row['categorie']) ?>
                    · <?= htmlspecialchars((string) $row['date_creation']) ?>
                </p>
                <?php
                $ctx = (string) $row['contenu'];
                $lim = 400;
                if (function_exists('mb_substr')) {
                    $short = (mb_strlen($ctx) > $lim) ? (mb_substr($ctx, 0, $lim) . '…') : $ctx;
                } else {
                    $short = (strlen($ctx) > $lim) ? (substr($ctx, 0, $lim) . '…') : $ctx;
                }
                ?>
                <p class="mb-3"><?= nl2br(htmlspecialchars($short)) ?></p>
                <div class="d-flex flex-wrap gap-2">
                    <form method="post" class="d-inline">
                        <input type="hidden" name="accept_id" value="<?= (int) $row['id_sujet'] ?>">
                        <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Accepter</button>
                    </form>
                    <form method="post" class="d-inline" onsubmit="return confirm('Refuser ce sujet ?');">
                        <input type="hidden" name="refuse_id" value="<?= (int) $row['id_sujet'] ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-times"></i> Refuser</button>
                    </form>
                    <a href="voir_sujet.php?id=<?= (int) $row['id_sujet'] ?>" class="btn btn-outline-secondary btn-sm">Voir</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <p class="mt-4 mb-0"><a href="forum.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left"></i> Retour au forum</a></p>
</div>

<?php include __DIR__ . '/includes/layout_foot.php'; ?>
