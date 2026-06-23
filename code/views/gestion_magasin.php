<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';
require_once '../config/db.php';

if (!isset($_SESSION['idProfil']) || !in_array((int)$_SESSION['idProfil'], [1, 2, 4])) {
    echo "<div class='alert alert-danger' style='margin:20px;'><strong>Accès refusé !</strong> Vous n'avez pas les permissions requises pour accéder à ce module.</div>";
    require_once '../includes/footer.php';
    exit();
}

$successMsg = $_SESSION['success_msg'] ?? '';
$errorMsg   = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

$isEditMode      = false;
$form_id         = '';
$form_typeEntite = '';
$form_idCible    = '';
$form_quantite   = '';

$current_page = basename($_SERVER['PHP_SELF']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete'])) {
    $idToDelete = intval($_POST['idMagasinToDelete'] ?? 0);

    try {
        $stmtRef = $pdo->prepare("
            SELECT m.referenceInterne,
                   m.quantiteDisponible,
                   m.typeEntite,
                   CASE
                       WHEN m.typeEntite = 'Equipement'     THEN e.libelle
                       WHEN m.typeEntite = 'Immobilisation' THEN i.libelle
                       ELSE 'Inconnu'
                   END AS designation
            FROM magasin m
            LEFT JOIN Equipements     e ON m.typeEntite = 'Equipement'     AND m.idCible = e.idEquip
            LEFT JOIN immobilisations i ON m.typeEntite = 'Immobilisation' AND m.idCible = i.idImmo
            WHERE m.idMagasin = ?
        ");
        $stmtRef->execute([$idToDelete]);
        $articleInfo = $stmtRef->fetch(PDO::FETCH_ASSOC);

        if ($articleInfo) {
            $pdo->beginTransaction();

            $pdo->prepare("DELETE FROM magasin WHERE idMagasin = ?")->execute([$idToDelete]);

            $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)")
                ->execute([
                    $_SESSION['idUtilisateur'] ?? 0,
                    "Suppression article magasin",
                    "DELETE",
                    "magasin",
                    "ID: $idToDelete | Réf: {$articleInfo['referenceInterne']} | Produit: {$articleInfo['designation']} ({$articleInfo['typeEntite']}) | Quantité retirée: {$articleInfo['quantiteDisponible']}"
                ]);

            $pdo->commit();
            $_SESSION['success_msg'] = "L'article « " . htmlspecialchars($articleInfo['designation']) . " » (Réf: " . htmlspecialchars($articleInfo['referenceInterne']) . ") a été supprimé avec succès.";
        } else {
            $_SESSION['error_msg'] = "Article introuvable.";
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $_SESSION['error_msg'] = "Erreur lors de la suppression : " . $e->getMessage();
    }

    header("Location: " . $current_page);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_magasin'])) {
    $actionType = $_POST['action_magasin'];
    $idMagasin  = isset($_POST['idMagasin']) ? intval($_POST['idMagasin']) : 0;
    $typeEntite = $_POST['typeEntite'] ?? '';
    $idCible    = intval($_POST['idCible'] ?? 0);
    $quantite   = intval($_POST['quantiteStock'] ?? 0);

    if ($actionType === 'update' && empty($idMagasin)) {
        $_SESSION['error_msg'] = "L'identifiant technique est requis pour la modification.";
    } elseif (!empty($typeEntite) && $idCible > 0 && $quantite > 0) {
        try {
            $pdo->beginTransaction();

            $table = ($typeEntite === 'Equipement') ? 'Equipements' : 'immobilisations';
            $colId = ($typeEntite === 'Equipement') ? 'idEquip' : 'idImmo';
            $stmtLib = $pdo->prepare("SELECT libelle FROM $table WHERE $colId = ?");
            $stmtLib->execute([$idCible]);
            $libelle = $stmtLib->fetchColumn() ?: "ID: $idCible";

            if ($actionType === 'create') {
                $sql = "INSERT INTO magasin (typeEntite, idCible, quantiteStock, quantiteDisponible, dateReception, referenceInterne)
                        VALUES (?, ?, ?, ?, ?, 'TEMP')
                        ON DUPLICATE KEY UPDATE
                            quantiteStock      = quantiteStock      + VALUES(quantiteStock),
                            quantiteDisponible = quantiteDisponible + VALUES(quantiteDisponible)";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$typeEntite, $idCible, $quantite, $quantite, date('Y-m-d')]);
                $lastId = $pdo->lastInsertId();

                if ($lastId > 0) {
                    $refAuto = 'STEG-' . str_pad($lastId, 5, '0', STR_PAD_LEFT);
                    $pdo->prepare("UPDATE magasin SET referenceInterne = ? WHERE idMagasin = ?")
                        ->execute([$refAuto, $lastId]);

                    $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)")
                        ->execute([
                            $_SESSION['idUtilisateur'] ?? 0,
                            "Création entrée stock magasin",
                            "INSERT",
                            "magasin",
                            "ID: $lastId | Réf: $refAuto | Produit: $libelle | Quantité: $quantite"
                        ]);

                    $_SESSION['success_msg'] = "Nouvel article « " . htmlspecialchars($libelle) . " » enregistré avec la Réf: " . htmlspecialchars($refAuto) . ".";
                } else {
                    $stmtExist = $pdo->prepare("SELECT idMagasin, referenceInterne FROM magasin WHERE typeEntite = ? AND idCible = ?");
                    $stmtExist->execute([$typeEntite, $idCible]);
                    $existRow = $stmtExist->fetch(PDO::FETCH_ASSOC);
                    $refExist = $existRow['referenceInterne'] ?? '?';
                    $idExist  = $existRow['idMagasin'] ?? 0;

                    $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)")
                        ->execute([
                            $_SESSION['idUtilisateur'] ?? 0,
                            "Mise à jour stock magasin",
                            "UPDATE",
                            "magasin",
                            "ID: $idExist | Réf: $refExist | Produit: $libelle | Ajout: +$quantite"
                        ]);

                    $_SESSION['success_msg'] = "Le stock pour l'article « " . htmlspecialchars($libelle) . " » (Réf: " . htmlspecialchars($refExist) . ") a été cumulé (+ $quantite).";
                }

            } elseif ($actionType === 'update') {
                $pdo->prepare("UPDATE magasin SET typeEntite = ?, idCible = ?, quantiteDisponible = ? WHERE idMagasin = ?")
                    ->execute([$typeEntite, $idCible, $quantite, $idMagasin]);

                $stmtRef = $pdo->prepare("SELECT referenceInterne FROM magasin WHERE idMagasin = ?");
                $stmtRef->execute([$idMagasin]);
                $refExist = $stmtRef->fetchColumn() ?: '?';

                $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)")
                    ->execute([
                        $_SESSION['idUtilisateur'] ?? 0,
                        "Modification article magasin",
                        "UPDATE",
                        "magasin",
                        "ID: $idMagasin | Réf: $refExist | Produit: $libelle | Qté dispo: $quantite"
                    ]);

                $_SESSION['success_msg'] = "L'article « " . htmlspecialchars($libelle) . " » (Réf: " . htmlspecialchars($refExist) . ") a été mis à jour avec succès.";
            }

            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $_SESSION['error_msg'] = "Erreur système : " . $e->getMessage();
        }
    } else {
        $_SESSION['error_msg'] = "Veuillez remplir tous les champs requis (*).";
    }

    header("Location: " . $current_page);
    exit();
}

