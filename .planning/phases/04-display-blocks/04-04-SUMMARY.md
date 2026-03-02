---
phase: 04-display-blocks
plan: "04"
subsystem: ui
tags: [php, gutenberg, blocks, oembed, dynamic-block, wordpress]

# Dependency graph
requires:
  - phase: 03-editor-blocks
    provides: Per-key talk meta fields (_speekr_media_youtube, _speekr_media_vimeo, _speekr_media_dailymotion, _speekr_media_speakerdeck, _speekr_media_slides, _speekr_media_slideshare, _speekr_media_other, speekr-summary, speekr-as-article) registered and saved via block editor panels
  - phase: 04-display-blocks
    plan: "01"
    provides: Block category registration, speekr_register_blocks() auto-discovery loop in blocks.php
provides:
  - speekr/single-talk dynamic block rendering full talk detail view on single CPT pages
  - Video priority chain (YouTube > Vimeo > Dailymotion) via wp_oembed_get() with 16:9 aspect-ratio wrapper
  - Featured image and SVG placeholder fallback when no video available
  - Short summary, post_content, conference reference section with city/date/country
  - Resources section: SpeakerDeck, Slides, Slideshare, plus repeatable _speekr_media_other links
  - "Read full article" CTA rendered only when speekr-as-article === 'on'
  - Graceful fallback message when block is inserted on a non-talk page
affects: [05-speaker-page, phase-5-fse-templates]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - wp_oembed_get() for video embeds (handles oEmbed discovery, transient caching, error handling)
    - ob_start() / ob_get_clean() pattern for render.php output buffering
    - get_the_ID() for current-post context in dynamic blocks (no post-selector attribute needed)
    - post_type guard before proceeding to prevent PHP warnings on non-talk pages
    - 'on' === string comparison for checkbox meta (not truthiness)

key-files:
  created:
    - src/blocks/single-talk/block.json
    - src/blocks/single-talk/index.js
    - src/blocks/single-talk/edit.js
    - src/blocks/single-talk/render.php
    - src/blocks/single-talk/style.scss
    - build/blocks/single-talk/ (compiled output: block.json, index.js, index.asset.php, style-index.css, style-index-rtl.css, render.php)
  modified: []

key-decisions:
  - "No viewScript needed for single-talk block — all rendering is PHP server-side, no interactive frontend JS required"
  - "wp_oembed_get() preferred over manual iframe construction — handles oEmbed discovery, WordPress transient caching, and error handling natively"
  - "get_the_ID() context approach (no post attribute) — block is designed exclusively for single 'talks' CPT pages; post selector would be over-engineering for this use case"

patterns-established:
  - "Single-post dynamic block pattern: post_type guard first → graceful fallback message → meta reads → compute → ob_start → template → ob_get_clean"
  - "Video priority chain: array_filter([youtube, vimeo, dailymotion]) → foreach → first wp_oembed_get() truthy result wins"

# Metrics
duration: 2min
completed: 2026-03-02
---

# Phase 4 Plan 04: Single Talk Block Summary

**Server-side dynamic block rendering full talk detail: wp_oembed_get() video priority chain (YouTube > Vimeo > Dailymotion), 16:9 wrapper, conference section with city/date, pill-style Resources section for slides and custom links**

## Performance

- **Duration:** ~2 min
- **Started:** 2026-03-02T00:12:02Z
- **Completed:** 2026-03-02T00:13:36Z
- **Tasks:** 1 of 1
- **Files modified:** 11 (5 source, 6 build output)

## Accomplishments

- Created all five source files for the `speekr/single-talk` block in a single task
- render.php implements full talk detail: video oEmbed priority chain, image/placeholder fallback, short summary, post_content, conference reference with geo meta, Resources pill list, blog CTA
- PHP lint passed, npm build succeeded, block.json appears in build output with correct block name

## Task Commits

1. **Task 1: Single Talk block — all source files** - `12fc6a3` (feat)

## Files Created/Modified

- `src/blocks/single-talk/block.json` - Block registration: render ref, style-index.css, no viewScript
- `src/blocks/single-talk/index.js` - Registers block type with metadata; imports style.scss
- `src/blocks/single-talk/edit.js` - Editor placeholder (italic notice, no real controls needed)
- `src/blocks/single-talk/render.php` - Full server-side render: oEmbed video chain, fallback image, conference section, Resources section, blog CTA guard
- `src/blocks/single-talk/style.scss` - 16:9 video wrapper (aspect-ratio), conference accent block (#9768a7), pill resource links, blog CTA button

## Decisions Made

- No viewScript: all rendering is server-side PHP; no interactive frontend JS required for this block
- Used `wp_oembed_get()` rather than manual iframe construction — it handles oEmbed discovery, caching via transients, and error handling natively
- `get_the_ID()` context (no post attribute on the block) — block is exclusively for single talk CPT pages; attribute-based post selection would be over-engineering

## Deviations from Plan

None — plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None — no external service configuration required.

## Next Phase Readiness

- `speekr/single-talk` block is registered and available in the block inserter under the "speekr" category
- Block renders correctly on single `talks` CPT posts; gracefully fails on other page types
- Phase 4 Plans 05–06 (speaker-profile and conference-detail display blocks) can now follow the same render.php pattern established here

---
*Phase: 04-display-blocks*
*Completed: 2026-03-02*
