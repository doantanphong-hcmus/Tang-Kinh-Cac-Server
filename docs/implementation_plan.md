# Project Plan: Tang Kinh Cac Backend v1

- **Plan status:** Proposed
- **Plan date:** 2026-11-13
- **Target start:** 2026-11-16
- **Target release candidate:** 2027-01-08
- **Source documents:** `specification.md`, `laravel_backend_architecture.md`, `coding_conventions.md`

## 1. Executive Summary

| Field | Value |
| --- | --- |
| Goal | Deliver a secure, documented Laravel REST API that fully supports the first four Android gateways: authentication, catalog, EPUB, and audiobook. |
| Scope | Greenfield backend repository using Laravel, PostgreSQL, Sanctum, private file storage, OpenAPI, migrations, seed data, and automated tests. |
| Delivery strategy | Contract-first, followed by small vertical slices that each include schema, endpoint, authorization, tests, and OpenAPI. |
| Timeline | 8 calendar weeks, from 2026-11-16 through 2027-01-08. |
| Baseline capacity | One full-time backend developer with part-time Android integration and review/testing support. |
| Release condition | Every acceptance criterion in `specification.md` passes and Android can implement the four gateways without reading backend source. |

The dates are a working baseline, not a promise independent of staffing. If only the backend developer is available for review, Android integration, and QA, keep the phase order but add contingency rather than cutting required tests or security work.

## 2. Scope and Priorities

### Must Have — v1 release scope

- Native Windows setup using PHP, Composer, and PostgreSQL without Docker.
- Versioned `/api/v1` REST API with stable success and error envelopes.
- Request identifiers, safe exception handling, rate limits, and environment-driven secrets.
- Sanctum registration, login, current-user, and current-token logout flows.
- PostgreSQL schema, constraints, migrations, factories, and deterministic seed data.
- Published-book catalog, detail, search, filters, sorting, genres, and pagination.
- Accent-insensitive and case-insensitive catalog search covered by tests.
- Private file metadata and storage abstraction for covers, EPUBs, and audio.
- Authorized, expiring EPUB descriptors and streamed downloads.
- Audiobook descriptors, ordered tracks, signed URLs, and byte-range streaming.
- VIP content denial until an entitlement system exists.
- Feature/unit test suite, formatting checks, CI, README, and complete OpenAPI 3 contract.

### Should Have — implement when it reduces delivery risk

- Static analysis at a practical level compatible with Laravel.
- Automated API contract validation in CI.
- Reusable test helpers for envelopes, signatures, and range responses.
- A small Android integration checklist or sample request collection derived from OpenAPI.

### Could Have — after v1 acceptance

- Optional Docker development environment.
- S3-compatible storage adapter and deployment automation.
- More sample catalog/media content.
- Performance/load-test baseline and operational dashboards.

### Won't Have — explicitly outside v1

- Firebase compatibility or migration.
- Website, administration dashboard, community, rankings, or analytics.
- Reading/listening progress synchronization.
- Payments, subscriptions, ownership, or VIP entitlements.
- Redis, WebSockets, real-time events, microservices, or a separate search service.
- EPUB parsing, DRM, or audio transcoding.

## 3. Team Allocation

| Role | Person | Primary responsibilities | Planned load |
| --- | --- | --- | --- |
| Backend owner | To assign | Architecture, implementation, migrations, storage, tests, OpenAPI, CI, and local operations | 100% |
| Android integrator | To assign | Validate DTOs, gateway usability, token handling, signed downloads, and audio seeking | 10–20% at phase gates |
| Reviewer / QA | To assign | Security review, acceptance tests, negative cases, and release sign-off | 10–20% from Week 2 |

If only one person is available, that person owns all rows, but review and acceptance activities must remain separate checklist items.

## 4. Delivery Principles

1. **OpenAPI and code move together.** An endpoint is incomplete until schema, examples, error responses, and tests agree.
2. **Build vertical slices.** Complete database-to-response behavior for one capability before expanding the next.
3. **Enforce rules twice where appropriate.** Application validation provides useful errors; database constraints protect integrity.
4. **Keep storage private.** Clients receive identifiers and signed URLs, never storage paths.
5. **Test failure paths early.** Authentication, visibility, access denial, expired signatures, and invalid ranges are core behavior.
6. **Defer abstractions until repeated behavior exists.** Do not create generic repositories, services, or response hierarchies.

