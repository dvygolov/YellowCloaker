import { getFlowDist, redistributeWeights } from './weights.js?v=16072602';
import { buildFolderRow } from './templates.js?v=16072603';

// ── Upload ZIP: pick file, prompt folder name, upload, insert row ──
export function handleZipUpload(btn) {
    var fi = btn.dataset.fi;
    if (!fi) return;

    // Find the step section's folder items container
    var stepSec = btn.closest('.step-section');
    if (!stepSec) return;
    var container = stepSec.querySelector('.flow-step-folder-items');
    if (!container) return;
    var showWeight = getFlowDist(fi) === 'weighted';

    // Pick file first (preserves user gesture), then ask for folder name
    var fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = '.zip';
    fileInput.style.display = 'none';
    document.body.appendChild(fileInput);

    fileInput.addEventListener('change', function () {
        if (!fileInput.files.length) { fileInput.remove(); return; }
        var file = fileInput.files[0];

        var folderName = prompt('Enter folder name for uploaded files:');
        if (!folderName || !folderName.trim()) { fileInput.remove(); return; }
        folderName = folderName.trim();
        if (!/^[a-zA-Z0-9_\-\.]+$/.test(folderName)) {
            alert('Invalid folder name. Use only letters, numbers, hyphens, underscores, dots.');
            fileInput.remove();
            return;
        }

        var fd = new FormData();
        fd.append('zipfile', file);
        fd.append('folder', folderName);

        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Uploading...';
        btn.style.pointerEvents = 'none';

        fetch('zipupload.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) {
                    alert('Upload error: ' + data.result);
                } else {
                    container.appendChild(buildFolderRow(data.folder, showWeight));
                    if (showWeight) {
                        redistributeWeights(container.querySelectorAll('.flow-step-weight'));
                    }
                }
            })
            .catch(function (err) { alert('Upload failed: ' + err); })
            .finally(function () {
                btn.innerHTML = '<i class="bi bi-upload"></i> Upload ZIP';
                btn.style.pointerEvents = '';
                fileInput.remove();
            });
    });
    fileInput.click();
}
