document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.PLAN_CONFIG || {};
    window.allowedProfilsForLocaux = cfg.allowedProfilsForLocaux || [1, 2, 3];
    window.currentUserProfilId = cfg.currentUserProfilId || 0;

    initPrintButton();

    const isPlanPage = document.getElementById('gridStage') !== null;
    const isUmlPage = document.getElementById('uml-display-image') !== null;

    if (isPlanPage) {
        const gestures = initGridGestures();
        initPlanCatalog();
        gestures.fitToStage();
    }

    if (isUmlPage) {
        initUmlFeatures();
    }
});

function initPlanCatalog() {
    const catalogSearch = document.getElementById('catalogSearch');
    if (catalogSearch) catalogSearch.addEventListener('input', applyCatalogFilters);

    document.querySelectorAll('.drag-item').forEach(item => {
        const itemType = item.getAttribute('data-type') || '';
        const isLocal = itemType === 'Local';

        if (!isLocal) {
            const stock = parseInt(item.getAttribute('data-stock') || '1', 10);
            if (stock <= 0) {
                disableDragItem(item);
                return;
            }
        }

        if (isLocal) {
            const badge = item.querySelector('.badge-stock');
            if (badge) badge.style.display = 'none';
        }

        item.addEventListener('dragstart', function (e) {
            const dragData = {
                id: this.dataset.id,
                label: this.dataset.label,
                type: this.dataset.type,
            };
            e.dataTransfer.setData('text/plain', JSON.stringify(dragData));
            window._currentDragType = this.dataset.type;
            this.classList.add('dragging');
        });

        item.addEventListener('dragend', function () {
            this.classList.remove('dragging');
            window._currentDragType = '';
        });
    });

    let selectedCells = [];
    let cellToUnmerge = null;
    let lastClickedCell = null;
    const SELECTION_CLASS = 'selected';

    const gridItems = document.querySelectorAll('.grid-item');
    const btnMergeCells = document.getElementById('btnMergeCells');
    const btnUnmergeMode = document.getElementById('btnMenuUnmergeMode');
    const deleteBtn = document.getElementById('deleteSelectedBtn');

    if (btnMergeCells) btnMergeCells.setAttribute('disabled', 'true');
    if (btnUnmergeMode) btnUnmergeMode.setAttribute('disabled', 'true');
    if (deleteBtn) deleteBtn.setAttribute('disabled', 'true');

    function getSelectedCount() {
        return document.querySelectorAll(`.grid-item.${SELECTION_CLASS}`).length;
    }

    function updateSelectionUI() {
        const count = getSelectedCount();
        if (count > 0) {
            showToast(`${count} élément(s) sélectionné(s).`, false, true);
            if (deleteBtn) deleteBtn.removeAttribute('disabled');
        } else {
            if (deleteBtn) deleteBtn.setAttribute('disabled', 'true');
            const toast = document.getElementById('toast-notification');
            if (toast) toast.style.display = 'none';
        }
    }

    function getAllPlacedCells() {
        return Array.from(gridItems).filter(c => c.querySelector('.placed-element'));
    }

    function getPlacedCellsBetween(a, b) {
        const placed = getAllPlacedCells();
        const idxA = placed.indexOf(a);
        const idxB = placed.indexOf(b);
        if (idxA === -1 || idxB === -1) return [b];
        const start = Math.min(idxA, idxB);
        const end = Math.max(idxA, idxB);
        return placed.slice(start, end + 1);
    }

    gridItems.forEach(cell => {
        const frontContent = cell.querySelector('.front-cell-content');

        cell.addEventListener('dragover', function (e) {
            const isOccupied = !!this.querySelector('.placed-element');
            const isLocal = window._currentDragType === 'Local';

            if (!isOccupied || isLocal) {
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

                if (
                    data.type === 'Local' &&
                    !window.allowedProfilsForLocaux.includes(Number(window.currentUserProfilId))
                ) {
                    showToast("Action refusée : Votre profil ne vous permet pas de placement de locaux.", true);
                    return;
                }

                if (data.type === 'Local' && frontContent) {
                    const existing = frontContent.querySelector('.placed-element');
                    if (existing) existing.remove();
                }

                const colIdx = this.getAttribute('data-col');
                const rowIdx = this.getAttribute('data-row');

                const formData = new FormData();
                formData.append('action_position', '1');
                formData.append('col_idx', colIdx);
                formData.append('row_idx', rowIdx);
                formData.append('current_floor', window.PLAN_CONFIG?.currentFloor ?? 'rc');

                if (data.type === 'Local') { formData.append('id_local', data.id); }
                else if (data.type === 'Voiture') { formData.append('matricule_voiture', data.id); }
                else { formData.append('id_magasin', data.id); }

                fetch(window.location.href, { method: 'POST', body: formData })
                    .then(res => {
                        if (!res.ok) throw new Error();
                        return res.json();
                    })
                    .then(result => {
                        if (result.status === 'success') {
                            showToast(result.message);

                            if (frontContent) {
                                const coordSpan = frontContent.querySelector('.cell-coords');
                                frontContent.innerHTML = '';
                                if (coordSpan) frontContent.appendChild(coordSpan);

                                const placedDiv = document.createElement('div');
                                placedDiv.className = `placed-element type-${escapeHtml(data.type)}`;
                                placedDiv.dataset.placedId = data.id;
                                placedDiv.dataset.placedType = data.type;
                                placedDiv.style.pointerEvents = 'none';

                                const labelSpan = document.createElement('span');
                                labelSpan.textContent = data.label;
                                labelSpan.style.pointerEvents = 'none';

                                placedDiv.appendChild(labelSpan);
                                frontContent.appendChild(placedDiv);
                            }

                            decrementStock(data.id, data.type);
                        } else {
                            showToast(result.message, true);
                        }
                    })
                    .catch(() => showToast("Erreur de communication réseau.", true));

            } catch {
                showToast("Échec de l'analyse des métadonnées de l'objet.", true);
            }
        });

        cell.addEventListener('click', function (e) {
            if (window.isDraggingGrid) return;

            if (this.querySelector('.placed-element')) {
                if (e.shiftKey && lastClickedCell) {
                    const range = getPlacedCellsBetween(lastClickedCell, this);
                    range.forEach(c => c.classList.add(SELECTION_CLASS));
                } else if (e.ctrlKey || e.metaKey) {
                    this.classList.toggle(SELECTION_CLASS);
                    lastClickedCell = this;
                } else {
                    const isAlreadyOnlySelected =
                        this.classList.contains(SELECTION_CLASS) && getSelectedCount() === 1;
                    document.querySelectorAll(`.grid-item.${SELECTION_CLASS}`).forEach(c => c.classList.remove(SELECTION_CLASS));
                    if (!isAlreadyOnlySelected) {
                        this.classList.add(SELECTION_CLASS);
                    }
                    lastClickedCell = this;
                }

                updateSelectionUI();
                return;
            }

            document.querySelectorAll(`.grid-item.${SELECTION_CLASS}`).forEach(c => c.classList.remove(SELECTION_CLASS));
            lastClickedCell = null;
            updateSelectionUI();

            const hasMergedId = this.hasAttribute('data-merged-group-id');

            if (hasMergedId && !e.altKey) {
                const gid = this.getAttribute('data-merged-group-id');
                const allInGroup = document.querySelectorAll(`#frontGrid .grid-item[data-merged-group-id='${gid}']`);
                let rootCell = allInGroup[0];
                
                for (let i = 0; i < allInGroup.length; i++) {
                    const r = parseInt(allInGroup[i].getAttribute('data-row'), 10);
                    const c = parseInt(allInGroup[i].getAttribute('data-col'), 10);
                    const rootR = parseInt(rootCell.getAttribute('data-row'), 10);
                    const rootC = parseInt(rootCell.getAttribute('data-col'), 10);
                    if (r < rootR || (r === rootR && c < rootC)) {
                        rootCell = allInGroup[i];
                    }
                }

                if (cellToUnmerge && cellToUnmerge !== rootCell) {
                    const oldGid = cellToUnmerge.getAttribute('data-merged-group-id');
                    if (oldGid) {
                        document.querySelectorAll(`#frontGrid .grid-item[data-merged-group-id='${oldGid}']`).forEach(c => c.classList.remove('selected-for-unmerge'));
                    }
                }

                if (cellToUnmerge === rootCell) {
                    allInGroup.forEach(c => c.classList.remove('selected-for-unmerge'));
                    cellToUnmerge = null;
                    if (btnUnmergeMode) btnUnmergeMode.setAttribute('disabled', 'true');
                } else {
                    allInGroup.forEach(c => c.classList.add('selected-for-unmerge'));
                    cellToUnmerge = rootCell;
                    if (btnUnmergeMode) btnUnmergeMode.removeAttribute('disabled');
                }
                return;
            }

            if (cellToUnmerge) {
                const oldGid = cellToUnmerge.getAttribute('data-merged-group-id');
                if (oldGid) {
                    document.querySelectorAll(`#frontGrid .grid-item[data-merged-group-id='${oldGid}']`).forEach(c => c.classList.remove('selected-for-unmerge'));
                }
                cellToUnmerge = null;
                if (btnUnmergeMode) btnUnmergeMode.setAttribute('disabled', 'true');
            }

            if (selectedCells.includes(this)) {
                this.classList.remove('selected-for-merge');
                selectedCells = selectedCells.filter(c => c !== this);
            } else {
                this.classList.add('selected-for-merge');
                selectedCells.push(this);
            }

            if (btnMergeCells) {
                if (selectedCells.length > 1) {
                    btnMergeCells.removeAttribute('disabled');
                } else {
                    btnMergeCells.setAttribute('disabled', 'true');
                }
            }
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll(`.grid-item.${SELECTION_CLASS}`).forEach(c => c.classList.remove(SELECTION_CLASS));
            lastClickedCell = null;
            updateSelectionUI();
        }

        if ((e.key === 'a' || e.key === 'A') && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            getAllPlacedCells().forEach(c => c.classList.add(SELECTION_CLASS));
            updateSelectionUI();
        }
    });

    if (btnMergeCells) {
        btnMergeCells.addEventListener('click', function () {
            if (selectedCells.length <= 1) return;
            processMergeAction(selectedCells, () => {
                selectedCells.forEach(c => c.classList.remove('selected-for-merge'));
                selectedCells = [];
                btnMergeCells.setAttribute('disabled', 'true');
            });
        });
    }

    if (btnUnmergeMode) {
        btnUnmergeMode.addEventListener('click', function () {
            if (!cellToUnmerge) return;
            processUnmergeAction(cellToUnmerge, () => {
                const gid = cellToUnmerge.getAttribute('data-merged-group-id');
                if (gid) {
                    document.querySelectorAll(`#frontGrid .grid-item[data-merged-group-id='${gid}']`).forEach(c => c.classList.remove('selected-for-unmerge'));
                }
                cellToUnmerge = null;
                btnUnmergeMode.setAttribute('disabled', 'true');
            });
        });
    }

    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            const selectedItems = Array.from(document.querySelectorAll(`.grid-item.${SELECTION_CLASS}`));

            if (selectedItems.length === 0) {
                showToast("Sélectionnez d'abord un ou plusieurs éléments sur la grille pour utiliser Supprimer élément.", true);
                return;
            }

            if (!confirm(`Supprimer élément : voulez-vous vraiment retirer les ${selectedItems.length} élément(s) de la grille ?`)) {
                return;
            }

            const backendFloor = new URLSearchParams(window.location.search).get('floor') || 'rc';
            let completed = 0;
            let errors = 0;

            selectedItems.forEach(item => {
                const row = item.getAttribute('data-row');
                const col = item.getAttribute('data-col');

                const formData = new FormData();
                formData.append('action_delete_item', '1');
                formData.append('row_idx', row);
                formData.append('col_idx', col);
                formData.append('current_floor', backendFloor);

                fetch(window.location.href, { method: 'POST', body: formData })
                    .then(res => {
                        if (!res.ok) throw new Error();
                        return res.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            const contentDiv = item.querySelector('.front-cell-content');
                            if (contentDiv) {
                                const coordSpan = contentDiv.querySelector('.cell-coords');
                                contentDiv.innerHTML = '';
                                if (coordSpan) contentDiv.appendChild(coordSpan);
                            }
                            item.classList.remove(SELECTION_CLASS);
                        } else {
                            errors++;
                        }
                    })
                    .catch(() => errors++)
                    .finally(() => {
                        completed++;
                        if (completed === selectedItems.length) {
                            lastClickedCell = null;
                            deleteBtn.setAttribute('disabled', 'true');
                            const toast = document.getElementById('toast-notification');
                            if (toast) toast.style.display = 'none';

                            if (errors === 0) {
                                showToast(`${selectedItems.length} élément(s) supprimé(s) avec succès.`);
                            } else {
                                showToast(`${errors} erreur(s) lors de l'exécution de Supprimer élément.`, true);
                            }
                        }
                    });
            });
        });
    }
}

