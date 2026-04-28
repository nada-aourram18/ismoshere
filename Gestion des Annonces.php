<?php
session_start();
include('db.php');

$roles_ok = ['admin', 'formateur', 'stagiaire'];
if (!isset($_SESSION['user_id'], $_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles_ok, true)) {
    header('Location: login.php?msg=' . rawurlencode('Connexion requise'));
    exit;
}

$role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Utilisateur';

// Vérification que l'utilisateur existe dans la base
try {
    $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE id_utilisateur = ?");
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        header("Location: login.php?msg=Utilisateur non trouvé");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur de vérification utilisateur: " . $e->getMessage();
    header("Location: login.php");
    exit;
}

// Vérification du rôle
if (!in_array($role, ['admin', 'formateur', 'stagiaire'])) {
    header("Location: acceuil2.php?msg=Accès non autorisé");
    exit;
}

// Vérification si l'utilisateur a des annonces (pour formateurs/admins)
$user_has_announcement = false;
if ($role === 'formateur' || $role === 'admin') {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM annonce WHERE id_utilisateur = ?");
        $stmt->execute([$user_id]);
        $user_has_announcement = $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur de vérification des annonces: " . $e->getMessage();
    }
}

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Ajout d'annonce
    if ($_POST['action'] === 'add' && ($role === 'formateur' || $role === 'admin')) {
        $titre = filter_input(INPUT_POST, 'titre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $contenu = filter_input(INPUT_POST, 'contenu', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $statut = in_array($_POST['statut'], ['Actif', 'Inactif']) ? $_POST['statut'] : 'Actif';
        $date_publication = !empty($_POST['date_publication']) ? $_POST['date_publication'] : date('Y-m-d');

        if (empty($titre) || empty($contenu)) {
            $_SESSION['error_message'] = "Veuillez remplir tous les champs obligatoires.";
        } else {
            $imagePath = null;
            // Traitement de l'image
            if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file_type = mime_content_type($_FILES['image']['tmp_name']);

                if (in_array($file_type, $allowed_types)) {
                    $targetDir = 'uploads/annonces/';
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }

                    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $new_filename = uniqid('annonce_', true) . '.' . $extension;
                    $targetPath = $targetDir . $new_filename;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                        $imagePath = $targetPath;
                    } else {
                        $_SESSION['error_message'] = "Erreur lors de l'upload de l'image.";
                    }
                } else {
                    $_SESSION['error_message'] = "Type de fichier non autorisé.";
                }
            }

            if (!isset($_SESSION['error_message'])) {
                try {
                    $pdo->beginTransaction();
                    
                    $stmt = $pdo->prepare("INSERT INTO annonce (titre, contenu, statut, date_publication, image, id_utilisateur) 
                                          VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$titre, $contenu, $statut, $date_publication, $imagePath, $user_id]);
                    
                    $pdo->commit();
                    $_SESSION['success_message'] = "Annonce ajoutée avec succès.";
                    header("Location: annonce.php");
                    exit;
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $_SESSION['error_message'] = "Erreur lors de l'ajout de l'annonce: " . $e->getMessage();
                }
            }
        }
    }

    // Suppression d'annonce
    if ($_POST['action'] === 'delete' && isset($_POST['annonce_id'])) {
        $annonce_id = intval($_POST['annonce_id']);
        
        try {
            $pdo->beginTransaction();
            
            // Vérification que l'annonce appartient à l'utilisateur (sauf admin)
            if ($role !== 'admin') {
                $stmt = $pdo->prepare("SELECT id_utilisateur FROM annonce WHERE id_annonce = ?");
                $stmt->execute([$annonce_id]);
                $owner_id = $stmt->fetchColumn();
                
                if ($owner_id != $user_id) {
                    $_SESSION['error_message'] = "Vous n'avez pas la permission de supprimer cette annonce.";
                    header("Location: annonce.php");
                    exit;
                }
            }
            
            $stmt = $pdo->prepare("DELETE FROM annonce WHERE id_annonce = ?");
            $stmt->execute([$annonce_id]);
            $pdo->commit();
            $_SESSION['success_message'] = "Annonce supprimée avec succès.";
            header("Location: annonce.php");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Erreur lors de la suppression: " . $e->getMessage();
        }
    }
}

