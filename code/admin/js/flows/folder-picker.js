// ── Folder Picker Modal logic ──
var fpResolve = null;

export function openFolderPicker(folders) {
    folders = Array.from(new Set((folders || []).map(function (folder) { return String(folder); })));
    var $list = $('#fp-list');
    var $empty = $('#fp-empty');
    var $search = $('#fp-search');
    var $ok = $('#fp-ok');
    var selectedFolders = new Set();

    if (fpResolve) {
        fpResolve(null);
        fpResolve = null;
    }

    $search.val('');

    function updateSelectionState() {
        var count = selectedFolders.size;
        $ok.prop('disabled', count === 0);
        $ok.text(count > 0 ? 'Add selected (' + count + ')' : 'Add selected');
    }

    function renderFolders(visibleFolders) {
        $empty.text(folders.length ? 'No folders match your search.' : 'No folders found. Upload a ZIP first.');
        renderFpList(visibleFolders, $list, $empty, selectedFolders, updateSelectionState);
    }

    renderFolders(folders);
    updateSelectionState();

    $search.off('input').on('input', function () {
        var q = $(this).val().toLowerCase();
        var filtered = folders.filter(function (f) { return f.toLowerCase().indexOf(q) !== -1; });
        renderFolders(filtered);
    });

    function closeWithResult(result) {
        var resolve = fpResolve;
        fpResolve = null;
        $.modal.close();
        if (resolve) resolve(result);
    }

    $ok.off('click').on('click', function () {
        var selected = folders.filter(function (folder) { return selectedFolders.has(folder); });
        closeWithResult(selected.length ? selected : null);
    });

    $('#fp-cancel').off('click').on('click', function () {
        closeWithResult(null);
    });

    $('#folderPickerModal').off('modal:close.folderPicker').on('modal:close.folderPicker', function () {
        if (!fpResolve) return;
        var resolve = fpResolve;
        fpResolve = null;
        resolve(null);
    });

    var promise = new Promise(function (resolve) { fpResolve = resolve; });

    $('#folderPickerModal').modal({
        modalClass: 'ywbmodal',
        fadeDuration: 250,
        fadeDelay: 0.80,
        showClose: false
    });
    window.setTimeout(function () { $search.trigger('focus'); }, 0);

    return promise;
}

function renderFpList(folders, $list, $empty, selectedFolders, onSelectionChange) {
    $list.empty();
    if (!folders.length) {
        $empty.show();
        return;
    }
    $empty.hide();
    folders.forEach(function (folder) {
        var isSelected = selectedFolders.has(folder);
        var $label = $('<label>').toggleClass('fp-selected', isSelected);
        var $input = $('<input>', { type: 'checkbox', name: 'fp-folder' })
            .val(folder)
            .prop('checked', isSelected);
        var $name = $('<span>', { class: 'fp-folder-name' }).text(folder);

        $input.on('change', function () {
            if (this.checked) selectedFolders.add(folder);
            else selectedFolders.delete(folder);
            $label.toggleClass('fp-selected', this.checked);
            onSelectionChange();
        });

        $label.append($input, $name);
        $list.append($label);
    });
}