function checkConnectionBFS(cells) {
    if (cells.length === 0) return false;
    const lookup = new Set(cells.map(c => `${c.getAttribute('data-row')},${c.getAttribute('data-col')}`));
    const visited = new Set();
    const queue = [];

    const firstKey = `${cells[0].getAttribute('data-row')},${cells[0].getAttribute('data-col')}`;
    queue.push(firstKey);
    visited.add(firstKey);

    while (queue.length > 0) {
        const curr = queue.shift();
        const [r, c] = curr.split(',').map(Number);
        const neighbors = [
            `${r+1},${c}`, `${r-1},${c}`,
            `${r},${c+1}`, `${r},${c-1}`
        ];

        for (const n of neighbors) {
            if (lookup.has(n) && !visited.has(n)) {
                visited.add(n);
                queue.push(n);
            }
        }
    }

    return visited.size === cells.length;
}

function updateMergedBorders(gid) {
    const groupCells = document.querySelectorAll(`#frontGrid .grid-item[data-merged-group-id='${gid}']`);
    if (groupCells.length === 0) return;

    const coordinateMap = new Set();
    groupCells.forEach(c => {
        const r = parseInt(c.getAttribute('data-row'), 10);
        const c_idx = parseInt(c.getAttribute('data-col'), 10);
        coordinateMap.add(`${r},${c_idx}`);
    });

    groupCells.forEach(cell => {
        const r = parseInt(cell.getAttribute('data-row'), 10);
        const c = parseInt(cell.getAttribute('data-col'), 10);

        const hasTop = coordinateMap.has(`${r-1},${c}`);
        const hasBottom = coordinateMap.has(`${r+1},${c}`);
        const hasLeft = coordinateMap.has(`${r},${c-1}`);
        const hasRight = coordinateMap.has(`${r},${c+1}`);

        cell.style.borderTop = hasTop ? 'none' : '1px solid #ccc';
        cell.style.borderBottom = hasBottom ? 'none' : '1px solid #ccc';
        cell.style.borderLeft = hasLeft ? 'none' : '1px solid #ccc';
        cell.style.borderRight = hasRight ? 'none' : '1px solid #ccc';
        
        cell.style.backgroundColor = '#ffffff';
    });
}

