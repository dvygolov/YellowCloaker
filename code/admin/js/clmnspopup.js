let columnsSortable = null;

function addColumnsToList(selectedClmns, availableClmns, existingFilters, filterType, options) {
    const $list = $('#columnsList');
    $list.empty();
    const showParamButton = options?.showParamButton !== false;
    document.getElementById('addParamColumn').style.display = showParamButton ? '' : 'none';

    // Add all selected columns in their saved order (regular + param interleaved)
    selectedClmns.forEach(column => {
        const f = typeof column === 'string' ? column : column.field;
        if (!f) return;
        if (f.startsWith('param.')) {
            const div = createParamItemElement('columnsList', f.substring(6));
            if (div) $list.append(div);
        } else {
            $list.append(createSortableItem(f, formatColumnName(f), true));
        }
    });

    // Then add unselected regular columns
    const selectedFields = selectedClmns.map(c => typeof c === 'string' ? c : c.field).filter(Boolean);
    availableClmns.forEach(column => {
        const columnField = typeof column === 'string' ? column : column.field;
        if (!selectedFields.includes(columnField)) {
            $list.append(createSortableItem(columnField, formatColumnName(columnField), false));
        }
    });
    
    // Destroy existing Sortable instance if it exists
    if (columnsSortable) {
        columnsSortable.destroy();
    }
    
    // Initialize new Sortable instance
    columnsSortable = initializeSortable('columnsList', 'columns');

    // Setup select/deselect buttons
    setupSelectButtons('selectAllColumns', 'deselectAllColumns', 'columnsList');

    // Attach checkbox change handlers
    $('#columnsList input[type="checkbox"]').on('change', function() {
        updateSaveButtonState();
    });

    // + Param button (event delegation for jquery-modal compatibility)
    document.removeEventListener('click', handleAddParamColumn);
    document.addEventListener('click', handleAddParamColumn);

    // Initialize filters (pass extra fields like 'reason' for blocked clicks)
    const extraFields = filterType === 'blocked' ? ['reason'] : [];
    initializeFilters(existingFilters || {}, extraFields);

    // Initial button state
    updateSaveButtonState();
}

function handleAddParamColumn(e) {
    if (!e.target.closest('#addParamColumn')) return;
    const name = prompt('URL param name:');
    if (!name || !name.trim()) return;
    const clean = name.trim().replace(/[^a-zA-Z0-9_]/g, '');
    if (!clean) return;
    addParamItem('columnsList', clean);
    updateSaveButtonState();
}

function getSelectedColumns() {
    return getSelectedItems('columnsList');
}

function setSaveButtonHandler(handlerUrl) {
    setupModalButtons('saveColumns', 'closeModal', async () => {
        const selectedColumns = getSelectedColumns();
        
        if (selectedColumns.length === 0) {
            alert('Please select at least one column');
            return;
        }

        try {
            const response = await fetch(handlerUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ columns: selectedColumns, filters: collectFilters() })
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();
            if (!data.error) {
                window.location.reload();
            } else {
                throw new Error(data.msg);
            }
        } catch (error) {
            alert('Error saving columns: ' + error.message);
        }
    });
}