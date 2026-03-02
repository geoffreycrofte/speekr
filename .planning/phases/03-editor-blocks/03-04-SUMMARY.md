---
phase: 03-editor-blocks
plan: "04"
subsystem: ui
tags: [gutenberg, block-editor, wordpress, jsx, webpack, speaker-profile, media-upload]

# Dependency graph
requires:
  - phase: 02-data-layer
    provides: "_speekr_headshots, _speekr_bio_short, _speekr_bio_long, _speekr_social_links, _speekr_rider meta keys registered for REST API"
  - phase: 03-editor-blocks
    provides: "blocks.php block registration infrastructure, @wordpress/scripts webpack config"
provides:
  - "src/blocks/speaker-profile-meta/ (block.json, index.js, edit.js) — four PluginDocumentSettingPanel sidebar panels for Speaker Profile CPT"
affects: [04-display-blocks, phase-5]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "PluginDocumentSettingPanel via registerPlugin — same pattern as conference-meta block"
    - "Arrow-button reorder (↑/↓) as accessible alternative to drag-and-drop for ordered lists"
    - "MediaUploadCheck + MediaUpload from @wordpress/block-editor for media library integration"
    - "useState for local add-form state; useEntityProp for persisted meta state"
    - "TextareaControl for bio/rider fields due to RichText breakage in PluginDocumentSettingPanel (Gutenberg #60524)"

key-files:
  created:
    - src/blocks/speaker-profile-meta/block.json
    - src/blocks/speaker-profile-meta/index.js
    - src/blocks/speaker-profile-meta/edit.js
  modified: []

key-decisions:
  - "Arrow-button reorder (↑/↓) used instead of drag-and-drop for Headshots panel — simpler, accessible, sufficient for MVP; drag upgrade deferred"
  - "TextareaControl for bio and rider fields — RichText broken in PluginDocumentSettingPanel since WP 6.5 (Gutenberg issue #60524, still unresolved); plain text stored, formatting applied at render in Phase 4"
  - "PLATFORMS list contains exactly 8 fixed platforms + Other per CONTEXT.md: LinkedIn, Facebook, Instagram, Bluesky, Mastodon, X, GitHub, Personal website"
  - "Headshots rendered as 'Attachment #ID' text with label TextControl — image preview via REST/apiFetch deferred as post-MVP enhancement"

patterns-established:
  - "All setMeta calls use spread pattern ({ ...meta, fieldKey: value }) to prevent clobbering other meta fields"
  - "Local add-form state (useState) + persisted state (useEntityProp) separation for Social Links add form"
  - "Rider object updated via updateRider helper: setMeta({ ...meta, _speekr_rider: { ...rider, [key]: value } })"

# Metrics
duration: 4min
completed: 2026-03-02
---

# Phase 3 Plan 04: Speaker Profile Meta Block Summary

**Four PluginDocumentSettingPanel sidebar panels for Speaker Profile CPT — Headshots (MediaUpload + arrow reorder + per-shot labels), Bio (short/long TextareaControl), Social Links (9-option dropdown + URL + add/remove list), and Rider (4 TextareaControls for AV/tech, Travel, Dietary, Accessibility)**

## Performance

- **Duration:** ~4 min
- **Started:** 2026-03-02T00:00:00Z
- **Completed:** 2026-03-02T00:04:00Z
- **Tasks:** 2
- **Files modified:** 3

## Accomplishments

- Speaker Profile edit screen now shows four sidebar panels covering all profile metadata (Headshots, Bio, Social Links, Rider)
- MediaUpload integration from @wordpress/block-editor with MediaUploadCheck wrapper for proper permission gating
- Social links add-flow with 9 platform options (8 fixed + Other) matches CONTEXT.md specification exactly
- Primary badge on first headshot, arrow reorder controls, and label fields per headshot all implemented
- Build exits 0; build/blocks/speaker-profile-meta/ output confirmed

## Task Commits

1. **Task 1: Scaffold speaker-profile-meta block.json and index.js** — `0c8bf20` (feat)
2. **Task 2: Build edit.js with four Speaker Profile meta panels** — `d743452` (feat)

## Files Created/Modified

- `src/blocks/speaker-profile-meta/block.json` — apiVersion 3, editorScript only, no viewScript or save
- `src/blocks/speaker-profile-meta/index.js` — registerPlugin with speekr_speaker post type guard
- `src/blocks/speaker-profile-meta/edit.js` — SpeakerProfileMetaPanels component with four PluginDocumentSettingPanel panels

## Decisions Made

- **Arrow-button reorder instead of drag-and-drop:** Draggable from @wordpress/components requires significant DOM wiring for drop targets; ↑/↓ buttons are simpler, accessible, and fully functional for MVP. Drag upgrade deferred.
- **TextareaControl for bio/rider:** RichText is broken in PluginDocumentSettingPanel since WP 6.5 (Gutenberg issue #60524). TextareaControl stores plain text; formatting will be applied at render time in Phase 4 display blocks. Code comments document this deviation with the issue reference.
- **Headshot previews deferred:** Attachment ID is displayed as text for MVP. Full image preview via MediaDetails or apiFetch is a post-MVP enhancement.

## Deviations from Plan

None — plan executed exactly as written. The plan itself pre-documented the RichText deviation (arrow buttons, TextareaControl) with rationale; these were intentional design choices in the plan, not runtime discoveries.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Speaker Profile meta block complete — all four panels operational with correct REST meta keys
- Phase 3 is now complete: Talks meta (Plan 01), Conference meta (Plan 03), Speaker Profile meta (Plan 04) all built
- Phase 4 display blocks can read _speekr_headshots[0], _speekr_bio_short, _speekr_bio_long, _speekr_social_links, _speekr_rider from REST API
- Geocoding strategy for Conference lat/lng still needs resolution before conference-map block in Phase 4

---
*Phase: 03-editor-blocks*
*Completed: 2026-03-02*
