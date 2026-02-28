# Codebase Concerns

**Analysis Date:** 2026-02-28

## Tech Debt

**Template Loader File Path Issue:**
- Issue: Property name mismatch in `Speekr_Templates_Loader::__construct()` assigns to `$this->template_dir` but methods reference `$this->templates_dir`. Line 17 uses `$this->template_dir` but class property is declared as `$templates_dir`.
- Files: `inc/classes/Speekr_Templates_Loader.php` (lines 8, 17, 29)
- Impact: Template loading fails silently due to undefined property access; plugin templates may not register properly
- Fix approach: Standardize property name to either `$template_dir` throughout or `$templates_dir` throughout

**Incomplete Import Functionality:**
- Issue: Post import process is fundamentally broken - uses disabled `set_post_type()` function (line 179 commented out) and undefined `$set` variable checked on line 199, causing import to always fail
- Files: `inc/admin/ajax.php` (lines 179, 188-196, 199)
- Impact: Admin importer cannot convert posts to talks; users see "Cannot edit that post" error regardless
- Fix approach: Replace with `wp_update_post()` call that modifies post type, ensure post meta is initialized before checking `$set`

**Unsafe Unserialize Usage:**
- Issue: Direct use of `unserialize()` on post meta without validation or error handling
- Files: `inc/functions/helpers.php` (lines 107-110)
- Impact: Corrupted serialized data will cause PHP errors; no fallback for missing meta keys
- Fix approach: Use `maybe_unserialize()` instead; add existence checks before access

**Missing Sanitization in Settings:**
- Issue: Layout option passes through without sanitization despite TODO comment indicating awareness
- Files: `inc/admin/settings.php` (line 205-206)
- Impact: Unsanitized value stored in options; potential injection if list layout values come from user input
- Fix approach: Validate `list_layout` against whitelist of allowed values (grid/list)

**Disabled Sanitizer Function:**
- Issue: `speekr_sanitize_importing()` function is commented out but referenced in settings registration
- Files: `inc/admin/importer.php` (lines 231-233 and line 34 reference)
- Impact: Importer settings are not sanitized; user selections bypass validation
- Fix approach: Implement the sanitization function to validate selected posts/tags/categories

## Known Bugs

**Nonce Verification Logic Error in AJAX:**
- Symptoms: Line 18 checks `if ( ! get_current_user_id() && current_user_can() )` which fails because user must BE logged in to have capabilities
- Files: `inc/admin/ajax.php` (line 18)
- Trigger: Any AJAX call to `speekr_create_default_page` when user is logged in
- Impact: Permission check inverted; function proceeds when user is NOT logged in (which is impossible), denying access to legitimate admins
- Fix approach: Change `!` to `||` OR remove the `get_current_user_id()` check entirely

**Same Logic Error in Other AJAX Functions:**
- Symptoms: Identical issue in two other functions
- Files: `inc/admin/ajax.php` (lines 62, 112)
- Functions: `speekr_remove_notice()` and `speekr_import_posts()`
- Impact: Both AJAX handlers block valid requests from logged-in users
- Fix approach: Apply same fix to all three functions

**Author ID Always Zero in Import:**
- Symptoms: Comment at line 195 indicates `$_POST['author']` equals 0, but code proceeds without validation
- Files: `inc/admin/ajax.php` (lines 184-196)
- Trigger: Using post importer with author selection
- Impact: All imported posts assigned to user ID 0 (no author) instead of selected author
- Workaround: Manually reassign author after import

**Variable Name Typo in Error Response:**
- Symptoms: Line 38 uses `$date['message']` instead of `$data['message']` when responding to failed post creation
- Files: `inc/admin/ajax.php` (line 38)
- Trigger: Post creation fails
- Impact: Error message not included in response; incorrect variable name never used again
- Workaround: None; uses wrong variable

**Duplicate Array Key in Post Type Registration:**
- Symptoms: Label array has `'attributes'` key defined twice (lines 28-29)
- Files: `inc/common/custom-posts.php` (lines 28-29)
- Impact: First definition overwritten; redundant code
- Fix approach: Remove duplicate line

**Unauthenticated AJAX Endpoints Exposed:**
- Symptoms: AJAX handlers registered with `add_action( 'wp_ajax_nopriv_*' )` allowing unauthenticated access
- Files: `inc/admin/ajax.php` (lines 46, 90, 215)
- Trigger: Direct requests to `wp-admin/admin-ajax.php` without login
- Impact: Unauthenticated users can attempt to trigger AJAX functions, though nonce verification should block them
- Risk: Combined with other logic errors, this exposes endpoints

