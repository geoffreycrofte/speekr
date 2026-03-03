---
phase: 05-templates-and-compatibility
plan: "01"
subsystem: api
tags: [wordpress, php, hooks, filters, extensibility, do_action, apply_filters]

# Dependency graph
requires:
  - phase: 04-display-blocks
    provides: "Five server-rendered block render.php files with complete frontend output"
provides:
  - "speekr_before_profile / speekr_after_profile action hooks in speaker-profile/render.php"
  - "speekr_before_single_talk / speekr_after_single_talk action hooks in single-talk/render.php"
  - "speekr_before_map / speekr_after_map action hooks in conference-map/render.php"
  - "speekr_before_conference_archive / speekr_after_conference_archive action hooks in conference-archive/render.php"
  - "speekr_before_talks_list / speekr_after_talks_list action hooks in talks-list/render.php"
  - "speekr_talk_output filter for per-card HTML modification in talks-list/render.php"
affects: [shortcodes, templates, developer-docs]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "do_action( 'speekr_before_{block}', $post_id_or_zero, $attributes ) / do_action( 'speekr_after_{block}', ... ) wrapping all block output"
    - "apply_filters( 'speekr_talk_output', $html, $talk_post, $attributes ) via local ob_start() inside foreach loop — safe nested buffer pattern"
    - "Blocks with no single post context (talks-list, conference-archive, conference-map) pass 0 as $post_id argument to hooks"

key-files:
  created: []
  modified:
    - src/blocks/speaker-profile/render.php
    - src/blocks/single-talk/render.php
    - src/blocks/conference-map/render.php
    - src/blocks/conference-archive/render.php
    - src/blocks/talks-list/render.php

key-decisions:
  - "Blocks without a single post context (talks-list, conference-archive, conference-map) pass 0 as the $post_id argument to all hooks — consistent, predictable API"
  - "speekr_talk_output filter uses local ob_start() inside the foreach loop only (not wrapping the whole file) — safe nested buffer that resolves before WordPress outer buffer collects render.php output"
  - "Hook signatures follow speekr_before_{block}( $post_id, $attributes ) pattern — $attributes always passed as second arg for maximum flexibility"

patterns-established:
  - "Developer action hooks: do_action( 'speekr_before_{block}', $post_id_or_0, $attributes ) immediately before the opening wrapper element"
  - "Developer action hooks: do_action( 'speekr_after_{block}', $post_id_or_0, $attributes ) immediately after the closing wrapper element"
  - "Per-item output filter: ob_start() before article HTML, ob_get_clean() + apply_filters() after — captures only the single card, not entire block output"

requirements-completed: [DEV-03]

# Metrics
duration: 2min
completed: 2026-03-03
---

# Phase 5 Plan 01: Developer Hooks Summary

**10 action hooks (before/after pairs for all 5 blocks) and 1 speekr_talk_output filter added across all display block render.php files**

## Performance

- **Duration:** ~2 min
- **Started:** 2026-03-03T00:34:04Z
- **Completed:** 2026-03-03T00:36:08Z
- **Tasks:** 2
- **Files modified:** 5

## Accomplishments
- Added `speekr_before_profile` / `speekr_after_profile` hooks to speaker-profile/render.php
- Added `speekr_before_single_talk` / `speekr_after_single_talk` hooks to single-talk/render.php
- Added `speekr_before_map` / `speekr_after_map` hooks to conference-map/render.php
- Added `speekr_before_conference_archive` / `speekr_after_conference_archive` hooks to conference-archive/render.php
- Added `speekr_before_talks_list` / `speekr_after_talks_list` hooks and `speekr_talk_output` filter to talks-list/render.php
- All hooks fire from the shared render.php code path (covers both block render and future shortcode paths)

## Task Commits

Each task was committed atomically:

1. **Task 1: Add before/after hooks to speaker-profile, single-talk, conference-map** - `55a7e40` (feat)
2. **Task 2: Add before/after hooks and speekr_talk_output filter to conference-archive and talks-list** - `4ef9e35` (feat)

**Plan metadata:** (docs commit — see below)

## Files Created/Modified
- `src/blocks/speaker-profile/render.php` - Added speekr_before_profile / speekr_after_profile hooks
- `src/blocks/single-talk/render.php` - Added speekr_before_single_talk / speekr_after_single_talk hooks
- `src/blocks/conference-map/render.php` - Added speekr_before_map / speekr_after_map hooks
- `src/blocks/conference-archive/render.php` - Added speekr_before_conference_archive / speekr_after_conference_archive hooks
- `src/blocks/talks-list/render.php` - Added speekr_before_talks_list / speekr_after_talks_list hooks + speekr_talk_output filter wrapping each article card

## Decisions Made
- Blocks without a single post context (talks-list, conference-archive, conference-map) pass `0` as the `$post_id` argument to all hooks — consistent, predictable API for developers hooking these actions.
- `speekr_talk_output` filter uses a local `ob_start()` inside the foreach loop only (not wrapping the entire file). This is safe: the inner buffer resolves before WordPress's outer buffer collects the full render.php output.
- Hook signatures follow `speekr_before_{block}( $post_id, $attributes )` — `$attributes` always passed as second argument so developers can conditionally apply logic based on block settings.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- All 5 block render.php files now have developer hooks — ready for shortcode registration (05-02) which will share the same render paths and automatically inherit these hooks.
- speekr_talk_output filter is ready for use by child themes or third-party plugins.

## Self-Check: PASSED

All claimed files exist. All task commits verified in git log.

---
*Phase: 05-templates-and-compatibility*
*Completed: 2026-03-03*
