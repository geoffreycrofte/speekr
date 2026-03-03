---
phase: "05"
plan: "02"
subsystem: templates
tags: [fse, block-templates, classic-templates, template-include]
dependency_graph:
  requires: []
  provides: [fse-block-templates, classic-template-routing]
  affects: [inc/front/templates.php, templates/*.html]
tech_stack:
  added: [register_block_template API]
  patterns: [3-path override lookup, template_include filter]
key_files:
  created:
    - inc/front/templates.php
    - templates/singular-speekr_speaker.html
    - templates/archive-talks.html
    - templates/single-talks.html
    - templates/archive-speekr_conference.html
  modified: []
decisions:
  - "register_block_template() API used (WP 6.7+); underscores in slugs valid in WP 6.9.1"
  - "Classic template 3-path lookup: child theme > parent theme > plugin fallback"
  - "inc/front/templates.php not wired in this plan — wiring deferred to Plan 04 (Speekr::includes)"
metrics:
  duration: "~3 min"
  completed: "2026-03-03"
  tasks_completed: 2
  files_created: 5
  files_modified: 0
---

# Phase 05 Plan 02: FSE Block Templates + Classic Template Routing Summary

**One-liner:** FSE block templates for 4 Speekr CPTs via register_block_template() + classic theme CPT routing via template_include filter with 3-path child/parent/plugin override lookup.

## What Was Built

Two capabilities delivered:

1. **FSE block templates** — 4 HTML files in `templates/` directory, each containing correct WordPress block markup with header/footer template parts and the relevant Speekr block. Conference archive includes both `speekr/conference-map` (above) and `speekr/conference-archive`.

2. **`inc/front/templates.php`** — Registers the 4 FSE templates via `register_block_template()` on `init`, and routes classic (non-FSE) theme CPT URLs via a `template_include` filter covering 5 CPT conditions with a 3-path override lookup.

## Tasks

| Task | Name | Commit | Files |
|------|------|--------|-------|
| 1 | Create 4 FSE block template HTML files | c8fee8f | templates/*.html (4 files) |
| 2 | Create inc/front/templates.php | e8301fe | inc/front/templates.php |

## Deviations from Plan

None — plan executed exactly as written.

## Self-Check

- [x] `templates/singular-speekr_speaker.html` exists and contains `wp:speekr/speaker-profile`
- [x] `templates/archive-talks.html` exists and contains `wp:speekr/talks-list`
- [x] `templates/single-talks.html` exists and contains `wp:speekr/single-talk`
- [x] `templates/archive-speekr_conference.html` exists and contains both `wp:speekr/conference-map` and `wp:speekr/conference-archive`
- [x] `inc/front/templates.php` passes PHP lint
- [x] `inc/front/templates.php` contains both `add_action('init', ...)` and `add_filter('template_include', ...)`
- [x] Task 1 commit: c8fee8f
- [x] Task 2 commit: e8301fe

## Self-Check: PASSED
