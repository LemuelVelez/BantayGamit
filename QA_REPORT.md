# BantayGamit QA Report

Date: 2026-09-08
Scope: full-code QA review plus Section 12 native date/number control implementation.

## 1. Summary

**Go / no-go: NO-GO for production.** The supplied checkout has multiple High-severity workflow/deployment/reporting defects and could not be exercised end-to-end because the execution host has no Composer, no `vendor/`, no MySQL client/server, no Docker CLI, and lacks the PHP extensions required by the project. I therefore did not claim runtime passes for login, database migrations/seeders, concurrency, XLSX generation, or Docker boot.

Checks actually executed on the supplied files:

| Check | Result |
|---|---:|
| PHP syntax lint (`app/` + `tests/`) | 141 / 141 pass |
| POST form CSRF-field static check | 26 / 26 pass |
| Native-control Chromium viewport assertions (360, 768, 1440), focus, and Y-m-d value | 5 / 5 pass |
| **Automated checks total** | **172 pass / 0 fail** |

Additional static tracing covered all 51 explicit routes, all 16 controllers, 8 application services, 34 views, both migrations, the seeder/CLI code, repository queries, Docker/config files, and the requested security/data-integrity paths. That review found **15 defects: 5 High, 8 Medium, 2 Low; 0 Critical statically confirmed**. The absence of a runtime database means race-condition/rollback findings are code-traced defects, not load-test reproductions.

### Overall health

Strong points: explicit routing with auto-routing disabled, global CSRF, role filters that re-read the user row, borrower ownership checks, bound/Query-Builder database access, output escaping in the main HTML views, transaction use around the stock-sensitive create/approve/release paths, deterministic equipment lock ordering, and a correct static availability formula.

Release blockers: dependency lock inconsistency, non-atomic request/maintenance state transitions under concurrency, last-admin invariant race, and an incorrect Borrowing History report filter. Runtime verification of the remaining checklist is mandatory after the environment is fixed.

## 2. Defects

