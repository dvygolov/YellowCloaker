// Traffic generation helpers for k6 load tests
// Provides realistic, diverse User-Agents, IPs, headers, query strings

// ── User-Agent pools ──

export const BOT_USER_AGENTS = [
    'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
    'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
    'Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)',
    'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
    'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)',
    'Mozilla/5.0 (compatible; SemrushBot/7~bl; +http://www.semrush.com/bot.html)',
    'Mozilla/5.0 (compatible; MJ12bot/v1.4.8; http://mj12bot.com/)',
    'Mozilla/5.0 (compatible; DotBot/1.2; +https://opensiteexplorer.org/dotbot)',
    'Twitterbot/1.0',
    'Mozilla/5.0 (compatible; Bytespider; spider-feedback@bytedance.com)',
    'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.0; +https://openai.com/gptbot)',
    'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/W.X.Y.Z Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
];

export const MOBILE_USER_AGENTS = [
    // Android Chrome
    'Mozilla/5.0 (Linux; Android 14; SM-S928B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.6167.143 Mobile Safari/537.36',
    'Mozilla/5.0 (Linux; Android 13; Pixel 7 Pro) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.230 Mobile Safari/537.36',
    'Mozilla/5.0 (Linux; Android 12; Redmi Note 11) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.6045.193 Mobile Safari/537.36',
    'Mozilla/5.0 (Linux; Android 11; Samsung Galaxy A52) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.5993.112 Mobile Safari/537.36',
    'Mozilla/5.0 (Linux; Android 14; OnePlus 12) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.43 Mobile Safari/537.36',
    // iOS Safari
    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1',
    'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
    // iOS Chrome
    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/121.0.6167.171 Mobile/15E148 Safari/604.1',
    // Android Firefox
    'Mozilla/5.0 (Android 14; Mobile; rv:122.0) Gecko/122.0 Firefox/122.0',
    'Mozilla/5.0 (Android 13; Mobile; rv:121.0) Gecko/121.0 Firefox/121.0',
];

export const DESKTOP_USER_AGENTS = [
    // Windows Chrome
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.6167.185 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.234 Safari/537.36',
    // Windows Firefox
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:122.0) Gecko/20100101 Firefox/122.0',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
    // Windows Edge
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36 Edg/121.0.2277.112',
    // macOS Safari
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_3) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
    // macOS Chrome
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.6167.185 Safari/537.36',
    // Linux Chrome
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.6167.185 Safari/537.36',
    // Linux Firefox
    'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:122.0) Gecko/20100101 Firefox/122.0',
];

export const ALL_REAL_USER_AGENTS = [...MOBILE_USER_AGENTS, ...DESKTOP_USER_AGENTS];

// ── Country pools ──

// Countries that MATCH white filters (will be blocked → white click)
export const WHITE_COUNTRIES = ['RU', 'BG', 'SG'];

// Countries that DON'T match white filters (will pass → black click)
export const BLACK_COUNTRIES = ['US', 'DE', 'GB', 'FR', 'BR', 'IN', 'JP', 'KR', 'AU', 'CA', 'IT', 'ES', 'MX', 'TR', 'PL'];

// ── Language pools ──

export const LANGUAGES = [
    'en-US,en;q=0.9',
    'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
    'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
    'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
    'es-ES,es;q=0.9,en-US;q=0.8,en;q=0.7',
    'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
    'ja-JP,ja;q=0.9,en-US;q=0.8,en;q=0.7',
    'ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7',
    'zh-CN,zh;q=0.9,en-US;q=0.8,en;q=0.7',
    'it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
    'pl-PL,pl;q=0.9,en-US;q=0.8,en;q=0.7',
    'tr-TR,tr;q=0.9,en-US;q=0.8,en;q=0.7',
    'bg-BG,bg;q=0.9,en-US;q=0.8,en;q=0.7',
];

// ── Referer pools ──

export const REFERERS = [
    'https://www.google.com/',
    'https://www.google.com/search?q=buy+product',
    'https://www.facebook.com/',
    'https://l.facebook.com/l.php?u=https%3A%2F%2Fexample.com',
    'https://www.instagram.com/',
    'https://t.co/abc123',
    'https://www.tiktok.com/',
    'https://www.youtube.com/',
    'https://yandex.ru/search/?text=test',
    'https://www.bing.com/search?q=test',
    '',  // direct traffic (no referer)
    '',
];

// ── Query string params ──

