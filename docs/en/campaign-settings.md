# Campaign Settings

## Main Sections

The campaign settings page includes:

- Domains
- Safe Page
- Flows
- Integration
- Scripts
- Postbacks

The campaign name is shown at the top of the sidebar. Use the pencil icon beside it to rename the campaign without returning to the dashboard.

All editor sections use one full-size treatment for text actions. Icon-only actions use the same height as the step controls, including rows added without reloading the page.

## Domains

The **Domains list** group contains every campaign domain. Each row shows its check result and keeps the delete button aligned with the domain field.

![Campaign settings overview](../assets/screenshots/campaign-settings-overview.png)

## Safe Page

Defines what blocked or filtered traffic receives. The `−/+` control beside **Safe Page** collapses or expands the domain-specific pages in the sidebar. It appears as soon as Domain-Specific mode is selected, and navigating to a domain page expands the branch automatically.

## Flows

Defines the black branch routing for allowed traffic.

**Save user path (Sticky)** and **JS Bot Detection** use explicit On/Off switches. Save user path reuses the same step variants when the visitor returns to a previously visited flow; flow matching itself still runs on every visit. JS Bot Detection settings appear only while its switch is On; the extra framed group is no longer used.

Flows and steps use the same drag handle to the left of their names. The previous up/down arrow controls are no longer used.

Use the `−/+` control beside **Flows** to collapse or expand the whole tree. The same control beside an individual flow affects only its steps. Tree state is saved per campaign, and navigating to a hidden step expands its branch automatically.

![Flows section in campaign settings](../assets/screenshots/campaign-settings-flows.png)

## Integration

Campaign launch methods are collected on one screen:

- **PHP Connect** shows the `api/phpconnect.php` URL and the campaign API key used by the bundled `phpclient.php`.
- **JavaScript Connect** shows the ready-to-embed `<script>` tag and controls whether the routed page replaces the current content, opens in an iframe, or redirects the browser.

JavaScript Connect Action is a campaign-wide integration setting and is no longer shown under Flows.

![Campaign integration settings](../assets/screenshots/campaign-settings-integration.png)

## Scripts

Backfix, redirects, event tracking and image lazy loading use the same explicit **Off/On** switches as the campaign-wide flow options. Feature-specific fields appear only while the corresponding switch is On.
