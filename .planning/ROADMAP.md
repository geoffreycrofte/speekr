# Roadmap: Speekr

## Overview

Speekr's modernization journey runs from a broken build toolchain to a fully block-native WordPress plugin. The dependency chain is strict and non-negotiable: the build pipeline must work before any block assets can be compiled; the data layer must expose every field to the REST API before any block can read it; editor blocks must exist before display blocks can pull from them; templates can only be assembled once all blocks exist. Five phases deliver that chain. Every v1 requirement maps to exactly one phase.

This is a brownfield plugin. The existing `inc/` structure and `Speekr` class in `inc/classes/Speekr.php` are preserved and extended throughout. No Composer, no PSR-4 namespacing, no `src/` directory — new PHP files follow WordPress conventions and are loaded via `require_once` in the existing bootstrap.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [ ] **Phase 1: Build Foundation** - Migrate build toolchain from node-sass/gulp to `@wordpress/scripts`; no PHP changes in this phase
- [ ] **Phase 2: Data Layer** - Register all CPTs (Speaker Profile, Conferences, Talks enhancements) with `show_in_rest: true`; register every post meta via `register_post_meta()`; add Topics taxonomy; audit and gate all `save_post` callbacks
- [ ] **Phase 3: Editor Blocks** - Build block editor sidebar panels for all three CPTs, replacing legacy meta boxes; register blocks via a plain PHP function using `register_block_type()`
- [ ] **Phase 4: Display Blocks** - Build all public-facing dynamic blocks: Speaker Profile, Talks List, Single Talk, Conference Archive, and Conference Map (Leaflet.js)
- [ ] **Phase 5: Templates and Compatibility** - Assemble FSE block templates from existing blocks; add classic PHP template files; add shortcodes wrapping block render functions; add developer action/filter hooks

## Phase Details

### Phase 1: Build Foundation
**Goal**: A developer can run `npm run build` on Node 18+ without errors; compiled block assets and legacy admin/frontend scripts land in `build/`; no changes to PHP files in this phase
**Depends on**: Nothing (first phase)
**Requirements**: ARCH-01
**Success Criteria** (what must be TRUE):
  1. `npm run build` completes without errors on Node 18+ and produces compiled block assets and legacy admin/frontend scripts in `build/`
  2. `npm run start` enters watch mode and recompiles SCSS and JS on file change without errors
  3. Plugin loads on WordPress without PHP fatal errors after the build toolchain migration (existing `inc/` bootstrap is untouched)
  4. Compiled assets in `build/` are enqueued correctly by the existing PHP enqueue hooks — no 404s in browser network tab
**Plans**: 4 plans

Plans:
- [ ] 01-01-PLAN.md — Replace package.json scripts/deps + create webpack.config.js + update .gitignore
- [ ] 01-02-PLAN.md — Create src/ entry points (admin JS/SCSS + frontend SCSS with font path fix)
- [ ] 01-03-PLAN.md — Run npm install + build + update PHP enqueue paths in 3 files
- [ ] 01-04-PLAN.md — Watch mode test + browser verification + remove legacy compiled assets

### Phase 2: Data Layer
**Goal**: All CPTs are visible to the block editor and REST API; every post meta field is registered and accessible via `useEntityProp()` in JavaScript; the Topics taxonomy is in place; no legacy `save_post` callback can silently wipe data
**Depends on**: Phase 1
**Requirements**: ARCH-02, PROF-01, PROF-02, PROF-03, PROF-04, PROF-05, TALK-01, CONF-01
**Success Criteria** (what must be TRUE):
  1. The block editor loads (not classic editor fallback) for the Talks, Speaker Profile, and Conferences CPTs
  2. All post meta fields for all CPTs are readable and writable via the WordPress REST API (verifiable with `/wp-json/wp/v2/{post-type}/{id}` request)
  3. Speaker can create a Speaker Profile post and populate headshots, short bio, long bio, social links, and rider fields
  4. Speaker can create a Conference entry with event name, date, city, country, event URL, and a talk reference
  5. Topics taxonomy appears in the Talks editor and accepts terms; topics are queryable via REST API
**Plans**: 3 plans

Plans:
- [ ] 02-01-PLAN.md — Register Speaker Profile CPT + all five Speaker Profile post meta fields (headshots, bio short, bio long, social links, rider); create inc/cpt/speaker-profile.php; wire into Speekr::includes()
- [ ] 02-02-PLAN.md — Register Conferences CPT + all five Conference post meta fields; update Talks CPT with show_in_rest: true and editor/custom-fields in supports; create inc/cpt/conferences.php; wire into Speekr::includes()
- [ ] 02-03-PLAN.md — Register Topics taxonomy on Talks CPT with show_in_rest: true; gate speekr_save_mb against REST double-fire with empty($_POST), wp_is_post_autosave(), and wp_is_post_revision() guards

### Phase 3: Editor Blocks
**Goal**: Speakers can enter and edit all talk metadata, conference data, and speaker profile data through native Gutenberg block editor sidebar panels — no classic meta boxes required
**Depends on**: Phase 2
**Requirements**: TALK-02, CONF-02
**Success Criteria** (what must be TRUE):
  1. Editing a Talk post shows block editor sidebar panels for: summary, media links (YouTube/Vimeo/Slides), conference reference, and Topics assignment — all fields save without page reload
  2. Editing a Conference post shows block editor sidebar panels for: event name, date, city, country, event URL, and talk reference — all fields save correctly
  3. Editing a Speaker Profile post shows block editor sidebar panels for: headshots, short bio, long bio, social links, and rider — rich text fields support formatting
  4. A plain PHP function in `inc/blocks/blocks.php` registers all blocks from `build/blocks/*/block.json` via `register_block_type()`; no block throws a console error on load
