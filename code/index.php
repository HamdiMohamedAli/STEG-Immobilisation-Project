<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['idUtilisateur']) && !empty($_SESSION['idUtilisateur'])) {
    header('Location: views/dashboard.php');
    exit();
} else {
    header('Location: views/login.php');
    exit();
}