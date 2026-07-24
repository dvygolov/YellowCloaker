(function (global) {
  'use strict';

  const transport = global.__yellowTdsEventTransport;
  const allowedEvents = new Set(
    Array.isArray({CUSTOM_EVENTS_JSON}) ? {CUSTOM_EVENTS_JSON} : []
  );

  if (!transport || allowedEvents.size === 0) {
    return;
  }

  global.ytdsEvent = function (eventName) {
    const normalizedName = typeof eventName === 'string' ? eventName.trim() : '';
    if (!allowedEvents.has(normalizedName)) {
      return Promise.reject(new TypeError('Event is not enabled in this YellowTDS campaign'));
    }
    return transport.sendEvent(normalizedName);
  };
})(window);
