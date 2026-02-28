# Coding Conventions

**Analysis Date:** 2026-02-28

## Naming Patterns

**Files:**
- WordPress-standard kebab-case for PHP files: `custom-posts.php`, `admin-customizations.php`, `speekr-admin.js`
- Directory structure uses hyphens: `/inc/admin/`, `/inc/front/`, `/inc/common/`, `/inc/functions/`, `/inc/classes/`
- Class files follow PascalCase: `Speekr.php`, `Speekr_Templates_Loader.php`

**Functions:**
- All public functions prefixed with plugin slug: `speekr_get_option()`, `speekr_register_post_types()`, `speekr_display_list_content()`
- Helper functions follow pattern `speekr_get_*()` for getters
- Callback functions follow pattern `speekr_*_mb()` for metabox callbacks, `speekr_*_section_text()` for section labels
- Constructor and lifecycle functions: `speekr_custom_meta_boxes()`, `speekr_save_mb()`
- Clear verb-first naming: `speekr_create_default_page()`, `speekr_remove_notice()`, `speekr_import_posts()`

**Variables:**
- Snake_case for PHP variables: `$media_links`, `$speekr_ml`, `$editor_id`, `$post_metas`
- Short variable names acceptable in loops: `$k`, `$id`, `$v`
- jQuery variables prefixed with `$`: `$embed_media`, `$embed_checkbox`, `$container`, `$line`, `$clone`
- Boolean context variables: `$is_network_activated`, `$has_posts`, `$can_import`

**Types:**
- No formal type hints used in function signatures
- Type indicators in comments: `@param (string)`, `@param (int)`, `@param (array)`, `@param (object)`, `@param (bool)`
- Return types in comments: `@return (array)`, `@return (bool)`, `@return (string)`
- Conditional checks use WordPress patterns: `isset()`, `empty()`, `is_wp_error()`, `get_post_type()`

## Code Style

**Formatting:**
- Tab indentation (width 1 as per WordPress standard) observed in package.json: `"indent-type tab", "indent-width 1"`
- No explicit formatter configured, but WordPress standards implied
- Opening braces on same line: `function() {`
- Multi-line conditionals aligned for readability

**Linting:**
- ESLint configured via `@wordpress/scripts` package version `^12.1.0`
- Lint command: `npm run lint:js` - lints `assets/js/*.js` files
- Configuration inherited from WordPress Scripts package (no custom .eslintrc found)

## Import Organization

**PHP includes:**
- Central includes via main class `Speekr::includes()` in `inc/classes/Speekr.php`
- Ordered by dependency: debug utilities first, then common, then functions, then classes, then features
- Uses `require_once()` for all includes
- Hooks available between include groups: `do_action( 'speekr_before_includes' )`, `do_action( 'speekr_after_includes' )`

```php
// Order from inc/classes/Speekr.php::includes()
require_once( SPEEKR_DIRNAME . '/inc/functions/debug.php' );           // Utilities first
require_once( SPEEKR_DIRNAME . '/inc/common/custom-posts.php' );       // Common setup
require_once( SPEEKR_DIRNAME . '/inc/common/custom-image-sizes.php' );
require_once( SPEEKR_DIRNAME . '/inc/functions/helpers.php' );        // Helper functions
require_once( SPEEKR_DIRNAME . '/inc/functions/markup.php' );
require_once( SPEEKR_DIRNAME . '/inc/functions/options.php' );
require_once( SPEEKR_DIRNAME . '/inc/functions/usermeta.php' );
require_once( SPEEKR_DIRNAME . '/inc/functions/settings.php' );
require_once( SPEEKR_DIRNAME . '/inc/common/default-types.php' );
require_once( SPEEKR_DIRNAME . '/inc/classes/Speekr_Templates_Loader.php' );  // Classes last
```

**JavaScript:**
- jQuery pattern with immediate function: `;( function( $, window, document, undefined ) { } )( jQuery, window, document );`
- Localizes global data via `speekr` object (embedded by enqueue)

**Path Constants:**
- Central definitions in main plugin file: `speekr.php`
- Constants like `SPEEKR_PLUGIN_URL`, `SPEEKR_CLASSES_DIR`, `SPEEKR_DIRNAME`
- Used throughout for file paths and URL generation

## Error Handling

