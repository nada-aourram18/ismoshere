<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');


if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}


$data = json_decode(file_get_contents('php://input'), true);


if (!isset($data['id_notification'])) {
    echo json_encode(['success' => false, 'message' => 'ID notification manquant']);
    exit;
}

$id = (int)$data['id_notification'];
$userId = $_SESSION['user_id'];


$sql = "UPDATE notification SET visible = 0 WHERE id_notification = :id AND id_utilisateur = :userId AND visible = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id, 'userId' => $userId]);
$ok = $stmt->rowCount() > 0;

echo json_encode(['success' => $ok]);
