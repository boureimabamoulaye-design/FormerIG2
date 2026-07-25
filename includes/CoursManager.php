<?php
/**
 * Classe pour gérer les cours
 */
class CoursManager
{
    /**
     * Créer un cours
     */
    public static function create($data)
    {
        $required = ['nom', 'code', 'filiere_id', 'classe_id', 'semestre_id'];
        foreach ($required as $field) {
            if (empty($data[$field] ?? '')) {
                return ['success' => false, 'message' => ucfirst($field) . ' est requis'];
            }
        }

        // Vérifier les doublons
        $existing = db()->fetchOne("SELECT id FROM cours WHERE code = ?", [$data['code']]);
        if ($existing) {
            return ['success' => false, 'message' => 'Ce code de cours existe déjà'];
        }

        // Gérer l'upload du PDF
        $fichier_pdf = null;
        if (!empty($_FILES['fichier_pdf']['name'])) {
            if (!Security::validateFileType($_FILES['fichier_pdf']['tmp_name'], ALLOWED_DOC_TYPES)) {
                return ['success' => false, 'message' => 'Seuls les fichiers PDF sont acceptés'];
            }

            if (!Security::validateFileSize($_FILES['fichier_pdf']['size'])) {
                return ['success' => false, 'message' => 'Fichier trop volumineux (50MB max)'];
            }

            $upload_dir = __DIR__ . '/../../uploads/cours/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $fichier_pdf = Security::generateSafeFileName($_FILES['fichier_pdf']['name']);
            if (!move_uploaded_file($_FILES['fichier_pdf']['tmp_name'], $upload_dir . $fichier_pdf)) {
                return ['success' => false, 'message' => 'Erreur lors de l\'upload du fichier'];
            }
        }

        try {
            $query = "INSERT INTO cours (nom, code, description, filiere_id, classe_id, semestre_id, enseignant_id, fichier_pdf) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            db()->execute($query, [
                $data['nom'],
                $data['code'],
                $data['description'] ?? '',
                $data['filiere_id'],
                $data['classe_id'],
                $data['semestre_id'],
                $data['enseignant_id'] ?? null,
                $fichier_pdf
            ]);

            Security::logAction(Auth::getAdminId(), 'admin', 'Création cours', 'Cours: ' . $data['nom']);

            return ['success' => true, 'message' => 'Cours créé avec succès'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Récupérer tous les cours
     */
    public static function getAll($limit = 20, $offset = 0, $filters = [])
    {
        $query = "SELECT c.*, f.nom as filiere_nom, cl.nom as classe_nom, s.nom as semestre_nom, 
                         e.prenom as enseignant_prenom, e.nom as enseignant_nom 
                  FROM cours c 
                  LEFT JOIN filieres f ON c.filiere_id = f.id 
                  LEFT JOIN classes cl ON c.classe_id = cl.id 
                  LEFT JOIN semestres s ON c.semestre_id = s.id 
                  LEFT JOIN enseignants e ON c.enseignant_id = e.id 
                  WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query .= " AND (c.nom LIKE ? OR c.code LIKE ?)";
            $params = array_merge($params, [$search, $search]);
        }

        if (!empty($filters['semestre_id'])) {
            $query .= " AND c.semestre_id = ?";
            $params[] = $filters['semestre_id'];
        }

        if (!empty($filters['classe_id'])) {
            $query .= " AND c.classe_id = ?";
            $params[] = $filters['classe_id'];
        }

        $query .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return db()->fetchAll($query, $params);
    }

    /**
     * Compter les cours
     */
    public static function count($filters = [])
    {
        $query = "SELECT COUNT(*) as total FROM cours WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query .= " AND (nom LIKE ? OR code LIKE ?)";
            $params = array_merge($params, [$search, $search]);
        }

        if (!empty($filters['semestre_id'])) {
            $query .= " AND semestre_id = ?";
            $params[] = $filters['semestre_id'];
        }

        if (!empty($filters['classe_id'])) {
            $query .= " AND classe_id = ?";
            $params[] = $filters['classe_id'];
        }

