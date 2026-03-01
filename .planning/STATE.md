# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-02-28)

**Core value:** A speaker shares one URL; a conference organizer finds everything they need — profile, assets, talks, past conferences — without the speaker maintaining a separate doc or PDF.
**Current focus:** Phase 2 — Data Layer

## Current Position

Phase: 2 of 5 (Data Layer)
Plan: 3 of N in current phase (Plan 03 complete — Topics taxonomy registered; speekr_save_mb gated against REST double-fire)
Status: Phase 2 in progress
Last activity: 2026-03-02 — Plan 03 complete: speekr_topic taxonomy with show_in_rest: true; speekr_save_mb REST API guard added

Progress: [█████░░░░░] 50%

## Performance Metrics

**Velocity:**
- Total plans completed: 6
- Average duration: ~2.5 min
- Total execution time: ~17 min

**By Phase:**

| Phase | Plans | Total | Avg/Plan |
|-------|-------|-------|----------|
| 01-build-foundation | 4 | ~12 min | ~3 min |
| 02-data-layer | 3 | ~6 min | ~2 min |

**Recent Trend:**
- Last 5 plans: 01-03 (~5 min), 01-04 (~5 min), 02-01 (~2 min), 02-02 (~3 min), 02-03 (~1 min)
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

### Pending Todos

None.

### Blockers/Concerns

- [Phase 4]: Geocoding strategy for Conference lat/lng must be decided before `blocks/conference-map/` implementation begins. Options: (a) manual lat/lng entry fields, (b) geocoding API call at post save, (c) bundled country/city coordinate lookup table. No external API key dependency preferred.
- [Phase 5]: Verify that Talks and Conferences CPT slugs use hyphens (not underscores) before FSE template registration — WP template name validation rejects underscores. Check `speekr_get_cpt_slug()` in `inc/functions/helpers.php`.

## Session Continuity

Last session: 2026-03-02
Stopped at: Completed 02-data-layer Plan 03 — speekr_topic taxonomy registered, speekr_save_mb REST guard added. Ready for Plan 04.
Resume file: None
