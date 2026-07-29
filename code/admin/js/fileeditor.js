'use strict';

var editorView = null;
var currentFolder = '';
var currentFile = '';
var isDirty = false;
var cmInstance = null;
var initialContent = '';

function normalizeEditorContent(text) {
    if (typeof text !== 'string') return '';
    return text.replace(/\r\n?/g, '\n');
}

function showEditorLoading(message) {
    var container = document.getElementById('fe-editor');
    if (!container) return;
    if (editorView) {
        editorView.destroy();
        editorView = null;
    }
    var safe = escHtml(message || 'Loading...');
    container.innerHTML =
        '<div class="fe-loading">' +
            '<div class="fe-loading-spinner" aria-hidden="true"></div>' +
            '<div class="fe-loading-text">' + safe + '</div>' +
        '</div>';
}

// ── Pick the right cm6 bundle for a file extension ──
function getCm6Bundle(ext) {
    switch (ext) {
        case 'css': return window.CM6_CSS;
        case 'js': case 'json': return window.CM6_JS;
        case 'php': return window.CM6_PHP;
        default: return window.CM6_HTML;
    }
}

// ── Build modal HTML ──
function buildModal() {
    var modal = document.createElement('div');
    modal.id = 'fe-modal';
    modal.className = 'fe-modal';
    modal.innerHTML =
        '<div class="fe-modal-content">' +
            '<div class="fe-header">' +
                '<span class="fe-title">File Editor: <span id="fe-folder-name"></span></span>' +
                '<div class="fe-header-actions">' +
                    '<span id="fe-current-file" class="fe-current-file"></span>' +
                    '<button class="btn btn-sm btn-success fe-btn" id="fe-save-btn" title="Save (Ctrl+S)"><i class="bi bi-floppy"></i> Save</button>' +
                    '<button class="btn btn-sm btn-secondary fe-btn" id="fe-close-btn"><i class="bi bi-x-lg"></i> Close</button>' +
                '</div>' +
            '</div>' +
            '<div class="fe-body">' +
                '<div class="fe-sidebar">' +
                    '<div class="fe-toolbar">' +
                        '<button class="btn btn-xs btn-outline-light fe-tb" id="fe-new-file" title="New File"><i class="bi bi-file-earmark-plus"></i></button>' +
                        '<button class="btn btn-xs btn-outline-light fe-tb" id="fe-new-folder" title="New Folder"><i class="bi bi-folder-plus"></i></button>' +
                        '<button class="btn btn-xs btn-outline-light fe-tb" id="fe-upload-file" title="Upload File"><i class="bi bi-upload"></i></button>' +
                        '<button class="btn btn-xs btn-outline-light fe-tb" id="fe-rename-btn" title="Rename"><i class="bi bi-pencil"></i></button>' +
                        '<button class="btn btn-xs btn-outline-light fe-tb" id="fe-download-btn" title="Download"><i class="bi bi-download"></i></button>' +
                        '<button class="btn btn-xs btn-outline-danger fe-tb" id="fe-delete-btn" title="Delete"><i class="bi bi-trash"></i></button>' +
                        '<button class="btn btn-xs btn-outline-light fe-tb" id="fe-refresh-btn" title="Refresh"><i class="bi bi-arrow-clockwise"></i></button>' +
                    '</div>' +
                    '<div class="fe-tree" id="fe-tree"></div>' +
                '</div>' +
                '<div class="fe-editor-wrap">' +
                    '<div id="fe-editor"></div>' +
                '</div>' +
            '</div>' +
        '</div>';
    document.body.appendChild(modal);
    return modal;
}

// ── Tree rendering ──
function renderTree(tree, container, depth) {
    depth = depth || 0;
    container.innerHTML = '';
    // Sort: dirs first, then files
    tree.sort(function (a, b) {
        if (a.type === b.type) return a.name.localeCompare(b.name);
        return a.type === 'dir' ? -1 : 1;
    });
    tree.forEach(function (item) {
        var el = document.createElement('div');
        el.className = 'fe-tree-item' + (item.type === 'dir' ? ' fe-tree-dir' : ' fe-tree-file');
        el.dataset.path = item.path;
        el.dataset.type = item.type;
        el.style.paddingLeft = (12 + depth * 16) + 'px';

        if (item.type === 'dir') {
            el.innerHTML = '<i class="bi bi-folder-fill fe-icon-dir"></i> ' + escHtml(item.name);
            var childContainer = document.createElement('div');
            childContainer.className = 'fe-tree-children';
            childContainer.style.display = 'none';
            renderTree(item.children || [], childContainer, depth + 1);

            el.addEventListener('click', function (e) {
                e.stopPropagation();
                selectTreeItem(el);
                childContainer.style.display = childContainer.style.display === 'none' ? 'block' : 'none';
                var icon = el.querySelector('i');
                if (childContainer.style.display === 'none') {
                    icon.className = 'bi bi-folder-fill fe-icon-dir';
                } else {
                    icon.className = 'bi bi-folder2-open fe-icon-dir';
                }
            });

            container.appendChild(el);
            container.appendChild(childContainer);
        } else {
            var icon = getFileIcon(item.name);
            el.innerHTML = '<i class="bi ' + icon + ' fe-icon-file"></i> ' + escHtml(item.name);
            el.addEventListener('click', function (e) {
                e.stopPropagation();
                openFile(item.path).then(function (opened) {
                    if (opened) {
                        selectTreeItem(el);
                    }
                });
            });
            container.appendChild(el);
        }
    });
}

