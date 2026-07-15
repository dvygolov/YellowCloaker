# Overview

## What YellowTDS Is

YellowTDS is a PHP traffic distribution and routing system. It decides which scenario should be applied to each request:

- white
- black
- trafficback

The system stores click, lead, payout, event, and campaign data in SQLite and exposes an admin panel for configuration and reporting.

## Main Entities

- campaign
- campaign domains
- white settings
- black settings
- flow
- step
- postback settings
- statistics settings