## 5. Phase Breakdown

### Phase 1: Foundation and API Contract — Week 1

**Milestone M1: Runnable skeleton — 2026-11-20**

| ID | Task | Description | Owner | Effort | Depends on | Deadline |
| --- | --- | --- | --- | --- | --- | --- |
| F01 | Bootstrap Laravel | Create the current stable Laravel application and required repository layout. | Backend | 0.5 d | — | Nov 16 |
| F02 | Configure PostgreSQL | Add environment template, PostgreSQL connection, isolated test configuration, and local database instructions. | Backend | 0.75 d | F01 | Nov 17 |
| F03 | Install core tooling | Add Sanctum, formatter, test runner, and practical static analysis configuration. | Backend | 0.75 d | F01 | Nov 17 |
| F04 | Establish API conventions | Implement `/api/v1`, JSON negotiation, response envelopes, stable error mapping, and request IDs. | Backend | 1.25 d | F01 | Nov 19 |
| F05 | Health endpoint | Add public health endpoint without infrastructure disclosure, with feature tests and OpenAPI. | Backend | 0.5 d | F04 | Nov 19 |
| F06 | CI baseline | Install dependencies, validate formatting, run analysis, migrate a test database, and execute tests. | Backend | 0.75 d | F02, F03 | Nov 20 |
| F07 | Foundation review | Verify clean Windows setup commands and the M1 exit criteria. | Reviewer | 0.5 d | F05, F06 | Nov 20 |

**Exit criteria**

- A clean checkout installs and serves locally on Windows without Docker.
- `/api/v1/health` returns the specified envelope.
- Representative 404, validation, authentication, and unexpected errors use the stable error structure with `request_id`.
- CI passes from an empty test database.

### Phase 2: Identity and Authentication — Week 2

**Milestone M2: Auth gateway complete — 2026-11-27**

| ID | Task | Description | Owner | Effort | Depends on | Deadline |
| --- | --- | --- | --- | --- | --- | --- |
| A01 | User schema and model | Add normalized identity fields, role, constraints, factories, and Sanctum token storage. | Backend | 1.0 d | F02 | Nov 23 |
| A02 | Registration | Validate and normalize fields, detect conflicts, create transactionally, and return no token. | Backend | 1.0 d | A01, F04 | Nov 24 |
| A03 | Login and token issuance | Add generic invalid-credential behavior, device name, rate limiting, and secure token response. | Backend | 0.75 d | A01 | Nov 25 |
| A04 | Current user and logout | Protect routes, return the current user, and revoke only the active token. | Backend | 0.5 d | A03 | Nov 25 |
| A05 | Auth test matrix | Cover normalization, uniqueness, validation, throttling, revoked tokens, non-enumeration, and secret non-disclosure. | Backend | 1.0 d | A02–A04 | Nov 26 |
| A06 | Contract integration review | Validate AuthGateway request/response shapes with Android and update OpenAPI. | Backend + Android | 0.75 d | A05 | Nov 27 |

**Exit criteria**

- All four AuthGateway operations pass happy-path and negative-path feature tests.
- Passwords never appear in responses or logs; tokens appear only in successful login responses.
- Android confirms it can map the contract without Laravel-specific types.

### Phase 3: Catalog Data Foundation — Week 3

**Milestone M3: Seeded relational catalog — 2026-12-04**

| ID | Task | Description | Owner | Effort | Depends on | Deadline |
| --- | --- | --- | --- | --- | --- | --- |
| C01 | Catalog migrations | Add authors, books, genres, pivot, alternative titles, keywords, indexes, and explicit delete behavior. | Backend | 1.25 d | F02 | Dec 1 |
| C02 | Catalog models | Add relationships, casts/enums, visibility scopes, factories, and integrity tests. | Backend | 1.0 d | C01 | Dec 2 |
| C03 | Search foundation | Enable `unaccent` or normalized searchable fields and prove accent/case-insensitive matching. | Backend | 1.0 d | C01 | Dec 3 |
| C04 | Seed catalog | Seed reader/admin accounts, eight books, authors, genres, titles, keywords, and a VIP book deterministically. | Backend | 1.0 d | A01, C02 | Dec 4 |
| C05 | Data review | Verify constraints, idempotent development seeding, copyrights, and source-document mapping. | Reviewer | 0.5 d | C03, C04 | Dec 4 |

