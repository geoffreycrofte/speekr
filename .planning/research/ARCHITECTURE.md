# Architecture Research

**Domain:** WordPress plugin modernization — Gutenberg blocks, FSE templates, PSR-4 PHP, CPT data layer
**Researched:** 2026-02-28
**Confidence:** HIGH (official WordPress developer docs + verified patterns)

## Standard Architecture

### System Overview

```
┌──────────────────────────────────────────────────────────────────┐
│                     WORDPRESS REQUEST LAYER                       │
│  init hook → CPT registration, meta registration, block types    │
├──────────────────────────────────────────────────────────────────┤
│                      PHP PLUGIN LAYER (PSR-4)                    │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌───────────┐  │
│  │  Bootstrap │  │   Admin    │  │   Blocks   │  │   Front   │  │
│  │  Plugin.php│  │  namespace │  │  namespace │  │ namespace │  │
│  └─────┬──────┘  └─────┬──────┘  └─────┬──────┘  └─────┬─────┘  │
│        │               │               │               │         │
│        └───────────────┴───────────────┴───────────────┘         │
│                         Common namespace                          │
│         (CPT registration, meta registration, helpers)           │
├──────────────────────────────────────────────────────────────────┤
│                     DATA LAYER (WordPress DB)                    │
│  ┌──────────────┐  ┌──────────────┐  ┌────────────────────────┐  │
│  │  Talks CPT   │  │ Conferences  │  │   Speaker Profile      │  │
│  │  post meta   │  │   CPT meta   │  │   CPT/options meta     │  │
│  └──────────────┘  └──────────────┘  └────────────────────────┘  │
├──────────────────────────────────────────────────────────────────┤
│                     JAVASCRIPT / BLOCK LAYER                     │
│  ┌──────────────────┐  ┌─────────────────────────────────────┐   │
│  │  Editor Scripts  │  │        Frontend / View Scripts      │   │
│  │  (editorScript)  │  │  (viewScript — Leaflet, display)    │   │
│  │  React + WP data │  │  Plain JS or Interactivity API      │   │
│  └──────────────────┘  └─────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────┘
```

### Component Responsibilities

| Component | Responsibility | Typical Implementation |
|-----------|----------------|------------------------|
| `speekr.php` (root) | Bootstrap only: load autoloader, define constants, instantiate Plugin class | Thin file, no logic |
| `Speekr\Plugin` | Service locator: wire up all subsystems on hooks | PSR-4 class, `init()` method |
| `Speekr\Common\PostTypes` | Register CPTs (Talks, Conferences, SpeakerProfile) with `show_in_rest: true` | PSR-4 class |
| `Speekr\Common\Meta` | `register_post_meta()` for all fields with `show_in_rest: true` | PSR-4 class |
| `Speekr\Blocks\Loader` | Loop over `build/blocks/*/block.json` and call `register_block_type()` | PSR-4 class, `init` hook |
| `Speekr\Admin\MetaBoxes` | Legacy admin meta boxes (kept during transition, removed when blocks replace them) | PSR-4 class |
| `Speekr\Admin\Settings` | Settings page, options API | PSR-4 class |
| `Speekr\Front\Templates` | Classic theme template loader (existing pattern, migrated to namespace) | PSR-4 class |
| `Speekr\Front\Shortcodes` | Shortcode registration + rendering | PSR-4 class |
| `Speekr\FSE\Templates` | Register FSE block templates via `get_block_templates` filter | PSR-4 class |
| Block `edit.js` | React component: reads/writes post meta via `useEntityProp`, renders editor UI | One file per block |
| Block `render.php` | PHP render callback for dynamic blocks: reads post meta, outputs HTML | One file per block |
| Block `view.js` | Frontend script (Leaflet init for map block, display interactions) | Only map block needs this |

## Recommended Project Structure

