<?php
session_start();
include 'db.php';

// Vérification des droits d'accès
$roles = ['admin'];
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles)) {
    header("Location: login.php?msg=Accès non autorisé");
    exit;
}
$role = $_SESSION['user_role'] ?? '';
$user_name = $_SESSION['user_name'] ?? 'Utilisateur';
$current_page = basename($_SERVER['PHP_SELF']);

// Traitement de la suppression
if (isset($_GET['delete_user'])) {
    $id_utilisateur = $_GET['delete_user'];
    
    try {
        // Vérifier qu'on ne supprime pas soi-même
        if ($id_utilisateur == $_SESSION['user_id']) {
            $_SESSION['error'] = "Vous ne pouvez pas supprimer votre propre compte";
            header("Location: user.php");
            exit;
        }
        
        // Soft delete (changement de statut)
        $stmt = $pdo->prepare("UPDATE utilisateur SET statut = 'supprime' WHERE id_utilisateur = ?");
        $stmt->execute([$id_utilisateur]);
        
        $_SESSION['success'] = "Utilisateur supprimé avec succès";
        header("Location: user.php");
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la suppression : " . $e->getMessage();
        header("Location: user.php");
        exit;
    }
}

// Traitement de l'ajout
if (isset($_POST['ajouterUser'])) {
    $errors = [];
    $old_input = $_POST;
    
    // Validation des données
    if (empty($_POST['nom'])) $errors['nom'] = "Le nom est requis";
    if (empty($_POST['prenom'])) $errors['prenom'] = "Le prénom est requis";
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = "Email invalide";
    if (empty($_POST['telephone'])) $errors['telephone'] = "Le téléphone est requis";
    if (empty($_POST['matricule_cef'])) $errors['matricule_cef'] = "Le matricule CEF est requis";
    if (empty($_POST['role'])) $errors['role'] = "Le rôle est requis";
    if (empty($_POST['id_filier'])) $errors['id_filier'] = "La filière est requise";
    if (empty($_POST['password'])) $errors['password'] = "Le mot de passe est requis";
    if ($_POST['password'] !== $_POST['confirm_password']) $errors['confirm_password'] = "Les mots de passe ne correspondent pas";
    
    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    if ($stmt->fetch()) $errors['email'] = "Cet email est déjà utilisé";
    
    if (empty($errors)) {
        try {
            // Gestion de l'upload de la photo
            $photo_profil = 'default-user.png';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $photo_profil = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], 'images/' . $photo_profil);
            }
            
            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

            // Insertion dans la base de données
            $stmt = $pdo->prepare("INSERT INTO utilisateur 
                (nom, prenom, email, telephon, matricule_CEF, role, id_filier, date_inscription, photo_profil, mot_de_passe, statut) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'valide')");
            
            $stmt->execute([
                $_POST['nom'],
                $_POST['prenom'],
                $_POST['email'],
                $_POST['telephone'],
                $_POST['matricule_cef'],
                $_POST['role'],
                $_POST['id_filier'],
                $_POST['date_inscription'],
                $photo_profil,
                $password_hash
            ]);
            
            $_SESSION['success'] = "Utilisateur ajouté avec succès";
            header("Location: user.php");
            exit;
            
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de l'ajout : " . $e->getMessage();
            header("Location: user.php");
            exit;
        }
    } else {
        $_SESSION['add_errors'] = $errors;
        $_SESSION['old_input'] = $old_input;
        header("Location: user.php");
        exit;
    }
}

