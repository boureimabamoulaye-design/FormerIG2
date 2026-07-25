<?php
/**
 * Header Admin
 */
require_once '../../includes/middleware.php';
require_once '../../config/database.php';
require_once '../../includes/Auth.php';

requireAdmin();
checkSessionTimeout();

$admin_id = Auth::getAdminId();
$admin_email = $_SESSION['admin_email'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Admin'; ?> - Gestion Scolaire</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f6fa;
            color: #333;
        }

        .admin-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            overflow-y: auto;
            padding: 20px;
            z-index: 1000;
        }

        .sidebar-logo {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 10px;
        }

        .sidebar-menu a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: white;
            padding: 20px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-weight: 600;
        }

        .logout-btn {
            padding: 8px 15px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        /* Content Area */
        .content {
            flex: 1;
            padding: 30px;
        }

        /* Footer */
        .footer {
            background: white;
            padding: 20px 30px;
            text-align: center;
            color: #999;
            border-top: 1px solid #eee;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .admin-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar-menu {
                display: flex;
                flex-wrap: wrap;
            }

            .sidebar-menu li {
                flex: 1;
                min-width: 150px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">📚 Gestion Scolaire</div>
            <nav>
                <ul class="sidebar-menu">
                    <li><a href="/admin/dashboard" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'active' : ''; ?>">🏠 Tableau de bord</a></li>
                    <li><a href="/admin/admins" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'admins') !== false) ? 'active' : ''; ?>">👥 Administrateurs</a></li>
                    <li><a href="/admin/etudiants" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'etudiants') !== false) ? 'active' : ''; ?>">🎓 Étudiants</a></li>
                    <li><a href="/admin/filieres" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'filieres') !== false) ? 'active' : ''; ?>">📚 Filières</a></li>
                    <li><a href="/admin/classes" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'classes') !== false) ? 'active' : ''; ?>">🏫 Classes</a></li>
                    <li><a href="/admin/semestres" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'semestres') !== false) ? 'active' : ''; ?>">📅 Semestres</a></li>
                    <li><a href="/admin/enseignants" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'enseignants') !== false) ? 'active' : ''; ?>">👨‍🏫 Enseignants</a></li>
                    <li><a href="/admin/cours" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'cours') !== false) ? 'active' : ''; ?>">📖 Cours</a></li>
                    <li><a href="/admin/notes" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'notes') !== false) ? 'active' : ''; ?>">📊 Notes</a></li>
                    <li><a href="/admin/bulletins" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'bulletins') !== false) ? 'active' : ''; ?>">📄 Bulletins</a></li>
                    <li><a href="/admin/autorisations" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'autorisations') !== false) ? 'active' : ''; ?>">🔐 Autorisations</a></li>
                    <li><a href="/admin/historique" class="<?php echo (strpos($_SERVER['PHP_SELF'], 'historique') !== false) ? 'active' : ''; ?>">📋 Historique</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-title"><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Tableau de bord'; ?></div>
                <div class="header-right">
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr($admin_email, 0, 1)); ?></div>
                        <span><?php echo htmlspecialchars($admin_email); ?></span>
                    </div>
                    <a href="/auth/logout.php" class="logout-btn">Déconnexion</a>
                </div>
            </header>

            <!-- Content -->
            <div class="content">