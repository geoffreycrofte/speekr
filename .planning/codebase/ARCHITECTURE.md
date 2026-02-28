# Architecture

**Analysis Date:** 2026-02-28

## Pattern Overview

**Overall:** Modular WordPress Plugin with Conditional Initialization

**Key Characteristics:**
- Single plugin class (`Speekr`) manages initialization and conditional loading
- Admin and frontend code separated into distinct includes based on execution context
- Heavy use of WordPress hooks (actions/filters) for extensibility
- Function-based utilities organized by domain (functions, admin, front)
- Template system via custom template loader for archive page customization

## Layers

**Plugin Core:**
- Purpose: Bootstrap plugin, manage initialization, control feature loading
- Location: `speekr.php`, `inc/classes/Speekr.php`
- Contains: Main class with init logic, mode detection, conditional includes
- Depends on: All other layers (loads them conditionally)
- Used by: WordPress bootstrap process

**Common/Shared Layer:**
- Purpose: Functionality required both in admin and frontend contexts
- Location: `inc/common/`, `inc/functions/`
- Contains: Custom post type registration, image sizes, utility functions (options, markup, helpers)
- Depends on: WordPress core
- Used by: Admin layer and frontend layer

**Admin Layer:**
- Purpose: Backend editing, configuration, and management features
- Location: `inc/admin/`
- Contains: Settings pages, meta boxes, AJAX handlers, notices, importer, menu integration
- Depends on: Common layer, WordPress admin functions
- Used by: WordPress admin dashboard only

**Frontend Layer:**
- Purpose: Public display of talks and archives
- Location: `inc/front/`
- Contains: Talk list rendering, single talk display, template loading, asset enqueuing
- Depends on: Common layer, WordPress template system
- Used by: Public site pages

**Template System:**
- Purpose: Allow per-page template customization for talk archives
- Location: `inc/classes/Speekr_Templates_Loader.php`, `inc/front/templates/`
- Contains: Template discovery, registration, filtering
- Depends on: WordPress theme integration
- Used by: WordPress template selection UI

## Data Flow

**Post Creation & Editing Flow:**

1. User creates/edits a "Talk" custom post in WordPress admin
2. `speekr_custom_meta_boxes()` in `inc/admin/custom-meta-boxes.php` displays meta box UI
3. Meta boxes render editors for: summary, media links, conference info, content linking
4. Post save triggers meta data persistence via WordPress post meta functions
5. Post meta stored as serialized arrays:
   - `speekr-media-links`: Links to YouTube, Vimeo, Slides, etc.
   - `speekr-conf`: Conference name and URL
   - `speekr-summary`: Short description
   - `speekr-as-article`: Boolean flag for blog linking

**Frontend Display Flow:**

1. Page request to "My Talks" archive page
2. `speekr_display_list_content()` in `inc/front/lists.php` hooks into `the_content`
3. Queries all "talks" custom posts
4. For each talk:
   - Retrieves post meta with `speekr_get_talk_metas()`
   - Determines media type (embedded video/slides or featured image)
   - Renders media via `speekr_get_media_header()` - attempts oEmbed first, falls back to iframe generation
   - Outputs full HTML with schema.org markup
5. Layout applied via CSS classes (`speekr-layout-grid`, `speekr-layout-list`, `speekr-layout-mixed`)

**Settings & Options Flow:**

1. Plugin stores all settings in single WordPress option: `speekr_settings`
2. Network-aware: uses `get_site_option()` if multisite activated, `get_option()` if single site
3. Settings accessed via `speekr_get_options()` and `speekr_get_option()`
4. Updates via `speekr_update_options()` or `speekr_update_option()`
5. Key settings: `list_page` (archive page ID), `list_layout` (grid/list/mixed), CSS activation, colors

**AJAX Interactions:**

1. User dismisses notices via AJAX in `speekr_remove_notice()`
2. Creates default page via AJAX in `speekr_create_default_page()`
3. Nonce verification on all AJAX actions
4. User meta management via `inc/functions/usermeta.php` for per-user notice state

**State Management:**

- Plugin state: WordPress option (`speekr_settings`) - single source of truth
- User state: User meta (`speekr_user_meta_*`) - per-user notices and preferences
- Post data: Post meta - serialized arrays on Talk posts
- No session, cache, or transient usage detected
- Global `$speekr_options` cached once at settings.php include time

