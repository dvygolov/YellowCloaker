'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const scriptsDirectory = path.resolve(__dirname, '../../../code/scripts');

class FakeClock {
  constructor(now = 0) {
    this.now = now;
    this.nextTimerId = 1;
    this.timers = new Map();
  }

  setTimeout(callback, delay = 0) {
    const id = this.nextTimerId;
    this.nextTimerId += 1;
    this.timers.set(id, {
      callback: callback,
      time: this.now + Math.max(0, Number(delay) || 0)
    });
    return id;
  }

  clearTimeout(id) {
    this.timers.delete(id);
  }

  async advance(milliseconds) {
    const target = this.now + milliseconds;
    await flushMicrotasks();

    while (true) {
      let nextId = 0;
      let nextTimer = null;
      for (const [id, timer] of this.timers) {
        if (
          timer.time <= target
          && (
            nextTimer === null
            || timer.time < nextTimer.time
            || (timer.time === nextTimer.time && id < nextId)
          )
        ) {
          nextId = id;
          nextTimer = timer;
        }
      }

      if (nextTimer === null) {
        break;
      }

      this.now = nextTimer.time;
      this.timers.delete(nextId);
      nextTimer.callback();
      await flushMicrotasks();
    }

    this.now = target;
    await flushMicrotasks();
  }
}

class FakeEventTarget {
  constructor() {
    this.listeners = new Map();
  }

  addEventListener(type, listener, options) {
    const listeners = this.listeners.get(type) || [];
    listeners.push({
      capture: options === true
        || Boolean(options && typeof options === 'object' && options.capture),
      listener: listener,
      once: Boolean(options && typeof options === 'object' && options.once)
    });
    this.listeners.set(type, listeners);
  }

  dispatchEvent(event) {
    if (!event.target) {
      event.target = this;
    }
    const listeners = (this.listeners.get(event.type) || []).slice();
    listeners.forEach((entry) => {
      entry.listener.call(this, event);
      if (entry.once) {
        const current = this.listeners.get(event.type) || [];
        this.listeners.set(
          event.type,
          current.filter((candidate) => candidate !== entry)
        );
      }
    });
  }
}

class FakeElement extends FakeEventTarget {
  constructor() {
    super();
    this.attributes = new Map();
    this.href = '';
    this.target = '';
  }

  closest(selector) {
    return selector === 'a[href]' && this.href ? this : null;
  }

  hasAttribute(name) {
    return this.attributes.has(name);
  }

  getAttribute(name) {
    return this.attributes.has(name) ? this.attributes.get(name) : null;
  }

  setAttribute(name, value) {
    this.attributes.set(name, String(value));
  }

  removeAttribute(name) {
    this.attributes.delete(name);
  }
}

class FakeHTMLElement extends FakeElement {}

class FakeHTMLFormElement extends FakeHTMLElement {
  constructor() {
    super();
    this.method = 'get';
    this.submitted = false;
  }

  submit() {
    this.submitted = true;
  }
}

function flushMicrotasks() {
  return new Promise((resolve) => {
    queueMicrotask(() => queueMicrotask(resolve));
  });
}

function deferred() {
  let resolve;
  let reject;
  const promise = new Promise((resolvePromise, rejectPromise) => {
    resolve = resolvePromise;
    reject = rejectPromise;
  });
  return { promise, reject, resolve };
}

function okResponse(status = 204) {
  return { ok: true, status: status };
}

function errorResponse(status) {
  return { ok: false, status: status };
}

function createBrowserSandbox(options = {}) {
  const clock = options.clock || new FakeClock();
  const window = new FakeEventTarget();
  const document = new FakeEventTarget();

  document.baseURI = 'https://landing.example/';
  document.readyState = 'loading';
  document.visibilityState = 'visible';
  document.documentElement = {
    clientHeight: 100,
    offsetHeight: 1000,
    scrollHeight: 1000,
    scrollTop: 0
  };
  document.body = {
    offsetHeight: 1000,
    scrollHeight: 1000,
    scrollTop: 0
  };

  window.document = document;
  window.innerHeight = 100;
  window.location = {
    href: 'https://landing.example/',
    assign(url) {
      this.href = url;
    }
  };
  window.scrollY = 0;
  window.setTimeout = clock.setTimeout.bind(clock);
  window.clearTimeout = clock.clearTimeout.bind(clock);

  const sandbox = {
    URL,
    Element: FakeElement,
    HTMLElement: FakeHTMLElement,
    HTMLFormElement: FakeHTMLFormElement,
    clearTimeout: clock.clearTimeout.bind(clock),
    document,
    fetch: options.fetch || (() => Promise.resolve(okResponse())),
    performance: {
      now: () => clock.now
    },
    requestAnimationFrame: (callback) => clock.setTimeout(callback, 0),
    setTimeout: clock.setTimeout.bind(clock),
    window
  };

  return { clock, document, sandbox, window };
}

