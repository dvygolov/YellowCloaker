(function (global) {
  'use strict';

  const transport = global.__yellowTdsEventTransport;
  const thresholds = Array.from(new Set(
    (Array.isArray({SCROLL_THRESHOLDS_JSON}) ? {SCROLL_THRESHOLDS_JSON} : [])
      .map(Number)
      .filter(function (value) {
        return Number.isInteger(value) && value > 0 && value <= 100;
      })
  )).sort(function (left, right) {
    return left - right;
  });

  if (!transport || thresholds.length === 0) {
    return;
  }

  const sent = new Set();
  const pending = new Map();
  let animationFrame = 0;

  function sendThreshold(threshold) {
    if (sent.has(threshold) || pending.has(threshold)) {
      return;
    }

    const request = transport.sendEvent('scroll_' + threshold).then(function (response) {
      pending.delete(threshold);
      sent.add(threshold);
      return response;
    }).catch(function () {
      pending.delete(threshold);
    });
    pending.set(threshold, request);
  }

  function reportDepth() {
    animationFrame = 0;
    const root = document.documentElement;
    const body = document.body;
    const viewportHeight = window.innerHeight || root.clientHeight || 0;
    const documentHeight = Math.max(
      root.scrollHeight || 0,
      root.offsetHeight || 0,
      root.clientHeight || 0,
      body ? body.scrollHeight || 0 : 0,
      body ? body.offsetHeight || 0 : 0
    );
    const scrollTop = Math.max(
      window.scrollY || root.scrollTop || (body ? body.scrollTop : 0) || 0,
      0
    );
    const depth = documentHeight <= viewportHeight
      ? 100
      : Math.min(100, ((scrollTop + viewportHeight) / documentHeight) * 100);

    thresholds.forEach(function (threshold) {
      if (depth >= threshold) {
        sendThreshold(threshold);
      }
    });
  }

  function scheduleDepthCheck() {
    if (animationFrame) {
      return;
    }
    animationFrame = requestAnimationFrame(reportDepth);
  }

  window.addEventListener('scroll', scheduleDepthCheck, { passive: true });
  window.addEventListener('resize', scheduleDepthCheck);
  scheduleDepthCheck();
})(window);