```
speekr/
├── speekr.php                    # Bootstrap only: constants, autoloader, new Plugin()
├── composer.json                 # PSR-4: "Speekr\\": "src/"
├── composer.lock
├── package.json                  # @wordpress/scripts, sass
├── package-lock.json
│
├── src/                          # PHP source (PSR-4 → Speekr\ namespace)
│   ├── Plugin.php                # Speekr\Plugin — service locator
│   ├── Common/
│   │   ├── PostTypes.php         # CPT registration (Talks, Conferences, SpeakerProfile)
│   │   ├── Taxonomies.php        # Topics taxonomy registration
│   │   ├── Meta.php              # All register_post_meta() calls
│   │   ├── ImageSizes.php        # Custom image sizes (migrated from inc/)
│   │   └── DefaultData.php       # Default talk types etc. (migrated from inc/)
│   ├── Admin/
│   │   ├── MetaBoxes.php         # Legacy meta boxes (keep during block transition)
│   │   ├── Settings.php          # Settings page
│   │   ├── Menus.php             # Admin menu registration
│   │   ├── Notices.php           # Admin notices
│   │   ├── Ajax.php              # AJAX handlers
│   │   ├── Enqueues.php          # Admin script/style enqueues
│   │   └── Importer.php          # Data importer
│   ├── Blocks/
│   │   └── Loader.php            # Registers all blocks from build/blocks/
│   ├── Front/
│   │   ├── Templates.php         # Classic theme template loader
│   │   ├── Shortcodes.php        # Shortcode registration
│   │   ├── Enqueues.php          # Frontend script/style enqueues
│   │   ├── Lists.php             # Talk list rendering helpers
│   │   └── Single.php            # Single talk rendering helpers
│   └── FSE/
│       └── Templates.php         # Block theme template registration
│
├── blocks/                       # JS/CSS source for all blocks (compiled → build/blocks/)
│   ├── talk-meta/                # Block: editor panel for Talk CPT meta fields
│   │   ├── block.json
│   │   ├── index.js              # registerBlockType entry
│   │   ├── edit.js               # React edit component (useEntityProp for meta)
│   │   ├── editor.scss
│   │   └── render.php            # Dynamic: reads meta, outputs HTML
│   ├── conference-meta/          # Block: editor panel for Conference CPT meta
│   │   ├── block.json
│   │   ├── index.js
│   │   ├── edit.js
│   │   ├── editor.scss
│   │   └── render.php
│   ├── speaker-profile/          # Display block: renders speaker bio/links
│   │   ├── block.json
│   │   ├── index.js
│   │   ├── edit.js
│   │   ├── style.scss
│   │   └── render.php
│   ├── talks-list/               # Display block: archive/listing of talks
│   │   ├── block.json
│   │   ├── index.js
│   │   ├── edit.js
│   │   ├── style.scss
│   │   └── render.php
│   ├── single-talk/              # Display block: single talk card/embed
│   │   ├── block.json
│   │   ├── index.js
│   │   ├── edit.js
│   │   ├── style.scss
│   │   └── render.php
│   └── conference-map/           # Display block: Leaflet.js world map
│       ├── block.json            # viewScript: "file:./view.js"
│       ├── index.js
│       ├── edit.js
│       ├── view.js               # Leaflet init (frontend only, not editor)
│       ├── style.scss
│       └── render.php            # Outputs map container + data attributes
│
├── build/                        # Compiled output (gitignored, generated by wp-scripts)
│   └── blocks/
│       ├── talk-meta/
│       │   ├── block.json        # copied verbatim
│       │   ├── index.js          # compiled
│       │   ├── render.php        # copied verbatim
│       │   └── index.css
│       └── [other blocks same pattern]
│
├── templates/                    # FSE block template HTML files (plugin-provided)
│   ├── single-talks.html
│   ├── archive-talks.html
│   ├── single-conferences.html
│   └── archive-conferences.html
│
├── assets/                       # Static assets (keep existing structure)
│   ├── css/
│   │   └── src/                  # SCSS source (replaced node-sass → sass)
│   ├── js/
│   │   ├── speekr-admin.js       # Existing jQuery admin scripts (keep during transition)
│   │   └── speekr.js             # Existing frontend scripts
│   ├── img/
│   └── fonts/
│
├── languages/                    # Translation files
├── inc/                          # Legacy PHP (kept during migration, deprecated gradually)
│   └── [existing files — do not delete until namespace equivalents are verified working]
└── vendor/                       # Composer autoload output (gitignored)
    └── autoload.php
```

