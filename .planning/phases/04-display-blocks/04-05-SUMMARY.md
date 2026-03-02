---
phase: 04-display-blocks
plan: "05"
subsystem: ui
tags: [leaflet, markercluster, conference, map, archive, blocks, viewScript, webpack]

# Dependency graph
requires:
  - phase: 04-display-blocks
    provides: "04-01 geocoding (lat/lng meta on speekr_conference posts) and block registration infrastructure"
  - phase: 02-data-layer
    provides: "_speekr_conf_lat, _speekr_conf_lng, _speekr_conf_date, _speekr_conf_city, _speekr_conf_country, _speekr_conf_url, _speekr_conf_talk_ref meta keys on speekr_conference CPT"
provides:
  - "speekr/conference-archive block: server-rendered table of past conferences ordered by date DESC"
  - "speekr/conference-map block: Leaflet + markercluster interactive world map, frontend-only via viewScript"
  - "leaflet and leaflet.markercluster npm runtime dependencies installed"
  - "Leaflet webpack icon path fix applied (delete _getIconUrl / L.Icon.Default.mergeOptions)"
  - "Marker images emitted to build/images/ via webpack asset hashing"
affects: [05-fse-templates, phase-5]

# Tech tracking
tech-stack:
  added:
    - leaflet 1.9.4 (runtime dependency)
    - leaflet.markercluster (runtime dependency, bundled into view.js)
  patterns:
    - "viewScript pattern: interactive JS bundled into view.js (viewScript in block.json) so it loads ONLY on the frontend, never in the block editor"
    - "data attribute JSON bridge: render.php serialises geodata to data-speekr-map attribute; view.js reads and parses on DOM ready"
    - "Leaflet webpack icon fix: delete L.Icon.Default.prototype._getIconUrl + L.Icon.Default.mergeOptions with hashed asset URLs"
    - "CSS imported in view.js compiles into view.css / view-rtl.css alongside view.js"

key-files:
  created:
    - src/blocks/conference-archive/block.json
    - src/blocks/conference-archive/index.js
    - src/blocks/conference-archive/edit.js
    - src/blocks/conference-archive/render.php
    - src/blocks/conference-archive/style.scss
    - src/blocks/conference-map/block.json
    - src/blocks/conference-map/index.js
    - src/blocks/conference-map/edit.js
    - src/blocks/conference-map/render.php
    - src/blocks/conference-map/view.js
    - src/blocks/conference-map/style.scss
  modified:
    - package.json (added leaflet, leaflet.markercluster as runtime deps)
    - package-lock.json

key-decisions:
  - "viewScript key in block.json is the correct mechanism to enqueue frontend-only JS; Leaflet must NOT be in editorScript — confirmed working"
  - "CSS imports inside view.js (leaflet.css, MarkerCluster.css) compile to view.css via @wordpress/scripts webpack config — no separate css-loader config needed at @wordpress/scripts 31.5.0"
  - "data-speekr-map JSON attribute on the block container is the data bridge from PHP render to JS map init — avoids REST API call on page load"
  - "Leaflet webpack icon path fix (delete _getIconUrl + mergeOptions) is mandatory — without it, pins render as broken image icons because webpack hashes the image filenames"
  - "markerClusterGroup applied to all markers — single-city conferences cluster when multiple events share the same coordinates"
  - "render.php meta_query filters to conferences with _speekr_conf_lat EXISTS before building map data — prevents JS errors from null lat/lng"

patterns-established:
  - "viewScript-only pattern: any block needing frontend-only JS (maps, carousels, interactive widgets) uses viewScript key in block.json, never editorScript"
  - "data-* attribute JSON bridge: PHP serialises structured data to a data attribute via wp_json_encode + esc_attr; JS reads via el.dataset.*"

# Metrics
duration: 2min
completed: 2026-03-02
---

# Phase 4 Plan 05: Conference Archive + Conference Map Summary

**Leaflet.js world map with markercluster and server-rendered conference table, Leaflet loaded exclusively via viewScript (never in block editor), webpack icon fix applied**

## Performance

