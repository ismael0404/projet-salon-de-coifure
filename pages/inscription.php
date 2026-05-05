<?php
require_once dirname(__DIR__) . '/includes/functions.php';

// Rediriger si l'utilisateur est déjà connecté
if(is_logged_in()) {
    $role = $_SESSION['user_role'];
    $path = ($role === 'client') ? 'client/dashboard.php' : (($role === 'employe') ? 'employe/dashboard.php' : 'admin/dashboard.php');
    redirect($path);
}

// Variables pour réafficher les valeurs si erreur (peuvent provenir d'une ancienne session)
$nom = $_GET['nom'] ?? '';
$prenom = $_GET['prenom'] ?? '';
$email = $_GET['email'] ?? '';
$telephone = $_GET['telephone'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Inscription - TYA STYLEX Salon de Coiffure</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    /* ... Vos autres styles ... */
    
    <style>
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            padding: 100px 0;
        }
        
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .register-card:hover {
            transform: translateY(-5px);
        }
        
        .register-header {
            background: linear-gradient(135deg, #d4a373, #b88352);
            padding: 30px;
            text-align: center;
            color: white;
        }
        
        .register-header h3 {
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .register-body {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 20px;
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
        
        .btn-register {
            background: linear-gradient(135deg, #d4a373, #b88352);
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(212, 163, 115, 0.4);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .login-link a {
            color: #d4a373;
            font-weight: 600;
            text-decoration: none;
        }
        
        .login-link a:hover {
            color: #b88352;
            text-decoration: underline;
        }
        
        .alert-message {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
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
        
        .password-strength {
            margin-top: 5px;
            font-size: 0.8rem;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
        
        .terms-check {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .terms-check input {
            width: 18px;
            height: 18px;
        }
        
        @media (max-width: 768px) {
            .register-body {
                padding: 25px;
            }
            .register-header {
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
                    <a class="btn btn-outline-primary ms-lg-3" href="connexion.php">
                        <i class="fas fa-sign-in-alt"></i> Connexion
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- ========== PAGE D'INSCRIPTION ========== -->
<div class="register-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="register-card" data-aos="fade-up" data-aos-duration="800">
                    <div class="register-header">
                        <i class="fas fa-scissors fa-3x mb-3"></i>
                        <h3>Créez votre compte</h3>
                        <p class="mb-0 opacity-75">Rejoignez la famille TYA STYLEX</p>
                    </div>
                    <div class="register-body">
                        
                        <?php 
                        $error = $_GET['error'] ?? '';
                        $flash_error = get_flash_message('error');
                        if($flash_error): ?>
                            <div class="alert-message error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($flash_error); ?></div>
                        <?php elseif($error == 'empty_fields'): ?>
                            <div class="alert-message error"><i class="fas fa-exclamation-circle"></i> Veuillez remplir tous les champs obligatoires.</div>
                        <?php elseif($error == 'invalid_email'): ?>
                            <div class="alert-message error"><i class="fas fa-exclamation-circle"></i> Veuillez saisir un email valide.</div>
                        <?php elseif($error == 'email_exists'): ?>
                            <div class="alert-message error"><i class="fas fa-exclamation-circle"></i> Cet email est déjà utilisé. Veuillez vous connecter.</div>
                        <?php elseif($error == 'password_mismatch'): ?>
                            <div class="alert-message error"><i class="fas fa-exclamation-circle"></i> Les mots de passe ne correspondent pas.</div>
                        <?php elseif($error == 'weak_password'): ?>
                            <div class="alert-message error"><i class="fas fa-exclamation-circle"></i> Le mot de passe doit contenir au moins 6 caractères.</div>
                        <?php elseif($error == 'terms_required'): ?>
                            <div class="alert-message error"><i class="fas fa-exclamation-circle"></i> Vous devez accepter les conditions d'utilisation.</div>
                        <?php elseif($error == 'phone_invalid'): ?>
                            <div class="alert-message error"><i class="fas fa-exclamation-circle"></i> Veuillez saisir un numéro de téléphone valide.</div>
                        <?php elseif($error == 'system_error'): ?>
                            <div class="alert-message error"><i class="fas fa-exclamation-circle"></i> Une erreur s'est produite. Veuillez réessayer.</div>
                        <?php endif; ?>
                        
                        <form id="registerForm" method="POST" action="../php/controllers/auth_controller.php?action=register">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-user"></i> Nom *</label>
                                        <div class="input-icon">
                                            <i class="fas fa-user"></i>
                                            <input type="text" name="nom" class="form-control" placeholder="Votre nom" value="<?php echo htmlspecialchars($nom); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-user"></i> Prénom *</label>
                                        <div class="input-icon">
                                            <i class="fas fa-user"></i>
                                            <input type="text" name="prenom" class="form-control" placeholder="Votre prénom" value="<?php echo htmlspecialchars($prenom); ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> Email *</label>
                                <div class="input-icon">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" name="email" class="form-control" placeholder="exemple@email.com" value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-phone"></i> Téléphone</label>
                                <div class="input-icon">
                                    <i class="fas fa-phone"></i>
                                    <input type="tel" name="telephone" class="form-control" placeholder="+225 XX XX XX XX XX" value="<?php echo htmlspecialchars($telephone); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-lock"></i> Mot de passe *</label>
                                        <div class="input-icon">
                                            <i class="fas fa-lock"></i>
                                            <input type="password" name="mot_de_passe" id="password" class="form-control" placeholder="6 caractères minimum" required>
                                            <span class="password-toggle" onclick="togglePassword()">
                                                <i class="fas fa-eye" id="toggleIcon"></i>
                                            </span>
                                        </div>
                                        <div class="password-strength" id="passwordStrength"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-lock"></i> Confirmer mot de passe *</label>
                                        <div class="input-icon">
                                            <i class="fas fa-lock"></i>
                                            <input type="password" name="confirme_mot_de_passe" id="confirmPassword" class="form-control" placeholder="Confirmer" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="terms-check">
                                    <input type="checkbox" name="terms" id="terms" required>
                                    <label for="terms" class="mb-0">
                                        J'accepte les <a href="#">conditions générales d'utilisation</a>
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-register text-white">
                                <i class="fas fa-user-plus"></i> S'inscrire
                            </button>
                            
                            <div class="login-link">
                                <p class="mb-0">Vous avez déjà un compte ? 
                                    <a href="connexion.php">
                                        <i class="fas fa-sign-in-alt"></i> Se connecter
                                    </a>
                                </p>
                            </div>
                        </form>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: true, offset: 100 });
    
    window.addEventListener('scroll', function() {
        const nav = document.getElementById('mainNav');
        if (window.scrollY > 50) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
    });
    
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
    
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirmPassword');
    const strengthDiv = document.getElementById('passwordStrength');
    
    passwordInput.addEventListener('keyup', function() {
        const password = this.value;
        let strength = 0;
        let message = '';
        let className = '';
        if (password.length >= 6) strength++;
        if (password.length >= 10) strength++;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        
        if (password.length === 0) {
            message = '';
            className = '';
        } else if (strength <= 2) {
            message = 'Mot de passe faible';
            className = 'strength-weak';
        } else if (strength <= 4) {
            message = 'Mot de passe moyen';
            className = 'strength-medium';
        } else {
            message = 'Mot de passe fort';
            className = 'strength-strong';
        }
        strengthDiv.innerHTML = message;
        strengthDiv.className = 'password-strength ' + className;
        
        if (confirmInput.value.length > 0) {
            if (password !== confirmInput.value) {
                confirmInput.style.borderColor = '#dc3545';
            } else {
                confirmInput.style.borderColor = '#28a745';
            }
        }
    });
    
    confirmInput.addEventListener('keyup', function() {
        if (this.value !== passwordInput.value) {
            this.style.borderColor = '#dc3545';
        } else {
            this.style.borderColor = '#28a745';
        }
    });
</script>
</body>
</html>