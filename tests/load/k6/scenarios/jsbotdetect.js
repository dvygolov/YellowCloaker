import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend } from 'k6/metrics';
import { BASE_URL, LIGHT_STAGES, STANDARD_THRESHOLDS, getStages } from '../config.js';
import {
    blackHeaders,
    randomQueryString,
} from '../helpers/traffic.js';

// Custom metrics
const jsBotErrorRate = new Rate('jsbot_errors');
const jsBotFullDuration = new Trend('jsbot_full_flow_duration', true);
const jsBotCheckPageDuration = new Trend('jsbot_checkpage_duration', true);
const jsBotPassDuration = new Trend('jsbot_pass_duration', true);

export const options = {
    scenarios: {
        jsbot_flow: {
            executor: 'ramping-vus',
            stages: getStages(LIGHT_STAGES),
            exec: 'jsBotDetectFlow',
            startVUs: 1,
            gracefulRampDown: '10s',
        },
    },
    thresholds: STANDARD_THRESHOLDS,
};

/**
 * JS Bot Detection flow simulates:
 * 1. User hits index.php → gets jscheck HTML page (with detection JS)
 * 2. Browser runs JS checks (pointerdown, touchstart, etc.) — we skip this, just wait
 * 3. JS calls js/index.php to report "passed" → server runs Tds::processJsCheck()
 * 4. Server returns JS with landing content or redirect
 *
 * NOTE: This scenario requires JS bot detection to be ENABLED in the campaign.
 * Run setup_campaign.php first, then manually enable jsbotdetection in admin,
 * or use the enable_jsbot.php helper.
 */
export function jsBotDetectFlow() {
    const startTime = Date.now();
    const headers = blackHeaders();
    const qs = randomQueryString();

    let jsCheckPageOk = false;

    group('JS Bot Detection: Phase 1 - Get check page', () => {
        const url = `${BASE_URL}/${qs}`;
        const res = http.get(url, {
            headers: headers,
            redirects: 0,
            tags: { type: 'jsbot_checkpage' },
        });

        jsBotCheckPageDuration.add(res.timings.duration);

        jsCheckPageOk = check(res, {
            'jsbot checkpage: status 200': (r) => r.status === 200,
            'jsbot checkpage: contains detect script': (r) =>
                r.body && (r.body.includes('BotDetector') || r.body.includes('script') || r.body.length > 100),
        });
    });

    if (!jsCheckPageOk) {
        jsBotErrorRate.add(true);
        // If we didn't get the check page, it might be a redirect (black without jscheck)
        // or an error. Either way, skip the rest.
        sleep(1);
        return;
    }

    // Simulate JS execution time (user interacting with the page)
    // Real users take 1-5 seconds to trigger events
    sleep(1 + Math.random() * 3);

    group('JS Bot Detection: Phase 2 - Pass check', () => {
        // Phase 2: JS reports successful check via js/index.php
        // The real JS calls this after passing all interactive tests
        const passUrl = `${BASE_URL}/js/index.php${qs}`;
        const passHeaders = Object.assign({}, headers, {
            'Accept': '*/*',
            'Sec-Fetch-Dest': 'script',
            'Sec-Fetch-Mode': 'no-cors',
            'Referer': `${BASE_URL}/`,
        });

        const res = http.get(passUrl, {
            headers: passHeaders,
            redirects: 0,
            tags: { type: 'jsbot_pass' },
        });

        jsBotPassDuration.add(res.timings.duration);

        const ok = check(res, {
            'jsbot pass: status 200': (r) => r.status === 200,
            'jsbot pass: got JS response': (r) =>
                r.headers['Content-Type'] && r.headers['Content-Type'].includes('javascript'),
        });

        jsBotErrorRate.add(!ok);
    });

    const totalDuration = Date.now() - startTime;
    jsBotFullDuration.add(totalDuration);

    // Simulate user on the landing page
    sleep(3 + Math.random() * 5);
}

export default function () {
    jsBotDetectFlow();
}
