(function (global) {
  'use strict';

  const transport = global.__yellowTdsEventTransport;
  const thresholds = Array.from(new Set(
    (Array.isArray({TIME_THRESHOLDS_JSON}) ? {TIME_THRESHOLDS_JSON} : [])
      .map(Number)
      .filter(function (value) {
        return Number.isInteger(value) && value > 0;
      })
  )).sort(function (left, right) {
    return left - right;
  });

  if (!transport || thresholds.length === 0) {
    return;
  }

  let accumulatedVisible = 0;
  let visibleSince = document.visibilityState === 'visible' ? performance.now() : null;
  const sent = new Set();
  const pending = new Map();
  let timer = 0;

  function visibleMilliseconds(now) {
    return accumulatedVisible + (visibleSince === null ? 0 : now - visibleSince);
  }

  function clearDeadline() {
    if (timer) {
      clearTimeout(timer);
      timer = 0;
    }
  }

  function reportReachedThresholds() {
    const now = performance.now();
    const visible = visibleMilliseconds(now);

    thresholds.forEach(function (threshold) {
      if (
        visible >= threshold * 1000
        && !sent.has(threshold)
        && !pending.has(threshold)
      ) {
        const request = transport.sendEvent('stay_' + threshold + 's').then(
          function (response) {
            pending.delete(threshold);
            sent.add(threshold);
            return response;
          }
        ).catch(function () {
          pending.delete(threshold);
        });
        pending.set(threshold, request);
      }
    });
    scheduleDeadline();
  }

  function scheduleDeadline() {
    clearDeadline();
    if (visibleSince === null) {
      return;
    }

    const visible = visibleMilliseconds(performance.now());
    const nextThreshold = thresholds.find(function (threshold) {
      return threshold * 1000 > visible;
    });
    if (typeof nextThreshold === 'undefined') {
      return;
    }

    const remaining = Math.max(0, nextThreshold * 1000 - visible);
    timer = window.setTimeout(
      reportReachedThresholds,
      Math.min(remaining, 2147483647)
    );
  }

  document.addEventListener('visibilitychange', function () {
    const now = performance.now();
    if (document.visibilityState === 'visible') {
      if (visibleSince === null) {
        visibleSince = now;
      }
    } else if (visibleSince !== null) {
      accumulatedVisible += now - visibleSince;
      visibleSince = null;
    }
    reportReachedThresholds();
  });

  scheduleDeadline();
})(window);
