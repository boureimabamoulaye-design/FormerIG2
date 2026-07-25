<?php
/**
 * Authentification Admin
 */
session_start();
require_once '../config/database.php';
require_once '../includes/Security.php';
require_once '../includes/Auth.php';

// Verifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Security::redirect('/');
}

// Vérifier le token CSRF
if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $error = urlencode('Token de sécurité invalide');
    Security::redirect('/?error=' . $error . '&type=admin');
}

// Obtenir les données du formulaire
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// Authentifier
$result = Auth::loginAdmin($email, $password);

if ($result['success']) {
    Security::redirect($result['redirect']);
} else {
    $error = urlencode($result['message']);
    Security::redirect('/?error=' . $error . '&type=admin');
}
?>