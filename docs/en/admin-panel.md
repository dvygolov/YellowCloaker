# Admin Panel

## Main Pages

- `admin/index.php` — campaigns dashboard
- `admin/campsettings.php` — campaign settings
- `admin/statistics.php` — statistics tables
- `admin/clicks.php` — click logs

## Main Capabilities

- manage campaigns
- configure traffic logic
- inspect statistics
- inspect click data
- manage folders and files
- scroll the campaign list within the available dashboard height
- view free disk space and the SQLite database, cache, and log sizes in the status bar below the table

![Campaign dashboard](../assets/screenshots/admin-dashboard-campaigns.png)

The **DB** size includes the main SQLite file and its WAL/SHM sidecar files. **Cache** covers the complete configured cache directory, including uploaded landing and white pages. Values are recalculated at most once per minute so large directories do not slow down the Dashboard. Free disk space is highlighted in yellow below 15% and red below 5%.

The **Settings** button opens [system settings](system-settings.md), plugin controls, and update actions. The GeoBases date in the header is informational.
