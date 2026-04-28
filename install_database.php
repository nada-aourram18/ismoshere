<?php
/**
 * Recrée la base ismoshere (suppression + tables + filières + compte admin).
 * À lancer UNIQUEMENT en ligne de commande :
 *   php install_database.php
 *
 * Identifiants : alignez sur db.php ($host, $user, $pass, $db).
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Exécution réservée au CLI : php install_database.php\n";
    exit(1);
}

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '123456';
$DB_NAME = 'ismoshere';

$schemaFile = __DIR__ . '/database/schema.sql';
if (!is_readable($schemaFile)) {
    fwrite(STDERR, "Fichier introuvable : {$schemaFile}\n");
    exit(1);
}

$sql = file_get_contents($schemaFile);
if ($sql === false) {
    fwrite(STDERR, "Impossible de lire le schéma.\n");
    exit(1);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS);
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    fwrite(STDERR, 'Connexion MySQL : ' . $e->getMessage() . "\n");
    exit(1);
}

try {
    if (!$mysqli->multi_query($sql)) {
        throw new mysqli_sql_exception($mysqli->error, $mysqli->errno);
    }
    do {
        if ($res = $mysqli->store_result()) {
            $res->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
} catch (mysqli_sql_exception $e) {
    fwrite(STDERR, 'Erreur SQL : ' . $e->getMessage() . "\n");
    exit(1);
}

$mysqli->select_db($DB_NAME);

$hash = password_hash('Admin123!', PASSWORD_DEFAULT);
$stmt = $mysqli->prepare(
    'INSERT INTO utilisateur (nom, prenom, email, telephon, matricule_CEF, `role`, id_filier, date_inscription, photo_profil, mot_de_passe, statut) '
    . 'VALUES (?, ?, ?, ?, ?, ?, 1, CURDATE(), ?, ?, ?)'
);
$nom = 'Admin';
$prenom = 'Système';
$email = 'admin@ismoshare.local';
$tel = '0699988776';
$mat = 'A998877661';
$role = 'admin';
$photo = 'default-user.png';
$statut = 'valide';
$stmt->bind_param('sssssssss', $nom, $prenom, $email, $tel, $mat, $role, $photo, $hash, $statut);
$stmt->execute();
$stmt->close();
$mysqli->close();

echo "Base «{$DB_NAME}» recréée.\n";
echo "Compte admin — email : {$email} | mot de passe : Admin123!\n";
echo "Modifiez le mot de passe après la première connexion.\n";
