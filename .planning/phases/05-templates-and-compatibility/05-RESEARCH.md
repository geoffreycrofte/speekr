# Phase 5: Templates and Compatibility - Research

**Researched:** 2026-03-03
**Domain:** WordPress FSE block templates, classic PHP theme templates, shortcodes, action/filter hooks
**Confidence:** HIGH

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

**FSE template structure**
- Each FSE `.html` template includes `wp:template-part` for header and footer — full page layout, not a minimal block-only wrapper
- Include `wp:post-title` in each template above the Speekr block (standard WP pattern, H1 heading)
- Speaker Profile template filename: `singular-speekr_speaker.html` (targets the CPT singular page directly)
- Conference archive template (`archive-speekr_conference.html`) includes both the map block above AND the conference-archive list block below — full conference history view out of the box
- Templates: `singular-speekr_speaker.html`, `archive-talks.html`, `single-talks.html`, `archive-speekr_conference.html`
- Register templates via `speekr_register_block_templates()` in `inc/front/templates.php`, hooked to `init`
- Verify CPT slugs use hyphens (not underscores) before template registration — WP template name validation rejects underscores (flagged in Phase 4 blocker; check `speekr_get_cpt_slug()` in `inc/functions/helpers.php`)

**Classic PHP template override mechanism**
- Override lookup order: child theme → parent theme → plugin (`get_stylesheet_directory()` → `get_template_directory()` → plugin `templates/classic/`)
- Plugin's `template_include` filter checks this path and loads the first match
- Classic templates call block `render.php` directly via `require` with `$attributes = []` — same render path as blocks, no duplicate logic
- Speaker profile template fires on `is_singular('speekr_speaker')` — any `speekr_speaker` post, not a designated settings page
- Classic template files: `archive-talk.php`, `single-talk.php`, `speaker-profile.php`, `archive-conference.php`, `single-conference.php`
- Implement `speekr_template_include()` in `inc/front/templates.php` hooked to `template_include`

**Shortcodes**
- 3 shortcodes only: `[speekr_profile]`, `[speekr_talks]`, `[speekr_map]`
- Key attributes exposed per shortcode:
  - `[speekr_profile layout="side-by-side|stacked" show_download="0|1"]`
  - `[speekr_talks layout="grid|list" limit="<n>"]`
  - `[speekr_map height="<px>"]`
- Shortcode callbacks call `wp_enqueue_style()` / `wp_enqueue_script()` for their block's assets
- Shortcodes render by requiring the corresponding block `render.php` with `$attributes` built from shortcode atts merged with defaults
- Implement in `inc/front/shortcodes.php`, loaded via `require_once` in `Speekr::includes()`

**Developer action/filter hooks**
- Before/after hooks pass `$post_id` and `$attributes` array
- All 5 display blocks get before/after hooks:
  - `speekr_before_profile` / `speekr_after_profile`
  - `speekr_before_talks_list` / `speekr_after_talks_list`
  - `speekr_before_map` / `speekr_after_map`
  - `speekr_before_conference_archive` / `speekr_after_conference_archive`
  - `speekr_before_single_talk` / `speekr_after_single_talk`
- `speekr_talk_output` filter (per-talk HTML modifier): `apply_filters('speekr_talk_output', $html, $talk_post, $attributes)`
- All hooks placed inside the respective block `render.php` files
- Hooks fire from both block and shortcode render paths (same code path via shared render.php)

