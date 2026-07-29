import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend } from 'k6/metrics';
import { BASE_URL, LIGHT_STAGES, STANDARD_THRESHOLDS, getStages } from '../config.js';
import {
    blackHeaders,
    jsConnectHeaders,
    randomQueryString,
} from '../helpers/traffic.js';

// Custom metrics
const jsConnectErrorRate = new Rate('jsconnect_errors');
const jsConnectFullDuration = new Trend('jsconnect_full_flow_duration', true);
const jsScriptDuration = new Trend('jsconnect_script_duration', true);

export const options = {
    scenarios: {
        jsconnect_flow: {
            executor: 'ramping-vus',
            stages: getStages(LIGHT_STAGES),
            exec: 'jsConnectFlow',
            startVUs: 1,
            gracefulRampDown: '10s',
        },
    },
    thresholds: STANDARD_THRESHOLDS,
};

/**
 * JS Connect flow simulates:
 * 1. User loads safe page (which includes <script src="js/index.php">)
 * 2. The JS script calls back to js/index.php which runs Tds::getJsAction()
 * 3. Server returns JS that replaces page content or redirects
 *
 * We simulate this as two sequential requests with shared cookies.
 */
export function jsConnectFlow() {
    const startTime = Date.now();
    const headers = blackHeaders();
    const qs = randomQueryString();
    const jar = http.cookieJar();

    group('JS Connect: Phase 1 - Load safe page', () => {
        // Phase 1: Initial page load (gets safe page since JS connect means
        // the initial page is white, and JS replaces it)
        const pageUrl = `${BASE_URL}/${qs}`;
        const res = http.get(pageUrl, {
            headers: headers,
            redirects: 0,
            tags: { type: 'jsconnect_page' },
        });

        check(res, {
            'jsconnect page: got response': (r) => r.status >= 200 && r.status < 400,
        });
    });

    // Small delay simulating browser parsing HTML and loading script
    sleep(0.1 + Math.random() * 0.3);

    group('JS Connect: Phase 2 - Script load', () => {
        // Phase 2: Browser loads js/index.php (the JS connect script)
        const scriptUrl = `${BASE_URL}/js/index.php${qs}`;
        const scriptHeaders = jsConnectHeaders(`${BASE_URL}/`);

        const res = http.get(scriptUrl, {
            headers: scriptHeaders,
            redirects: 0,
            tags: { type: 'jsconnect_script' },
        });

        jsScriptDuration.add(res.timings.duration);

        const ok = check(res, {
            'jsconnect script: status 200': (r) => r.status === 200,
            'jsconnect script: content-type is JS': (r) =>
                r.headers['Content-Type'] && r.headers['Content-Type'].includes('javascript'),
            'jsconnect script: body not empty': (r) => r.body && r.body.length > 10,
        });

        jsConnectErrorRate.add(!ok);
    });

    const totalDuration = Date.now() - startTime;
    jsConnectFullDuration.add(totalDuration);

    // Simulate user reading the page
    sleep(2 + Math.random() * 3);
}

export default function () {
    jsConnectFlow();
}
