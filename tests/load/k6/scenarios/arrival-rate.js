import http from 'k6/http';
import { check } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';
import {
    BASE_URL,
    CAPACITY_THRESHOLDS,
    getArrivalRateScenario,
} from '../config.js';

const errors = new Rate('yellowtds_errors');
const serverResponses = new Counter('yellowtds_responses');
const tdsDuration = new Trend('yellowtds_duration', true);

export const options = {
    discardResponseBodies: true,
    summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'p(99)'],
    scenarios: {
        capacity: getArrivalRateScenario('capacityTraffic'),
    },
    thresholds: CAPACITY_THRESHOLDS,
};

function identity() {
    if ((__ENV.IDENTITY || 'new') === 'repeat') {
        return __ENV.REPEAT_ID || 'repeat-user';
    }
    return `${__VU}-${__ITER}-${Date.now()}`;
}

function rootRequest(kind) {
    const uid = encodeURIComponent(identity());
    const white = kind === 'white' ? '1' : '0';
    return http.get(`${BASE_URL}/?lt=match&white=${white}&uid=${uid}`, {
        redirects: 0,
        headers: {
            'Accept-Language': 'en-US,en;q=0.9',
            'User-Agent': __ENV.USER_AGENT || 'YellowTDS-k6/1.0',
            'Connection': 'keep-alive',
        },
        tags: { traffic: kind },
    });
}

function jsConnectRequest() {
    const uid = encodeURIComponent(identity());
    return http.get(`${BASE_URL}/js/index.php?lt=match&uid=${uid}`, {
        redirects: 0,
        headers: {
            'Accept-Language': 'en-US,en;q=0.9',
            'User-Agent': __ENV.USER_AGENT || 'YellowTDS-k6/1.0',
            'Referer': `${BASE_URL}/`,
            'Connection': 'keep-alive',
        },
        tags: { traffic: 'jsconnect' },
    });
}

export function capacityTraffic() {
    let kind = __ENV.TRAFFIC || 'black';
    if (kind === 'mixed') {
        const roll = Math.random();
        kind = roll < 0.60 ? 'black' : (roll < 0.90 ? 'white' : 'jsconnect');
    }

    const response = kind === 'jsconnect' ? jsConnectRequest() : rootRequest(kind);
    tdsDuration.add(response.timings.duration, { traffic: kind });
    serverResponses.add(1, { status: `${response.status}`, traffic: kind });

    const ok = check(response, {
        'YellowTDS returned a handled response': (r) =>
            r.status === 200 || r.status === 301 || r.status === 302,
    }, { traffic: kind });
    errors.add(!ok, { traffic: kind });
}

export default capacityTraffic;
