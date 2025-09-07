# REST API scaffold and local testing

This repo has a minimal REST scaffold to help migrate the legacy PHP app to a REST architecture while preserving existing endpoints.

Quick start (XAMPP)

1. Ensure XAMPP Apache and MySQL are running. Place this project under `htdocs` (it already is).
2. Create the database `hr441` and import `hr4.sql`:

   - Open PowerShell and run (adjust paths as needed):

```powershell
cd 'C:\NEWXAMPP\mysql\bin'
.\mysql.exe -u root -p
CREATE DATABASE hr441 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit
.\mysql.exe -u root -p hr441 < 'C:\NEWXAMPP\htdocs\degur\hr4.sql'
```

3. Optionally set local DB environment overrides in `php/db_local.php` or set environment variables `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.

Run the Python analytics service

1. Create a virtualenv and install requirements in `python/`:

```powershell
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r python\requirements.txt
uvicorn services.analytics_api:app --reload --port 8001
```

Optional Docker Compose

Use `docker-compose.override.yml` to run a local test stack (MySQL, PHP, Python). This is optional and the app is intended to run on XAMPP.

Notes & Next steps

- The REST entrypoint is `php/rest/index.php`. Routes are basic (e.g., `?r=auth/login`).
- A lightweight JWT helper is included. Change `JWT_SECRET` environment variable in production.
- I preserved existing API files; next I'll: add more REST controllers for employees, payroll, claims, etc., update frontend to use `js/api_client.js`, and run tests.
