# Codebase Structure

**Analysis Date:** 2026-02-28

## Directory Layout

```
speekr/
├── speekr.php                          # Plugin entry point and constants
├── uninstall.php                        # Plugin uninstall cleanup
├── inc/
│   ├── classes/
│   │   ├── Speekr.php                  # Main plugin orchestration class
│   │   └── Speekr_Templates_Loader.php  # Template discovery and filtering
│   ├── admin/
│   │   ├── settings.php                 # Settings page fields and form
│   │   ├── menus.php                    # Admin menu and submenu registration
│   │   ├── custom-meta-boxes.php        # Post edit meta box UI
│   │   ├── ajax.php                     # AJAX handlers for admin actions
│   │   ├── enqueues.php                 # Admin CSS/JS loading
│   │   ├── importer.php                 # Tool for importing posts to talks CPT
│   │   ├── notices.php                  # Admin notices and user dismissals
│   │   └── admin-customizations.php     # Misc admin UI tweaks
│   ├── front/
│   │   ├── lists.php                    # Archive page rendering
│   │   ├── single.php                   # Single talk page customization
│   │   ├── enqueues.php                 # Frontend CSS/JS loading
│   │   └── templates/
│   │       └── speekr-grid.php          # Alternate template for talks listing
│   ├── common/
│   │   ├── custom-posts.php             # Register "talks" custom post type
│   │   ├── custom-image-sizes.php       # Define media image sizes
│   │   └── default-types.php            # Register default media types and CSS values
│   └── functions/
│       ├── helpers.php                  # Media extraction helpers (YouTube ID, Vimeo ID, etc.)
│       ├── markup.php                   # HTML generation functions
│       ├── options.php                  # Settings getter/setter wrappers
│       ├── settings.php                 # Settings UI field definitions
│       ├── urls.php                     # URL building helpers
│       ├── usermeta.php                 # Per-user meta getter/setter
│       └── debug.php                    # Debug logging utilities
├── assets/
│   ├── css/
│   │   └── src/                         # Source CSS files (compiled to root)
│   ├── js/
│   │   ├── speekr.js                    # Frontend JavaScript
│   │   └── speekr-admin.js              # Backend JavaScript
│   ├── img/                             # Image assets
│   └── fonts/
│       ├── admin/                       # Admin icon fonts
│       └── front/                       # Frontend icon fonts
└── languages/                           # Translation files (po/mo)
```

## Directory Purposes

**`inc/classes/`:**
- Purpose: Object-oriented plugin components
- Contains: Main orchestration class, template system class
- Key files: `Speekr.php` (required), `Speekr_Templates_Loader.php` (instantiated in Speekr.php includes)

**`inc/admin/`:**
- Purpose: All WordPress admin dashboard functionality
- Contains: Settings UI, AJAX endpoints, meta boxes, notices, menu integration
- Key files: `settings.php` (options form), `custom-meta-boxes.php` (post editing UI), `ajax.php` (server actions)
- Only loaded when `is_admin()` returns true

**`inc/front/`:**
- Purpose: All public-facing site functionality
- Contains: Talk archive display, single post customization, public asset loading
- Key files: `lists.php` (generates archive HTML), `templates/` (alternate layouts)
- Only loaded when `!is_admin()`

**`inc/common/`:**
- Purpose: Shared functionality for both admin and frontend
- Contains: Custom post type definition, image size registration, media type definitions
- Always loaded regardless of context
- Files included before context-specific admin/front includes

**`inc/functions/`:**
- Purpose: Utility functions organized by domain
- Contains: Option accessor wrappers, media extraction, HTML generation, URL builders
- Pattern: One file per concern (options.php, markup.php, helpers.php, etc.)
- Always loaded as part of common layer

**`assets/`:**
- Purpose: CSS, JavaScript, fonts, and images
- `css/src/`: Source files (preprocessed or manually organized)
- `js/`: Direct JavaScript includes - `speekr.js` (frontend), `speekr-admin.js` (admin)
- `img/`: Plugin icons and graphics
- `fonts/`: Icon fonts separated by context (admin/front)
- Enqueued selectively via `inc/admin/enqueues.php` and `inc/front/enqueues.php`

**`languages/`:**
- Purpose: Internationalization files
- Contains: `.po` (source) and `.mo` (compiled) translation files
- Textdomain: `'speekr'` (set in `speekr.php` line 14)
- Loaded via `load_plugin_textdomain()` in `Speekr::load_textdomain()`

## Key File Locations

**Entry Points:**
- `speekr.php`: Plugin header and bootstrap (required by WordPress)
- `inc/classes/Speekr.php`: Main class instantiated from entry point

**Configuration:**
- `speekr.php` lines 17-25: Constants for paths and slugs
- `inc/functions/options.php`: Settings accessor functions
- `inc/functions/settings.php`: Settings field definitions

**Core Logic:**
- `inc/classes/Speekr.php`: Feature loading orchestration
- `inc/common/custom-posts.php`: Custom post type registration
- `inc/classes/Speekr_Templates_Loader.php`: Template system
- `inc/front/lists.php`: Archive rendering logic (largest frontend file)
- `inc/functions/markup.php`: Media embed generation (media type handling)

**Testing:**
- No test files detected in repository
- No phpunit.xml or test directory found

**Admin Features:**
- `inc/admin/settings.php`: Settings form fields and sections
- `inc/admin/menus.php`: Menu/submenu registration
- `inc/admin/custom-meta-boxes.php`: Post meta UI (media, conference, summary fields)
- `inc/admin/ajax.php`: AJAX endpoints for page creation, notice dismissal

