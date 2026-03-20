<?php

declare(strict_types=1);

/**
 * Example: Working with the ps_distributionapiclient PrestaShop module.
 *
 * ps_distributionapiclient connects PrestaShop stores to the PrestaShop
 * Distribution API for marketplace features: module/theme browsing, purchase,
 * license validation, and automated updates from the Back Office.
 *
 * This file documents common integration patterns.
 */

// --- The module operates entirely from Back Office ---
// Merchants interact via: Modules > Module Manager and Modules > Module Catalog
// There is no front-office storefront display.

// --- Triggering a module update check programmatically ---
// The Distribution API client is called internally by PrestaShop's
// module update mechanism. You can trigger it via CLI:
//
// php bin/console prestashop:module update ps_facetedsearch

// --- Configuration keys used by the module ---
// Configuration::get('PS_ADDONS_API_KEY')    — Addons API key for authentication
// Configuration::get('PS_ADDONS_EMAIL')      — Account email linked to the API key

// --- Checking for available module updates (internal API) ---
// This is handled automatically by the Back Office dashboard and
// Modules > Module Manager, which calls the Distribution API on load.

// --- Distribution API base URL ---
// The client communicates with: https://api-addons.prestashop.com
// Endpoints include:
//   /request/check/  — check for module updates
//   /request/buy/    — initiate module purchase
//   /request/download/ — download a purchased module

// --- Proxy configuration for the HTTP client ---
// If your server uses an outbound proxy, configure it in PrestaShop:
// Advanced Parameters > Administration > Proxy settings
