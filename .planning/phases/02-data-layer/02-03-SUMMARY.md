---
phase: 02-data-layer
plan: 03
subsystem: database
tags: [wordpress, taxonomy, rest-api, block-editor, meta-boxes]

# Dependency graph
requires:
  - phase: 02-data-layer plan 01
    provides: Talks CPT with show_in_rest true
  - phase: 02-data-layer plan 02
    provides: Talks CPT editor and custom-fields supports enabled
provides:
  - speekr_topic taxonomy registered on Talks CPT with REST API support
  - block editor sidebar Topics panel
  - /wp-json/wp/v2/speekr_topic REST endpoint
  - speekr_save_mb gated against REST API double-fire data loss
affects: [phase-03-blocks, phase-04-frontend, phase-05-fse]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Use speekr_get_cpt_slug() for all CPT slug references — never hardcode 'talks'"
    - "Gate save_post callbacks with empty($_POST) before any POST field checks to prevent REST double-fire"
    - "wp_is_post_autosave() and wp_is_post_revision() as belt-and-suspenders guards after the $_POST gate"

key-files:
  created: []
  modified:
    - inc/admin/custom-meta-boxes.php

key-decisions:
  - "speekr_topic taxonomy: hierarchical: false (tag-like) with show_in_rest: true — enables block editor sidebar panel and /wp-json/wp/v2/speekr_topic endpoint without any additional code"
  - "REST API save guard uses empty($_POST) as primary check — REST requests have no POST body, making this a reliable and cheap guard; wp_is_post_autosave/revision added for edge cases where $_POST may be partially populated"
  - "Existing _wpnonce and DOING_AUTOSAVE checks retained after new guards — belt-and-suspenders for classic editor saves"

patterns-established:
  - "Taxonomy registration: separate named function + add_action('init') at top of file after ABSPATH guard"
  - "save_post guard order: empty($_POST) -> wp_is_post_autosave -> wp_is_post_revision -> nonce check -> capability check"

# Metrics
duration: 1min
completed: 2026-03-02
---

# Phase 2 Plan 03: Topics Taxonomy + REST Gate Summary

**speekr_topic tag-like taxonomy registered with REST/block-editor support, and speekr_save_mb gated against block editor data-loss via empty($_POST) guard**

## Performance

- **Duration:** ~1 min
- **Started:** 2026-03-01T23:26:38Z
- **Completed:** 2026-03-01T23:27:25Z
- **Tasks:** 2
- **Files modified:** 1

## Accomplishments
- `speekr_topic` taxonomy registered on the Talks CPT — appears in block editor sidebar as a standard tag panel immediately
- `/wp-json/wp/v2/speekr_topic` REST endpoint available for Phase 3 block queries
- `speekr_save_mb` protected by three early-return guards preventing block editor saves from overwriting meta with empty values
- All existing meta save logic (speekr-media-links, speekr-conf, speekr-summary, speekr-as-article, speekr-is-featured) unchanged

## Task Commits

Each task was committed atomically:

1. **Task 1: Register Topics taxonomy** - `a91e8c7` (feat)
2. **Task 2: Gate speekr_save_mb against REST API double-fire** - `e9a1173` (fix)

**Plan metadata:** (docs commit follows)

## Files Created/Modified
- `inc/admin/custom-meta-boxes.php` - Added speekr_register_topics_taxonomy() and three REST guards in speekr_save_mb

## Decisions Made
- `speekr_topic` registered with `hierarchical: false` (tag-like, not category-like) per plan spec
- `show_in_rest: true` is the only requirement for the block editor sidebar panel — no additional REST controller needed
- `apply_filters('speekr_topics_taxonomy_args', $args)` added for extensibility (same pattern as rest of codebase)
- `empty($_POST)` chosen as primary REST guard — REST API requests have no POST body at all, making this the most reliable signal

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None. Both tasks completed cleanly on first attempt.

## User Setup Required

None - no external service configuration required. No flush_rewrite_rules needed; WordPress flushes on plugin activation.

## Next Phase Readiness
- Phase 3 block development can begin: Topics taxonomy is queryable via REST, Talks CPT supports block editor, meta data loss on block saves is prevented
- Manual verification recommended: edit a Talk post in block editor, confirm Topics panel appears in sidebar, confirm existing meta persists after save

---
*Phase: 02-data-layer*
*Completed: 2026-03-02*
