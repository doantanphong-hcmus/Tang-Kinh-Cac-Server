# Tang Kinh Cac Server

Laravel REST API for the Tang Kinh Cac Android application.

## Requirements

- PHP 8.5.
- Composer 2.
- PostgreSQL 18 for the supported local setup.
- PHP extensions: `dom`, `fileinfo`, `filter`, `hash`, `iconv`, `json`, `libxml`, `openssl`, `PDO`, `pdo_pgsql`, `Phar`, `session`, `tokenizer`, `xml`, and `xmlwriter`. Native `ctype` and `mbstring` are recommended; Composer provides the installed polyfills when they are unavailable.

Docker, Redis, and a queue worker are not required for local version 1 development.

Confirm that PHP exposes PostgreSQL before setup:

```powershell
php -r "var_export(PDO::getAvailableDrivers());"
```

The PDO driver output must include `pgsql`.

## Native PostgreSQL setup on Windows

Open `psql` as the PostgreSQL administrator. Use the administrator password chosen during PostgreSQL installation:

```powershell
& 'C:\Program Files\PostgreSQL\18\bin\psql.exe' -U postgres -d postgres
```

Create a non-superuser role and separate development and test databases. Replace the example password with a local-only value:

```sql
CREATE ROLE tang_kinh_cac WITH LOGIN PASSWORD 'choose-a-local-password';
CREATE DATABASE tang_kinh_cac OWNER tang_kinh_cac;
CREATE DATABASE tang_kinh_cac_test OWNER tang_kinh_cac;
```

The test database must never point to development or production data.

## Application setup

```powershell
composer install
composer check-platform-reqs
Copy-Item .env.example .env
Copy-Item .env.testing.example .env.testing
php artisan key:generate
php artisan key:generate --env=testing
```

Every platform check must report `success`.

Set `DB_PASSWORD` in both `.env` and `.env.testing` to the local role password. These files are ignored by Git.

Review these environment values before migrating:

| Variable | Development value | Purpose |
| --- | --- | --- |
| `APP_URL` | `http://127.0.0.1:8000` or the LAN URL used by Android | Base URL used when Laravel generates absolute links. |
| `DB_DATABASE` | `tang_kinh_cac` | Development database. |
| `DB_USERNAME` | `tang_kinh_cac` | Non-superuser PostgreSQL role. |
| `DB_PASSWORD` | Local role password | Keep it only in ignored environment files. |
| `MEDIA_SIGNED_URL_TTL_MINUTES` | `5` | Lifetime of signed EPUB and audio URLs. |

`.env.testing` must use `tang_kinh_cac_test`, never the development or production database.

Create the development schema and start the server:

```powershell
php artisan migrate --seed
php artisan storage:link
php artisan serve --host=0.0.0.0 --port=8000
```

The development seeder creates these local-only accounts:

| Role | Email | Password |
| --- | --- | --- |
| Reader | `reader@example.test` | `password` |
| Administrator | `admin@example.test` | `password` |

These credentials are intentionally simple and must never be used outside local development.

Local base URLs:

- Windows host: `http://127.0.0.1:8000/api/v1/`
- Android Emulator: `http://10.0.2.2:8000/api/v1/`
- Physical device: `http://<host-lan-ip>:8000/api/v1/`

For a physical device, use the Windows host's LAN address, keep the device on the same network, and allow inbound TCP port `8000` through the local firewall. Local HTTP is for Android debug builds only. Production and release builds must use an HTTPS API URL.

## File storage

- EPUB and audio binaries use the private `media` disk at `storage/app/private/media/`.
- Seeded fixtures are written under `epubs/` and `audio/` inside that directory.
- Media is delivered only through short-lived signed API URLs; storage paths are never public API fields.
- `php artisan storage:link` exposes only `storage/app/public/` as `public/storage`. It does not expose private media.
- The PHP process must be able to write to `storage/` and `bootstrap/cache/`.

Changing `MEDIA_SIGNED_URL_TTL_MINUTES` affects newly generated descriptors and track lists. Android clients must request a fresh descriptor or track list after a signed URL expires.

## Tests

Verify that `.env.testing` names `tang_kinh_cac_test`, then run:

```powershell
php artisan migrate:fresh --env=testing --force
php artisan test
```

Never run the test commands against the development or production database.

## Quality commands

```powershell
composer format
composer analyse
composer test
vendor\bin\yaml-lint openapi\openapi.yaml
```

`composer format` applies Pint formatting. Commit only after reviewing its diff. `composer test` clears cached configuration before running the PHPUnit suite.

## Project documentation

- [Backend specification](docs/specification.md)
- [Architecture decision](docs/laravel_backend_architecture.md)
- [Implementation plan](docs/implementation_plan.md)
- [Coding conventions](docs/coding_conventions.md)
- [Version 1 acceptance evidence](docs/acceptance_evidence.md)
- [OpenAPI contract](openapi/openapi.yaml)
