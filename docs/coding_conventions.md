# Coding Conventions: Tang Kinh Cac Backend

- **Status:** Required
- **Version:** 1.0
- **Date:** 2026-11-13
- **Applies to:** All project-owned backend code, tests, migrations, seeders, configuration, and API documentation

## 1. Purpose

This document defines the minimum coding standard for the Tang Kinh Cac backend. Its goals are to keep the code safe, predictable, and understandable by a new maintainer without requiring excessive ceremony.

These rules use the following terms:

- **Must / must not:** mandatory; violations block review.
- **Should / should not:** the default; deviations need a clear reason in the review.
- **May:** optional.

When rules conflict, use this priority order:

1. Security and correctness.
2. `specification.md` and the OpenAPI contract.
3. `laravel_backend_architecture.md`.
4. This convention.
5. Existing local style.

## 2. General Principles

- Write the smallest implementation that completely satisfies the current requirement.
- Prefer clear framework features over custom abstractions.
- Optimize for the next reader, not for the fewest characters.
- Keep business rules on the server and test them at their trust boundaries.
- Make invalid states difficult to store by combining validation with database constraints.
- Do not add speculative extension points, generic base classes, or configuration “for later.”
- A non-trivial change is incomplete without an appropriate automated test.

## 3. PHP and Formatting

- Use the PHP version supported by the selected Laravel release and pin it in `composer.json`.
- Every project-owned PHP file must declare strict types immediately after the opening tag:

```php
<?php

declare(strict_types=1);
```

- Follow PSR-12 and the Laravel formatter configured by the repository.
- Formatting must be performed automatically; do not debate formatting in review.
- Use typed parameters, return values, properties, and backed enums where they make invalid values harder to express.
- Use `final` for a class only when inheritance is deliberately unsupported, not mechanically on every class.
- Prefer early returns to deeply nested conditionals.
- Use braces for all control structures, including one-line branches.
- Do not suppress errors or static-analysis findings without a nearby explanation.
- Do not use debug helpers such as `dd`, `dump`, `var_dump`, or `print_r` in committed code.

## 4. Naming

- Names must describe domain intent, not implementation trivia.
- Classes and enums use `PascalCase`; methods, properties, and variables use `camelCase`.
- Database columns and JSON fields use `snake_case` as required by the API contract.
- Boolean names should read as a fact or question: `isActive`, `hasAudiobook`, `canDownload`.
- Collections use plural names; a single model or value uses a singular name.
- Avoid unexplained abbreviations. Established terms such as `id`, `url`, `api`, `epub`, and `sha256` are allowed.
- Action classes should use a verb and domain object, for example `IssueAccessToken` or `CreateSignedMediaUrl`.
- Test names must state observable behavior, for example `reader_cannot_view_archived_books`.
- Stable error codes use uppercase `SNAKE_CASE`, for example `CONTENT_ACCESS_DENIED`.

## 5. File and Class Organization

- One primary class, interface, trait, or enum per PHP file.
- The file name must match the primary symbol.
- Keep methods focused. If a method performs unrelated stages, split it by responsibility rather than by arbitrary line count.
- Keep related private methods near the public method that uses them when practical.
- Use these namespaces for HTTP code:

```text
App\Http\Controllers\Api\V1
App\Http\Requests\Api\V1
App\Http\Resources\Api\V1
```

- Controllers coordinate HTTP concerns only: accept validated input, call the relevant model/action, and return a Resource or response.
- Form Requests own request validation and request-level authorization.
- API Resources own public response shapes. Never return an Eloquent model directly.
- Eloquent models own relationships, casts, and small model-specific queries; they must not become general service containers.
- Introduce an action or service only for real workflow logic, transactions, authorization coordination, signing, or storage interaction.
- Do not create generic `BaseRepository`, `BaseService`, controller, Resource, or response-wrapper hierarchies.
- Route closures must not contain business logic.

## 6. Comment Policy

Comments are required when they preserve information that correctly written code cannot express on its own. They explain **why, intent, constraints, or risk**—not a line-by-line translation of the code.

### 6.1 Comments are mandatory for

1. **Non-obvious business rules** — especially publication visibility, VIP denial, token revocation, file selection, and media authorization.
2. **Security-sensitive decisions** — signature validation order, information deliberately hidden from a response, normalization used for uniqueness, or protection against traversal/enumeration.
3. **Invariants and ordering requirements** — behavior that depends on transactions, locks, database constraints, deterministic sorting, or operations occurring in a specific order.
4. **Algorithms and protocol details** — range boundary calculations, checksum behavior, normalization rules, regular expressions, or unusual query construction.
5. **Workarounds and surprising framework behavior** — state the reason, the limitation, and when the workaround can be removed.
6. **Meaningful groups of related functions** — when several private methods form one conceptual stage and that grouping is not already obvious from the class or method names, add one short heading comment before the group.
7. **Intentional trade-offs or omissions** — explain why the simpler or less general choice is correct for the current requirements.

