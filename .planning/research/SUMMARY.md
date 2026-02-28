# Project Research Summary

**Project:** Speekr — WordPress Conference Speaker Toolkit Plugin Modernization
**Domain:** WordPress plugin — Gutenberg blocks, FSE templates, PSR-4 PHP, CPT data layer
**Researched:** 2026-02-28
**Confidence:** HIGH

## Executive Summary

Speekr is a self-hosted WordPress plugin that gives a single speaker a dedicated, organizer-ready digital presence: a canonical URL containing their bio, talks, conference history, and requirements. Unlike SaaS competitors (Sessionize, Notist), Speekr is data-sovereign and deeply integrated into WordPress — meaning organizers land on the speaker's own site rather than a third-party profile. The recommended modernization approach is a phased, additive migration: introduce PSR-4 namespacing and the `@wordpress/scripts` build pipeline first, migrate data registration second, then build new Gutenberg blocks and FSE templates on top of that stable foundation without deleting working legacy code until its replacement is verified.

The stack is fully determined: WordPress 6.8+ floor unlocks the `wp_register_block_types_from_metadata_collection()` manifest API; `@wordpress/scripts@31.5.0` replaces the EOL `node-sass`/`gulp` pipeline with zero-config Webpack 5 + Dart Sass; Composer PSR-4 autoloading replaces manual `require_once` chains; Leaflet 1.9.4 + react-leaflet v4 provides the world map differentiator without React 19 conflicts. Every version choice is pinned with a concrete rationale against a newer alternative that would break under the current dependency graph.

The primary risk is the migration path itself, not the new architecture. Three legacy patterns in Speekr's current codebase will cause silent data loss or invisible failures if carried forward unchanged: CPTs registered without `show_in_rest: true` (block editor silently falls back to classic), `save_post` callbacks that read `$_POST` (fire with empty data in REST context), and post meta stored via raw `update_post_meta()` without `register_post_meta()` (invisible to `useEntityProp()`). All three must be remediated in the first phase, before any block work begins.

## Key Findings

### Recommended Stack

