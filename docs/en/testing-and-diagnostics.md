# Testing and Diagnostics

## PHPUnit

Tests are run from the outer repository root:

```bash
vendor/bin/phpunit
```

## Covered Areas

- flows
- statistics
- Thompson Sampling

## Server Logs

The **Logs** status item on the dashboard opens the YellowTDS server log viewer. It supports ranges of up to 31 days, level and source filters, full-text search, paged loading, and ZIP downloads of filtered records.

![Server log viewer](../assets/screenshots/server-log-viewer.png)

New events use JSON Lines files at `logs/YYYY-MM-DD.log`. Every record contains a timestamp, level (`trace`, `info`, `warning`, `error`), source, and message. Trace records are written only while Debug mode is enabled.

Configure retention under **Settings → General → Log retention**. The default is 30 days. Automatic cleanup applies only to the new daily files; legacy `logs/<category>/` directories are neither migrated nor displayed.

Logs produced by an external PHP Connect client remain on the site hosting that client and are not collected by the server viewer.
