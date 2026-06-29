<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';
require_once '../config/db.php';

if (!isset($_SESSION['idProfil']) || (string)$_SESSION['idProfil'] !== '1') {
    echo "<div class='alert alert-danger' style='margin:20px;'><strong>Accès refusé !</strong> Vous devez être Administrateur pour accéder à ce module.</div>";
    require_once '../includes/footer.php';
    exit();
}

$successMsg = '';
$errorMsg   = '';
$isEditMode = false;

$form_id      = '';
$form_nom     = '';
$form_email   = '';
$form_tel     = '';
$form_profil  = '';
$form_idLocal = '';
$form_statut  = 1;

if (isset($_GET['delete'])) {
    $idToDelete = trim($_GET['delete']);

    if (isset($_SESSION['idUtilisateur']) && (int)$idToDelete === (int)$_SESSION['idUtilisateur']) {
        $errorMsg = "Vous ne pouvez pas supprimer votre propre compte administrateur.";
    } else {
        try {
            $stmtName = $pdo->prepare("
                SELECT u.nom, u.email, u.tel, p.labelProfil, u.idLocalAssigne
                FROM users u
                LEFT JOIN profils p ON u.idProfil = p.idProfil
                WHERE u.idUtilisateur = ?
            ");
            $stmtName->execute([(int)$idToDelete]);
            $agentInfo = $stmtName->fetch(PDO::FETCH_ASSOC);

            if ($agentInfo) {
                $pdo->prepare("DELETE FROM users WHERE idUtilisateur = ?")->execute([(int)$idToDelete]);

                $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)")
                    ->execute([
                        (int)$_SESSION['idUtilisateur'],
                        "Suppression du compte utilisateur",
                        "DELETE",
                        "users",
                        "ID: " . str_pad($idToDelete, 5, '0', STR_PAD_LEFT) . " | Nom: {$agentInfo['nom']} | Email: {$agentInfo['email']} | Rôle: {$agentInfo['labelProfil']}"
                    ]);

                $successMsg = "L'utilisateur « {$agentInfo['nom']} » (ID " . str_pad($idToDelete, 5, '0', STR_PAD_LEFT) . ") a été supprimé avec succès.";
            } else {
                $errorMsg = "Utilisateur introuvable.";
            }
        } catch (PDOException $e) {
            $errorMsg = "Erreur lors de la suppression : " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_user'])) {
    $actionType    = $_POST['action_user'];
    $idUtilisateur = isset($_POST['idUtilisateur']) ? trim($_POST['idUtilisateur']) : '';
    $nom           = trim($_POST['nom']);
    $email         = trim($_POST['email']);
    $tel           = trim($_POST['tel']);
    $passwordBrut  = trim($_POST['password']);
    $profilId      = intval($_POST['profil']);
    $idLocal       = !empty($_POST['idLocalAssigne']) ? intval($_POST['idLocalAssigne']) : null;
    $statut        = isset($_POST['statut']) ? intval($_POST['statut']) : 1;

    $stmtProfil = $pdo->prepare("SELECT labelProfil FROM profils WHERE idProfil = ?");
    $stmtProfil->execute([$profilId]);
    $roleLabel = $stmtProfil->fetchColumn() ?: "ID profil: $profilId";

    if ($actionType === 'update' && empty($idUtilisateur)) {
        $errorMsg = "L'identifiant technique est requis pour la modification.";
    } elseif ($actionType === 'create' && !preg_match('/^\d{5}$/', $passwordBrut)) {
        $errorMsg = "Le mot de passe initial doit comporter exactement 5 chiffres.";
    } elseif ($actionType === 'update' && !empty($passwordBrut) && !preg_match('/^\d{5}$/', $passwordBrut)) {
        $errorMsg = "Le nouveau mot de passe doit comporter exactement 5 chiffres.";
    } elseif (!empty($nom) && !empty($email)) {

        try {
            if ($actionType === 'create') {
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                $stmtCheck->execute([$email]);

                if ($stmtCheck->fetchColumn() > 0) {
                    $errorMsg = "Cette adresse email est déjà attribuée.";
                } else {
                    $passwordHash = hash('sha256', $passwordBrut);
                    
                    $pdo->prepare("INSERT INTO users (nom, email, tel, motDePasse, idProfil, idLocalAssigne, statut) VALUES (?, ?, ?, ?, ?, ?, ?)")
                        ->execute([$nom, $email, $tel, $passwordHash, $profilId, $idLocal, $statut]);

                    $newId = $pdo->lastInsertId();
                    $displayId = str_pad($newId, 5, '0', STR_PAD_LEFT);
                    $statusLabel = ($statut === 1) ? 'Actif' : 'Inactif';
                    
                    $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)")
                        ->execute([
                            (int)$_SESSION['idUtilisateur'],
                            "Création du compte utilisateur",
                            "INSERT",
                            "users",
                            "ID: $displayId | Nom: $nom | Email: $email | Rôle: $roleLabel | Statut: $statusLabel" . ($idLocal ? " | Zone: $idLocal" : "")
                        ]);

                    $successMsg = "L'utilisateur « $nom » a été enregistré avec succès avec l'ID $displayId.";
                }

            } elseif ($actionType === 'update') {
                $idInt = (int)$idUtilisateur;
                $stmtCheckEmail = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND idUtilisateur != ?");
                $stmtCheckEmail->execute([$email, $idInt]);

                if ($stmtCheckEmail->fetchColumn() > 0) {
                    $errorMsg = "Cette adresse email est déjà associée à un autre agent.";
                } else {
                    if (!empty($passwordBrut)) {
                        $passwordHash = hash('sha256', $passwordBrut);
                        $pdo->prepare("UPDATE users SET nom=?, email=?, tel=?, motDePasse=?, idProfil=?, idLocalAssigne=?, statut=? WHERE idUtilisateur=?")
                            ->execute([$nom, $email, $tel, $passwordHash, $profilId, $idLocal, $statut, $idInt]);
                        $pwdNote = " | Mot de passe : réinitialisé";
                    } else {
                        $pdo->prepare("UPDATE users SET nom=?, email=?, tel=?, idProfil=?, idLocalAssigne=?, statut=? WHERE idUtilisateur=?")
                            ->execute([$nom, $email, $tel, $profilId, $idLocal, $statut, $idInt]);
                        $pwdNote = "";
                    }

                    $displayId = str_pad($idInt, 5, '0', STR_PAD_LEFT);
                    $statusLabel = ($statut === 1) ? 'Actif' : 'Inactif';
                    
                    $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)")
                        ->execute([
                            (int)$_SESSION['idUtilisateur'],
                            "Modification du compte utilisateur",
                            "UPDATE",
                            "users",
                            "ID: $displayId | Nom: $nom | Email: $email | Rôle: $roleLabel | Statut: $statusLabel" . ($idLocal ? " | Zone: $idLocal" : "") . $pwdNote
                        ]);

                    $successMsg = "Les informations de l'agent « $nom » ont été mises à jour.";
                }
            }
        } catch (PDOException $e) {
            $errorMsg = "Erreur système : " . $e->getMessage();
        }
    } else {
        $errorMsg = "Veuillez remplir tous les champs requis (*).";
    }
}

