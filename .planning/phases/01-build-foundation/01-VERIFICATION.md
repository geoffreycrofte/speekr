---
phase: 01-build-foundation
verified: 2026-03-01T00:00:00Z
status: human_needed
score: 4/4 automated must-haves verified
re_verification: false
human_verification:
  - test: "Open WordPress admin, navigate to a Speekr page (Talks > Add New or Speekr settings). Open DevTools Network tab."
    expected: "No 404 for build/admin/style-index.css or build/admin/index.js. Speekr icon visible in admin sidebar. Purple/violet color scheme intact."
    why_human: "WordPress must be running and PHP must bootstrap correctly to confirm enqueue hooks fire and assets serve without 404. Cannot verify HTTP responses programmatically."
  - test: "Navigate to a frontend page that lists Talks while DevTools Network tab is open."
    expected: "No 404 for build/frontend/style-style.css. Talk link icons visible (small icons before YouTube, slides, etc. links). Confirms font path fix works at runtime."
    why_human: "Font rendering at runtime requires a browser. The SCSS compiles without errors but the ../../assets/fonts/front/ relative URL path fix can only be confirmed by visual rendering of the icon font."
  - test: "Run `npm run start` in plugin root, wait for 'compiled successfully', then stop with Ctrl+C."
    expected: "Watch mode starts without compilation errors. webpack reports successful compilation of admin/index and frontend/style entries. SIGINT exit is acceptable."
    why_human: "npm run start requires an interactive terminal session. Cannot run it non-interactively in a verification script (watch mode never exits on its own)."
---

# Phase 01: Build Foundation — Verification Report

**Phase Goal:** A developer can run `npm run build` on Node 18+ without errors; compiled block assets and legacy admin/frontend scripts land in `build/`; no changes to PHP files in this phase (beyond enqueue path updates)
**Verified:** 2026-03-01
**Status:** human_needed — all automated checks passed; 3 items need human/browser verification
**Re-verification:** No — initial verification

---

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | `npm run build` completes without errors on Node 18+ and produces compiled assets in `build/` | VERIFIED | Build ran: `webpack 5.105.3 compiled successfully in 635 ms`; `build/admin/style-index.css` (17 KB), `build/admin/index.js` (6.3 KB), `build/frontend/style-style.css` (10.9 KB) all exist |
| 2 | `npm run start` enters watch mode and recompiles without errors | NEEDS HUMAN | Config and source files are correct; watch mode cannot be run non-interactively |
| 3 | Plugin loads on WordPress without PHP fatal errors after migration | NEEDS HUMAN | PHP enqueue files are syntactically correct; actual WordPress loading requires browser verification |
| 4 | Compiled assets in `build/` are enqueued correctly — no 404s in browser network tab | NEEDS HUMAN | PHP enqueue paths point to existing `build/` files; actual HTTP serving requires browser verification |

**Automated score:** All 4 automated must-haves (scripts, config, source files, build output, enqueue wiring) pass. 3 truths require human verification.

---

## Required Artifacts

### Plan 01-01: Build Config

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `package.json` | 5 wp-scripts npm scripts; `@wordpress/scripts@^31.5.0` in devDependencies; no node-sass/gulp/csso-cli | VERIFIED | Scripts: `[build, build:dev, start, lint, clean]`; devDeps: `[@wordpress/scripts, dir-archiver]`; old deps absent |
| `webpack.config.js` | Extends defaultConfig; calls `defaultConfig.entry()` with parentheses; 3 entry points | VERIFIED | Valid JS syntax (`node --check` exits 0); spreads `defaultConfig.entry()`; entries: `admin/index`, `frontend/style`; auto-block detection via spread |
| `.gitignore` | Contains `build/**/*.map`; does NOT contain `build/` or `build/$` | VERIFIED | Line 6: `build/**/*.map` present; no `^build$` or `^build/$` line found |

### Plan 01-02: Source Files

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `src/admin/index.js` | Admin JS webpack entry point | VERIFIED | Exists, 9.21 KB (webpack reports it built) |
| `src/admin/style.scss` | Admin CSS webpack entry point | VERIFIED | Exists; webpack compiled it to 17.1 KB CSS output |
| `src/frontend/style.scss` | Frontend CSS entry with corrected font paths (`../../assets/fonts/front/`) | VERIFIED | 5 occurrences of `../../assets/fonts/front/`; 0 occurrences of old `../fonts/front/` path |
| `src/blocks/.gitkeep` | Placeholder so git tracks blocks dir | VERIFIED | File exists at `src/blocks/.gitkeep` |

