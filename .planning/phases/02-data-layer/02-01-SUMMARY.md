---
phase: 02-data-layer
plan: 01
subsystem: database
tags: [wordpress, cpt, register_post_type, register_post_meta, rest-api, block-editor]

requires:
  - phase: 01-build-foundation
    provides: Plugin bootstrap (Speekr.php includes() method, SPEEKR_DIRNAME constant, ABSPATH guard pattern)

provides:
  - speekr_speaker CPT registered with show_in_rest: true and block editor support
  - Five post meta fields registered via register_post_meta() with REST API schemas
  - Sanitize functions for headshots, social_links, and rider structured fields

affects:
  - 02-data-layer (plans 02+) — all subsequent CPTs follow this registration pattern
  - Phase 3 (blocks) — blocks will read/write these meta fields via REST API
  - Phase 5 (FSE templates) — Speaker Profile CPT slug used in template registration

tech-stack:
  added: []
  patterns:
    - "CPT files live in inc/cpt/{slug}.php, loaded via require_once in Speekr::includes() (not includes_admin()) for REST API availability"
    - "Structured meta (arrays/objects) use expanded show_in_rest schema with items.properties and additionalProperties: false for schema strictness"
    - "Each meta field has auth_callback checking current_user_can('edit_posts')"
    - "Sanitize callbacks defined as named functions (not closures) for reliability with WordPress's internal meta sanitization"

key-files:
  created:
    - inc/cpt/speaker-profile.php
  modified:
    - inc/classes/Speekr.php

key-decisions:
  - "additionalProperties: false kept in headshots and social_links items schema for now — remove in Plan 03 verification if REST saves are rejected"
  - "require_once placed in includes() not includes_admin() — CPT must be registered on all requests including REST API"
  - "PHP binary not in PATH; used /Applications/MAMP/bin/php/php8.2.0/bin/php for linting"

patterns-established:
  - "CPT pattern: register_post_type() in named function hooked to init, file at inc/cpt/{slug}.php, loaded from Speekr::includes()"
  - "Meta pattern: register_post_meta() in separate named function hooked to init, expanded show_in_rest schema for object/array fields"

duration: 2min
completed: 2026-03-02
---

# Phase 2 Plan 01: Speaker Profile CPT + Meta Registration Summary

**speekr_speaker CPT with block editor support and five REST API-accessible post meta fields (headshots, bio_short, bio_long, social_links, rider) registered via register_post_meta() with expanded JSON schemas**

## Performance

- **Duration:** ~2 min
- **Started:** 2026-03-01T23:20:07Z
- **Completed:** 2026-03-01T23:22:00Z
- **Tasks:** 2
- **Files modified:** 2

## Accomplishments

- Created `inc/cpt/speaker-profile.php` with `speekr_speaker` CPT registration (`show_in_rest: true`, supports `editor` and `custom-fields`)
- Registered all five post meta fields with correct types and REST schemas: `_speekr_headshots`, `_speekr_bio_short`, `_speekr_bio_long`, `_speekr_social_links`, `_speekr_rider`
- Wired `speaker-profile.php` into `Speekr::includes()` after `custom-posts.php`, ensuring the CPT is available on REST API requests (not gated behind `is_admin()`)

## Task Commits

Each task was committed atomically:

1. **Task 1: Create inc/cpt/speaker-profile.php** - `46a3954` (feat)
2. **Task 2: Wire speaker-profile.php into Speekr::includes()** - `5bb4657` (feat)

## Files Created/Modified

- `inc/cpt/speaker-profile.php` — Speaker Profile CPT registration, five `register_post_meta()` calls, three sanitize functions
- `inc/classes/Speekr.php` — Added `require_once` for `inc/cpt/speaker-profile.php` in `includes()`

## Decisions Made

- `additionalProperties: false` kept in `_speekr_headshots` and `_speekr_social_links` items schema for schema strictness; plan notes to remove in Plan 03 if REST saves are rejected
- CPT `require_once` placed in `includes()` (not `includes_admin()`) so the post type is registered on all requests — required for REST API and front-end query access
- Used `/Applications/MAMP/bin/php/php8.2.0/bin/php` for PHP linting (PHP not in PATH in this shell environment)

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

- PHP binary not in PATH; located at `/Applications/MAMP/bin/php/php8.2.0/bin/php`. Used that directly for all lint checks. Both files passed with no syntax errors.

## User Setup Required

None - no external service configuration required. WordPress verification (admin menu, block editor, REST API endpoint) can be done manually after plugin reload in browser.

## Next Phase Readiness

- `speekr_speaker` CPT is registered and accessible via REST at `/wp-json/wp/v2/speekr_speaker`
- All five meta fields are registered with `show_in_rest` — ready for block editor consumption in Phase 3
- Pattern established for subsequent CPT registrations in Plans 02+ (Conferences, etc.)

---
*Phase: 02-data-layer*
*Completed: 2026-03-02*