function decrementStock(itemId, itemType) {
    if (itemType === 'Local') return;

    const dragItem = document.querySelector(
        `.drag-item[data-id="${CSS.escape(String(itemId))}"][data-type="${itemType}"]`
    );
    if (!dragItem) return;

    let stock = parseInt(dragItem.getAttribute('data-stock') || '1', 10);
    stock = Math.max(0, stock - 1);
    dragItem.setAttribute('data-stock', stock);

    const badge = dragItem.querySelector('.badge-stock');
    if (badge) badge.textContent = `x${stock}`;

    if (stock === 0) {
        disableDragItem(dragItem);
        const wrapper = dragItem.closest('.catalog-item-wrapper');
        if (wrapper) {
            wrapper.remove();
            checkEmptyCatalog();
        }
    }
}

function disableDragItem(dragItem) {
    dragItem.setAttribute('draggable', 'false');
    dragItem.style.opacity = '0.4';
    dragItem.style.cursor = 'not-allowed';
    dragItem.style.pointerEvents = 'none';
}

function initGridGestures() {
    const stage = document.getElementById('gridStage');
    const grid = document.getElementById('frontGrid');
    if (!stage || !grid) return { fitToStage: () => {} };

    const btnIn = document.getElementById('zoom-in');
    const btnOut = document.getElementById('zoom-out');
    const btnReset = document.getElementById('zoom-reset');
    const deleteBtn = document.getElementById('deleteSelectedBtn');

    let scale = 1;
    let translateX = 0;
    let translateY = 0;
    let isPanning = false;
    let startX = 0;
    let startY = 0;
    let mDownX = 0;
    let mDownY = 0;
    let animationFrameId = null;
    window.isDraggingGrid = false;

    const minScale = 0.2;
    const maxScale = 5;

    function applyTransform() {
        if (animationFrameId) cancelAnimationFrame(animationFrameId);
        animationFrameId = requestAnimationFrame(() => {
            grid.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
        });
    }

    function fitToStage() {
        const stageW = stage.clientWidth || stage.offsetWidth;
        const stageH = stage.clientHeight || stage.offsetHeight;
        const gridW = grid.offsetWidth;
        const gridH = grid.offsetHeight;
        if (!gridW || !gridH) return;

        const padding = 32;
        const scaleX = (stageW - padding) / gridW;
        const scaleY = (stageH - padding) / gridH;
        scale = Math.min(scaleX, scaleY, 1);
        scale = Math.max(minScale, scale);

        translateX = 0;
        translateY = 0;
        applyTransform();
    }

    stage.addEventListener('wheel', (e) => {
        e.preventDefault();
        e.stopPropagation();

        const zoomIntensity = 0.1;
        const previousScale = scale;

        scale = e.deltaY < 0
            ? Math.min(maxScale, scale + zoomIntensity)
            : Math.max(minScale, scale - zoomIntensity);

        const rect = stage.getBoundingClientRect();
        const mouseX = e.clientX - rect.left - rect.width / 2;
        const mouseY = e.clientY - rect.top - rect.height / 2;

        translateX -= mouseX * (scale / previousScale - 1);
        translateY -= mouseY * (scale / previousScale - 1);

        applyTransform();
    }, { passive: false });

    stage.addEventListener('mousedown', (e) => {
        isPanning = true;
        window.isDraggingGrid = false;
        mDownX = e.clientX;
        mDownY = e.clientY;
        startX = e.clientX - translateX;
        startY = e.clientY - translateY;
    });

    window.addEventListener('mousemove', (e) => {
        if (!isPanning) return;
        if (Math.abs(e.clientX - mDownX) > 5 || Math.abs(e.clientY - mDownY) > 5) {
            window.isDraggingGrid = true;
        }
        translateX = e.clientX - startX;
        translateY = e.clientY - startY;
        applyTransform();
    });

    window.addEventListener('mouseup', () => {
        isPanning = false;
        setTimeout(() => { window.isDraggingGrid = false; }, 50);
    });

    if (btnIn) btnIn.addEventListener('click', () => { scale = Math.min(maxScale, scale + 0.2); applyTransform(); });
    if (btnOut) btnOut.addEventListener('click', () => { scale = Math.max(minScale, scale - 0.2); applyTransform(); });
    if (btnReset) {
        btnReset.addEventListener('click', () => {
            fitToStage();
            document.querySelectorAll('.grid-item.selected').forEach(c => c.classList.remove('selected'));
            if (deleteBtn) deleteBtn.setAttribute('disabled', 'true');
            const toast = document.getElementById('toast-notification');
            if (toast) toast.style.display = 'none';
            showToast("Vue du plan réinitialisée.");
        });
    }

    return { fitToStage };
}

