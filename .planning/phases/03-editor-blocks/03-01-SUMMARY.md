---
phase: 03-editor-blocks
plan: "01"
subsystem: blocks
tags: [gutenberg, block-registration, register_post_meta, rest-api, meta-boxes]

requires:
  - phase: 02-data-layer
    provides: Talks CPT registered with editor+custom-fields supports; speekr_get_cpt_slug() helper available

provides:
  - speekr_register_blocks() glob loop over build/blocks/*/block.json
  - speekr_register_talk_meta() registering 5 Talk meta keys for REST/block editor
  - inc/blocks/blocks.php wired into Speekr::includes() (runs on all requests incl. REST)
  - Classic Talks meta boxes hidden in block editor via __back_compat_meta_box: true

affects:
  - 03-editor-blocks (plans 02, 03, 04 — block implementations can now useEntityProp())
  - Phase 5 templates (meta keys available via REST for display logic)

tech-stack:
  added: []
  patterns:
    - "Block registration via glob loop over build/blocks/{name}/block.json — add a new block by building it; no PHP registration changes needed"
    - "Separate meta keys for block editor vs classic editor: _speekr_media_* (new REST keys) vs speekr-media-links (legacy serialised array, classic only)"
    - "register_post_meta() with auth_callback as anonymous function returning current_user_can('edit_posts')"

key-files:
  created:
    - inc/blocks/blocks.php
  modified:
    - inc/classes/Speekr.php
    - inc/admin/custom-meta-boxes.php

key-decisions:
  - "blocks.php loaded via Speekr::includes() (not includes_admin()) so block and meta registration runs on all requests including REST API"
  - "Block-editor media links use new _speekr_media_{youtube,vimeo,slides} keys; legacy speekr-media-links key NOT registered for REST to avoid schema complexity and REST 400 errors — two separate save paths"
  - "speekr-conf registered as object type with strict schema (additionalProperties: false) matching the existing classic-editor data structure"

patterns-established:
  - "Pattern: Any new block added to build/blocks/{name}/ is auto-registered — no PHP change needed"
  - "Pattern: __back_compat_meta_box: true hides classic meta boxes in Gutenberg while preserving them in classic editor"

duration: 2min
completed: 2026-03-02
---

# Phase 3 Plan 01: PHP Block Registration Foundation Summary

**glob-based block registration loop + 5 Talk meta keys registered for REST API, with classic meta boxes hidden in Gutenberg via __back_compat_meta_box**

## Performance

- **Duration:** ~2 min
- **Started:** 2026-03-02T00:44:06Z
- **Completed:** 2026-03-02T00:45:40Z
- **Tasks:** 2
- **Files modified:** 3

## Accomplishments

- Created `inc/blocks/blocks.php` with `speekr_register_blocks()` (glob loop over build/blocks/) and `speekr_register_talk_meta()` (5 Talk meta keys for REST)
- Wired `inc/blocks/blocks.php` into `Speekr::includes()` so registration happens on all requests including the REST API
- Added `__back_compat_meta_box: true` to the three Talks classic meta boxes (summary, media-links, conference) — they vanish from Gutenberg but remain in the classic editor

## Task Commits

Each task was committed atomically:

1. **Task 1: Create inc/blocks/blocks.php** - `b42c9f5` (feat)
2. **Task 2: Wire blocks.php into includes() and hide classic meta boxes** - `5eb79b9` (feat)

## Files Created/Modified

- `inc/blocks/blocks.php` - Block registration glob loop + Talk meta registration (5 keys)
- `inc/classes/Speekr.php` - Added require_once for blocks.php inside includes()
- `inc/admin/custom-meta-boxes.php` - __back_compat_meta_box: true on speekr-summary, speekr-media-links, speekr-conference

## Decisions Made

- `blocks.php` loaded in `includes()` not `includes_admin()` — blocks register on every request (REST, front-end, admin), matching the CPT/taxonomy pattern from Phase 2
- Block-editor media links use dedicated `_speekr_media_{youtube,vimeo,slides}` keys. The legacy `speekr-media-links` serialised array is left untouched in the classic editor path. Two save paths prevent REST 400 errors from schema mismatches.
- `speekr-conf` object meta uses `additionalProperties: false` for strictness, consistent with the Phase 2 pattern for structured meta schemas.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed docblock comment that prematurely closed PHP block comment**

- **Found during:** Task 1 verification (PHP lint)
- **Issue:** The `@param` docblock line `build/blocks/*/block.json` contained `*/` which PHP parsed as closing the block comment, causing a parse error on line 8
- **Fix:** Reworded the docblock to `build/blocks/{name}/block.json` — semantically equivalent, no `*/` sequence
- **Files modified:** `inc/blocks/blocks.php`
- **Verification:** `/Applications/MAMP/bin/php/php8.2.0/bin/php -l inc/blocks/blocks.php` → No syntax errors
- **Committed in:** `b42c9f5` (Task 1 commit)

---

**Total deviations:** 1 auto-fixed (Rule 1 — bug in docblock)
**Impact on plan:** Minor — only affected a comment string, no logic changes. PHP parse error caught immediately by lint step.

## Issues Encountered

None beyond the docblock lint error documented above.

## User Setup Required

None — no external service configuration required.

## Next Phase Readiness

- Block registration infrastructure is in place — `build/blocks/{name}/` directories will auto-register when built
- All 5 Talk meta keys exposed via REST API; `useEntityProp( 'postType', 'talks', 'speekr-summary' )` etc. will work in block editor JS
- Classic Talks meta boxes hidden in Gutenberg — block implementations in Plans 02-04 can replace them cleanly
- Ready for Plan 02: Talk Summary block implementation

---
*Phase: 03-editor-blocks*
*Completed: 2026-03-02*
