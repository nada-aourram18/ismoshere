<?php
session_start();
include("db.php");

// Vérification si l'utilisateur est connecté et est un admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php?msg=Accès refusé.");
    exit;
}
$role = $_SESSION['user_role'] ?? '';
$user_name = $_SESSION['user_name'] ?? 'Utilisateur';

// Traitement de la validation/rejet des ressources
if (isset($_POST['action'], $_POST['id_ressource'])) {
    $id = (int)$_POST['id_ressource'];
    $action = $_POST['action'];

    $nouveauStatut = ($action === 'valider') ? 'validée' : 'rejetée';

    $stmtUpdate = $pdo->prepare("UPDATE ressource SET statut = ? WHERE id_ressource = ?");
    $stmtUpdate->execute([$nouveauStatut, $id]);
}

// Récupération des ressources en attente de validation avec l'ID utilisateur
$stmt = $pdo->query("
    SELECT 
        r.id_ressource, 
        r.titre, 
        r.description, 
        r.fichier, 
        r.type, 
        r.filiere, 
        r.module, 
        r.date_upload, 
        r.statut,
        r.id_utilisateur,
        u.nom, 
        u.prenom
    FROM ressource r
    JOIN utilisateur u ON r.id_utilisateur = u.id_utilisateur
    WHERE r.statut = 'en_attente'
    ORDER BY r.date_upload DESC
");
$ressources = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$valide = $pdo->query("SELECT COUNT(*) FROM ressource WHERE statut='validée'")->fetchColumn();
$attente = $pdo->query("SELECT COUNT(*) FROM ressource WHERE statut='en_attente'")->fetchColumn();
$rejete = $pdo->query("SELECT COUNT(*) FROM ressource WHERE statut='rejetée'")->fetchColumn();
$page_title = 'Validation des ressources - ISMOShare';
$current_page = basename(__FILE__);
$extra_head = <<<'HTML'
<style>
        :root { --teal-iso: #0f766e; --waiting: #fbbf24; --rejected: #ef4444; --bg-page: #f7f9fc; }
        .val-res-page { background: var(--bg-page); }
        .val-res-page .text-teal { color: var(--teal-iso) !important; }
        .val-res-page .card-table .card-header { background: white; font-weight: 600; }
        .val-res-page .badge-wait { background: rgba(251,191,36,.2); color: var(--waiting); }
        .val-res-page .badge-valid { background: rgba(34,197,94,.2); color: #22c55e; }
        .val-res-page .badge-refus { background: rgba(239,68,68,.2); color: var(--rejected); }
        .val-res-page .btn-circle { width: 32px; height: 32px; padding: 0; border-radius: 50% !important; display: inline-flex; align-items: center; justify-content: center; }
        .val-res-page table td, .val-res-page table th { vertical-align: middle; }
        .val-res-page .file-icon { font-size: 1.5rem; color: var(--teal-iso); }
        .val-res-page .badge-type { padding: 0.35em 0.65em; font-weight: 600; }
        .val-res-page .badge-cours { background-color: #3b82f6; color: white; }
        .val-res-page .badge-tp { background-color: #6b7280; color: white; }
        .val-res-page .badge-exam { background-color: #ef4444; color: white; }
        .val-res-page .user-id-badge { background-color: #e2e8f0; color: #4a5568; font-size: 0.8rem; padding: 0.25em 0.5em; border-radius: 10px; }
        .val-res-page footer { background-color: var(--teal-iso); color: white; padding: 1.5rem 0; margin-top: 2rem; }
</style>
HTML;
include __DIR__ . '/includes/layout_head.php';
include __DIR__ . '/includes/app_nav.php';
?>

<div class="container py-4 val-res-page ismo-main">
    <header class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="text-teal m-0">
            <i class="fa-solid fa-file-circle-check me-2"></i>Validation des ressources
        </h1>
    </header>

    <div class="card card-table shadow-sm border-0">
        <?php if ($ressources): ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="text-nowrap">
                <tr>
                    <th class="bg-light">Ressource</th>
                    <th>Type</th>
                    <th>Filière/Module</th>
                    <th>Utilisateur</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($ressources as $r): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="file-icon me-3">
                                    <?php 
                                    $icon = match($r['type']) {
                                        'cours' => 'fa-book',
                                        'tp' => 'fa-laptop-code',
                                        'exam' => 'fa-file-circle-question',
                                        default => 'fa-file'
                                    };
                                    ?>
                                    <i class="fas <?= $icon ?>"></i>
                                </div>
                                <div>
                                    <strong><?= htmlspecialchars($r['titre']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars(substr($r['description'], 0, 50)) ?>...</small>
                                </div>
                            </div>
                        </td>
                        
                        <td>
                            <?php 
                            $badgeClass = match($r['type']) {
                                'cours' => 'badge-cours',
                                'tp' => 'badge-tp',
                                'exam' => 'badge-exam',
                                default => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?= $badgeClass ?> badge-type">
                                <?= htmlspecialchars($r['type']) ?>
                            </span>
                        </td>
                        
                        <td>
                            <small class="d-block"><?= htmlspecialchars($r['filiere']) ?></small>
                            <small class="text-muted"><?= htmlspecialchars($r['module']) ?></small>
                        </td>
                        
                        <td><?= htmlspecialchars($r['prenom'] . ' ' . htmlspecialchars($r['nom']) )?></td>
                        <td><?= date('d/m/Y', strtotime($r['date_upload'])) ?></td>
                        
                        <td>
                            <span class="badge badge-wait d-inline-flex align-items-center gap-1 px-2 py-1">
                                <i class="fa-solid fa-hourglass-half small"></i> En attente
                            </span>
                        </td>
                        
                        <td class="text-nowrap">
                            <div class="d-flex gap-2">
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="id_ressource" value="<?= $r['id_ressource'] ?>">
                                    <button class="btn btn-success btn-circle" name="action" value="valider"
                                           onclick="return confirm('Valider cette ressource ?');">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>
                                
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="id_ressource" value="<?= $r['id_ressource'] ?>">
                                    <button class="btn btn-danger btn-circle" name="action" value="rejeter"
                                           onclick="return confirm('Rejeter cette ressource ?');">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </form>
                                
                                <a href="fichiers/<?= htmlspecialchars($r['fichier']) ?>" 
                                   class="btn btn-teal btn-circle" 
                                   download
                                   title="Télécharger">
                                    <i class="fa-solid fa-download"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="card-body text-center text-muted py-5">
                Aucune ressource en attente de validation.
            </div>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header fw-semibold">
            <i class="fa-solid fa-chart-column me-2"></i>Statistiques des validations
        </div>
        <div class="card-body py-4">
            <div class="d-flex justify-content-around text-center">
                <div>
                    <div class="fs-3 fw-bold text-success"><?= $valide ?></div>
                    <small class="text-muted">Validées</small>
                </div>
                <div>
                    <div class="fs-3 fw-bold" style="color:var(--waiting);"><?= $attente ?></div>
                    <small class="text-muted">En attente</small>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-danger"><?= $rejete ?></div>
                    <small class="text-muted">Rejetées</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/layout_foot.php';