function executeScript(fileName, sandbox, replacements = {}) {
  let source = fs.readFileSync(path.join(scriptsDirectory, fileName), 'utf8');
  Object.entries(replacements).forEach(([placeholder, value]) => {
    source = source.split(placeholder).join(JSON.stringify(value));
  });
  vm.runInNewContext(source, sandbox, { filename: fileName });
}

function loadTransport(fetchImplementation, start = 0) {
  const browser = createBrowserSandbox({
    clock: new FakeClock(start),
    fetch: fetchImplementation
  });
  executeScript('eventtracking.js', browser.sandbox, {
    '{CLICK_ID_JSON}': 'click-1',
    '{EVENT_API_URL_JSON}': 'https://tracker.example/api/events.php',
    '{STEP_INDEX_JSON}': 2,
    '{VARIANT_JSON}': 'landing-a'
  });
  return {
    ...browser,
    transport: browser.window.__yellowTdsEventTransport
  };
}

function createVitalsHarness(options = {}) {
  const browser = createBrowserSandbox({
    clock: new FakeClock(options.start || 0)
  });
  const callbacks = {};
  const calls = [];
  const sendPerformance = options.sendPerformance || ((metrics) => {
    calls.push(JSON.parse(JSON.stringify(metrics)));
    return Promise.resolve(okResponse());
  });

  browser.window.__yellowTdsEventTransport = { sendPerformance };
  browser.window.webVitals = {};
  ['TTFB', 'FCP', 'LCP', 'INP', 'CLS'].forEach((name) => {
    browser.window.webVitals['on' + name] = (callback, callbackOptions) => {
      assert.equal(callbackOptions.reportAllChanges, true);
      callbacks[name.toLowerCase()] = callback;
    };
  });

  executeScript('performancetracking.js', browser.sandbox);

  return {
    ...browser,
    callbacks,
    calls,
    emit(name, value) {
      callbacks[name.toLowerCase()]({ name, value });
    }
  };
}

function navigationEvent(target) {
  return {
    altKey: false,
    button: 0,
    ctrlKey: false,
    defaultPrevented: false,
    metaKey: false,
    shiftKey: false,
    target,
    type: 'click',
    preventDefault() {
      this.defaultPrevented = true;
    }
  };
}

function formNavigationEvent(form, submitter = null) {
  return {
    defaultPrevented: false,
    submitter,
    target: form,
    type: 'submit',
    preventDefault() {
      this.defaultPrevented = true;
    }
  };
}

test('transport retries network and retryable HTTP failures with the same body', async () => {
  const requests = [];
  const responses = [
    () => Promise.reject(new TypeError('offline')),
    () => Promise.resolve(errorResponse(429)),
    () => Promise.resolve(okResponse())
  ];
  const harness = loadTransport((url, options) => {
    requests.push({ body: options.body, time: harness.clock.now, url });
    return responses.shift()();
  });

  const request = harness.transport.sendEvent('cta_click');
  await flushMicrotasks();
  assert.equal(requests.length, 1);

  await harness.clock.advance(249);
  assert.equal(requests.length, 1);
  await harness.clock.advance(1);
  assert.equal(requests.length, 2);

  await harness.clock.advance(999);
  assert.equal(requests.length, 2);
  await harness.clock.advance(1);
  await request;

  assert.deepEqual(requests.map((entry) => entry.time), [0, 250, 1250]);
  assert.equal(new Set(requests.map((entry) => entry.body)).size, 1);
  assert.equal(
    JSON.parse(requests[0].body).value,
    0,
    'the event timestamp is captured once, before retries'
  );
});