if (isset($_GET['edit'])) {
    $stmtEdit = $pdo->prepare("SELECT * FROM magasin WHERE idMagasin = ?");
    $stmtEdit->execute([intval($_GET['edit'])]);
    $magToEdit = $stmtEdit->fetch(PDO::FETCH_ASSOC);

    if ($magToEdit) {
        $isEditMode      = true;
        $form_id         = $magToEdit['idMagasin'];
        $form_typeEntite = $magToEdit['typeEntite'];
        $form_idCible    = $magToEdit['idCible'];
        $form_quantite   = $magToEdit['quantiteDisponible'];
    }
}

$listeEquipements     = $pdo->query("SELECT idEquip, libelle, typeEquip FROM Equipements ORDER BY libelle ASC")->fetchAll(PDO::FETCH_ASSOC);
$listeImmobilisations = $pdo->query("SELECT idImmo, libelle, typeImmo FROM immobilisations ORDER BY libelle ASC")->fetchAll(PDO::FETCH_ASSOC);

$form_libelleEdit = '';
if ($isEditMode && $form_idCible) {
    if ($form_typeEntite === 'Equipement') {
        foreach ($listeEquipements as $eq) {
            if ($eq['idEquip'] == $form_idCible) { $form_libelleEdit = $eq['libelle']; break; }
        }
    } else {
        foreach ($listeImmobilisations as $im) {
            if ($im['idImmo'] == $form_idCible) { $form_libelleEdit = $im['libelle']; break; }
        }
    }
}

