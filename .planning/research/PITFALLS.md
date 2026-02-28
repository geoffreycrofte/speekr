# Pitfalls Research

**Domain:** WordPress plugin modernization — Gutenberg blocks, FSE, PHP modernization
**Researched:** 2026-02-28
**Confidence:** HIGH (verified via official WordPress Developer docs, Block Editor Handbook, and official GitHub issues)

---

## Critical Pitfalls

### Pitfall 1: CPT Missing `show_in_rest` — Block Editor Silently Falls Back to Classic

**What goes wrong:**
The Gutenberg block editor will not load for a custom post type that lacks `show_in_rest => true` in its registration args. The editor falls back to the classic editor without throwing any error or warning. This is already the case in Speekr: `speekr_register_post_types()` in `inc/common/custom-posts.php` has neither `show_in_rest` nor `editor` in `supports`, and `custom-fields` is also commented out.

**Why it happens:**
Gutenberg communicates entirely over the REST API. CPTs default to `show_in_rest => false` for historical reasons. Developers assume adding block support is purely a JS-side concern and miss the PHP registration step.

**How to avoid:**
Add all three to `register_post_type()` args before any block work begins:
```php
'show_in_rest' => true,
'supports'     => [ 'title', 'editor', 'author', 'thumbnail', 'revisions', 'custom-fields' ],
```
`custom-fields` support is required for `register_post_meta()` to function at all. Without it, meta registered with `show_in_rest => true` will silently fail to appear in the REST payload.

**Warning signs:**
- Block editor loads the classic editor UI on the Talk CPT screen
- `register_post_meta()` calls complete without errors but values never appear in `useEntityProp()`
- REST API request to `/wp-json/wp/v2/talks` returns 404 or 403

**Phase to address:**
Phase 1 (Foundation / CPT modernization) — must be the very first change before any block registration.

---

### Pitfall 2: `save_post` Hook Fires Twice (and Overwrites Meta With Empty Values)

**What goes wrong:**
When the block editor saves a post, WordPress fires two requests: a REST API save, then a separate `admin-ajax.php` request to sync legacy meta boxes (`meta-box-loader=1`). The `save_post` hook fires on both. Any meta-saving logic tied to `save_post` that reads from `$_POST` will write empty values on the second pass, because `$_POST` is empty in the REST context.

This is the single most common data-loss bug when migrating meta boxes to Gutenberg. Speekr's `inc/admin/custom-meta-boxes.php` almost certainly uses `$_POST` reads inside a `save_post` callback.

**Why it happens:**
The classic editor serialized all form data into `$_POST` on submit. The block editor uses the REST API for the post save, then fires `save_post` as a side effect. `$_POST` is not populated in this path. Developers don't notice because the save "works" in the classic editor context of the meta-box-loader request.

**How to avoid:**
- Gate any `$_POST`-based meta saving with: `if ( isset( $_POST['meta-box-loader'] ) ) { ... }`
- Better: migrate meta to block attributes or use `register_post_meta()` with `show_in_rest => true`, then read via `useEntityProp()` in JS — this bypasses `save_post` entirely for those fields
- Use `rest_after_insert_{post_type}` instead of `save_post` for REST-originated saves; it receives the correct post object

**Warning signs:**
- Meta values disappear or reset to empty after saving a talk in the block editor
- `save_post` callback runs but `$_POST['speekr_*']` keys are absent
- Data saves correctly in classic editor mode but not in block editor mode

**Phase to address:**
Phase 1 (Meta migration) — audit all `save_post` callbacks in `inc/admin/custom-meta-boxes.php` before writing any block code.

---

### Pitfall 3: Block Editor iframe Isolation Breaks Admin JS (WordPress 6.3+)

