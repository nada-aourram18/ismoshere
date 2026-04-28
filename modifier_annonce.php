<?php
include 'db.php';

if (!isset($_GET['id'])) {
    die("ID de l'annonce manquant.");
}

$id = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM annonce WHERE id_annonce = ?");
$stmt->execute([$id]);
$annonce = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$annonce) {
    die("Annonce introuvable.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'];
    $contenu = $_POST['contenu'];
    $statut = $_POST['statut'];
    $date_publication = $_POST['date_publication'];

    $imagePath = $annonce['image']; 

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageTmpPath = $_FILES['image']['tmp_name'];
        $imageName = basename($_FILES['image']['name']);
        $targetDir = 'uploads/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $targetPath = $targetDir . time() . '_' . $imageName;

        if (move_uploaded_file($imageTmpPath, $targetPath)) {
            // supprimer l'ancienne image si elle existe et différente de null
            if ($imagePath && file_exists($imagePath)) {
                unlink($imagePath);
            }
            $imagePath = $targetPath;
        }
    }

    $stmt = $pdo->prepare("UPDATE annonce SET titre = ?, contenu = ?, statut = ?, date_publication = ?, image = ? WHERE id_annonce = ?");
    $stmt->execute([$titre, $contenu, $statut, $date_publication, $imagePath, $id]);

    header("Location: Gestion des Annonces.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Annonce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4">Modifier l'annonce</h2>

    <form method="post" enctype="multipart/form-data" class="border p-4 mb-4 bg-white rounded shadow-sm">
        <div class="mb-3">
            <label class="form-label">Titre *</label>
            <input type="text" name="titre" class="form-control" value="<?= htmlspecialchars($annonce['titre']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Statut *</label>
            <select name="statut" class="form-select" required>
                <option value="Actif" <?= $annonce['statut'] === 'Actif' ? 'selected' : '' ?>>Actif</option>
                <option value="Inactif" <?= $annonce['statut'] === 'Inactif' ? 'selected' : '' ?>>Inactif</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Contenu *</label>
            <textarea name="contenu" class="form-control" rows="4" required><?= htmlspecialchars($annonce['contenu']) ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Date de publication</label>
            <input type="date" name="date_publication" class="form-control" value="<?= htmlspecialchars($annonce['date_publication']) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Image actuelle</label><br>
            <?php if (!empty($annonce['image']) && file_exists($annonce['image'])): ?>
                <img src="<?= htmlspecialchars($annonce['image']) ?>" alt="Image annonce" style="max-width: 150px; border-radius: 4px;">
            <?php else: ?>
                <span class="text-muted">Pas d'image</span>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label class="form-label">Changer l'image</label>
            <input type="file" name="image" class="form-control" accept="image/*">
            <small class="form-text text-muted">Laisser vide pour garder l'image actuelle.</small>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
        <a href="Gestion des Annonces.php" class="btn btn-secondary">Annuler</a>
    </form>
</div>
</body>
</html>