Examples:

```php
// Normalize before the transaction so conflict errors match the values protected by unique indexes.
$identity = $this->normalizer->normalize($request->validated());
```

```php
// Validate expiry before resolving file metadata to avoid leaking whether a private file exists.
if ($this->signatureHasExpired($expiresAt)) {
    throw InvalidMediaSignature::expired();
}
```

```php
// RFC 9110 byte-range parsing
private function parseRange(string $header, int $fileSize): ByteRange
{
    // The HTTP range end is inclusive; storage stream lengths are counts.
    $length = $end - $start + 1;
    // ...
}
```

```php
// Signed media URL verification

private function signatureHasExpired(CarbonImmutable $expiresAt): bool
{
    // ...
}

private function signatureMatches(string $signature): bool
{
    // ...
}
```

### 6.2 PHPDoc is required when

- A public or protected API has behavior, side effects, exceptions, or a contract that its signature cannot communicate.
- Static analysis needs a precise generic, array shape, callback signature, or collection element type.
- A framework hook has important inputs or behavior that are otherwise difficult to discover.

PHPDoc must not repeat native types or restate the method name.

Good:

```php
/**
 * @return Collection<int, Book>
 */
private function publishedBooks(): Collection
```

Unhelpful and therefore prohibited:

```php
/**
 * Get the user ID.
 *
 * @return int The user ID.
 */
public function getUserId(): int
```

### 6.3 Comments must not

- Restate obvious code, for example `// Increment count` above `$count++`.
- Narrate every line or every branch.
- Compensate for vague naming or an oversized method; improve the code first.
- Preserve deleted code. Version control already does that.
- Claim future behavior that does not exist.
- Contain secrets, bearer tokens, private storage paths, personal data, or production credentials.
- Use decorative banners or large separator blocks that add no domain meaning.

### 6.4 Maintenance markers

- `TODO` must reference a tracked issue and state the missing outcome:

```php
// TODO(#123): Replace the local signed route after object storage is selected.
```

- A deliberately accepted shortcut must state its limit and upgrade condition:

```php
// shortcut: local disk only for v1; introduce an object-storage adapter when deployment requires it.
```

- Bare `TODO`, `FIXME`, `HACK`, and commented-out code must not be merged.
- When behavior changes, related comments and PHPDoc must change in the same commit. An incorrect comment is a defect.

### 6.5 Review enforcement

A reviewer must reject code when:

- Non-obvious business, security, protocol, or ordering logic lacks an intent comment.
- A cluster of related helper methods is difficult to navigate and lacks either a clearer extraction/name or a concise group comment.
- A comment merely repeats the code, is stale, or hides poor naming.
- A workaround or shortcut lacks its limitation and removal condition.

There is no required comment count. Compliance is judged by whether a maintainer can understand the reason behind consequential logic without reverse-engineering it from tests or framework internals.

## 7. Laravel and Domain Rules

- Validate all untrusted input using Form Requests or an equally explicit boundary.
- Normalize identity values on the server before checking uniqueness or persistence.
- Use Laravel hashing facilities for passwords and Sanctum for personal access tokens.
- Protected endpoints must use `auth:sanctum` and explicit resource/content authorization.
- Use transactions when a workflow must succeed or fail as one unit.
- Prefer Eloquent or the query builder when they express the query clearly and safely.
- Raw SQL requires a specific need, parameter binding, a rationale comment, and focused tests.
- Use explicit foreign-key delete behavior. File deletion must never occur implicitly through a database cascade.
- Use enums or validated constants for role, book status, access level, and file type.
- Read secrets and environment-specific behavior through configuration, not direct `env()` calls outside configuration files.
- Never rely on Android or Room to enforce server-side business rules.

## 8. API Conventions

- All application routes live under `/api/v1`.
- JSON and database fields use `snake_case`; timestamps use ISO 8601 UTC.
- Return the documented success envelope, error envelope, pagination metadata, status code, and stable error code.
- Error messages must be safe for clients; production responses must not expose exceptions, SQL, paths, or internal class names.
- Every error response must contain the current request identifier.
- Never expose Eloquent serialization, internal foreign keys, storage disks, or storage paths accidentally.
- Breaking contract changes require a new API version. Optional fields require matching tests and OpenAPI updates.
- Endpoint code, feature tests, and OpenAPI changes must be delivered together.

## 9. Storage and Streaming

