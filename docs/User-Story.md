# Sprint 1: Foundation, Core Engine & Legal Rules

**Sprint Objective:** Establish the complete end-to-end architecture, database layer, core business logic with unit tests, and foundational scoring documentation.

---

## Phase A: Architecture, Database & Setup

### US-1.1: Database Schema & Entity Persistence

**User Story:** As a developer, I want to define persistent database entities for Purchase Orders, Cost Centres, Suppliers, Consultants, Time Bookings, and Planned Absences in MySQL so that data survives application restarts.

**Technical Deliverables:** `prisma/schema.prisma` (or raw SQL migration files) configured for MySQL.

**Target Scoring:** Criterion 1 · Persistence and data model (Level 2).

### US-1.2: Layered Architecture Configuration

**User Story:** As a developer, I want a clean separation between API Data Transfer Objects (DTOs), Domain Logic, and Database Persistence repositories so that the project structure is modular and easy to maintain.

**Technical Deliverables:** Directory structure (`/src/dto`, `/src/domain`, `/src/persistence`).

**Target Scoring:** Criterion 4 · Architecture practice (Building block A).

### US-1.3: Environment & Database Configuration

**User Story:** As a developer or juror, I want a safe `.env.example` file with default MySQL parameters so that the application can be cloned and run without manual credential guesswork.

**Technical Deliverables:** `.env.example` with `DATABASE_URL="mysql://root:@localhost:3306/stundenklar"`.

**Target Scoring:** Criterion 5 · Runs from the README.

---

## Phase B: Business Rules, Compliance & Unit Tests

### US-1.4: Order Budget Exceedance Rule

**User Story:** As a Client Project Lead, I want the system to reject any time booking that exceeds an order's remaining free budget so that external costs stay strictly under control.

**Technical Deliverables:** Domain method `Order.book(hours)` returning clear error messages (HTTP 400).

**Target Scoring:** Criterion 2 · Business rules and unit tests (Level 2).

### US-1.5: German Working Time Act (ArbZG) Legal Guardrails

**User Story:** As a Service Provider, I want the system to automatically validate daily bookings against German labor laws so that consultants do not exceed 10 hours per day or violate the mandatory 11-hour rest period.

**Technical Deliverables:** Domain validator class enforcing ArbZG daily hours cap and rest-period limits.

**Target Scoring:** Criterion 2 · Business rules and unit tests (Level 2).

### US-1.6: Unit Test Suite for Business Rules

**User Story:** As a developer, I want comprehensive unit tests covering accepted, rejected, and boundary cases for all business rules so that any regression is caught instantly.

**Technical Deliverables:** Vitest/Jest unit tests (e.g., rejects booking beyond free budget, rejects >10h work day) passing with a single command (`npm test`).

**Target Scoring:** Criterion 2 · Business rules and unit tests (Level 3).

---

## Phase C: Seed Data & Mandatory Documentation

### US-1.7: Database Seeding & Demo Data

**User Story:** As a juror or demo presenter, I want pre-seeded sample data including the Customer Portal Migration order (140 PD) and historical bookings so that the app works immediately upon launch.

**Technical Deliverables:** Seed script (`prisma/seed.ts` or `seed.sql`) creating 2 purchase orders, 3 consultants, 1 supplier, and historical bookings.

**Target Scoring:** Criterion 5 · Runs from the README.

### US-1.8: Domain Model & ER Diagram Documentation

**User Story:** As an architecture juror, I want a documented domain glossary and Mermaid ER diagram so that the current database structure and expansion phases are clear.

**Technical Deliverables:** `docs/domain.md` containing entity definitions and a Mermaid `erDiagram`.

**Target Scoring:** Criterion 1 · Persistence and data model (Level 2).

### US-1.9: Architectural Decision Records (ADRs)

**User Story:** As an architecture juror, I want documented decisions regarding the tech stack and user identity management so that the design trade-offs are transparent.

**Technical Deliverables:** `docs/adr/0001-tech-stack.md` and `docs/adr/0002-identity.md`.

**Target Scoring:** Criterion 4 · Architecture practice (Building block B).

### US-1.10: Roles, Permissions & Privacy Matrix

**User Story:** As a compliance officer, I want a written permission matrix defining access rights across Consultant, Project Lead, Purchasing, and Finance roles while enforcing GDPR data minimisation.

**Technical Deliverables:** `docs/roles-and-privacy.md`.

**Target Scoring:** Criterion 3 · Roles, permissions and data privacy (Level 2).

### US-1.11: README Evidence Map

**User Story:** As a technical juror, I want an Evidence Map table in the README linking directly to repository paths so that I can evaluate scoring criteria within 5 minutes.

**Technical Deliverables:** Evidence map section in `README.md`.

**Target Scoring:** Criterion 5 · Runs from the README.
