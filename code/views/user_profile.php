<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['idProfil'])) {
    header('Location: login.php');
    exit();
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';
require_once '../config/db.php';

$message = '';
$error = '';

$idUtilisateur = isset($_SESSION['idUtilisateur']) ? (int)$_SESSION['idUtilisateur'] : 0;
$idProfilSession = isset($_SESSION['idProfil']) ? (int)$_SESSION['idProfil'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_phone'])) {
    $new_phone = trim($_POST['tel']);
    
    if (!empty($new_phone)) {
        try {
            $stmtUpdate = $pdo->prepare("UPDATE users SET tel = ? WHERE idUtilisateur = ?");
            $stmtUpdate->execute([$new_phone, $idUtilisateur]);

            $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
            $stmtLog->execute([
                $idUtilisateur,
                "Modification du numéro de téléphone : $new_phone",
                "UPDATE",
                "Utilisateur",
                "ID: " . str_pad($idUtilisateur, 5, '0', STR_PAD_LEFT)
            ]);

            $message = "Votre numéro de téléphone a été mis à jour avec succès.";
        } catch (PDOException $e) {
            $error = "Une erreur est survenue lors de la mise à jour du téléphone.";
        }
    } else {
        $error = "Le numéro de téléphone ne peut pas être vide.";
    }
}

try {
    $queryUser = "SELECT u.*, p.labelProfil, l.labelLocal 
                  FROM users u 
                  LEFT JOIN profils p ON u.idProfil = p.idProfil
                  LEFT JOIN locaux l ON u.idLocalAssigne = l.idLocal
                  WHERE u.idUtilisateur = ?";
    $stmtUser = $pdo->prepare($queryUser);
    $stmtUser->execute([$idUtilisateur]);
    $userInfos = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$userInfos) {
        $error = "Impossible de récupérer les informations de votre profil.";
    }
} catch (PDOException $e) {
    $error = "Erreur de connexion à la base de données lors du chargement du profil.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    if ($idProfilSession !== 1) {
        try {
            $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
            $stmtLog->execute([
                $idUtilisateur,
                "Demande de réinitialisation de mot de passe soumise à l'administrateur (Format attendu : SHA2-256)",
                "REQUEST",
                "Utilisateur",
                "ID: " . str_pad($idUtilisateur, 5, '0', STR_PAD_LEFT) . " | Nom: " . ($userInfos['nom'] ?? 'Inconnu')
            ]);

            $message = "Votre demande de réinitialisation de mot de passe a bien été envoyée à l'administrateur.";
        } catch (PDOException $e) {
            $error = "Une erreur est survenue lors de l'enregistrement de la demande. Veuillez réessayer.";
        }
    } else {
        $error = "Action non autorisée pour ce profil.";
    }
}
?>

<style>
    .profile-wrapper {
        padding: 20px;
        max-width: 700px;
        margin: 0 auto;
    }
    .profile-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        padding: 30px;
        margin-top: 20px;
        border: 1px solid #e2e8f0;
    }
    .profile-info {
        margin-bottom: 25px;
    }
    .info-group {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 10px;
    }
    .info-label {
        font-weight: 600;
        color: #475569;
    }
    .info-value {
        color: #1e293b;
        font-weight: 500;
    }
    .profile-header-title {
        color: #1e293b;
        font-size: 1.5rem;
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .btn-reset-password {
        background-color: #dc2626;
        color: #fff;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        transition: background-color 0.2s ease;
    }
    .btn-reset-password:hover {
        background-color: #b91c1c;
    }
    .phone-form {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .phone-input {
        padding: 4px 8px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        font-size: 0.95rem;
        color: #1e293b;
        font-weight: 500;
        width: 150px;
    }
    .btn-update-phone {
        background-color: #0284c7;
        color: #fff;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 600;
        transition: background-color 0.2s;
    }
    .btn-update-phone:hover {
        background-color: #0369a1;
    }
</style>

<div class="profile-wrapper">
    <div class="profile-card">
        <div class="profile-header" style="margin-bottom: 25px;">
            <h2 class="profile-header-title">
                <i class="fa-solid fa-user" style="color: #0284c7;"></i> Mon Profil
            </h2>
            <p style="color: #64748b; margin: 0; font-size: 0.9rem;">STEG — Gestion des Immobilisations & Logistique</p>
        </div>

        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 25px;">

        <?php if (!empty($message)): ?>
            <div class="alert alert-success" style="background-color: #dcfce7; color: #15803d; padding: 12px; border-radius: 6px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-circle-check"></i>
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="background-color: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($userInfos): ?>
        <div class="profile-info">
            <div class="info-group">
                <span class="info-label"><i class="fa-solid fa-hashtag" style="width: 20px;"></i> Identifiant</span> 
                <span class="info-value"><?= htmlspecialchars(str_pad($userInfos['idUtilisateur'], 5, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="info-group">
                <span class="info-label"><i class="fa-solid fa-id-card" style="width: 20px;"></i> Nom Complet</span> 
                <span class="info-value"><?= htmlspecialchars($userInfos['nom'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="info-group">
                <span class="info-label"><i class="fa-solid fa-envelope" style="width: 20px;"></i> Email</span> 
                <span class="info-value"><?= htmlspecialchars($userInfos['email'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="info-group">
                <span class="info-label"><i class="fa-solid fa-phone" style="width: 20px;"></i> Téléphone</span> 
                <span class="info-value">
                    <form action="user_profile.php" method="POST" class="phone-form">
                        <input type="hidden" name="update_phone" value="1">
                        <input type="text" name="tel" class="phone-input" value="<?= htmlspecialchars($userInfos['tel'], ENT_QUOTES, 'UTF-8') ?>" required>
                        <button type="submit" class="btn-update-phone"><i class="fa-solid fa-floppy-disk"></i> Enregistrer</button>
                    </form>
                </span>
            </div>
            <div class="info-group">
                <span class="info-label"><i class="fa-solid fa-user-shield" style="width: 20px;"></i> Rôle / Profil</span> 
                <span class="info-value" style="background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">
                    <?= htmlspecialchars($userInfos['labelProfil'] ?? 'Non défini', ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <div class="info-group">
                <span class="info-label"><i class="fa-solid fa-location-dot" style="width: 20px;"></i> Local Assigné</span> 
                <span class="info-value"><?= htmlspecialchars($userInfos['labelLocal'] ?? 'Aucun local assigné', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>

        <?php if ($idProfilSession !== 1): ?>
        <div style="background: #fff7ed; border-left: 4px solid #ea580c; padding: 15px; border-radius: 0 6px 6px 0; margin-top: 30px;">
            <p style="font-size: 0.88em; color: #9a3412; margin: 0; line-height: 1.5; text-align: center;">
                <i class="fa-solid fa-key" style="margin-right: 5px;"></i> 
                Besoin de modifier votre mot de passe ? <br> Cliquez sur le bouton ci-dessous pour envoyer une alerte de réinitialisation à votre administrateur.
            </p>
            
            <form action="user_profile.php" method="POST" style="margin-top: 15px; display: flex; justify-content: center;">
                <input type="hidden" name="request_reset" value="1">
                <button type="submit" class="btn-reset-password">
                    <i class="fa-solid fa-paper-plane"></i> Demander un changement de mot de passe
                </button>
            </form>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>