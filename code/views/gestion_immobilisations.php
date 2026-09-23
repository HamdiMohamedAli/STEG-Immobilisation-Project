<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';
require_once '../config/db.php';

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_immo'])) {
    $action = $_POST['action_immo'];
    $userId = $_SESSION['idUtilisateur'] ?? null;

    if ($action === 'create') {
        $typeImmo = trim($_POST['typeImmo'] ?? '');
        $libelle = trim($_POST['libelle'] ?? '');

        if (!empty($typeImmo) && !empty($libelle)) {
            try {
                $pdo->beginTransaction();

                $sqlInsert = "INSERT INTO immobilisations (typeImmo, libelle) VALUES (?, ?)";
                $stmt = $pdo->prepare($sqlInsert);
                $stmt->execute([$typeImmo, $libelle]);

                $lastId = $pdo->lastInsertId();

                $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
                $actionDescription = "Création de l'immobilisation " . sprintf('%04d', $lastId) . " (typeImmo: $typeImmo, libelle: $libelle)";
                $stmtLog->execute([$userId, $actionDescription, 'CREATE', 'Immobilisation', $lastId]);

                $pdo->commit();
                $successMsg = "L'immobilisation a été enregistrée avec succès sous l'identifiant " . sprintf('%04d', $lastId) . ".";
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $errorMsg = "Erreur lors de la création de l'immobilisation : " . $e->getMessage();
            }
        } else {
            $errorMsg = "Veuillez renseigner le type et le libellé de l'immobilisation.";
        }
    }

    elseif ($action === 'update') {
        $idImmo = intval($_POST['idImmo'] ?? 0);
        $typeImmo = trim($_POST['typeImmo'] ?? '');
        $libelle = trim($_POST['libelle'] ?? '');

        if ($idImmo > 0 && !empty($typeImmo) && !empty($libelle)) {
            try {
                $pdo->beginTransaction();

                $sqlUpdate = "UPDATE immobilisations SET typeImmo = ?, libelle = ? WHERE idImmo = ?";
                $stmt = $pdo->prepare($sqlUpdate);
                $stmt->execute([$typeImmo, $libelle, $idImmo]);

                $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
                $actionDescription = "Modification de l'immobilisation " . sprintf('%04d', $idImmo) . " (Type: $typeImmo, Libellé: $libelle)";
                $stmtLog->execute([$userId, $actionDescription, 'UPDATE', 'Immobilisation', $idImmo]);

                $pdo->commit();
                $successMsg = "L'immobilisation n°" . sprintf('%04d', $idImmo) . " a été modifiée avec succès.";
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $errorMsg = "Erreur lors de la modification de l'immobilisation : " . $e->getMessage();
            }
        } else {
            $errorMsg = "Données invalides. Impossible de mettre à jour l'immobilisation.";
        }
    }

    elseif ($action === 'delete') {
        $idImmo = intval($_POST['idImmo'] ?? 0);

        if ($idImmo > 0) {
            try {
                $pdo->beginTransaction();

                $stmtSelect = $pdo->prepare("SELECT typeImmo, libelle FROM immobilisations WHERE idImmo = ?");
                $stmtSelect->execute([$idImmo]);
                $immoData = $stmtSelect->fetch(PDO::FETCH_ASSOC);

                if ($immoData) {
                    $sqlDelete = "DELETE FROM immobilisations WHERE idImmo = ?";
                    $stmt = $pdo->prepare($sqlDelete);
                    $stmt->execute([$idImmo]);

                    $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
                    $actionDescription = "Suppression de l'immobilisation " . sprintf('%04d', $idImmo) . " (Type: {$immoData['typeImmo']}, Libellé: {$immoData['libelle']})";
                    $stmtLog->execute([$userId, $actionDescription, 'DELETE', 'Immobilisation', $idImmo]);

                    $pdo->commit();
                    $successMsg = "L'immobilisation n°" . sprintf('%04d', $idImmo) . " a été définitivement supprimée.";
                } else {
                    $pdo->rollBack();
                    $errorMsg = "L'immobilisation demandée n'existe pas ou a déjà été supprimée.";
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $errorMsg = "Erreur lors de la suppression de l'immobilisation : " . $e->getMessage();
            }
        } else {
            $errorMsg = "Identifiant d'immobilisation incorrect.";
        }
    }
}

