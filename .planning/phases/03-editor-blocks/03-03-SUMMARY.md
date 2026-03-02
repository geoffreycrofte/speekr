---
phase: 03-editor-blocks
plan: "03"
subsystem: ui
tags: [gutenberg, block-editor, react, wordpress-plugins, PluginDocumentSettingPanel, apiFetch]

# Dependency graph
requires:
  - phase: 02-data-layer
    provides: "_speekr_conf_date, _speekr_conf_city, _speekr_conf_country, _speekr_conf_url, _speekr_conf_talk_ref, _speekr_conf_speakers registered for REST API via show_in_rest"
  - phase: 03-editor-blocks plan 01
    provides: "blocks.php block registration infrastructure; speekr/conference-meta block registered server-side"
provides:
  - "src/blocks/conference-meta/block.json — apiVersion 3, editorScript only, no viewScript"
  - "src/blocks/conference-meta/index.js — registerPlugin with speekr_conference post type guard"
  - "src/blocks/conference-meta/edit.js — three PluginDocumentSettingPanel components: Conference Details, Talk Reference, Speakers"
  - "build/blocks/conference-meta/ — compiled JS and asset manifest"
affects: [04-display-blocks, 05-fse-templates]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "registerPlugin with useSelect post-type guard to restrict sidebar panels to a single CPT"
    - "useEntityProp('postType', postType, 'meta') + spread setMeta pattern for safe meta updates"
    - "apiFetch with addQueryArgs + 300ms debounced useEffect for search-as-you-type REST queries"
    - "Speaker ID array: local name cache (id -> title) fetched once for uncached IDs on mount"

key-files:
  created:
    - src/blocks/conference-meta/block.json
    - src/blocks/conference-meta/index.js
    - src/blocks/conference-meta/edit.js
    - build/blocks/conference-meta/index.js
    - build/blocks/conference-meta/index.asset.php
    - build/blocks/conference-meta/block.json
  modified: []

key-decisions:
  - "All state hooks (talk search, speaker search, names cache) live in a single ConferenceMetaPanels component rather than per-panel sub-components — avoids prop-drilling meta/setMeta while keeping file count minimal"
  - "Speaker name cache uses setSpeakerNames functional update ((prev) => ({...prev, ...newNames})) to avoid stale closure issues with concurrent fetches"
  - "selectedTalkId/selectedSpeakers derive from meta at render time (not in separate useState) so they always reflect the latest persisted value"

patterns-established:
  - "Post-type guard pattern: registerPlugin wraps component that returns null if postType !== target — cleaner than filtering in each panel"
  - "Spread setMeta: always { ...meta, key: value } — never just { key: value } — prevents wiping sibling meta keys"
  - "Search-as-you-type: useRef for debounce timer, clearTimeout on each keystroke, 300ms delay, min 2 chars before firing"

# Metrics
duration: 1min
completed: 2026-03-02
---

# Phase 3 Plan 03: Conference Meta Block Summary

**Three Gutenberg sidebar panels for Conference CPT — Details (date/city/country/URL), Talk Reference (search-as-you-type single picker via /wp/v2/talks), and Speakers (multi-select search via /wp/v2/speekr_speaker with add/remove list) — all wired to REST-exposed meta keys via useEntityProp spread pattern**

## Performance

- **Duration:** ~1 min
- **Started:** 2026-03-02T15:53:55Z
- **Completed:** 2026-03-02T15:55:00Z
- **Tasks:** 2
- **Files modified:** 6 (3 source + 3 build)

## Accomplishments

- Conference Details panel with 4 TextControl fields (date, city, country, event URL) writing to their respective meta keys via spread setMeta
- Talk Reference panel with debounced search-as-you-type against /wp/v2/talks, single selection with title display and Clear button
- Speakers panel with debounced multi-select search against /wp/v2/speekr_speaker, per-speaker name cache, add/remove list with X buttons
- All panels restricted to speekr_conference post type via registerPlugin guard — invisible on Talk or Speaker Profile screens
- npm run build exits 0; build/blocks/conference-meta/ contains index.js, index.asset.php, block.json

## Task Commits

1. **Task 1: Scaffold block.json and index.js** - `7711fe6` (feat) — committed in prior session
2. **Task 2: Build edit.js with three Conference meta panels** - `7275b5d` (feat)

## Files Created/Modified

- `src/blocks/conference-meta/block.json` — Block metadata: apiVersion 3, editorScript only, no viewScript or attributes
- `src/blocks/conference-meta/index.js` — registerPlugin entry point with speekr_conference post type guard
- `src/blocks/conference-meta/edit.js` — ConferenceMetaPanels: three PluginDocumentSettingPanel components with full search, state, and meta write logic
- `build/blocks/conference-meta/index.js` — Compiled and minified block editor script
- `build/blocks/conference-meta/index.asset.php` — Asset manifest with dependency array for wp_enqueue_script
- `build/blocks/conference-meta/block.json` — Copied block metadata for runtime registration

## Decisions Made

- All state hooks consolidated in a single ConferenceMetaPanels component rather than splitting into per-panel sub-components — avoids prop-drilling meta/setMeta and keeps the file count at three as specified
- Speaker name cache uses functional setState update to avoid stale closure issues when multiple fetches overlap
- `selectedTalkId` and `selectedSpeakers` derived directly from meta at render time (not mirrored in separate useState) — ensures displayed values always reflect persisted state

## Deviations from Plan

None — plan executed exactly as written. Task 1 had been committed in a prior partial execution; Task 2 (edit.js) was the remaining work.

## Issues Encountered

None. Build compiled cleanly on first run with no warnings or errors related to conference-meta code.

## User Setup Required

None — no external service configuration required.

## Next Phase Readiness

- Conference meta block is complete and ready for editor testing
- Three panels (Details, Talk Reference, Speakers) will appear only on speekr_conference edit screens
- Next plans in phase 03 can proceed: Talk meta block (03-04) and Speaker Profile meta block (03-05)
- Phase 4 (display blocks) can reference _speekr_conf_talk_ref and _speekr_conf_speakers knowing the edit-side UI exists

---
*Phase: 03-editor-blocks*
*Completed: 2026-03-02*

## Self-Check: PASSED

- FOUND: src/blocks/conference-meta/block.json
- FOUND: src/blocks/conference-meta/index.js
- FOUND: src/blocks/conference-meta/edit.js
- FOUND: build/blocks/conference-meta/index.js
- FOUND: build/blocks/conference-meta/index.asset.php
- FOUND: commit 7711fe6 (Task 1 — scaffold block.json and index.js)
- FOUND: commit 7275b5d (Task 2 — edit.js with three Conference meta panels)
