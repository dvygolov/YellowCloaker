# System Settings

The **Settings** button in the header opens instance-wide YellowTDS settings. These values are separate from campaign settings.

![System settings modal](../assets/screenshots/settings-modal-general.png)

## Tabs

- **General** — new password, admin domain/IP restrictions, admin path, UTP, and debug mode.
- **Storage** — SQLite file name, cache root, and cache directory names.
- **Plugins** — currency sources and VPN/proxy detectors, preferred currencies, and `any`/`most` decision mode.
- **Updates** — check and install a YellowTDS update or refresh GeoBases.

The server-detected current IP is shown below **Allowed admin IP**. **Add current IP** copies it into the field with one click. On the **Plugins** tab, every plugin has an explicit switch and an **Enabled** or **Disabled** label; options belonging to a disabled plugin are inactive.

Changing the admin path, database file name, or cache directory names physically renames the corresponding files and directories. Existing destinations are treated as conflicts and are never overwritten or merged. After an admin path change, the browser automatically redirects to the new URL.

## Storage format

`settings.php` contains defaults and the settings manager. UI changes are written to the adjacent `settings.local.php`, which returns a PHP array and produces no direct output. System settings do not depend on the database.

The current password is never returned to the browser. An empty password field preserves the current value; a non-empty value replaces it.

Plugins are discovered automatically from `*Plugin.php` files in `plugins/currency/` and `plugins/vpn/`. Newly discovered plugins are disabled. Removing a plugin file removes its settings the next time Settings is opened or saved.
