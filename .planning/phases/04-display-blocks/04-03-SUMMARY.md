---
phase: "04-display-blocks"
plan: "03"
subsystem: "frontend-blocks"
tags: ["dynamic-block", "talks", "php-render", "vanilla-js", "client-filter", "taxonomy"]
dependency_graph:
  requires: ["04-01", "speekr_topic taxonomy (02-03)", "talks CPT (02-01)", "speekr_conference CPT (02-02)"]
  provides: ["speekr/talks-list block", "client-side pill filter", "talk card HTML with media priority"]
  affects: ["any page/template using speekr/talks-list"]
tech_stack:
  added: []
  patterns:
    - "WP_Query with rewind_posts() for two-pass topic collection then card rendering"
    - "viewScript pattern — view.js auto-enqueued only when block is on the page"
    - "data-topics attribute on cards matched against pill data-topic for client filter"
    - "Vimeo thumbnail via direct oEmbed JSON fetch (wp_remote_get, 5s timeout)"
    - "container-type: inline-size for responsive grid without media queries"
    - "@use 'sass:color' Dart Sass 3.x-compatible SCSS"
key_files:
  created:
    - src/blocks/talks-list/block.json
    - src/blocks/talks-list/index.js
    - src/blocks/talks-list/edit.js
    - src/blocks/talks-list/render.php
    - src/blocks/talks-list/view.js
    - src/blocks/talks-list/style.scss
    - assets/img/placeholder-talk.svg
    - build/blocks/talks-list/block.json
    - build/blocks/talks-list/index.js
    - build/blocks/talks-list/view.js
    - build/blocks/talks-list/style-index.css
  modified: []
decisions:
  - "@use 'sass:color' import included in style.scss to follow Dart Sass 3.x convention established in Plan 02, even though darken() is not used — keeps SCSS future-proof"
  - "Two-pass WP_Query approach: first pass collects all unique topics and builds talk_topic_map, then rewind_posts() for card rendering — avoids running wp_get_post_terms() twice per post"
  - "speekr_resolve_card_media() defined as a named function inside render.php (not anonymous) for clarity; function name is unique per plugin convention"
  - "Vimeo thumbnail fetched live via wp_remote_get (5s timeout) rather than caching in a transient — acceptable for MVP; can add transient caching in Phase 5 if performance is a concern"
metrics:
  duration: "~3 min"
  completed: "2026-03-02"
  tasks_completed: 2
  files_created: 11
---

# Phase 4 Plan 03: Talks List Dynamic Block Summary

**One-liner:** Talks List dynamic block with PHP render (media priority chain, topic data attrs, conference meta), vanilla JS pill filter, and responsive grid/list CSS.

## Tasks Completed

| # | Name | Commit | Key files |
|---|------|--------|-----------|
| 1 | Talks List block source — render.php, block.json, index.js, edit.js, style.scss | 89f7762 | src/blocks/talks-list/{block.json,index.js,edit.js,render.php,style.scss}, assets/img/placeholder-talk.svg |
| 2 | Talks List view.js — vanilla JS pill filter | 82e7114 | src/blocks/talks-list/view.js, build/blocks/talks-list/* |

## What Was Built

The `speekr/talks-list` dynamic block renders all published Talks via `WP_Query` with a two-pass strategy: the first pass collects unique `speekr_topic` terms and builds a `$talk_topic_map`, then `rewind_posts()` iterates for card HTML output. Each card:

- Resolves media via a priority chain: YouTube thumbnail > Vimeo oEmbed thumbnail > slide link (SpeakerDeck / Slides.com / Slideshare) > featured image > SVG placeholder
- Sets `data-topics="slug1,slug2"` for client-side filtering
- Shows conference date/location from the linked `speekr_conference` CPT (`_speekr_conf_talk_ref`) with a legacy fallback to the `speekr-conf` meta object
- Renders a "Read more" button only when `speekr-as-article === 'on'`

The filter bar renders horizontal pill buttons (one per unique topic + "All") using `wp_get_post_terms`. The `view.js` viewScript handles filtering entirely client-side by toggling `hidden` on card `<article>` elements.

CSS uses `container-type: inline-size` for responsive grid (3 → 2 → 1 column) and list (horizontal card with 200px media column) layouts via `@container` queries.

## Verification Results

- `/Applications/MAMP/bin/php/php8.2.0/bin/php -l src/blocks/talks-list/render.php` — No syntax errors
- `npm run build` — exits 0 (webpack 5.105.3 compiled successfully in 1046 ms)
- `build/blocks/talks-list/` — block.json, index.js, view.js, style-index.css, asset manifests present
- `assets/img/placeholder-talk.svg` — exists
- `speekr-as-article` guard: `'on' === $as_article` in render.php — confirmed

## Deviations from Plan

None — plan executed exactly as written.

## Self-Check: PASSED

- src/blocks/talks-list/block.json — FOUND
- src/blocks/talks-list/render.php — FOUND
- src/blocks/talks-list/view.js — FOUND
- build/blocks/talks-list/view.js — FOUND
- build/blocks/talks-list/style-index.css — FOUND
- assets/img/placeholder-talk.svg — FOUND
- Commits 89f7762, 82e7114 — FOUND
