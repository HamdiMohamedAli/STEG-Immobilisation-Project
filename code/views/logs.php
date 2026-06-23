<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';
require_once '../config/db.php';

$errorMsg = '';
$logs = [];

$page   = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit  = 15;
$offset = ($page - 1) * $limit;

try {
    // Auto-detect the date column
    $columns    = $pdo->query("DESCRIBE journal_logs")->fetchAll(PDO::FETCH_COLUMN);
    $dateColumn = $columns[2] ?? 'idLog';
    foreach ($columns as $col) {
        if (stripos($col, 'date') !== false || stripos($col, 'time') !== false || stripos($col, 'stamp') !== false) {
            $dateColumn = $col;
            break;
        }
    }

    $totalLogs  = $pdo->query("SELECT COUNT(*) FROM journal_logs")->fetchColumn();
    $totalPages = ceil($totalLogs / $limit);

    // ── Added l.idCible and l.typeAction to the SELECT ──────────────────────
    $query = "SELECT l.idLog, l.action, l.typeAction, l.idCible,
                     l.idUtilisateur, l.$dateColumn AS dateGenerique, u.nom
              FROM journal_logs l
              LEFT JOIN users u ON l.idUtilisateur = u.idUtilisateur
              ORDER BY l.$dateColumn DESC
              LIMIT $limit OFFSET $offset";

    $logs = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errorMsg = "Erreur lors du chargement du journal d'activités : " . $e->getMessage();
}

// Badge colour per action type
function typeActionBadge(string $type): string {
    $map = [
        'INSERT' => ['#dcfce7', '#16a34a'],
        'UPDATE' => ['#dbeafe', '#1d4ed8'],
        'DELETE' => ['#fee2e2', '#dc2626'],
        'ERROR'  => ['#fef9c3', '#b45309'],
    ];
    [$bg, $fg] = $map[strtoupper($type)] ?? ['#f1f5f9', '#475569'];
    return "<span style='background:$bg;color:$fg;padding:2px 8px;border-radius:12px;font-size:0.7rem;font-weight:700;'>$type</span>";
}
?>

<div class="logs-wrapper" style="padding: 2rem; max-width: 1200px; margin: 0 auto;">
    <div class="logs-header" style="margin-bottom: 2rem;">
        <h1 style="color: #1e293b; font-size: 2rem; margin-bottom: 0.5rem;">
            <i class="fa-solid fa-clock-rotate-left" style="color: #0284c7;"></i> Journal des Événements Security
        </h1>
        <p style="color: #64748b;">Historique centralisé des actions et opérations de maintenance du district STEG.</p>
    </div>

    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger" style="background:#fee2e2;color:#991b1b;padding:1rem;border-radius:.5rem;margin-bottom:1.5rem;">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($errorMsg) ?>
        </div>
    <?php endif; ?>

    <div class="logs-card" style="background:#fff;padding:1.5rem;border-radius:.75rem;box-shadow:0 4px 6px -1px rgb(0 0 0/.1);">
        <div class="table-scroll" style="overflow-x:auto;">
            <table class="steg-table" style="width:100%;border-collapse:collapse;text-align:left;">
                <thead>
                    <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                        <th style="padding:.75rem 1rem;color:#475569;font-weight:600;white-space:nowrap;">ID Log</th>
                        <th style="padding:.75rem 1rem;color:#475569;font-weight:600;white-space:nowrap;">Horodatage</th>
                        <th style="padding:.75rem 1rem;color:#475569;font-weight:600;white-space:nowrap;">Opérateur</th>
                        <th style="padding:.75rem 1rem;color:#475569;font-weight:600;white-space:nowrap;">Type</th>
                        <th style="padding:.75rem 1rem;color:#475569;font-weight:600;">Action &amp; Détails</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" style="padding:2rem;text-align:center;color:#94a3b8;">
                                Aucune activité répertoriée dans le système.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr style="border-bottom:1px solid #f1f5f9;transition:background .2s;"
                                onmouseover="this.style.backgroundColor='#f8fafc'"
                                onmouseout="this.style.backgroundColor='transparent'">

                                <!-- ID -->
                                <td style="padding:.75rem 1rem;color:#64748b;font-family:monospace;">
                                    #<?= sprintf('%05d', $log['idLog']) ?>
                                </td>

                                <!-- Timestamp -->
                                <td style="padding:.75rem 1rem;color:#334155;white-space:nowrap;">
                                    <i class="fa-regular fa-calendar-days" style="color:#94a3b8;margin-right:.4rem;"></i>
                                    <?= date('d/m/Y H:i:s', strtotime($log['dateGenerique'])) ?>
                                </td>

                                <!-- Operator -->
                                <td style="padding:.75rem 1rem;color:#0f172a;font-weight:600;white-space:nowrap;">
                                    <i class="fa-solid fa-user-shield" style="color:#64748b;margin-right:.4rem;font-size:.85rem;"></i>
                                    <?php if (!empty($log['idUtilisateur'])): ?>
                                        (ID: <?= htmlspecialchars($log['idUtilisateur']) ?>)
                                        <?= htmlspecialchars($log['nom'] ?? 'Utilisateur Existant') ?>
                                    <?php else: ?>
                                        System
                                    <?php endif; ?>
                                </td>

                                <!-- Type badge -->
                                <td style="padding:.75rem 1rem;">
                                    <?= typeActionBadge($log['typeAction'] ?? '') ?>
                                </td>

                                <!-- Action + idCible detail -->
                                <td style="padding:.75rem 1rem;max-width:480px;">
                                    <div style="font-weight:600;color:#1e293b;font-size:.85rem;">
                                        <?= htmlspecialchars($log['action']) ?>
                                    </div>
                                    <?php if (!empty($log['idCible'])): ?>
                                        <div style="margin-top:.35rem;padding:.3rem .6rem;background:#f1f5f9;border-left:3px solid #0284c7;border-radius:0 4px 4px 0;font-size:.78rem;color:#475569;word-break:break-word;">
                                            <i class="fa-solid fa-tag" style="color:#0284c7;margin-right:.35rem;"></i>
                                            <?= htmlspecialchars($log['idCible']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div style="display:flex;justify-content:center;margin-top:1.5rem;gap:.25rem;flex-wrap:wrap;">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" style="padding:.5rem .75rem;border:1px solid #e2e8f0;border-radius:.375rem;color:#0284c7;text-decoration:none;">Précédent</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>"
                       style="padding:.5rem .75rem;border:1px solid #e2e8f0;border-radius:.375rem;text-decoration:none;
                              <?= $i === $page ? 'background:#0284c7;color:#fff;border-color:#0284c7;' : 'color:#334155;' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>" style="padding:.5rem .75rem;border:1px solid #e2e8f0;border-radius:.375rem;color:#0284c7;text-decoration:none;">Suivant</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>