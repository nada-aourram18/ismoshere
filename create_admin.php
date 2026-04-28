<?php
/**
 * Crée un compte administrateur (une fois). Ouvrez dans le navigateur puis supprimez ce fichier.
 */
declare(strict_types=1);

require __DIR__ . '/db.php';

$email = 'admin@ismoshare.local';
$plainPassword = 'Admin123!';

try {
    $check = $pdo->prepare('SELECT id_utilisateur FROM utilisateur WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "Un utilisateur avec l'email {$email} existe déjà.\n";
        exit;
    }

    $idFilier = null;
    $row = $pdo->query('SELECT id_filier FROM filiere ORDER BY id_filier ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $idFilier = (int) $row['id_filier'];
    }

    $qMat = $pdo->prepare('SELECT 1 FROM utilisateur WHERE matricule_CEF = ? LIMIT 1');
    $qTel = $pdo->prepare('SELECT 1 FROM utilisateur WHERE telephon = ? LIMIT 1');

    $matricule = '';
    for ($i = 0; $i < 100; $i++) {
        $candidate = 'A' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        $qMat->execute([$candidate]);
        if (!$qMat->fetchColumn()) {
            $matricule = $candidate;
            break;
        }
    }
    if ($matricule === '') {
        throw new RuntimeException('Impossible de générer un matricule CEF unique.');
    }

    $telephon = '';
    for ($i = 0; $i < 100; $i++) {
        $candidate = '06' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $qTel->execute([$candidate]);
        if (!$qTel->fetchColumn()) {
            $telephon = $candidate;
            break;
        }
    }
    if ($telephon === '') {
        throw new RuntimeException('Impossible de générer un numéro de téléphone unique.');
    }

    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $today = date('Y-m-d');

    $sql = 'INSERT INTO utilisateur
        (nom, prenom, email, telephon, matricule_CEF, role, id_filier, date_inscription, photo_profil, mot_de_passe, statut)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'Admin',
        'Système',
        $email,
        $telephon,
        $matricule,
        'admin',
        $idFilier,
        $today,
        'default-user.png',
        $hash,
        'valide',
    ]);

    header('Content-Type: text/plain; charset=utf-8');
    echo "Compte admin créé.\n";
    echo "Email : {$email}\n";
    echo "Mot de passe : {$plainPassword}\n";
    echo "Supprimez create_admin.php après utilisation.\n";
} catch (PDOException $e) {
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(500);
    echo 'Erreur : ' . $e->getMessage();
} catch (RuntimeException $e) {
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(500);
    echo 'Erreur : ' . $e->getMessage();
}
