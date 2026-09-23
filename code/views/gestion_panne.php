<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../includes/header.php';
require_once '../includes/navbar.php';
require_once '../config/db.php';

echo '<link rel="stylesheet" href="print.css">';

if (!isset($_SESSION['idProfil']) || !in_array((int)$_SESSION['idProfil'], [1, 2, 3, 4])) {
    echo "<div class='alert alert-danger'><strong>Accès refusé !</strong> Vous n'avez pas les permissions requises pour accéder à ce module.</div>";
    require_once '../includes/footer.php';
    exit();
}

$successMsg = $_SESSION['success_msg'] ?? '';
$errorMsg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

$isEditMode = false;
$form_idPanne = '';
$form_description = '';
$form_dateDeclaration = date('Y-m-d');
$form_gravite = 'Moyenne';
$form_statut = 'actif';
$form_typeCible = '';
$form_idCible = '';

$current_page = basename($_SERVER['PHP_SELF']);
$userId = $_SESSION['idUtilisateur'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete'])) {
    $idToDelete = intval($_POST['idPanneToDelete'] ?? 0);

    try {
        $stmtCheck = $pdo->prepare("
            SELECT p.idPanne, p.gravite, p.description, p.matriculeVoiture, p.idEquip, e.libelle AS nomEquip
            FROM pannes p
            LEFT JOIN equipements e ON p.idEquip = e.idEquip
            WHERE p.idPanne = ?
        ");
        $stmtCheck->execute([$idToDelete]);
        $panne = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($panne) {
            $pdo->beginTransaction();

            $pdo->prepare("DELETE FROM pannes WHERE idPanne = ?")->execute([$idToDelete]);

            $cibleContext = !empty($panne['matriculeVoiture'])
                ? "Véhicule: " . $panne['matriculeVoiture']
                : "Équipement: " . $panne['nomEquip'];

            $pdo->prepare("
                INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([
                $userId,
                "Suppression du ticket de panne",
                "DELETE",
                "Panne",
                "Ticket: " . sprintf('%04d', $idToDelete) . " | " . $cibleContext . " | Gravité: {$panne['gravite']}"
            ]);

            $pdo->commit();
            $_SESSION['success_msg'] = "Le ticket #" . sprintf('%04d', $idToDelete) . " a été supprimé avec succès.";
        } else {
            $_SESSION['error_msg'] = "Ticket de panne introuvable.";
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['error_msg'] = "Erreur lors de la suppression : " . $e->getMessage();
    }

    header("Location: " . $current_page);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_panne'])) {
    $actionType = $_POST['action_panne'];
    $idPanne = isset($_POST['idPanne']) ? intval($_POST['idPanne']) : 0;
    $typeCible = $_POST['typeCible'] ?? '';
    $idCibleVal = trim($_POST['idCible'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $dateDeclaration = $_POST['dateDeclaration'] ?? date('Y-m-d');
    $gravite = $_POST['gravite'] ?? 'Moyenne';

    $idEquip = ($typeCible === 'Equipement' && !empty($idCibleVal)) ? intval($idCibleVal) : null;
    $matriculeVoiture = ($typeCible === 'Vehicule' && !empty($idCibleVal)) ? $idCibleVal : null;

    $redirectUrl = ($actionType === 'update') ? $current_page . "?edit=" . $idPanne : $current_page;

    if (!empty($description) && (!empty($idEquip) || !empty($matriculeVoiture))) {
        try {
            $pdo->beginTransaction();
            $cibleLog = "";

            if ($idEquip) {
                $stmtEquip = $pdo->prepare("SELECT libelle FROM equipements WHERE idEquip = ?");
                $stmtEquip->execute([$idEquip]);
                $cibleLog = "Équipement: " . ($stmtEquip->fetchColumn() ?: "ID: $idEquip");
            } else {
                $stmtVeh = $pdo->prepare("SELECT marque, modele FROM voitures WHERE matricule = ?");
                $stmtVeh->execute([$matriculeVoiture]);
                $veh = $stmtVeh->fetch(PDO::FETCH_ASSOC);
                $cibleLog = "Véhicule: " . $matriculeVoiture . " (" . ($veh ? $veh['marque'] . ' ' . $veh['modele'] : 'Inconnu') . ")";
            }

            if ($actionType === 'create') {
                $pdo->prepare("
                    INSERT INTO pannes (description, dateDeclaration, gravite, idEquip, matriculeVoiture, idUtilisateur)
                    VALUES (?, ?, ?, ?, ?, ?)
                ")->execute([$description, $dateDeclaration, $gravite, $idEquip, $matriculeVoiture, $userId]);

                $lastId = $pdo->lastInsertId();

                $pdo->prepare("
                    INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible)
                    VALUES (?, ?, ?, ?, ?)
                ")->execute([
                    $userId,
                    "Déclaration d'un ticket de panne",
                    "INSERT",
                    "Panne",
                    "Ticket: " . sprintf('%04d', $lastId) . " | " . $cibleLog . " | Gravité: $gravite"
                ]);

                $_SESSION['success_msg'] = "La panne a été déclarée avec succès (Ticket #" . sprintf('%04d', $lastId) . ").";
                $redirectUrl = $current_page;

            } elseif ($actionType === 'update') {
                $pdo->prepare("
                    UPDATE pannes
                    SET description = ?, dateDeclaration = ?, gravite = ?, idEquip = ?, matriculeVoiture = ?
                    WHERE idPanne = ?
                ")->execute([$description, $dateDeclaration, $gravite, $idEquip, $matriculeVoiture, $idPanne]);

                $pdo->prepare("
                    INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible)
                    VALUES (?, ?, ?, ?, ?)
                ")->execute([
                    $userId,
                    "Mise à jour du ticket de panne",
                    "UPDATE",
                    "Panne",
                    "Ticket: " . sprintf('%04d', $idPanne) . " | " . $cibleLog . " | Gravité: $gravite"
                ]);

                $_SESSION['success_msg'] = "Le ticket #" . sprintf('%04d', $idPanne) . " a été mis à jour.";
                $redirectUrl = $current_page;
            }

            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['error_msg'] = "Erreur système : " . $e->getMessage();
        }
    } else {
        $_SESSION['error_msg'] = "Veuillez remplir tous les champs requis et sélectionner une cible valide.";
    }

    header("Location: " . $redirectUrl);
    exit();
}

if (isset($_GET['edit'])) {
    $idToEdit = intval($_GET['edit']);

    $stmtEdit = $pdo->prepare("SELECT * FROM pannes WHERE idPanne = ?");
    $stmtEdit->execute([$idToEdit]);
    $panneToEdit = $stmtEdit->fetch(PDO::FETCH_ASSOC);

    if ($panneToEdit) {
        $isEditMode = true;
        $form_idPanne = $panneToEdit['idPanne'];
        $form_description = $panneToEdit['description'];
        $form_dateDeclaration = date('Y-m-d', strtotime($panneToEdit['dateDeclaration']));
        $form_gravite = $panneToEdit['gravite'];

        if (!empty($panneToEdit['idEquip'])) {
            $form_typeCible = 'Equipement';
            $form_idCible = $panneToEdit['idEquip'];
        } else {
            $form_typeCible = 'Vehicule';
            $form_idCible = $panneToEdit['matriculeVoiture'];
        }
    }
}

$equipements = $pdo->query("SELECT idEquip, libelle, typeEquip FROM equipements ORDER BY libelle ASC")->fetchAll(PDO::FETCH_ASSOC);
$voitures = $pdo->query("SELECT matricule, marque, modele FROM voitures ORDER BY marque ASC")->fetchAll(PDO::FETCH_ASSOC);

$listePannes = [];
try {
    $listePannes = $pdo->query("
        SELECT p.*, u.nom, e.libelle AS nomEquip, e.typeEquip, v.marque, v.modele
        FROM pannes p
        LEFT JOIN users u ON p.idUtilisateur = u.idUtilisateur
        LEFT JOIN equipements e ON p.idEquip = e.idEquip
        LEFT JOIN voitures v ON p.matriculeVoiture = v.matricule
        ORDER BY p.idPanne ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMsg = "Impossible de charger le registre des pannes : " . $e->getMessage();
}
?>

<script>
    window.rawEquipements = <?= json_encode($equipements) ?>;
    window.rawVoitures = <?= json_encode($voitures) ?>;

    function handleTypeCibleChange() {
        const typeCible = document.getElementById('typeCible').value;
        const divSubType = document.getElementById('div-sub-type');
        const divRecherche = document.getElementById('div-recherche');
        const subTypeSelect = document.getElementById('subTypeFilter');
        const searchInput = document.getElementById('search-material');
        const idCibleInput = document.getElementById('idCible');

        subTypeSelect.innerHTML = '<option value="">-- Sélectionner une sous-catégorie --</option>';
        searchInput.value = '';
        idCibleInput.value = '';
        divRecherche.style.display = 'none';

        if (typeCible === 'Equipement') {
            const uniqueTypes = [...new Set(window.rawEquipements.map(item => item.typeEquip).filter(t => t))].sort();

            uniqueTypes.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t;
                opt.textContent = t;
                subTypeSelect.appendChild(opt);
            });

            divSubType.style.display = 'block';
        } else if (typeCible === 'Vehicule') {
            divSubType.style.display = 'none';
            setupDatalist('Vehicule', null);
            divRecherche.style.display = 'block';
        } else {
            divSubType.style.display = 'none';
        }
    }

    function handleSubTypeChange() {
        const selectedSubType = document.getElementById('subTypeFilter').value;
        const divRecherche = document.getElementById('div-recherche');
        const searchInput = document.getElementById('search-material');
        const idCibleInput = document.getElementById('idCible');

        searchInput.value = '';
        idCibleInput.value = '';

        if (selectedSubType === '') {
            divRecherche.style.display = 'none';
            return;
        }

        setupDatalist('Equipement', selectedSubType);
        divRecherche.style.display = 'block';
    }

    function setupDatalist(type, subType) {
        const datalist = document.getElementById('liste-cibles');
        datalist.innerHTML = '';

        if (type === 'Equipement') {
            const filtered = window.rawEquipements.filter(item => item.typeEquip === subType);

            filtered.forEach(item => {
                const option = document.createElement('option');
                option.value = item.libelle;
                option.setAttribute('data-id', item.idEquip);
                datalist.appendChild(option);
            });
        } else if (type === 'Vehicule') {
            window.rawVoitures.forEach(item => {
                const option = document.createElement('option');
                option.value = item.matricule + ' - ' + item.marque + ' ' + item.modele;
                option.setAttribute('data-id', item.matricule);
                datalist.appendChild(option);
            });
        }
    }

    function handleDatalistInput(value) {
        const datalist = document.getElementById('liste-cibles');
        const idCibleInput = document.getElementById('idCible');
        const options = datalist.options;

        idCibleInput.value = '';

        for (let i = 0; i < options.length; i++) {
            if (options[i].value === value) {
                idCibleInput.value = options[i].getAttribute('data-id');
                break;
            }
        }
    }
</script>

<div class="pannes-wrapper">

    <div class="pannes-card pannes-screen-header" style="margin-bottom:15px;display:flex;justify-content:space-between;align-items:center;">
        <h2><i class="fa-solid fa-triangle-exclamation"></i> Centre de Maintenance &amp; Suivi des Pannes (STEG)</h2>
    </div>

    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger"><i class="fa-solid fa-exclamation-triangle"></i> <?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="pannes-grid">

        <div class="pannes-card card-form">
            <h2><i class="fa-solid fa-sliders"></i> <?= $isEditMode ? "Propriétés du Ticket" : "Nouveau Composant" ?></h2>
            <hr>

            <form action="<?= htmlspecialchars($current_page, ENT_QUOTES, 'UTF-8') . ($isEditMode ? '?edit=' . $form_idPanne : '') ?>" method="POST">
                <input type="hidden" name="action_panne" value="<?= $isEditMode ? 'update' : 'create' ?>">

                <?php if ($isEditMode): ?>
                    <input type="hidden" name="idPanne" value="<?= intval($form_idPanne) ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Numéro de ID / Identifiant</label>
                    <input type="text" readonly value="<?= $isEditMode ? sprintf('%04d', $form_idPanne) : 'Génération Auto' ?>">
                </div>

                <div class="form-group">
                    <label for="typeCible">Nature du Matériel *</label>
                    <select name="typeCible" id="typeCible" required onchange="handleTypeCibleChange()">
                        <option value="">-- Choisir Type --</option>
                        <option value="Equipement" <?= $form_typeCible === 'Equipement' ? 'selected' : '' ?>>Équipement Technique</option>
                        <option value="Vehicule" <?= $form_typeCible === 'Vehicule' ? 'selected' : '' ?>>Véhicule Logistique</option>
                    </select>
                </div>

                <div class="form-group" id="div-sub-type" style="display:none;">
                    <label for="subTypeFilter">Sous-Catégorie Matériel</label>
                    <select id="subTypeFilter" onchange="handleSubTypeChange()">
                        <option value="">-- Filtrer Type --</option>
                    </select>
                </div>

                <div class="form-group" id="div-recherche" style="display:none;">
                    <label for="search-material">Sélectionner Élément *</label>
                    <input type="text" id="search-material" placeholder="Rechercher libellé..." list="liste-cibles" oninput="handleDatalistInput(this.value)">
                    <datalist id="liste-cibles"></datalist>
                </div>

                <input type="hidden" id="idCible" name="idCible" value="<?= htmlspecialchars($form_idCible, ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-group">
                    <label for="dateDeclaration">Date Déclaration *</label>
                    <input type="date" id="dateDeclaration" name="dateDeclaration" required value="<?= htmlspecialchars($form_dateDeclaration, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="form-group">
                    <label for="gravite">Criticité Priorité *</label>
                    <select id="gravite" name="gravite" required>
                        <option value="Faible" <?= $form_gravite === 'Faible' ? 'selected' : '' ?>>Faible (Basse)</option>
                        <option value="Moyenne" <?= $form_gravite === 'Moyenne' ? 'selected' : '' ?>>Moyenne</option>
                        <option value="Majeure" <?= $form_gravite === 'Majeure' ? 'selected' : '' ?>>Majeure</option>
                        <option value="Critique" <?= $form_gravite === 'Critique' ? 'selected' : '' ?>>Critique (Haute)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Symptômes / Diagnostic *</label>
                    <textarea id="description" name="description" required rows="5" placeholder="Description technique de la panne..."><?= htmlspecialchars($form_description, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div style="margin-top:20px;display:flex;flex-direction:column;gap:8px;">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fa-solid fa-floppy-disk"></i> <?= $isEditMode ? "Mettre à jour Element" : "Appliquer / Enregistrer" ?>
                    </button>

                    <?php if ($isEditMode): ?>
                        <a href="<?= htmlspecialchars($current_page, ENT_QUOTES, 'UTF-8') ?>" class="control-btn btn-block">
                            <i class="fa-solid fa-xmark"></i> Annuler l'édition
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="pannes-card card-table">
            <div class="table-header-container" style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px dashed #cbd5e1;padding-bottom:10px;margin-bottom:15px;">
                <h2>
                    <i class="fa-solid fa-list-check"></i> Registre Général des Incidents de Réseau
                    <span style="font-size:12px;color:var(--text-muted);font-weight:normal;margin-left:6px;">
                        (<?= count($listePannes) ?> instance<?= count($listePannes) > 1 ? 's' : '' ?> active<?= count($listePannes) > 1 ? 's' : '' ?>)
                    </span>
                </h2>

                <button type="button" onclick="window.print();" class="btn-print" style="background-color:#0d6efd;color:white;border:none;padding:8px 16px;border-radius:4px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-print"></i> Imprimer
                </button>
            </div>

            <div class="print-header-steg">
                <table style="width:100%;border-collapse:collapse;border:none;">
                    <tr>
                        <td style="width:65%;border:none;text-align:left;vertical-align:top;">
                            <strong>Société Tunisienne de l'Électricité et du Gaz</strong><br>
                            Direction Informatique &amp; Logistique
                        </td>
                        <td style="width:35%;border:none;text-align:right;vertical-align:top;">
                            Date d'édition : <?= date('d/m/Y H:i') ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="border:none;text-align:center;padding-top:20px;font-size:18px;font-weight:bold;">
                            Registre Officiel des Incidents de Panne
                        </td>
                    </tr>
                </table>
            </div>

            <div class="table-scroll">
                <table class="steg-table">
                    <thead>
                        <tr>
                            <th style="width:70px;">Ticket</th>
                            <th style="width:180px;">Cible Concernée</th>
                            <th>Détails de l'incident / Contexte Log</th>
                            <th style="width:90px;">Gravité</th>
                            <th style="width:80px;" class="btn-action">Actions</th>
                        </tr>
                    </thead>

                    <tbody id="pannes-table-body">
                        <?php foreach ($listePannes as $panne):
                            $targetType = !empty($panne['matriculeVoiture']) ? 'Vehicule' : 'Equipement';
                            $gravClass = strtolower($panne['gravite'] ?? 'moyenne');
                            $gravClassCss = 'severity-' . $gravClass;
                        ?>
                            <tr>
                                <td style="text-align:center;">
                                    <span class="cell-code"><?= sprintf('%04d', $panne['idPanne']) ?></span>
                                </td>

                                <td>
                                    <?php if ($targetType === 'Vehicule'): ?>
                                        <span class="badge-status status-en-cours">
                                            <i class="fa-solid fa-truck-field"></i> <?= htmlspecialchars($panne['matriculeVoiture'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <div class="sub-id" style="margin-top:4px;">
                                            <?= htmlspecialchars(($panne['marque'] ?? '') . ' ' . ($panne['modele'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge-status status-active">
                                            <i class="fa-solid fa-gear"></i> <?= htmlspecialchars($panne['nomEquip'] ?? 'ID: ' . $panne['idEquip'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="sub-id" style="margin-bottom:4px;">
                                        Déclaré par : <strong><?= htmlspecialchars($panne['nom'] ?? 'Anonyme', ENT_QUOTES, 'UTF-8') ?></strong>
                                        le <?= !empty($panne['dateDeclaration']) ? date('d/m/Y', strtotime($panne['dateDeclaration'])) : '' ?>
                                    </div>
                                    <div class="desc-text">
                                        <?= htmlspecialchars($panne['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge-grav <?= htmlspecialchars($gravClassCss, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($panne['gravite'] ?? 'Moyenne', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>

                                <td class="btn-action" style="text-align:center;">
                                    <div style="display:flex;gap:10px;justify-content:center;align-items:center;">
                                        <a href="<?= htmlspecialchars($current_page, ENT_QUOTES, 'UTF-8') ?>?edit=<?= intval($panne['idPanne']) ?>" title="Modifier" style="color:#d97706;font-size:14px;text-decoration:none;">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>

                                        <form action="<?= htmlspecialchars($current_page, ENT_QUOTES, 'UTF-8') ?>" method="POST" onsubmit="return confirm('Supprimer définitivement le ticket #<?= sprintf('%04d', $panne['idPanne']) ?> ?');" style="margin:0;padding:0;display:inline-block;">
                                            <input type="hidden" name="action_delete" value="1">
                                            <input type="hidden" name="idPanneToDelete" value="<?= intval($panne['idPanne']) ?>">
                                            <button type="submit" title="Supprimer" style="background:none;border:none;color:#dc3545;font-size:14px;cursor:pointer;padding:0;vertical-align:middle;">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <tr id="no-data-row" style="display:<?= empty($listePannes) ? '' : 'none' ?>;">
                            <td colspan="5" class="no-data">Aucun incident de maintenance répertorié sur la grille.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($isEditMode && !empty($form_typeCible)): ?>
        document.getElementById('typeCible').value = <?= json_encode($form_typeCible) ?>;
        handleTypeCibleChange();

        <?php if ($form_typeCible === 'Equipement' && !empty($panneToEdit['idEquip'])):
            $stmtGetSub = $pdo->prepare("SELECT typeEquip, libelle FROM equipements WHERE idEquip = ?");
            $stmtGetSub->execute([$panneToEdit['idEquip']]);
            $subInfo = $stmtGetSub->fetch(PDO::FETCH_ASSOC);
            if ($subInfo):
        ?>
            document.getElementById('subTypeFilter').value = <?= json_encode($subInfo['typeEquip']) ?>;
            handleSubTypeChange();
            document.getElementById('search-material').value = <?= json_encode($subInfo['libelle']) ?>;
        <?php
            endif;
        elseif ($form_typeCible === 'Vehicule' && !empty($panneToEdit['matriculeVoiture'])):
            $stmtGetVeh = $pdo->prepare("SELECT marque, modele FROM voitures WHERE matricule = ?");
            $stmtGetVeh->execute([$panneToEdit['matriculeVoiture']]);
            $vehInfo = $stmtGetVeh->fetch(PDO::FETCH_ASSOC);
            if ($vehInfo):
        ?>
            document.getElementById('search-material').value = <?= json_encode($panneToEdit['matriculeVoiture'] . ' - ' . $vehInfo['marque'] . ' ' . $vehInfo['modele']) ?>;
        <?php
            endif;
        endif;
        ?>

        document.getElementById('idCible').value = <?= json_encode($form_idCible) ?>;
    <?php endif; ?>
});
</script>

<?php
require_once '../includes/footer.php';
?>