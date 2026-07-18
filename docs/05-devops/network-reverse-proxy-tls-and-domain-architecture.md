# PMMS Network, Reverse Proxy, TLS, and Domain Architecture

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [../03-security/infrastructure-runtime-and-network-security.md, Section 5](../03-security/infrastructure-runtime-and-network-security.md#5-network-and-runtime-security) · [deployment-topology-and-runtime-units.md](deployment-topology-and-runtime-units.md)

This document defines reverse-proxy responsibilities, TLS/domain/certificate architecture, and network zoning. **No Nginx/Apache/Caddy configuration, domain name, or firewall rule is created here**, per working rule 11.

---

## 1. Reverse Proxy Architecture

Responsibilities a future reverse-proxy configuration must satisfy:

TLS termination · host routing (administrative vs. public vs. API vs. Reverb) · request size limits · timeouts (tuned differently for ordinary requests vs. long-running exports/imports) · static assets (serving Vite build output efficiently) · security headers (restated from [../03-security/infrastructure-runtime-and-network-security.md, Section 5](../03-security/infrastructure-runtime-and-network-security.md#5-network-and-runtime-security)) · rate-limiting readiness · WebSocket proxying (for Reverb) · health checks (per [observability-logging-metrics-tracing-and-alerting.md](observability-logging-metrics-tracing-and-alerting.md)) · public-versus-administrative domain distinction · access logging · maintenance responses (per [deployment-strategies-rollbacks-and-maintenance.md, Section 3](deployment-strategies-rollbacks-and-maintenance.md#3-maintenance-mode-strategy)).

**No proxy is selected or configured in this phase.**

## 2. Domain and TLS Architecture

### Candidate Domains (Structural, Not Named)

Administrative portal · public portal · API · Reverb · an object-download gateway where MinIO access requires a public-facing endpoint · monitoring · Staging · Pilot.

Every environment beyond Local uses its own distinct subdomain/domain, consistent with [environment-architecture.md, Section 2](environment-architecture.md#2-environment-isolation)'s "separate domains or subdomains" isolation requirement — never a shared domain distinguished only by a path or query parameter, which would complicate cookie/CORS isolation.

### Requirements

- **TLS for all non-local environments** — restated absolutely from [../03-security/infrastructure-runtime-and-network-security.md, Section 5](../03-security/infrastructure-runtime-and-network-security.md#5-network-and-runtime-security).
- **Automated certificate renewal readiness** — a candidate future control (e.g., an ACME-based automated renewal process), not yet implemented.
- **Certificate expiry monitoring** — per [observability-logging-metrics-tracing-and-alerting.md, Section 4](observability-logging-metrics-tracing-and-alerting.md#4-metrics-architecture) ("certificate expiry" metric).
- **Domain ownership** — a specific, accountable owner (Infrastructure owner, per Phase 0.6) for every registered domain.
- **DNS change control** — DNS changes follow the same change-management discipline as any other production-affecting change, per [incident-problem-change-and-release-management.md, Section 5](incident-problem-change-and-release-management.md#5-change-management).
- **Environment separation** — restated from Section 1 above.
- **Secure cookies** — restated from [../03-security/identity-authentication-and-session-security.md, Section 3](../03-security/identity-authentication-and-session-security.md#3-session-security); `SESSION_SECURE_COOKIE` enforced wherever TLS is enforced.
- **Public and private endpoint distinction** — a public-facing domain never inadvertently exposes an administrative or API surface without its own independent authorization boundary.

**No actual domain name is created unless already approved** — restated per working rule 31; this document defines the structure a future domain-registration decision fills in.

## 3. Network Zones

| Zone | Contents | Notes |
|---|---|---|
| Public edge | Reverse proxy, public-facing load balancer | The only zone directly reachable from the internet |
| Application | Laravel web runtime, Reverb | Reachable from Public Edge; not directly reachable from the internet |
| Real-time | Reverb's WebSocket-serving capacity | May be co-located with Application initially, separated as scale requires |
| Worker | Queue workers, scheduler | Not reachable from Public Edge at all — workers pull work, they don't serve inbound requests |
| Data | MySQL | Reachable only from Application and Worker zones |
| Object storage | MinIO | Reachable only from Application and Worker zones, plus a narrowly-scoped signed-URL path for direct client download where designed |
| Management | Administrative/deployment tooling | Reachable only via the same governed production-access path as any other privileged access, per [production-support-access-and-data-repair-operations.md](production-support-access-and-data-repair-operations.md) |
| Monitoring | Observability stack | Reachable from every other zone (collecting telemetry) but not reachable from the Public Edge |
| Backup | Backup storage destination | Reachable only from Data/Object Storage zones' backup processes, never from Public Edge or Application |
| Venue local network | Local venue server, scanners, score stations | Connects to the central system only through the governed sync boundary, per [meet-day-venue-and-offline-operations.md](meet-day-venue-and-offline-operations.md) |
| External integration | Any future approved third-party service boundary | Currently empty — no external integration is approved, per [../03-security/vendor-and-third-party-risk.md, Section 3](../03-security/vendor-and-third-party-risk.md#3-currently-approved-vendors) |

### Permitted Communication Direction (Conceptual)

Public Edge → Application (inbound only) · Application ↔ Data/Object Storage (bidirectional, application-initiated) · Worker → Data/Object Storage (bidirectional, worker-initiated) · Monitoring ← every zone (telemetry pull/push, one-directional collection) · Backup ← Data/Object Storage (one-directional, backup-initiated) · Venue Local Network → Application (sync, application-authorized) · no zone reaches Management except through the governed access path.

**No firewall rule is created by this document** — this section defines the conceptual boundary a future firewall/security-group configuration implements.

## 4. Runtime Service Discovery

A future capability (how the application locates MySQL/Redis/MinIO/Reverb endpoints at runtime) — via environment-variable-based configuration initially (matching the existing `config/*.php` pattern), evaluated for a more dynamic service-discovery mechanism (DNS-based or a service mesh) only if the topology's complexity later justifies it. **No specific mechanism is selected in this phase.**

## 5. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably reverse-proxy product selection (Nginx is the common default but not committed here) and whether a CDN is introduced for static-asset/public-portal delivery.
