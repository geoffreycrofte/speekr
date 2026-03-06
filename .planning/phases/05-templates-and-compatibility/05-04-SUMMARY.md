---
phase: 05-templates-and-compatibility
plan: "04"
subsystem: bootstrap
tags: [wiring, human-verify, has_archive, templates, shortcodes, hooks]
dependency_graph:
  requires: [05-01, 05-02, 05-03]
  provides: [all-compatibility-layers-active]
  affects: [inc/classes/Speekr.php, inc/cpt/talks.php]
tech_stack:
  added: []
  patterns: [includes-wiring, version-based-rewrite-flush]
key_files:
  created: []
  modified:
    - inc/classes/Speekr.php
    - inc/cpt/talks.php
decisions:
  - "templates.php loaded in includes() (all requests) so Site Editor can register templates on admin requests"
  - "shortcodes.php loaded in includes() (all requests) per CONTEXT.md locked decision"
  - "has_archive => apply_filters('speekr_talks_has_archive', true) on Talks CPT — developer-overridable"
  - "speekr-main CSS enqueued on all Speekr CPT pages (talks, conference, speaker)"
  - "speaker-profile block: is_singular('speekr_speaker') auto-resolution + headshot slideshow"
  - "showRider attribute defaults false; rider hidden unless showRider=true or show_rider='1' shortcode param"
  - "single-conference.php: banner + header meta dl + talk cards + speaker wall design"
metrics:
  duration: ~5 min (extended — multiple rounds of fixes during UAT)
  completed: 2026-03-06
  tasks_completed: 2
  files_created: 0
  files_modified: 5
---

# Phase 5 Plan 04: Wiring + Human Verify Summary

templates.php and shortcodes.php wired into Speekr::includes(); Talks CPT updated with has_archive; all Phase 5 compatibility layers verified across FSE (block theme), classic theme, shortcodes, and developer hooks. Several bug fixes landed during UAT rounds before final approval.

## Tasks Completed

| Task | Name | Result | Notes |
|------|------|--------|-------|
| 1 | Wire templates.php + shortcodes.php + has_archive | Done | All 3 files modified, PHP lint clean |
| 2 | Human verify FSE, classic, shortcodes, hooks | Approved | Multiple UAT rounds; fixes applied before approval |

## UAT Fixes Applied

| Commit | Fix |
|--------|-----|
| 9d576bb | Speekr_Templates_Loader::$template_dir property typo |
| 5ecb37f | shortcodes.php moved to includes_front() then back to includes() |
| 22410cc | Site Editor: removed wp-edit-post CSS dep; added wp_is_block_theme() guard to speekr_template_include |
| 3f9b885 | enqueues.php: WP_Post_Type::$ID warning on CPT archive pages |
| a0d26e9 | Hide Talk as Content meta box from Gutenberg; invert speekr-as-article logic |
| 8a26a4b | Fix checkbox label wording for speekr-as-article |
| 92cb9f3 | Add links to all talk cards; reorder media image priority |
| c2cc9d7 | Round 2: headshot slideshow, rider toggle, conference single redesign, enqueues CPT fix |
| 1ced9df | Hide Read full article on talk's own page |
| 0d297ce | speekr-main CSS on conference + speaker CPT pages |