**What goes wrong:**
Speekr's `speekr-admin.js` uses jQuery extensively and manipulates DOM nodes like `#postimagediv`, `#wp-content-wrap`, and custom meta box containers. In WordPress 6.3+, the post editor is iframed when all blocks use Block API v3 and no traditional meta boxes remain. jQuery-based DOM manipulation targeting the outer document will stop working silently once the editor becomes iframed, because the admin script runs in the parent window while the editor content is inside the iframe.

**Why it happens:**
Developers assume the block editor DOM structure is similar to the classic editor. The `enqueue_block_editor_assets` hook always loads scripts into the parent window, not the iframe. jQuery selectors for editor-internal elements like `#postimagediv` won't find anything inside the iframe.

**How to avoid:**
- If keeping meta boxes (hybrid approach), register them explicitly with `__block_editor_compatible_meta_box => true` — this prevents the editor from becoming iframed
- For any UI that must live inside the editor context, use block sidebar panels via `PluginSidebar` or `PluginDocumentSettingPanel` (React components from `@wordpress/edit-post`)
- Do not use `enqueue_block_editor_assets` to load jQuery-dependent scripts that target editor content nodes

**Warning signs:**
- `$('#postimagediv')` returns an empty jQuery object in browser console
- The cover replacement move in `speekr-admin.js` (line 13: `$('#speekr-cover-replacement').insertAfter(...)`) stops working
- Browser console shows no errors but UI elements don't appear in the right place

**Phase to address:**
Phase 2 (Block UI / sidebar migration) — plan the React-based sidebar replacement before removing meta boxes; keep meta boxes until the replacement is complete to prevent iframe isolation.

---

### Pitfall 4: Post Meta Not Available in Block Editor Without `show_in_rest` on `register_post_meta()`

**What goes wrong:**
`useEntityProp('postType', 'speekr_talk', 'meta')` returns `undefined` or an empty object even when meta is being saved correctly in PHP. This silently breaks any block or sidebar control that reads existing post meta.

**Why it happens:**
The REST API only includes post meta fields that were explicitly registered via `register_post_meta()` with `show_in_rest => true`. Meta values stored via `update_post_meta()` directly (without registering the key) are invisible to the block editor. Speekr's current meta saving uses raw `update_post_meta()` calls without registration.

**How to avoid:**
Register every meta key that blocks or the sidebar will read or write:
```php
register_post_meta( 'speekr_talk', '_speekr_conference_name', [
    'show_in_rest'  => true,
    'single'        => true,
    'type'          => 'string',
    'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
] );
```
The `auth_callback` is mandatory for security — without it, any authenticated user can write to the meta field via the REST API.

**Warning signs:**
- `wp.data.select('core').getEditedEntityRecord('postType', 'speekr_talk', postId).meta` returns `{}`
- Existing meta values don't pre-populate block editor controls on load
- Values saved via the block editor sidebar disappear on next load

**Phase to address:**
Phase 1 (Meta registration audit) — create a meta registration file before touching any block JS.

---

### Pitfall 5: FSE Block Template Registration — Underscore in CPT Slug Breaks Validation

**What goes wrong:**
`register_block_template()` (WordPress 6.7+, formerly `wp_register_block_template()`) validates template names with a regex that rejects underscores. If the Speekr CPT slug contains underscores (e.g., `speekr_talk`), the template name `speekr//single-speekr_talk` will fail registration with a silent error or PHP warning.

**Why it happens:**
The WP_Block_Templates_Registry validation regex is `/^[a-z0-9-]+\/\/[a-z0-9-]+$/` — it accepts only lowercase letters, numbers, and hyphens. This is a known open bug in WordPress core as of early 2026.

**How to avoid:**
- Use a CPT slug without underscores (`speekr-talk` not `speekr_talk`) — check the current slug in `speekr_get_cpt_slug()` in `inc/functions/helpers.php`
- If the CPT slug already has underscores and cannot be changed (backward compat), register the template under a generic name not tied to the CPT slug: `speekr//talk-single` rather than `speekr//single-speekr_talk`
- Register on the `init` hook, not `after_setup_theme` or later hooks

