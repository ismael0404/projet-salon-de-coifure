<?php
// Démarrer la session
session_start();

// Rediriger si l'utilisateur est déjà connecté
if(isset($_SESSION['user_id'])) {
    if($_SESSION['user_role'] == 'client') {
        header('Location: client/dashboard_client.php');
    } elseif($_SESSION['user_role'] == 'employe') {
        header('Location: employe/dashboard_employe.php');
    } elseif($_SESSION['user_role'] == 'admin') {
        header('Location: admin/dashboard_admin.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Connexion - TYA STYLEX Salon de Coiffure</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        /* Styles spécifiques pour la page de connexion */
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            padding: 100px 0;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
        }
        
        .login-header {
            background: linear-gradient(135deg, #d4a373, #b88352);
            padding: 30px;
            text-align: center;
            color: white;
        }
        
        .login-header h3 {
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .login-body {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #2d3436;
        }
        
        .form-control {
            padding: 12px 15px;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #d4a373;
            box-shadow: 0 0 0 0.2rem rgba(212, 163, 115, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #d4a373, #b88352);
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(212, 163, 115, 0.4);
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }
        
        .forgot-password a {
            color: #d4a373;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .forgot-password a:hover {
            color: #b88352;
            text-decoration: underline;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .register-link a {
            color: #d4a373;
            font-weight: 600;
            text-decoration: none;
        }
        
        .register-link a:hover {
            color: #b88352;
            text-decoration: underline;
        }
        
        .alert-message {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
        }
        
        .alert-message.show {
            display: block;
        }
        
        .alert-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #d4a373;
        }
        
        .input-icon input {
            padding-left: 45px;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        
        .password-toggle:hover {
            color: #d4a373;
        }
        
        @media (max-width: 768px) {
            .login-body {
                padding: 25px;
            }
            
            .login-header {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<!-- ========== NAVIGATION BAR ========== -->
<nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="../index.php">
            <img src="../assets/images/ty.jpg" alt="Logo TYA STYLEX" class="navbar-logo" onerror="this.src='https://via.placeholder.com/45x45?text=TYA'">
            <span>TYA STYLEX</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="../index.php#accueil">Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="../index.php#services">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="../index.php#tarifs">Tarifs</a></li>
                <li class="nav-item"><a class="nav-link" href="../index.php#contact">Contact</a></li>
                <li class="nav-item">
                    <a class="btn btn-primary ms-2" href="inscription.php">
                        <i class="fas fa-user-plus"></i> Inscription
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- ========== PAGE DE CONNEXION ========== -->
<div class="login-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-card" data-aos="fade-up" data-aos-duration="800">
                    <div class="login-header">
                        <i class="fas fa-scissors fa-3x mb-3"></i>
                        <h3>Bienvenue chez TYA STYLEX</h3>
                        <p class="mb-0 opacity-75">Connectez-vous à votre compte</p>
                    </div>
                    <div class="login-body">
                        
                        <!-- Message d'alerte -->
                        <?php
                        $error = isset($_GET['error']) ? $_GET['error'] : '';
                        $success = isset($_GET['success']) ? $_GET['success'] : '';
                        
                        if($error == 'empty_fields'): ?>
                            <div class="alert-message error show">
                                <i class="fas fa-exclamation-circle"></i> Veuillez remplir tous les champs.
                            </div>
                        <?php elseif($error == 'invalid_email'): ?>
                            <div class="alert-message error show">
                                <i class="fas fa-exclamation-circle"></i> Veuillez saisir un email valide.
                            </div>
                        <?php elseif($error == 'invalid_credentials'): ?>
                            <div class="alert-message error show">
                                <i class="fas fa-exclamation-circle"></i> Email ou mot de passe incorrect.
                            </div>
                        <?php elseif($error == 'account_inactive'): ?>
                            <div class="alert-message error show">
                                <i class="fas fa-exclamation-circle"></i> Votre compte est inactif. Veuillez contacter l'administrateur.
                            </div>
                        <?php elseif($error == 'account_suspended'): ?>
                            <div class="alert-message error show">
                                <i class="fas fa-exclamation-circle"></i> Votre compte a été suspendu. Veuillez contacter l'administrateur.
                            </div>
                        <?php elseif($error == 'system_error'): ?>
                            <div class="alert-message error show">
                                <i class="fas fa-exclamation-circle"></i> Une erreur système s'est produite. Veuillez réessayer.
                            </div>
                        <?php elseif($success == 'deconnexion'): ?>
                            <div class="alert-message success show">
                                <i class="fas fa-check-circle"></i> Vous avez été déconnecté avec succès.
                            </div>
                        <?php elseif($success == 'inscription_ok'): ?>
                            <div class="alert-message success show">
                                <i class="fas fa-check-circle"></i> Inscription réussie ! Veuillez vous connecter.
                            </div>
                        <?php endif; ?>
                        
                        <!-- Formulaire de connexion -->
                        <form id="loginForm" method="POST" action="../php/auth/connexion.php">
                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> Email</label>
                                <div class="input-icon">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" name="email" class="form-control" placeholder="exemple@email.com" required autocomplete="email" value="<?php echo isset($_COOKIE['user_email']) ? htmlspecialchars($_COOKIE['user_email']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-lock"></i> Mot de passe</label>
                                <div class="input-icon">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" name="mot_de_passe" id="password" class="form-control" placeholder="Votre mot de passe" required>
                                    <span class="password-toggle" onclick="togglePassword()">
                                        <i class="fas fa-eye" id="toggleIcon"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="form-group form-check">
                                <input type="checkbox" class="form-check-input" id="rememberMe" name="remember_me">
                                <label class="form-check-label" for="rememberMe">Se souvenir de moi</label>
                            </div>
                            
                            <button type="submit" class="btn btn-login text-white">
                                <i class="fas fa-sign-in-alt"></i> Se connecter
                            </button>
                            
                            <div class="forgot-password">
                                <a href="mot_de_passe_oublie.php">
                                    <i class="fas fa-key"></i> Mot de passe oublié ?
                                </a>
                            </div>
                            
                            <div class="register-link">
                                <p class="mb-0">Pas encore de compte ? 
                                    <a href="inscription.php">
                                        <i class="fas fa-user-plus"></i> S'inscrire
                                    </a>
                                </p>
                            </div>
                        </form>
                        
                        <!-- Ou se connecter avec -->
                        <div class="text-center mt-4">
                            <p class="text-muted mb-3">Ou connectez-vous avec</p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="#" class="btn btn-outline-secondary rounded-circle" style="width: 45px; height: 45px; line-height: 45px; padding: 0;">
                                    <i class="fab fa-google"></i>
                                </a>
                                <a href="#" class="btn btn-outline-secondary rounded-circle" style="width: 45px; height: 45px; line-height: 45px; padding: 0;">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="btn btn-outline-secondary rounded-circle" style="width: 45px; height: 45px; line-height: 45px; padding: 0;">
                                    <i class="fab fa-instagram"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========== FOOTER ========== -->
<footer class="footer-section py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-4 text-center text-md-start">
                <img src="../assets/images/ty.jpg" alt="Logo TYA STYLEX" class="footer-logo" onerror="this.src='https://via.placeholder.com/40x40?text=TYA'">
            </div>
            <div class="col-md-4 text-center">
                <p class="mb-0">&copy; 2024 TYA STYLEX - Tous droits réservés</p>
            </div>
            <div class="col-md-4 text-center text-md-end">
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                    <a href="#"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- ========== SCRIPTS ========== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    // Initialisation AOS
    AOS.init({
        duration: 800,
        once: true,
        offset: 100
    });
    
    // Navigation scroll
    window.addEventListener('scroll', function() {
        const nav = document.getElementById('mainNav');
        if (window.scrollY > 50) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
    });
    
    // Afficher/masquer le mot de passe
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-message');
        alerts.forEach(function(alert) {
            alert.classList.remove('show');
        });
    }, 5000);
</script>
</body>
</html>