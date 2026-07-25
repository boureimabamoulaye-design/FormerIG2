<?php
/**
 * Gestion des étudiants - CRUD complet
 */
session_start();
require_once '../../config/database.php';
require_once '../../includes/Security.php';
require_once '../../includes/Auth.php';
require_once '../../includes/middleware.php';
require_once '../../includes/EtudiantManager.php';

requireAdmin();
checkSessionTimeout();

$page_title = 'Gestion des étudiants';
$message = '';
$error = '';
$etudiants = [];
$filieres = [];
$classes = [];

// Charger les filières et classes
try {
    $filieres = db()->fetchAll("SELECT id, nom FROM filieres ORDER BY nom");
    $classes = db()->fetchAll("SELECT id, nom FROM classes ORDER BY nom");
} catch (Exception $e) {
    $error = 'Erreur lors du chargement des données: ' . $e->getMessage();
}

// Traiter les actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de sécurité invalide';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $result = EtudiantManager::create([
                'matricule' => trim($_POST['matricule'] ?? ''),
                'nom' => trim($_POST['nom'] ?? ''),
                'prenom' => trim($_POST['prenom'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'telephone' => trim($_POST['telephone'] ?? ''),
                'adresse' => trim($_POST['adresse'] ?? ''),
                'password' => trim($_POST['password'] ?? ''),
                'classe_id' => $_POST['classe_id'] ?? 0,
                'filiere_id' => $_POST['filiere_id'] ?? 0
            ]);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        } elseif ($action === 'delete') {
            $result = EtudiantManager::delete($_POST['id'] ?? 0);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Récupérer les étudiants avec filtres
try {
    $page = max(1, intval($_GET['page'] ?? 1));
    $per_page = 20;
    $offset = ($page - 1) * $per_page;

    $filters = [];
    if (!empty($_GET['search'])) {
        $filters['search'] = $_GET['search'];
    }
    if (!empty($_GET['classe_id'])) {
        $filters['classe_id'] = $_GET['classe_id'];
    }
    if (!empty($_GET['filiere_id'])) {
        $filters['filiere_id'] = $_GET['filiere_id'];
    }

    $etudiants = EtudiantManager::getAll($per_page, $offset, $filters);
    $total = EtudiantManager::count($filters);
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

    .btn-secondary {
        background: #95a5a6;
        color: white;
    }

    .btn-secondary:hover {
        background: #7f8c8d;
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
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
        font-family: inherit;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }

    .filter-section {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
        padding: 15px;
        background: #f5f6fa;
        border-radius: 8px;
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

    .photo-thumbnail {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .photo-thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: cover;
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
        overflow-y: auto;
    }

    .modal.active {
        display: flex;
    }

    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 10px;
        max-width: 600px;
        width: 90%;
        margin: 20px auto;
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
        flex-wrap: wrap;
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

    .file-input-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
    }

    .file-input-wrapper input[type=file] {
        position: absolute;
        left: -9999px;
    }

    .file-input-label {
        display: inline-block;
        padding: 10px 15px;
        background: #667eea;
        color: white;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
    }

    .file-name {
        margin-top: 5px;
        font-size: 12px;
        color: #666;
    }

    @media (max-width: 768px) {
        .content-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .filter-section {
            grid-template-columns: 1fr;
        }

        .table {
            font-size: 12px;
        }

        .table th,
        .table td {
            padding: 8px;
        }

        .action-buttons {
            flex-direction: column;
        }
    }
</style>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="content-header">
    <h2 style="font-size: 24px; font-weight: 600; margin: 0;">Total étudiants: <?php echo $total; ?></h2>
    <button class="btn btn-primary" onclick="openModal('addModal')">+ Ajouter un étudiant</button>
</div>

<!-- Filtres -->
<div class="card">
    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; align-items: flex-end;">
        <div class="form-group">
            <label>🔍 Recherche</label>
            <input type="text" name="search" placeholder="Nom, email, matricule..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>📚 Filière</label>
            <select name="filiere_id">
                <option value="">Toutes</option>
                <?php foreach ($filieres as $filiere): ?>
                    <option value="<?php echo $filiere['id']; ?>" <?php echo ($_GET['filiere_id'] ?? '') == $filiere['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($filiere['nom']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>🏫 Classe</label>
            <select name="classe_id">
                <option value="">Toutes</option>
                <?php foreach ($classes as $classe): ?>
                    <option value="<?php echo $classe['id']; ?>" <?php echo ($_GET['classe_id'] ?? '') == $classe['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($classe['nom']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Filtrer</button>
            <a href="?" class="btn btn-secondary" style="width: 100%; text-align: center; margin-top: 5px;">Réinitialiser</a>
        </div>
    </form>
</div>

<!-- Modal Ajouter -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span>🎓 Ajouter un étudiant</span>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Matricule *</label>
                    <input type="text" name="matricule" required>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>
            </div>

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

            <div class="form-row">
                <div class="form-group">
                    <label>Téléphone *</label>
                    <input type="tel" name="telephone" required>
                </div>
                <div class="form-group">
                    <label>Mot de passe *</label>
                    <input type="password" name="password" required>
                    <small style="color: #999;">Min 6 caractères, 1 majuscule, 1 chiffre</small>
                </div>
            </div>

            <div class="form-group">
                <label>Adresse *</label>
                <textarea name="adresse" required rows="2"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Filière *</label>
                    <select name="filiere_id" required onchange="loadClasses(this.value)">
                        <option value="">Sélectionner</option>
                        <?php foreach ($filieres as $filiere): ?>
                            <option value="<?php echo $filiere['id']; ?>"><?php echo htmlspecialchars($filiere['nom']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Classe *</label>
                    <select name="classe_id" required id="classe-select">
                        <option value="">D'abord sélectionner une filière</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Photo</label>
                <div class="file-input-wrapper">
                    <label class="file-input-label">Choisir une photo</label>
                    <input type="file" name="photo" accept="image/*" onchange="updateFileName(this)">
                </div>
                <div class="file-name" id="fileName"></div>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Créer l'étudiant</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Annuler</button>
            </div>
        </form>
    </div>
</div>

<!-- Table des étudiants -->
<div class="card">
    <?php if (!empty($etudiants)): ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Matricule</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Classe</th>
                        <th>Filière</th>
                        <th>Téléphone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($etudiants as $etudiant): ?>
                        <tr>
                            <td>
                                <div class="photo-thumbnail">
                                    <?php if ($etudiant['photo']): ?>
                                        <img src="/uploads/photos/<?php echo htmlspecialchars($etudiant['photo']); ?>" alt="Photo">
                                    <?php else: ?>
                                        <span style="font-size: 20px;">👤</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><strong><?php echo htmlspecialchars($etudiant['matricule']); ?></strong></td>
                            <td><?php echo htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']); ?></td>
                            <td><?php echo htmlspecialchars($etudiant['email']); ?></td>
                            <td><?php echo htmlspecialchars($etudiant['classe_nom'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($etudiant['filiere_nom'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($etudiant['telephone']); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="/admin/etudiant-detail.php?id=<?php echo $etudiant['id']; ?>" class="btn btn-primary btn-small">🔍 Détails</a>
                                    <button class="btn btn-danger btn-small" onclick="confirmDelete(<?php echo $etudiant['id']; ?>)">🗑️ Supprimer</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="text-align: center; padding: 30px; color: #999;">Aucun étudiant trouvé</p>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo !empty($_GET['classe_id']) ? '&classe_id=' . $_GET['classe_id'] : ''; ?><?php echo !empty($_GET['filiere_id']) ? '&filiere_id=' . $_GET['filiere_id'] : ''; ?>" class="<?php echo ($page == $i) ? 'active' : ''; ?>"><?php echo $i; ?></a>
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

    function updateFileName(input) {
        const fileName = input.files[0]?.name || '';
        document.getElementById('fileName').textContent = fileName ? '✓ ' + fileName : '';
    }

    function loadClasses(filiere_id) {
        // En production, faire un appel AJAX
        // Pour l'instant, on charge les classes mémorialisées
        const classesData = <?php echo json_encode(db()->fetchAll("SELECT id, nom FROM classes ORDER BY nom")); ?>;
        const select = document.getElementById('classe-select');
        
        select.innerHTML = '<option value="">Sélectionner</option>';
        
        classesData.forEach(classe => {
            if (classe.filiere_id == filiere_id || filiere_id == '') {
                const option = document.createElement('option');
                option.value = classe.id;
                option.textContent = classe.nom;
                select.appendChild(option);
            }
        });
    }

    function confirmDelete(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cet étudiant ?')) {
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

    // Fermer le modal en cliquant en dehors
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.remove('active');
        }
    });
</script>

<?php
require_once 'includes/footer.php';
?>