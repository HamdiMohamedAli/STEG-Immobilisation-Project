const allowedProfilsForLocaux = [1, 2, 3];
const currentFloor = "<?= htmlspecialchars($floor) ?>";
const canMergeCells = "<?= $canMergeCells ? 'true' : 'false' ?>";

document.addEventListener('DOMContentLoaded', () => {
    initPrintButton();
    initGridGestures();

    // ==========================================
    // INITIALISATION ET FILTRES DU CATALOGUE (plan.php)
    // ==========================================
    const typeField = document.getElementById('typeEntite');
    const currentId = document.getElementById('idCible');
    const searchInput = document.getElementById('search-material');

    if (typeField && typeField.value !== "") {
        setupDatalistOptions(typeField.value);

        if (currentId && currentId.value !== '0') {
            const list = (typeField.value === 'Equipement') ? window.rawEquipements : window.rawImmobilisations;
            const idKey = (typeField.value === 'Equipement') ? 'idEquip' : 'idImmo';
            const found = list.find(item => item[idKey] == currentId.value);
            if (found && searchInput) {
                searchInput.value = found.libelle;
            }
        }
    }

    const catalogSearch = document.getElementById('catalogSearch');
    const typeLocalFilter = document.getElementById('typeLocalFilter');
    
    if (catalogSearch) catalogSearch.addEventListener('input', applyCatalogFilters);
    if (typeLocalFilter) typeLocalFilter.addEventListener('change', applyCatalogFilters);

    // ==========================================
    // LOGIQUE DE GLISSER-DÉPOSER DES ÉLÉMENTS (plan.php)
    // ==========================================
    const dragItems = document.querySelectorAll('.drag-item');
    dragItems.forEach(item => {
        item.addEventListener('dragstart', function(e) {
            const dragData = {
                id: this.getAttribute('data-id'),
                label: this.getAttribute('data-label'),
                type: this.getAttribute('data-type')
            };
            e.dataTransfer.setData('text/plain', JSON.stringify(dragData));
            this.classList.add('dragging');
        });

        item.addEventListener('dragend', function() {
            this.classList.remove('dragging');
        });
    });

    let isMergeMode = false;
    let isUnmergeMode = false;
    let selectedCells = [];

    const gridItems = document.querySelectorAll('.grid-item');
    const btnMergeCells = document.getElementById('btnMergeCells');
    const btnUnmergeMode = document.getElementById('btnMenuUnmergeMode');

    gridItems.forEach(cell => {
        const frontContent = cell.querySelector('.front-cell-content');
        
        cell.addEventListener('dragover', function(e) {
            if (!this.querySelector('.placed-element') && !isMergeMode && !isUnmergeMode) {
                e.preventDefault();
                this.classList.add('drag-hover');
            }
        });

        cell.addEventListener('dragleave', function() {
            this.classList.remove('drag-hover');
        });

        cell.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-hover');

            try {
                const data = JSON.parse(e.dataTransfer.getData('text/plain'));

                if (data.type === 'Local' && !allowedProfilsForLocaux.includes(Number(window.currentUserProfilId))) {
                    showToast("Action refusée : Votre profil ne vous permet pas de placement de locaux.", true);
                    return;
                }

                const colIdx = this.getAttribute('data-col');
                const rowIdx = this.getAttribute('data-row');

                const formData = new FormData();
                formData.append('action_position', '1');
                formData.append('col_idx', colIdx);
                formData.append('row_idx', rowIdx);
                formData.append('current_floor', currentFloor);
                
                if (data.type === 'Local') {
                    formData.append('id_local', data.id);
                } else if (data.type === 'Voiture') {
                    formData.append('matricule_voiture', data.id); 
                } else {
                    formData.append('id_magasin', data.id);
                }

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(result => {
                    if (result.status === 'success') {
                        showToast(result.message);
                        if (frontContent) {
                            frontContent.innerHTML = `
                                <div class="placed-element type-${data.type}" data-placed-id="${data.id}" data-placed-type="${data.type}">
                                    ${data.label}
                                    <button type="button" class="delete-item-trigger" title="Supprimer l'objet">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            `;
                            attachDeleteEvent(frontContent.querySelector('.delete-item-trigger'));
                        }
                        
                        const stockBadge = document.querySelector(`.badge-stock[data-stock-id="${data.id}"]`);
                        if (stockBadge) {
                            let currentStock = parseInt(stockBadge.textContent.replace('x', '')) || 0;
                            let newStock = Math.max(0, currentStock - 1);
                            stockBadge.textContent = 'x' + newStock;
                            
                            if (newStock === 0) {
                                const wrapper = stockBadge.closest('.catalog-item-wrapper');
                                if (wrapper) {
                                    wrapper.remove();
                                    checkEmptyCatalog();
                                }
                            }
                        }
                    } else {
                        showToast(result.message, true);
                    }
                })
                .catch(() => showToast("Erreur de communication réseau.", true));

            } catch (err) {
                showToast("Échec de l'analyse des métadonnées de l'objet.", true);
            }
        });

        // ==========================================
        // GESTION DU CLIC : SÉLECTION OU FUSION CELLS (plan.php)
        // ==========================================
        cell.addEventListener('click', function(e) {
            if (e.target.closest('.delete-item-trigger')) return;

            if (!isMergeMode && !isUnmergeMode) {
                this.classList.toggle('selected-layer');
            } else if (isMergeMode) {
                if (this.querySelector('.placed-element')) {
                    showToast("Impossible de fusionner une cellule contenant un objet !", true);
                    return;
                }
                
                if (selectedCells.includes(this)) {
                    this.style.outline = '';
                    this.style.backgroundColor = '';
                    selectedCells = selectedCells.filter(c => c !== this);
                } else {
                    this.style.outline = '2px dashed #38bdf8';
                    this.style.backgroundColor = 'rgba(56, 189, 248, 0.15)';
                    selectedCells.push(this);
                }

                if (selectedCells.length >= 2) {
                    processMergeAction(selectedCells, () => {
                        selectedCells.forEach(c => {
                            c.style.outline = '';
                            c.style.backgroundColor = '';
                        });
                        selectedCells = [];
                    });
                }
            } else if (isUnmergeMode) {
                const mergedZone = this.querySelector('.custom-merged-zone');
                if (mergedZone || this.style.gridArea) {
                    processUnmergeAction(this);
                } else {
                    showToast("Cette cellule n'est pas fusionnée.", true);
                }
            }
        });
    });

    document.querySelectorAll('.delete-item-trigger').forEach(btn => attachDeleteEvent(btn));

    if (btnMergeCells) {
        btnMergeCells.addEventListener('click', function() {
            isMergeMode = !isMergeMode;
            isUnmergeMode = false;
            selectedCells.forEach(c => {
                c.style.outline = '';
                c.style.backgroundColor = '';
            });
            selectedCells = [];
            
            if (isMergeMode) {
                this.style.background = '#0284c7';
                if (btnUnmergeMode) btnUnmergeMode.style.background = '';
                showToast("Mode fusion activé. Cliquez sur les cellules pour configurer votre zone.");
            } else {
                this.style.background = '';
            }
        });
    }

    if (btnUnmergeMode) {
        btnUnmergeMode.addEventListener('click', function() {
            isUnmergeMode = !isUnmergeMode;
            isMergeMode = false;
            selectedCells.forEach(c => {
                c.style.outline = '';
                c.style.backgroundColor = '';
            });
            selectedCells = [];

            if (isUnmergeMode) {
                this.style.background = '#dc2626';
                if (btnMergeCells) btnMergeCells.style.background = '';
                showToast("Mode dissociation activé. Sélectionnez la cellule fusionnée à diviser.");
            } else {
                this.style.background = '';
            }
        });
    }

    // ==========================================
    // LOGIQUE EXCLUSIVE À LA PAGE : uml.php
    // ==========================================
    const displayImage = document.getElementById('uml-display-image');
    if (displayImage) {
        const links = document.querySelectorAll('.uml-link');
        const zoomContainer = document.getElementById('zoom-container');
        const viewport = document.getElementById('viewport');
        const btnIn = document.getElementById('zoom-in');
        const btnOut = document.getElementById('zoom-out');
        const btnReset = document.getElementById('zoom-reset');

        let umlScale = 1;
        let translateUmlX = 0;
        let translateUmlY = 0;

        function updateUmlTransform() {
            if(zoomContainer) {
                zoomContainer.style.transform = `translate(${translateUmlX}px, ${translateUmlY}px) scale(${umlScale})`;
            }
        }

        function resetUmlView() {
            umlScale = 1; translateUmlX = 0; translateUmlY = 0;
            if(zoomContainer) {
                zoomContainer.style.transition = 'none'; 
                updateUmlTransform();
                setTimeout(() => { zoomContainer.style.transition = 'transform 0.15s ease-out'; }, 50);
            }
        }

        links.forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                links.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                displayImage.src = this.getAttribute('href');
                displayImage.onload = () => resetUmlView();
            });
        });

        if (btnIn) btnIn.addEventListener('click', () => { umlScale = Math.min(4, umlScale + 0.2); updateUmlTransform(); });
        if (btnOut) btnOut.addEventListener('click', () => { umlScale = Math.max(0.4, umlScale - 0.2); updateUmlTransform(); });
        if (btnReset) btnReset.addEventListener('click', resetUmlView);
    }
});

