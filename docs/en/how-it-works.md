# How It Works

## General Request Flow

The main request path is:

1. The request enters `index.php`.
2. The project resolves a campaign by domain.
3. `core.php` collects click parameters and evaluates filters.
4. `tds.php` selects Safe Page, an offer funnel, or trafficback.
5. The selected action is executed through `main.php`, `actions.php`, `htmlprocessing.php`, and related components.

## Safe Page Branch

The Safe Page branch is used for traffic that should not enter the offer funnel.

## Black Branch

The black branch is used for allowed traffic and is built around flows and funnel steps.
