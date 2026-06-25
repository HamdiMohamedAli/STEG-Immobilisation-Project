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

    // Validation stricte : exactement 5 chiffres
    if (preg_match('/^\d{5}$/', $idUtilisateur) && preg_match('/^\d{5}$/', $password)) {
        try {
            // Conversion en entier pour matcher l'ID numérique de MySQL (qui ignore les zéros à gauche à la recherche)
            $idInt = (int)$idUtilisateur;

            $stmt = $pdo->prepare("SELECT * FROM users WHERE idUtilisateur = ?");
            $stmt->execute([$idInt]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($utilisateur) {
                // Génération du hash PHP en minuscules
                $hashSaisi = strtolower(hash('sha256', $password));
                
                // CORRECTION FINALE : On force la comparaison en minuscules pour ignorer les caprices de casse de SHA2() selon les serveurs
                if (hash_equals(strtolower($utilisateur['motDePasse']), $hashSaisi)) {
                    
                    // Vérification du statut du compte
                    if ((int)$utilisateur['statut'] !== 1) {
                        try {
                            $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
                            $stmtLog->execute([
                                $idInt,
                                "Tentative de connexion sur un compte bloqué",
                                "ERROR",
                                "Utilisateur",
                                "ID: {$idUtilisateur} | Nom: {$utilisateur['nom']} | Statut: Inactif"
                            ]);
                        } catch (PDOException $e) {}

                        $error = "Votre compte est inactif ou a été suspendu. Veuillez contacter votre administrateur.";
                    } else {
                        // Récupération du profil
                        $stmtProfil = $pdo->prepare("SELECT * FROM profils WHERE idProfil = ?");
                        $stmtProfil->execute([$utilisateur['idProfil']]);
                        $profil = $stmtProfil->fetch(PDO::FETCH_ASSOC);

                        // Stockage propre en session (Réintroduction des zéros à gauche pour l'affichage)
                        $_SESSION['idUtilisateur']     = str_pad($utilisateur['idUtilisateur'], 5, '0', STR_PAD_LEFT);
                        $_SESSION['nom']               = $utilisateur['nom'];
                        $_SESSION['idProfil']          = $utilisateur['idProfil'];
                        $_SESSION['labelProfil']       = $profil ? $profil['labelProfil'] : 'Visiteur';
                        $_SESSION['idLocalDesignated'] = $utilisateur['idLocalAssigne'];

                        // Log de connexion réussie
                        try {
                            $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
                            $stmtLog->execute([
                                $idInt,
                                "Connexion au système",
                                "LOGIN",
                                "Utilisateur",
                                "ID: {$_SESSION['idUtilisateur']} | Nom: {$_SESSION['nom']} | Rôle: {$_SESSION['labelProfil']}"
                            ]);
                        } catch (PDOException $e) {}

                        header('Location: main.php');
                        exit();
                    }
                } else {
                    $error = "Identifiant (ID) ou mot de passe incorrect.";
                }
            } else {
                $error = "Identifiant (ID) ou mot de passe incorrect.";
            }

            // Log en cas d'échec d'authentification
            if (!empty($error) && $error === "Identifiant (ID) ou mot de passe incorrect.") {
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
            }

        } catch (PDOException $e) {
            $error = "Une erreur serveur est survenue. Veuillez réessayer plus tard.";
        }
    } else {
        $error = "L'identifiant (ID) et le mot de passe doivent comporter exactement 5 chiffres.";
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
                       pattern="\d{5}" maxlength="5" minlength="5"
                       inputmode="numeric" placeholder="Ex: 00000">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required
                       pattern="\d{5}" maxlength="5" minlength="5"
                       inputmode="numeric" placeholder="•••••">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </form>
    </div>
</body>
</html>