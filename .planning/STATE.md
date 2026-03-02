# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-02-28)

**Core value:** A speaker shares one URL; a conference organizer finds everything they need — profile, assets, talks, past conferences — without the speaker maintaining a separate doc or PDF.
**Current focus:** Phase 2 — Data Layer

## Current Position

Phase: 2 of 5 (Data Layer)
Plan: 5 of N in current phase (Plan 05 complete — _speekr_conf_speakers array meta registered on speekr_conference CPT; REST API and useEntityProp() ready)
Status: Phase 2 in progress
Last activity: 2026-03-02 — Plan 05 complete: _speekr_conf_speakers meta field added to inc/cpt/conferences.php; Conference CPT data layer complete

Progress: [█████░░░░░] 50%

## Performance Metrics

**Velocity:**
- Total plans completed: 8
- Average duration: ~2.4 min
- Total execution time: ~19 min

**By Phase:**

| Phase | Plans | Total | Avg/Plan |
|-------|-------|-------|----------|
| 01-build-foundation | 4 | ~12 min | ~3 min |
| 02-data-layer | 5 | ~9 min | ~1.8 min |

**Recent Trend:**
- Last 5 plans: 02-01 (~2 min), 02-02 (~3 min), 02-03 (~1 min), 02-04 (~2 min), 02-05 (~1 min)
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

### Pending Todos

None.

### Blockers/Concerns

- [Phase 4]: Geocoding strategy for Conference lat/lng must be decided before `blocks/conference-map/` implementation begins. Options: (a) manual lat/lng entry fields, (b) geocoding API call at post save, (c) bundled country/city coordinate lookup table. No external API key dependency preferred.
- [Phase 5]: Verify that Talks and Conferences CPT slugs use hyphens (not underscores) before FSE template registration — WP template name validation rejects underscores. Check `speekr_get_cpt_slug()` in `inc/functions/helpers.php`.

## Session Continuity

Last session: 2026-03-02
Stopped at: Completed 02-data-layer Plan 05 — _speekr_conf_speakers array meta registered on Conference CPT; Conference CPT data layer complete. Ready for Plan 06.
Resume file: None
