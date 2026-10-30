# Product vision

## Product name

Pegel

## In one sentence

For **client project leads**, who **learn too late that a consulting budget will not last until the end of the project**, our product **Pegel** is a **time booking and budget forecast web application** that **shows how far each purchase order reaches, including vacations and public holidays, and warns in time when a follow-up order must start**. Unlike spreadsheets and the old tool, it **forecasts the end of the budget instead of reporting only what is already used, and checks every booking against the Working Time Act (ArbZG) on the server**.

## Who we build for first

The **client project lead**. Today they learn how much budget is used only when the monthly invoice arrives, and they cannot tell whether a budget lasts until the project ends. Pegel answers "will my budget last, and by when must we order more?" in three seconds.

## Roles

The project lead on the client side is the person we build for first. They want a budget traffic light that shows the expected end date, an early warning with a ready-made follow-up order proposal, and the ability to approve the proof of work (Leistungsnachweis) before an invoice is paid. They see the bookings of their own projects, so they can check what was done against the budget.

Purchasing on the client side wants to know early enough when a follow-up order must be placed, because it needs several weeks to do so. They want order limits, framework agreements and lead-time alerts such as “order by week 47”. They see aggregated person-days per order, not single bookings, because they plan orders and do not need to check individuals.

Finance and controlling on the client side want to check invoices against approved hours and spot rule violations without doing it by hand in spreadsheets. They see the approved hours per order and period, which is the data an invoice is checked against.

The external consultant on the supplier side wants to book working time in seconds and not twice, and to enter planned vacation once so the client’s forecast is right. They book only against the orders they are assigned to, and they see only their own bookings, which also protects the personal data of their colleagues.

## The core flow

1. An external consultant signs in and sees only the orders they are assigned to.
2. They book hours (date, duration, short description) against an order and its accounting object (cost centre or project element).
3. The server checks the booking: free budget, overlapping bookings, ArbZG limits. A violating booking is rejected with a clear message.
4. The booking is stored and the order's used and free budget update.
5. The project lead opens the order and sees ordered, used and free person-days, plus the forecast: expected end of the budget and the latest date for a follow-up order.

## What makes us special

- **A forecast, not just a total:** end of budget at the current pace, adjusted for planned vacations and public holidays, with an "order by" date based on purchasing lead time.
- **Rules enforced on the server:** budget limit, overlaps and ArbZG limits live in the domain and are covered by unit tests.
- **Privacy by design:** the client plans with person-days per order and week. Single consultants' daily hours and vacations are visible only where a role needs them.

## What we leave out on purpose

- **No instructions to individual consultants on when or how to work.** The client steers budget and progress, not working schedules, so it does not act like the employer (risk of Scheinselbstständigkeit or illegal Arbeitnehmerüberlassung).
- **No payment execution.** Pegel approves proof of work. Payment stays in the existing ERP system.
- **No real single sign-on in the demo.** One seeded demo user per role. The production sign-in approach is documented in an ADR.
- **No integration with suppliers' own time tracking** in the first stage. Bookings are entered in Pegel.

## Our track

Working code (guide 1.3), with a clickable mockup in `docs/mockup/` as fallback and as a building block for criterion 4.

## Our stack

Laravel (PHP) with MySQL, tested with PHPUnit. See [ADR 0001](adr/0001-stack.md).
