<?php
include("db.php");

if (isset($_GET["id_utilisateur"]) && !empty($_GET["id_utilisateur"])) {
    $id = $_GET["id_utilisateur"];
    
    try {
        $quer = $pdo->prepare("DELETE FROM utilisateur WHERE id_utilisateur = ?");
        $result = $quer->execute([$id]);

        if ($result) {
            header("Location: user.php?msg=Utilisateur bien supprimé");
            exit;
        } else {
            header("Location: user.php?msgr=Erreur lors de la suppression");
            exit;
        }
    } catch (PDOException $e) {
        header("Location: user.php?msgr=" . urlencode("Erreur PDO : " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: user.php?msgr=Paramètre manquant");
    exit;
}
?>
