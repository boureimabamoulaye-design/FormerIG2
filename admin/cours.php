<?php
/**
 * Gestion des cours
 */
session_start();
require_once '../../config/database.php';
require_once '../../includes/Security.php';
require_once '../../includes/Auth.php';
require_once '../../includes/middleware.php';
require_once '../../includes/DataManager.php';
require_once '../../includes/CoursManager.php';

requireAdmin();
checkSessionTimeout();

$page_title = 'Gestion des cours';
$message = '';
$error = '';

// Charger les données de base
$filieres = db()->fetchAll("SELECT id, nom FROM filieres ORDER BY nom");
$classes = db()->fetchAll("SELECT id, nom, filiere_id FROM classes ORDER BY nom");
$semestres = db()->fetchAll("SELECT id, nom, numero FROM semestres ORDER BY numero");
$enseignants = db()->fetchAll("SELECT id, CONCAT(prenom, ' ', nom) as fullname FROM enseignants ORDER BY nom");

// Traiter les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token invalide';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $result = CoursManager::create([
                'nom' => trim($_POST['nom'] ?? ''),
                'code' => trim($_POST['code'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'filiere_id' => $_POST['filiere_id'] ?? 0,
                'classe_id' => $_POST['classe_id'] ?? 0,
                'semestre_id' => $_POST['semestre_id'] ?? 0,
                'enseignant_id' => $_POST['enseignant_id'] ?? null
            ]);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        } elseif ($action === 'delete') {
            $result = CoursManager::delete($_POST['id'] ?? 0);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Récupérer les cours
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$filters = [];
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}
if (!empty($_GET['semestre_id'])) {
    $filters['semestre_id'] = $_GET['semestre_id'];
}
if (!empty($_GET['classe_id'])) {
    $filters['classe_id'] = $_GET['classe_id'];
}

$cours_list = CoursManager::getAll($per_page, $offset, $filters);
$total = CoursManager::count($filters);
$total_pages = ceil($total / $per_page);

require_once 'includes/header.php';
?>