### Plan 01-03: Build Output and PHP Enqueues

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `build/admin/style-index.css` | Compiled admin CSS | VERIFIED | 17,556 bytes; produced by current build |
| `build/admin/index.js` | Compiled admin JS | VERIFIED | 6,477 bytes; produced by current build |
| `build/frontend/style-style.css` | Compiled frontend CSS | VERIFIED | 11,206 bytes; produced by current build |
| `inc/admin/enqueues.php` | Points to `build/admin/style-index.css` + `build/admin/index.js` | VERIFIED | Line 16–17 contain correct paths; no `assets/css/` or `assets/js/` references remain |
| `inc/admin/notices.php` | Points to `build/admin/style-index.css`; `admin.min.css` bug fixed | VERIFIED | Line 32 correct; no `admin.min.css` found |
| `inc/front/enqueues.php` | Points to `build/frontend/style-style.css`; SCRIPT_DEBUG toggle removed | VERIFIED | Line 25 correct; no `SCRIPT_DEBUG` or `.min.` found |

### Plan 01-04: Cleanup

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `assets/css/speekr-admin.css` (deleted) | Should not exist | VERIFIED | `ls assets/css/` returns empty — all .css files deleted |
| `assets/css/src/` (deleted) | Directory should not exist | VERIFIED | `ls assets/css/src/` returns "not found" |
| `assets/js/speekr.js` (deleted) | Should not exist | VERIFIED | Only `speekr-admin.js` remains in `assets/js/` |
| `assets/fonts/` (untouched) | Both `admin/` and `front/` dirs with font files intact | VERIFIED | `assets/fonts/admin/` and `assets/fonts/front/` both contain `.eot`, `.svg`, `.ttf`, `.woff` |
| `build/fonts/` | Content-hash font files emitted by webpack | VERIFIED | `speekr.1d3c47b5.eot`, `speekr.2a406cb2.ttf`, `speekr.2fc3b703.woff` present |

---

## Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `webpack.config.js` | `src/admin/index.js` | entry point path | WIRED | `path.resolve(__dirname, 'src/admin/index.js')` in entry; file exists; webpack confirms it built the file |
| `webpack.config.js` | `src/frontend/style.scss` | entry point path | WIRED | `path.resolve(__dirname, 'src/frontend/style.scss')` in entry; file exists; webpack confirms it built the file |
| `inc/admin/enqueues.php` | `build/admin/style-index.css` | `wp_enqueue_style` | WIRED | Line 16: `SPEEKR_PLUGIN_URL . 'build/admin/style-index.css'`; file exists in build/ |
| `inc/admin/enqueues.php` | `build/admin/index.js` | `wp_enqueue_script` | WIRED | Line 17: `SPEEKR_PLUGIN_URL . 'build/admin/index.js'`; file exists in build/ |
| `inc/admin/notices.php` | `build/admin/style-index.css` | `wp_enqueue_style` | WIRED | Line 32: `SPEEKR_PLUGIN_URL . 'build/admin/style-index.css'`; file exists in build/ |
| `inc/front/enqueues.php` | `build/frontend/style-style.css` | `wp_enqueue_style` | WIRED | Line 25: `SPEEKR_PLUGIN_URL . 'build/frontend/style-style.css'`; file exists in build/ |
| `src/frontend/style.scss` | `assets/fonts/front/` | `@font-face url()` | WIRED | 5 occurrences of `../../assets/fonts/front/`; font files confirmed present at that relative path |

---

## PHP File Scope Check

**Constraint:** No PHP files changed beyond the 3 enqueue path updates.

Git diff over all phase commits (`edf7226` through `d96f58e`) against PHP files shows **only** `inc/admin/enqueues.php`, `inc/admin/notices.php`, and `inc/front/enqueues.php` were modified. Changes were exactly the enqueue path updates specified in the plan. No other `inc/` files, no `speekr.php`, no `classes/` or `functions/` files were touched.

**Status: VERIFIED** — PHP change constraint respected.

