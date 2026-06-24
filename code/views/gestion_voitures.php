<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';
require_once '../config/db.php';

$successMsg = '';
$errorMsg = '';
$isEditMode = false;

$form_matricule = '';
$form_marque = '';
$form_modele = '';
$form_statut = '';

$current_page = basename($_SERVER['PHP_SELF']);
$userId = $_SESSION['idUtilisateur'] ?? null;

if (isset($_GET['delete'])) {
    $matriculeToDelete = trim($_GET['delete']);
    
    try {
        $stmtCheck = $pdo->prepare("SELECT marque, modele FROM voitures WHERE matricule = ?");
        $stmtCheck->execute([$matriculeToDelete]);
        $voiture = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($voiture) {
            $nomVehicule = $voiture['marque'] . " " . $voiture['modele'];
            
            $pdo->beginTransaction();
            
            $stmtDel = $pdo->prepare("DELETE FROM voitures WHERE matricule = ?");
            $stmtDel->execute([$matriculeToDelete]);

            $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
            $stmtLog->execute([
                $userId, 
                "Suppression du véhicule logistique : $nomVehicule ($matriculeToDelete)",
                "DELETE",
                "voitures",
                $matriculeToDelete
            ]);

            $pdo->commit();
            $successMsg = "Le véhicule '$nomVehicule' ($matriculeToDelete) a été retiré du parc avec succès.";
        } else {
            $errorMsg = "Véhicule introuvable.";
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $errorMsg = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_voiture'])) {
    $actionType = $_POST['action_voiture'];
    $matricule = trim($_POST['matricule'] ?? '');
    $marque = trim($_POST['marque'] ?? '');
    $modele = trim($_POST['modele'] ?? '');
    $statut = $_POST['statut'] ?? 'Disponible';

    if (!empty($matricule) && !empty($marque) && !empty($statut)) {
        if (!preg_match('/^09-\d{6}$/', $matricule)) {
            $errorMsg = "Format d'immatriculation invalide. Le format doit être '09-' suivi de 6 chiffres (Ex: 09-123456).";
        } else {
            try {
                if ($actionType === 'create') {
                    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM voitures WHERE matricule = ?");
                    $stmtCheck->execute([$matricule]);
                    
                    if ($stmtCheck->fetchColumn() > 0) {
                        $errorMsg = "Le véhicule immatriculé '$matricule' existe déjà.";
                    } else {
                        $pdo->beginTransaction();
                        
                        $sqlInsert = "INSERT INTO voitures (matricule, marque, modele, statut) VALUES (?, ?, ?, ?)";
                        $pdo->prepare($sqlInsert)->execute([$matricule, $marque, $modele, $statut]);

                        $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)")
                            ->execute([
                                $userId, 
                                "Ajout du véhicule logistique : $marque $modele ($matricule)",
                                "CREATE",
                                "voitures",
                                $matricule
                            ]);

                        $pdo->commit();
                        $successMsg = "Le véhicule '$marque $modele' a été enregistré avec succès.";
                    }
                } elseif ($actionType === 'update') {
                    $pdo->beginTransaction();
                    
                    $sqlUpdate = "UPDATE voitures SET marque = ?, modele = ?, statut = ? WHERE matricule = ?";
                    $pdo->prepare($sqlUpdate)->execute([$marque, $modele, $statut, $matricule]);

                    $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)")
                        ->execute([
                            $userId, 
                            "Modification du véhicule : $marque $modele ($matricule) - Statut: $statut",
                            "UPDATE",
                            "voitures",
                            $matricule
                        ]);

                    $pdo->commit();
                    $successMsg = "Les informations du véhicule '$matricule' ont été mises à jour.";
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $errorMsg = "Erreur système : " . $e->getMessage();
            }
        }
    } else {
        $errorMsg = "Veuillez remplir tous les champs requis (*).";
    }
}

if (isset($_GET['edit'])) {
    $matriculeToEdit = trim($_GET['edit']);
    $stmtEdit = $pdo->prepare("SELECT * FROM voitures WHERE matricule = ?");
    $stmtEdit->execute([$matriculeToEdit]);
    $voitureToEdit = $stmtEdit->fetch(PDO::FETCH_ASSOC);

    if ($voitureToEdit) {
        $isEditMode = true;
        $form_matricule = $voitureToEdit['matricule'];
        $form_marque = $voitureToEdit['marque'];
        $form_modele = $voitureToEdit['modele'];
        $form_statut = $voitureToEdit['statut'];
    }
}

$voitures = [];
try {
    $query = "SELECT * FROM voitures ORDER BY marque ASC, modele ASC";
    $voitures = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMsg = "Impossible de charger le parc automobile : " . $e->getMessage();
}
?>

<div class="immo-wrapper">
    <div class="immo-header">
        <h1><i class="fa-solid fa-car"></i> Gestion des Véhicules (STEG)</h1>
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
                <i class="fa-solid <?= $isEditMode ? 'fa-car-burst' : 'fa-plus' ?>"></i> 
                <?= $isEditMode ? "Modifier le véhicule" : "Enregistrer un véhicule" ?>
            </h2>
            <hr>
            <form action="<?= $current_page ?>" method="POST" class="steg-form">
                <input type="hidden" name="action_voiture" value="<?= $isEditMode ? 'update' : 'create' ?>">

                <div class="form-control">
                    <label for="matricule">Numéro d'immatriculation *</label>
                    <input type="text" id="matricule" name="matricule" required 
                           pattern="^09-\d{6}$" 
                           maxlength="9"
                           title="L'immatriculation doit être au format '09-' suivi de exactement 6 chiffres (Ex: 09-123456)"
                           placeholder="Ex: 09-123456" 
                           value="<?= htmlspecialchars($form_matricule) ?>" <?= $isEditMode ? 'readonly style="background-color: #e9ecef; cursor: not-allowed;"' : '' ?>>
                </div>

                <div class="form-control">
                    <label for="marque">Marque *</label>
                    <input type="text" id="marque" name="marque" required placeholder="Ex: Citroën, Mahindra, Peugeot" value="<?= htmlspecialchars($form_marque) ?>">
                </div>

                <div class="form-control">
                    <label for="modele">Modèle</label>
                    <input type="text" id="modele" name="modele" placeholder="Ex: Berlingo, Partner, D-Max" value="<?= htmlspecialchars($form_modele) ?>">
                </div>

                <div class="form-control">
                    <label for="statut">Statut opérationnel *</label>
                    <select id="statut" name="statut" required>
                        <option value="Disponible" <?= $form_statut === 'Disponible' ? 'selected' : '' ?>>Disponible (Actif)</option>
                        <option value="En Panne" <?= $form_statut === 'En Panne' ? 'selected' : '' ?>>En Maintenance / En Panne</option>
                        <option value="Réformé" <?= $form_statut === 'Réformé' ? 'selected' : '' ?>>Hors Service (Réformé)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-success btn-block">
                    <i class="fa-solid <?= $isEditMode ? 'fa-floppy-disk' : 'fa-car-side' ?>"></i> 
                    <?= $isEditMode ? "Mettre à jour" : "Enregistrer le véhicule" ?>
                </button>

                <?php if ($isEditMode): ?>
                    <a href="<?= $current_page ?>" class="btn btn-primary btn-block" style="margin-top: 10px; background-color: #64748b; text-align: center; display: block; text-decoration: none;">
                        <i class="fa-solid fa-xmark"></i> Annuler la modification
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="immo-card card-table">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2><i class="fa-solid fa-list"></i> État de Flotte Active</h2>
                <button type="button" onclick="window.print();" class="btn btn-primary btn-print" style="padding: 6px 14px;">
                    <i class="fa-solid fa-print"></i> Imprimer
                </button>
            </div>
            <hr>
            
            <div class="print-header-steg">
                <table style="width: 100%;">
                    <tr>
                        <td style="text-align: left;">
                            <strong>Société Tunisienne de l'Électricité et du Gaz</strong><br>
                            Direction Informatique & Logistique
                        </td>
                        <td style="text-align: right;">
                            Date d'édition : <?= date('d/m/Y H:i') ?>
                        </td>
                    </tr>
                </table>
                <h2 style="text-align: center; margin-top: 20px;">État Officiel du Parc Automobile Actif</h2>
            </div>

            <div class="table-scroll">
                <table class="steg-table">
                    <thead>
                        <tr>
                            <th>Immatriculation</th>
                            <th>Marque & Modèle</th>
                            <th>Statut opérationnel</th>
                            <th class="btn-action">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($voitures)): ?>
                            <tr>
                                <td colspan="4" class="text-center no-data">Aucun véhicule enregistré dans le parc de ce district.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($voitures as $voiture): ?>
                                <tr>
                                    <td class="cell-code">
                                        <?= htmlspecialchars($voiture['matricule']) ?>
                                    </td>
                                    <td class="cell-desc">
                                        <strong><?= htmlspecialchars($voiture['marque']) ?></strong> <?= htmlspecialchars($voiture['modele']) ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $statusClass = 'en-attente';
                                        if ($voiture['statut'] === 'En Panne') $statusClass = 'en-attente';
                                        if ($voiture['statut'] === 'Disponible') $statusClass = 'résolu';
                                        if ($voiture['statut'] === 'Réformé') $statusClass = 'rejete'; ?>
                                        <span class="badge-status status-<?= $statusClass ?>">
                                            <?= htmlspecialchars($voiture['statut']) ?>
                                        </span>
                                    </td>
                                    <td class="btn-action" style="white-space: nowrap; width: 10%;">
                                        <a href="<?= $current_page ?>?edit=<?= urlencode($voiture['matricule']) ?>" class="btn-action edit" title="Modifier" style="color: #ca8a04; margin-right: 12px; font-size: 1.1rem; text-decoration: none;">
                                            <i class="fa-solid fa-pen-to-square" style="color: #ffc107; font-size: 1.1rem;"></i>
                                        </a>
                                        <a href="<?= $current_page ?>?delete=<?= urlencode($voiture['matricule']) ?>" class="btn-action delete" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir retirer définitivement du parc le véhicule <?= htmlspecialchars($voiture['marque'] . ' ' . $voiture['modele']) ?> (<?= htmlspecialchars($voiture['matricule']) ?>) ?');" style="color: #dc2626; font-size: 1.1rem; text-decoration: none;">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
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