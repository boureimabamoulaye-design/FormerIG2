<?php
/**
 * Page d'accueil - Redirection vers la bonne zone
 */
session_start();
require_once 'config/database.php';
require_once 'includes/Security.php';
require_once 'includes/Auth.php';

// Si admin connecté, rediriger vers dashboard admin
if (Auth::isAdminLoggedIn()) {
    Security::redirect('/admin/dashboard');
}

// Si étudiant connecté, rediriger vers dashboard étudiant
if (Auth::isEtudiantLoggedIn()) {
    Security::redirect('/etudiant/dashboard');
}

// Sinon, afficher la page d'accueil
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Scolaire - Connexion</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            max-width: 900px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
        }

        .container-left {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .container-left h1 {
            font-size: 32px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .container-left p {
            font-size: 16px;
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .container-right {
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-options {
            display: flex;
            gap: 20px;
            margin-bottom: 40px;
        }

        .login-option {
            flex: 1;
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            font-size: 16px;
            font-weight: 600;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .login-btn.active {
            background: #667eea;
            color: white;
        }

        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
        }

        .form-section h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
        }

        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }

            .container-left {
                display: none;
            }

            .container-right {
                padding: 40px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="container-left">
            <div class="logo">📚</div>
            <h1>Gestion Scolaire</h1>
            <p>Système complet de gestion des étudiants, cours, notes et bulletins.</p>
        </div>

        <div class="container-right">
            <div class="login-options">
                <div class="login-option">
                    <button class="login-btn active" onclick="switchForm('admin')">👤 Admin</button>
                </div>
                <div class="login-option">
                    <button class="login-btn" onclick="switchForm('etudiant')">🎓 Étudiant</button>
                </div>
            </div>

            <!-- Formulaire Admin -->
            <form id="admin-form" class="form-section active" method="POST" action="/auth/login-admin.php">
                <h2>Connexion Admin</h2>
                <div id="admin-message" class="message"></div>
                <div class="form-group">
                    <label for="admin-email">Email</label>
                    <input type="email" id="admin-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="admin-password">Mot de passe</label>
                    <input type="password" id="admin-password" name="password" required>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                <button type="submit" class="submit-btn">Connexion</button>
            </form>

            <!-- Formulaire Étudiant -->
            <form id="etudiant-form" class="form-section" method="POST" action="/auth/login-etudiant.php">
                <h2>Connexion Étudiant</h2>
                <div id="etudiant-message" class="message"></div>
                <div class="form-group">
                    <label for="etudiant-matricule">Matricule</label>
                    <input type="text" id="etudiant-matricule" name="matricule" required>
                </div>
                <div class="form-group">
                    <label for="etudiant-password">Mot de passe</label>
                    <input type="password" id="etudiant-password" name="password" required>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                <button type="submit" class="submit-btn">Connexion</button>
            </form>
        </div>
    </div>

    <script>
        function switchForm(type) {
            // Cacher tous les formulaires
            document.getElementById('admin-form').classList.remove('active');
            document.getElementById('etudiant-form').classList.remove('active');

            // Désélectionner tous les boutons
            document.querySelectorAll('.login-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Afficher le formulaire sélectionné
            if (type === 'admin') {
                document.getElementById('admin-form').classList.add('active');
                document.querySelectorAll('.login-btn')[0].classList.add('active');
            } else {
                document.getElementById('etudiant-form').classList.add('active');
                document.querySelectorAll('.login-btn')[1].classList.add('active');
            }
        }

        // Afficher les messages d'erreur s'il y en a
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('error')) {
            const error = urlParams.get('error');
            const type = urlParams.get('type') || 'admin';
            const messageElement = document.getElementById(type + '-message');
            messageElement.textContent = decodeURIComponent(error);
            messageElement.classList.add('error');
            switchForm(type);
        }
    </script>
</body>
</html>