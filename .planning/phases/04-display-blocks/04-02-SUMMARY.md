---
phase: 04-display-blocks
plan: "02"
subsystem: ui
tags: [gutenberg, dynamic-block, speaker-profile, php-render, scss, social-links, press-kit, inspector-controls]

# Dependency graph
requires:
  - phase: 04-display-blocks
    plan: "01"
    provides: press-kit REST endpoint at speekr/v1/press-kit/{id} linked from allowDownload button
  - phase: 03-editor-blocks
    plan: "06"
    provides: speekr_speaker CPT meta fields (_speekr_headshots, _speekr_bio_short, _speekr_social_links, _speekr_rider) and Speaker Profile Meta block
provides:
  - speekr/speaker-profile dynamic block registered via block.json auto-discovery glob
  - Server-side render.php: headshots, short bio, apply_filters(the_content) body, social icon row (9-platform inline SVG map + generic fallback), rider sections (av/travel/dietary/accessibility), optional press-kit download button
  - Two layout modes: side-by-side (CSS grid, 200px headshot column) and stacked (flexbox, centered headshots)
  - InspectorControls: Layout SelectControl + Press Kit ToggleControl in editor sidebar
  - style-index.css compiled from style.scss with container queries, social link circles, rider section, branded press-kit button
  - assets/img/placeholder-speaker.svg silhouette placeholder
affects: [04-03, 04-04, 04-05, 04-06]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Dynamic block with null save(), server-side render.php, style as CSS side-effect of index.js import
    - Inline SVG icon map keyed by platform slug with generic link icon fallback for unknown platforms
    - ob_start()/ob_get_clean() buffer pattern for complex PHP render output
    - container-type: inline-size + @container queries for responsive layout without media queries
    - "@use 'sass:color' + color.adjust() instead of deprecated darken() for Dart Sass 3.x compatibility"

key-files:
  created:
    - src/blocks/speaker-profile/block.json
    - src/blocks/speaker-profile/index.js
    - src/blocks/speaker-profile/edit.js
    - src/blocks/speaker-profile/render.php
    - src/blocks/speaker-profile/style.scss
    - assets/img/placeholder-speaker.svg
    - build/blocks/speaker-profile/block.json
    - build/blocks/speaker-profile/index.js
    - build/blocks/speaker-profile/render.php
    - build/blocks/speaker-profile/style-index.css
  modified: []

key-decisions:
  - "Sass darken() deprecated in Dart Sass 3.x — use @use 'sass:color' + color.adjust($color, $lightness: -10%) in all new SCSS"
  - "Social link icon-only display is the default (screen-reader-text span provides accessible label); display style toggle deferred to Phase 5"
  - "style key in block.json is 'file:./style-index.css' (compiled output name) not 'file:./style.scss' — @wordpress/scripts outputs style-index.css from SCSS side-effects"

patterns-established:
  - "Dynamic block pattern: null save(), render key in block.json pointing to render.php, style as side-effect import in index.js"
  - "PHP render buffer: ob_start() at variable setup point, return ob_get_clean() at end — never echo directly"
  - "Inline SVG map: $platform_icons array keyed by lowercase sanitize_key($platform), $generic_link_icon fallback — avoids file I/O per request"
  - "phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped with explanatory comment for static SVG maps and apply_filters('the_content', ...) outputs"

# Metrics
duration: 3min
completed: 2026-03-02
---

# Phase 4 Plan 02: Speaker Profile Block Summary

**Dynamic speekr/speaker-profile block with PHP render, CSS grid/flex layouts, 9-platform SVG icon row, rider sections, and optional press-kit download link**

## Performance

- **Duration:** ~3 min
- **Started:** 2026-03-02T22:21:37Z
- **Completed:** 2026-03-02T22:24:49Z
- **Tasks:** 1
- **Files modified:** 6 created (source) + 6 created (build output)