function initUmlFeatures() {
    const viewport = document.getElementById('viewport');
    const displayImage = document.getElementById('uml-display-image');
    const links = document.querySelectorAll('.uml-link');
    const zoomContainer = document.getElementById('zoom-container');

    const btnIn = document.getElementById('uml-zoom-in') || document.getElementById('zoom-in');
    const btnOut = document.getElementById('uml-zoom-out') || document.getElementById('zoom-out');
    const btnReset = document.getElementById('uml-zoom-reset') || document.getElementById('zoom-reset');

    const ctrl = createPanZoom({
        container: viewport,
        target: zoomContainer,
        minScale: 0.4,
        maxScale: 4,
        skipTargets: ['.zoom-controls'],
    });

    links.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            links.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            displayImage.src = this.getAttribute('href');
            displayImage.onload = () => ctrl.reset();
        });
    });

    if (btnIn) btnIn.addEventListener('click', () => ctrl.zoomBy(+0.2));
    if (btnOut) btnOut.addEventListener('click', () => ctrl.zoomBy(-0.2));
    if (btnReset) btnReset.addEventListener('click', () => ctrl.reset());
}

function createPanZoom({ container, target, minScale = 0.2, maxScale = 5, skipTargets = [] }) {
    if (!container || !target) return { reset: () => {}, zoomBy: () => {} };

    let scale = 1, tx = 0, ty = 0, panning = false, sx = 0, sy = 0;
    let animationFrameId = null;

    function apply() {
        if (animationFrameId) cancelAnimationFrame(animationFrameId);
        animationFrameId = requestAnimationFrame(() => {
            target.style.transform = `translate(${tx}px, ${ty}px) scale(${scale})`;
        });
    }

    container.addEventListener('wheel', (e) => {
        e.preventDefault();
        const prev = scale;
        const delta = 0.1;
        scale = e.deltaY < 0 ? Math.min(maxScale, scale + delta) : Math.max(minScale, scale - delta);
        const rect = container.getBoundingClientRect();
        tx -= (e.clientX - rect.left - rect.width / 2) * (scale / prev - 1);
        ty -= (e.clientY - rect.top - rect.height / 2) * (scale / prev - 1);
        apply();
    }, { passive: false });

    container.addEventListener('mousedown', (e) => {
        if (skipTargets.some(sel => e.target.closest(sel))) return;
        panning = true;
        sx = e.clientX - tx;
        sy = e.clientY - ty;
    });
    window.addEventListener('mousemove', (e) => {
        if (!panning) return;
        tx = e.clientX - sx;
        ty = e.clientY - sy;
        apply();
    });
    window.addEventListener('mouseup', () => { panning = false; });

    return {
        reset() { scale = 1; tx = 0; ty = 0; apply(); },
        zoomBy(delta) { scale = Math.min(maxScale, Math.max(minScale, scale + delta)); apply(); },
    };
}

