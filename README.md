# Bayambang GIS Mapping System

Laravel-based GIS management system for barangay boundaries, municipal boundary data, map layers, critical facilities, DRRM assets, and user access control.

## Features

- Public interactive map with barangay search, selection, profiles, layer toggles, and municipal boundary display.
- Admin map for staff/admin users with barangay selection, feature overlays, basemaps, identify tools, and measurement tools.
- Barangay CRUD with boundary drawing/editing, visibility controls, demographic fields, land-use fields, and hazard metadata.
- Bulk boundary upload with preview/confirm flow for GeoJSON/JSON and Shapefile ZIP files.
- Map feature management for points, polylines, and polygons by layer type.
- Layer type management for icons, colors, categories, and geometry types.
- Jetstream/Fortify authentication with iHRIS login integration.
- Role-based access using Spatie Permission.
- API token scaffolding through Laravel Sanctum/Jetstream.

## Stack

- PHP 8.3+
- Laravel 13
- Laravel Jetstream, Fortify, Livewire, Sanctum
- Spatie Laravel Permission
- Spatie Laravel Activitylog
- Vite 8
- Tailwind CSS 4
- Leaflet-based map views

## Requirements

- PHP 8.3 or newer
- Composer
- Node.js and npm
- SQLite for local development, or another Laravel-supported database
- PHP extensions commonly required by Laravel, plus `zip` for Shapefile ZIP processing

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

For local development, use:

```bash
composer run dev
```

That starts the Laravel server, queue listener, log tail, and Vite dev server together.

You can also run the services separately:

```bash
php artisan serve
npm run dev
```

## Environment

The default `.env.example` uses SQLite:

```env
DB_CONNECTION=sqlite
```

If using SQLite, create the database file before migrating if it does not exist:

```bash
touch database/database.sqlite
php artisan migrate --seed
```

Important iHRIS settings:

```env
IHRIS_API_BASE_URL=https://testihris.bayambang.gov.ph/api
IHRIS_LOGIN_ENDPOINT=login
IHRIS_USERNAME_FIELD=email
IHRIS_ALLOWED_OFFICES="BDRRMC|MDRRMO|Municipal Disaster Risk Reduction Management Office"
IHRIS_ADMIN_OVERRIDE_EMAILS=
IHRIS_SUPER_ADMIN_EMAILS=
IHRIS_OFFICE_UUID=
IHRIS_TIMEOUT=10
```

For local testing without a live iHRIS account, enable the configured test login:

```env
IHRIS_TEST_LOGIN_ENABLED=true
IHRIS_TEST_LOGIN_EMAIL=test@bdrrmc.local
IHRIS_TEST_LOGIN_PASSWORD=password
IHRIS_TEST_LOGIN_NAME="BDRRMC Test Admin"
```

Set `IHRIS_SUPER_ADMIN_EMAILS` to the email that should receive `super-admin` access on login.

Activity logging is handled by `spatie/laravel-activitylog`. The package writes audit records to the `activity_log` table by default.

```env
ACTIVITY_LOGGER_ENABLED=true
ACTIVITY_LOGGER_TABLE_NAME=activity_log
```

## Roles And Access

The application uses three roles:

- `staff`: can access the admin map and feature management.
- `admin`: can manage map data, uploads, barangays, and layer types.
- `super-admin`: can manage users and roles in addition to admin capabilities.

Access summary:

| Area | Staff | Admin | Super Admin |
| --- | --- | --- | --- |
| `/admin/map` | Yes | Yes | Yes |
| `/admin/features` | Yes | Yes | Yes |
| `/admin/uploads` | No | Yes | Yes |
| `/admin/barangays` | No | Yes | Yes |
| `/admin/layer-types` | No | Yes | Yes |
| `/admin/users` | No | No | Yes |

During iHRIS login, allowed office users are created or updated locally. Emails in `IHRIS_SUPER_ADMIN_EMAILS` receive the `super-admin` role. Other allowed users receive `admin` unless they already have `staff` or `admin`.

## Seed Data

`php artisan migrate --seed` creates:

- Sample user: `test@example.com`
- Default map layer types:
  - Barangay Hall
  - Health Center
  - Multi-purpose Bldg
  - Covered Court
  - Police/Tanod Post
  - Evacuation Center
  - BERT Responder
  - Road Network
  - Density Zone
  - Household
- Sample barangays:
  - Tococ East
  - Talibaew
- Sample map features for the seeded barangays

## Upload Formats

Admin uploads accept files up to 50 MB each.

Supported formats:

- `.geojson`
- `.json`
- `.zip` containing Shapefile files

For Shapefile ZIP uploads, the archive must contain at least:

- `.shp`
- `.dbf`

GeoJSON uploads may be a `FeatureCollection` or a single `Feature`.

The importer tries to match barangay names using common fields:

```text
NAME_3, NAME_4, BGY_NAME, BRGY_NAME, BARANGAY, NAME, BRGY, ADM4_EN
```

The importer can also extract common attributes:

```text
population, total_area, hazard_level, land_use,
agri_area, residential_area, commercial_area,
unidentified_area, description
```

Municipal boundary records are detected when the feature name is `Bayambang` or contains text like `Bayambang boundary`, `Bayambang municipal`, or `municipal boundary`.

## Main Routes

Public:

- `/`
- `/api/barangays`
- `/api/barangays/{barangay}/features`

Authenticated:

- `/dashboard`
- `/admin/map`
- `/admin/features`
- `/admin/uploads`
- `/admin/barangays`
- `/admin/layer-types`
- `/admin/users`

Sanctum API:

- `/api/user`

## Testing

Run the PHP test suite:

```bash
php artisan test
```

Or through Composer:

```bash
composer test
```

Build frontend assets:

```bash
npm run build
```

Current verified baseline:

- `php artisan test` passes
- `npm run build` passes

## Deployment Checklist

- Set production `.env` values.
- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Configure the production database.
- Configure iHRIS API URL, office UUID, allowed offices, and super-admin emails.
- Run `composer install --no-dev --optimize-autoloader`.
- Run `npm ci` and `npm run build`.
- Run `php artisan migrate --force`.
- Run `php artisan config:cache`.
- Run `php artisan route:cache`.
- Run `php artisan view:cache`.
- Configure queue worker if background jobs are enabled.
- Configure web server document root to `public/`.

## Useful Commands

```bash
php artisan migrate:fresh --seed
php artisan test
npm run build
composer run dev
php artisan route:list
```

## Notes For Developers

- Boundary coordinates are stored as latitude/longitude arrays for Leaflet rendering.
- Upload preview files are stored temporarily under `storage/app/upload-previews`.
- Bulk imports update matched barangays and create records for unmatched named features.
- Municipal boundary records are stored in the `barangays` table with `is_municipal_boundary=true`.
- Role-protected admin routes are defined in `routes/web.php`.
- Audit trail records use Spatie Activitylog's `activity_log` table and are displayed at `/admin/activity-logs`.
