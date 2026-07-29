(function (global) {
  'use strict';

  const transport = global.__yellowTdsEventTransport;
  const vitals = global.webVitals;
  if (!transport || !vitals) {
    return;
  }

  const requiredMetrics = ['ttfb', 'fcp', 'lcp', 'inp', 'cls'];
  const metrics = Object.create(null);
  const reportOptions = { reportAllChanges: true };
  let completed = false;
  let pendingRequest = null;
  let deadline = 0;
  let deadlinePassed = false;

  function normalizeMetric(metric) {
    if (!metric || !Number.isFinite(metric.value)) {
      return;
    }

    const name = String(metric.name || '').toLowerCase();
    if (requiredMetrics.indexOf(name) === -1) {
      return;
    }
    metrics[name] = name === 'cls'
      ? Math.round(metric.value * 10000) / 10000
      : Math.max(0, Math.round(metric.value));

    if (deadlinePassed && !completed && !pendingRequest) {
      requestSnapshot();
    }
  }

  function clearDeadline() {
    if (deadline) {
      clearTimeout(deadline);
      deadline = 0;
    }
  }

  function sendSnapshot() {
    if (completed) {
      return Promise.resolve(null);
    }

    if (pendingRequest) {
      return pendingRequest;
    }

    if (Object.keys(metrics).length === 0) {
      return Promise.resolve(null);
    }

    const snapshot = Object.assign({}, metrics);
    const request = transport.sendPerformance(snapshot).then(function (response) {
      completed = true;
      pendingRequest = null;
      clearDeadline();
      return response;
    }).catch(function (error) {
      if (pendingRequest === request) {
        pendingRequest = null;
      }
      throw error;
    });
    pendingRequest = request;
    return request;
  }

  function requestSnapshot() {
    sendSnapshot().catch(function () {});
  }

  function controlledLinkNavigation(event) {
    if (
      event.defaultPrevented
      || event.button !== 0
      || event.metaKey
      || event.ctrlKey
      || event.shiftKey
      || event.altKey
    ) {
      return;
    }

    const target = event.target instanceof Element
      ? event.target.closest('a[href]')
      : null;
    if (
      !target
      || target.hasAttribute('download')
      || (target.target && target.target.toLowerCase() !== '_self')
    ) {
      return;
    }

    let destination;
    try {
      destination = new URL(target.href, document.baseURI);
    } catch (error) {
      return;
    }
    if (destination.protocol !== 'http:' && destination.protocol !== 'https:') {
      return;
    }

    const current = new URL(window.location.href);
    const hashOnly = destination.origin === current.origin
      && destination.pathname === current.pathname
      && destination.search === current.search
      && destination.hash !== current.hash;
    if (!hashOnly) {
      requestSnapshot();
    }
  }

  function controlledFormNavigation(event) {
    const form = event.target;
    if (event.defaultPrevented || !(form instanceof HTMLFormElement)) {
      return;
    }

    const submitter = event.submitter instanceof HTMLElement
      ? event.submitter
      : null;
    const method = submitter && submitter.hasAttribute('formmethod')
      ? (submitter.getAttribute('formmethod') || 'get')
      : form.method;
    const navigationTarget = submitter && submitter.hasAttribute('formtarget')
      ? (submitter.getAttribute('formtarget') || '')
      : form.target;

    if (
      String(method || 'get').toLowerCase() !== 'dialog'
      && (!navigationTarget || String(navigationTarget).toLowerCase() === '_self')
    ) {
      requestSnapshot();
    }
  }

  function deferNavigationCheck(handler, event) {
    Promise.resolve().then(function () {
      handler(event);
    });
  }

  function visibilityChanged() {
    if (document.visibilityState === 'hidden') {
      requestSnapshot();
    }
  }

  vitals.onTTFB(normalizeMetric, reportOptions);
  vitals.onFCP(normalizeMetric, reportOptions);
  vitals.onLCP(normalizeMetric, reportOptions);
  vitals.onINP(normalizeMetric, reportOptions);
  vitals.onCLS(normalizeMetric, reportOptions);

  // Capture cannot be skipped by a descendant stopPropagation(). Deferring the
  // check lets all landing handlers cancel the event before a snapshot starts.
  window.addEventListener('click', function (event) {
    deferNavigationCheck(controlledLinkNavigation, event);
  }, true);
  window.addEventListener('submit', function (event) {
    deferNavigationCheck(controlledFormNavigation, event);
  }, true);
  document.addEventListener('visibilitychange', visibilityChanged);

  deadline = window.setTimeout(
    function () {
      deadline = 0;
      deadlinePassed = true;
      requestSnapshot();
    },
    Math.max(0, 10000 - performance.now())
  );
})(window);
