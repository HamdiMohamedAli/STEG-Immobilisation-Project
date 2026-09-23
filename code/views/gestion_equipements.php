<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/header.php';
require_once '../includes/navbar.php';
require_once '../config/db.php';

echo '<link rel="stylesheet" href="print.css">';

$successMsg = $_SESSION['success_msg'] ?? '';
$errorMsg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

$current_page = basename($_SERVER['PHP_SELF']);
$categories = ["Câble de connexion", "Imprimante", "Périphérique informatique", "Projecteur", "Réfrigérateur", "Télévision", "Unité Centrale"];
natcasesort($categories);

$userId = $_SESSION['idUtilisateur'] ?? null;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        if (isset($_POST['action_delete'])) {
            $id = intval($_POST['idEquipToDelete'] ?? 0);
            $stmt = $pdo->prepare("SELECT libelle FROM Equipements WHERE idEquip = ?");
            $stmt->execute([$id]);
            $libelle = $stmt->fetchColumn();

            if ($libelle) {
                $pdo->prepare("DELETE FROM Equipements WHERE idEquip = ?")->execute([$id]);
                $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, 'DELETE', 'Equipement', ?)")
                    ->execute([$userId, "Suppression de l'équipement " . sprintf('%04d', $id) . " ($libelle)", $id]);
                $_SESSION['success_msg'] = "L'équipement '" . htmlspecialchars($libelle) . "' a été supprimé avec succès.";
            } else {
                $_SESSION['error_msg'] = "Équipement introuvable.";
            }
        } elseif (isset($_POST['action_ajout'])) {
            $actionType = $_POST['action_ajout'];
            $typeEquip = $_POST['typeEquip'] ?? '';
            $libelle = trim($_POST['libelle'] ?? '');

            if ($typeEquip && $libelle) {
                if ($actionType === 'create') {
                    $pdo->prepare("INSERT INTO Equipements (typeEquip, libelle) VALUES (?, ?)")->execute([$typeEquip, $libelle]);
                    $lastId = $pdo->lastInsertId();
                    $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, 'CREATE', 'Equipement', ?)")
                        ->execute([$userId, "Création de l'équipement " . sprintf('%04d', $lastId) . " ($libelle)", $lastId]);
                    $_SESSION['success_msg'] = "L'équipement '" . htmlspecialchars($libelle) . "' a été enregistré avec succès.";
                } elseif ($actionType === 'update') {
                    $id = intval($_POST['idEquip'] ?? 0);
                    $pdo->prepare("UPDATE Equipements SET typeEquip = ?, libelle = ? WHERE idEquip = ?")->execute([$typeEquip, $libelle, $id]);
                    $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, 'UPDATE', 'Equipement', ?)")
                        ->execute([$userId, "Modification de l'équipement " . sprintf('%04d', $id) . " (Nouveau libelle: $libelle)", $id]);
                    $_SESSION['success_msg'] = "L'équipement '" . sprintf('%04d', $id) . "' a été mis à jour.";
                }
            } else {
                $_SESSION['error_msg'] = "Veuillez remplir tous les champs obligatoires (*).";
            }
        }
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['error_msg'] = "Erreur : " . $e->getMessage();
    }
    header("Location: " . $current_page);
    exit();
}

// Edit mode initialization
$isEditMode = false;
$form = ['idEquip' => '', 'typeEquip' => '', 'libelle' => ''];

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM Equipements WHERE idEquip = ?");
    $stmt->execute([intval($_GET['edit'])]);
    if ($equip = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $isEditMode = true;
        $form = $equip;
    }
}

// Get next ID if not editing
$nextId = 1;
if (!$isEditMode) {
    try {
        $status = $pdo->query("SHOW TABLE STATUS LIKE 'Equipements'")->fetch(PDO::FETCH_ASSOC);
        $nextId = $status['Auto_increment'] ?? ($pdo->query("SELECT MAX(idEquip) FROM Equipements")->fetchColumn() + 1);
    } catch (Exception $e) {}
}

