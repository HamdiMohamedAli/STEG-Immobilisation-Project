<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';
require_once '../config/db.php';

$successMsg = '';
$errorMsg = '';

$allowedTypes = ['Atelier', 'Bureau', 'DataCenter', 'Issue de secours', 'Local', 'Salle', 'Magasin', 'Sanitaire'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_local'])) {
    $action = $_POST['action_local'];
    $userId = $_SESSION['idUtilisateur'] ?? null;

    if ($action === 'create') {
        $typeLocal = trim($_POST['typeLocal'] ?? '');
        $labelLocal = trim($_POST['labelLocal'] ?? '');

        if (!empty($typeLocal) && in_array($typeLocal, $allowedTypes, true)) {
            try {
                $pdo->beginTransaction();

                $sqlInsert = "INSERT INTO locaux (typeLocal, labelLocal) VALUES (?, ?)";
                $stmt = $pdo->prepare($sqlInsert);
                $stmt->execute([$typeLocal, $labelLocal]);
                
                $lastId = $pdo->lastInsertId();

                $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
                $actionDescription = "Création du local " . sprintf('%04d', $lastId) . " (typeLocal: $typeLocal, libelle: $labelLocal)";
                $stmtLog->execute([$userId, $actionDescription, 'CREATE', 'Local', $lastId]);

                $pdo->commit();
                $successMsg = "Le local a été enregistré avec succès sous l'identifiant " . sprintf('%04d', $lastId) . ".";
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $errorMsg = "Erreur lors de la création du local : " . $e->getMessage();
            }
        } else {
            $errorMsg = "Veuillez sélectionner un type de local valide.";
        }
    }

    elseif ($action === 'update') {
        $idLocal = intval($_POST['idLocal'] ?? 0);
        $typeLocal = trim($_POST['typeLocal'] ?? '');
        $labelLocal = trim($_POST['labelLocal'] ?? '');

        if ($idLocal > 0 && !empty($typeLocal) && in_array($typeLocal, $allowedTypes, true)) {
            try {
                $pdo->beginTransaction();

                $sqlUpdate = "UPDATE locaux SET typeLocal = ?, labelLocal = ? WHERE idLocal = ?";
                $stmt = $pdo->prepare($sqlUpdate);
                $stmt->execute([$typeLocal, $labelLocal, $idLocal]);

                $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
                $actionDescription = "Modification du local " . sprintf('%04d', $idLocal) . " (Type: $typeLocal, Libellé: $labelLocal)";
                $stmtLog->execute([$userId, $actionDescription, 'UPDATE', 'Local', $idLocal]);

                $pdo->commit();
                $successMsg = "Le local n°" . sprintf('%04d', $idLocal) . " a été modifié avec succès.";
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $errorMsg = "Erreur lors de la modification du local : " . $e->getMessage();
            }
        } else {
            $errorMsg = "Données invalides. Impossible de mettre à jour le local.";
        }
    }

    elseif ($action === 'delete') {
        $idLocal = intval($_POST['idLocal'] ?? 0);

        if ($idLocal > 0) {
            try {
                $pdo->beginTransaction();

                $stmtSelect = $pdo->prepare("SELECT typeLocal, labelLocal FROM locaux WHERE idLocal = ?");
                $stmtSelect->execute([$idLocal]);
                $localData = $stmtSelect->fetch(PDO::FETCH_ASSOC);

                if ($localData) {
                    $sqlDelete = "DELETE FROM locaux WHERE idLocal = ?";
                    $stmt = $pdo->prepare($sqlDelete);
                    $stmt->execute([$idLocal]);

                    $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
                    $actionDescription = "Suppression du local " . sprintf('%04d', $idLocal) . " (Type: {$localData['typeLocal']}, Libellé: {$localData['labelLocal']})";
                    $stmtLog->execute([$userId, $actionDescription, 'DELETE', 'Local', $idLocal]);

                    $pdo->commit();
                    $successMsg = "Le local n°" . sprintf('%04d', $idLocal) . " a été définitivement supprimé.";
                } else {
                    $pdo->rollBack();
                    $errorMsg = "Le local demandé n'existe pas ou a déjà été supprimé.";
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $errorMsg = "Erreur lors de la suppression du local : " . $e->getMessage();
            }
        } else {
            $errorMsg = "Identifiant de local incorrect.";
        }
    }
}

$nextId = 1;
try {
    $stmtNext = $pdo->query("SELECT idLocal FROM locaux ORDER BY idLocal DESC LIMIT 1;");
    $lastRow = $stmtNext->fetch(PDO::FETCH_ASSOC);
    if ($lastRow) {
        $nextId = intval($lastRow['idLocal']) + 1;
    }
} catch (PDOException $e) {}

$locaux = [];
try {
    $query = "SELECT l.* FROM locaux l ORDER BY l.idLocal ASC";
    $locaux = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMsg = "Échec du chargement du cadastre des locaux : " . $e->getMessage();
}
?>

<div class="locaux-wrapper">
    <div class="locaux-header">
        <h1><i class="fa-solid fa-building-user"></i> Gestion des Locaux</h1>
    </div>
    <br>

    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success"><i class="fa-solid fa-check-double"></i> <?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="locaux-grid">
        <div class="locaux-card card-form">
            <h2 id="form-title"><i class="fa-solid fa-square-plus"></i> Déclarer un nouveau local</h2>
            <hr>
            <form action="gestion_locaux.php" method="POST" class="steg-form" id="localForm">
                <input type="hidden" name="action_local" id="formAction" value="create">
                <input type="hidden" name="idLocal" id="formIdLocal" value="">

                <div class="form-control">
                    <label for="idLocalDisplay" id="labelIdDisplay">Identifiant (Automatique)</label>
                    <input type="text" id="idLocalDisplay" value="<?= sprintf('%04d', $nextId) ?>" readonly style="background-color: #e9ecef; cursor: not-allowed; font-weight: bold;">
                </div>

                <div class="form-control">
                    <label for="typeLocal">Type Local *</label>
                    <select id="typeLocal" name="typeLocal" required>
                        <option value="">-- Sélectionner un type --</option>
                        <?php foreach ($allowedTypes as $type): ?>
                            <option value="<?= $type ?>"><?= $type ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-control">
                    <label for="labelLocal">Libellé Local</label>
                    <input type="text" id="labelLocal" name="labelLocal" placeholder="Ex: Local Technique Principal">
                </div>

                <button type="submit" id="btnSubmitForm" class="btn btn-primary btn-block">
                    <i class="fa-solid fa-floppy-disk"></i> Enregistrer le local
                </button>
                
                <button type="button" id="btnCancelEdit" class="btn btn-secondary btn-block" style="display:none; margin-top: 8px;" onclick="resetFormToCreate(<?= $nextId ?>)">
                    <i class="fa-solid fa-xmark"></i> Annuler la modification
                </button>
            </form>
        </div>

        <div class="locaux-card card-table">
            <h2><i class="fa-solid fa-map"></i> Cadastre Actif des Locaux</h2>
            <hr>
            <div class="table-scroll">
                <table class="steg-table">
                    <thead>
                        <tr>
                            <th>ID Local</th>
                            <th>Type Local</th>
                            <th>Libellé Local</th>
                            <th style="text-align: center; width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($locaux)): ?>
                            <tr>
                                <td colspan="4" class="text-center no-data">Aucun local enregistré.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($locaux as $local): ?>
                                <tr>
                                    <td class="cell-id-local"><strong><?= sprintf('%04d', $local['idLocal']) ?></strong></td>
                                    <td><strong><?= htmlspecialchars($local['typeLocal'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><strong><?= !empty($local['labelLocal']) ? htmlspecialchars($local['labelLocal'], ENT_QUOTES, 'UTF-8') : '<i>Aucun libellé</i>' ?></strong></td>
                                    <td style="text-align: center;">
                                        <div style="display: flex; justify-content: center; gap: 12px;">
                                            <button type="button" title="Modifier" style="background: none; border: none; padding: 0; cursor: pointer;"
                                                onclick="prepareEdit(<?= $local['idLocal'] ?>, '<?= htmlspecialchars(addslashes($local['typeLocal']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($local['labelLocal']), ENT_QUOTES, 'UTF-8') ?>')">
                                                <i class="fa-solid fa-pen-to-square" style="color: #ffc107; font-size: 1.1rem;"></i>
                                            </button>
                                            
                                            <form action="gestion_locaux.php" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement le local <?= sprintf('%04d', $local['idLocal']) ?> ?');">
                                                <input type="hidden" name="action_local" value="delete">
                                                <input type="hidden" name="idLocal" value="<?= $local['idLocal'] ?>">
                                                <button type="submit" title="Supprimer" style="background: none; border: none; padding: 0; cursor: pointer;">
                                                    <i class="fa-solid fa-trash-can" style="color: #dc3545; font-size: 1.1rem;"></i>
                                                </button>
                                            </form>
                                        </div>
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