| ID | Severity | Module | Title | Steps to reproduce / trace | Expected | Actual | Location |
|---|---|---|---|---|---|---|---|
| BG-QA-001 | High | Setup / XLSX | `composer.lock` is inconsistent with the declared PhpSpreadsheet requirement | Fresh checkout; inspect `composer.json`, then run `composer install`. | Lock file includes every root production requirement and a clean install is reproducible. | `composer.json` requires `phpoffice/phpspreadsheet:^5.9`, but the lock contains only CodeIgniter, Laminas Escaper, and PSR Log. This host had no Composer, so the command itself was blocked, but the lock inconsistency is directly present. README's recovery note is framed for an already-installed checkout, after the initial `composer install` step. | `composer.json:6-10`; `composer.lock:8-12`; `README.md:46-59` |
| BG-QA-002 | High | Borrowing | Request transitions race because request status is read before the transaction and never row-locked/conditionally updated | Two actors load the same pending request; concurrently approve vs reject, or borrower cancel vs staff approve. | Exactly one valid transition wins; the loser sees the new state and rolls back without audit/notification side effects. | `requireRequest()` and transition validation happen before the transaction. Only equipment rows are locked. The request update is by ID with no `WHERE status = old_status`. Two callers can both act on stale `pending` state, producing contradictory audit/notifications and a final status determined by write order. | `app/Application/Services/BorrowingService.php:49-68,55-62,115-118` |
| BG-QA-003 | High | Maintenance | Terminal maintenance state can be overwritten by a concurrent stale transition | Start from `in_progress`; concurrently submit `completed` and `scheduled`. | Once completed, the record is terminal and cannot be reopened. | `updateStatus()` reads the row and validates before an unguarded update, with no transaction/row lock/compare-and-swap. Both operations can validate against stale `in_progress`; a later `scheduled` write can overwrite `completed`. | `app/Application/Services/MaintenanceService.php:7-9,22` |
| BG-QA-004 | High | Users | Last-active-admin protection is vulnerable to a check-then-update race | With exactly two active admins A and B, have A demote/deactivate B while B demotes/deactivates A concurrently. | The database must retain at least one active admin. | Both requests can observe `activeAdminCount() == 2` before either update and both proceed, leaving zero active admins. No transaction or user/admin lock protects the invariant. | `app/Controllers/UsersController.php:7-9` |
| BG-QA-005 | High | Reports | Borrowing History report includes non-history states | Compare borrower history behavior with `GET /reports?type=history`; include pending/approved/released records. | “Borrowing History” should match the application's history definition: returned, rejected, cancelled. | Borrower history explicitly filters to `returned/rejected/cancelled`, but `ReportingService` applies no type-specific condition for `history`, so all request states are returned unless an optional status filter is supplied. | `app/Controllers/BorrowingsController.php:5`; `app/Application/Services/ReportingService.php:16-18`; `app/Views/reports/index.php:1` |
| BG-QA-006 | Medium | Borrowing | Tampered release/return condition silently becomes `good` | POST a condition key with a value outside `Config\BantayGamit::$conditions`. | Reject the request as invalid and leave data unchanged. | Both release and return paths silently replace an unknown value with `good`, which can falsify inspection data rather than surfacing invalid input. | `app/Application/Services/BorrowingService.php:71-84`; allowed values `app/Config/BantayGamit.php:20-22` |
| BG-QA-007 | Medium | Notifications / Overdue | Overdue status and overdue notification are not atomic | Cause notification insertion to fail after an overdue status update. | Either status+notification both commit or neither does. | `refreshOverdue()` updates the request first, then inserts the notification outside a transaction. If notification insertion fails, the request stays `overdue` and will not be selected by the `status = released` query on the next refresh, permanently losing the overdue notification. | `app/Application/Services/BorrowingService.php:87-98` |
| BG-QA-008 | Medium | Notifications / Due soon | Due-soon de-duplication is raceable | Hit two GET pages that call `refreshOverdue()` concurrently for the same due-soon request. | At most one reminder per exact request/message. | The code performs `countAllResults()` and then a separate insert. There is no transaction/lock or unique key covering `(user_id,type,message)`, so concurrent refreshes can both observe zero and insert duplicates. | `app/Application/Services/BorrowingService.php:100-109`; `app/Database/Migrations/2026-09-03-000001_CreateBantayGamitSchema.php:88-92` |
| BG-QA-009 | Medium | Reports | Report date filters accept invalid and reversed values without validation | Request `/reports?from=not-a-date&to=...` or `from > to`. | Reject/normalize invalid ranges with a clear validation message. | `ReportsController::input()` copies raw strings; `ReportingService` concatenates time suffixes and sends them to comparisons. There is no date-format or range validation, so behavior becomes database-coercion/empty-result dependent and can mislead users. Runtime DB behavior was blocked on this host. | `app/Controllers/ReportsController.php:10-24`; `app/Application/Services/ReportingService.php:12-18` |
| BG-QA-010 | Medium | XLSX / Security | User-controlled strings are not protected from spreadsheet formula interpretation | Put a leading `=` formula-like value in a user-controlled exported field such as purpose/equipment name, then export XLSX and open it. | Exported user data must be written explicitly as text, not executable spreadsheet formulas. | `normalizeValue()` returns arbitrary strings unchanged and `fromArray()` is used without explicit string binding or a leading-formula neutralization step. Runtime XLSX confirmation was blocked because the required package is absent from the lock/environment. | `app/Application/Services/XlsxReportService.php:94-103,195-207` |
| BG-QA-011 | Medium | Authentication | No login throttling / brute-force protection | Repeatedly POST valid username + wrong passwords. | Rate-limit, delay, or lock repeated failed attempts by account/client according to policy. | Login route goes directly to validation/authentication; no throttle filter/service/cache counter is configured. | `app/Config/Routes.php:4`; `app/Controllers/AuthController.php:5-7`; `app/Config/Filters.php:5-7` |
| BG-QA-012 | Medium | Setup / Documentation | README's `.env.example` description contradicts the actual file | Follow README installation literally and copy `.env.example`. | Example values described in README match the file and point local setup to localhost unless the user is explicitly told to change them. | README states local `app.baseURL` and `database.default.*` values, while the actual example sets a production URL and `DB_HOST=mysql-service-host`. The instructions say “update database credentials” but do not call out changing the production baseURL before `php spark serve`. | `README.md:61-91,93-107`; `.env.example:1-14` |
| BG-QA-013 | Medium | Audit / Data integrity | Several mutations commit before their audit record and are not transactional with audit logging | Force an audit insert failure during equipment create, user update/create, profile update, category/location mutation. | Business mutation and required audit record commit or roll back together. | Multiple code paths mutate under autocommit and then call `AuditService::log()`. An audit failure can make the controller report failure while the business row has already changed and no audit row exists. | `app/Application/Services/EquipmentService.php:9-13`; `app/Controllers/UsersController.php:6-8`; `app/Controllers/ProfileController.php:6`; `app/Controllers/EquipmentCategoriesController.php:8-9`; `app/Controllers/EquipmentLocationsController.php:7-8` |
| BG-QA-014 | Low | Authentication | Login password policy is inconsistent with create/update policy | Compare validation groups or attempt a legacy/imported account with a password shorter than six characters. | One documented password policy, or a deliberate compatibility rule. | Login requires minimum 6 while create/update/profile require minimum 8. Current seeded accounts are longer, so this does not lock out the provided accounts; it is a policy/legacy compatibility inconsistency. | `app/Config/Validation.php:25-28,56-75,99-105` |
| BG-QA-015 | Low | Reports / Performance | Available report performs N+1 equipment ID lookups | Generate `type=available` over N rows. | Resolve IDs in the original query or calculate availability in one set-based query. | The report fetches rows, then runs one equipment lookup per row before `availableQuantity()`, which itself runs additional queries. Asset-code uniqueness keeps the lookup logically correct under the schema, but cost grows with report size. | `app/Application/Services/ReportingService.php:6-10`; unique asset code in `app/Database/Migrations/2026-09-03-000001_CreateBantayGamitSchema.php:45-49` |