function processMergeAction(cells, callback) {
    if (!checkConnectionBFS(cells)) {
        showToast("La sélection doit former un ensemble de cases connectées de manière continue.", true);
        if (callback) callback();
        return;
    }

    let minRow = Infinity, maxRow = -Infinity, minCol = Infinity, maxCol = -Infinity;
    cells.forEach(cell => {
        const r = parseInt(cell.getAttribute('data-row'), 10);
        const c = parseInt(cell.getAttribute('data-col'), 10);
        if (r < minRow) minRow = r;
        if (r > maxRow) maxRow = r;
        if (c < minCol) minCol = c;
        if (c > maxCol) maxCol = c;
    });

    const backendFloor = new URLSearchParams(window.location.search).get('floor') || 'rc';
    const formData = new FormData();
    formData.append('action_merge_cells', '1');
    formData.append('current_floor', backendFloor);
    
    const rawCoords = cells.map(c => `${c.getAttribute('data-row')},${c.getAttribute('data-col')}`).join(';');
    formData.append('merged_coords', rawCoords);

    fetch(window.location.href, { method: 'POST', body: formData })
        .then(res => {
            if (!res.ok) throw new Error();
            return res.json();
        })
        .then(result => {
            if (result.status === 'success') {
                const groupId = 'g_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
                
                cells.forEach(c => {
                    c.setAttribute('data-merged-group-id', groupId);
                    c.classList.add('custom-merged-child-styling');
                    
                    const frontContent = c.querySelector('.front-cell-content');
                    if (frontContent) {
                        frontContent.innerHTML = '';
                    }
                });

                updateMergedBorders(groupId);
                showToast(result.message || "Zone fusionnée avec succès.");
            } else {
                showToast("Erreur : " + result.message, true);
            }
        })
        .catch(() => showToast("Erreur réseau lors de la fusion.", true))
        .finally(() => { if (callback) callback(); });
}

