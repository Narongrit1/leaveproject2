# Repository Guidelines

## Project Structure & Module Organization

This is a pure PHP + MySQL leave management system intended to run under XAMPP.

- Root `*.php` files are page controllers for login, dashboards, leave requests, attendance requests, reports, and admin screens.
- `includes/` contains shared application code such as authentication, database access, layout partials, helpers, and reusable forms.
- `assets/css/app.css` and `assets/js/app.js` contain global frontend styling and JavaScript.
- `database/` contains schema and seed SQL files.
- `uploads/` stores user-uploaded attachments and should not be used for source code.

## Build, Test, and Development Commands

Run locally through Apache with the repository located at `C:\xampp\htdocs\leaveproject2`.

```powershell
C:\xampp\php\php.exe -l dashboard.php
```

Checks PHP syntax for a single file. Run this on every PHP file you edit.

```powershell
Invoke-WebRequest -UseBasicParsing http://localhost/leaveproject2/login.php
```

Verifies the app is reachable through XAMPP.

There is no package build step and no npm dependency workflow in this repository.

## Coding Style & Naming Conventions

Use PHP with short, page-oriented controllers matching existing file names, for example `leave_create.php`, `attendance_view.php`, and `users_list.php`. Prefer `snake_case` for PHP functions and variables where the existing code does so. Keep shared logic in `includes/functions.php` or a relevant include file instead of duplicating it in page controllers.

Use 4-space indentation in PHP and CSS. Escape output with `e()` before rendering user-controlled values. Use existing helpers such as `redirect_to()`, `set_flash()`, `csrf_field()`, and `verify_csrf()`.

Frontend changes should reuse Tailwind utility classes and the global styles in `assets/css/app.css`.

## Testing Guidelines

No automated test framework is currently configured. For changes, perform focused manual verification:

- Run PHP syntax checks with `C:\xampp\php\php.exe -l path\to\file.php`.
- Open the affected page through `http://localhost/leaveproject2/`.
- Test both success and error states for forms.
- Confirm role-protected pages still redirect or render correctly.

## Commit & Pull Request Guidelines

This workspace does not include Git history, so no project-specific commit convention is available. Use concise imperative commit messages, such as `Update dashboard layout` or `Fix leave approval validation`.

Pull requests should include a short summary, affected pages, manual test notes, and screenshots for UI changes. Mention any database schema or seed changes explicitly.

## Security & Configuration Tips

Keep database credentials in `includes/config.php` local to the environment. Do not commit real production credentials or uploaded user files. Preserve CSRF checks on POST routes and validate uploads through the existing helper functions.
