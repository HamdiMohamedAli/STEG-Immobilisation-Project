<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trigger_print_log'])) {
    header('Content-Type: application/json');
    try {
        $totalAgents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE statut = 1")->fetchColumn();
        $nom         = $_SESSION['nom']          ?? 'Inconnu';
        $role        = $_SESSION['labelProfil']  ?? 'Inconnu';
        $id          = $_SESSION['idUtilisateur'] ?? 0;

        $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
        $stmtLog->execute([
            $id,
            "Impression du registre officiel du personnel (STEG)",
            "PRINT",
            "users",
            "Imprimé par : ID $id | Nom : $nom | Rôle : $role | Nombre d'agents imprimés : $totalAgents"
        ]);
        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

$errorMsg = '';
$agents   = [];
try {
    $agents = $pdo->query("
        SELECT u.*, p.labelProfil
        FROM users u
        LEFT JOIN profils p ON u.idProfil = p.idProfil
        WHERE u.statut = 1
        ORDER BY u.idUtilisateur ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMsg = "Impossible de charger le registre du personnel : " . $e->getMessage();
}
?>

<style>
    .print-header-steg { display: none; }
    @media print {
        .users-header, .alert, .btn-action, th:last-child, td:last-child,
        nav, header, footer, #navbar, .btn-print { display: none !important; }

        .users-wrapper, .card-table, .table-scroll {
            margin: 0 !important; padding: 0 !important;
            width: 100% !important; box-shadow: none !important; overflow: visible !important;
        }
        body { background-color: #fff !important; color: #000 !important; padding: 15mm !important; }

        .steg-table { width: 100% !important; border-collapse: collapse !important; margin-top: 15px !important; }
        .steg-table th,
        .steg-table td { border: 1px solid #000 !important; padding: 8px !important; font-size: 11pt !important; color: #000 !important; }

        .print-header-steg { display: block !important; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .print-header-steg table { width: 100%; }
        .print-header-steg h2 { margin: 0; font-size: 16pt; }
    }
</style>

<div class="users-wrapper">
    <div class="users-header">
        <h1><i class="fa-solid fa-users-gear"></i> Liste du Personnel (STEG)</h1>
    </div>
    <br>

    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger"><i class="fa-solid fa-user-xmark"></i> <?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="users-card card-table" style="background:white;border-radius:8px;box-shadow:var(--card-shadow);padding:22px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <h2 style="margin:0;font-size:1.15rem;color:#0f172a;font-weight:600;">
                <i class="fa-solid fa-address-book"></i> Registre Actif des Agents
                <span style="font-size:0.8rem;color:#64748b;font-weight:400;margin-left:8px;">
                    (<?= count($agents) ?> agent<?= count($agents) > 1 ? 's' : '' ?>)
                </span>
            </h2>
            <button type="button" id="btnPrintRegister" class="btn-print"
                    style="background-color:#0d6efd;color:white;border:none;padding:8px 16px;border-radius:4px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid fa-print"></i> Imprimer le registre
            </button>
        </div>
        <hr style="border:0;height:1px;background:#e2e8f0;margin:15px 0 20px;">

        <div class="print-header-steg">
            <table style="width:100%;border:none;">
                <tr>
                    <td style="border:none !important;text-align:left;padding:0 !important;">
                        <strong>Société Tunisienne de l'Électricité et du Gaz</strong><br>
                        Direction Informatique &amp; Logistique
                    </td>
                    <td style="border:none !important;text-align:right;padding:0 !important;">
                        Date d'édition : <?= date('d/m/Y H:i') ?>
                    </td>
                </tr>
            </table>
            <h2 style="text-align:center;margin-top:20px;font-size:18pt;">Registre Officiel du Personnel Assigné</h2>
        </div>

        <div class="table-scroll">
            <table class="steg-table" style="width:100%;border-collapse:collapse;font-size:0.9rem;text-align:left;">
                <thead>
                    <tr style="background-color:#f8fafc;color:#475569;font-weight:600;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.5px;">
                        <th style="padding:12px 14px;border-bottom:1px solid #e2e8f0;">ID</th>
                        <th style="padding:12px 14px;border-bottom:1px solid #e2e8f0;">Nom &amp; Prénom</th>
                        <th style="padding:12px 14px;border-bottom:1px solid #e2e8f0;">Rôle</th>
                        <th style="padding:12px 14px;border-bottom:1px solid #e2e8f0;">Email</th>
                        <th style="padding:12px 14px;border-bottom:1px solid #e2e8f0;">Contact</th>
                        <th style="padding:12px 14px;border-bottom:1px solid #e2e8f0;">Affectation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($agents)): ?>
                        <tr>
                            <td colspan="6" class="no-data" style="text-align:center;color:var(--text-light);font-style:italic;padding:25px 0;">
                                Aucun utilisateur actif trouvé.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($agents as $agent): ?>
                        <tr style="border-bottom:1px solid #e2e8f0;">
                            <td class="cell-user-id" style="padding:12px 14px;font-family:'Courier New',Courier,monospace;font-weight:700;color:var(--primary-color);font-size:1rem;">
                                <?= str_pad($agent['idUtilisateur'], 4, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td style="padding:12px 14px;"><strong><?= htmlspecialchars($agent['nom']) ?></strong></td>
                            <td style="padding:12px 14px;">
                                <?= htmlspecialchars($agent['labelProfil'] ?? 'N/A') ?>
                            </td>
                            <td style="padding:12px 14px;"><?= htmlspecialchars($agent['email']) ?></td>
                            <td style="padding:12px 14px;"><?= htmlspecialchars($agent['tel']) ?></td>
                            <td style="padding:12px 14px;">
                                <?php if ($agent['idLocalAssigne'] !== null && $agent['idLocalAssigne'] !== ''): ?>
                                    <span class="badge-loc-id"><i class="fa-solid fa-location-arrow"></i> Zone <?= htmlspecialchars($agent['idLocalAssigne']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Aucune</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>