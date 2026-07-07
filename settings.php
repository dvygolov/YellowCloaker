<?php
$cloSettings =
[
//password for the cloaker's admin page
"adminPassword" => "12345qweasd",

//if you add a domain here, then only users from this domain will be able to access the admin page
//all others will get a 404
"adminDomain" => "",

//if you add an IP here, then only users from this IP will be able to access the admin page
//when behind Cloudflare, the real visitor IP will be taken from CF-Connecting-IP only for real Cloudflare proxy IPs
"adminIp" => "",

//admin panel path segment. Installer can replace this with a random value like e3c80abc
"adminPath" => "admin",

//WARNING:if you are using nginx either change your website's config so that it prevents people from
//downloading your database, or just rename the db file so security through obscurity will work! :-D
"dbConnection" => "clicks.db",

//set to true if you want to use universal thankyou page (UTP) instead of the thankyou pages from your landings, 
//UTP autotranslates itself to the user's language and lets you effortlessy 
//manage pixels for Facebook/TikTok/Google and other sources.
"useUTP" => false,

//if true the cloaker will:
//- show PHP errors if any,
//- won't obfuscate any javascript code
//- add tracing to some javascripts (they will print info to browser console)
//- will add YWB headers to the response, where you'll be able to see, how long does it take to process requests
"debug" => true,

//root directory for all caches
"cachingDir" => "caching",

//folder where all landings and prelandings are stored (inside cachingDir)
"landingFolder" => "landings",

//folder where all white pages are stored (inside cachingDir)
"whiteFolder" => "whites",

//folder for caching CURL white page resources (inside cachingDir, auto-managed)
"whiteCurlCache" => "whites_curl",

//folder for DeviceDetector cache (inside cachingDir)
"devicesCache" => "devices",

//folder for currency rate cache (inside cachingDir)
"currencyCache" => "currency",

//folder for proxy/VPN detection cache (inside cachingDir)
"proxyVpnCache" => "proxyvpn",

//external service plugins. Plugins are registered explicitly, no directory scanning.
"plugins" => [
    "currency" => [
        "sources" => [
            "frankfurter" => [],
            "turkish" => ["RUB", "THB"]
        ]
    ],
    "proxyVpn" => [
        "mode" => "any",
        "detectors" => ["blackbox", "ipintel"]
    ]
]
];

function get_cache_path(string $subKey): string {
    global $cloSettings;
    return $cloSettings['cachingDir'] . '/' . $cloSettings[$subKey];
}
