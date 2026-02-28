# Stack Research

**Domain:** WordPress plugin — Gutenberg blocks, FSE templates, PHP PSR-4, modern build toolchain
**Project:** Speekr modernization
**Researched:** 2026-02-28
**Confidence:** HIGH (core tooling verified against official docs and Gutenberg source)

---

## WordPress Version Floor

**Minimum: WordPress 6.8** — Required for `wp_register_block_types_from_metadata_collection()`, the standard
multi-block registration API (introduced WP 6.8.0). This is the cut-off that unlocks zero-boilerplate block
registration and the blocks-manifest build step. Current stable is **6.9.1** (February 3, 2026).

**Why not 6.7?** WP 6.7 introduced `wp_register_block_metadata_collection()` (the predecessor), but 6.8 simplified
it further to a single function call. The extra three months of 6.7 compatibility is not worth maintaining a
different code path.

**FSE / block templates:** `register_block_template()` was introduced in WordPress 6.7. No meaningful reason to
require anything lower.

**PHP floor: 8.1** — WordPress 6.8 fully supports PHP 8.3. WordPress.org recommends 8.3+. PHP 8.1 is the
reasonable floor for a new plugin: enum support, fibers, intersection types, readonly properties. PHP 7.4 is still
technically supported by WP core but is EOL and should not be targeted.

