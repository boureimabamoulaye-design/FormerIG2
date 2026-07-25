<?php
/**
 * Gestion des administrateurs
 */
session_start();
require_once '../../config/database.php';
require_once '../../includes/Security.php';
require_once '../../includes/Auth.php';
require_once '../../includes/middleware.php';
require_once '../../includes/AdminManager.php';

requireAdmin();
checkSessionTimeout();

$page_title = 'Gestion des administrateurs';
$message = '';
$error = '';
$admins = [];

// Traiter les actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de sécurité invalide';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $result = AdminManager::create(
                trim($_POST['nom'] ?? ''),
                trim($_POST['prenom'] ?? ''),
                trim($_POST['email'] ?? ''),
                trim($_POST['password'] ?? '')
            );
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        } elseif ($action === 'update') {
            $result = AdminManager::update(
                $_POST['id'] ?? 0,
                trim($_POST['nom'] ?? ''),
                trim($_POST['prenom'] ?? ''),
                trim($_POST['email'] ?? '')
            );
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        } elseif ($action === 'delete') {
            $result = AdminManager::delete($_POST['id'] ?? 0);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        } elseif ($action === 'change_password') {
            $result = AdminManager::changePassword(
                $_POST['id'] ?? 0,
                trim($_POST['old_password'] ?? ''),
                trim($_POST['new_password'] ?? '')
            );
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        } elseif ($action === 'reset_password') {
            $result = AdminManager::resetPassword($_POST['id'] ?? 0);
            if ($result['success']) {
                $message = $result['message'] . ' - Nouveau mot de passe: <strong>' . htmlspecialchars($result['new_password']) . '</strong>';
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Récupérer la liste des admins
try {
    $page = max(1, intval($_GET['page'] ?? 1));
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    $admins = AdminManager::getAll($per_page, $offset);
    $total = AdminManager::count();
    $total_pages = ceil($total / $per_page);
} catch (Exception $e) {
    $error = 'Erreur: ' . $e->getMessage();
}

require_once 'includes/header.php';
?>

<style>
    .content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    .btn-danger {
        background: #e74c3c;
        color: white;
    }

    .btn-danger:hover {
        background: #c0392b;
    }

    .btn-warning {
        background: #f39c12;
        color: white;
    }

    .btn-warning:hover {
        background: #d68910;
    }

    .btn-small {
        padding: 6px 12px;
        font-size: 12px;
    }

    .alert {
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        background: white;
    }

    .table thead {
        background: #f5f6fa;
    }

    .table th {
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #666;
        font-size: 12px;
        text-transform: uppercase;
        border-bottom: 2px solid #e0e0e0;
    }

    .table td {
        padding: 12px;
        border-bottom: 1px solid #e0e0e0;
    }

    .table tbody tr:hover {
        background: #f9f9f9;
    }

    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 2000;
        justify-content: center;
        align-items: center;
    }

    .modal.active {
        display: flex;
    }

    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 10px;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #999;
    }

    .pagination {
        display: flex;
        gap: 5px;
        justify-content: center;
        margin-top: 30px;
    }

    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        text-decoration: none;
        color: #667eea;
    }

    .pagination a:hover {
        background: #667eea;
        color: white;
    }

    .pagination .active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }
</style>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="content-header">
    <h2 style="font-size: 24px; font-weight: 600; margin: 0;">Administrateurs</h2>
    <button class="btn btn-primary" onclick="openModal('addModal')">+ Ajouter un administrateur</button>
</div>

<!-- Modal Ajouter -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span>Ajouter un administrateur</span>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Nom *</label>
                    <input type="text" name="nom" required>
                </div>
                <div class="form-group">
                    <label>Prénom *</label>
                    <input type="text" name="prenom" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label>Mot de passe *</label>
                <input type="password" name="password" required>
                <small style="color: #999;">Min 6 caractères, 1 majuscule, 1 chiffre</small>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Créer</button>
                <button type="button" class="btn" style="background: #999; color: white;" onclick="closeModal('addModal')">Annuler</button>
            </div>
        </form>
    </div>
</div>

<!-- Table des administrateurs -->
<div class="card">
    <?php if (!empty($admins)): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Date de création</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?></strong></td>
                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($admin['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-primary btn-small" onclick="editAdmin(<?php echo $admin['id']; ?>)">✏️ Éditer</button>
                                <?php if ($admin['id'] != Auth::getAdminId()): ?>
                                    <button class="btn btn-danger btn-small" onclick="confirmDelete(<?php echo $admin['id']; ?>)">🗑️ Supprimer</button>
                                <?php endif; ?>
                                <button class="btn btn-warning btn-small" onclick="resetPassword(<?php echo $admin['id']; ?>)">🔑 Réinitialiser</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align: center; padding: 30px; color: #999;">Aucun administrateur trouvé</p>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="<?php echo ($page == $i) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<script>
    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }

    function editAdmin(id) {
        alert('Fonction d\'édition à implémenter');
    }

    function confirmDelete(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cet administrateur ?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="${id}">
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function resetPassword(id) {
        if (confirm('Êtes-vous sûr de vouloir réinitialiser le mot de passe ?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="id" value="${id}">
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>

<?php
require_once 'includes/footer.php';
?>