// Traitement de la modification
if (isset($_POST['modifierUser'])) {
    $errors = [];
    $old_input = $_POST;
    
    // Validation des donnéesprofiles
    if (empty($_POST['nom'])) $errors['nom'] = "Le nom est requis";
    if (empty($_POST['prenom'])) $errors['prenom'] = "Le prénom est requis";
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = "Email invalide";
    if (empty($_POST['telephone'])) $errors['telephone'] = "Le téléphone est requis";
    if (empty($_POST['matricule_cef'])) $errors['matricule_cef'] = "Le matricule CEF est requis";
    if (empty($_POST['role'])) $errors['role'] = "Le rôle est requis";
    if (empty($_POST['id_filier'])) $errors['id_filier'] = "La filière est requise";
    
    // Vérifier si l'email existe déjà pour un autre utilisateur
    $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = ? AND id_utilisateur != ?");
    $stmt->execute([$_POST['email'], $_POST['id_utilisateur']]);
    if ($stmt->fetch()) $errors['email'] = "Cet email est déjà utilisé par un autre utilisateur";
    
    if (empty($errors)) {
        try {
            // Récupérer l'utilisateur actuel pour la photo
            $stmt = $pdo->prepare("SELECT photo_profil FROM utilisateur WHERE id_utilisateur = ?");
            $stmt->execute([$_POST['id_utilisateur']]);
            $user = $stmt->fetch();
            $photo_profil = $user['photo_profil'];
            
            // Gestion de l'upload de la nouvelle photo
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                // Supprimer l'ancienne photo si ce n'est pas la photo par défaut
                if ($photo_profil != 'default-user.png' && file_exists('images/' . $photo_profil)) {
                    unlink('images/' . $photo_profil);
                }
                
                $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $photo_profil = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], 'images/' . $photo_profil);
            }
            
            // Mise à jour dans la base de données
            $stmt = $pdo->prepare("UPDATE utilisateur SET 
                nom = ?, prenom = ?, email = ?, telephon = ?, matricule_CEF = ?, 
                role = ?, id_filier = ?, date_modification = ?, photo_profil = ? 
                WHERE id_utilisateur = ?");
            
            $stmt->execute([
                $_POST['nom'],
                $_POST['prenom'],
                $_POST['email'],
                $_POST['telephone'],
                $_POST['matricule_cef'],
                $_POST['role'],
                $_POST['id_filier'],
                $_POST['date_modification'],
                $photo_profil,
                $_POST['id_utilisateur']
            ]);
            
            $_SESSION['success'] = "Utilisateur modifié avec succès";
            header("Location: user.php");
            exit;
            
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la modification : " . $e->getMessage();
            header("Location: user.php");
            exit;
        }
    } else {
        $_SESSION['modif_errors'] = $errors;
        $_SESSION['old_input'] = $old_input;
        $_SESSION['edit_user_id'] = $_POST['id_utilisateur'];
        header("Location: user.php");
        exit;
    }
}

// Récupération des données de session pour affichage
$add_errors = $_SESSION['add_errors'] ?? [];
$modif_errors = $_SESSION['modif_errors'] ?? [];
$old_input = $_SESSION['old_input'] ?? [];
$edit_user_id = $_SESSION['edit_user_id'] ?? null;
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;

// Nettoyage des données de session
unset($_SESSION['add_errors'], $_SESSION['modif_errors'], $_SESSION['old_input'], 
      $_SESSION['edit_user_id'], $_SESSION['success'], $_SESSION['error']);

