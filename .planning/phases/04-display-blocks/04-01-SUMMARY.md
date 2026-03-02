---
phase: 04-display-blocks
plan: "01"
subsystem: api
tags: [geocoding, nominatim, rest-api, ziparchive, post-meta, gutenberg]

# Dependency graph
requires:
  - phase: 03-editor-blocks
    provides: conference-meta edit.js with three PluginDocumentSettingPanel panels and meta/setMeta pattern
  - phase: 02-data-layer
    provides: speekr_conference CPT, _speekr_conf_city/_speekr_conf_country meta fields, speekr_speaker CPT
provides:
  - _speekr_conf_lat and _speekr_conf_lng post meta registered on speekr_conference (type=number, show_in_rest=true)
  - speekr_geocode_conference_on_save() hook: Nominatim geocoding on post save with failure transient
  - speekr_geocode_failure_notice() hook: admin notice surfacing geocoding failures
  - Conference editor Panel 4: manual lat/lng TextControl inputs when coordinates absent
  - REST endpoint GET /wp-json/speekr/v1/press-kit/{id} streaming speaker-kit ZIP
  - inc/front/press-kit.php loaded via Speekr::includes() (REST API-accessible)
affects: [04-02, 04-03, 04-04, 04-05, 04-06]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Nominatim geocoding via wp_remote_get with required User-Agent header, skip-if-already-geocoded guard
    - Transient-based failure signaling between save_post hook and admin_notices hook
    - ZipArchive streaming REST endpoint (exit after headers + readfile + unlink)
    - Conditional panel pattern: PluginDocumentSettingPanel shown only when meta condition is false

key-files:
  created:
    - inc/front/press-kit.php
  modified:
    - inc/cpt/conferences.php
    - src/blocks/conference-meta/edit.js
    - inc/classes/Speekr.php

key-decisions:
  - "Geocoding strategy resolved: Nominatim (OpenStreetMap) via wp_remote_get — no API key, GPL-compatible, skip-if-already-geocoded to minimize API hits"
  - "press-kit.php loaded via Speekr::includes() not includes_front() — REST API must load on admin requests too"
  - "Manual coordinates panel uses conditional render (!lat && !lng) rather than a separate block or settings page"

patterns-established:
  - "save_post_speekr_conference hook: autosave/revision/DOING_AUTOSAVE guards before any meta work"
  - "Transient key pattern: speekr_geocode_failed_{post_id} for cross-hook communication"
  - "ZipArchive REST endpoint: CREATE|OVERWRITE, addFile per attachment, addFromString for markdown, readfile+unlink, exit"

# Metrics
duration: 2min
completed: 2026-03-02
---

# Phase 4 Plan 01: PHP Foundation for Display Blocks Summary

**Nominatim geocoding + transient failure notice on Conference save, manual lat/lng panel in editor, and ZipArchive press-kit REST endpoint at speekr/v1/press-kit/{id}**

## Performance

- **Duration:** ~2 min
- **Started:** 2026-03-02T00:16:57Z
- **Completed:** 2026-03-02T00:19:05Z
- **Tasks:** 2
- **Files modified:** 4 (3 modified, 1 created)

## Accomplishments
- _speekr_conf_lat and _speekr_conf_lng registered on speekr_conference CPT with type=number and show_in_rest=true, exposed in WP REST API
- Nominatim geocoding hook fires on save_post_speekr_conference; skips autosaves, revisions, DOING_AUTOSAVE, already-geocoded posts; sets failure transient on error; stores lat/lng on success
- Admin notice surfaces geocoding failure to editor and clears the transient after display
- Conference editor sidebar gains Panel 4 (Coordinates Manual) with lat/lng TextControl inputs, visible only when both are absent (geocoding not yet run or failed)
- REST endpoint /wp-json/speekr/v1/press-kit/{id} streams a ZIP containing headshots/ folder and speaker-kit.md; returns WP_Error for invalid IDs or missing ZipArchive support
- press-kit.php wired into Speekr::includes() so REST API can access endpoint from both admin and frontend contexts

## Task Commits

Each task was committed atomically:

1. **Task 1: Geocoding meta + save_post hook + admin notice** - `51fceee` (feat)
2. **Task 2: Manual lat/lng panel + press-kit REST endpoint + bootstrap wiring** - `776ec3b` (feat)

**Plan metadata:** (docs commit follows)

## Files Created/Modified
- `inc/cpt/conferences.php` - Added Fields 7-8 (lat/lng meta), speekr_geocode_conference_on_save(), speekr_geocode_failure_notice()
- `src/blocks/conference-meta/edit.js` - Added Panel 4: Coordinates (Manual) with conditional render
- `inc/front/press-kit.php` - New file: REST endpoint registration + speekr_press_kit_download() callback
- `inc/classes/Speekr.php` - Added require_once for press-kit.php in includes()

## Decisions Made
- Geocoding strategy resolved in favor of Nominatim (OpenStreetMap) — no API key required, GPL-compatible, free usage within rate limits; skip-if-already-geocoded prevents redundant API calls on re-saves
- press-kit.php loaded via includes() (not includes_front()) because REST API requests arrive outside the is_admin() / else branch; consistent with CPT and blocks.php load pattern established in Phase 2
- Manual coordinates panel uses conditional render rather than a separate admin settings page or classic meta box — keeps all conference meta in the unified editor sidebar

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
- curl verification of the live REST endpoint was not possible from the shell environment (Local by Flywheel site not reachable via CLI); verified via PHP lint + grep on file contents instead. REST route registration pattern is identical to other working routes.

## User Setup Required
None - no external service configuration required. Nominatim is a public API requiring only a compliant User-Agent header (included).

## Next Phase Readiness
- lat/lng meta fields are registered and REST-accessible — Conference Map block (04-02) can use them directly via useEntityProp()
- press-kit endpoint is live — Speaker Profile block (04-06) can link to it when allowDownload attribute is true
- Geocoding failure notice will surface to editors with missing coordinates, prompting manual entry

## Self-Check: PASSED

All created/modified files exist on disk. Both task commits (51fceee, 776ec3b) confirmed in git log.

---
*Phase: 04-display-blocks*
*Completed: 2026-03-02*
