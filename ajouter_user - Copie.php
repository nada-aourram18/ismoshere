<?php
include 'db.php';

session_start();
$messageerr = [];

// $host = 'localhost';
// $db = 'ismoshere';
// $user = 'root';
// $pass = '';

try {
    // $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouterUser'])) {
        // Récupération des données
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $filiere = trim($_POST['filiere'] ?? null);
        $matricule = trim($_POST['matricule_cef'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validations
        if (empty($nom) || !ctype_alpha($nom)) $messageerr['nom'] = "Nom invalide";
        if (empty($prenom) || !ctype_alpha($prenom)) $messageerr['prenom'] = "Prénom invalide";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $messageerr['email'] = "Email invalide";
        if (!preg_match('/^[A-Z][0-9]{9}$/', $matricule)) $messageerr['matricule'] = "Matricule invalide";
        if (!preg_match('/^0[67][0-9]{8}$/', $telephone)) $messageerr['telephone'] = "Téléphone invalide";
        if (!in_array($role, ['admin', 'formateur', 'stagiaire'])) $messageerr['role'] = "Rôle invalide";
        if (empty($password) || $password !== $confirmPassword) $messageerr['password'] = "Mots de passe non valides";

        // Vérifications unicité
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) $messageerr['email'] = "Email existe déjà";

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE matricule_CEF = ?");
        $stmt->execute([$matricule]);
        if ($stmt->fetchColumn() > 0) $messageerr['matricule'] = "Matricule existe déjà";

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE telephon = ?");
        $stmt->execute([$telephone]);
        if ($stmt->fetchColumn() > 0) $messageerr['telephone'] = "Téléphone existe déjà";

        // Image
        $profileImageName = null;
        if (!empty($_FILES['profileImage']['name'])) {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (in_array($_FILES['profileImage']['type'], $allowedTypes)) {
                $ext = pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION);
                $profileImageName = uniqid('profile_') . '.' . $ext;
                move_uploaded_file($_FILES['profileImage']['tmp_name'], "images/" . $profileImageName);
            } else {
                $messageerr['profileImage'] = "Image invalide";
            }
        }

        // Insertion
        if (empty($messageerr)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO utilisateur (nom, prenom, email, role, mot_de_passe, matricule_CEF, telephon, id_filier, photo_profil, statut)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'valide')");
            $stmt->execute([
                $nom, $prenom, $email, $role, $hashedPassword,
                $matricule, $telephone,
                $filiere ?: null,
                $profileImageName
            ]);
            header("Location: user.php?msg=Utilisateur ajouté avec succès");
            exit;
        } else {
            $_SESSION['errors'] = $messageerr;
            header("Location: user.php?addUserError=1");
            exit;
        }
    }
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}
?>
