# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-02-28)

**Core value:** A speaker shares one URL; a conference organizer finds everything they need — profile, assets, talks, past conferences — without the speaker maintaining a separate doc or PDF.
**Current focus:** Phase 4 — Frontend Display Blocks

## Current Position

Phase: 4 of 5 (Frontend Display Blocks) — IN PROGRESS
Plan: 3 of 6 in phase 04 (Plan 03 complete — Talks List dynamic block: render.php with media priority chain, topic pill filter data attrs, conference meta lookup; vanilla JS view.js; responsive grid/list CSS)
Status: Phase 4 in progress — Plans 01–03 complete, Plan 04 next
Last activity: 2026-03-02 — Plan 03 complete: speekr/talks-list block with render.php, pill filter view.js, style.scss with container queries

Progress: [█████████░] 92%

## Performance Metrics

**Velocity:**
- Total plans completed: 15
- Average duration: ~2.2 min
- Total execution time: ~33 min

**By Phase:**

| Phase | Plans | Total | Avg/Plan |
|-------|-------|-------|----------|
| 01-build-foundation | 4 | ~12 min | ~3 min |
| 02-data-layer | 5 | ~9 min | ~1.8 min |
| 03-editor-blocks | 8 | ~26 min | ~3.3 min |
| 04-display-blocks | 3 | ~8 min | ~2.7 min |

**Recent Trend:**
- Last 5 plans: 03-02 (~2 min), 03-08 (~3 min), 04-01 (~2 min), 04-02 (~3 min), 04-03 (~3 min)
- Trend: consistent

*Updated after each plan completion*

## Accumulated Context

### Decisions

Decisions are logged in PROJECT.md Key Decisions table.
Recent decisions affecting current work:

- [Roadmap]: Leaflet.js for world map (GPL-compatible, no API key required)
- [Roadmap]: `@wordpress/scripts` replaces entire node-sass/gulp/csso-cli pipeline
- [Roadmap]: No Composer, no PSR-4 namespacing — existing `inc/` structure and `Speekr` class preserved; new PHP files added to `inc/` and loaded via `require_once` in `inc/classes/Speekr.php`
- [Roadmap]: Block registration handled by `speekr_register_blocks()` plain function in `inc/blocks/blocks.php`, not a namespaced class
- [Roadmap]: Geocoding strategy for Conference lat/lng is an open design decision — must be resolved before Phase 4 (manual entry vs. geocoding API vs. coordinate lookup table)
- [Phase 01-build-foundation]: defaultConfig.entry() must be called as a function in @wordpress/scripts v27+; webpack.config.js documents this with inline comment
- [Phase 01-build-foundation]: build/ is NOT gitignored (WordPress plugin convention); only build/**/*.map is excluded
- [Phase 01-build-foundation Plan 02]: @font-face url() paths in src/frontend/style.scss use ../../assets/fonts/front/ (relative to build/frontend/ output, not source location)
- [Phase 01-build-foundation Plan 03]: @wordpress/scripts CSS output uses style- prefix — entry admin/index produces build/admin/style-index.css (not index.css); entry frontend/style produces build/frontend/style-style.css (not style.css)
- [Phase 01-build-foundation Plan 03]: RTL companion files auto-emitted: style-index-rtl.css, style-style-rtl.css
- [Phase 01-build-foundation Plan 04]: Legacy cleanup gated on human-verify — files deleted only after browser confirmation of no 404s and font rendering
- [Phase 01-build-foundation Plan 04]: assets/js/speekr-admin.js retained alongside src/admin/index.js — both are same source; deferred cleanup
- [Phase 02-data-layer Plan 01]: CPT files live in inc/cpt/{slug}.php, loaded via require_once in Speekr::includes() (not includes_admin()) for REST API availability
- [Phase 02-data-layer Plan 01]: Structured meta (arrays/objects) use expanded show_in_rest schema with items.properties and additionalProperties: false for strictness; remove additionalProperties: false from headshots/social_links items if REST saves are rejected in Plan 03 verification
- [Phase 02-data-layer Plan 01]: PHP binary not in PATH in this shell; use /Applications/MAMP/bin/php/php8.2.0/bin/php for linting
- [Phase 02-data-layer]: Conferences CPT key is 'speekr_conference' (underscore) — Phase 5 must verify FSE template name compatibility before template registration
- [Phase 02-data-layer]: Talks CPT 'editor' and 'custom-fields' supports were previously commented out; both activated in Plan 02 to enable block editor and useEntityProp() in Phase 3
- [Phase 02-data-layer Plan 03]: speekr_topic taxonomy: hierarchical: false (tag-like) with show_in_rest: true — enables block editor sidebar panel and /wp-json/wp/v2/speekr_topic endpoint without additional code
- [Phase 02-data-layer Plan 03]: save_post guard order established: empty($_POST) -> wp_is_post_autosave -> wp_is_post_revision -> nonce check -> capability check; use this order for all future save_post callbacks
- [Phase 02-data-layer]: Taxonomy registration moved from admin-only bootstrap (includes_admin) to common path (includes) — same pattern as CPTs in Plan 01; ensures REST API availability without additional hooks
- [Phase 02-data-layer Plan 05]: single=true required for array meta with show_in_rest schema — WordPress needs single=true to expose arrays correctly via REST API for useEntityProp(); default=array() ensures field is never null in REST response
- [Phase 03-editor-blocks Plan 01]: blocks.php loaded via Speekr::includes() (not includes_admin()) so block and meta registration runs on all requests including REST API — same pattern as CPTs and taxonomies
- [Phase 03-editor-blocks Plan 01]: Block-editor media links use new _speekr_media_{youtube,vimeo,slides} keys; legacy speekr-media-links serialised array NOT registered for REST — two separate save paths to avoid schema complexity and REST 400 errors
- [Phase 03-editor-blocks Plan 01]: __back_compat_meta_box: true hides speekr-summary, speekr-media-links, speekr-conference meta boxes in Gutenberg while keeping them functional in classic editor; speekr-content (wp_editor) intentionally left visible in both
- [Phase 03-editor-blocks Plan 03]: All Conference meta state hooks consolidated in single ConferenceMetaPanels component — avoids prop-drilling meta/setMeta into per-panel sub-components
- [Phase 03-editor-blocks Plan 03]: Speaker name cache uses functional setSpeakerNames update ((prev) => ({...prev, ...newNames})) to avoid stale closure issues with concurrent fetches
- [Phase 03-editor-blocks Plan 03]: selectedTalkId and selectedSpeakers derived from meta at render time (not mirrored in useState) — always reflects persisted state without sync issues
- [Phase 03-editor-blocks Plan 04]: Arrow-button reorder (↑/↓) used instead of drag-and-drop for Headshots panel — simpler, accessible, sufficient for MVP; drag upgrade deferred
- [Phase 03-editor-blocks Plan 04]: TextareaControl for bio/rider fields — RichText broken in PluginDocumentSettingPanel since WP 6.5 (Gutenberg issue #60524); plain text stored, formatting applied at render in Phase 4
- [Phase 03-editor-blocks Plan 02]: TextareaControl for Talk summary field — same RichText limitation as Speaker Profile; plain text stored via sanitize_textarea_field, formatting can be applied at render in Phase 4
- [Phase 03-editor-blocks Plan 02]: Appears In panel fetches up to 100 conferences on mount and filters client-side by _speekr_conf_talk_ref === postId — acceptable ceiling for plugin's use case
- [Phase 03-editor-blocks Plan 06]: HeadshotItem extracted as sub-component so useSelect(getMedia) can be called per-item without violating React hooks rules (hooks cannot be called inside .map())
- [Phase 03-editor-blocks Plan 06]: post_content (block editor body) is canonical long-form bio — _speekr_bio_long deregistered from PHP and panel removed from UI; Short Bio remains for program intros
- [Phase 03-editor-blocks Plan 06]: decodeEntities from @wordpress/html-entities applied at every title.rendered reference in conference-meta (storage + display) — first-party WP package, no install needed
- [Phase 03-editor-blocks Plan 07]: Talks CPT registered in inc/common/custom-posts.php (not a separate talks.php) — show_in_menu added there; admin-menu.php loaded first in includes_admin() so menu slug 'speekr' is available before CPT submenu resolution
- [Phase 03-editor-blocks Plan 07]: All plugin CPTs use show_in_menu => 'speekr' pattern; new admin pages go in inc/admin/admin-menu.php loaded via Speekr::includes_admin()
- [Phase 03-editor-blocks Plan 08]: build/editor/ output filename is speekr-panels.css (not style-speekr-panels.css) — style- prefix only applies to CSS side-effects of JS entry files, not direct SCSS entries
- [Phase 03-editor-blocks Plan 08]: @wordpress/icons installed as devDependency for local build — runtime WP ships it globally but webpack needs it locally to resolve imports during compilation
- [Phase 03-editor-blocks Plan 08]: formatQuote not exported by @wordpress/icons — use quote instead; always verify icon export names from the package's exports list before importing
- [Phase 03-editor-blocks Plan 08]: Editor-only styles use webpack entry + enqueue_block_editor_assets pattern — do not add to block.json editorStyle (that would limit scope to individual blocks only)
- [Phase 03-editor-blocks Plan 08]: Panel visual state pattern: speekr-panel-{name} CSS class + is-filled conditional suffix; CSS sets icon color (muted grey → WP blue #007cba)
- [Phase 04-display-blocks Plan 02]: Sass darken() deprecated in Dart Sass 3.x — use @use 'sass:color' + color.adjust($color, $lightness: -10%) in all new SCSS files
- [Phase 04-display-blocks Plan 02]: style key in block.json references compiled output name (file:./style-index.css) not source SCSS — @wordpress/scripts names CSS side-effects style-index.css
- [Phase 04-display-blocks Plan 02]: Social link display is icon-only by default (screen-reader-text span for accessibility); display style toggle deferred to Phase 5

### Pending Todos

None.

### Blockers/Concerns

- [Phase 04-display-blocks Plan 01]: Geocoding strategy resolved — Nominatim (OpenStreetMap) via wp_remote_get with required User-Agent header; no API key, GPL-compatible; skip-if-already-geocoded guard prevents redundant API calls on re-saves
- [Phase 04-display-blocks Plan 01]: press-kit.php loaded via Speekr::includes() (not includes_front()) — REST API must be available on both admin and frontend requests; consistent with CPT/blocks.php pattern
- [Phase 04-display-blocks Plan 01]: Manual coordinates panel uses conditional render in editor sidebar (!lat && !lng) rather than separate settings page or classic meta box — keeps all conference meta unified
- [Phase 5]: Verify that Talks and Conferences CPT slugs use hyphens (not underscores) before FSE template registration — WP template name validation rejects underscores. Check `speekr_get_cpt_slug()` in `inc/functions/helpers.php`.

## Session Continuity

Last session: 2026-03-02
Stopped at: Completed 04-display-blocks Plan 03 — speekr/talks-list dynamic block: render.php with media priority chain (YT/Vimeo/slide/featured/placeholder), topic pill filter data-topics attrs, conference meta lookup, speekr-as-article guard; vanilla JS view.js pill filter; style.scss with container queries and grid/list layouts.
Resume file: None