### Structure Rationale

- **`src/`**: PSR-4 root mapping `Speekr\` → all PHP classes. Subfolders match namespace segments. No `require_once` needed after autoloader is loaded.
- **`blocks/`**: JS/CSS source separated from PHP source. Each block is self-contained. `@wordpress/scripts` auto-discovers `blocks/*/index.js` as entry points.
- **`build/blocks/`**: Compiled output consumed by `Speekr\Blocks\Loader`. The loader iterates subdirectories and calls `register_block_type( $dir )` pointing at each `block.json`. This is the canonical WordPress multi-block pattern as of 2025.
- **`templates/`**: FSE HTML templates provided by the plugin. These are HTML files using block markup, registered via `Speekr\FSE\Templates` using the `get_block_templates` filter. Theme can override by providing the same-named file in its own `templates/` dir.
- **`inc/`**: Preserved unchanged during migration. Legacy files are deprecated one by one as PSR-4 equivalents are verified working. No big-bang rewrite risk.
- **`vendor/`**: Composer output. Never edited manually. Added to `.gitignore`.

## Architectural Patterns

### Pattern 1: Dynamic Block with Post Meta (CPT data entry)

**What:** A block registered in the editor for a specific CPT. The `edit.js` component reads and writes post meta via `useEntityProp`. The `render.php` file reads the same meta on the frontend via `get_post_meta()`.

**When to use:** All CPT data fields (Talk conference name, conference URL, media links, summary; Conference location, date; Speaker bio, social links). Post meta is queryable server-side, survives block markup changes, and is accessible via REST API.

**Trade-offs:** Requires `register_post_meta()` with `show_in_rest: true`. Data lives in `wp_postmeta` not in block HTML serialization. Cannot use static `save()` function — block must be dynamic (`render` in `block.json` pointing at `render.php`).

**Example (block.json for a dynamic block):**
```json
{
  "name": "speekr/talk-meta",
  "title": "Talk Details",
  "category": "speekr",
  "description": "Manage talk metadata in the block editor",
  "supports": { "html": false, "inserter": false },
  "usesContext": ["postId", "postType"],
  "editorScript": "file:./index.js",
  "editorStyle": "file:./index.css",
  "render": "file:./render.php"
}
```

**Example (edit.js — read/write post meta):**
```javascript
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';

export function Edit() {
  const postType = useSelect( select =>
    select('core/editor').getCurrentPostType()
  );
  const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

  const confName = meta['speekr-conf'] ? meta['speekr-conf'].name : '';
  const updateConfName = ( value ) => setMeta({
    ...meta,
    'speekr-conf': { ...meta['speekr-conf'], name: value }
  });

  return <TextControl
    label="Conference Name"
    value={ confName }
    onChange={ updateConfName }
  />;
}
```

**PHP requirement (register_post_meta must precede block registration):**
```php
register_post_meta( 'talks', 'speekr-conf', [
  'show_in_rest' => true,
  'single'       => true,
  'type'         => 'object',
  'properties'   => [ 'name' => [ 'type' => 'string' ], 'url' => [ 'type' => 'string' ] ],
]);
```

### Pattern 2: PluginDocumentSettingPanel (Sidebar meta for CPTs)

**What:** A JS file registered as `editorScript` (not a `block.json` block) that uses `PluginDocumentSettingPanel` to add a sidebar panel to the post editor. Useful for CPT fields that aren't "blocks" per se (metadata panels, not content).

**When to use:** Alternative to Pattern 1 for fields that should appear in the document sidebar rather than the post content area. Useful for conference-level metadata that doesn't need to be placed in the content stream.

**Trade-offs:** Not block-based — cannot be placed in templates. Still reads/writes via `useEntityProp`. Requires `@wordpress/plugins` and `@wordpress/edit-post` imports. Only renders when the editor is active (not frontend).

**Example:**
```javascript
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { useEntityProp } from '@wordpress/core-data';

registerPlugin('speekr-conference-panel', {
  render: () => (
    <PluginDocumentSettingPanel name="speekr-conference" title="Conference Details">
      { /* meta fields here */ }
    </PluginDocumentSettingPanel>
  ),
});
```

### Pattern 3: Multi-Block Registration via Block Loader

**What:** A single PHP class that iterates over `build/blocks/` subdirectories and calls `register_block_type()` for each `block.json` found. On WordPress 6.8+, use `wp_register_block_types_from_metadata_collection()` with a generated `blocks-manifest.php`.

**When to use:** Any plugin with 2+ blocks. Eliminates manual registration of each block.

**Trade-offs:** Requires a reliable build step that copies `block.json` (and `render.php`) into `build/blocks/`. The `@wordpress/scripts` build process handles this automatically when blocks are in `blocks/*/`.

**Example (Speekr\Blocks\Loader):**
```php
namespace Speekr\Blocks;

class Loader {
  public function register(): void {
    $blocks_dir = plugin_dir_path( SPEEKR_FILE ) . 'build/blocks/';
    foreach ( glob( $blocks_dir . '*', GLOB_ONLYDIR ) as $block_dir ) {
      register_block_type( $block_dir );
    }
  }
}
// Hooked: add_action( 'init', [ new Loader(), 'register' ] );
```

### Pattern 4: FSE Template Registration via Plugin

**What:** PHP class hooking `get_block_templates` filter to inject `WP_Block_Template` objects built from HTML files in `plugin/templates/`. Supports block themes (FSE) without requiring the theme to include the templates.

**When to use:** All CPT-specific templates (single-talks.html, archive-talks.html, etc.) that the plugin should provide by default, overridable by themes.

**Trade-offs:** Requires `show_in_rest: true` on the CPT. The HTML templates must use valid block markup. Changes to template HTML require re-saving or cache clear. Themes can override by providing same-named templates.

**Example (FSE\Templates::register):**
```php
add_filter( 'get_block_templates', function( $templates, $query, $template_type ) {
  $plugin_templates = glob( plugin_dir_path( SPEEKR_FILE ) . 'templates/*.html' );
  foreach ( $plugin_templates as $file ) {
    $slug = basename( $file, '.html' );
    // Skip if a theme or DB version already provides this slug
    $already_registered = array_filter( $templates, fn($t) => $t->slug === $slug );
    if ( ! empty( $already_registered ) ) continue;

    $template           = new \WP_Block_Template();
    $template->id       = 'speekr//' . $slug;
    $template->theme    = 'speekr';
    $template->slug     = $slug;
    $template->source   = 'plugin';
    $template->type     = $template_type;
    $template->content  = file_get_contents( $file );
    $templates[]        = $template;
  }
  return $templates;
}, 10, 3 );
```

### Pattern 5: PSR-4 Bootstrap + Legacy Compatibility Shim

**What:** The root `speekr.php` loads `vendor/autoload.php` first, then continues to `require_once` legacy `inc/` files. The new `Speekr\Plugin` class co-exists with the legacy `Speekr` class. Legacy functions (`speekr_*`) continue working. New functionality is added only in namespaced classes.

**When to use:** Entire migration phase — until all `inc/` files have been replaced by `src/` equivalents and verified safe to remove.

**Trade-offs:** Brief period of duplication. Clear separation: anything using `speekr_` prefix is legacy; anything under `Speekr\` namespace is new. No risk of breaking existing installs during migration.

**Example (root bootstrap file, transition state):**
```php
// speekr.php

// 1. Constants (unchanged)
define( 'SPEEKR_FILE', __FILE__ );
// ...

// 2. Composer autoloader (NEW)
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
  require_once __DIR__ . '/vendor/autoload.php';
}

// 3. Legacy bootstrap (keep until fully migrated)
require_once SPEEKR_CLASSES_DIR . 'Speekr.php';
$speekr = new Speekr(); // legacy class, unchanged

// 4. New plugin class (NEW, additive only)
$speekr_modern = new \Speekr\Plugin();
$speekr_modern->init();
```

## Data Flow

### Editor Data Flow (Block Meta Entry)

```
User edits field in block editor
    ↓
edit.js useEntityProp( 'postType', 'talks', 'meta' )
    ↓ (React state update)
@wordpress/data store (core/entities/record)
    ↓ (on save_post / autosave)
REST API PATCH /wp/v2/talks/{id}  [meta field must have show_in_rest: true]
    ↓
WordPress saves to wp_postmeta table
    ↓
render.php get_post_meta( $post_id, 'speekr-conf', true )
    ↓
HTML output served to frontend visitor
```

### Frontend Map Data Flow (Conference Map Block)

```
Conference CPT posts exist in DB (lat/lng in post meta)
    ↓
render.php: builds JSON array of [ { lat, lng, title, url } ]
    → outputs as data attribute or inline <script> JSON
    ↓
block.json viewScript: "file:./view.js"   (only loads when block is on page)
    ↓
view.js: reads JSON from DOM, initializes Leaflet map
    → wp_enqueue_script( 'leaflet' ) registered separately in Front\Enqueues
    ↓
Leaflet renders interactive map with conference pins
    ↓
User clicks pin → popup shows conference details
```

### FSE Template Resolution

```
WordPress request for /talks/my-talk/
    ↓
Template hierarchy: single-talks.html
    ↓
Check DB (saved customizations) → Check active theme templates/ → Check plugin templates/
    ↓
FSE\Templates filter injects plugin WP_Block_Template if no theme/DB version exists
    ↓
Block editor renders template → PHP render callbacks fire for dynamic blocks
    ↓
HTML delivered to visitor
```

### Classic Theme Fallback Flow

```
Classic theme active (no FSE support)
    ↓
WordPress template hierarchy: single-talks.php → single.php → index.php
    ↓
Front\Templates::loader() hooks template_include filter
    → serves plugin's inc/front/templates/*.php if theme has no equivalent
    ↓
Template calls speekr_get_talk_embed(), shortcodes, action hooks
    ↓
Front\Enqueues loads speekr.css + speekr.js for classic layout
```

## Scaling Considerations

| Scale | Architecture Adjustments |
|-------|--------------------------|
| 1 speaker, local WP | Current approach is correct. No optimization needed. |
| Plugin distributed (1k+ installs) | Ensure `vendor/` is bundled OR use WordPress-native autoloading fallback. Test with common themes (Twenty* series). |
| High-traffic speaker site | Block template output is HTML — statically cacheable by any WP caching plugin. Leaflet map data comes from server render; cache the render.php output with transients for REST-heavy queries. |

### Scaling Priorities

1. **First bottleneck:** Conference map REST query on every page load. Fix: `set_transient()` in `render.php` for the lat/lng JSON, bust on Conference post save.
2. **Second bottleneck:** Multiple CPT queries on archive pages. Fix: Standard WP object cache. No plugin-specific work needed.

## Anti-Patterns

### Anti-Pattern 1: Static Save Function for CPT Meta Blocks

**What people do:** Use a static `save()` function in blocks that store CPT data, serializing meta into block HTML attributes.

**Why it's wrong:** Block validation errors on every post update when post meta changes, because saved HTML no longer matches. Any change to block structure requires a migration. Post meta data stored in block HTML is not queryable server-side.

**Do this instead:** Use `"render": "file:./render.php"` in `block.json`. Make the block dynamic. `save()` returns `null`. All data lives in post meta via `register_post_meta`.

### Anti-Pattern 2: Loading All Block Scripts on Every Page

**What people do:** Enqueue all block editor scripts and frontend view scripts globally in `wp_enqueue_scripts`.

**Why it's wrong:** Leaflet.js, React components for editor, and block-specific CSS load even on pages with no Speekr blocks. Significant performance penalty.

**Do this instead:** Use `editorScript`, `style`, and `viewScript` fields in `block.json`. WordPress only loads scripts/styles when the block is actually present on the page or in the editor. Leaflet goes in `viewScript` — only loads when the conference-map block is present.

### Anti-Pattern 3: Big-Bang Refactor of Legacy inc/ Files

**What people do:** Delete all `inc/` files at once and replace with PSR-4 equivalents.

**Why it's wrong:** Existing installs break. Functions called by third-party code (`speekr_get_options()`, action/filter hooks) disappear. Backward compatibility promise broken.

**Do this instead:** Run legacy and modern code in parallel. Keep all `inc/` files. New functionality goes in `src/`. Replace `inc/` functions one at a time, wrapping new class methods with legacy function aliases. Remove legacy file only when its replacement is verified and covered.

### Anti-Pattern 4: Registering Meta Without show_in_rest

**What people do:** Add `register_post_meta()` but omit `'show_in_rest' => true`.

**Why it's wrong:** `useEntityProp` in the block editor reads meta via the REST API. Without `show_in_rest: true`, the field is invisible to the editor, and the block silently fails to read or write the value.

**Do this instead:** Always include `'show_in_rest' => true` for any meta field that the block editor needs to read or write. For object/array meta, also specify `'type'` and either a `'schema'` or `'properties'` sub-key.

### Anti-Pattern 5: Storing All Talk Fields in One Serialized Meta Key

**What people do:** Store all talk metadata as one big serialized array in a single meta key (the existing `speekr-conf` and `speekr-media-links` approach).

**Why it's wrong:** REST API cannot expose individual array properties easily. Complex `show_in_rest` schema required. Meta query on individual fields is impossible. Block editor has to deserialize/reserialize the whole blob on every field change.

**Do this instead (for new CPTs):** Register each logical field as its own meta key with `register_post_meta`. For backward compatibility on existing Talk meta keys (`speekr-conf`, `speekr-media-links`): expose them as objects via `show_in_rest` with an explicit `schema`. New Conference and SpeakerProfile CPTs should use individual meta keys from the start.

## Integration Points

### External Services

| Service | Integration Pattern | Notes |
|---------|---------------------|-------|
| Leaflet.js | `wp_register_script()` in `Front\Enqueues`, then referenced via `viewScript` block.json field OR `wp_enqueue_script` dependency chain | Use cdnjs or bundle; OpenStreetMap tiles require attribution |
| oEmbed providers (YouTube, Vimeo, SpeakerDeck, Slideshare) | Existing `wp_remote_get()` to oEmbed endpoints in `helpers.php` — migrate to `Speekr\Common\Embed` class | Keep existing logic, just move to namespace |
| WordPress REST API | Used implicitly by `useEntityProp` in all editor blocks | No custom endpoints needed for meta-backed blocks; CPTs need `show_in_rest: true` |
| OpenStreetMap | Via Leaflet.js tile layers | Free, no API key; attribution required in map UI |

### Internal Boundaries

| Boundary | Communication | Notes |
|----------|---------------|-------|
| PHP ↔ Block Editor (JS) | REST API — post meta via `/wp/v2/{cpt}/{id}` | All meta must be `show_in_rest: true` |
| render.php ↔ post meta | Direct `get_post_meta()` call | Standard WP server-side; fast |
| view.js ↔ render.php | Data attributes or inline JSON in HTML | render.php writes data, view.js reads it on DOMContentLoaded |
| Legacy `inc/` ↔ `src/` classes | Function aliases: legacy functions delegate to new class methods | Transition boundary; both exist simultaneously |
| FSE Templates ↔ Classic Templates | Mutually exclusive per request: FSE active = block template wins; classic theme = template loader wins | Both code paths live in codebase; PHP detects which applies |
| `Speekr\Common\Meta` ↔ all blocks | Meta keys registered once in Meta class, shared by PHP renders and JS editor components | Single source of truth for meta key names |

## Suggested Build Order

Dependencies between components determine the safe build sequence. Each phase requires the previous to be complete.

```
Phase 1: Foundation (no dependencies)
  └── composer.json + PSR-4 mapping
  └── Speekr\Plugin bootstrap class
  └── package.json updated (@wordpress/scripts, sass replacing node-sass)
  └── webpack.config.js (extends @wordpress/scripts default, adds blocks/ entry points)

Phase 2: Data Layer (requires Phase 1)
  └── Speekr\Common\PostTypes — CPT registrations with show_in_rest: true
  └── Speekr\Common\Meta — register_post_meta() for all CPT fields
  └── Speekr\Common\Taxonomies — Topics taxonomy

Phase 3: Admin Layer / Block Editor Entry (requires Phase 2)
  └── Speekr\Blocks\Loader — block type registration loop
  └── blocks/talk-meta/ — editor UI block for existing Talk CPT
  └── Speekr\Admin\* migrations (MetaBoxes, Settings, Menus kept working)

Phase 4: New CPTs + Their Blocks (requires Phase 2, 3)
  └── Conferences CPT + meta registration
  └── SpeakerProfile CPT + meta registration
  └── blocks/conference-meta/ — editor block for Conferences CPT
  └── blocks/speaker-profile/ — editor block for SpeakerProfile

Phase 5: Display Blocks (requires Phase 4)
  └── blocks/talks-list/ — frontend listing block
  └── blocks/single-talk/ — single talk display block
  └── blocks/conference-map/ — Leaflet.js map block

Phase 6: Templates (requires Phase 5)
  └── templates/*.html — FSE block templates
  └── Speekr\FSE\Templates — registration logic
  └── Classic theme template files (inc/front/templates/ additions)
  └── Speekr\Front\Shortcodes

Phase 7: Cleanup (requires all previous)
  └── Remove inc/ files as src/ equivalents are verified
  └── Remove legacy Speekr class when Speekr\Plugin fully replaces it
```

## Sources

- [Block Editor Fundamentals — File Structure of a Block](https://developer.wordpress.org/block-editor/getting-started/fundamentals/file-structure-of-a-block/) — HIGH confidence
- [Block Registration — developer.wordpress.org](https://developer.wordpress.org/block-editor/getting-started/fundamentals/registration-of-a-block/) — HIGH confidence
- [Meta Boxes Migration to Block Editor](https://developer.wordpress.org/block-editor/how-to-guides/metabox/) — HIGH confidence
- [Refactoring the Multi-Block Plugin (WordPress Developer Blog, 2025)](https://developer.wordpress.org/news/2025/08/refactoring-the-multi-block-plugin-build-smarter-register-cleaner-scale-easier/) — HIGH confidence
- [Implementing Namespaces in WordPress Plugin Development (WordPress Developer Blog, 2025)](https://developer.wordpress.org/news/2025/09/implementing-namespaces-and-coding-standards-in-wordpress-plugin-development/) — HIGH confidence
- [Creating a Custom Block That Stores Post Meta (WordPress Developer Blog)](https://developer.wordpress.org/news/2023/03/creating-a-custom-block-that-stores-post-meta/) — HIGH confidence
- [Register Block Templates via Plugins — WebberZone](https://webberzone.com/custom-block-theme-templates-wordpress-plugins/) — MEDIUM confidence (verified against WP Handbook patterns)
- [PluginDocumentSettingPanel Reference](https://developer.wordpress.org/block-editor/reference-guides/slotfills/plugin-document-setting-panel/) — HIGH confidence
- [PSR-4 Autoloading for WordPress Plugins — DLX Plugins](https://dlxplugins.com/tutorials/creating-a-psr-4-autoloading-wordpress-plugin) — MEDIUM confidence (aligns with official PSR-4 standard)
- [Block metadata viewScriptModule field in 6.5](https://make.wordpress.org/core/2024/03/04/block-metadata-viewscriptmodule-field-in-6-5/) — HIGH confidence

---
*Architecture research for: Speekr WordPress plugin modernization*
*Researched: 2026-02-28*
