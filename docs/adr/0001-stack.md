# ADR 0001: Stack: Laravel, MySQL, PHPUnit

- **Status:** Accepted
- **Track:** Working code

## Context

Pegel must deliver the core flow end to end in a short time: a consultant books hours, the server checks them, the budget updates, and the project lead sees the forecast. The product has a relational core (orders, accounting objects, bookings, assignments, vacations, public holidays), several roles, and business rules that need strong unit tests. The client is a large German company, so the stack should also be familiar to enterprise teams that will maintain it.

## Decision

- **Backend and web UI:** Laravel (PHP) with server-rendered views (Blade) and small amounts of JavaScript where needed.
- **Database:** MySQL, with migrations and seeders for demo data.
- **Tests:** PHPUnit for unit and feature tests.
- **Structure:** business rules sit in a framework-independent domain layer (see ADR 0003); controllers and Eloquent models stay thin.

## Alternatives considered

- **Node.js with Express and TypeScript:** good fit for a small API, but authentication, validation, migrations, policies and a test runner would all need to be assembled by hand. More setup time for the same result.
- **Single-page app with a separate API:** more moving parts, two deployments and a second place for rules to leak into. Not justified for the first stage.
- **PostgreSQL instead of MySQL:** equally capable here. MySQL was chosen because it is the default in the Laravel ecosystem and common in the client's hosting landscape.

## Consequences

- Authentication, authorization policies, validation, migrations and testing tools come built in, so time goes into the domain instead of plumbing.
- The domain layer must stay free of Laravel classes so it can be tested without a database and moved to another framework if needed.
- The team needs PHP and Laravel knowledge. A short setup guide in the README keeps onboarding quick.
