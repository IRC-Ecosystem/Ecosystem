# Low-Memory Eco Compose Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the Eco Docker workflow usable on a 4 GB Windows laptop while documenting profiles, limits, verification, and the observed runtime status accurately.

**Architecture:** Keep database and core integration services in a small default Compose stack. Move user interfaces and domain workloads to named profiles, preserving their existing dependencies. Apply Compose-native local resource limits and instruct developers to build serially; no application code changes are required for this operational concern.

**Tech Stack:** Docker Compose, Docker Desktop/WSL2, MySQL 8, Markdown.

---

## File Structure

- Modify: `docker-compose.yml` — service profiles and local resource limits.
- Modify: `README.md` — concise default startup and profile usage.
- Modify: `dokumentasi.md` — corrected verified status and 4 GB operations workflow.
- Create: `DEVELOPER_GUIDE.md` — developer-only lifecycle, profiles, diagnostics, memory measurement, and safe cleanup.

### Task 1: Configure Compose profiles and resource ceilings

**Files:**
- Modify: `docker-compose.yml`

- [ ] **Step 1: Validate the current Compose model before editing**

Run:
```bash
docker compose config --quiet
docker compose config --services
```

Expected: exit code `0`; 13 service names listed.

- [ ] **Step 2: Add a small default stack and profile optional services**

Keep `mysql`, `central-bank`, `umkm-insight`, and `connector` unprofiled. Add `profiles` to optional services:

```yaml
wallet:
  profiles: [ui]
gateway:
  profiles: [ui]
smartbank-frontend:
  profiles: [ui]
central-bank-frontend:
  profiles: [ui]
pos:
  profiles: [pos]
marketplace:
  profiles: [marketplace]
supplierhub:
  profiles: [supplier]
logistikita:
  profiles: [pos, supplier, integrator]
api-integrator:
  profiles: [integrator]
```

Preserve existing environment, dependency, healthcheck, network, and port definitions.

- [ ] **Step 3: Apply Compose-native limits to every service**

Add `mem_limit` and `cpus` at each service root. Use this ceiling:

```yaml
mysql:                  { mem_limit: 768m, cpus: 1.0 }
central-bank:           { mem_limit: 256m, cpus: 0.75 }
umkm-insight:           { mem_limit: 160m, cpus: 0.50 }
connector:              { mem_limit: 256m, cpus: 0.75 }
wallet:                 { mem_limit: 192m, cpus: 0.50 }
gateway:                { mem_limit: 160m, cpus: 0.50 }
smartbank-frontend:     { mem_limit: 256m, cpus: 0.50 }
central-bank-frontend:  { mem_limit: 192m, cpus: 0.50 }
pos:                    { mem_limit: 192m, cpus: 0.50 }
marketplace:            { mem_limit: 192m, cpus: 0.50 }
supplierhub:            { mem_limit: 192m, cpus: 0.50 }
logistikita:            { mem_limit: 128m, cpus: 0.50 }
api-integrator:         { mem_limit: 192m, cpus: 0.50 }
```

- [ ] **Step 4: Validate the rendered default and profile configurations**

Run:
```bash
docker compose config --quiet
docker compose config --format json > /tmp/eco-compose.json
docker compose --profile ui --profile pos --profile marketplace --profile supplier --profile integrator config --quiet
```

Expected: all commands exit `0`; rendered JSON includes configured `mem_limit` values.

### Task 2: Document low-memory operations

**Files:**
- Modify: `README.md`
- Modify: `dokumentasi.md`
- Create: `DEVELOPER_GUIDE.md`

- [ ] **Step 1: Replace README all-stack default with baseline startup**

Document:

```powershell
Copy-Item .env.example .env
# Populate every change_me_* value. Do not commit .env.
docker compose --parallel 1 up -d --build --wait
```

Add profile examples:

```powershell
docker compose --parallel 1 --profile ui up -d --build --wait
docker compose --parallel 1 --profile pos up -d --build --wait
docker compose --parallel 1 --profile marketplace up -d --build --wait
```

Correct the persistent volume name to `mysql_prod_data`.

- [ ] **Step 2: Revise runtime evidence in `dokumentasi.md`**

Replace unconditional claims that every service is healthy with dated observed evidence: Docker daemon restart on 2026-08-10 started 13 existing containers; MySQL reconnection caused Central Bank, Connector, POS, and API Integrator startup/health failures; no OOM kill occurred. State that validation after the profile change is authoritative.

- [ ] **Step 3: Create `DEVELOPER_GUIDE.md`**

Include exact sections:

```markdown
# Eco Developer Guide

## Prerequisites
## Configure Secrets
## Default Core Stack
## Domain Profiles
## Full Stack
## Verify Services
## Measure Memory
## Troubleshoot Startup Failures
## Stop and Reset
## Disk Cleanup
```

State that `docker builder prune`/`docker system prune` are destructive cleanup commands and must be reviewed before use. Include `docker compose down` as the normal stop command and `docker compose down -v` only as an explicit data-reset operation.

### Task 3: Verify the 4 GB workflow

**Files:**
- No code changes expected

- [ ] **Step 1: Stop the existing project stack without deleting volumes**

Run:
```bash
docker compose down
```

Expected: project containers and network removed; `eco_mysql_prod_data` remains.

- [ ] **Step 2: Start only the baseline with serialized build/start**

Run:
```bash
docker compose --parallel 1 up -d --build --wait
```

Expected: baseline services are running; if an existing application issue prevents health, collect `docker compose logs --tail=100 <service>` and report it without masking it.

- [ ] **Step 3: Test baseline endpoints and inspect limits**

Run:
```bash
curl -fsS http://localhost:3000/api/v1/health
curl -fsS http://localhost:5000/ready
docker stats --no-stream
docker inspect --format '{{.Name}} {{.HostConfig.Memory}} {{.HostConfig.NanoCpus}}' eco-mysql-1 eco-central-bank-1 eco-umkm-insight-1 eco-connector-1
```

Expected: endpoints return HTTP `200`; inspect output shows non-zero resource settings.

- [ ] **Step 4: Start one optional domain profile and test it**

Run:
```bash
docker compose --parallel 1 --profile ui up -d --wait
curl -fsS http://localhost:3001/
curl -fsS http://localhost:5173/
docker stats --no-stream
```

Expected: UI endpoints return HTTP `200`; measured memory remains below Docker Desktop's configured 3.57 GiB limit.

- [ ] **Step 5: Preserve the final inspection output**

Run:
```bash
docker compose ps
docker system df
git -C SmartBank status --short --branch
git -C POS status --short --branch
git -C Marketplace status --short --branch
git -C SupplierHub status --short --branch
git -C Logistika status --short --branch
git -C Api-Integrator status --short --branch
git -C UMKM-Insight status --short --branch
```

Expected: accurate final state included in the user-facing review summary.

### Task 4: Prepare review and PR boundaries

**Files:**
- No further code changes expected

- [ ] **Step 1: Inspect diffs per repository**

Run the following in each repository that contains changed files:

```bash
git status --short --branch
git diff --check
git diff --stat
```

Expected: no whitespace errors. Keep unrelated pre-existing changes intact.

- [ ] **Step 2: Present review summary and request explicit PR confirmation**

Report changed files, Compose behavior, documentation updates, tests, known runtime failures, branch/remote availability, and exact intended PR repository/repositories. Do not commit, push, or open a GitHub PR before the user explicitly confirms the final diff and repository target.
