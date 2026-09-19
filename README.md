# WP Mini Notes

A small WordPress notes project built from scratch with PHP, WordPress themes, plugins, custom post types, forms and CRUD. This is a learning project used to practise the WordPress + PHP roadmap.

## What is included

- Custom theme: Home, About, Post, 404, Login and Dashboard pages.
- Custom plugin: registers the `note` post type, handles login, CRUD, ownership, metadata, admin UI and a private REST endpoint.
- Study documentation: data flow, API map, study guide and test report.
- Practice labs and integration tests.
- Local installer script for Windows/XAMPP.

## Stack

- WordPress 7.1
- PHP 8.1+
- XAMPP Apache on port 8080
- MySQL/MariaDB on port 3306
- Plain HTML, CSS and JavaScript (no build step)

## Quick start on Windows with XAMPP

1. Download the official WordPress 7.1 ZIP and place it at:

```text
C:\Users\%USERNAME%\Downloads\wordpress-7.1.zip
```

2. Start Apache and MySQL in XAMPP Control Panel.

3. Open PowerShell in the repository root and run:

```powershell
.\Install-Local.ps1 -WordPressZip "$env:USERPROFILE\Downloads\wordpress-7.1.zip"
```

The installer will:

- Stop if the target folder or database already exists to avoid overwriting data.
- Extract WordPress core into `C:\xampp\htdocs\wp-mini-notes`.
- Copy this theme and plugin into `wp-content`.
- Create the database `wp_mini_notes`, generate `wp-config.php` with random salts and write local `.htaccess` rules.
- Install WordPress, activate the plugin and theme, create pages and sample users.
- Save login credentials in `LOCAL-ACCESS.json` next to this README, outside the web root.

If MySQL has a password, set it in the terminal before running the installer:

```powershell
$env:MN_DB_PASS = "your_mysql_password"
.\Install-Local.ps1 -WordPressZip "$env:USERPROFILE\Downloads\wordpress-7.1.zip"
```

## URLs after installation

```text
Home page:   http://localhost:8080/wp-mini-notes
Admin:       http://localhost:8080/wp-mini-notes/wp-admin
Dashboard:   http://localhost:8080/wp-mini-notes/dashboard
Login:       http://localhost:8080/wp-mini-notes/login
```

Login details are written to `LOCAL-ACCESS.json`. Keep that file private.

## Project structure

```text
wp-content/plugins/mini-notes-core/   Custom plugin (CPT, forms, admin, REST)
wp-content/themes/mini-notes/         Custom theme
docs/                                 Study documentation
labs/                                 PHP/WordPress practice exercises
tests/                                Integration and smoke tests
tools/                                Installer and preview router
Install-Local.ps1                     Windows installer
Step_Study.md                         Learning roadmap
```

## Running tests

Only run tests against a local instance created for learning, because fixtures may be deleted permanently.

```powershell
$env:MN_ROOT = "C:\xampp\htdocs\wp-mini-notes"
$env:MN_RUN_TESTS = "1"
& C:\xampp\php\php.exe .\tests\integration.php
node .\tests\http-smoke.mjs "http://localhost:8080/wp-mini-notes" ".\LOCAL-ACCESS.json"
```

Node is only used for testing, not for running the application.

## What is NOT tracked

WordPress core files, uploaded media, local configuration and generated files are excluded from Git:

```text
wp-admin/
wp-includes/
wp-config.php
.htaccess
wp-content/uploads/
LOCAL-ACCESS.json
```

## Progress

- [x] Install WordPress locally
- [x] Explore WordPress directory structure
- [x] Create a custom theme
- [x] Add dashboard and login templates
- [x] Create a notes plugin
- [x] Implement note CRUD
- [x] Add study documentation, labs and tests
