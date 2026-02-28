# Phase 1: Build Foundation - Context

**Gathered:** 2026-03-01
**Status:** Ready for planning

<domain>
## Phase Boundary

Migrate the build toolchain from node-sass/gulp to @wordpress/scripts so `npm run build` works on Node 18+ and produces compiled block assets plus legacy admin/frontend scripts in `build/`. Enqueue paths in PHP updated to point to the new `build/` locations. No other PHP changes in this phase. No blocks built yet — just the pipeline that will compile them.

</domain>

<decisions>
## Implementation Decisions

### Block source directory
- Block source files live in `src/blocks/` (e.g. `src/blocks/talk-meta/`)
- Each block directory contains: `index.js`, `edit.js`, `render.php`, `style.scss`
- No separate `editor.scss` — one `style.scss` shared between editor and frontend
- `block.json` placement: Claude's discretion (standard @wordpress/scripts convention)

### Legacy assets
- Legacy admin SCSS and JS move into the webpack pipeline (unified build, not two separate pipelines)
- All output goes to `build/` — enqueue paths in PHP updated in Phase 1 to match
- `build/` organized as: `build/blocks/`, `build/admin/`, `build/frontend/`
- Minification handled by webpack production mode; no separate `.min.css` files needed
- Old `assets/css/src/` SCSS source files and old compiled CSS/JS cleaned up after `build/` is confirmed working

### npm scripts API
- `npm run build` — production build (minified, no source maps)
- `npm run build:dev` — development build (readable, with source maps)
- `npm run start` — watch mode covering all source files (src/blocks/ + legacy SCSS/JS)
- `npm run lint` — single command running all linters (JS + CSS via @wordpress/scripts)
- `npm run clean` — wipe `build/` before rebuilding
- Old scripts (`compile:css`, `minify:css`, `watch`) removed

### Build output structure
- `build/blocks/{block-name}/` — compiled block assets per block
- `build/admin/` — compiled admin JS and CSS
- `build/frontend/` — compiled frontend JS and CSS
- `build/` committed to git (WordPress plugin convention — installs without build step)
- Source maps gitignored (`.map` files excluded from git to keep repo size down)

### Claude's Discretion
- `block.json` location (src alongside JS or generated): Claude picks standard @wordpress/scripts approach
- Exact webpack.config.js structure for multiple entry points
- How to handle the existing font assets in `assets/fonts/` (likely leave in place, reference from CSS)

</decisions>

<specifics>
## Specific Ideas

- The existing custom icon font (`assets/fonts/admin/speekr`) should remain accessible from compiled admin CSS — keep font assets in place, update CSS paths if needed
- The PHP SCRIPT_DEBUG check (currently toggles between `.css` and `.min.css`) should be simplified since there's no longer a separate `.min.css`

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 01-build-foundation*
*Context gathered: 2026-03-01*
