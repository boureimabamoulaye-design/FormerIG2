<?php
/**
 * Dashboard Admin - Page d'accueil avec statistiques
 */
session_start();
require_once '../../config/database.php';
require_once '../../includes/Security.php';
require_once '../../includes/Auth.php';
require_once '../../includes/middleware.php';

requireAdmin();
checkSessionTimeout();

$page_title = 'Tableau de bord';

// Récupérer les statistiques
try {
    // Nombre d'étudiants
    $stats_etudiants = db()->fetchOne("SELECT COUNT(*) as total FROM etudiants");
    $total_etudiants = $stats_etudiants['total'] ?? 0;

    // Nombre de filières
    $stats_filieres = db()->fetchOne("SELECT COUNT(*) as total FROM filieres");
    $total_filieres = $stats_filieres['total'] ?? 0;

    // Nombre de classes
    $stats_classes = db()->fetchOne("SELECT COUNT(*) as total FROM classes");
    $total_classes = $stats_classes['total'] ?? 0;

    // Nombre de semestres
    $stats_semestres = db()->fetchOne("SELECT COUNT(*) as total FROM semestres");
    $total_semestres = $stats_semestres['total'] ?? 0;

    // Nombre de cours
    $stats_cours = db()->fetchOne("SELECT COUNT(*) as total FROM cours");
    $total_cours = $stats_cours['total'] ?? 0;

    // Nombre de notes
    $stats_notes = db()->fetchOne("SELECT COUNT(*) as total FROM notes");
    $total_notes = $stats_notes['total'] ?? 0;

    // Nombre de bulletins
    $stats_bulletins = db()->fetchOne("SELECT COUNT(*) as total FROM bulletins");
    $total_bulletins = $stats_bulletins['total'] ?? 0;

    // Nombre d'enseignants
    $stats_enseignants = db()->fetchOne("SELECT COUNT(*) as total FROM enseignants");
    $total_enseignants = $stats_enseignants['total'] ?? 0;

    // Dernières actions
    $derniers_etudiants = db()->fetchAll(
        "SELECT id, matricule, nom, prenom, email, created_at FROM etudiants ORDER BY created_at DESC LIMIT 5"
    );

    // Derniers bulletins
    $derniers_bulletins = db()->fetchAll(
        "SELECT b.*, e.nom, e.prenom, e.matricule, s.nom as semestre_nom 
         FROM bulletins b 
         JOIN etudiants e ON b.etudiant_id = e.id 
         JOIN semestres s ON b.semestre_id = s.id 
         ORDER BY b.created_at DESC LIMIT 5"
    );

} catch (Exception $e) {
    $error = 'Erreur lors du chargement des statistiques: ' . $e->getMessage();
}

require_once 'includes/header.php';
?>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border-left: 5px solid #667eea;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
    }

    .stat-card.students {
        border-left-color: #667eea;
    }

    .stat-card.filieres {
        border-left-color: #764ba2;
    }

    .stat-card.classes {
        border-left-color: #f093fb;
    }

    .stat-card.semestres {
        border-left-color: #4facfe;
    }

    .stat-card.courses {
        border-left-color: #00f2fe;
    }

    .stat-card.notes {
        border-left-color: #43e97b;
    }

    .stat-card.bulletins {
        border-left-color: #fa709a;
    }

    .stat-card.teachers {
        border-left-color: #30cfd0;
    }

    .stat-icon {
        font-size: 32px;
        margin-bottom: 10px;
    }

    .stat-label {
        color: #999;
        font-size: 12px;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 8px;
        letter-spacing: 0.5px;
    }

    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #333;
    }

    .section-title {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 20px;
        margin-top: 40px;
        color: #333;
        border-bottom: 2px solid #667eea;
        padding-bottom: 10px;
    }

    .data-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-top: 30px;
    }

    .data-card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }

    .data-card h3 {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #333;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
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
        color: #333;
        font-size: 14px;
    }

    .table tbody tr:hover {
        background: #f9f9f9;
    }

    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge.success {
        background: #d4edda;
        color: #155724;
    }

    .badge.warning {
        background: #fff3cd;
        color: #856404;
    }

    .badge.danger {
        background: #f8d7da;
        color: #721c24;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .data-section {
            grid-template-columns: 1fr;
        }

        .table {
            font-size: 12px;
        }

        .table th,
        .table td {
            padding: 8px;
        }
    }
