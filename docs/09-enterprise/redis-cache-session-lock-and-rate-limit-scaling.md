# PMMS Redis, Cache, Session, Lock, and Rate-Limit Scaling

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../01-architecture/caching-and-session-architecture.md](../01-architecture/caching-and-session-architecture.md) · [../05-devops/mysql-redis-minio-and-stateful-service-operations.md, Section 2](../05-devops/mysql-redis-minio-and-stateful-service-operations.md#2-redis-operations-architecture)

**No Redis configuration, clustering, or replication configuration is created here.**

---

## 1. Redis Remains Non-Authoritative

Restated absolutely per working rule 30 — nothing in this document changes Redis's status as transient cache/queue/session/lock/rate-limit infrastructure, never a system of record.

## 2. Redis Scaling Evaluation

Memory sizing · key cardinality · TTL governance · eviction policy · queue load · cache load · session load · lock load · rate-limit load · tenant-aware key prefixes (Section 3) · clustering readiness · replication readiness · failover readiness — all evaluated, none committed to a specific configuration in this phase, consistent with [../05-devops/mysql-redis-minio-and-stateful-service-operations.md, Section 2](../05-devops/mysql-redis-minio-and-stateful-service-operations.md#2-redis-operations-architecture)'s existing "not committed to" discipline for Reverb pub-sub coordination scaling.

## 3. Cache Architecture

For each cache, define: owner · data · key format · tenant context · invalidation · TTL · maximum staleness · fallback · sensitivity · observability · rebuild method.

Restated and extended from [../01-architecture/caching-and-session-architecture.md, "Candidate Cache Targets"](../01-architecture/caching-and-session-architecture.md#1-caching-architecture): public announcements, published schedules, published results, medal-tally projections, Organization Directory references, Sports Catalog references, feature configuration, permission-decision fragments where safe, public athlete-profile projections, dashboard summaries.

## 4. Cache Key Rules

A cache key includes: environment · tenant (where applicable) · organization or meet when required · capability · record or query identity · version · locale where relevant.

**Never rely on user-supplied tenant values without validation** — restated absolutely, directly extending [tenant-context-identification-and-propagation.md, Section 1](tenant-context-identification-and-propagation.md#1-tenant-identification-sources)'s trusted-source requirement into the cache-key layer specifically; a cache key built from an unvalidated client-supplied tenant ID could serve tenant A's cached data to tenant B.

## 5. Cache Invalidation

Prefer: event-driven invalidation (a domain event, per [../08-workflows/event-taxonomy-ownership-and-contracts.md](../08-workflows/event-taxonomy-ownership-and-contracts.md), triggers cache invalidation in its consumers) · versioned cache keys (a new version number invalidates implicitly rather than requiring an explicit delete) · short TTL for uncertain dependencies · explicit invalidation from the owning context.

Avoid: indefinite cache · hidden cross-context dependencies · cache clearing as normal correctness logic (a cache-clear should never be required to make the application behave correctly — that signals a caching or invalidation design flaw) · caching unauthorized data globally.

## 6. Cache Stampede Protection

Evaluated: locking · request coalescing · probabilistic early refresh · stale-while-revalidate · prewarming · rate limiting.

**Redis locks must not replace authoritative business validation** — restated absolutely per working rule 48 (caches must not become authoritative), applied specifically to distributed-lock usage; a Redis lock coordinates concurrent cache regeneration, it never substitutes for MySQL's own transactional/optimistic-locking concurrency control (per [../02-data/transaction-concurrency-and-locking.md](../02-data/transaction-concurrency-and-locking.md)).

## 7. Cache Isolation

**Cached protected data must remain tenant-isolated** — restated absolutely per working rule 49. A cache entry for Restricted or Highly Restricted-tier data (per [../02-data/information-classification-and-privacy.md](../02-data/information-classification-and-privacy.md)) is never shared across tenants, and its TTL is deliberately shorter than a Public-tier cache entry given the higher cost of staleness-driven exposure.

## 8. Rate Limiting

Rate limiting is Redis-backed (leveraging existing atomic-counter primitives) and applied per: authenticated user · API client · tenant (Section, [noisy-neighbor-fair-use-and-resource-governance.md](noisy-neighbor-fair-use-and-resource-governance.md)) · anonymous/public IP-based limiting for public endpoints. No specific numeric rate-limit value is invented here — every limit is a placeholder pending pilot evidence.

## 9. Distributed Locks

Redis distributed locks coordinate but never substitute for authoritative database validation — restated unchanged from [../02-data/transaction-concurrency-and-locking.md, Section 5](../02-data/transaction-concurrency-and-locking.md#5-outbox-and-event-persistence-evaluation)'s equivalent principle for optimistic locking.

## 10. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-19 (Redis clustering/replication adoption trigger) and ED-20 (specific rate-limit numeric values per endpoint category).
