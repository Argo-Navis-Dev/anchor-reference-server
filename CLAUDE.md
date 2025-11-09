# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **Stellar Anchor Reference Server** implementation built with Laravel 10, demonstrating how to implement various Stellar Ecosystem Proposals (SEPs) using the [Argo Navis PHP Anchor SDK](https://github.com/Argo-Navis-Dev/php-anchor-sdk).

The server implements:
- SEP-01: Stellar Info File (stellar.toml)
- SEP-06: Deposit and Withdrawal
- SEP-08: Regulated Assets
- SEP-09: Standard KYC Fields
- SEP-10: Stellar Authentication
- SEP-12: KYC API
- SEP-24: Hosted Deposit and Withdrawal (Interactive Flow)
- SEP-31: Cross-Border Payments
- SEP-38: Anchor RFQ (Request for Quote)

## Development Setup

### Initial Setup

1. Install dependencies:
   ```bash
   php composer install
   npm install
   ```

2. Create `.env` from `.env.example` and configure

3. Create SQLite database:
   ```bash
   sqlite3 database/database.sqlite "SELECT 1;"
   ```

4. Run migrations and seed data:
   ```bash
   php artisan migrate:refresh --seed
   ```

### Running the Server

Development requires two processes running simultaneously:

```bash
php artisan serve          # Laravel server on http://localhost:8000
npm run dev               # Vite dev server on https://localhost:5173
```

The server accepts requests at `https://localhost:5173` (Vite proxies to Laravel).

### Common Commands

**Testing:**
```bash
php artisan test                              # Run all tests
php artisan test --filter=StellarCustomerTest # Run specific test
vendor/bin/phpunit tests/Unit/StellarCustomerTest.php
```

**Database:**
```bash
php artisan migrate:refresh --seed   # Reset database and seed
php artisan migrate                  # Run migrations
php artisan db:seed                  # Seed data only
```

**Build:**
```bash
npm run build    # Production build
```

**Code Quality:**
```bash
vendor/bin/pint  # Laravel Pint (code style)
```

## Architecture

### Layered Architecture

The application follows a layered architecture pattern:

1. **HTTP Layer** ([app/Http/Controllers](app/Http/Controllers)): Thin controllers that delegate to the SDK
   - Each SEP has a dedicated controller (e.g., `StellarCustomerController` for SEP-12)
   - Controllers pass requests to the PHP Anchor SDK service layer
   - The SDK handles all Stellar-specific protocol logic

2. **Integration Layer** ([app/Stellar](app/Stellar)): Business logic implementations
   - Each SEP has an `*Integration.php` class implementing SDK callback interfaces
   - Examples: `CustomerIntegration`, `TransferIntegration`, `QuotesIntegration`
   - These implement interfaces from `ArgoNavis\PhpAnchorSdk\callback\I*Integration`
   - Integration classes contain the business logic for handling SEP operations

3. **Configuration Layer** ([app/Stellar](app/Stellar)): SEP-specific configurations
   - `StellarAppConfig.php`, `StellarSep10Config.php`, `StellarSep06Config.php`, `StellarSep24Config.php`
   - These implement SDK config interfaces and read from [config/stellar.php](config/stellar.php)
   - All Stellar-specific settings are in [config/stellar.php](config/stellar.php), loaded from `.env`

