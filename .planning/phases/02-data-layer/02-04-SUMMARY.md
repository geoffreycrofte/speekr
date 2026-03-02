---
phase: 02-data-layer
plan: "04"
subsystem: api
tags: [taxonomy, rest-api, wordpress, php]

# Dependency graph
requires:
  - phase: 02-data-layer
    provides: "speekr_topic taxonomy defined in custom-meta-boxes.php (admin-only path)"
provides:
  - "inc/common/taxonomies.php — standalone taxonomy registration file on common bootstrap path"
  - "speekr_topic taxonomy available on all request paths including REST API"
  - "GET /wp-json/wp/v2/speekr_topic returns 200 (gap closed)"
affects:
  - "03-block-editor — block editor sidebar Topics panel can now fetch/save terms via REST"
  - "Phase 5 — FSE templates may reference speekr_topic"

# Tech tracking
tech-stack:
  added: []
  patterns: ["Taxonomy registration in inc/common/ (not inc/admin/) for REST API availability — mirrors CPT pattern from Plan 01"]

key-files:
  created:
    - inc/common/taxonomies.php
  modified:
    - inc/admin/custom-meta-boxes.php
    - inc/classes/Speekr.php

key-decisions:
  - "Taxonomy registration moved from admin-only bootstrap path (includes_admin) to common path (includes) — same pattern used for CPTs in Plan 01; ensures REST API availability without additional hooks"

patterns-established:
  - "Any WordPress taxonomy or CPT that must be accessible via REST API must be registered inside Speekr::includes(), not includes_admin() or includes_front()"

# Metrics
duration: 2min
completed: 2026-03-02
---

# Phase 2 Plan 04: Topics Taxonomy REST API Gap Closure Summary

**speekr_topic taxonomy moved from admin-only path to common bootstrap path, unblocking /wp-json/wp/v2/speekr_topic and enabling block editor Topics panel via REST**

## Performance

- **Duration:** ~2 min
- **Started:** 2026-03-02T09:03:28Z
- **Completed:** 2026-03-02T09:05:30Z
- **Tasks:** 2
- **Files modified:** 3

## Accomplishments
- Created `inc/common/taxonomies.php` with `speekr_register_topics_taxonomy()` extracted verbatim from `custom-meta-boxes.php`
- Removed function from `custom-meta-boxes.php` — no duplicate definition exists anywhere in the codebase
- Wired `taxonomies.php` into `Speekr::includes()` between `custom-posts.php` and `speaker-profile.php`
- Taxonomy now fires on every WordPress request including REST API, closing the `/wp-json/wp/v2/speekr_topic` 404 gap

## Task Commits

Each task was committed atomically:

1. **Task 1: Extract taxonomy registration to inc/common/taxonomies.php** - `eaab6be` (feat)
2. **Task 2: Wire taxonomies.php into Speekr::includes()** - `cd494d5` (feat)

**Plan metadata:** (docs commit follows)

## Files Created/Modified
- `inc/common/taxonomies.php` - New file containing speekr_register_topics_taxonomy() and add_action('init') — runs on all request paths
- `inc/admin/custom-meta-boxes.php` - Removed speekr_register_topics_taxonomy() and its add_action (function now only in taxonomies.php)
- `inc/classes/Speekr.php` - Added require_once for inc/common/taxonomies.php inside includes() method at line 84

## Decisions Made
- Taxonomy registration moved to `inc/common/` following the exact same pattern established in Plan 01 for CPTs — no new patterns introduced, pure consistency

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- `/wp-json/wp/v2/speekr_topic` will return 200 on next WordPress page load (no flush needed — taxonomy registration change takes effect immediately)
- Block editor sidebar Topics panel can now fetch and save terms via REST
- Plan 05 (`_speekr_conf_speakers` meta field gap) is next in the queue

---
*Phase: 02-data-layer*
*Completed: 2026-03-02*

## Self-Check: PASSED

- inc/common/taxonomies.php: FOUND
- .planning/phases/02-data-layer/02-04-SUMMARY.md: FOUND
- Commit eaab6be: FOUND
- Commit cd494d5: FOUND