Source: [PHP 8.5 support in WordPress 6.9](https://make.wordpress.org/core/2025/11/21/php-8-5-support-in-wordpress-6-9/)
— [WordPress Requirements](https://wordpress.org/about/requirements/)

---

## Recommended Stack

### Core Technologies

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|-----------------|
| WordPress | >= 6.8 | Platform | `wp_register_block_types_from_metadata_collection()` available; FSE templates via `register_block_template()` stable since 6.7 |
| PHP | >= 8.1 | Server language | Enums, readonly props, named arguments; avoids writing PHP 7.4 patterns that have since been superseded |
| @wordpress/scripts | 31.5.0 | Build toolchain | Webpack 5.97+, React 18, sass-loader included by default, zero-config SCSS, PostCSS, block manifest generation; officially replaces node-sass |
| React | 18.x | Block editor UI | Required peer dependency of @wordpress/scripts 31.x; React 19 not yet supported by @wordpress/scripts |
| Composer | 2.x | PHP dependency management + autoloader | Only practical way to get PSR-4 autoloading in a WordPress plugin without writing a custom autoloader |

Sources: [Gutenberg packages/scripts package.json](https://github.com/WordPress/gutenberg/blob/trunk/packages/scripts/package.json)
— [Gutenberg webpack config confirms sass-loader](https://github.com/WordPress/gutenberg/blob/trunk/packages/scripts/config/webpack.config.js)

### Block Registration (PHP — WP 6.8+)

| Function | Introduced | Purpose |
|----------|-----------|---------|
| `wp_register_block_types_from_metadata_collection()` | WP 6.8.0 | One-call registration of all blocks using generated manifest. Pass `$path` and `$manifest` — done. |
| `register_block_template()` | WP 6.7.0 | Register FSE block templates from a plugin, with `plugin-slug//template-name` naming convention |
| `register_block_type_from_metadata()` | WP 5.5.0 | Fallback for single-block registration if manifest approach is not used |

Use `wp_register_block_types_from_metadata_collection()` exclusively. Do not loop through blocks manually.

Source: [More efficient block type registration in 6.8](https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/)
— [WP function reference](https://developer.wordpress.org/reference/functions/wp_register_block_types_from_metadata_collection/)

### Supporting Libraries

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Leaflet.js | **1.9.4** (stable) | Interactive conference world map | Frontend map rendering. Do NOT use 2.0.0-alpha — it is pre-release, removes the global `L`, breaks all existing plugin patterns |
| react-leaflet | **4.x** (not 5.x) | React bindings for Leaflet | Gutenberg map block edit component. react-leaflet v5 requires **React 19**, which @wordpress/scripts 31.x does not support. Pin to v4.x |
| @wordpress/components | bundled via @wordpress/scripts | Block editor UI primitives | All block inspector controls, panels, buttons — use instead of custom UI |
| @wordpress/data | bundled | Block editor state management | Access editor store (post meta, block attributes) — no Redux boilerplate required |
| @wordpress/block-editor | bundled | Block editor hooks + HOCs | `useBlockProps`, `InspectorControls`, `InnerBlocks` |
| @wordpress/i18n | bundled | Internationalisation in JS | `__()`, `_n()` — required for any user-facing strings in blocks |
| @wordpress/api-fetch | bundled | REST API calls from blocks | Fetching CPT data in edit context (speaker profiles, talks, conferences) |

**Leaflet version rationale:** Leaflet 2.0.0-alpha was released August 2025. The targeted stable release was
November 2025 but has not shipped as stable as of February 2026. react-leaflet v4 targets Leaflet 1.8+.
Use 1.9.4 (latest stable), which is fully supported.

Source: [Leaflet 2.0 alpha announcement](https://leafletjs.com/2025/05/18/leaflet-2.0.0-alpha.html)
— [react-leaflet v5 release notes require React 19](https://github.com/PaulLeCam/react-leaflet/releases)

### Development Tools

| Tool | Version | Purpose | Notes |
|------|---------|---------|-------|
| @wordpress/scripts | 31.5.0 | Build, watch, lint | Replaces node-sass, gulp, custom webpack. Run `wp-scripts build` and `wp-scripts start` |
| @wordpress/env | 10.0.0 | Local Docker WordPress environment | Peer dep of @wordpress/scripts; already available in dev env via Local |
| wp-scripts build-blocks-manifest | (built into @wordpress/scripts 31.x) | Generate blocks-manifest.php | Run as post-build step; feeds `wp_register_block_types_from_metadata_collection()` |
| Composer | 2.x | PSR-4 autoloader generation | Run `composer dump-autoload -o` for production, `composer install` for dev |
| sass | 1.x (Dart Sass) | SCSS compilation | Bundled inside @wordpress/scripts via sass-loader. No separate installation needed. |

---

## PHP Architecture: PSR-4 with Composer

**Pattern:** Composer-managed PSR-4 autoloading with a top-level vendor namespace.

```json
// composer.json (plugin root)
{
  "name": "speekr/speekr-plugin",
  "autoload": {
    "psr-4": {
      "Speekr\\": "src/"
    }
  },
  "require": {
    "php": ">=8.1"
  }
}
```

**Directory:** `src/` holds all namespaced PHP classes. The existing `inc/classes/Speekr.php` moves into
`src/Core/Plugin.php` under `namespace Speekr\Core;`.

**Bootstrap:** `speekr.php` includes Composer autoloader before any plugin code:

```php
if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}
```

**Namespace format:** `Speekr\Core`, `Speekr\Blocks`, `Speekr\Admin`, `Speekr\PostTypes`, `Speekr\Templates`.
Use PascalCase class names (PSR standard), not WordPress's `class-name-of-class.php` filename convention.
This is intentional: PSR-4 and WordPress filename conventions are incompatible. Use PSR-4 when using Composer.

Source: [WordPress Developer Blog — namespaces and coding standards](https://developer.wordpress.org/news/2025/09/implementing-namespaces-and-coding-standards-in-wordpress-plugin-development/)
— [PSR-4 specification](https://www.php-fig.org/psr/psr-4/)

---

## Build Pipeline: @wordpress/scripts

**Replace node-sass and gulp entirely.** @wordpress/scripts wraps Webpack 5 with sass-loader, PostCSS,
MiniCSSExtractPlugin, Babel, ESLint, and Stylelint pre-configured.

```json
// package.json scripts (replace existing)
{
  "scripts": {
    "build": "wp-scripts build --blocks-manifest",
    "start": "wp-scripts start",
    "lint:js": "wp-scripts lint-js",
    "lint:style": "wp-scripts lint-style",
    "format": "wp-scripts format",
    "plugin-zip": "wp-scripts plugin-zip"
  }
}
```

**`--blocks-manifest` flag** — generates `build/blocks-manifest.php` automatically during every build.
This file is consumed by `wp_register_block_types_from_metadata_collection()` in PHP.

**SCSS:** Import `.scss` files directly in block JavaScript (`import './style.scss'`). Webpack compiles them.
No `node-sass`, no separate CSS build step, no `csso-cli` required.

**Entry points follow block.json convention:**
- `src/blocks/speaker-profile/index.js` → `build/blocks/speaker-profile/index.js`
- Each block has its own `block.json`, `edit.js`, `save.js`, `style.scss`, `editor.scss`

Source: [Refactoring the multi-block plugin — WP Developer Blog](https://developer.wordpress.org/news/2025/08/refactoring-the-multi-block-plugin-build-smarter-register-cleaner-scale-easier/)

---

## Installation

```bash
# Remove deprecated packages
npm uninstall node-sass csso-cli gulp

# Install current build toolchain
npm install -D @wordpress/scripts@31.5.0

# Install PHP dependency manager (if not already installed globally)
brew install composer  # or download from getcomposer.org

# Initialize Composer in plugin root
composer init
# Add PSR-4 autoload section to composer.json, then:
composer install
```

```bash
# Regular dev workflow
npm run start        # Webpack watch mode with HMR
npm run build        # Production build + blocks-manifest.php
composer dump-autoload -o   # Optimised autoloader for production
```

---

## Alternatives Considered

| Recommended | Alternative | When to Use Alternative |
|-------------|-------------|-------------------------|
| @wordpress/scripts | Vite + @vitejs/plugin-react | If building a decoupled React SPA; not appropriate for Gutenberg blocks where Webpack externalisation of @wordpress/* is required |
| @wordpress/scripts | Custom Webpack config | Only if @wordpress/scripts defaults are genuinely insufficient (they are not for this project) |
| Composer PSR-4 | Custom PHP autoloader | If the plugin must ship with no vendor/ directory (some plugin review guidelines); adds maintenance overhead with no benefit here |
| react-leaflet v4 + Leaflet 1.9 | Leaflet 2.0 + vanilla JS | Once Leaflet 2.0 stable ships and react-leaflet v5 supports a React 18-compatible build |
| Leaflet.js | Google Maps JS API | Requires API key, per-use billing; Leaflet + OpenStreetMap is zero-cost and sufficient for a read-only world map |
| WordPress 6.8 floor | WordPress 6.5 floor | Only if compatibility stats show significant user base on 6.5–6.7; at that point, fall back to `register_block_type_from_metadata()` loop and lose the manifest optimisation |

---

## What NOT to Use

| Avoid | Why | Use Instead |
|-------|-----|-------------|
| `node-sass` | Abandoned; binds to native Node binary; breaks on every major Node upgrade; incompatible with Node 18+ | Dart Sass via `sass` package, bundled inside @wordpress/scripts sass-loader |
| `csso-cli` / separate CSS minification | @wordpress/scripts handles CSS minification in production builds natively | `npm run build` with @wordpress/scripts |
| `gulp` | No role when @wordpress/scripts handles the entire build pipeline | Remove from dependencies |
| `react-leaflet` v5 | Requires React 19; @wordpress/scripts 31.x bundles React 18; v5 would conflict with Gutenberg's React version | react-leaflet v4.x |
| Leaflet 2.0.0-alpha | Pre-release; removes global `L`; react-leaflet v4 not compatible; production risk | Leaflet 1.9.4 (latest stable) |
| `@wordpress/create-block` (as a generator) | Useful for scaffolding one block; not a runtime dependency | Only use as a one-time scaffold tool if helpful |
| Global `new Speekr()` anti-pattern | Existing pattern; no namespacing, no autoloading, mixes concerns | PSR-4 `Speekr\Core\Plugin` instantiated from bootstrap after autoloader loads |
| jQuery in block code | Not available in the block editor React context; legacy admin only | React hooks + @wordpress/* packages in blocks; jQuery permitted only in legacy admin pages if absolutely required |

---

## Stack Patterns by Variant

**If the active theme is a block theme (FSE):**
- Use `register_block_template()` from plugin to provide CPT archive and single templates
- Templates live in `templates/` directory as HTML files with block markup
- Theme can override using Site Editor

**If the active theme is a classic theme:**
- Block templates registered via `register_block_template()` are not rendered by classic themes
- Provide classic PHP template files (e.g. `single-talk.php`) in `templates/classic/` directory and load via `template_include` filter
- Blocks still work in post content via shortcode or block embeddings

**For the conference map block (dynamic, Leaflet):**
- Block is a dynamic block — PHP render callback fetches conference CPT data, outputs a JSON data attribute
- React/Leaflet edit view loads in block editor for preview
- Frontend: lightweight vanilla JS (or a tiny React island) reads the JSON data attribute, initialises Leaflet
- This avoids loading React on the frontend for a map that doesn't need editor interactivity after save

---

## Version Compatibility

| Package | Compatible With | Notes |
|---------|-----------------|-------|
| @wordpress/scripts@31.5.0 | React@18, Webpack@5.97, Node@18.12+ | Node 20 LTS recommended; Node 22 LTS also works |
| react-leaflet@4.x | Leaflet@1.8–1.9, React@18 | Do NOT use react-leaflet@5; requires React 19 |
| Leaflet@1.9.4 | react-leaflet@4.x | Stable branch; Leaflet 2.0 still alpha as of Feb 2026 |
| WordPress@6.8+ | PHP@8.1–8.3 | PHP 8.3 recommended; PHP 7.4 not worth targeting |
| Composer@2.x | PHP@8.1+ | Composer 1.x is EOL |

---

## Sources

- [Gutenberg packages/scripts package.json (trunk)](https://github.com/WordPress/gutenberg/blob/trunk/packages/scripts/package.json) — confirmed @wordpress/scripts 31.5.0, Webpack 5.97, React 18 peer dep (HIGH confidence)
- [Gutenberg webpack.config.js (trunk)](https://github.com/WordPress/gutenberg/blob/trunk/packages/scripts/config/webpack.config.js) — confirmed native sass-loader for .scss/.sass (HIGH confidence)
- [@wordpress/scripts handbook](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/) — build, start, lint scripts confirmed (HIGH confidence)
- [wp_register_block_types_from_metadata_collection() reference](https://developer.wordpress.org/reference/functions/wp_register_block_types_from_metadata_collection/) — introduced WP 6.8.0 (HIGH confidence)
- [register_block_template() — WP 6.7 announcement](https://developer.wordpress.org/news/2024/08/registering-block-templates-via-plugins-in-wordpress-6-7/) — FSE template registration from plugins (HIGH confidence)
- [More efficient block type registration in 6.8](https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/) — blocks-manifest workflow (HIGH confidence)
- [Refactoring the multi-block plugin](https://developer.wordpress.org/news/2025/08/refactoring-the-multi-block-plugin-build-smarter-register-cleaner-scale-easier/) — 2025 recommended multi-block plugin structure (HIGH confidence)
- [PHP namespaces in WordPress plugins](https://developer.wordpress.org/news/2025/09/implementing-namespaces-and-coding-standards-in-wordpress-plugin-development/) — official WP developer blog guidance (HIGH confidence)
- [PSR-4 specification](https://www.php-fig.org/psr/psr-4/) — autoloader standard (HIGH confidence)
- [Leaflet 2.0 alpha announcement](https://leafletjs.com/2025/05/18/leaflet-2.0.0-alpha.html) — 2.0 still pre-release Feb 2026 (HIGH confidence)
- [react-leaflet v5 releases](https://github.com/PaulLeCam/react-leaflet/releases) — v5 requires React 19 (HIGH confidence)
- [WordPress What's New for Developers February 2026](https://developer.wordpress.org/news/2026/02/whats-new-for-developers-february-2026/) — WP 6.9.1 current stable, WP 7.0 Beta 1 imminent (HIGH confidence)
- [PHP 8.5 support in WordPress 6.9](https://make.wordpress.org/core/2025/11/21/php-8-5-support-in-wordpress-6-9/) — PHP version landscape (HIGH confidence)
- [block.json fundamentals handbook](https://developer.wordpress.org/block-editor/getting-started/fundamentals/block-json/) — block metadata registration pattern (HIGH confidence)

---

*Stack research for: Speekr WordPress plugin modernization — Gutenberg blocks, FSE, PSR-4, modern build toolchain*
*Researched: 2026-02-28*