**Plans**: TBD

Plans:
- [ ] 03-01: Create `inc/blocks/blocks.php` with a `speekr_register_blocks()` function that loops `build/blocks/*/block.json` and calls `register_block_type()`; hook it to `init`; load the file via `require_once` in `inc/classes/Speekr.php`; scaffold `blocks/talk-meta/` with `block.json` (apiVersion 3), `edit.js` using `useEntityProp` and `PluginDocumentSettingPanel`, and `index.js`
- [ ] 03-02: Scaffold `blocks/conference-meta/` editor block following the same pattern as `talk-meta`; scaffold `blocks/speaker-profile-meta/` editor block for Speaker Profile CPT; confirm all three editor blocks save correctly without interfering with legacy meta boxes

### Phase 4: Display Blocks
**Goal**: Visitors to a speaker's site can view the speaker profile, browse talks with topic filtering, view individual talk detail with media embeds, browse conference history, and see all conference locations on an interactive world map
**Depends on**: Phase 3
**Requirements**: PROF-06, TALK-03, TALK-04, CONF-03, CONF-04
**Success Criteria** (what must be TRUE):
  1. Inserting the Speaker Profile block on any page renders the speaker's headshot, bio, social links, and rider without additional configuration
  2. Inserting the Talks List block renders all published talks in a grid; filtering by Topic reduces the displayed talks to the selected topic without a page reload
  3. Inserting the Single Talk block on a single talk page renders the media embed (or featured image fallback), description, conference reference, and all relevant links
  4. Inserting the Conference Archive block renders a filterable list of past conference appearances with name, date, city, and talk given
  5. Inserting the Conference Map block renders a Leaflet.js map with one pin per conference; clicking a pin shows a popup with conference name, date, talk given, and event link; Leaflet does not load in the block editor (only on the frontend via `viewScript`)
**Plans**: TBD

Plans:
- [ ] 04-01: Build `blocks/speaker-profile/` as a dynamic block with `render.php` reading Speaker Profile post meta; build `blocks/talks-list/` with `render.php` querying published Talks posts, optional Topics filter, and topic filter UI in `edit.js`
- [ ] 04-02: Build `blocks/single-talk/` as a dynamic block with `render.php` that renders media embed via existing oEmbed/iframe helpers in `inc/` and all talk meta fields
- [ ] 04-03: Build `blocks/conference-map/` following the dynamic block + `viewScript` pattern: `render.php` outputs conference lat/lng coordinates as a JSON data attribute; `view.js` reads the attribute and initializes Leaflet 1.9.4 (never loaded in the editor); build `blocks/conference-archive/` as a dynamic block with filterable list render

### Phase 5: Templates and Compatibility
**Goal**: The plugin works in block themes (FSE templates), classic themes (PHP template files), and shortcode-based page builders; developers can extend all output points via action/filter hooks
**Depends on**: Phase 4
**Requirements**: DEV-01, DEV-02, DEV-03, DEV-04
**Success Criteria** (what must be TRUE):
  1. Activating a block theme shows Speekr FSE templates in the Site Editor for: speaker profile page, talk archive, single talk, and conference archive — all templates render correctly with real data
  2. A classic theme (non-FSE) correctly loads Speekr PHP template files for `archive-talk.php`, `single-talk.php`, `speaker-profile.php`, `archive-conference.php`, and `single-conference.php`; child themes can override these by placing a file of the same name in the child theme directory
  3. `[speekr_profile]`, `[speekr_talks]`, and `[speekr_map]` shortcodes output the same content as their block equivalents when placed in any post or page editor
  4. A developer can hook `speekr_before_profile`, `speekr_after_profile`, `speekr_before_talks_list`, `speekr_after_talks_list`, `speekr_before_map`, `speekr_after_map`, and `speekr_talk_output` to modify output without editing plugin files
**Plans**: TBD

Plans:
- [ ] 05-01: Write FSE `templates/single-talk.html`, `templates/archive-talk.html`, `templates/single-conference.html`, `templates/archive-conference.html` using existing blocks; register templates via a `speekr_register_block_templates()` function in `inc/front/templates.php` hooked to `init`; verify CPT slugs use hyphens (not underscores) before registering templates
- [ ] 05-02: Write classic PHP template files in `templates/classic/`: `archive-talk.php`, `single-talk.php`, `speaker-profile.php`, `archive-conference.php`, `single-conference.php`; implement a `speekr_template_include()` function in `inc/front/templates.php` hooked to `template_include` with child theme override support
- [ ] 05-03: Add shortcode functions (`speekr_shortcode_profile()`, `speekr_shortcode_talks()`, `speekr_shortcode_map()`) in `inc/front/shortcodes.php`, each calling the corresponding block `render.php` directly; add all action/filter hooks at major output points throughout `render.php` files; load both files via `require_once` in `inc/classes/Speekr.php`

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3 → 4 → 5

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Build Foundation | 0/4 | Not started | - |
| 2. Data Layer | 0/3 | Not started | - |
| 3. Editor Blocks | 0/2 | Not started | - |
| 4. Display Blocks | 0/3 | Not started | - |
| 5. Templates and Compatibility | 0/3 | Not started | - |
