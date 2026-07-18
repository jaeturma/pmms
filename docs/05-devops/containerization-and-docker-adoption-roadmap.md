# PMMS Containerization and Docker Adoption Roadmap

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [deployment-topology-and-runtime-units.md](deployment-topology-and-runtime-units.md) · [../01-architecture/environment-and-configuration-model.md](../01-architecture/environment-and-configuration-model.md)

This document defines a phased Docker adoption roadmap and future container-image/registry strategy. **No Dockerfile, Docker Compose file, or container image is created here**, per working rules 5–6, 21.

---

## 1. Docker Adoption Roadmap

### Phase A — Documentation and Readiness (This Phase Contributes To, Does Not Complete)

- **Runtime inventory** — the set of processes PMMS needs (web application, queue workers, scheduler, Reverb, and the stateful services MySQL/Redis/MinIO), per Section 2.
- **Required services** — identified, not yet containerized.
- **Volumes** — conceptually identified (application storage, MySQL data, MinIO data) — no volume created.
- **Networks** — conceptually identified (per [network-reverse-proxy-tls-and-domain-architecture.md, Section 3](network-reverse-proxy-tls-and-domain-architecture.md#3-network-zones)) — no network created.
- **Health checks** — conceptually defined (per [observability-logging-metrics-tracing-and-alerting.md, Section 1](observability-logging-metrics-tracing-and-alerting.md#1-health-checks)) — no health-check script written.
- **Secrets** — the inventory from [configuration-feature-flag-and-secret-management.md, Section 3](configuration-feature-flag-and-secret-management.md#3-secret-management) — no secret handled.
- **Build stages** — per [build-artifact-and-dependency-management.md, Section 1](build-artifact-and-dependency-management.md#1-build-architecture) — no build stage implemented as a Dockerfile.
- **Image ownership** — which team/role owns which container image definition, once images exist.

### Phase B — Local Development (Candidate, Not Committed)

- An optional local container environment (Laravel Sail or an equivalent Docker Compose setup) as an alternative to native local development (Section, [local-development-environment.md, Section 3](local-development-environment.md#3-local-development-tooling-evaluation)).
- Developer documentation for whichever local approach is eventually adopted.
- A performance assessment on Windows specifically, given the current development context — Docker Desktop's filesystem-performance characteristics on Windows are a known evaluation point before recommending it as the default local approach.

### Phase C — Integration and CI

- Reproducible service dependencies (a real MySQL/Redis/MinIO available to CI-run integration tests, per [../04-quality/quality-open-decisions.md, QD-21](../04-quality/quality-open-decisions.md#qd-21--integration-test-infrastructure-approach)) — a natural, low-risk first real use of containers, since CI environments are inherently ephemeral and disposable.
- Automated integration testing against these containerized dependencies.

### Phase D — Staging and Production

- Immutable application images (the Laravel/PHP-FPM runtime, built once per release per Section, [build-artifact-and-dependency-management.md](build-artifact-and-dependency-management.md)).
- Separate stateful services (MySQL/Redis/MinIO) — either containerized with durable, external volumes or run as managed/dedicated services outside the container orchestration entirely, a decision deferred to [devops-open-decisions.md](devops-open-decisions.md).
- Controlled secrets, per [configuration-feature-flag-and-secret-management.md, Section 3](configuration-feature-flag-and-secret-management.md#3-secret-management).
- A container registry (Section 3).
- Image scanning (a candidate CI/CD security gate, per [ci-cd-and-release-pipeline-architecture.md, Section 5](ci-cd-and-release-pipeline-architecture.md#5-security-and-privacy-pipeline-gates)).
- Version promotion — the same built image promoted through environments, mirroring [ci-cd-and-release-pipeline-architecture.md, Section 3](ci-cd-and-release-pipeline-architecture.md#3-continuous-delivery-architecture)'s artifact-promotion principle.

**Docker is confirmed technology direction "during a later implementation phase"** — restated from the approved technology stack; this roadmap sequences that adoption without generating any actual configuration in this documentation-only phase.

## 2. Container Strategy (Future Candidates)

| Candidate Container | Process |
|---|---|
| PHP-FPM application | The Laravel application runtime |
| Nginx or reverse proxy | Per [network-reverse-proxy-tls-and-domain-architecture.md](network-reverse-proxy-tls-and-domain-architecture.md) |
| Queue worker | Horizon-supervised workers |
| Scheduler | The Laravel scheduler process |
| Reverb | The real-time broadcast process |
| MySQL if self-hosted | Contingent on the cloud-versus-on-premise/managed-service decision (Section, [cost-resource-and-capacity-governance.md](cost-resource-and-capacity-governance.md)) |
| Redis | The cache/queue/lock backing store |
| MinIO | The object-storage service |
| Malware scanner | Once a scanner is selected, per [../03-security/file-object-storage-and-malware-security.md, Section 3](../03-security/file-object-storage-and-malware-security.md#3-malware-scanning-architecture) |
| Monitoring agents | Once an observability stack is selected, per [observability-logging-metrics-tracing-and-alerting.md](observability-logging-metrics-tracing-and-alerting.md) |

### Rules (Applied Once Containers Are Actually Built)

1. **Separate process concerns where practical** — one container, one responsibility (the application does not bundle its own database).
2. **Do not package stateful data into application images** — the application image is stateless; MySQL/MinIO/Redis data lives in externally-managed volumes or dedicated services, never baked into an image.
3. **Use non-root execution where feasible** — containers run as an unprivileged user by default.
4. **Use health checks** — every container declares how its own health is checked, per [observability-logging-metrics-tracing-and-alerting.md, Section 1](observability-logging-metrics-tracing-and-alerting.md#1-health-checks).
5. **Use minimal, trusted base images** — official, actively-maintained base images only, minimizing attack surface.
6. **Pin versions** — no `:latest` tag in any environment beyond Local experimentation.
7. **Keep images immutable** — restated from [devops-and-platform-operations-strategy.md, Section 2](devops-and-platform-operations-strategy.md#2-devops-principles), Principle 3.
8. **Keep secrets external** — restated absolutely; no secret is ever baked into an image layer.
9. **Persist only required volumes** — a container's ephemeral working data is not persisted; only genuinely durable state (MySQL data, MinIO objects) is volume-backed.

## 3. Container Registry Strategy (Not Yet Selected)

A container registry (GitHub Container Registry, given the confirmed GitHub direction, is the most natural candidate; a cloud-provider-native registry is an alternative once a deployment topology is chosen) is required once Phase D (Section 1) begins — not selected in this documentation-only phase. Registry requirements, once selected: access control matching the production-access governance in [production-support-access-and-data-repair-operations.md](production-support-access-and-data-repair-operations.md), image-scanning integration, and a retention policy preventing unbounded image-history growth.

## 4. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably whether MySQL/Redis/MinIO are self-hosted-and-containerized versus run as managed cloud services (a decision entangled with the still-unresolved deployment-topology and cloud-versus-on-premise questions), and container-registry selection.
