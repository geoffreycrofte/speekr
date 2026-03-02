---
phase: 03-editor-blocks
plan: "02"
subsystem: ui
tags: [gutenberg, wordpress-blocks, registerPlugin, PluginDocumentSettingPanel, useEntityProp, apiFetch]

# Dependency graph
requires:
  - phase: 03-editor-blocks/03-01
    provides: meta key registration for speekr-summary, _speekr_media_*, speekr-conf, _speekr_conf_talk_ref on talks CPT

provides:
  - src/blocks/talk-meta/ — three editable sidebar panels (Summary, Media Links, Conference) plus read-only Appears In reverse-lookup panel for the Talks CPT
  - registerPlugin('speekr-talk-meta') guarded to talks post type only

affects:
  - 03-editor-blocks/03-05 (if any Talk block enqueue coordination needed)
  - phase-04-display-layer (summary and media link fields rendered in Talk display templates)
  - phase-04-display-layer (speekr-conf object consumed for conference attribution)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - PluginDocumentSettingPanel imported from @wordpress/editor (not @wordpress/edit-post)
    - TextareaControl for multi-line meta — RichText broken in sidebar panels since WP 6.5
    - useEntityProp spread pattern for all setMeta calls: setMeta({ ...meta, key: value })
    - apiFetch reverse lookup on mount for read-only relational panels

key-files:
  created:
    - src/blocks/talk-meta/block.json
    - src/blocks/talk-meta/index.js
    - src/blocks/talk-meta/edit.js
    - build/blocks/talk-meta/index.js
    - build/blocks/talk-meta/index.asset.php
    - build/blocks/talk-meta/block.json
  modified: []

key-decisions:
  - "TextareaControl for summary field — RichText broken in PluginDocumentSettingPanel since WP 6.5 (Gutenberg issue #60524, still unresolved in WP 6.9.1); plain text stored, formatting can be applied at render time in Phase 4"
  - "Appears In panel fetches up to 100 conferences on mount via apiFetch — acceptable limit for plugin's use case; filters by _speekr_conf_talk_ref === postId"

patterns-established:
  - "Talk meta panels use same registerPlugin + post type guard pattern as Conference and Speaker Profile blocks"
  - "Read-only reverse-lookup panel: useState/useEffect/apiFetch on mount, filter client-side by meta field match"

# Metrics
duration: 2min
completed: 2026-03-02
---

# Phase 3 Plan 02: Talk Meta Block Summary

**Talk edit sidebar built with four PluginDocumentSettingPanel components — Summary (TextareaControl), Media Links (YouTube/Vimeo/Slides), Conference (name/url object), and read-only Appears In reverse lookup via apiFetch**

## Performance

- **Duration:** ~2 min
- **Started:** 2026-03-02T16:00:48Z
- **Completed:** 2026-03-02T16:02:00Z
- **Tasks:** 2
- **Files modified:** 6 created

## Accomplishments

- `src/blocks/talk-meta/` scaffolded with block.json (apiVersion 3, editorScript only, no viewScript) and registerPlugin entry guarded to talks post type
- Four `PluginDocumentSettingPanel` components: Summary (TextareaControl), Media Links (3 URL TextControls), Conference (name/url object TextControls), Appears In (read-only apiFetch reverse lookup)
- All `setMeta` calls use spread pattern — no clobbering of other meta keys
- `npm run build` exits 0, `build/blocks/talk-meta/` contains compiled output

## Task Commits

Each task was committed atomically:

1. **Task 1: Scaffold block.json and index.js** - `d984d4b` (feat)
2. **Task 2: Build edit.js with four Talk meta panels** - `e98d233` (feat)

**Plan metadata:** _(docs commit follows)_

## Files Created/Modified

- `src/blocks/talk-meta/block.json` - Block metadata: apiVersion 3, editorScript only, speekr/talk-meta name
- `src/blocks/talk-meta/index.js` - registerPlugin entry point with talks post type guard
- `src/blocks/talk-meta/edit.js` - TalkMetaPanels component: four PluginDocumentSettingPanel instances
- `build/blocks/talk-meta/index.js` - Compiled block script
- `build/blocks/talk-meta/index.asset.php` - Block asset dependencies manifest
- `build/blocks/talk-meta/block.json` - Copied block metadata for WordPress registration

## Decisions Made

- TextareaControl used for summary field instead of RichText — RichText is broken in `PluginDocumentSettingPanel` since WP 6.5 (Gutenberg issue #60524, still unresolved). Plain text stored via `sanitize_textarea_field`. Rich text formatting can be applied at render time in Phase 4 without data migration.
- Appears In panel fetches up to 100 conferences on mount and filters client-side by `_speekr_conf_talk_ref === postId`. Acceptable ceiling for this plugin's use case; no debounce needed since it's a mount-only fetch.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Talk meta panels ready for testing in the block editor on the talks CPT
- Meta keys (`speekr-summary`, `_speekr_media_youtube`, `_speekr_media_vimeo`, `_speekr_media_slides`, `speekr-conf`) were registered in Plan 03-01 — panels save via REST immediately
- Phase 4 display layer can consume all four meta fields: summary as plain text, media links as URLs, speekr-conf object for conference attribution

---
*Phase: 03-editor-blocks*
*Completed: 2026-03-02*
