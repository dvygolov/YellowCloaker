# Events

## Purpose

**Events** is a first-class campaign settings section for browser-side engagement and real-user performance measurements. Each collector has its own **Off/On** switch. YellowTDS injects only enabled collectors and only into the root `index.html` or `index.php` of each step. Nested pages, iframes, and other landing files do not receive trackers.

![Events settings](../assets/screenshots/events-settings-overview.png)

## Scroll Depth

Enable scroll tracking and enter up to 32 page-depth thresholds as whole percentages from 1 to 100. For example, the `50` threshold produces the `scroll_50` event when the visitor first reaches half of the page.

YellowTDS keeps the first occurrence for each clickid and step. The stored value is the elapsed time in milliseconds from tracker initialization on that step. Repeated crossings of the same threshold do not replace it.

## Visible Time

Enable visible-time tracking and enter up to 32 whole-second thresholds from 1 to 86400. For example, the `60` threshold produces `stay_60s` after the page has been visible for 60 seconds.

The timer measures visible page time rather than merely keeping a timer running in a hidden tab. As with scroll events, YellowTDS stores the elapsed milliseconds of the first occurrence for each clickid and step.

## Performance Measurement

**Measure performance** enables Real User Monitoring (RUM) through browser performance APIs. YellowTDS collects at most one performance sample for each clickid and step. A sample can contain:

| Metric | Meaning | Unit |
| --- | --- | --- |
| **LCP** | Largest Contentful Paint: when the largest visible content element finishes rendering | milliseconds |
| **INP** | Interaction to Next Paint: how quickly the page responds to a user interaction | milliseconds |
| **CLS** | Cumulative Layout Shift: how much visible content unexpectedly moves | unitless score |
| **TTFB** | Time to First Byte: time until the first response byte arrives | milliseconds |
| **FCP** | First Contentful Paint: when the first visible content is rendered | milliseconds |

The collector keeps the latest available values and sends one snapshot 10 seconds after the document started. If a regular link navigation or form submission starts earlier, YellowTDS starts a keepalive delivery immediately without delaying or replacing the browser's native navigation. The document becoming `hidden` is an emergency fallback for Back, closing the tab, or entering another address.

Metric availability depends on browser support and what happens during the visit. The packet contains only values actually received by send time. For example, a visit with no interaction may have no INP; YellowTDS leaves it missing rather than substituting zero. Enabled performance metrics become available for campaign reporting but are not added to existing reports automatically.

Performance is measured only in the root index of an HTML/PHP landing into which YellowTDS can inject the collector. Internal pages and documents inside iframes are deliberately not measured. It is unavailable for external redirects and not injected by JS Connect, where navigation timings would describe the host document rather than a YellowTDS-delivered landing. PHP Connect and regular HTML delivery are supported.

## Custom Events

The **Custom events** list is an allowlist of up to 64 names. Add each event name that the campaign is allowed to accept. The **Copy** button on the right of each row copies a ready-to-use call with the current name, for example:

```javascript
ytdsEvent('cta_click');
```

Calls for names outside the campaign allowlist are rejected. YellowTDS stores only the first occurrence for each clickid and step, as elapsed milliseconds from tracker initialization on that step.

## Interpreting Event Values

Scroll, visible-time, and custom event values answer “how long after tracking started on this step did the event first happen?” A lower value means the first occurrence happened earlier; an absent value means the event was not recorded for that clickid and step. Every request also carries the exact landing variant recorded for the step; a mismatched variant is rejected.

Performance values are the browser measurements themselves: timing metrics are expressed in milliseconds, while CLS is a unitless stability score. Missing performance values remain missing so that they do not distort reporting.
