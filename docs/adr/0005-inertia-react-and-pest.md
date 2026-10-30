# ADR 0005: Inertia React UI and Pest Tests

- **Status:** Accepted
- **Date:** 2026-10-30
- **Deciders:** Pegel team

## Context

The original stack record describes Blade views and PHPUnit, but the application now uses Inertia React pages and Pest tests. The evidence and onboarding guidance should describe the implementation the team actually maintains.

## Options considered

1. **Keep the original Blade and PHPUnit description** – No work, but it misleads contributors and reviewers.
2. **Record the current Inertia React and Pest implementation** – Accurate and consistent with the manifests and application structure; requires maintaining a separate frontend and backend testing workflow.
3. **Migrate back to Blade and PHPUnit** – One rendering model and familiar test framework, but adds unnecessary migration work and discards the current implementation.

## Decision

We choose **Laravel with Inertia React for the web UI and Pest for tests**. This records the current implementation and supersedes ADR 0001's descriptions of Blade and PHPUnit; Laravel and MySQL remain the backend and database choices.

## Consequences

Contributors use React pages for Inertia screens and Pest for application tests. Frontend type checks/builds and PHP tests are separate validation steps. ADR 0001 remains the original stack decision record; this ADR clarifies the UI and test choices now in use.
