---
phase: 02-data-layer
plan: 02
subsystem: database
tags: [wordpress, cpt, rest-api, block-editor, post-meta]

# Dependency graph
requires:
  - phase: 02-data-layer plan 01
    provides: inc/cpt/speaker-profile.php and inc/cpt/ directory structure
  - phase: 01-build-foundation
    provides: Speekr class with includes() method and require_once pattern
provides:
  - Conferences CPT (speekr_conference) with block editor and REST API support
  - Five Conference post meta fields exposed via REST API
  - Talks CPT updated with show_in_rest: true, editor, and custom-fields in supports
  - Both CPTs ready for Phase 3 block useEntityProp() usage
affects: [03-block-editor, 04-map-feature, 05-fse-templates]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - CPT files live in inc/cpt/{slug}.php, loaded via require_once in Speekr::includes()
    - All CPT meta fields registered with show_in_rest: true, sanitize_callback, and auth_callback
    - register_post_type and register_post_meta both hooked to 'init' as separate functions in same file

key-files:
  created:
    - inc/cpt/conferences.php
  modified:
    - inc/common/custom-posts.php
    - inc/classes/Speekr.php

key-decisions:
  - "Conferences CPT key is 'speekr_conference' (underscore) — Phase 5 must verify template name compatibility before FSE template registration (WP rejects underscores in template names)"
  - "Talks CPT editor and custom-fields supports were previously commented out — both activated to enable block editor and useEntityProp() in Phase 3"

patterns-established:
  - "All meta fields: single: true, show_in_rest: true, sanitize_callback, auth_callback pattern"
  - "CPT registration and meta registration are separate functions in the same file, both hooked to init"

# Metrics
duration: 3min
completed: 2026-03-02
---

# Phase 2 Plan 02: Conferences CPT + Talks CPT Block Editor Readiness Summary

**speekr_conference CPT registered with five REST-exposed meta fields; Talks CPT updated with show_in_rest, editor, and custom-fields supports for block editor and useEntityProp() access**

## Performance

- **Duration:** ~3 min
- **Started:** 2026-03-02T00:43:42Z
- **Completed:** 2026-03-02T00:46:30Z
- **Tasks:** 2
- **Files modified:** 3 (1 created, 2 updated)

## Accomplishments

- Created `inc/cpt/conferences.php` with `speekr_conference` CPT registration (`show_in_rest: true`, block editor supports) and five `register_post_meta()` calls for `_speekr_conf_date`, `_speekr_conf_city`, `_speekr_conf_country`, `_speekr_conf_url`, `_speekr_conf_talk_ref`
- Updated Talks CPT in `inc/common/custom-posts.php` to add `show_in_rest: true` and uncomment `editor` and `custom-fields` in supports array — enabling block editor (was classic editor before this plan)
- Wired `conferences.php` into `Speekr::includes()` via `require_once` immediately after `speaker-profile.php`

## Task Commits

Each task was committed atomically:

1. **Task 1: Create inc/cpt/conferences.php** - `8abcd57` (feat)
2. **Task 2: Update Talks CPT + bootstrap wire-up** - `8bc4e6f` (feat)

**Plan metadata:** (docs commit follows)

## Files Created/Modified

- `inc/cpt/conferences.php` - Conferences CPT registration and five post meta field registrations
- `inc/common/custom-posts.php` - Talks CPT: added show_in_rest, uncommented editor + custom-fields in supports
- `inc/classes/Speekr.php` - Added require_once for conferences.php in includes() method

## Decisions Made

- Conferences CPT key is `speekr_conference` (underscore) consistent with project naming — Phase 5 must verify FSE template name compatibility (WP rejects underscores in template names per existing blocker in STATE.md)
- Talks CPT `editor` and `custom-fields` were previously commented out; both activated here as they are required for block meta to be writable via `useEntityProp()` in Phase 3 blocks

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Both CPTs are block-editor-ready with `show_in_rest: true` and `custom-fields` in supports
- All five Conference meta fields exposed via REST API at `/wp-json/wp/v2/speekr_conference`
- Talks CPT REST endpoint at `/wp-json/wp/v2/talks` now exposes meta fields
- Manual WordPress verification still needed (browser): Conferences menu item, block editor loads for both CPTs, REST endpoints return data
- Phase 3 blocks can now use `useEntityProp()` for both Talks and Conferences CPTs

---
*Phase: 02-data-layer*
*Completed: 2026-03-02*

## Self-Check: PASSED

- FOUND: inc/cpt/conferences.php
- FOUND: inc/common/custom-posts.php
- FOUND: inc/classes/Speekr.php
- FOUND: .planning/phases/02-data-layer/02-02-SUMMARY.md
- FOUND: commit 8abcd57 (Task 1)
- FOUND: commit 8bc4e6f (Task 2)