## 3. Security findings

### Authorization

Static result: **pass with runtime verification still required**. Routes are explicitly grouped under `auth`, `role:borrower`, `role:admin,barangay_official`, and `role:admin` (`app/Config/Routes.php:5-27`); auto-routing is disabled (`app/Config/Routing.php:90-97`). `RoleFilter` re-reads status/role from the database and denies with a dashboard redirect (`app/Filters/RoleFilter.php:13-34`). The borrower detail IDOR check exists (`app/Controllers/BorrowRequestsController.php:7`), and cancellation re-checks ownership in the service (`app/Application/Services/BorrowingService.php:49-52`). `assertStaffActor()` is also present for approve/reject/release/return (`BorrowingService.php:57,67,73,83,118`).

Blocked: signing in as all three roles and executing each restricted route could not be performed without the application runtime/database.

### CSRF

Static result: **pass**. CSRF is a global `before` filter (`app/Config/Filters.php:7`) with token regeneration enabled (`app/Config/Security.php:69-85`). The automated view scan found **26/26 POST forms include `csrf_field()`**. Runtime forged/missing-token submissions remain blocked.

### XSS

Static result: **HTML paths pass; XLSX formula-injection gap is BG-QA-010**. User-controlled values in the reviewed list/detail/report/print views are rendered with `esc()`; print report cells also escape values (`app/Views/reports/print.php:1`). No direct raw echo of the requested payload fields was found in the audited views. Runtime browser payload execution was blocked.

### SQL injection

