// Shared configuration for all k6 load test scenarios

// Base URL of the PHP server under test
// Override with: k6 run -e BASE_URL=http://your-server:8080 scenario.js
export const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8080';

function positiveInt(name, fallback) {
    const value = Number.parseInt(__ENV[name] || `${fallback}`, 10);
    return Number.isFinite(value) && value > 0 ? value : fallback;
}

// Capacity tests use an open workload model. Unlike ramping-vus this keeps the
// requested arrival rate independent from response time, making saturation and
// dropped work visible instead of silently lowering the offered load.
export function getArrivalRateScenario(exec = 'capacityTraffic') {
    const rate = positiveInt('RATE', 10);
    const preAllocatedVUs = positiveInt('PREALLOCATED_VUS', Math.max(20, rate));
    const maxVUs = positiveInt('MAX_VUS', Math.max(preAllocatedVUs, rate * 4));

    return {
        executor: 'constant-arrival-rate',
        exec,
        rate,
        timeUnit: __ENV.TIME_UNIT || '1s',
        duration: __ENV.DURATION || '90s',
        preAllocatedVUs,
        maxVUs,
        gracefulStop: __ENV.GRACEFUL_STOP || '10s',
    };
}

export const CAPACITY_THRESHOLDS = {
    'http_req_failed': ['rate<=0.01'],
    'http_req_duration': ['p(95)<=500', 'p(99)<=2000'],
    'yellowtds_errors': ['rate<=0.01'],
};

// Standard ramping profile: warm-up → ramp to breaking point → cool-down
export const STANDARD_STAGES = [
    { duration: '30s', target: 10 },    // warm-up
    { duration: '1m',  target: 50 },    // light load
    { duration: '1m',  target: 100 },   // medium load
    { duration: '1m',  target: 200 },   // heavy load
    { duration: '1m',  target: 500 },   // stress
    { duration: '30s', target: 1000 },  // spike / breaking point
    { duration: '30s', target: 10 },    // cool-down / recovery
];

// Quick smoke test profile (for validation before full run)
export const SMOKE_STAGES = [
    { duration: '10s', target: 5 },
    { duration: '20s', target: 10 },
    { duration: '10s', target: 1 },
];

// Short paired A/B profile used by compare-uniqueness.ps1.
export const COMPARISON_STAGES = [
    { duration: '5s', target: 5 },
    { duration: '10s', target: 10 },
    { duration: '5s', target: 1 },
];

// Light profile for scenarios with external dependencies (JS bot detection)
export const LIGHT_STAGES = [
    { duration: '30s', target: 10 },
    { duration: '1m',  target: 30 },
    { duration: '1m',  target: 60 },
    { duration: '1m',  target: 100 },
    { duration: '30s', target: 10 },
];

// PHP built-in dev server profile (single-threaded, max ~20 concurrent)
export const DEVSERVER_STAGES = [
    { duration: '15s', target: 1 },     // baseline single-user
    { duration: '30s', target: 3 },     // light
    { duration: '30s', target: 5 },     // medium
    { duration: '30s', target: 10 },    // heavy for dev server
    { duration: '30s', target: 15 },    // stress
    { duration: '30s', target: 20 },    // breaking point
    { duration: '15s', target: 1 },     // cool-down
];

// Caddy + php-cgi profile (16 workers, ramp to 200 VU to find saturation point)
export const CADDY_STAGES = [
    { duration: '15s', target: 5 },      // warm-up
    { duration: '30s', target: 20 },     // light
    { duration: '30s', target: 50 },     // medium
    { duration: '1m',  target: 100 },    // heavy
    { duration: '1m',  target: 150 },    // stress
    { duration: '1m',  target: 200 },    // near-saturation
    { duration: '30s', target: 5 },      // cool-down
];

// Heavy write profile for SQLite stress test
export const WRITE_STRESS_STAGES = [
    { duration: '20s', target: 20 },
    { duration: '1m',  target: 100 },
    { duration: '1m',  target: 200 },
    { duration: '1m',  target: 400 },
    { duration: '1m',  target: 800 },
    { duration: '30s', target: 1000 },
    { duration: '30s', target: 10 },
];

// Standard thresholds
export const STANDARD_THRESHOLDS = {
    'http_req_duration': ['p(95)<2000'],  // 95% of requests under 2s
    'http_req_failed': ['rate<0.05'],     // less than 5% errors
};

// Strict thresholds for white traffic (should be fast — just filter + error/redirect)
export const WHITE_THRESHOLDS = {
    'http_req_duration': ['p(95)<500'],
    'http_req_failed': ['rate<0.01'],
};

// Relaxed thresholds for black traffic (loads HTML from disk, writes to DB)
export const BLACK_THRESHOLDS = {
    'http_req_duration': ['p(95)<1000'],
    'http_req_failed': ['rate<0.02'],
};

// Use: k6 run -e PROFILE=smoke scenario.js  to pick a lighter profile
export function getStages(defaultStages) {
    const profile = __ENV.PROFILE || 'standard';
    switch (profile) {
        case 'smoke': return SMOKE_STAGES;
        case 'comparison': return COMPARISON_STAGES;
        case 'light': return LIGHT_STAGES;
        case 'heavy': return WRITE_STRESS_STAGES;
        case 'devserver': return DEVSERVER_STAGES;
        case 'caddy': return CADDY_STAGES;
        default: return defaultStages;
    }
}
