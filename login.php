<!DOCTYPE html>
<html lang="fr">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Connexion - ISMOShare</title>
   <!-- Bootstrap 5 CSS -->
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
   <!-- Font Awesome -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
   <!-- Google Fonts -->
   <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   <style>
      :root {
         --primary: #0f766e;
         --primary-light: #0f766e;;
         --secondary: #f0fdf4;
         --dark: #1e3a3a;
      }

      body {
         font-family: 'Poppins', sans-serif;
         background-color: #f8f9fa;
         min-height: 100vh;
         display: flex;
         align-items: center;
         background: linear-gradient(135deg, var(--secondary) 0%, #ffffff 100%);
      }

      .login-container {
         max-width: 420px;
         width: 100%;
         margin: 0 auto;
      }

      .login-card {
         border: none;
         border-radius: 12px;
         box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
         overflow: hidden;
         transition: transform 0.3s ease;
      }

      .login-card:hover {
         transform: translateY(-5px);
      }

      .card-header {
         background-color: var(--primary);
         color: white;
         padding: 1.5rem;
         text-align: center;
      }

      .logo-text {
         font-weight: 700;
         font-size: 1.8rem;
      }

      .logo-text span {
         color: var(--primary-light);
      }

      .card-body {
         padding: 2rem;
      }

      .form-control {
         border-radius: 8px;
         padding: 0.75rem 1rem;
         border: 1px solid #e0e0e0;
      }

      .form-control:focus {
         border-color: var(--primary-light);
         box-shadow: 0 0 0 0.25rem rgba(94, 234, 212, 0.25);
      }

      .input-group-text {
         background-color: transparent;
         border-right: none;
      }

      .form-floating>label {
         padding: 0.75rem 1rem;
      }

      .btn-primary {
         background-color: var(--primary);
         border: none;
         padding: 0.75rem;
         font-weight: 500;
         letter-spacing: 0.5px;
         border-radius: 8px;
      }

      .btn-primary:hover {
         background-color: #0d645e;
      }

      .divider {
         display: flex;
         align-items: center;
         margin: 1.5rem 0;
         color: #6c757d;
      }

      .divider::before,
      .divider::after {
         content: "";
         flex: 1;
         border-bottom: 1px solid #dee2e6;
      }

      .divider::before {
         margin-right: 1rem;
      }

      .divider::after {
         margin-left: 1rem;
      }

      .social-btn {
         display: flex;
         align-items: center;
         justify-content: center;
         padding: 0.5rem;
         border-radius: 8px;
         font-weight: 500;
         margin-bottom: 0.5rem;
      }

      .social-btn i {
         margin-right: 0.5rem;
         font-size: 1.1rem;
      }

      .links a {
         color: var(--primary);
         text-decoration: none;
         font-weight: 500;
         transition: all 0.3s;
      }

      .links a:hover {
         color: var(--primary-dark);
         text-decoration: underline;
      }
   </style>
</head>
<?php
if (session_status() == PHP_SESSION_NONE) {
   session_start();
}
$messageerr = [];
$flash_ok = '';
if (isset($_GET['msg']) && is_string($_GET['msg']) && $_GET['msg'] !== '') {
    $flash_ok = htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8');
} elseif (isset($_GET['inscription']) && $_GET['inscription'] === 'ok') {
    $flash_ok = 'Inscription enregistrée. Connectez-vous une fois votre compte validé par un administrateur.';
}

try {
   include('db.php');

if (isset($_POST["connect"])) {
   $email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
   $mot_de_passe = isset($_POST['password']) ? (string) $_POST['password'] : '';

   $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email = ?");
   $stmt->execute([$email]);
   $user = $stmt->fetch();

   if ($user) {
      $stored = $user['mot_de_passe'] ?? '';
      $ok = password_verify($mot_de_passe, $stored);
      if (!$ok && hash_equals($stored, $mot_de_passe)) {
          $ok = true;
      }
      if ($ok) {
         $statut = (string) ($user['statut'] ?? '');
         if ($statut === 'valide') {
         $_SESSION['user_id'] = $user['id_utilisateur'];
         $_SESSION['user_role'] = $user['role'];
         $_SESSION['user_name'] = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?: 'Utilisateur';
         $_SESSION['id_utilisateur'] = (int) $user['id_utilisateur'];
         $_SESSION['role'] = $user['role'];

         switch ($user['role']) {
            case 'admin':
            case 'formateur':
            case 'stagiaire':
               header("Location: acceuil2.php");
               exit;
            default:
               $messageerr["user"] = "Rôle inconnu.";
               unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_name'], $_SESSION['id_utilisateur'], $_SESSION['role']);
         }
         } elseif ($statut === 'en attente') {
            $messageerr["statut"] = "Votre compte est en attente de validation.";
         } elseif ($statut === 'rejete') {
            $messageerr["statut"] = "Votre compte a été refusé. Contactez l'administration.";
         } elseif ($statut === 'supprime') {
            $messageerr["statut"] = "Ce compte n'est plus actif.";
         } else {
            $messageerr["statut"] = "Compte non disponible pour la connexion.";
         }
      } else {
          $messageerr["motdepass"]="Mot de passe incorrect.";
      }
   } else {
       $messageerr["user"]="Cet utilisateur n'existe pas";
   }
}
} catch (PDOException $e) {
   die("Erreur de connexion : " . $e->getMessage());
}

?>

<body>
   <div class="container py-5">
      <div class="login-container">
         <div class="login-card card">
            <div class="card-header">
               <h1 class="logo-text">ISMO<span>Share</span></h1>
               <p class="mb-0">Plateforme collaborative des stagiaires</p>
            </div>
 <?php if ($flash_ok !== ''): ?><div class="alert alert-success small mb-2"><?= $flash_ok ?></div><?php endif; ?>
 <?php if(isset($messageerr['statut'] ))echo"<div class='alert alert-danger small mb-2'>".htmlspecialchars($messageerr['statut'], ENT_QUOTES, 'UTF-8')."</div>";?>
            <div class="card-body">
               <form method="post">
                  <div class="mb-3">
                     <label for="email" class="form-label">Adresse email</label>
                     <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" placeholder="votre@email.ma" required>
                     </div>
                      <?php if(isset($messageerr['user'] ))echo"<div style=color:red>{$messageerr['user']}</div>";?>
                  </div>

                  <div class="mb-3">
                     <label for="password" class="form-label">Mot de passe</label>
                     <?php if(isset($messageerr['motdepass'] ))echo"<div style=color:red>{$messageerr['motdepass']}</div>";?>
                     <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                           <i class="fas fa-eye"></i>
                        </button>
                     </div>
                  </div>

                  <div class="d-flex justify-content-between align-items-center mb-4">
                     <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember">
                        <label class="form-check-label" for="remember">Se souvenir de moi</label>
                     </div>
                     <a href="#" class="text-decoration-none">Mot de passe oublié ?</a>
                  </div>

                  <button type="submit" class="btn btn-primary w-100 mb-3" name="connect">
                     <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                  </button>


               </form>

               <div class="text-center mt-4 links">
                  <p>Nouveau sur ISMOShare ? <a href="inscription.php">Créer un compte</a></p>
               </div>
            </div>
         </div>


      </div>
   </div>

   <!-- Bootstrap JS Bundle with Popper -->
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
   <script>
      // Toggle password visibility
      document.getElementById('togglePassword').addEventListener('click', function() {
         const password = document.getElementById('password');
         const icon = this.querySelector('i');
         if (password.type === 'password') {
            password.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
         } else {
            password.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
         }
      });
   </script>
</body>

</html>