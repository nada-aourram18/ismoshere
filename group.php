<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['user_role'] ?? 'user';

$group_id = (int) ($_REQUEST['group_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $group_id > 0) {
    $msg = trim($_POST['message']);
    if ($msg !== '') {
        $chk = $pdo->prepare('SELECT user1, user2 FROM groupe WHERE id_groupe = ?');
        $chk->execute([$group_id]);
        $gchk = $chk->fetch(PDO::FETCH_ASSOC);
        $allowed = $gchk && (
            $role === 'admin'
            || (int) $gchk['user1'] === (int) $user_id
            || (int) $gchk['user2'] === (int) $user_id
        );
        if ($allowed) {
            $stmt = $pdo->prepare('INSERT INTO messager (id_groupe, id_utilisateur, contenu, created_at) VALUES (?, ?, ?, NOW())');
            $stmt->execute([$group_id, $user_id, $msg]);
            header('Location: group.php?group_id=' . $group_id);
            exit();
        }
    }
}

// GROUPES - admin voit tout, autres voient seulement leurs groupes
if ($role === 'admin') {
    $stmt = $pdo->query("
        SELECT g.id_groupe, 
               u1.nom AS user1_nom, u1.prenom AS user1_prenom, 
               u2.nom AS user2_nom, u2.prenom AS user2_prenom
        FROM groupe g
        LEFT JOIN utilisateur u1 ON g.user1 = u1.id_utilisateur
        LEFT JOIN utilisateur u2 ON g.user2 = u2.id_utilisateur
        ORDER BY g.id_groupe ASC
    ");
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("
        SELECT g.id_groupe, 
               u1.nom AS user1_nom, u1.prenom AS user1_prenom, 
               u2.nom AS user2_nom, u2.prenom AS user2_prenom
        FROM groupe g
        LEFT JOIN utilisateur u1 ON g.user1 = u1.id_utilisateur
        LEFT JOIN utilisateur u2 ON g.user2 = u2.id_utilisateur
        WHERE g.user1 = ? OR g.user2 = ?
        ORDER BY g.id_groupe ASC
    ");
    $stmt->execute([$user_id, $user_id]);
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$group_name = '';
$group = null;
if ($group_id > 0) {
    $stmt = $pdo->prepare("SELECT id_groupe, user1, user2 FROM groupe WHERE id_groupe = ?");
    $stmt->execute([$group_id]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$group) {
        $group_id = 0;
    } elseif ($role !== 'admin' && (int) $group['user1'] !== (int) $user_id && (int) $group['user2'] !== (int) $user_id) {
        $group_id = 0;
        $group = null;
    } elseif ($group) {
        $stmt1 = $pdo->prepare("SELECT nom, prenom FROM utilisateur WHERE id_utilisateur = ?");
        $stmt2 = $pdo->prepare("SELECT nom, prenom FROM utilisateur WHERE id_utilisateur = ?");
        $stmt1->execute([$group['user1']]);
        $user1 = $stmt1->fetch(PDO::FETCH_ASSOC);
        $stmt2->execute([$group['user2']]);
        $user2 = $stmt2->fetch(PDO::FETCH_ASSOC);

        $group_name = "Conversation entre " . htmlspecialchars($user1['prenom'] . ' ' . $user1['nom']) . " و " . htmlspecialchars($user2['prenom'] . ' ' . $user2['nom']);
    }
}

// Récupération des messages
$messages = [];
if ($group_id > 0) {
    $stmt = $pdo->prepare("
        SELECT m.id_msg, m.contenu, m.created_at, m.id_utilisateur, u.prenom, u.nom, u.photo_profil, u.role AS sender_role
        FROM messager m
        INNER JOIN utilisateur u ON m.id_utilisateur = u.id_utilisateur
        WHERE m.id_groupe = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$group_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$page_title = 'Messagerie - ISMOShare';
$current_page = basename(__FILE__);
$extra_head = <<<'HTML'
<style>
        :root { --ismo-blue: #0f766e; --primary: #0f766e; --sidebar-width: 280px; }
        .messaging-container {
            display: flex;
            height: calc(100vh - 58px);
        }
        .sidebar {
            width: var(--sidebar-width);
            background-color: white;
            border-right: 1px solid #dee2e6;
            overflow-y: auto;
        }
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            background-color: var(--primary);
            color: white;
            padding: 1rem;
        }
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            background-color: #f0fdf4;
            display: flex;
            flex-direction: column;
        }
        .message-input {
            padding: 1rem;
            border-top: 1px solid #dee2e6;
            background-color: white;
        }
        .message {
            max-width: 70%;
            margin-bottom: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            word-wrap: break-word;
        }
        .received {
            background-color: white;
            align-self: flex-start;
        }
        .sent {
            background-color: #d1fae5;
            align-self: flex-end;
        }
        .message-time {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.5rem;
            text-align: right;
        }
        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            cursor: pointer;
        }
        .conversation-item:hover {
            background-color: #f8f9fa;
        }
        .conversation-item.active {
            background-color: #e9ecef;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
HTML;
include __DIR__ . '/includes/layout_head.php';
include __DIR__ . '/includes/app_nav.php';
?>

    <div class="messaging-container">
        <div class="sidebar">
            <div class="p-3 border-bottom">
                <h5>Conversations</h5>
                <form method="GET" action="group.php" class="mb-3">
                    <input type="text" name="search" placeholder="Rechercher..." class="form-control" />
                </form>
            </div>
            <div class="conversation-list">
                <?php foreach ($groups as $g): 
                    $user1_fullname = htmlspecialchars($g['user1_prenom'] . ' ' . $g['user1_nom']);
                    $user2_fullname = htmlspecialchars($g['user2_prenom'] . ' ' . $g['user2_nom']);
                ?>
                    <a href="group.php?group_id=<?= $g['id_groupe'] ?>" class="conversation-item <?= ((int)$g['id_groupe'] === (int)$group_id) ? 'active' : '' ?>">
                        <div class="d-flex align-items-center">
                            <span class="user-avatar me-3 d-inline-flex align-items-center justify-content-center bg-secondary text-white small" style="min-width:40px;min-height:40px;border-radius:50%;"><?= strtoupper(substr($g['user1_prenom'] ?? '', 0, 1) . substr($g['user2_prenom'] ?? '', 0, 1)) ?></span>
                            <div class="flex-grow-1">
                                <h6 class="mb-0"><?= $user1_fullname . ' & ' . $user2_fullname ?></h6>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="chat-area">
            <div class="chat-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?= htmlspecialchars($group_name ?: "Sélectionnez une conversation") ?></h5>
            </div>

            <div class="messages-container" id="messagesContainer">
                <?php if ($group_id > 0 && $messages): ?>
                    <?php foreach ($messages as $msg): 
                        $sender_name = htmlspecialchars($msg['prenom'] . ' ' . $msg['nom']);
                        $rawPhoto = trim((string) ($msg['photo_profil'] ?? ''));
                        if ($rawPhoto !== '' && preg_match('#^https?://#i', $rawPhoto)) {
                            $avatar = htmlspecialchars($rawPhoto);
                        } elseif ($rawPhoto !== '') {
                            $avatar = 'images/' . htmlspecialchars($rawPhoto);
                        } else {
                            $avatar = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><rect fill="#e2e8f0" width="40" height="40" rx="20"/><text x="20" y="25" text-anchor="middle" font-size="14" fill="#0f766e">?</text></svg>');
                        }
                        $isSent = ($msg['id_utilisateur'] == $user_id);
                        $class = $isSent ? 'sent' : 'received';
                        $senderRole = $msg['sender_role'] ?? '';
                        $canDeleteMsg = false;
                        if ($role === 'admin') {
                            $canDeleteMsg = true;
                        } elseif ($role === 'formateur' && $senderRole === 'stagiaire') {
                            $canDeleteMsg = true;
                        } elseif ($role === 'stagiaire' && (int) $msg['id_utilisateur'] === (int) $user_id) {
                            $canDeleteMsg = true;
                        }
                    ?>
                        <div class="message <?= $class ?>">
                            <?php if ($class === 'received'): ?>
                                <div class="d-flex align-items-center mb-2">
                                    <img src="<?= $avatar ?>" class="user-avatar me-2" alt="avatar" />
                                    <strong><?= $sender_name ?></strong>
                                </div>
                            <?php endif; ?>
                            <p><?= nl2br(htmlspecialchars($msg['contenu'])) ?></p>
                            <div class="message-time d-flex justify-content-between align-items-center">
                                <span><?= date("H:i", strtotime($msg['created_at'])) ?></span>
                                <?php if ($canDeleteMsg): ?>
                                    <form method="POST" action="delete_message.php" class="ms-2">
                                        <input type="hidden" name="id_msg" value="<?= (int) $msg['id_msg'] ?>">
                                        <input type="hidden" name="group_id" value="<?= (int) $group_id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce message ?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-muted mt-4">Aucun message à afficher</p>
                <?php endif; ?>
            </div>

            <?php if ($group_id > 0): ?>
            <form method="POST" class="message-input d-flex" action="group.php?group_id=<?= (int) $group_id ?>" onsubmit="return validateForm()">
                <input type="hidden" name="group_id" value="<?= (int) $group_id ?>">
                <input type="text" name="message" id="messageInput" class="form-control me-2" placeholder="Écrivez un message..." autocomplete="off" required />
                <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane"></i></button>
            </form>
            <?php else: ?>
                <div class="p-3 text-center text-muted">Veuillez sélectionner une conversation pour envoyer un message.</div>
            <?php endif; ?>
        </div>
    </div>

<?php ob_start(); ?>
    <script>
        function validateForm() {
            const input = document.getElementById('messageInput');
            return input.value.trim() !== '';
        }
        const messagesContainer = document.getElementById('messagesContainer');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    </script>
<?php
$extra_scripts = ob_get_clean();
include __DIR__ . '/includes/layout_foot.php';
