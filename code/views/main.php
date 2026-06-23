<?php
require_once '../includes/header.php';
require_once '../includes/navbar.php';

if (isset($_SESSION['idProfil'])) {
    switch ($_SESSION['idProfil']) {
        case '1':
        case '2':
        case '3':
        case '4':
        case '5':
            header("Location: dashboard.php");
            exit();

        case '6':
            header("Location: gestion_plan.php");
            exit();
            
        default:
            header("Location: login.php");
            exit();
    }
} else {
    header("Location: login.php");
    exit();
}

require_once '../includes/footer.php';
?>