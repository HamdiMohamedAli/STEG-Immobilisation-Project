const allowedProfilsForLocaux = [1, 2, 3];

document.addEventListener('DOMContentLoaded', () => {
    initUMLViewer();
    initPrintButton();

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

    const form = document.querySelector('.steg-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const idCible = document.getElementById('idCible').value;
            if (!idCible || idCible === '0') {
                e.preventDefault();
                alert('Veuillez sélectionner un article valide depuis la liste déroulante.');
            }
        });
    }
});

function initUMLViewer() {
    const container = document.getElementById('zoom-container');
    const img = document.getElementById('uml-display-image');

    if (!container || !img) return;

    let scale = 1;
    const minScale = 0.5;
    const maxScale = 4;
    let isDragging = false;
    let startX, startY, translateX = 0, translateY = 0;

    function updateTransform() {
        img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
    }

    function resetZoom() {
        scale = 1;
        translateX = 0;
        translateY = 0;
        updateTransform();
    }

    document.querySelectorAll('.uml-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.uml-link').forEach(item => item.classList.remove('active'));
            this.classList.add('active');
            img.src = this.getAttribute('href');
            resetZoom();
        });
    });

    container.addEventListener('wheel', function(e) {
        e.preventDefault();
        const zoomFactor = 0.1;
        scale = e.deltaY < 0 
            ? Math.min(maxScale, scale + zoomFactor) 
            : Math.max(minScale, scale - zoomFactor);
        updateTransform();
    }, { passive: false });

    const zoomInBtn = document.getElementById('zoom-in');
    if (zoomInBtn) zoomInBtn.addEventListener('click', () => { scale = Math.min(maxScale, scale + 0.25); updateTransform(); });

    const zoomOutBtn = document.getElementById('zoom-out');
    if (zoomOutBtn) zoomOutBtn.addEventListener('click', () => { scale = Math.max(minScale, scale - 0.25); updateTransform(); });

    const zoomResetBtn = document.getElementById('zoom-reset');
    if (zoomResetBtn) zoomResetBtn.addEventListener('click', resetZoom);

    container.addEventListener('mousedown', function(e) {
        isDragging = true;
        container.style.cursor = 'grabbing';
        startX = e.clientX - translateX;
        startY = e.clientY - translateY;
    });

    window.addEventListener('mousemove', function(e) {
        if (!isDragging) return;
        translateX = e.clientX - startX;
        translateY = e.clientY - startY;
        updateTransform();
    });

    window.addEventListener('mouseup', () => {
        isDragging = false;
        container.style.cursor = 'grab';
    });
}

function prepareEdit(id, type, label) {
    document.getElementById('form-title').innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Modifier le local n°' + String(id).padStart(4, '0');
    document.getElementById('formAction').value = 'update';
    document.getElementById('formIdLocal').value = id;
    document.getElementById('idLocalDisplay').value = String(id).padStart(4, '0');
    document.getElementById('labelIdDisplay').innerText = "Identifiant (Modification active)";
    document.getElementById('typeLocal').value = type;
    document.getElementById('labelLocal').value = label;

    const btnSubmit = document.getElementById('btnSubmitForm');
    btnSubmit.className = "btn btn-warning btn-block";
    btnSubmit.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Mettre à jour';
    document.getElementById('btnCancelEdit').style.display = 'block';

    document.getElementById('localForm').scrollIntoView({ behavior: 'smooth' });
}

function resetFormToCreate(nextId) {
    document.getElementById('form-title').innerHTML = '<i class="fa-solid fa-square-plus"></i> Déclarer un nouveau local';
    document.getElementById('formAction').value = 'create';
    document.getElementById('formIdLocal').value = '';
    document.getElementById('idLocalDisplay').value = String(nextId).padStart(4, '0');
    document.getElementById('labelIdDisplay').innerText = "Identifiant (Automatique)";
    document.getElementById('localForm').reset();

    const btnSubmit = document.getElementById('btnSubmitForm');
    btnSubmit.className = "btn btn-primary btn-block";
    btnSubmit.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Enregistrer le local';
    document.getElementById('btnCancelEdit').style.display = 'none';
}

