<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db.php';

if (!isset($_SESSION['idUtilisateur']) || !isset($_SESSION['idProfil'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['status' => 'error', 'message' => 'Session expirée ou invalide.']);
    exit;
}

$errorMessage = "";
$successMessage = "";
$userProfil = (int)$_SESSION['idProfil'];
$userId     = $_SESSION['idUtilisateur'];
$idPlan     = 1;
$primaryKey = 'idElement';

function getCoordsFromGrid($col, $row) {
    return [
        'x' => (max(1, intval($col)) - 1) * 50,
        'y' => (max(1, intval($row)) - 1) * 50,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_submit_steg_form'])) {
        $idCible = $_POST['idCible'] ?? '0';
        if (empty($idCible) || $idCible === '0') {
            $errorMessage = "Veuillez sélectionner un article valide depuis la liste déroulante.";
        } else {
            $successMessage = "Formulaire validé avec succès.";
        }
    }

    if (isset($_POST['action_delete_item'])) {
        header('Content-Type: application/json');
        $allowedDeleteProfils = [1, 2, 3];
        if (!in_array($userProfil, $allowedDeleteProfils)) {
            echo json_encode(['status' => 'error', 'message' => 'Action refusée : Votre profil ne permet pas de supprimer des éléments.']);
            exit;
        }

        $colIndex    = intval($_POST['col_idx']       ?? 0);
        $rowIndex    = intval($_POST['row_idx']       ?? 0);
        $targetFloor = $_POST['current_floor']         ?? 'rc';

        if ($colIndex >= 1 && $rowIndex >= 1) {
            $coords           = getCoordsFromGrid($colIndex, $rowIndex);
            $orientationValue = ($targetFloor === 'e1') ? 1 : 0;

            try {
                $pdo->beginTransaction();
                $stmtFind = $pdo->prepare(
                    "SELECT ep.{$primaryKey}, ep.idLocal, ep.matriculeVoiture, m.typeEntite as mType,
                            eq.libelle as eqLabel, im.libelle as imLabel, l.labelLocal as lLabel,
                            v.modele as vLabel, v.marque as vMarque
                     FROM element_plan ep
                     LEFT JOIN magasin m       ON ep.idMagasin = m.idMagasin
                     LEFT JOIN equipements eq  ON m.typeEntite = 'Equipement' AND m.idCible = eq.idEquip
                     LEFT JOIN immobilisations im ON m.typeEntite = 'Immobilisation' AND m.idCible = im.idImmo
                     LEFT JOIN locaux l        ON ep.idLocal = l.idLocal
                     LEFT JOIN voitures v      ON ep.matriculeVoiture = v.matricule
                     WHERE ep.idPlan = ? AND ep.xPlan = ? AND ep.yPlan = ? AND ep.orientation = ? 
                     LIMIT 1"
                );
                $stmtFind->execute([$idPlan, $coords['x'], $coords['y'], $orientationValue]);
                $item = $stmtFind->fetch(PDO::FETCH_ASSOC);

                if ($item) {
                    $idElement = $item[$primaryKey];
                    $itemDetails = "Inconnu";
                    if (!empty($item['idLocal'])) {
                        $itemDetails = "[Local] " . $item['lLabel'];
                    } elseif (!empty($item['matriculeVoiture'])) {
                        $itemDetails = "[Voiture] " . $item['vMarque'] . " " . $item['vLabel'] . " (" . $item['matriculeVoiture'] . ")";
                    } elseif (!empty($item['mType'])) {
                        $label = ($item['mType'] === 'Equipement') ? $item['eqLabel'] : $item['imLabel'];
                        $itemDetails = "[" . $item['mType'] . "] " . $label;
                    }

                    $pdo->prepare("DELETE FROM element_plan WHERE {$primaryKey} = ?")->execute([$idElement]);
                    $actionDesc = "Suppression de l'élément : " . $itemDetails . " | Pos: (X: {$coords['x']}, Y: {$coords['y']}) | Niveau: " . strtoupper($targetFloor);
                    
                    $pdo->prepare(
                        "INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)"
                    )->execute([$userId, $actionDesc, 'DELETE', 'element_plan', $idElement]);
                    
                    $pdo->commit();
                    echo json_encode(['status' => 'success', 'message' => "Élément supprimé avec succès."]);
                    exit;
                } else {
                    $pdo->rollBack();
                    echo json_encode(['status' => 'error', 'message' => "Aucun élément trouvé à cet emplacement."]);
                    exit;
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                echo json_encode(['status' => 'error', 'message' => "Erreur DB: " . $e->getMessage()]);
                exit;
            }
        }
        echo json_encode(['status' => 'error', 'message' => "Paramètres invalides."]);
        exit;
    }

    if (isset($_POST['action_position'])) {
        header('Content-Type: application/json');
        $idMagasin        = (!empty($_POST['id_magasin'])) ? intval($_POST['id_magasin']) : null;
        $idLocal          = (!empty($_POST['id_local'])) ? intval($_POST['id_local']) : null;
        $matriculeVoiture = (!empty($_POST['matricule_voiture'])) ? $_POST['matricule_voiture'] : null;
        $colIndex    = intval($_POST['col_idx']    ?? 0);
        $rowIndex    = intval($_POST['row_idx']    ?? 0);
        $targetFloor = $_POST['current_floor']      ?? 'rc';

        if ($idLocal !== null && !in_array($userProfil, [1, 2, 3])) {
            echo json_encode(['status' => 'error', 'message' => "Action refusée : Profil insuffisant pour placer des locaux."]);
            exit;
        }
        if ($matriculeVoiture !== null && !in_array($userProfil, [1, 2, 5])) {
            echo json_encode(['status' => 'error', 'message' => "Action refusée : Profil insuffisant pour placer des véhicules."]);
            exit;
        }

        if (($idMagasin > 0 || $idLocal > 0 || $matriculeVoiture !== null) && $colIndex >= 1 && $rowIndex >= 1) {
            $coords           = getCoordsFromGrid($colIndex, $rowIndex);
            $orientationValue = ($targetFloor === 'e1') ? 1 : 0;

            try {
                $pdo->beginTransaction();
                if ($idLocal) {
                    $chk = $pdo->prepare("SELECT COUNT(*) FROM element_plan WHERE idLocal = ?");
                    $chk->execute([$idLocal]);
                    if ($chk->fetchColumn() > 0) { throw new Exception("Ce local est déjà positionné sur le plan."); }
                }
                if ($matriculeVoiture) {
                    $chk = $pdo->prepare("SELECT COUNT(*) FROM element_plan WHERE matriculeVoiture = ?");
                    $chk->execute([$matriculeVoiture]);
                    if ($chk->fetchColumn() > 0) { throw new Exception("Ce véhicule est déjà positionné sur le plan."); }
                }

                $pdo->prepare(
                    "INSERT INTO element_plan (idPlan, xPlan, yPlan, orientation, idMagasin, idLocal, matriculeVoiture) VALUES (?, ?, ?, ?, ?, ?, ?)"
                )->execute([$idPlan, $coords['x'], $coords['y'], $orientationValue, $idMagasin, $idLocal, $matriculeVoiture]);
                
                $lastId = $pdo->lastInsertId();
                $itemDetails = "Inconnu";

                if ($idLocal) {
                    $stmt = $pdo->prepare("SELECT labelLocal FROM locaux WHERE idLocal = ?");
                    $stmt->execute([$idLocal]);
                    $itemDetails = "[Local] " . ($stmt->fetchColumn() ?: $idLocal);
                } elseif ($matriculeVoiture) {
                    $stmt = $pdo->prepare("SELECT marque, modele FROM voitures WHERE matricule = ?");
                    $stmt->execute([$matriculeVoiture]);
                    $v = $stmt->fetch(PDO::FETCH_ASSOC);
                    $itemDetails = "[Voiture] " . ($v ? "{$v['marque']} {$v['modele']}" : "") . " ($matriculeVoiture)";
                } elseif ($idMagasin) {
                    $stmt = $pdo->prepare("
                        SELECT m.typeEntite, eq.libelle as eqLabel, im.libelle as imLabel 
                        FROM magasin m
                        LEFT JOIN equipements eq ON m.typeEntite = 'Equipement' AND m.idCible = eq.idEquip
                        LEFT JOIN immobilisations im ON m.typeEntite = 'Immobilisation' AND m.idCible = im.idImmo
                        WHERE m.idMagasin = ?
                    ");
                    $stmt->execute([$idMagasin]);
                    $m = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($m) {
                        $label = ($m['typeEntite'] === 'Equipement') ? $m['eqLabel'] : $m['imLabel'];
                        $itemDetails = "[{$m['typeEntite']}] " . $label;
                    }
                }

                $actionDesc = "Placement de l'élément : " . $itemDetails . " | Pos: (X: {$coords['x']}, Y: {$coords['y']}) | Niveau: " . strtoupper($targetFloor);
                $pdo->prepare(
                    "INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)"
                )->execute([$userId, $actionDesc, 'CREATE', 'element_plan', $lastId]);
                
                $pdo->commit();
                echo json_encode(['status' => 'success', 'message' => "Élément enregistré avec succès."]);
                exit;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit;
            }
        }
        echo json_encode(['status' => 'error', 'message' => "Paramètres invalides."]);
        exit;
    }

    if (isset($_POST['action_merge_cells'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Zone fusionnée.']);
        exit;
    }
    if (isset($_POST['action_unmerge_cells'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Zone dissociée.']);
        exit;
    }
}

$rows  = isset($_GET['rows'])  ? max(2, min(30, (int)$_GET['rows']))  : 10;
$cols  = isset($_GET['cols'])  ? max(2, min(30, (int)$_GET['cols']))  : 16;
$floor = $_GET['floor'] ?? 'rc';

$canAdjustDimensions = in_array($userProfil, [1, 2, 3]);
$canMergeCells       = in_array($userProfil, [1, 2, 3]);
$allowedProfilsForLocaux = [1, 2, 3];

$allowedTypes = [];
if (in_array($userProfil, [1, 2, 5]))          { $allowedTypes[] = 'Voiture'; }
if (in_array($userProfil, [1, 2, 3]))          { $allowedTypes[] = 'Local'; }
if (in_array($userProfil, [1, 2, 3, 4, 5, 6])) { $allowedTypes[] = 'Immobilisation'; $allowedTypes[] = 'Equipement'; }

$composants = [];
if (in_array('Voiture', $allowedTypes)) {
    try {
        $queryVoitures = "SELECT matricule, marque, modele FROM voitures
                          WHERE statut = 'Disponible'
                          AND matricule NOT IN (SELECT matriculeVoiture FROM element_plan WHERE matriculeVoiture IS NOT NULL)";
        foreach ($pdo->query($queryVoitures)->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $composants[] = [
                'value' => $item['matricule'],
                'label' => $item['marque'] . " " . $item['modele'] . " (" . $item['matricule'] . ")",
                'type'  => 'Voiture',
                'stock' => 1,
            ];
        }
    } catch (PDOException $e) {}
}

if (in_array('Local', $allowedTypes)) {
    try {
        $queryLocaux = "SELECT idLocal, typeLocal, labelLocal FROM locaux";
        foreach ($pdo->query($queryLocaux)->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $composants[] = [
                'value' => $item['idLocal'],
                'label' => $item['labelLocal'] . " (" . $item['typeLocal'] . ")",
                'type'  => 'Local',
                'stock' => 1,
            ];
        }
    } catch (PDOException $e) {}
}

$magasinTypes = array_intersect(['Equipement', 'Immobilisation'], $allowedTypes);
if (!empty($magasinTypes)) {
    try {
        $inClause   = implode(',', array_fill(0, count($magasinTypes), '?'));
        $queryMagasin = "SELECT m.idMagasin, m.typeEntite, m.referenceInterne,
                         (m.quantiteDisponible - COALESCE(ep_count.placed, 0)) as totalStock,
                         CASE
                            WHEN m.typeEntite = 'Equipement'     THEN eq.libelle
                            WHEN m.typeEntite = 'Immobilisation' THEN im.libelle
                         END as element_label
                         FROM magasin m
                         LEFT JOIN equipements eq     ON m.typeEntite = 'Equipement'    AND m.idCible = eq.idEquip
                         LEFT JOIN immobilisations im ON m.typeEntite = 'Immobilisation' AND m.idCible = im.idImmo
                         LEFT JOIN (SELECT idMagasin, COUNT(*) as placed FROM element_plan WHERE idMagasin IS NOT NULL GROUP BY idMagasin) ep_count
                                ON m.idMagasin = ep_count.idMagasin
                         WHERE m.typeEntite IN ($inClause)
                         HAVING totalStock > 0";
        $stmtM = $pdo->prepare($queryMagasin);
        $stmtM->execute(array_values($magasinTypes));
        foreach ($stmtM->fetchAll(PDO::FETCH_ASSOC) as $item) {
            if (intval($item['totalStock']) > 0) {
                $composants[] = [
                    'value' => $item['idMagasin'],
                    'label' => $item['element_label'] . " (" . $item['referenceInterne'] . ")",
                    'type'  => $item['typeEntite'],
                    'stock' => intval($item['totalStock']),
                ];
            }
        }
    } catch (PDOException $e) {}
}

$elementsPositionnes = [];
try {
    $query = "SELECT ep.xPlan, ep.yPlan, ep.idLocal, ep.matriculeVoiture, m.typeEntite as mType,
              eq.libelle as eqLabel, im.libelle as imLabel, l.labelLocal as lLabel,
              v.modele as vLabel, v.marque as vMarque, ep.idMagasin
              FROM element_plan ep
              LEFT JOIN magasin m       ON ep.idMagasin          = m.idMagasin
              LEFT JOIN equipements eq  ON m.typeEntite = 'Equipement'    AND m.idCible = eq.idEquip
              LEFT JOIN immobilisations im ON m.typeEntite = 'Immobilisation' AND m.idCible = im.idImmo
              LEFT JOIN locaux l        ON ep.idLocal             = l.idLocal
              LEFT JOIN voitures v      ON ep.matriculeVoiture    = v.matricule
              WHERE ep.idPlan = ? AND ep.orientation = ?";
    $filterOrientation = ($floor === 'e1') ? 1 : 0;
    $stmt = $pdo->prepare($query);
    $stmt->execute([$idPlan, $filterOrientation]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $raw) {
        $c = ($raw['xPlan'] / 50) + 1;
        $r = ($raw['yPlan'] / 50) + 1;
        $label = ''; $type = ''; $id = '0';
        if (!empty($raw['idLocal'])) {
            $label = $raw['lLabel'];
            $type  = 'Local';
            $id    = $raw['idLocal'];
        } elseif (!empty($raw['matriculeVoiture'])) {
            $label = $raw['vMarque'] . " " . $raw['vLabel'];
            $type  = 'Voiture';
            $id    = $raw['matriculeVoiture'];
        } elseif (!empty($raw['mType'])) {
            $label = (($raw['mType'] === 'Equipement') ? $raw['eqLabel'] : $raw['imLabel']);
            $type  = $raw['mType'];
            $id    = $raw['idMagasin'];
        }
        if ($label !== '' && in_array($type, $allowedTypes)) {
            $elementsPositionnes["{$r}_{$c}"] = ['id' => $id, 'label' => $label, 'type' => $type];
        }
    }
} catch (PDOException $e) {}

$groupedComposants = [];
foreach ($composants as $comp) {
    $groupedComposants[$comp['type']][] = $comp;
}
$groupLabels = [
    'Voiture'        => '🚗 Voitures',
    'Local'          => '🏢 Locaux',
    'Equipement'     => '🛠️ Équipements',
    'Immobilisation' => '📦 Immobilisations',
];

require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>
<style>
    .grid-item {
        position: relative !important;
        cursor: cell !important;
        transition: background-color 0.15s;
        user-select: none;
    }
    .grid-item.selected {
        outline: 3px solid #3b82f6 !important;
        background-color: rgba(59, 130, 246, 0.2) !important;
        z-index: 10;
    }
    .grid-item.selected-for-merge {
        background-color: rgba(16, 185, 129, 0.3) !important;
        outline: 2px dashed #10b981 !important;
    }
    .grid-item.selected-for-unmerge {
        background-color: rgba(239, 68, 68, 0.3) !important;
        outline: 2px dashed #ef4444 !important;
    }
    .front-cell-content {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none !important;
    }
    .placed-element {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0px;
        font-size: 11px;
        font-weight: 600;
        text-align: center;
        box-shadow: inset 0 0 0 1px rgba(0,0,0,0.1);
        pointer-events: none !important;
    }
    .custom-merged-zone {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #475569;
        color: #f8fafc;
        border: 1px solid #64748b;
        font-size: 11px;
        font-weight: 600;
        pointer-events: none !important;
    }
    .type-Local { background-color: #fef08a; color: #854d0e; border: 1px solid #eab308; }
    .type-Voiture { background-color: #bbf7d0; color: #166534; border: 1px solid #22c55e; }
    .type-Magasin, .type-Equipement, .type-Immobilisation { background-color: #bfdbfe; color: #1e40af; border: 1px solid #3b82f6; }
</style>

<?php if (!empty($errorMessage)): ?>
    <script>alert(<?= json_encode($errorMessage) ?>);</script>
<?php endif; ?>

<div id="toast-notification"></div>
<div class="main-wrapper">
    <div class="sidebar">
        <h3>Matériel &amp; Catalogues</h3>
        <div class="search-select-box">
            <input type="text" id="catalogSearch" placeholder="Rechercher un composant…">
        </div>
        <div id="accordionGroupContainer">
            <?php if (empty($groupedComposants)): ?>
                <span id="emptyCatalogMsg" style="font-size:12px;color:#cbd5e1;font-style:italic;padding:10px;">
                    Aucun article disponible.
                </span>
            <?php else: ?>
                <span id="emptyCatalogMsg" style="font-size:12px;color:#cbd5e1;font-style:italic;padding:10px;display:none;">
                    Aucun article disponible.
                </span>
                <?php foreach ($groupedComposants as $typeKey => $items): ?>
                    <details class="catalog-group" data-group-type="<?= htmlspecialchars($typeKey, ENT_QUOTES, 'UTF-8') ?>" open>
                        <summary><?= htmlspecialchars($groupLabels[$typeKey] ?? $typeKey, ENT_QUOTES, 'UTF-8') ?></summary>
                        <ul class="catalogue-container">
                            <?php foreach ($items as $comp): ?>
                                <li class="catalog-item-wrapper"
                                    data-item-id="<?= htmlspecialchars($comp['value'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-item-type="<?= htmlspecialchars($comp['type'], ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="drag-item"
                                         draggable="true"
                                         data-id="<?= htmlspecialchars($comp['value'], ENT_QUOTES, 'UTF-8') ?>"
                                         data-label="<?= htmlspecialchars($comp['label'], ENT_QUOTES, 'UTF-8') ?>"
                                         data-type="<?= htmlspecialchars($comp['type'], ENT_QUOTES, 'UTF-8') ?>"
                                         data-stock="<?= (int)$comp['stock'] ?>">
                                        <span><?= htmlspecialchars($comp['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="badge-stock">x<?= (int)$comp['stock'] ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="uml-viewport">
        <h2 style="font-size:1.2rem;margin-top:0;margin-bottom:20px;font-weight:600;color:#f8fafc;">
            Plan Actif : <?= $floor === 'rc' ? 'Rez-de-chaussée' : '1er Étage' ?>
            <span style="font-size:13px;color:#94a3b8;font-weight:normal;margin-left:10px;">
                (<?= $cols * 50 ?>px × <?= $rows * 50 ?>px)
            </span>
        </h2>
        <div class="grid-stage" id="gridStage">
            <div class="grid-container front-grid" id="frontGrid"
                 style="
                     grid-template-columns: repeat(<?= (int)$cols ?>, 50px);
                     grid-template-rows:    repeat(<?= (int)$rows ?>, 50px);
                     width:  <?= (int)$cols * 50 ?>px;
                     height: <?= (int)$rows * 50 ?>px;
                 ">
                <?php
                for ($r = 1; $r <= $rows; $r++) {
                    for ($c = 1; $c <= $cols; $c++) {
                        $cellKey = "{$r}_{$c}";
                        echo "<div class='grid-item' data-row='".(int)$r."' data-col='".(int)$c."'>";
                        echo "<div class='front-cell-content'>";
                        if (isset($elementsPositionnes[$cellKey])) {
                            $el = $elementsPositionnes[$cellKey];
                            $safeType = htmlspecialchars($el['type'], ENT_QUOTES, 'UTF-8');
                            echo "<div class='placed-element type-{$safeType}' data-placed-id='".htmlspecialchars($el['id'], ENT_QUOTES, 'UTF-8')."' data-placed-type='{$safeType}'>";
                            echo "<span>" . htmlspecialchars($el['label'], ENT_QUOTES, 'UTF-8') . "</span>";
                            echo "</div>";
                        }
                        echo "</div>";
                        echo "</div>";
                    }
                }
                ?>
            </div>
        </div>
        <div class="zoom-controls">
            <button id="zoom-in"    title="Zoom avant">➕</button>
            <button id="zoom-out"   title="Zoom arrière">➖</button>
            <button id="zoom-reset" title="Réinitialiser la vue">🔄</button>
        </div>
    </div>
    <div class="control-sidebar">
        <h3>Configuration</h3>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <a href="?floor=rc&rows=<?= (int)$rows ?>&cols=<?= (int)$cols ?>"
               class="control-btn <?= $floor === 'rc' ? 'active' : '' ?>">
                 Rez-de-chaussée
            </a>
            <a href="?floor=e1&rows=<?= (int)$rows ?>&cols=<?= (int)$cols ?>"
               class="control-btn <?= $floor === 'e1' ? 'active' : '' ?>">
                 1er Étage
            </a>
        </div>
        <?php if ($canMergeCells): ?>
        <h3>Fusion</h3>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <button id="btnMergeCells" class="control-btn" disabled>Fusionner</button>
            <button id="btnMenuUnmergeMode" class="control-btn unmerge-action-btn">Dissocier</button>
        </div>
        <?php endif; ?>
        <h3>Actions</h3>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <button id="deleteSelectedBtn" class="control-btn btn-danger">
                Supprimer les éléments
            </button>
        </div>
    </div>
</div>
<script>
    window.PLAN_CONFIG = {
        allowedProfilsForLocaux: <?php echo json_encode($allowedProfilsForLocaux); ?>,
        currentUserProfilId: <?php echo json_encode($userProfil); ?>,
        currentFloor: <?php echo json_encode($floor); ?>
    };
</script>
<?php require_once '../includes/footer.php'; ?>