// ==========================================
// NAVIGATION PAN & ZOOM DU PLAN EXCLUSIF (plan.php)
// ==========================================
function initGridGestures() {
    const stage = document.getElementById('gridStage');
    const grid = document.getElementById('frontGrid');
    
    // Protection contextuelle : Si nous ne sommes pas sur plan.php, on quitte immédiatement.
    if (!stage || !grid) return;

    // Ciblage des boutons uniquement s'ils appartiennent au conteneur du plan
    const btnIn = stage.parentElement.querySelector('#zoom-in') || document.getElementById('zoom-in');
    const btnOut = stage.parentElement.querySelector('#zoom-out') || document.getElementById('zoom-out');
    const btnReset = document.getElementById('btnResetView') || document.getElementById('zoom-reset');
    
    let scale = 1;
    let translateX = 0;
    let translateY = 0;
    let isPanning = false;
    let startX = 0;
    let startY = 0;

    const minScale = 0.4;
    const maxScale = 5;

    function applyTransform() {
        grid.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
    }

    // Capture explicite et forcée du zoom molette sur plan.php
    stage.addEventListener('wheel', (e) => {
        e.preventDefault();
        e.stopPropagation(); // Évite les conflits de propagation de fenêtres
        
        const zoomIntensity = 0.1;
        const previousScale = scale;
        
        if (e.deltaY < 0) {
            scale = Math.min(maxScale, scale + zoomIntensity);
        } else {
            scale = Math.max(minScale, scale - zoomIntensity);
        }

        const rect = stage.getBoundingClientRect();
        const mouseX = e.clientX - rect.left - rect.width / 2;
        const mouseY = e.clientY - rect.top - rect.height / 2;

        translateX -= mouseX * (scale / previousScale - 1);
        translateY -= mouseY * (scale / previousScale - 1);

        applyTransform();
    }, { passive: false });

    // Drag / Pan à la souris
    stage.addEventListener('mousedown', (e) => {
        if (e.target.closest('.delete-item-trigger') || e.target.closest('.placed-element') || e.target.closest('.front-cell-content span')) return;
        isPanning = true;
        startX = e.clientX - translateX;
        startY = e.clientY - translateY;
    });

    window.addEventListener('mousemove', (e) => {
        if (!isPanning) return;
        translateX = e.clientX - startX;
        translateY = e.clientY - startY;
        applyTransform();
    });

    window.addEventListener('mouseup', () => {
        isPanning = false;
    });

    // Liaison sécurisée des boutons de zoom pour l'espace plan.php
    if (btnIn) {
        btnIn.addEventListener('click', (e) => {
            if (document.getElementById('gridStage')) { // Double vérification de présence de la grille
                scale = Math.min(maxScale, scale + 0.2);
                applyTransform();
            }
        });
    }

    if (btnOut) {
        btnOut.addEventListener('click', (e) => {
            if (document.getElementById('gridStage')) {
                scale = Math.max(minScale, scale - 0.2);
                applyTransform();
            }
        });
    }

    if (btnReset) {
        btnReset.addEventListener('click', () => {
            if (document.getElementById('gridStage')) {
                scale = 1;
                translateX = 0;
                translateY = 0;
                applyTransform();
                document.querySelectorAll('.grid-item').forEach(c => c.classList.remove('selected-layer'));
                showToast("Vue du plan réinitialisée.");
            }
        });
    }
}

