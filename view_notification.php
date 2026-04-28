<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    echo "ID notification manquant.";
    exit;
}

$id = (int)$_GET['id'];

$sql = "SELECT * FROM notification WHERE id_notification = :id AND id_utilisateur = :userId AND visible = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id, 'userId' => $userId]);
$notif = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notif) {
    echo "Notification non trouvée ou non autorisée.";
    exit;
}

if (($notif['statut'] ?? '') === 'unread') {
    $u = $pdo->prepare("UPDATE notification SET statut = 'read' WHERE id_notification = ? AND id_utilisateur = ?");
    $u->execute([$id, $userId]);
    $notif['statut'] = 'read';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Détail de la Notification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="p-5">

    <div class="container">
        <h1>Détail de la Notification #<?= htmlspecialchars($notif['id_notification']) ?></h1>
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">Message</h5>
                <p class="card-text"><?= nl2br(htmlspecialchars($notif['message'])) ?></p>
                <p><strong>Date :</strong> <?= date('d/m/Y H:i:s', strtotime($notif['date_notification'])) ?></p>
                <p><strong>Statut :</strong> <?= htmlspecialchars($notif['statut']) ?></p>
                <a href="notification.php" class="btn btn-primary mt-3">Retour aux notifications</a>
            </div>
        </div>
    </div>

</body>
</html>
