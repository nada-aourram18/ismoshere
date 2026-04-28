<?php
// Connexion à la base de données
session_start();
$roles = ['admin', 'formateur', 'stagiaire'];
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || 
   !in_array($_SESSION['user_role'], $roles)) {
    header("Location: login.php?msg=Accès non autorisé");
    exit;
}
$role = $_SESSION['user_role'] ?? '';
$user_name = $_SESSION['user_name'] ?? 'Utilisateur';
$current_page = basename($_SERVER['PHP_SELF']);
include 'db.php';

try {
    // Requête pour récupérer tous les champs des utilisateurs en attente
    $stmt = $pdo->query("SELECT * FROM utilisateur WHERE statut = 'en attente'");
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les noms des colonnes pour l'affichage dynamique
    $colonnes = [];
    if (count($utilisateurs) > 0) {
        $colonnes = array_keys($utilisateurs[0]);
    }

    // Exclure les colonnes non désirées
    $colonnes_a_exclure = ['mot_de_passe', 'id_utilisateur', 'id_filiere'];
    $colonnes = array_filter($colonnes, fn($col) => !in_array($col, $colonnes_a_exclure));
    
    // Traitement des actions
    if(isset($_POST['action'], $_POST['id'])) {
        $id = (int)$_POST['id'];
        $action = $_POST['action'];
        if ($action === 'valider') {
            $stmt = $pdo->prepare("UPDATE utilisateur SET statut = 'valide' WHERE id_utilisateur = ?");
            $stmt->execute([$id]);
        } elseif ($action === 'rejeter') {
            $stmt = $pdo->prepare("UPDATE utilisateur SET statut = 'rejete' WHERE id_utilisateur = ?");
            $stmt->execute([$id]);
        }
        // Rafraîchir la page après action
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
$page_title = 'Validation des demandes - ISMOShare';
include __DIR__ . '/includes/layout_head.php';
include __DIR__ . '/includes/app_nav.php';
?>

<div class="container main-content validation-page">
    <div class="header">
        <h1 class="logo-text">Validation des demandes <small class="text-muted">- Utilisateurs en attente</small></h1>
    </div>

    <div class="table-container ismo-table-wrap">
        <?php if (count($utilisateurs) > 0): ?>
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <?php foreach ($colonnes as $colonne): ?>
                        <th><?= ucfirst(str_replace('_', ' ', $colonne)) ?></th>
                    <?php endforeach; ?>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($utilisateurs as $user): ?>
                    <tr>
                        <?php foreach ($colonnes as $colonne): ?>
                            <td data-label="<?= ucfirst(str_replace('_', ' ', $colonne)) ?>">
                                <?php if ($colonne === 'photo_profil' && !empty($user[$colonne])): ?>
                                    <img src="images/<?= htmlspecialchars($user[$colonne]) ?>" alt="Photo profil" width="40" class="rounded-circle">
                                <?php elseif ($colonne === 'prenom' || $colonne === 'nom'): ?>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3">
                                            <?= strtoupper(substr($user['prenom'], 0, 1)) . strtoupper(substr($user['nom'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <?= htmlspecialchars($user[$colonne]) ?>
                                            <div class="field-label"><?= ucfirst($colonne) ?></div>
                                        </div>
                                    </div>
                                <?php elseif ($colonne === 'statut'): ?>
                                    <span class="badge badge-pending rounded-pill">
                                        <i class="fas fa-hourglass-half me-1"></i>
                                        <?= htmlspecialchars($user[$colonne]) ?>
                                    </span>
                                <?php elseif ($colonne === 'date_inscription'): ?>
                                    <?= date('d/m/Y H:i', strtotime($user[$colonne])) ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($user[$colonne]) ?>
                                    <div class="field-label"><?= ucfirst(str_replace('_', ' ', $colonne)) ?></div>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td data-label="Actions">
                            <div class="btn-group">
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="id" value="<?= $user['id_utilisateur'] ?>">
                                    <button name="action" value="valider" class="btn btn-sm btn-validate me-1" title="Valider">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="id" value="<?= $user['id_utilisateur'] ?>">
                                    <button name="action" value="rejeter" class="btn btn-sm btn-reject me-1" title="Rejeter">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                                <a href="user_details.php?id=<?= $user['id_utilisateur'] ?>" class="btn btn-sm btn-view" title="Voir détails">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <h4>Aucune demande en attente</h4>
                <p>Toutes les demandes ont été traitées</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$extra_scripts = <<<'JS'
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function handleResize() {
            const isMobile = window.innerWidth < 992;
            document.querySelectorAll('.field-label').forEach(label => {
                label.style.display = isMobile ? 'block' : 'none';
            });
        }
        handleResize();
        window.addEventListener('resize', handleResize);
    });
</script>
JS;
include __DIR__ . '/includes/layout_foot.php';