Static result: **pass**. Search/filter code uses CodeIgniter Query Builder `where/like/orLike`; the only raw lock query uses a parameter placeholder with a bound integer (`app/Infrastructure/Persistence/MySqlEquipmentRepository.php:52-57`). No request value was found interpolated into raw SQL.

### IDOR

Static result: **pass by trace** for the explicitly requested request-detail/cancel cases (`BorrowRequestsController.php:7-8`; `BorrowingService.php:49-52`). Runtime cross-account tests are blocked.

### Session handling

Static result: **pass for requested controls**. Active status is checked during authentication (`app/Application/Services/AuthService.php:6-9`); `AuthFilter` re-reads the user each request, destroys invalid/inactive sessions, refreshes role/display/unread count, and sets no-store/no-cache headers (`app/Filters/AuthFilter.php:8-9`). Login regenerates session ID and logout destroys it (`app/Controllers/AuthController.php:6-7`). Cookies are HttpOnly, SameSite=Lax, and Secure in production (`app/Config/Cookie.php:57-66,84-90`). The missing brute-force control is BG-QA-011.

### Secrets / diagnostic exposure

No committed `.env` was present; `.gitignore` excludes environment files and runtime writable data. The supplied source archive nevertheless contained ignored development log/session artifacts. The current session files did not contain authenticated user keys; the log does expose a local development filesystem path and DB connection stack trace. `.dockerignore` excludes `writable/*` and `*.log`, so those artifacts are not copied by the Docker build (`.dockerignore:16-17`). Treat release archives as source distributions and strip ignored runtime artifacts before sharing.

## 4. Data-integrity findings

- **Availability formula — static pass.** `availableQuantity()` returns zero for unavailable/maintenance/retired equipment, subtracts approved requested quantities, released/overdue outstanding quantities, and open maintenance, and clamps at zero (`app/Infrastructure/Persistence/MySqlEquipmentRepository.php:76-93`). `activeAllocatedQuantity()` uses the same allocation categories for total-quantity reduction checks (`app/Application/Services/EquipmentService.php:57-61`). Runtime hand-calculation/concurrency confirmation is blocked.
- **Oversell protection — partially strong, runtime blocked.** Request creation and approval/release sort equipment IDs and lock equipment rows before availability checks (`BorrowingService.php:35-39,57-60,73-76`). That should serialize stock allocation. The separate request-state race in BG-QA-002 still allows inconsistent workflow side effects even if inventory oversell is prevented.
- **Transactions/rollback — gaps found.** BG-QA-007 and BG-QA-013 identify non-atomic notification/audit paths. Borrow create/approve/reject/release/return and maintenance create do use DB transactions for their main data paths.
- **Maintenance availability — static pass.** Active maintenance statuses are subtracted; completed/cancelled are excluded, so completion/cancellation releases quantity back to the pool (`MySqlEquipmentRepository.php:89-92`; `MaintenanceService.php:22`).
- **Retire/restore — static pass.** Retirement is idempotent, blocked by pending/active requests or open maintenance, and restore sets `unavailable` (`EquipmentService.php:27-42,63-67`).
- **Partial returns — design gap, not counted as a defect without a product requirement.** `validateReturnQuantities()` supports arbitrary quantities, but the only service entry point `returnAll()` always computes and writes the full released amount (`BorrowingService.php:24,81-84`). The current UI explicitly says all quantities will be returned. If partial return is required, this is missing functionality rather than a currently broken path.
- **Zero quantity / future acquisition date — policy decisions.** `total_quantity=0` is intentionally accepted by `is_natural` and the service only rejects negatives (`Validation.php:35`; `EquipmentService.php:44-48`). `acquired_date` validates format only and does not reject a future date (`Validation.php:39`). The QA request did not state the expected policy, so these are documented rather than reported as defects.
- **Foreign keys / orphaning — static pass.** The schema uses RESTRICT for referenced categories/locations/equipment and SET NULL/CASCADE where history preservation is intended; `down()` drops in reverse dependency order (`2026-09-03-000001_CreateBantayGamitSchema.php:108-113`). Runtime DELETE attempts are blocked.
- **GET writes — architectural risk confirmed.** Dashboard/borrowing/report GETs call `refreshOverdue()`, causing status and notification writes during reads. The compare-and-set on overdue status prevents duplicate overdue transitions, but the due-soon race in BG-QA-008 remains. Consider moving scheduled state maintenance/reminders to a command/cron/queue to preserve GET idempotency and simplify concurrency.