const UTM_SOURCES = ['facebook', 'google', 'tiktok', 'instagram', 'youtube', 'vk', 'telegram', 'twitter'];
const UTM_MEDIUMS = ['cpc', 'cpm', 'social', 'email', 'referral', 'display'];
const UTM_CAMPAIGNS = ['camp1', 'camp2', 'summer_sale', 'black_friday', 'test_2024', 'retarget_v2'];

// ── Helper functions ──

export function randomItem(arr) {
    return arr[Math.floor(Math.random() * arr.length)];
}

export function randomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

export function randomIP() {
    return `${randomInt(1, 223)}.${randomInt(0, 255)}.${randomInt(0, 255)}.${randomInt(1, 254)}`;
}

// Generate a random query string with UTM params and optional extras
// NOTE: k6 doesn't have URLSearchParams, so we build manually
export function randomQueryString(includeCpc = false) {
    const parts = [];

    // ~70% chance of UTM params
    if (Math.random() < 0.7) {
        parts.push('utm_source=' + encodeURIComponent(randomItem(UTM_SOURCES)));
        parts.push('utm_medium=' + encodeURIComponent(randomItem(UTM_MEDIUMS)));
        parts.push('utm_campaign=' + encodeURIComponent(randomItem(UTM_CAMPAIGNS)));
    }

    // ~50% chance of clickid
    if (Math.random() < 0.5) {
        parts.push('clickid=clk_' + randomInt(100000, 999999) + '_' + randomInt(1000, 9999));
    }

    // ~30% chance of fbclid
    if (Math.random() < 0.3) {
        parts.push('fbclid=fb_' + randomInt(1000000, 9999999));
    }

    // ~20% chance of sub params
    if (Math.random() < 0.2) {
        parts.push('sub1=s1_' + randomInt(1, 100));
        parts.push('sub2=s2_' + randomInt(1, 50));
    }

    if (includeCpc) {
        parts.push('cpc=' + (Math.random() * 2).toFixed(2));
    }

    return parts.length > 0 ? '?' + parts.join('&') : '';
}

// Build headers for a white (bot) request
export function whiteBotHeaders() {
    return {
        'User-Agent': randomItem(BOT_USER_AGENTS),
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language': 'en-US,en;q=0.5',
        'Accept-Encoding': 'gzip, deflate',
        'Connection': 'keep-alive',
        'X-Forwarded-For': randomIP(),
    };
}

// Build headers for a white (filtered country) request
export function whiteCountryHeaders() {
    return {
        'User-Agent': randomItem(ALL_REAL_USER_AGENTS),
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language': randomItem(LANGUAGES),
        'Accept-Encoding': 'gzip, deflate, br',
        'Connection': 'keep-alive',
        'Referer': randomItem(REFERERS),
        'X-Forwarded-For': randomIP(),
    };
}

// Build headers for a black (real user, non-filtered) request
export function blackHeaders() {
    return {
        'User-Agent': randomItem(ALL_REAL_USER_AGENTS),
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language': randomItem(LANGUAGES),
        'Accept-Encoding': 'gzip, deflate, br',
        'Connection': 'keep-alive',
        'Referer': randomItem(REFERERS),
        'Sec-Ch-Ua': '"Not A(Brand";v="99", "Google Chrome";v="121", "Chromium";v="121"',
        'Sec-Ch-Ua-Mobile': Math.random() < 0.6 ? '?1' : '?0',
        'Sec-Ch-Ua-Platform': randomItem(['"Android"', '"iOS"', '"Windows"', '"macOS"']),
        'Sec-Fetch-Dest': 'document',
        'Sec-Fetch-Mode': 'navigate',
        'Sec-Fetch-Site': 'cross-site',
        'Upgrade-Insecure-Requests': '1',
        'X-Forwarded-For': randomIP(),
    };
}

// Build headers for JS connect request (script tag load)
// IMPORTANT: Sec-Fetch-Dest MUST be 'script' — js/index.php checks this
// and returns jQuery instead of the real action if it's not 'script'
export function jsConnectHeaders(refererUrl) {
    return {
        'User-Agent': randomItem(ALL_REAL_USER_AGENTS),
        'Accept': '*/*',
        'Accept-Language': randomItem(LANGUAGES),
        'Accept-Encoding': 'gzip, deflate, br',
        'Connection': 'keep-alive',
        'Referer': refererUrl,
        'Sec-Fetch-Dest': 'script',
        'Sec-Fetch-Mode': 'no-cors',
        'Sec-Fetch-Site': 'same-origin',
        'X-Forwarded-For': randomIP(),
    };
}
