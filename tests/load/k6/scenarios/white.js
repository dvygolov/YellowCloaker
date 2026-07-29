import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';
import { BASE_URL, STANDARD_STAGES, WHITE_THRESHOLDS, getStages } from '../config.js';
import {
    whiteBotHeaders,
    whiteCountryHeaders,
    randomQueryString,
    randomItem,
    BOT_USER_AGENTS,
} from '../helpers/traffic.js';

// Custom metrics
const whiteErrorRate = new Rate('white_errors');
const whiteBotDuration = new Trend('white_bot_duration', true);
const whiteCountryDuration = new Trend('white_country_duration', true);

export const options = {
    scenarios: {
        white_bots: {
            executor: 'ramping-vus',
            stages: getStages(STANDARD_STAGES),
            exec: 'botTraffic',
            startVUs: 1,
            gracefulRampDown: '10s',
        },
        white_countries: {
            executor: 'ramping-vus',
            stages: getStages(STANDARD_STAGES),
            exec: 'countryTraffic',
            startVUs: 1,
            gracefulRampDown: '10s',
        },
    },
    thresholds: WHITE_THRESHOLDS,
};

// Simulate bot/crawler traffic — should be filtered by UA contains "bot"
export function botTraffic() {
    const headers = whiteBotHeaders();
    const qs = randomQueryString();
    const url = `${BASE_URL}/${qs}`;

    const res = http.get(url, {
        headers: headers,
        redirects: 0,  // don't follow redirects, measure raw response
        tags: { type: 'white_bot' },
    });

    whiteBotDuration.add(res.timings.duration);

    const ok = check(res, {
        'white bot: status is 200 or 302 or 404': (r) =>
            r.status === 200 || r.status === 302 || r.status === 404,
    });

    whiteErrorRate.add(!ok);
    sleep(Math.random() * 0.5); // 0-500ms between requests
}

// Simulate traffic from filtered countries (RU, BG, SG)
// These have real-user UAs but come from blocked countries
export function countryTraffic() {
    const headers = whiteCountryHeaders();
    const qs = randomQueryString();
    const url = `${BASE_URL}/${qs}`;

    const res = http.get(url, {
        headers: headers,
        redirects: 0,
        tags: { type: 'white_country' },
    });

    whiteCountryDuration.add(res.timings.duration);

    const ok = check(res, {
        'white country: status is 200 or 302 or 404': (r) =>
            r.status === 200 || r.status === 302 || r.status === 404,
    });

    whiteErrorRate.add(!ok);
    sleep(Math.random() * 0.5);
}

export default function () {
    // If run without scenarios, alternate between bot and country traffic
    if (Math.random() < 0.5) {
        botTraffic();
    } else {
        countryTraffic();
    }
}
