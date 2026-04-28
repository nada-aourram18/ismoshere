<?php
include 'db.php';
   $stmt = $pdo->query("SELECT * FROM filiere");
    $filieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // var_dump($filieres);
$messageerr = [];
try {
    // $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        extract($_POST);

        if (isset($_POST['Sinscrire'])) {

            // Validation du nom
            if (!isset($nom) || empty($nom) || !ctype_alpha($nom)) {
                $messageerr["nom"] = "Nom invalide";
            }

            // Validation du prénom
            if (!isset($prenom) || empty($prenom) || !ctype_alpha($prenom)) {
                $messageerr["prenom"] = "Prénom invalide";
            }

            // Validation de l'email
            if (!isset($email) || empty($email)) {
                $messageerr["email"] = "Email invalide";
            } elseif (!preg_match("/^[0-9]{13}@ofppt-edu\.ma$/", $email)) {
                $messageerr["email"] = "Veuillez entrer un mail OFPPT valide (13 chiffres)";
            }

            // Validation du matricule
            if (!isset($matricule) || empty($matricule) || !preg_match("/^[A-Z][0-9]{9}$/", $matricule)) {
                $messageerr["matricule"] = "Matricule invalide : une lettre majuscule puis 9 chiffres (votre vrai matricule CEF).";
            }

            // Validation de la filière
            if (empty($filiere)) {
                $messageerr["filiere"] = "Veuillez sélectionner votre filière";
            }

            // Validation de l'image
           if (isset($_FILES['profileImage'])) {
   // if ($_FILES['myfyle']['type'] == 'zip') {

      $profile = $_FILES["profileImage"]["name"];
      $result = move_uploaded_file($_FILES["profileImage"]["tmp_name"], "images/$profile");
   // }
}
            // Validation du mot de passe
            if (!isset($password) || empty($password)) {
                $messageerr["password"] = "Veuillez entrer un mot de passe";
            }

            // Validation de la confirmation
            if (!isset($confirmPassword) || empty($confirmPassword)) {
                $messageerr["confirmPassword"] = "Veuillez confirmer votre mot de passe";
            } elseif ($confirmPassword !== $password) {
                $messageerr["confirmPassword"] = "Les mots de passe ne correspondent pas";
            }
            if(!isset($telephone)||empty($telephone)){
                $messageerr["telephone"]="entrez votre telephone ";
            }
            if(!preg_match("/^(06|07)[0-9]{8}$/",$telephone)){
                
               $messageerr["telephone"]="entre le numero de telephone sous form 06XXXXXXXX";
            }
            
            if(!isset($statut)||empty($statut)){
                $messageerr["statut"]="choisi votre role ";
            }

            if (empty($messageerr) && isset($matricule)) {
                $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE matricule_CEF = ?");
                $stmt->execute([$matricule]);
                if ($stmt->fetch()) {
                    $messageerr["matricule"] = "Ce matricule CEF est déjà enregistré. Indiquez le vôtre.";
                }
            }
            if (empty($messageerr) && isset($telephone)) {
                $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE telephon = ?");
                $stmt->execute([$telephone]);
                if ($stmt->fetch()) {
                    $messageerr["telephone"] = "Ce numéro de téléphone est déjà utilisé.";
                }
            }
            if (empty($messageerr) && isset($email)) {
                $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $messageerr["email"] = "Cet email est déjà enregistré.";
                }
            }

            // Si aucune erreur, insertion
            if (empty($messageerr)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO utilisateur (nom,prenom,email,role,mot_de_passe,matricule_CEF,telephon, id_filier,photo_profil, statut)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'en attente')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nom, $prenom, $email, $statut, $hashedPassword, $matricule, $telephone, $filiere, $profile]);

                if ($stmt) {
                    header("Location: login.php?inscription=ok");
                    exit;
                } else {
                    echo "<div style='color:red'>Erreur d'insertion</div>";
                }
            } 
        }
    }
} 
catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - ISMOShare</title>
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
        .register-container {
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
        }
        .register-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
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
        .password-strength {
            height: 5px;
            background-color: #e9ecef;
            border-radius: 3px;
            margin-top: 5px;
        }
        .password-strength-bar {
            height: 100%;
            border-radius: 3px;
            width: 0%;
            transition: width 0.3s ease;
        }
        .progress-weak { background-color: #dc3545; width: 25%; }
        .progress-medium { background-color: #ffc107; width: 50%; }
        .progress-strong { background-color: #28a745; width: 75%; }
        .progress-very-strong { background-color: #0f766e; width: 100%; }
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
        .form-note {
            font-size: 0.85rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="register-container">
            <div class="register-card card">
                <div class="card-header">
                    <h1 class="logo-text">ISMO<span>Share</span></h1>
                    <p class="mb-0">Créez votre compte</p>
                </div>
                <div class="card-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="firstName" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="firstName" name="prenom" placeholder="Votre prénom" required>
                            </div>
                            <?php if(isset($messageerr["prenom"]))echo"<div style=color:red>{$messageerr['prenom']}</div>";     ?>
                            <div class="col-md-6 mb-3">
                                <label for="lastName" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="lastName" name="nom" placeholder="Votre nom" required>
                            </div>
                              <?php if(isset($messageerr["nom"]))echo"<div style=color:red>{$messageerr['nom']}</div>";     ?>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email institutionnel</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="prenom.nom@ismo.ma" required>
                            </div>
                            <div class="form-note">Utilisez votre email ISMO</div>
                        </div>
                          <?php if(isset($messageerr["email"]))echo"<div style=color:red>{$messageerr['email']}</div>";     ?>
                        <div class="mb-3">
                            <label for="matricule" class="form-label">Matricule CEF</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                <input type="text" class="form-control" id="matricule" name="matricule" placeholder="Votre matricule" required>
                            </div>
                        </div>
                         <?php if(isset($messageerr["matricule"]))echo"<div style=color:red>{$messageerr['matricule']}</div>";     ?>
                         <label for="telephone" class="form-label">Téléphone</label>
                          <div class="input-group">
                          <span class="input-group-text"><i class="fas fa-phone"></i></span>
                          <input type="tel" class="form-control" id="telephone" name="telephone" placeholder="06XXXXXXXX" required>
                         </div>
                         <?php
                         if(isset($messageerr["telephone"]))echo"<div style=color:red>{$messageerr['telephone']}</div>";
                         ?>
                         <br><br>
                          <label class="form-label">Votre role <br><br>
                         <input type="radio" name="statut" value="formateur">
                          Formateur <br><br>
                        <input type="radio" name="statut" value="stagiaire">
                        Stagiaire
                         </label>   
                         <br><br>
                        <div class="mb-3">
                            <label for="filiere" class="form-label">Filière</label>
                            <select class="form-select" id="filiere" name="filiere" required>
                                <option value="" selected disabled>Sélectionnez votre filière</option>
                                <?php foreach($filieres as $f){?>
                                    <option value="<?= $f["id_filier"]?>"><?= $f["nom_filiere"]?></option>
                                <?php }?>
                                <!-- <option value="DEV">Développement Digital</option> 
                                <option value="1">Informatique</option>
                                <option value="2">Gestion</option>
                                  <option value="3">Électronique</option> -->
                            </select>
                        </div>
                        <?php if(isset($messageerr["filiere"]))echo"<div style=color:red>{$messageerr['filiere']}</div>";     ?>
                        <div class="mb-3">
                            <label for="profileImage" class="form-label">Photo de profil</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-image"></i></span>
                                <input type="file" class="form-control" id="profileImage" name="profileImage" accept="image/*" required>
                            </div>
                            <div class="form-note">Formats acceptés : jpg, jpeg, png.</div> 
                        </div>
                        <?php if(isset($messageerr["profileImage"]))echo"<div style=color:red>{$messageerr['profileImage']}</div>";     ?>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <?php if(isset($messageerr["password"]))echo"<div style=color:red>{$messageerr['password']}</div>";     ?>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="passwordStrength"></div>
                            </div>
                            <div class="form-note">8 caractères minimum, avec majuscule, minuscule et chiffre</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirmer le mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirmPassword"  name="confirmPassword" placeholder="••••••••" required>
                            </div>
                        </div>
                          <?php if(isset($messageerr["confirmPassword"]))echo"<div style=color:red>{$messageerr['confirmPassword']}</div>";     ?>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3" name="Sinscrire">
                            <i class="fas fa-user-plus me-2"></i>S'inscrire
                        </button>
                        <div class="text-center mt-3 links">
                            <p>Déjà membre ? <a href="login.php">Se connecter</a></p>
                        </div>
                    </form>
                </div>
            </div>
            
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
        document.getElementById('password').addEventListener('input', function() {
            const strengthBar = document.getElementById('passwordStrength');
            const password = this.value;
            let strength = 0;

            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            strengthBar.className = 'password-strength-bar';
            if (password.length === 0) {
                strengthBar.style.width = '0%';
            } else if (strength <= 2) {
                strengthBar.classList.add('progress-weak');
            } else if (strength === 3) {
                strengthBar.classList.add('progress-medium');
            } else if (strength === 4) {
                strengthBar.classList.add('progress-strong');
            } else {
                strengthBar.classList.add('progress-very-strong');
            }
        });

        // Confirm password validation
        document.getElementById('confirmPassword').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            if (confirmPassword !== '' && password !== confirmPassword) {
                this.setCustomValidity("Les mots de passe ne correspondent pas");
            } else {
                this.setCustomValidity("");
            }
        });
    </script>
</body>
</html>