function getFileIcon(name) {
    var ext = name.split('.').pop().toLowerCase();
    var map = {
        'php': 'bi-filetype-php',
        'html': 'bi-filetype-html',
        'htm': 'bi-filetype-html',
        'css': 'bi-filetype-css',
        'js': 'bi-filetype-js',
        'json': 'bi-filetype-json',
        'xml': 'bi-filetype-xml',
        'svg': 'bi-filetype-svg',
        'png': 'bi-filetype-png',
        'jpg': 'bi-filetype-jpg',
        'jpeg': 'bi-filetype-jpg',
        'gif': 'bi-filetype-gif',
        'txt': 'bi-filetype-txt',
        'md': 'bi-filetype-md'
    };
    return map[ext] || 'bi-file-earmark';
}

function escHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

var selectedTreeEl = null;
function selectTreeItem(el) {
    if (selectedTreeEl) selectedTreeEl.classList.remove('fe-selected');
    el.classList.add('fe-selected');
    selectedTreeEl = el;
}

function getSelectedPath() {
    return selectedTreeEl ? selectedTreeEl.dataset.path : '';
}

function getSelectedType() {
    return selectedTreeEl ? selectedTreeEl.dataset.type : '';
}

// ── API calls ──
var currentType = 'landing';
function apiCall(action, params, method) {
    method = method || 'POST';
    var url = 'fileeditor.php?action=' + action + '&folder=' + encodeURIComponent(currentFolder) + '&type=' + encodeURIComponent(currentType);

    if (method === 'GET') {
        Object.keys(params || {}).forEach(function (k) {
            url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
        });
        return fetch(url).then(function (r) { return r.json(); });
    }

    var fd = new FormData();
    Object.keys(params || {}).forEach(function (k) {
        fd.append(k, params[k]);
    });
    return fetch(url, { method: 'POST', body: fd }).then(function (r) { return r.json(); });
}

// ── Find index file in tree (root level: index.php > index.html > index.htm) ──
function findIndexFile(tree) {
    var names = ['index.php', 'index.html', 'index.htm'];
    for (var n = 0; n < names.length; n++) {
        for (var i = 0; i < tree.length; i++) {
            if (tree[i].type === 'file' && tree[i].name === names[n]) {
                return tree[i].path;
            }
        }
    }
    return null;
}

// ── Load tree ──
function loadTree() {
    apiCall('list', {}, 'GET').then(function (data) {
        if (data.error) {
            alert('Error loading tree: ' + data.result);
            return;
        }
        var treeEl = document.getElementById('fe-tree');
        renderTree(data.tree, treeEl);

        // Auto-open index file if no file is currently open
        if (!currentFile) {
            var indexFile = findIndexFile(data.tree);
            if (indexFile) {
                openFile(indexFile).then(function (opened) {
                    if (!opened) return;
                    var items = treeEl.querySelectorAll('.fe-tree-item');
                    for (var i = 0; i < items.length; i++) {
                        var el = items[i];
                        if (el.dataset.type === 'file' && el.dataset.path === indexFile) {
                            selectTreeItem(el);
                            break;
                        }
                    }
                });
            }
        }
    });
}

