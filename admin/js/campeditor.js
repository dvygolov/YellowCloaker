document.addEventListener("DOMContentLoaded", function () {
    function reloadWithConfig(configName) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('config', configName);
        const newUrl = `${window.location.origin}${window.location.pathname}?${urlParams.toString()}`;
        window.location.href = newUrl;
    }

    function campEditor(body) {
        let res = await fetch("campaigneditor.php", {
            method: "POST",
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: body
        });
        return res.json();
    }

    document.getElementById("newcampaign").onclick = async () => {
        let configName = prompt("Enter new campaign name:");
        if (configName === '' || configName == null) return;
        let js = await campEditor(`action=add&name=${configName}`)
        if (js["result"] === "OK")
            reloadWithConfig(configName);
        else
            alert(`An error occured: ${js["result"]}`);
    };

    document.getElementById("dupconfig").onclick = async () => {
        let curConfigName = "<?= $config ?>";
        let newConfigName = prompt("Enter new config name:");
        if (newConfigName === '' || newConfigName == null) return;

        let js = await campEditor(`action=dup&name=${curConfigName}&dupname=${newConfigName}`);
        if (js["result"] === "OK")
            reloadWithConfig(newConfigName);
        else
            alert(`An error occured: ${js["result"]}`);
    };

    document.getElementById("delconfig").onclick = async () => {
        let config = "<?= $config ?>";
        if (config === 'default') {
            alert("Can't delete default config!");
            return;
        }
        if (!confirm(`Are you sure you want to delete this config: ${config}?`)) return;
        let res = await fetch("campaigneditor.php", {
            method: "POST",
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=del&name=${config}`
        });
        let js = await res.json();
        if (js["result"] === "OK")
            reloadWithConfig("default");
        else
            alert(`An error occured: ${js["result"]}`);
    };
});