- Store file metadata in PostgreSQL and binary content through Laravel's filesystem abstraction.
- Client-provided paths must never be passed to the filesystem.
- Resolve files using authorized database identifiers and server-owned metadata.
- Calculate and persist checksums when files enter storage, not on every request.
- Signed URLs must be short-lived, opaque with respect to storage, and tested for modification and expiry.
- EPUB and audio responses must stream bounded chunks; do not read a complete media file into PHP memory.
- Range calculations must be isolated and tested for full, open-ended, suffix, boundary, unsatisfiable, and malformed ranges.

## 10. Database and Migrations

- Every schema change must use a committed migration; manual production schema changes are prohibited.
- Migrations must define indexes, uniqueness, nullability, and foreign-key behavior explicitly.
- Use meaningful table and column names consistent with the specification.
- Do not edit an already-deployed migration to change production history; create a new migration.
- Development seeders must be deterministic enough for repeatable testing and must use only redistributable or project-owned media.
- Factories should create valid defaults and expose states such as `published`, `archived`, `vip`, or `withEpub` where tests benefit.

## 11. Error Handling and Logging

- Throw or return domain-specific failures at the point where their meaning is known; map them to HTTP errors centrally.
- Do not catch an exception only to ignore it or return a generic success.
- Unexpected failures must be logged with request ID, route, status, and authenticated user ID when available.
- Logs must not contain passwords, bearer tokens, submitted credentials, private file bytes, or unnecessary personal data.
- Authentication failures must not reveal whether an email exists.
- A fallback path that changes correctness or security must never fail silently.

## 12. Testing Conventions

- Use feature tests for endpoint contracts, middleware, authorization, database behavior, and streaming headers.
- Use unit tests for isolated non-trivial logic such as normalization, signing helpers, or byte-range parsing.
- Test names describe behavior; test bodies follow Arrange–Act–Assert without mandatory section comments when the phases are already clear.
- Each test should prove one behavior, while a small set of closely related assertions on one response is acceptable.
- Avoid mocking Eloquent when a database-backed feature test better represents the behavior.
- Tests must be isolated, deterministic, and independent of execution order or production services.
- Every bug fix must include a regression test that fails before the fix.
- Security-sensitive and branching logic must cover meaningful failure paths, not only the happy path.

## 13. Dependencies and Configuration

- Add a dependency only when Laravel, PHP, or existing packages cannot solve the requirement clearly and safely with a small amount of code.
- Pin dependency constraints deliberately and commit the lock file.
- Document any required PHP extension or external service in the README and CI configuration.
- Configuration keys must have safe development defaults where possible and be documented in `.env.example`.
- Never commit `.env`, credentials, private keys, production media, or machine-specific configuration.

## 14. Git and Review Hygiene

- Keep commits focused and leave the repository in a testable state.
- Do not mix unrelated formatting or refactoring into a feature change.
- Commit messages should state the observable outcome, for example `Add signed EPUB download endpoint`.
- Generated files, local databases, logs, caches, and private media must be excluded from version control.
- Review the complete affected behavior, not only the changed lines.

## 15. Automated Enforcement

CI must run at least:

1. Dependency installation from the lock file.
2. Laravel formatter check.
3. Static analysis at the configured level.
4. Migration of a fresh PostgreSQL test database.
5. Unit and feature tests.
6. OpenAPI validation once the contract file exists.
7. A check that committed code contains no debug calls, forbidden bare maintenance markers, or known secret files.

Comment quality cannot be reliably measured by comment count or a linter. It is enforced through the review rules in section 6.5 and the checklist below.

## 16. Pull Request Checklist

- [ ] The change is within the approved scope and uses the smallest clear design.
- [ ] Names and structure make straightforward behavior understandable without comments.
- [ ] Consequential business, security, protocol, invariant, and workaround logic explains **why** where needed.
- [ ] Related helper groups are either self-evident, extracted clearly, or introduced by a concise group comment.
- [ ] No comment restates code, has become stale, or contains sensitive information.
- [ ] Validation, authorization, database constraints, and error mapping are present where applicable.
- [ ] Tests cover success and meaningful failure paths.
- [ ] OpenAPI, examples, migrations, seeders, and README are updated where affected.
- [ ] Formatter, static analysis, migrations, tests, and contract checks pass.
- [ ] No debug code, bare TODO/FIXME/HACK marker, secret, or private storage path is committed.

## 17. Definition of Convention Compliance

A change complies with this convention when automated checks pass and a reviewer can answer the following without reverse-engineering hidden assumptions:

- What responsibility does this code own?
- Which business or security rules does it enforce?
- Why does any non-obvious ordering, algorithm, workaround, or trade-off exist?
- What proves the success and failure behavior?
- Does the public API still match the OpenAPI contract?

If the code explains the mechanism and comments preserve the reasoning, the change is ready for review.
