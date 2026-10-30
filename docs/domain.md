# Domain Model & Entity Architecture

This document describes the domain terminology, core entity relations, and architecture of **Pegel**.

## Glossary

| Term | German | Meaning |
|---|---|---|
| **Purchase Order** | *Bestellung* | The client's formal contract to a supplier with a defined scope, period, and budget. Every booking belongs to exactly one order. |
| **Person-Day (PD)** | *Personentag (PT)* | Standard industry unit: 1 person working 1 full day (standardized as 8.0 hours). Day rates and order budgets are counted in PD. |
| **Framework Agreement** | *Rahmenvertrag* | A master contract with a supplier defining general conditions and a global umbrella budget from which individual purchase orders draw. *(Expansion stage)* |
| **Cost Centre / PSP Element** | *Kostenstelle / PSP-Element* | Client internal accounting objects that decide which department or project budget pays for external consultant hours. |
| **Proof of Work** | *Leistungsnachweis* | Verified itemized list of hours worked by a supplier during a billing period (e.g., month), approved by the client Project Lead before invoice settlement. |
| **Working Time Act** | *Arbeitszeitgesetz (ArbZG)* | German federal labor law mandating a maximum 10-hour working day (§ 3) and an 11-hour mandatory uninterrupted rest period between shifts (§ 5). |
| **Planned Absence** | *Geplante Abwesenheit* | Planned vacations, client site shutdowns (e.g. Christmas/New Year), or public holidays that impact consultant availability and shift budget burn-out dates. |
| **Calendar Week (KW)** | *Kalenderwoche (KW)* | Standard German business planning cadence (KW 1 to KW 52/53). Budget forecasts and purchasing lead-time re-order alerts are pinned to calendar weeks. |

---

## Entity Relationship Diagram

Entities marked **`(now)`** are implemented with server-side database tables, foreign keys, and unit tests in the current release. Entities marked **`(later)`** represent the next expansion stage.

```mermaid
erDiagram
    USER ||--o| CONSULTANT : "linked to"
    USER ||--o{ PURCHASE_ORDER : "manages (project lead)"
    USER ||--o{ TIME_BOOKING : "approves"

    SUPPLIER ||--o{ CONSULTANT : "employs"
    SUPPLIER ||--o{ PURCHASE_ORDER : "contracts"
    SUPPLIER ||--o{ FRAMEWORK_AGREEMENT_LATER : "holds (later)"

    COST_CENTRE ||--o{ PURCHASE_ORDER : "funds"

    FRAMEWORK_AGREEMENT_LATER ||--o{ PURCHASE_ORDER : "draws from (later)"

    PURCHASE_ORDER ||--o{ PURCHASE_ORDER_CONSULTANT : "assigned"
    CONSULTANT ||--o{ PURCHASE_ORDER_CONSULTANT : "assigned"

    PURCHASE_ORDER ||--o{ TIME_BOOKING : "contains"
    CONSULTANT ||--o{ TIME_BOOKING : "performs"

    CONSULTANT ||--o{ PLANNED_ABSENCE : "registers"

    TIME_BOOKING ||--o| INVOICE_LINE_LATER : "reconciles (later)"

    USER {
        bigint id PK
        string name
        string email UK
        string role "consultant | project_lead | purchasing | finance"
        string password
    }

    SUPPLIER {
        bigint id PK
        string name
        string contact_email
        string city
    }

    CONSULTANT {
        bigint id PK
        bigint user_id FK "nullable"
        bigint supplier_id FK
        string name
        string email UK
        decimal daily_rate_eur
    }

    COST_CENTRE {
        bigint id PK
        string code UK "e.g. CC-4100"
        string name
        string department
    }

    PURCHASE_ORDER {
        bigint id PK
        string order_number UK "e.g. 4500012345"
        string title
        bigint supplier_id FK
        bigint cost_centre_id FK
        bigint project_lead_id FK
        decimal ordered_budget_pd "e.g. 140.0 PD"
        decimal hourly_rate_eur "e.g. 120.00 EUR"
        date start_date
        date end_date
        string status "active | completed | closed"
    }

    PURCHASE_ORDER_CONSULTANT {
        bigint purchase_order_id PK,FK
        bigint consultant_id PK,FK
    }

    TIME_BOOKING {
        bigint id PK
        bigint purchase_order_id FK
        bigint consultant_id FK
        date date
        time start_time "nullable"
        time end_time "nullable"
        decimal duration_hours "e.g. 8.00"
        text description
        string accounting_object "e.g. PSP: P-2026-0815.03"
        string activity
        string status "pending | approved | rejected"
        text rejection_reason
        timestamp approved_at
        bigint approved_by FK
    }

    PLANNED_ABSENCE {
        bigint id PK
        bigint consultant_id FK
        string type "vacation | shutdown | public_holiday"
        date start_date
        date end_date
        string description
    }

    FRAMEWORK_AGREEMENT_LATER {
        bigint id PK
        string agreement_number UK
        bigint supplier_id FK
        decimal total_pool_budget_pd
        date valid_until
    }

    INVOICE_LINE_LATER {
        bigint id PK
        string invoice_number
        bigint time_booking_id FK
        decimal billed_amount_eur
        string match_status "matched | variance | missing"
    }
```

---

## Core Budget & Forecast Formulas

1. **Total Ordered Hours:**
   $$\text{orderedHours} = \text{orderedBudgetPD} \times 8.0$$

2. **Total Used Hours & PD:**
   $$\text{usedHours} = \sum_{\text{status} \neq \text{'rejected'}} \text{durationHours}$$
   $$\text{usedPD} = \frac{\text{usedHours}}{8.0}$$

3. **Remaining Free Budget:**
   $$\text{remainingBudgetHours} = \max(0, \text{orderedHours} - \text{usedHours})$$
   $$\text{remainingBudgetPD} = \frac{\text{remainingBudgetHours}}{8.0}$$

4. **Predictive Depletion Week:**
   Calculated by projecting weekly consultant velocity (e.g., 6 PD / week) minus planned absence blocks and client plant shutdowns into the project timeline.
