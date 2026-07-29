const scriptTag = document.currentScript;
const redirect = scriptTag.hasAttribute("data-redirect")
  ? scriptTag.getAttribute("data-redirect") == "true"
  : false;
const links = JSON.parse(scriptTag.getAttribute("data-links") || "[]");
const traceEnabled = scriptTag.hasAttribute("data-traceenabled")
  ? scriptTag.getAttribute("data-traceenabled") == "true"
  : false;

document.addEventListener("DOMContentLoaded", function() {
  var frameNames = [];
  for (var i = 0; i < links.length; i++) {
    frameNames.push("BfFrame_" + i);
  }
  var viewCount = links.length + 1; // landing(0) + N links

  trace(
    "Back Button Fix v0.6.0 by Yellow Web",
    "font-size:25px;color:yellow;font-weight:bold",
  );

  if (isLocalHost()) {
    if (traceEnabled) trace("Localhost found!");
    else return;
  }
  if (isInIframe()) {
    trace("We are in frame!");
    //return;
  }
  trace(`Links: ${JSON.stringify(links)}`);
  trace(`Mode is: ${redirect ? "Redirect" : "Iframe"}`);
  if (links.length === 0) {
    trace("No links provided, backfix disabled.");
    return;
  }
  removeAnchors();
  trace("Anchors fix started...");
  backInFrame();

  // Carousel views: 0=landing, 1..N=links[0..N-1]
  // Going back cycles: landing -> link1 -> link2 -> ... -> linkN -> landing -> ...
  // Going forward reverses the cycle
  const DEPTH = 50;

  function backInFrame() {
    function init() {
      // Create first iframe immediately, rest lazily
      createFrame(frameNames[0], links[0]);
      var idle = window.requestIdleCallback || function(cb) { setTimeout(cb, 1); };
      for (var j = 1; j < links.length; j++) {
        (function(idx) {
          idle(function() { createFrame(frameNames[idx], links[idx]); });
        })(j);
      }
      // Push states with pre-computed view for each level
      for (var i = DEPTH; i >= 1; i--) {
        var view = i % viewCount;
        window.history.pushState({ bf: true, view: view }, "", window.location);
      }
      // Final state on top = landing (where user is now)
      window.history.pushState({ bf: true, view: 0 }, "", window.location);
      trace(`Pushed ${DEPTH + 1} states for ${viewCount} views.`);
    }

    if (!isIos()) {
      trace("Not IOs, cheching gesture!");
      checkUserGesture(function() {
        init();
        trace("Initialized after gesture.");
      });
    } else {
      trace("IOs found!");
      init();
      trace("Initialized.");
    }

    window.onpopstate = function(t) {
      if (!t.state || !t.state.bf) {
        trace("OnPopState: not our state!");
        return;
      }

      trace(`Popped state: view=${t.state.view}`);

      if (redirect) {
        window.location.href = links[0];
        return;
      }

      showView(t.state.view);
    };
  }

  function showView(viewIndex) {
    if (viewIndex === 0) {
      // Show landing (original page)
      trace("Showing landing page.");
      frameNames.forEach(function(fn) {
        var f = document.getElementById(fn);
        if (f) f.style.display = "none";
      });
      document.querySelectorAll("body > *").forEach(function(e) {
        if (frameNames.indexOf(e.id) === -1) {
          e.style.display = "";
        }
      });
      document.body.style.overflow = "";
    } else {
      showFrame(frameNames[viewIndex - 1]);
    }
  }

  function createFrame(name, url) {
    if (redirect) {
      trace("Creating prerender for redirect.");
      let prerender = document.createElement("link");
      prerender.rel = "prerender";
      prerender.href = url;
      document.head.appendChild(prerender);
    } else {
      var nodeFrame = document.getElementById(name);
      if (nodeFrame) nodeFrame.parentNode.removeChild(nodeFrame);
      var frame = document.createElement("iframe");
      frame.style.width = "100%";
      frame.id = name;
      frame.name = name;
      frame.style.height = "100vh";
      frame.style.position = "fixed";
      frame.style.top = 0;
      frame.style.left = 0;
      frame.style.border = "none";
      frame.style.zIndex = 999997;
      frame.style.display = "none";
      frame.style.backgroundColor = "#fff";
      document.body.append(frame);
      frame.src = url;
      trace(`Created frame ${name} for ${url}!`);
    }
  }

  function showFrame(name) {
    var nodeFrame = document.getElementById(name);
    nodeFrame.style.display = "block";
    document.body.style.overflow = "hidden";
    document.querySelectorAll(`body > *:not(#${name})`).forEach(function(e) {
      e.style.display = "none";
    });
    trace(`Frame ${name} displayed!`);
  }

  function checkUserGesture(callback) {
    var audio = document.createElement("audio");
    var st = setInterval(function() {
      var playPromise = audio.play();
      if (playPromise instanceof Promise) {
        if (!audio.paused) {
          clearInterval(st);
          callback();
        }
        playPromise.then(function() { }).catch(function() { });
      } else {
        if (!audio.paused) {
          clearInterval(st);
          callback();
        }
      }
    }, 100);
  }

  function removeAnchors() {
    setInterval(function() {
      const anchors = document.querySelectorAll('a[href*="#"]');
      for (let anchor of anchors) {
        if (anchor.dataset.bfFixed) continue;
        anchor.dataset.bfFixed = "1";
        anchor.removeAttribute("onclick");
        anchor.addEventListener("click", function(e) {
          e.preventDefault();
          const blockID = anchor.getAttribute("href").substring(1);
          document.getElementById(blockID).scrollIntoView({
            behavior: "smooth",
            block: "start",
          });
        });
      }
    }, 1000);
  }

  function isInIframe() {
    try {
      return (
        window != window.top ||
        document != top.document ||
        self.location != top.location
      );
    } catch (e) {
      return true;
    }
  }

  function isIos() {
    return /(iPad|iPod|iPhone|Mac)/i.test(navigator.platform);
  }

  function isLocalHost() {
    return (
      window.location.host.includes("localhost") ||
      window.location.host.includes("127.0.0.1") ||
      window.location.protocol === "file:"
    );
  }
  function trace(msg, style = null) {
    if (!traceEnabled) return;
    if (style == null) console.log("Backfix: " + msg);
    else {
      console.log("%c" + msg, style);
    }
  }
});