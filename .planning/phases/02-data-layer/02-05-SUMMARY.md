---
phase: 02-data-layer
plan: "05"
subsystem: database
tags: [wordpress, register_post_meta, rest-api, cpt, speekr_conference]

# Dependency graph
requires:
  - phase: 02-data-layer
    provides: Conference CPT (speekr_conference) registered with show_in_rest:true and custom-fields support
provides:
  - _speekr_conf_speakers meta field on speekr_conference with type=array, single=true, show_in_rest integer-items schema
affects: [03-editor-blocks, phase-3-block-editor]

# Tech tracking
tech-stack:
  added: []
  patterns: [register_post_meta with type=array and show_in_rest schema for useEntityProp() array access]

key-files:
  created: []
  modified: [inc/cpt/conferences.php]

key-decisions:
  - "single=true required for array meta when show_in_rest schema is used — WordPress needs single=true to expose arrays correctly via REST API for useEntityProp()"
  - "default=array() ensures _speekr_conf_speakers is never null in REST response — block code can safely spread or iterate without null-check"

patterns-established:
  - "Array meta pattern: type=array, single=true, show_in_rest with schema.items.type=integer, default=array()"

# Metrics
duration: 1min
completed: 2026-03-02
---

# Phase 2 Plan 05: _speekr_conf_speakers Meta Field Summary

**`_speekr_conf_speakers` registered on speekr_conference CPT as a REST-exposed integer array, enabling Phase 3 block editor code to read and write speaker IDs via `useEntityProp()`**

## Performance

- **Duration:** ~1 min
- **Started:** 2026-03-02T00:06:13Z
- **Completed:** 2026-03-02T00:06:48Z
- **Tasks:** 1
- **Files modified:** 1

## Accomplishments
- Appended Field 6 (`_speekr_conf_speakers`) inside `speekr_register_conference_meta()` in `inc/cpt/conferences.php`
- Field uses `type=array`, `single=true`, `show_in_rest` with `schema.items.type=integer`, and `default=array()`
- All five original conference meta fields remain unchanged
- PHP lint passes cleanly; `register_post_meta` count is now 6

## Task Commits

Each task was committed atomically:

1. **Task 1: Register _speekr_conf_speakers meta field** - `a5cc168` (feat)

**Plan metadata:** (see final commit below)

## Files Created/Modified
- `inc/cpt/conferences.php` - Added Field 6 (_speekr_conf_speakers) inside speekr_register_conference_meta()

## Decisions Made
- `single=true` is required even for array meta when `show_in_rest` schema is used — WordPress needs it to expose the value correctly via REST API for `useEntityProp()`. This is the established WordPress pattern for block-editor-accessible array meta.
- `default=array()` ensures the field is always present in REST responses (never null), so Phase 3 block code can safely iterate or spread it without a null check.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- `_speekr_conf_speakers` is registered and REST-accessible on `speekr_conference`
- Phase 3 block editor can call `useEntityProp('postType', 'speekr_conference', 'meta')` and read/write `_speekr_conf_speakers` as an array of integer post IDs
- The Conference CPT data layer is now complete: 5 scalar fields + 1 array field

---
*Phase: 02-data-layer*
*Completed: 2026-03-02*
