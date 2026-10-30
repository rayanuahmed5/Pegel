# ADR 0004: Demo Superadmin Username Login

- **Status:** Accepted
- **Date:** 2026-10-30
- **Deciders:** Pegel team

## Context

The demo needs a default superadmin account that can sign in as `superadmin`. Existing demo accounts use email-based login. The demo must remain local and not depend on a customer identity provider.

## Options considered

1. **Use an email-only superadmin account** – Minimal change, but does not support the requested username login.
2. **Support username or email login** – Allows the requested username while preserving existing demo logins; requires a nullable unique username field and a custom Fortify authentication lookup.
3. **Replace demo credentials with SSO** – Closer to production identity, but needs external identity-provider configuration and prevents a self-contained demo.

## Decision

We choose **username or email login** and seed `superadmin` with password `password`. The superadmin can view all role dashboards and approve or reject bookings, but cannot create time bookings on behalf of consultants.

## Consequences

Existing email logins continue to work, and the admin account is available after database seeding. The demo credential is intentionally predictable and must not be reused outside local evaluation.
