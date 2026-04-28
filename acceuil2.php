<?php
// Démarrage de session et vérification d'authentification
session_start();

$roles_autorises = ['admin', 'formateur', 'stagiaire'];
if (!isset($_SESSION['user_id'], $_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles_autorises, true)) {
    header('Location: login.php?msg=' . rawurlencode('Veuillez vous connecter.'));
    exit;
}

// Récupération des données de session
$role = $_SESSION['user_role'] ?? '';
$user_name = $_SESSION['user_name'] ?? 'Utilisateur';
$current_page = basename($_SERVER['PHP_SELF']);

/* Photo étudiants uniquement : fichiers locaux assets/etudiants.* ou accueil-hero.* ; sinon photo groupe d’étudiants (Unsplash) — pas nada.jpeg (logo). */
$hero_students_fallback = 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=82';
$heroCandidates = [
    __DIR__ . '/assets/etudiants.jpg'    => 'assets/etudiants.jpg',
    __DIR__ . '/assets/etudiants.png'    => 'assets/etudiants.png',
    __DIR__ . '/assets/etudiants.webp'   => 'assets/etudiants.webp',
    __DIR__ . '/assets/accueil-hero.png' => 'assets/accueil-hero.png',
    __DIR__ . '/assets/accueil-hero.jpg' => 'assets/accueil-hero.jpg',
];
$hero_image_src = $hero_students_fallback;
foreach ($heroCandidates as $abs => $url) {
    if (is_file($abs)) {
        $hero_image_src = $url;
        break;
    }
}

$page_title = 'ISMOShare - Plateforme collaborative';
$extra_head = <<<'HTML'
<style>
        :root { --ismo-green: #0f766e; }
        .hero-section {
            position: relative;
            overflow: hidden;
            border-top: 4px solid var(--ismo-green);
            color: #fff;
            padding: 4rem 0 4.5rem;
            min-height: min(70vh, 640px);
            display: flex;
            align-items: center;
        }
        .hero-bg-layer {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
        }
        /* Voile « chafaf » : la photo remplit tout l’espace et reste visible à travers */
        .hero-veil {
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(
                165deg,
                rgba(45, 52, 62, 0.78) 0%,
                rgba(45, 52, 62, 0.48) 42%,
                rgba(15, 118, 110, 0.36) 100%
            );
        }
        .hero-section-inner {
            position: relative;
            z-index: 2;
            text-shadow: 0 2px 14px rgba(0, 0, 0, 0.4);
        }
        .welcome-card {
            border-left: 4px solid var(--ismo-green);
            border-radius: 12px;
            transition: transform 0.3s ease;
        }
        .welcome-card:hover { transform: translateY(-4px); }
        .feature-icon { font-size: 2.5rem; color: var(--ismo-green); margin-bottom: 15px; }
        .feature-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }
        .btn-ismo { background-color: var(--ismo-green); color: white; border-radius: 8px; }
        .btn-ismo:hover { background-color: #0d5d56; color: white; }
</style>
HTML;
include __DIR__ . '/includes/layout_head.php';
include __DIR__ . '/includes/app_nav.php';
?>

    <!-- Hero : photo plein écran sur toute la zone + voile chafaf ; texte par-dessus -->
    <section class="hero-section text-center" aria-label="Accueil ISMOShare">
        <div class="hero-bg-layer" style="background-image: url('<?= htmlspecialchars($hero_image_src, ENT_QUOTES, 'UTF-8') ?>');"></div>
        <div class="hero-veil" aria-hidden="true"></div>
        <div class="container hero-section-inner">
            <h1 class="display-3 fw-bold mb-3">Bienvenue sur ISMOShare</h1>
            <p class="lead fs-3 mb-0">Votre plateforme collaborative dédiée à la réussite</p>
        </div>
    </section>

    <!-- Message de bienvenue -->
    <div class="container my-5 ismo-main">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card welcome-card mb-5">
                    <div class="card-body p-4 p-lg-5">
                        <h2 class="card-title text-success mb-4">
                            <i class="bi bi-chat-square-heart"></i> Message de bienvenue
                        </h2>
                        <p class="fs-5">
                            Chers membres de l'ISMO Tétouan,<br><br>
                            Nous sommes ravis de vous accueillir sur <strong>ISMOShare</strong>, la plateforme
                            collaborative conçue spécialement pour faciliter votre parcours de formation. Cet espace
                            vous permet d'accéder à toutes les ressources pédagogiques, d'échanger avec vos pairs et
                            formateurs, et de rester informé des dernières actualités de l'institut.
                        </p>
                        <p class="fs-5">
                            Ensemble, partageons les connaissances et construisons une communauté d'entraide pour
                            exceller dans nos formations professionnelles.
                        </p>
                        <div class="text-end mt-4">
                            <p class="fst-italic mb-0">L'équipe ISMOShare</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fonctionnalités principales -->
    <div class="container mb-5">
        <h2 class="text-center mb-5">Découvrez nos fonctionnalités</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 feature-card">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon">
                            <i class="bi bi-book"></i>
                        </div>
                        <h3 class="h4">Ressources Pédagogiques</h3>
                        <p class="text-muted">
                            Accédez à l'ensemble des cours, travaux pratiques et examens corrigés, classés par filière
                            et module.
                        </p>
                        <a href="" class="btn btn-ismo mt-3">Explorer</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 feature-card">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h3 class="h4">Espace Collaboratif</h3>
                        <p class="text-muted">
                            Posez vos questions, partagez vos connaissances et bénéficiez de l'expérience de toute la
                            communauté.
                        </p>
                        <a href="" class="btn btn-ismo mt-3">Participer</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 feature-card">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon">
                            <i class="bi bi-bell"></i>
                        </div>
                        <h3 class="h4">Annonces & Actualités</h3>
                        <p class="text-muted">
                            Restez informé des opportunités, événements et informations importantes de l'ISMO.
                        </p>
                        <a href="" class="btn btn-ismo mt-3">Voir les annonces</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Call to Action -->

    <!-- Pied de page -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <h5><i class="bi bi-share"></i> ISMOShare</h5>
                    <p>Plateforme collaborative des stagiaires de l'ISMO Tétouan</p>
                    <img src="https://ismo.ma/wp-content/uploads/2022/05/cropped-logo-ismo-1-32x32.png" alt="Logo ISMO"
                        width="60">
                </div>
                <div class="col-md-3 mb-3">
                    <h5>Liens utiles</h5>
                    <ul class="list-unstyled">
                        <li><a href="acceuil2.php" class="text-white">Accueil</a></li>
                        <li><a href="Ressources Pedagogiques.php" class="text-white">Ressources</a></li>
                        <li><a href="forum.php" class="text-white">Forum</a></li>
                        <li><a href="https://ismo.ma/" class="text-white" target="_blank">Site ISMO</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-3">
                    <h5>Contact</h5>
                    <p><i class="bi bi-envelope"></i> contact@ismoshare.ma</p>
                    <p><i class="bi bi-telephone"></i> +212 539 999 999</p>
                    <p><i class="bi bi-geo-alt"></i> ISMO Tétouan, Maroc</p>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; <?= date('Y') ?> ISMOShare - Tous droits réservés</p>
            </div>
        </div>
    </footer>

<?php include __DIR__ . '/includes/layout_foot.php'; ?>