        $result = db()->fetchOne($query, $params);
        return $result['total'] ?? 0;
    }

    /**
     * Récupérer un cours par ID
     */
    public static function getById($id)
    {
        $query = "SELECT c.*, f.nom as filiere_nom, cl.nom as classe_nom, s.nom as semestre_nom 
                  FROM cours c 
                  LEFT JOIN filieres f ON c.filiere_id = f.id 
                  LEFT JOIN classes cl ON c.classe_id = cl.id 
                  LEFT JOIN semestres s ON c.semestre_id = s.id 
                  WHERE c.id = ?";
        return db()->fetchOne($query, [$id]);
    }

    /**
     * Mettre à jour un cours
     */
    public static function update($id, $data)
    {
        $cours = self::getById($id);
        if (!$cours) {
            return ['success' => false, 'message' => 'Cours non trouvé'];
        }

        if (!empty($data['code']) && $data['code'] != $cours['code']) {
            $existing = db()->fetchOne("SELECT id FROM cours WHERE code = ? AND id != ?", [$data['code'], $id]);
            if ($existing) {
                return ['success' => false, 'message' => 'Ce code existe déjà'];
            }
        }

        // Gérer le nouveau PDF
        $fichier = $cours['fichier_pdf'];
        if (!empty($_FILES['fichier_pdf']['name'])) {
            if (!Security::validateFileType($_FILES['fichier_pdf']['tmp_name'], ALLOWED_DOC_TYPES)) {
                return ['success' => false, 'message' => 'Seuls les fichiers PDF sont acceptés'];
            }

            if (!Security::validateFileSize($_FILES['fichier_pdf']['size'])) {
                return ['success' => false, 'message' => 'Fichier trop volumineux'];
            }

            // Supprimer l'ancien PDF
            if ($fichier) {
                $old_path = __DIR__ . '/../../uploads/cours/' . $fichier;
                if (file_exists($old_path)) {
                    unlink($old_path);
                }
            }

            $upload_dir = __DIR__ . '/../../uploads/cours/';
            $fichier = Security::generateSafeFileName($_FILES['fichier_pdf']['name']);
            if (!move_uploaded_file($_FILES['fichier_pdf']['tmp_name'], $upload_dir . $fichier)) {
                return ['success' => false, 'message' => 'Erreur lors de l\'upload'];
            }
        }

        try {
            $query = "UPDATE cours SET nom = ?, code = ?, description = ?, filiere_id = ?, classe_id = ?, semestre_id = ?, enseignant_id = ?, fichier_pdf = ? WHERE id = ?";
            db()->execute($query, [
                $data['nom'] ?? $cours['nom'],
                $data['code'] ?? $cours['code'],
                $data['description'] ?? $cours['description'],
                $data['filiere_id'] ?? $cours['filiere_id'],
                $data['classe_id'] ?? $cours['classe_id'],
                $data['semestre_id'] ?? $cours['semestre_id'],
                $data['enseignant_id'] ?? $cours['enseignant_id'],
                $fichier,
                $id
            ]);

            Security::logAction(Auth::getAdminId(), 'admin', 'Modification cours', 'Cours: ' . $cours['nom']);

            return ['success' => true, 'message' => 'Cours modifié avec succès'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Supprimer un cours
     */
    public static function delete($id)
    {
        $cours = self::getById($id);
        if (!$cours) {
            return ['success' => false, 'message' => 'Cours non trouvé'];
        }

        try {
            // Supprimer le PDF
            if ($cours['fichier_pdf']) {
                $pdf_path = __DIR__ . '/../../uploads/cours/' . $cours['fichier_pdf'];
                if (file_exists($pdf_path)) {
                    unlink($pdf_path);
                }
            }

            // Supprimer les notes associées
            db()->execute("DELETE FROM notes WHERE cours_id = ?", [$id]);

            // Supprimer le cours
            $query = "DELETE FROM cours WHERE id = ?";
            db()->execute($query, [$id]);

            Security::logAction(Auth::getAdminId(), 'admin', 'Suppression cours', 'Suppression: ' . $cours['nom']);

            return ['success' => true, 'message' => 'Cours supprimé avec succès'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Télécharger un PDF de cours
     */
    public static function downloadPDF($id)
    {
        $cours = self::getById($id);
        if (!$cours || !$cours['fichier_pdf']) {
            return ['success' => false, 'message' => 'Fichier non trouvé'];
        }

        $file_path = __DIR__ . '/../../uploads/cours/' . $cours['fichier_pdf'];
        if (!file_exists($file_path)) {
            return ['success' => false, 'message' => 'Fichier inexistant'];
        }

        // Enregistrer le téléchargement
        Security::logAction(Auth::getEtudiantId(), 'etudiant', 'Téléchargement cours', 'Cours: ' . $cours['nom']);

        return ['success' => true, 'file_path' => $file_path, 'file_name' => $cours['nom'] . '.pdf'];
    }
}
?>