## Key Abstractions

**Custom Post Type (Talks):**
- Purpose: Store conference talks as content
- Examples: `inc/common/custom-posts.php` line 74
- Pattern: `register_post_type()` with custom labels, supports featured image, title, author, revisions
- Meta fields stored as post meta with `speekr-` prefix

**Media Handler:**
- Purpose: Resolve media links (YouTube, Vimeo, Slides, etc.) to embeddable HTML
- Examples: `inc/functions/helpers.php` (YouTube/Vimeo ID extraction), `inc/functions/markup.php` (embed generation)
- Pattern:
  - Extract video IDs from URLs via regex/string parsing
  - Call external APIs (SpeakerDeck, Slideshare) for oEmbed data
  - Generate iframes with platform-specific URLs and parameters
  - Try `WP_oEmbed` first, then fallback to custom handling

**Template Loader:**
- Purpose: Inject plugin templates into WordPress theme template selection
- Examples: `inc/classes/Speekr_Templates_Loader.php`
- Pattern: Directory scanning, file header parsing, filter-based template override

**Settings Accessor Functions:**
- Purpose: Abstract option storage location (network vs. single-site)
- Examples: `speekr_get_options()`, `speekr_update_option()`
- Pattern: Helper functions that check network activation status and call appropriate WP function

**Permission Checker:**
- Purpose: Centralize capability checks
- Examples: `speekr_current_user_can_do()` in `inc/functions/helpers.php` (checks `edit_users`)
- Pattern: Single function called from admin pages and AJAX handlers

## Entry Points

**Plugin Load:**
- Location: `speekr.php` line 29
- Triggers: WordPress plugin loading (mu-plugins, plugins directories)
- Responsibilities: Define constants, require main class, instantiate `Speekr`

**Speekr Class Initialization:**
- Location: `inc/classes/Speekr.php` constructor
- Triggers: Plugin file inclusion
- Responsibilities:
  - Add `init` action for textdomain loading
  - Detect network activation
  - Conditionally load admin or frontend includes
  - Register activation hook for install routine

**Frontend Content Display:**
- Location: `inc/front/lists.php` line 21 (hooked to `the_content` filter)
- Triggers: Page rendering for "My Talks" archive page
- Responsibilities: Query talks, format output, apply layout

**Admin Initialization:**
- Locations: Multiple entry points in `inc/admin/`
- Examples:
  - `speekr_add_settings_menu()` in `menus.php` hooked to `admin_menu`
  - `speekr_plugin_settings()` in `settings.php` hooked to `admin_init`
  - Custom meta box callbacks in `custom-meta-boxes.php`

## Error Handling

**Strategy:** Minimal error handling - relies on WordPress defaults

**Patterns:**
- AJAX handlers return `wp_send_json_error()` on nonce failure or permission check
- Post insertion returns `WP_Error` object (checked with `is_wp_error()` in `inc/classes/Speekr.php` line 66)
- External API calls (SpeakerDeck, Slideshare) check `is_wp_error()` response
- No try-catch blocks detected
- Failed oEmbed calls silently fallback to custom iframe generation

## Cross-Cutting Concerns

**Logging:**
- Debug mode via `inc/functions/debug.php`
- No structured logging detected
- WordPress default error logging via `error_log()`

**Validation:**
- Nonce validation: `wp_verify_nonce()` on all AJAX actions
- Permission validation: `current_user_can()` checks on settings pages, `speekr_current_user_can_do()` wrapper
- Input sanitization: Minimal - uses `esc_url()`, `esc_attr()`, `esc_html()` in output
- No server-side validation of media URLs detected

**Authentication:**
- Leverages WordPress user system
- Nonce tokens for AJAX
- Role-based access: "manage_options" for settings, "edit_users" for core features
- No custom token or session management

**Multisite Support:**
- Network-aware option storage: checks `SPEEKR_NETWORK_ACTIVATED` constant
- Sets constant in `is_network()` method (line 32 of `Speekr.php`)
- Uses `get_site_option()` for network-wide settings
- Uses `get_blog_option()` for individual site updates

---

*Architecture analysis: 2026-02-28*
