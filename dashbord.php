<?php
session_start();
include 'db.php';

// Vérification du rôle
$roles = ['admin', 'formateur', 'stagiaire'];
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles)) {
    header("Location: login.php?msg=Accès non autorisé");
    exit;
}

$role = $_SESSION['user_role'];

// Récupération des statistiques
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM utilisateur")->fetchColumn(),
    'resources' => $pdo->query("SELECT COUNT(*) FROM ressource")->fetchColumn(),
    'validated_resources' => $pdo->query("SELECT COUNT(*) FROM ressource WHERE statut = 'validée'")->fetchColumn(),
    'pending_resources' => $pdo->query("SELECT COUNT(*) FROM ressource WHERE statut = 'en_attente'")->fetchColumn(),
    'filieres' => $pdo->query("SELECT COUNT(*) FROM filiere")->fetchColumn()
];

// Récupération des dernières ressources
$latestResources = $pdo->query("
    SELECT r.*, CONCAT(u.nom, ' ', u.prenom) AS auteur 
    FROM ressource r 
    JOIN utilisateur u ON r.id_utilisateur = u.id_utilisateur 
    ORDER BY date_upload DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Récupération des utilisateurs récents
$latestUsers = $pdo->query("
    SELECT u.*, f.nom_filiere 
    FROM utilisateur u 
    LEFT JOIN filiere f ON u.id_filier = f.id_filier 
    ORDER BY date_inscription DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Récupération des données pour les graphiques
$activityData = $pdo->query("
    SELECT 
        DATE_FORMAT(date_upload, '%Y-%m-%d') AS day, 
        COUNT(*) AS uploads 
    FROM ressource 
    WHERE date_upload >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
    GROUP BY day 
    ORDER BY day
")->fetchAll(PDO::FETCH_ASSOC);

$userDistribution = $pdo->query("
    SELECT 
        role, 
        COUNT(*) AS count 
    FROM utilisateur 
    GROUP BY role
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Tableau de bord - ISMOShare';
$current_page = basename(__FILE__);
$extra_head = <<<'HTML'
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
        .dash-page .card {
            border-radius: 12px;
            border-top: 3px solid #0f766e;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            height: 100%;
        }
        .dash-page .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        .dash-page .stat-card { text-align: center; padding: 20px; }
        .dash-page .stat-icon {
            width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;
            border-radius: 50%; margin: 0 auto 15px; font-size: 1.5rem;
            background-color: rgba(15, 118, 110, 0.12); color: #0f766e;
        }
        .dash-page .recent-activity-item {
            border-left: 3px solid rgba(15, 118, 110, 0.45);
            padding-left: 15px; margin-bottom: 15px;
        }
        .dash-page .activity-badge {
            width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;
            border-radius: 50%; margin-right: 10px;
        }
        .dash-page .badge-en_attente { background-color: #ffc107; color: #000; }
        .dash-page .badge-validee { background-color: #198754; color: #fff; }
        .dash-page .badge-rejetee { background-color: #dc3545; color: #fff; }
</style>
HTML;
include __DIR__ . '/includes/layout_head.php';
include __DIR__ . '/includes/app_nav.php';
?>

    <!-- Contenu principal -->
    <div class="container py-4 dash-page ismo-main">
        <!-- En-tête -->
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="fas fa-tachometer-alt me-2"></i>Tableau de Bord</h2>
                <p class="text-muted">Aperçu global de votre plateforme</p>
            </div>
        </div>

        <!-- Cartes de statistiques -->
        <div class="row mb-4">
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="mb-2"><?= $stats['users'] ?></h3>
                        <p class="text-muted mb-0">Utilisateurs</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h3 class="mb-2"><?= $stats['resources'] ?></h3>
                        <p class="text-muted mb-0">Ressources</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3 class="mb-2"><?= $stats['validated_resources'] ?></h3>
                        <p class="text-muted mb-0">Ressources validées</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="mb-2"><?= $stats['pending_resources'] ?></h3>
                        <p class="text-muted mb-0">En attente</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphiques et activité récente -->
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Activité des ressources (30 derniers jours)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="activityChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Répartition des utilisateurs</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="usersChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dernières activités et ressources -->
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Derniers utilisateurs</h5>
                        <a href="users.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
                    </div>
                    <div class="card-body">
                        <?php foreach ($latestUsers as $user): ?>
                            <div class="recent-activity-item">
                                <div class="d-flex">
                                    <div class="activity-badge bg-primary text-white">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h6>
                                        <p class="text-muted mb-1"><?= htmlspecialchars($user['role']) ?> - <?= htmlspecialchars($user['nom_filiere'] ?? 'Non attribué') ?></p>
                                        <small class="text-muted"><i class="fas fa-clock me-1"></i><?= date('d/m/Y', strtotime($user['date_inscription'])) ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Dernières ressources</h5>
                        <a href="resources.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Type</th>
                                        <th>Statut</th>
                                        <th>Auteur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($latestResources as $resource): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($resource['titre']) ?></td>
                                            <td><?= htmlspecialchars($resource['type']) ?></td>
                                            <td>
                                                <?php 
                                                $badgeClass = '';
                                                if ($resource['statut'] == 'validée') {
                                                    $badgeClass = 'badge-validee';
                                                } elseif ($resource['statut'] == 'en_attente') {
                                                    $badgeClass = 'badge-en_attente';
                                                } else {
                                                    $badgeClass = 'badge-rejetee';
                                                }
                                                ?>
                                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($resource['statut']) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($resource['auteur']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php ob_start(); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const activityData = <?= json_encode($activityData) ?>;
            const activityLabels = activityData.map(item => item.day);
            const activityValues = activityData.map(item => item.uploads);
            const activityCtx = document.getElementById('activityChart').getContext('2d');
            new Chart(activityCtx, {
                type: 'line',
                data: {
                    labels: activityLabels,
                    datasets: [{
                        label: 'Ressources ajoutées',
                        data: activityValues,
                        borderColor: '#0f766e',
                        backgroundColor: 'rgba(15, 118, 110, 0.12)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'top' } },
                    scales: { y: { beginAtZero: true } }
                }
            });
            const userDistribution = <?= json_encode($userDistribution) ?>;
            const userLabels = userDistribution.map(item => item.role);
            const userValues = userDistribution.map(item => item.count);
            const usersCtx = document.getElementById('usersChart').getContext('2d');
            new Chart(usersCtx, {
                type: 'doughnut',
                data: {
                    labels: userLabels,
                    datasets: [{
                        data: userValues,
                        backgroundColor: [
                            'rgba(15, 118, 110, 0.75)',
                            'rgba(13, 110, 253, 0.7)',
                            'rgba(220, 53, 69, 0.7)',
                            'rgba(255, 193, 7, 0.7)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        });
    </script>
<?php
$extra_scripts = ob_get_clean();
include __DIR__ . '/includes/layout_foot.php';