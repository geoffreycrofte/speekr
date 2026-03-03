---
phase: 05-templates-and-compatibility
plan: "03"
subsystem: frontend
tags: [classic-templates, shortcodes, php]
dependency_graph:
  requires: []
  provides: [classic-theme-cpt-templates, shortcodes]
  affects: [inc/front/templates/classic, inc/front/shortcodes.php]
tech_stack:
  added: []
  patterns: [classic-template-fallback, ob_start-shortcode-pattern]
key_files:
  created:
    - inc/front/templates/classic/speaker-profile.php
    - inc/front/templates/classic/archive-talk.php
    - inc/front/templates/classic/single-talk.php
    - inc/front/templates/classic/archive-conference.php
    - inc/front/templates/classic/single-conference.php
    - inc/front/shortcodes.php
  modified: []
decisions:
  - "talks-list block.json has no limit attribute — shortcode [speekr_talks] omits limit from $attributes"
  - "single-conference.php reuses conference-archive/render.php — no dedicated single-conference block (MVP)"
  - "ob_start() in shortcode callbacks is safe — different context from render.php restriction (no outer WP buffer)"
metrics:
  duration: ~3 min
  completed: 2026-03-03
  tasks_completed: 2
  files_created: 6
---

# Phase 5 Plan 03: Classic Templates and Shortcodes Summary

5 classic PHP template files and 1 shortcodes.php file created, giving classic theme users CPT template fallbacks and allowing any post/page to embed speaker profile, talks list, or conference map via shortcodes — all reusing existing block render.php files without duplicating logic.

## Tasks Completed

| Task | Name | Commit | Files |
|------|------|--------|-------|
| 1 | Create 5 classic PHP template files | d5e4807 | inc/front/templates/classic/*.php (5 files) |
| 2 | Create shortcodes.php with 3 shortcode registrations | b22a70a | inc/front/shortcodes.php |

## What Was Built

**Classic templates** (`inc/front/templates/classic/`): Each file follows the pattern — ABSPATH guard, `get_header()`, `$attributes = array()`, `require SPEEKR_DIRNAME . '/src/blocks/{block}/render.php'`, `get_footer()`. Child theme override path documented in each file's doc comment.

| Template file | Block render.php | CPT condition |
|---|---|---|
| speaker-profile.php | speaker-profile/render.php | is_singular('speekr_speaker') |
| archive-talk.php | talks-list/render.php | is_post_type_archive('talks') |
| single-talk.php | single-talk/render.php | is_singular('talks') |
| archive-conference.php | conference-archive/render.php | is_post_type_archive('speekr_conference') |
| single-conference.php | conference-archive/render.php | is_singular('speekr_conference') |

**Shortcodes** (`inc/front/shortcodes.php`): 3 shortcodes using ob_start()+require+ob_get_clean() pattern (safe in shortcode context — no outer WP buffer). Each enqueues relevant CSS/JS assets.

| Shortcode | Attributes | Block |
|---|---|---|
| [speekr_profile] | layout, show_download | speaker-profile |
| [speekr_talks] | layout | talks-list |
| [speekr_map] | height | conference-map |

## Deviations from Plan

**1. [Rule 1 - Verification] talks-list limit attribute omitted from shortcode**
- **Found during:** Task 2 (block.json verification step)
- **Issue:** Plan template showed `limit` attribute and `[speekr_talks limit="<n>"]`, but talks-list/block.json has no `limit` attribute — only `layout`
- **Fix:** Omitted `limit` from shortcode_atts defaults and `$attributes` array; shortcode signature is `[speekr_talks layout="grid|list"]` only
- **Files modified:** inc/front/shortcodes.php

## Self-Check: PASSED

Files exist:
- inc/front/templates/classic/speaker-profile.php — FOUND
- inc/front/templates/classic/archive-talk.php — FOUND
- inc/front/templates/classic/single-talk.php — FOUND
- inc/front/templates/classic/archive-conference.php — FOUND
- inc/front/templates/classic/single-conference.php — FOUND
- inc/front/shortcodes.php — FOUND

Commits: d5e4807, b22a70a — verified in git log
