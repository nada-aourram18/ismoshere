<?php
session_start();

// Configuration
define('UPLOAD_DIR', './fichiers/');
define('MAX_FILE_SIZE', 40 * 1024 * 1024); // 40 Mo
define('ALLOWED_MIME_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/zip',
    'application/x-zip-compressed'
]);
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'zip']);

// Vérification de rôle
$allowed_roles = ['admin', 'formateur', 'stagiaire'];
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || 
   !in_array($_SESSION['user_role'], $allowed_roles)) {
    header("Location: login.php?msg=Accès non autorisé");
    exit;
}
$role = $_SESSION['user_role'] ?? '';
$user_name = $_SESSION['user_name'] ?? 'Utilisateur';
$current_page = basename($_SERVER['PHP_SELF']);

include("db.php");

// Fonctions utilitaires
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' Mo';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' Ko';
    }
    return $bytes . ' octets';
}

function validateFileUpload($file) {
    $errors = [];
    
    // Vérification erreur upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Erreur lors de l'upload du fichier";
        return $errors;
    }
    
    // Vérification taille
    if ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = "Le fichier est trop volumineux (max " . formatFileSize(MAX_FILE_SIZE) . ")";
    }
    
    // Vérification type MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    
    if (!in_array($mime, ALLOWED_MIME_TYPES)) {
        $errors[] = "Type de fichier non autorisé";
    }
    
    // Vérification extension
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (!in_array(strtolower($ext), ALLOWED_EXTENSIONS)) {
        $errors[] = "Extension de fichier non autorisée";
    }
    
    return $errors;
}

// Gestion des actions
$errors = [];
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token CSRF invalide";
    } else {
        // Traitement des différentes actions
        if (isset($_POST['supprimer']) ){
            handleDeleteResource($pdo, $_POST['id_ressource'], $_SESSION['user_id'], $_SESSION['user_role']);
        } elseif (isset($_POST['id_ressource'])) {
            handleUpdateResource($pdo);
        } else {
            handleCreateResource($pdo);
        }
    }
}

function handleDeleteResource($pdo, $resourceId, $userId, $userRole) {
    global $errors, $success_msg;
    
    // Vérifier que l'utilisateur a le droit de supprimer
    $check = $pdo->prepare("SELECT id_utilisateur FROM ressource WHERE id_ressource=?");
    $check->execute([$resourceId]);
    $owner_id = $check->fetchColumn();
    
    if ($userRole === 'admin' || $userId == $owner_id) {
        $s = $pdo->prepare("SELECT fichier FROM ressource WHERE id_ressource=?");
        $s->execute([$resourceId]);
        $m = $s->fetch();
        
        if ($m) {
            if (!empty($m['fichier']) && file_exists(UPLOAD_DIR . $m['fichier'])) {
                unlink(UPLOAD_DIR . $m['fichier']);
            }
            
            $d = $pdo->prepare("DELETE FROM ressource WHERE id_ressource=?");
            $d->execute([$resourceId]);
            $success_msg = "Ressource supprimée avec succès";
        } else {
            $errors[] = "Ressource introuvable";
        }
    } else {
        $errors[] = "Vous n'avez pas la permission de supprimer cette ressource";
    }
}