**Frontend Features:**
- `inc/front/lists.php`: Main archive page content generation
- `inc/front/single.php`: Single talk page customization (thumbnail replacement)
- `inc/front/templates/speekr-grid.php`: Alternate template file for custom layouts

## Naming Conventions

**Files:**
- Procedural files: `lowercase-with-dashes.php` (e.g., `custom-posts.php`, `helper-functions.php`)
- Class files: `Capitalized_With_Underscores.php` (e.g., `Speekr.php`, `Speekr_Templates_Loader.php`)
- Admin-specific: `inc/admin/*.php`
- Frontend-specific: `inc/front/*.php`
- Common/shared: `inc/common/*.php`, `inc/functions/*.php`

**Functions:**
- Plugin-scoped prefix: `speekr_` (e.g., `speekr_get_options()`, `speekr_display_list_content()`)
- Internal helper prefix: `speekr_` (no separate convention for private vs. public)
- Callback functions: Named with context (e.g., `speekr_summary_mb`, `speekr_display_list_content`)

**Classes:**
- Capitalized with underscores: `Speekr`, `Speekr_Templates_Loader`
- Public methods: `camelCase` (e.g., `__construct()`, `load_textdomain()`)
- Private properties: Prefixed with `$` (e.g., `$templates_dir`, `$templates`)

**Variables:**
- Options array: `speekr_options` (global, cached once)
- Post meta arrays: Unpacked with `speekr_get_talk_metas()` - returns array with keys: `media_links`, `conf_infos`, `summary`, `as_article`
- Post meta keys: `speekr-` prefix (e.g., `speekr-media-links`, `speekr-conf`)
- WordPress option key: `speekr_settings` (single option storing all plugin settings)

**Constants:**
- UPPERCASE_WITH_UNDERSCORES in `speekr.php`:
  - `SPEEKR_PLUGIN_NAME`, `SPEEKR_VERSION`, `SPEEKR_FILE`
  - `SPEEKR_DIRNAME`, `SPEEKR_CLASSES_DIR`, `SPEEKR_PLUGIN_URL`
  - `SPEEKR_SLUG` ("speekr"), `SPEEKR_SETTING_SLUG` ("speekr_settings")
  - `SPEEKR_NETWORK_ACTIVATED` (boolean, set during init)

**Custom Post Type:**
- Slug: `talks` (set in `speekr_register_post_types()`, `custom-posts.php`)
- Query var: `talks` (allows `?talks=name` URLs)

**Custom Meta Fields:**
- `speekr-media-links`: Serialized array of media type and URLs
- `speekr-conf`: Serialized array with `name` and `url` keys
- `speekr-summary`: String with talk description
- `speekr-as-article`: String "on"/"off" flag

## Where to Add New Code

**New Feature for Admin:**
- Primary code: `inc/admin/[feature-name].php` (new file following pattern)
- Register hooks: Include file in `Speekr::includes_admin()` (line 105-118)
- Settings UI: Add fields in existing `settings.php` or create separate file
- Menu items: Add to `menus.php` via `add_submenu_page()`

**New Feature for Frontend:**
- Primary code: `inc/front/[feature-name].php` (new file)
- Register hooks: Include file in `Speekr::includes_front()` (line 128-136)
- Templates: Add to `inc/front/templates/` directory with `Template Name:` header

**New Utility Functions:**
- Shared (both contexts): `inc/functions/[domain].php` - organize by domain (options, markup, helpers, etc.)
- Admin-only: `inc/admin/[feature].php`
- Frontend-only: `inc/front/[feature].php`
- Always use `speekr_` prefix

**New Classes:**
- Location: `inc/classes/[Class_Name].php`
- Naming: `Capitalized_With_Underscores`
- Instantiate in `Speekr::includes()` if shared, or in context-specific includes

**New Meta Boxes:**
- Location: `inc/admin/custom-meta-boxes.php`
- Pattern:
  1. Define callback in file
  2. Register via `add_meta_box()` in `speekr_custom_meta_boxes()` function
  3. Meta key prefix: `speekr-`

**New Settings:**
- Location: `inc/functions/settings.php` (add field definition) + `inc/functions/options.php` (add getter/setter if needed)
- Pattern:
  1. Add `add_settings_field()` call in `speekr_plugin_settings()`
  2. Stored in `speekr_settings` option
  3. Access via `speekr_get_option($option_name)`

**New AJAX Endpoints:**
- Location: `inc/admin/ajax.php` (if admin) or new file
- Pattern:
  1. Define function with nonce verification and capability check
  2. Return `wp_send_json_success()` or `wp_send_json_error()`
  3. Register both `wp_ajax_` and `wp_ajax_nopriv_` actions if needed

## Special Directories

**`assets/css/src/`:**
- Purpose: Source CSS files (if using preprocessor)
- Generated: Possibly (not confirmed - may be commit these directly)
- Committed: Yes
- Build process: Not detected (no webpack, gulp, or build tool found)

**`languages/`:**
- Purpose: Translation files for i18n
- Generated: No (user manually creates via translation tools)
- Committed: Yes (po/mo files)

**`.planning/codebase/`:**
- Purpose: GSD analysis documents (this directory)
- Generated: Yes (by mappers)
- Committed: Yes
- Note: Created during GSD mapping phase

---

*Structure analysis: 2026-02-28*
