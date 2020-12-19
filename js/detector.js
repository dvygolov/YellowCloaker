function log(text) {
    console.log(text);
}

function BotDetector(args) {
    var self = this;
    self.isBot = false;
	self.reason = '';
    self.tests = {};
    self.timeout = args.timeout || 1000;
    self.callback = args.callback || null;
    self.notified = false;

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
        WHEEL: 'wheel',
        SCROLL: 'scroll',
        DEVICEMOTION: 'devicemotion',
        DEVICEORIENTATION: 'deviceorientation',
    };

    var selectedTests = args.tests || [];
    selectedTests.forEach(st => {
        log('<div>Listening for:' + st + '</div>');
        switch (st) {
            case Tests.MOUSE:
            case Tests.KEYDOWN:
            case Tests.WHEEL:
            case Tests.SCROLL:
            case Tests.TOUCHSTART:
                self.tests[st] = function () {
                    var e = function () {
                        log('<div>' + st + '</div>');
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
                        log('<br/>');
                        log('<div>' + st + '</div>');
                        log('<div>Alpha:' + et.alpha + '</div>');
                        log('<div>Beta:' + et.beta + '</div>');
                        log('<div>Gamma:' + et.gamma + '</div>');
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
                        log('<br/>');
                        log('<div>' + st + '</div>');
                        log('<div>X:' + et.acceleration.x + '</div>');
                        log('<div>Y:' + et.acceleration.y + '</div>');
                        log('<div>Z:' + et.acceleration.z + '</div>');
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
                        if (self.motionCount >= self.maxDeviceEventsCount*2)
                            window.removeEventListener(st, e);
                    };
                    if (window.DeviceMotionEvent)
                        window.addEventListener(st, e);
                    else
                        console.log("No Motion Detected!");
                };
                break;
        }
    });

}


BotDetector.prototype.update = function () {
    var self = this;
    var count = 0;
    var tests = 0;
	self.reason='';
    for (var t in self.tests) {
        if (self.tests.hasOwnProperty(t) && self.tests[t] === true) {
			self.reason+=t+';';
            count++;
        }
        tests++;
    }
    self.isBot = count == 0;
    self.allMatched = count == tests;
    if (self.notified === false) {
        self.callback(self);
        self.notified = true;
    }
};

BotDetector.prototype.monitor = function () {
    var self = this;
    for (var i in self.tests) {
        if (self.tests.hasOwnProperty(i)) {
            self.tests[i].call();
        }
    }
    setTimeout(function () {
        self.update(true);
    }, self.timeout);
};