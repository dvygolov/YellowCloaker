
class BotDetector {
  constructor(args) {
    this.isBot = false;
    this.reason = '';
    this.tests = {};
    this.timeout = args.timeout || 1000;
    this.callback = args.callback || null;
    this.notified = false;
    this.tzStart = args.tzStart || 0;
    this.tzEnd = args.tzEnd || 0;
    this.debug = args.debug || false;

    this.maxDeviceEventsCount = 10;
    this.motionCount = 0;
    this.orientationCount = 0;
    this.acceleration = null;
    this.orientation = null;
    this.acceldelta = 0.1;
    this.orientdelta = 0.2;
    this.acceldiff = 0;
    this.orientdiff = 0;
    this.acceldiffmax = 3;
    this.orientdiffmax = 5;

    this.selectedTests = args.tests || [];
    this.Tests = {
      KEYDOWN: 'keydown',
      MOUSE: 'mousemove',
      TOUCHSTART: 'touchstart',
      SCROLL: 'scroll',
      DEVICEMOTION: 'devicemotion',
      DEVICEORIENTATION: 'deviceorientation',
      TIMEZONE: 'timezone',
      AUDIOCONTEXT: 'audiocontext'
    };

    this.initializeTests();
  }

  log(text) {
    if (this.debug) {
      console.log(text);
    }
  }

  arrayRemove(arr, item) {
    return arr.filter(el => el !== item);
  }

  initializeTests() {
    this.log('Listening for: ' + this.selectedTests.join());

    if (this.selectedTests.includes(this.Tests.TIMEZONE)) {
      this.checkTimeZone();
    }

    if (this.selectedTests.includes(this.Tests.AUDIOCONTEXT)) {
      this.checkAudioContext();
    }

    this.log('Tests count:' + this.selectedTests.length);
    if (this.selectedTests.length === 0) {
      this.log('No interactive tests, all ok, exiting...');
      this.callback(this);
      return;
    }

    this.selectedTests.forEach(test => this.setupTest(test));
  }

  checkTimeZone() {
    this.log('Min allowed tz: ' + this.tzStart);
    this.log('Max allowed tz: ' + this.tzEnd);
    let curZone = -(new Date().getTimezoneOffset() / 60);
    this.log('Current tz: ' + curZone);
    if (curZone < this.tzStart || curZone > this.tzEnd) {
      this.reason = 'timezone:' + curZone;
      this.isBot = true;
      this.callback(this);
      return;
    }
    this.selectedTests = this.arrayRemove(this.selectedTests, this.Tests.TIMEZONE);
  }

  checkAudioContext() {
    try {
      window.AudioContext = window.AudioContext || window.webkitAudioContext;
      let context = new AudioContext();
      this.log('Audio engine found!');
      this.selectedTests = this.arrayRemove(this.selectedTests, this.Tests.AUDIOCONTEXT);
    } catch (e) {
      this.reason = 'audiocontext';
      this.isBot = true;
      this.callback(this);
      return;
    }
  }

  setupTest(test) {
    switch (test) {
      case this.Tests.MOUSE:
      case this.Tests.KEYDOWN:
      case this.Tests.SCROLL:
      case this.Tests.TOUCHSTART:
        this.tests[test] = () => {
          const eventListener = (evt) => {
            this.log(`${test} ${evt.target}`);
            this.tests[test] = true;
            this.update();
          };
          window.addEventListener(test, eventListener, { once: true });
        };
        break;
      case this.Tests.DEVICEORIENTATION:
        this.tests[test] = () => {
          const eventListener = (et) => {
            this.log(`${test}: Alpha:${et.alpha} Beta:${et.beta} Gamma:${et.gamma}`);
            this.orientationCount++;
            if (this.orientation !== null) {
              if (Math.abs(et.alpha - this.orientation.alpha) > this.orientdelta ||
                Math.abs(et.beta - this.orientation.beta) > this.orientdelta ||
                Math.abs(et.gamma - this.orientation.gamma) > this.orientdelta) {
                this.orientdiff++;
                this.log('Orientation Diff found!' + this.orientdiff);
              }
            }
            this.orientation = et;
            if (this.orientdiff >= this.orientdiffmax) {
              this.log('MAX orientation Diff!');
              window.removeEventListener(test, eventListener);
              this.tests[test] = true;
              this.update();
            }
            if (this.orientationCount >= this.maxDeviceEventsCount)
              window.removeEventListener(test, eventListener);
          };
          if (window.DeviceOrientationEvent)
            window.addEventListener(test, eventListener);
          else
            this.log("No Orientation Detected!");
        };
        break;
      case this.Tests.DEVICEMOTION:
        this.tests[test] = () => {
          const eventListener = (et) => {
            this.log(`${test}: X:${et.acceleration.x} Y:${et.acceleration.y} Z:${et.acceleration.z}`);
            this.motionCount++;
            if (this.acceleration !== null) {
              if (Math.abs(et.acceleration.x - this.acceleration.x) > this.acceldelta ||
                Math.abs(et.acceleration.y - this.acceleration.y) > this.acceldelta ||
                Math.abs(et.acceleration.z - this.acceleration.z) > this.acceldelta) {
                this.acceldiff++;
                this.log('Acceleration Diff found!' + this.acceldiff);
              }
            }
            this.acceleration = et.acceleration;
            if (this.acceldiff >= this.acceldiffmax) {
              this.log('MAX acceleration Diff!');
              window.removeEventListener(test, eventListener);
              this.tests[test] = true;
              this.update();
            }
            if (this.motionCount >= this.maxDeviceEventsCount * 2)
              window.removeEventListener(test, eventListener);
          };
          if (window.DeviceMotionEvent)
            window.addEventListener(test, eventListener);
          else
            this.log("No Motion Detected!");
        };
        break;
    }
  }

  update() {
    let count = 0;
    let passReason = '';
    for (let t in this.tests) {
      if (this.tests.hasOwnProperty(t) && this.tests[t] === true) {
        passReason += t + ';';
        count++;
      }
    }
    if (passReason !== '') {
      this.reason = passReason;
    }
    this.isBot = count === 0;
    if (!this.notified) {
      this.callback(this);
      this.notified = true;
    }
  }

  monitor() {
    if (this.isBot) return;

    for (let i in this.tests) {
      if (this.tests.hasOwnProperty(i)) {
        this.tests[i].call(this);
      }
    }

    if (Object.keys(this.tests).length > 0) {
      setTimeout(() => {
        this.log('Tests timeout!');
        this.reason = 'timeout';
        this.update();
      }, this.timeout);
    }
  }
}
