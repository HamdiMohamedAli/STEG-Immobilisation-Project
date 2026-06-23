<?php
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="main-wrapper">

    <div class="sidebar">
        <h3>Sélectionner un Diagramme :</h3>
        <ul>
            <li class="menu-category"><strong>Diagramme de Cas d'Utilisation</strong></li>
            <li><a href="../../assets/use_case_diagram.png" class="uml-link active">Diagramme de Cas d'Utilisation</a></li>

            <li class="menu-category"><strong>Diagrammes de Classes</strong></li>
            <li><a href="../../assets/class_diagram.png" class="uml-link">Diagramme de Classes</a></li>
            
            <li class="menu-category"><strong>Diagrammes de Séquence</strong></li>
            <li><a href="../../assets/seq_auth.png" class="uml-link">Séquence : S'authentifier par profil</a></li>
            <li><a href="../../assets/seq_admin.png" class="uml-link">Séquence : Gérer les utilisateurs & profils</a></li>
            <li><a href="../../assets/seq_voir_plan.png" class="uml-link">Séquence : Visualiser le plan</a></li>
            <li><a href="../../assets/seq_crud.png" class="uml-link">Séquence : Enregistrement en magasin (CRUD)</a></li>
            <li><a href="../../assets/seq_drag_drop.png" class="uml-link">Séquence : Placement des éléments (Drag & Drop)</a></li>
            <li><a href="../../assets/seq_alertes.png" class="uml-link">Séquence : Déclaration et routage des pannes</a></li>
            <li><a href="../../assets/seq_pdf.png" class="uml-link">Séquence : Génération de rapports PDF</a></li>
        </ul>
    </div>

    <div class="uml-viewport">
        <div class="zoom-controls">
            <button id="zoom-in" title="Zoom In">➕</button>
            <button id="zoom-out" title="Zoom Out">➖</button>
            <button id="zoom-reset" title="Reset View">🔄</button>
        </div>

        <div class="uml-zoom-container" id="zoom-container">
            <img src="../../assets/use_case_diagram.png" id="uml-display-image" alt="UML Diagram">
        </div>
    </div>

</div>

<?php
require_once '../includes/footer.php';
?>