test('transport retries 5xx responses', async () => {
  let requestCount = 0;
  const harness = loadTransport(() => {
    requestCount += 1;
    return Promise.resolve(
      requestCount === 1 ? errorResponse(503) : okResponse()
    );
  });

  const request = harness.transport.sendEvent('cta_click');
  await flushMicrotasks();
  assert.equal(requestCount, 1);
  await harness.clock.advance(250);
  await request;
  assert.equal(requestCount, 2);
});

for (const status of [400, 404, 422]) {
  test(`transport does not retry HTTP ${status}`, async () => {
    let requestCount = 0;
    const harness = loadTransport(() => {
      requestCount += 1;
      return Promise.resolve(errorResponse(status));
    });

    await assert.rejects(
      harness.transport.sendEvent('cta_click'),
      new RegExp(String(status))
    );
    await harness.clock.advance(5000);
    assert.equal(requestCount, 1);
  });
}

test('performance transport clears failed pending state and keeps successful state', async () => {
  let requestCount = 0;
  const harness = loadTransport(() => {
    requestCount += 1;
    if (requestCount <= 3) {
      return Promise.reject(new TypeError('offline'));
    }
    return Promise.resolve(okResponse());
  });

  const failed = harness.transport.sendPerformance({ ttfb: 100 });
  await flushMicrotasks();
  await harness.clock.advance(250);
  await harness.clock.advance(1000);
  await assert.rejects(failed, /offline/);

  const successful = harness.transport.sendPerformance({ ttfb: 200 });
  await successful;
  assert.equal(requestCount, 4);
  assert.equal(
    harness.transport.sendPerformance({ ttfb: 300 }),
    successful,
    'a successful Performance packet remains final'
  );
  assert.equal(requestCount, 4);
});

test('RUM waits for the navigation deadline and snapshots the latest metric values', async () => {
  const harness = createVitalsHarness({ start: 3000 });
  harness.emit('CLS', 0);
  harness.emit('TTFB', 100.4);
  harness.emit('FCP', 200.4);
  harness.emit('LCP', 300.4);
  harness.emit('INP', 40.4);
  assert.equal(harness.calls.length, 0, 'all five provisional values do not send');

  harness.emit('CLS', 0.123456);
  await harness.clock.advance(6999);
  assert.equal(harness.calls.length, 0);
  await harness.clock.advance(1);

  assert.deepEqual(harness.calls, [{
    cls: 0.1235,
    fcp: 200,
    inp: 40,
    lcp: 300,
    ttfb: 100
  }]);
});

test('RUM deadline starts without load and permits a partial packet without INP', async () => {
  const harness = createVitalsHarness();
  harness.emit('TTFB', 80);
  harness.emit('FCP', 160);
  harness.emit('LCP', 320);
  harness.emit('CLS', 0.01);

  await harness.clock.advance(10000);

  assert.equal(harness.document.readyState, 'loading');
  assert.deepEqual(harness.calls, [{
    cls: 0.01,
    fcp: 160,
    lcp: 320,
    ttfb: 80
  }]);
  assert.equal(Object.hasOwn(harness.calls[0], 'inp'), false);
});

test('RUM sends the first metric that arrives after an already elapsed deadline', async () => {
  const harness = createVitalsHarness({ start: 12000 });

  await harness.clock.advance(0);
  assert.equal(harness.calls.length, 0);

  harness.emit('TTFB', 95);
  await flushMicrotasks();

  assert.deepEqual(harness.calls, [{ ttfb: 95 }]);
});

