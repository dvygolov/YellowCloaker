```
                            YellowTDS
    _            __     __  _ _             __          __  _
   | |           \ \   / / | | |            \ \        / / | |
   | |__  _   _   \ \_/ /__| | | _____      _\ \  /\  / /__| |__
   | '_ \| | | |   \   / _ \ | |/ _ \ \ /\ / /\ \/  \/ / _ \ '_ \
   | |_) | |_| |    | |  __/ | | (_) \ V  V /  \  /\  /  __/ |_) |
   |_.__/ \__, |    |_|\___|_|_|\___/ \_/\_/    \/  \/ \___|_.__/
           __/ |
          |___/             https://yellowweb.top

If you like this script, PLEASE DONATE!
```

[Support this project](https://yellowweb.top/donate)

# YellowTDS

YellowTDS is a traffic distribution system for routing traffic according to campaign rules. The project includes the filtering engine, SQLite storage, admin panel, statistics, click logs, postback handling, and multiple integration modes.

## What This Product Does

The system receives incoming traffic and decides what should be returned for each request:

- the white branch for blocked or filtered traffic
- the black branch for allowed traffic
- trafficback when no campaign matches

Key capabilities:

- campaign-based routing by domain
- white and black logic
- multi-step funnels and flows
- equal, weighted, and Thompson Sampling distribution
- JS bot detection
- S2S postbacks
- statistics, custom tables, and click views
- JS Connect and PHP Connect

## Quick Start

### VPS Auto-Install

For a clean Debian/Ubuntu VPS, use the auto-installer:

```bash
curl -fsSL https://raw.githubusercontent.com/dvygolov/YellowTDS/multipleconfigs/code/install.sh | sudo bash
```

The script asks for a domain, verifies DNS points to the VPS, installs nginx/PHP/HTTPS, the MMDB C extension, and downloads geobases from `sapics/ip-location-db`.

The automatic installer is intended for a clean VPS without a hosting control panel. If FastPanel, Plesk, cPanel/WHM, DirectAdmin, HestiaCP, VestaCP, aaPanel, ISPmanager, CyberPanel, or CloudPanel is detected, it stops before changing the server configuration. On such servers, follow [Installing with Hosting Control Panels](docs/en/hosting-panels.md).

To add multiple domains to an existing instance:

```bash
curl -fsSL https://raw.githubusercontent.com/dvygolov/YellowTDS/multipleconfigs/code/install.sh | sudo bash -s -- --add-domain
```

Enter domains comma-separated, for example: `tds1.example.com,tds2.example.com`.

See: [VPS Installation](docs/en/installation.md).

### Manual Install

1. Copy the contents of `code/` into the site's document root.
2. Open `settings.php` and configure at least:
   - `adminPassword`
   - `dbConnection`
   - `debug` (`false` in production)
   - `adminDomain` if needed
   - `adminIp` if needed: one or more comma-separated IP addresses
3. Make sure PHP can write to:
   - `db/`
   - `logs/`
   - `caching/`
4. Open `/admin/`.
5. Create a campaign, add domains, configure white/black behavior, and save.

## Main Entry Points

- `code/index.php` — main runtime entry point
- `code/js/index.php` — JS Connect
- `code/api/phpconnect.php` — PHP Connect API
- `code/api/postback.php` — incoming postbacks
- `code/send.php` — lead form submission relay
- `code/next.php` — funnel step transitions
- `code/admin/` — admin panel

## Repository Layout

- `code/` — the self-contained YellowTDS distribution; deployment needs only this directory;
- `docs/` — RU/EN user documentation, screenshots, and OpenAPI;
- `tests/` — engine, application, browser/load tests, and diagnostic tools;
- `temp/` — local temporary and IDE files that are not part of the distribution.

## Full Documentation

The full bilingual documentation lives inside this repository:

- [English documentation](docs/en/index.md)
- [Русская документация](docs/ru/index.md)

Recommended reading order:

1. [Product Overview](docs/en/overview.md)
2. [How It Works](docs/en/how-it-works.md)
3. [Admin Login](docs/en/admin-login.md)
4. [Campaign Settings](docs/en/campaign-settings.md)
5. [Statistics](docs/en/statistics.md)