## 5. Source-traced requested behaviors that look correct

- Inactive accounts are rejected by `AuthService` (`AuthService.php:6-9`), and mid-session deactivation is enforced by both auth/role filters.
- `GET /logout` has no route and auto-routing is disabled; logout is POST-only (`Routes.php:4`; `Routing.php:97`).
- Borrower equipment status is forcibly `available`, and borrower detail access to non-available equipment throws 404 (`EquipmentController.php:4-5`).
- Duplicate request item rows are merged before availability validation (`BorrowingService.php:32-39`).
- Runtime request numbers use `BR-YYYY-%06d` and the DB has a unique request number key (`BorrowingService.php:40-42`; migration `:61`).
- Staff cannot approve/reject/release/receive a request where they are also the borrower (`BorrowingService.php:118`).
- Inactive categories/locations are rejected during equipment create/update (`EquipmentService.php:50-55`). Existing equipment is joined without category/location status filtering, so deactivation does not hide historical equipment.
- Reducing total quantity below active allocation is rejected; equality is allowed (`EquipmentService.php:21,57-61`).
- Maintenance creation rejects quantity <1, unsupported starting states, supplied completion date, negative cost, and quantity above current availability (`MaintenanceService.php:10-20`).
- Settings enforce `due_soon_days` 0-30 and save settings + audit in one transaction (`Validation.php:107-110`; `SettingsController.php:6`).
- Notification mark-read scopes by both notification ID and user ID; mark-all avoids a spurious audit when unread count is zero; messages use `mb_substr(...,500)` (`NotificationService.php:5-11`).
- Unknown report type falls back to inventory (`ReportsController.php:12-15`).
- Main report and print views escape displayed cell values (`reports/index.php:1`; `reports/print.php:1`).
- The migration marker's filename/class mismatch is not reported as an application defect: the locked CodeIgniter 4.7.x migration runner discovers the actual class in the file. Runtime migrate/status/rollback remains blocked.
- Seeder dependency failures are designed to be explicit: `idBy()` throws `RuntimeException` if prerequisites are absent (`SeedSupport.php:51-58`). Idempotent upsert stats and “No pending seed data” output are implemented (`SeedSupport.php:27-49`; `SeedCommand.php:102-111`). Console styling disables rich output for non-TTY, `NO_COLOR`, or `--no-ansi` (`ConsoleStyle.php:12-20`).

## 6. Untested or blocked areas

The following requested tests could not be honestly executed in this environment:

- `composer install` and `composer test`: Composer is not installed and `vendor/` is absent. `php spark` fails immediately because `vendor/codeigniter4/framework/system/Boot.php` is missing.
- Database-backed behavior: no MySQL client/server is available, and the host PHP CLI lacks the project's required `intl`, `mbstring`, `mysqli`, `gd`, and `zip` extensions.
- Full authentication/session/RBAC/IDOR/CSRF runtime matrix, all CRUD workflows, migrations/refresh/rollback, seed-twice tests, DB FK DELETE behavior, availability hand calculations, and database concurrency/load tests.
- XLSX generation/opening in Excel or LibreOffice, including style/format/filename and formula-injection confirmation.
- Docker build/run and boot-failure behavior: no Docker CLI is available. Static Dockerfile review confirms production environment, port 3000, writable ownership, and migration-at-boot command (`Dockerfile:45-62`). A migration failure makes the shell command exit before Apache starts; under a restart policy this can become a crash/restart loop.
- Full responsive application UI, JavaScript interactions, and keyboard navigation against live server-rendered pages.
- Browser coverage: only **Chromium 144 headless** was available. Chrome/Edge binaries, Firefox, and Safari/WebKit were not available, so those were not claimed as tested.

