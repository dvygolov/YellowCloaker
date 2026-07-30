# Admin Login

## Admin Path

The main admin entry point is:

- `/admin/`

![Admin login page](../assets/screenshots/admin-login-page.png)

## Password Source

The password is configured in:

- `settings.php`

Key:

- `adminPassword`

## Protection

Authentication stores a logged-in session and uses rate limiting for repeated failed attempts.

## Domain Restriction

You can restrict admin access to a single host with:

- `adminDomain`

## IP Restriction

You can also restrict admin access to selected IP addresses with:

- `adminIp`

The value contains one or more comma-separated IPv4/IPv6 addresses, for example `198.51.100.10, 203.0.113.15, 2001:db8::10`. Access is allowed when the current IP matches any address in the list. Existing single-IP values remain valid.

When the site is behind Cloudflare, the IP check uses `CF-Connecting-IP`, but only if the proxy IP itself belongs to Cloudflare. This check depends on a readable and fresh ASN geobase.

When **Debug Mode** is disabled, an allowed-domain or allowed-IP mismatch returns only a generic `404 Not Found` response. Neither the configured value nor the detected value is exposed publicly. When **Debug Mode** is enabled, the diagnostic denial reason is shown instead.
