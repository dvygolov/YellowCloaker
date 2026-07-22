# Conversions and Postbacks

## Campaign Status Catalog

The **Conversions** section in Campaign settings contains the campaign-wide status catalog. Every row has:

- an immutable internal name used by YellowTDS;
- comma-separated incoming aliases.

Matching is case-insensitive. A name or alias can belong to only one status. Lead, Purchase, Reject, and Trash cannot be renamed or deleted, but their aliases can be changed. Custom statuses such as Reg, Dep, or Install can be added without a fixed limit.

Deleting a custom status shows its current and historical uses before confirmation. Existing click snapshots, conversion history, and saved report definitions remain intact; the deleted name and aliases are no longer accepted as incoming values.

![Conversions settings](../assets/screenshots/conversions-settings-overview.png)

## Incoming Postback

Send a request to `api/postback.php` with:

- `clickid` — required click identifier;
- `status` — required internal name or alias;
- `payout` — optional non-negative number, default `0`;
- `currency` — optional three-letter code, default `USD`;
- `tid` — optional transaction identifier;
- `pbkey` — required only when pbkey protection is enabled for the campaign.

Example:

```text
/api/postback.php?clickid={sub1}&status={status}&payout={payout}&currency=USD&tid={transaction_id}&pbkey=secret
```

The first accepted status is the clickid's initial conversion. Later status changes are stored in history but do not increase the base **Conversions** metric. `clicks.status` is the latest accepted status and `clicks.payout` is cumulative revenue. The raw incoming status is retained in history for diagnostics.

Unknown statuses are rejected and written to the warning log. If pbkey protection is enabled, any rejected postback is returned as a generic `404 Not Found` unless Debug Mode is enabled. Without pbkey protection, YellowTDS always returns the actual JSON result.

## Duplicate Transactions and Paid Repeats

When Transaction ID deduplication is enabled, every reused `tid` is rejected. A paid repeat of the current status requires a new `tid`; status or payout correction by reusing a prior `tid` is not supported. A repeat of the current status without payout is always rejected.

When deduplication is disabled, **Paid repeat without tid** controls a paid repeat of the current status:

- **Reject duplicate** — reject it; this is the default.
- **Accept as upsell** — append another history row and add its payout.

## Other Conversion Sources

The same resolver and history writer are used for all sources:

- **Successful proxied form** optionally creates a zero-payout status; the default selected status is Lead.
- **Website status tracking** injects `ytdsConversion(status)` into routed pages. It uses the current clickid, accepts an internal name or alias, and never accepts payout.

```javascript
ytdsConversion('Reg').then(console.log).catch(console.error);
```

## Outgoing S2S Postbacks

The event checklist is generated from the campaign catalog. `{status}` in the outgoing URL is replaced with the normalized internal status. Each accepted history row, including an accepted paid repeat, can trigger its selected S2S events.