## 7. Prioritized fix list before production

1. Fix `composer.lock` so a clean `composer install` contains PhpSpreadsheet; commit the regenerated lock and make the Docker vendor stage copy/use it for reproducible builds.
2. Make borrow-request state transitions atomic: lock the request row (or conditional `UPDATE ... WHERE status = ?` and verify affected rows) inside the transaction before side effects; keep equipment locks in deterministic order.
3. Apply the same compare-and-set/row-lock approach to maintenance transitions.
4. Enforce the active-admin invariant transactionally/with row or advisory locking so concurrent admin changes cannot leave zero active admins.
5. Make the `history` report use the same terminal states as borrower history unless a different product definition is explicitly intended.
6. Validate report date formats/ranges and reject invalid/reversed filters.
7. Reject unknown release/return conditions instead of coercing them to `good`.
8. Make overdue state + notification atomic and make due-soon de-duplication concurrency-safe (unique reminder key or transaction/locking).
9. Bind exported user strings explicitly as text / neutralize leading spreadsheet formula characters.
10. Add login throttling.
11. Wrap required audit logging with the mutations it documents, or define a reliable outbox/audit failure policy.
12. Correct README/.env example consistency; then run the entire blocked runtime matrix and the existing test suite before release.

## 8. Date picker / number input restyle implementation

Modified file: `public/assets/css/app.css:91-211` only. No view markup or JavaScript date-picker library was added.

### What changed

- Unified date/number control height, padding, border, radius, background, font, and toolbar sizing with the Berry-inspired form controls.
- Added hover, primary focus ring, restored a visible `:focus-visible` outline for these controls, error tint for `aria-invalid="true"`, and disabled/readonly behavior.
- Styled Chromium/WebKit date edit segments and focused month/day/year segment highlighting.
- Styled the calendar-picker indicator with theme-token background, tint filter, pointer cursor, and hover/active transitions.
- Polished number spinner opacity/interaction and removed Firefox number textfield decoration behavior.
- Mobile inputs become 44px tall at <=620px; report-toolbar date/number controls are 170px desktop and full-width on mobile.
- New block introduces **no hard-coded hex colours**; state colours are token-based, with `color-mix()` used for soft rings/tints.

### Verification performed

Chromium 144 static harness using the final stylesheet:

| Viewport | Result |
|---|---|
| 360px | 44px controls, full-width toolbar controls, no horizontal overflow |
| 768px | 41px controls, 170px toolbar date/number controls, wrapping without overflow |
| 1440px | 41px controls, aligned toolbar sizing, no horizontal overflow |

Keyboard focus produced a visible 3px focus-visible outline plus primary border/ring; editing retained the native date value `2026-09-19` in **Y-m-d** format. Invalid controls use the error token border/tint, and disabled/readonly controls render at `.55` opacity with `not-allowed` cursor. The reports print view is self-contained inline CSS and does not load `app.css`, so it is unaffected (`app/Views/reports/print.php:1`).

### Browser-native limitation

The popup calendar grid/panel itself is rendered by the browser/operating system and **cannot be fully restyled with CSS**. Chromium exposes the calendar indicator and date-edit pseudo-elements used here; Firefox and Safari expose less. The field, editable segments, indicator, focus/error states, and dimensions can be themed, but the calendar popup's grid, header, navigation controls, fonts, and internal selection styling remain browser-native.

If pixel-identical calendar panels across browsers are a hard requirement, a custom accessible date-picker component is required. That adds JavaScript/state management, date parsing/formatting, keyboard semantics, focus trapping/return, localization, mobile behavior, validation integration, accessibility testing, and a larger maintenance/browser test surface. That was intentionally not introduced here.

### Browser coverage note

