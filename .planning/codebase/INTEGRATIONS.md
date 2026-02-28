# External Integrations

**Analysis Date:** 2026-02-28

## APIs & External Services

**Presentation Platforms:**

- **SpeakerDeck** - Embed presentations from SpeakerDeck URLs
  - SDK/Client: Custom via oEmbed
  - Endpoint: `https://speakerdeck.com/oembed.json`
  - Function: `speekr_get_speakerdeck_iframe()` in `inc/functions/helpers.php` lines 40-52
  - Method: `wp_remote_get()` to fetch oEmbed JSON, extract HTML iframe
  - Error handling: Returns `false` on `WP_Error` response

- **Slideshare** - Embed presentations from Slideshare URLs
  - SDK/Client: Slideshare oEmbed API v2
  - Endpoint: `http://www.slideshare.net/api/oembed/2?url=[url]&format=json`
  - Function: `speekr_get_slideshare_iframe()` in `inc/functions/helpers.php` lines 62-79
  - Method: `wp_remote_get()` to fetch oEmbed JSON
  - Data extracted: HTML iframe embed and thumbnail image URL
  - Error handling: Returns `false` on `WP_Error` response

- **Slides.com** - Embed presentations from Slides.com URLs
  - SDK/Client: Direct iframe URL transformation
  - Function: `speekr_get_slides_iframe_url()` in `inc/functions/helpers.php` lines 89-91
  - Method: URL string manipulation, no external API call required
  - Format: Transforms URL to embed format with dark theme

- **YouTube** - Video embed support
  - SDK/Client: Not directly called; uses WordPress native oEmbed
  - Function: `speekr_get_youtube_id()` in `inc/functions/helpers.php` lines 14-17
  - Method: Parses video ID from URL (`?v=` parameter)

- **Vimeo** - Video embed support
  - SDK/Client: Not directly called; uses WordPress native oEmbed
  - Function: `speekr_get_vimeo_id()` in `inc/functions/helpers.php` lines 27-30
  - Method: Parses video ID from URL (URL path split)

## Data Storage

**Databases:**

- **WordPress Native Database (MySQL/MariaDB)**
  - Connection: Via WordPress wp-config.php (not in plugin)
  - Client: WordPress wpdb global
  - Data stored:
    - Custom Post Type `talk` - Talk/presentation metadata
    - Post Meta - `speekr-media-links`, `speekr-conf`, `speekr-summary`, `speekr-as-article`
    - Plugin Options - Stored in `wp_options` table under key `speekr_settings`
    - Network Options (multisite) - Stored in `wp_sitemeta` when network activated

**Options Storage Pattern:**

```php
// From inc/functions/options.php
// Single site: get_option( 'speekr_settings' )
// Network: get_site_option( 'speekr_settings' )
```

**Post Meta Keys:**
- `speekr-media-links` - Serialized array of media/presentation links
- `speekr-conf` - Serialized array of conference information
- `speekr-summary` - String summary of talk
- `speekr-as-article` - String flag ("on") for article publication status

**File Storage:**

- **Local filesystem only** - No cloud storage integration
- WordPress media library for featured images (cover images)
- Custom image sizes registered: `speekr_talk_image` (size defined in `inc/common/custom-image-sizes.php`)

**Caching:**

- None - No Redis, Memcached, or caching layer implemented
- Uses WordPress native object caching if available (no explicit configuration)

## Authentication & Identity

**Auth Provider:**

- **WordPress Native** - Custom authentication
  - Implementation: Uses WordPress user system
  - Permission checks: `current_user_can( 'edit_users' )`
  - User selection: `get_users()` query in `inc/admin/importer.php`
  - AJAX Nonces: `wp_verify_nonce()` for secure AJAX requests in `inc/admin/ajax.php`

**Security:**

- NONCE verification: `wp_verify_nonce()` in AJAX endpoints
- Capability checks before database modifications
- No API keys or custom auth tokens used

## Monitoring & Observability

**Error Tracking:**