// ==========================================
// FONCTIONS UTILITAIRES RESTANTES
// ==========================================
function checkEmptyCatalog() {
    const wrappers = document.querySelectorAll('.catalog-item-wrapper');
    const msg = document.getElementById('emptyCatalogMsg');
    
    document.querySelectorAll('.catalog-group').forEach(group => {
        const remainingItems = group.querySelectorAll('.catalog-item-wrapper');
        if (remainingItems.length === 0) group.remove();
    });

    if (wrappers.length === 0 && msg) msg.style.display = 'block';
}

function printDocumentWithTitle(customTitle) {
    const originalTitle = document.title; document.title = customTitle;
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'trigger_print_log=1'
    }).finally(() => { window.print(); setTimeout(() => { document.title = originalTitle; }, 500); });
}

function initPrintButton() {
    const printBtn = document.getElementById('btnPrintRegister');
    if (printBtn) {
        printBtn.onclick = function (e) {
            e.preventDefault(); printBtn.disabled = true;
            const today = new Date().toISOString().slice(0, 10);
            printDocumentWithTitle("Registre_Officiel_Personnel_STEG_" + today);
            setTimeout(() => { printBtn.disabled = false; }, 1000);
        };
    }
}

function setupDatalistOptions(type) {
    const datalist = document.getElementById('liste-articles'); if (!datalist) return;
    const items = (type === 'Equipement') ? window.rawEquipements : window.rawImmobilisations;
    const idKey = (type === 'Equipement') ? 'idEquip' : 'idImmo';
    let optionsHTML = '';
    items.forEach(item => { optionsHTML += `<option value="${escapeHtml(item.libelle)}" data-id="${item[idKey]}"></option>`; });
    datalist.innerHTML = optionsHTML;
}