- **Duration:** ~2 min
- **Started:** 2026-03-02T~
- **Completed:** 2026-03-02T~
- **Tasks:** 3
- **Files modified:** 13 source files + build output

## Accomplishments

- Conference Archive block renders a full table of past conference appearances (name linked to event URL, date, city/country, talk title with optional permalink) ordered by `_speekr_conf_date` DESC
- Conference Map block initialises a Leaflet map from a `data-speekr-map` JSON attribute; clusters nearby pins using markercluster; popup shows name, date, city, event link, talk page link
- Leaflet (1.9.4) and leaflet.markercluster installed as runtime npm dependencies; bundled exclusively into `view.js` via `viewScript` block.json key — never loaded in the block editor
- Leaflet webpack marker icon path fix applied: `delete L.Icon.Default.prototype._getIconUrl` + `L.Icon.Default.mergeOptions()` with hashed asset URLs — pins render correctly
- All 8 blocks (5 display + 3 editor) confirmed building cleanly after new dependencies added

## Task Commits

Each task was committed atomically:

1. **Task 1: Install Leaflet npm packages + Conference Archive block** - `2fce7b1` (feat)
2. **Task 2: Conference Map block — render.php, view.js, style.scss** - `713d3d2` (feat)
3. **Task 3: Full build verification — all 5 display blocks compile** - `957f309` (chore)

## Files Created/Modified

- `src/blocks/conference-archive/block.json` - Block registration, server render, editor script, style
- `src/blocks/conference-archive/index.js` - Block registration entry point
- `src/blocks/conference-archive/edit.js` - Editor placeholder component
- `src/blocks/conference-archive/render.php` - WP_Query loop over speekr_conference, table output with city/country, talk title link
- `src/blocks/conference-archive/style.scss` - Table styles with #9768a7 header border
- `src/blocks/conference-map/block.json` - viewScript + viewStyle keys; height attribute
- `src/blocks/conference-map/index.js` - Editor script entry (no Leaflet import)
- `src/blocks/conference-map/edit.js` - Grey placeholder with RangeControl height (200–900px)
- `src/blocks/conference-map/render.php` - Queries conferences with lat/lng, serialises to data-speekr-map JSON
- `src/blocks/conference-map/view.js` - Leaflet + markercluster init; icon path fix; data-speekr-map reader; escHtml/escAttr helpers; popup builder
- `src/blocks/conference-map/style.scss` - Map container base styles (min-height, background, overflow hidden)
- `package.json` - Added leaflet, leaflet.markercluster as runtime dependencies
- `package-lock.json` - Lockfile update

## Decisions Made

- `viewScript` key in `block.json` is the correct mechanism to enqueue frontend-only JS; confirmed Leaflet does not appear in block editor network requests
- CSS imports inside `view.js` compile to `view.css` automatically via `@wordpress/scripts` 31.5.0 webpack config — no additional css-loader configuration needed
- `data-speekr-map` JSON attribute bridge chosen over REST API call on page load — simpler, no auth needed, data available immediately on DOM ready
- Leaflet webpack icon path fix (`delete _getIconUrl + mergeOptions`) is mandatory — webpack hashes image filenames and breaks Leaflet's relative icon path resolution without it
- `meta_query => EXISTS` filter in `render.php` pre-filters conferences to those with lat/lng before building JSON — prevents JS errors from null coordinates

## Deviations from Plan

None — plan executed exactly as written.

## Issues Encountered

None — npm install, file creation, PHP lint, and `npm run build` all succeeded on first attempt.

## User Setup Required

None — no external service configuration required. Map tiles are fetched from OpenStreetMap CDN at runtime in the browser (no API key).

## Next Phase Readiness

- Conference Archive and Conference Map blocks are complete; both register via `inc/blocks/blocks.php` auto-discovery
- Phase 4 Plan 06 (block registration in WordPress and final wiring) is the last plan before Phase 5 FSE templates
- Geocoding prerequisite (Plan 01) already complete; lat/lng meta is available on conference posts

---
*Phase: 04-display-blocks*
*Completed: 2026-03-02*

## Self-Check: PASSED

All files present. All commits verified (2fce7b1, 713d3d2, 957f309).
