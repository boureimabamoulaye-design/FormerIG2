<?php
/**
 * Déconnexion
 */
session_start();
require_once '../config/database.php';
require_once '../includes/Security.php';
require_once '../includes/Auth.php';

// Déconnecter l'utilisateur
Auth::logout();
?>