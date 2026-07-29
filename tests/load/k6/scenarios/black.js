import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';
import { BASE_URL, STANDARD_STAGES, BLACK_THRESHOLDS, getStages } from '../config.js';
import {
    blackHeaders,
    randomQueryString,
    randomItem,
    BLACK_COUNTRIES,
} from '../helpers/traffic.js';

// Custom metrics
const blackErrorRate = new Rate('black_errors');
const blackRedirectDuration = new Trend('black_redirect_duration', true);
const blackRedirectCount = new Counter('black_redirects');
const blackHtmlCount = new Counter('black_html_responses');

export const options = {
    scenarios: {
        black_traffic: {
            executor: 'ramping-vus',
            stages: getStages(STANDARD_STAGES),
            exec: 'blackTraffic',
            startVUs: 1,
            gracefulRampDown: '10s',
        },
    },
    thresholds: BLACK_THRESHOLDS,
};

// Simulate real user traffic that passes white filters → goes to black flows
export function blackTraffic() {
    const headers = blackHeaders();
    // Include cpc param ~30% of the time to test cost tracking
    const qs = randomQueryString(Math.random() < 0.3);
    const url = `${BASE_URL}/${qs}`;

    const res = http.get(url, {
        headers: headers,
        redirects: 0,  // don't follow — we want to measure the TDS response time
        tags: { type: 'black' },
    });

    blackRedirectDuration.add(res.timings.duration);

    if (res.status === 302 || res.status === 301) {
        blackRedirectCount.add(1);
    } else if (res.status === 200) {
        blackHtmlCount.add(1);
    }

    const ok = check(res, {
        'black: got valid response': (r) =>
            r.status === 200 || r.status === 301 || r.status === 302,
        'black: redirect has location or body has content': (r) =>
            (r.status === 301 || r.status === 302) ? !!r.headers['Location'] : (r.body && r.body.length > 0),
    });

    blackErrorRate.add(!ok);

    // Simulate realistic user think time: 1-3 seconds
    sleep(1 + Math.random() * 2);
}

export default function () {
    blackTraffic();
}