## Security Considerations

**Unsanitized POST Data Usage:**
- Risk: Multiple instances of direct `$_POST` array access without sanitization or validation
- Files: `inc/admin/ajax.php` (lines 71, 126-128, 137, 140, 156, 184), `inc/admin/custom-meta-boxes.php` (lines 347-376, 382-383, 391, 395, 402)
- Current mitigation: WordPress nonce verification present in most cases
- Recommendations: Always sanitize/validate individual POST values; use `sanitize_text_field()`, `intval()`, `esc_url()` etc.

**Missing Nonce Field in Meta Box Save:**
- Risk: `speekr_save_mb()` checks for nonce existence but doesn't verify it specifically for this action
- Files: `inc/admin/custom-meta-boxes.php` (lines 335-337)
- Current mitigation: WordPress post save nonce exists but not explicitly verified in this handler
- Recommendations: Add explicit `wp_verify_nonce()` call with specific nonce action

**Unvalidated External API Calls:**
- Risk: Helper functions make HTTP requests to external services (Speakerdeck, Slideshare) without timeout or retry limits
- Files: `inc/functions/helpers.php` (lines 41-78)
- Current mitigation: `wp_remote_get()` used instead of `curl`, timeout defaults applied
- Recommendations: Add explicit timeout parameter; implement error/retry logic; validate response format before decoding JSON

**Direct File System Access Without Validation:**
- Risk: Template loader uses `opendir()` and `filetype()` directly on plugin templates directory
- Files: `inc/classes/Speekr_Templates_Loader.php` (lines 34-41)
- Current mitigation: Only runs on plugin directory which is trusted
- Recommendations: Use `glob()` instead of `opendir()`; add proper file validation

**Helpers Access Without Capability Checks:**
- Risk: `is_speekr_plugin_allowed_pages()` uses GET parameters without user capability verification
- Files: `inc/functions/helpers.php` (lines 138-144)
- Current mitigation: Function is only used for enqueue decisions, not permission gates
- Recommendations: Only use for UI elements; never use as access control; add `current_user_can()` checks where needed

## Performance Bottlenecks

**Unoptimized Template Discovery:**
- Problem: Template loader scans entire templates directory on every page template request, iterates all files
- Files: `inc/classes/Speekr_Templates_Loader.php` (lines 34-51)
- Cause: No caching of template list; runs during `theme_page_templates` filter on every admin page load
- Improvement path: Cache template list in transient with invalidation hook; use `glob()` instead of `opendir()` for efficiency

**External API Calls on Post Meta Access:**
- Problem: `speekr_get_talk_metas()` unserializes potentially large meta data on every call
- Files: `inc/functions/helpers.php` (lines 99-114)
- Cause: No caching; unserialize happens even if metadata not needed
- Improvement path: Add static caching within same request; implement lazy loading pattern

**Multiple Options Fetches:**
- Problem: `speekr_get_options()` called multiple times per request without caching
- Files: `inc/admin/settings.php` (line 8), `inc/admin/importer.php` (line 8), and throughout
- Cause: Global variable initialized but not always reused; each inclusion re-fetches
- Improvement path: Use WordPress settings API more consistently; rely on single global initialization

**HTTP Requests on Post Save:**
- Problem: Meta box save may trigger HTTP requests to external APIs without async handling
- Files: `inc/admin/custom-meta-boxes.php` (saves media links)
- Cause: Synchronous `wp_remote_get()` calls during post save
- Improvement path: Validate URLs only; fetch embeds asynchronously via background task

## Fragile Areas

**Post Importer Module:**
- Files: `inc/admin/ajax.php` (entire import section lines 101-213)
- Why fragile: Depends on broken `set_post_type()` reference, undefined `$set` variable, zero author ID issue, author assignment via POST bypass
- Safe modification: Add comprehensive error handling; implement retry mechanism; add unit tests for each import scenario
- Test coverage: No tests present; impossible to verify functionality

**Template Loader System:**
- Files: `inc/classes/Speekr_Templates_Loader.php`
- Why fragile: Property name mismatch causes silent failures; relies on file system operations without fallback
- Safe modification: Standardize property names; add logging/error reporting; test with various template directory states
- Test coverage: No tests; relies on manual verification during development

