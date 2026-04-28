<?php
session_start();
include("db.php");

$roles = ['admin', 'formateur', 'stagiaire'];
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || 
   !in_array($_SESSION['user_role'], $roles)) {
    header("Location: login.php?msg=Accès non autorisé");
    exit;
}

$id_utilisateur = $_SESSION['user_id'];
$role = $_SESSION['user_role'];

// Récupérer les infos utilisateur + filière
$select = $pdo->prepare("SELECT u.*, f.nom_filiere 
    FROM utilisateur u
    LEFT JOIN filiere f ON u.id_filier = f.id_filier
    WHERE u.id_utilisateur = ?");
$select->execute([$id_utilisateur]);
$user = $select->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Erreur : utilisateur introuvable.");
}

$erreur = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $nom = trim($_POST['nom'] ?? "");
    $prenom = trim($_POST['prenom'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $telephone = trim($_POST['telephone'] ?? "");
    $matricule_cef = trim($_POST['matricule_cef'] ?? "");
    $role_post = trim($_POST['role'] ?? "");
    $filiere = $_POST['filiere'] ?? "";
    $actuel = $_POST['actuel'] ?? "";
    $nouveau = $_POST['nouveau'] ?? "";
    $confirmer = $_POST['confirmer'] ?? "";

    if (!$nom) $erreur["nom"] = "Le nom est requis";
    if (!$prenom) $erreur["prenom"] = "Le prénom est requis";
    if (!$email) $erreur["email"] = "L'email est requis";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreur["email"] = "Email invalide";

    if (!$telephone) $erreur["telephone"] = "Téléphone est requis";
    elseif (!preg_match('/^(06|07)[0-9]{8}$/', $telephone)) $erreur["telephone"] = "Téléphone invalide";

    if (!$matricule_cef) $erreur["matricule"] = "Matricule est requis";
    elseif (!preg_match('/^[A-Z][0-9]{9}$/', $matricule_cef)) $erreur["matricule"] = "Matricule invalide";

    if (!$role_post) $erreur["role"] = "Rôle est requis";
    if (!$filiere) $erreur["filiere"] = "Filière est requise";

    $query_pass = $pdo->prepare("SELECT mot_de_passe FROM utilisateur WHERE id_utilisateur = ?");
    $query_pass->execute([$id_utilisateur]);
    $b = $query_pass->fetch(PDO::FETCH_ASSOC);
    $hash_password = $b['mot_de_passe'] ?? "";

    if ($actuel !== "" || $nouveau !== "" || $confirmer !== "") {
        if (!$actuel || !$nouveau || !$confirmer) {
            $erreur["motdepasse"] = "Veuillez remplir tous les champs du mot de passe pour le changer";
        } elseif (!password_verify($actuel, $hash_password) && !hash_equals($hash_password, $actuel)) {
            $erreur["motdepasse"] = "Mot de passe actuel incorrect";
        } elseif ($nouveau !== $confirmer) {
            $erreur["motdepasse"] = "Le nouveau mot de passe n'est pas confirmé";
        } else {
            $hash_password = password_hash($nouveau, PASSWORD_DEFAULT);
        }
    }

    if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photo_profil']['tmp_name'];
        $fileName = $_FILES['photo_profil']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileExtension, $allowedfileExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = './images/';
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $photo_profil = $newFileName;
            } else {
                $erreur['photo_profil'] = "Erreur lors de l'upload de la photo.";
            }
        } else {
            $erreur['photo_profil'] = "Format de photo non autorisé.";
        }
    } else {
        $photo_profil = $user['photo_profil'];
    }

    if (empty($erreur)) {
        $mod = $pdo->prepare("UPDATE utilisateur SET nom=?, prenom=?, email=?, role=?, mot_de_passe=?, matricule_CEF=?, telephon=?, id_filier=?, photo_profil=? WHERE id_utilisateur=?");
        $mod->execute([$nom, $prenom, $email, $role_post, $hash_password, $matricule_cef, $telephone, $filiere, $photo_profil, $id_utilisateur]);

        $success = "Profil mis à jour avec succès !";

        $select = $pdo->prepare("SELECT u.*, f.nom_filiere 
            FROM utilisateur u
            LEFT JOIN filiere f ON u.id_filier = f.id_filier
            WHERE u.id_utilisateur = ?");
        $select->execute([$id_utilisateur]);
        $user = $select->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            die("Erreur : utilisateur mis à jour introuvable.");
        }
    }
}

$page_title = 'Mon profil - ISMOShare';
$current_page = basename(__FILE__);
$extra_head = <<<'HTML'
<style>
    .profil-page .profile-header {
      background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
      border-radius: 16px;
      color: #fff;
      padding: 2rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 10px 28px rgba(15, 118, 110, 0.2);
    }
    .profil-page .profile-avatar {
      width: 140px; height: 140px; border: 4px solid rgba(255,255,255,0.9);
      box-shadow: 0 4px 16px rgba(0,0,0,0.15); object-fit: cover;
    }
    .profil-page .info-card { border-radius: 12px; transition: all 0.25s; border: none; }
    .profil-page .info-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
