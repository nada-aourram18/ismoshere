<?php
include("db.php");

$search_nom = "";
$sql = "SELECT * FROM utilisateur";

if (isset($_GET['search_nom']) && !empty(trim($_GET['search_nom']))) {
    $search_nom = trim($_GET['search_nom']);
    $sql .= " WHERE nom LIKE :nom";
}

$stmt = $pdo->prepare($sql);

if (!empty($search_nom)) {
    $stmt->execute(['nom' => '%' . $search_nom . '%']);
} else {
    $stmt->execute();
}

$utilisateurs = $stmt->fetchAll();
?>