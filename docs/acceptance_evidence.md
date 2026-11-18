# Version 1 Acceptance Evidence

- **Specification:** [`specification.md`](specification.md), section 20
- **Verification date:** 2026-11-18
- **Environment:** Windows, PHP 8.5.12, Composer 2.8.12, PostgreSQL 18.4, Laravel 13.42.0
- **Result:** 14 of 14 acceptance criteria passed

This checklist traces each version 1 acceptance criterion to automated coverage or a recorded manual verification. Test references name the smallest focused evidence; the full suite remains the release gate.

| # | Acceptance criterion | Evidence | Result |
| --- | --- | --- | --- |
| 1 | The project installs and runs locally on Windows without Docker. | D05 clean-machine rehearsal below: a clean checkout was installed, migrated, seeded, and served using native PHP, Composer, and PostgreSQL. The health endpoint returned `ok` without Docker. | Pass |
| 2 | PostgreSQL migrations create the complete schema from an empty database. | `php artisan migrate:fresh --env=testing --force`; `CatalogMigrationTest`; `BookFileMigrationTest`; `UserTest`. | Pass |
| 3 | Seeders produce a usable development catalog and sample accounts. | `DatabaseSeederTest`; `EpubSeedTest`; `AudioSeedTest`. | Pass |
| 4 | Registration, login, current-user lookup, and logout work through Sanctum. | `RegisterTest`; `LoginTest`; `CurrentUserTest`; `LogoutTest`. | Pass |
| 5 | Published books can be listed, searched, filtered, paginated, and opened. | `BookListTest`; `BookDetailTest`; `BookSearchTest`; `GenreListTest`; `GenreBookListTest`. | Pass |
| 6 | Draft and archived books are hidden from readers. | `BookTest::test_reader_visibility_scope_returns_only_published_books`; `BookDetailTest` draft and archived cases; `BookListTest::test_returns_only_published_books_in_title_order_with_pagination_metadata`. | Pass |
| 7 | A free EPUB descriptor and signed download work end to end. | `EpubDescriptorTest`; `EpubDownloadTest`; `EpubSeedTest::test_seeded_sample_epub_downloads_end_to_end_without_exposing_storage_metadata`. | Pass |
| 8 | Free audiobook tracks can be listed and streamed with byte-range support. | `AudioTrackTest`; `AudioTrackStreamTest`; `ResolveByteRangeTest`; `AudioSeedTest`. | Pass |
| 9 | VIP content is denied with the specified error. | VIP cases in `EpubDescriptorTest`, `EpubDownloadTest`, `AudiobookDescriptorTest`, `AudioTrackTest`, and `AudioTrackStreamTest`, asserting `403 CONTENT_ACCESS_DENIED`. | Pass |
| 10 | All responses follow the documented envelopes and error codes. | `ApiConventionsTest`; endpoint feature tests; `OpenApiTest`. | Pass |
| 11 | Storage paths and secrets never appear in API responses. | `EpubDescriptorTest`; `EpubSeedTest`; `AudioTrackTest`; `OpenBookFileStreamTest`; D03 security review. Only `.env.example` and `.env.testing.example` are tracked. | Pass |
| 12 | Feature tests and code-quality checks pass. | Recorded D04 run: 199 tests and 1328 assertions; Pint, PHPStan, OpenAPI YAML lint, and `git diff --check` passed. CI repeats formatting, analysis, fresh migration, and tests in `.github/workflows/tests.yml`. | Pass |
| 13 | The OpenAPI contract matches the running implementation. | `OpenApiTest` compares every runtime API route, method, authentication requirement, parameter, response, error example, and reference with `openapi/openapi.yaml`; YAML lint passed. | Pass |
| 14 | Android can implement all four initial gateways without reading backend source code. | Manual contract review: `openapi/openapi.yaml` defines Auth, BookCatalog, BookContent, and Audiobook operations with request/response schemas and examples. `BookResourceTest`, endpoint feature tests, and `OpenApiTest` verify the documented transport shapes. | Pass |

## Recorded verification commands

```powershell
php artisan migrate:fresh --env=testing --force
composer format
composer analyse
composer test
vendor\bin\yaml-lint openapi\openapi.yaml
git diff --check
```

## D05 clean-machine rehearsal

The README procedure was rehearsed from detached commit `0b038b0` in a temporary checkout with no `vendor`, environment files, or media storage. The dedicated `tang_kinh_cac_test` database name was confirmed before its existing schema was wiped; development data was not touched.

- Composer installed all 119 locked dependencies and every platform requirement passed.
- Both application keys were generated, all 8 migrations ran from an empty database, and the seeders created 2 users, 8 books, and 3 media records.
- The private media storage was populated from empty, the public storage link was created, and the server started at the documented Windows URL.
- `GET /api/v1/health` returned `ok`.
- The README test and quality commands passed: 199 tests, 1328 assertions, Pint, PHPStan, OpenAPI YAML lint, and a clean formatter diff.

The PostgreSQL role and empty database are documented prerequisites requiring administrator access, so their operating-system installation and role creation were not repeated. The rehearsal worktree, spawned server processes, and temporary storage were removed after verification.
