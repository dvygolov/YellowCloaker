function replaceContent(b64) {
    var html = decodeURIComponent(escape(atob(b64)));
    var tempDiv = document.createElement('div');
    tempDiv.innerHTML = html;

    var scripts = tempDiv.querySelectorAll('script');
    var scriptContents = [];
    var scriptSrcs = [];

    scripts.forEach(function (script) {
        if (script.getAttribute('src')) {
            // Store src and all attributes
            var scriptInfo = {
                src: script.getAttribute('src'),
                attributes: {}
            };
            
            // Copy all attributes except src
            for (var i = 0; i < script.attributes.length; i++) {
                var attr = script.attributes[i];
                if (attr.name !== 'src') {
                    scriptInfo.attributes[attr.name] = attr.value;
                }
            }
            
            scriptSrcs.push(scriptInfo);
        } else {
            scriptContents.push(script.innerHTML);
        }
        script.remove();
    });

    document.documentElement.innerHTML = tempDiv.innerHTML;

    // Load external scripts first and wait for them to complete
    var loadPromises = scriptSrcs.map(function(scriptInfo) {
        return new Promise(function(resolve, reject) {
            var script = document.createElement('script');
            script.src = scriptInfo.src;
            for (var attr in scriptInfo.attributes) {
                script.setAttribute(attr, scriptInfo.attributes[attr]);
            }
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    });

    // Execute inline scripts only after all external scripts have loaded
    Promise.all(loadPromises).then(function() {
        scriptContents.forEach(function (content) {
            var script = document.createElement('script');
            script.innerHTML = content;
            document.head.appendChild(script);
        });
    }).catch(function(error) {
        console.error('Error loading external scripts:', error);
        // Execute inline scripts anyway in case of error
        scriptContents.forEach(function (content) {
            var script = document.createElement('script');
            script.innerHTML = content;
            document.head.appendChild(script);
        });
    }).finally(function() {
        //fake DOMContentLoaded, hehe \m/
        if (document.readyState !== 'loading') {
            console.log("DISPATCHING FAKE DOMContentLoaded...");
            window.document.dispatchEvent(
                new Event("DOMContentLoaded", {
                    bubbles: false,
                    cancelable: true,
                }),
            );
            console.log("FINISHED DISPATCHING FAKE DOMContentLoaded!");
        }else{
            console.log("DOCUMENT IS STILL LOADING, NO NEED FOR FAKE DISPATCHER");
        }
    });
};
