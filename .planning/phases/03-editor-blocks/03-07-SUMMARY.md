---
phase: 03-editor-blocks
plan: "07"
subsystem: admin-ui
tags: [wordpress-admin, admin-menu, cpt, php]

# Dependency graph
requires:
  - phase: 02-data-layer
    provides: speekr_speaker, speekr_conference, talks CPTs registered with show_in_rest
  - phase: 03-editor-blocks
    provides: block editor panels for all three CPTs
provides:
  - Top-level Speekr admin menu (slug 'speekr', dashicons-microphone)
  - All three CPTs (Talks, Conferences, Speakers) sub-listed under Speekr menu
  - Speaker CPT labels renamed from Speaker Profile(s) to Speaker(s)
affects: [04-public-templates, 05-fse-templates]

# Tech tracking
tech-stack:
  added: []
  patterns: [show_in_menu => 'speekr' for all plugin CPTs]

key-files:
  created:
    - inc/admin/admin-menu.php
  modified:
    - inc/classes/Speekr.php
    - inc/cpt/speaker-profile.php
    - inc/cpt/conferences.php
    - inc/common/custom-posts.php

key-decisions:
  - "Talks CPT is registered in inc/common/custom-posts.php (not inc/cpt/talks.php) — show_in_menu added there"
  - "admin-menu.php loaded at start of includes_admin() before other admin files to ensure menu registration happens early"
  - "Speaker Profile CPT label field renamed: 'Speaker Profile(s)' -> 'Speaker(s)' across all label keys"

patterns-established:
  - "New admin pages go in inc/admin/admin-menu.php and are loaded via Speekr::includes_admin()"
  - "All plugin CPTs use show_in_menu => 'speekr' to sub-list under the Speekr top-level menu"

# Metrics
duration: 5min
completed: 2026-03-02
---

# Phase 3 Plan 7: Admin Menu Reorganization Summary

**Speekr top-level admin menu (dashicons-microphone) with Talks, Conferences, and Speakers as sub-items; Speaker Profile CPT relabeled to Speakers**

## Performance

- **Duration:** ~5 min
- **Started:** 2026-03-02T16:50:00Z
- **Completed:** 2026-03-02T16:55:34Z
- **Tasks:** 2
- **Files modified:** 5

## Accomplishments
- Created `inc/admin/admin-menu.php` with `speekr_register_admin_menu()` top-level menu (slug 'speekr', dashicons-microphone icon, position 25) and overview landing page callback
- Added `show_in_menu => 'speekr'` to all three CPTs: Talks (custom-posts.php), Conferences, and Speaker Profile
- Renamed all Speaker Profile CPT labels from 'Speaker Profile(s)' to 'Speaker(s)' across 11 label keys

## Task Commits

Each task was committed atomically:

1. **Task 1: Create inc/admin/admin-menu.php with Speekr top-level menu** - `d0cf424` (feat)
2. **Task 2: Add show_in_menu to all CPTs + rename Speaker Profile labels** - `8873aa7` (feat)

**Plan metadata:** (docs commit to follow)

## Files Created/Modified
- `inc/admin/admin-menu.php` - New file: top-level Speekr menu registration and landing page callback
- `inc/classes/Speekr.php` - Wired admin-menu.php via require_once at start of includes_admin()
- `inc/cpt/speaker-profile.php` - Added show_in_menu => 'speekr'; renamed all labels to Speakers/Speaker
- `inc/cpt/conferences.php` - Added show_in_menu => 'speekr'
- `inc/common/custom-posts.php` - Changed show_in_menu from true to 'speekr' for Talks CPT

## Decisions Made
- Talks CPT registered in `inc/common/custom-posts.php` (not a separate talks.php) — `show_in_menu` updated there
- `admin-menu.php` loaded first in `includes_admin()` so the top-level menu slug is registered before any CPT submenus are evaluated
- Existing `inc/admin/menus.php` Settings/Import submenus left pointing to `edit.php?post_type=talks` as parent — they remain functional under Talks; only CPT list items move under the new Speekr menu

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- WordPress admin now has a clean Speekr top-level menu with all three CPTs grouped under it
- Speaker CPT displays as "Speakers" throughout admin UI
- No blockers for Phase 4 (public templates) introduced by this change

---
*Phase: 03-editor-blocks*
*Completed: 2026-03-02*
