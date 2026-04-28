<?php
session_start();
require __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'], $_SESSION['user_role'])) {
    header('Location: group.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$role = $_SESSION['user_role'];

$id = (int) ($_POST['id_msg'] ?? 0);
$gid = (int) ($_POST['group_id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . ($gid > 0 ? 'group.php?group_id=' . $gid : 'group.php'));
    exit;
}

$stmt = $pdo->prepare('
    SELECT m.id_msg, m.id_groupe, m.id_utilisateur AS author_id, u.role AS author_role
    FROM messager m
    INNER JOIN utilisateur u ON m.id_utilisateur = u.id_utilisateur
    WHERE m.id_msg = ?
');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header('Location: ' . ($gid > 0 ? 'group.php?group_id=' . $gid : 'group.php'));
    exit;
}

$msgGroupe = (int) $row['id_groupe'];
if ($gid > 0 && $msgGroupe !== $gid) {
    header('Location: group.php?group_id=' . $gid);
    exit;
}

$allowed = false;
if ($role === 'admin') {
    $allowed = true;
} elseif ($role === 'stagiaire') {
    $allowed = ((int) $row['author_id'] === $userId);
} elseif ($role === 'formateur') {
    if (($row['author_role'] ?? '') === 'stagiaire') {
        $chk = $pdo->prepare('SELECT 1 FROM groupe WHERE id_groupe = ? AND (user1 = ? OR user2 = ?)');
        $chk->execute([$msgGroupe, $userId, $userId]);
        $allowed = (bool) $chk->fetchColumn();
    }
}

if ($allowed) {
    $pdo->prepare('DELETE FROM messager WHERE id_msg = ?')->execute([$id]);
}

header('Location: ' . ($msgGroupe > 0 ? 'group.php?group_id=' . $msgGroupe : 'group.php'));
exit;
