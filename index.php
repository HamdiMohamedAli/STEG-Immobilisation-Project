<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['idUtilisateur']) && !empty($_SESSION['idUtilisateur'])) {
    header('Location: code/views/dashboard.php');
    exit();
} else {
    header('Location: code/views/login.php');
    exit();
}