# AGENTS.md

M.O.R.P.H.I.C is a digital software/asset store built on **CodeIgniter 3**, PHP 8.2+, MySQL/MariaDB. CSS and JS are hand-written; there is **no build step, no npm, no test suite, and no CI**. The `composer.json` only describes the CI framework itself — you never run `composer install` to make the app work. Full setup prose is in `README.md` (Indonesian); this file records only the non-obvious bits.

## Running it
- Apache/XAMPP with `mod_rewrite`. `index_page` is empty and `.htaccess` sends everything through `index.php`.
- Import the schema: `mysql -u root < database.sql` (creates `morphic_store`). DB credentials are hardcoded in `application/config/database.php` (`root`, empty password).
- **Gotcha:** `application/config/config.php` `base_url` is still `http://localhost/BaseFullstackProject/`. This checkout is `MORPHIC`, so update `base_url` to the real folder or every generated/asset URL breaks.
- Environment is selected by the `CI_ENV` server var in `index.php` (default `development`, which hides `E_DEPRECATED`/`E_USER_DEPRECATED` so CI3's dynamic-property notices don't flood pages). There are no per-environment config files.
- Timezone is pinned to `Asia/Jakarta` in `index.php` and the seeder to match MySQL's local clock. Keep new CLI scripts consistent.
- Demo analytics data: `php tools/seed_demo_traffic.php [days] [hits]` or `--clear`. Reads `DB_HOST`/`DB_USER`/`DB_PASS`/`DB_NAME` env vars, else the XAMPP defaults.

## Architecture
- All base controllers are declared together in `application/core/MY_Controller.php`: `MY_Controller` (bootstraps every `*_model`, cart helpers, `render()`, visit tracking), plus `Frontend_Controller` (guests allowed), `Customer_Controller` (login required), `Admin_Controller` (staff only, admin layout).
- Controllers in `application/controllers/admin/` must extend `Admin_Controller`; everything else extends `Frontend_Controller` or `Customer_Controller`.
- Shared models are autoloaded by name in `MY_Controller::__construct()` (`$this->user_model`, `$this->product_model`, ...). Add new globally used models there rather than loading per controller.
- Render through `$this->render($view, $data, $title)`, which wraps `layouts/store` or `layouts/admin`. Use `render_plain()` for output without a layout.
- App settings live in `application/config/morphic.php` (pagination, upload paths, payment methods/bank accounts, license prefix, rate limits, analytics window) and `unsplash.php`; both are autoloaded.
- `application/controllers/Welcome.php` + `views/welcome_message.php` are unused CI scaffolding (`default_controller` is `home`). Don't wire new features to them.

## Security conventions
- CSRF is enabled and **regenerates on every POST**. POST forms use `form_open()`/`form_open_multipart()` (token injected automatically). AJAX endpoints must return the refreshed token to the client (see `Admin\Products` Unsplash search) or the next submission fails.
- Product files live outside the web root in `storage/private_products/` and are streamed only by `Download::index()` via per-license tokens (`dl_tok_...`) with a download quota. Never link them directly.
- Set `protected $track_visits = FALSE;` on controllers that aren't page views (file streaming, JSON/AJAX endpoints). Visit logging skips POST, AJAX, admin, and CLI requests and never stores query strings.
- Roles are ordered numerically via `role_level()`: owner(4) > super_admin(3) > admin(2) > customer(1). Use `is_staff_role()`, `can_manage_role()`, `assignable_roles()` instead of string comparisons. Nobody may change their own role or manage an account above their level; admin password resets revoke all of the target account's sessions.
- Schema changes go straight into `database.sql` — there are no migrations in use.

## Style
- Tabs for indentation, LF line endings, trailing newline (`.editorconfig`). Match CI3 conventions already in the codebase: `array()`, snake_case methods, `defined('BASEPATH') OR exit(...)` guard at the top of every PHP file.
