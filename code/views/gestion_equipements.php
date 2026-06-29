<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';
require_once '../config/db.php';

$successMsg = $_SESSION['success_msg'] ?? '';
$errorMsg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

$isEditMode = false;
$form_idEquip = '';
$form_typeEquip = '';
$form_libelle = '';

$current_page = basename($_SERVER['PHP_SELF']);

$categories = ["Câble de connexion", "Imprimante", "Périphérique informatique", "Projecteur", "Réfrigérateur", "Télévision", "Unité Centrale"];
natcasesort($categories);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['idUtilisateur'] ?? null;

    if (isset($_POST['action_delete'])) {
        $idToDelete = intval($_POST['idEquipToDelete'] ?? 0);
        
        try {
            $stmtCheck = $pdo->prepare("SELECT libelle FROM Equipements WHERE idEquip = ?");
            $stmtCheck->execute([$idToDelete]);
            $equip = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($equip) {
                $libelleEquip = $equip['libelle'];
                
                $pdo->beginTransaction();
                
                $stmtDel = $pdo->prepare("DELETE FROM Equipements WHERE idEquip = ?");
                $stmtDel->execute([$idToDelete]);

                $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
                $actionDescription = "Suppression de l'équipement " . sprintf('%04d', $idToDelete) . " ($libelleEquip)";
                $stmtLog->execute([$userId, $actionDescription, 'DELETE', 'Equipement', $idToDelete]);

                $pdo->commit();
                $_SESSION['success_msg'] = "L'équipement '" . htmlspecialchars($libelleEquip) . "' a été supprimé avec succès.";
            } else {
                $_SESSION['error_msg'] = "Équipement introuvable.";
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $_SESSION['error_msg'] = "Erreur lors de la suppression : " . $e->getMessage();
        }
        
        header("Location: " . $current_page);
        exit();
    }

    if (isset($_POST['action_ajout'])) {
        $actionType = $_POST['action_ajout'];
        $typeEquip = $_POST['typeEquip'] ?? '';
        $libelle = trim($_POST['libelle'] ?? '');

        if (!empty($typeEquip) && !empty($libelle)) {
            try {
                if ($actionType === 'create') {
                    $pdo->beginTransaction();
                    $sqlInsert = "INSERT INTO Equipements (typeEquip, libelle) VALUES (?, ?)";
                    $pdo->prepare($sqlInsert)->execute([$typeEquip, $libelle]);
                    $lastId = $pdo->lastInsertId();

                    $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
                    $actionDescription = "Création de l'équipement " . sprintf('%04d', $lastId) . " ($libelle)";
                    $stmtLog->execute([$userId, $actionDescription, 'CREATE', 'Equipement', $lastId]);
                    $pdo->commit();

                    $_SESSION['success_msg'] = "L'équipement '" . htmlspecialchars($libelle) . "' a été enregistré avec succès.";
                    
                } elseif ($actionType === 'update') {
                    $idEquip = intval($_POST['idEquip'] ?? 0);
                    
                    $pdo->beginTransaction();
                    $sqlUpdate = "UPDATE Equipements SET typeEquip = ?, libelle = ? WHERE idEquip = ?";
                    $pdo->prepare($sqlUpdate)->execute([$typeEquip, $libelle, $idEquip]);

                    $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
                    $actionDescription = "Modification de l'équipement " . sprintf('%04d', $idEquip) . " (Nouveau libelle: $libelle)";
                    $stmtLog->execute([$userId, $actionDescription, 'UPDATE', 'Equipement', $idEquip]);
                    $pdo->commit();

                    $_SESSION['success_msg'] = "L'équipement '" . sprintf('%04d', $idEquip) . "' a été mis à jour.";
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $_SESSION['error_msg'] = "Erreur système : " . $e->getMessage();
            }
        } else {
            $_SESSION['error_msg'] = "Veuillez remplir tous les champs obligatoires (*).";
        }
        
        header("Location: " . $current_page);
        exit();
    }
}

if (isset($_GET['edit'])) {
    $idToEdit = intval($_GET['edit']);
    $stmtEdit = $pdo->prepare("SELECT * FROM Equipements WHERE idEquip = ?");
    $stmtEdit->execute([$idToEdit]);
    $equipToEdit = $stmtEdit->fetch(PDO::FETCH_ASSOC);

    if ($equipToEdit) {
        $isEditMode = true;
        $form_idEquip = $equipToEdit['idEquip'];
        $form_typeEquip = $equipToEdit['typeEquip'];
        $form_libelle = $equipToEdit['libelle'];
    }
}

$nextId = 1;
if (!$isEditMode) {
    try {
        $stmtNext = $pdo->query("SHOW TABLE STATUS LIKE 'Equipements'");
        $status = $stmtNext->fetch(PDO::FETCH_ASSOC);
        if ($status && isset($status['Auto_increment'])) {
            $nextId = intval($status['Auto_increment']);
        } else {
            $stmtFallback = $pdo->query("SELECT idEquip FROM Equipements ORDER BY idEquip DESC LIMIT 1");
            $lastRow = $stmtFallback->fetch(PDO::FETCH_ASSOC);
            if ($lastRow) {
                $nextId = intval($lastRow['idEquip']) + 1;
            }
        }
    } catch (PDOException $e) {}
}

$biens = [];
try {
    $query = "SELECT idEquip, typeEquip, libelle FROM Equipements ORDER BY idEquip ASC";
    $biens = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMsg = "Impossible de charger le catalogue des biens : " . $e->getMessage();
}
?>

<div class="immo-wrapper">
    <div class="immo-header">
        <h1><i class="fa-solid fa-boxes-stacked"></i> Gestion des Equipements (STEG)</h1>
    </div>
    <br>

    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="immo-grid">
        <div class="immo-card card-form">
            <h2>
                <i class="fa-solid <?= $isEditMode ? 'fa-pen-to-square' : 'fa-plus' ?>"></i> 
                <?= $isEditMode ? "Modifier le bien " . sprintf('%04d', $form_idEquip) : "Enregistrer un nouveau bien" ?>
            </h2>
            <hr>
            <form action="<?= htmlspecialchars($current_page) ?>" method="POST" class="steg-form">
                <input type="hidden" name="action_ajout" value="<?= $isEditMode ? 'update' : 'create' ?>">
                <?php if ($isEditMode): ?>
                    <input type="hidden" name="idEquip" value="<?= intval($form_idEquip) ?>">
                <?php endif; ?>

                <div class="form-control">
                    <label for="idLocalDisplay">Identifiant Auto</label>
                    <input type="text" id="idLocalDisplay" value="<?= $isEditMode ? sprintf('%04d', $form_idEquip) : sprintf('%04d', $nextId) ?>" readonly style="background-color: #e9ecef; cursor: not-allowed; font-weight: bold;">
                </div>

                <div class="form-control">
                    <label for="typeEquip">Type d'équipement *</label>
                    <select id="typeEquip" name="typeEquip" required>
                        <option value="" disabled <?= !$isEditMode ? 'selected' : '' ?>>-- Choisir un type --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $form_typeEquip === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-control">
                    <label for="libelle">Libellé / Désignation *</label>
                    <input type="text" id="libelle" name="libelle" required placeholder="Ex: Télévision LED 55 Pouces" value="<?= htmlspecialchars($form_libelle) ?>">
                </div>

                <button type="submit" class="btn btn-success btn-block">
                    <i class="fa-solid <?= $isEditMode ? 'fa-floppy-disk' : 'fa-arrow-down-long' ?>"></i> 
                    <?= $isEditMode ? "Mettre à jour l'inventaire" : "Valider l'inventaire" ?>
                </button>

                <?php if ($isEditMode): ?>
                    <a href="<?= htmlspecialchars($current_page) ?>" class="btn btn-primary btn-block" style="margin-top: 10px; background-color: #64748b; text-align: center; display: block; text-decoration: none;">
                        <i class="fa-solid fa-xmark"></i> Annuler la modification
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="immo-card card-table">
            <h2><i class="fa-solid fa-list"></i> Registre des Données Brutes</h2>
            <hr>
            <div class="table-scroll">
                <table class="steg-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Libellé</th>
                            <th>Type d'équipement</th>
                            <th class="btn-action">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($biens)): ?>
                            <tr>
                                <td colspan="4" class="text-center no-data">Aucun bien équipement brut référencé pour le moment.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($biens as $bien): ?>
                                <tr>
                                    <td class="cell-code"><?= sprintf('%04d', intval($bien['idEquip'])) ?></td>
                                    <td class="cell-desc"><strong><?= htmlspecialchars($bien['libelle']) ?></strong></td>
                                    <td><strong><?= htmlspecialchars($bien['typeEquip']) ?></strong></td>
                                    <td class="btn-action" style="white-space: nowrap; display: flex; align-items: center;">
                                        <a href="<?= htmlspecialchars($current_page) ?>?edit=<?= urlencode($bien['idEquip']) ?>" class="btn-action edit" title="Modifier" style="color: var(--warning-color); margin-right: 15px; font-size: 1.1rem; text-decoration: none;">
                                            <i class="fa-solid fa-pen-to-square" style="color: #ffc107; font-size: 1.1rem;"></i>
                                        </a>
                                        
                                        <form action="<?= htmlspecialchars($current_page) ?>" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement l\'équipement <?= htmlspecialchars(addslashes($bien['libelle'])) ?> ?');" style="margin: 0; padding: 0;">
                                            <input type="hidden" name="action_delete" value="1">
                                            <input type="hidden" name="idEquipToDelete" value="<?= intval($bien['idEquip']) ?>">
                                            <button type="submit" style="background: none; border: none; color: var(--danger-color); font-size: 1.1rem; cursor: pointer; padding: 0;">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>