<?php
include 'db.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    
    $stmt = $pdo->prepare("SELECT image FROM annonce WHERE id_annonce = ?");
    $stmt->execute([$id]);
    $annonce = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($annonce) {
        
        if (!empty($annonce['image']) && file_exists($annonce['image'])) {
            unlink($annonce['image']);
        }

     
        $stmt = $pdo->prepare("DELETE FROM annonce WHERE id_annonce = ?");
        $stmt->execute([$id]);
    }

    header("Location: Gestion des Annonces.php");
    exit;
} else {
    echo "ID de l'annonce manquant.";
}