$distinctTypes = [];
try {
    $typesEquip = $pdo->query("SELECT DISTINCT typeEquip AS typeName FROM Equipements WHERE typeEquip IS NOT NULL AND typeEquip != ''")->fetchAll(PDO::FETCH_COLUMN);
    $typesImmo  = $pdo->query("SELECT DISTINCT typeImmo AS typeName FROM immobilisations WHERE typeImmo IS NOT NULL AND typeImmo != ''")->fetchAll(PDO::FETCH_COLUMN);
    $distinctTypes = array_unique(array_merge($typesEquip, $typesImmo));
    natcasesort($distinctTypes);
} catch (PDOException $e) {}

$stocksMagasin = [];
try {
    $stocksMagasin = $pdo->query("
        SELECT m.*,
               CASE
                    WHEN m.typeEntite = 'Equipement'     THEN e.libelle
                    WHEN m.typeEntite = 'Immobilisation' THEN i.libelle
                    ELSE 'N/A'
               END AS designation,
               CASE
                    WHEN m.typeEntite = 'Equipement'     THEN e.typeEquip
                    WHEN m.typeEntite = 'Immobilisation' THEN i.typeImmo
                    ELSE ''
               END AS itemTypeCategory
        FROM magasin m
        LEFT JOIN Equipements     e ON m.typeEntite = 'Equipement'     AND m.idCible = e.idEquip
        LEFT JOIN immobilisations i ON m.typeEntite = 'Immobilisation' AND m.idCible = i.idImmo
        ORDER BY m.idMagasin ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMsg = "Impossible de charger l'inventaire : " . $e->getMessage();
}
?>

<script>
    window.rawEquipements     = <?= json_encode($listeEquipements) ?>;
    window.rawImmobilisations = <?= json_encode($listeImmobilisations) ?>;

    function handleNatureChange() {
        const typeEntite    = document.getElementById('typeEntite').value;
        const subTypeSelect = document.getElementById('subTypeFilter');
        const divSubType    = document.getElementById('div-sub-type');
        const divRecherche  = document.getElementById('div-recherche');
        const searchInput   = document.getElementById('search-material');
        const idCibleInput  = document.getElementById('idCible');

        subTypeSelect.innerHTML = '<option value="">-- Sélectionner une sous-catégorie --</option>';
        searchInput.value  = '';
        idCibleInput.value = '0';
        divRecherche.style.display = 'none';

        if (typeEntite === '') {
            divSubType.style.display = 'none';
            return;
        }

        const dataSrc = (typeEntite === 'Equipement') ? window.rawEquipements : window.rawImmobilisations;
        const keyType = (typeEntite === 'Equipement') ? 'typeEquip' : 'typeImmo';

        const uniqueTypes = [...new Set(dataSrc.map(item => item[keyType]).filter(Boolean))].sort((a, b) => a.localeCompare(b));
        uniqueTypes.forEach(t => {
            const opt = document.createElement('option');
            opt.value       = t;
            opt.textContent = t;
            subTypeSelect.appendChild(opt);
        });

        divSubType.style.display = 'block';

        if (typeof toggleSelects === 'function') { toggleSelects(); }
    }

    function handleSubTypeChange() {
        const typeEntite      = document.getElementById('typeEntite').value;
        const selectedSubType = document.getElementById('subTypeFilter').value;
        const divRecherche    = document.getElementById('div-recherche');
        const datalist        = document.getElementById('liste-articles');
        const searchInput     = document.getElementById('search-material');
        const idCibleInput    = document.getElementById('idCible');

        searchInput.value  = '';
        idCibleInput.value = '0';
        datalist.innerHTML = '';

        if (selectedSubType === '') {
            divRecherche.style.display = 'none';
            return;
        }

        const dataSrc = (typeEntite === 'Equipement') ? window.rawEquipements : window.rawImmobilisations;
        const idKey   = (typeEntite === 'Equipement') ? 'idEquip' : 'idImmo';
        const typeKey = (typeEntite === 'Equipement') ? 'typeEquip' : 'typeImmo';

        dataSrc.filter(item => item[typeKey] === selectedSubType).forEach(item => {
            const option = document.createElement('option');
            option.value = item.libelle;
            option.setAttribute('data-id', item[idKey]);
            datalist.appendChild(option);
        });

        divRecherche.style.display = 'block';
    }

    function handleDatalistInput(val) {
        const datalist     = document.getElementById('liste-articles');
        const idCibleInput = document.getElementById('idCible');
        const options      = datalist.querySelectorAll('option');
        let found          = false;

        options.forEach(opt => {
            if (opt.value === val) {
                idCibleInput.value = opt.getAttribute('data-id');
                found = true;
            }
        });

        if (!found) {
            idCibleInput.value = '0';
        }
    }

    function filterTableByType(selectedType) {
        const rows = document.querySelectorAll('#magasin-table-body tr:not(.no-data-row)');
        let visibleCount = 0;

        rows.forEach(row => {
            const rowType = row.getAttribute('data-item-type');
            if (selectedType === '' || rowType === selectedType) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const noDataRow = document.getElementById('no-data-row');
        if (noDataRow) {
            noDataRow.style.display = (visibleCount === 0) ? '' : 'none';
        }
    }

    <?php if ($isEditMode && $form_typeEntite): ?>
    document.addEventListener('DOMContentLoaded', function () {
        const typeEntite    = <?= json_encode($form_typeEntite) ?>;
        const idCible       = <?= json_encode((string)$form_idCible) ?>;
        const libelleEdit   = <?= json_encode($form_libelleEdit) ?>;

        const typeEntiteEl  = document.getElementById('typeEntite');
        const subTypeSelect = document.getElementById('subTypeFilter');
        const divSubType    = document.getElementById('div-sub-type');
        const divRecherche  = document.getElementById('div-recherche');
        const searchInput   = document.getElementById('search-material');
        const idCibleInput  = document.getElementById('idCible');
        const datalist      = document.getElementById('liste-articles');

        typeEntiteEl.value = typeEntite;

        const dataSrc = (typeEntite === 'Equipement') ? window.rawEquipements : window.rawImmobilisations;
        const idKey   = (typeEntite === 'Equipement') ? 'idEquip' : 'idImmo';
        const typeKey = (typeEntite === 'Equipement') ? 'typeEquip' : 'typeImmo';

        const uniqueTypes = [...new Set(dataSrc.map(item => item[typeKey]).filter(Boolean))].sort((a, b) => a.localeCompare(b));
        subTypeSelect.innerHTML = '<option value="">-- Sélectionner une sous-catégorie --</option>';
        uniqueTypes.forEach(t => {
            const opt       = document.createElement('option');
            opt.value       = t;
            opt.textContent = t;
            subTypeSelect.appendChild(opt);
        });
        divSubType.style.display = 'block';

        const matchedItem = dataSrc.find(item => String(item[idKey]) === idCible);
        if (matchedItem) {
            const subType = matchedItem[typeKey];
            subTypeSelect.value = subType;

            dataSrc.filter(item => item[typeKey] === subType).forEach(item => {
                const option = document.createElement('option');
                option.value = item.libelle;
                option.setAttribute('data-id', item[idKey]);
                datalist.appendChild(option);
            });

            searchInput.value  = libelleEdit;
            idCibleInput.value = idCible;
            divRecherche.style.display = 'block';
        }
    });
    <?php endif; ?>
</script>

<div class="users-wrapper">
    <div class="users-header">
        <h1><i class="fa-solid fa-boxes-stacked"></i> Gestion &amp; Inventaire du Magasin (STEG)</h1>
    </div>
    <br>

    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger"><i class="fa-solid fa-exclamation-triangle"></i> <?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="users-grid">

        <div class="users-card card-form">
            <h2>
                <i class="fa-solid <?= $isEditMode ? 'fa-pen-to-square' : 'fa-plus-circle' ?>"></i>
                <?= $isEditMode ? "Modifier le lot ID : " . htmlspecialchars($form_id) : "Faire une nouvelle entrée stock" ?>
            </h2>
            <hr>
            <form action="<?= htmlspecialchars($current_page) ?>" method="POST" class="steg-form">
                <input type="hidden" name="action_magasin" value="<?= $isEditMode ? 'update' : 'create' ?>">

                <div class="form-group-row">
                    <div class="form-control">
                        <label for="idMagasin">ID Technique</label>
                        <?php if ($isEditMode): ?>
                            <input type="text" id="idMagasin" name="idMagasin" readonly style="background-color:#e9ecef;" value="<?= htmlspecialchars($form_id) ?>">
                        <?php else: ?>
                            <input type="text" placeholder="Automatique" readonly style="background-color:#e9ecef; color:#6c757d;">
                        <?php endif; ?>
                    </div>

                    <div class="form-control">
                        <label for="typeEntite">Nature du matériel *</label>
                        <select name="typeEntite" id="typeEntite" required onchange="handleNatureChange()">
                            <option value="">-- Sélectionner --</option>
                            <option value="Equipement" <?= ($form_typeEntite === 'Equipement') ? 'selected' : '' ?>>Équipement Technique</option>
                            <option value="Immobilisation" <?= ($form_typeEntite === 'Immobilisation') ? 'selected' : '' ?>>Immobilisation Générale</option>
                        </select>
                    </div>
                </div>

                <div class="form-control" id="div-sub-type" style="display: none;">
                    <label for="subTypeFilter" style="color: #0d6efd;"><i class="fa-solid fa-layer-group"></i> Catégorie / Type d'élément *</label>
                    <select id="subTypeFilter" onchange="handleSubTypeChange()">
                        <option value="">-- Sélectionner une sous-catégorie --</option>
                    </select>
                </div>

                <div class="form-control" id="div-recherche" style="display: none;">
                    <label for="search-material" style="color: #0d6efd;"><i class="fa-solid fa-magnifying-glass"></i> Rechercher / Sélectionner l'article *</label>
                    <input type="text" id="search-material" placeholder="Tapez les premières lettres..." list="liste-articles" oninput="handleDatalistInput(this.value)">
                    <datalist id="liste-articles"></datalist>
                </div>

                <input type="hidden" id="idCible" name="idCible" value="<?= htmlspecialchars($form_idCible ?: '0') ?>">

                <div class="form-control">
                    <label for="quantiteStock"><?= $isEditMode ? 'Quantité Disponible Actuelle *' : 'Quantité à ajouter au stock *' ?></label>
                    <input type="number" id="quantiteStock" name="quantiteStock" required min="1" placeholder="Ex: 10" value="<?= htmlspecialchars($form_quantite) ?>">
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <?= $isEditMode ? "Enregistrer les modifications" : "Valider l'entrée en stock" ?>
                </button>

                <?php if ($isEditMode): ?>
                    <a href="<?= htmlspecialchars($current_page) ?>" class="btn btn-block" style="text-align:center;margin-top:10px;display:block;text-decoration:none;background:#6c757d;color:white;padding:10px;border-radius:4px;">
                        <i class="fa-solid fa-xmark"></i> Annuler la modification
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="users-card card-table">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;flex-wrap:wrap;gap:10px;">
                <h2 style="margin:0;">
                    <i class="fa-solid fa-list-check"></i> Inventaire en Magasin
                    <span style="font-size:0.8rem;color:#64748b;font-weight:400;margin-left:8px;">
                        (<?= count($stocksMagasin) ?> référence<?= count($stocksMagasin) > 1 ? 's' : '' ?>)
                    </span>
                </h2>

                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="form-control" style="margin:0; min-width:200px;">
                        <select id="filterCategoryType" onchange="filterTableByType(this.value)" style="padding:6px 10px; border-radius:4px; border:1px solid #ccc; font-weight:600;">
                            <option value="">-- Filtrer par Catégorie/Type --</option>
                            <?php foreach ($distinctTypes as $tName): ?>
                                <option value="<?= htmlspecialchars($tName) ?>"><?= htmlspecialchars($tName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="button" onclick="window.print();" class="btn-print" style="background:#0d6efd;color:white;border:none;padding:6px 14px;border-radius:4px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;">
                        <i class="fa-solid fa-print"></i> Imprimer
                    </button>
                </div>
            </div>
            <hr>

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
                <h2 style="text-align:center;margin-top:20px;font-size:18pt;">Registre Officiel de l'Inventaire Magasin</h2>
            </div>

            <div class="table-scroll">
                <table class="steg-table">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Désignation</th>
                            <th>Type Entité</th>
                            <th>Qté Disponible</th>
                            <th class="btn-action">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="magasin-table-body">
                        <?php foreach ($stocksMagasin as $stock): ?>
                        <?php $slugRole = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $stock['typeEntite'])); ?>
                        <tr data-item-type="<?= htmlspecialchars($stock['itemTypeCategory']) ?>">
                            <td class="cell-user-id"><strong><?= htmlspecialchars($stock['referenceInterne']) ?></strong></td>
                            <td><strong><?= htmlspecialchars($stock['designation'] ?? 'N/A') ?></strong></td>
                            <td>
                                <span class="badge-role-type role-<?= $slugRole ?>">
                                    <?= htmlspecialchars($stock['typeEntite']) ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color: #22c55e;"><?= (int)$stock['quantiteDisponible'] ?></strong> unité(s)
                            </td>
                            <td class="btn-action" style="white-space:nowrap; display: flex; align-items: center;">
                                <a href="<?= htmlspecialchars($current_page) ?>?edit=<?= intval($stock['idMagasin']) ?>" title="Modifier" style="color:#ffc107;margin-right:15px;font-size:1.1rem; text-decoration: none;">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>

                                <form action="<?= htmlspecialchars($current_page) ?>" method="POST" onsubmit="return confirm('Supprimer définitivement l\'article « <?= htmlspecialchars(addslashes($stock['designation'])) ?> » du stock ?');" style="margin: 0; padding: 0;">
                                    <input type="hidden" name="action_delete" value="1">
                                    <input type="hidden" name="idMagasinToDelete" value="<?= intval($stock['idMagasin']) ?>">
                                    <button type="submit" style="background: none; border: none; color: #dc3545; font-size: 1.1rem; cursor: pointer; padding: 0;">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <tr id="no-data-row" class="no-data-row" style="display: <?= empty($stocksMagasin) ? '' : 'none' ?>;">
                            <td colspan="5" style="text-align:center; padding:20px; color:#6c757d;">Aucun article disponible en magasin pour cette sélection.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>