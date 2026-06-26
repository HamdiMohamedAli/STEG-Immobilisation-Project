<?php
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<style>
    .main-wrapper {
        display: flex;
        height: calc(100vh - 60px); 
        font-family: system-ui, -apple-system, sans-serif;
        background-color: #0f172a; 
        overflow: hidden;
    }

    .sidebar {
        width: 320px;
        background: #1e293b; 
        border-right: 1px solid #334155;
        padding: 20px;
        overflow-y: auto;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.2);
    }

    .sidebar h3 {
        font-size: 1.1rem;
        color: #f8fafc;
        border-bottom: 2px solid #334155;
    }

    .sidebar ul {
        overflow: hidden;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .menu-category {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #94a3b8;
        margin: 20px 0 8px 0;
    }

    .sidebar ul li a {
        display: block;
        padding: 10px 14px;
        color: #cbd5e1;
        text-decoration: none;
        border-radius: 6px;
        margin-bottom: 4px;
        font-size: 0.95rem;
        transition: all 0.2s ease;
    }

    .sidebar ul li a:hover {
        background-color: #334155;
        color: #38bdf8;
    }

    .sidebar ul li a.active {
        background-color: #0369a1;
        color: #ffffff;
        font-weight: 600;
    }

    .uml-viewport {
        position: relative;
        flex: 1;
        background-color: #0f172a; 
        overflow: hidden;
        cursor: grab;
    }

    .uml-viewport:active {
        cursor: grabbing;
    }

    .zoom-controls {
        position: absolute;
        bottom: 25px; 
        left: 50%;
        transform: translateX(-50%); 
        display: flex;
        flex-direction: row; 
        gap: 8px; 
        z-index: 10;
        background: rgba(30, 41, 59, 0.85); 
        padding: 6px; 
        width: max-content; 
        border-radius: 30px; 
        border: 1px solid rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
    }

    .zoom-controls button {
        background: #334155;
        border: 1px solid #475569;
        border-radius: 50%; 
        width: 36px; 
        height: 36px;
        font-size: 0.95rem;
        color: #f8fafc;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        user-select: none;
    }

    .zoom-controls button:hover {
        background: #475569;
        border-color: #64748b;
        transform: scale(1.08);
    }

    .zoom-controls button:active {
        transform: scale(0.92);
        background: #1e293b;
    }

    /* Outer Container Centering Rules */
    .uml-outer-container {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding-bottom: 60px; 
    }

    /* Zoom Container */
    .uml-zoom-container {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px; 
        transform-origin: center center;
        will-change: transform;
    }

    #uml-display-image {
        max-width: 85vw;
        max-height: 72vh; 
        object-fit: contain;
        background: #ffffff; 
        padding: 24px; 
        border-radius: 8px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        user-select: none;
        pointer-events: none; 
    }
</style>

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
            <li><a href="../../assets/seq_crud_universal.png" class="uml-link">Séquence : Enregistrement des données (CRUD)</a></li>
            <li><a href="../../assets/seq_magasin.png" class="uml-link">Séquence : Gestion du magasin</a></li>
            <li><a href="../../assets/seq_voir_plan.png" class="uml-link">Séquence : Visualiser le plan</a></li>
            <li><a href="../../assets/seq_drag_drop.png" class="uml-link">Séquence : Placement des éléments (Drag & Drop)</a></li>
            <li><a href="../../assets/seq_alertes.png" class="uml-link">Séquence : Déclaration et routage des pannes</a></li>
            <li><a href="../../assets/seq_pdf.png" class="uml-link">Séquence : Génération de rapports PDF</a></li>
        </ul>
    </div>

    <div class="uml-viewport" id="viewport">
        <div class="uml-outer-container">
            <div class="uml-zoom-container" id="zoom-container">
                <img src="../../assets/use_case_diagram.png" id="uml-display-image" alt="Diagramme de Cas d'Utilisation">
            </div>
        </div>

        <div class="zoom-controls">
            <button id="zoom-in" title="Zoom In">➕</button>
            <button id="zoom-out" title="Zoom Out">➖</button>
            <button id="zoom-reset" title="Reset View">🔄</button>
        </div>
    </div>

</div>

<script src="script.js"></script>

<?php
require_once '../includes/footer.php';
?>