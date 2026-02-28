# Phase 1: Build Foundation - Research

**Researched:** 2026-03-01
**Domain:** @wordpress/scripts webpack build toolchain migration (node-sass/gulp → wp-scripts)
**Confidence:** HIGH

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

**Phase Boundary**
Migrate the build toolchain from node-sass/gulp to @wordpress/scripts so `npm run build` works on Node 18+ and produces compiled block assets plus legacy admin/frontend scripts in `build/`. Enqueue paths in PHP updated to point to the new `build/` locations. No other PHP changes in this phase. No blocks built yet — just the pipeline that will compile them.

**Block source directory**
- Block source files live in `src/blocks/` (e.g. `src/blocks/talk-meta/`)
- Each block directory contains: `index.js`, `edit.js`, `render.php`, `style.scss`
- No separate `editor.scss` — one `style.scss` shared between editor and frontend
- `block.json` placement: Claude's discretion (standard @wordpress/scripts convention)

**Legacy assets**
- Legacy admin SCSS and JS move into the webpack pipeline (unified build, not two separate pipelines)
- All output goes to `build/` — enqueue paths in PHP updated in Phase 1 to match
- `build/` organized as: `build/blocks/`, `build/admin/`, `build/frontend/`
- Minification handled by webpack production mode; no separate `.min.css` files needed
- Old `assets/css/src/` SCSS source files and old compiled CSS/JS cleaned up after `build/` is confirmed working

**npm scripts API**
- `npm run build` — production build (minified, no source maps)
- `npm run build:dev` — development build (readable, with source maps)
- `npm run start` — watch mode covering all source files (src/blocks/ + legacy SCSS/JS)
- `npm run lint` — single command running all linters (JS + CSS via @wordpress/scripts)
- `npm run clean` — wipe `build/` before rebuilding
- Old scripts (`compile:css`, `minify:css`, `watch`) removed

**Build output structure**
- `build/blocks/{block-name}/` — compiled block assets per block
- `build/admin/` — compiled admin JS and CSS
- `build/frontend/` — compiled frontend JS and CSS
- `build/` committed to git (WordPress plugin convention — installs without build step)
- Source maps gitignored (`.map` files excluded from git to keep repo size down)

### Claude's Discretion
- `block.json` location (src alongside JS or generated): Claude picks standard @wordpress/scripts approach
- Exact webpack.config.js structure for multiple entry points
- How to handle the existing font assets in `assets/fonts/` (likely leave in place, reference from CSS)

### Deferred Ideas (OUT OF SCOPE)
None — discussion stayed within phase scope.
</user_constraints>

---

## Summary

The project currently uses `node-sass@4.x` and `gulp@4.x`, which do not run on Node 18+ (node-sass relies on native bindings that fail on modern Node). The goal is to replace this entire pipeline with `@wordpress/scripts@31.5.0`, which wraps webpack 5, babel, postcss, sass-loader, and stylelint/eslint into a zero-config tool maintained by the WordPress core team.

The migration requires a custom `webpack.config.js` because the default @wordpress/scripts config auto-detects blocks in `src/` but knows nothing about the legacy admin and frontend SCSS/JS at `src/admin/` and `src/frontend/`. The config must extend the default — calling `defaultConfig.entry()` (note: `entry` is a function in current versions) to pick up block entries, then spread in the legacy entries. Block `block.json` files belong in `src/blocks/{block-name}/` and are automatically copied to `build/blocks/{block-name}/` by the build process.

