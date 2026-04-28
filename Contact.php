<?php
session_start();
include 'db.php';

$roles = ['admin', 'formateur', 'stagiaire'];
if (!isset($_SESSION['user_id'], $_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles, true)) {
    header('Location: login.php?msg=' . rawurlencode('Veuillez vous connecter.'));
    exit;
}
// if (!isset($_SESSION['id_utilisateur'])) {
//     header("Location:login.php");
//     exit;
// }

$userRole = $_SESSION['user_role'] ?? '';
$role = $userRole;
$userId = (string) ($_SESSION['id_utilisateur'] ?? $_SESSION['user_id'] ?? '');

if($_SERVER['REQUEST_METHOD']=="POST"){
  extract($_POST);
  $msgerr = [];
  if(isset($envoyer)){
    if(!isset($nom) || empty($nom)) $msgerr['nom'] = "Veuillez entrer votre nom.";
    if(!isset($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $msgerr['email'] = "Veuillez entrer un email valide.";
    if(!isset($sujet) || empty($sujet)) $msgerr['sujet'] = "Veuillez entrer le sujet de votre message.";
    if(!isset($message) || empty($message)) $msgerr['message'] = "Veuillez entrer votre message.";
    if(empty($msgerr)){
      include("dbconnexion.php");
      $nom = htmlspecialchars($nom);
      $email = htmlspecialchars($email);
      $sujet = htmlspecialchars($sujet);
      $message = htmlspecialchars($message);
      try {
        $req = $pdo->prepare("INSERT INTO contact (nom, email, sujet, message) VALUES (?, ?, ?, ?)");
        $req->execute([$nom, $email, $sujet, $message]);
        header("Location:contact.php?msgs=Message envoyé avec succès");
        exit;
      } catch (PDOException $e) {
        echo "Erreur lors de l'envoi du message : " . $e->getMessage();
      }
    }
  }
}
$page_title = 'Contact - ISMOShare';
$current_page = basename(__FILE__);
$extra_head = <<<'HTML'
<style>
    .contact-page .contact-section {
        background-color: white;
        border-radius: 14px;
        padding: 2.5rem;
        box-shadow: 0 10px 28px rgba(15, 118, 110, 0.08);
        border: 1px solid rgba(15, 118, 110, 0.08);
    }
    .contact-page .contact-icon { font-size: 1.2rem; color: #0f766e; }
    .contact-page .contact-title { color: #0d5d56; font-weight: 700; }
    .contact-page .btn-send { background-color: #0f766e; color: white; border: none; padding: 10px 22px; border-radius: 8px; }
    .contact-page .btn-send:hover { background-color: #0d5d56; }
</style>
HTML;
include __DIR__ . '/includes/layout_head.php';
include __DIR__ . '/includes/app_nav.php';
?>

  <!-- Section de contact -->
  <div class="container my-5 contact-page ismo-main">
    <div class="contact-section">
      <h2 class="contact-title mb-4"><i class="fas fa-envelope me-2"></i>Contactez-nous</h2>
      <?php if(isset($_GET['msgs'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['msgs']) ?></div>
      <?php endif; ?>
      <form method="POST">
        <div class="row mb-3">
          <div class="col-md-6">
            <?php if(isset($msgerr['nom'])){echo "<div class='text-danger'>{$msgerr['nom']}</div>";}?>
            <label class="form-label"><i class="fas fa-user contact-icon me-1"></i>Nom complet</label>
            <input type="text" name="nom" class="form-control" placeholder="Votre nom" required>
          </div>
          <div class="col-md-6">
            <?php if(isset($msgerr['email'])){echo "<div class='text-danger'>{$msgerr['email']}</div>";}?>
            <label class="form-label"><i class="fas fa-envelope contact-icon me-1"></i>Email</label>
            <input type="email" name="email" class="form-control" placeholder="exemple@mail.com" required>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-tag contact-icon me-1"></i>Sujet</label>
          <?php if(isset($msgerr['sujet'])){echo "<div class='text-danger'>{$msgerr['sujet']}</div>";}?>
          <input type="text" name="sujet" class="form-control" placeholder="Sujet du message" required>
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-comment-dots contact-icon me-1"></i>Message</label>
          <?php if(isset($msgerr['message'])){echo "<div class='text-danger'>{$msgerr['message']}</div>";}?>
          <textarea name="message" class="form-control" rows="5" placeholder="Écrivez votre message ici..." required></textarea>
        </div>
        <button type="submit" name="envoyer" class="btn btn-send px-4"><i class="fas fa-paper-plane me-2"></i>Envoyer</button>
      </form>
    </div>
  </div>

<?php include __DIR__ . '/includes/layout_foot.php';