// Récupération des annonces
try {
    $query = "SELECT a.*, u.nom, u.prenom FROM annonce a 
              JOIN utilisateur u ON a.id_utilisateur = u.id_utilisateur 
              ORDER BY a.date_publication DESC";
    $annonces = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $annonces = [];
    $_SESSION['error_message'] = "Erreur lors de la récupération des annonces: " . $e->getMessage();
}
$page_title = 'Gestion des annonces - ISMOShare';
$current_page = basename(__FILE__);
$extra_head = <<<'HTML'
<style>
        /* Même palette que le reste du site : plus de bleu Bootstrap sur cette page */
        .gestion-annonces-page {
            --bs-primary: #0f766e;
            --bs-primary-rgb: 15, 118, 110;
            --bs-link-color: #0d5d56;
            --bs-link-hover-color: #0a4a45;
        }
        .gestion-annonces-page .card-header.bg-primary {
            background: linear-gradient(105deg, #0d5d56 0%, #0f766e 45%, #0d9488 100%) !important;
            border: none;
        }
        .gestion-annonces-page .img-thumbnail { max-width: 150px; height: auto; }
        .gestion-annonces-page .badge { font-size: 0.9em; }
        .gestion-annonces-page .alert-info {
            background: rgba(15, 118, 110, 0.08);
            border-color: rgba(15, 118, 110, 0.22);
            color: #0d5d56;
        }
</style>
HTML;
include __DIR__ . '/includes/layout_head.php';
include __DIR__ . '/includes/app_nav.php';
?>

<div class="container py-4 gestion-annonces-page ismo-main">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="ismo-page-title mb-0">Gestion des Annonces</h2>
        <div>
            <a href="annonce.php" class="btn btn-outline-primary me-2">
                <i class="bi bi-eye"></i> Voir les annonces publiques
            </a>
            <a href="#listeAnnonces" class="btn btn-outline-secondary">
                <i class="bi bi-list-ul"></i> Voir la liste
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <?php if ($role === 'formateur' || $role === 'admin'): ?>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Ajouter une annonce</h5>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="titre" class="form-label">Titre <span class="text-danger">*</span></label>
                        <input type="text" id="titre" name="titre" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
                        <select id="statut" name="statut" class="form-select" required>
                            <option value="Actif">Actif</option>
                            <option value="Inactif">Inactif</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="contenu" class="form-label">Contenu <span class="text-danger">*</span></label>
                    <textarea id="contenu" name="contenu" class="form-control" rows="5" required></textarea>
                </div>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="date_publication" class="form-label">Date de publication</label>
                        <input type="date" id="date_publication" name="date_publication" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="image" class="form-label">Image</label>
                        <input type="file" id="image" name="image" class="form-control" accept="image/*">
                        <div class="form-text">Taille max: 2MB. Formats: JPEG, PNG, GIF, WebP</div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Publier l'annonce
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0" id="listeAnnonces"><i class="bi bi-list-ul"></i> Liste des Annonces</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($annonces)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Date</th>
                                <th>Contenu</th>
                                <th>Statut</th>
                                <th>Image</th>
                                <th>Auteur</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($annonces as $annonce): ?>
                                <tr>
                                    <td><?= htmlspecialchars($annonce['titre']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($annonce['date_publication'])) ?></td>
                                    <td><?= nl2br(htmlspecialchars($annonce['contenu'])) ?></td>
                                    <td>
                                        <span class="badge rounded-pill bg-<?= $annonce['statut'] === 'Actif' ? 'success' : 'secondary' ?>">
                                            <?= htmlspecialchars($annonce['statut']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($annonce['image']) && file_exists($annonce['image'])): ?>
                                            <img src="<?= htmlspecialchars($annonce['image']) ?>" alt="Image annonce" class="img-thumbnail">
                                        <?php else: ?>
                                            <span class="text-muted">Aucune image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($annonce['prenom'] . ' ' . $annonce['nom']) ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="modifier_annonce.php?id=<?= $annonce['id_annonce'] ?>" class="btn btn-sm btn-outline-primary" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            
                                            <?php if ($annonce['id_utilisateur'] == $user_id || $role === 'admin'): ?>
                                            <form method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette annonce ?');">
                                                <input type="hidden" name="annonce_id" value="<?= $annonce['id_annonce'] ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Aucune annonce disponible.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/layout_foot.php';