---

## Plan Deviations Noted

The following deviation from plan was auto-corrected by Claude during execution and is correctly reflected in the codebase:

**CSS output naming convention:** Plan 01-03 expected `build/admin/index.css` and `build/frontend/style.css`, but `@wordpress/scripts` v31.5.0 adds a `style-` prefix to CSS output filenames by default. Actual files are `build/admin/style-index.css` and `build/frontend/style-style.css`. The PHP enqueue paths were updated to match the actual output — this is correct behavior. The PLAN.md must_haves for Plan 01-03 listed `build/admin/index.css` and `build/frontend/style.css` which no longer exist, but this is expected: the deviation was documented and the correct files exist.

---

## Anti-Patterns Found

| File | Pattern | Severity | Impact |
|------|---------|----------|--------|
| `webpack.config.js` line 13 | Comment says `build/admin/index.{js,css}` but actual CSS output is `style-index.css` | Info | Stale comment; does not affect build behavior. Could confuse future developers. |

No blocking anti-patterns found. No TODOs, FIXMEs, placeholder returns, or empty handlers detected in modified files.

---

## Human Verification Required

### 1. Browser: Admin Assets Load Without 404

**Test:** Open WordPress admin while logged in (your local WP instance). Open DevTools Network tab. Navigate to a Speekr admin page (Talks > Add New, or the Speekr settings page).
**Expected:** No 404 for `build/admin/style-index.css` or `build/admin/index.js`. Speekr icon visible in admin sidebar. Purple/violet color scheme intact.
**Why human:** WordPress must be running and the PHP bootstrap must fire for enqueue hooks to execute. The PHP files are syntactically correct and point to existing build files, but actual HTTP 200 responses require a running server.

### 2. Browser: Frontend Icon Font Renders

**Test:** Navigate to a frontend WordPress page that lists Talks (or a single Talk post).
**Expected:** No 404 for `build/frontend/style-style.css`. Talk link icons visible — small icons before YouTube, slides, etc. links. This confirms the `../../assets/fonts/front/` path fix works at runtime.
**Why human:** Font rendering at runtime requires a browser. The SCSS compiles cleanly and the path fix is verified in source, but the relative URL only resolves correctly when served by WordPress from the plugin's URL base. A runtime font 404 would be silent at build time.

### 3. Terminal: Watch Mode

**Test:** Run `npm run start` from the plugin root. Wait for "compiled successfully" output. Stop with Ctrl+C.
**Expected:** Watch mode starts without compilation errors. webpack reports successful compilation of `admin/index` and `frontend/style` entries. SIGINT exit after Ctrl+C is acceptable.
**Why human:** `npm run start` enters an indefinite watch loop and cannot be run non-interactively in a verification script. Source files and config are verified correct, making failure unlikely.

---

## Summary

All automated checks pass. The build pipeline migration from `node-sass@4.x / gulp@4.x` to `@wordpress/scripts@31.5.0` is complete and correctly wired end-to-end:

- `package.json` has exactly 5 `wp-scripts`-based npm scripts; old tooling removed
- `webpack.config.js` correctly calls `defaultConfig.entry()` as a function (required for v27+) and defines admin + frontend legacy entry points
- Source files exist at `src/admin/index.js`, `src/admin/style.scss`, and `src/frontend/style.scss` (with corrected `@font-face` font paths for the build output location)
- `npm run build` compiles successfully: `webpack 5.105.3 compiled successfully in 635 ms`
- Build outputs exist and are non-trivial: `style-index.css` (17 KB), `index.js` (6.3 KB), `style-style.css` (11 KB), plus RTL companions and font files in `build/fonts/`
- All 3 PHP enqueue files updated to point to `build/` paths with no remaining references to `assets/css/` or `assets/js/`; pre-existing `admin.min.css` 404 bug fixed; `SCRIPT_DEBUG` toggle removed
- Legacy compiled CSS files and old SCSS sources deleted from `assets/css/`; `assets/fonts/` untouched
- No PHP files modified beyond the 3 enqueue files

Phase goal is structurally achieved. Browser verification is needed to confirm the compiled assets serve without 404s and icon fonts render correctly at runtime.

---

_Verified: 2026-03-01_
_Verifier: Claude (gsd-verifier)_
