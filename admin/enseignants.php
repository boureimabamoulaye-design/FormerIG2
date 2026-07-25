<?php
/**
 * Gestion des enseignants
 */
session_start();
require_once '../../config/database.php';
require_once '../../includes/Security.php';
require_once '../../includes/Auth.php';
require_once '../../includes/middleware.php';
require_once '../../includes/DataManager.php';

requireAdmin();
checkSessionTimeout();

$page_title = 'Gestion des enseignants';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token invalide';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            $result = EnseignantManager::create(
                trim($_POST['nom'] ?? ''),
                trim($_POST['prenom'] ?? ''),
                trim($_POST['email'] ?? ''),
                trim($_POST['telephone'] ?? '')
            );
            if ($result['success']) { $message = $result['message']; } else { $error = $result['message']; }
        } elseif ($action === 'delete') {
            $result = EnseignantManager::delete($_POST['id'] ?? 0);
            if ($result['success']) { $message = $result['message']; } else { $error = $result['message']; }
        }
    }
}

$page = max(1, intval($_GET['page'] ?? 1));
$enseignants = EnseignantManager::getAll(20, ($page - 1) * 20);
$total = EnseignantManager::count();
$total_pages = ceil($total / 20);

require_once 'includes/header.php';
?>

<style>
    .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: 600; }
    .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .btn-danger { background: #e74c3c; color: white; }
    .btn-small { padding: 6px 12px; font-size: 12px; }
    .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    .alert-success { background: #d4edda; color: #155724; }
    .alert-danger { background: #f8d7da; color: #721c24; }
    .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08); }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
    .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
    .table { width: 100%; border-collapse: collapse; }
    .table th { padding: 12px; background: #f5f6fa; font-weight: 600; text-align: left; border-bottom: 2px solid #e0e0e0; }
    .table td { padding: 12px; border-bottom: 1px solid #e0e0e0; }
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 2000; justify-content: center; align-items: center; }
    .modal.active { display: flex; }
    .modal-content { background: white; padding: 30px; border-radius: 10px; max-width: 500px; width: 90%; }
</style>

<?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2>Enseignants (<?php echo $total; ?>)</h2>
    <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('active')">+ Ajouter</button>
</div>

<div id="addModal" class="modal">
    <div class="modal-content">
        <h3>Nouvel enseignant</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
            <div class="form-group"><label>Nom *</label><input type="text" name="nom" required></div>
            <div class="form-group"><label>Prénom *</label><input type="text" name="prenom" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email"></div>
            <div class="form-group"><label>Téléphone</label><input type="tel" name="telephone"></div>
            <button type="submit" class="btn btn-primary">Créer</button>
            <button type="button" class="btn" onclick="this.closest('.modal').classList.remove('active')" style="background: #999; color: white;">Annuler</button>
        </form>
    </div>
</div>

<div class="card">
    <?php if (!empty($enseignants)): ?>
        <table class="table">
            <thead><tr><th>Nom</th><th>Email</th><th>Téléphone</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($enseignants as $ens): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($ens['prenom'] . ' ' . $ens['nom']); ?></strong></td>
                        <td><?php echo htmlspecialchars($ens['email'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($ens['telephone'] ?? 'N/A'); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $ens['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                <button type="submit" class="btn btn-danger btn-small" onclick="return confirm('Supprimer?')">Del</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align: center; padding: 30px;">Aucun enseignant</p>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>