// ── Open file ──
function openFile(filePath) {
    if (hasUnsavedChanges() && !confirm('You have unsaved changes. Discard?')) return Promise.resolve(false);
    showEditorLoading('Loading file...');

    var ext = filePath.split('.').pop().toLowerCase();

    // Image preview instead of editor
    var imageExts = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp', 'svg', 'ico'];
    if (imageExts.indexOf(ext) !== -1) {
        currentFile = filePath;
        document.getElementById('fe-current-file').textContent = filePath;
        if (editorView) { editorView.destroy(); editorView = null; }
        var container = document.getElementById('fe-editor');
        container.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;padding:16px;">' +
            '<img src="../' + (currentType === 'white' ? (window.WHITE_FOLDER || 'caching/whites') : (window.LANDING_FOLDER || 'caching/landings')) + '/' + encodeURIComponent(currentFolder) + '/' + filePath.split('/').map(encodeURIComponent).join('/') + '" ' +
            'style="max-width:100%;max-height:100%;object-fit:contain;border-radius:4px;" />' +
            '</div>';
        isDirty = false;
        initialContent = '';
        return Promise.resolve(true);
    }

    // Other binary files
    var binaryExts = ['woff', 'woff2', 'ttf', 'eot', 'otf', 'zip', 'gz', 'tar', 'pdf', 'mp3', 'mp4', 'avi', 'mov'];
    if (binaryExts.indexOf(ext) !== -1) {
        alert('Binary file — cannot edit: ' + filePath);
        return Promise.resolve(false);
    }

    return apiCall('read', { file: filePath }, 'GET').then(function (data) {
        if (data.error) {
            alert('Error reading file: ' + data.result);
            return false;
        }
        currentFile = filePath;
        document.getElementById('fe-current-file').textContent = filePath;
        setEditorContent(data.content, ext);
        isDirty = false;
        return true;
    }).catch(function (err) {
        alert('Error reading file: ' + err);
        return false;
    });
}

// ── CodeMirror setup via local bundles ──
function setEditorContent(content, ext) {
    var container = document.getElementById('fe-editor');
    if (editorView) {
        editorView.destroy();
        editorView = null;
    }
    container.innerHTML = '';
    var bundle = getCm6Bundle(ext);
    if (!bundle) {
        container.textContent = 'CodeMirror not loaded';
        return;
    }
    cmInstance = bundle.load();
    editorView = cmInstance.newEditor(container, content, { dark: true, lineWrapping: true });
    showSearchPanelByDefault();
    initialContent = normalizeEditorContent(content);
    isDirty = false;
}

function hasUnsavedChanges() {
    if (!editorView || !currentFile) return false;
    return normalizeEditorContent(editorView.state.doc.toString()) !== normalizeEditorContent(initialContent);
}

function showSearchPanelByDefault() {
    if (!editorView || !editorView.contentDOM) return;
    setTimeout(function () {
        if (!editorView || !editorView.contentDOM) return;
        editorView.focus();
        editorView.contentDOM.dispatchEvent(new KeyboardEvent('keydown', {
            key: 'f',
            ctrlKey: true,
            bubbles: true,
            cancelable: true,
        }));
    }, 0);
}

// ── Save file ──
function saveFile() {
    if (!currentFile) {
        alert('No file open');
        return;
    }
    var content = editorView.state.doc.toString();
    apiCall('save', { file: currentFile, content: content }).then(function (data) {
        if (data.error) {
            alert('Save error: ' + data.result);
        } else {
            isDirty = false;
            initialContent = normalizeEditorContent(content);
            alert('Saved successfully!');
        }
    });
}