**Warning signs:**
- `register_block_template()` returns `WP_Error` or false
- The custom template does not appear in the Site Editor template list
- PHP debug log shows "Template name must be in the format plugin_uri//template_name" or similar

**Phase to address:**
Phase 3 (FSE template registration) — verify CPT slug naming before attempting any template registration.

---

### Pitfall 6: node-sass is End-of-Life and Fails on Node.js 18+

**What goes wrong:**
`package.json` uses `node-sass@^4.14.1`. node-sass reached end-of-life and its repository was archived on July 24, 2024. It fails to compile on Node.js 18+ because it requires native bindings that are not built for modern Node ABI versions. Running `npm install` or `npm run css` on a modern development machine will either fail during install or fail during compilation.

**Why it happens:**
node-sass was the original Sass compiler for Node. It was superseded by Dart Sass (`sass` package), which is the primary implementation as of 2020. The gap between the old `package.json` and current Node.js LTS versions has grown to the point of breakage.

**How to avoid:**
Replace node-sass with the `sass` package (Dart Sass). Migration is generally straightforward:
```bash
npm uninstall node-sass
npm install --save-dev sass
```
Update `package.json` scripts: replace `node-sass` binary calls with `sass`. Most SCSS syntax is compatible; the main breaking changes are `@import` (use `@use` / `@forward` instead) and some division syntax (`math.div()` instead of `/`). The current SCSS in Speekr likely uses `@import` throughout, which will produce deprecation warnings but still compile — address in a follow-up pass.

Alternatively, migrate fully to `@wordpress/scripts` which handles SCSS via webpack + sass-loader (Dart Sass) automatically via entry points in `block.json`.

**Warning signs:**
- `npm install` fails with "Node Sass does not yet support your current environment" or gyp errors
- `npm run css` produces `Error: Cannot find module 'node-sass'`
- CI/CD pipeline fails on Node 18 or 20

**Phase to address:**
Phase 0 (Build environment setup) — fix before any other development work begins.

---

## Moderate Pitfalls

### Pitfall 7: Block API Version Missing Causes Unexpected iframing

**What goes wrong:**
Without `"apiVersion": 3` in `block.json`, blocks default to API v1/v2. When a mix of API versions exists (some blocks v3, others lower), the editor iframing decision becomes unpredictable. WordPress only iframes the editor when ALL registered blocks are v3+. A single block at v2 prevents iframing but causes console warnings.

**How to avoid:**
Set `"apiVersion": 3` in every `block.json` from the start. This also enables the `useBlockProps()` hook requirement in the `save` function — failing to include `useBlockProps()` in the save return causes block validation failures that invalidate all saved content.

**Phase to address:** Phase 2 (Block scaffolding).

---

### Pitfall 8: Block Deprecation Not Set Up Causes "Block Validation Failed" for All Existing Posts

**What goes wrong:**
When the `save()` function of a block changes (even whitespace differences), WordPress compares the new save output to the stored serialized HTML and finds a mismatch. Every post containing that block shows "Block contains unexpected or invalid content" and the editor refuses to render it without manual user intervention.

**Why it happens:**
Developers update block attributes or markup, regenerate the build, and assume existing content will just update. It won't — block serialized HTML is immutable storage.

**How to avoid:**
- Plan the block attribute schema carefully before first production deployment
- After any attribute or markup change, add a deprecation entry with the old `save()` function and a `migrate()` function
- Never ship a changed `save()` without a deprecation record

Also: the `apiVersion` must be set consistently in both the current `block.json` and in each deprecation entry. Missing `apiVersion` in a deprecation entry causes the deprecation processor to apply the `blocks.getSaveContent.extraProps` filter inconsistently, generating false validation failures.

**Phase to address:** Phase 2 (Block development) — establish deprecation discipline from the first block build.

---

