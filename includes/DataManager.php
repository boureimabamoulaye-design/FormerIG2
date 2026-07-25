<?php
/**
 * Classe pour gérer les filières
 */
class FiliereManager
{
    public static function create($nom, $description = '')
    {
        if (empty($nom)) {
            return ['success' => false, 'message' => 'Le nom est requis'];
        }

        // Vérifier les doublons
        $existing = db()->fetchOne("SELECT id FROM filieres WHERE nom = ?", [$nom]);
        if ($existing) {
            return ['success' => false, 'message' => 'Cette filière existe déjà'];
        }

        try {
            $query = "INSERT INTO filieres (nom, description) VALUES (?, ?)";
            db()->execute($query, [$nom, $description]);
            Security::logAction(Auth::getAdminId(), 'admin', 'Création filière', "Filière: $nom");
            return ['success' => true, 'message' => 'Filière créée'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    public static function getAll($limit = 50, $offset = 0)
    {
        $query = "SELECT * FROM filieres ORDER BY nom LIMIT ? OFFSET ?";
        return db()->fetchAll($query, [$limit, $offset]);
    }

    public static function getById($id)
    {
        return db()->fetchOne("SELECT * FROM filieres WHERE id = ?", [$id]);
    }

    public static function count()
    {
        $result = db()->fetchOne("SELECT COUNT(*) as total FROM filieres");
        return $result['total'] ?? 0;
    }

    public static function update($id, $nom, $description = '')
    {
        if (empty($nom)) {
            return ['success' => false, 'message' => 'Le nom est requis'];
        }

        $existing = db()->fetchOne("SELECT id FROM filieres WHERE nom = ? AND id != ?", [$nom, $id]);
        if ($existing) {
            return ['success' => false, 'message' => 'Une filière avec ce nom existe déjà'];
        }

        try {
            $query = "UPDATE filieres SET nom = ?, description = ? WHERE id = ?";
            db()->execute($query, [$nom, $description, $id]);
            Security::logAction(Auth::getAdminId(), 'admin', 'Modification filière', "Filière: $nom");
            return ['success' => true, 'message' => 'Filière modifiée'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    public static function delete($id)
    {
        // Vérifier s'il y a des classes liées
        $classes = db()->fetchOne("SELECT COUNT(*) as total FROM classes WHERE filiere_id = ?", [$id]);
        if ($classes['total'] > 0) {
            return ['success' => false, 'message' => 'Impossible: des classes sont liées à cette filière'];
        }

        try {
            $query = "DELETE FROM filieres WHERE id = ?";
            db()->execute($query, [$id]);
            Security::logAction(Auth::getAdminId(), 'admin', 'Suppression filière', 'Suppression effectuée');
            return ['success' => true, 'message' => 'Filière supprimée'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }
}

/**
 * Classe pour gérer les classes
 */
class ClasseManager
{
    public static function create($nom, $filiere_id)
    {
        if (empty($nom) || empty($filiere_id)) {
            return ['success' => false, 'message' => 'Tous les champs sont requis'];
        }

        $existing = db()->fetchOne("SELECT id FROM classes WHERE nom = ? AND filiere_id = ?", [$nom, $filiere_id]);
        if ($existing) {
            return ['success' => false, 'message' => 'Cette classe existe déjà pour cette filière'];
        }

        try {
            $query = "INSERT INTO classes (nom, filiere_id) VALUES (?, ?)";
            db()->execute($query, [$nom, $filiere_id]);
            Security::logAction(Auth::getAdminId(), 'admin', 'Création classe', "Classe: $nom");
            return ['success' => true, 'message' => 'Classe créée'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    public static function getAll($limit = 50, $offset = 0)
    {
        $query = "SELECT c.*, f.nom as filiere_nom FROM classes c 
                  LEFT JOIN filieres f ON c.filiere_id = f.id 
                  ORDER BY f.nom, c.nom LIMIT ? OFFSET ?";
        return db()->fetchAll($query, [$limit, $offset]);
    }

    public static function getByFiliere($filiere_id)
    {
        $query = "SELECT * FROM classes WHERE filiere_id = ? ORDER BY nom";
        return db()->fetchAll($query, [$filiere_id]);
    }

    public static function getById($id)
    {
        return db()->fetchOne("SELECT * FROM classes WHERE id = ?", [$id]);
    }

    public static function count()
    {
        $result = db()->fetchOne("SELECT COUNT(*) as total FROM classes");
        return $result['total'] ?? 0;
    }

    public static function update($id, $nom, $filiere_id)
    {
        if (empty($nom) || empty($filiere_id)) {
            return ['success' => false, 'message' => 'Tous les champs sont requis'];
        }

        $existing = db()->fetchOne("SELECT id FROM classes WHERE nom = ? AND filiere_id = ? AND id != ?", [$nom, $filiere_id, $id]);
        if ($existing) {
            return ['success' => false, 'message' => 'Cette classe existe déjà'];
        }

        try {
            $query = "UPDATE classes SET nom = ?, filiere_id = ? WHERE id = ?";
            db()->execute($query, [$nom, $filiere_id, $id]);
            Security::logAction(Auth::getAdminId(), 'admin', 'Modification classe', "Classe: $nom");
            return ['success' => true, 'message' => 'Classe modifiée'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    public static function delete($id)
    {
        $etudiants = db()->fetchOne("SELECT COUNT(*) as total FROM etudiants WHERE classe_id = ?", [$id]);
        if ($etudiants['total'] > 0) {
            return ['success' => false, 'message' => 'Impossible: des étudiants sont liés à cette classe'];
        }

        try {
            $query = "DELETE FROM classes WHERE id = ?";
            db()->execute($query, [$id]);
            Security::logAction(Auth::getAdminId(), 'admin', 'Suppression classe', 'Suppression effectuée');
            return ['success' => true, 'message' => 'Classe supprimée'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }
}

/**
 * Classe pour gérer les semestres
 */
class SemestreManager
{
    public static function create($nom, $numero, $description = '')
    {
        if (empty($nom) || empty($numero)) {
            return ['success' => false, 'message' => 'Tous les champs sont requis'];
        }

        $existing_numero = db()->fetchOne("SELECT id FROM semestres WHERE numero = ?", [$numero]);
        if ($existing_numero) {
            return ['success' => false, 'message' => 'Ce numéro de semestre existe déjà'];
        }

        try {
            $query = "INSERT INTO semestres (nom, numero, description) VALUES (?, ?, ?)";
            db()->execute($query, [$nom, $numero, $description]);
            Security::logAction(Auth::getAdminId(), 'admin', 'Création semestre', "Semestre: $nom");
            return ['success' => true, 'message' => 'Semestre créé'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    public static function getAll($limit = 50, $offset = 0)
    {
        $query = "SELECT * FROM semestres ORDER BY numero LIMIT ? OFFSET ?";
        return db()->fetchAll($query, [$limit, $offset]);
    }

    public static function getById($id)
    {
        return db()->fetchOne("SELECT * FROM semestres WHERE id = ?", [$id]);
    }

    public static function count()
    {
        $result = db()->fetchOne("SELECT COUNT(*) as total FROM semestres");
        return $result['total'] ?? 0;
    }

    public static function update($id, $nom, $numero, $description = '')
    {
        if (empty($nom) || empty($numero)) {
            return ['success' => false, 'message' => 'Tous les champs sont requis'];
        }

        $existing = db()->fetchOne("SELECT id FROM semestres WHERE numero = ? AND id != ?", [$numero, $id]);
        if ($existing) {
            return ['success' => false, 'message' => 'Ce numéro de semestre existe déjà'];
        }

        try {
            $query = "UPDATE semestres SET nom = ?, numero = ?, description = ? WHERE id = ?";
            db()->execute($query, [$nom, $numero, $description, $id]);
            Security::logAction(Auth::getAdminId(), 'admin', 'Modification semestre', "Semestre: $nom");
            return ['success' => true, 'message' => 'Semestre modifié'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    public static function delete($id)
    {
        $cours = db()->fetchOne("SELECT COUNT(*) as total FROM cours WHERE semestre_id = ?", [$id]);
        if ($cours['total'] > 0) {
            return ['success' => false, 'message' => 'Impossible: des cours sont liés à ce semestre'];
        }

        try {
            $query = "DELETE FROM semestres WHERE id = ?";
            db()->execute($query, [$id]);
            Security::logAction(Auth::getAdminId(), 'admin', 'Suppression semestre', 'Suppression effectuée');
            return ['success' => true, 'message' => 'Semestre supprimé'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }
}

/**
 * Classe pour gérer les enseignants
 */
class EnseignantManager
{
    public static function create($nom, $prenom, $email = '', $telephone = '')
    {
        if (empty($nom) || empty($prenom)) {
            return ['success' => false, 'message' => 'Nom et prénom requis'];
        }

        if (!empty($email)) {
            if (!Security::validateEmail($email)) {
                return ['success' => false, 'message' => 'Email invalide'];
            }
            $existing = db()->fetchOne("SELECT id FROM enseignants WHERE email = ?", [$email]);
            if ($existing) {
                return ['success' => false, 'message' => 'Cet email existe déjà'];
            }
        }

        try {
            $query = "INSERT INTO enseignants (nom, prenom, email, telephone) VALUES (?, ?, ?, ?)";
            db()->execute($query, [$nom, $prenom, $email, $telephone]);
            Security::logAction(Auth::getAdminId(), 'admin', 'Création enseignant', "Enseignant: $prenom $nom");
            return ['success' => true, 'message' => 'Enseignant créé'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    public static function getAll($limit = 50, $offset = 0)
    {
        $query = "SELECT * FROM enseignants ORDER BY nom LIMIT ? OFFSET ?";
        return db()->fetchAll($query, [$limit, $offset]);
    }

    public static function getById($id)
    {
        return db()->fetchOne("SELECT * FROM enseignants WHERE id = ?", [$id]);
    }

    public static function count()
    {
        $result = db()->fetchOne("SELECT COUNT(*) as total FROM enseignants");
        return $result['total'] ?? 0;
    }

    public static function update($id, $nom, $prenom, $email = '', $telephone = '')
    {
        if (empty($nom) || empty($prenom)) {
            return ['success' => false, 'message' => 'Nom et prénom requis'];
        }

        if (!empty($email)) {
            if (!Security::validateEmail($email)) {
                return ['success' => false, 'message' => 'Email invalide'];
            }
            $existing = db()->fetchOne("SELECT id FROM enseignants WHERE email = ? AND id != ?", [$email, $id]);
            if ($existing) {
                return ['success' => false, 'message' => 'Cet email existe déjà'];
            }
        }

        try {
            $query = "UPDATE enseignants SET nom = ?, prenom = ?, email = ?, telephone = ? WHERE id = ?";
            db()->execute($query, [$nom, $prenom, $email, $telephone, $id]);
            Security::logAction(Auth::getAdminId(), 'admin', 'Modification enseignant', "Enseignant: $prenom $nom");
            return ['success' => true, 'message' => 'Enseignant modifié'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    public static function delete($id)
    {
        $cours = db()->fetchOne("SELECT COUNT(*) as total FROM cours WHERE enseignant_id = ?", [$id]);
        if ($cours['total'] > 0) {
            return ['success' => false, 'message' => 'Impossible: des cours sont liés à cet enseignant'];
        }

        try {
            $query = "DELETE FROM enseignants WHERE id = ?";
            db()->execute($query, [$id]);
            Security::logAction(Auth::getAdminId(), 'admin', 'Suppression enseignant', 'Suppression effectuée');
            return ['success' => true, 'message' => 'Enseignant supprimé'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }
}
?>