// ── Toolbar actions ──
function setupToolbar() {
    document.getElementById('fe-save-btn').addEventListener('click', saveFile);

    document.getElementById('fe-close-btn').addEventListener('click', function () {
        if (hasUnsavedChanges() && !confirm('You have unsaved changes. Close anyway?')) return;
        document.getElementById('fe-modal').style.display = 'none';
        document.body.classList.remove('fe-modal-open');
        currentFolder = '';
        currentFile = '';
        isDirty = false;
        initialContent = '';
        selectedTreeEl = null;
        document.getElementById('fe-tree').innerHTML = '';
        document.getElementById('fe-current-file').textContent = '';
        document.getElementById('fe-editor').innerHTML = '';
        if (editorView) {
            editorView.destroy();
            editorView = null;
        }
    });

    document.getElementById('fe-refresh-btn').addEventListener('click', loadTree);

    document.getElementById('fe-new-file').addEventListener('click', function () {
        var selPath = getSelectedPath();
        var selType = getSelectedType();
        var basePath = '';
        if (selPath) {
            basePath = selType === 'dir' ? selPath : selPath.substring(0, selPath.lastIndexOf('/'));
        }
        var name = prompt('New file name:', '');
        if (!name || !name.trim()) return;
        var filePath = basePath ? basePath + '/' + name.trim() : name.trim();
        apiCall('create', { file: filePath, type: 'file' }).then(function (data) {
            if (data.error) { alert(data.result); return; }
            loadTree();
            openFile(filePath);
        });
    });

    document.getElementById('fe-new-folder').addEventListener('click', function () {
        var selPath = getSelectedPath();
        var selType = getSelectedType();
        var basePath = '';
        if (selPath) {
            basePath = selType === 'dir' ? selPath : selPath.substring(0, selPath.lastIndexOf('/'));
        }
        var name = prompt('New folder name:', '');
        if (!name || !name.trim()) return;
        var dirPath = basePath ? basePath + '/' + name.trim() : name.trim();
        apiCall('create', { file: dirPath, type: 'dir' }).then(function (data) {
            if (data.error) { alert(data.result); return; }
            loadTree();
        });
    });

    document.getElementById('fe-upload-file').addEventListener('click', function () {
        var selPath = getSelectedPath();
        var selType = getSelectedType();
        var subpath = '';
        if (selPath) {
            subpath = selType === 'dir' ? selPath : selPath.substring(0, selPath.lastIndexOf('/'));
        }
        var fi = document.createElement('input');
        fi.type = 'file';
        fi.style.display = 'none';
        document.body.appendChild(fi);
        fi.addEventListener('change', function () {
            if (!fi.files.length) { fi.remove(); return; }
            var fd = new FormData();
            fd.append('file', fi.files[0]);
            fd.append('subpath', subpath);
            var url = 'fileeditor.php?action=upload&folder=' + encodeURIComponent(currentFolder) + '&type=' + encodeURIComponent(currentType);
            fetch(url, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error) { alert(data.result); return; }
                    loadTree();
                })
                .catch(function (err) { alert('Upload failed: ' + err); })
                .finally(function () { fi.remove(); });
        });
        fi.click();
    });

    document.getElementById('fe-rename-btn').addEventListener('click', function () {
        var selPath = getSelectedPath();
        if (!selPath) { alert('Select a file or folder first'); return; }
        var oldName = selPath.split('/').pop();
        var newName = prompt('Rename to:', oldName);
        if (!newName || !newName.trim() || newName.trim() === oldName) return;
        apiCall('rename', { file: selPath, newName: newName.trim() }).then(function (data) {
            if (data.error) { alert(data.result); return; }
            if (currentFile === selPath) {
                currentFile = data.newPath;
                document.getElementById('fe-current-file').textContent = data.newPath;
            }
            loadTree();
        });
    });

    document.getElementById('fe-download-btn').addEventListener('click', function () {
        var selPath = getSelectedPath();
        var selType = getSelectedType();
        if (!selPath || selType === 'dir') { alert('Select a file first'); return; }
        var url = '../' + (currentType === 'white' ? (window.WHITE_FOLDER || 'caching/whites') : (window.LANDING_FOLDER || 'caching/landings')) + '/' + encodeURIComponent(currentFolder) + '/' + selPath.split('/').map(encodeURIComponent).join('/');
        var a = document.createElement('a');
        a.href = url;
        a.download = selPath.split('/').pop();
        document.body.appendChild(a);
        a.click();
        a.remove();
    });

    document.getElementById('fe-delete-btn').addEventListener('click', function () {
        var selPath = getSelectedPath();
        if (!selPath) { alert('Select a file or folder first'); return; }
        if (!confirm('Delete "' + selPath + '"?')) return;
        apiCall('delete', { file: selPath }).then(function (data) {
            if (data.error) { alert(data.result); return; }
            if (currentFile === selPath) {
                currentFile = '';
                document.getElementById('fe-current-file').textContent = '';
                if (editorView) {
                    setEditorContent('', 'txt');
                }
            }
            selectedTreeEl = null;
            loadTree();
        });
    });
}

// ── Keyboard shortcut (Ctrl+S) at document level ──
document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        var modal = document.getElementById('fe-modal');
        if (modal && modal.style.display !== 'none') {
            e.preventDefault();
            saveFile();
        }
    }
});

// ── Public API ──
export function openFileEditor(folderName, type) {
    var modal = document.getElementById('fe-modal');
    if (!modal) {
        modal = buildModal();
        setupToolbar();
    }

    currentFolder = folderName;
    currentType = type || 'landing';
    currentFile = '';
    isDirty = false;
    document.getElementById('fe-folder-name').textContent = folderName;
    document.getElementById('fe-current-file').textContent = '';
    document.getElementById('fe-editor').innerHTML = '';
    document.getElementById('fe-tree').innerHTML = '';
    selectedTreeEl = null;
    initialContent = '';

    modal.style.display = 'flex';
    document.body.classList.add('fe-modal-open');
    showEditorLoading('Loading files...');
    loadTree();
}

// Window export for backward compat with inline scripts
window.openFileEditor = openFileEditor;