function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function applyCatalogFilters() {
    const searchInput = document.getElementById('catalogSearch'), typeLocalFilter = document.getElementById('typeLocalFilter');
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '', filterType = typeLocalFilter ? typeLocalFilter.value : '';
    const items = document.querySelectorAll('#catalogContainer .drag-item, #catalogContainer li');

    items.forEach(item => {
        const itemLabel = (item.getAttribute('data-label') || item.textContent).toLowerCase();
        const itemType = item.getAttribute('data-type'), itemSubtype = item.getAttribute('data-type-local');
        const matchesSearch = itemLabel.includes(searchTerm);
        let matchesType = (filterType === '') ? true : (itemType === 'Local' && itemSubtype === filterType);
        item.style.display = (matchesSearch && matchesType) ? '' : 'none';
    });
}

function showToast(message, isError = false) {
    const toast = document.getElementById('toast-notification'); if (!toast) return;
    toast.textContent = message;
    toast.style.cssText = `position:fixed; bottom:20px; right:20px; z-index:9999; padding:12px 24px; border-radius:6px; color:white; font-size:13px; font-weight:500; display:block; box-shadow:0 4px 12px rgba(0,0,0,0.15); background:${isError ? '#ef4444' : '#10b981'}`;
    setTimeout(() => { toast.style.display = 'none'; }, 3500);
}