for (const trigger of ['hidden', 'link', 'form']) {
  test(`RUM ${trigger} trigger produces at most one successful packet`, async () => {
    const harness = createVitalsHarness();
    harness.emit('TTFB', 90);

    if (trigger === 'hidden') {
      harness.document.visibilityState = 'hidden';
      harness.document.dispatchEvent({ type: 'visibilitychange' });
    } else if (trigger === 'link') {
      const link = new FakeHTMLElement();
      link.href = 'https://landing.example/next';
      harness.window.dispatchEvent(navigationEvent(link));
    } else {
      const form = new FakeHTMLFormElement();
      harness.window.dispatchEvent(formNavigationEvent(form));
    }
    await flushMicrotasks();
    assert.equal(harness.calls.length, 1);

    harness.document.visibilityState = 'hidden';
    harness.document.dispatchEvent({ type: 'visibilitychange' });
    const link = new FakeHTMLElement();
    link.href = 'https://landing.example/another';
    harness.window.dispatchEvent(navigationEvent(link));
    harness.window.dispatchEvent(formNavigationEvent(new FakeHTMLFormElement()));
    await harness.clock.advance(10000);

    assert.equal(harness.calls.length, 1);
  });
}

test('RUM clears failed collector state so a later trigger can send newer values', async () => {
  const calls = [];
  const harness = createVitalsHarness({
    sendPerformance(metrics) {
      calls.push(JSON.parse(JSON.stringify(metrics)));
      return calls.length === 1
        ? Promise.reject(new TypeError('offline'))
        : Promise.resolve(okResponse());
    }
  });
  harness.emit('TTFB', 100);

  const link = new FakeHTMLElement();
  link.href = 'https://landing.example/next';
  harness.window.dispatchEvent(navigationEvent(link));
  await flushMicrotasks();
  harness.emit('TTFB', 200);

  harness.document.visibilityState = 'hidden';
  harness.document.dispatchEvent({ type: 'visibilitychange' });
  await flushMicrotasks();

  assert.deepEqual(calls, [{ ttfb: 100 }, { ttfb: 200 }]);
});

test('RUM observes link navigation without replacing native activation', async () => {
  const pending = deferred();
  const harness = createVitalsHarness({
    sendPerformance() {
      return pending.promise;
    }
  });
  harness.emit('TTFB', 100);
  const link = new FakeHTMLElement();
  link.href = 'https://landing.example/next';
  const event = navigationEvent(link);

  harness.window.dispatchEvent(event);
  await flushMicrotasks();

  assert.equal(event.defaultPrevented, false);
  assert.equal(harness.window.location.href, 'https://landing.example/');
  assert.equal(harness.calls.length, 0);
  pending.resolve(okResponse());
});

test('RUM observes form submission without replacing native submission', async () => {
  const pending = deferred();
  const harness = createVitalsHarness({
    sendPerformance() {
      return pending.promise;
    }
  });
  harness.emit('TTFB', 100);
  const form = new FakeHTMLFormElement();
  const event = formNavigationEvent(form);

  harness.window.dispatchEvent(event);
  await flushMicrotasks();

  assert.equal(event.defaultPrevented, false);
  assert.equal(form.submitted, false);
  pending.resolve(okResponse());
});

test('RUM uses submitter method and target overrides when detecting navigation', async () => {
  const harness = createVitalsHarness();
  harness.emit('TTFB', 100);

  const form = new FakeHTMLFormElement();
  form.method = 'dialog';
  form.target = '_blank';
  const submitter = new FakeHTMLElement();
  submitter.setAttribute('formmethod', 'post');
  submitter.setAttribute('formtarget', '');
  const event = formNavigationEvent(form, submitter);

  harness.window.dispatchEvent(event);
  await flushMicrotasks();

  assert.equal(event.defaultPrevented, false);
  assert.deepEqual(harness.calls, [{ ttfb: 100 }]);
});

test('RUM ignores submitter dialog actions and already cancelled navigation', async () => {
  const harness = createVitalsHarness();
  harness.emit('TTFB', 100);

  const form = new FakeHTMLFormElement();
  const submitter = new FakeHTMLElement();
  submitter.setAttribute('formmethod', 'dialog');
  harness.window.dispatchEvent(formNavigationEvent(form, submitter));

  const link = new FakeHTMLElement();
  link.href = 'https://landing.example/cancelled';
  const event = navigationEvent(link);
  event.preventDefault();
  harness.window.dispatchEvent(event);
  await flushMicrotasks();

  assert.equal(harness.calls.length, 0);
});