## Accomplishments
- Speaker Profile dynamic block registered as speekr/speaker-profile with auto-discovery via existing speekr_register_blocks() glob
- Server-side render.php reads all speaker meta (_speekr_headshots, _speekr_bio_short, _speekr_social_links, _speekr_rider, post_content) and produces semantic HTML; returns empty string with no warnings if no published speekr_speaker post exists
- Inline SVG icon map for 9 platforms (linkedin, twitter, x, github, instagram, youtube, mastodon, bluesky, website) with generic link icon fallback; each link gets a screen-reader-text span for accessibility
- Two layout modes: side-by-side (CSS grid, 200px headshot column) and stacked (flexbox); container queries collapse side-by-side to single column at 600px container width
- InspectorControls: Layout SelectControl (side-by-side/stacked) + Press Kit ToggleControl in editor sidebar
- "Download press kit" button links to rest_url('speekr/v1/press-kit/{id}') — only rendered when allowDownload attribute is true

## Task Commits

Each task was committed atomically:

1. **Task 1: Speaker Profile block source files** - `030b473` (feat)

**Plan metadata:** (docs commit follows)

## Files Created/Modified
- `src/blocks/speaker-profile/block.json` - Block registration: speekr/speaker-profile, layout + allowDownload attributes, render and style references
- `src/blocks/speaker-profile/index.js` - Block registration entry, null save(), SCSS side-effect import
- `src/blocks/speaker-profile/edit.js` - Editor placeholder + InspectorControls (Layout SelectControl, Press Kit ToggleControl)
- `src/blocks/speaker-profile/render.php` - Server-side render: headshots, bio short, apply_filters(the_content), social icon row, rider sections, press-kit button
- `src/blocks/speaker-profile/style.scss` - Frontend CSS: grid/flex layouts, container queries, social link circles, rider section, branded press-kit button
- `assets/img/placeholder-speaker.svg` - Grey silhouette placeholder SVG

## Decisions Made
- Sass `darken()` is deprecated in Dart Sass 3.x — replaced with `@use 'sass:color'` and `color.adjust($color, $lightness: -10%)` to eliminate build warnings and ensure forward compatibility; apply this pattern to all future SCSS
- Social link display is icon-only by default (the screen-reader-text span handles accessibility); a user-facing display style toggle is deferred to Phase 5 per plan spec
- `style` key in block.json references the compiled output name (`file:./style-index.css`), not the source SCSS — this matches the @wordpress/scripts CSS side-effect output naming convention

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug/Deprecation] Replaced deprecated Sass darken() with color.adjust()**
- **Found during:** Task 1 (build verification)
- **Issue:** Plan-provided style.scss used `darken(#9768a7, 10%)` which is deprecated in Dart Sass and will be removed in Dart Sass 3.0.0; build emitted 2 deprecation warnings
- **Fix:** Added `@use 'sass:color'` at top of style.scss; replaced `darken(#9768a7, 10%)` with `color.adjust(#9768a7, $lightness: -10%)`
- **Files modified:** `src/blocks/speaker-profile/style.scss`
- **Verification:** `npm run build` compiled successfully with no warnings
- **Committed in:** `030b473` (Task 1 commit)

---

**Total deviations:** 1 auto-fixed (Rule 1 - deprecation/future breakage)
**Impact on plan:** Minor fix to plan-provided code. Eliminates build noise and prevents future compile failure when Dart Sass 3.0 drops the function. No behavior change.

## Issues Encountered
None — PHP lint passed immediately, build succeeded on second attempt (after SCSS fix).

## User Setup Required
None - no external service configuration required. Block is auto-discovered by existing speekr_register_blocks() glob pattern.

## Next Phase Readiness
- Speaker Profile block is live and auto-discovered — inserting it on any page renders the speaker's full profile from post meta
- press-kit.php REST endpoint (from Plan 01) is correctly linked via rest_url() in render.php when allowDownload=true
- Layout attributes (side-by-side/stacked) work end-to-end via InspectorControls and render.php CSS classes
- Plan 03 (Conference List block) can proceed independently

## Self-Check: PASSED