One critical cross-cutting concern: the frontend SCSS currently references fonts at `../fonts/front/speekr.*` — a path relative to `assets/css/src/`. When CSS is compiled to `build/frontend/`, that relative URL breaks. The font files should stay in `assets/fonts/` (per the user's decision) and the SCSS paths must be updated to use a plugin-root-relative URL (via `SPEEKR_PLUGIN_URL`) or webpack's `resolve-url-loader` — the simplest approach is updating the `@font-face` `url()` declarations before compilation to be absolute plugin URLs, injected via a SCSS variable or hardcoded relative path from the new output location.

**Primary recommendation:** Install `@wordpress/scripts@^31.5.0`, create `webpack.config.js` extending the default config with legacy entry points, move source files to `src/admin/` and `src/frontend/`, update SCSS font paths, and update four PHP enqueue paths to point to `build/`.

---

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| @wordpress/scripts | ^31.5.0 | Unified build tool: webpack 5 + babel + postcss + sass + eslint + stylelint | Official WordPress-maintained package; zero-config for blocks; handles JS, SCSS, CSS extraction, source maps |

### What @wordpress/scripts bundles (no separate installs needed)

| Tool | Version (bundled) | Purpose |
|------|-------------------|---------|
| webpack | 5.x | Module bundler |
| sass-loader + sass | latest | SCSS compilation |
| postcss-loader + autoprefixer | latest | CSS post-processing |
| mini-css-extract-plugin | latest | Extracts CSS to separate files |
| @wordpress/dependency-extraction-webpack-plugin | latest | Generates `*.asset.php` files listing WP script dependencies |
| eslint (wp config) | latest | JS linting |
| stylelint (wp config) | latest | CSS/SCSS linting |

### Removed Dependencies

| Remove | Why |
|--------|-----|
| node-sass@4.x | Uses native bindings, broken on Node 18+; replaced by Dart Sass (bundled in wp-scripts) |
| gulp@4.x | No longer needed; webpack handles watch/build |
| csso-cli | Minification now handled by webpack production mode |
| @wordpress/scripts@^12.1.0 | Upgrade to ^31.5.0 — major version jumped; Node >=18.12.0 enforced from v28.0.0 |

**Installation:**
```bash
npm install --save-dev @wordpress/scripts@^31.5.0
npm uninstall node-sass gulp csso-cli
```

---

## Architecture Patterns

### Recommended Project Structure

```
speekr/                          # plugin root
├── src/
│   ├── blocks/
│   │   └── talk-meta/           # one dir per block (future phases)
│   │       ├── block.json       # src alongside JS — auto-detected
│   │       ├── index.js
│   │       ├── edit.js
│   │       ├── render.php
│   │       └── style.scss
│   ├── admin/
│   │   ├── index.js             # entry: admin JS (was assets/js/speekr-admin.js)
│   │   └── style.scss           # entry: admin CSS (was assets/css/src/speekr-admin.scss)
│   └── frontend/
│       └── style.scss           # entry: frontend CSS (was assets/css/src/speekr.scss)
├── assets/
│   └── fonts/                   # stays in place — referenced via plugin URL in CSS
│       ├── admin/speekr.*
│       └── front/speekr.*
├── build/                       # committed to git
│   ├── blocks/
│   │   └── talk-meta/
│   │       ├── block.json       # auto-copied from src
│   │       ├── index.js
│   │       ├── index.asset.php  # auto-generated dependency manifest
│   │       └── style-index.css
│   ├── admin/
│   │   ├── index.js
│   │   ├── index.asset.php
│   │   └── index.css
│   └── frontend/
│       └── style.css            # CSS-only entry; index.js empty (see pitfall below)
├── webpack.config.js            # extends @wordpress/scripts default
└── package.json
```

### Pattern 1: Extending Default Config with Legacy Entry Points

The default `@wordpress/scripts` webpack config auto-detects `block.json` files in `src/` and sub-directories. `defaultConfig.entry` is a **function** that returns the entries object — it must be called with `()`.

```javascript
// webpack.config.js
// Source: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/
// Verified via: https://samhermes.com/posts/customize-default-wp-scripts-config/
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
  ...defaultConfig,
  entry: {
    // Auto-detect all block.json entry points in src/blocks/
    ...defaultConfig.entry(),
    // Legacy admin: JS + SCSS in one entry (CSS extracted by MiniCSSExtractPlugin)
    'admin/index': [
      path.resolve( __dirname, 'src/admin/index.js' ),
      path.resolve( __dirname, 'src/admin/style.scss' ),
    ],
    // Legacy frontend: SCSS only (produces build/frontend/style.css + an empty JS — see pitfall)
    'frontend/style': path.resolve( __dirname, 'src/frontend/style.scss' ),
  },
};
```

**Important:** `defaultConfig.entry` is a function (not an object) in @wordpress/scripts v27+. Always call it with `()`.

### Pattern 2: npm scripts in package.json

```json
{
  "scripts": {
    "build":     "wp-scripts build",
    "build:dev": "wp-scripts build --mode=development",
    "start":     "wp-scripts start",
    "lint":      "wp-scripts lint-js && wp-scripts lint-style",
    "clean":     "rm -rf build"
  }
}
```

**Source maps:** The `WP_DEVTOOL` env variable controls source map type (available since v25.4.0). For `build:dev`, source maps are enabled by default. Production `build` disables them. The existing `.gitignore` already excludes `*.map` files — extend it to cover `build/**/*.map`.

### Pattern 3: block.json in src alongside JS (Claude's discretion recommendation)

Place `block.json` in `src/blocks/{block-name}/` alongside `index.js`. The build process automatically detects it, uses `file:` references to resolve JS/CSS entry points, and copies `block.json` to `build/blocks/{block-name}/`. PHP registration points to the build directory.

```json
// src/blocks/talk-meta/block.json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "speekr/talk-meta",
  "version": "1.0.0",
  "title": "Talk Meta",
  "editorScript": "file:./index.js",
  "style": "file:./style-index.css",
  "render": "file:./render.php"
}
```

```php
// PHP registration (future phase — documented for context)
register_block_type( __DIR__ . '/build/blocks/talk-meta' );
```

### Pattern 4: Updating PHP Enqueue Paths

Replace all four `assets/css/` and `assets/js/` references with `build/` paths. The `SCRIPT_DEBUG` pattern is simplified — single CSS output, no `.min.css` distinction.

```php
// inc/admin/enqueues.php — BEFORE
wp_enqueue_style( 'speekr-main', SPEEKR_PLUGIN_URL . 'assets/css/speekr-admin.css', ... );
wp_enqueue_script( 'speekr-main', SPEEKR_PLUGIN_URL . 'assets/js/speekr-admin.js', ... );

// AFTER
wp_enqueue_style( 'speekr-main', SPEEKR_PLUGIN_URL . 'build/admin/index.css', array(), SPEEKR_VERSION, 'all' );
wp_enqueue_script( 'speekr-main', SPEEKR_PLUGIN_URL . 'build/admin/index.js', array( 'jquery' ), SPEEKR_VERSION, true );
```

```php
// inc/front/enqueues.php — BEFORE
$debug = ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG === true ) ? '' : '.min';
wp_enqueue_style( 'speekr-main', SPEEKR_PLUGIN_URL . 'assets/css/speekr' . $debug . '.css', ... );

// AFTER (no debug toggle — single output file)
wp_enqueue_style( 'speekr-main', SPEEKR_PLUGIN_URL . 'build/frontend/style.css', array(), SPEEKR_VERSION, 'all' );
```

```php
// inc/admin/notices.php — BEFORE (already broken — file doesn't exist)
wp_enqueue_style( 'speekr-main', SPEEKR_PLUGIN_URL . 'assets/css/admin.min.css', ... );

// AFTER (also fix the pre-existing broken path)
wp_enqueue_style( 'speekr-main', SPEEKR_PLUGIN_URL . 'build/admin/index.css', array(), SPEEKR_VERSION, 'all' );
```

### Anti-Patterns to Avoid

- **Calling `wp-scripts build` directly with arguments for non-block entries:** Works but is awkward for multiple entries; `webpack.config.js` is cleaner and maintainable.
- **Replacing `defaultConfig` entirely:** Loses dependency-extraction-webpack-plugin, MiniCSSExtractPlugin configuration, and WordPress-specific loaders. Always spread `...defaultConfig`.
- **Spreading `...defaultConfig.entry` without calling it:** In v27+, this spreads a function reference, not its return value. Use `...defaultConfig.entry()`.
- **Importing `getWebpackEntryPoints` from utils:** Fragile internal API. Use `defaultConfig.entry()` which calls it internally — this is the documented public approach.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| SCSS compilation | Custom sass CLI script | Sass-loader bundled in @wordpress/scripts | Version drift, Node compatibility, PostCSS integration complexity |
| JS minification | Custom terser/uglify step | webpack production mode (`wp-scripts build`) | Already configured correctly for WordPress externals |
| CSS minification | csso-cli (removing) | CssMinimizerWebpackPlugin (bundled) | Integrated into the webpack pipeline |
| JS/CSS linting | Custom eslint/stylelint configs | `wp-scripts lint-js` / `wp-scripts lint-style` | WordPress-recommended configs maintained by core team |
| Dependency manifests | Manual `*.asset.php` | @wordpress/dependency-extraction-webpack-plugin (auto) | Critical for correct WordPress script versioning; hand-rolling is error-prone |
| Watch mode | gulp.watch / nodemon | `wp-scripts start` | HMR-compatible, handles block hot reload |

**Key insight:** @wordpress/scripts bundles and pre-configures every tool in the chain. The only reason to touch webpack config is to add non-block entry points — not to configure loaders, plugins, or optimization.

---

## Common Pitfalls

### Pitfall 1: Font URL Path Breaks After Build

**What goes wrong:** `src/frontend/style.scss` contains `@font-face` with `url('../fonts/front/speekr.eot')` — a path relative to the source file at `assets/css/src/`. When compiled to `build/frontend/style.css`, the relative `../fonts/front/` path resolves to `build/fonts/front/` which doesn't exist. Browser loads CSS but no icons render.

**Why it happens:** Webpack's css-loader/sass-loader resolves and may rewrite `url()` paths relative to the **output** file location, not the source file.

**How to avoid:** Before moving source files, update all `url()` font paths in SCSS to be relative to `build/frontend/` (i.e., `../../assets/fonts/front/speekr.eot`). Alternatively, use WordPress's `SPEEKR_PLUGIN_URL` — but that requires PHP interpolation or a SCSS variable set at build time. The simplest, self-contained approach: use the path relative to the final `build/frontend/` output location.

**Warning signs:** Admin font icon (`dashicons-speekr`) disappears, or frontend icon font renders as boxes after first production build.

**Note:** The admin font is injected via inline `<style>` in PHP (`speekr_crappy_admin_styles()`) using `SPEEKR_PLUGIN_URL` — this is not affected by the build migration.

### Pitfall 2: CSS-only Entry Produces an Empty JS File

**What goes wrong:** When `frontend/style` entry point is SCSS-only, webpack still emits `build/frontend/style.js` (empty or nearly empty). This unused file gets enqueued or clutters the build directory.

**Why it happens:** Webpack always emits a JS file per entry even if the entry only produces CSS. This is fundamental webpack behavior.

**How to avoid:** Two options:
1. Accept the empty JS file (it's harmless, just extra bytes). Don't enqueue it via `wp_enqueue_script`.
2. Install `webpack-remove-empty-scripts` plugin and add it to `webpack.config.js` plugins array. This is the modern replacement for `FixStyleOnlyEntriesPlugin`.

**Warning signs:** `build/frontend/style.js` exists and is either empty or contains only webpack runtime boilerplate.

### Pitfall 3: `defaultConfig.entry` Is a Function, Not an Object

**What goes wrong:** `{ ...defaultConfig.entry, 'admin/index': ... }` spreads the function reference itself, not the block entry points. Block entries are silently dropped. Build succeeds but produces no block assets.

**Why it happens:** In @wordpress/scripts v27+, the entry field was changed from a static object to a function (`getWebpackEntryPoints('script')`) so blocks are discovered lazily.

**How to avoid:** Always use `...defaultConfig.entry()` — call it with `()`. Verified correct in the wild: https://samhermes.com/posts/customize-default-wp-scripts-config/

**Warning signs:** `build/` directory lacks `blocks/` subdirectory after running `npm run build`.

### Pitfall 4: `admin.min.css` Reference in notices.php Is Already Broken

**What goes wrong:** `inc/admin/notices.php:32` enqueues `assets/css/admin.min.css` — this file does not exist anywhere in the project. The welcome notice currently loads no styles.

**Why it happens:** Pre-existing bug, unrelated to this migration.

**How to avoid:** The Phase 1 PHP enqueue update should fix this as a side effect, pointing it to `build/admin/index.css` along with the other admin enqueues.

**Warning signs:** If you audit enqueues and notice a 404 on `admin.min.css` — that's expected and gets fixed in this phase.

### Pitfall 5: node_modules Conflicts Between Old and New Packages

**What goes wrong:** `node-sass@4.x` uses native bindings (node-gyp). On Node 22, installing it alongside the new packages may fail or emit native binding rebuild errors.

**Why it happens:** node-sass's native bindings target specific Node versions and are not pre-compiled for Node 18+.

**How to avoid:** Uninstall node-sass and gulp first (`npm uninstall node-sass gulp csso-cli`), then delete `node_modules/`, then install fresh (`npm install`).

**Warning signs:** `npm install` logs errors about `node-gyp rebuild` or `binding.node` not found.

### Pitfall 6: `build/` in .gitignore Must Not Be Added

**What goes wrong:** Common instinct is to add `build/` to `.gitignore`. The user decision is to commit `build/` to git (WordPress plugin distribution convention). If `build/` ends up gitignored, production installs without a build step break.

**Why it happens:** Most modern web projects gitignore build output. WordPress plugin distribution is different — many sites install from source without running npm.

**How to avoid:** Do NOT add `build/` to `.gitignore`. Do add `build/**/*.map` to exclude source maps only. The existing `.gitignore` already has `assets/css/*.map` — extend it.

---

## Code Examples

### webpack.config.js — Complete Pattern

```javascript
// webpack.config.js
// Source: https://samhermes.com/posts/customize-default-wp-scripts-config/
// Verified: defaultConfig.entry() pattern for @wordpress/scripts v27+
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
  ...defaultConfig,
  entry: {
    // Block entry points: auto-detected from src/blocks/*/block.json
    ...defaultConfig.entry(),
    // Legacy admin: JS (speekr-admin.js) + SCSS compiled to build/admin/index.{js,css}
    'admin/index': [
      path.resolve( __dirname, 'src/admin/index.js' ),
      path.resolve( __dirname, 'src/admin/style.scss' ),
    ],
    // Legacy frontend: SCSS only → build/frontend/style.css
    // Note: also emits build/frontend/style.js (empty) — safe to ignore
    'frontend/style': path.resolve( __dirname, 'src/frontend/style.scss' ),
  },
};
```

### package.json scripts

```json
{
  "scripts": {
    "build":     "wp-scripts build",
    "build:dev": "wp-scripts build --mode=development",
    "start":     "wp-scripts start",
    "lint":      "wp-scripts lint-js && wp-scripts lint-style",
    "clean":     "rm -rf build"
  }
}
```

### Updated .gitignore additions

```
# Already present
node_modules/*
assets/css/*.map

# Add for new build pipeline
build/**/*.map
```

### Font path correction in SCSS (before moving to src/)

The existing path in `assets/css/src/speekr.scss`:
```scss
// BEFORE (relative to assets/css/src/)
@font-face {
  font-family: 'speekr';
  src: url('../fonts/front/speekr.eot?pguas6');
}
```

```scss
// AFTER (relative to build/frontend/ output location)
@font-face {
  font-family: 'speekr';
  src: url('../../assets/fonts/front/speekr.eot?pguas6');
}
```

Similarly for admin SCSS if any `@font-face` is added there. The admin font icon is currently injected via inline PHP `<style>` — not compiled CSS — so it's unaffected.

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| node-sass (native bindings) | Dart Sass (pure JS, bundled in wp-scripts) | ~2020 | No native build step; works on any Node version |
| gulp task runner | webpack via @wordpress/scripts | ~2019 | Single unified pipeline |
| Separate `.min.css` files with SCRIPT_DEBUG toggle | Single webpack output, minified in production mode | n/a | Simpler enqueue logic |
| csso-cli minification step | CssMinimizerWebpackPlugin (bundled) | n/a | Integrated, zero config |
| Manual asset versioning | `*.asset.php` with content hash | @wordpress/scripts v12+ | Automatic cache busting |

**Deprecated/outdated:**
- `node-sass`: No longer maintained, incompatible with Node 18+. Dart Sass (maintained by Google) is the standard.
- `gulp`: Largely superseded by webpack for WordPress plugin development. Gutenberg itself uses webpack.
- `@wordpress/scripts@^12.1.0`: Installed in this project; was missing Node 18+ enforcement. `^31.5.0` is current (Feb 2026). Multiple major versions jumped: key breaking change is `entry` field changed to a function in v27+.
- `FixStyleOnlyEntriesPlugin`: Superseded by `webpack-remove-empty-scripts`.

---

## Existing Codebase — What Changes

| File | Action | Details |
|------|--------|---------|
| `assets/js/speekr-admin.js` | Move to `src/admin/index.js` | Content unchanged; uses jQuery IIFE pattern |
| `assets/js/speekr.js` | Move to `src/frontend/index.js` (or omit as entry — file is near-empty) | 3-line empty IIFE |
| `assets/css/src/speekr-admin.scss` | Move to `src/admin/style.scss` | Update font URL paths if any are added |
| `assets/css/src/speekr.scss` | Move to `src/frontend/style.scss` | Update `@font-face url()` paths (see Pitfall 1) |
| `assets/css/speekr-admin.css` | Delete (post-verification) | Compiled output now in `build/admin/index.css` |
| `assets/css/speekr-admin.min.css` | Delete (post-verification) | No longer generated |
| `assets/css/speekr.css` | Delete (post-verification) | Compiled output now in `build/frontend/style.css` |
| `assets/css/speekr.min.css` | Delete (post-verification) | No longer generated |
| `inc/admin/enqueues.php` | Update 2 paths | `assets/css/speekr-admin.css` → `build/admin/index.css`; `assets/js/speekr-admin.js` → `build/admin/index.js` |
| `inc/admin/notices.php` | Update 1 path (also fixes existing bug) | `assets/css/admin.min.css` → `build/admin/index.css` |
| `inc/front/enqueues.php` | Update 1 path + remove SCRIPT_DEBUG toggle | Single `build/frontend/style.css` |
| `package.json` | Replace devDependencies + scripts | Remove node-sass/gulp/csso-cli; add @wordpress/scripts@^31.5.0; replace scripts |
| `.gitignore` | Add `build/**/*.map` | Source maps excluded; `build/` itself committed |
| `webpack.config.js` | New file | Extends default config with legacy entry points |

---

## Open Questions

1. **Does `speekr.js` (frontend JS) need to be an entry point?**
   - What we know: `assets/js/speekr.js` is a 3-line empty IIFE — no actual code, never enqueued from `inc/front/enqueues.php`.
   - What's unclear: Whether this was intentional (planned feature) or abandoned.
   - Recommendation: Do not create a `src/frontend/index.js` entry point in this phase. If frontend JS is needed later, add it then. Keep things minimal.

2. **Should `webpack-remove-empty-scripts` be installed to suppress the empty `frontend/style.js`?**
   - What we know: An empty JS file will be emitted for the `frontend/style` CSS-only entry. It is harmless if not enqueued.
   - What's unclear: Whether the presence of this file in `build/` would confuse future developers or CI.
   - Recommendation: Skip the plugin in Phase 1 (keep dependencies minimal). Document that `build/frontend/style.js` is expected and empty.

3. **Font path strategy: relative path vs. SCSS variable vs. webpack resolve-url-loader?**
   - What we know: The font `@font-face` in `speekr.scss` uses `../fonts/front/` relative to source. Output moves to `build/frontend/`.
   - What's unclear: Whether sass-loader with `resolve-url-loader` can handle this automatically (it typically requires `sourceMap: true` in sass-loader options).
   - Recommendation: Use the simple approach — hardcode the path relative to build output (`../../assets/fonts/front/`). This is portable, zero-config, and easy to verify.

---

## Sources

### Primary (HIGH confidence)
- npm registry: `@wordpress/scripts` latest → `31.5.0`, engines: `node >=18.12.0` — fetched live 2026-03-01
- https://raw.githubusercontent.com/WordPress/gutenberg/trunk/packages/scripts/CHANGELOG.md — version history, Node requirements, entry function change
- https://raw.githubusercontent.com/WordPress/gutenberg/trunk/packages/scripts/config/webpack.config.js — entry uses `getWebpackEntryPoints('script')` which returns a function
- https://raw.githubusercontent.com/WordPress/gutenberg/trunk/packages/scripts/utils/config.js — `getWebpackEntryPoints` is a higher-order function; returns a function that returns an object
- https://developer.wordpress.org/news/2024/09/how-to-build-a-multi-block-plugin/ — official multi-block structure, `build/blocks/{block-name}/` output, `register_block_type(__DIR__ . '/build/blocks/...')` pattern

### Secondary (MEDIUM confidence)
- https://samhermes.com/posts/customize-default-wp-scripts-config/ — `...defaultConfig.entry()` pattern with double-parenthesis verified against changelog
- https://www.nomar.dev/migrating-to-the-wp-scripts-analysing-two-cases/ — MiniCSSExtractPlugin CSS filename customization pattern for migrations
- https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/ — build/start flags, source-path option, block.json auto-detection behavior

### Tertiary (LOW confidence)
- https://github.com/fqborges/webpack-fix-style-only-entries — `webpack-remove-empty-scripts` as modern replacement for empty JS suppression (superseded by newer package name, needs version verification)

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — npm registry version fetched live; CHANGELOG verified via GitHub
- Architecture: HIGH — multi-block structure from official WordPress Developer Blog (2024)
- Entry function pattern: HIGH — verified against actual source file at `packages/scripts/utils/config.js`
- Pitfalls: HIGH (Pitfall 1 font path) — confirmed by inspecting actual SCSS source; MEDIUM (Pitfall 2 empty JS) — documented behavior, multiple sources agree
- PHP enqueue paths: HIGH — read actual source files in the project

**Research date:** 2026-03-01
**Valid until:** 2026-04-01 (stable package; @wordpress/scripts releases frequently but conventions are stable)
