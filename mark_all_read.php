<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');


if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$userId = $_SESSION['user_id'];


$sql = "UPDATE notification SET statut = 'read' WHERE id_utilisateur = :userId AND statut = 'unread' AND visible = 1";
$stmt = $pdo->prepare($sql);
$success = $stmt->execute(['userId' => $userId]);

echo json_encode(['success' => $success]);
