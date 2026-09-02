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
- Operational reports with printer-friendly output and CSV export
- Responsive Berry-inspired header, sidebar/drawer, cards, tables, forms, chips, alerts, and confirmation dialog

## Requirements

- PHP 8.2 or newer with extensions required by CodeIgniter 4
- Composer 2
- MySQL 8+ or compatible MySQL/MariaDB server
- A web server whose production document root points to `public/`

## Installation

1. Create the project at `~/Documents/Projects/BantayGamit` using a CodeIgniter 4.7+ appstarter/TallyTech-compatible base.
2. Place the BantayGamit modified files in that project root, preserving their paths.
3. Run:

```bash
composer install
```

4. Copy `.env.example` to `.env` and update the database credentials.
5. Create an empty MySQL database named `bantay_gamit`.
6. Initialize the schema:

```bash
php spark migrate
```

7. Load development/demo data only in a development environment:

```bash
php spark db:seed BantayGamitSeeder
```

8. Start the local server:

```bash
php spark serve
```

Open `http://localhost:8080/`.

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
| Borrower | `borrower` | `Borrower@12345` |

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
├── Database/Seeds/              Development demonstration data
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
# BantayGamit