function handleUpdateResource($pdo) {
    global $errors, $success_msg;
    
    $id_ressource = $_POST['id_ressource'];
    $titre = $_POST['titre'] ?? '';
    $description = $_POST['description'] ?? '';
    $filiere = $_POST['filiere'] ?? '';
    $module = $_POST['module'] ?? '';
    $type = $_POST['type'] ?? '';

    // Validation des champs
    if (empty($titre)) $errors[] = "Le titre est requis";
    if (empty($description)) $errors[] = "La description est requise";
    if (empty($filiere)) $errors[] = "La filière est requise";
    if (empty($module)) $errors[] = "Le module est requis";
    if (empty($type)) $errors[] = "Le type est requis";

    $file = $_FILES['fichier'] ?? null;
    $update_file = false;
    $nom_fichier = "";

    if ($file && $file['error'] == 0) {
        $fileErrors = validateFileUpload($file);
        if (!empty($fileErrors)) {
            $errors = array_merge($errors, $fileErrors);
        } else {
            $filename = uniqid() . '_' . basename($file['name']);
            $destination = UPLOAD_DIR . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $update_file = true;
                $nom_fichier = $filename;
                
                // Supprimer l'ancien fichier
                $s = $pdo->prepare("SELECT fichier FROM ressource WHERE id_ressource=?");
                $s->execute([$id_ressource]);
                $old_file = $s->fetchColumn();
                
                if ($old_file && file_exists(UPLOAD_DIR . $old_file)) {
                    unlink(UPLOAD_DIR . $old_file);
                }
            } else {
                $errors[] = "Erreur d'enregistrement du fichier";
            }
        }
    }

    if (empty($errors)) {
        try {
            if ($update_file) {
                $req = $pdo->prepare("UPDATE ressource SET titre=?, description=?, type=?, filiere=?, module=?, fichier=?, statut='en_attente' WHERE id_ressource=?");
                $req->execute([sanitize($titre), sanitize($description), sanitize($type), sanitize($filiere), sanitize($module), $nom_fichier, $id_ressource]);
            } else {
                $req = $pdo->prepare("UPDATE ressource SET titre=?, description=?, type=?, filiere=?, module=?, statut='en_attente' WHERE id_ressource=?");
                $req->execute([sanitize($titre), sanitize($description), sanitize($type), sanitize($filiere), sanitize($module), $id_ressource]);
            }
            
            $success_msg = "Ressource mise à jour avec succès et en attente de validation";
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la mise à jour : " . $e->getMessage();
        }
    }
}

// function handleCreateResource($pdo) {
//     global $errors, $success_msg;
    
//     $titre = $_POST['titre'] ?? '';
//     $description = $_POST['description'] ?? '';
//     $filiere = $_POST['filiere'] ?? '';
//     $module = $_POST['module'] ?? '';
//     $type = $_POST['type'] ?? '';

//     // Validation des champs
//     if (empty($titre)) $errors[] = "Le titre est requis";
//     if (empty($description)) $errors[] = "La description est requise";
//     if (empty($filiere)) $errors[] = "La filière est requise";
//     if (empty($module)) $errors[] = "Le module est requis";
//     if (empty($type)) $errors[] = "Le type est requis";

//     // Validation du fichier
//     $fichier = $_FILES['fichier'] ?? null;
//     if (!$fichier || $fichier['error'] != UPLOAD_ERR_OK) {
//         $errors[] = "Veuillez sélectionner un fichier valide";
//     } else {
//         $fileErrors = validateFileUpload($fichier);
//         if (!empty($fileErrors)) {
//             $errors = array_merge($errors, $fileErrors);
//         }
//     }

//     if (empty($errors)) {
//         $filename = uniqid() . '_' . basename($fichier['name']);
//         $destination = UPLOAD_DIR . $filename;
        
       
// // ... (le reste de votre code existant)

