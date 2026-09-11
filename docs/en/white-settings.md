# Safe Page Settings

## Purpose

Safe Page settings define what to do with traffic that should not enter the offer funnel.

Short explanations for filters, Global/Domain-Specific scope, and HTTP codes are available from the `i` icons. Hover an icon or focus it with the keyboard to show its hint.

The full reference for every filter and value format is in [Filter Value Format](black-settings-and-flows.md#filter-value-format).

## Available Actions

- local safe page from folder
- redirect
- load a website using CURL
- return HTTP code

In **local safe page from folder** mode, **Upload ZIP** creates a new folder in the system safe-page directory. The ZIP must contain `index.php`, `index.html`, or `index.htm` at archive root or inside one top-level folder. Use **Add Existing** to select a folder that is already uploaded.

![Safe Page settings section](../assets/screenshots/white-settings-overview.png)

## Global vs domain-specific

You can use one shared safe-page configuration or an independent configuration for each campaign domain.

When you switch to **Domain-Specific**, the domain links appear in the editor sidebar immediately. Saving or reloading the page is not required, and adding or removing a domain updates those links right away.

The **Method** dropdown contains the same four actions in both the global configuration and every domain-specific Safe Page. Selecting an action immediately shows only its related fields.

![Domain-specific safe page tree](../assets/screenshots/white-settings-domain-specific.png)
