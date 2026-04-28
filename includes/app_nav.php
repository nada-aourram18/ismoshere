<?php
declare(strict_types=1);

$ismo_brand_image = is_file(__DIR__ . '/../nada.jpeg')
    ? 'nada.jpeg'
    : 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=120&h=120&fit=crop&q=70';

$role = $role ?? ($_SESSION['user_role'] ?? '');
$current_page = $current_page ?? basename($_SERVER['PHP_SELF'] ?? '');
$nav_bell_count = $nav_bell_count ?? '3';

if (!function_exists('ismo_nav_class')) {
    function ismo_nav_class(string $file): string
    {
        $cur = $GLOBALS['current_page'] ?? basename($_SERVER['PHP_SELF'] ?? '');
        $base = ($cur === $file) ? 'nav-link active' : 'nav-link';
        return 'class="' . htmlspecialchars($base, ENT_QUOTES, 'UTF-8') . '"';
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark ismo-navbar sticky-top shadow-sm">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand d-flex align-items-center gap-2" href="acceuil2.php">
            <img src="<?= htmlspecialchars($ismo_brand_image, ENT_QUOTES, 'UTF-8') ?>" alt="ISMO" width="40" height="40" class="rounded-circle" style="object-fit:cover">
            <span>ISMOShare</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#ismoMainNav" aria-controls="ismoMainNav" aria-expanded="false" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="ismoMainNav">
            <ul class="navbar-nav flex-wrap align-items-lg-center my-2 my-lg-0">
                <li class="nav-item">
                    <a <?= ismo_nav_class('acceuil2.php') ?> href="acceuil2.php">Accueil</a>
                </li>
                <?php if ($role === 'admin') : ?>
                    <li class="nav-item"><a <?= ismo_nav_class('dashbord.php') ?> href="dashbord.php">Dashboard</a></li>
                    <li class="nav-item"><a <?= ismo_nav_class('user.php') ?> href="user.php">Utilisateurs</a></li>
                    <li class="nav-item"><a <?= ismo_nav_class('validation1.php') ?> href="validation1.php">Validation inscription</a></li>
                    <li class="nav-item"><a <?= ismo_nav_class('Ressources Pedagogiques.php') ?> href="Ressources Pedagogiques.php">Ressources</a></li>
                    <li class="nav-item"><a <?= ismo_nav_class('validation_ressource.php') ?> href="validation_ressource.php">Validation ressources</a></li>
                    <li class="nav-item"><a <?= ismo_nav_class('Gestion des Annonces.php') ?> href="Gestion des Annonces.php">Gestion annonces</a></li>
                    <li class="nav-item"><a <?= ismo_nav_class('annonce.php') ?> href="annonce.php">Annonces</a></li>
                    <li class="nav-item"><a <?= ismo_nav_class('forum.php') ?> href="forum.php">Forum</a></li>
                    <li class="nav-item"><a <?= ismo_nav_class('validation_forum.php') ?> href="validation_forum.php">Validation forum</a></li>
                    <li class="nav-item"><a <?= ismo_nav_class('group.php') ?> href="group.php">Groupes</a></li>
                <?php elseif ($role === 'stagiaire') : ?>
                    <li class="nav-item"><a <?= ismo_nav_class('Ressources Pedagogiques.php') ?> href="Ressources Pedagogiques.php">Ressources</a></li>
                    <li class="nav-item"><a <?= ismo_nav_class('Gestion des Annonces.php') ?> href="Gestion des Annonces.php">Annonces</a></li>
                    <li class="nav-item"><a <?= ismo_nav_class('forum.php') ?> href="forum.php">Forum</a></li>
                    <li class="nav-item"><a <?= ismo_nav_class('group.php') ?> href="group.php">Groupes</a></li>
                    <li class="nav-item"><a <?= ismo_nav_class('Contact.php') ?> href="Contact.php">Contact</a></li>
                <?php elseif ($role === 'formateur') : ?>
                    <li class="nav-item"><a <?= ismo_nav_class('Ressources Pedagogiques.php') ?> href="Ressources Pedagogiques.php">Ressources</a></li>
                    <li class="nav-item"><a <?= ismo_nav_class('Gestion des Annonces.php') ?> href="Gestion des Annonces.php">Annonces</a></li>
                    <li class="nav-item"><a <?= ismo_nav_class('forum.php') ?> href="forum.php">Forum</a></li>
                    <li class="nav-item"><a <?= ismo_nav_class('validation_forum.php') ?> href="validation_forum.php">Validation forum</a></li>
                    <li class="nav-item"><a <?= ismo_nav_class('group.php') ?> href="group.php">Groupes</a></li>
                    <li class="nav-item"><a <?= ismo_nav_class('Contact.php') ?> href="Contact.php">Contact</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav flex-row align-items-center ms-lg-auto gap-1 ismo-nav-actions">
                <li class="nav-item">
                    <a class="nav-link text-white position-relative px-2" href="notification.php" title="Notifications">
                        <i class="fas fa-bell fs-5"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger ismo-nav-badge"><?= htmlspecialchars((string) $nav_bell_count, ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white px-2" href="profiel.php" title="Profil"><i class="fas fa-user fs-5"></i></a>
                </li>
                <li class="nav-item ps-lg-2">
                    <a class="btn btn-outline-light btn-sm" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