- None detected - No external error tracking service (Sentry, Rollbar, etc.)
- WordPress native `WP_Error` handling in oEmbed functions
- Conditional logging via `is_wp_error()` checks

**Logs:**

- WordPress native `error_log()` potential via `inc/functions/debug.php`
- Admin notices for user-facing errors in `inc/admin/notices.php`
- Console logging in JavaScript (`speekr-admin.js`) likely present

## CI/CD & Deployment

**Hosting:**

- WordPress.org plugin directory ready (has standard plugin header)
- Self-hosted WordPress installation (no SaaS platform specified)
- Multisite compatible

**CI Pipeline:**

- None detected - No GitHub Actions, GitLab CI, or other CI service configuration
- Local npm scripts available for development (`npm run watch`, `npm run compile:css`, `npm run lint:js`)

## Environment Configuration

**Required env vars:**

- None - Plugin uses WordPress configuration entirely (via wp-config.php)
- No external API keys or secrets required for core functionality
- oEmbed endpoints use HTTP/HTTPS (hardcoded in functions)

**Secrets location:**

- Not applicable - Plugin does not manage secrets
- Relies on WordPress database credentials (managed externally)

## Webhooks & Callbacks

**Incoming:**

- None detected - No webhook endpoints

**Outgoing:**

- None detected - No webhook triggers or callbacks to external services

## Action Hooks & Filters

**Custom Hooks (for extensibility):**

- `speekr_before_includes` - Before main includes
- `speekr_after_includes` - After main includes
- `speekr_before_includes_admin` - Before admin includes
- `speekr_after_includes_admin` - After admin includes
- `speekr_before_includes_front` - Before frontend includes
- `speekr_after_includes_front` - After frontend includes
- `speekr_pre_get_option_[option_name]` - Pre-filter for individual options
- `speekr_get_option_[option_name]` - Post-filter for individual options
- `speekr_pre_get_options` - Pre-filter for all options
- `speekr_get_options` - Post-filter for all options
- `speekr_get_option_page_url` - Filter settings page URL
- `speekr_default_talks_page_status` - Filter default talks page status on activation

**WordPress Hooks Used:**

- `admin_enqueue_scripts` - Register admin CSS/JS
- `wp_enqueue_scripts` - Register frontend CSS
- `admin_head` - Add custom font-face styles
- `init` - Register post types, load textdomain
- `admin_init` - Register settings
- `wp_ajax_[action]` - AJAX endpoints (authenticated)
- `wp_ajax_nopriv_[action]` - AJAX endpoints (not authenticated)

## AJAX Endpoints

**Admin AJAX Actions:**

- `speekr_create_default_page` - Create default "My Talks" page
  - Authenticated: Yes (both `wp_ajax` and `wp_ajax_nopriv`)
  - Nonce check: `create_default_page` nonce
  - Handler: `speekr_create_default_page()` in `inc/admin/ajax.php`

- `speekr_remove_notice` - Dismiss admin notices
  - Authenticated: Yes (both `wp_ajax` and `wp_ajax_nopriv`)
  - Handler: `speekr_remove_notice()` in `inc/admin/ajax.php`

- `speekr_import_posts` - Bulk import posts as talks
  - Authenticated: Yes (both `wp_ajax` and `wp_ajax_nopriv`)
  - Data: POST parameters for tags, categories, posts selection
  - Handler: `speekr_import_posts()` in `inc/admin/ajax.php`
  - Processes: Tag filtering, category filtering, post type conversion

## External Dependencies Summary

**No Third-Party API Keys Required** - Plugin is self-contained and relies solely on:
1. WordPress core functionality
2. oEmbed public APIs (SpeakerDeck, Slideshare)
3. WordPress database
4. Local filesystem

**Network Calls Made:**
1. SpeakerDeck oEmbed: `https://speakerdeck.com/oembed.json?url=[url]`
2. Slideshare oEmbed: `http://www.slideshare.net/api/oembed/2?url=[url]&format=json`

---

*Integration audit: 2026-02-28*
