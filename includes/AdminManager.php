<?php
/**
 * Classe pour gérer les administrateurs
 */
class AdminManager
{
    /**
     * Ajouter un administrateur
     */
    public static function create($nom, $prenom, $email, $password)
    {
        // Validation
        if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Tous les champs sont requis'];
        }

        if (!Security::validateEmail($email)) {
            return ['success' => false, 'message' => 'Email invalide'];
        }

        if (!Security::validatePassword($password)) {
            return ['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères, une majuscule et un chiffre'];
        }

        // Vérifier que l'email n'existe pas
        $existing = db()->fetchOne("SELECT id FROM admins WHERE email = ?", [$email]);
        if ($existing) {
            return ['success' => false, 'message' => 'Cet email est déjà utilisé'];
        }

        try {
            $hashed_password = Security::hashPassword($password);
            $query = "INSERT INTO admins (nom, prenom, email, password) VALUES (?, ?, ?, ?)";
            db()->execute($query, [$nom, $prenom, $email, $hashed_password]);

            Security::logAction(Auth::getAdminId(), 'admin', 'Création admin', "Création du compte: $email");

            return ['success' => true, 'message' => 'Administrateur créé avec succès'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Récupérer un administrateur par ID
     */
    public static function getById($id)
    {
        $query = "SELECT id, nom, prenom, email, created_at FROM admins WHERE id = ?";
        return db()->fetchOne($query, [$id]);
    }

    /**
     * Récupérer tous les administrateurs
     */
    public static function getAll($limit = 50, $offset = 0)
    {
        $query = "SELECT id, nom, prenom, email, created_at FROM admins ORDER BY created_at DESC LIMIT ? OFFSET ?";
        return db()->fetchAll($query, [$limit, $offset]);
    }

    /**
     * Compter les administrateurs
     */
    public static function count()
    {
        $result = db()->fetchOne("SELECT COUNT(*) as total FROM admins");
        return $result['total'] ?? 0;
    }

    /**
     * Mettre à jour un administrateur
     */
    public static function update($id, $nom, $prenom, $email)
    {
        if (empty($nom) || empty($prenom) || empty($email)) {
            return ['success' => false, 'message' => 'Tous les champs sont requis'];
        }

        if (!Security::validateEmail($email)) {
            return ['success' => false, 'message' => 'Email invalide'];
        }

        // Vérifier que l'email n'existe pas pour un autre admin
        $existing = db()->fetchOne("SELECT id FROM admins WHERE email = ? AND id != ?", [$email, $id]);
        if ($existing) {
            return ['success' => false, 'message' => 'Cet email est déjà utilisé par un autre administrateur'];
        }

        try {
            $query = "UPDATE admins SET nom = ?, prenom = ?, email = ? WHERE id = ?";
            db()->execute($query, [$nom, $prenom, $email, $id]);

            Security::logAction(Auth::getAdminId(), 'admin', 'Modification admin', "Modification de: $email");

            return ['success' => true, 'message' => 'Administrateur modifié avec succès'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Changer le mot de passe
     */
    public static function changePassword($id, $oldPassword, $newPassword)
    {
        if (empty($oldPassword) || empty($newPassword)) {
            return ['success' => false, 'message' => 'Tous les champs sont requis'];
        }

        if (!Security::validatePassword($newPassword)) {
            return ['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères, une majuscule et un chiffre'];
        }

        // Récupérer le mot de passe actuel
        $admin = db()->fetchOne("SELECT password FROM admins WHERE id = ?", [$id]);
        if (!$admin) {
            return ['success' => false, 'message' => 'Administrateur non trouvé'];
        }

        // Vérifier l'ancien mot de passe
        if (!Security::verifyPassword($oldPassword, $admin['password'])) {
            return ['success' => false, 'message' => 'Ancien mot de passe incorrect'];
        }

        try {
            $hashed = Security::hashPassword($newPassword);
            $query = "UPDATE admins SET password = ? WHERE id = ?";
            db()->execute($query, [$hashed, $id]);

            Security::logAction($id, 'admin', 'Changement mot de passe', 'Changement de mot de passe');

            return ['success' => true, 'message' => 'Mot de passe changé avec succès'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Réinitialiser le mot de passe (générer un nouveau)
     */
    public static function resetPassword($id)
    {
        $newPassword = bin2hex(random_bytes(4)); // Générer un mot de passe aléatoire
        $hashed = Security::hashPassword($newPassword);

        try {
            $query = "UPDATE admins SET password = ? WHERE id = ?";
            db()->execute($query, [$hashed, $id]);

            Security::logAction(Auth::getAdminId(), 'admin', 'Réinitialisation mot de passe', 'Réinitialisation effectuée');

            return ['success' => true, 'message' => 'Mot de passe réinitialisé', 'new_password' => $newPassword];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Supprimer un administrateur
     */
    public static function delete($id)
    {
        // Empêcher la suppression du dernier admin
        $count = db()->fetchOne("SELECT COUNT(*) as total FROM admins");
        if ($count['total'] <= 1) {
            return ['success' => false, 'message' => 'Impossible de supprimer le dernier administrateur'];
        }

        // Empêcher un admin de se supprimer lui-même
        if ($id == Auth::getAdminId()) {
            return ['success' => false, 'message' => 'Vous ne pouvez pas vous supprimer vous-même'];
        }

        try {
            $query = "DELETE FROM admins WHERE id = ?";
            db()->execute($query, [$id]);

            Security::logAction(Auth::getAdminId(), 'admin', 'Suppression admin', 'Suppression effectuée');

            return ['success' => true, 'message' => 'Administrateur supprimé avec succès'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }
}
?>