**Meta Box Handling:**
- Files: `inc/admin/custom-meta-boxes.php` (especially save function lines 333-410)
- Why fragile: Complex POST data processing with minimal validation; multiple serialized arrays; no transaction handling
- Safe modification: Break save function into smaller, testable functions; add schema validation before save
- Test coverage: No automated tests

**Settings Sanitization:**
- Files: `inc/admin/settings.php` (lines 201-220)
- Why fragile: Incomplete sanitizer; incomplete importer sanitizer; minimal validation of user choices
- Safe modification: Implement whitelist validation for all settings; add detailed comments about expected formats
- Test coverage: No tests for settings validation

## Scaling Limits

**Database Query Performance:**
- Current capacity: Suitable for installations with <5000 talks
- Limit: No pagination or indexing hints for large talk archives; REST API may timeout with large datasets
- Scaling path: Add custom table indexing; implement pagination in list queries; optimize featured image loading

**Serialized Meta Data:**
- Current capacity: Up to 64KB per post meta (MySQL limit)
- Limit: Media links array grows with each link added; no size validation
- Scaling path: Consider JSON storage; implement maximum link count validation

**Template Discovery Performance:**
- Current capacity: Acceptable with <50 template files
- Limit: File system scan on every page template filter hit
- Scaling path: Implement transient caching with invalidation

## Dependencies at Risk

**PHP Version Compatibility:**
- Risk: Code uses features from PHP 5.6+ (closures in `is_network()`, modern array syntax)
- Impact: Will fail on PHP <5.6; many WordPress hosts still support PHP 5.6
- Migration plan: Update code to use older compatible syntax or document minimum PHP requirement

**WordPress Version Requirements:**
- Risk: No version check in plugin header; uses `get_page_template_slug()` (added WP 4.7) without fallback
- Impact: Plugin breaks silently on older WordPress versions
- Migration plan: Add `Requires: 4.7+` to plugin header; implement fallback for older versions

**Deprecated WordPress Functions:**
- Risk: `get_file_data()` may change in future WordPress versions
- Impact: Template discovery could fail after WordPress updates
- Migration plan: Add defensive checks; consider alternative template discovery method

## Missing Critical Features

**No Error Logging:**
- Problem: Silent failures throughout; no way to debug production issues
- Blocks: Diagnosing import failures, template loading issues, API call problems
- Missing: Structured error logging; debug mode; error reporting

**No Input Validation Schema:**
- Problem: POST data validated inline with no schema reference
- Blocks: Cannot verify data consistency; easy to miss validation cases
- Missing: Centralized validation rules; data schema documentation

**No Transactional Safety:**
- Problem: Multi-step operations (import loop) can leave database in inconsistent state if interrupted
- Blocks: Recovery from failed imports; atomic operations
- Missing: Transaction support; rollback capability; state tracking

**No Capability Customization:**
- Problem: Hard-coded to `edit_users` capability check
- Blocks: Granular permission control for team workflows
- Missing: Plugin-specific capabilities; capability customization hooks

## Test Coverage Gaps

**Post Import Logic:**
- What's not tested: The entire import loop including author assignment, post type conversion, multiple iteration handling
- Files: `inc/admin/ajax.php` (lines 101-213)
- Risk: Broken functionality ships undetected; regressions introduced silently
- Priority: **High** - Critical user-facing feature

**Settings Sanitization:**
- What's not tested: Validation of layout values, color values, page selection
- Files: `inc/admin/settings.php` (lines 201-220)
- Risk: Invalid settings saved to database; UI may render incorrectly
- Priority: **High** - Core configuration feature

**Security Nonce Verification:**
- What's not tested: All AJAX nonce verification logic with both valid and invalid nonces
- Files: `inc/admin/ajax.php` (lines 17, 60, 104-107)
- Risk: Security holes introduced by logic errors go undetected
- Priority: **High** - Security critical

**Template Discovery and Loading:**
- What's not tested: Template discovery with various directory states, template selection, theme integration
- Files: `inc/classes/Speekr_Templates_Loader.php`
- Risk: Template features silently fail; users cannot select templates
- Priority: **Medium** - Feature quality impact

**Meta Box Data Serialization:**
- What's not tested: Saving/loading complex nested arrays, edge cases with empty values
- Files: `inc/admin/custom-meta-boxes.php` (lines 333-410)
- Risk: Data corruption, missing meta information, display errors
- Priority: **Medium** - Data integrity

---

*Concerns audit: 2026-02-28*