**Exit criteria**

- Migrations create the relational schema from an empty PostgreSQL database.
- Database constraints reject invalid uniqueness and foreign-key states.
- Required catalog seed records exist and use only project-owned or redistributable material.

### Phase 4: Catalog API — Week 4

**Milestone M4: BookCatalogGateway complete — 2026-12-11**

| ID | Task | Description | Owner | Effort | Depends on | Deadline |
| --- | --- | --- | --- | --- | --- | --- |
| C06 | Book resources | Define stable list/detail resources, author/genre nesting, flags, URLs, and decimal formatting. | Backend | 0.75 d | C02, F04 | Dec 7 |
| C07 | Book list | Add authenticated search, filters, deterministic sorts, visibility, and bounded pagination. | Backend | 1.25 d | C03, C06 | Dec 8 |
| C08 | Book detail | Resolve numeric ID or slug and hide missing, draft, and archived books consistently. | Backend | 0.75 d | C06 | Dec 9 |
| C09 | Genre endpoints | Return active genres with published counts and paginated visible books. | Backend | 0.75 d | C07 | Dec 10 |
| C10 | Catalog test matrix | Cover filters, combinations, accents, sorts, pagination, visibility, counts, and not-found behavior. | Backend | 1.0 d | C07–C09 | Dec 10 |
| C11 | Contract integration review | Validate BookCatalogGateway DTOs and examples with Android; finalize OpenAPI section. | Backend + Android | 0.5 d | C10 | Dec 11 |

**Exit criteria**

- Reader accounts see only published books.
- Search, filters, sort, and pagination are deterministic and tested.
- Android validates the complete catalog gateway contract.

### Phase 5: EPUB Content Delivery — Week 5

**Milestone M5: EPUB flow complete — 2026-12-18**

| ID | Task | Description | Owner | Effort | Depends on | Deadline |
| --- | --- | --- | --- | --- | --- | --- |
| E01 | File metadata schema | Add `book_files`, type-specific validation, checksum metadata, private paths, indexes, and delete restrictions. | Backend | 1.0 d | C01 | Dec 14 |
| E02 | Storage service boundary | Configure local private storage and minimal focused actions for selection, signing, and streaming. | Backend | 0.75 d | E01 | Dec 15 |
| E03 | EPUB descriptor | Enforce publication/access rules and return version, size, checksum, signed URL, and expiry. | Backend | 0.75 d | E02, C08 | Dec 16 |
| E04 | EPUB download | Validate signature/expiry and stream with safe filename, length, content type, and ETag headers. | Backend | 1.0 d | E03 | Dec 17 |
| E05 | EPUB seed and tests | Add a redistributable sample and cover missing, VIP, modified/expired signature, bytes, headers, and path secrecy. | Backend | 1.0 d | E04 | Dec 18 |
| E06 | Android download review | Validate checksum, expiry, download, and app-private storage assumptions. | Backend + Android | 0.5 d | E05 | Dec 18 |

**Exit criteria**

- A free EPUB downloads end to end without loading the full file into PHP memory.
- VIP and invalid-signature paths return the specified stable errors.
- No API response reveals a storage disk or path.

### Phase 6: Audiobook Delivery — Week 6

**Milestone M6: AudiobookGateway complete — 2026-12-25**

| ID | Task | Description | Owner | Effort | Depends on | Deadline |
| --- | --- | --- | --- | --- | --- | --- |
| AU01 | Audiobook descriptor | Return availability, book metadata, total duration, and track count under publication/access rules. | Backend | 0.75 d | E01, C08 | Dec 21 |
| AU02 | Track list | Return active tracks ordered by `sort_order` with signed media URLs and expiry. | Backend | 0.75 d | AU01, E02 | Dec 22 |
| AU03 | Range parser and stream | Implement valid full/range responses, `206`, range headers, seek behavior, and bounded streaming. | Backend | 1.5 d | AU02 | Dec 23 |
| AU04 | Audio seed and test matrix | Add two redistributable tracks and test ordering, bytes, ranges, invalid ranges, signatures, missing content, and VIP denial. | Backend | 1.25 d | AU03 | Dec 24 |
| AU05 | Android playback review | Verify OkHttp seeking/stream behavior and finalize audiobook OpenAPI contracts. | Backend + Android | 0.5 d | AU04 | Dec 25 |

