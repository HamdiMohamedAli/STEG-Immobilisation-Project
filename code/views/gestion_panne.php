<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';
require_once '../config/db.php';

if (!isset($_SESSION['idProfil']) || !in_array((int)$_SESSION['idProfil'], [1, 2, 3, 4])) {
    echo "<div class='alert alert-danger' style='margin:20px;'><strong>Accès refusé !</strong> Vous n'avez pas les permissions requises pour accéder à ce module.</div>";
    require_once '../includes/footer.php';
    exit();
}

$successMsg = $_SESSION['success_msg'] ?? '';
$errorMsg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

$isEditMode = false;

$form_idPanne        = '';
$form_description    = '';
$form_dateDeclaration = date('Y-m-d');
$form_gravite        = 'Moyenne';
$form_statut         = 'actif';
$form_typeCible      = '';
$form_idCible        = '';

$current_page = basename($_SERVER['PHP_SELF']);
$userId       = $_SESSION['idUtilisateur'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete'])) {
    $idToDelete = intval($_POST['idPanneToDelete'] ?? 0);

    try {
        $stmtCheck = $pdo->prepare("
            SELECT p.idPanne, p.gravite, p.description, p.matriculeVoiture, p.idEquip,
                   e.libelle AS nomEquip
            FROM pannes p
            LEFT JOIN Equipements e ON p.idEquip = e.idEquip
            WHERE p.idPanne = ?
        ");
        $stmtCheck->execute([$idToDelete]);
        $panne = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($panne) {
            $pdo->beginTransaction();

            $pdo->prepare("DELETE FROM pannes WHERE idPanne = ?")->execute([$idToDelete]);

            $cibleContext = !empty($panne['matriculeVoiture']) ? "Véhicule: " . $panne['matriculeVoiture'] : "Équipement: " . $panne['nomEquip'];

            $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)")
                ->execute([
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
    $actionType      = $_POST['action_panne'];
    $idPanne         = isset($_POST['idPanne']) ? intval($_POST['idPanne']) : 0;
    $typeCible       = $_POST['typeCible'] ?? '';
    $idCibleVal      = trim($_POST['idCible'] ?? '');
    $description     = trim($_POST['description'] ?? '');
    $dateDeclaration = $_POST['dateDeclaration'] ?? date('Y-m-d');
    $gravite         = $_POST['gravite']  ?? 'Moyenne';
    $statut          = $_POST['statut']   ?? 'actif';

    $idEquip          = ($typeCible === 'Equipement' && !empty($idCibleVal)) ? intval($idCibleVal) : null;
    $matriculeVoiture = ($typeCible === 'Vehicule' && !empty($idCibleVal)) ? $idCibleVal : null;

    if (!empty($description) && (!empty($idEquip) || !empty($matriculeVoiture))) {
        try {
            $pdo->beginTransaction();

            $cibleLog = "";
            if ($idEquip) {
                $stmtEquip = $pdo->prepare("SELECT libelle FROM Equipements WHERE idEquip = ?");
                $stmtEquip->execute([$idEquip]);
                $cibleLog = "Équipement: " . ($stmtEquip->fetchColumn() ?: "ID: $idEquip");
            } else {
                $cibleLog = "Véhicule: " . $matriculeVoiture;
            }

            if ($actionType === 'create') {
                $pdo->prepare("INSERT INTO pannes (description, dateDeclaration, gravite, statut, idEquip, matriculeVoiture, idUtilisateur) VALUES (?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$description, $dateDeclaration, $gravite, $statut, $idEquip, $matriculeVoiture, $userId]);
                $lastId = $pdo->lastInsertId();

                $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)")
                    ->execute([
                        $userId,
                        "Déclaration d'un ticket de panne",
                        "INSERT",
                        "Panne",
                        "Ticket: " . sprintf('%04d', $lastId) . " | " . $cibleLog . " | Gravité: $gravite | Statut: $statut"
                    ]);

                $_SESSION['success_msg'] = "La panne a été déclarée avec succès (Ticket #" . sprintf('%04d', $lastId) . ").";

            } elseif ($actionType === 'update') {
                $pdo->prepare("UPDATE pannes SET description=?, dateDeclaration=?, gravite=?, statut=?, idEquip=?, matriculeVoiture=? WHERE idPanne=?")
                    ->execute([$description, $dateDeclaration, $gravite, $statut, $idEquip, $matriculeVoiture, $idPanne]);

                $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)")
                    ->execute([
                        $userId,
                        "Mise à jour du ticket de panne",
                        "UPDATE",
                        "Panne",
                        "Ticket: " . sprintf('%04d', $idPanne) . " | " . $cibleLog . " | Gravité: $gravite | Statut: $statut"
                    ]);

                $_SESSION['success_msg'] = "Le ticket #" . sprintf('%04d', $idPanne) . " a été mis à jour.";
            }

            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['error_msg'] = "Erreur système : " . $e->getMessage();
        }
    } else {
        $_SESSION['error_msg'] = "Veuillez remplir tous les champs requis et sélectionner une cible valide.";
    }

    header("Location: " . $current_page);
    exit();
}

if (isset($_GET['edit'])) {
    $idToEdit = intval($_GET['edit']);
    $stmtEdit = $pdo->prepare("SELECT * FROM pannes WHERE idPanne = ?");
    $stmtEdit->execute([$idToEdit]);
    $panneToEdit = $stmtEdit->fetch(PDO::FETCH_ASSOC);

    if ($panneToEdit) {
        $isEditMode           = true;
        $form_idPanne         = $panneToEdit['idPanne'];
        $form_description     = $panneToEdit['description'];
        $form_dateDeclaration = date('Y-m-d', strtotime($panneToEdit['dateDeclaration']));
        $form_gravite         = $panneToEdit['gravite'];
        $form_statut          = $panneToEdit['statut'] ?? 'actif';
        
        if (!empty($panneToEdit['idEquip'])) {
            $form_typeCible = 'Equipement';
            $form_idCible   = $panneToEdit['idEquip'];
        } else {
            $form_typeCible = 'Vehicule';
            $form_idCible   = $panneToEdit['matriculeVoiture'];
        }
    }
}

$equipements = $pdo->query("SELECT idEquip, libelle, typeEquip FROM Equipements ORDER BY libelle ASC")->fetchAll(PDO::FETCH_ASSOC);
$voitures    = $pdo->query("SELECT matricule, marque, modele FROM voitures ORDER BY marque ASC")->fetchAll(PDO::FETCH_ASSOC);

$listePannes = [];
try {
    $listePannes = $pdo->query("
        SELECT p.*, u.nom, e.libelle AS nomEquip, e.typeEquip, v.marque, v.modele
        FROM pannes p
        LEFT JOIN users u ON p.idUtilisateur = u.idUtilisateur
        LEFT JOIN Equipements e ON p.idEquip = e.idEquip
        LEFT JOIN voitures v ON p.matriculeVoiture = v.matricule
        ORDER BY p.idPanne DESC
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
        const typeCible = document.getElementById('typeCible').value;
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

<div class="users-wrapper">
    <div class="users-header">
        <h1><i class="fa-solid fa-triangle-exclamation"></i> Centre de Maintenance &amp; Suivi des Pannes (STEG)</h1>
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
                <?= $isEditMode ? "Modifier le Ticket #" . sprintf('%04d', $form_idPanne) : "Signaler une nouvelle panne" ?>
            </h2>
            <hr>
            <form action="<?= htmlspecialchars($current_page) ?>" method="POST" class="steg-form">
                <input type="hidden" name="action_panne" value="<?= $isEditMode ? 'update' : 'create' ?>">
                <?php if ($isEditMode): ?>
                    <input type="hidden" name="idPanne" value="<?= $form_idPanne ?>">
                <?php endif; ?>

                <div class="form-group-row">
                    <div class="form-control">
                        <label>Numéro de Ticket</label>
                        <input type="text" readonly style="background-color:#e9ecef;" value="<?= $isEditMode ? sprintf('%04d', $form_idPanne) : 'Automatique' ?>">
                    </div>
                    
                    <div class="form-control">
                        <label for="typeCible">Nature de l'unité en panne *</label>
                        <select name="typeCible" id="typeCible" required onchange="handleTypeCibleChange()">
                            <option value="">-- Sélectionner --</option>
                            <option value="Equipement" <?= ($form_typeCible === 'Equipement') ? 'selected' : '' ?>>Équipement Technique</option>
                            <option value="Vehicule" <?= ($form_typeCible === 'Vehicule') ? 'selected' : '' ?>>Véhicule Logistique</option>
                        </select>
                    </div>
                </div>

                <div class="form-control" id="div-sub-type" style="display: none;">
                    <label for="subTypeFilter" style="color: #0d6efd;"><i class="fa-solid fa-layer-group"></i> Catégorie de l'équipement *</label>
                    <select id="subTypeFilter" onchange="handleSubTypeChange()">
                        <option value="">-- Sélectionner une sous-catégorie --</option>
                    </select>
                </div>

                <div class="form-control" id="div-recherche" style="display: none;">
                    <label for="search-material" style="color: #0d6efd;"><i class="fa-solid fa-magnifying-glass"></i> Spécifier l'élément ciblé *</label>
                    <input type="text" id="search-material" placeholder="Tapez les premières lettres..." list="liste-cibles" oninput="handleDatalistInput(this.value)">
                    <datalist id="liste-cibles"></datalist>
                </div>

                <input type="hidden" id="idCible" name="idCible" value="<?= htmlspecialchars($form_idCible) ?>">

                <div class="form-control">
                    <label for="dateDeclaration">Date de Constat *</label>
                    <input type="date" id="dateDeclaration" name="dateDeclaration" required value="<?= htmlspecialchars($form_dateDeclaration) ?>">
                </div>

                <div class="form-group-row">
                    <div class="form-control">
                        <label for="gravite">Niveau de Gravité *</label>
                        <select id="gravite" name="gravite" required>
                            <option value="Faible"   <?= $form_gravite === 'Faible'   ? 'selected' : '' ?>>Faible</option>
                            <option value="Moyenne"  <?= $form_gravite === 'Moyenne'  ? 'selected' : '' ?>>Moyenne</option>
                            <option value="Critique" <?= $form_gravite === 'Critique' ? 'selected' : '' ?>>Critique</option>
                        </select>
                    </div>

                    <div class="form-control">
                        <label for="statut">État de Résolution *</label>
                        <select id="statut" name="statut" required>
                            <option value="actif"    <?= $form_statut === 'actif'    ? 'selected' : '' ?>>Actif (En Attente)</option>
                            <option value="en_cours" <?= $form_statut === 'en_cours' ? 'selected' : '' ?>>En cours d'intervention</option>
                            <option value="resolu"   <?= $form_statut === 'resolu'   ? 'selected' : '' ?>>Résolu (Fermé)</option>
                        </select>
                    </div>
                </div>

                <div class="form-control">
                    <label for="description">Symptômes / Description du problème *</label>
                    <textarea id="description" name="description" required rows="4" placeholder="Décrivez l'anomalie constatée..."><?= htmlspecialchars($form_description) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <?= $isEditMode ? "Enregistrer les modifications" : "Émettre l'alerte de panne" ?>
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
                    <i class="fa-solid fa-list-check"></i> Registre Général des Incidents
                    <span style="font-size:0.8rem;color:#64748b;font-weight:400;margin-left:8px;">
                        (<?= count($listePannes) ?> ticket<?= count($listePannes) > 1 ? 's' : '' ?>)
                    </span>
                </h2>
                
                <div style="display:flex;align-items:center;gap:10px;">
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
                            Direction Informatique &amp; Maintenance Logistique
                        </td>
                        <td style="border:none !important;text-align:right;padding:0 !important;">
                            Date d'édition : <?= date('d/m/Y H:i') ?>
                        </td>
                    </tr>
                </table>
                <h2 style="text-align:center;margin-top:20px;font-size:18pt;">Registre Officiel du Suivi Technique des Incidents</h2>
            </div>

            <div class="table-scroll">
                <table class="steg-table">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Cible concernée</th>
                            <th>Détails de l'incident</th>
                            <th>Gravité</th>
                            <th>Statut</th>
                            <th class="btn-action">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="pannes-table-body">
                        <?php foreach ($listePannes as $panne): 
                            $targetType = !empty($panne['matriculeVoiture']) ? 'Vehicule' : 'Equipement';
                            $gravClass  = strtolower($panne['gravite'] ?? 'moyenne');
                            $statValue  = $panne['statut'] ?? 'actif';
                            
                            $statMap    = [
                                'actif'    => ['en-attente', 'En Attente'], 
                                'en_cours' => ['en-cours', 'En Cours'], 
                                'resolu'   => ['résolu', 'Résolu']
                            ];
                            $resolvedStatus = $statMap[$statValue] ?? ['en-attente', htmlspecialchars($statValue)];
                        ?>
                        <tr>
                            <td class="cell-user-id"><strong><?= sprintf('%04d', $panne['idPanne']) ?></strong></td>
                            <td>
                                <?php if ($targetType === 'Vehicule'): ?>
                                    <span class="badge-role-type role-immobilisation" style="background-color: #e0f2fe; color: #0369a1;">
                                        <i class="fa-solid fa-car"></i> <?= htmlspecialchars($panne['matriculeVoiture']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge-role-type role-equipement">
                                        <i class="fa-solid fa-screwdriver-wrench"></i> <?= htmlspecialchars($panne['nomEquip'] ?? 'ID: '.$panne['idEquip']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size:0.8rem; color:#64748b;">
                                    Déclaré par : <?= htmlspecialchars($panne['nom'] ?? 'Anonyme') ?> le <?= !empty($panne['dateDeclaration']) ? date('d/m/Y', strtotime($panne['dateDeclaration'])) : '' ?>
                                </div>
                                <div style="margin-top:4px;font-weight:bold;"><?= htmlspecialchars($panne['description'] ?? '') ?></div>
                            </td>
                            <td><span class="badge-grav severity-<?= $gravClass ?>"><?= htmlspecialchars($panne['gravite'] ?? 'Moyenne') ?></span></td>
                            <td><span class="badge-status status-<?= $resolvedStatus[0] ?>"><?= $resolvedStatus[1] ?></span></td>
                            <td class="btn-action" style="white-space:nowrap; display: flex; align-items: center;">
                                <a href="<?= htmlspecialchars($current_page) ?>?edit=<?= intval($panne['idPanne']) ?>" title="Modifier" style="color:#ffc107;margin-right:15px;font-size:1.1rem; text-decoration: none;">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                
                                <form action="<?= htmlspecialchars($current_page) ?>" method="POST" onsubmit="return confirm('Supprimer définitivement le ticket #<?= sprintf('%04d', $panne['idPanne']) ?> ?');" style="margin: 0; padding: 0;">
                                    <input type="hidden" name="action_delete" value="1">
                                    <input type="hidden" name="idPanneToDelete" value="<?= intval($panne['idPanne']) ?>">
                                    <button type="submit" style="background: none; border: none; color: #dc3545; font-size: 1.1rem; cursor: pointer; padding: 0;">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <tr id="no-data-row" class="no-data-row" style="display: <?= empty($listePannes) ? '' : 'none' ?>;">
                            <td colspan="6" style="text-align:center; padding:20px; color:#6c757d;">Aucun incident de maintenance répertorié pour cette sélection.</td>
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
        document.getElementById('typeCible').value = "<?= $form_typeCible ?>";
        handleTypeCibleChange();
        <?php if ($form_typeCible === 'Equipement' && !empty($panneToEdit['idEquip'])): 
            $stmtGetSub = $pdo->prepare("SELECT typeEquip, libelle FROM Equipements WHERE idEquip = ?");
            $stmtGetSub->execute([$panneToEdit['idEquip']]);
            $subInfo = $stmtGetSub->fetch(PDO::FETCH_ASSOC);
            if ($subInfo): ?>
                document.getElementById('subTypeFilter').value = "<?= htmlspecialchars($subInfo['typeEquip']) ?>";
                handleSubTypeChange();
                document.getElementById('search-material').value = "<?= htmlspecialchars($subInfo['libelle']) ?>";
            <?php endif; ?>
        <?php elseif ($form_typeCible === 'Vehicule' && !empty($panneToEdit['matriculeVoiture'])): 
            $stmtGetVeh = $pdo->prepare("SELECT marque, modele FROM voitures WHERE matricule = ?");
            $stmtGetVeh->execute([$panneToEdit['matriculeVoiture']]);
            $vehInfo = $stmtGetVeh->fetch(PDO::FETCH_ASSOC);
            if ($vehInfo): ?>
                document.getElementById('search-material').value = "<?= htmlspecialchars($panneToEdit['matriculeVoiture'] . ' - ' . $vehInfo['marque'] . ' ' . $vehInfo['modele']) ?>";
            <?php endif; ?>
        <?php endif; ?>
        document.getElementById('idCible').value = "<?= htmlspecialchars($form_idCible) ?>";
    <?php endif; ?>
});
</script>

<?php require_once '../includes/footer.php'; ?>