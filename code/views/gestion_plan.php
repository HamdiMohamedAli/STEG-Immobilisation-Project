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

$userProfil = (int)$_SESSION['idProfil'];
$userId     = $_SESSION['idUtilisateur'];
$primaryKey = 'idElement';

// --- HANDLE SUB-PLANS & FLOORS INITIALIZATION ---
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `plan_centre` (
      `idPlan` INT(11) NOT NULL AUTO_INCREMENT,
      `libelleEtage` VARCHAR(100) NOT NULL DEFAULT 'Étage 1',
      `parentLocalId` INT(11) DEFAULT NULL,
      `nb_lignes` INT(11) NOT NULL DEFAULT 10,
      `nb_colonnes` INT(11) NOT NULL DEFAULT 16,
      PRIMARY KEY (`idPlan`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
} catch (Exception $ex) {}

try {
    $pdo->exec("ALTER TABLE plan_centre ADD COLUMN parentLocalId INT(11) DEFAULT NULL");
} catch (Exception $ex) {}
try {
    $pdo->exec("ALTER TABLE plan_centre ADD COLUMN nb_lignes INT(11) NOT NULL DEFAULT 10");
} catch (Exception $ex) {}
try {
    $pdo->exec("ALTER TABLE plan_centre ADD COLUMN nb_colonnes INT(11) NOT NULL DEFAULT 16");
} catch (Exception $ex) {}

$allPlans = [];
try {
    $stmtPlans = $pdo->query("SELECT * FROM plan_centre ORDER BY idPlan ASC");
    $allPlans = $stmtPlans->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

if (empty($allPlans)) {
    $pdo->exec("INSERT INTO `plan_centre` (`idPlan`, `libelleEtage`, `parentLocalId`, `nb_lignes`, `nb_colonnes`) VALUES (1, 'Étage 1', NULL, 10, 16)");
    $allPlans = [['idPlan' => 1, 'libelleEtage' => 'Étage 1', 'parentLocalId' => null, 'nb_lignes' => 10, 'nb_colonnes' => 16]];
}

foreach ($allPlans as &$p) {
    if (!isset($p['libelleEtage'])) {
        $p['libelleEtage'] = 'Étage ' . $p['idPlan'];
    }
    if (!isset($p['nb_lignes'])) $p['nb_lignes'] = 10;
    if (!isset($p['nb_colonnes'])) $p['nb_colonnes'] = 16;
}
unset($p);

$requestedPlanId = isset($_GET['idPlan']) ? (int)$_GET['idPlan'] : ($allPlans[0]['idPlan'] ?? 1);
$validPlanIds = array_column($allPlans, 'idPlan');
$idPlan = in_array($requestedPlanId, $validPlanIds) ? $requestedPlanId : ($allPlans[0]['idPlan'] ?? 1);

$activePlan = null;
foreach ($allPlans as $p) {
    if ((int)$p['idPlan'] === $idPlan) {
        $activePlan = $p;
        break;
    }
}
$activePlanLabel = $activePlan['libelleEtage'] ?? 'Étage 1';
$parentLocalId = $activePlan['parentLocalId'] ?? null;
$rows = max(2, min(30, (int)($activePlan['nb_lignes'] ?? 10)));
$cols = max(2, min(30, (int)($activePlan['nb_colonnes'] ?? 16)));

// Determine the ground floor ID (first plan with no parent)
$groundFloorId = null;
foreach ($allPlans as $p) {
    if (empty($p['parentLocalId'])) {
        $groundFloorId = (int)$p['idPlan'];
        break;
    }
}

function getCoordsFromGrid($col, $row) {
    return [
        'x' => (max(1, intval($col)) - 1) * 50,
        'y' => (max(1, intval($row)) - 1) * 50,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_update_dimensions'])) {
        header('Content-Type: application/json');
        $targetPlanId = intval($_POST['id_plan'] ?? $idPlan);
        $newRows = max(2, min(30, intval($_POST['rows'] ?? 10)));
        $newCols = max(2, min(30, intval($_POST['cols'] ?? 16)));

        try {
            $upd = $pdo->prepare("UPDATE plan_centre SET nb_lignes = ?, nb_colonnes = ? WHERE idPlan = ?");
            $upd->execute([$newRows, $newCols, $targetPlanId]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if (isset($_POST['action_add_floor'])) {
        header('Content-Type: application/json');
        try {
            // Count only main floors (where parentLocalId is null/empty)
            $mainFloorsCount = 0;
            foreach ($allPlans as $p) {
                if (empty($p['parentLocalId'])) {
                    $mainFloorsCount++;
                }
            }
            $libelleEtage = "Étage " . ($mainFloorsCount + 1);

            $insert = $pdo->prepare("INSERT INTO plan_centre (libelleEtage, parentLocalId, nb_lignes, nb_colonnes) VALUES (?, NULL, 10, 16)");
            $insert->execute([$libelleEtage]);
            $newIdPlan = $pdo->lastInsertId();

            echo json_encode(['success' => true, 'idPlan' => $newIdPlan, 'libelle' => $libelleEtage]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if (isset($_POST['action_delete_floor'])) {
        header('Content-Type: application/json');
        $targetPlanId = intval($_POST['idPlan'] ?? 0);
        if ($targetPlanId <= 1) {
            echo json_encode(['success' => false, 'message' => 'Impossible de supprimer le plan principal.']);
            exit;
        }

        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM element_plan WHERE idPlan = ?")->execute([$targetPlanId]);
            $pdo->prepare("DELETE FROM plan_centre WHERE idPlan = ?")->execute([$targetPlanId]);
            $pdo->commit();

            $fallbackStmt = $pdo->query("SELECT idPlan FROM plan_centre ORDER BY idPlan ASC LIMIT 1");
            $nextPlanId = $fallbackStmt->fetchColumn();

            echo json_encode(['success' => true, 'nextPlanId' => $nextPlanId]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if (isset($_POST['action_enter_subplan'])) {
        header('Content-Type: application/json');
        $localId = intval($_POST['id_local'] ?? 0);
        $localLabel = trim($_POST['local_label'] ?? 'Local');

        if ($localId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Local invalide.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT idPlan FROM plan_centre WHERE parentLocalId = ? LIMIT 1");
            $stmt->execute([$localId]);
            $subPlanId = $stmt->fetchColumn();

            if (!$subPlanId) {
                $ins = $pdo->prepare("INSERT INTO plan_centre (libelleEtage, parentLocalId, nb_lignes, nb_colonnes) VALUES (?, ?, 8, 8)");
                $ins->execute(["Sous-plan: " . $localLabel, $localId]);
                $subPlanId = $pdo->lastInsertId();
            }

            echo json_encode(['success' => true, 'subPlanId' => $subPlanId]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if (isset($_POST['action_delete_item'])) {
        header('Content-Type: application/json');
        $idElement = intval($_POST['idElement'] ?? 0);

        if ($idElement > 0) {
            try {
                $sel = $pdo->prepare("SELECT idLocal FROM element_plan WHERE idElement = ?");
                $sel->execute([$idElement]);
                $elData = $sel->fetch(PDO::FETCH_ASSOC);
                if ($elData && !empty($elData['idLocal'])) {
                    $pdo->prepare("DELETE FROM plan_centre WHERE parentLocalId = ?")->execute([$elData['idLocal']]);
                }

                $pdo->prepare("DELETE FROM element_plan WHERE {$primaryKey} = ?")->execute([$idElement]);
                echo json_encode(['status' => 'success', 'message' => "Élément supprimé avec succès."]);
                exit;
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => "Erreur DB: " . $e->getMessage()]);
                exit;
            }
        }
        echo json_encode(['status' => 'error', 'message' => "Paramètres invalides."]);
        exit;
    }

    if (isset($_POST['action_position'])) {
        header('Content-Type: application/json');
        
        $targetPlanId     = intval($_POST['id_plan'] ?? $idPlan);
        $idElement        = (!empty($_POST['id_element']))        ? intval($_POST['id_element'])        : null;
        $idMagasin        = (!empty($_POST['id_magasin']))        ? intval($_POST['id_magasin'])        : null;
        $idLocal          = (!empty($_POST['id_local']))          ? intval($_POST['id_local'])          : null;
        $matriculeVoiture = (!empty($_POST['matricule_voiture'])) ? $_POST['matricule_voiture']         : null;
        $colIndex         = intval($_POST['col_idx'] ?? 0);
        $rowIndex         = intval($_POST['row_idx'] ?? 0);

        if ($colIndex >= 1 && $rowIndex >= 1) {
            $coords = getCoordsFromGrid($colIndex, $rowIndex);

            try {
                $pdo->beginTransaction();

                $stmtCheck = $pdo->prepare("SELECT idElement FROM element_plan WHERE idPlan = ? AND xPlan = ? AND yPlan = ?");
                $stmtCheck->execute([$targetPlanId, $coords['x'], $coords['y']]);
                $existingCellItem = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                if ($existingCellItem && (!$idElement || $existingCellItem['idElement'] != $idElement)) {
                    $pdo->prepare("DELETE FROM element_plan WHERE idElement = ?")->execute([$existingCellItem['idElement']]);
                }

                if ($idElement) {
                    $stmtMove = $pdo->prepare("UPDATE element_plan SET idPlan = ?, xPlan = ?, yPlan = ? WHERE idElement = ?");
                    $stmtMove->execute([$targetPlanId, $coords['x'], $coords['y'], $idElement]);
                } else {
                    $stmtIns = $pdo->prepare("INSERT INTO element_plan (idPlan, xPlan, yPlan, idMagasin, idLocal, matriculeVoiture) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmtIns->execute([$targetPlanId, $coords['x'], $coords['y'], $idMagasin, $idLocal, $matriculeVoiture]);
                }

                $pdo->commit();
                echo json_encode(['status' => 'success', 'message' => "Élément placé/déplacé avec succès."]);
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
}

$composants = [];

// Only fetch vehicles if we are on the main ground floor (not an upper floor or sub-plan)
if (empty($parentLocalId) && $idPlan === $groundFloorId) {
    try {
        $queryVoitures = "SELECT matricule, marque, modele FROM voitures
                          WHERE statut = 'Disponible'
                          AND matricule NOT IN (SELECT matriculeVoiture FROM element_plan WHERE matriculeVoiture IS NOT NULL)";
        $stmtV = $pdo->prepare($queryVoitures);
        $stmtV->execute();
        foreach ($stmtV->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $composants[] = [
                'value' => $item['matricule'],
                'label' => $item['marque'] . " " . $item['modele'] . " (" . $item['matricule'] . ")",
                'type'  => 'Voiture',
                'stock' => 1,
            ];
        }
    } catch (PDOException $e) {}
}

try {
    $queryLocaux = "SELECT idLocal, typeLocal, labelLocal FROM locaux
                    WHERE idLocal NOT IN (SELECT idLocal FROM element_plan WHERE idLocal IS NOT NULL)";
    $stmtL = $pdo->prepare($queryLocaux);
    $stmtL->execute();
    foreach ($stmtL->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $composants[] = [
            'value' => $item['idLocal'],
            'label' => $item['labelLocal'] . " (" . $item['typeLocal'] . ")",
            'type'  => 'Local',
            'stock' => 1,
        ];
    }
} catch (PDOException $e) {}

try {
    $queryMagasin = "SELECT m.idMagasin, m.typeEntite, m.referenceInterne,
                     (m.quantiteDisponible - COALESCE(ep_count.placed, 0)) as totalStock,
                     CASE
                        WHEN m.typeEntite = 'Equipement'    THEN eq.libelle
                        WHEN m.typeEntite = 'Immobilisation' THEN im.libelle
                     END as element_label
                     FROM magasin m
                     LEFT JOIN equipements eq     ON m.typeEntite = 'Equipement'    AND m.idCible = eq.idEquip
                     LEFT JOIN immobilisations im ON m.typeEntite = 'Immobilisation' AND m.idCible = im.idImmo
                     LEFT JOIN (SELECT idMagasin, COUNT(*) as placed FROM element_plan WHERE idMagasin IS NOT NULL GROUP BY idMagasin) ep_count
                                ON m.idMagasin = ep_count.idMagasin
                     WHERE m.typeEntite IN ('Equipement', 'Immobilisation')
                     HAVING totalStock > 0";
    
    $stmtM = $pdo->prepare($queryMagasin);
    $stmtM->execute();
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

$elementsPositionnes = [];
try {
    $query = "SELECT ep.idElement, ep.xPlan, ep.yPlan, ep.idLocal, ep.matriculeVoiture, m.typeEntite as mType,
              eq.libelle as eqLabel, im.libelle as imLabel, l.labelLocal as lLabel,
              v.modele as vLabel, v.marque as vMarque, ep.idMagasin
              FROM element_plan ep
              LEFT JOIN magasin m          ON ep.idMagasin      = m.idMagasin
              LEFT JOIN equipements eq     ON m.typeEntite = 'Equipement'    AND m.idCible = eq.idEquip
              LEFT JOIN immobilisations im ON m.typeEntite = 'Immobilisation' AND m.idCible = im.idImmo
              LEFT JOIN locaux l           ON ep.idLocal        = l.idLocal
              LEFT JOIN voitures v         ON ep.matriculeVoiture = v.matricule
              WHERE ep.idPlan = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$idPlan]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $raw) {
        $c = ($raw['xPlan'] / 50) + 1;
        $r = ($raw['yPlan'] / 50) + 1;
        $label = ''; $type = ''; $idValue = '0';
        if (!empty($raw['idLocal'])) {
            $label = $raw['lLabel'];
            $type  = 'Local';
            $idValue    = $raw['idLocal'];
        } elseif (!empty($raw['matriculeVoiture'])) {
            $label = $raw['vMarque'] . " " . $raw['vLabel'];
            $type  = 'Voiture';
            $idValue    = $raw['matriculeVoiture'];
        } elseif (!empty($raw['mType'])) {
            $label = (($raw['mType'] === 'Equipement') ? $raw['eqLabel'] : $raw['imLabel']);
            $type  = $raw['mType'];
            $idValue    = $raw['idMagasin'];
        }
        if ($label !== '') {
            $elementsPositionnes["{$r}_{$c}"] = [
                'idElement' => $raw['idElement'],
                'id'        => $idValue,
                'label'     => $label,
                'type'      => $type
            ];
        }
    }
} catch (PDOException $e) {}

$groupLabels = [
    'Local'            => 'Locaux',
    'Voiture'          => 'Voitures',
    'Equipement'     => 'Équipements',
    'Immobilisation' => 'Immobilisations',
];

$groupedComposants = [];
foreach ($groupLabels as $key => $label) {
    $groupedComposants[$key] = [];
}
foreach ($composants as $comp) {
    if (isset($groupedComposants[$comp['type']])) {
        $groupedComposants[$comp['type']][] = $comp;
    }
}
$groupedComposants = array_filter($groupedComposants, function($items) {
    return !empty($items);
});

require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>
<style>
    .grid-stage {
        overflow: hidden;
        position: relative;
        cursor: grab;
        background: #0f172a;
        border-radius: 8px;
        min-height: 500px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .grid-stage:active {
        cursor: grabbing;
    }
    .grid-container.front-grid {
        transform-origin: center center;
        transition: transform 0.05s ease-out;
    }
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
    .front-cell-content {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .placed-element {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 600;
        text-align: center;
        box-shadow: inset 0 0 0 1px rgba(0,0,0,0.1);
        cursor: grab;
        padding: 4px;
    }
    .placed-element:active { cursor: grabbing; }
    .type-Local { background-color: #fef08a; color: #854d0e; border: 1px solid #eab308; }
    .type-Voiture { background-color: #bbf7d0; color: #166534; border: 1px solid #22c55e; }
    .type-Magasin, .type-Equipement, .type-Immobilisation { background-color: #bfdbfe; color: #1e40af; border: 1px solid #3b82f6; }
</style>

<div id="toast-notification"></div>
<div class="main-wrapper">
    <div class="sidebar">
        <h3>Matériel &amp; Catalogues</h3>
        <div class="search-select-box">
            <input type="text" id="catalogSearch" placeholder="Rechercher un composant…">
        </div>
        <div id="accordionGroupContainer">
            <?php foreach ($groupedComposants as $typeKey => $items): ?>
                <details class="catalog-group" data-group-type="<?= htmlspecialchars($typeKey, ENT_QUOTES, 'UTF-8') ?>">
                    <summary><?= htmlspecialchars($groupLabels[$typeKey] ?? $typeKey, ENT_QUOTES, 'UTF-8') ?></summary>
                    <ul class="catalogue-container">
                        <?php foreach ($items as $comp): ?>
                            <li class="catalog-item-wrapper">
                                <div class="drag-item"
                                     draggable="true"
                                     data-id="<?= htmlspecialchars($comp['value'], ENT_QUOTES, 'UTF-8') ?>"
                                     data-label="<?= htmlspecialchars($comp['label'], ENT_QUOTES, 'UTF-8') ?>"
                                     data-type="<?= htmlspecialchars($comp['type'], ENT_QUOTES, 'UTF-8') ?>">
                                    <span><?= htmlspecialchars($comp['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="badge-stock">x<?= (int)$comp['stock'] ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="uml-viewport">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="font-size:1.2rem;margin:0;font-weight:600;color:#f8fafc;">
                Plan Actif : <?= htmlspecialchars($activePlanLabel, ENT_QUOTES, 'UTF-8') ?> (<?= (int)$cols ?>m × <?= (int)$rows ?>m)
            </h2>
        </div>
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
                            echo "<div class='placed-element type-{$safeType}' 
                                       draggable='true'
                                       data-element-id='".(int)$el['idElement']."'
                                       data-placed-id='".htmlspecialchars($el['id'], ENT_QUOTES, 'UTF-8')."' 
                                       data-placed-type='{$safeType}'
                                       data-placed-label='".htmlspecialchars($el['label'], ENT_QUOTES, 'UTF-8')."'>";
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
    </div>
    <div class="control-sidebar">
        <h3>Configuration</h3>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <?php if (empty($parentLocalId)): ?>
                <label for="floorSelect" style="font-size:13px;color:#cbd5e1;"><strong>Étage actuel :</strong></label>
                <select id="floorSelect" onchange="switchFloor(this.value)" class="control-btn" style="background:#1e293b;color:#fff;border:1px solid #475569;text-align:left;padding:8px;">
                    <?php foreach ($allPlans as $planOpt): ?>
                        <?php if(empty($planOpt['parentLocalId'])): ?>
                            <option value="<?= (int)$planOpt['idPlan'] ?>" <?= ((int)$planOpt['idPlan'] === $idPlan) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($planOpt['libelleEtage'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <button type="button" onclick="addNewFloor()" class="control-btn" style="background:#3b82f6;color:#fff;border:none;">
                    + Ajouter un étage
                </button>
                <button type="button" onclick="deleteCurrentFloor()" class="control-btn btn-danger" style="background:#ef4444;color:#fff;border:none;">
                    Supprimer cet étage
                </button>
            <?php else: ?>
                <button onclick="switchFloor(1)" class="control-btn" style="background:#475569;color:#fff;border:none;padding:8px;font-size:13px;cursor:pointer;">
                    ← Retour au plan principal
                </button>
            <?php endif; ?>
        </div>

        <div style="margin-top:15px;">
            <h3 style="margin-bottom:8px;">Dimensions (1m² / cellule)</h3>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <label for="inputRows" style="font-size:13px;color:#cbd5e1;">Lignes (m) :</label>
                    <input type="number" id="inputRows" min="2" max="30" value="<?= (int)$rows ?>" style="width:70px;background:#1e293b;color:#fff;border:1px solid #475569;padding:4px 8px;border-radius:4px;">
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <label for="inputCols" style="font-size:13px;color:#cbd5e1;">Colonnes (m) :</label>
                    <input type="number" id="inputCols" min="2" max="30" value="<?= (int)$cols ?>" style="width:70px;background:#1e293b;color:#fff;border:1px solid #475569;padding:4px 8px;border-radius:4px;">
                </div>
                <button type="button" onclick="updateGridSize()" class="control-btn" style="background:#3b82f6;color:#fff;border:none;margin-top:4px;cursor:pointer;">
                    Enregistrer les dimensions
                </button>
            </div>
        </div>

        <div id="subplanSection" style="display:none; margin-top:15px;">
            <h3 id="subplanHeader" style="margin-bottom:8px;">Sous-plan (Local)</h3>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <button id="enterSubPlanBtn" class="control-btn" style="background:#854d0e;color:#fff;border:none;">
                    Entrer dans le sous-plan ➔
                </button>
            </div>
        </div>

        <div id="actionsSection" style="display:none; margin-top:15px;">
            <h3 style="margin-bottom:8px;">Actions</h3>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <button id="deleteSelectedBtn" class="control-btn btn-danger">
                    Supprimer l'élément sélectionné
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.PLAN_CONFIG = {
        currentPlanId: <?php echo json_encode($idPlan); ?>
    };

    function switchFloor(idPlanVal) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('idPlan', idPlanVal);
        window.location.search = urlParams.toString();
    }

    function updateGridSize() {
        const rows = document.getElementById('inputRows').value;
        const cols = document.getElementById('inputCols').value;
        
        const formData = new FormData();
        formData.append('action_update_dimensions', '1');
        formData.append('id_plan', window.PLAN_CONFIG.currentPlanId);
        formData.append('rows', rows);
        formData.append('cols', cols);

        fetch(window.location.pathname, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        });
    }

    function addNewFloor() {
        const formData = new FormData();
        formData.append('action_add_floor', '1');
        fetch(window.location.pathname, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => { if(data.success) { switchFloor(data.idPlan); } else { alert(data.message); } });
    }

    function deleteCurrentFloor() {
        if (!confirm('Supprimer cet étage et ses éléments ?')) return;
        const formData = new FormData();
        formData.append('action_delete_floor', '1');
        formData.append('idPlan', window.PLAN_CONFIG.currentPlanId);
        fetch(window.location.pathname, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => { if(data.success) { switchFloor(data.nextPlanId); } else { alert(data.message); } });
    }

    function enterSubPlan(localId, localLabel) {
        const formData = new FormData();
        formData.append('action_enter_subplan', '1');
        formData.append('id_local', localId);
        formData.append('local_label', localLabel);
        
        fetch(window.location.pathname, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                switchFloor(data.subPlanId);
            } else {
                alert(data.message);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        let selectedCell = null;
        let activeLocalData = null;

        const subplanSection = document.getElementById('subplanSection');
        const enterSubPlanBtn = document.getElementById('enterSubPlanBtn');
        const actionsSection = document.getElementById('actionsSection');

        document.querySelectorAll('.grid-item').forEach(cell => {
            cell.addEventListener('click', (e) => {
                document.querySelectorAll('.grid-item').forEach(c => c.classList.remove('selected'));
                cell.classList.add('selected');
                selectedCell = cell;

                const placedEl = cell.querySelector('.placed-element');
                if (placedEl) {
                    actionsSection.style.display = 'block';

                    if (placedEl.getAttribute('data-placed-type') === 'Local') {
                        activeLocalData = {
                            id: placedEl.getAttribute('data-placed-id'),
                            label: placedEl.getAttribute('data-placed-label')
                        };
                        subplanSection.style.display = 'block';
                    } else {
                        activeLocalData = null;
                        subplanSection.style.display = 'none';
                    }
                } else {
                    activeLocalData = null;
                    subplanSection.style.display = 'none';
                    actionsSection.style.display = 'none';
                }
            });
        });

        if (enterSubPlanBtn) {
            enterSubPlanBtn.addEventListener('click', () => {
                if (activeLocalData) {
                    enterSubPlan(activeLocalData.id, activeLocalData.label);
                }
            });
        }

        const deleteBtn = document.getElementById('deleteSelectedBtn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => {
                if (!selectedCell) {
                    alert('Veuillez sélectionner une case contenant un élément.');
                    return;
                }
                const placedEl = selectedCell.querySelector('.placed-element');
                if (!placedEl) {
                    alert('Aucun élément à supprimer dans cette case.');
                    return;
                }
                const idElement = placedEl.getAttribute('data-element-id');
                if (!idElement) return;

                if (!confirm('Voulez-vous vraiment supprimer cet élément du plan ?')) {
                    return;
                }

                const formData = new FormData();
                formData.append('action_delete_item', '1');
                formData.append('idElement', idElement);

                fetch(window.location.pathname, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            });
        }

        // --- FREE PAN & ZOOM CENTERING ---
        const stage = document.getElementById('gridStage');
        const container = document.getElementById('frontGrid');
        
        let scale = 1;
        let pointX = 0;
        let pointY = 0;
        let panning = false;
        let startX = 0;
        let startY = 0;

        function updateTransform() {
            container.style.transform = `translate(${pointX}px, ${pointY}px) scale(${scale})`;
        }

        stage.addEventListener('wheel', (e) => {
            e.preventDefault();
            const zoomIntensity = 0.1;
            
            if (e.deltaY < 0) {
                scale = Math.min(scale + zoomIntensity, 2.5);
            } else {
                scale = Math.max(scale - zoomIntensity, 0.4);
            }

            updateTransform();
        }, { passive: false });

        stage.addEventListener('mousedown', (e) => {
            if (e.target.closest('.grid-item') || e.target.closest('.control-sidebar') || e.target.closest('.sidebar') || e.target.closest('button')) return;
            panning = true;
            startX = e.clientX - pointX;
            startY = e.clientY - pointY;
        });

        window.addEventListener('mousemove', (e) => {
            if (!panning) return;
            e.preventDefault();
            pointX = e.clientX - startX;
            pointY = e.clientY - startY;
            updateTransform();
        });

        window.addEventListener('mouseup', () => {
            panning = false;
        });

        // Drag and Drop implementation
        let draggedData = null;

        document.querySelectorAll('.drag-item, .placed-element').forEach(item => {
            item.addEventListener('dragstart', (e) => {
                draggedData = {
                    idElement: item.getAttribute('data-element-id') || null,
                    id: item.getAttribute('data-id') || item.getAttribute('data-placed-id'),
                    type: item.getAttribute('data-type') || item.getAttribute('data-placed-type')
                };
            });
        });

        document.querySelectorAll('.grid-item').forEach(cell => {
            cell.addEventListener('dragover', (e) => e.preventDefault());
            cell.addEventListener('drop', (e) => {
                e.preventDefault();
                if (!draggedData) return;

                const col = cell.getAttribute('data-col');
                const row = cell.getAttribute('data-row');

                const formData = new FormData();
                formData.append('action_position', '1');
                formData.append('id_plan', window.PLAN_CONFIG.currentPlanId);
                formData.append('col_idx', col);
                formData.append('row_idx', row);

                if (draggedData.idElement) {
                    formData.append('id_element', draggedData.idElement);
                } else {
                    if (draggedData.type === 'Local') formData.append('id_local', draggedData.id);
                    else if (draggedData.type === 'Voiture') formData.append('matricule_voiture', draggedData.id);
                    else formData.append('id_magasin', draggedData.id);
                }

                fetch(window.location.pathname, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            });
        });
    });
</script>
<?php require_once '../includes/footer.php'; ?>