function attachDeleteEvent(btn) {
    if (!btn) return;
    btn.addEventListener('click', function (e) {
        e.stopImmediatePropagation();
        e.preventDefault();

        const cell = this.closest('.grid-item');
        const colIdx = cell.getAttribute('data-col');
        const rowIdx = cell.getAttribute('data-row');

        if (confirm(`Voulez-vous vraiment retirer cet objet de la case [Ligne ${rowIdx}, Colonne ${colIdx}] ?`)) {
            const formData = new FormData();
            formData.append('action_delete_item', '1');
            formData.append('col_idx', colIdx);
            formData.append('row_idx', rowIdx);
            formData.append('current_floor', currentFloor);

            fetch(window.location.href, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(result => {
                if (result.status === 'success') {
                    showToast(result.message);
                    window.location.reload();
                } else { showToast(result.message, true); }
            })
            .catch(() => showToast("Erreur de communication réseau.", true));
        }
    });
}

function processMergeAction(cells, callback) {
    let minRow = Infinity, maxRow = -Infinity, minCol = Infinity, maxCol = -Infinity;
    cells.forEach(cell => {
        const r = parseInt(cell.getAttribute('data-row')), c = parseInt(cell.getAttribute('data-col'));
        if (r < minRow) minRow = r; if (r > maxRow) maxRow = r;
        if (c < minCol) minCol = c; if (c > maxCol) maxCol = c;
    });

    if (cells.length !== (maxRow - minRow + 1) * (maxCol - minCol + 1)) {
        alert('La sélection doit former un rectangle parfait et continu sans cases vides manquantes.');
        if (callback) callback(); return;
    }

    const zoneName = prompt("Entrez un nom ou un label pour cet espace fusionné :");
    if (!zoneName || zoneName.trim() === "") { if (callback) callback(); return; }

    const finalName = zoneName.trim(), rowSpan = maxRow - minRow + 1, colSpan = maxCol - minCol + 1;
    const targetCell = cells.find(c => parseInt(c.getAttribute('data-row')) === minRow && parseInt(c.getAttribute('data-col')) === minCol);
    const frontContent = targetCell.querySelector('.front-cell-content');

    if (frontContent) {
        targetCell.setAttribute('data-rspan', rowSpan);
        targetCell.setAttribute('data-cspan', colSpan);
        frontContent.innerHTML = `<div class="custom-merged-zone"><span class="zone-title"></span><span style="font-size:10px; opacity:0.6;">(${rowSpan}x${colSpan})</span></div>`;
        frontContent.querySelector('.zone-title').textContent = finalName;
        targetCell.style.cssText = `grid-row: span ${rowSpan}; grid-column: span ${colSpan}; width:auto; height:auto;`;
    }

    cells.forEach(c => { if (c !== targetCell) c.style.display = 'none'; });
    if (callback) callback();
}

function processUnmergeAction(cell) {
    const row = parseInt(cell.getAttribute('data-row')), col = parseInt(cell.getAttribute('data-col'));
    const rSpan = parseInt(cell.getAttribute('data-rspan') || "1"), cSpan = parseInt(cell.getAttribute('data-cspan') || "1");
    const title = cell.querySelector('.zone-title') ? cell.querySelector('.zone-title').textContent : "Zone";

    if (confirm(`Désouder et restaurer les cellules de la zone "${title}" ?`)) {
        cell.style.cssText = ''; cell.removeAttribute('data-rspan'); cell.removeAttribute('data-cspan');
        
        const frontContent = cell.querySelector('.front-cell-content');
        if (frontContent) {
            frontContent.innerHTML = `<span>X:${(col - 1) * 50}</span><span>Y:${(row - 1) * 50}</span>`;
        }

        for (let r = row; r < row + rSpan; r++) {
            for (let c = col; c < col + cSpan; c++) {
                const latentCell = document.querySelector(`#frontGrid .grid-item[data-row='${r}'][data-col='${c}']`);
                if (latentCell) latentCell.style.display = '';
            }
        }
        if (document.getElementById('btnMenuUnmergeMode')) document.getElementById('btnMenuUnmergeMode').click();
    }
}