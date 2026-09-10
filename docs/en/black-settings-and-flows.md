# Black Settings and Flows

## Main Areas

- JS Connect action
- JS bot detection
- flows

## Flows

A flow contains:

- name
- filters
- steps
- distribution
- optimization settings

Flows are evaluated in order. Drag the handle to the left of a flow name to reorder it. When the handle has keyboard focus, `↑` and `↓` provide the same control.

When [uniqueness counting](uniqueness.md) is enabled, flow filters include Campaign and Flow uniqueness conditions. Safe Page never exposes this filter.

## Filter Value Format

For fields with **in** and **not in**, enter multiple values as a comma-separated list without quotes: `en,it`, `desktop,mobile,other`, `Android,iOS`. Spaces around commas are ignored, and matching is case-insensitive. These operators require an exact value match; use **contains** or **not contains** for partial matching when the selected filter supports them.

**Language** checks the browser's primary language from the HTTP `Accept-Language` header. YellowTDS takes the highest-priority language, lowercases it, and keeps the first two characters. Use two-letter [ISO 639-1 language codes](https://en.wikipedia.org/wiki/List_of_ISO_639_language_codes): `en`, `it`, `ru`, `de`, `fr`, `es`, `pt`, `tr`, and so on. Region subtags are not stored: `en-US` and `en-GB` are matched as `en`.

**Country** checks the visitor country by IP and uses two-letter uppercase [ISO 3166-1 alpha-2 country codes](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2): `US`, `IT`, `RU`, `DE`, `BR`.

**Device** checks the device type returned by the bundled DeviceDetector library. Accepted values are:

`desktop`, `smartphone`, `tablet`, `feature phone`, `console`, `tv`, `car browser`, `smart display`, `camera`, `portable media player`, `phablet`, `smart speaker`, `wearable`, `peripheral`.

The filter also supports the convenience alias `mobile`: it means phone traffic and expands to `smartphone`, `feature phone`, and `phablet`. A `feature phone` is a basic phone with a browser; a `phablet` is a large smartphone between a phone and a tablet. Tablets are not included in `mobile`; add `tablet` separately when needed. For example, `mobile,tablet` matches phones and tablets, while `mobile` matches phones only.

The `other` alias groups the remaining rare device types: `console`, `tv`, `car browser`, `smart display`, `camera`, `portable media player`, `smart speaker`, `wearable`, and `peripheral`. `other` does not include `desktop`, `smartphone`, `feature phone`, `phablet`, or `tablet`.

The **Bot** filter uses DeviceDetector's server-side User-Agent classification and accepts **Yes** or **No**. It runs before browser checks and does not replace the separate **JS bot detection** stage. When a Safe Page rule blocks a bot, Blocked clicks keeps reason `bot` and the complete original User-Agent.

Every flow also has two separate daily conversion rules:

- **Conversion cap (campaign)** counts all accepted history rows for the selected statuses across the campaign.
- **Conversion cap (flow)** counts only rows attributed to the current flow.

Choose one of `<`, `<=`, `=`, `!=`, `>=`, or `>`, one or more campaign statuses, and a numeric value. Counts include accepted paid-repeat or upsell rows. A day is bounded by the campaign timezone and always uses conversion-row time, independent of the statistics attribution setting. When a flow no longer matches its cap rule, YellowTDS evaluates the next flow and then TrafficBack.

![Conversion cap filter](../assets/screenshots/conversion-cap-filter.png)

## Steps

Steps use the same handle to keep ordering consistent. A redirect is a terminal action, so its step remains locked in the last position.

Each step can contain folders or redirect URLs. Weight is stored with the corresponding folder or URL. A folder also stores its load type and its own MVT settings.

## Landing MVT

Every folder entry has an **MVT** section. The Copy button beside each Test name copies its placeholder, such as `#TEST1#`; put it into the landing HTML and add text or HTML Values to the Test. YellowTDS selects one Value independently and uniformly for every active TEST and performs a trusted string replacement. The same placeholder may occur more than once in the HTML.

TEST numbers follow creation order. Values use A, B … Z, AA, and subsequent codes. A saved Value is read-only: archive it and append another Value when the content must change. Archived TESTs and Values stay in the configuration with their original number or code, so numbering never rolls back.

![MVT settings for a folder entry](../assets/screenshots/campaign-settings-mvt.png)

One `clickid + step` always keeps the same landing and MVT combination. A new click first reuses the current PHP-session assignment. When **Save user path** is enabled, the existing `saved_paths` cookie provides a five-day fallback. Direct Load refreshes and Back navigation read the assignment already recorded for the reached step instead of selecting again.

Only active TESTs and Values participate in new assignments. A previously assigned archived Value continues to render for an existing click, session, or Sticky path while MVT remains enabled. When MVT is disabled, YellowTDS does not replace placeholders and does not scan or repair the landing HTML.
