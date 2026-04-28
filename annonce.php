<?php
session_start();
include 'db.php';

// Activation de la vérification du rôle
$roles = ['admin', 'formateur', 'stagiaire'];
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles)) {
    header("Location: login.php?msg=Accès non autorisé");
    exit;
}

$role = $_SESSION['user_role']; // Définition de la variable $role

$stmt = $pdo->prepare("SELECT * FROM annonce ORDER BY date_publication DESC");
$stmt->execute();
$annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Annonces - ISMOShare';
$current_page = basename(__FILE__);
$extra_head = <<<'HTML'
<style>
        .annonce-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: 12px;
            transition: 0.2s;
        }
        .annonce-card:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }
        .annonce-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        .badge-active {
            background-color: #0f766e;
            color: #fff;
            font-size: 0.85rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .badge-inactive {
            background-color: #adb5bd;
            color: #fff;
            font-size: 0.85rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .annonce-title {
            color: #212529;
            font-size: 1.2rem;
            font-weight: 600;
            text-decoration: none;
        }
        .annonce-title:hover {
            text-decoration: underline;
            color: #0d6efd;
        }
        .annonce-date {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .annonce-content {
            font-size: 0.95rem;
            color: #333;
            margin-bottom: 10px;
        }
        .action-buttons {
            margin-top: auto;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .view-count i {
            color: #6c757d;
            margin-right: 5px;
        }
    </style>
HTML;
include __DIR__ . '/includes/layout_head.php';
include __DIR__ . '/includes/app_nav.php';
?>

<!-- CONTENU DES ANNONCES -->
<div class="container mt-4 ismo-main">
    <h2 class="mb-4">Liste des Annonces</h2>
    <div class="row">
        <?php foreach ($annonces as $annonce): ?>
            <div class="col-md-6 col-lg-4 d-flex">
                <div class="annonce-card">
                    <a href="annonce.php?id=<?= $annonce['id_annonce'] ?>">
                        <img src="<?= htmlspecialchars($annonce['image'] ?: 'https://source.unsplash.com/random/600x400/?' . urlencode($annonce['titre'])) ?>"
                             alt="<?= htmlspecialchars($annonce['titre']) ?>" class="annonce-img">
                    </a>
                    <div class="annonce-body">
                        <span class="<?= ($annonce['statut'] === 'Actif') ? 'badge-active' : 'badge-inactive' ?> mb-2">
                            <?= htmlspecialchars($annonce['statut'] === 'Actif' ? 'Active' : 'Terminée') ?>
                        </span>
                        <h3 class="annonce-title">
                            <a href="annonce.php?id=<?= $annonce['id_annonce'] ?>" class="annonce-title">
                                <?= htmlspecialchars($annonce['titre']) ?>
                            </a>
                        </h3>
                        <p class="annonce-date">
                            <i class="far fa-clock me-1"></i>Publié le <?= date('d/m/Y', strtotime($annonce['date_publication'])) ?>
                        </p>
                        <p class="annonce-content"><?= nl2br(htmlspecialchars($annonce['contenu'])) ?></p>
                        <div class="action-buttons">
                            <span class="view-count"><i class="far fa-eye me-1"></i><?= $annonce['views'] ?? 0 ?> vues</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/layout_foot.php';