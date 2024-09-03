document.addEventListener("DOMContentLoaded", function () {

    document.getElementById("newcampaign").onclick = async () => {
        let campName = prompt("Enter new campaign name:");
        if (campName)
            await campEditor('add', null, campName);
    };
});

async function campEditor(action, campId=null, name=null) {
    let body = `action=${action}`;
    if (campId)
        body += `&campId=${campId}`;
    if (name)
        body += `&name=${name}`;

    let res = await fetch("campeditor.php", {
        method: "POST",
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: body
    });
    let js = await res.json();
    if (js.error)
        alert(`An error occured: ${js.result}`);
    else
        window.location.reload();
}

async function campActionsHandler(e, cell) {
    let target = e.target;

    // Check if the target is the <i> element, if so, get the parent <button>
    if (target.tagName === 'I') {
        target = target.closest('button');
    }

    const row = cell.getRow();
    const campaignId = row.getData().id;

    if (target.classList.contains('btn-rename')) {
        const newName = prompt("Enter new campaign name:");
        if (newName) {
            await campEditor('ren', campaignId, newName);
        }
        else
            alert('Campaign name can not be empty!');
    }

    if (target.classList.contains('btn-delete')) {
        if (confirm(`Are you sure? Going to delete campaign ${row.getData().name}.`)) {
            await campEditor('del', campaignId);
        }
    }

    if (target.classList.contains('btn-clone')) {
        await campEditor('dup', campaignId);
    }

    if (target.classList.contains('btn-copy-link')) {
        const link = `https://example.com/campaign/${campaignId}`;
        await navigator.clipboard.writeText(link);
        alert(`Link ${link} copied to clipboard!`);
    }

    if (target.classList.contains('btn-stats')) {
        window.location.href = `statistics.php?campId=${campaignId}`;
    }
}
