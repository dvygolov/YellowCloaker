import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';
import { BASE_URL, STANDARD_STAGES, STANDARD_THRESHOLDS, getStages } from '../config.js';
import {
    whiteBotHeaders,
    whiteCountryHeaders,
    blackHeaders,
    jsConnectHeaders,
    randomQueryString,
    randomItem,
    randomInt,
} from '../helpers/traffic.js';

// ── Custom metrics per traffic type ──
const whiteRate = new Counter('mixed_white_requests');
const blackRate = new Counter('mixed_black_requests');
const jsConnectRate = new Counter('mixed_jsconnect_requests');
const postbackRate = new Counter('mixed_postback_requests');

const whiteDuration = new Trend('mixed_white_duration', true);
const blackDuration = new Trend('mixed_black_duration', true);
const jsConnectDuration = new Trend('mixed_jsconnect_duration', true);

const overallErrorRate = new Rate('mixed_errors');

export const options = {
    scenarios: {
        mixed_traffic: {
            executor: 'ramping-vus',
            stages: getStages(STANDARD_STAGES),
            exec: 'mixedTraffic',
            startVUs: 1,
            gracefulRampDown: '10s',
        },
    },
    thresholds: {
        'http_req_duration': ['p(95)<2000', 'p(99)<5000'],
        'http_req_failed': ['rate<0.05'],
        'mixed_errors': ['rate<0.05'],
    },
};

/**
 * Mixed traffic scenario — the most realistic simulation.
 *
 * Traffic distribution:
 *   60% white (30% bots + 30% filtered countries)
 *   30% black (real users passing filters)
 *    5% JS connect (two-phase flow)
 *    5% postback simulation (lpctr/status updates)
 */
export function mixedTraffic() {
    const roll = Math.random();

    if (roll < 0.30) {
        // 30% — Bot traffic (white)
        doBotRequest();
    } else if (roll < 0.60) {
        // 30% — Filtered country traffic (white)
        doCountryFilteredRequest();
    } else if (roll < 0.90) {
        // 30% — Real user traffic (black)
        doBlackRequest();
    } else if (roll < 0.95) {
        // 5% — JS connect flow
        doJsConnectFlow();
    } else {
        // 5% — Postback / conversion simulation
        doPostbackSimulation();
    }
}

function doBotRequest() {
    const headers = whiteBotHeaders();
    const qs = randomQueryString();
    const url = `${BASE_URL}/${qs}`;

    const res = http.get(url, {
        headers: headers,
        redirects: 0,
        tags: { type: 'mixed_white_bot' },
    });

    whiteRate.add(1);
    whiteDuration.add(res.timings.duration);

    const ok = check(res, {
        'mixed bot: valid status': (r) => r.status >= 200 && r.status < 500,
    });
    overallErrorRate.add(!ok);

    sleep(Math.random() * 0.3);
}

function doCountryFilteredRequest() {
    const headers = whiteCountryHeaders();
    const qs = randomQueryString();
    const url = `${BASE_URL}/${qs}`;

    const res = http.get(url, {
        headers: headers,
        redirects: 0,
        tags: { type: 'mixed_white_country' },
    });

    whiteRate.add(1);
    whiteDuration.add(res.timings.duration);

    const ok = check(res, {
        'mixed country: valid status': (r) => r.status >= 200 && r.status < 500,
    });
    overallErrorRate.add(!ok);

    sleep(Math.random() * 0.5);
}

function doBlackRequest() {
    const headers = blackHeaders();
    const qs = randomQueryString(Math.random() < 0.3);
    const url = `${BASE_URL}/${qs}`;

    const res = http.get(url, {
        headers: headers,
        redirects: 0,
        tags: { type: 'mixed_black' },
    });

    blackRate.add(1);
    blackDuration.add(res.timings.duration);

    const ok = check(res, {
        'mixed black: valid response': (r) =>
            r.status === 200 || r.status === 301 || r.status === 302,
    });
    overallErrorRate.add(!ok);

    // Real users have think time
    sleep(1 + Math.random() * 2);
}

function doJsConnectFlow() {
    const headers = blackHeaders();
    const qs = randomQueryString();

    // Phase 1: Load page
    const pageUrl = `${BASE_URL}/${qs}`;
    const pageRes = http.get(pageUrl, {
        headers: headers,
        redirects: 0,
        tags: { type: 'mixed_jsconnect_page' },
    });

    jsConnectRate.add(1);

    sleep(0.1 + Math.random() * 0.2);

    // Phase 2: Load JS script
    const scriptUrl = `${BASE_URL}/js/index.php${qs}`;
    const scriptHeaders = jsConnectHeaders(`${BASE_URL}/`);

    const scriptRes = http.get(scriptUrl, {
        headers: scriptHeaders,
        redirects: 0,
        tags: { type: 'mixed_jsconnect_script' },
    });

    jsConnectDuration.add(scriptRes.timings.duration);

    const ok = check(scriptRes, {
        'mixed jsconnect: script loaded': (r) => r.status === 200,
    });
    overallErrorRate.add(!ok);

    sleep(2 + Math.random() * 3);
}

function doPostbackSimulation() {
    // Simulate a next-step transition call with fake click context.
    // The DB lookup will fail fast with "CLICK NOT FOUND", but this still exercises
    // request routing and error handling on next.php.
    const headers = blackHeaders();
    const fakeClickid = `loadtest_${randomInt(100000, 999999)}`;
    const url = `${BASE_URL}/next.php?clickid=${fakeClickid}&step=0`;

    const res = http.get(url, {
        headers: headers,
        redirects: 0,
        tags: { type: 'mixed_postback' },
    });

    postbackRate.add(1);

    // Will get a die() with error text, but no 500 = server is healthy
    const ok = check(res, {
        'mixed postback: no server error': (r) => r.status < 500,
    });
    overallErrorRate.add(!ok);

    sleep(0.5 + Math.random());
}

export default function () {
    mixedTraffic();
}
