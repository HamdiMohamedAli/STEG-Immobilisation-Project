<?php 
$idProfil = $_SESSION['idProfil'] ?? null;
?>

<nav class="main-navbar">
    <div class="nav-brand">
        <span class="brand-logo">STEG</span>
        <span class="brand-sub">Immobilisations</span>
    </div>
    
    <ul class="nav-links">
        <?php if ($idProfil !== null): ?>
            <?php if (in_array((int)$idProfil, [1, 2, 3, 4, 5])): ?>
                <li><a href="dashboard.php" class="nav-item"><i class="fa-solid fa-chart-pie"></i> Tableau de bord</a></li>
            <?php endif; ?>

            <?php if (in_array((int)$idProfil, [1, 2, 3, 4, 5, 6])): ?>
                <li><a href="gestion_plan.php" class="nav-item"><i class="fa-solid fa-map"></i> Plan 2D</a></li>
            <?php endif; ?>

            <?php if (in_array((int)$idProfil, [1, 2, 4])): ?>
                <li><a href="gestion_magasin.php" class="nav-item"><i class="fa-solid fa-boxes-stacked"></i> Magasin</a></li>
            <?php endif; ?>

            <?php if (in_array((int)$idProfil, [1, 2, 3])): ?>
                <li><a href="logs.php" class="nav-item"><i class="fa-solid fa-history"></i> Journal Logs</a></li>
                <li><a href="uml.php" class="nav-item"><i class="fa-solid fa-diagram-project"></i> UML</a></li>
            <?php endif; ?>
        <?php endif; ?>
    </ul>

    <div class="nav-user-profile">
        <?php if (isset($_SESSION['nom']) || isset($_SESSION['labelProfil'])): ?>
            <div class="user-info">
                <span class="user-name">
                    <?= htmlspecialchars($_SESSION['nom'] ?? '') ?>
                </span>
                
                <span class="user-role-badge">
                    <?= htmlspecialchars(ucfirst($_SESSION['labelProfil'] ?? 'Visiteur')) ?> 
                    (ID: <?= htmlspecialchars($userIdFormate ?? 'N/A') ?>)
                </span>
            </div>
            <a href="logout.php" class="btn-logout" title="Se déconnecter">
                <i class="fa-solid fa-power-off"></i>
            </a>
        <?php else: ?>
            <a href="login.php" class="nav-item"><i class="fa-solid fa-sign-in-alt"></i> Se connecter</a>
        <?php endif; ?>
    </div>
</nav>
<main class="main-content">