<?php
session_start();
include 'db.php';

$roles = ['admin', 'formateur', 'stagiaire'];
if (!isset($_SESSION['user_id'], $_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles, true)) {
    header('Location: login.php?msg=' . rawurlencode('Veuillez vous connecter.'));
    exit;
}

$userRole = $_SESSION['user_role'];
$role = $userRole;
$userId = (string) ($_SESSION['id_utilisateur'] ?? $_SESSION['user_id']);

$error = '';
$migration_needed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['creer_sujet'])) {
    $titre = trim((string) ($_POST['sujet'] ?? ''));
    $contenu = trim((string) ($_POST['contenu'] ?? ''));
    $categorie = trim((string) ($_POST['categorie'] ?? 'Général'));
    if ($titre === '' || $contenu === '') {
        $error = 'Veuillez remplir le titre et le contenu.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO sujet (titre, contenu, categorie, id_utilisateur, date_creation, statut_validation) VALUES (?, ?, ?, ?, CURDATE(), \'en_attente\')'
            );
            $stmt->execute([$titre, $contenu, $categorie !== '' ? $categorie : 'Général', (int) $_SESSION['user_id']]);
            header('Location: forum.php?created_pending=1');
            exit;
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'statut_validation') !== false || stripos($msg, 'Unknown column') !== false) {
                $migration_needed = true;
                $error = 'Exécutez le script SQL database/migration_forum_validation.sql sur votre base MySQL.';
            } else {
                $error = 'Erreur lors de la création du sujet.';
            }
        }
    }
}

