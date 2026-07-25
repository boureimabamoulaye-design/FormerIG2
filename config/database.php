<?php
/**
 * Configuration de connexion à la base de données
 * Connexion PDO MySQL 8.0+
 * Compatible WAMP et XAMPP
 */

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gestio_scolaire');
define('DB_PORT', 3306);

// Options de sécurité
define('APP_DEBUG', false);
define('SESSION_TIMEOUT', 3600); // 1 heure
define('UPLOAD_MAX_SIZE', 52428800); // 50MB
define('ALLOWED_PHOTO_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('ALLOWED_DOC_TYPES', ['application/pdf']);

/**
 * Classe Database - Gestion de la connexion PDO
 */
class Database
{
    private static $instance = null;
    private $connection;

    /**
     * Singleton - Obtenir l'instance de connexion
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructeur - Établir la connexion PDO
     */
    private function __construct()
    {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            
            $this->connection = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
        } catch (PDOException $e) {
            die('Erreur de connexion à la base de données: ' . $e->getMessage());
        }
    }

    /**
     * Obtenir la connexion
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Exécuter une requête préparée
     */
    public function execute($query, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception('Erreur SQL: ' . $e->getMessage());
        }
    }

    /**
     * Récupérer une seule ligne
     */
    public function fetchOne($query, $params = [])
    {
        $stmt = $this->execute($query, $params);
        return $stmt->fetch();
    }

    /**
     * Récupérer toutes les lignes
     */
    public function fetchAll($query, $params = [])
    {
        $stmt = $this->execute($query, $params);
        return $stmt->fetchAll();
    }

    /**
     * Obtenir le dernier ID inséré
     */
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Commencer une transaction
     */
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Valider une transaction
     */
    public function commit()
    {
        return $this->connection->commit();
    }

    /**
     * Annuler une transaction
     */
    public function rollback()
    {
        return $this->connection->rollBack();
    }

    /**
     * Empêcher le clonage
     */
    private function __clone() {}

    /**
     * Empêcher la désérialisation
     */
    public function __wakeup()
    {
        throw new Exception("Impossible de désérialiser la connexion");
    }
}

// Alias rapide pour accéder à la base de données
function db()
{
    return Database::getInstance();
}
?>