Tested: Chromium 144 headless (representative engine for Chrome/Edge behavior, but **not** a claim that the actual Chrome/Edge binaries were tested). Not available: Firefox, Safari/WebKit. Actual cross-browser manual verification is still required.

### CSS diff

```diff
--- /mnt/data/original-app.css	2026-09-07 22:25:52.995546595 +0000
+++ public/assets/css/app.css	2026-09-07 22:24:33.075857373 +0000
@@ -87,3 +87,125 @@
 @media(max-width:620px){.topbar{padding-left:max(12px,env(safe-area-inset-left));padding-right:max(12px,env(safe-area-inset-right));gap:8px}.topbar-logo{gap:8px}.menu-toggle{margin-left:0}.toolbar .custom-select{min-width:0;flex:1 1 170px}.btn{padding-inline:13px}}
 @media(max-width:480px){.topbar-logo{max-width:none;flex:0 0 auto}.brand{gap:0}.brand img{width:38px;height:38px;flex-basis:38px}.menu-toggle{width:40px;height:40px;min-width:40px}.notification-button{margin-right:2px}.custom-select-menu{max-width:calc(100vw - 20px)}}
 @media(max-width:360px){.topbar{gap:6px}.brand img{width:36px;height:36px;flex-basis:36px}.menu-toggle,.icon-button{width:40px;height:40px;min-width:40px}}
+
+/* QA implementation: native date and number control polish */
+input[type="date"],
+input[type="number"]{
+  width:100%;
+  min-height:41px;
+  height:41px;
+  padding:9px 11px;
+  border:1px solid var(--grey-200);
+  border-radius:var(--radius);
+  background:var(--paper);
+  color:var(--grey-700);
+  font:inherit;
+  line-height:1.35;
+  box-shadow:none;
+  transition:border-color .18s ease,box-shadow .18s ease,background-color .18s ease,color .18s ease,opacity .18s ease;
+}
+input[type="date"]{padding-right:10px;color-scheme:light}
+input[type="date"]:hover:not(:disabled):not([readonly]),
+input[type="number"]:hover:not(:disabled):not([readonly]){border-color:var(--grey-300)}
+input[type="date"]:focus,
+input[type="number"]:focus{
+  border-color:var(--primary);
+  box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 14%,transparent);
+}
+input[type="date"]:focus-visible,
+input[type="number"]:focus-visible{
+  outline:3px solid color-mix(in srgb,var(--secondary) 42%,var(--paper));
+  outline-offset:2px;
+}
+input[type="date"][aria-invalid="true"],
+input[type="number"][aria-invalid="true"]{
+  border-color:var(--error);
+  background:color-mix(in srgb,var(--error-light) 48%,var(--paper));
+  box-shadow:0 0 0 3px color-mix(in srgb,var(--error) 10%,transparent);
+}
+input[type="date"]:disabled,
+input[type="date"][readonly],
+input[type="number"]:disabled,
+input[type="number"][readonly]{
+  background:var(--grey-100);
+  color:var(--grey-500);
+  opacity:.55;
+  cursor:not-allowed;
+}
+
+input[type="date"]::-webkit-calendar-picker-indicator{
+  width:18px;
+  height:18px;
+  margin:0;
+  padding:5px;
+  border-radius:999px;
+  background-color:var(--primary-light);
+  cursor:pointer;
+  opacity:.78;
+  filter:invert(46%) sepia(94%) saturate(2010%) hue-rotate(184deg) brightness(96%) contrast(88%);
+  transition:background-color .18s ease,opacity .18s ease,transform .18s ease;
+}
+input[type="date"]::-webkit-calendar-picker-indicator:hover{
+  background-color:var(--secondary-light);
+  opacity:1;
+  transform:scale(1.06);
+}
+input[type="date"]::-webkit-calendar-picker-indicator:active{opacity:.88;transform:scale(.96)}
+input[type="date"]:disabled::-webkit-calendar-picker-indicator,
+input[type="date"][readonly]::-webkit-calendar-picker-indicator{cursor:not-allowed;opacity:.45}
+input[type="date"]::-webkit-datetime-edit{padding:0;color:var(--grey-500)}
+input[type="date"]::-webkit-datetime-edit-fields-wrapper{padding:0}
+input[type="date"]::-webkit-datetime-edit-text{color:var(--grey-500);padding:0 2px}
+input[type="date"]::-webkit-datetime-edit-month-field,
+input[type="date"]::-webkit-datetime-edit-day-field,
+input[type="date"]::-webkit-datetime-edit-year-field{
+  color:var(--grey-700);
+  border-radius:4px;
+  padding:1px 2px;
+}
+input[type="date"]::-webkit-datetime-edit-month-field:focus,
+input[type="date"]::-webkit-datetime-edit-day-field:focus,
+input[type="date"]::-webkit-datetime-edit-year-field:focus{
+  background:var(--primary-light);
+  color:var(--primary-dark);
+}
+input[type="date"]:invalid::-webkit-datetime-edit,
+input[type="date"][value=""]::-webkit-datetime-edit{color:var(--grey-500)}
+input[type="date"]:invalid::-webkit-datetime-edit-month-field,
+input[type="date"]:invalid::-webkit-datetime-edit-day-field,
+input[type="date"]:invalid::-webkit-datetime-edit-year-field,
+input[type="date"][value=""]::-webkit-datetime-edit-month-field,
+input[type="date"][value=""]::-webkit-datetime-edit-day-field,
+input[type="date"][value=""]::-webkit-datetime-edit-year-field{color:var(--grey-500)}
+input[type="date"]:focus::-webkit-datetime-edit-month-field,
+input[type="date"]:focus::-webkit-datetime-edit-day-field,
+input[type="date"]:focus::-webkit-datetime-edit-year-field{color:var(--grey-700)}
+
+input[type="number"]{-moz-appearance:textfield}
+input[type="number"]::-webkit-inner-spin-button,
+input[type="number"]::-webkit-outer-spin-button{
+  margin:0;
+  opacity:.6;
+  cursor:pointer;
+  transition:opacity .18s ease,transform .18s ease;
+}
+input[type="number"]::-webkit-inner-spin-button:hover,
+input[type="number"]::-webkit-outer-spin-button:hover{opacity:1;transform:scale(1.04)}
+input[type="number"]:disabled::-webkit-inner-spin-button,
+input[type="number"]:disabled::-webkit-outer-spin-button,
+input[type="number"][readonly]::-webkit-inner-spin-button,
+input[type="number"][readonly]::-webkit-outer-spin-button{cursor:not-allowed;opacity:.35}
+input[type="date"]::-moz-focus-inner,
+input[type="number"]::-moz-focus-inner{border:0;padding:0}
+
+.toolbar input[type="date"],
+.toolbar input[type="number"]{min-height:41px;height:41px}
+@media(max-width:620px){
+  input[type="date"],input[type="number"],
+  .toolbar input[type="date"],.toolbar input[type="number"]{min-height:44px;height:44px}
+}
+.toolbar input[type="date"],
+.toolbar input[type="number"]{width:auto;flex:0 1 170px;min-width:150px}
+@media(max-width:620px){
+  .toolbar input[type="date"],.toolbar input[type="number"]{width:100%;flex:1 1 100%;min-width:0}
+}

```

## 9. Test-suite coverage assessment

Existing unit tests define 9 tests total: 3 AuthService tests, 4 borrowing-rule tests, and 2 maintenance-rule tests. They cover authentication success/wrong-password/inactive-user plus pure transition/overdue/return-quantity rules and maintenance transition rules. Database-backed availability math, transaction rollback, concurrency, permission filters/routes, controller validation, notifications, reports, XLSX, migration/seeder behavior, and the UI are not covered. `composer test` could not be run because Composer/vendor are unavailable on this host.