if (isset($_GET['edit'])) {
    $stmtEdit = $pdo->prepare("SELECT * FROM users WHERE idUtilisateur = ?");
    $stmtEdit->execute([(int)trim($_GET['edit'])]);
    $agentToEdit = $stmtEdit->fetch(PDO::FETCH_ASSOC);

    if ($agentToEdit) {
        $isEditMode   = true;
        $form_id      = str_pad($agentToEdit['idUtilisateur'], 5, '0', STR_PAD_LEFT);
        $form_nom     = $agentToEdit['nom'];
        $form_email   = $agentToEdit['email'];
        $form_tel     = $agentToEdit['tel'];
        $form_profil  = $agentToEdit['idProfil'];
        $form_idLocal = $agentToEdit['idLocalAssigne'];
        $form_statut  = intval($agentToEdit['statut']);
    }
}

$agents = [];
try {
    $agents = $pdo->query("
        SELECT u.*, p.labelProfil
        FROM users u
        LEFT JOIN profils p ON u.idProfil = p.idProfil
        ORDER BY u.idUtilisateur ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMsg = "Impossible de charger le registre du personnel : " . $e->getMessage();
}
?>

<div class="users-wrapper">
    <div class="users-header">
        <h1><i class="fa-solid fa-users-gear"></i> Administration du Personnel (STEG)</h1>
    </div>
    <br>

    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success"><i class="fa-solid fa-user-check"></i> <?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger"><i class="fa-solid fa-user-xmark"></i> <?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="users-grid">

        <div class="users-card card-form">
            <h2>
                <i class="fa-solid <?= $isEditMode ? 'fa-user-pen' : 'fa-user-plus' ?>"></i>
                <?= $isEditMode ? "Modifier l'agent ID : " . htmlspecialchars($form_id) : "Inscrire un nouvel agent" ?>
            </h2>
            <hr>
            <form action="gestion_utilisateurs.php" method="POST" class="steg-form">
                <input type="hidden" name="action_user" value="<?= $isEditMode ? 'update' : 'create' ?>">

                <div class="form-group-row">
                    <div class="form-control">
                        <label for="idUtilisateur">ID</label>
                        <?php if ($isEditMode): ?>
                            <input type="text" id="idUtilisateur" name="idUtilisateur" readonly
                                   style="background-color:#e9ecef;" value="<?= htmlspecialchars($form_id) ?>">
                        <?php else: ?>
                            <input type="text" placeholder="Généré automatiquement" readonly style="background-color:#e9ecef; color:#6c757d;">
                        <?php endif; ?>
                    </div>
                    <div class="form-control">
                        <label for="profil">Rôle Système *</label>
                        <select id="profil" name="profil" required>
                            <?php
                            try {
                                foreach ($pdo->query("SELECT * FROM profils ORDER BY idProfil ASC")->fetchAll(PDO::FETCH_ASSOC) as $p):
                                    $sel = ($form_profil == $p['idProfil']) ? 'selected' : '';
                                    echo "<option value='{$p['idProfil']}' $sel>" . htmlspecialchars($p['labelProfil']) . "</option>";
                                    endforeach;
                            } catch (PDOException $e) {
                                foreach ([1=>'Administrateur',2=>'Responsable',3=>'Chauffeur'] as $idRef => $role)
                                    echo "<option value='$idRef'" . ($form_profil==$idRef?' selected':'') . ">$role</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-control">
                    <label for="nom">Nom et Prénom de l'agent *</label>
                    <input type="text" id="nom" name="nom" required placeholder="Ex: AHMED Ben Ali" value="<?= htmlspecialchars($form_nom) ?>">
                </div>

                <div class="form-control">
                    <label for="email">Adresse Email Institutionnelle *</label>
                    <input type="email" id="email" name="email" required placeholder="Ex: a.benali@steg.com.tn" value="<?= htmlspecialchars($form_email) ?>">
                </div>

                <div class="form-group-row">
                    <div class="form-control">
                        <label for="tel">Téléphone Interne</label>
                        <input type="text" id="tel" name="tel" placeholder="Ex: 71XXXXXX" value="<?= htmlspecialchars($form_tel) ?>">
                    </div>
                    <div class="form-control">
                        <label for="password">Mot de passe <?= $isEditMode ? '(Optionnel)' : '*' ?></label>
                        <input type="password" id="password" name="password"
                               pattern="\d{5}" maxlength="5" minlength="5" inputmode="numeric"
                               placeholder="<?= $isEditMode ? 'Laisser vide pour inchangé' : '•••••' ?>"
                               <?= $isEditMode ? '' : 'required' ?>>
                    </div>
                </div>

                <div class="form-group-row">
                    <div class="form-control">
                        <label for="idLocalAssigne">ID Local Assigné (Optionnel)</label>
                        <input type="number" id="idLocalAssigne" name="idLocalAssigne" placeholder="Ex: 1" value="<?= htmlspecialchars($form_idLocal) ?>">
                    </div>
                    <div class="form-control">
                        <label for="statut">Statut du Compte *</label>
                        <select id="statut" name="statut" required>
                            <option value="1" <?= ($form_statut === 1) ? 'selected' : '' ?>>Actif (Autorisé)</option>
                            <option value="0" <?= ($form_statut === 0) ? 'selected' : '' ?>>Inactif (Bloqué)</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa-solid <?= $isEditMode ? 'fa-floppy-disk' : 'fa-user-shield' ?>"></i>
                    <?= $isEditMode ? "Enregistrer les modifications" : "Valider la création du compte" ?>
                </button>

                <?php if ($isEditMode): ?>
                    <a href="gestion_utilisateurs.php" class="btn btn-block"
                       style="text-align:center;margin-top:10px;display:block;text-decoration:none;background:#6c757d;color:white;padding:10px;border-radius:4px;">
                        <i class="fa-solid fa-xmark"></i> Annuler la modification
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="users-card card-table">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <h2 style="margin:0;">
                    <i class="fa-solid fa-address-book"></i> Registre Actif des Agents
                    <span style="font-size:0.8rem;color:#64748b;font-weight:400;margin-left:8px;">
                        (<?= count($agents) ?> agent<?= count($agents) > 1 ? 's' : '' ?>)
                    </span>
                </h2>
                <button type="button" onclick="window.print();" class="btn-print"
                        style="background:#0d6efd;color:white;border:none;padding:6px 14px;border-radius:4px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-print"></i> Imprimer
                </button>
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
                <h2 style="text-align:center;margin-top:20px;font-size:18pt;">Registre Officiel du Personnel Assigné</h2>
            </div>

            <div class="table-scroll">
                <table class="steg-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom &amp; Prénom</th>
                            <th>Rôle</th>
                            <th>Email</th>
                            <th>Statut</th>
                            <th>Affectation</th>
                            <th class="btn-action">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agents as $agent): ?>
                        <tr>
                            <td class="cell-user-id"><?= str_pad($agent['idUtilisateur'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td><strong><?= htmlspecialchars($agent['nom']) ?></strong></td>
                            <td>
                                <span class="badge-role-type role-<?= strtolower($agent['labelProfil'] ?? 'agent') ?>">
                                    <?= htmlspecialchars($agent['labelProfil'] ?? 'N/A') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($agent['email']) ?></td>
                            <td>
                                <?php if (intval($agent['statut']) === 1): ?>
                                    <span class="badge-status status-active"><i class="fa-solid fa-circle-check"></i> Actif</span>
                                <?php else: ?>
                                    <span class="badge-status status-inactive"><i class="fa-solid fa-circle-xmark"></i> Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($agent['idLocalAssigne'] !== null && $agent['idLocalAssigne'] !== ''): ?>
                                    <span class="badge-loc-id"><i class="fa-solid fa-location-arrow"></i> Zone <?= htmlspecialchars($agent['idLocalAssigne']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Aucune</span>
                                <?php endif; ?>
                            </td>
                            <td class="btn-action" style="white-space:nowrap;">
                                <a href="gestion_utilisateurs.php?edit=<?= $agent['idUtilisateur'] ?>"
                                   title="Modifier" style="color:#ffc107;margin-right:10px;font-size:1.1rem;">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="gestion_utilisateurs.php?delete=<?= $agent['idUtilisateur'] ?>"
                                   title="Supprimer"
                                   onclick="return confirm('Supprimer définitivement « <?= htmlspecialchars(addslashes($agent['nom'])) ?> » ?');"
                                   style="color:#dc3545;font-size:1.1rem;">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>