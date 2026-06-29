<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

if (isset($_SESSION['idUtilisateur'])) {
    try {
        $idInt = (int)$_SESSION['idUtilisateur'];

        $stmtLog = $pdo->prepare("INSERT INTO journal_logs (idUtilisateur, action, typeAction, cibleEntite, idCible) VALUES (?, ?, ?, ?, ?)");
        $stmtLog->execute([
            $idInt,
            "Déconnexion du système",
            "LOGOUT",
            "Utilisateur",
            "ID: {$_SESSION['idUtilisateur']} | Nom: {$_SESSION['nom']} | Rôle: {$_SESSION['labelProfil']}"
        ]);
    } catch (PDOException $e) {
    }
}

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

header("Location: login.php");
exit();
?>