# BantayGamit

**BantayGamit — Web-Based Barangay Equipment Monitoring System** is a server-rendered CodeIgniter 4 application for barangay equipment inventory, borrowing, returns, maintenance, notifications, reports, users, and audit activity.

## Stack

- PHP 8.2+
- CodeIgniter 4.7+
- MySQL / MySQLi
- CodeIgniter MVC, migrations, seeders, validation, sessions, CSRF, and filters
- HTML5, CSS3, and vanilla JavaScript

The backend conventions follow the supplied TallyTech reference while the reusable PHP views and CSS adapt the supplied Berry dashboard design language. No React, Material UI, React Router, or Vite runtime is required.

## Roles

- `admin`: full system administration, inventory, requests, users, reports, audit logs, and settings
- `barangay_official`: inventory operations, approvals, releases, returns, maintenance, reports, and notifications
- `borrower`: browse equipment, submit/cancel own pending requests, track active loans/history, notifications, and profile

Role filters protect restricted URLs on the server. Borrower request ownership is checked again in the controller before record details are rendered.

## Main Features

- Equipment, categories, and storage locations
- Derived availability based on approved reservations, outstanding releases, and active maintenance
- Multi-item borrowing requests
- Central request transition rules
- Approval/rejection with rejection reason
- Release and return condition recording
- Overdue detection
- Maintenance quantity tracking
- User administration and account activation/deactivation
- Per-user notifications
- Audit logging for important actions
- Operational reports with printer-friendly output and styled, color-coded XLSX export
- Responsive Berry-inspired header, sidebar/drawer, cards, tables, forms, chips, alerts, and confirmation dialog

## Requirements

- PHP 8.2 or newer with `intl`, `mbstring`, `mysqli`, `gd`, `xml`, and `zip` enabled
- Composer 2
- MySQL 8+ or compatible MySQL/MariaDB server
- A web server whose production document root points to `public/`

## Installation

1. Place the `BantayGamit` project at `~/Documents/Projects/BantayGamit`.
2. Run:

```bash
composer install
```

If you are applying an update to an already-installed checkout and `composer.lock` does not yet contain PhpSpreadsheet, run:

```bash
composer update phpoffice/phpspreadsheet -W
```

3. Copy `.env.example` to `.env` and update the database credentials.
4. Create an empty MySQL database named `bantay_gamit`.
5. Initialize the schema:

```bash
php spark migrate
```

6. Load development/demo data only in a development environment:

```bash
php spark db:seed BantayGamitSeeder
```

The master seeder runs the dependency-ordered seeders for users, categories, locations, equipment, borrowing workflows, maintenance, historical report data, notifications, settings, and audit logs. Individual seeders can also be run directly, for example:

```bash
php spark db:seed UserSeeder
php spark db:seed EquipmentSeeder
php spark db:seed ReportDataSeeder
```

7. Start the local server:

```bash
php spark serve
```

Open `http://localhost:8080/`. The application timezone is configured as `Asia/Manila`.

## Environment

The provided `.env.example` uses:

```dotenv
CI_ENVIRONMENT=development
app.baseURL='http://localhost:8080/'
database.default.hostname=localhost
database.default.database=bantay_gamit
database.default.username=root
database.default.password=
database.default.DBDriver=MySQLi
database.default.DBPrefix=
database.default.port=3306
```

Never commit real production credentials.

## Development Accounts

These are intentional development seeder credentials and must not be used in production:

| Role | Username | Password |
| --- | --- | --- |
| Administrator | `admin` | `Admin@12345` |
| Barangay Official | `official` | `Official@12345` |
| Barangay Official | `official2` | `Official2@12345` |
| Borrower | `borrower` | `Borrower@12345` |
| Borrower | `juan` | `Borrower@12345` |
| Borrower | `ana` | `Borrower@12345` |
| Borrower | `mario` | `Borrower@12345` |

## Database

Schema creation is migration-only; no SQL dump is required. The primary migration creates:

- `users`
- `equipment_categories`
- `equipment_locations`
- `equipment`
- `borrow_requests`
- `borrow_request_items`
- `maintenance_records`
- `notifications`
- `audit_logs`
- `settings`

The migration `down()` method drops tables in reverse dependency order.

## Architecture

```text
app/
├── Application/Services/        Business workflows and reusable application logic
├── Config/                      Explicit routes and BantayGamit configuration
├── Controllers/                 Thin request/response controllers
├── Database/Migrations/         Full schema initialization
├── Database/Seeds/              Dependency-ordered development/demo seeders
├── Domain/Repositories/         Equipment repository contract
├── Filters/                     Authentication and role authorization
├── Helpers/                     Shared icon/label/date helpers
├── Infrastructure/Persistence/  MySQL equipment repository
├── Models/                      CodeIgniter persistence models
└── Views/                       Reusable server-rendered layouts and module views
public/assets/                   Local CSS, JavaScript, SVG icons, and branding
```

## Security Notes

- CSRF is enabled globally.
- Passwords use `password_hash(..., PASSWORD_DEFAULT)` and `password_verify(...)`.
- Session IDs regenerate after successful login.
- Authenticated pages use no-store/no-cache headers.
- State-changing operations use POST routes.
- Query Builder/Models are used for persistence.
- Role restrictions and borrower ownership checks are enforced server-side.
- User-facing errors do not expose database exception details.
- Critical borrowing operations use database transactions.
- Audit logs never store passwords.

## Tests

Run:

```bash
composer test
```

The included unit tests cover authentication behavior, valid/invalid request status transitions, overdue rules, and over-return prevention. Database-backed workflow tests can be added using CodeIgniter's `DatabaseTestTrait` when a dedicated test database is configured.
