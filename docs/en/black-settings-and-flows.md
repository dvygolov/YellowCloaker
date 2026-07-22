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

The **Bot** filter uses DeviceDetector's server-side User-Agent classification and accepts **Yes** or **No**. It runs before browser checks and does not replace the separate **JS bot detection** stage. When a Safe Page rule blocks a bot, Blocked clicks keeps reason `bot` and the complete original User-Agent.

Every flow also has two separate daily conversion rules:

- **Conversion cap (campaign)** counts all accepted history rows for the selected statuses across the campaign.
- **Conversion cap (flow)** counts only rows attributed to the current flow.

Choose one of `<`, `<=`, `=`, `!=`, `>=`, or `>`, one or more campaign statuses, and a numeric value. Counts include accepted paid-repeat or upsell rows. A day is bounded by the campaign timezone and always uses conversion-row time, independent of the statistics attribution setting. When a flow no longer matches its cap rule, YellowTDS evaluates the next flow and then TrafficBack.

![Conversion cap filter](../assets/screenshots/conversion-cap-filter.png)

## Steps

Steps use the same handle to keep ordering consistent. A redirect is a terminal action, so its step remains locked in the last position.
