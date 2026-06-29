<?php 
$idProfil = $_SESSION['idProfil'] ?? null;
$labelProfil = $_SESSION['labelProfil'] ?? 'Visiteur';

$initiales = "U";
if (isset($_SESSION['nom']) && !empty(trim($_SESSION['nom']))) {
    $motsNom = explode(' ', trim($_SESSION['nom']));
    $premiereLettre = mb_substr($motsNom[0], 0, 1, 'UTF-8');
    $secondeLettre = (count($motsNom) > 1) ? mb_substr($motsNom[1], 0, 1, 'UTF-8') : '';
    $initiales = strtoupper($premiereLettre . $secondeLettre);
}
?>

<style>
    .profile-clickable-link {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        text-decoration: none;
        color: inherit;
        padding: 4px 8px;
        border-radius: 8px;
        transition: background-color 0.2s ease;
    }
    .profile-clickable-link:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }
    .user-info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }
    .user-name {
        display: block;
        font-weight: 600;
    }
    .user-role-badge {
        display: inline-block;
        font-size: 0.8rem;
        opacity: 0.8;
    }
    .user-avatar-initials {
        width: 38px;
        height: 38px;
        background-color: #0284c7;
        color: #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        flex-shrink: 0;
    }
    .profile-clickable-link:hover .user-avatar-initials {
        background-color: #0369a1;
    }
    .nav-user-profile {
        display: flex;
        align-items: center;
    }
</style>

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

            <?php if (in_array((int)$idProfil, [1, 2])): ?>
                <li><a href="logs.php" class="nav-item"><i class="fa-solid fa-history"></i> Journal Logs</a></li>
            <?php endif; ?>

            <?php if (in_array((int)$idProfil, [1])): ?>
                <li><a href="uml.php" class="nav-item"><i class="fa-solid fa-diagram-project"></i> UML</a></li>
                <li><a href="rapport.php" class="nav-item"><i class="fa-solid fa-book"></i> Rapport</a></li>
            <?php endif; ?>
        <?php endif; ?>
    </ul>

    <div class="nav-user-profile">
        <?php if (isset($_SESSION['idUtilisateur'])): ?>
            <a href="user_profile.php" class="profile-clickable-link" title="Voir mon profil">
                <div class="user-avatar-initials">
                    <?= htmlspecialchars($initiales, ENT_QUOTES, 'UTF-8') ?>
                </div>
                
                <div class="user-info">
                    <span class="user-name">
                        <?= htmlspecialchars($_SESSION['nom'] ?? 'Utilisateur', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    
                    <span class="user-role-badge">
                        <?= htmlspecialchars($labelProfil, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
            </a>
            
            <a href="logout.php" class="btn-logout" title="Se déconnecter" style="margin-left: 10px;">
                <i class="fa-solid fa-power-off"></i>
            </a>
        <?php else: ?>
            <a href="login.php" class="nav-item"><i class="fa-solid fa-sign-in-alt"></i> Se connecter</a>
        <?php endif; ?>
    </div>
</nav>
<main class="main-content">