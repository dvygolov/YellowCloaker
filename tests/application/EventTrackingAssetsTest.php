<?php

use PHPUnit\Framework\TestCase;

final class EventTrackingAssetsTest extends TestCase
{
    public function testClientAssetsImplementTheRequiredDeliverySemantics(): void
    {
        $transport = file_get_contents(__DIR__ . '/../../code/scripts/eventtracking.js');
        $scroll = file_get_contents(__DIR__ . '/../../code/scripts/scrolltracking.js');
        $visibleTime = file_get_contents(__DIR__ . '/../../code/scripts/visibletimetracking.js');
        $custom = file_get_contents(__DIR__ . '/../../code/scripts/customeventtracking.js');
        $performance = file_get_contents(__DIR__ . '/../../code/scripts/performancetracking.js');

        $this->assertStringContainsString('event: eventName', $transport);
        $this->assertStringContainsString(
            'value: Math.max(0, Math.round(performance.now() - trackerStart))',
            $transport
        );
        $this->assertStringContainsString("'Content-Type': 'text/plain;charset=UTF-8'", $transport);
        $this->assertStringContainsString("credentials: 'omit'", $transport);
        $this->assertStringContainsString('const body = JSON.stringify(payload);', $transport);
        $this->assertStringContainsString('const retryDelays = [0, 250, 1000];', $transport);
        $this->assertStringContainsString('response.status === 429', $transport);
        $this->assertStringContainsString('response.status >= 500', $transport);
        $this->assertStringNotContainsString('sendBeacon', $transport);
        $this->assertStringNotContainsString('URLSearchParams', $transport);

        $this->assertStringContainsString('requestAnimationFrame(reportDepth)', $scroll);
        $this->assertStringNotContainsString('setInterval', $visibleTime);
        $this->assertStringContainsString('scheduleDeadline', $visibleTime);
        $this->assertStringContainsString('global.ytdsEvent = function', $custom);
        $this->assertStringContainsString('return transport.sendEvent(normalizedName)', $custom);

        $this->assertStringContainsString(
            'Math.max(0, 10000 - performance.now())',
            $performance
        );
        $this->assertStringNotContainsString("window.addEventListener('load'", $performance);
        $this->assertStringContainsString(
            "deferNavigationCheck(controlledLinkNavigation, event)",
            $performance
        );
        $this->assertStringContainsString(
            "deferNavigationCheck(controlledFormNavigation, event)",
            $performance
        );
        $this->assertStringContainsString(
            "window.addEventListener('click', function (event)",
            $performance
        );
        $this->assertStringContainsString(
            "window.addEventListener('submit', function (event)",
            $performance
        );
        $this->assertStringContainsString(
            'event.defaultPrevented ||',
            $performance
        );
        $this->assertStringContainsString(
            "submitter.hasAttribute('formmethod')",
            $performance
        );
        $this->assertStringContainsString(
            "String(method || 'get').toLowerCase() !== 'dialog'",
            $performance
        );
        $this->assertStringContainsString('reportAllChanges: true', $performance);
        $this->assertStringContainsString(
            "document.addEventListener('visibilitychange', visibilityChanged)",
            $performance
        );
        $this->assertStringContainsString('const snapshot = Object.assign({}, metrics);', $performance);
        $this->assertStringContainsString('deadlinePassed = true;', $performance);
        $this->assertStringNotContainsString('continueAfterSnapshot', $performance);
        $this->assertStringNotContainsString('event.preventDefault();', $performance);
        $this->assertStringNotContainsString('location.assign', $performance);
        $this->assertStringNotContainsString('pagehide', $performance);
        $this->assertStringNotContainsString("metrics.inp = 0", $performance);
    }

    public function testVendoredWebVitalsBundleIsPinnedOfficialBuild(): void
    {
        $bundle = __DIR__ . '/../../code/scripts/vendor/web-vitals-5.3.0/web-vitals.iife.js';
        $license = __DIR__ . '/../../code/scripts/vendor/web-vitals-5.3.0/LICENSE';

        $this->assertFileExists($bundle);
        $this->assertFileExists($license);
        $this->assertSame(
            'bc7a1a0cc6e0eab320c0eae3064eaa4c1b4da607e8f1d896c6a59cafbeccd257',
            hash_file('sha256', $bundle)
        );
        $this->assertStringContainsString(
            'Copyright 2020 Google LLC',
            file_get_contents($license)
        );
    }

    public function testCampaignFormAlwaysSubmitsAnExplicitCustomEventList(): void
    {
        $formSubmit = file_get_contents(__DIR__ . '/../../code/admin/js/campsettings/form-submit.js');

        $this->assertStringContainsString('payload.events = payload.events || {};', $formSubmit);
        $this->assertStringContainsString('payload.events.custom = Array.from(', $formSubmit);
        $this->assertStringContainsString(
            "document.querySelectorAll('#custom-event-list .custom-event-name')",
            $formSubmit
        );
        $this->assertStringContainsString(
            'window.normalizeEventThresholdInputs()',
            $formSubmit
        );
    }

    public function testCustomEventRowsOwnTheirCopyButtonsAndNoGlobalExampleRemains(): void
    {
        $form = file_get_contents(__DIR__ . '/../../code/admin/campsettings.php');
        $eventsUi = file_get_contents(__DIR__ . '/../../code/admin/js/campsettings/events.js');

        $this->assertStringContainsString('copy-custom-event', $form);
        $this->assertStringContainsString('copy-custom-event', $eventsUi);
        $this->assertStringContainsString("`ytdsEvent('\${name}');`", $eventsUi);
        $this->assertStringNotContainsString('custom-event-snippet', $form);
        $this->assertStringNotContainsString("ytdsEvent('cta_click');</code>", $form);
    }
}
