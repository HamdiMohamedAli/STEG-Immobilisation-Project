<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['idUtilisateur'])) {
    header('Location: main.php');
    exit();
}

require_once '../config/db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idUtilisateur = trim($_POST['idUtilisateur']);
    $password      = trim($_POST['password']);

    if (preg_match('/^\d{4}$/', $idUtilisateur) && preg_match('/^\d{4}$/', $password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE idUtilisateur = ?");
            $stmt->execute([$idUtilisateur]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($utilisateur && password_verify($password, $utilisateur['motDePasse'])) {
                
                // MODIFICATION : Vérification du statut du compte (1 = Actif)
                if ((int)$utilisateur['statut'] !== 1) {
                    try {
                        $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
                        $stmtLog->execute([
                            $utilisateur['idUtilisateur'],
                            "Tentative de connexion sur un compte bloqué",
                            "ERROR",
                            "Utilisateur",
                            "ID: {$utilisateur['idUtilisateur']} | Nom: {$utilisateur['nom']} | Statut: Inactif"
                        ]);
                    } catch (PDOException $e) {}

                    $error = "Votre compte est inactif ou a été suspendu. Veuillez contacter votre administrateur.";
                } else {
                    // Le compte est actif, on procède à la connexion
                    $stmt = $pdo->prepare("SELECT * FROM profils WHERE idProfil = ?");
                    $stmt->execute([$utilisateur['idProfil']]);
                    $profil = $stmt->fetch(PDO::FETCH_ASSOC);

                    $_SESSION['idUtilisateur']     = $utilisateur['idUtilisateur'];
                    $_SESSION['nom']               = $utilisateur['nom'];
                    $_SESSION['idProfil']          = $utilisateur['idProfil'];
                    $_SESSION['labelProfil']       = $profil ? $profil['labelProfil'] : 'Visiteur';
                    $_SESSION['idLocalDesignated'] = $utilisateur['idLocalAssigne'];

                    try {
                        $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
                        $stmtLog->execute([
                            $_SESSION['idUtilisateur'],
                            "Connexion au système",
                            "LOGIN",
                            "Utilisateur",
                            "ID: {$_SESSION['idUtilisateur']} | Nom: {$_SESSION['nom']} | Rôle: {$_SESSION['labelProfil']}"
                        ]);
                    } catch (PDOException $e) {
                        // Log failure is non-blocking
                    }

                    header('Location: main.php');
                    exit();
                }

            } else {
                // Log failed attempt (wrong password or unknown ID)
                try {
                    $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
                    $stmtLog->execute([
                        0,
                        "Tentative de connexion échouée",
                        "ERROR",
                        "Utilisateur",
                        "ID saisi: $idUtilisateur"
                    ]);
                } catch (PDOException $e) {}

                $error = "Identifiant (ID) ou mot de passe incorrect.";
            }
        } catch (PDOException $e) {
            $error = "Une erreur serveur est survenue. Veuillez réessayer plus tard.";
        }
    } else {
        $error = "L'identifiant (ID) et le mot de passe doivent comporter exactement 4 chiffres.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STEG - Connexion</title>
    <link href="../css/style.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-header">
            <h2>STEG</h2>
            <p>Gestion des Immobilisations</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="login-form">
            <div class="form-group">
                <label for="idUtilisateur">Identifiant</label>
                <input type="text" id="idUtilisateur" name="idUtilisateur" required
                       pattern="\d{4}" maxlength="4" minlength="4"
                       inputmode="numeric" placeholder="Ex: 0000">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required
                       pattern="\d{4}" maxlength="4" minlength="4"
                       inputmode="numeric" placeholder="••••">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </form>
    </div>
</body>
</html>