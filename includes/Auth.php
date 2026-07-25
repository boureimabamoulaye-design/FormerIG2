<?php
/**
 * Classe d'authentification pour Admin et Étudiant
 */
class Auth
{
    const SESSION_ADMIN = 'admin_id';
    const SESSION_ADMIN_EMAIL = 'admin_email';
    const SESSION_ETUDIANT = 'etudiant_id';
    const SESSION_ETUDIANT_MATRICULE = 'etudiant_matricule';
    const SESSION_TYPE = 'user_type'; // 'admin' ou 'etudiant'

    /**
     * Authentifier un administrateur
     */
    public static function loginAdmin($email, $password)
    {
        try {
            // Validation
            if (empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'Email et mot de passe requis'];
            }

            if (!Security::validateEmail($email)) {
                return ['success' => false, 'message' => 'Email invalide'];
            }

            // Rechercher l'administrateur
            $query = "SELECT id, email, password FROM admins WHERE email = ? LIMIT 1";
            $admin = db()->fetchOne($query, [$email]);

            if (!$admin) {
                return ['success' => false, 'message' => 'Email ou mot de passe incorrect'];
            }

            // Vérifier le mot de passe
            if (!Security::verifyPassword($password, $admin['password'])) {
                return ['success' => false, 'message' => 'Email ou mot de passe incorrect'];
            }

            // Créer la session
            session_regenerate_id(true);
            $_SESSION[self::SESSION_ADMIN] = $admin['id'];
            $_SESSION[self::SESSION_ADMIN_EMAIL] = $admin['email'];
            $_SESSION[self::SESSION_TYPE] = 'admin';

            // Enregistrer l'action
            Security::logAction($admin['id'], 'admin', 'Connexion', 'Connexion réussie');

            return ['success' => true, 'message' => 'Connexion réussie', 'redirect' => '/admin/dashboard'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur système: ' . $e->getMessage()];
        }
    }

    /**
     * Authentifier un étudiant
     */
    public static function loginEtudiant($matricule, $password)
    {
        try {
            // Validation
            if (empty($matricule) || empty($password)) {
                return ['success' => false, 'message' => 'Matricule et mot de passe requis'];
            }

            if (!Security::validateMatricule($matricule)) {
                return ['success' => false, 'message' => 'Matricule invalide'];
            }

            // Rechercher l'étudiant
            $query = "SELECT id, matricule, password FROM etudiants WHERE matricule = ? LIMIT 1";
            $etudiant = db()->fetchOne($query, [$matricule]);

            if (!$etudiant) {
                return ['success' => false, 'message' => 'Matricule ou mot de passe incorrect'];
            }

            // Vérifier le mot de passe
            if (!Security::verifyPassword($password, $etudiant['password'])) {
                return ['success' => false, 'message' => 'Matricule ou mot de passe incorrect'];
            }

            // Créer la session
            session_regenerate_id(true);
            $_SESSION[self::SESSION_ETUDIANT] = $etudiant['id'];
            $_SESSION[self::SESSION_ETUDIANT_MATRICULE] = $etudiant['matricule'];
            $_SESSION[self::SESSION_TYPE] = 'etudiant';

            // Enregistrer l'action
            Security::logAction($etudiant['id'], 'etudiant', 'Connexion', 'Connexion réussie');

            return ['success' => true, 'message' => 'Connexion réussie', 'redirect' => '/etudiant/dashboard'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur système: ' . $e->getMessage()];
        }
    }

    /**
     * Vérifier si un admin est connecté
     */
    public static function isAdminLoggedIn()
    {
        return isset($_SESSION[self::SESSION_ADMIN]) && isset($_SESSION[self::SESSION_TYPE]) && $_SESSION[self::SESSION_TYPE] === 'admin';
    }

    /**
     * Vérifier si un étudiant est connecté
     */
    public static function isEtudiantLoggedIn()
    {
        return isset($_SESSION[self::SESSION_ETUDIANT]) && isset($_SESSION[self::SESSION_TYPE]) && $_SESSION[self::SESSION_TYPE] === 'etudiant';
    }

    /**
     * Obtenir l'ID de l'admin connecté
     */
    public static function getAdminId()
    {
        return $_SESSION[self::SESSION_ADMIN] ?? null;
    }

    /**
     * Obtenir l'ID de l'étudiant connecté
     */
    public static function getEtudiantId()
    {
        return $_SESSION[self::SESSION_ETUDIANT] ?? null;
    }

    /**
     * Obtenir le matricule de l'étudiant connecté
     */
    public static function getEtudiantMatricule()
    {
        return $_SESSION[self::SESSION_ETUDIANT_MATRICULE] ?? null;
    }

    /**
     * Obtenir le type de l'utilisateur connecté
     */
    public static function getUserType()
    {
        return $_SESSION[self::SESSION_TYPE] ?? null;
    }

    /**
     * Déconnecter l'utilisateur
     */
    public static function logout()
    {
        // Enregistrer la déconnexion
        if (self::isAdminLoggedIn()) {
            Security::logAction(self::getAdminId(), 'admin', 'Déconnexion', 'Déconnexion');
        } elseif (self::isEtudiantLoggedIn()) {
            Security::logAction(self::getEtudiantId(), 'etudiant', 'Déconnexion', 'Déconnexion');
        }

        // Détruire la session
        $_SESSION = [];
        if (ini_get('session.use_cookies') === '1') {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();

        Security::redirect('/');
    }
}
?>