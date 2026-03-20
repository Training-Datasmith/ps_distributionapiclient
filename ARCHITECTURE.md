# Architecture: ps_distributionapiclient

## Purpose

A PrestaShop module providing an API client for the PrestaShop Distribution API. Enables
modules to communicate with PrestaShop's central distribution infrastructure for module
updates, marketplace integration, and license management.

## Directory Structure

```
ps_distributionapiclient.php   # Main module class
src/
  Client/                      # HTTP client implementations for the Distribution API
  Config/                      # Client configuration (endpoints, credentials)
vendor/                        # Bundled dependencies
translations/                  # Translation files
tests/                         # PHPStan and unit tests
```

## Key Design Decisions

Provides a PSR-compliant HTTP client configured for the PrestaShop Distribution API.
Authentication is handled via OAuth or API keys stored in module configuration.
Other PrestaShop modules depend on this module's services via Symfony service injection.

## Extension Points

Register custom API endpoints by extending the client configuration in your module's
service definitions.
