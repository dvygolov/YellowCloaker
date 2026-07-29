import http from 'k6/http';
import exec from 'k6/execution';
import { check } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

const fixturePath = __ENV.EVENT_FIXTURE || '../../.events-fixture.json';
const fixture = JSON.parse(open(fixturePath));
const baseUrl = (__ENV.BASE_URL || 'http://127.0.0.1:8080').replace(/\/+$/, '');
const statsUrl = (__ENV.STATS_URL || '')
    .replaceAll('{campaign_id}', `${fixture.campaign_id || ''}`);

if (
    fixture.version !== 2
    || !fixture.prefix
    || !Number.isInteger(fixture.campaign_id)
    || !Number.isInteger(fixture.click_count)
    || !Number.isInteger(fixture.steps_per_click)
    || !Number.isInteger(fixture.target_count)
    || !Number.isInteger(fixture.unique_target_count)
    || !Number.isInteger(fixture.retry_target_start)
    || !Number.isInteger(fixture.retry_target_count)
    || fixture.target_count !== fixture.click_count * fixture.steps_per_click
    || fixture.unique_target_count + fixture.retry_target_count !== fixture.target_count
    || fixture.retry_target_start !== fixture.unique_target_count
    || fixture.unique_target_count < 1
    || fixture.retry_target_count < 1
) {
    throw new Error(`Invalid Events fixture manifest: ${fixturePath}`);
}