### Claude's Discretion
- Exact `wp:template-part` slug names for header/footer in FSE templates (follow the active block theme's template part slugs, with `header` and `footer` as fallbacks)
- Exact `wp:group` wrapper structure inside FSE templates
- Whether `speekr_template_include()` extends `Speekr_Templates_Loader` or is a standalone function
- How `$attributes` is structured when classic templates require render.php (empty array `[]` is fine; render.php defaults handle it)

### Deferred Ideas (OUT OF SCOPE)
None — discussion stayed within phase scope.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| DEV-01 | Plugin provides FSE block templates for block themes: speaker profile page, talk archive, single talk, conference archive | `register_block_template()` API (WP 6.7+), confirmed working in WP 6.9.1 with underscore fix |
| DEV-02 | Plugin provides classic PHP template files overridable in child themes: `speaker-profile.php`, `archive-talk.php`, `single-talk.php`, `archive-conference.php`, `single-conference.php` | `template_include` filter pattern + three-path lookup (child → parent → plugin) |
| DEV-03 | Plugin exposes action and filter hooks at all major output points | `do_action()` / `apply_filters()` inside render.php files; established `speekr_` prefix pattern |
| DEV-04 | Plugin provides shortcodes wrapping block render functions: `[speekr_profile]`, `[speekr_talks]`, `[speekr_map]` | `add_shortcode()` + `require` render.php with `$attributes` built from shortcode atts |
</phase_requirements>

---

## Summary

Phase 5 wires three compatibility layers onto the existing display blocks from Phase 4. All five block `render.php` files already use the correct direct-echo pattern (no ob_start) and accept an `$attributes` array — they are fully ready to be required by both classic templates and shortcode callbacks.

The FSE layer uses `register_block_template()`, introduced in WordPress 6.7 and confirmed in the installed WP 6.9.1. Critically, the underscore validation bug (Trac #62523) that would have rejected template slugs like `speekr_conference` has been fixed in the installed version — the regex is now `/^[a-z0-9_\-]+\/\/[a-z0-9_\-]+$/`. Template slugs must match the WordPress template hierarchy (e.g., `single-talks`, `archive-talks`, `singular-speekr_speaker`, `archive-speekr_conference`).

The classic template layer uses a standalone `speekr_template_include()` function on the `template_include` filter — distinct from the existing `Speekr_Templates_Loader` class which handles page templates only. The shortcode layer lives in `inc/front/shortcodes.php` and calls `add_shortcode()` for three handles, each requiring the corresponding block render.php with a constructed `$attributes` array. Developer hooks (`do_action` / `apply_filters`) are inserted directly into all five render.php files so they fire from every render path automatically.

**Primary recommendation:** Implement all three compatibility layers in two new files (`inc/front/templates.php` and `inc/front/shortcodes.php`), add `do_action`/`apply_filters` hooks to all five render.php files, and load both new files via `Speekr::includes_front()`.

---

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| `register_block_template()` | WP 6.7+ (WP 6.9.1 installed) | Register FSE plugin templates in Site Editor | Official WP Core API; no workarounds needed at this WP version |
| `template_include` filter | All WP versions | Override the template file loaded by WP for CPT pages | Canonical WP hook for theme template overrides |
| `add_shortcode()` | All WP versions | Register shortcode handlers | Standard WP shortcode API |
| `do_action()` / `apply_filters()` | All WP versions | Developer extension hooks | WP plugin hook API; established pattern in plugin already |

### No External Dependencies
All work is pure WordPress PHP — no additional packages required.

---

## Architecture Patterns

### Recommended Project Structure

New files:
```
inc/front/
├── templates.php        # speekr_register_block_templates() + speekr_template_include()
├── shortcodes.php       # add_shortcode() for [speekr_profile], [speekr_talks], [speekr_map]
└── templates/
    └── classic/
        ├── archive-talk.php
        ├── single-talk.php
        ├── speaker-profile.php
        ├── archive-conference.php
        └── single-conference.php
```

FSE templates directory (plugin root):
```
templates/
├── singular-speekr_speaker.html
├── archive-talks.html
├── single-talks.html
└── archive-speekr_conference.html
```

### Pattern 1: FSE Template Registration via register_block_template()

**What:** Register named FSE templates with WordPress so they appear in the Site Editor and are used when a block theme is active.
**When to use:** Block theme is active (WordPress detects automatically; plugin-registered templates are only surfaced for block themes).

Template name format: `{plugin-folder-slug}//{template-slug}`
- Plugin folder for this plugin: `speekr`
- Template slugs must match WP template hierarchy names for automatic routing

```php
// Source: WP 6.9.1 /wp-includes/class-wp-block-templates-registry.php
// Validation regex: /^[a-z0-9_\-]+\/\/[a-z0-9_\-]+$/
// Underscores ARE allowed in WP 6.9.1 (bug fixed from 6.7 original)

function speekr_register_block_templates() {
    register_block_template(
        'speekr//singular-speekr_speaker',
        array(
            'title'   => __( 'Speaker Profile', 'speekr' ),
            'content' => file_get_contents( SPEEKR_DIRNAME . '/templates/singular-speekr_speaker.html' ),
        )
    );

    register_block_template(
        'speekr//archive-talks',
        array(
            'title'   => __( 'Talks Archive', 'speekr' ),
            'content' => file_get_contents( SPEEKR_DIRNAME . '/templates/archive-talks.html' ),
        )
    );

    register_block_template(
        'speekr//single-talks',
        array(
            'title'   => __( 'Single Talk', 'speekr' ),
            'content' => file_get_contents( SPEEKR_DIRNAME . '/templates/single-talks.html' ),
        )
    );

    register_block_template(
        'speekr//archive-speekr_conference',
        array(
            'title'   => __( 'Conference Archive', 'speekr' ),
            'content' => file_get_contents( SPEEKR_DIRNAME . '/templates/archive-speekr_conference.html' ),
        )
    );
}
add_action( 'init', 'speekr_register_block_templates' );
```

### Pattern 2: FSE Template HTML Content

Templates must be valid block markup. Store in separate `.html` files for readability. Use `file_get_contents()` to pass content to `register_block_template()`.

Example content for `singular-speekr_speaker.html`:
```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
    <!-- wp:post-title {"level":1} /-->
    <!-- wp:speekr/speaker-profile /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

Example content for `archive-speekr_conference.html` (map above + list below):
```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
    <!-- wp:post-title {"level":1} /-->
    <!-- wp:speekr/conference-map /-->
    <!-- wp:speekr/conference-archive /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

### Pattern 3: Classic Template Override via template_include

**What:** A standalone function on `template_include` that intercepts CPT requests and loads the plugin's classic PHP templates, with child theme and parent theme override support.
**When to use:** Classic (non-FSE) theme is active.

```php
// Three-path lookup: child theme > parent theme > plugin
function speekr_template_include( $template ) {
    $template_map = array();

    if ( is_singular( 'speekr_speaker' ) ) {
        $template_map = array(
            get_stylesheet_directory() . '/speekr/speaker-profile.php',
            get_template_directory()   . '/speekr/speaker-profile.php',
            SPEEKR_DIRNAME . '/inc/front/templates/classic/speaker-profile.php',
        );
    } elseif ( is_post_type_archive( speekr_get_cpt_slug() ) ) {
        $template_map = array(
            get_stylesheet_directory() . '/speekr/archive-talk.php',
            get_template_directory()   . '/speekr/archive-talk.php',
            SPEEKR_DIRNAME . '/inc/front/templates/classic/archive-talk.php',
        );
    } elseif ( is_singular( speekr_get_cpt_slug() ) ) {
        $template_map = array(
            get_stylesheet_directory() . '/speekr/single-talk.php',
            get_template_directory()   . '/speekr/single-talk.php',
            SPEEKR_DIRNAME . '/inc/front/templates/classic/single-talk.php',
        );
    } elseif ( is_post_type_archive( 'speekr_conference' ) ) {
        $template_map = array(
            get_stylesheet_directory() . '/speekr/archive-conference.php',
            get_template_directory()   . '/speekr/archive-conference.php',
            SPEEKR_DIRNAME . '/inc/front/templates/classic/archive-conference.php',
        );
    } elseif ( is_singular( 'speekr_conference' ) ) {
        $template_map = array(
            get_stylesheet_directory() . '/speekr/single-conference.php',
            get_template_directory()   . '/speekr/single-conference.php',
            SPEEKR_DIRNAME . '/inc/front/templates/classic/single-conference.php',
        );
    }

    foreach ( $template_map as $candidate ) {
        if ( file_exists( $candidate ) ) {
            return $candidate;
        }
    }

    return $template;
}
add_filter( 'template_include', 'speekr_template_include' );
```

**Classic template file pattern** — each classic template calls `get_header()`, sets up a `$attributes = []` array, requires the block's render.php, then calls `get_footer()`:

```php
<?php
// inc/front/templates/classic/speaker-profile.php
get_header();
?>
<main id="primary" class="site-main">
    <?php
    $attributes = array();
    require SPEEKR_DIRNAME . '/src/blocks/speaker-profile/render.php';
    ?>
</main>
<?php
get_footer();
```

### Pattern 4: Shortcode Registration

**What:** Shortcodes that wrap block render.php files, building `$attributes` from shortcode atts.
**When to use:** Any theme, especially page builders or legacy classic theme contexts.

```php
// inc/front/shortcodes.php

function speekr_shortcode_profile( $atts ) {
    $atts = shortcode_atts( array(
        'layout'        => 'side-by-side',
        'show_download' => 0,
    ), $atts, 'speekr_profile' );

    $attributes = array(
        'layout'        => $atts['layout'],
        'allowDownload' => (bool) $atts['show_download'],
    );

    ob_start();
    // Block enqueues handle assets; shortcode path also needs them.
    wp_enqueue_style( 'speekr-speaker-profile-style',
        SPEEKR_PLUGIN_URL . 'build/blocks/speaker-profile/style-index.css',
        array(), SPEEKR_VERSION );
    require SPEEKR_DIRNAME . '/src/blocks/speaker-profile/render.php';
    return ob_get_clean();
}
add_shortcode( 'speekr_profile', 'speekr_shortcode_profile' );
```

**CRITICAL NOTE:** `ob_start()` + `ob_get_clean()` IS safe in shortcode callbacks. The block-render incompatibility (Phase 4 KEY PITFALL) only affects render.php files loaded by `register_block_type()` — WordPress wraps those in its own outer ob_start(). Shortcode callbacks run in normal PHP flow with no outer buffer wrapping. Using ob_start() in shortcode callbacks is the standard pattern.

### Pattern 5: Developer Action/Filter Hooks in render.php

**What:** `do_action()` calls at the start and end of output in each render.php; `apply_filters()` for per-talk HTML.
**When to use:** Add to all 5 display block render.php files.

```php
// At the start of substantive output (after all variable setup, before first HTML):
do_action( 'speekr_before_profile', $post_id, $attributes );

// ... existing HTML output ...

// At the end of output (after closing tag):
do_action( 'speekr_after_profile', $post_id, $attributes );
```

Per-talk filter in talks-list render.php (wraps each `<article>` card's HTML):
```php
// Capture the article HTML, then pass through filter
ob_start();
?>
<article class="speekr-talk-card" ...>
    ...
</article>
<?php
$talk_html = ob_get_clean();
$talk_post  = get_post( $pid );
echo apply_filters( 'speekr_talk_output', $talk_html, $talk_post, $attributes );
```

**NOTE on ob_start() in render.php:** The Phase 4 rule "never use ob_start in render.php" applies to wrapping the entire render.php output. Using ob_start() locally to capture a single article element (not the whole file) is safe because the outer WordPress buffer already wraps the file — local ob_start/ob_get_clean pairs for sub-sections are fine.

### Anti-Patterns to Avoid

- **Separate render logic in classic templates:** Don't duplicate the block's render logic in classic template files. Always `require` the block `render.php` with `$attributes = []`.
- **Wrapping entire render.php in ob_start:** The outer WP buffer wraps render.php on block render. Never add a top-level `ob_start()` / `return ob_get_clean()` pattern in render.php. (Confirmed root cause from Phase 4.)
- **Named functions in render.php:** Don't use `function foo()` inside render.php — they redeclare fatally on repeated renders. Use `$fn = static function() {}` closures instead. (Established Phase 4 pattern.)
- **Hardcoding template part slugs:** Don't hardcode `header`/`footer` only; FSE themes vary in slug names. The fallback `header`/`footer` is acceptable for the plugin default, but document that admins can customize via Site Editor.
- **Loading shortcodes via includes() (all requests):** Shortcodes are frontend-only — load via `includes_front()`. REST API requests don't need shortcodes.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| FSE template registration | Custom `wp_templates` post insertion, custom `template_include` for block themes | `register_block_template()` | WP 6.7+ API; handles Site Editor integration, theme override precedence, custom template detection |
| Asset deduplication in shortcodes | Manual checks for already-enqueued scripts | `wp_enqueue_style()` / `wp_enqueue_script()` | WordPress deduplicates by handle automatically |
| Template part slug detection | Querying active theme for parts | Use `header`/`footer` as defaults; Site Editor lets users customize | Acceptable default; no code needed |

**Key insight:** The `register_block_template()` API is the only correct way to surface plugin templates in FSE Site Editor in WP 6.7+. The older approach of dropping `.html` files in a `block-templates/` folder inside the plugin is not supported — only themes can do file-based FSE templates.

---

## Common Pitfalls

### Pitfall 1: Underscore in CPT slug — historical validation bug
**What goes wrong:** On WP 6.7–6.8, `register_block_template('speekr//singular-speekr_speaker', ...)` returned a `WP_Error` because the validation regex `/^[a-z0-9-]+\/\/[a-z0-9-]+$/` excluded underscores.
**Why it happens:** The original regex didn't include `_` in the character class.
**How to avoid:** Confirmed RESOLVED in WP 6.9.1 (installed version). The regex is now `/^[a-z0-9_\-]+\/\/[a-z0-9_\-]+$/`. No workaround needed. Verify by checking `wp-includes/class-wp-block-templates-registry.php` line 53.
**Warning signs:** `WP_Error` with code `template_no_prefix` returned from `register_block_template()`.

### Pitfall 2: ob_start() in shortcode callbacks vs. render.php distinction
**What goes wrong:** Developer conflates the Phase 4 "never use ob_start in render.php" rule with shortcode callbacks and avoids capturing output, breaking the shortcode return value.
**Why it happens:** The Phase 4 rule is specifically about render.php files registered with `register_block_type()` — WP wraps these in an outer ob_start(). Shortcode callbacks have no such outer buffer.
**How to avoid:** Use `ob_start()` + `require` + `ob_get_clean()` in shortcode callbacks — this is the standard and only pattern. The Phase 4 restriction does NOT apply to shortcode callbacks.
**Warning signs:** Shortcode outputs nothing (empty string) or renders content outside the shortcode position.

### Pitfall 3: speekr_template_include() conflicting with Speekr_Templates_Loader
**What goes wrong:** The new `speekr_template_include()` function and the existing `Speekr_Templates_Loader::add_template_filter()` both hook `template_include` — they could interfere if not ordered correctly.
**Why it happens:** `Speekr_Templates_Loader` handles page templates (user-selected `_wp_page_template` meta). The new function handles CPT archive/singular routing. They operate on different conditions, so they don't conflict — but must not be merged.
**How to avoid:** Keep them separate. `speekr_template_include()` checks `is_singular()` / `is_post_type_archive()` conditions and bails if none match. `Speekr_Templates_Loader` checks `get_page_template_slug()`. No overlap.
**Warning signs:** CPT pages unexpectedly loading page templates from `inc/front/templates/` (the old legacy directory).

### Pitfall 4: Classic template locate path vs. plugin template path mismatch
**What goes wrong:** Classic templates call `require SPEEKR_DIRNAME . '/src/blocks/speaker-profile/render.php'` — but this is the source file. After `npm run build`, the compiled render.php is a copy in `build/blocks/speaker-profile/render.php`.
**Why it happens:** `register_block_type()` uses the `build/` path for block render files. Classic templates and shortcodes should also use `build/` paths for consistency — or use `src/` paths since render.php files contain pure PHP (not compiled).
**How to avoid:** PHP `render.php` files are pure PHP — they are not compiled by webpack. The `src/blocks/` path is correct and canonical. `build/blocks/` contains a copy registered with `register_block_type()`. Use `src/blocks/` in classic templates and shortcodes to avoid subtle divergence (though both are equivalent).
**Warning signs:** Template changes in `src/blocks/*/render.php` not reflected in classic templates.

### Pitfall 5: Talks CPT has no archive by default
**What goes wrong:** The Talks CPT registration uses `'has_archive' => false` implicitly — `is_post_type_archive('talks')` returns false, so the archive-talk.php classic template never fires.
**Why it happens:** Reading `inc/cpt/talks.php`: no `has_archive` key → defaults to false. The `archive-talks` FSE template targets the archive but only renders if WordPress can route to it.
**How to avoid:** Check if `has_archive` needs to be set to `true` on the talks CPT, or if the `archive-talks` template should use a different routing strategy (e.g., a designated page). This requires verification against the actual CPT registration before implementing the archive template.
**Warning signs:** `/talks/` returns 404; `is_post_type_archive('talks')` returns false even with published talks.

### Pitfall 6: Shortcode requires render.php but $attributes uses different key names
**What goes wrong:** block.json attributes use camelCase (`allowDownload`, `speakerId`) but shortcode atts use snake_case (`show_download`). The shortcode must translate these correctly.
**Why it happens:** WordPress shortcode attributes are typically lowercase/snake_case while JS/JSON attributes use camelCase.
**How to avoid:** In the shortcode callback, build `$attributes` with camelCase keys matching block.json exactly (e.g., `'allowDownload' => (bool) $atts['show_download']`). The render.php checks `$attributes['allowDownload']`, not `$attributes['show_download']`.
**Warning signs:** Download button never shows in `[speekr_profile show_download="1"]`; layout always defaults to `side-by-side` despite passing `layout` attribute.

---

## Code Examples

### FSE Template Registration (full function)

```php
// Source: WP 6.9.1 register_block_template() API
// File: inc/front/templates.php

function speekr_register_block_templates() {
    $tmpl_dir = SPEEKR_DIRNAME . '/templates/';

    $templates = array(
        'speekr//singular-speekr_speaker'    => array(
            'title' => __( 'Speaker Profile', 'speekr' ),
            'file'  => 'singular-speekr_speaker.html',
        ),
        'speekr//archive-talks'              => array(
            'title' => __( 'Talks Archive', 'speekr' ),
            'file'  => 'archive-talks.html',
        ),
        'speekr//single-talks'               => array(
            'title' => __( 'Single Talk', 'speekr' ),
            'file'  => 'single-talks.html',
        ),
        'speekr//archive-speekr_conference'  => array(
            'title' => __( 'Conference Archive', 'speekr' ),
            'file'  => 'archive-speekr_conference.html',
        ),
    );

    foreach ( $templates as $name => $def ) {
        $path = $tmpl_dir . $def['file'];
        if ( ! file_exists( $path ) ) {
            continue;
        }
        register_block_template( $name, array(
            'title'   => $def['title'],
            'content' => file_get_contents( $path ),
        ) );
    }
}
add_action( 'init', 'speekr_register_block_templates' );
```

### Shortcode Callback Pattern

```php
// Source: WordPress shortcode API + Phase 4 block $attributes conventions
// File: inc/front/shortcodes.php

function speekr_shortcode_talks( $atts ) {
    $atts = shortcode_atts( array(
        'layout' => 'grid',
        'limit'  => -1,
    ), $atts, 'speekr_talks' );

    $attributes = array(
        'layout' => sanitize_key( $atts['layout'] ),
        'limit'  => (int) $atts['limit'],
    );

    // Enqueue block assets; WP deduplicates by handle.
    wp_enqueue_style(
        'speekr-talks-list-style',
        SPEEKR_PLUGIN_URL . 'build/blocks/talks-list/style-index.css',
        array(),
        SPEEKR_VERSION
    );
    wp_enqueue_script(
        'speekr-talks-list-view',
        SPEEKR_PLUGIN_URL . 'build/blocks/talks-list/view.js',
        array(),
        SPEEKR_VERSION,
        true
    );

    ob_start();
    require SPEEKR_DIRNAME . '/src/blocks/talks-list/render.php';
    return ob_get_clean();
}
add_shortcode( 'speekr_talks', 'speekr_shortcode_talks' );
```

### Developer Hook Placement (speaker-profile/render.php)

```php
// Source: Established speekr_ hook naming pattern (inc/cpt/talks.php, Speekr.php)
// Add immediately before first HTML output (after $post_id is resolved):

do_action( 'speekr_before_profile', $post_id, $attributes );
?>
<div class="wp-block-speekr-speaker-profile <?php echo esc_attr( $layout_class ); ?>">
    ...
</div>
<?php
do_action( 'speekr_after_profile', $post_id, $attributes );
```

### Per-Talk Output Filter (talks-list/render.php)

```php
// Wrap each article card so the filter receives complete card HTML.
// Using a local ob_start() inside the loop — NOT wrapping the whole file.

ob_start();
?>
<article class="speekr-talk-card" data-topics="<?php echo $topics_attr; ?>">
    <?php /* ... existing card markup ... */ ?>
</article>
<?php
$talk_html  = ob_get_clean();
$talk_post  = get_post( $pid );
echo apply_filters( 'speekr_talk_output', $talk_html, $talk_post, $attributes ); // phpcs:ignore
```

### Loading New Files in Speekr::includes_front()

```php
// Source: inc/classes/Speekr.php
// Add to includes_front():

public function includes_front() {
    do_action( 'speekr_before_includes_front' );

    require_once( SPEEKR_DIRNAME . '/inc/front/enqueues.php' );
    require_once( SPEEKR_DIRNAME . '/inc/front/lists.php' );
    require_once( SPEEKR_DIRNAME . '/inc/front/single.php' );
    require_once( SPEEKR_DIRNAME . '/inc/front/templates.php' );  // Phase 5: new
    require_once( SPEEKR_DIRNAME . '/inc/front/shortcodes.php' ); // Phase 5: new

    do_action( 'speekr_after_includes_front' );
}
```

**IMPORTANT:** `register_block_template()` must run on `init` regardless of frontend/admin context because it registers into the template registry used by both Site Editor and frontend rendering. The `add_action('init', 'speekr_register_block_templates')` call inside `templates.php` will only execute when `templates.php` is loaded — and since it's in `includes_front()`, it only runs on non-admin requests. This is fine because the Site Editor runs in the admin context where a different mechanism serves templates. However, if the Site Editor needs to pick up plugin templates, `templates.php` may need to load via `includes()` (all requests) rather than `includes_front()`. Research inconclusive — verify during implementation by testing in Site Editor.

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Drop `.html` files in plugin `block-templates/` folder | `register_block_template()` API | WP 6.7 (Nov 2024) | Plugin templates now visible in Site Editor; theme can override |
| Underscore forbidden in template slugs | Underscores allowed (`[a-z0-9_\-]+`) | WP 6.9 / patch to 6.8 | `speekr_conference` and `speekr_speaker` slugs work directly |
| Custom shortcodes duplicate render logic | Shortcodes require the same render.php | Phase 4 render.php established | Single code path; no divergence |

**Deprecated/outdated:**
- `Speekr_Templates_Loader::add_template_filter()`: Handles `_wp_page_template` meta (page templates). NOT to be used for CPT template routing — keep it for backward compatibility only.

---

## Open Questions

1. **Does talks CPT have `has_archive => true`?**
   - What we know: `inc/cpt/talks.php` does not explicitly set `has_archive` — defaults to `false`. The FSE template `archive-talks` and classic `archive-talk.php` target a talks archive.
   - What's unclear: Whether the archive template will ever be reached without enabling `has_archive`. If the intended pattern is "always use the list page" (see `speekr_get_pages_id('list_page')`), the archive template may be a no-op.
   - Recommendation: Enable `has_archive => true` on the Talks CPT during implementation, or confirm the archive template should not exist if talks use a designated page instead.

2. **Does `register_block_template()` need to run on all requests or frontend-only?**
   - What we know: The Site Editor operates in an admin context. If `templates.php` is only loaded in `includes_front()`, the hook `add_action('init', 'speekr_register_block_templates')` won't fire during admin requests — including the Site Editor.
   - What's unclear: Whether `WP_Block_Templates_Registry` needs to be populated during admin/Site Editor requests for templates to appear there.
   - Recommendation: Load `templates.php` via `Speekr::includes()` (all requests) rather than `includes_front()`. This is consistent with the pattern used for CPTs, taxonomies, and blocks — all of which register on all requests for REST/admin access.

3. **Classic template directory for child theme overrides — which subfolder?**
   - What we know: The CONTEXT.md specifies child themes override via `get_stylesheet_directory() . '/speekr/{template-file}.php'`.
   - What's unclear: This subfolder path (`/speekr/`) is a convention choice — not enforced anywhere. It should be documented in a README or inline comment.
   - Recommendation: Use `/speekr/` subfolder; add a comment block in the plugin template files explaining the override path.

---

## Sources

### Primary (HIGH confidence)
- WP 6.9.1 source: `/wp-includes/class-wp-block-templates-registry.php` — verified regex `/^[a-z0-9_\-]+\/\/[a-z0-9_\-]+$/`, full register() method
- WP 6.9.1 source: `/wp-includes/block-template.php` line 411 — `register_block_template()` function definition
- Plugin source: `src/blocks/*/render.php` — all 5 files verified, direct-echo pattern confirmed
- Plugin source: `inc/classes/Speekr.php` — `includes_front()` and `includes()` structure confirmed
- Plugin source: `inc/classes/Speekr_Templates_Loader.php` — handles `_wp_page_template` meta only; confirmed separate from CPT routing concern

### Secondary (MEDIUM confidence)
- [Registering block templates via plugins in WordPress 6.7 – WordPress Developer Blog](https://developer.wordpress.org/news/2024/08/registering-block-templates-via-plugins-in-wordpress-6-7/) — API introduction, content format, file_get_contents pattern
- [register_block_template() – Function Reference](https://developer.wordpress.org/reference/functions/register_block_template/) — parameter documentation
- [Creating templates for custom post types – Full Site Editing](https://fullsiteediting.com/lessons/creating-block-templates-for-custom-post-types/) — template hierarchy naming conventions

### Tertiary (LOW confidence — verify during implementation)
- [Gutenberg Issue #67066](https://github.com/WordPress/gutenberg/issues/67066) — confirms underscore bug; fix status in WP 6.9 inferred from installed source, not from official release notes

---

## Metadata

**Confidence breakdown:**
- FSE registration API: HIGH — verified against installed WP 6.9.1 source
- Template slug validation (underscore fix): HIGH — verified in installed WP source directly
- Classic template pattern: HIGH — standard WordPress `template_include` filter, established in existing Speekr_Templates_Loader
- Shortcode ob_start safety: HIGH — standard WordPress shortcode pattern; Phase 4 restriction verified as block-render-specific
- talks CPT archive status: LOW — has_archive not set in talks.php; archive template may be unreachable without a fix

**Research date:** 2026-03-03
**Valid until:** 2026-06-03 (90 days — WP Core APIs are stable)
