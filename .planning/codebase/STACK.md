# Technology Stack

**Analysis Date:** 2026-02-28

## Languages

**Primary:**
- PHP (WordPress plugin language) - Entire plugin logic, custom post types, admin functionality
- JavaScript (Vanilla) - Admin and frontend interactions
- SCSS - Stylesheets for admin and frontend

## Runtime

**Environment:**
- WordPress (CMS framework)

**Package Manager:**
- npm (Node Package Manager) - Defined in `package.json`
- Lockfile: Present (`package-lock.json`)

## Frameworks

**Core:**
- WordPress 5.0+ (assumed minimum) - CMS and plugin API
- jQuery - Enqueued as dependency for admin scripts in `inc/admin/enqueues.php`

**Build/Dev:**
- node-sass 4.14.1 - SCSS compilation for CSS assets
- @wordpress/scripts 12.1.0 - WordPress recommended linting and build tooling
- csso-cli 3.0.0 - CSS minification and optimization

**Utilities:**
- Gulp 4.0.2 - Task runner (dependency declared but gulpfile removed per commits)
- dir-archiver 1.1.1 - File archival utility for distribution

## Key Dependencies

**Critical:**
- jQuery 3.x+ - Used in admin scripts for DOM manipulation (`inc/admin/enqueues.php`)

**Utilities:**
- wp-scripts (via @wordpress/scripts) - Linting JS using wp-scripts lint-js

## Build Configuration

**CSS Compilation:**
- Source: `assets/css/src/` (SCSS files)
  - `speekr-admin.scss` (18KB) - Admin styling
  - `speekr.scss` (4KB) - Frontend styling
- Output: `assets/css/` (compiled CSS)
  - `speekr-admin.css` / `speekr-admin.min.css`
  - `speekr.css` / `speekr.min.css`
- Minification with source maps via csso-cli
- NPM scripts in `package.json`:
  - `npm run watch` - Watch SCSS and auto-compile
  - `npm run compile:css` - Compile SCSS to CSS
  - `npm run minify:css` - Minify CSS with source maps

**JavaScript:**
- Source: `assets/js/`
  - `speekr-admin.js` (9KB) - Admin functionality
  - `speekr.js` (71 bytes) - Frontend functionality
- Enqueued via `inc/admin/enqueues.php` and `inc/front/enqueues.php`
- Linting: `npm run lint:js` via @wordpress/scripts

## Font Assets

**Custom Icon Font:**
- Location: `assets/fonts/admin/speekr`
- Format: Multi-format EOT, TTF, WOFF, SVG
- Usage: Dashboard menu icon via `@font-face` declared in `inc/admin/enqueues.php`

## Asset Enqueuing

**Admin:**
- Stylesheet: `speekr-admin.css` - Loaded only on Speekr admin pages
- Script: `speekr-admin.js` - Loaded only on Speekr admin pages
- Dependencies: jQuery
- Script localization: `speekr` object with translatable strings

**Frontend:**
- Stylesheet: `speekr.css` / `speekr.min.css` - Conditional loading on talks list page or single talk
- Script: `speekr.js` - Not actively used (71 bytes)
- Debug mode: SCRIPT_DEBUG constant triggers minified vs non-minified CSS

## Accessibility & Localization

**Text Domain:**
- Domain: `speekr`
- Directory: `languages/` (expected, not committed)
- Load location: `inc/classes/Speekr.php` line 46

**Capabilities System:**
- Uses WordPress native `current_user_can()` and `edit_users` capability for admin access
- Custom capability logic: `speekr_current_user_can_do()` in `inc/functions/helpers.php`

## Configuration Files

**Plugin Manifest:**
- `speekr.php` (root) - Main plugin file with plugin header and initialization
- Version: 1.0
- License: GPLv2 or later
- Author: Geoffrey Crofte, Stephanie Walter

**Version Constants:**
- SPEEKR_VERSION = '1.0'
- SPEEKR_PLUGIN_NAME = 'Speekr'
- SPEEKR_SLUG = 'speekr'

## Platform Requirements

**Development:**
- Node.js (for npm) - Required to run build scripts
- npm (package management)
- SCSS knowledge for styling modifications

**Production:**
- WordPress 5.0+ (minimum assumed)
- PHP 5.6+ (assumed, no specific version declared)
- Standard WordPress database (MySQL/MariaDB via WordPress)
- Multisite support (code checks for network activation via `SPEEKR_NETWORK_ACTIVATED`)

## Supported Features

**Multisite:**
- Plugin detects network activation via `is_plugin_active_for_network()`
- Options stored in site options (network level) or blog options (single site level)
- Functions in `inc/functions/options.php` handle dual-site storage

**Browser Compatibility:**
- Uses CSS Grid and modern CSS via SCSS (no IE support indicated)
- jQuery 3.x compatibility (modern browsers only)

---

*Stack analysis: 2026-02-28*