**Exit criteria**

- Android can list and seek within two ordered sample tracks.
- Valid range requests return correct `206` headers and bytes.
- Audio is streamed without reading the entire object into PHP memory.

### Phase 7: Contract, Documentation, and End-to-End Verification — Week 7

**Milestone M7: Feature-complete beta — 2027-01-01**

| ID | Task | Description | Owner | Effort | Depends on | Deadline |
| --- | --- | --- | --- | --- | --- | --- |
| D01 | Complete OpenAPI | Reconcile every endpoint, schema, parameter, status, stable code, and example with implementation. | Backend | 1.0 d | M2, M4–M6 | Dec 29 |
| D02 | Complete README | Document prerequisites, native PostgreSQL, setup, seed accounts, commands, storage, and all Android base URLs. | Backend | 0.75 d | M1, D01 | Dec 29 |
| D03 | Cross-cutting security review | Inspect authorization, rate limits, logs, CORS, secrets, path handling, validation, and production error behavior. | Reviewer | 1.0 d | M2, M4–M6 | Dec 30 |
| D04 | Full acceptance suite | Trace every specification acceptance criterion to an automated test or recorded manual verification. | Backend + QA | 1.0 d | D01–D03 | Dec 31 |
| D05 | Clean-machine rehearsal | Rebuild from empty database and storage using README only; run all quality commands. | QA | 0.75 d | D02, D04 | Jan 1 |

**Exit criteria**

- OpenAPI matches the observed running API.
- A clean setup succeeds using only the documented commands.
- Every acceptance criterion has evidence and no unresolved critical security finding.

### Phase 8: Hardening and Release Candidate — Week 8

**Milestone M8: v1 release candidate — 2027-01-08**

| ID | Task | Description | Owner | Effort | Depends on | Deadline |
| --- | --- | --- | --- | --- | --- | --- |
| R01 | Fix beta findings | Resolve integration, contract, correctness, and security defects; add regression tests. | Backend | 2.0 d | M7 | Jan 6 |
| R02 | Performance sanity checks | Verify bounded pagination, query counts, indexes, streaming memory, and representative catalog response time. | Backend | 0.75 d | R01 | Jan 6 |
| R03 | Final Android smoke test | Exercise all four gateways against a seeded release-candidate instance. | Android + Backend | 0.75 d | R01 | Jan 7 |
| R04 | Release audit | Run migrations, seed, formatting, analysis, full tests, OpenAPI validation, and secret scan. | Backend + Reviewer | 0.75 d | R02, R03 | Jan 8 |
| R05 | Sign-off | Record known limitations, release notes, rollback/backup assumptions, and acceptance decision. | Project owner | 0.5 d | R04 | Jan 8 |

**Exit criteria**

- All required quality commands pass on the release candidate.
- No open severity-critical/high defect remains.
- Android completes smoke tests for Auth, Catalog, EPUB, and Audiobook gateways.
- The project owner accepts all v1 criteria or records an explicit exception.

## 6. Dependency Graph

```text
F01 Bootstrap
 ├─> F02 PostgreSQL ────────────────┬─> A01 Users ─> Auth Gateway (M2)
 │                                  └─> C01 Catalog schema ─> C02 Models
 ├─> F03 Tooling ─> F06 CI                              │
 └─> F04 API conventions ─> F05 Health                  ├─> C03 Search
                                                        ├─> C04 Seed
                                                        └─> Catalog API (M4)
                                                             │
                                   C01 ─> E01 File schema ───┼─> EPUB (M5)
                                                             └─> Audiobook (M6)

M2 + M4 + M5 + M6 ─> OpenAPI/README/Security/Acceptance (M7)
M7 ─> Fixes + Performance + Android smoke test ─> Release candidate (M8)
```

The critical path is foundation → catalog schema → catalog visibility → file metadata/storage → audio range streaming → acceptance → release audit.

## 7. Milestone Timeline

