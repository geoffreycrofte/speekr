---
phase: 03-editor-blocks
plan: "06"
subsystem: ui
tags: [gutenberg, block-editor, react, wordpress, useSelect, getMedia, decodeEntities, drag-and-drop]

# Dependency graph
requires:
  - phase: 03-editor-blocks plan 04
    provides: speaker-profile-meta/edit.js with arrow-button reorder and bio panels
  - phase: 03-editor-blocks plan 02
    provides: talk-meta/edit.js with Media Links (YouTube, Vimeo, Slides)
  - phase: 03-editor-blocks plan 03
    provides: conference-meta/edit.js with Talk Reference and Speakers search
provides:
  - HeadshotItem sub-component with useSelect + getMedia for real img thumbnails
  - HTML5 drag-to-reorder for headshots (replaces arrow buttons)
  - Long Bio panel removed from Speaker Profile (post_content is long-form area)
  - _speekr_bio_long deregistered from inc/cpt/speaker-profile.php
  - decodeEntities applied to all title.rendered in conference-meta search results
  - Talk Media Links panel with all 7 types: YouTube, Vimeo, Dailymotion, Slides, SpeakerDeck, Slideshare
  - Repeatable Other links pattern (label + URL) in Talk Media Links panel
  - 4 new meta keys registered in blocks.php with REST API access
affects: [03-editor-blocks, 04-display-blocks, phase 4 speaker profile display]

# Tech tracking
tech-stack:
  added:
    - "@wordpress/html-entities decodeEntities (first-party, no install needed)"
  patterns:
    - "HeadshotItem sub-component: useSelect hooks in sub-component, not parent — avoids calling hooks conditionally in array .map()"
    - "HTML5 DnD: draggable + onDragStart + onDragOver (preventDefault) + onDrop via dragIndex state in parent"
    - "decodeEntities wrapper pattern: wrap every title.rendered at the point of storage/display, not at render"
    - "Repeatable link pattern: local form state (label + url) + array meta field with add/remove handlers"

key-files:
  created: []
  modified:
    - src/blocks/speaker-profile-meta/edit.js
    - src/blocks/conference-meta/edit.js
    - src/blocks/talk-meta/edit.js
    - inc/blocks/blocks.php
    - inc/cpt/speaker-profile.php

key-decisions:
  - "HeadshotItem extracted as sub-component so useSelect can be called per-item (React hooks rules prohibit calling hooks inside .map() in parent)"
  - "HTML5 DnD chosen over arrow buttons: plan explicitly called for the upgrade; dragIndex state lives in parent, not HeadshotItem"
  - "post_content (block editor body) is the canonical long bio — _speekr_bio_long deregistered and panel removed; bio field is now UI-only Short Bio"
  - "_speekr_media_other registered as array type with show_in_rest schema; no sanitize_callback needed as items are validated by schema"

patterns-established:
  - "Sub-component pattern for items that need per-item useSelect calls (avoids hooks-in-map violation)"
  - "decodeEntities from @wordpress/html-entities applied at every title.rendered reference"

# Metrics
duration: 3min
completed: 2026-03-02
---

# Phase 3 Plan 06: Speaker Profile headshots + entity decoding + talk media parity Summary

**HeadshotItem with getMedia img thumbnails, HTML5 DnD reorder, decodeEntities on all search results, and 7-type media links panel with repeatable Other links**

## Performance

- **Duration:** ~3 min
- **Started:** 2026-03-02T00:08:23Z
- **Completed:** 2026-03-02T00:11:32Z
- **Tasks:** 3
- **Files modified:** 5

## Accomplishments

- HeadshotItem sub-component uses `useSelect( select => select('core').getMedia(id) )` to render actual `<img>` thumbnails (80x80, cover) instead of "Attachment #N" text
- Headshots reorder replaced from arrow buttons to HTML5 drag-to-reorder (draggable, onDragStart, onDrop); dragIndex state managed in parent
- Long Bio TextareaControl removed from Bio panel; _speekr_bio_long deregistered from PHP; Short Bio help text updated to direct editors to post content area
- `decodeEntities` from `@wordpress/html-entities` applied to all 4 `title.rendered` references in conference-meta: selectedTalkTitle, speakerNames cache, talk results buttons, speaker results buttons
- Talk Media Links panel expanded from 3 to 7 named types: YouTube, Vimeo, Slides, Dailymotion, SpeakerDeck, Slideshare, plus repeatable Other links (label+URL add/remove)
- Four new meta keys registered in blocks.php: `_speekr_media_dailymotion`, `_speekr_media_speakerdeck`, `_speekr_media_slideshare`, `_speekr_media_other` (array type with REST schema)

## Task Commits

Each task was committed atomically:

1. **Task 1: Fix speaker-profile-meta/edit.js — image display, DnD, remove long bio** - `0633547` (feat)
2. **Task 2: Fix conference-meta/edit.js — HTML entity decoding** - `affa876` (feat)
3. **Task 3: Add missing media types + register meta + remove _speekr_bio_long** - `d2a8a77` (feat)

## Files Created/Modified

- `src/blocks/speaker-profile-meta/edit.js` - HeadshotItem sub-component with getMedia + HTML5 DnD; Long Bio removed
- `src/blocks/conference-meta/edit.js` - decodeEntities import + applied to all title.rendered references
- `src/blocks/talk-meta/edit.js` - Dailymotion, SpeakerDeck, Slideshare fields + repeatable Other links panel
- `inc/blocks/blocks.php` - 4 new meta keys registered (dailymotion, speakerdeck, slideshare, other)
- `inc/cpt/speaker-profile.php` - _speekr_bio_long registration removed

## Decisions Made

- **HeadshotItem as sub-component:** React hooks rules prohibit calling `useSelect` inside a `.map()` in the parent component. Extracting HeadshotItem as a named sub-component allows each item to call its own `useSelect` hook legally.
- **post_content is canonical long bio:** _speekr_bio_long deregistered entirely — not just hidden. The block editor body (post_content) provides richer editing with full Gutenberg support. Short Bio remains for program intros.
- **_speekr_media_other without sanitize_callback:** Array type with REST schema validates items; individual sanitization handled by WordPress core REST sanitization for the schema-defined string properties.

## Deviations from Plan

None — plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None — no external service configuration required.

## Next Phase Readiness

- Phase 3 (Editor Blocks) is complete: all five plans executed
- Phase 4 (Display Blocks) can begin: all meta fields registered, block editor panels functional
- Display blocks will receive clean string data from Short Bio (no Long Bio to handle), decoded entity text from conference searches, and full 7-type media link data from Talk posts
- Geocoding strategy for Conference lat/lng must still be decided before Phase 4 conference-map block implementation

---
*Phase: 03-editor-blocks*
*Completed: 2026-03-02*
