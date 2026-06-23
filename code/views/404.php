<?php
http_response_code(404);

require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="error-wrapper">
    <div class="error-container">
        <div class="error-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h1 class="error-title">404</h1>
        <h2 class="error-subtitle">Page introuvable ou en développement</h2>
        <p class="error-text">
            Désolé, la ressource ou le module de gestion de la <strong>STEG</strong> auquel vous tentez d'accéder n'est pas encore disponible ou a été déplacé.
        </p>
        <div class="error-actions">
            <a href="main.php" class="btn btn-primary">
                <i class="fa-solid fa-house"></i> Retour au Tableau de Bord
            </a>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>