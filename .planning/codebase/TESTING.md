# Testing Patterns

**Analysis Date:** 2026-02-28

## Test Framework

**Runner:**
- Not detected - No test framework configured
- No test runner package in `package.json`
- No test configuration files found (`jest.config.js`, `phpunit.xml`, `vitest.config.js`)

**Assertion Library:**
- Not applicable - No testing infrastructure present

**Run Commands:**
```bash
npm run lint:js              # Only code quality check available (lints JS files)
```

## Test File Organization

**Location:**
- Not applicable - No test files present in codebase
- Test files would typically follow naming: `*.test.php`, `*.spec.js`
- No dedicated `tests/` directory exists

**Naming:**
- Not applicable

**Structure:**
- Not applicable

## Test Structure

**Suite Organization:**
- Not detected

**Patterns:**
- Not detected - No test patterns established in codebase

## Mocking

**Framework:**
- Not applicable - No testing framework configured

**Patterns:**
- Not applicable

**What to Mock:**
- Not applicable

**What NOT to Mock:**
- Not applicable

## Fixtures and Factories

**Test Data:**
- Not applicable

**Location:**
- Not applicable

## Coverage

**Requirements:** Not enforced - No coverage tooling configured

**View Coverage:**
- Not applicable

## Test Types

**Unit Tests:**
- Not implemented
- Candidates for unit testing:
  - `inc/functions/helpers.php`: Video ID extraction functions (`speekr_get_youtube_id()`, `speekr_get_vimeo_id()`)
  - `inc/functions/options.php`: Option getter/setter functions (`speekr_get_option()`, `speekr_update_option()`)
  - `inc/functions/usermeta.php`: User metadata functions (`speekr_get_user_meta()`, `speekr_update_user_meta()`)

**Integration Tests:**
- Not implemented
- Candidates for integration testing:
  - AJAX handlers in `inc/admin/ajax.php`: `speekr_create_default_page()`, `speekr_remove_notice()`, `speekr_import_posts()`
  - Meta box save operations in `inc/admin/custom-meta-boxes.php::speekr_save_mb()`
  - Post type registration and queries in `inc/common/custom-posts.php`

**E2E Tests:**
- Not used

## Common Test Patterns

**Where Tests Would Go:**
```
tests/
├── unit/
│   ├── functions/
│   │   ├── test-helpers.php
│   │   ├── test-options.php
│   │   └── test-usermeta.php
│   ├── classes/
│   │   └── test-speekr.php
│   └── admin/
│       └── test-ajax.php
├── integration/
│   ├── test-post-types.php
│   └── test-metaboxes.php
└── fixtures/
    └── sample-data.php
```

**Async Testing:**
- JavaScript AJAX operations use jQuery Promises `.done()` for async handling
- Example from `assets/js/speekr-admin.js`:
  ```javascript
  $.post(
      ajaxurl,
      { action: action, _wpnonce: $_this.data( 'nonce' ) }
  ).done( function( data ) {
      $_this.removeClass( 'speekr-loading' );
      if ( data.success === true ) {
          $_this.after( '<p class="speekr-success">' + data.data.message + '</p>' );
      } else {
          $_this.after( '<p class="speekr-error">' + data.data.message + '</p>' );
      }
  });
  ```

**Error Testing:**
- AJAX handlers return success/error via WordPress JSON responses
- Nonce verification pattern allows testing security:
  ```php
  if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'] , 'action_name' ) ) {
      // Success path
  } else {
      wp_send_json_error( $data );
  }
  ```

## Known Testing Gaps

**Core Functionality Not Tested:**

1. **AJAX Import Function** (`inc/admin/ajax.php::speekr_import_posts()`)
   - Lines 101-213: Critical post import logic
   - Handles loops, post type conversion, author assignment
   - No verification that posts actually convert to talks CPT
   - No test for incomplete loop recovery

2. **Template Loading** (`inc/classes/Speekr_Templates_Loader.php`)
   - File system operations with `opendir()` - error cases not tested
   - Path resolution logic for template selection

3. **Video Embedding Functions** (`inc/functions/helpers.php`)
   - External API calls to Speakerdeck, Slideshare oEmbed endpoints
   - No test for malformed URLs or API failures
   - JSON parsing assumes valid structure

4. **Option Filtering** (`inc/functions/options.php`)
   - Network vs single-site logic branches - cross-environment testing needed
   - Pre/post filter hooks may interfere with tests

5. **Frontend Display** (`inc/front/lists.php`, `inc/front/single.php`)
   - Post template rendering with missing metadata
   - Fallback behavior when meta not set

## Recommendations for Test Implementation

**Priority 1 - Security Critical:**
- AJAX nonce verification
- User capability checks
- Input sanitization in metabox save operations

**Priority 2 - Data Integrity:**
- Option management (get/update/delete cycles)
- Post meta serialization/unserialization
- Post import/conversion workflow

**Priority 3 - Integration:**
- Template file loading and fallback resolution
- External API interactions (with mocked responses)
- Multi-site option handling

---

*Testing analysis: 2026-02-28*
