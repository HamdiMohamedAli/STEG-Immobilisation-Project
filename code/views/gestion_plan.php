<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

$errorMessage = "";
$successMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_submit_steg_form'])) {
    $idCible = $_POST['idCible'] ?? '0';
    if (empty($idCible) || $idCible === '0') {
        $errorMessage = "Veuillez sélectionner un article valide depuis la liste déroulante.";
    } else {
        $successMessage = "Formulaire validé avec succès.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete_item'])) {
    header('Content-Type: application/json');
    
    $primaryKey = 'idElement'; 
    $colIndex = intval($_POST['col_idx'] ?? 0);
    $rowIndex = intval($_POST['row_idx'] ?? 0);
    $targetFloor = $_POST['current_floor'] ?? 'rc';
    $userId = $_SESSION['idUtilisateur'] ?? 1111; 
    $idPlan = 1;

    if ($colIndex >= 1 && $rowIndex >= 1) {
        $xPlan = ($colIndex - 1) * 50;
        $yPlan = ($rowIndex - 1) * 50;
        $orientationValue = ($targetFloor === 'e1') ? 1 : 0;

        try {
            $pdo->beginTransaction();
            
            $stmtFind = $pdo->prepare("SELECT {$primaryKey} FROM element_plan WHERE idPlan = ? AND xPlan = ? AND yPlan = ? AND orientation = ? LIMIT 1");
            $stmtFind->execute([$idPlan, $xPlan, $yPlan, $orientationValue]);
            $item = $stmtFind->fetch(PDO::FETCH_ASSOC);

            if ($item) {
                $idElement = $item[$primaryKey];
                
                $stmtDel = $pdo->prepare("DELETE FROM element_plan WHERE {$primaryKey} = ?");
                $stmtDel->execute([$idElement]);
                
                $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
                $actionDesc = "Suppression Élément Plan (X: $xPlan, Y: $yPlan) - Niveau: " . strtoupper($targetFloor);
                $stmtLog->execute([$userId, $actionDesc, 'DELETE', 'element_plan', $idElement]);
                
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_position'])) {
    header('Content-Type: application/json');
    
    $idMagasin = isset($_POST['id_magasin']) && $_POST['id_magasin'] !== '' ? intval($_POST['id_magasin']) : null;
    $idLocal = isset($_POST['id_local']) && $_POST['id_local'] !== '' ? intval($_POST['id_local']) : null;
    $matriculeVoiture = isset($_POST['matricule_voiture']) && $_POST['matricule_voiture'] !== '' ? $_POST['matricule_voiture'] : null;
    
    $colIndex = intval($_POST['col_idx'] ?? 0);
    $rowIndex = intval($_POST['row_idx'] ?? 0);
    $targetFloor = $_POST['current_floor'] ?? 'rc';
    $userId = $_SESSION['idUtilisateur'] ?? 1111; 
    $idPlan = 1;

    if (($idMagasin > 0 || $idLocal > 0 || $matriculeVoiture !== null) && $colIndex >= 1 && $rowIndex >= 1) {
        $xPlan = ($colIndex - 1) * 50;
        $yPlan = ($rowIndex - 1) * 50;
        $orientationValue = ($targetFloor === 'e1') ? 1 : 0;

        try {
            $pdo->beginTransaction();
            
            $sqlInsert = "INSERT INTO element_plan (idPlan, xPlan, yPlan, orientation, idMagasin, idLocal, matriculeVoiture) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sqlInsert)->execute([$idPlan, $xPlan, $yPlan, $orientationValue, $idMagasin, $idLocal, $matriculeVoiture]);
            $lastId = $pdo->lastInsertId();
            
            $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
            $actionDesc = "Placement Élément Plan (X: $xPlan, Y: $yPlan) - Niveau: " . strtoupper($targetFloor);
            $stmtLog->execute([$userId, $actionDesc, 'CREATE', 'element_plan', $lastId]);
            
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => "Élément enregistré avec succès."]);
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            echo json_encode(['status' => 'error', 'message' => "Erreur DB: " . $e->getMessage()]);
            exit;
        }
    }
    echo json_encode(['status' => 'error', 'message' => "Paramètres invalides."]);
    exit;
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';

$rows = isset($_GET['rows']) ? max(2, min(30, (int)$_GET['rows'])) : 10;
$cols = isset($_GET['cols']) ? max(2, min(30, (int)$_GET['cols'])) : 16;
$floor = isset($_GET['floor']) ? $_GET['floor'] : 'rc';
$idPlan = 1;

$userProfil = isset($_SESSION['idProfil']) ? (int)$_SESSION['idProfil'] : 1; 
$canAdjustDimensions = in_array($userProfil, [1, 2, 3]);
$canMergeCells = in_array($userProfil, [1, 2, 3]);

$allowedTypes = [];
if (in_array($userProfil, [1, 2, 5])) { $allowedTypes[] = 'Voiture'; }
if (in_array($userProfil, [1, 2, 3])) { $allowedTypes[] = 'Local'; }
if (in_array($userProfil, [1, 2, 3, 4, 5, 6])) { $allowedTypes[] = 'Immobilisation'; }
if (in_array($userProfil, [1, 2, 3, 4, 5, 6])) { $allowedTypes[] = 'Equipement'; }

$composants = [];

if (in_array('Voiture', $allowedTypes)) {
    try {
        $queryVoitures = "SELECT matricule, marque, modele FROM voitures WHERE statut = 'Disponible' AND matricule NOT IN (SELECT matriculeVoiture FROM element_plan WHERE matriculeVoiture IS NOT NULL)";
        $itemsV = $pdo->query($queryVoitures)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($itemsV as $item) {
            $composants[] = [
                'value' => $item['matricule'],
                'label' => $item['marque'] . " " . $item['modele'] . " (" . $item['matricule'] . ")",
                'type' => 'Voiture',
                'stock' => 1
            ];
        }
    } catch (PDOException $e) {}
}

if (in_array('Local', $allowedTypes)) {
    try {
        $queryLocaux = "SELECT idLocal, typeLocal, labelLocal FROM locaux";
        $itemsL = $pdo->query($queryLocaux)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($itemsL as $item) {
            $composants[] = [
                'value' => $item['idLocal'],
                'label' => $item['labelLocal'] . " (" . $item['typeLocal'] . ")",
                'type' => 'Local',
                'stock' => 1
            ];
        }
    } catch (PDOException $e) {}
}

$magasinTypes = array_intersect(['Equipement', 'Immobilisation'], $allowedTypes);
if (!empty($magasinTypes)) {
    try {
        $inClause = implode(',', array_fill(0, count($magasinTypes), '?'));
        $queryMagasin = "SELECT MIN(m.idMagasin) as idMagasin, m.typeEntite, m.referenceInterne,
                         (SUM(m.quantiteDisponible) - COALESCE(ep_count.placed, 0)) as totalStock,
                         CASE 
                            WHEN m.typeEntite = 'Equipement' THEN eq.libelle
                            WHEN m.typeEntite = 'Immobilisation' THEN im.libelle
                         END as element_label
                         FROM magasin m
                         LEFT JOIN equipements eq ON m.typeEntite = 'Equipement' AND m.idCible = eq.idEquip
                         LEFT JOIN immobilisations im ON m.typeEntite = 'Immobilisation' AND m.idCible = im.idImmo
                         LEFT JOIN (
                            SELECT idMagasin, COUNT(*) as placed 
                            FROM element_plan 
                            WHERE idMagasin IS NOT NULL 
                            GROUP BY idMagasin
                         ) ep_count ON m.idMagasin = ep_count.idMagasin
                         WHERE m.quantiteDisponible > 0 AND m.typeEntite IN ($inClause)
                         GROUP BY m.typeEntite, m.referenceInterne, element_label, ep_count.placed";
        
        $stmtM = $pdo->prepare($queryMagasin);
        $stmtM->execute(array_values($magasinTypes));
        $itemsM = $stmtM->fetchAll(PDO::FETCH_ASSOC);
        foreach ($itemsM as $item) {
            if (intval($item['totalStock']) > 0) {
                $composants[] = [
                    'value' => $item['idMagasin'],
                    'label' => $item['element_label'] . " (" . $item['referenceInterne'] . ")",
                    'type' => $item['typeEntite'],
                    'stock' => $item['totalStock']
                ];
            }
        }
    } catch (PDOException $e) {}
}

$elementsPositionnes = [];
try {
    $query = "SELECT ep.xPlan, ep.yPlan, ep.idLocal, ep.matriculeVoiture, m.typeEntite as mType, 
              eq.libelle as eqLabel, im.libelle as imLabel, l.labelLocal as lLabel, l.typeLocal as lType,
              v.modele as vLabel, v.marque as vMarque
              FROM element_plan ep
              LEFT JOIN magasin m ON ep.idMagasin = m.idMagasin
              LEFT JOIN equipements eq ON m.typeEntite = 'Equipement' AND m.idCible = eq.idEquip
              LEFT JOIN immobilisations im ON m.typeEntite = 'Immobilisation' AND m.idCible = im.idImmo
              LEFT JOIN locaux l ON ep.idLocal = l.idLocal
              LEFT JOIN voitures v ON ep.matriculeVoiture = v.matricule
              WHERE ep.idPlan = ? AND ep.orientation = ?";
    
    $filterOrientation = ($floor === 'e1') ? 1 : 0;
    $stmt = $pdo->prepare($query);
    $stmt->execute([$idPlan, $filterOrientation]);
    $rawElements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rawElements as $raw) {
        $c = ($raw['xPlan'] / 50) + 1;
        $r = ($raw['yPlan'] / 50) + 1;
        
        $label = '';
        $type = '';
        
        if (!empty($raw['idLocal'])) {
            $label = "[Local] " . $raw['lLabel'];
            $type = 'Local';
        } elseif (!empty($raw['matriculeVoiture'])) {
            $label = "[Voiture] " . $raw['vMarque'] . " " . $raw['vLabel'];
            $type = 'Voiture';
        } elseif (!empty($raw['mType'])) {
            $label = "[" . $raw['mType'] . "] " . (($raw['mType'] === 'Equipement') ? $raw['eqLabel'] : $raw['imLabel']);
            $type = $raw['mType'];
        }

        if ($label !== '' && in_array($type, $allowedTypes)) {
            $elementsPositionnes["{$r}_{$c}"] = ['label' => $label, 'type' => $type];
        }
    }
} catch (PDOException $e) {}

$groupedComposants = [];
foreach ($composants as $comp) {
    $groupedComposants[$comp['type']][] = $comp;
}

$groupLabels = [
    'Voiture' => '🚗 Voitures',
    'Local' => '🏢 Locaux',
    'Equipement' => '🛠️ Équipements',
    'Immobilisation' => '📦 Immobilisations'
];
?>

<?php if (!empty($errorMessage)): ?>
    <script>alert(<?= json_encode($errorMessage) ?>);</script>
<?php endif; ?>

<style>
    .main-wrapper {
        display: flex;
        height: calc(100vh - 60px); 
        font-family: system-ui, -apple-system, sans-serif;
        background-color: #0f172a; 
        overflow: hidden;
    }
    
    .sidebar, .control-sidebar { 
        width: 320px;
        background: #1e293b; 
        border-right: 1px solid #334155;
        padding: 20px;
        overflow-y: auto;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.2);
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .control-sidebar {
        border-right: none;
        border-left: 1px solid #334155;
    }

    .sidebar h3, .control-sidebar h3 { 
        font-size: 1.1rem;
        color: #f8fafc;
        margin: 0;
        padding-bottom: 10px;
        border-bottom: 2px solid #334155;
    }

    .search-select-box { 
        background: #0f172a; 
        border: 1px solid #334155; 
        border-radius: 8px; 
        padding: 6px; 
    }

    .search-select-box input { 
        background: #1e293b; 
        border: 1px solid #334155; 
        color: white; 
        padding: 8px; 
        border-radius: 6px; 
        font-size: 13px; 
        outline: none; 
        width: 100%; 
        box-sizing: border-box; 
    }
    
    .catalog-group { 
        margin-bottom: 8px; 
        background: #0f172a; 
        border: 1px solid #334155; 
        border-radius: 8px; 
        overflow: hidden; 
    }

    .catalog-group summary { 
        padding: 10px 12px; 
        font-size: 13px; 
        font-weight: 600; 
        color: #f8fafc; 
        cursor: pointer; 
        user-select: none; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        background: #1e293b; 
    }

    .catalog-group summary::after { 
        content: '\25BC'; 
        font-size: 9px; 
        color: #94a3b8; 
    }

    .catalog-group[open] summary::after { 
        transform: rotate(180deg); 
    }
    
    .catalogue-container { 
        max-height: 250px; 
        overflow-y: auto; 
        list-style: none; 
        padding: 8px; 
        margin: 0; 
        display: flex; 
        flex-direction: column; 
        gap: 6px; 
    }

    .drag-item { 
        background: #1e293b; 
        border: 1px solid #334155; 
        padding: 10px 12px; 
        border-radius: 8px; 
        cursor: grab; 
        font-size: 13px; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        color: #cbd5e1;
    }

    .drag-item:hover { 
        border-color: #38bdf8; 
        background: #334155; 
    }

    .badge-stock { 
        background: #334155; 
        color: #e2e8f0; 
        font-size: 11px; 
        padding: 2px 6px; 
        border-radius: 20px; 
        font-weight: 600; 
    }
    
    .uml-viewport { 
        position: relative;
        flex: 1;
        background-color: #0f172a; 
        overflow: hidden;
        padding: 24px;
        display: flex;
        flex-direction: column;
    }

    /* Suppression stricte des scrollbars sur la scène de grille */
    .grid-stage { 
        background: #0f172a; 
        border-radius: 8px; 
        border: 2px dashed #334155; 
        padding: 16px; 
        flex: 1; 
        overflow: hidden; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        cursor: grab;
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
    }

    .grid-stage::-webkit-scrollbar {
        display: none; /* Chrome, Safari and Opera */
    }

    .grid-stage:active {
        cursor: grabbing;
    }

    .grid-container { 
        display: grid; 
        background: #1e293b; 
        border: 1px solid #475569; 
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4);
        transform-origin: center center;
        will-change: transform;
        user-select: none;
    }
    
    .grid-item { 
        border: 1px solid #334155; 
        position: relative; 
        box-sizing: border-box; 
        background: #1e293b; 
    }

    .grid-item.drag-hover .front-cell-content { 
        background: rgba(3, 105, 161, 0.4); 
        outline: 2px dashed #38bdf8; 
    }
    
    .front-cell-content { 
        position: absolute; 
        inset: 0; 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        justify-content: center; 
        font-size: 9px; 
        color: #475569; 
        z-index: 2; 
        background: #1e293b; 
    }

    .grid-item:hover .front-cell-content { 
        background: rgba(30, 41, 59, 0.85); 
    }
    
    .sub-grid-cell { 
        position: absolute; 
        inset: 0; 
        background: #090d16; 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        justify-content: center; 
        font-size: 8px; 
        color: #0284c7; 
        opacity: 0.7; 
        z-index: 1; 
    }

    .grid-item.selected-layer .front-cell-content { 
        background: rgba(30, 41, 59, 0.15) !important; 
        outline: 2px solid #0369a1; 
        color: #f8fafc; 
    }

    .placed-element { 
        position: absolute; 
        inset: 2px; 
        border-radius: 4px; 
        padding: 4px; 
        font-size: 10px; 
        font-weight: bold; 
        color: white; 
        display: flex; 
        flex-direction: column; 
        justify-content: space-between; 
        z-index: 3; 
    }
    
    .type-Voiture { background: #0284c7; border-left: 3px solid #0ea5e9; }
    .type-Local { background: #b45309; border-left: 3px solid #f59e0b; }
    .type-Equipement { background: #047857; border-left: 3px solid #10b981; }
    .type-Immobilisation { background: #6d28d9; border-left: 3px solid #8b5cf6; }
    
    .delete-item-trigger { 
        background: rgba(0,0,0,0.3); 
        border: none; 
        color: #f87171; 
        border-radius: 3px; 
        cursor: pointer; 
        padding: 2px 5px; 
        font-size: 10px; 
        z-index: 5; 
    }

    .delete-item-trigger:hover { 
        background: #ef4444; 
        color: white; 
    }

    .control-btn { 
        background: #334155; 
        border: 1px solid #475569; 
        color: white; 
        padding: 8px 12px; 
        border-radius: 6px; 
        text-decoration: none; 
        font-size: 13px; 
        text-align: center; 
        cursor: pointer; 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        gap: 8px; 
        transition: all 0.2s;
    }

    .control-btn:hover { 
        background: #475569; 
        color: #38bdf8;
    }

    .control-btn.active {
        background: #0369a1;
        color: white;
    }

    /* Style identique aux contrôles UML */
    .zoom-controls {
        position: absolute;
        bottom: 25px; 
        left: 50%;
        transform: translateX(-50%); 
        display: flex;
        flex-direction: row; 
        gap: 8px; 
        z-index: 10;
        background: rgba(30, 41, 59, 0.85); 
        padding: 6px; 
        width: max-content; 
        border-radius: 30px; 
        border: 1px solid rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
    }

    .zoom-controls button {
        background: #334155;
        border: 1px solid #475569;
        border-radius: 50%; 
        width: 36px; 
        height: 36px;
        font-size: 0.95rem;
        color: #f8fafc;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        user-select: none;
    }

    .zoom-controls button:hover {
        background: #475569;
        border-color: #64748b;
        transform: scale(1.08);
    }

    .zoom-controls button:active {
        transform: scale(0.92);
        background: #1e293b;
    }

    #toast-notification { 
        position: fixed; 
        bottom: 20px; 
        right: 20px; 
        display: none; 
        z-index: 1000; 
        background: #1e293b;
        border: 1px solid #334155;
        padding: 12px 24px;
        border-radius: 8px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3);
    }
</style>

<div id="toast-notification"></div>

<div class="main-wrapper">
    <div class="sidebar">
        <h3>Matériel & Catalogues</h3>
        <div class="search-select-box">
            <input type="text" id="catalogSearch" placeholder="Rechercher un composant...">
        </div>

        <div id="accordionGroupContainer">
            <?php if (empty($groupedComposants)): ?>
                <span id="emptyCatalogMsg" style="font-size:12px; color:var(--text-muted); font-style:italic; padding: 10px;">Aucun article disponible.</span>
            <?php else: ?>
                <span id="emptyCatalogMsg" style="font-size:12px; color:var(--text-muted); font-style:italic; padding: 10px; display:none;">Aucun article disponible.</span>
                <?php foreach ($groupedComposants as $typeKey => $items): ?>
                    <details class="catalog-group" data-group-type="<?= $typeKey ?>">
                        <summary><?= $groupLabels[$typeKey] ?? $typeKey ?></summary>
                        <ul class="catalogue-container">
                            <?php foreach ($items as $comp): ?>
                                <li class="catalog-item-wrapper">
                                    <div class="drag-item" draggable="true" data-id="<?= $comp['value'] ?>" data-label="<?= htmlspecialchars($comp['label']) ?>" data-type="<?= $comp['type'] ?>">
                                        <span><?= htmlspecialchars($comp['label']) ?></span>
                                        <span class="badge-stock">x<?= $comp['stock'] ?></span>
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
        <h2 style="font-size: 1.2rem; margin-top: 0; margin-bottom: 20px; font-weight: 600; color: #f8fafc;">
            Plan Actif: <?= $floor === 'rc' ? 'Rez-de-chaussée' : '1er Étage' ?> 
            <span style="font-size:13px; color:#94a3b8; font-weight:normal; margin-left:10px;">(<?= ($cols * 50) ?>px × <?= ($rows * 50) ?>px)</span>
        </h2>

        <div class="grid-stage" id="gridStage">
            <div class="grid-container" id="frontGrid" style="grid-template-columns: repeat(<?= $cols ?>, 1fr); grid-template-rows: repeat(<?= $rows ?>, 1fr); width: <?= $cols * 50 ?>px; height: <?= $rows * 50 ?>px;">
                <?php
                for ($r = 1; $r <= $rows; $r++) {
                    for ($c = 1; $c <= $cols; $c++) {
                        $cellKey = "{$r}_{$c}";
                        $computedX = ($c - 1) * 50;
                        $computedY = ($r - 1) * 50;
                        
                        echo "<div class='grid-item' data-row='$r' data-col='$c'>";
                        echo "<div class='sub-grid-cell'><sub>X:$computedX</sub><sub>Y:$computedY</sub></div>";
                        echo "<div class='front-cell-content'>";
                        
                        if (isset($elementsPositionnes[$cellKey])) {
                            $el = $elementsPositionnes[$cellKey];
                            echo "<div class='placed-element type-{$el['type']}'>";
                            echo "<span>" . htmlspecialchars($el['label']) . "</span>";
                            echo "<button class='delete-item-trigger' title='Supprimer'>🗑️</button>";
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
            <button id="zoom-in" title="Zoom In">➕</button>
            <button id="zoom-out" title="Zoom Out">➖</button>
            <button id="zoom-reset" title="Réinitialiser">🔄</button>
        </div>
    </div>

    <div class="control-sidebar">
        <h3>Configuration</h3>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <a href="?floor=rc&rows=<?= $rows ?>&cols=<?= $cols ?>" class="control-btn <?= $floor === 'rc' ? 'active' : '' ?>">Rez-de-chaussée</a>
            <a href="?floor=e1&rows=<?= $rows ?>&cols=<?= $cols ?>" class="control-btn <?= $floor === 'e1' ? 'active' : '' ?>">1er Étage</a>
        </div>
    </div>
</div>

<script src="script.js"></script>

<?php
require_once '../includes/footer.php';
?>