| Week | Dates | Focus | Deliverable |
| --- | --- | --- | --- |
| 1 | Nov 16–20 | Foundation | Runnable API skeleton, conventions, health, and CI |
| 2 | Nov 23–27 | Authentication | Complete AuthGateway contract |
| 3 | Nov 30–Dec 4 | Relational catalog | Migrations, constraints, search foundation, and seeds |
| 4 | Dec 7–11 | Catalog API | Complete BookCatalogGateway contract |
| 5 | Dec 14–18 | EPUB | Signed descriptor and streamed download |
| 6 | Dec 21–25 | Audiobook | Ordered tracks and byte-range streaming |
| 7 | Dec 28–Jan 1 | Integration | Complete contract, docs, security, and acceptance evidence |
| 8 | Jan 4–8 | Hardening | Android smoke test and signed-off release candidate |

## 8. Verification Strategy

### Automated checks on every change

- Formatting and static-analysis checks.
- Unit tests for normalization, signing/access helpers, and range parsing where isolated logic exists.
- Feature tests against PostgreSQL for all endpoint behavior and database constraints.
- Fresh migration of the test database.
- OpenAPI syntax/contract validation once configured.

### Required endpoint test matrix

Each applicable endpoint must cover success, missing authentication, invalid/revoked token, validation failure, not found, access denial, stable envelope/code, and correct status. Authentication endpoints additionally cover throttling; media endpoints additionally cover expiry, signature modification, headers, exact bytes, and bounded streaming.

### Phase-gate evidence

- CI run URL or recorded local command output.
- OpenAPI operation and example for each completed endpoint.
- Android confirmation at M2, M4, M5, M6, and M8.
- Acceptance-criteria checklist completed at M7 and re-run at M8.

## 9. Risks and Mitigations

| Risk | Impact | Mitigation / trigger |
| --- | --- | --- |
| PHP/Laravel version mismatch on Windows | Setup delay | Pin supported versions in `composer.json` and README; prove clean setup in Week 1. |
| Accent-insensitive search differs by PostgreSQL environment | Incorrect results or migration failures | Choose `unaccent` or normalization in Week 3, migrate it explicitly, and test Vietnamese accented/unaccented pairs. |
| Signed URLs accidentally expose private paths | Security breach | Sign opaque IDs/routes only; add response assertions that disks and paths never appear. |
| Audio range implementation has off-by-one or memory defects | Broken seeking or server pressure | Isolate range parsing, test first/middle/suffix/invalid ranges and exact bytes, inspect peak memory. |
| Seed media has unclear copyright | Repository/legal risk | Use project-owned or clearly redistributable tiny fixtures and record provenance. |
| OpenAPI drifts from implementation | Android integration churn | Update it in the same change as each endpoint and perform explicit reconciliation in Week 7. |
| One-person bottleneck reduces independent review | Escaped defects | Use checklist-driven self-review, CI gates, and reserve Week 8 entirely for hardening. |
| Scope expands into deferred domains | Schedule failure | Treat specification v1 as the change-control baseline; move new domains to a later versioned plan. |
| Storage works locally but not with future object storage | Rework | Use Laravel filesystem APIs and streaming interfaces; avoid local-path assumptions in controllers. |
| Late Android integration reveals unusable DTOs | Contract rework | Hold gateway reviews at the end of every related phase, not only at release. |

## 10. Definition of Done

A task is done only when:

- Its implementation follows the accepted architecture and `coding_conventions.md`, and contains no unrelated abstraction.
- Validation, authorization, database constraints, and safe errors are present where applicable.
- Automated happy-path and negative-path tests pass against PostgreSQL.
- OpenAPI and examples are updated in the same change.
- No secret, token, private storage path, copyrighted production file, or sensitive log data is introduced.
- Formatting, analysis, and the relevant test suite pass in CI.

A milestone is done only when its exit criteria have been demonstrated. The v1 project is done only when all acceptance criteria in `specification.md` pass and the four Android gateways have completed their integration smoke tests.

## 11. Change Control

- The approved specification and architecture decision are the scope baseline.
- Any new endpoint, domain, external service, or breaking response change requires written impact analysis before entering the active milestone.
- Non-breaking optional fields may be added only with OpenAPI and test updates.
- Breaking changes require a new API version.
- Schedule changes should preserve the dependency order and release gates; required security, test, and documentation work must not be silently removed to recover dates.