function positiveInt(name, fallback) {
    const parsed = Number.parseInt(__ENV[name] || `${fallback}`, 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
}

const profile = __ENV.PROFILE || 'standard';
const smoke = profile === 'smoke';
const defaultUniqueIterations = smoke
    ? Math.min(300, fixture.unique_target_count)
    : fixture.unique_target_count;
const uniqueIterations = Math.min(
    positiveInt('ITERATIONS', defaultUniqueIterations),
    fixture.unique_target_count
);
const uniqueVUs = Math.min(
    uniqueIterations,
    positiveInt('VUS', smoke ? 10 : profile === 'heavy' ? 250 : 75)
);
const contentionDuration = __ENV.CONTENTION_DURATION || (smoke ? '15s' : '90s');
const retryRate = positiveInt('RETRY_RATE', smoke ? 5 : 25);
const rejectionRate = positiveInt('REJECTION_RATE', smoke ? 5 : 15);
const statsRate = positiveInt('STATS_RATE', 1);

const ordinaryDuration = new Trend('events_unique_ordinary_duration', true);
const performanceDuration = new Trend('events_unique_performance_duration', true);
const retryDuration = new Trend('events_retry_duration', true);
const rejectionDuration = new Trend('events_rejection_duration', true);
const statsDuration = new Trend('events_stats_duration', true);

const uniqueOrdinaryErrors = new Rate('events_unique_ordinary_errors');
const uniquePerformanceErrors = new Rate('events_unique_performance_errors');
const retryErrors = new Rate('events_retry_errors');
const rejectionErrors = new Rate('events_rejection_errors');
const statsErrors = new Rate('events_stats_errors');
const unexpectedErrors = new Rate('events_unexpected_errors');

const totalRequests = new Counter('events_total_requests');
const uniqueWrites = new Counter('events_unique_writes');
const acceptedRetries = new Counter('events_accepted_retries');
const expectedRejections = new Counter('events_expected_rejections');
const statsReads = new Counter('events_stats_reads');
const unexpectedStatuses = new Counter('events_unexpected_statuses');

const scenarios = {
    unique_writes: {
        executor: 'shared-iterations',
        exec: 'writeUniqueEvents',
        vus: uniqueVUs,
        iterations: uniqueIterations,
        maxDuration: __ENV.MAX_DURATION || (smoke ? '2m' : '10m'),
        gracefulStop: '10s',
    },
    duplicate_retries: {
        executor: 'constant-arrival-rate',
        exec: 'retryExistingEvents',
        rate: retryRate,
        timeUnit: '1s',
        duration: contentionDuration,
        preAllocatedVUs: Math.max(5, retryRate),
        maxVUs: Math.max(20, retryRate * 4),
        gracefulStop: '10s',
    },
    unknown_rejections: {
        executor: 'constant-arrival-rate',
        exec: 'rejectUnknownEvents',
        rate: rejectionRate,
        timeUnit: '1s',
        duration: contentionDuration,
        preAllocatedVUs: Math.max(5, rejectionRate),
        maxVUs: Math.max(20, rejectionRate * 4),
        gracefulStop: '10s',
    },
};

const thresholds = {
    events_unique_ordinary_errors: ['rate<0.005'],
    events_unique_performance_errors: ['rate<0.005'],
    events_retry_errors: ['rate<0.005'],
    events_rejection_errors: ['rate<0.005'],
    events_unexpected_errors: ['rate<0.005'],
    events_unique_ordinary_duration: ['p(95)<750'],
    events_unique_performance_duration: ['p(95)<750'],
    events_retry_duration: ['p(95)<750'],
    events_rejection_duration: ['p(95)<500'],
    http_req_failed: ['rate<0.005'],
    'dropped_iterations{scenario:duplicate_retries}': ['count==0'],
    'dropped_iterations{scenario:unknown_rejections}': ['count==0'],
};

if (statsUrl !== '') {
    scenarios.stats_reads = {
        executor: 'constant-arrival-rate',
        exec: 'readEventStatistics',
        rate: statsRate,
        timeUnit: '1s',
        duration: contentionDuration,
        preAllocatedVUs: Math.max(2, statsRate * 2),
        maxVUs: Math.max(10, statsRate * 10),
        gracefulStop: '30s',
    };
    thresholds.events_stats_errors = ['rate<0.005'];
    thresholds.events_stats_duration = ['p(95)<2000'];
    thresholds['dropped_iterations{scenario:stats_reads}'] = ['count==0'];
}

export const options = {
    scenarios,
    thresholds,
};

function targetForIndex(targetIndex) {
    const clickIndex = Math.floor(targetIndex / fixture.steps_per_click);
    const stepIndex = targetIndex % fixture.steps_per_click;
    return {
        clickid: `${fixture.prefix}${clickIndex}`,
        step_index: stepIndex,
        variant: `events-landing-${stepIndex}-v${clickIndex % 4}`,
    };
}

function parseBody(response) {
    try {
        return response.json();
    } catch (_) {
        return null;
    }
}

function postJson(payload, eventType, responseCallback) {
    return http.post(
        `${baseUrl}/api/events.php`,
        JSON.stringify(payload),
        {
            headers: {
                'Content-Type': 'text/plain;charset=UTF-8',
                Accept: 'application/json',
            },
            redirects: 0,
            responseCallback,
            tags: {
                endpoint: 'events',
                event_type: eventType,
            },
        }
    );
}

function recordResult(ok, categoryErrors, response, eventType) {
    const tags = { event_type: eventType };
    categoryErrors.add(!ok, tags);
    unexpectedErrors.add(!ok, tags);
    totalRequests.add(1);
    if (!ok) {
        unexpectedStatuses.add(1, {
            status: `${response.status}`,
            event_type: eventType,
        });
    }
}

function ordinaryPayload(target, value, event = 'cta_click') {
    return {
        ...target,
        event,
        value,
    };
}

function performancePayload(target, ordinal) {
    return {
        ...target,
        performance: {
            ttfb: 80 + (ordinal % 600),
            fcp: 400 + (ordinal % 1600),
            lcp: 800 + (ordinal % 3200),
            inp: 40 + (ordinal % 900),
            cls: (ordinal % 250) / 1000,
        },
    };
}

/**
 * Every shared iteration owns one empty click-step. Its two keys are written
 * once and never reused by another unique-write iteration.
 */
export function writeUniqueEvents() {
    const iteration = exec.scenario.iterationInTest;
    if (iteration >= uniqueIterations) {
        exec.test.abort(`Unique Events iteration ${iteration} exceeds configured range`);
    }
    const target = targetForIndex(iteration);

    const ordinaryResponse = postJson(
        ordinaryPayload(target, 1000 + (iteration % 30000)),
        'unique_ordinary',
        http.expectedStatuses(200)
    );
    ordinaryDuration.add(ordinaryResponse.timings.duration);
    const ordinaryBody = parseBody(ordinaryResponse);
    const ordinaryOk = check(ordinaryResponse, {
        'unique ordinary: exactly accepted': (response) =>
            response.status === 200 && ordinaryBody?.ok === true,
    });
    recordResult(ordinaryOk, uniqueOrdinaryErrors, ordinaryResponse, 'unique_ordinary');
    if (ordinaryOk) {
        uniqueWrites.add(1, { event_type: 'ordinary' });
    }

    const performanceResponse = postJson(
        performancePayload(target, iteration),
        'unique_performance',
        http.expectedStatuses(200)
    );
    performanceDuration.add(performanceResponse.timings.duration);
    const performanceBody = parseBody(performanceResponse);
    const performanceOk = check(performanceResponse, {
        'unique performance: exactly accepted': (response) =>
            response.status === 200 && performanceBody?.ok === true,
    });
    recordResult(
        performanceOk,
        uniquePerformanceErrors,
        performanceResponse,
        'unique_performance'
    );
    if (performanceOk) {
        uniqueWrites.add(1, { event_type: 'performance' });
    }
}

/**
 * Reserved targets were pre-seeded by setup_events.php. Different values are
 * retried concurrently to exercise first-write-wins and idempotent 200 replies.
 */
export function retryExistingEvents() {
    const retryIndex = exec.scenario.iterationInTest % fixture.retry_target_count;
    const targetIndex = fixture.retry_target_start + retryIndex;
    const target = targetForIndex(targetIndex);
    const performance = (exec.scenario.iterationInTest % 2) === 1;
    const response = postJson(
        performance
            ? performancePayload(target, targetIndex + 100000)
            : ordinaryPayload(target, 999999),
        performance ? 'retry_performance' : 'retry_ordinary',
        http.expectedStatuses(200)
    );
    retryDuration.add(response.timings.duration);
    const body = parseBody(response);
    const ok = check(response, {
        'duplicate retry: exactly accepted': (result) =>
            result.status === 200 && body?.ok === true,
    });
    recordResult(ok, retryErrors, response, performance ? 'retry_performance' : 'retry_ordinary');
    if (ok) {
        acceptedRetries.add(1);
    }
}

/**
 * Unknown but syntactically valid names must be rejected with 422, not written
 * and not counted as transport failures by k6.
 */
export function rejectUnknownEvents() {
    const retryIndex = exec.scenario.iterationInTest % fixture.retry_target_count;
    const target = targetForIndex(fixture.retry_target_start + retryIndex);
    const response = postJson(
        ordinaryPayload(target, 1234, 'unknown_event'),
        'unknown_event',
        http.expectedStatuses(422)
    );
    rejectionDuration.add(response.timings.duration);
    const body = parseBody(response);
    const ok = check(response, {
        'unknown event: exactly rejected': (result) =>
            result.status === 422
            && body?.error === 'Event is not enabled for this campaign',
    });
    recordResult(ok, rejectionErrors, response, 'unknown_event');
    if (ok) {
        expectedRejections.add(1);
    }
}

/**
 * Optional: pass STATS_URL with a {campaign_id} placeholder and any required
 * admin authentication. setup_events.php configures table 0 for this campaign.
 */
export function readEventStatistics() {
    const response = http.get(statsUrl, {
        redirects: 0,
        responseCallback: http.expectedStatuses(200),
        tags: {
            endpoint: 'event_statistics',
        },
    });
    statsDuration.add(response.timings.duration);
    const ok = check(response, {
        'event statistics: table rendered': (result) =>
            result.status === 200
            && typeof result.body === 'string'
            && result.body.includes('statsTable0Data')
            && result.body.includes('Events API load'),
    });
    recordResult(ok, statsErrors, response, 'statistics');
    if (ok) {
        statsReads.add(1);
    }
}

export default writeUniqueEvents;

function metricValues(data, name) {
    return data.metrics[name]?.values || {};
}

function formatTrend(data, name) {
    const values = metricValues(data, name);
    return `avg=${(values.avg || 0).toFixed(2)}ms p95=${(values['p(95)'] || 0).toFixed(2)}ms`;
}

function formatRate(data, name) {
    return `${((metricValues(data, name).rate || 0) * 100).toFixed(3)}%`;
}

export function handleSummary(data) {
    const requests = metricValues(data, 'events_total_requests');
    const lines = [
        '',
        'YellowTDS Events mixed-contention summary',
        `  unique empty targets: ${uniqueIterations}`,
        `  requests classified: ${requests.count || 0}`,
        `  unique ordinary: ${formatTrend(data, 'events_unique_ordinary_duration')}, errors=${formatRate(data, 'events_unique_ordinary_errors')}`,
        `  unique performance: ${formatTrend(data, 'events_unique_performance_duration')}, errors=${formatRate(data, 'events_unique_performance_errors')}`,
        `  duplicate retries: ${formatTrend(data, 'events_retry_duration')}, errors=${formatRate(data, 'events_retry_errors')}`,
        `  expected 422 flood: ${formatTrend(data, 'events_rejection_duration')}, classification errors=${formatRate(data, 'events_rejection_errors')}`,
    ];
    if (statsUrl !== '') {
        lines.push(
            `  concurrent statistics: ${formatTrend(data, 'events_stats_duration')}, errors=${formatRate(data, 'events_stats_errors')}`
        );
    }
    lines.push(
        `  all unexpected outcomes: ${formatRate(data, 'events_unexpected_errors')}`,
        ''
    );
    return {
        stdout: lines.join('\n'),
    };
}
