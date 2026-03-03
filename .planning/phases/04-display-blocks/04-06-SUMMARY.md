---
phase: 04-display-blocks
plan: "06"
status: complete
completed: 2026-03-03
---

# Plan 04-06 Summary: Phase 4 Verification + Gap Fixes

## What Was Done

### Task 1 — Build + lint

- `npm run build` — webpack compiled successfully (5142 ms)
- All 5 render.php files pass PHP lint with no syntax errors
- All 8 block directories present in `build/blocks/`
- No WordPress debug.log (debug logging off; no errors surfaced during live testing)

### Gap fixes applied during live testing (not in original plan scope)

Several issues were discovered and fixed before human verification could pass:

**Fix 1 — Talks List 500 (Cannot redeclare)**
`src/blocks/talks-list/render.php`: Named function `speekr_resolve_card_media()` was
redeclared on every block render in the same request → PHP fatal. Converted to
`$resolve_card_media = static function()` closure.

**Fix 2 — CPT singular pages returning 404**
`inc/classes/Speekr.php`: Rewrite rules never flushed after `speekr_conference` and
`speekr_speaker` CPTs were registered in Phase 3. Added `maybe_flush_rewrite_rules()`
(version-based, runs once per version at `init` priority 999) and forced flush + option
delete in `install()`.

**Fix 3 — PHP fatal on null $post in Speekr_Templates_Loader**
`inc/classes/Speekr_Templates_Loader.php`: `$post->ID` accessed without null guard in
`register_plugin_templates()` and `add_template_filter()`. Added `if ( ! $post ) return`
guards. Triggered on 404 pages and some AJAX/REST contexts.

**Fix 4 — All display blocks rendering blank (root cause: ob_start nesting)**
`src/blocks/talks-list/render.php`, `src/blocks/speaker-profile/render.php`,
`src/blocks/single-talk/render.php`, `src/blocks/conference-archive/render.php`:
WordPress wraps render.php includes in its own outer `ob_start()`. The blocks had inner
`ob_start()` + `return ob_get_clean()` patterns which captured all output into an inner
buffer, leaving WordPress's outer buffer empty → blocks rendered as empty strings.
Conference Map (which echoed directly) was the only working block, confirming the diagnosis.
Fixed by removing all inner ob_start/ob_get_clean wrappers and converting
`return '<string>'` early exits to `echo '<string>'; return;`.

**Fix 5 — speakerId / talkId attribute + picker (added to speaker-profile and single-talk)**
`src/blocks/speaker-profile/block.json`, `src/blocks/speaker-profile/edit.js`,
`src/blocks/speaker-profile/render.php`: Added `speakerId` integer attribute with debounced
REST search picker in editor sidebar. render.php uses smart resolution: explicit speakerId
→ talk-page `_speekr_talk_speaker` meta → single-speaker auto-select → admin hint.

`src/blocks/single-talk/block.json`, `src/blocks/single-talk/edit.js`,
`src/blocks/single-talk/render.php`: Added `talkId` integer attribute with talk picker.
render.php: explicit talkId → current talks CPT page → placeholder message.

**Fix 6 — _speekr_talk_speaker meta + Speaker panel in talk-meta block**
`inc/blocks/blocks.php`: Registered `_speekr_talk_speaker` integer meta (show_in_rest: true).
`src/blocks/talk-meta/edit.js`: Added Panel 5 "Speaker" with debounced search against
`/wp/v2/speekr_speaker`, using the same pattern as conference-meta.

**Fix 7 — talks CPT file location**
`inc/common/custom-posts.php` → `inc/cpt/talks.php` (consistent with all other CPT files).
`inc/classes/Speekr.php` include path updated.

**Fix 8 — YouTube URL parsing warning**
`inc/functions/helpers.php`: `speekr_get_youtube_id()` used `explode('?v=', $url)[1]`
causing "Undefined array key 1" for YouTube URLs not using `?v=` format. Replaced with
regex covering youtu.be, ?v=, /embed/, /shorts/, /v/.

### Task 2 — Human verification

User confirmed live: "Ok, now it works." All 5 blocks render correctly on normal WordPress
pages after the ob_start fix. No PHP errors reported.

## Files Modified (gap fixes)

| File | Change |
|------|--------|
| `src/blocks/talks-list/render.php` | Static closure; removed ob_start |
| `src/blocks/speaker-profile/render.php` | speakerId resolution; removed ob_start |
| `src/blocks/speaker-profile/edit.js` | speakerId picker panel |
| `src/blocks/speaker-profile/block.json` | speakerId attribute |
| `src/blocks/single-talk/render.php` | talkId resolution; removed ob_start |
| `src/blocks/single-talk/edit.js` | talkId picker panel |
| `src/blocks/single-talk/block.json` | talkId attribute |
| `src/blocks/conference-archive/render.php` | Removed ob_start |
| `src/blocks/talk-meta/edit.js` | Speaker panel (Panel 5) |
| `inc/blocks/blocks.php` | Register _speekr_talk_speaker meta |
| `inc/classes/Speekr.php` | maybe_flush_rewrite_rules(); talks CPT path |
| `inc/classes/Speekr_Templates_Loader.php` | Null guards for $post |
| `inc/functions/helpers.php` | YouTube ID regex |
| `inc/cpt/talks.php` | New file (from custom-posts.php) |
| `inc/common/custom-posts.php` | Deleted |

## Key Decision

**ob_start inside render.php is incompatible with WordPress block rendering.**
WordPress wraps render.php in its own outer `ob_start()`. Any inner `ob_start()` +
`return ob_get_clean()` pattern inside render.php will leave the outer buffer empty.
All render.php files must echo directly (or use `?>...html...<?php` interleaving).