<style>
    .content-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
    .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s ease; text-decoration: none; display: inline-block; }
    .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
    .btn-danger { background: #e74c3c; color: white; }
    .btn-danger:hover { background: #c0392b; }
    .btn-success { background: #27ae60; color: white; }
    .btn-success:hover { background: #229954; }
    .btn-small { padding: 6px 12px; font-size: 12px; }
    .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08); margin-bottom: 20px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 14px; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; font-family: inherit; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
    .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
    .filter-section { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; padding: 15px; background: #f5f6fa; border-radius: 8px; }
    .table { width: 100%; border-collapse: collapse; background: white; }
    .table thead { background: #f5f6fa; }
    .table th { padding: 12px; text-align: left; font-weight: 600; color: #666; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #e0e0e0; }
    .table td { padding: 12px; border-bottom: 1px solid #e0e0e0; }
    .table tbody tr:hover { background: #f9f9f9; }
    .action-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 2000; justify-content: center; align-items: center; overflow-y: auto; }
    .modal.active { display: flex; }
    .modal-content { background: white; padding: 30px; border-radius: 10px; max-width: 600px; width: 90%; margin: 20px auto; }
    .modal-header { font-size: 20px; font-weight: 600; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
    .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #999; }
    .file-input-wrapper { position: relative; overflow: hidden; }
    .file-input-wrapper input[type=file] { position: absolute; left: -9999px; }
    .file-input-label { display: inline-block; padding: 10px 15px; background: #667eea; color: white; border-radius: 5px; cursor: pointer; font-size: 14px; }
    .file-name { margin-top: 5px; font-size: 12px; color: #666; }
</style>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="content-header">
    <h2 style="font-size: 24px; font-weight: 600; margin: 0;">Cours (<?php echo $total; ?>)</h2>
    <button class="btn btn-primary" onclick="openModal('addModal')">+ Ajouter un cours</button>
</div>

<!-- Filtres -->
<div class="card">
    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; align-items: flex-end;">
        <div class="form-group">
            <label>🔍 Recherche</label>
            <input type="text" name="search" placeholder="Nom, code..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>📅 Semestre</label>
            <select name="semestre_id">
                <option value="">Tous</option>
                <?php foreach ($semestres as $sem): ?>
                    <option value="<?php echo $sem['id']; ?>" <?php echo ($_GET['semestre_id'] ?? '') == $sem['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($sem['nom']); ?>
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
        </div>
    </form>
</div>

<!-- Modal Ajouter -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span>📖 Ajouter un cours</span>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Nom du cours *</label>
                    <input type="text" name="nom" required>
                </div>
                <div class="form-group">
                    <label>Code du cours *</label>
                    <input type="text" name="code" required>
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="2"></textarea>
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

            <div class="form-row">
                <div class="form-group">
                    <label>Semestre *</label>
                    <select name="semestre_id" required>
                        <option value="">Sélectionner</option>
                        <?php foreach ($semestres as $sem): ?>
                            <option value="<?php echo $sem['id']; ?>"><?php echo htmlspecialchars($sem['nom']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Enseignant</label>
                    <select name="enseignant_id">
                        <option value="">Aucun</option>
                        <?php foreach ($enseignants as $ens): ?>
                            <option value="<?php echo $ens['id']; ?>"><?php echo htmlspecialchars($ens['fullname']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Fichier PDF (optionnel)</label>
                <div class="file-input-wrapper">
                    <label class="file-input-label">Choisir un PDF</label>
                    <input type="file" name="fichier_pdf" accept=".pdf" onchange="updateFileName(this)">
                </div>
                <div class="file-name" id="fileName"></div>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Créer le cours</button>
                <button type="button" class="btn" style="background: #999; color: white;" onclick="closeModal('addModal')">Annuler</button>
            </div>
        </form>
    </div>
</div>

<!-- Table des cours -->
<div class="card">
    <?php if (!empty($cours_list)): ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Nom</th>
                        <th>Classe</th>
                        <th>Semestre</th>
                        <th>Enseignant</th>
                        <th>PDF</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cours_list as $cours): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($cours['code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($cours['nom']); ?></td>
                            <td><?php echo htmlspecialchars($cours['classe_nom'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($cours['semestre_nom'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars(($cours['enseignant_prenom'] ?? '') . ' ' . ($cours['enseignant_nom'] ?? '') ?: 'N/A'); ?></td>
                            <td>
                                <?php if ($cours['fichier_pdf']): ?>
                                    <span style="color: green; font-weight: bold;">✓ PDF</span>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($cours['fichier_pdf']): ?>
                                        <a href="/admin/download-cours.php?id=<?php echo $cours['id']; ?>" class="btn btn-success btn-small" download>💾 Télécharger</a>
                                    <?php endif; ?>
                                    <button class="btn btn-danger btn-small" onclick="confirmDelete(<?php echo $cours['id']; ?>)">🗑️ Supprimer</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="text-align: center; padding: 30px; color: #999;">Aucun cours trouvé</p>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div style="display: flex; gap: 5px; justify-content: center; margin-top: 30px; flex-wrap: wrap;">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo !empty($_GET['semestre_id']) ? '&semestre_id=' . $_GET['semestre_id'] : ''; ?><?php echo !empty($_GET['classe_id']) ? '&classe_id=' . $_GET['classe_id'] : ''; ?>" 
               style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px; text-decoration: none; color: #667eea; <?php echo ($page == $i) ? 'background: #667eea; color: white; border-color: #667eea;' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<script>
    const classesData = <?php echo json_encode($classes); ?>;

    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }

    function loadClasses(filiere_id) {
        const select = document.getElementById('classe-select');
        select.innerHTML = '<option value="">Sélectionner</option>';
        
        classesData.forEach(classe => {
            if (classe.filiere_id == filiere_id) {
                const option = document.createElement('option');
                option.value = classe.id;
                option.textContent = classe.nom;
                select.appendChild(option);
            }
        });
    }

    function updateFileName(input) {
        const fileName = input.files[0]?.name || '';
        document.getElementById('fileName').textContent = fileName ? '✓ ' + fileName : '';
    }

    function confirmDelete(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce cours ?')) {
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