$search = trim((string) ($_GET['search'] ?? ''));
$forums = [];
try {
    $sql = 'SELECT s.id_sujet, s.titre, s.contenu, s.categorie, s.date_creation, u.nom, u.prenom,
        (SELECT COUNT(*) FROM reponse r WHERE r.id_sujet = s.id_sujet) AS nb_rep,
        (SELECT COUNT(*) FROM like_sujet l WHERE l.id_sujet = s.id_sujet) AS nb_like
        FROM sujet s
        JOIN utilisateur u ON s.id_utilisateur = u.id_utilisateur
        WHERE s.statut_validation = \'accepte\'';
    $params = [];
    if ($search !== '') {
        $sql .= ' AND (s.titre LIKE ? OR s.contenu LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    $sql .= ' ORDER BY s.date_creation DESC, s.id_sujet DESC';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $forums = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $msg = $e->getMessage();
    if (stripos($msg, 'statut_validation') !== false || stripos($msg, 'Unknown column') !== false) {
        $migration_needed = true;
    }
    $forums = [];
}

$page_title = 'Forum - ISMOShare';
$current_page = basename(__FILE__);
$extra_head = <<<'HTML'
<style>
        :root {
            --primary-color: #0f766e;
            --secondary-color: #0d5e5b;
            --light-color: #f8f9fa;
            --dark-color: #0f766e;
        }
        
        .forum-page-wrap {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .bg-ismo {
            background-color: var(--dark-color) !important;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px 0;
        }
        
        .forum-title {
            color: var(--primary-color);
            font-size: 2.2em;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .search-bar {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            max-width: 1000px;
            margin: 0 auto 30px;
        }
        
        .search-bar input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            border-right: none;
        }
        
        .search-bar button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0 15px;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        
        .forum-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .forum-item {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .forum-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .forum-subject {
            color: var(--primary-color);
            font-size: 1.3em;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .forum-meta {
            display: flex;
            justify-content: space-between;
            color: #7f8c8d;
            font-size: 0.9em;
            margin-bottom: 12px;
        }
        
        .forum-excerpt {
            color: #34495e;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        
        .forum-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .forum-stats {
            display: flex;
            font-size: 0.9em;
        }
        
        .stat {
            margin-right: 20px;
            color: #7f8c8d;
            display: flex;
            align-items: center;
        }
        
        .stat i {
            margin-right: 5px;
        }
        
        .like-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            color: #7f8c8d;
            transition: all 0.2s;
        }
        
        .like-btn:hover {
            color: var(--primary-color);
            transform: scale(1.1);
        }
        
        .like-btn.liked {
            color: var(--primary-color);
        }
        
        .like-count {
            margin-left: 5px;
        }
        
        .action-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.2s;
            margin-left: 10px;
        }
        
        .action-btn:hover {
            background-color: var(--secondary-color);
        }
        
        .action-btn i {
            margin-right: 5px;
        }
        
        .action-btn.delete {
            background-color: #dc3545;
        }
        
        .action-btn.delete:hover {
            background-color: #c82333;
        }
        
        .moderator-actions {
            display: flex;
        }
        
        .create-forum-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: 500;
            margin-bottom: 20px;
            display: inline-block;
            text-decoration: none;
        }
        
        .create-forum-btn:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        
        /* Modal styles */
        .modal-content {
            border-radius: 10px;
        }
        
        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-bottom: none;
        }
        
        .modal-footer {
            border-top: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
    </style>
HTML;
include __DIR__ . '/includes/layout_head.php';
include __DIR__ . '/includes/app_nav.php';
?>

    <!-- Contenu principal -->
    <div class="forum-container forum-page-wrap ismo-main">
        <div class="header">
            <h1 class="forum-title">Forums ISMO SHARE</h1>
            <p>Discussions et échanges entre étudiants</p>
            <button type="button" class="create-forum-btn" data-bs-toggle="modal" data-bs-target="#createForumModal">
                <i class="fas fa-plus-circle"></i> Créer un nouveau sujet
            </button>
            
            <?php if (!empty($_GET['created_pending'])) : ?>
                <div class="alert alert-info mt-3">
                    Votre sujet a été envoyé. Il apparaîtra dans la liste du forum après <strong>validation</strong> par un administrateur ou un formateur.
                    <?php if ($role === 'admin' || $role === 'formateur') : ?>
                        <a href="validation_forum.php" class="alert-link">Aller à la validation forum</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($_GET['msg'])) : ?>
                <div class="alert alert-warning mt-3"><?= htmlspecialchars((string) $_GET['msg']) ?></div>
            <?php endif; ?>

            <?php if ($migration_needed) : ?>
                <div class="alert alert-danger mt-3">
                    Ajoutez la colonne de validation : exécutez <code>database/migration_forum_validation.sql</code> dans phpMyAdmin.
                </div>
            <?php endif; ?>

            <?php if ($error !== '') : ?>
                <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
        </div>

        <!-- Barre de recherche -->
        <form method="GET" action="forum.php" class="search-bar">
            <input type="text" name="search" placeholder="Rechercher dans les forums..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>

        <!-- Liste des forums (sujets validés uniquement) -->
        <?php if (empty($forums) && !$migration_needed) : ?>
            <p class="text-center text-muted py-5">Aucun sujet pour le moment. Créez le premier ou modifiez votre recherche.</p>
        <?php endif; ?>

        <?php foreach ($forums as $f) :
            $plain = preg_replace('/\s+/', ' ', strip_tags((string) $f['contenu']));
            $excerpt = function_exists('mb_substr')
                ? mb_substr($plain, 0, 220)
                : substr($plain, 0, 220);
            $long = function_exists('mb_strlen') ? mb_strlen((string) $f['contenu']) > 220 : strlen((string) $f['contenu']) > 220;
            ?>
            <div class="forum-item">
                <div class="forum-subject"><?= htmlspecialchars((string) $f['titre']) ?></div>
                <div class="forum-meta">
                    <span><?= htmlspecialchars($f['prenom'] . ' ' . $f['nom']) ?></span>
                    <span><?= htmlspecialchars((string) $f['date_creation']) ?> · <?= htmlspecialchars((string) $f['categorie']) ?></span>
                </div>
                <div class="forum-excerpt"><?= htmlspecialchars($excerpt) ?><?= $long ? '…' : '' ?></div>
                <div class="forum-actions">
                    <div class="forum-stats">
                        <span class="stat"><i class="fas fa-comments"></i> <?= (int) $f['nb_rep'] ?></span>
                        <span class="stat"><i class="fas fa-heart"></i> <?= (int) $f['nb_like'] ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="voir_sujet.php?id=<?= (int) $f['id_sujet'] ?>" class="action-btn"><i class="fas fa-eye"></i> Ouvrir</a>
                        <?php if ($role === 'admin') : ?>
                            <button type="button" class="action-btn delete" onclick="confirmDelete(<?= (int) $f['id_sujet'] ?>)"><i class="fas fa-trash"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    </div>

    <!-- Modal de création de sujet -->
    <div class="modal fade" id="createForumModal" tabindex="-1" aria-labelledby="createForumModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="forum.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createForumModalLabel">Créer un nouveau sujet</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="sujet" class="form-label">Titre du sujet</label>
                            <input type="text" class="form-control" id="sujet" name="sujet" required>
                        </div>
                        <div class="mb-3">
                            <label for="categorie" class="form-label">Catégorie</label>
                            <select class="form-select" id="categorie" name="categorie" required>
                                <option value="Général">Général</option>
                                <option value="Entraide">Entraide</option>
                                <option value="Questions">Questions</option>
                                <option value="Astuce">Astuce</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="contenu" class="form-label">Contenu</label>
                            <textarea class="form-control" id="contenu" name="contenu" rows="5" required></textarea>
                        </div>
                        <p class="small text-muted mb-0">Après envoi, un administrateur ou un formateur devra valider le sujet avant qu’il n’apparaisse ici.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="creer_sujet" class="btn btn-primary">Créer le sujet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php ob_start(); ?>
    <script>
        // Fonction pour gérer les likes
        function toggleLike(button, forumId) {
            const likeCount = button.querySelector('.like-count');
            let count = parseInt(likeCount.textContent);
            
            fetch('forum_vote.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ forum_id: forumId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (button.classList.contains('liked')) {
                        button.classList.remove('liked');
                        count--;
                    } else {
                        button.classList.add('liked');
                        count++;
                    }
                    likeCount.textContent = count;
                } else {
                    alert(data.message || 'Erreur lors du vote');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erreur de connexion au serveur');
            });
        }

        // Fonction de confirmation de suppression
        function confirmDelete(forumId) {
            if (confirm('Voulez-vous vraiment supprimer ce sujet ? Cette action est irréversible.')) {
                window.location.href = 'delete_forum.php?id=' + forumId;
            }
        }

        // Initialisation des likes déjà effectués
        document.addEventListener('DOMContentLoaded', function() {
            // Ici vous pourriez ajouter une requête AJAX pour vérifier
            // quels sujets l'utilisateur a déjà liké et ajouter la classe 'liked'
        });
    </script>
<?php
$extra_scripts = ob_get_clean();
include __DIR__ . '/includes/layout_foot.php';