try {
    // Statistiques des utilisateurs
    $f = $pdo->query("SELECT COUNT(*) FROM utilisateur WHERE role='formateur' AND statut='valide'")->fetchColumn();
    $s = $pdo->query("SELECT COUNT(*) FROM utilisateur WHERE role='stagiaire' AND statut='valide'")->fetchColumn();
    $a = $pdo->query("SELECT COUNT(*) FROM utilisateur WHERE role='admin' AND statut='valide'")->fetchColumn();
    $t = $pdo->query("SELECT COUNT(*) FROM utilisateur WHERE statut='valide'")->fetchColumn();

    // Gestion de la recherche
    $search_nom = "";
    $sql = "SELECT utilisateur.*, filiere.nom_filiere 
            FROM utilisateur 
            LEFT JOIN filiere ON utilisateur.id_filier = filiere.id_filier
            WHERE utilisateur.statut = 'valide'";

    if (isset($_GET['search_nom']) && !empty(trim($_GET['search_nom']))) {
        $search_nom = trim($_GET['search_nom']);
        $sql .= " AND utilisateur.nom LIKE :nom";
    }

    $stmt = $pdo->prepare($sql);

    if (!empty($search_nom)) {
        $stmt->execute(['nom' => '%' . $search_nom . '%']);
    } else {
        $stmt->execute();
    }

    $users = $stmt->fetchAll();
    
    // Récupération des filières pour les selects
    $filieres = $pdo->query("SELECT * FROM filiere")->fetchAll();

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
$page_title = 'Gestion des utilisateurs - ISMOShare';
$current_page = basename(__FILE__);
$extra_head = <<<'HTML'
<style>
        .users-page .profile-header {
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
            border-radius: 14px;
            color: white;
            padding: 1.75rem 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 24px rgba(15, 118, 110, 0.2);
        }
        .users-page .user-avatar {
            width: 40px; height: 40px; border-radius: 50%; object-fit: cover;
            border: 2px solid rgba(255,255,255,0.5);
        }
        .users-page .card { border-radius: 12px; transition: all 0.25s; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        .users-page .card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .users-page .badge-role { padding: 5px 10px; border-radius: 20px; font-weight: 500; font-size: 0.8rem; }
        .users-page .badge-admin { background-color: rgba(220, 53, 69, 0.12); color: #dc3545; }
        .users-page .badge-teacher { background-color: rgba(13, 110, 253, 0.12); color: #0d6efd; }
        .users-page .badge-student { background-color: rgba(25, 135, 84, 0.12); color: #198754; }
        .users-page .table th { background: linear-gradient(180deg, #0f766e, #0d5d56); color: white; font-weight: 600; }
        .users-page .action-btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; transition: transform 0.2s; }
        .users-page .action-btn:hover { transform: scale(1.08); }
        .users-page .search-box { position: relative; max-width: 400px; }
        .users-page .search-box .form-control { padding-left: 40px; border-radius: 20px; }
        .users-page .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #6c757d; }
</style>
HTML;
include __DIR__ . '/includes/layout_head.php';
include __DIR__ . '/includes/app_nav.php';
?>

<div class="container py-4 users-page ismo-main">
    <!-- En-tête -->
    <div class="profile-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-1"><i class="fas fa-users me-2"></i>Gestion des Utilisateurs</h1>
            <p class="mb-0">Gérez les comptes et les permissions</p>
        </div>
        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus me-1"></i> Nouvel utilisateur
        </button>
    </div>

    <!-- Messages d'alerte -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="card h-100 border-primary">
                <div class="card-body text-center py-4">
                    <h3 class="mb-0 text-primary"><?= $t ?></h3>
                    <p class="mb-0">Utilisateurs</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-success">
                <div class="card-body text-center py-4">
                    <h3 class="mb-0 text-success"><?= $s ?></h3>
                    <p class="mb-0">Stagiaires</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-info">
                <div class="card-body text-center py-4">
                    <h3 class="mb-0 text-info"><?= $f ?></h3>
                    <p class="mb-0">Formateurs</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-danger">
                <div class="card-body text-center py-4">
                    <h3 class="mb-0 text-danger"><?= $a ?></h3>
                    <p class="mb-0">Administrateurs</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recherche et boutons -->
    <div class="d-flex justify-content-between mb-4">
        <form method="GET" class="d-flex align-items-center">
            <div class="search-box me-2">
                <i class="fas fa-search"></i>
                <input type="text" name="search_nom" class="form-control" 
                       placeholder="Rechercher par nom..." 
                       value="<?= htmlspecialchars($search_nom) ?>">
            </div>
            <button type="submit" class="btn btn-primary me-2">
                <i class="fas fa-search me-1"></i> Rechercher
            </button>
            <a href="user.php" class="btn btn-outline-secondary">
                <i class="fas fa-sync-alt me-1"></i> Réinitialiser
            </a>
        </form>
    </div>

    <!-- Tableau des utilisateurs -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th width="80">Photo</th>
                            <th>Nom complet</th>
                            <th>Email</th>
                            <th width="120">Rôle</th>
                            <th width="150">Filière</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $i => $u): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <img src="images/<?= htmlspecialchars($u['photo_profil'] ?: 'default-user.png') ?>" 
                                     class="user-avatar" alt="<?=$u['prenom']?>" >
                            </td>
                            <td>
                                <strong><?= strtoupper(htmlspecialchars($u['nom'])) ?></strong><br>
                                <?= htmlspecialchars($u['prenom']) ?>
                            </td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="badge-role 
                                    <?= $u['role'] === 'admin' ? 'badge-admin' : 
                                       ($u['role'] === 'formateur' ? 'badge-teacher' : 'badge-student') ?>">
                                    <?= htmlspecialchars($u['role']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($u['nom_filiere'] ?? 'Non attribuée') ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="#" class="btn btn-sm btn-outline-primary action-btn"
                                       data-bs-toggle="modal" data-bs-target="#editUserModal<?= $u['id_utilisateur'] ?>"
                                       title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="user.php?delete_user=<?= $u['id_utilisateur'] ?>" 
                                       class="btn btn-sm btn-outline-danger action-btn"
                                       onclick="return confirm('Voulez-vous vraiment supprimer cet utilisateur ?');"
                                       title="Supprimer">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajout utilisateur -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="user.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="ajouterUser" value="1">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Nouvel utilisateur</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom *</label>
                            <input type="text" name="nom" class="form-control <?php echo isset($add_errors['nom']) ? 'is-invalid' : ''; ?>" 
                                   value="<?= htmlspecialchars($old_input['nom'] ?? '') ?>" required>
                            <?php if (isset($add_errors['nom'])): ?>
                                <div class="invalid-feedback"><?= $add_errors['nom'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prénom *</label>
                            <input type="text" name="prenom" class="form-control <?php echo isset($add_errors['prenom']) ? 'is-invalid' : ''; ?>" 
                                   value="<?= htmlspecialchars($old_input['prenom'] ?? '') ?>" required>
                            <?php if (isset($add_errors['prenom'])): ?>
                                <div class="invalid-feedback"><?= $add_errors['prenom'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control <?php echo isset($add_errors['email']) ? 'is-invalid' : ''; ?>" 
                                   value="<?= htmlspecialchars($old_input['email'] ?? '') ?>" required>
                            <?php if (isset($add_errors['email'])): ?>
                                <div class="invalid-feedback"><?= $add_errors['email'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Téléphone *</label>
                            <input type="tel" name="telephone" class="form-control <?php echo isset($add_errors['telephone']) ? 'is-invalid' : ''; ?>" 
                                   value="<?= htmlspecialchars($old_input['telephone'] ?? '') ?>" required>
                            <?php if (isset($add_errors['telephone'])): ?>
                                <div class="invalid-feedback"><?= $add_errors['telephone'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Matricule CEF *</label>
                            <input type="text" name="matricule_cef" class="form-control <?php echo isset($add_errors['matricule_cef']) ? 'is-invalid' : ''; ?>" 
                                   value="<?= htmlspecialchars($old_input['matricule_cef'] ?? '') ?>" required>
                            <?php if (isset($add_errors['matricule_cef'])): ?>
                                <div class="invalid-feedback"><?= $add_errors['matricule_cef'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rôle *</label>
                            <select name="role" class="form-select <?php echo isset($add_errors['role']) ? 'is-invalid' : ''; ?>" required>
                                <option value="">Sélectionner...</option>
                                <option value="admin" <?= (isset($old_input['role']) && $old_input['role'] === 'admin' ? 'selected' : '') ?>>Administrateur</option>
                                <option value="formateur" <?= (isset($old_input['role']) && $old_input['role'] === 'formateur' ? 'selected' : '' )?>>Formateur</option>
                                <option value="stagiaire" <?= (isset($old_input['role']) && $old_input['role'] === 'stagiaire' ? 'selected' : '') ?>>Stagiaire</option>
                            </select>
                            <?php if (isset($add_errors['role'])): ?>
                                <div class="invalid-feedback"><?= $add_errors['role'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Filière *</label>
                            <select name="id_filier" class="form-select <?php echo isset($add_errors['id_filier']) ? 'is-invalid' : ''; ?>" required>
                                <option value="">Sélectionner...</option>
                                <?php foreach ($filieres as $f): ?>
                                    <option value="<?= $f['id_filier'] ?>" 
                                        <?= (isset($old_input['id_filier']) && $old_input['id_filier'] == $f['id_filier'] ? 'selected' : '') ?>>
                                        <?= htmlspecialchars($f['nom_filiere']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($add_errors['id_filier'])): ?>
                                <div class="invalid-feedback"><?= $add_errors['id_filier'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date d'inscription *</label>
                            <input type="date" name="date_inscription" class="form-control" 
                                   value="<?= htmlspecialchars($old_input['date_inscription'] ?? date('Y-m-d')) ?>" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Mot de passe *</label>
                            <input type="password" name="password" class="form-control <?php echo isset($add_errors['password']) ? 'is-invalid' : ''; ?>" required>
                            <?php if (isset($add_errors['password'])): ?>
                                <div class="invalid-feedback"><?= $add_errors['password'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirmation *</label>
                            <input type="password" name="confirm_password" class="form-control <?php echo isset($add_errors['confirm_password']) ? 'is-invalid' : ''; ?>" required>
                            <?php if (isset($add_errors['confirm_password'])): ?>
                                <div class="invalid-feedback"><?= $add_errors['confirm_password'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Photo de profil</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modals de modification pour chaque utilisateur -->
<?php foreach ($users as $u): ?>
<div class="modal fade" id="editUserModal<?= $u['id_utilisateur'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="user.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="modifierUser" value="1">
                <input type="hidden" name="id_utilisateur" value="<?= $u['id_utilisateur'] ?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Modifier utilisateur</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom *</label>
                            <input type="text" name="nom" class="form-control <?php echo (isset($modif_errors['nom']) && $edit_user_id == $u['id_utilisateur']) ? 'is-invalid' : ''; ?>" 
                                   value="<?= htmlspecialchars(($edit_user_id == $u['id_utilisateur'] ? ($old_input['nom'] ?? '') : $u['nom'])) ?>" required>
                            <?php if (isset($modif_errors['nom']) && $edit_user_id == $u['id_utilisateur']): ?>
                                <div class="invalid-feedback"><?= $modif_errors['nom'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prénom *</label>
                            <input type="text" name="prenom" class="form-control <?php echo (isset($modif_errors['prenom']) && $edit_user_id == $u['id_utilisateur']) ? 'is-invalid' : ''; ?>" 
                                   value="<?= htmlspecialchars(($edit_user_id == $u['id_utilisateur'] ? ($old_input['prenom'] ?? '') : $u['prenom'])) ?>" required>
                            <?php if (isset($modif_errors['prenom']) && $edit_user_id == $u['id_utilisateur']): ?>
                                <div class="invalid-feedback"><?= $modif_errors['prenom'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control <?php echo (isset($modif_errors['email']) && $edit_user_id == $u['id_utilisateur']) ? 'is-invalid' : ''; ?>" 
                                   value="<?= htmlspecialchars(($edit_user_id == $u['id_utilisateur'] ? ($old_input['email'] ?? '') : $u['email'])) ?>" required>
                            <?php if (isset($modif_errors['email']) && $edit_user_id == $u['id_utilisateur']): ?>
                                <div class="invalid-feedback"><?= $modif_errors['email'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Téléphone *</label>
                            <input type="tel" name="telephone" class="form-control <?php echo (isset($modif_errors['telephone']) && $edit_user_id == $u['id_utilisateur']) ? 'is-invalid' : ''; ?>" 
                                   value="<?= htmlspecialchars(($edit_user_id == $u['id_utilisateur'] ? ($old_input['telephone'] ?? '') : $u['telephon'])) ?>" required>
                            <?php if (isset($modif_errors['telephone']) && $edit_user_id == $u['id_utilisateur']): ?>
                                <div class="invalid-feedback"><?= $modif_errors['telephone'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Matricule CEF *</label>
                            <input type="text" name="matricule_cef" class="form-control <?php echo (isset($modif_errors['matricule_cef']) && $edit_user_id == $u['id_utilisateur']) ? 'is-invalid' : ''; ?>" 
                                   value="<?= htmlspecialchars(($edit_user_id == $u['id_utilisateur'] ? ($old_input['matricule_cef'] ?? '') : $u['matricule_CEF'])) ?>" required>
                            <?php if (isset($modif_errors['matricule_cef']) && $edit_user_id == $u['id_utilisateur']): ?>
                                <div class="invalid-feedback"><?= $modif_errors['matricule_cef'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rôle *</label>
                            <select name="role" class="form-select <?php echo (isset($modif_errors['role']) && $edit_user_id == $u['id_utilisateur']) ? 'is-invalid' : ''; ?>" required>
                                <option value="admin" <?= (($edit_user_id == $u['id_utilisateur'] ? ($old_input['role'] ?? '') : $u['role']) === 'admin') ? 'selected' : '' ?>>Administrateur</option>
                                <option value="formateur" <?= (($edit_user_id == $u['id_utilisateur'] ? ($old_input['role'] ?? '') : $u['role']) === 'formateur') ? 'selected' : '' ?>>Formateur</option>
                                <option value="stagiaire" <?= (($edit_user_id == $u['id_utilisateur'] ? ($old_input['role'] ?? '') : $u['role']) === 'stagiaire') ? 'selected' : '' ?>>Stagiaire</option>
                            </select>
                            <?php if (isset($modif_errors['role']) && $edit_user_id == $u['id_utilisateur']): ?>
                                <div class="invalid-feedback"><?= $modif_errors['role'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Filière *</label>
                            <select name="id_filier" class="form-select <?php echo (isset($modif_errors['id_filier']) && $edit_user_id == $u['id_utilisateur']) ? 'is-invalid' : ''; ?>" required>
                                <?php foreach ($filieres as $f): ?>
                                    <option value="<?= $f['id_filier'] ?>" 
                                        <?= (($edit_user_id == $u['id_utilisateur'] ? ($old_input['id_filier'] ?? '') : $u['id_filier']) == $f['id_filier']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($f['nom_filiere']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($modif_errors['id_filier']) && $edit_user_id == $u['id_utilisateur']): ?>
                                <div class="invalid-feedback"><?= $modif_errors['id_filier'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date modification</label>
                            <input type="date" name="date_modification" class="form-control" 
                                   value="<?= date('Y-m-d') ?>" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Photo de profil</label>
                        <div class="d-flex align-items-center mb-2">
                            <img src="images/<?= htmlspecialchars($u['photo_profil'] ?: 'default-user.png') ?>" 
                                 class="img-thumbnail me-3" width="80" alt="Photo actuelle">
                            <div>
                                <input type="file" name="photo" class="form-control" accept="image/*">
                                <small class="text-muted">Laisser vide pour ne pas modifier</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php ob_start(); ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($add_errors)): ?>
            new bootstrap.Modal(document.getElementById('addUserModal')).show();
        <?php endif; ?>
        <?php if (!empty($modif_errors) && $edit_user_id): ?>
            new bootstrap.Modal(document.getElementById('editUserModal<?= $edit_user_id ?>')).show();
        <?php endif; ?>
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                const preview = this.closest('.modal-body').querySelector('img');
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) { preview.src = e.target.result; };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        });
    });
</script>
<?php
$extra_scripts = ob_get_clean();
include __DIR__ . '/includes/layout_foot.php';