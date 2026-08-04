# Shared Hosting Installation

Installing YellowTDS on ordinary shared hosting has not changed: select a domain, upload the files, and open the admin panel in a browser.

## Hosting requirements

- PHP 8.2 or newer;
- the PHP `sqlite3` extension;
- permission for PHP to write inside the YellowTDS directory;
- HTTPS for the selected domain or subdomain.

The `curl`, `mbstring`, `xml`, `zip`, and `maxminddb` extensions are recommended but are not required for a basic installation.

## Installation

1. Download and extract the `multipleconfigs` branch ZIP:

   ```text
   https://github.com/dvygolov/YellowTDS/archive/refs/heads/multipleconfigs.zip
   ```

2. Upload only the contents of the `code/` directory to the website directory. `code/` is already the complete distribution.
3. Enable PHP 8.2+, SQLite, and HTTPS in the hosting control panel.
4. Open the admin panel and finish the configuration:

   ```text
   https://example.com/admin/
   ```

YellowTDS can be placed at the website root or in a subdirectory. For a subdirectory installation, include it in the admin URL, for example `https://example.com/tds/admin/`.

## Security note

The admin panel opens directly and does not require `.htaccess`. Before sending production traffic, however, the web server must block direct access to the SQLite database, settings, and logs. Direct Load also requires its service URLs to be routed to `index.php`.

Apache and LiteSpeed use `.htaccess` for these rules; nginx uses equivalent site configuration. If the host already routes missing URLs to `index.php`, Direct Load works without any additional action. If no such rule exists and the configuration cannot be changed, Direct Load is unavailable; use Base Load instead.

This is web-server protection, not a separate YellowTDS installation step. Ready-to-use examples are available under [Required Web-Server Rules](hosting-panels.md#required-web-server-rules).