test('RUM capture observers see stopped propagation and respect later cancellation', async () => {
  const stoppedHarness = createVitalsHarness();
  stoppedHarness.emit('TTFB', 100);
  const stoppedLink = new FakeHTMLElement();
  stoppedLink.href = 'https://landing.example/stopped';

  assert.equal(stoppedHarness.window.listeners.get('click')[0].capture, true);
  assert.equal(stoppedHarness.window.listeners.get('submit')[0].capture, true);
  stoppedHarness.window.dispatchEvent(navigationEvent(stoppedLink));
  await flushMicrotasks();
  assert.deepEqual(stoppedHarness.calls, [{ ttfb: 100 }]);

  const cancelledHarness = createVitalsHarness();
  cancelledHarness.emit('TTFB', 200);
  cancelledHarness.window.addEventListener('click', function (event) {
    event.preventDefault();
  });
  const cancelledLink = new FakeHTMLElement();
  cancelledLink.href = 'https://landing.example/cancelled-late';
  cancelledHarness.window.dispatchEvent(navigationEvent(cancelledLink));
  await flushMicrotasks();

  assert.equal(cancelledHarness.calls.length, 0);
});

test('scroll thresholds keep independent pending and successful state', async () => {
  const browser = createBrowserSandbox();
  const calls = [];
  browser.window.scrollY = 500;
  browser.window.__yellowTdsEventTransport = {
    sendEvent(eventName) {
      const result = deferred();
      calls.push({ eventName, result });
      return result.promise;
    }
  };

  executeScript('scrolltracking.js', browser.sandbox, {
    '{SCROLL_THRESHOLDS_JSON}': [50, 75]
  });
  await browser.clock.advance(0);
  assert.deepEqual(calls.map((call) => call.eventName), ['scroll_50']);

  browser.window.dispatchEvent({ type: 'scroll' });
  await browser.clock.advance(0);
  assert.equal(calls.length, 1, 'a pending threshold is not duplicated');

  browser.window.scrollY = 700;
  browser.window.dispatchEvent({ type: 'scroll' });
  await browser.clock.advance(0);
  assert.deepEqual(
    calls.map((call) => call.eventName),
    ['scroll_50', 'scroll_75'],
    'a later threshold does not wait for an earlier pending threshold'
  );

  calls[0].result.reject(new TypeError('offline'));
  await flushMicrotasks();
  browser.window.dispatchEvent({ type: 'scroll' });
  await browser.clock.advance(0);
  assert.deepEqual(
    calls.map((call) => call.eventName),
    ['scroll_50', 'scroll_75', 'scroll_50']
  );

  calls[1].result.resolve(okResponse());
  calls[2].result.resolve(okResponse());
  await flushMicrotasks();
  browser.window.dispatchEvent({ type: 'scroll' });
  await browser.clock.advance(0);
  assert.equal(calls.length, 3, 'successful thresholds remain final');
});

test('visible-time thresholds keep independent pending and successful state', async () => {
  const browser = createBrowserSandbox();
  const calls = [];
  browser.window.__yellowTdsEventTransport = {
    sendEvent(eventName) {
      const result = deferred();
      calls.push({ eventName, result });
      return result.promise;
    }
  };

  executeScript('visibletimetracking.js', browser.sandbox, {
    '{TIME_THRESHOLDS_JSON}': [1, 2]
  });
  await browser.clock.advance(1000);
  assert.deepEqual(calls.map((call) => call.eventName), ['stay_1s']);

  await browser.clock.advance(1000);
  assert.deepEqual(
    calls.map((call) => call.eventName),
    ['stay_1s', 'stay_2s'],
    'a later threshold does not wait for an earlier pending threshold'
  );

  calls[0].result.reject(new TypeError('offline'));
  await flushMicrotasks();
  browser.document.visibilityState = 'hidden';
  browser.document.dispatchEvent({ type: 'visibilitychange' });
  assert.deepEqual(
    calls.map((call) => call.eventName),
    ['stay_1s', 'stay_2s', 'stay_1s']
  );

  calls[1].result.resolve(okResponse());
  calls[2].result.resolve(okResponse());
  await flushMicrotasks();
  browser.document.visibilityState = 'visible';
  browser.document.dispatchEvent({ type: 'visibilitychange' });
  browser.document.visibilityState = 'hidden';
  browser.document.dispatchEvent({ type: 'visibilitychange' });
  assert.equal(calls.length, 3, 'successful thresholds remain final');
});
