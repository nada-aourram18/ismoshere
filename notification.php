<?php
session_start();
require_once 'db.php'; 

$roles_ok = ['admin', 'formateur', 'stagiaire'];
if (!isset($_SESSION['user_id'], $_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles_ok, true)) {
    header('Location: login.php?msg=' . rawurlencode('Veuillez vous connecter.'));
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];
$role = $_SESSION['user_role']; // For the navbar

$sql = "SELECT * FROM notification WHERE id_utilisateur = :userId AND visible = 1 ORDER BY id_notification DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['userId' => $userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unreadCount = 0;
foreach ($notifications as $notif) {
    if ($notif['statut'] === 'unread') {
        $unreadCount++;
    }
}
$totalCount = count($notifications);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Notifications - ISMOShare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="assets/ismo-app.css" />
    <style>
        :root {
            --ismo-blue: #0f766e;
            --ismo-green: #0f766e;
            --primary-color: #0f766e;
            --dark-color: #0f766e;
            --light-color: #f8f9fa;
            --danger-color: #dc3545;
            --sidebar-bg: #0f766e;
            --sidebar-text: rgba(255, 255, 255, 0.8);
            --sidebar-hover: rgba(255, 255, 255, 0.1);
            --sidebar-active: #ffffff;
        }
        body.ismo-body.notification-layout {
            min-height: 100vh;
        }
        .main-content {
            margin-left: auto;
            margin-right: auto;
            max-width: 920px;
            padding: 1.5rem 1rem 2.5rem;
            width: 100%;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .page-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }
        .page-title i {
            color: var(--primary-color);
            margin-right: 15px;
        }
        .notification-panel {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .notification-tabs {
            display: flex;
            border-bottom: 1px solid #eee;
        }
        .notification-tab {
            padding: 15px 20px;
            cursor: pointer;
            font-weight: 500;
            position: relative;
            transition: all 0.3s;
        }
        .notification-tab:hover {
            background-color: #f9f9f9;
        }
        .notification-tab.active {
            color: var(--primary-color);
        }
        .notification-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--primary-color);
        }
        .notification-tab-badge {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 0.75rem;
            margin-left: 8px;
        }
        .notification-list {
            max-height: 70vh;
            overflow-y: auto;
        }
        .notification-item {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            transition: all 0.3s;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .notification-item.unread {
            background-color: rgba(15, 118, 110, 0.05);
        }
        .notification-item:hover {
            background-color: #f9f9f9;
        }
        .notification-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            flex-shrink: 0;
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        .notification-content {
            flex: 1;
            min-width: 0;
        }
        .notification-title {
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }
        .badge-notification {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px;
            padding: 3px 8px;
            font-size: 0.75rem;
            margin-left: 10px;
        }
        .notification-message {
            color: #555;
            margin-bottom: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .notification-meta {
            display: flex;
            justify-content: space-between;
            color: #6c757d;
            font-size: 0.85rem;
        }
        .notification-time {
            display: flex;
            align-items: center;
        }
        .notification-time i {
            margin-right: 5px;
        }
        .notification-actions {
            display: flex;
            gap: 10px;
        }
        .btn-notification {
            padding: 5px 10px;
            font-size: 0.8rem;
            border-radius: 5px;
            cursor: pointer;
            border: none;
        }
        .btn-notification-view {
            background-color: rgba(15, 118, 110, 0.1);
            color: var(--primary-color);
        }
        .btn-notification-view:hover {
            background-color: rgba(15, 118, 110, 0.2);
        }
        .btn-notification-dismiss {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }
        .btn-notification-dismiss:hover {
            background-color: rgba(231, 76, 60, 0.2);
        }
        .notification-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f9f9f9;
        }
        .mark-all-read {
            font-size: 0.9rem;
        }
        .mark-all-read a {
            color: var(--primary-color);
            text-decoration: none;
        }
        .mark-all-read a:hover {
            text-decoration: underline;
        }
        .user-profile {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            color: white;
            font-weight: bold;
        }
        .user-info {
            flex: 1;
        }
        .user-name {
            font-weight: 500;
            margin-bottom: 2px;
        }
        .user-role {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.6rem;
        }
        .empty-state {
            color: #6c757d;
        }
    </style>
</head>
<body class="ismo-body notification-layout">

<?php
$current_page = basename(__FILE__);
$nav_bell_count = (string) $unreadCount;
include __DIR__ . '/includes/app_nav.php';
?>

    <div class="main-content">
        <div class="header">
            <h1 class="page-title">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </h1>
        </div>

        <div class="notification-panel">
            <div class="notification-tabs">
                <div class="notification-tab active" id="tab-all">
                    Toutes <span class="notification-tab-badge" id="badge-all"><?= $totalCount ?></span>
                </div>
                <div class="notification-tab" id="tab-unread">
                    Non lues <span class="notification-tab-badge" id="badge-unread"><?= $unreadCount ?></span>
                </div>
            </div>

            <div class="notification-list" id="notification-list">
                <?php if ($totalCount > 0): ?>
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notification-item <?= ($notif['statut'] === 'unread') ? 'unread' : '' ?>" data-status="<?= $notif['statut'] ?>" data-id="<?= $notif['id_notification'] ?>">
                            <div class="notification-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title">
                                    Notification #<?= htmlspecialchars($notif['id_notification']) ?>
                                    <?php if ($notif['statut'] === 'unread'): ?>
                                        <span class="badge-notification">Nouveau</span>
                                    <?php endif; ?>
                                </div>
                                <div class="notification-message" title="<?= htmlspecialchars($notif['message']) ?>">
                                    <?= htmlspecialchars($notif['message']) ?>
                                </div>
                                <div class="notification-meta">
                                    <span class="notification-time">
                                        <i class="far fa-clock"></i> <?= date('d/m/Y H:i:s', strtotime($notif['date_notification'])) ?>
                                    </span>
                                    <div class="notification-actions">
                                        <button class="btn-notification btn-notification-view" type="button">
                                            <i class="fas fa-eye"></i> Voir
                                        </button>
                                        <button class="btn-notification btn-notification-dismiss" type="button">
                                            <i class="fas fa-times"></i> Masquer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state text-center p-5">
                        <i class="fas fa-bell-slash fa-3x mb-3"></i>
                        <h4>Aucune notification pour le moment</h4>
                    </div>
                <?php endif; ?>
            </div>

            <div class="notification-footer">
                <div class="mark-all-read">
                    <a href="#" id="mark-all-read">Marquer toutes comme lues</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.btn-notification-view').forEach(button => {
            button.addEventListener('click', function () {
                const notifElem = this.closest('.notification-item');
                const notifId = notifElem.getAttribute('data-id');
                window.location.href = `view_notification.php?id=${notifId}`;
            });
        });

        document.querySelectorAll('.btn-notification-dismiss').forEach(button => {
            button.addEventListener('click', function () {
                const notifElem = this.closest('.notification-item');
                const notifId = notifElem.getAttribute('data-id');

                fetch('hide_notification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id_notification: notifId }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        notifElem.style.display = 'none';
                        
                        let countAll = parseInt(document.getElementById('badge-all').textContent);
                        let countUnread = parseInt(document.getElementById('badge-unread').textContent);
                        document.getElementById('badge-all').textContent = countAll - 1;

                        if (notifElem.classList.contains('unread')) {
                            document.getElementById('badge-unread').textContent = countUnread - 1;
                        }
                    } else {
                        alert('Erreur lors de la suppression : ' + (data.message || 'Erreur inconnue'));
                    }
                })
                .catch(() => {
                    alert('Erreur de connexion au serveur.');
                });
            });
        });

        const tabAll = document.getElementById('tab-all');
        const tabUnread = document.getElementById('tab-unread');
        const list = document.getElementById('notification-list');

        tabAll.addEventListener('click', () => {
            tabAll.classList.add('active');
            tabUnread.classList.remove('active');
            [...list.children].forEach(item => {
                if (item.classList.contains('notification-item')) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = '';
                }
            });
        });

        tabUnread.addEventListener('click', () => {
            tabUnread.classList.add('active');
            tabAll.classList.remove('active');
            [...list.children].forEach(item => {
                if (!item.classList.contains('notification-item')) {
                    item.style.display = 'none';
                    return;
                }
                item.style.display = item.getAttribute('data-status') === 'unread' ? 'flex' : 'none';
            });
        });

        document.getElementById('mark-all-read').addEventListener('click', (e) => {
            e.preventDefault();
            fetch('mark_all_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ user_id: <?= json_encode($userId) ?> }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                        const badge = item.querySelector('.badge-notification');
                        if (badge) badge.remove();
                    });
                    document.getElementById('badge-unread').textContent = '0';
                } else {
                    alert('Erreur lors de la mise à jour.');
                }
            })
            .catch(() => {
                alert('Erreur de connexion au serveur.');
            });
        });
    });
    </script>
</body>
</html>