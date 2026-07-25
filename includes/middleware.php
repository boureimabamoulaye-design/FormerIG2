<?php
/**
 * Middlewares de protection
 */

/**
 * Middleware: Vérifier que l'admin est connecté
 */
function requireAdmin()
{
    if (!Auth::isAdminLoggedIn()) {
        Security::redirect('/');
    }
}

/**
 * Middleware: Vérifier que l'étudiant est connecté
 */
function requireEtudiant()
{
    if (!Auth::isEtudiantLoggedIn()) {
        Security::redirect('/');
    }
}

/**
 * Middleware: Empêcher les accès non autorisés
 */
function requireNotLogged()
{
    if (Auth::isAdminLoggedIn() || Auth::isEtudiantLoggedIn()) {
        if (Auth::isAdminLoggedIn()) {
            Security::redirect('/admin/dashboard');
        } else {
            Security::redirect('/etudiant/dashboard');
        }
    }
}

/**
 * Middleware: Vérifier qu'un étudiant n'accéde qu'à ses données
 */
function requireSameEtudiant($etudiant_id)
{
    if (Auth::getEtudiantId() != $etudiant_id) {
        http_response_code(403);
        die('Accès refusé');
    }
}

/**
 * Middleware: Vérifier le timeout de session
 */
function checkSessionTimeout()
{
    if (isset($_SESSION['last_activity'])) {
        $inactive = time() - $_SESSION['last_activity'];
        if ($inactive > SESSION_TIMEOUT) {
            Auth::logout();
        }
    }
    $_SESSION['last_activity'] = time();
}
?>