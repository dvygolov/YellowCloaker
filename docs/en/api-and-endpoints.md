# API and Endpoints

## Main Runtime Endpoints

- `index.php`
- `js/index.php`
- `api/phpconnect.php`
- `api/postback.php`
- `api/conversion.php`
- `send.php`
- `next.php`
- `api/updateparams.php`

## Conversion Endpoints

`api/postback.php` accepts `clickid`, `status`, optional `payout`, `currency`, a campaign-configured transaction ID parameter, and campaign `pbkey`. The default transaction ID name is `tid`; campaigns can allow several names for different affiliate programs. Send only one non-empty configured name per request. Query and form fields are read explicitly, while cookies and a field duplicated between GET and POST are rejected or ignored as described in [Conversions and Postbacks](postbacks.md). The endpoint returns a structured JSON result unless pbkey protection masks a rejected request as `404 Not Found`.

`api/conversion.php` is a same-origin POST endpoint used by the injected `ytdsConversion(status)` helper. Website status tracking must be enabled in the campaign. It accepts only the current `clickid` and an internal status name or alias; payout is not exposed to the browser.

All conversion sources write the same `conversions` history and update the click snapshot atomically.

## Campaign Integration

The campaign editor's **Integration** section keeps both external launch methods together:

- PHP Connect: copy the endpoint and campaign API key into the bundled `phpclient.php`.
- JavaScript Connect: embed the displayed `js/index.php` script tag and select how the routed page is opened.

The JavaScript action is stored per campaign and supports content replacement, iframe, and redirect modes.

## Admin Endpoints

- `admin/login.php`
- `admin/campeditor.php`
- `admin/clmnseditor.php`
- `admin/clicksdata.php`
- `admin/fileeditor.php`
- `admin/listfolders.php`
- `admin/zipupload.php`
