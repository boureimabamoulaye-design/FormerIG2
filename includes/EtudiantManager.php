<?php
/**
 * Classe pour gérer les étudiants
 */
class EtudiantManager
{
    /**
     * Créer un étudiant
     */
    public static function create($data)
    {
        // Validation
        $required = ['matricule', 'nom', 'prenom', 'email', 'telephone', 'adresse', 'password', 'classe_id', 'filiere_id'];
        foreach ($required as $field) {
            if (empty($data[$field] ?? '')) {
                return ['success' => false, 'message' => ucfirst($field) . ' est requis'];
            }
        }

        // Validations spécifiques
        if (!Security::validateMatricule($data['matricule'])) {
            return ['success' => false, 'message' => 'Matricule invalide (3-20 caractères alphanumériques)'];
        }

        if (!Security::validateEmail($data['email'])) {
            return ['success' => false, 'message' => 'Email invalide'];
        }

        if (!Security::validatePhone($data['telephone'])) {
            return ['success' => false, 'message' => 'Téléphone invalide'];
        }

        if (!Security::validatePassword($data['password'])) {
            return ['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères, une majuscule et un chiffre'];
        }

        // Vérifier les doublons
        $existing_matricule = db()->fetchOne("SELECT id FROM etudiants WHERE matricule = ?", [$data['matricule']]);
        if ($existing_matricule) {
            return ['success' => false, 'message' => 'Ce matricule existe déjà'];
        }

        $existing_email = db()->fetchOne("SELECT id FROM etudiants WHERE email = ?", [$data['email']]);
        if ($existing_email) {
            return ['success' => false, 'message' => 'Cet email est déjà utilisé'];
        }

        // Vérifier que la classe existe
        $classe = db()->fetchOne("SELECT id FROM classes WHERE id = ?", [$data['classe_id']]);
        if (!$classe) {
            return ['success' => false, 'message' => 'Classe invalide'];
        }

        // Gérer la photo
        $photo_name = null;
        if (!empty($_FILES['photo']['name'])) {
            if (!Security::validateFileType($_FILES['photo']['tmp_name'], ALLOWED_PHOTO_TYPES)) {
                return ['success' => false, 'message' => 'Type de photo invalide (JPEG, PNG, GIF uniquement)'];
            }

            if (!Security::validateFileSize($_FILES['photo']['size'])) {
                return ['success' => false, 'message' => 'Photo trop volumineux (50MB max)'];
            }

            $upload_dir = __DIR__ . '/../../uploads/photos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $photo_name = Security::generateSafeFileName($_FILES['photo']['name']);
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo_name)) {
                return ['success' => false, 'message' => 'Erreur lors de l\'upload de la photo'];
            }
        }

        try {
            $hashed_password = Security::hashPassword($data['password']);
            $query = "INSERT INTO etudiants (matricule, nom, prenom, email, telephone, adresse, photo, classe_id, filiere_id, password) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            db()->execute($query, [
                $data['matricule'],
                $data['nom'],
                $data['prenom'],
                $data['email'],
                $data['telephone'],
                $data['adresse'],
                $photo_name,
                $data['classe_id'],
                $data['filiere_id'],
                $hashed_password
            ]);

            Security::logAction(Auth::getAdminId(), 'admin', 'Création étudiant', 'Création: ' . $data['matricule']);

            return ['success' => true, 'message' => 'Étudiant créé avec succès'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Récupérer un étudiant par ID
     */
    public static function getById($id)
    {
        $query = "SELECT * FROM etudiants WHERE id = ? LIMIT 1";
        return db()->fetchOne($query, [$id]);
    }

    /**
     * Récupérer tous les étudiants avec pagination et filtres
     */
    public static function getAll($limit = 20, $offset = 0, $filters = [])
    {
        $query = "SELECT e.*, c.nom as classe_nom, f.nom as filiere_nom FROM etudiants e 
                  LEFT JOIN classes c ON e.classe_id = c.id 
                  LEFT JOIN filieres f ON e.filiere_id = f.id 
                  WHERE 1=1";
        $params = [];

        // Filtres
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query .= " AND (e.nom LIKE ? OR e.prenom LIKE ? OR e.email LIKE ? OR e.matricule LIKE ?)";
            $params = array_merge($params, [$search, $search, $search, $search]);
        }

        if (!empty($filters['classe_id'])) {
            $query .= " AND e.classe_id = ?";
            $params[] = $filters['classe_id'];
        }

        if (!empty($filters['filiere_id'])) {
            $query .= " AND e.filiere_id = ?";
            $params[] = $filters['filiere_id'];
        }

        $query .= " ORDER BY e.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return db()->fetchAll($query, $params);
    }

    /**
     * Compter les étudiants avec filtres
     */
    public static function count($filters = [])
    {
        $query = "SELECT COUNT(*) as total FROM etudiants WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR matricule LIKE ?)";
            $params = array_merge($params, [$search, $search, $search, $search]);
        }

        if (!empty($filters['classe_id'])) {
            $query .= " AND classe_id = ?";
            $params[] = $filters['classe_id'];
        }

        if (!empty($filters['filiere_id'])) {
            $query .= " AND filiere_id = ?";
            $params[] = $filters['filiere_id'];
        }

        $result = db()->fetchOne($query, $params);
        return $result['total'] ?? 0;
    }

    /**
     * Mettre à jour un étudiant
     */
    public static function update($id, $data)
    {
        $etudiant = self::getById($id);
        if (!$etudiant) {
            return ['success' => false, 'message' => 'Étudiant non trouvé'];
        }

        // Validation
        if (!empty($data['email']) && $data['email'] != $etudiant['email']) {
            if (!Security::validateEmail($data['email'])) {
                return ['success' => false, 'message' => 'Email invalide'];
            }

            $existing = db()->fetchOne("SELECT id FROM etudiants WHERE email = ? AND id != ?", [$data['email'], $id]);
            if ($existing) {
                return ['success' => false, 'message' => 'Cet email est déjà utilisé'];
            }
        }

        if (!empty($data['telephone']) && !Security::validatePhone($data['telephone'])) {
            return ['success' => false, 'message' => 'Téléphone invalide'];
        }

        // Gérer la nouvelle photo
        $photo = $etudiant['photo'];
        if (!empty($_FILES['photo']['name'])) {
            if (!Security::validateFileType($_FILES['photo']['tmp_name'], ALLOWED_PHOTO_TYPES)) {
                return ['success' => false, 'message' => 'Type de photo invalide'];
            }

            if (!Security::validateFileSize($_FILES['photo']['size'])) {
                return ['success' => false, 'message' => 'Photo trop volumineux'];
            }

            // Supprimer l'ancienne photo
            if ($photo) {
                $old_path = __DIR__ . '/../../uploads/photos/' . $photo;
                if (file_exists($old_path)) {
                    unlink($old_path);
                }
            }

            $upload_dir = __DIR__ . '/../../uploads/photos/';
            $photo = Security::generateSafeFileName($_FILES['photo']['name']);
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo)) {
                return ['success' => false, 'message' => 'Erreur lors de l\'upload'];
            }
        }

        try {
            $query = "UPDATE etudiants SET nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ?, photo = ?, classe_id = ?, filiere_id = ? WHERE id = ?";
            db()->execute($query, [
                $data['nom'] ?? $etudiant['nom'],
                $data['prenom'] ?? $etudiant['prenom'],
                $data['email'] ?? $etudiant['email'],
                $data['telephone'] ?? $etudiant['telephone'],
                $data['adresse'] ?? $etudiant['adresse'],
                $photo,
                $data['classe_id'] ?? $etudiant['classe_id'],
                $data['filiere_id'] ?? $etudiant['filiere_id'],
                $id
            ]);

            Security::logAction(Auth::getAdminId(), 'admin', 'Modification étudiant', 'Modification: ' . $etudiant['matricule']);

            return ['success' => true, 'message' => 'Étudiant modifié avec succès'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Supprimer un étudiant
     */
    public static function delete($id)
    {
        $etudiant = self::getById($id);
        if (!$etudiant) {
            return ['success' => false, 'message' => 'Étudiant non trouvé'];
        }

        try {
            // Supprimer la photo
            if ($etudiant['photo']) {
                $photo_path = __DIR__ . '/../../uploads/photos/' . $etudiant['photo'];
                if (file_exists($photo_path)) {
                    unlink($photo_path);
                }
            }

            // Supprimer l'étudiant (cascade dans les notes et bulletins)
            $query = "DELETE FROM etudiants WHERE id = ?";
            db()->execute($query, [$id]);

            Security::logAction(Auth::getAdminId(), 'admin', 'Suppression étudiant', 'Suppression: ' . $etudiant['matricule']);

            return ['success' => true, 'message' => 'Étudiant supprimé avec succès'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }
}
?>