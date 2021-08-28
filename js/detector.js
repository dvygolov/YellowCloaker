function log(text) {
    console.log(text);
}

function arrayRemove(arr, item) {
    for (var i = 0; i < arr.length; i++) {
        if (arr[i] === item) {
            arr.splice(i, 1);
            i--;
        }
    }
    return arr;
}

function BotDetector(args) {
    var self = this;
    self.isBot = false;
    self.reason = '';
    self.tests = {};
    self.timeout = args.timeout || 1000;
    self.callback = args.callback || null;
    self.notified = false;
    self.tzStart = args.tzStart || 0;
    self.tzEnd = args.tzEnd || 0;

    self.maxDeviceEventsCount = 10;
    self.motionCount = 0;
    self.orientationCount = 0;
    self.acceleration = null;
    self.orientation = null;
    self.acceldelta = 0.1;
    self.orientdelta = 0.2;
    self.acceldiff = 0;
    self.orientdiff = 0;
    self.acceldiffmax = 3;
    self.orientdiffmax = 5;

    Tests = {
        KEYDOWN: 'keydown',
        MOUSE: 'mousemove',
        TOUCHSTART: 'touchstart',
        SCROLL: 'scroll',
        DEVICEMOTION: 'devicemotion',
        DEVICEORIENTATION: 'deviceorientation',
        TIMEZONE: 'timezone',
        AUDIOCONTEXT: 'audiocontext'
    };

    var selectedTests = args.tests || [];
    log('Listening for:' + selectedTests.join());

    if (selectedTests.includes(Tests.TIMEZONE)) {
        log('Min allowed tz: ' + self.tzStart);
        log('Max allowed tz: ' + self.tzEnd);
        var curZone = -(new Date().getTimezoneOffset() / 60);
        log('Current tz: ' + curZone);
        if (curZone < self.tzStart || curZone > self.tzEnd) {
            self.reason = 'timezone:' + curZone;
            self.isBot = true;
            self.callback(self);
            return;
        }
        selectedTests=arrayRemove(selectedTests, Tests.TIMEZONE);
    }

    if (selectedTests.includes(Tests.AUDIOCONTEXT)) {
        try {
            window.AudioContext = window.AudioContext || window.webkitAudioContext;
            context = new AudioContext();
            log('Audio engine found!');
            selectedTests = arrayRemove(selectedTests, Tests.AUDIOCONTEXT);
        }
        catch (e) {
            self.reason = 'audiocontext';
            self.isBot = true;
            self.callback(self);
            return;
        }
    }

    if (selectedTests.length == 0) //if previous two tests passed and there are no others
    {
        log('No interactive tests, all ok, exiting...');
        self.callback(self);
    }

    selectedTests.forEach(st => {
        switch (st) {
            case Tests.MOUSE:
            case Tests.KEYDOWN:
            case Tests.SCROLL:
            case Tests.TOUCHSTART:
                self.tests[st] = function () {
                    var e = function (evt) {
                        log(st + evt.target);
                        self.tests[st] = true;
                        self.update();
                    };
                    window.addEventListener(st, e, {
                        once: true
                    });
                };
                break;
            case Tests.DEVICEORIENTATION:
                self.tests[st] = function () {
                    var e = function (et) {
                        log(st + ': Alpha:' + et.alpha + ' Beta:' + et.beta + ' Gamma:' + et.gamma);
                        self.orientationCount++;
                        if (self.orientation !== null) {
                            if (Math.abs(et.alpha - self.orientation.alpha) > self.orientdelta ||
                                Math.abs(et.beta - self.orientation.beta) > self.orientdelta ||
                                Math.abs(et.gamma - self.orientation.gamma) > self.orientdelta) {
                                self.orientdiff++;
                                log('Orientation Diff found!' + self.orientdiff);
                            }
                        }
                        self.orientation = et;
                        if (self.orientdiff >= self.orientdiffmax) {
                            log('MAX orientation Diff!');
                            window.removeEventListener(st, e);
                            self.tests[st] = true;
                            self.update();
                        }
                        if (self.orientationCount >= self.maxDeviceEventsCount)
                            window.removeEventListener(st, e);
                    };
                    if (window.DeviceOrientationEvent)
                        window.addEventListener(st, e);
                    else
                        console.log("No Orientation Detected!");
                };
                break;
            case Tests.DEVICEMOTION:
                self.tests[st] = function () {
                    var e = function (et) {
                        log(st + ': X:' + et.acceleration.x + ' Y:' + et.acceleration.y + ' Z:' + et.acceleration.z);
                        self.motionCount++;
                        if (self.acceleration !== null) {
                            if (Math.abs(et.acceleration.x - self.acceleration.x) > self.acceldelta ||
                                Math.abs(et.acceleration.y - self.acceleration.y) > self.acceldelta ||
                                Math.abs(et.acceleration.z - self.acceleration.z) > self.acceldelta) {
                                self.acceldiff++;
                                log('Acceleration Diff found!' + self.acceldiff);
                            }
                        }
                        self.acceleration = et.acceleration;
                        if (self.acceldiff >= self.acceldiffmax) {
                            log('MAX acceleration Diff!');
                            window.removeEventListener(st, e);
                            self.tests[st] = true;
                            self.update();
                        }
                        if (self.motionCount >= self.maxDeviceEventsCount * 2)
                            window.removeEventListener(st, e);
                    };
                    if (window.DeviceMotionEvent)
                        window.addEventListener(st, e);
                    else
                        log("No Motion Detected!");
                };
                break;
        }
    });
}

BotDetector.prototype.update = function () {
    var self = this;
    var count = 0;
    var passReason = '';
    for (var t in self.tests) {
        if (self.tests.hasOwnProperty(t) && self.tests[t] === true) {
            passReason += t + ';';
            count++;
        }
    }
    if (passReason != '')
        self.reason = passReason;
    self.isBot = count == 0;
    if (self.notified === false) {
        self.callback(self);
        self.notified = true;
    }
};

BotDetector.prototype.monitor = function () {
    var self = this;
    if (self.isBot)
        return;
    for (var i in self.tests) {
        if (self.tests.hasOwnProperty(i)) {
            self.tests[i].call();
        }
    }
    if (Object.keys(self.tests).length > 0)
        setTimeout(function () {
            log('Tests timeout!');
            self.reason = 'timeout';
            self.update();
        }, self.timeout);
};