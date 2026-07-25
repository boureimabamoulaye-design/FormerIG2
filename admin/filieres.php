<?php
/**
 * Gestion des filières
 */
session_start();
require_once '../../config/database.php';
require_once '../../includes/Security.php';
require_once '../../includes/Auth.php';
require_once '../../includes/middleware.php';
require_once '../../includes/DataManager.php';

requireAdmin();
checkSessionTimeout();

$page_title = 'Gestion des filières';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token invalide';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            $result = FiliereManager::create(trim($_POST['nom'] ?? ''), trim($_POST['description'] ?? ''));
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        } elseif ($action === 'update') {
            $result = FiliereManager::update($_POST['id'] ?? 0, trim($_POST['nom'] ?? ''), trim($_POST['description'] ?? ''));
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        } elseif ($action === 'delete') {
            $result = FiliereManager::delete($_POST['id'] ?? 0);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;
$filieres = FiliereManager::getAll($per_page, $offset);
$total = FiliereManager::count();
$total_pages = ceil($total / $per_page);

require_once 'includes/header.php';
?>

<style>
    .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s ease; text-decoration: none; display: inline-block; }
    .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
    .btn-danger { background: #e74c3c; color: white; }
    .btn-danger:hover { background: #c0392b; }
    .btn-small { padding: 6px 12px; font-size: 12px; }
    .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08); margin-bottom: 20px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
    .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; font-family: inherit; }
    .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
    .table { width: 100%; border-collapse: collapse; }
    .table thead { background: #f5f6fa; }
    .table th { padding: 12px; text-align: left; font-weight: 600; color: #666; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #e0e0e0; }
    .table td { padding: 12px; border-bottom: 1px solid #e0e0e0; }
    .table tbody tr:hover { background: #f9f9f9; }
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 2000; justify-content: center; align-items: center; }
    .modal.active { display: flex; }
    .modal-content { background: white; padding: 30px; border-radius: 10px; max-width: 500px; width: 90%; }
    .modal-header { font-size: 20px; font-weight: 600; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
    .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #999; }
</style>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2 style="font-size: 24px; font-weight: 600; margin: 0;">Filières (<?php echo $total; ?>)</h2>
    <button class="btn btn-primary" onclick="openModal('addModal')">+ Ajouter</button>
</div>

<!-- Modal Ajouter -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span>Nouvelle filière</span>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
            <div class="form-group"><label>Nom *</label><input type="text" name="nom" required></div>
            <div class="form-group"><label>Description</label><textarea name="description" rows="3"></textarea></div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Créer</button>
                <button type="button" class="btn" style="background: #999; color: white;" onclick="closeModal('addModal')">Annuler</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <?php if (!empty($filieres)): ?>
        <table class="table">
            <thead>
                <tr><th>Nom</th><th>Description</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($filieres as $filiere): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($filiere['nom']); ?></strong></td>
                        <td><?php echo htmlspecialchars(substr($filiere['description'] ?? '', 0, 50)); ?></td>
                        <td>
                            <button class="btn btn-primary btn-small" onclick="editFiliere(<?php echo $filiere['id']; ?>, '<?php echo htmlspecialchars($filiere['nom']); ?>', '<?php echo htmlspecialchars($filiere['description'] ?? ''); ?>')">Edit</button>
                            <button class="btn btn-danger btn-small" onclick="confirmDelete(<?php echo $filiere['id']; ?>)">Del</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align: center; padding: 30px; color: #999;">Aucune filière</p>
    <?php endif; ?>
</div>

<script>
    function openModal(id) { document.getElementById(id).classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }
    function editFiliere(id, nom, desc) { alert('Edit: ' + nom); }
    function confirmDelete(id) {
        if (confirm('Supprimer?')) {
            const f = document.createElement('form'); f.method = 'POST';
            f.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${id}"><input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">`;  
            document.body.appendChild(f); f.submit();
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>