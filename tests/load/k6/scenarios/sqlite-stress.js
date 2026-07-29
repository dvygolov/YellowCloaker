import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';
import { BASE_URL, WRITE_STRESS_STAGES, getStages } from '../config.js';
import {
    blackHeaders,
    randomQueryString,
} from '../helpers/traffic.js';

// Custom metrics
const writeErrorRate = new Rate('sqlite_write_errors');
const writeDuration = new Trend('sqlite_write_duration', true);
const totalWrites = new Counter('sqlite_total_writes');
const busyErrors = new Counter('sqlite_busy_errors');

export const options = {
    scenarios: {
        sqlite_stress: {
            executor: 'ramping-vus',
            stages: getStages(WRITE_STRESS_STAGES),
            exec: 'stressWrite',
            startVUs: 1,
            gracefulRampDown: '10s',
        },
    },
    thresholds: {
        'http_req_duration': ['p(95)<3000'],  // relaxed — we're looking for the breaking point
        'sqlite_write_errors': ['rate<0.10'],  // up to 10% errors before we call it broken
    },
};

/**
 * SQLite stress test: hammer the black click path as fast as possible.
 * Every request that passes filters writes to the clicks table.
 *
 * This scenario has minimal sleep to maximize write pressure on SQLite.
 * Watch for:
 * - Response time degradation (SQLite BUSY waits)
 * - 500 errors (SQLite lock timeouts)
 * - The VU count where things start breaking
 */
export function stressWrite() {
    const headers = blackHeaders();
    // Always include cpc to test the cost extraction path too
    const qs = randomQueryString(true);
    const url = `${BASE_URL}/${qs}`;

    const res = http.get(url, {
        headers: headers,
        redirects: 0,
        tags: { type: 'sqlite_stress' },
    });

    writeDuration.add(res.timings.duration);
    totalWrites.add(1);

    const ok = check(res, {
        'sqlite stress: valid response': (r) =>
            r.status === 200 || r.status === 301 || r.status === 302,
    });

    if (!ok) {
        writeErrorRate.add(true);
        // Check if it's likely a SQLite busy error (500 status)
        if (res.status === 500) {
            busyErrors.add(1);
        }
    } else {
        writeErrorRate.add(false);
    }

    // Minimal sleep — just enough to not be a pure DoS
    sleep(0.05 + Math.random() * 0.1);
}

export default function () {
    stressWrite();
}