</style>

<?php if (isset($error)): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Cartes statistiques -->
<div class="stats-grid">
    <div class="stat-card students">
        <div class="stat-icon">🎓</div>
        <div class="stat-label">Étudiants</div>
        <div class="stat-value"><?php echo $total_etudiants; ?></div>
    </div>

    <div class="stat-card filieres">
        <div class="stat-icon">📚</div>
        <div class="stat-label">Filières</div>
        <div class="stat-value"><?php echo $total_filieres; ?></div>
    </div>

    <div class="stat-card classes">
        <div class="stat-icon">🏫</div>
        <div class="stat-label">Classes</div>
        <div class="stat-value"><?php echo $total_classes; ?></div>
    </div>

    <div class="stat-card semestres">
        <div class="stat-icon">📅</div>
        <div class="stat-label">Semestres</div>
        <div class="stat-value"><?php echo $total_semestres; ?></div>
    </div>

    <div class="stat-card courses">
        <div class="stat-icon">📖</div>
        <div class="stat-label">Cours</div>
        <div class="stat-value"><?php echo $total_cours; ?></div>
    </div>

    <div class="stat-card notes">
        <div class="stat-icon">📊</div>
        <div class="stat-label">Notes</div>
        <div class="stat-value"><?php echo $total_notes; ?></div>
    </div>

    <div class="stat-card bulletins">
        <div class="stat-icon">📄</div>
        <div class="stat-label">Bulletins</div>
        <div class="stat-value"><?php echo $total_bulletins; ?></div>
    </div>

    <div class="stat-card teachers">
        <div class="stat-icon">👨‍🏫</div>
        <div class="stat-label">Enseignants</div>
        <div class="stat-value"><?php echo $total_enseignants; ?></div>
    </div>
</div>

<!-- Dernières données -->
<div class="data-section">
    <!-- Derniers étudiants -->
    <div class="data-card">
        <h3>📝 Derniers étudiants inscrits</h3>
        <?php if (!empty($derniers_etudiants)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Matricule</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($derniers_etudiants as $etudiant): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($etudiant['matricule']); ?></strong></td>
                            <td><?php echo htmlspecialchars($etudiant['nom'] . ' ' . $etudiant['prenom']); ?></td>
                            <td><?php echo htmlspecialchars($etudiant['email']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($etudiant['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: #999; text-align: center; padding: 20px;">Aucun étudiant enregistré</p>
        <?php endif; ?>
    </div>

    <!-- Derniers bulletins -->
    <div class="data-card">
        <h3>📄 Derniers bulletins générés</h3>
        <?php if (!empty($derniers_bulletins)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Semestre</th>
                        <th>Moyenne</th>
                        <th>Mention</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($derniers_bulletins as $bulletin): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($bulletin['prenom'] . ' ' . $bulletin['nom']); ?></td>
                            <td><?php echo htmlspecialchars($bulletin['semestre_nom']); ?></td>
                            <td><strong><?php echo number_format($bulletin['moyenne'] ?? 0, 2, ',', '.'); ?></strong></td>
                            <td>
                                <?php 
                                $mention = $bulletin['mention'] ?? 'N/A';
                                $badge_class = 'badge';
                                if ($mention === 'Très Bien') $badge_class .= ' success';
                                elseif ($mention === 'Bien' || $mention === 'Assez Bien') $badge_class .= ' warning';
                                else $badge_class .= ' danger';
                                ?>
                                <span class="<?php echo $badge_class; ?>"><?php echo htmlspecialchars($mention); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: #999; text-align: center; padding: 20px;">Aucun bulletin généré</p>
        <?php endif; ?>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>