$nextId = 1;
try {
    $stmtNext = $pdo->query("SELECT idImmo FROM immobilisations ORDER BY idImmo DESC LIMIT 1;");
    $lastRow = $stmtNext->fetch(PDO::FETCH_ASSOC);
    if ($lastRow) {
        $nextId = intval($lastRow['idImmo']) + 1;
    }
} catch (PDOException $e) {}

$typesImmoExistants = [];
try {
    $typesImmoExistants = $pdo->query("SELECT DISTINCT typeImmo FROM immobilisations WHERE typeImmo IS NOT NULL AND typeImmo != '' ORDER BY typeImmo ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {}

$immobilisations = [];
try {
    $query = "SELECT i.* FROM immobilisations i ORDER BY i.idImmo ASC";
    $immobilisations = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMsg = "Échec du chargement du registre des immobilisations : " . $e->getMessage();
}
?>

<div class="locaux-wrapper">
    <div class="locaux-header">
        <h1><i class="fa-solid fa-couch"></i> Gestion des Immobilisations</h1>
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
            <h2 id="form-title"><i class="fa-solid fa-square-plus"></i> Déclarer une nouvelle immobilisation</h2>
            <hr>
            <form action="gestion_immobilisations.php" method="POST" class="steg-form" id="immoForm">
                <input type="hidden" name="action_immo" id="formAction" value="create">
                <input type="hidden" name="idImmo" id="formIdImmo" value="">

                <div class="form-control">
                    <label for="idImmoDisplay" id="labelIdDisplay">Identifiant (Automatique)</label>
                    <input type="text" id="idImmoDisplay" value="<?= sprintf('%04d', $nextId) ?>" readonly style="background-color: #e9ecef; cursor: not-allowed; font-weight: bold;">
                </div>

                <div class="form-control">
                    <label for="typeImmo">Type Immobilisation *</label>
                    <input type="text" id="typeImmo" name="typeImmo" list="liste-types-immo" placeholder="Ex: Bureau, Siège, Armoire..." required>
                    <datalist id="liste-types-immo">
                        <?php foreach ($typesImmoExistants as $t): ?>
                            <option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="form-control">
                    <label for="libelle">Libellé Immobilisation *</label>
                    <input type="text" id="libelle" name="libelle" placeholder="Ex: Table de bureau en bois 1.80m" required>
                </div>

                <button type="submit" id="btnSubmitForm" class="btn btn-primary btn-block">
                    <i class="fa-solid fa-floppy-disk"></i> Enregistrer l'immobilisation
                </button>

                <button type="button" id="btnCancelEdit" class="btn btn-secondary btn-block" style="display:none; margin-top: 8px;" onclick="resetFormToCreate(<?= $nextId ?>)">
                    <i class="fa-solid fa-xmark"></i> Annuler la modification
                </button>
            </form>
        </div>

<div class="locaux-card card-table">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">

        <h2 style="margin:0;">
            <i class="fa-solid fa-clipboard-list"></i>
            Registre Actif des Immobilisations

            <span style="font-size:0.8rem;color:#64748b;font-weight:400;margin-left:8px;">
                (<?= count($immobilisations) ?> immobilisation<?= count($immobilisations) > 1 ? 's' : '' ?>)
            </span>
        </h2>

        <button
            type="button"
            onclick="window.print();"
            class="btn-print"
            style="background:#0d6efd;color:white;border:none;padding:6px 14px;border-radius:4px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;"
        >
            <i class="fa-solid fa-print"></i> Imprimer
        </button>

    </div>

    <hr>

    <div class="print-header-steg">

        <table style="width:100%;border:none;">

            <tr>

                <td style="border:none !important;text-align:left;padding:0 !important;">

                    <strong>Société Tunisienne de l'Électricité et du Gaz</strong><br>

                    Direction de la Distribution<br>

                    Division Logistique &amp; Approvisionnement

                </td>

                <td style="border:none !important;text-align:right;padding:0 !important;">

                    Date d'édition : <?= date('d/m/Y H:i') ?>

                </td>

            </tr>

        </table>

        <h2 style="text-align:center;margin-top:20px;font-size:18pt;">

            Registre Officiel des Immobilisations

        </h2>

    </div>

    <div class="table-scroll">

        <table class="steg-table">

            <thead>

                <tr>
                    <th>ID Immo</th>
                    <th>Type Immobilisation</th>
                    <th>Libellé</th>
                    <th class="btn-action" style="text-align:center;width:100px;">Actions</th>
                </tr>

            </thead>

            <tbody>

                <?php if (empty($immobilisations)): ?>

                    <tr>

                        <td colspan="4" class="text-center no-data">

                            Aucune immobilisation enregistrée.

                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($immobilisations as $immo): ?>

                        <tr>

                            <td class="cell-id-local">

                                <strong>
                                    <?= sprintf('%04d', $immo['idImmo']) ?>
                                </strong>

                            </td>

                            <td>

                                <strong>
                                    <?= htmlspecialchars($immo['typeImmo'], ENT_QUOTES, 'UTF-8') ?>
                                </strong>

                            </td>

                            <td>

                                <strong>
                                    <?= htmlspecialchars($immo['libelle'], ENT_QUOTES, 'UTF-8') ?>
                                </strong>

                            </td>

                            <td
                                class="btn-action"
                                style="text-align:center;white-space:nowrap;"
                            >

                                <div style="display:flex;justify-content:center;gap:12px;">

                                    <button
                                        type="button"
                                        title="Modifier"
                                        style="background:none;border:none;padding:0;cursor:pointer;"
                                        onclick="prepareEdit(
                                            <?= $immo['idImmo'] ?>,
                                            '<?= htmlspecialchars(addslashes($immo['typeImmo']), ENT_QUOTES, 'UTF-8') ?>',
                                            '<?= htmlspecialchars(addslashes($immo['libelle']), ENT_QUOTES, 'UTF-8') ?>'
                                        )"
                                    >
                                        <i
                                            class="fa-solid fa-pen-to-square"
                                            style="color:#ffc107;font-size:1.1rem;"
                                        ></i>
                                    </button>

                                    <form
                                        action="gestion_immobilisations.php"
                                        method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement l\'immobilisation <?= sprintf('%04d', $immo['idImmo']) ?> ?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="action_immo"
                                            value="delete"
                                        >

                                        <input
                                            type="hidden"
                                            name="idImmo"
                                            value="<?= $immo['idImmo'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            title="Supprimer"
                                            style="background:none;border:none;padding:0;cursor:pointer;"
                                        >
                                            <i
                                                class="fa-solid fa-trash-can"
                                                style="color:#dc3545;font-size:1.1rem;"
                                            ></i>
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

<script>
    function prepareEdit(idImmo, typeImmo, libelle) {
        document.getElementById('formAction').value = 'update';
        document.getElementById('formIdImmo').value = idImmo;
        document.getElementById('idImmoDisplay').value = String(idImmo).padStart(4, '0');
        document.getElementById('typeImmo').value = typeImmo;
        document.getElementById('libelle').value = libelle;

        document.getElementById('form-title').innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Modifier l\'immobilisation ' + String(idImmo).padStart(4, '0');
        document.getElementById('labelIdDisplay').textContent = 'Identifiant';
        document.getElementById('btnSubmitForm').innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Enregistrer les modifications';
        document.getElementById('btnCancelEdit').style.display = 'block';

        document.getElementById('immoForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function resetFormToCreate(nextId) {
        document.getElementById('formAction').value = 'create';
        document.getElementById('formIdImmo').value = '';
        document.getElementById('idImmoDisplay').value = String(nextId).padStart(4, '0');
        document.getElementById('typeImmo').value = '';
        document.getElementById('libelle').value = '';

        document.getElementById('form-title').innerHTML = '<i class="fa-solid fa-square-plus"></i> Déclarer une nouvelle immobilisation';
        document.getElementById('labelIdDisplay').textContent = 'Identifiant (Automatique)';
        document.getElementById('btnSubmitForm').innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Enregistrer l\'immobilisation';
        document.getElementById('btnCancelEdit').style.display = 'none';
    }
</script>

<?php
require_once '../includes/footer.php';
?>