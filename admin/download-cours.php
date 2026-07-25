<?php
/**
 * Télécharger un fichier de cours
 */
session_start();
require_once '../../config/database.php';
require_once '../../includes/Security.php';
require_once '../../includes/Auth.php';
require_once '../../includes/middleware.php';
require_once '../../includes/CoursManager.php';

// Vérifier que c'est un admin
requireAdmin();

if (empty($_GET['id'])) {
    http_response_code(404);
    die('Cours non trouvé');
}

$result = CoursManager::downloadPDF($_GET['id']);

if (!$result['success']) {
    http_response_code(404);
    die(htmlspecialchars($result['message']));
}

$file_path = $result['file_path'];
$file_name = $result['file_name'];

if (!file_exists($file_path)) {
    http_response_code(404);
    die('Fichier non trouvé');
}

// Headers pour le téléchargement
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: private, max-age=0, must-revalidate');

// Envoyer le fichier
readfile($file_path);
exit();
?>