**Patterns:**
- WordPress error objects checked with `is_wp_error()` before using data
- Example from `inc/functions/helpers.php::speekr_get_youtube_id()`:
  ```php
  $response = wp_remote_get( $json_link );
  if ( is_wp_error( $response ) ) {
      return false;
  } else {
      $json = isset( $response['body'] ) ? json_decode( $response['body'] ) : '';
      return isset( $json->html ) ? $json->html : false;
  }
  ```

- Nonce verification for AJAX: `wp_verify_nonce( $_POST['_wpnonce'], 'action_name' )` with early exit pattern
- User capability checks: `current_user_can()` with early exit if unauthorized
- Example from `inc/admin/ajax.php::speekr_create_default_page()`:
  ```php
  if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'] , 'create_default_page' ) ) {
      if ( ! get_current_user_id() && current_user_can( 'manage_options' ) ) {
          $data['message'] = __( 'You are not allowed to edit this item.' );
          wp_send_json_error( $data );
          exit;
      }
  }
  ```

- WordPress AJAX responses: `wp_send_json_success()`, `wp_send_json_error()` with data array
- Post update validation: returns boolean from `wp_insert_post()`, `wp_update_post()`
- Data type coercion with `(int)`, `(array)` for safety before database operations

## Logging

**Framework:** `console.log()`, `console.info()` in JavaScript (no logger library)

**Patterns:**
- Info message on load: `console.info('Speekr Admin JS Loaded');` in `assets/js/speekr-admin.js`
- Debug logs for AJAX operations: `console.log($(this))`, `console.log(data)`
- PHP has debug functions but wrapped in conditional: `if ( defined( 'SPEEKR_DEBUG' ) && SPEEKR_DEBUG ) { speekr_log() }`
- No production-level logging system found

## Comments

**When to Comment:**
- Function documentation with standard block comments
- Special logic explanation (e.g., "Ajaxception…" comment at line 271 in speekr-admin.js)
- Complex array structures documented inline: `// https:($0)/($1)/vimeo.com($2)/([id])$3` in helpers.php line 28

**JSDoc/TSDoc:**
- PHP uses standard format but not formal PHPDoc:
  ```php
  /**
   * Get youtube video ID from an URL.
   * @param  (string) $url The URL of the video.
   * @return (string)      The id of the video.
   *
   * @author Geoffrey Crofte
   * @since  1.0
   */
  function speekr_get_youtube_id( $url ) {
  ```
- Includes `@author`, `@since`, `@param`, `@return` tags
- No return type hints used; type specified only in comment

## Function Design

**Size:** Functions are compact, typically 5-30 lines
- Utility functions minimal: `speekr_get_youtube_id()` is 3 lines
- Complex operations like AJAX handlers 50-100 lines with clear phase separation

**Parameters:**
- Minimal parameters: helpers take 1-2 arguments
- Example: `speekr_get_option( $option, $default = false )`
- Array unpacking for complex data: `speekr_get_talk_metas( $post_id )` returns array, not multiple params

**Return Values:**
- Functions return simple types: bool, string, int, array
- Getters return data or false on failure
- AJAX handlers exit with `wp_send_json_*()` - no explicit return needed
- Example pattern:
  ```php
  if ( isset( $options[ $option ] ) ) {
      return (int) $options[ $page ];
  }
  return false;
  ```

## Module Design

**Exports:**
- No explicit export system (PHP functions auto-exported)
- All public functions follow naming convention: `speekr_*` prefix ensures namespace
- Functions added to hooks via `add_action()`, `add_filter()` calls

**Barrel Files:**
- No barrel file pattern used
- Includes managed centrally via `Speekr::includes()` in main class

**Organisation by Layer:**
```
inc/classes/        - Class definitions and object-oriented features
inc/admin/          - Admin UI, settings, metaboxes, AJAX endpoints
inc/front/          - Frontend display logic and template handling
inc/common/         - Shared setup: post types, image sizes, defaults
inc/functions/      - Utility functions grouped by concern:
                      - options.php: option management
                      - markup.php: HTML output helpers
                      - helpers.php: data transformation utilities
                      - debug.php: logging utilities
                      - urls.php: URL generation
                      - usermeta.php: user metadata management
                      - settings.php: settings getters
```

---

*Convention analysis: 2026-02-28*