// Fetch list
try {
    $biens = $pdo->query("SELECT idEquip, typeEquip, libelle FROM Equipements ORDER BY idEquip ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errorMsg = "Impossible de charger le catalogue : " . $e->getMessage();
}

$safePage = htmlspecialchars($current_page, ENT_QUOTES, 'UTF-8');
?>

<div class="immo-wrapper">
    <div class="immo-header">
        <h1><i class="fa-solid fa-boxes-stacked"></i> Gestion des Equipements (STEG)</h1>
    </div>
    <br>

    <?php if ($successMsg): ?>
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="immo-grid">
        <!-- FORMULAIRE -->
        <div class="immo-card card-form">
            <h2>
                <i class="fa-solid <?= $isEditMode ? 'fa-pen-to-square' : 'fa-plus' ?>"></i>
                <?= $isEditMode ? "Modifier le bien " . sprintf('%04d', $form['idEquip']) : "Enregistrer un nouveau bien" ?>
            </h2>
            <hr>
            <form action="<?= $safePage ?>" method="POST" class="steg-form">
                <input type="hidden" name="action_ajout" value="<?= $isEditMode ? 'update' : 'create' ?>">
                <?php if ($isEditMode): ?>
                    <input type="hidden" name="idEquip" value="<?= intval($form['idEquip']) ?>">
                <?php endif; ?>

                <div class="form-control">
                    <label>Identifiant Auto</label>
                    <input type="text" value="<?= sprintf('%04d', $isEditMode ? $form['idEquip'] : $nextId) ?>" readonly style="background-color:#e9ecef; cursor:not-allowed; font-weight:bold;">
                </div>

                <div class="form-control">
                    <label for="typeEquip">Type d'équipement *</label>
                    <select id="typeEquip" name="typeEquip" required>
                        <option value="" disabled <?= !$isEditMode ? 'selected' : '' ?>>-- Choisir un type --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>" <?= $form['typeEquip'] === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-control">
                    <label for="libelle">Libellé / Désignation *</label>
                    <input type="text" id="libelle" name="libelle" required placeholder="Ex: Télévision LED 55 Pouces" value="<?= htmlspecialchars($form['libelle'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <button type="submit" class="btn btn-success btn-block">
                    <i class="fa-solid <?= $isEditMode ? 'fa-floppy-disk' : 'fa-arrow-down-long' ?>"></i>
                    <?= $isEditMode ? "Mettre à jour l'inventaire" : "Valider l'inventaire" ?>
                </button>

                <?php if ($isEditMode): ?>
                    <a href="<?= $safePage ?>" class="btn btn-primary btn-block" style="margin-top:10px; background-color:#64748b; text-align:center; display:block; text-decoration:none;">
                        <i class="fa-solid fa-xmark"></i> Annuler la modification
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="immo-card card-table">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <h2><i class="fa-solid fa-list"></i> Registre des Données Brutes</h2>
                <button type="button" onclick="window.print();" class="btn-print" style="background-color:#0d6efd;color:white;border:none;padding:8px 16px;border-radius:4px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-print"></i> Imprimer
                </button>
            </div>
            <hr>

            <div class="print-header-steg">
                <table style="width:100%; border-collapse:collapse; border:none;">
                    <tr>
                        <td style="width:65%; border:none; text-align:left; vertical-align:top;">
                            <strong>Société Tunisienne de l'Électricité et du Gaz</strong><br>Direction Informatique &amp; Logistique
                        </td>
                        <td style="width:35%; border:none; text-align:right; vertical-align:top;">
                            Date d'édition : <?= date('d/m/Y H:i') ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="border:none; text-align:center; padding-top:20px; font-size:18px; font-weight:bold;">
                            Registre Officiel des Équipements
                        </td>
                    </tr>
                </table>
            </div>

            <div class="table-scroll">
                <table class="steg-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Libellé</th>
                            <th>Type d'équipement</th>
                            <th class="btn-action" style="text-align:center; width:100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($biens)): ?>
                            <tr><td colspan="4" class="text-center no-data">Aucun bien équipement brut référencé pour le moment.</td></tr>
                        <?php else: ?>
                            <?php foreach ($biens as $bien): ?>
                                <tr>
                                    <td class="cell-code"><strong><?= sprintf('%04d', $bien['idEquip']) ?></strong></td>
                                    <td class="cell-desc"><strong><?= htmlspecialchars($bien['libelle'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><strong><?= htmlspecialchars($bien['typeEquip'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td class="btn-action" style="white-space:nowrap; display:flex; align-items:center; justify-content:center;">
                                        <!-- MODIFIER -->
                                        <a href="<?= $safePage ?>?edit=<?= $bien['idEquip'] ?>" class="btn-action edit" title="Modifier" style="color:var(--warning-color); margin-right:15px; font-size:1.1rem; text-decoration:none;">
                                            <i class="fa-solid fa-pen-to-square" style="color:#ffc107; font-size:1.1rem;"></i>
                                        </a>
                                        <!-- SUPPRIMER -->
                                        <form action="<?= $safePage ?>" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement l\'équipement <?= htmlspecialchars(addslashes($bien['libelle']), ENT_QUOTES, 'UTF-8') ?> ?');" style="margin:0; padding:0;">
                                            <input type="hidden" name="action_delete" value="1">
                                            <input type="hidden" name="idEquipToDelete" value="<?= $bien['idEquip'] ?>">
                                            <button type="submit" title="Supprimer" style="background:none; border:none; color:var(--danger-color); font-size:1.1rem; cursor:pointer; padding:0;">
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

<?php require_once '../includes/footer.php'; ?>