### Pitfall 9: PSR-4 Autoloading Conflicts With WordPress Coding Standards File Naming

**What goes wrong:**
PSR-4 expects class files named exactly as the class (e.g., `Speekr.php`). WordPress Coding Standards (PHPCS ruleset) expects files prefixed with `class-` (e.g., `class-speekr.php`). Running PHPCS with the WordPress ruleset on a PSR-4 directory structure produces errors on every class file, breaking automated review workflows.

**Why it happens:**
Two legitimate standards with incompatible file naming conventions. Both are "correct" in their respective contexts.

**How to avoid:**
Adopt a `phpcs.xml` that excludes the `WordPress.Files.FileName` sniff for namespaced class directories, while keeping the rule active for procedural files:
```xml
<rule ref="WordPress.Files.FileName">
    <exclude-pattern>src/</exclude-pattern>
</rule>
```
Keep the existing procedural files (`inc/functions/`, `inc/common/`) on their current naming convention and only use PSR-4 namespacing for new classes in a separate `src/` directory.

**Phase to address:** Phase 1 (PHP modernization).

---

### Pitfall 10: `@wordpress/scripts` Entry Points Do Not Auto-Discover Non-Block Files

**What goes wrong:**
`@wordpress/scripts` webpack config automatically discovers entry points by scanning for `block.json` files in the `src/` directory. Admin scripts, front-end scripts, and legacy JS files (like `speekr-admin.js`) are not discovered automatically. They must be registered as explicit webpack entry points in a custom `webpack.config.js`, or they will not be compiled.

**Why it happens:**
Developers assume that placing any JS file in `src/` will compile it. Only files referenced in `block.json` script fields, or in an explicit `webpack.config.js` entry, get compiled.

**How to avoid:**
Create a `webpack.config.js` that extends the default config:
```js
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
module.exports = {
    ...defaultConfig,
    entry: {
        ...defaultConfig.entry(),
        'speekr-admin': './src/admin/speekr-admin.js',
        'speekr-frontend': './src/frontend/speekr-frontend.js',
    },
};
```

**Phase to address:** Phase 0 (Build environment setup).

---

### Pitfall 11: jQuery Admin Scripts Conflict With Block Editor React Lifecycle

