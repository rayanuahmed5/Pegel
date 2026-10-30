# Roles, Permissions and Data Privacy

This document defines the permission concept, role authorization boundaries, and GDPR (*DSGVO*) data minimisation architecture for **Pegel**.

---

## 1. Roles Across Both Sides

| Role | Side | Description | View Focus |
|---|---|---|---|
| **External Consultant** | Supplier | External software engineer, architect, or consultant delivering project work. | Books daily working hours, enters planned vacations, and sees **only their own** bookings. |
| **Project Lead** | Client | Corporate project manager responsible for milestone delivery and project budget execution. | Monitors real-time budget burn rate, traffic lights, review itemized timesheets, and approves Proof of Work (*Leistungsnachweis*). |
| **Purchasing** *(Procurement)* | Client | Manages external purchase orders, contract quotas, and supplier framework agreements. | Views **aggregated person-days per order** and lead-time re-order alerts to issue follow-up orders 4 weeks in advance. |
| **Finance & Controlling** | Client | Accounts payable and financial controlling team reconciling invoices against approved work. | Reconciles monthly supplier invoices against approved hours per purchase order and flags billing variances. |
| **Superadmin** | Pegel administration | Trusted demo administrator for cross-role oversight and support. | Can view each role dashboard and approve or reject Proof of Work; cannot impersonate consultants or book time for them. |

---

## 2. Role Permission Matrix

Legend:
- `✓` = Allowed / Full access
- `✗` = Forbidden / Denied
- `Σ` = Aggregated totals only (no individual personal names or daily breakdown)

| Action or Data Scope | External Consultant | Client Project Lead | Client Purchasing | Client Finance | Superadmin |
|---|:---:|:---:|:---:|:---:|:---:|
| **Book working hours** | ✓ *(own only)* | ✗ | ✗ | ✗ | ✗ |
| **Register planned vacations** | ✓ *(own only)* | ✗ | ✗ | ✗ | ✗ |
| **See own bookings (with description)** | ✓ | ✓ | ✗ | ✗ | ✓ *(all)* |
| **See colleagues' daily bookings** | ✗ | ✓ *(project only)*| ✗ | ✗ | ✓ *(all)* |
| **See single daily hours per person** | ✓ *(own only)* | ✓ | ✗ | ✗ | ✓ *(all)* |
| **See total aggregated hours per order**| ✓ | ✓ | ✓ | ✓ | ✓ |
| **View remaining budget & forecast chart**| ✗ | ✓ | ✓ | ✓ | ✓ |
| **Approve / Reject Proof of Work**| ✗ | ✓ | ✗ | ✗ | ✓ |
| **View purchasing lead-time alert**| ✗ | ✓ | ✓ | ✗ | ✓ |
| **Audit invoice reconciliation**| ✗ | ✗ | ✗ | ✓ | ✓ |

---

## 3. Personal Data Minimisation (GDPR / DSGVO Art. 5)

| Data Field | Why We Store It | Who Needs It | Minimisation Rule | Retention Period |
|---|---|---|---|---|
| **Consultant Name & Email** | Assigning bookings to contractual personnel; login authentication. | Consultant, Project Lead | Never exposed to Purchasing or external unauthorized parties. | Duration of purchase order + statutory 10-year German tax audit retention (*HGB § 257*). |
| **Daily Start & End Times** | Verifying compliance with German Working Time Act (*ArbZG § 3, § 5*). | System compliance validator, Consultant | Stored only to validate 10h daily limit and 11h rest periods. Hidden from Purchasing and general dashboards. | Automatically purged or archived 2 years after contract completion. |
| **Task / Activity Description** | Justifying project progress and verifying work deliverables. | Consultant, Project Lead | Visible to Project Lead during Proof of Work approval; not sent to general accounting ledgers. | Kept with approved booking records. |
| **Planned Absence Dates** | Forecasting accurate budget burn-out dates (accounting for non-working days). | Forecasting Engine, Project Lead | Only presence/absence dates are stored; medical or sensitive personal absence reasons are **strictly excluded**. | Deleted once the calendar period has elapsed. |

---

## 4. Key Conflicts and Architectural Trade-Offs

### Conflict A: Transparency for the Client vs. Data Privacy for Consultants
- **The Tension:** Project Leads want total visibility into how consultants spend their hours, while German privacy law (*BDSG/DSGVO*) and works councils protect workers from continuous behavioral surveillance.
- **Pegel Solution:** Role-based segregation. Project Leads see task descriptions and daily duration to approve work deliverables, but Purchasing and Finance see **only aggregated person-days per order**. Consultants never see each other's hourly logs or day rates.

### Conflict B: Budget Control vs. Not Becoming the Employer (*Scheinselbstständigkeit*)
- **The Tension:** Under German labor law, if a client exercises direct disciplinary control over external consultants (e.g. dictating exact working hours, core hours, or approving personal vacation leave), the relationship risks being legally reclassified as disguised employment (*Scheinselbstständigkeit*) or illegal temporary employment (*Arbeitnehmerüberlassung*).
- **Pegel Solution:** Pegel tracks **orders, budgets, and milestones**, not people management. The client does not approve or reject vacation requests; vacation dates are entered by consultants purely as non-binding availability forecasts so the projection engine remains accurate.

---

## 5. Server-Side Enforcement

Access control in Pegel is enforced on the server:
1. **Domain Model Guardrails:** Methods like `PurchaseOrder::book()` enforce that only explicitly assigned consultants can submit bookings.
2. **Controller checks:** Booking creation requires an authenticated consultant account; only project leads, finance users, or the superadmin can approve or reject bookings.
3. **Data visibility:** The superadmin can view all role dashboards. Consultant booking creation always uses the signed-in consultant's own profile.
