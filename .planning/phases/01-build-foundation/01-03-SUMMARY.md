---
phase: 01-build-foundation
plan: 03
subsystem: infra
tags: [webpack, wordpress-scripts, npm, build, scss, php]

# Dependency graph
requires:
  - phase: 01-01
    provides: "@wordpress/scripts webpack config and updated package.json"
  - phase: 01-02
    provides: "src/admin/index.js, src/admin/style.scss, src/frontend/style.scss entry points"
provides:
  - "build/ directory populated: build/admin/style-index.css, build/admin/index.js, build/frontend/style-style.css"
  - "PHP enqueue paths updated in all 3 files to reference build/ output"
  - "Pre-existing bug fixed: inc/admin/notices.php was referencing admin.min.css which never existed"
affects: [02-speaker-profile, 03-conference-map, 04-blocks, 05-fse-templates]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "@wordpress/scripts CSS output uses style- prefix: entry key admin/index → build/admin/style-index.css; entry key frontend/style → build/frontend/style-style.css"
    - "RTL companion files emitted automatically: style-index-rtl.css, style-style-rtl.css"
    - "Frontend-only SCSS entry emits an empty style.js — expected, do not enqueue it"

key-files:
  created:
    - build/admin/style-index.css
    - build/admin/style-index-rtl.css
    - build/admin/index.js
    - build/admin/index.asset.php
    - build/frontend/style-style.css
    - build/frontend/style-style-rtl.css
    - build/frontend/style.js
    - build/frontend/style.asset.php
    - build/fonts/speekr.1d3c47b5.eot
    - build/fonts/speekr.2a406cb2.ttf
    - build/fonts/speekr.2fc3b703.woff
    - package-lock.json
  modified:
    - inc/admin/enqueues.php
    - inc/admin/notices.php
    - inc/front/enqueues.php

key-decisions:
  - "@wordpress/scripts CSS output uses style- prefix naming: style-index.css (not index.css), style-style.css (not style.css) — PHP enqueue paths updated to match actual output"
  - "Stale package-lock.json (locked @wordpress/scripts@^12.1.0) deleted to allow fresh resolution against updated package.json"

patterns-established:
  - "CSS filename pattern: entry key {dir}/{name} → build/{dir}/style-{name}.css"
  - "RTL pattern: every CSS file gets a companion -rtl.css emitted automatically by @wordpress/scripts"

# Metrics
duration: 5min
completed: 2026-03-01
---

# Phase 1 Plan 03: Build Execution Summary

**Production build wired end-to-end: npm install resolves @wordpress/scripts@31.5 cleanly, webpack produces compiled CSS/JS in build/, and all 3 PHP enqueue files updated to load from build/ (including fix for never-existing admin.min.css)**

## Performance

- **Duration:** ~5 min
- **Started:** 2026-03-01T00:14:47Z
- **Completed:** 2026-03-01T00:19:30Z
- **Tasks:** 2
- **Files modified:** 4 (package-lock.json + 3 PHP files) + build/ directory created

## Accomplishments

- Fresh npm install with @wordpress/scripts@31.5.0 succeeds (stale package-lock.json removed)
- `npm run build` exits 0; build/ directory populated with compiled admin and frontend assets
- All 3 PHP enqueue files updated: no remaining references to assets/css/ or assets/js/
- Pre-existing bug fixed: `inc/admin/notices.php` referenced `admin.min.css` which never existed

## Task Commits

Each task was committed atomically:

1. **Task 1: Clean install dependencies and run production build** - `f9d34b9` (chore)
2. **Task 2: Update PHP enqueue paths in 3 files** - `902630d` (feat)

**Plan metadata:** _(docs commit follows)_

## Files Created/Modified

- `package-lock.json` - Regenerated for @wordpress/scripts@31.5.0 (old version locked node-sass/gulp)
- `build/admin/style-index.css` - Compiled admin CSS (34 KB)
- `build/admin/index.js` - Compiled admin JS (6.4 KB)
- `build/admin/index.asset.php` - Asset versioning file
- `build/frontend/style-style.css` - Compiled frontend CSS (22 KB)
- `build/frontend/style.js` - Empty JS stub (expected; do not enqueue)
- `build/fonts/` - 3 frontend icon font files with content-hash filenames
- `inc/admin/enqueues.php` - Updated to build/admin/style-index.css + build/admin/index.js
- `inc/admin/notices.php` - Updated to build/admin/style-index.css (fixed admin.min.css bug)
- `inc/front/enqueues.php` - Updated to build/frontend/style-style.css; SCRIPT_DEBUG toggle removed

## Decisions Made

- **CSS output naming:** The plan expected `build/admin/index.css` and `build/frontend/style.css`, but `@wordpress/scripts` outputs CSS with a `style-` prefix by default when the entry is a JS/SCSS file. Actual outputs are `style-index.css` and `style-style.css`. PHP enqueue paths updated to match actual filenames rather than bending webpack config to override the convention.

- **Stale lock file:** Deleted package-lock.json because it still encoded the old dependency tree (`@wordpress/scripts@^12.1.0`, `node-sass`, `gulp`), which prevented npm from resolving the new versions. A fresh lock file was generated.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Deleted stale package-lock.json that blocked npm install**
- **Found during:** Task 1 (npm install)
- **Issue:** package-lock.json still referenced @wordpress/scripts@12.6.1 (old version). npm ERESOLVE error: could not resolve @wordpress/scripts@^31.5.0 against cached 12.6.1 dependency tree
- **Fix:** Removed package-lock.json; ran fresh `npm install` which resolved correctly
- **Files modified:** package-lock.json (deleted then regenerated)
- **Verification:** npm install completed with 1541 packages, exit 0
- **Committed in:** f9d34b9 (Task 1 commit)

**2. [Rule 1 - Bug] Corrected PHP enqueue paths to match actual @wordpress/scripts CSS output filenames**
- **Found during:** Task 1 (build output inspection)
- **Issue:** Plan specified `build/admin/index.css` and `build/frontend/style.css`, but @wordpress/scripts adds a `style-` prefix to CSS output filenames. Actual files: `build/admin/style-index.css` and `build/frontend/style-style.css`
- **Fix:** Updated Task 2 PHP enqueue paths to use the actual output filenames
- **Files modified:** inc/admin/enqueues.php, inc/admin/notices.php, inc/front/enqueues.php
- **Verification:** `ls build/admin/style-index.css build/frontend/style-style.css` confirms files exist; grep confirms PHP references correct paths
- **Committed in:** 902630d (Task 2 commit)

---

**Total deviations:** 2 auto-fixed (1 blocking, 1 bug)
**Impact on plan:** Both fixes required for correct operation. No scope creep. The CSS naming deviation is a standard @wordpress/scripts convention that future plans referencing build CSS paths must use.

## Issues Encountered

- npm ERESOLVE on initial install due to stale package-lock.json — resolved by deleting lock file and reinstalling cleanly.
- @wordpress/scripts CSS output filename convention (`style-` prefix) differs from plan's expected filenames — enqueue paths adjusted to match actual output.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Build pipeline fully operational: `npm run build` produces all expected assets
- WordPress will load compiled CSS/JS from build/ on next plugin activation
- PHP enqueue files are clean with no legacy assets/css/ or assets/js/ references
- Phase 1 complete — foundation build pipeline migration done; ready for Phase 2 feature work
- Note for future plans: CSS files in build/ use `style-` prefix pattern (`style-index.css`, `style-style.css`)

---
*Phase: 01-build-foundation*
*Completed: 2026-03-01*