4. **Helper Classes** ([app/Stellar/*/](app/Stellar)): SEP-specific utility functions
   - Each SEP folder contains helper classes (e.g., `Sep12Helper`, `Sep24Helper`)
   - Helpers manage database operations for transactions, customers, quotes, etc.

### SEP Organization

Each SEP implementation is organized in [app/Stellar](app/Stellar) by SEP number:
- `Sep06Transfer/` - Deposit and withdrawal
- `Sep08RegulatedAssets/` - Regulated assets approval
- `Sep12Customer/` - KYC management
- `Sep24Interactive/` - Interactive deposit/withdrawal
- `Sep31CrossBorder/` - Cross-border payments
- `Sep38Quote/` - Request for quote
- `Shared/` - Common utilities across SEPs

### Database Models

Key Laravel models in [app/Models](app/Models):
- `Sep12Customer` - KYC customer data
- `Sep12Field`, `Sep12TypeToFields` - KYC field definitions
- `Sep06Transaction`, `Sep24Transaction`, `Sep31Transaction` - Transaction records
- `Sep38ExchangeQuote`, `Sep38Rate` - Quote and rate data
- `Sep08KycStatus` - Regulated asset KYC status
- `AnchorAsset` - Anchor-supported assets

### Background Jobs

Transaction watchers in [app/Jobs](app/Jobs):
- `Sep6DepositPaymentsWatcher` - Monitors incoming deposits
- `Sep6DepositPendingTrustWatcher` - Watches for trustline establishment
- `Sep6WithdrawalsWatcher` - Processes withdrawals
- `Sep6PendingInfoWatcher` - Handles transactions pending additional info
- `FundTestAccounts` - Funds test accounts on testnet

Queue is configured as `database` in `.env` (see `QUEUE_CONNECTION`).

### Admin Interface

Built with [Filament 3.2](https://filamentphp.com/). Resources in [app/Filament/Resources](app/Filament/Resources) provide CRUD interfaces for:
- Customers, KYC fields, transactions, assets, rates, quotes
- Default admin credentials: `anchor.admin@argo-navis.dev` / `AnchorAdmin2023!`

### Routes

- [routes/api.php](routes/api.php): All SEP API endpoints (SEP-06, 08, 10, 12, 24, 31, 38)
- [routes/web.php](routes/web.php): SEP-01 (stellar.toml) at `.well-known/stellar.toml`, SEP-12 demo UI
- Most routes require `StellarAuthMiddleware` for SEP-10 JWT authentication

### Authentication Flow

1. Client requests challenge from `/auth` (SEP-10)
2. Client signs challenge and submits
3. Server validates and returns JWT token
4. Subsequent requests include JWT via `StellarAuthMiddleware`
5. Middleware validates JWT and injects `stellar_auth` data into request

### Important Configuration Notes

- **Network**: Configured in `.env` via `STELLAR_NETWORK`, `STELLAR_NETWORK_PASSPHRASE`, `STELLAR_HORIZON_URL`
- **Signing Keys**: `STELLAR_SIGNING_KEY` (server operations), `STELLAR_JWT_SIGNING_KEY` (JWT signing)
- **Assets**: Test assets (USDC, JPYC) and SEP-08 regulated asset (STAR) configured in `.env`
- **SEP-12**: Set `auto_accept` in [config/stellar.php](config/stellar.php) for automatic KYC approval
- **Database**: Uses SQLite by default (`database/database.sqlite`)

### Testing

Tests in [tests/Unit](tests/Unit) cover end-to-end SEP flows:
- `StellarCustomerTest` - SEP-10 auth + SEP-12 KYC
- `StellarTransferTest` - SEP-06 transfers
- `StellarInteractiveTest` - SEP-24 interactive flow
- `StellarQuotesTest` - SEP-38 quotes
- `StellarCrossBorderTest` - SEP-31 cross-border
- `StellarRegulatedAssetsTest` - SEP-08 regulated assets

Tests use the actual database (not in-memory) - see [phpunit.xml](phpunit.xml).

## Key Design Patterns

1. **Integration Interface Pattern**: All SEP business logic implements SDK callback interfaces (`ICustomerIntegration`, `ITransferIntegration`, etc.)
2. **Helper Pattern**: Each SEP has helper classes to encapsulate database operations and data transformations
3. **Config Pattern**: SEP configurations are centralized in [config/stellar.php](config/stellar.php), accessed via config classes
4. **Delegation Pattern**: Controllers are thin wrappers that delegate to the PHP Anchor SDK service layer

## Working with SEPs

When modifying SEP implementations:
1. Understand the SDK interface in `ArgoNavis\PhpAnchorSdk\callback\`
2. Modify the integration class in [app/Stellar/Sep*/](app/Stellar)
3. Update helper classes if database schema changes
4. Update configuration in [config/stellar.php](config/stellar.php) if needed
5. Update models and migrations for data structure changes
6. Test using the corresponding test in [tests/Unit](tests/Unit)

The PHP Anchor SDK handles all Stellar protocol compliance - focus on business logic in integration classes.
