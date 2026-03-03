# Phase 5: Templates and Compatibility - Context

**Gathered:** 2026-03-03
**Status:** Ready for planning

<domain>
## Phase Boundary

Wire up three compatibility layers so the plugin works regardless of what theme the speaker runs: (1) FSE block templates for block themes, (2) classic PHP template files for classic themes with child theme override support, and (3) shortcodes for page-builder / legacy contexts. Add developer action/filter hooks at all major output points across all display blocks.

</domain>

<decisions>
## Implementation Decisions

### FSE template structure
- Each FSE `.html` template includes `wp:template-part` for header and footer — full page layout, not a minimal block-only wrapper
- Include `wp:post-title` in each template above the Speekr block (standard WP pattern, H1 heading)
- Speaker Profile template filename: `singular-speekr_speaker.html` (targets the CPT singular page directly)
- Conference archive template (`archive-speekr_conference.html`) includes **both** the map block above AND the conference-archive list block below — full conference history view out of the box
- Templates: `singular-speekr_speaker.html`, `archive-talks.html`, `single-talks.html`, `archive-speekr_conference.html`
- Register templates via `speekr_register_block_templates()` in `inc/front/templates.php`, hooked to `init`
- **Verify CPT slugs use hyphens (not underscores) before template registration** — WP template name validation rejects underscores (flagged in Phase 4 blocker; check `speekr_get_cpt_slug()` in `inc/functions/helpers.php`)

### Classic PHP template override mechanism
- Override lookup order: child theme → parent theme → plugin (`get_stylesheet_directory()` → `get_template_directory()` → plugin `templates/classic/`)
- Plugin's `template_include` filter checks this path and loads the first match
- Classic templates call block `render.php` directly via `require` with `$attributes = []` — same render path as blocks, no duplicate logic
- Speaker profile template fires on `is_singular('speekr_speaker')` — any `speekr_speaker` post, not a designated settings page
- Classic template files: `archive-talk.php`, `single-talk.php`, `speaker-profile.php`, `archive-conference.php`, `single-conference.php`
- Implement `speekr_template_include()` in `inc/front/templates.php` hooked to `template_include`

### Shortcodes
- 3 shortcodes only: `[speekr_profile]`, `[speekr_talks]`, `[speekr_map]` — single-talk and conference-archive are CPT templates, not page-embeddable widgets
- Key attributes exposed per shortcode:
  - `[speekr_profile layout="side-by-side|stacked" show_download="0|1"]`
  - `[speekr_talks layout="grid|list" limit="<n>"]`
  - `[speekr_map height="<px>"]`
- Shortcode callbacks call `wp_enqueue_style()` / `wp_enqueue_script()` for their block's assets — WordPress deduplicates if block is also on the page
- Shortcodes render by requiring the corresponding block `render.php` with `$attributes` built from shortcode atts merged with defaults
- Implement in `inc/front/shortcodes.php`, loaded via `require_once` in `Speekr::includes()`

### Developer action/filter hooks
- **Before/after hooks** pass `$post_id` and `$attributes` array: `do_action('speekr_before_profile', $speaker_id, $attributes)`
- **All 5 display blocks get before/after hooks** (consistent pattern):
  - `speekr_before_profile` / `speekr_after_profile`
  - `speekr_before_talks_list` / `speekr_after_talks_list`
  - `speekr_before_map` / `speekr_after_map`
  - `speekr_before_conference_archive` / `speekr_after_conference_archive`
  - `speekr_before_single_talk` / `speekr_after_single_talk`
- **`speekr_talk_output` filter** (per-talk HTML modifier): `apply_filters('speekr_talk_output', $html, $talk_post, $attributes)` — developer gets rendered HTML, WP_Post object, and block/shortcode attributes
- All hooks placed inside the respective block `render.php` files
- Hooks fire from both block and shortcode render paths (same code path via shared render.php)

### Claude's Discretion
- Exact `wp:template-part` slug names for header/footer in FSE templates (follow the active block theme's template part slugs, with `header` and `footer` as fallbacks)
- Exact `wp:group` wrapper structure inside FSE templates
- Whether `speekr_template_include()` extends `Speekr_Templates_Loader` or is a standalone function
- How `$attributes` is structured when classic templates require render.php (empty array `[]` is fine; render.php defaults handle it)

</decisions>

<specifics>
## Specific Ideas

- Conference archive FSE template: map above, list below — gives a full visual conference history view in one template
- Classic templates use render.php directly — single code path, no divergence between block, shortcode, and template output
- Shortcodes are low-config: `[speekr_talks]` works with no atts, `[speekr_talks layout="list" limit="5"]` for customization

</specifics>

<code_context>
## Existing Code Insights

### Reusable Assets
- `inc/classes/Speekr_Templates_Loader.php`: existing page template loader (handles `theme_page_templates` + `template_include` for page templates). The Phase 5 `template_include` hook for CPT templates is a separate concern — either extend this class or add a standalone function. The existing class should not be removed.
- `inc/front/templates/speekr-grid.php`: legacy page template using `do_action()` hooks — establishes the hook naming pattern
- `src/blocks/*/render.php`: 5 render files (speaker-profile, talks-list, single-talk, conference-archive, conference-map) — shortcodes and classic templates require these directly
- `inc/front/enqueues.php`, `inc/front/lists.php`, `inc/front/single.php`: existing front-end includes loaded via `Speekr::includes_front()`

### Established Patterns
- `inc/front/` is the directory for frontend-only PHP files; new `templates.php` and `shortcodes.php` go here
- Files loaded via `require_once` in `inc/classes/Speekr.php` → `Speekr::includes()` for code needed on all requests (including REST), or `Speekr::includes_front()` for frontend-only
- Shortcodes and template_include filter are frontend-only → load via `includes_front()`
- Hook naming convention: `speekr_` prefix, snake_case (established by existing hooks in `inc/cpt/talks.php` and `inc/classes/Speekr.php`)
- Existing `apply_filters('speekr_talks_rewrite_slug', ...)` and `apply_filters('speekr_talk_cpt_args', ...)` establish the filter pattern

### Integration Points
- `Speekr::includes_front()` in `inc/classes/Speekr.php`: add `require_once` for `inc/front/templates.php` and `inc/front/shortcodes.php`
- Block render.php files: add `do_action()` calls at the start and end of output in each of the 5 render files
- `block.json` template names must match WordPress template hierarchy slugs — verify with `speekr_get_cpt_slug()` in `inc/functions/helpers.php` before writing `.html` filenames
- `inc/front/press-kit.php`: already loaded via `includes()` — shortcodes load via `includes_front()` (different hook)

</code_context>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 05-templates-and-compatibility*
*Context gathered: 2026-03-03*