**What goes wrong:**
jQuery code that runs on `DOMContentLoaded` or in an IIFE (as Speekr's `speekr-admin.js` does) fires before React has rendered the block editor DOM. Selectors for elements inserted by React (like block toolbars or editor-specific panels) return empty jQuery objects.

**Why it happens:**
Classic admin pages have a static DOM. The block editor DOM is rendered asynchronously by React. jQuery code that expects a static DOM at load time will not find React-rendered elements.

**How to avoid:**
- For Gutenberg-editor-specific UI, use React (`@wordpress/components`, `@wordpress/plugins`) instead of jQuery
- Keep jQuery code for settings pages and non-editor admin screens; don't try to integrate it with the editor's React tree
- If jQuery code must interact with editor content, use `wp.domReady()` instead of a bare IIFE, and add event delegation rather than direct selection

**Phase to address:** Phase 2 (Admin JS modernization).

---

### Pitfall 12: Leaflet.js Loaded in Block Editor Context Breaks iframed Editor

**What goes wrong:**
If Leaflet.js is enqueued on the block editor page (for a map preview block, for example), and the editor is iframed, Leaflet's global `L` object is initialized in the parent window context. Leaflet attempts to create DOM elements in the parent document, not the iframe document, causing map containers to not render or throw errors.

**Why it happens:**
Leaflet is not designed to be used across iframe boundaries. Its DOM manipulation assumes a single-document context.

**How to avoid:**
- Enqueue Leaflet only on the frontend, not in the editor: use `enqueue_block_assets` with an `! is_admin()` check, or define a separate `viewScript` in `block.json` for the frontend rendering
- In the block editor, render a static map placeholder or use a server-side render placeholder instead of an interactive Leaflet map
- If a Leaflet preview is needed in the editor, load it via the Interactivity API in a view script, not in the editor bundle

**Phase to address:** Phase 3 (Map/venue block development).

---

## Minor Pitfalls

### Pitfall 13: `wp_register_block_template()` vs `register_block_template()` API Name Change

**What goes wrong:**
Documentation and tutorials written before WordPress 6.7 reference `wp_register_block_template()`. In WordPress 6.7, the function was renamed to `register_block_template()` (without the `wp_` prefix). Using the old name on WordPress 6.7+ will either call a removed function or produce a PHP notice.

**How to avoid:**
Use `register_block_template()` and `unregister_block_template()`. Add a version check if supporting pre-6.7 WordPress:
```php
if ( function_exists( 'register_block_template' ) ) {
    register_block_template( 'speekr//single-talk', [...] );
}
```

**Phase to address:** Phase 3 (FSE templates).

---

### Pitfall 14: WordPress.org Plugin Repo Rejects Bundled npm Build Artifacts Without Source

**What goes wrong:**
WordPress.org reviewers will flag compiled JS/CSS files if no corresponding source files are included or linked. Submitting a plugin with a `build/` directory but no `src/` directory, or minified files with no source map links to a public repo, causes the review to stall.

**How to avoid:**
- Include `src/` in the plugin directory submitted to WordPress.org SVN, or include a `README` pointing to the GitHub repository where source is available
- Do not gitignore `src/` in the SVN submission
- Compiled assets (the `build/` directory) must be included in the SVN tag (they are the deployed files), but source must be accessible

**Phase to address:** Phase 4 (WordPress.org submission preparation).

---

### Pitfall 15: Text Domain Mismatch in Multi-Block Plugins

**What goes wrong:**
When scaffolding multiple blocks with `@wordpress/create-block`, each block gets its own text domain matching the block name. In a multi-block plugin, all blocks must share the plugin's text domain (`speekr`). Mismatched text domains mean translations don't load, and `wp i18n make-pot` generates incorrect POT files.

**How to avoid:**
In every `block.json`, set `"textdomain": "speekr"` explicitly. Do not use the block name as the text domain.

**Phase to address:** Phase 2 (Block scaffolding).

---

## Technical Debt Patterns

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Keep all meta boxes as-is, add blocks alongside | No migration risk initially | Editor is never iframed; can't use FSE; meta duplication | MVP / Phase 1 only as a temporary bridge |
| Use `show_in_rest => true` on meta without `auth_callback` | Less code | Any authenticated user can write arbitrary values via REST API | Never |
| Compile SCSS with node-sass via NVM pin to Node 16 | Avoids migration work | Ties the project to an EOL Node version; blocks CI/CD upgrades | Never for new work |
| Load jQuery in the block editor to handle sidebar UI | Reuse existing code | jQuery DOM mutations race with React render; breaks on iframe | Never |
| Skip block deprecations on first release | Faster shipping | Any future attribute change breaks all existing content | Never once the plugin is installed on production sites |
| Skip `register_post_meta()` and use raw `get_post_meta()` in REST callbacks | Simpler PHP | Meta invisible to block editor's `useEntityProp()`; no type coercion | Never for fields that blocks will read |

---

## Integration Gotchas

| Integration | Common Mistake | Correct Approach |
|-------------|----------------|------------------|
| `save_post` + block editor | Reading `$_POST` in the hook to save meta | Gate with `$_POST['meta-box-loader']` check, or migrate to `register_post_meta()` + REST |
| `wp_enqueue_script` in editor | Using `wp_enqueue_scripts` hook for editor assets | Use `enqueue_block_editor_assets` for editor-only; `enqueue_block_assets` for editor+frontend |
| Leaflet.js in block editor | Enqueuing on `enqueue_block_editor_assets` | Use `viewScript` in `block.json` for frontend-only; serve a static placeholder in editor |
| `register_block_template()` | Calling it on `plugins_loaded` or `after_setup_theme` | Call on `init` hook only |
| Multiple blocks, one plugin | One webpack entry per block, all separate | Use `wp_register_block_types_from_metadata_collection()` (WP 6.7+) or a loop over block directories |
| Composer + WordPress.org SVN | Committing `vendor/` to SVN | Do commit `vendor/` (WordPress.org has no Composer); exclude it from git but include in SVN deployment |

---

## Performance Traps

| Trap | Symptoms | Prevention | When It Breaks |
|------|----------|------------|----------------|
| Enqueuing Leaflet on every page | Slow page load even on non-map pages | Conditional enqueue based on `has_block()` or post meta check | Any page load |
| Loading block editor scripts on non-editor admin pages | Admin pages slow, JS errors | Use `get_current_screen()` guard in enqueue callbacks | Immediately |
| `register_post_meta()` without `single => true` | `useEntityProp` returns array instead of scalar; JS errors | Always set `single => true` for block editor meta | Every save |

---

## Security Mistakes

| Mistake | Risk | Prevention |
|---------|------|------------|
| `register_post_meta()` without `auth_callback` | Any logged-in user can write meta via REST API | Always include `'auth_callback' => function() { return current_user_can('edit_posts'); }` |
| AJAX handlers without nonce verification | CSRF attacks against admin actions | Verify nonce with `check_ajax_referer()` in every AJAX handler (Speekr already does this; maintain the pattern) |
| Unescaped meta output in block `save()` | XSS if meta contains user-supplied HTML | Use `wp_kses_post()` or `esc_html()` when rendering meta in block save output |
| Including compiled vendor JS from npm without license audit | WordPress.org rejection; GPL violation | Run `npm list --prod` and verify all dependencies are MIT/BSD/GPL compatible |

---

## "Looks Done But Isn't" Checklist

- [ ] **Block editor on Talk CPT:** Verify `show_in_rest => true` and `editor` in `supports` — without these, the block editor never loads regardless of block registration
- [ ] **Post meta in sidebar controls:** Verify each meta key is registered via `register_post_meta()` with `show_in_rest => true` and `single => true` — without this, `useEntityProp()` returns nothing
- [ ] **SCSS compilation:** Run `npm install && npm run build` on Node.js 20 — node-sass will fail; confirm Dart Sass migration is complete before marking build setup done
- [ ] **Meta saving backward compat:** Test saving a Talk in both classic editor mode and block editor mode — values should persist in both; regression here is silent
- [ ] **FSE template appears in Site Editor:** Verify by navigating to Site Editor > Templates — the template must appear in the list, not just be registered without error
- [ ] **Block validation on reload:** After saving a Talk with a block, reload the editor — no "Block validation failed" notice should appear
- [ ] **jQuery admin script:** Verify the cover replacement UI in `speekr-admin.js` still functions after switching to block editor — if the meta box is removed, the jQuery selector will fail
- [ ] **Text domain in all block.json files:** Run `wp i18n make-pot . languages/speekr.pot` and verify all strings from all blocks appear in the POT file

---

## Recovery Strategies

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| `save_post` overwrites meta with empty values | MEDIUM | Audit all meta callbacks, add `meta-box-loader` gate, restore data from post revisions if available |
| Block validation failed on all existing posts | HIGH | Add deprecation array to block with original `save()`, deploy, then migrate posts using `wp block migrate` CLI or WP-CLI |
| node-sass build failure on CI | LOW | Pin to Node 16 via `.nvmrc` temporarily; migrate to Dart Sass before next feature work |
| FSE template not appearing | LOW | Check return value of `register_block_template()`, verify hook timing (`init`), verify WP 6.7+ |
| Meta invisible in block editor | LOW | Add `register_post_meta()` calls, flush REST cache, verify with REST API browser |

---

## Pitfall-to-Phase Mapping

| Pitfall | Prevention Phase | Verification |
|---------|------------------|--------------|
| CPT missing `show_in_rest` | Phase 1: CPT modernization | Block editor loads on Talk CPT; `/wp-json/wp/v2/talks` returns 200 |
| `save_post` double-fire / empty meta | Phase 1: Meta migration audit | Save in both classic + block editor; verify meta persists |
| Meta not in REST API (`register_post_meta`) | Phase 1: Meta registration | `useEntityProp()` returns expected values in browser console |
| node-sass EOL build failure | Phase 0: Build tooling | `npm run build` passes on Node.js 20 |
| `@wordpress/scripts` non-block entry points | Phase 0: Build tooling | Admin and frontend scripts compile to `build/` |
| Block API version + iframe isolation | Phase 2: Block scaffolding | Editor iframing behavior is intentional and documented |
| Block deprecation / save mismatch | Phase 2: Block development | Reload editor after save; no validation errors |
| PSR-4 vs WPCS file naming | Phase 1: PHP modernization | PHPCS passes with custom ruleset configuration |
| jQuery + React lifecycle conflict | Phase 2: Admin JS modernization | Admin JS functions correctly alongside block editor |
| Leaflet in iframe editor | Phase 3: Map/venue block | No Leaflet errors in block editor; map renders on frontend |
| FSE template underscore CPT bug | Phase 3: FSE templates | Template appears in Site Editor template list |
| WordPress.org SVN source requirement | Phase 4: Submission prep | Source files accessible; review does not request clarification |
| Text domain in multi-block | Phase 2: Block scaffolding | `make-pot` captures all block strings under `speekr` domain |

---

## Sources

- [Meta Boxes — Block Editor Handbook](https://developer.wordpress.org/block-editor/how-to-guides/metabox/) — HIGH confidence; official docs on REST API requirement, `show_in_rest`, `custom-fields` support, `save_post` hook behavior
- [Enqueueing Assets in the Editor — Block Editor Handbook](https://developer.wordpress.org/block-editor/how-to-guides/enqueueing-assets-in-the-editor/) — HIGH confidence; official docs on iframe isolation, hook differences, WordPress 6.3+ behavior
- [Registering Block Templates via Plugins in WordPress 6.7 — WordPress Developer Blog](https://developer.wordpress.org/news/2024/08/registering-block-templates-via-plugins-in-wordpress-6.7/) — HIGH confidence; official announcement of `register_block_template()`, naming requirements
- [Deprecation — Block Editor Handbook](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-deprecation/) — HIGH confidence; official docs on save function validation and migration
- [Block Metadata (block.json) — Block Editor Handbook](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/) — HIGH confidence; entry point detection, text domain, apiVersion requirements
- [Detailed Plugin Guidelines — WordPress.org Plugin Handbook](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) — HIGH confidence; official GPL compliance, obfuscation, source requirements
- [Node Sass is End-of-Life — Sass Blog](https://sass-lang.com/blog/node-sass-is-end-of-life/) — HIGH confidence; official deprecation notice; repository archived July 2024
- [FSE template name underscore CPT bug — WebberZone](https://webberzone.com/custom-block-theme-templates-wordpress-plugins/) — MEDIUM confidence; community-reported regex issue with underscore CPT slugs
- [save_post hook firing twice — WordPress GitHub #20550](https://github.com/WordPress/gutenberg/issues/20550) — MEDIUM confidence; confirmed in official GitHub issue tracker
- [Block API version missing deprecation failure — WordPress GitHub #70123](https://github.com/WordPress/gutenberg/issues/70123) — MEDIUM confidence; official GitHub issue confirming apiVersion behavior in deprecation

---
*Pitfalls research for: WordPress plugin modernization (Gutenberg blocks, FSE, PHP PSR-4)*
*Researched: 2026-02-28*
