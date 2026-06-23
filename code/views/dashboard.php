<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['idProfil'])) {
    header('Location: login.php');
    exit();
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';
require_once '../config/db.php';

$idProfil = (int)$_SESSION['idProfil'];

$totalUtilisateurs = 0;
$totalStock        = 0;
$totalVoitures     = 0;
$totalPannes       = 0;
$recentLogs        = [];

$profils = [
    1 => "Administrateur",
    2 => "Chef du Centre Informatique",
    3 => "Responsable de Services",
    4 => "Responsable Équipements",
    5 => "Gestionnaire (Voitures)",
    6 => "Agent de Local"
];
$profilLabel = $profils[$idProfil] ?? "Agent";

$modules = [
    'utilisateurs'      => ['url' => 'list_utilisateurs.php',      'icon' => 'fa-solid fa-users',              'label' => 'Utilisateurs',     'allowed' => [1, 2]],
    'locaux'            => ['url' => 'gestion_locaux.php',         'icon' => 'fa-solid fa-building',           'label' => 'Locaux & Zones',   'allowed' => [1, 2, 3, 6]],
    'immobilisations'   => ['url' => 'gestion_immobilisations.php', 'icon' => 'fa-solid fa-boxes-stacked',     'label' => 'Immobilisations',  'allowed' => [1, 2, 3, 4]],
    'equipements'       => ['url' => 'gestion_equipements.php',    'icon' => 'fa-solid fa-screwdriver-wrench', 'label' => 'Équipements',      'allowed' => [1, 2, 3, 4]],
    'voitures'          => ['url' => 'gestion_voitures.php',       'icon' => 'fa-solid fa-car-side',           'label' => 'Voitures',         'allowed' => [1, 2, 5]],
    'pannes'            => ['url' => 'gestion_panne.php',          'icon' => 'fa-solid fa-triangle-exclamation','label' => 'Suivi des Pannes', 'allowed' => [1, 2, 3, 4, 5, 6]],
    'controle'          => ['url' => 'gestion_utilisateurs.php',   'icon' => 'fa-solid fa-users-gear',         'label' => 'Contrôle Personnel','allowed' => [1], 'style' => 'color: #dc2626;']
];

try {
    $totalUtilisateurs = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalStock        = (int)$pdo->query("SELECT SUM(quantiteDisponible) FROM magasin")->fetchColumn();
    $totalVoitures     = (int)$pdo->query("SELECT COUNT(*) FROM voitures")->fetchColumn();
    $totalPannes       = (int)$pdo->query("SELECT COUNT(*) FROM pannes")->fetchColumn();

    if (in_array($idProfil, [1, 2])) {
        $recentLogs = $pdo->query("
            SELECT l.action, l.typeAction, l.idCible, l.dateAction, u.nom
            FROM journal_logs l
            LEFT JOIN users u ON l.idUtilisateur = u.idUtilisateur
            ORDER BY l.dateAction DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $errorDashboard = "Erreur lors du chargement des indicateurs : " . $e->getMessage();
}

function typeDot(string $type): string {
    $colors = [
        'INSERT' => '#16a34a', 'UPDATE' => '#2563eb', 'DELETE' => '#dc2626',
        'LOGIN'  => '#0284c7', 'LOGOUT' => '#64748b', 'ERROR'  => '#ca8a04',
    ];
    $color = $colors[strtoupper($type)] ?? '#94a3b8';
    return "<span style='display:inline-block;width:8px;height:8px;border-radius:50%;background:$color;margin-right:5px;flex-shrink:0;'></span>";
}
?>

<style>
    .full-width-actions { grid-template-columns: 1fr !important; }
    .full-width-actions .actions-grid { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) !important; }
</style>

<div class="dashboard-wrapper">
    <div class="dashboard-welcome">
        <h1>Tableau de Bord — <?= htmlspecialchars($profilLabel) ?></h1>
    </div>

    <?php if (isset($errorDashboard)): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <?= htmlspecialchars($errorDashboard, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="stat-cards-container">
        <div class="stat-card">
            <div class="stat-icon bg-primary"><i class="fa-solid fa-users"></i></div>
            <div class="stat-details">
                <h3>Personnel</h3>
                <p class="stat-number"><?= $totalUtilisateurs ?></p>
                <span class="stat-label">Agents inscrits</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-success"><i class="fa-solid fa-boxes-stacked"></i></div>
            <div class="stat-details">
                <h3>Stock</h3>
                <p class="stat-number"><?= $totalStock ?></p>
                <span class="stat-label">Total articles disponibles</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-warning"><i class="fa-solid fa-car"></i></div>
            <div class="stat-details">
                <h3>Logistique</h3>
                <p class="stat-number"><?= $totalVoitures ?></p>
                <span class="stat-label">Véhicules de fonction</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background-color:#dc2626;"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="stat-details">
                <h3>Maintenance</h3>
                <p class="stat-number"><?= $totalPannes ?></p>
                <span class="stat-label">Incidents enregistrés</span>
            </div>
        </div>
    </div>

    <div class="dashboard-grid <?= !in_array($idProfil, [1, 2]) ? 'full-width-actions' : '' ?>">

        <div class="dashboard-section card-actions">
            <h2><i class="fa-solid fa-gears"></i> Actions de Gestion</h2>
            <hr>
            <div class="actions-grid">
                <?php foreach ($modules as $key => $mod): ?>
                    <?php if (in_array($idProfil, $mod['allowed'])): ?>
                        <a href="<?= $mod['url'] ?>" class="action-btn <?= $key === 'controle' ? 'btn-admin-only' : '' ?>">
                            <i class="<?= $mod['icon'] ?>" style="<?= $mod['style'] ?? '' ?>"></i>
                            <span><?= $mod['label'] ?></span>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (in_array($idProfil, [1, 2])): ?>
        <div class="dashboard-section card-logs">
            <h2><i class="fa-solid fa-clock-rotate-left"></i> Journal d'Activités Récentes</h2>
            <hr>
            <?php if (empty($recentLogs)): ?>
                <p class="no-data">Aucune action répertoriée dans le journal.</p>
            <?php else: ?>
                <div class="table-scroll">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Opérateur</th>
                                <th>Action &amp; Détails</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLogs as $log): ?>
                            <tr>
                                <td style="color:#64748b;font-size:0.8rem;white-space:nowrap;">
                                    <?= !empty($log['dateAction']) ? date('d/m/Y H:i', strtotime($log['dateAction'])) : 'Date inconnue' ?>
                                </td>
                                <td style="font-weight:600;color:#1e293b;white-space:nowrap;">
                                    <?= htmlspecialchars($log['nom'] ?? 'Système', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td style="color:#334155;font-size:0.82rem;">
                                    <div style="display:flex;align-items:center;font-weight:600;">
                                        <?= typeDot($log['typeAction'] ?? '') ?>
                                        <?= htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <?php if (!empty($log['idCible'])): ?>
                                    <div style="margin-top:.3rem;padding:.25rem .5rem;background:#f1f5f9;border-left:3px solid #0284c7;border-radius:0 3px 3px 0;font-size:0.75rem;color:#475569;word-break:break-word;">
                                        <i class="fa-solid fa-tag" style="color:#0284c7;margin-right:.3rem;"></i>
                                        <?= htmlspecialchars($log['idCible'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>