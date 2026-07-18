# PMMS Scalability and Workload-Isolation Architecture

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../01-architecture/resilience-performance-and-scaling.md, Section 1](../01-architecture/resilience-performance-and-scaling.md#1-scaling-boundaries)

---

## 1. Scalability Model

Scaling types evaluated: vertical scaling · horizontal scaling · workload decomposition · process isolation · tenant partitioning · read scaling · write scaling · storage scaling · geographic scaling.

**Scaling decisions must have measurable triggers** — restated absolutely as this document's governing rule; no scaling mechanism is adopted speculatively, only once a specific, measured threshold (per [workload-capacity-and-scale-assumptions.md, Section 2](workload-capacity-and-scale-assumptions.md#2-scale-evidence-requirements)) is reached.

| Scaling Type | Applies To | Trigger Category |
|---|---|---|
| Vertical | Single-server pilot topology (per [../05-devops/deployment-topology-and-runtime-units.md](../05-devops/deployment-topology-and-runtime-units.md)) | Sustained resource saturation on the existing node |
| Horizontal | Web/worker nodes once the Production-Oriented Topology is adopted | Sustained request/job backlog beyond one node's capacity |
| Workload decomposition | Independently scalable workload groups (Section 2) | A specific workload's growth outpaces the others' |
| Process isolation | Reverb, workers, scheduler — already isolated as separate processes even in the pilot topology | Architectural default, not threshold-triggered |
| Tenant partitioning | Future multi-tenant deployment (per [tenant-data-ownership-and-isolation-architecture.md](tenant-data-ownership-and-isolation-architecture.md)) | A specific tenant's scale or isolation/residency requirement |
| Read scaling | Read replicas (per [mysql-performance-replication-partitioning-and-scaling-readiness.md](mysql-performance-replication-partitioning-and-scaling-readiness.md)) | Sustained read-query load contending with write-path performance |
| Write scaling | Partitioning/sharding readiness (same document) | Sustained write-throughput or table-size limits |
| Storage scaling | MinIO capacity growth | Sustained object-storage growth rate |
| Geographic scaling | Multi-region readiness (per [disaster-recovery-topology-failover-and-failback.md](disaster-recovery-topology-failover-and-failback.md)) | A specific regional-latency or data-residency requirement |

## 2. Independently Scalable Workloads (Restated)

Restated unchanged from [../01-architecture/resilience-performance-and-scaling.md, Section 1](../01-architecture/resilience-performance-and-scaling.md#1-scaling-boundaries): public traffic, administrative web requests, API traffic, queue workers, notifications, imports/exports, media processing, report generation, Reverb connections, public projections, search, mobile synchronization, file storage.

## 3. Workload-Isolation Rules

**Public traffic must not starve official scoring or result workflows** — restated as the single most important scaling rule in the platform, unchanged from Phase 0.4.

| Rule | Direction |
|---|---|
| Separate worker pools per queue category | Restated unchanged from [../08-workflows/queue-routing-priority-retry-and-failure-architecture.md, Section 2](../08-workflows/queue-routing-priority-retry-and-failure-architecture.md#2-queue-priority-and-isolation) |
| Public-facing capacity isolated from administrative capacity | The public portal's read load must never compete with score-entry or credential-validation request capacity |
| Reverb isolated as its own process | Given its persistent-connection nature, restated from [../05-devops/application-worker-scheduler-and-realtime-deployment.md, Section 3](../05-devops/application-worker-scheduler-and-realtime-deployment.md#3-reverb-deployment) |
| Bulk/expensive work isolated from time-critical work | `imports`/`exports`/`media`/`analytics` never share worker capacity with `critical` |
| Tenant workload isolation | A future multi-tenant deployment prevents one tenant's load from degrading another's — see [noisy-neighbor-fair-use-and-resource-governance.md](noisy-neighbor-fair-use-and-resource-governance.md) |

## 4. Scaling Evolution Path

Restated from [../05-devops/deployment-topology-and-runtime-units.md](../05-devops/deployment-topology-and-runtime-units.md): the single-server pilot topology, the Production-Oriented Topology (horizontally-scalable web/worker nodes, isolated Reverb, dedicated stateful services), and the Hybrid Venue Topology represent an **evolution path, not a menu the platform must immediately choose from** — restated absolutely, no final topology is selected without capacity and pilot evidence.

## 5. Do Not Recommend Kubernetes Without Justification

Restated absolutely per working rule 14: Kubernetes is never recommended as the default scaling mechanism without measured justification and demonstrated operational capability — a simpler horizontal-scaling approach (additional application/worker nodes behind a load balancer) is evaluated first.

## 6. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-04 (specific scaling trigger thresholds per workload, deferred pending pilot evidence).
