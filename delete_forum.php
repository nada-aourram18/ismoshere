<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: forum.php');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id > 0) {
    $pdo->prepare('DELETE FROM sujet WHERE id_sujet = ?')->execute([$id]);
}

header('Location: forum.php');
exit;
