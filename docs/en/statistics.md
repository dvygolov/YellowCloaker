# Statistics

## Main Capabilities

Campaign statistics let you:

- create multiple saved tables
- configure columns
- configure group by
- save filters
- save order by
- configure MVT grouping
- export tables to XLSX

![Statistics table example](../assets/screenshots/statistics-table-overview.png)

![Statistics table editor modal](../assets/screenshots/statistics-table-editor-modal.png)

## MVT Grouping

Open the table editor with the columns icon. In **Group By**, choose **+ MVT**, then select a `Flow / Step / Landing` placement.

- **All combinations** — the exact assignment across all TESTs, such as `ACB`;
- turn it off to select individual TESTs. Their checked rows can be dragged into the nesting order: TEST1 followed by TEST2 makes `A → A/B/C` branches, and a third selected TEST adds the next level below each branch.

After **OK**, MVT appears as a compact editable **MVT** item in the ordinary **Group By** list and can be moved with the other dimensions. Hover or focus its information icon to see the selected `Flow / Step / Landing` placement and TEST mode; use the pencil to edit it or the trash icon to remove it. The chosen placement scopes the report, so redundant **Flow**, **Step**, and **Landing** dimensions are disabled. Remove the MVT item to restore them.

![MVT grouping in statistics](../assets/screenshots/statistics-mvt-grouping.png)

Combination labels use no separators. A missing TEST number is shown as a dash: TEST1=A, TEST2=C, TEST4=B is displayed as `AC-B`. Sorting and aggregation use the structured JSON assignment rather than parsing this short label.

MVT grouping is always scoped to its campaign, flow, step, and landing, so identically numbered TESTs from different placements cannot mix. A click is counted when it reaches the step. A conversion is attributed to the MVT assignment of every step reached by its recorded conversion step. No separate impression or pageview rows are created.

## Custom Metrics

Custom formula columns can use:

- base metrics
- derived metrics

The former built-in Approval, Approval without trash, App, App(t), and related sales CR presets are not special metrics. Build the required business ratio as a formula instead. For example:

- `App`: `purchase/conversion*100`
- `App(t)`: `purchase/(conversion-trash)*100`

Division by zero produces `0`.

## Event Columns

Events enabled in campaign [Events settings](events.md) are added with **+ Event** in the table editor. Choose the metric and calculation in a separate dialog; configured Event columns remain in the main list while the full set of options stays out of the way. Event data is reported in the ordinary statistics table; use the **Flow → Step → Landing** grouping hierarchy to compare the landing pages that produced the samples.

When adding an event column, choose one calculation:

- **Count** — number of recorded samples
- **Average** — arithmetic mean of the recorded values
- **P75** — 75th percentile
- **Min** — smallest recorded value
- **Max** — largest recorded value

**Count** is the default for scroll, visible-time, and custom events. **P75** is the default for LCP, INP, CLS, TTFB, and FCP performance measurements. Sum is deliberately unavailable because adding elapsed times or browser measurements does not produce a meaningful result.

P75 uses the nearest-rank method: sort the available values from smallest to largest and take the value at position `ceil(0.75 × N)`, counting positions from one. Missing measurements are excluded. A group without values displays `0` for Count and `—` for Average, P75, Min, and Max.

## Conversion Attribution

**Settings → General → Conversion attribution** selects one mode for every statistics table:

- **Click time** attributes conversions and all revenue rows to the original click date.
- **Conversion time** attributes initial conversions and revenue to the time of each accepted conversion row.

The **Conversions** base metric counts only the first accepted status for a clickid. Later status changes and paid repeats remain in conversion history but do not increase this metric. The campaign timezone shown in **Misc** controls date grouping. The timezone selector in the page header edits that same campaign value.

## Status Columns

Use **+ Status** in the table editor to choose a campaign status and one calculation:

- **Current** — clickids whose latest accepted status equals the selected status.
- **Count** — every accepted history row with that status; a clickid may count more than once.
- **Unique clickids** — distinct clickids that have the status at least once.
- **Nth occurrence** — clickids whose selected occurrence of the status falls in the attribution period.

Each calculation has its own tooltip in the editor. Lead, Purchase, Reject, and Trash are ordinary **Current** presets. A formula can use a configured status-column token as well as the built-in metric tokens.

Filtering by **Status** always uses the latest status snapshot. Its value is free text, so a removed historical status can still be entered manually.

![Status column editor](../assets/screenshots/statistics-status-column.png)

## Unique Clicks

**Uniques** counts Campaign unique clicks. **Flow uniques** sums unique flow entries and is available without Group by Flow. **U/C**, **EPuC**, and **CPuC** use Campaign uniques. Legacy clicks are not backfilled, and legacy-only or mixed groups show `—`. See [Uniqueness Counting](uniqueness.md).