function processUnmergeAction(cell, callback) {
    const gid = cell.getAttribute('data-merged-group-id');
    if (!gid) {
        if (callback) callback();
        return;
    }

    const backendFloor = new URLSearchParams(window.location.search).get('floor') || 'rc';
    const formData = new FormData();
    formData.append('action_unmerge_cells', '1');
    formData.append('merged_group_id', gid);
    formData.append('current_floor', backendFloor);

    fetch(window.location.href, { method: 'POST', body: formData })
        .then(res => {
            if (!res.ok) throw new Error();
            return res.json();
        })
        .then(result => {
            if (result.status === 'success') {
                const groupedCells = document.querySelectorAll(`#frontGrid .grid-item[data-merged-group-id='${gid}']`);
                
                groupedCells.forEach(c => {
                    c.removeAttribute('data-merged-group-id');
                    c.classList.remove('custom-merged-child-styling');
                    
                    c.style.borderTop = '';
                    c.style.borderBottom = '';
                    c.style.borderLeft = '';
                    c.style.borderRight = '';
                    c.style.backgroundColor = '';
                    
                    const frontContent = c.querySelector('.front-cell-content');
                    if (frontContent) {
                        const row = parseInt(c.getAttribute('data-row'), 10);
                        const col = parseInt(c.getAttribute('data-col'), 10);
                        frontContent.innerHTML = `<span class="cell-coords">X:${(col - 1) * 50} Y:${(row - 1) * 50}</span>`;
                    }
                });
                showToast(result.message || "Zone dissociée avec succès.");
            } else {
                showToast("Erreur : " + result.message, true);
            }
        })
        .catch(() => showToast("Erreur réseau lors de la dissociation.", true))
        .finally(() => { if (callback) callback(); });
}

function checkEmptyCatalog() {
    document.querySelectorAll('.catalog-group').forEach(group => {
        if (group.querySelectorAll('.catalog-item-wrapper').length === 0) group.remove();
    });

    const remaining = document.querySelectorAll('.catalog-item-wrapper').length;
    const msg = document.getElementById('emptyCatalogMsg');
    if (remaining === 0 && msg) msg.style.display = 'block';
}

function applyCatalogFilters() {
    const searchInput = document.getElementById('catalogSearch');
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';

    document.querySelectorAll('#accordionGroupContainer .drag-item').forEach(item => {
        const label = (item.getAttribute('data-label') || item.textContent).toLowerCase();
        item.closest('.catalog-item-wrapper').style.display =
            label.includes(searchTerm) ? '' : 'none';
    });

    document.querySelectorAll('.catalog-group').forEach(group => {
        const visible = group.querySelectorAll('.catalog-item-wrapper:not([style*="display: none"])').length;
        group.style.display = visible > 0 ? '' : 'none';
    });
}

function printDocumentWithTitle(customTitle) {
    const originalTitle = document.title;
    document.title = customTitle;
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'trigger_print_log=1',
    }).finally(() => {
        window.print();
        document.title = originalTitle;
    });
}