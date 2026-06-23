<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

// --- AJAX ENDPOINT FOR REMOVING PLACED ITEMS ---
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

// --- STANDARD PLACEMENT ENDPOINT ---
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
if (in_array($userProfil, [1, 2, 4])) { $allowedTypes[] = 'Immobilisation'; }
if (in_array($userProfil, [1, 2, 4])) { $allowedTypes[] = 'Equipement'; }

$composants = [];

if (in_array('Voiture', $allowedTypes)) {
    try {
        $queryVoitures = "SELECT matricule, marque, modele FROM voitures WHERE statut = 'Disponible'";
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
                         SUM(m.quantiteDisponible) as totalStock,
                         CASE 
                            WHEN m.typeEntite = 'Equipement' THEN eq.libelle
                            WHEN m.typeEntite = 'Immobilisation' THEN im.libelle
                         END as element_label
                         FROM magasin m
                         LEFT JOIN equipements eq ON m.typeEntite = 'Equipement' AND m.idCible = eq.idEquip
                         LEFT JOIN immobilisations im ON m.typeEntite = 'Immobilisation' AND m.idCible = im.idImmo
                         WHERE m.quantiteDisponible > 0 AND m.typeEntite IN ($inClause)
                         GROUP BY m.typeEntite, m.referenceInterne, element_label";
        
        $stmtM = $pdo->prepare($queryMagasin);
        $stmtM->execute(array_values($magasinTypes));
        $itemsM = $stmtM->fetchAll(PDO::FETCH_ASSOC);
        foreach ($itemsM as $item) {
            $composants[] = [
                'value' => $item['idMagasin'],
                'label' => $item['element_label'] . " (" . $item['referenceInterne'] . ")",
                'type' => $item['typeEntite'],
                'stock' => $item['totalStock']
            ];
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

        if ($label !== '') {
            $elementsPositionnes["{$r}_{$c}"] = ['label' => $label, 'type' => $type];
        }
    }
} catch (PDOException $e) {}

// Group components dynamically by type for details layout structuring
$groupedComposants = [];
foreach ($composants as $comp) {
    $groupedComposants[$comp['type']][] = $comp;
}

// Friendly mapping labels for French groups
$groupLabels = [
    'Voiture' => '🚗 Voitures',
    'Local' => '🏢 Locaux',
    'Equipement' => '🛠️ Équipements',
    'Immobilisation' => '📦 Immobilisations'
];
?>

<style>
    :root {
        --bg-main: #0f172a;
        --bg-card: #1e293b;
        --border-color: #334155;
        --text-muted: #94a3b8;
        --accent-blue: #3b82f6;
    }
    body { background-color: var(--bg-main); color: #f8fafc; font-family: 'Inter', system-ui, sans-serif; overflow-x: hidden; }
    
    .main-wrapper { 
        display: grid; 
        grid-template-columns: minmax(260px, 300px) 1fr minmax(260px, 300px); 
        gap: 20px; 
        padding: 20px; 
        max-width: 100%; 
        box-sizing: border-box; 
    }
    
    .sidebar, .control-sidebar { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; display: flex; flex-direction: column; gap: 20px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); align-self: start; min-width: 0; }
    .sidebar h3, .control-sidebar h3 { font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin: 0; display: flex; align-items: center; gap: 8px; }
    .search-select-box { background: #111827; border: 1px solid var(--border-color); border-radius: 8px; padding: 6px; display: flex; flex-direction: column; gap: 8px; }
    .search-select-box input { background: #1e293b; border: 1px solid var(--border-color); color: white; padding: 8px; border-radius: 6px; font-size: 13px; outline: none; width: 100%; box-sizing: border-box; }
    
    /* Styled Accordion Groups */
    .catalog-group { margin-bottom: 8px; background: #111827; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; }
    .catalog-group summary { padding: 10px 12px; font-size: 13px; font-weight: 600; color: #f8fafc; cursor: pointer; user-select: none; list-style: none; display: flex; justify-content: space-between; align-items: center; background: #1e293b; transition: background 0.2s; }
    .catalog-group summary::-webkit-details-marker { display: none; }
    .catalog-group summary:hover { background: #24334d; }
    .catalog-group summary::after { content: '\25BC'; font-size: 9px; color: var(--text-muted); transition: transform 0.2s; }
    .catalog-group[open] summary::after { transform: rotate(180deg); }
    
    .catalogue-container { max-height: 350px; overflow-y: auto; list-style: none; padding: 8px; margin: 0; display: flex; flex-direction: column; gap: 6px; }
    .drag-item { background: #1e293b; border: 1px solid var(--border-color); padding: 10px 12px; border-radius: 8px; cursor: grab; font-size: 13px; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s; min-width: 0; }
    .drag-item span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .drag-item:hover { border-color: var(--accent-blue); background: #2563eb1a; }
    .badge-stock { background: #334155; color: #e2e8f0; font-size: 11px; padding: 2px 6px; border-radius: 20px; font-weight: 600; flex-shrink: 0; }
    
    .uml-viewport { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); min-width: 0; }
    .grid-stage { background: #0f172a; border-radius: 8px; border: 2px dashed var(--border-color); overflow: auto; padding: 10px; max-width: 100%; }
    .grid-container { display: grid; background: #1e293b; border: 1px solid #475569; width: max-content; }
    
    .grid-item { border: 1px solid #334155; width: 50px; height: 50px; display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 9px; color: #475569; position: relative; box-sizing: border-box; transition: background 0.15s; }
    .grid-item.drag-hover { background: rgba(59, 130, 246, 0.2); border: 2px dashed var(--accent-blue); }
    .placed-element { position: absolute; inset: 2px; border-radius: 4px; padding: 4px; font-size: 10px; font-weight: bold; color: white; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
    .type-Voiture { background: #0284c7; border-left: 3px solid #0ea5e9; }
    .type-Local { background: #b45309; border-left: 3px solid #f59e0b; }
    .type-Equipement { background: #047857; border-left: 3px solid #10b981; }
    .type-Immobilisation { background: #6d28d9; border-left: 3px solid #8b5cf6; }
    .delete-item-trigger { background: rgba(0,0,0,0.3); border: none; color: #f87171; border-radius: 3px; cursor: pointer; padding: 2px 5px; align-self: flex-end; font-size: 10px; transition: background 0.2s; }
    .delete-item-trigger:hover { background: #ef4444; color: white; }
    .control-btn, .merge-action-btn { background: #334155; border: 1px solid var(--border-color); color: white; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; text-align: center; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: background 0.2s; width: 100%; box-sizing: border-box; }
    .control-btn:hover, .merge-action-btn:hover { background: #475569; }
    .uml-link { display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 8px; color: var(--text-muted); text-decoration: none; font-size: 13px; }
    .uml-link.active { background: #2563eb; color: white; font-weight: 500; }
    #toast-notification { position: fixed; bottom: 20px; right: 20px; display: none; z-index: 1000; transition: all 0.3s ease; }
    
    @media (max-width: 1024px) {
        .main-wrapper { grid-template-columns: 1fr; }
        .sidebar, .control-sidebar { width: 100%; }
    }
</style>

<div id="toast-notification"></div>

<div class="main-wrapper">
    <div class="sidebar">
        <h3><i class="fa-solid fa-boxes-stacked"></i> Matériel & Catalogues</h3>
        <div class="search-select-box">
            <input type="text" id="catalogSearch" placeholder="Rechercher un composant...">
        </div>

        <div id="accordionGroupContainer" style="margin-top: 10px;">
            <?php if (empty($groupedComposants)): ?>
                <span style="font-size:12px; color:var(--text-muted); font-style:italic; padding: 10px;">Aucun article disponible.</span>
            <?php else: ?>
                <?php foreach ($groupedComposants as $typeKey => $items): ?>
                    <details class="catalog-group" open data-group-type="<?= $typeKey ?>">
                        <summary><?= $groupLabels[$typeKey] ?? $typeKey ?></summary>
                        <ul class="catalogue-container">
                            <?php foreach ($items as $comp): ?>
                                <li class="catalog-item-wrapper">
                                    <div class="drag-item type-<?= $comp['type'] ?>" draggable="true" data-id="<?= $comp['value'] ?>" data-label="<?= htmlspecialchars($comp['label']) ?>" data-type="<?= $comp['type'] ?>">
                                        <span><i class="fa-solid fa-grip-vertical" style="opacity: 0.4; margin-right: 6px;"></i><?= htmlspecialchars($comp['label']) ?></span>
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
        <h2 style="font-size: 18px; margin-top: 0; margin-bottom: 20px; font-weight: 600;">
            Plan Actif: <?= $floor === 'rc' ? 'Rez-de-chaussée' : '1er Étage' ?> 
            <span style="font-size:13px; color:var(--text-muted); font-weight:normal; margin-left:10px;">(<?= ($cols * 50) ?>px × <?= ($rows * 50) ?>px)</span>
        </h2>

        <div class="grid-stage">
            <div class="grid-container" id="frontGrid" style="grid-template-columns: repeat(<?= $cols ?>, 50px); grid-template-rows: repeat(<?= $rows ?>, 50px);">
                <?php
                for ($r = 1; $r <= $rows; $r++) {
                    for ($c = 1; $c <= $cols; $c++) {
                        $cellKey = "{$r}_{$c}";
                        echo "<div class='grid-item' data-row='$r' data-col='$c'>";
                        
                        if (isset($elementsPositionnes[$cellKey])) {
                            $el = $elementsPositionnes[$cellKey];
                            echo "<div class='placed-element type-{$el['type']}'>";
                            echo "<span>" . htmlspecialchars($el['label']) . "</span>";
                            echo "<button type='button' class='delete-item-trigger'><i class='fa-solid fa-trash'></i></button>";
                            echo "</div>";
                        } else {
                            $computedX = ($c - 1) * 50;
                            $computedY = ($r - 1) * 50;
                            echo "<span>X:$computedX</span><span>Y:$computedY</span>";
                        }
                        echo "</div>";
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <div class="control-sidebar">
        <?php if ($canAdjustDimensions): ?>
            <h3><i class="fa-solid fa-arrows-to-dot"></i> Dimensions Grille</h3>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size:13px;">Lignes (<?= $rows ?>)</span>
                    <div style="display:flex; gap:4px;">
                        <a href="?rows=<?= $rows - 1 ?>&cols=<?= $cols ?>&floor=<?= $floor ?>" class="control-btn" style="padding:4px 10px; width:auto;">-</a>
                        <a href="?rows=<?= $rows + 1 ?>&cols=<?= $cols ?>&floor=<?= $floor ?>" class="control-btn" style="padding:4px 10px; width:auto;">+</a>
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size:13px;">Colonnes (<?= $cols ?>)</span>
                    <div style="display:flex; gap:4px;">
                        <a href="?rows=<?= $rows ?>&cols=<?= $cols - 1 ?>&floor=<?= $floor ?>" class="control-btn" style="padding:4px 10px; width:auto;">-</a>
                        <a href="?rows=<?= $rows ?>&cols=<?= $cols + 1 ?>&floor=<?= $floor ?>" class="control-btn" style="padding:4px 10px; width:auto;">+</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($canMergeCells): ?>
            <h3><i class="fa-solid fa-object-group"></i> Actions Zones</h3>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <button type="button" id="btnMergeCells" class="merge-action-btn"><i class="fa-solid fa-compress"></i> Fusionner Sélection</button>
                <button type="button" id="btnUnmergeMode" class="merge-action-btn" style="color:#f87171;"><i class="fa-solid fa-expand"></i> Dissocier Cellule</button>
            </div>
        <?php endif; ?>

        <h3><i class="fa-solid fa-layer-group"></i> Étages</h3>
        <nav style="display: flex; flex-direction: column; gap: 4px;">
            <a href="?rows=<?= $rows ?>&cols=<?= $cols ?>&floor=rc" class="uml-link <?= $floor === 'rc' ? 'active' : '' ?>">
                <i class="fa-solid fa-house"></i> Rez-de-chaussée (0)
            </a>
            <a href="?rows=<?= $rows ?>&cols=<?= $cols ?>&floor=e1" class="uml-link <?= $floor === 'e1' ? 'active' : '' ?>">
                <i class="fa-solid fa-building"></i> 1er étage (1)
            </a>
        </nav>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const currentFloor = (new URLSearchParams(window.location.search)).get('floor') || 'rc';
    let isMergeMode = false, isUnmergeMode = false, selectedCells = [];

    const catalogSearch = document.getElementById('catalogSearch');
    const toast = document.getElementById('toast-notification');

    function showToast(msg, isError = false) {
        toast.textContent = msg;
        toast.style.cssText = `position:fixed; bottom:20px; right:20px; padding:12px 20px; border-radius:8px; color:white; font-size:13px; font-weight:500; box-shadow:0 10px 15px -3px rgba(0,0,0,0.3); display:block; background:${isError ? '#ef4444' : '#10b981'}`;
        setTimeout(() => { toast.style.display = 'none'; }, 3000);
    }

    function filterCatalog() {
        const searchTerm = catalogSearch.value.toLowerCase().trim();
        const groups = document.querySelectorAll('.catalog-group');

        groups.forEach(group => {
            const items = group.querySelectorAll('.catalog-item-wrapper');
            let visibleInGroupCount = 0;

            items.forEach(item => {
                const matchesSearch = item.textContent.toLowerCase().includes(searchTerm);
                if (matchesSearch) {
                    item.style.display = '';
                    visibleInGroupCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            if (searchTerm !== '' && visibleInGroupCount > 0) {
                group.setAttribute('open', 'true');
            }
            group.style.display = (visibleInGroupCount > 0 || searchTerm === '') ? '' : 'none';
        });
    }
    if (catalogSearch) {
        catalogSearch.addEventListener('input', filterCatalog);
    }

    document.querySelectorAll('.drag-item').forEach(item => {
        item.addEventListener('dragstart', function (e) {
            e.dataTransfer.setData('text/plain', JSON.stringify({
                id: this.getAttribute('data-id'),
                label: this.getAttribute('data-label'),
                type: this.getAttribute('data-type')
            }));
        });
    });

    document.querySelectorAll('.grid-item').forEach(cell => {
        cell.addEventListener('dragover', function (e) {
            if (!this.querySelector('.placed-element') && !isMergeMode && !isUnmergeMode) {
                e.preventDefault();
                this.classList.add('drag-hover');
            }
        });

        cell.addEventListener('dragleave', function () { this.classList.remove('drag-hover'); });

        cell.addEventListener('drop', function (e) {
            e.preventDefault();
            this.classList.remove('drag-hover');
            const data = JSON.parse(e.dataTransfer.getData('text/plain'));

            const formData = new FormData();
            formData.append('action_position', '1');
            formData.append('col_idx', this.getAttribute('data-col'));
            formData.append('row_idx', this.getAttribute('data-row'));
            formData.append('current_floor', currentFloor);
            
            if (data.type === 'Local') formData.append('id_local', data.id);
            else if (data.type === 'Voiture') formData.append('matricule_voiture', data.id);
            else formData.append('id_magasin', data.id);

            fetch(window.location.href, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if(res.status === 'success') {
                    showToast(res.message);
                    this.innerHTML = `<div class="placed-element type-${data.type}"><span>[${data.type}] ${data.label}</span><button type="button" class="delete-item-trigger"><i class="fa-solid fa-trash"></i></button></div>`;
                    attachDeleteEvent(this.querySelector('.delete-item-trigger'));
                } else showToast(res.message, true);
            });
        });
    });

    function attachDeleteEvent(btn) {
        if(!btn) return;
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const cell = this.closest('.grid-item');
            if (!confirm("Supprimer cet élément ?")) return;

            const formData = new FormData();
            formData.append('action_delete_item', '1');
            formData.append('col_idx', cell.getAttribute('data-col'));
            formData.append('row_idx', cell.getAttribute('data-row'));
            formData.append('current_floor', currentFloor);

            fetch(window.location.href, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    showToast(res.message);
                    const cx = (cell.getAttribute('data-col') - 1) * 50;
                    const cy = (cell.getAttribute('data-row') - 1) * 50;
                    cell.innerHTML = `<span>X:${cx}</span><span>Y:${cy}</span>`;
                } else showToast(res.message, true);
            });
        });
    }
    document.querySelectorAll('.delete-item-trigger').forEach(btn => attachDeleteEvent(btn));

    const btnMerge = document.getElementById('btnMergeCells');
    const btnUnmerge = document.getElementById('btnUnmergeMode');

    if(btnMerge) {
        btnMerge.addEventListener('click', () => {
            isMergeMode = !isMergeMode; isUnmergeMode = false;
            resetSelection();
            btnMerge.style.background = isMergeMode ? '#2563eb' : '';
            if(btnUnmerge) btnUnmerge.style.background = '';
        });
    }

    if(btnUnmerge) {
        btnUnmerge.addEventListener('click', () => {
            isUnmergeMode = !isUnmergeMode; isMergeMode = false;
            resetSelection();
            btnUnmerge.style.background = isUnmergeMode ? '#ef4444' : '';
            if(btnMerge) btnMerge.style.background = '';
        });
    }

    document.querySelectorAll('.grid-item').forEach(cell => {
        cell.addEventListener('click', function() {
            if (isMergeMode) {
                if (this.querySelector('.placed-element')) return;
                if (selectedCells.includes(this)) {
                    this.style.background = '';
                    selectedCells = selectedCells.filter(c => c !== this);
                } else {
                    this.style.background = 'rgba(59, 130, 246, 0.4)';
                    selectedCells.push(this);
                }
                if (selectedCells.length >= 2) handleMergeExecution();
            } else if (isUnmergeMode && this.style.gridArea) {
                handleUnmergeExecution(this);
            }
        });
    });

    function resetSelection() {
        selectedCells.forEach(c => c.style.background = '');
        selectedCells = [];
    }

    function handleMergeExecution() {
        const title = prompt("Nom de la zone fusionnée :");
        if(!title) { resetSelection(); return; }
        let minR = 99, maxR = 0, minC = 99, maxC = 0;
        selectedCells.forEach(c => {
            const r = parseInt(c.getAttribute('data-row')), col = parseInt(c.getAttribute('data-col'));
            if(r < minR) minR = r; if(r > maxR) maxR = r;
            if(col < minC) minC = col; if(col > maxC) maxC = col;
        });
        const rootCell = selectedCells.find(c => parseInt(c.getAttribute('data-row')) === minR && parseInt(c.getAttribute('data-col')) === minC);
        if(rootCell) {
            rootCell.style.gridArea = `${minR} / ${minC} / ${maxR + 1} / ${maxC + 1}`;
            rootCell.style.background = '#334155';
            rootCell.innerHTML = `<div style="color:white; font-weight:bold; font-size:11px; text-align:center;">${title}</div>`;
            selectedCells.forEach(c => { if(c !== rootCell) c.style.display = 'none'; });
        }
        isMergeMode = false; btnMerge.style.background = ''; resetSelection();
    }

    function handleUnmergeExecution(cell) {
        const area = cell.style.gridArea.split('/');
        const rStart = parseInt(area[0]), cStart = parseInt(area[1]), rEnd = parseInt(area[2]), cEnd = parseInt(area[3]);
        document.querySelectorAll('.grid-item').forEach(c => {
            const r = parseInt(c.getAttribute('data-row')), col = parseInt(c.getAttribute('data-col'));
            if(r >= rStart && r < rEnd && col >= cStart && col < cEnd) {
                c.style.display = ''; c.style.gridArea = ''; c.style.background = '';
                c.innerHTML = `<span>X:${(col-1)*50}</span><span>Y:${(r-1)*50}</span>`;
            }
        });
        isUnmergeMode = false; btnUnmerge.style.background = '';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>