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
$form_idImmo = '';
$form_typeImmo = '';
$form_libelle = '';

$current_page = basename($_SERVER['PHP_SELF']);

$categories = ["Armoire", "Bureau", "Chaise", "Fauteuil", "Porte", "Réfrigérateur", "Table", "Tableau"];
natcasesort($categories);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['idUtilisateur'] ?? null;

    if (isset($_POST['action_delete'])) {
        $idToDelete = intval($_POST['idImmoToDelete'] ?? 0);
        
        try {
            $stmtCheck = $pdo->prepare("SELECT libelle FROM immobilisations WHERE idImmo = ?");
            $stmtCheck->execute([$idToDelete]);
            $immo = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($immo) {
                $libelleImmo = $immo['libelle'];
                
                $pdo->beginTransaction();
                
                $stmtDel = $pdo->prepare("DELETE FROM immobilisations WHERE idImmo = ?");
                $stmtDel->execute([$idToDelete]);

                $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
                $actionDescription = "Suppression de l'immobilisation " . sprintf('%04d', $idToDelete) . " ($libelleImmo)";
                $stmtLog->execute([$userId, $actionDescription, 'DELETE', 'Immobilisation', $idToDelete]);

                $pdo->commit();
                $_SESSION['success_msg'] = "L'immobilisation '" . htmlspecialchars($libelleImmo) . "' a été supprimée avec succès.";
            } else {
                $_SESSION['error_msg'] = "Immobilisation introuvable.";
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
        $typeImmo = $_POST['typeImmo'] ?? '';
        $libelle = trim($_POST['libelle'] ?? '');

        if (!empty($typeImmo) && !empty($libelle)) {
            try {
                if ($actionType === 'create') {
                    $pdo->beginTransaction();
                    $sqlInsert = "INSERT INTO immobilisations (typeImmo, libelle) VALUES (?, ?)";
                    $pdo->prepare($sqlInsert)->execute([$typeImmo, $libelle]);
                    $lastId = $pdo->lastInsertId();

                    $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
                    $actionDescription = "Création de l'immobilisation " . sprintf('%04d', $lastId) . " ($libelle)";
                    $stmtLog->execute([$userId, $actionDescription, 'CREATE', 'Immobilisation', $lastId]);
                    $pdo->commit();

                    $_SESSION['success_msg'] = "L'immobilisation '" . htmlspecialchars($libelle) . "' a été enregistrée avec succès.";
                    
                } elseif ($actionType === 'update') {
                    $idImmo = intval($_POST['idImmo'] ?? 0);
                    
                    $pdo->beginTransaction();
                    $sqlUpdate = "UPDATE immobilisations SET typeImmo = ?, libelle = ? WHERE idImmo = ?";
                    $pdo->prepare($sqlUpdate)->execute([$typeImmo, $libelle, $idImmo]);

                    $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
                    $actionDescription = "Modification de l'immobilisation " . sprintf('%04d', $idImmo) . " (Nouveau libelle: $libelle)";
                    $stmtLog->execute([$userId, $actionDescription, 'UPDATE', 'Immobilisation', $idImmo]);
                    $pdo->commit();

                    $_SESSION['success_msg'] = "L'immobilisation '" . sprintf('%04d', $idImmo) . "' a été mise à jour.";
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
    $stmtEdit = $pdo->prepare("SELECT * FROM immobilisations WHERE idImmo = ?");
    $stmtEdit->execute([$idToEdit]);
    $immoToEdit = $stmtEdit->fetch(PDO::FETCH_ASSOC);

    if ($immoToEdit) {
        $isEditMode = true;
        $form_idImmo = $immoToEdit['idImmo'];
        $form_typeImmo = $immoToEdit['typeImmo'];
        $form_libelle = $immoToEdit['libelle'];
    }
}

$nextId = 1;
if (!$isEditMode) {
    try {
        $stmtNext = $pdo->query("SHOW TABLE STATUS LIKE 'immobilisations'");
        $status = $stmtNext->fetch(PDO::FETCH_ASSOC);
        if ($status && isset($status['Auto_increment'])) {
            $nextId = intval($status['Auto_increment']);
        } else {
            $stmtFallback = $pdo->query("SELECT idImmo FROM immobilisations ORDER BY idImmo DESC LIMIT 1");
            $lastRow = $stmtFallback->fetch(PDO::FETCH_ASSOC);
            if ($lastRow) {
                $nextId = intval($lastRow['idImmo']) + 1;
            }
        }
    } catch (PDOException $e) {}
}

$biens = [];
try {
    $query = "SELECT idImmo, typeImmo, libelle FROM immobilisations ORDER BY idImmo DESC";
    $biens = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMsg = "Impossible de charger le catalogue des biens : " . $e->getMessage();
}
?>

<div class="immo-wrapper">
    <div>
        <h1><i class="fa-solid fa-boxes-stacked"></i> Gestion des Immobilisations (STEG)</h1>
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
                <?= $isEditMode ? "Modifier le bien " . sprintf('%04d', $form_idImmo) : "Enregistrer un nouveau bien" ?>
            </h2>
            <hr>
            <form action="<?= htmlspecialchars($current_page) ?>" method="POST" class="steg-form">
                <input type="hidden" name="action_ajout" value="<?= $isEditMode ? 'update' : 'create' ?>">
                <?php if ($isEditMode): ?>
                    <input type="hidden" name="idImmo" value="<?= intval($form_idImmo) ?>">
                <?php endif; ?>

                <div class="form-control">
                    <label for="idLocalDisplay">Identifiant Auto</label>
                    <input type="text" id="idLocalDisplay" value="<?= $isEditMode ? sprintf('%04d', $form_idImmo) : sprintf('%04d', $nextId) ?>" readonly style="background-color: #e9ecef; cursor: not-allowed; font-weight: bold;">
                </div>

                <div class="form-control">
                    <label for="typeImmo">Type d'immobilisation *</label>
                    <select id="typeImmo" name="typeImmo" required>
                        <option value="" disabled <?= !$isEditMode ? 'selected' : '' ?>>-- Choisir un type --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $form_typeImmo === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-control">
                    <label for="libelle">Libellé / Désignation *</label>
                    <input type="text" id="libelle" name="libelle" required placeholder="Ex: Porte d'entrée blindée bloc B" value="<?= htmlspecialchars($form_libelle) ?>">
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
                            <th>Type d'immobilisation</th>
                            <th class="btn-action">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($biens)): ?>
                            <tr>
                                <td colspan="4" class="text-center no-data">Aucun bien immobilisé brut référencé pour le moment.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($biens as $bien): ?>
                                <tr>
                                    <td class="cell-code"><?= sprintf('%04d', intval($bien['idImmo'])) ?></td>
                                    <td class="cell-desc"><strong><?= htmlspecialchars($bien['libelle']) ?></strong></td>
                                    <td><strong><?= htmlspecialchars($bien['typeImmo']) ?></strong></td>
                                    <td class="btn-action" style="white-space: nowrap; display: flex; align-items: center;">
                                        <a href="<?= htmlspecialchars($current_page) ?>?edit=<?= urlencode($bien['idImmo']) ?>" class="btn-action edit" title="Modifier" style="color: var(--warning-color); margin-right: 15px; font-size: 1.1rem; text-decoration: none;">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        
                                        <form action="<?= htmlspecialchars($current_page) ?>" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement l\'immobilisation <?= htmlspecialchars(addslashes($bien['libelle'])) ?> ?');" style="margin: 0; padding: 0;">
                                            <input type="hidden" name="action_delete" value="1">
                                            <input type="hidden" name="idImmoToDelete" value="<?= intval($bien['idImmo']) ?>">
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