The build pipeline replaces `node-sass`, `gulp`, and `csso-cli` entirely with `@wordpress/scripts@31.5.0`, which wraps Webpack 5 with sass-loader (Dart Sass), PostCSS, MiniCSSExtractPlugin, Babel, ESLint, and Stylelint pre-configured. The `--blocks-manifest` build flag generates `build/blocks-manifest.php` automatically, consumed by `wp_register_block_types_from_metadata_collection()` for zero-boilerplate block registration. PHP moves to Composer-managed PSR-4 autoloading under the `Speekr\` namespace, mapping to a new `src/` directory that coexists with the existing `inc/` during migration. The WordPress version floor is 6.8; PHP floor is 8.1.

**Core technologies:**
- WordPress 6.8+: required for `wp_register_block_types_from_metadata_collection()` and stable `register_block_template()` — eliminates manual block registration loops
- PHP 8.1+: enables enums, readonly props, named arguments — no reason to maintain PHP 7.4 patterns
- @wordpress/scripts 31.5.0: replaces the entire build pipeline; Dart Sass, Webpack 5, React 18 peer dep bundled
- Composer 2.x with PSR-4: enables namespaced, autoloaded PHP without a custom loader
- Leaflet 1.9.4 + react-leaflet 4.x: world map in block editor (react-leaflet) and frontend (view.js); do NOT use Leaflet 2.0 alpha or react-leaflet v5 (requires React 19)

**Do not use:** `node-sass` (EOL, fails on Node 18+), `react-leaflet@5` (requires React 19), `Leaflet 2.0.0-alpha` (pre-release, removes global `L`), jQuery in block editor context, global `new Speekr()` anti-pattern without namespacing.

See `/Users/CRG/Local Sites/wordpress/app/public/wp-content/plugins/speekr/.planning/research/STACK.md` for full version compatibility table.

### Expected Features

The feature landscape is organized around a key distinction: Speekr is not an event-management platform — it is the speaker's own presence. Organizers land on the speaker's site and get everything they need without an email request. That shapes what is in scope and what is explicitly out.

**Must have (table stakes — v1):**
- Speaker Profile CPT with short bio, long bio, professional title, headshot, social links (LinkedIn, Twitter/X, GitHub, Mastodon, Bluesky), contact/booking URL, and speaker rider text
- Conferences CPT with event name, date, city, country, event URL, talk reference, slides/video links per appearance — documents the speaking track record
- Talks CPT enhancements: Topics taxonomy, block editor support replacing legacy meta boxes
- Gutenberg display blocks: Speaker Profile, Talks List (with optional topic filter), Conference Map
- World Map Block using Leaflet.js — conference pins with click-to-detail popups; visually unique differentiator with no competitor equivalent
- FSE block templates for speaker-profile, archive-talk, single-talk, archive-conference
- Classic PHP template fallback for non-FSE themes (Divi, Avada, older Genesis)
- Shortcodes wrapping block rendering functions for legacy theme compatibility
- Developer hooks at all major output points

**Should have (competitive differentiators — v1.x after validation):**
- Multiple bio lengths (short ~75w, medium ~150w, long ~500w) — eliminates organizer email requests for "a shorter version"
- Stats Summary block ("X talks, Y conferences, Z countries") — derived from existing data, low complexity
- Schema.org JSON-LD output: Person (speaker profile), Event (conferences), PresentationDigitalDocument (talks)
- Structured speaker rider sections (Travel, AV, Honorarium, Stage, Scheduling)
- Full Talk–Conference bidirectional relationship (a talk given at multiple conferences)

**Defer to v2+:**
- Upcoming appearances / future event display — requires past vs. future logic; low value until plugin has adoption
- Speaker one-sheet PDF export — significant complexity (PDF library); evaluate if users request it
- Sessionize embed bridge — complex auth/sync problem

**Anti-features (out of scope, never build):**
- Booking calendar / availability system — Calendly/Cal.com exist; add a "Book Me" link field instead
- Multi-speaker support — contradicts single-speaker architecture; fundamentally different product
- CRM / lead tracking — link to HubSpot free, Notion, Airtable instead
- Video or slide file hosting — use the existing embed/link system; external platforms do this better

See `/Users/CRG/Local Sites/wordpress/app/public/wp-content/plugins/speekr/.planning/research/FEATURES.md` for the full dependency graph and competitor analysis.

### Architecture Approach

The architecture follows the 2025 WordPress canonical multi-block plugin pattern: a PSR-4 `src/` layer for PHP classes, a `blocks/` directory for JS/CSS block source, a `build/` directory for compiled output consumed by a single `Speekr\Blocks\Loader` class, and `templates/` for FSE HTML block templates. The legacy `inc/` directory is preserved unchanged during migration and replaced file-by-file as PSR-4 equivalents are verified working. All CPT data is stored as post meta registered via `register_post_meta()` with `show_in_rest: true`; blocks read and write meta via `useEntityProp()` in the editor and `get_post_meta()` in `render.php` for frontend output. The Conference Map block follows the dynamic block + viewScript pattern: `render.php` outputs conference lat/lng as a JSON data attribute; `view.js` (frontend-only) reads the attribute and initializes Leaflet, so React never loads on the frontend for the map.

**Major components:**
1. `Speekr\Plugin` — service locator; wires all subsystems to WordPress hooks
2. `Speekr\Common\{PostTypes, Meta, Taxonomies}` — data layer; CPT registration, meta registration, Topics taxonomy; must be complete before any block work
3. `Speekr\Blocks\Loader` — iterates `build/blocks/*/block.json`, calls `register_block_type()` for each; on WP 6.8+ uses manifest API
4. Block suite (6 blocks): `talk-meta`, `conference-meta`, `speaker-profile`, `talks-list`, `single-talk`, `conference-map` — each self-contained with `block.json`, `edit.js`, `render.php`, `style.scss`
5. `Speekr\FSE\Templates` — injects `WP_Block_Template` objects from `templates/*.html` via `get_block_templates` filter for block theme support
6. `Speekr\Front\{Templates, Shortcodes, Enqueues}` — classic theme fallback; shortcodes wrapping block render functions; conditional Leaflet enqueue
7. Legacy `inc/` — preserved during migration; functions wrapped with aliases delegating to new class methods

The build order is strictly determined by data dependencies: Foundation (Composer + @wordpress/scripts) → Data Layer (CPTs + meta) → Admin/Block Editor entry → New CPTs + their editor blocks → Display blocks → Templates + shortcodes → Legacy cleanup.

See `/Users/CRG/Local Sites/wordpress/app/public/wp-content/plugins/speekr/.planning/research/ARCHITECTURE.md` for full file structure, patterns, data flow diagrams, and anti-pattern documentation.

### Critical Pitfalls

All six critical pitfalls directly apply to Speekr's existing codebase. They are ordered by the phase that must address them.

1. **node-sass EOL build failure** — Speekr's `package.json` uses `node-sass@^4.14.1` which fails on Node 18+; remove and replace with `@wordpress/scripts` before any development work begins (Phase 0)
2. **CPT registered without `show_in_rest: true`** — Speekr's `inc/common/custom-posts.php` has neither `show_in_rest` nor `editor` in `supports`, nor `custom-fields`; the block editor will silently fall back to the classic editor for all CPTs until this is fixed; must be the very first PHP change (Phase 1)
3. **`save_post` double-fire overwrites meta with empty values** — Speekr's `inc/admin/custom-meta-boxes.php` almost certainly reads `$_POST` in a `save_post` callback; in the block editor context this fires with an empty `$_POST`, silently wiping field values; audit and gate all callbacks before writing any block code (Phase 1)
4. **Post meta not registered via `register_post_meta()`** — existing meta stored with raw `update_post_meta()` is invisible to `useEntityProp()`; register every meta key blocks will read or write, with `show_in_rest: true`, `single: true`, and an explicit `auth_callback` (Phase 1)
5. **Leaflet.js in iframed block editor** — Leaflet must only be enqueued on the frontend via `viewScript` in `block.json`; loading it in the editor context causes DOM targeting errors and broken map containers (Phase 3)
6. **FSE template registration fails on CPT slugs with underscores** — WP's template name validation regex rejects underscores; verify CPT slugs use hyphens, or register templates under a generic non-slug name (Phase 3)

**Moderate pitfalls to track:**
- Every `block.json` must have `"apiVersion": 3` from the start; missing this causes unpredictable editor iframing behavior
- Block `save()` function changes without a deprecation record cause "Block validation failed" on all existing posts — establish deprecation discipline on the first block built
- PSR-4 file naming conflicts with WordPress PHPCS ruleset; configure `phpcs.xml` to exclude the `WordPress.Files.FileName` sniff for `src/`
- Non-block JS files (admin scripts, frontend scripts) are not auto-discovered by `@wordpress/scripts`; they require explicit `webpack.config.js` entry points
- `register_post_meta()` without `auth_callback` exposes meta to any authenticated user via REST API — security requirement, never skip

See `/Users/CRG/Local Sites/wordpress/app/public/wp-content/plugins/speekr/.planning/research/PITFALLS.md` for the full pitfall-to-phase mapping and recovery strategies.

## Implications for Roadmap

Based on the combined research, the architecture's build-order dependency graph directly maps to roadmap phases. There is no ambiguity about sequencing — each layer depends on the previous being stable.

### Phase 0: Build Environment and Foundation
**Rationale:** The existing build toolchain is broken on modern Node.js and blocks all subsequent work. This is not optional preparation — it is a hard prerequisite. No block can be built without a working pipeline.
**Delivers:** Working `npm run build`, `npm run start` using `@wordpress/scripts`; Composer with PSR-4 autoloader loading; plugin bootstrap updated to load `vendor/autoload.php`; `Speekr\Plugin` service locator class in place; `webpack.config.js` with both block entry points and legacy admin/frontend script entry points.
**Addresses:** Node-sass EOL (Pitfall 6), non-block entry point discovery (Pitfall 10)
**Avoids:** No feature work starts until a developer can run `npm run build` on Node 20 without errors.
**Research flag:** Standard patterns — `@wordpress/scripts` migration is well-documented; no phase-specific research needed.

### Phase 1: Data Layer Modernization (CPTs, Meta, Taxonomy)
**Rationale:** All block editor functionality depends on CPTs having `show_in_rest: true` and every field having a proper `register_post_meta()` registration. This must be complete and verified before any block JavaScript is written — otherwise blocks appear to work but fail silently on save.
**Delivers:** All three CPTs (Talks, Conferences, SpeakerProfile) registered with `show_in_rest: true` and `custom-fields` in supports; every field registered via `register_post_meta()` with `show_in_rest: true`, `single: true`, and `auth_callback`; Topics taxonomy registered; all `save_post` callbacks in legacy meta boxes audited and gated against REST double-fire; PSR-4 `src/Common/` layer in place.
**Addresses:** Speaker Profile CPT (table stakes), Conferences CPT (table stakes), Topics taxonomy (table stakes, differentiator)
**Avoids:** CPT missing `show_in_rest` (Pitfall 1), `save_post` double-fire data loss (Pitfall 2), meta invisible in block editor (Pitfall 4), security exposure without `auth_callback` (security pitfall)
**Research flag:** Standard patterns — CPT registration and meta registration are canonical WordPress APIs; no phase research needed.

### Phase 2: Block Editor Scaffolding (Editor Blocks for CPTs)
**Rationale:** Once the data layer is in place, the editor blocks that replace legacy meta boxes can be built against a stable API. This phase replaces the classic meta box UI for the Talk CPT and adds editor blocks for the new Conferences and SpeakerProfile CPTs. The legacy `inc/admin/custom-meta-boxes.php` is kept active alongside the new blocks until parity is confirmed.
**Delivers:** `Speekr\Blocks\Loader` class; `blocks/talk-meta/` editor block (replaces Talk meta boxes); `blocks/conference-meta/` editor block; `blocks/speaker-profile/` editor block for the new SpeakerProfile CPT; all blocks with `"apiVersion": 3` and `"textdomain": "speekr"`; `phpcs.xml` configured for PSR-4 coexistence; legacy admin JS assessed and adapted to React lifecycle.
**Addresses:** Block editor support for all CPTs (table stakes), developer-facing compatibility
**Avoids:** Block API version missing (Pitfall 7), text domain mismatch (Pitfall 15), PSR-4 / WPCS naming conflict (Pitfall 9), jQuery / React lifecycle conflict (Pitfall 11), block validation discipline established from first block
**Research flag:** Standard patterns — `useEntityProp`, `PluginDocumentSettingPanel`, block registration are all well-documented; no phase research needed.

### Phase 3: Display Blocks (Public-Facing Frontend Blocks)
**Rationale:** With CPT data accessible via the block editor and REST API, the public display blocks can be built as dynamic blocks with `render.php` callbacks. The Conference Map block is the highest-complexity item and must follow the `viewScript` pattern strictly to avoid Leaflet iframe conflicts.
**Delivers:** `blocks/talks-list/` with optional topic filter; `blocks/single-talk/` for talk detail display; `blocks/conference-map/` with Leaflet 1.9.4 in `view.js` (frontend only), static placeholder in editor; all blocks as dynamic blocks (`save()` returns null, data lives in post meta); conditional Leaflet enqueue (`has_block()` check).
**Addresses:** World Map Block (key differentiator), Talks List with topic filtering, Speaker Profile display block
**Avoids:** Leaflet in iframed editor (Pitfall 12), loading block scripts on every page (Anti-Pattern 2), static save for CPT meta blocks (Anti-Pattern 1)
**Research flag:** Leaflet + viewScript pattern is documented but worth a quick confirmation against WP 6.8 viewScript behavior before implementation begins. Geocoding strategy for Conference lat/lng (manual entry vs. geocoding service at save time) is an open design decision.

### Phase 4: Templates and Legacy Compatibility
**Rationale:** With all blocks built and verified, FSE block templates can be assembled from those blocks. Classic theme fallbacks and shortcodes wrap existing block render functions — no new rendering logic, just new delivery paths.
**Delivers:** `templates/single-talks.html`, `templates/archive-talks.html`, `templates/single-conferences.html`, `templates/archive-conferences.html`; `Speekr\FSE\Templates` registration class (hook: `init`); classic PHP template files in `inc/front/templates/`; `Speekr\Front\Shortcodes` (`[speekr_talks]`, `[speekr_map]`, `[speekr_profile]`); developer hooks at all major output points.
**Addresses:** FSE templates (table stakes for modern themes), classic theme compatibility (table stakes for legacy themes), shortcodes, developer extensibility
**Avoids:** FSE template underscore CPT slug bug (Pitfall 5), `register_block_template()` / `wp_register_block_template()` naming (Pitfall 13), calling template registration on wrong hook
**Research flag:** Standard patterns for shortcodes; FSE template registration has one known underscore slug bug worth verifying against current WP version before implementing.

### Phase 5: Enhancements and v1.x Features
**Rationale:** With the core plugin stable and in use, v1.x additions can be validated against real usage patterns before committing to complexity.
**Delivers:** Multiple bio lengths (short/medium/long) on Speaker Profile CPT; Stats Summary block (derived from existing data); Schema.org JSON-LD output (Person + Event + Presentation); structured speaker rider sections.
**Addresses:** Multiple bio lengths (differentiator), Stats Summary block (differentiator), Schema.org output (SEO and rich results), speaker rider (differentiator)
**Research flag:** Schema.org vocabulary for `PresentationDigitalDocument` may need spot research to confirm the correct type and required properties. Otherwise standard patterns.

### Phase 6: Legacy Cleanup
**Rationale:** Only safe to execute after all `src/` equivalents are verified working in production. Removing `inc/` files before their replacements are confirmed working risks breaking existing installs.
**Delivers:** Removal of `inc/` files whose PSR-4 equivalents are verified; removal of legacy `Speekr` class once `Speekr\Plugin` fully replaces it; removal of legacy meta box registrations for CPTs that are fully served by block editor; clean repository with no dead code paths.
**Addresses:** Technical debt from migration period
**Research flag:** No research needed; execution and verification only.

### Phase Ordering Rationale

- **Foundation before data before blocks:** The Gutenberg block editor communicates entirely over the REST API. Every block that reads post meta depends on that meta being registered with `show_in_rest: true`. That registration depends on CPTs being registered with `show_in_rest: true`. Both depend on the PHP autoloader working. The dependency chain is strictly ordered.
- **Editor blocks before display blocks:** Editor blocks (Phase 2) establish the data writing path. Display blocks (Phase 3) depend on that data existing. Building display blocks first would require test data entry through the legacy meta boxes, creating validation confusion.
- **Templates after blocks:** FSE templates are assembled from blocks. They cannot be built before the blocks they reference exist.
- **Legacy cleanup last:** The additive migration pattern (keep `inc/`, add `src/`, remove `inc/` only when `src/` equivalent is verified) eliminates big-bang rewrite risk. Any earlier cleanup risks breaking existing installs.
- **Pitfall prevention is embedded in phase sequence:** The six critical pitfalls all have a natural prevention phase that aligns with when the relevant code is first touched. They are not a separate concern — they define why the phase order is what it is.

### Research Flags

Phases needing deeper research during planning:
- **Phase 3 (Display Blocks):** The geocoding strategy for Conference lat/lng needs a design decision before implementation — manual lat/lng entry vs. geocoding at save time via a third-party API vs. a local geocoding library. This is an unresolved design gap in the current research.
- **Phase 5 (v1.x Enhancements):** Schema.org `PresentationDigitalDocument` type properties need spot research to confirm correct vocabulary before JSON-LD implementation.

Phases with standard patterns (skip research-phase):
- **Phase 0 (Build Environment):** `@wordpress/scripts` migration is thoroughly documented in official WordPress Developer Blog posts.
- **Phase 1 (Data Layer):** CPT registration, `register_post_meta()`, and REST API exposure are canonical, stable APIs with official documentation.
- **Phase 2 (Editor Blocks):** `useEntityProp`, `PluginDocumentSettingPanel`, and block registration with `block.json` are well-documented.
- **Phase 4 (Templates and Legacy Compatibility):** Shortcode patterns and classic template loading are stable WordPress patterns; FSE template registration has the one known underscore slug bug to check but is otherwise documented.
- **Phase 6 (Cleanup):** Execution-only; no research needed.

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | All version choices verified against official Gutenberg GitHub, WordPress Developer Blog, and official package releases. Version pins are specific and rationale is concrete. |
| Features | MEDIUM-HIGH | Table stakes and anti-features are well-validated against live competitor sites and real-world speaker patterns. The specific field set for Speaker Profile CPT (bio lengths, rider structure) is informed by multiple independent sources. Competitor platform features verified against live pages. |
| Architecture | HIGH | All patterns sourced from official WordPress Developer Blog posts from 2025, official Block Editor Handbook, and verified against current Gutenberg trunk. The build order is derived from hard API dependencies, not opinion. |
| Pitfalls | HIGH | Six critical pitfalls are verified against official docs, WordPress GitHub issues, and — critically — the specific existing Speekr codebase (files like `inc/common/custom-posts.php` and `inc/admin/custom-meta-boxes.php` are directly referenced as containing the problematic patterns). |

**Overall confidence:** HIGH

### Gaps to Address

- **Geocoding strategy for Conference lat/lng:** The world map requires coordinates. Research did not resolve whether to use manual entry (simplest, requires speaker to look up lat/lng), a geocoding API call at post save (adds an external dependency and API key management), or a bundled country/city coordinate lookup table (no external dependency but limited precision). This must be decided before Phase 3 implementation begins.
- **CPT slug verification:** The existing `speekr_get_cpt_slug()` in `inc/functions/helpers.php` determines whether the current CPT slugs use underscores. If they do, the FSE template registration strategy must account for the underscore validation bug. Verify actual slug values before Phase 4.
- **WordPress.org submission scope:** Research confirmed source files must accompany compiled assets in SVN, and `vendor/` must be committed to SVN (unlike git). The deployment workflow for WordPress.org submission is not yet planned and should be addressed during Phase 4 or as a separate preparatory task.

## Sources

### Primary (HIGH confidence)
- [Gutenberg packages/scripts package.json (trunk)](https://github.com/WordPress/gutenberg/blob/trunk/packages/scripts/package.json) — confirmed @wordpress/scripts 31.5.0, React 18 peer dep, Webpack 5.97
- [Gutenberg webpack.config.js (trunk)](https://github.com/WordPress/gutenberg/blob/trunk/packages/scripts/config/webpack.config.js) — confirmed native sass-loader for SCSS
- [wp_register_block_types_from_metadata_collection() reference](https://developer.wordpress.org/reference/functions/wp_register_block_types_from_metadata_collection/) — WP 6.8.0 introduction confirmed
- [More efficient block type registration in 6.8](https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/) — blocks-manifest workflow
- [Refactoring the Multi-Block Plugin (WordPress Developer Blog, 2025)](https://developer.wordpress.org/news/2025/08/refactoring-the-multi-block-plugin-build-smarter-register-cleaner-scale-easier/) — 2025 canonical multi-block structure
- [PHP namespaces in WordPress plugins (WordPress Developer Blog, 2025)](https://developer.wordpress.org/news/2025/09/implementing-namespaces-and-coding-standards-in-wordpress-plugin-development/) — official PSR-4 guidance
- [Meta Boxes — Block Editor Handbook](https://developer.wordpress.org/block-editor/how-to-guides/metabox/) — `show_in_rest`, `custom-fields`, `save_post` double-fire behavior
- [Enqueueing Assets in the Editor — Block Editor Handbook](https://developer.wordpress.org/block-editor/how-to-guides/enqueueing-assets-in-the-editor/) — iframe isolation, WP 6.3+ behavior
- [Registering Block Templates via Plugins in WordPress 6.7](https://developer.wordpress.org/news/2024/08/registering-block-templates-via-plugins-in-wordpress-6.7/) — `register_block_template()` naming, requirements
- [Node Sass is End-of-Life — Sass Blog](https://sass-lang.com/blog/node-sass-is-end-of-life/) — EOL confirmed, repository archived July 2024
- [Sara Soueidan's Speaker Rider](https://www.sarasoueidan.com/speaker-rider/) — real-world rider structure from a prominent tech speaker
- [WordPress.org Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) — source file and GPL compliance requirements

### Secondary (MEDIUM confidence)
- [Sessionize Speakers Directory](https://sessionize.com/speakers-directory) — competitor profile field analysis (live page)
- [Notist (noti.st)](https://noti.st/) — competitor feature set (live page)
- [Speaking Events WordPress Plugin](https://wordpress.org/plugins/speaking-events/) — WordPress.org plugin competitor analysis (official source)
- [SpeakerHub Media Kit guidance](https://speakerhub.com/skillcamp/what-include-speaker-media-kit-example) — media kit component list
- [FSE template underscore bug — WebberZone](https://webberzone.com/custom-block-theme-templates-wordpress-plugins/) — community-reported; aligns with WP regex behavior
- [save_post double-fire — Gutenberg GitHub #20550](https://github.com/WordPress/gutenberg/issues/20550) — confirmed in official issue tracker
- [react-leaflet v5 release notes](https://github.com/PaulLeCam/react-leaflet/releases) — React 19 requirement confirmed
- [Leaflet 2.0 alpha announcement](https://leafletjs.com/2025/05/18/leaflet-2.0.0-alpha.html) — still pre-release as of Feb 2026

---
*Research completed: 2026-02-28*
*Ready for roadmap: yes*
