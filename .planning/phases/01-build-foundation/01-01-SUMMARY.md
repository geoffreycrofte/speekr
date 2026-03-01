---
phase: 01-build-foundation
plan: 01
subsystem: infra
tags: [webpack, wp-scripts, wordpress, build-tooling, node-sass, gulp]

# Dependency graph
requires: []
provides:
  - Updated package.json with @wordpress/scripts@^31.5.0 and 5 wp-scripts npm scripts
  - webpack.config.js extending defaultConfig with legacy admin/index and frontend/style entry points
  - .gitignore exclusion of build/**/*.map (build/ itself remains committed)
affects: [01-02, 01-03, all phases using npm run build]

# Tech tracking
tech-stack:
  added: ["@wordpress/scripts@^31.5.0"]
  patterns: ["webpack.config.js extends defaultConfig and calls defaultConfig.entry() as a function (required for v27+)"]

key-files:
  created: ["webpack.config.js"]
  modified: ["package.json", ".gitignore"]

key-decisions:
  - "defaultConfig.entry() must be called as a function (not spread as object) in @wordpress/scripts v27+; documented with comment in webpack.config.js"
  - "Legacy admin/index entry bundles both JS and SCSS; frontend/style entry is SCSS-only (emits empty JS — do not enqueue)"
  - "build/ directory is NOT gitignored (WordPress plugin convention); only build/**/*.map is excluded"

patterns-established:
  - "Entry points pattern: blocks via defaultConfig.entry() + legacy entries under admin/ and frontend/ namespaces"
  - "No npm install in this plan — deferred to Plan 03 after source files exist"

# Metrics
duration: 1min
completed: 2026-03-01
---

# Phase 1 Plan 01: Replace Build Pipeline with @wordpress/scripts Summary

**@wordpress/scripts@^31.5.0 replaces node-sass/gulp pipeline; webpack.config.js extends defaultConfig with auto-block detection and legacy admin/frontend entry points**

## Performance

- **Duration:** ~1 min
- **Started:** 2026-03-01T00:09:12Z
- **Completed:** 2026-03-01T00:10:02Z
- **Tasks:** 2
- **Files modified:** 3

## Accomplishments
- Replaced broken node-sass@4.x/gulp@4.x build pipeline with @wordpress/scripts@^31.5.0
- Created webpack.config.js that correctly calls `defaultConfig.entry()` as a function (required for v27+)
- Added three entry points: auto-detected blocks, legacy admin/index (JS+SCSS), legacy frontend/style (SCSS only)
- Updated .gitignore to exclude source maps from build/ while keeping build/ itself committed

## Task Commits

Each task was committed atomically:

1. **Task 1: Replace package.json devDependencies and npm scripts** - `edf7226` (chore)
2. **Task 2: Create webpack.config.js and update .gitignore** - `688fe5c` (chore)

**Plan metadata:** _(docs commit follows)_

## Files Created/Modified
- `package.json` - Scripts replaced with wp-scripts equivalents; devDependencies trimmed to @wordpress/scripts + dir-archiver; dependencies block removed
- `webpack.config.js` - New file; extends @wordpress/scripts defaultConfig with entry() function call and legacy entry points
- `.gitignore` - Added `build/**/*.map` exclusion rule; build/ itself remains unignored

## Decisions Made
- `defaultConfig.entry` is a function in @wordpress/scripts v27+, not a plain object — must call with `()` when spreading. Documented inline in webpack.config.js with reference link.
- Legacy frontend/style entry emits an empty `build/frontend/style.js` file as a webpack side-effect — expected behavior, noted in comment to avoid confusion when enqueuing.
- `dir-archiver` retained in devDependencies (unrelated to build pipeline — used for plugin archiving).

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- Build tooling config complete; ready for Plan 02 (source file scaffolding: src/admin/, src/frontend/, src/blocks/)
- npm install deferred to Plan 03 per roadmap; webpack.config.js will load correctly once @wordpress/scripts is installed

---
*Phase: 01-build-foundation*
*Completed: 2026-03-01*

## Self-Check: PASSED

- package.json: FOUND
- webpack.config.js: FOUND
- .gitignore: FOUND
- 01-01-SUMMARY.md: FOUND
- Commit edf7226: FOUND
- Commit 688fe5c: FOUND