function printDocumentWithTitle(customTitle) {
    const originalTitle = document.title;
    document.title = customTitle;

    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'trigger_print_log=1'
    }).finally(function () {
        window.print();
        setTimeout(() => {
            document.title = originalTitle;
        }, 500);
    });
}

function initPrintButton() {
    const printBtn = document.getElementById('btnPrintRegister');
    if (printBtn) {
        printBtn.onclick = function (e) {
            e.preventDefault();
            printBtn.disabled = true;
            const today = new Date().toISOString().slice(0, 10);
            printDocumentWithTitle("Registre_Officiel_Personnel_STEG_" + today);
            setTimeout(() => { printBtn.disabled = false; }, 1000);
        };
    }
}

function toggleSelects() {
    const type = document.getElementById('typeEntite').value;
    const divSearch = document.getElementById('div-recherche');
    const searchInput = document.getElementById('search-material');
    const idCible = document.getElementById('idCible');

    if (searchInput) searchInput.value = '';
    if (idCible) idCible.value = '0';

    if (type === 'Equipement' || type === 'Immobilisation') {
        if (divSearch) divSearch.style.display = 'block';
        setupDatalistOptions(type);
        if (searchInput) {
            setTimeout(() => searchInput.focus(), 50);
        }
    } else {
        if (divSearch) divSearch.style.display = 'none';
    }
}

function setupDatalistOptions(type) {
    const datalist = document.getElementById('liste-articles');
    if (!datalist) return;

    const items = (type === 'Equipement') ? window.rawEquipements : window.rawImmobilisations;
    const idKey = (type === 'Equipement') ? 'idEquip' : 'idImmo';

    let optionsHTML = '';
    items.forEach(item => {
        optionsHTML += `<option value="${escapeHtml(item.libelle)}" data-id="${item[idKey]}"></option>`;
    });
    datalist.innerHTML = optionsHTML;
}

function handleDatalistInput(value) {
    const datalist = document.getElementById('liste-articles');
    const idCibleInput = document.getElementById('idCible');
    if (!datalist || !idCibleInput) return;

    const option = Array.from(datalist.options).find(opt => opt.value === value);
    idCibleInput.value = option ? option.getAttribute('data-id') : '0';
}

function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, m => map[m]);
}

document.addEventListener("DOMContentLoaded", function () {
    const frontGrid = document.getElementById('frontGrid');
    const dragItems = document.querySelectorAll('.drag-item');
    const searchInput = document.getElementById('catalogSearch');
    const typeLocalFilter = document.getElementById('typeLocalFilter');
    
    let activeDragData = null;

    function applyCatalogFilters() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const filterType = typeLocalFilter ? typeLocalFilter.value : '';
        const items = document.querySelectorAll('#catalogContainer .drag-item');

        items.forEach(item => {
            const itemLabel = item.getAttribute('data-label').toLowerCase();
            const itemType = item.getAttribute('data-type');
            const itemSubtype = item.getAttribute('data-type-local');

            const matchesSearch = itemLabel.includes(searchTerm);
            let matchesType = true;

            if (filterType !== '') {
                if (itemType === 'Local') {
                    matchesType = (itemSubtype === filterType);
                } else {
                    matchesType = false;
                }
            }

            if (matchesSearch && matchesType) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyCatalogFilters);
    }

    if (typeLocalFilter) {
        typeLocalFilter.addEventListener('change', applyCatalogFilters);
    }

    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast-notification');
        toast.textContent = message;
        toast.style.backgroundColor = type === 'success' ? '#22c55e' : '#ef4444';
        toast.style.display = 'block';
        setTimeout(() => { toast.style.display = 'none'; }, 3500);
    }

    dragItems.forEach(item => {
        item.addEventListener('dragstart', function(e) {
            activeDragData = {
                id: this.getAttribute('data-id'),
                label: this.getAttribute('data-label'),
                type: this.getAttribute('data-type')
            };
            e.dataTransfer.effectAllowed = 'move';
        });
    });

    frontGrid.querySelectorAll('.grid-item').forEach(cell => {
        cell.addEventListener('dragover', function(e) {
            e.preventDefault();
            if (!this.querySelector('.placed-element')) {
                this.classList.add('drag-over');
            }
        });

        cell.addEventListener('dragleave', function() {
            this.classList.remove('drag-over');
        });

        cell.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');

            if (this.querySelector('.placed-element') || !activeDragData) return;

            if (activeDragData.type === 'Local' && !allowedProfilsForLocaux.includes(Number(window.currentUserProfilId))) {
                showToast("Action refusée : Votre profil ne vous permet pas de placement de locaux.", "error");
                return;
            }

            const colIdx = this.getAttribute('data-col');
            const rowIdx = this.getAttribute('data-row');
            const targetCell = this;

            const formData = new URLSearchParams();
            formData.append('action_position', '1');
            formData.append('id_magasin', activeDragData.id);
            formData.append('col_idx', colIdx);
            formData.append('row_idx', rowIdx);
            formData.append('current_floor', currentFloor);

            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    targetCell.innerHTML = `<div class="placed-element type-${activeDragData.type}">${activeDragData.label}</div>`;
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                showToast("Erreur lors de la communication réseau.", "error");
                console.error(error);
            });
        });
    });
});

