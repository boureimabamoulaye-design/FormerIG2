<?php
/**
 * Classe de sécurité - Protection contre XSS, CSRF, SQL Injection
 */
class Security
{
    /**
     * Générer un token CSRF
     */
    public static function generateCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Vérifier un token CSRF
     */
    public static function verifyCSRFToken($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
    }

    /**
     * Échapper les caractères spéciaux (XSS)
     */
    public static function escape($data)
    {
        if (is_array($data)) {
            return array_map('htmlspecialchars', $data);
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Valider une adresse email
     */
    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valider un téléphone
     */
    public static function validatePhone($phone)
    {
        return preg_match('/^[0-9\s\-\+\(\)]{7,}$/', $phone);
    }

    /**
     * Valider un matricule
     */
    public static function validateMatricule($matricule)
    {
        return preg_match('/^[A-Z0-9]{3,20}$/', $matricule);
    }

    /**
     * Valider la force d'un mot de passe
     */
    public static function validatePassword($password)
    {
        return strlen($password) >= 6 && 
               preg_match('/[A-Z]/', $password) && 
               preg_match('/[0-9]/', $password);
    }

    /**
     * Hacher un mot de passe
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Vérifier un mot de passe
     */
    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Valider un type de fichier
     */
    public static function validateFileType($filePath, $allowedTypes)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        return in_array($mimeType, $allowedTypes);
    }

    /**
     * Valider la taille d'un fichier
     */
    public static function validateFileSize($fileSize, $maxSize = UPLOAD_MAX_SIZE)
    {
        return $fileSize <= $maxSize;
    }

    /**
     * Générer un nom de fichier sécurisé
     */
    public static function generateSafeFileName($originalName)
    {
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        return bin2hex(random_bytes(16)) . '.' . strtolower($ext);
    }

    /**
     * Obtenir l'adresse IP du client
     */
    public static function getClientIP()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return trim($ip);
    }

    /**
     * Obtenir l'user agent
     */
    public static function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Enregistrer une action dans l'historique
     */
    public static function logAction($userId, $typeUtilisateur, $action, $description = null)
    {
        try {
            $query = "INSERT INTO historique (utilisateur_id, type_utilisateur, action, description, adresse_ip, user_agent) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            
            db()->execute($query, [
                $userId,
                $typeUtilisateur,
                $action,
                $description,
                self::getClientIP(),
                self::getUserAgent()
            ]);
        } catch (Exception $e) {
            // Silent fail pour l'historique
        }
    }

    /**
     * Vérifier si la requête est AJAX
     */
    public static function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Rediriger de manière sécurisée
     */
    public static function redirect($url, $statusCode = 302)
    {
        if (headers_sent()) {
            echo '<script>window.location.href="' . htmlspecialchars($url) . '";</script>';
        } else {
            header('Location: ' . $url, true, $statusCode);
        }
        exit();
    }
}
?>