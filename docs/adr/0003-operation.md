# ADR 0003: Operation, Deployment and Scalability

- **Status:** Accepted
- **Track:** Working code

## Context

Pegel serves enterprise clients with sensitive financial booking data and external supplier contracts. Large German enterprises have strict compliance mandates regarding data residency (GDPR / DSGVO), ISO 27001, and works council (Betriebsrat) co-determination.

The product must support two primary deployment topologies:
1. Self-hosted on-premise / private cloud within the client's internal network (OpenShift, Kubernetes, or VMware vSphere).
2. Managed multi-tenant or single-tenant cloud (AWS Europe - Frankfurt `eu-central-1`, Azure Germany West Central).

In addition, the system must easily scale from a few hundred monthly bookings during pilot testing to hundreds of thousands of monthly bookings across dozens of simultaneous suppliers and projects.

## Decision

- **Packaging and Portability:**
  - Standard containerization using Docker / OCI containers.
  - Multi-stage container builds containing PHP-FPM, Nginx, and static asset builds, orchestrated locally with `docker-compose.yml` and in production with Helm charts on Kubernetes.
- **Data Persistence & Isolation:**
  - Relational database storage (MySQL 8.4+ / AWS Aurora MySQL / Azure Database for MySQL).
  - Row-level tenant partitioning and foreign key indexes on `(consultant_id, date)` and `(purchase_order_id, status)` ensuring `O(1)` / `O(log N)` lookup times even with millions of records.
- **Handling Load & Heavy Calculations:**
  - Forecasting queries (pace, holiday adjustments, buffer depletion) are read-only aggregate queries executed directly via optimized SQL window functions or cached in Redis.
  - Asynchronous background jobs (via Laravel Queues / Redis) for bulk CSV timesheet imports and end-of-month invoice reconciliation runs, preventing HTTP worker thread starvation.

## Alternatives considered

- **Pure SaaS multi-tenant only on public cloud:** Rejected. Many German public sector and manufacturing enterprises prohibit storing internal cost centre structures and external consultant project data on non-vetted public multi-tenant SaaS clouds.
- **Serverless / FaaS (e.g. AWS Lambda + DynamoDB):** Rejected. Relational integrity with foreign keys, cascading deletes, and complex multi-table accounting validations are essential to auditability. Serverless cold starts and connection pooling overhead introduce unnecessary complexity.
- **Single monolithic Bare-Metal Server without containers:** Rejected. Lacks automated scaling, declarative configuration, and uniform reproducibility across local developer laptops and enterprise production clusters.

## Consequences

- The application can be deployed anywhere Docker or Kubernetes runs.
- Jurors can run the application with zero external infrastructure.
- High operational reliability: Stateless PHP worker pods scale horizontally behind a load balancer, with Redis handling queue execution and session state caching.