const currentFloor = "<?= htmlspecialchars($floor) ?>";
const canMergeCells = "<?= $canMergeCells ? 'true' : 'false' ?>";

document.addEventListener('DOMContentLoaded', () => {
    const deleteButtons = document.querySelectorAll('.delete-item-trigger');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            
            const cell = this.closest('.grid-item');
            const row = cell.getAttribute('data-row');
            const col = cell.getAttribute('data-col');
            
            if (confirm(`Voulez-vous vraiment retirer cet objet de la case [Ligne ${row}, Colonne ${col}] ?`)) {
                const formData = new FormData();
                formData.append('action_delete_item', '1');
                formData.append('col_idx', col);
                formData.append('row_idx', row);
                formData.append('current_floor', currentFloor);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const computedX = (parseInt(col) - 1) * 50;
                        const computedY = (parseInt(row) - 1) * 50;
                        cell.innerHTML = `<span>X:${computedX}</span><span>Y:${computedY}</span>`;
                        
                        const toast = document.getElementById('toast-notification');
                        if (toast) {
                            toast.innerText = data.message;
                            toast.style.background = '#10b981';
                            toast.style.display = 'block';
                            setTimeout(() => toast.style.display = 'none', 3000);
                        } else {
                            alert(data.message);
                        }
                    } else {
                        alert(data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Erreur lors du traitement de la requête.");
                });
            }
        });
    });

    if (canMergeCells) {
        let selectedCells = [];
        let unmergeModeActive = false;
        
        const gridItems = document.querySelectorAll('#frontGrid .grid-item');
        const mergeBtn = document.getElementById('btnMergeCells');
        const unmergeModeBtn = document.getElementById('btnUnmergeMode');

        if (unmergeModeBtn) {
            unmergeModeBtn.addEventListener('click', () => {
                unmergeModeActive = !unmergeModeActive;
                if (unmergeModeActive) {
                    unmergeModeBtn.classList.add('active-mode');
                    unmergeModeBtn.innerHTML = `<i class="fa-solid fa-hand-pointer"></i> Cliquez sur un bloc à diviser`;
                    selectedCells.forEach(c => c.element.classList.remove('selected-for-merge'));
                    selectedCells = [];
                } else {
                    unmergeModeBtn.classList.remove('active-mode');
                    unmergeModeBtn.innerHTML = `<i class="fa-solid fa-expand"></i> Séparer / Annuler Fusion`;
                }
            });
        }

        gridItems.forEach(item => {
            item.addEventListener('click', (e) => {
                if (e.target.closest('.delete-item-trigger')) return;

                const row = parseInt(item.getAttribute('data-row'));
                const col = parseInt(item.getAttribute('data-col'));

                if (unmergeModeActive) {
                    const mergedZone = item.querySelector('.custom-merged-zone');
                    if (mergedZone) {
                        const rSpan = parseInt(item.getAttribute('data-rspan') || "1");
                        const cSpan = parseInt(item.getAttribute('data-cspan') || "1");

                        if (confirm(`Désouder et restaurer les cellules de la zone "${mergedZone.querySelector('.zone-title').textContent}" ?`)) {
                            item.style.gridRow = '';
                            item.style.gridColumn = '';
                            item.removeAttribute('data-rspan');
                            item.removeAttribute('data-cspan');
                            
                            const computedX = (col - 1) * 50;
                            const computedY = (row - 1) * 50;
                            item.innerHTML = `<span>X:${computedX}</span><span>Y:${computedY}</span>`;

                            for (let r = row; r < row + rSpan; r++) {
                                for (let c = col; c < col + cSpan; c++) {
                                    const latentCell = document.querySelector(`#frontGrid .grid-item[data-row='${r}'][data-col='${c}']`);
                                    if (latentCell) {
                                        latentCell.style.display = '';
                                    }
                                }
                            }
                            unmergeModeBtn.click();
                        }
                    }
                    return;
                }

                if (item.querySelector('.placed-element')) return;
                
                const index = selectedCells.findIndex(cell => cell.row === row && cell.col === col);
                if (index > -1) {
                    selectedCells.splice(index, 1);
                    item.classList.remove('selected-for-merge');
                } else {
                    selectedCells.push({ row, col, element: item });
                    item.classList.add('selected-for-merge');
                }
            });
        });

        if (mergeBtn) {
            mergeBtn.addEventListener('click', () => {
                if (unmergeModeActive) unmergeModeBtn.click();

                if (selectedCells.length < 2) {
                    alert('Veuillez sélectionner au moins deux cellules vides contiguës à fusionner.');
                    return;
                }

                let minRow = Infinity, maxRow = -Infinity;
                let minCol = Infinity, maxCol = -Infinity;

                selectedCells.forEach(c => {
                    if (c.row < minRow) minRow = c.row;
                    if (c.row > maxRow) maxRow = c.row;
                    if (c.col < minCol) minCol = c.col;
                    if (c.col > maxCol) maxCol = c.col;
                });

                const expectedCount = (maxRow - minRow + 1) * (maxCol - minCol + 1);
                if (selectedCells.length !== expectedCount) {
                    alert('La sélection doit former un rectangle parfait et continu sans cases vides manquantes.');
                    return;
                }

                const zoneName = prompt('Entrez un nom ou une désignation pour cette zone fusionnée :');
                if (zoneName === null) return;
                
                const finalName = zoneName.trim() === '' ? 'Zone Spécifique' : zoneName.trim();

                const targetCell = selectedCells.find(c => c.row === minRow && c.col === minCol);
                const rowSpan = maxRow - minRow + 1;
                const colSpan = maxCol - minCol + 1;

                targetCell.element.setAttribute('data-rspan', rowSpan);
                targetCell.element.setAttribute('data-cspan', colSpan);

                targetCell.element.innerHTML = `
                    <div class="custom-merged-zone">
                        <span class="zone-title"></span>
                        <span style="font-size:10px; opacity:0.6;">(${rowSpan}x${colSpan})</span>
                    </div>
                `;
                targetCell.element.querySelector('.zone-title').textContent = finalName;

                targetCell.element.style.gridRow = `span ${rowSpan}`;
                targetCell.element.style.gridColumn = `span ${colSpan}`;
                targetCell.element.classList.remove('selected-for-merge');
                targetCell.element.style.width = 'auto';
                targetCell.element.style.height = 'auto';

                selectedCells.forEach(c => {
                    if (c !== targetCell) {
                        c.element.style.display = 'none';
                        c.element.classList.remove('selected-for-merge');
                    }
                });

                selectedCells = [];
            });
        }
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const urlParams = new URLSearchParams(window.location.search);
    const currentFloor = urlParams.get('floor') || 'rc';
    let isMergeMode = false;
    let isUnmergeMode = false;
    let selectedCells = [];

    const frontGrid = document.getElementById('frontGrid');
    const catalogSearch = document.getElementById('catalogSearch');
    const catalogContainer = document.getElementById('catalogContainer');
    const typeLocalFilter = document.getElementById('typeLocalFilter');
    const btnMergeCells = document.getElementById('btnMergeCells');
    const btnUnmergeMode = document.getElementById('btnUnmergeMode');
    const toast = document.getElementById('toast-notification');

    function showToast(message, isError = false) {
        if (!toast) return;
        toast.textContent = message;
        toast.style.backgroundColor = isError ? '#ef4444' : '#10b981';
        toast.style.color = '#ffffff';
        toast.style.padding = '12px 24px';
        toast.style.borderRadius = '6px';
        toast.style.position = 'fixed';
        toast.style.bottom = '20px';
        toast.style.right = '20px';
        toast.style.zIndex = '9999';
        toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        toast.style.display = 'block';

        setTimeout(() => { toast.style.display = 'none'; }, 3500);
    }

    function applyCatalogContainerFilters() {
        const term = catalogSearch ? catalogSearch.value.toLowerCase().trim() : '';
        const filterType = typeLocalFilter ? typeLocalFilter.value : '';
        const items = catalogContainer ? catalogContainer.querySelectorAll('li') : [];

        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            const itemType = item.getAttribute('data-type');
            const itemSubtype = item.getAttribute('data-type-local');

            const matchesSearch = text.includes(term);
            let matchesType = true;

            if (filterType !== '') {
                if (itemType === 'Local') {
                    matchesType = (itemSubtype === filterType);
                } else {
                    matchesType = false;
                }
            }

            item.style.display = (matchesSearch && matchesType) ? '' : 'none';
        });
    }

    if (catalogSearch && catalogContainer) {
        catalogSearch.addEventListener('input', applyCatalogContainerFilters);
    }

    if (typeLocalFilter && catalogContainer) {
        typeLocalFilter.addEventListener('change', applyCatalogContainerFilters);
    }

    const dragItems = document.querySelectorAll('.drag-item');
    dragItems.forEach(item => {
        item.addEventListener('dragstart', function (e) {
            e.dataTransfer.setData('text/plain', JSON.stringify({
                id: this.getAttribute('data-id'),
                label: this.getAttribute('data-label'),
                type: this.getAttribute('data-type')
            }));
            this.classList.add('dragging');
        });

        item.addEventListener('dragend', function () {
            this.classList.remove('dragging');
        });
    });

    const gridItems = document.querySelectorAll('.grid-item');
    gridItems.forEach(cell => {
        cell.addEventListener('dragover', function (e) {
            if (!this.querySelector('.placed-element') && !isMergeMode && !isUnmergeMode) {
                e.preventDefault();
                this.classList.add('drag-hover');
            }
        });

        cell.addEventListener('dragleave', function () {
            this.classList.remove('drag-hover');
        });

        cell.addEventListener('drop', function (e) {
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
                        this.innerHTML = `
                            <div class="placed-element type-${data.type}">
                                ${data.label}
                                <button type="button" class="delete-item-trigger" title="Supprimer l'objet">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        `;
                        attachDeleteEvent(this.querySelector('.delete-item-trigger'));
                    } else {
                        showToast(result.message, true);
                    }
                })
                .catch(() => showToast("Erreur de communication réseau.", true));

            } catch (err) {
                showToast("Échec de l'analyse des métadonnées de l'objet.", true);
            }
        });
    });

    function attachDeleteEvent(btn) {
        if (!btn) return;
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const cell = this.closest('.grid-item');
            const colIdx = cell.getAttribute('data-col');
            const rowIdx = cell.getAttribute('data-row');

            if (confirm("Voulez-vous vraiment supprimer cet élément du plan ?")) {
                const formData = new FormData();
                formData.append('action_delete_item', '1');
                formData.append('col_idx', colIdx);
                formData.append('row_idx', rowIdx);
                formData.append('current_floor', currentFloor);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(result => {
                    if (result.status === 'success') {
                        showToast(result.message);
                        const computedX = (parseInt(colIdx) - 1) * 50;
                        const computedY = (parseInt(rowIdx) - 1) * 50;
                        cell.innerHTML = `<span>X:${computedX}</span><span>Y:${computedY}</span>`;
                    } else {
                        showToast(result.message, true);
                    }
                })
                .catch(() => showToast("Erreur de communication réseau.", true));
            }
        });
    }
    document.querySelectorAll('.delete-item-trigger').forEach(btn => attachDeleteEvent(btn));

    if (btnMergeCells) {
        btnMergeCells.addEventListener('click', function () {
            isMergeMode = !isMergeMode;
            isUnmergeMode = false;
            clearGridSelection();
            
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
        btnUnmergeMode.addEventListener('click', function () {
            isUnmergeMode = !isUnmergeMode;
            isMergeMode = false;
            clearGridSelection();

            if (isUnmergeMode) {
                this.style.background = '#dc2626';
                if (btnMergeCells) btnMergeCells.style.background = '';
                showToast("Mode dissociation activé. Sélectionnez la cellule fusionnée à diviser.");
            } else {
                this.style.background = '';
            }
        });
    }

    gridItems.forEach(cell => {
        cell.addEventListener('click', function () {
            if (isMergeMode) {
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
                    processMergeAction();
                }
            } else if (isUnmergeMode) {
                if (this.style.gridArea) {
                    processUnmergeAction(this);
                } else {
                    showToast("Cette cellule n'est pas fusionnée.", true);
                }
            }
        });
    });

    function clearGridSelection() {
        selectedCells.forEach(cell => {
            cell.style.outline = '';
            cell.style.backgroundColor = '';
        });
        selectedCells = [];
    }

    function processMergeAction() {
        const zoneName = prompt("Entrez un nom ou un label pour cet espace fusionné :");
        if (!zoneName || zoneName.trim() === "") {
            clearGridSelection();
            return;
        }

        let minRow = Infinity, maxRow = -Infinity;
        let minCol = Infinity, maxCol = -Infinity;

        selectedCells.forEach(cell => {
            const r = parseInt(cell.getAttribute('data-row'));
            const c = parseInt(cell.getAttribute('data-col'));
            if (r < minRow) minRow = r; if (r > maxRow) maxRow = r;
            if (c < minCol) minCol = c; if (c > maxCol) maxCol = c;
        });

        const primaryCell = selectedCells.find(cell => 
            parseInt(cell.getAttribute('data-row')) === minRow && 
            parseInt(cell.getAttribute('data-col')) === minCol
        );

        if (primaryCell) {
            primaryCell.style.gridRowStart = minRow;
            primaryCell.style.gridRowEnd = maxRow + 1;
            primaryCell.style.gridColumnStart = minCol;
            primaryCell.style.gridColumnEnd = maxCol + 1;
            primaryCell.style.gridArea = `${minRow} / ${minCol} / ${maxRow + 1} / ${maxCol + 1}`;
            primaryCell.style.backgroundColor = '#334155';
            primaryCell.style.border = '2px solid #64748b';
            primaryCell.innerHTML = `<div style="font-weight:bold; text-align:center; color:#f8fafc; font-size:12px;">${zoneName}</div>`;
            
            selectedCells.forEach(cell => {
                if (cell !== primaryCell) {
                    cell.style.display = 'none';
                }
            });
        }
        isMergeMode = false;
        if (btnMergeCells) btnMergeCells.style.background = '';
        clearGridSelection();
    }

    function processUnmergeAction(cell) {
        const area = cell.style.gridArea.split('/');
        const rStart = parseInt(area[0]), cStart = parseInt(area[1]), rEnd = parseInt(area[2]), cEnd = parseInt(area[3]);

        gridItems.forEach(c => {
            const r = parseInt(c.getAttribute('data-row'));
            const col = parseInt(c.getAttribute('data-col'));
            if (r >= rStart && r < rEnd && col >= cStart && col < cEnd) {
                c.style.display = '';
                c.style.gridArea = '';
                c.style.gridRowStart = '';
                c.style.gridRowEnd = '';
                c.style.gridColumnStart = '';
                c.style.gridColumnEnd = '';
                c.style.backgroundColor = '';
                c.style.border = '';
                c.innerHTML = `<span>X:${(col - 1) * 50}</span><span>Y:${(r - 1) * 50}</span>`;
            }
        });
        isUnmergeMode = false;
        if (btnUnmergeMode) btnUnmergeMode.style.background = '';
    }
});