function handleCreateResource($pdo) {
    global $errors, $success_msg;
    
    $titre = $_POST['titre'] ?? '';
    $description = $_POST['description'] ?? '';
    $filiere = $_POST['filiere'] ?? '';
    $module = $_POST['module'] ?? '';
    $type = $_POST['type'] ?? '';

    // Validation des champs
    if (empty($titre)) $errors[] = "Le titre est requis";
    if (empty($description)) $errors[] = "La description est requise";
    if (empty($filiere)) $errors[] = "La filière est requise";
    if (empty($module)) $errors[] = "Le module est requis";
    if (empty($type)) $errors[] = "Le type est requis";

    // Validation du fichier
    $fichier = $_FILES['fichier'] ?? null;
    if (!$fichier || $fichier['error'] != UPLOAD_ERR_OK) {
        $errors[] = "Veuillez sélectionner un fichier valide";
    } else {
        $fileErrors = validateFileUpload($fichier);
        if (!empty($fileErrors)) {
            $errors = array_merge($errors, $fileErrors);
        }
    }

    if (empty($errors)) {
        // Créer le dossier s'il n'existe pas
        if (!file_exists(UPLOAD_DIR)) {
            if (!mkdir(UPLOAD_DIR, 0755, true)) {
                $errors[] = "Impossible de créer le dossier d'upload";
                return;
            }
        }

        // Vérifier que le dossier est accessible en écriture
        if (!is_writable(UPLOAD_DIR)) {
            if (!chmod(UPLOAD_DIR, 0755)) {
                $errors[] = "Le dossier d'upload n'est pas accessible en écriture";
                return;
            }
        }
    }

        // Générer un nom de fichier unique et sécurisé
        $extension = pathinfo($fichier['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . preg_replace("/[^A-Za-z0-9.]/", '_', $fichier['name']);
        $destination = UPLOAD_DIR . $filename;
        
        // Déplacer le fichier uploadé
        if (move_uploaded_file($fichier['tmp_name'], $destination)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO ressource (titre, description, fichier, type, filiere, module, date_upload, statut, id_utilisateur) 
                                     VALUES (?, ?, ?, ?, ?, ?, NOW(), 'en_attente', ?)");

                $stmt->execute([
                    sanitize($titre),
                    sanitize($description),
                    $filename,
                    sanitize($type),
                    sanitize($filiere),
                    sanitize($module),
                    $_SESSION['user_id']
                ]);

                $success_msg = "Ressource ajoutée avec succès et en attente de validation";
            } catch (PDOException $e) {
                $errors[] = "Erreur d'insertion: " . $e->getMessage();
                // Supprimer le fichier uploadé en cas d'échec
                if (file_exists($destination)) {
                    unlink($destination);
                }
            }
        } else {
            $errors[] = "Erreur lors de l'enregistrement du fichier";
        }
    }
// }
// }
// Récupération des données pour les filtres
$filieres = $pdo->query("SELECT DISTINCT filiere FROM ressource WHERE statut='validée' ORDER BY filiere")->fetchAll(PDO::FETCH_COLUMN, 0);
$modules = $pdo->query("SELECT DISTINCT module FROM ressource WHERE statut='validée' ORDER BY module")->fetchAll(PDO::FETCH_COLUMN, 0);
$annees = $pdo->query("SELECT DISTINCT YEAR(date_upload) as annee FROM ressource WHERE statut='validée' ORDER BY annee DESC")->fetchAll(PDO::FETCH_COLUMN, 0);

// Application des filtres
$where = ["r.statut='validée'"];
$params = [];
$search_term = isset($_GET['recherche']) ? trim($_GET['recherche']) : '';
$filiere_filter = isset($_GET['filiere']) ? $_GET['filiere'] : '';
$module_filter = isset($_GET['module']) ? $_GET['module'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$annee_filter = isset($_GET['annee']) ? $_GET['annee'] : '';

if (!empty($search_term)) {
    $where[] = "(r.titre LIKE ? OR r.type LIKE ? OR r.module LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
}

if (!empty($filiere_filter)) {
    $where[] = "r.filiere = ?";
    $params[] = $filiere_filter;
}

if (!empty($module_filter)) {
    $where[] = "r.module = ?";
    $params[] = $module_filter;
}

if (!empty($type_filter)) {
    $where[] = "r.type = ?";
    $params[] = $type_filter;
}

if (!empty($annee_filter)) {
    $where[] = "YEAR(r.date_upload) = ?";
    $params[] = $annee_filter;
}

// Récupération des ressources filtrées
$sql = "SELECT r.*, u.nom, u.prenom FROM ressource r JOIN utilisateur u ON r.id_utilisateur = u.id_utilisateur";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY r.date_upload DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ressources = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Erreur de recherche : " . $e->getMessage();
    $ressources = [];
}

// Statistiques
$stats = $pdo->query("SELECT 
    SUM(CASE WHEN type='cours' AND statut='validée' THEN 1 ELSE 0 END) as cours,
    SUM(CASE WHEN type='tp' AND statut='validée' THEN 1 ELSE 0 END) as tp,
    SUM(CASE WHEN type='exam' AND statut='validée' THEN 1 ELSE 0 END) as exam
    FROM ressource")->fetch(PDO::FETCH_ASSOC);

// Génération du token CSRF
$csrf_token = generateCSRFToken();

$page_title = 'Ressources pédagogiques - ISMOShare';
$current_page = basename(__FILE__);
$extra_head = <<<'HTML'
<style>
        :root {
            --primary: #0f766e;
            --light-bg: #f8f9fa;
        }
        .res-sources-page .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
        }
        
        .res-sources-page .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .badge-course {
            background-color: var(--primary);
            color: white;
        }
        
        .badge-tp {
            background-color: #6c757d;
            color: white;
        }
        
        .badge-exam {
            background-color: #dc3545;
            color: white;
        }
        
        .filter-section {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }
        
        .document-icon {
            font-size: 2rem;
            color: var(--primary);
        }
        
        .text-primary {
            color: var(--primary) !important;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: #0d645c;
            border-color: #0d645c;
        }
        
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1100;
        }
        
        .file-info {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .resource-card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .resource-card .card-body {
            flex-grow: 1;
        }
    </style>
HTML;
include __DIR__ . '/includes/layout_head.php';
include __DIR__ . '/includes/app_nav.php';
?>

    <div class="container py-5 res-sources-page ismo-main">
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= implode('<br>', $errors) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="alert alert-info mb-4 d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-info-circle me-2"></i>
                Affichage des ressources <strong>validées</strong> uniquement
                <?php if($search_term): ?>
                    | Recherche : "<strong><?= sanitize($search_term) ?></strong>"
                <?php endif; ?>
            </div>
            <span class="badge bg-primary">
                <?= count($ressources) ?> ressource(s) trouvée(s)
            </span>
        </div>

        <div class="row">
            <div class="col-lg-3 mb-4">
                <div class="filter-section">
                    <h5 class="mb-4"><i class="fas fa-filter me-2"></i>Filtres</h5>
                    <form method="GET" action="">
                        <div class="mb-3">
                            <label for="recherche" class="form-label">Recherche</label>
                            <input type="text" id="recherche" name="recherche" class="form-control" 
                                   value="<?= sanitize($search_term) ?>" placeholder="Titre, description...">
                        </div>
                        
                        <div class="mb-3">
                            <label for="filiere" class="form-label">Filière</label>
                            <select id="filiere" name="filiere" class="form-select">
                                <option value="">Toutes les filières</option>
                                <?php foreach($filieres as $f): ?>
                                    <option value="<?= sanitize($f) ?>" <?= $filiere_filter == $f ? 'selected' : '' ?>>
                                        <?= sanitize($f) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="module" class="form-label">Module</label>
                            <select id="module" name="module" class="form-select">
                                <option value="">Tous les modules</option>
                                <?php foreach($modules as $m): ?>
                                    <option value="<?= sanitize($m) ?>" <?= $module_filter == $m ? 'selected' : '' ?>>
                                        <?= sanitize($m) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Type de document</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="type_all" value="" <?= empty($type_filter) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="type_all">Tous les types</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="type_cours" value="cours" <?= $type_filter == 'cours' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="type_cours">Cours</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="type_tp" value="tp" <?= $type_filter == 'tp' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="type_tp">Travaux Pratiques</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="type_exam" value="exam" <?= $type_filter == 'exam' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="type_exam">Examens</label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="annee" class="form-label">Année</label>
                            <select id="annee" name="annee" class="form-select">
                                <option value="">Toutes les années</option>
                                <?php foreach($annees as $a): ?>
                                    <option value="<?= sanitize($a) ?>" <?= $annee_filter == $a ? 'selected' : '' ?>>
                                        <?= sanitize($a) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-check me-2"></i>Appliquer
                            </button>
                            <a href="?" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-book-open me-2"></i>Ressources Pédagogiques</h2>
                    
                    <?php if ($_SESSION['user_role'] !== 'stagiaire'): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="fas fa-upload me-2"></i>Uploader
                    </button>
                    <?php endif; ?>
                </div>
                
                <!-- Statistiques -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="card bg-primary text-white p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Cours</h6>
                                    <h2 class="mb-0"><?= $stats['cours'] ?></h2>
                                </div>
                                <i class="fas fa-book document-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="card bg-secondary text-white p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Travaux Pratiques</h6>
                                    <h2 class="mb-0"><?= $stats['tp'] ?></h2>
                                </div>
                                <i class="fas fa-laptop-code document-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger text-white p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Examens</h6>
                                    <h2 class="mb-0"><?= $stats['exam'] ?></h2>
                                </div>
                                <i class="fas fa-file-alt document-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Liste des ressources -->
                <div class="row" id="documentsContainer">
                    <?php if (empty($ressources)): ?>
                        <div class="col-12">
                            <div class="alert alert-warning text-center py-4">
                                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                <h4>Aucune ressource trouvée</h4>
                                <p class="mb-0">Modifiez vos critères de recherche ou uploader une nouvelle ressource</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach($ressources as $ressource): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card resource-card shadow-sm">
                                <div class="card-header bg-white border-bottom-0 pb-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge <?= 
                                            $ressource['type'] == 'cours' ? 'badge-course' : 
                                            ($ressource['type'] == 'tp' ? 'badge-tp' : 'badge-exam') 
                                        ?>">
                                            <?= sanitize(ucfirst($ressource['type'])) ?>
                                        </span>
                                        <small class="text-muted">
                                            <?= date('d/m/Y', strtotime($ressource['date_upload'])) ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title text-primary mb-2">
                                        <?= sanitize($ressource['titre']) ?>
                                    </h5>
                                    <p class="card-text text-muted mb-3">
                                        <?= sanitize(substr($ressource['description'], 0, 100)) ?>
                                        <?= strlen($ressource['description']) > 100 ? '...' : '' ?>
                                    </p>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex flex-wrap gap-2">
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-graduation-cap me-1"></i>
                                                <?= sanitize($ressource['filiere']) ?>
                                            </span>
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-book me-1"></i>
                                                <?= sanitize($ressource['module']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-auto">
                                        <div class="file-info mb-2">
                                            <i class="fas fa-file me-1"></i>
                                            <?= pathinfo($ressource['fichier'], PATHINFO_EXTENSION) ?> • 
                                            <?= formatFileSize(filesize(UPLOAD_DIR . $ressource['fichier'])) ?>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <a href="fichiers/<?= sanitize($ressource['fichier']) ?>" 
                                               class="btn btn-sm btn-success download-btn"
                                               download
                                               title="Télécharger">
                                                <i class="fas fa-download me-1"></i> Télécharger
                                            </a>
                                               <a href="commentaire.php?id=<?=$ressource['id_ressource']?>" 
                                               class="btn btn-sm btn-success download-btn"
                                               
                                               title="Commantaires">
                                                <!-- <i class="fas fa-download me-1"></i>  -->
                                                Commantaires
                                            </a>
                                            <?php if($_SESSION['user_role'] === 'admin' || $_SESSION['user_id'] == $ressource['id_utilisateur']): ?>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-warning" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editModal<?= $ressource['id_ressource'] ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" 
                                                      onsubmit="return confirm('Voulez-vous vraiment supprimer cette ressource ?');">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                    <input type="hidden" name="id_ressource" 
                                                           value="<?= $ressource['id_ressource'] ?>">
                                                    <button type="submit" name="supprimer" 
                                                            class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modal de modification -->
                        <div class="modal fade" id="editModal<?= $ressource['id_ressource'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form method="POST" enctype="multipart/form-data" class="modal-content">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <input type="hidden" name="id_ressource" value="<?= $ressource['id_ressource'] ?>">

                                    <div class="modal-header">
                                        <h5 class="modal-title">Modifier Ressource</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Titre</label>
                                            <input type="text" name="titre" class="form-control" 
                                                   value="<?= sanitize($ressource['titre']) ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" rows="3" required><?= sanitize($ressource['description']) ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Type</label>
                                            <select name="type" class="form-select" required>
                                                <option value="cours" <?= $ressource['type'] == 'cours' ? 'selected' : '' ?>>Cours</option>
                                                <option value="tp" <?= $ressource['type'] == 'tp' ? 'selected' : '' ?>>Travaux Pratiques</option>
                                                <option value="exam" <?= $ressource['type'] == 'exam' ? 'selected' : '' ?>>Examen</option>
                                            </select>
                                        </div>

                                        <div class="row g-2">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Filière</label>
                                                <select name="filiere" class="form-select" required>
                                                    <option value="">Sélectionner...</option>
                                                    <?php foreach($filieres as $f): ?>
                                                        <option value="<?= sanitize($f) ?>" <?= $ressource['filiere'] == $f ? 'selected' : '' ?>>
                                                            <?= sanitize($f) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Module</label>
                                                <select name="module" class="form-select" required>
                                                    <option value="">Sélectionner...</option>
                                                    <?php foreach($modules as $m): ?>
                                                        <option value="<?= sanitize($m) ?>" <?= $ressource['module'] == $m ? 'selected' : '' ?>>
                                                            <?= sanitize($m) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Remplacer le fichier (optionnel)</label>
                                            <input type="file" name="fichier" class="form-control" 
                                                   accept=".pdf,.doc,.docx,.zip">
                                            <small class="text-muted">Fichier actuel: <?= sanitize($ressource['fichier']) ?></small>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal d'upload -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-upload me-2"></i>Uploader un document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type de document</label>
                        <select class="form-select" name="type" required>
                            <option value="">Sélectionner...</option>
                            <option value="cours">Cours</option>
                            <option value="tp">Travaux Pratiques</option>
                            <option value="exam">Examen</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Titre</label>
                        <input type="text" name="titre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fichier (PDF, DOC, DOCX, ZIP - max <?= formatFileSize(MAX_FILE_SIZE) ?>)</label>
                        <input type="file" class="form-control" name="fichier" 
                               accept=".pdf,.doc,.docx,.zip" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Filière</label>
                            <select class="form-select" name="filiere" required>
                                <option value="">Sélectionner...</option>
                                <option>Développement Digital</option>
                                <option>Réseaux et Systèmes</option>
                                <option>Informatique</option>
                                <option>Téléconseiller Centres d'Appels</option>
                                <option>Intelligence Artificielle</option>
                                <option>Bureauticien Certifié en Microsoft Office Specialist</option>
                                <option>Infrastructure Digitale</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Module</label>
                            <select name="module" class="form-select" required>
                                <option value="">Sélectionner...</option>
                                <option>Développement Web</option>
                                <option>Bases de données</option>
                                <option>Algorithmique</option>
                                <option>PHP</option>
                                <option>JS</option>
                                <option>Cisco</option>
                                <option>Sécurité Réseau</option>
                                <option>Administration Système</option>
                                <option>Bureautique</option>
                                <option>Systèmes d'exploitation</option>
                                <option>Communication</option>
                                <option>Anglais Technique</option>
                                <option>Python IA</option>
                                <option>Machine Learning</option>
                                <option>Deep Learning</option>
                                <option>Word</option>
                                <option>Excel</option>
                                <option>PowerPoint</option>
                                <option>Réseaux</option>
                                <option>Cloud</option>
                                <option>Serveurs</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Uploader</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Toast pour les téléchargements -->
    <div class="toast align-items-center text-white bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true" id="downloadToast">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-download me-2"></i> Préparation du téléchargement...
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    
<?php
ob_start();
?>
    <script>
    document.querySelectorAll('.download-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const toast = new bootstrap.Toast(document.getElementById('downloadToast'));
            toast.show();
            setTimeout(() => { toast.hide(); }, 3000);
        });
    });
    </script>
<?php
$extra_scripts = ob_get_clean();
include __DIR__ . '/includes/layout_foot.php';