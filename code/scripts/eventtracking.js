(function (global) {
  'use strict';

  if (global.__yellowTdsEventTransport) {
    return;
  }

  const endpoint = {EVENT_API_URL_JSON};
  const clickId = {CLICK_ID_JSON};
  const stepIndex = {STEP_INDEX_JSON};
  const variant = {VARIANT_JSON};
  const trackerStart = performance.now();
  const eventRequests = new Map();
  let performanceRequest = null;
  const retryDelays = [0, 250, 1000];

  function wait(delay) {
    return new Promise(function (resolve) {
      window.setTimeout(resolve, delay);
    });
  }

  function post(payload) {
    const body = JSON.stringify(payload);

    function attempt(attemptIndex) {
      return Promise.resolve().then(function () {
        return fetch(endpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'text/plain;charset=UTF-8'
          },
          body: body,
          mode: 'cors',
          credentials: 'omit',
          keepalive: true
        });
      }).then(function (response) {
        if (response.ok) {
          return response;
        }

        const error = new Error(
          'YellowTDS event request failed with HTTP ' + response.status
        );
        const retryable = response.status === 429
          || (response.status >= 500 && response.status <= 599);
        if (retryable && attemptIndex + 1 < retryDelays.length) {
          return wait(retryDelays[attemptIndex + 1]).then(function () {
            return attempt(attemptIndex + 1);
          });
        }
        throw error;
      }, function (error) {
        if (attemptIndex + 1 < retryDelays.length) {
          return wait(retryDelays[attemptIndex + 1]).then(function () {
            return attempt(attemptIndex + 1);
          });
        }
        throw error;
      });
    }

    return attempt(0);
  }

  function sendEvent(eventName) {
    if (
      typeof eventName !== 'string'
      || !/^[a-z][a-z0-9_]{0,63}$/.test(eventName)
      || eventName === 'performance'
      || eventName.indexOf('performance_') === 0
    ) {
      return Promise.reject(new TypeError('Invalid YellowTDS event name'));
    }

    if (eventRequests.has(eventName)) {
      return eventRequests.get(eventName);
    }

    const request = post({
      clickid: clickId,
      step_index: stepIndex,
      variant: variant,
      event: eventName,
      value: Math.max(0, Math.round(performance.now() - trackerStart))
    }).catch(function (error) {
      eventRequests.delete(eventName);
      throw error;
    });
    eventRequests.set(eventName, request);
    return request;
  }

  function sendPerformance(metrics) {
    if (performanceRequest) {
      return performanceRequest;
    }

    const request = post({
      clickid: clickId,
      step_index: stepIndex,
      variant: variant,
      performance: metrics
    }).catch(function (error) {
      if (performanceRequest === request) {
        performanceRequest = null;
      }
      throw error;
    });
    performanceRequest = request;
    return request;
  }

  global.__yellowTdsEventTransport = Object.freeze({
    sendEvent: sendEvent,
    sendPerformance: sendPerformance
  });
})(window);
