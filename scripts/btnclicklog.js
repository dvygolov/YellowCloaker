async function logconversion(element) {
    console.log('starting logging');
    //get the form element
    let node = element;
    while (node.nodeName != "FORM" && node.parentNode) {
        node = node.parentNode;
    }

    let lead = "";
    let hasName = false;
    let hasPhone = false;
    let inputs = node.getElementsByTagName('input');
    console.log('found ' + inputs.length + ' inputs');
    for (let i = 0; i < inputs.length; i++) {
        let input = inputs[i];
        if (input.name == "name" && input.value !== '') {
            hasName = true;
            console.log('found name');
            lead += input.name + "=" + input.value + "&";
        }
        if (input.name == "phone" && input.value !== '') {
            console.log('found phone');
            hasPhone = true;
            lead += input.name + "=" + input.value + "&";
        }
    }
    if (hasName && hasPhone) {
        await postButtonLog(lead);
    } else {
        console.log("No name or phone to log conversion!");
    }
}

async function postButtonLog(lead) {
    try {
        const response = await fetch('../buttonlog.php', {
            method: 'POST',
            headers: {
                'Content-type': 'application/x-www-form-urlencoded'
            },
            body: lead
        });
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error:', error);
    }
}


function addconversionlog() {
    let buttons = document.querySelectorAll("form button");
    buttons.forEach(function (button) {
        button.addEventListener('click', async function () {
            await logconversion(this);
        });
    });

    let submits = document.querySelectorAll("form input[type='submit']");
    submits.forEach(function (submit) {
        submit.addEventListener('click', async function () {
            await logconversion(this);
        });
    });
}

window.addEventListener('DOMContentLoaded', addconversionlog, false);