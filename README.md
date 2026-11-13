# Tang Kinh Cac Server

Laravel REST API for the Tang Kinh Cac Android application.

## Requirements

- PHP 8.3 or newer.
- Composer 2.
- PostgreSQL 18 for the supported local setup.
- PHP extensions required by Laravel and `pdo_pgsql` for PostgreSQL.

Confirm the database driver before setup:

```powershell
php -r "var_export(PDO::getAvailableDrivers());"
```

The output must include `pgsql`.

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
Copy-Item .env.example .env
Copy-Item .env.testing.example .env.testing
php artisan key:generate
php artisan key:generate --env=testing
```

Set `DB_PASSWORD` in both `.env` and `.env.testing` to the local role password. These files are ignored by Git.

Create the development schema and start the server:

```powershell
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8000
```

Local base URLs:

- Windows host: `http://127.0.0.1:8000/api/v1/`
- Android Emulator: `http://10.0.2.2:8000/api/v1/`
- Physical device: `http://<host-lan-ip>:8000/api/v1/`

Local HTTP is for debug builds only. Production and release builds must use HTTPS.

## Tests

Verify that `.env.testing` names `tang_kinh_cac_test`, then run:

```powershell
php artisan migrate:fresh --env=testing --force
php artisan test
```

Never run the test commands against the development or production database.

## Project documentation

- [Backend specification](docs/specification.md)
- [Architecture decision](docs/laravel_backend_architecture.md)
- [Implementation plan](docs/implementation_plan.md)
- [Coding conventions](docs/coding_conventions.md)