</style>
HTML;
include __DIR__ . '/includes/layout_head.php';
include __DIR__ . '/includes/app_nav.php';
?>

<div class="container py-4 profil-page ismo-main">
  <!-- Profile Header -->
  <div class="profile-header d-flex align-items-center gap-4">
    <img src="images/<?= htmlspecialchars($user['photo_profil']) ?>" alt="Photo Profil" class="profile-avatar rounded-circle" />
    <div>
      <h1 class="mb-1"><?= htmlspecialchars($user['nom']) ?> <?= htmlspecialchars($user['prenom']) ?></h1>
      <p><i class="fas fa-user-tag me-1"></i> <?= htmlspecialchars(ucfirst($user['role'])) ?></p>
      <p><i class="fas fa-graduation-cap me-1"></i> <?= htmlspecialchars($user['nom_filiere']) ?></p>
    </div>
  </div>

  <!-- Messages -->
  <?php if ($success): ?>
    <div class="alert alert-success">
      <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>
  <?php if (!empty($erreur)): ?>
    <div class="alert alert-danger">
      <i class="fas fa-exclamation-circle me-2"></i>
      <ul class="mb-0">
        <?php foreach ($erreur as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- Informations -->
  <div class="card info-card mb-4">
    <div class="card-body">
      <h5 class="card-title text-primary mb-4">
        <i class="fas fa-info-circle me-2"></i>Informations Personnelles
      </h5>
      <table class="table table-borderless">
        <tr>
          <th width="30%"><i class="fas fa-id-card me-2 text-secondary"></i>Matricule</th>
          <td><?= htmlspecialchars($user['matricule_CEF']) ?></td>
        </tr>
        <tr>
          <th><i class="fas fa-envelope me-2 text-secondary"></i>Email</th>
          <td><?= htmlspecialchars($user['email']) ?></td>
        </tr>
        <tr>
          <th><i class="fas fa-phone me-2 text-secondary"></i>Téléphone</th>
          <td><?= htmlspecialchars($user['telephon']) ?></td>
        </tr>
        <tr>
          <th><i class="fas fa-calendar-alt me-2 text-secondary"></i>Date inscription</th>
          <td><?= htmlspecialchars($user['date_inscription']) ?></td>
        </tr>
      </table>
      <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editAllModal">
        <i class="fas fa-edit me-1"></i> Modifier mes informations
      </button>
    </div>
  </div>
</div>

<!-- Modal Modification -->
<div class="modal fade" id="editAllModal" tabindex="-1" aria-labelledby="editAllModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form action="" method="POST" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-cog me-2"></i>Modifier les informations</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Nom</label>
              <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($user['nom']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Prénom</label>
              <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($user['prenom']) ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Téléphone</label>
            <input type="tel" name="telephone" class="form-control" value="<?= htmlspecialchars($user['telephon']) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Matricule CEF</label>
            <input type="text" name="matricule_cef" class="form-control" value="<?= htmlspecialchars($user['matricule_CEF']) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Rôle</label>
            <input type="text" name="role" class="form-control" value="<?= htmlspecialchars($user['role']) ?>" required>
          </div>

          <div class="mb-3">
            <label for="filiere" class="form-label">Filière</label>
            <select class="form-select" id="filiere" name="filiere" required>
              <?php
              $querF = $pdo->query("SELECT * FROM filiere");
              $filieres = $querF->fetchAll(PDO::FETCH_ASSOC);
              foreach ($filieres as $f) {
                $selected = ($user['id_filier'] == $f['id_filier']) ? 'selected' : '';
                echo "<option value='{$f['id_filier']}' $selected>" . htmlspecialchars($f['nom_filiere']) . "</option>";
              }
              ?>
            </select>
          </div>

          <hr class="my-4" />

          <div class="mb-3">
            <label for="photo_profil" class="form-label">Modifier la photo de profil</label>
            <input type="file" class="form-control" id="photo_profil" name="photo_profil" accept="image/*">
            <img id="preview" src="images/<?= htmlspecialchars($user['photo_profil']) ?>" alt="Photo Profil" class="img-thumbnail mt-2" style="max-width: 150px;">
          </div>

          <h6 class="mb-3"><i class="fas fa-lock me-2 text-secondary"></i>Changer le mot de passe</h6>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Mot de passe actuel</label>
              <input type="password" name="actuel" class="form-control" placeholder="Ancien mot de passe">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Nouveau mot de passe</label>
              <input type="password" name="nouveau" class="form-control" placeholder="Nouveau mot de passe">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Confirmer le mot de passe</label>
              <input type="password" name="confirmer" class="form-control" placeholder="Confirmer">
            </div>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn btn-success">
          <i class="fas fa-save me-1"></i> Enregistrer les modifications
        </button>
      </div>
    </form>
  </div>
</div>

<?php
ob_start();
?>
<script>
  const inputFile = document.getElementById('photo_profil');
  const preview = document.getElementById('preview');
  if (inputFile && preview) {
    inputFile.addEventListener('change', function() {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          preview.src = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    });
  }
</script>
<?php
$extra_scripts = ob_get_clean();
include __DIR__ . '/includes/layout_foot.php';