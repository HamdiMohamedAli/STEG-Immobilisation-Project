<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['idUtilisateur'])) {
    header('Location: login.php');
    exit();
}

$userIdFormate = str_pad($_SESSION['idUtilisateur'], 5, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STEG - Gestion des Immobilisations</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../js/script.js" defer></script>
</head>
<body class="dashboard-body">
<div class="app-layout">