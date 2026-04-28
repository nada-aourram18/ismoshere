<?php
session_start();
include("db.php"); // connexion à la BDD

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id_utilisateur'] ?? null;
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $matricule_cef = trim($_POST['matricule_cef'] ?? '');
    $date_modification = $_POST['date_modification'] ?? '';
    $role = trim($_POST['role'] ?? '');
    $id_filiere = $_POST['id_filier'] ?? null;

    // === Validation ===
    if (!$id) {
        $errors['id'] = "Identifiant utilisateur manquant.";
    }
    if (empty($nom) || !ctype_alpha($nom)) {
        $errors['nom'] = "Nom invalide.";
    }
    if (empty($prenom) || !ctype_alpha($prenom)) {
        $errors['prenom'] = "Prénom invalide.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match("/^\d{13}@ofppt-edu\\.ma$/", $email)) {
        $errors['email'] = "Veuillez entrer un mail OFPPT valide (13 chiffres).";
    }
    if (empty($telephone) || !preg_match("/^0(6|7)\d{8}$/", $telephone)) {
        $errors['telephone'] = "Entrez un numéro de téléphone valide (ex: 06XXXXXXXX).";
    }
    if (empty($matricule_cef) || !preg_match("/^[A-Z]\d{9}$/", $matricule_cef)) {
        $errors['matricule_cef'] = "Matricule CEF invalide (ex: A123456789).";
    }
    if (empty($date_modification)) {
        $errors['date_modification'] = "Date de modification obligatoire.";
    }
    if (empty($role)) {
        $errors['role'] = "Rôle obligatoire.";
    }
    if (empty($id_filiere)) {
        $errors['id_filier'] = "Filière obligatoire.";
    }

    // === Gestion de la photo ===
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['photo']['type'], $allowed_types)) {
            $errors['photo'] = "Format d'image non autorisé. Utilisez JPG, PNG ou GIF.";
        } elseif ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
            $errors['photo'] = "Taille de l'image trop grande (max 5MB).";
        } else {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $new_name = uniqid('profile_') . '.' . $ext;
            $destination = __DIR__ . '/images/' . $new_name;
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
                $errors['photo'] = "Erreur lors de l'upload de l'image.";
            } else {
                $photo_path = 'images/' . $new_name;
            }
        }
    }

    // === Si erreurs => retour vers user.php avec erreurs en session ===
    if (!empty($errors)) {
        $_SESSION['modif_errors'] = $errors;
        $_SESSION['old_input'] = $_POST;
        header("Location: user.php?edit=" . urlencode($id));
        exit;
    }

    // === Mise à jour en base ===
    try {
        if ($photo_path) {
            $stmt = $pdo->prepare("UPDATE utilisateur SET nom=?, prenom=?, email=?, telephon=?, matricule_CEF=?, date_inscription=?, role=?, id_filier=?, photo_profil=? WHERE id_utilisateur=?");
            $result = $stmt->execute([$nom, $prenom, $email, $telephone, $matricule_cef, $date_modification, $role, $id_filiere, $photo_path, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE utilisateur SET nom=?, prenom=?, email=?, telephon=?, matricule_CEF=?, date_inscription=?, role=?, id_filier=? WHERE id_utilisateur=?");
            $result = $stmt->execute([$nom, $prenom, $email, $telephone, $matricule_cef, $date_modification, $role, $id_filiere, $id]);
        }

        if ($result) {
            header("Location: user.php?msg=Modification réussie");
            exit;
        } else {
            throw new Exception("Erreur lors de la mise à jour.");
        }

    } catch (Exception $e) {
        $_SESSION['modif_errors'] = ['general' => $e->getMessage()];
        $_SESSION['old_input'] = $_POST;
        $_SESSION['edit_user_id'] = $id;
header("Location: user.php");
exit;
    }

} else {
    // Méthode non autorisée
    $_SESSION['modif_errors'] = ['general' => "Méthode non autorisée."];
    header("Location: user.php");
    exit;
}
