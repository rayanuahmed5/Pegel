# Pegel

For **client project leads** who learn too late that an external consulting budget will run out before project completion, **Pegel** is a **time booking and predictive budget forecast web application** that projects remaining person-days, factors in planned vacations and shutdowns, enforces German Working Time Act (_ArbZG_) compliance on the server, and alerts purchasing in time to place follow-up orders.

Read our complete [Product Vision](docs/product-vision.md).

---

## Prerequisites

| Tool         | Recommended Version                  | Check Command        |
| ------------ | ------------------------------------ | -------------------- |
| **PHP**      | 8.4.1 or newer                       | `php -v`             |
| **Composer** | 2.x                                  | `composer --version` |
| **Node.js**  | 20.x or 22.x                         | `node -v`            |
| **MySQL**    | 8.0+ or 8.4+ (e.g. Laragon / Docker) | `mysql --version`    |

---

## Setup

Run the following commands in order from a fresh clone:

Start Laragon's MySQL service and create an empty `pegel` database. If you use a
different database name or port, update the `DB_*` values in `.env` after copying
the example file.

```bash
Copy-Item .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
```

---

## Run

Start the local application server:

```bash
php artisan serve
```

In a second terminal, start the frontend development server:

```bash
npm run dev
```

Open [http://localhost:8000](http://localhost:8000) in your browser. The demo role switcher is enabled only in local and test environments.

---

## Test

Run the complete test suite, including budget, ArbZG, authorization, authentication, and forecast coverage:

```bash
php artisan test --compact
```

---

## Demo Users

The database seeder pre-populates the challenge scenario with 4 role-specific demo users and a superadmin account (all use password: `password`):

| Role                    | Sign In                 | What to Try / Key Features                                                                                               |
| ----------------------- | ----------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| **External Consultant** | `consultant@pegel.test` | View assigned order `4500012345`, book daily hours with ArbZG validation, register planned vacation.                     |
| **Client Project Lead** | `lead@pegel.test`       | View Customer portal migration order (140 PD total, 84 PD used), inspect burn-rate traffic light, approve Proof of Work. |
| **Client Purchasing**   | `purchasing@pegel.test` | View aggregated person-days per order, monitor framework limits, receive 4-week re-order warning alert.                  |
| **Client Finance**      | `finance@pegel.test`    | Audit monthly approved timesheets against supplier invoices with automated variance checks.                              |
| **Superadmin**          | `superadmin` (username) | View all role dashboards and approve or reject Proof of Work. Cannot book time on behalf of a consultant.                |

---

## Evidence Map

Where the technical juror finds direct evidence for each scoring criterion in [scoring.md](docs/scoring.md):

| Criterion                              |     Target Level      | Evidence Location in Repository                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| -------------------------------------- | :-------------------: | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **1 · Persistence and data model**     | **Level 2 (2/2 pts)** | • Database Migrations: [`database/migrations/`](database/migrations/)<br>• Eloquent Models: [`app/Models/`](app/Models/) (`PurchaseOrder`, `Consultant`, `Supplier`, `TimeBooking`, `PlannedAbsence`, `CostCentre`)<br>• ER Diagram & Glossary: [`docs/domain.md`](docs/domain.md)                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| **2 · Business rules and unit tests**  | **Level 3 (3/3 pts)** | • Dedicated Domain Services: [`app/Domain/Services/BookingService.php`](app/Domain/Services/BookingService.php), [`app/Domain/Services/ArbzgValidationService.php`](app/Domain/Services/ArbzgValidationService.php), and [`app/Domain/Services/ForecastService.php`](app/Domain/Services/ForecastService.php)<br>• Domain Logic & Budget Guardrail: [`app/Models/PurchaseOrder.php`](app/Models/PurchaseOrder.php)<br>• Unit & Feature Tests: [`tests/Unit/OrderBudgetRuleTest.php`](tests/Unit/OrderBudgetRuleTest.php), [`tests/Unit/ArbzgRuleTest.php`](tests/Unit/ArbzgRuleTest.php), [`tests/Unit/ForecastServiceTest.php`](tests/Unit/ForecastServiceTest.php), and [`tests/Feature/BookingFlowTest.php`](tests/Feature/BookingFlowTest.php) |
| **3 · Roles, permissions and privacy** | **Level 2 (2/2 pts)** | • Permission Concept & Role Matrix: [`docs/roles-and-privacy.md`](docs/roles-and-privacy.md)<br>• GDPR Personal Data Minimisation Breakdown: [`docs/roles-and-privacy.md`](docs/roles-and-privacy.md)<br>• Server-Side Role Model: [`database/migrations/2026_10_30_163625_add_role_to_users_table.php`](database/migrations/2026_10_30_163625_add_role_to_users_table.php) & [`app/Models/User.php`](app/Models/User.php)<br>• Interactive Multi-Role Dashboards: [`resources/js/pages/dashboard.tsx`](resources/js/pages/dashboard.tsx) & [`resources/js/pages/welcome.tsx`](resources/js/pages/welcome.tsx)                                                                                                                                     |
| **4 · Architecture and engineering**   | **Level 2 (2/2 pts)** | • **Block A (Structure):** Modular separation of Domain ([`app/Domain/`](app/Domain/)), Persistence Entities ([`app/Models/`](app/Models/)), and HTTP Request/DTOs ([`app/Http/Requests/`](app/Http/Requests/))<br>• **Block B (ADRs):** ≥3 ADRs in [`docs/adr/`](docs/adr/) including [`0001-stack.md`](docs/adr/0001-stack.md), [`0005-inertia-react-and-pest.md`](docs/adr/0005-inertia-react-and-pest.md) (UI and tests), [`0002-identity.md`](docs/adr/0002-identity.md) (Identity/SSO), and [`0003-operation.md`](docs/adr/0003-operation.md) (Cloud/On-Premise Scalability)<br>• **Block C (Git history):** Regular commits tracking feature progression                                                                                    |
| **5 · Runs from the README**           | **Level 1 (1/1 pt)**  | Exact commands in Prerequisites, Setup, Run, Test, and Demo users sections above. Pre-seeded with the challenge dataset (Customer portal migration: 140 PD, 84 PD used).                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |

---

## Team

- **Product:** Pegel
- **Track:** Working Code Track (Target: 10/10 technical score)
