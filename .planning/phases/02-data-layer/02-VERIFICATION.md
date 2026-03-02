---
phase: 02-data-layer
verified: 2026-03-02T10:30:00Z
status: passed
score: 9/9 must-haves verified
re_verification: true
  previous_status: gaps_found
  previous_score: 7/9
  gaps_closed:
    - "Topics taxonomy registered on all bootstrap paths including REST API — /wp-json/wp/v2/speekr_topic will return 200"
    - "Topics taxonomy block editor sidebar panel can fetch and save terms via REST (REST path now registers taxonomy)"
    - "_speekr_conf_speakers array meta registered on speekr_conference with type=array, single=true, show_in_rest integer-items schema"
  gaps_remaining: []
  regressions: []
human_verification:
  - test: "Confirm /wp-json/wp/v2/speekr_topic returns 200 with taxonomy data"
    expected: "REST endpoint returns JSON with taxonomy terms list, not 404"
    why_human: "Requires live WordPress environment to make REST request"
  - test: "Confirm block editor Topics panel renders and saves terms on Talk edit screen"
    expected: "Topics panel appears in the right sidebar; adding/removing a term and saving the post persists the change"
    why_human: "Requires browser and live WordPress — panel appearance and REST term-save can only be observed in browser"
  - test: "Confirm saving a Talk via block editor does not wipe existing meta values"
    expected: "After saving a Talk in the block editor, speekr-media-links, speekr-conf, and speekr-summary meta retain their previous values"
    why_human: "Requires live WordPress environment and an existing Talk post with meta data"
  - test: "Confirm Speaker Profile block editor loads (not classic editor)"
    expected: "Editing a speekr_speaker post loads Gutenberg block editor"
    why_human: "Requires browser and live WordPress"
  - test: "Confirm Conference block editor loads (not classic editor)"
    expected: "Editing a speekr_conference post loads Gutenberg block editor"
    why_human: "Requires browser and live WordPress"
---

# Phase 2: Data Layer Verification Report

**Phase Goal:** All CPTs are visible to the block editor and REST API; every post meta field is registered and accessible via useEntityProp() in JavaScript; the Topics taxonomy is in place; no legacy save_post callback can silently wipe data
**Verified:** 2026-03-02T10:30:00Z
**Status:** passed
**Re-verification:** Yes — after gap closure plans 02-04 and 02-05

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | Speaker Profile CPT visible to block editor and REST API | VERIFIED | `inc/cpt/speaker-profile.php` line 35: `show_in_rest: true`; supports array includes `'editor'` and `'custom-fields'` (line 39); loaded in `includes()` at Speekr.php line 85 |
| 2 | All five Speaker Profile meta fields registered with show_in_rest and auth_callback | VERIFIED | `inc/cpt/speaker-profile.php` contains 5 `register_post_meta()` calls (lines 135, 156, 165, 174, 196); all have `show_in_rest`, `sanitize_callback`, and `auth_callback`; structured fields use expanded JSON schema |
| 3 | Conference CPT visible to block editor and REST API | VERIFIED | `inc/cpt/conferences.php` line 34: `show_in_rest: true`; supports array includes `'editor'` and `'custom-fields'` (line 39); loaded in `includes()` at Speekr.php line 86 |
| 4 | All six Conference meta fields registered with show_in_rest | VERIFIED | `inc/cpt/conferences.php` contains 6 `register_post_meta()` calls (5 original + Field 6 `_speekr_conf_speakers`); all have `show_in_rest`, `auth_callback`; field 6 uses type=array schema with integer items and `default=array()` |
| 5 | Talks CPT updated to support block editor and REST API | VERIFIED | `inc/common/custom-posts.php` line 47: `show_in_rest: true`; line 57: `'editor'` active; line 60: `'custom-fields'` active |
| 6 | Topics taxonomy appears in Talks block editor sidebar as a tag-like panel | VERIFIED | `inc/common/taxonomies.php` registers `speekr_topic` with `hierarchical: false`, `show_ui: true`, `show_in_rest: true`; loaded in `includes()` (line 84 of Speekr.php) — fires on all request paths including admin page loads where block editor runs |
| 7 | Topics queryable via REST API at /wp-json/wp/v2/speekr_topic | VERIFIED | `speekr_register_topics_taxonomy()` now lives in `inc/common/taxonomies.php` which is loaded via `Speekr::includes()` at line 84 — executes on every WordPress request including REST API (previously admin-only; gap now closed) |
| 8 | Block editor saves do NOT trigger speekr_save_mb and wipe meta | VERIFIED | `speekr_save_mb()` in `inc/admin/custom-meta-boxes.php` line 337: `if ( empty( $_POST ) ) { return; }` as first guard; guard order confirmed: `empty($_POST)` (337) -> `wp_is_post_autosave` (342) -> `wp_is_post_revision` (347) -> `_wpnonce` (351) -> `DOING_AUTOSAVE` (355) |
| 9 | Classic editor saves still save all meta correctly | VERIFIED | All 5 `update_post_meta()` calls present and unchanged (speekr-media-links, speekr-conf, speekr-summary, speekr-as-article, speekr-is-featured); existing guards retain `_wpnonce` and `DOING_AUTOSAVE` checks |

**Score:** 9/9 truths verified

### Required Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `inc/common/taxonomies.php` | speekr_register_topics_taxonomy() on common bootstrap path | VERIFIED | Exists; 44 lines; ABSPATH guard; complete function with `hierarchical: false`, `show_in_rest: true`, `apply_filters` hook, `speekr_get_cpt_slug()` call; `add_action('init', ...)` present; PHP lint clean |
| `inc/classes/Speekr.php` | require_once for taxonomies.php inside includes() | VERIFIED | Line 84: `require_once( SPEEKR_DIRNAME . '/inc/common/taxonomies.php' )` inside `includes()` method, between custom-posts.php (line 83) and speaker-profile.php (line 85); PHP lint clean |
| `inc/admin/custom-meta-boxes.php` | No speekr_register_topics_taxonomy() definition (moved out) | VERIFIED | Zero occurrences of `speekr_register_topics_taxonomy` in file (grep exits 1); PHP lint clean; speekr_save_mb() guards intact |
| `inc/cpt/conferences.php` | 6 register_post_meta() calls; _speekr_conf_speakers as type=array | VERIFIED | 6 `register_post_meta()` calls confirmed (grep -c = 6); `_speekr_conf_speakers` at line 105: `type=array`, `single=true`, `show_in_rest` with schema.items.type=integer, `default=array()`; exactly 1 `add_action` for `speekr_register_conference_meta`; PHP lint clean |
| `inc/cpt/speaker-profile.php` | Speaker Profile CPT + 5 meta registrations | VERIFIED | Unchanged from initial verification; 5 `register_post_meta()` calls with `show_in_rest`, `sanitize_callback`, `auth_callback` |

### Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `inc/classes/Speekr.php` | `inc/common/taxonomies.php` | `require_once` in `includes()` | WIRED | Line 84 in `includes()` method (not `includes_admin()`) — confirmed by grep output showing surrounding lines are other `includes()` entries |
| `inc/common/taxonomies.php` | WordPress REST API | `register_taxonomy('speekr_topic')` with `show_in_rest: true` on `init` hook | WIRED | Function definition at line 12, `add_action('init', ...)` at line 44; `show_in_rest: true` confirmed at line 35 of taxonomies.php |
| `inc/classes/Speekr.php` | `inc/cpt/conferences.php` | `require_once` in `includes()` | WIRED | Line 86 in `includes()` method |
| `inc/cpt/conferences.php` | WordPress REST API | `register_post_meta('speekr_conference', '_speekr_conf_speakers')` with show_in_rest array schema | WIRED | Line 105: full registration with type=array, single=true, show_in_rest schema present; `add_action('init', 'speekr_register_conference_meta')` at line 120 |
| `inc/classes/Speekr.php` | `inc/cpt/speaker-profile.php` | `require_once` in `includes()` | WIRED | Line 85 in `includes()` method |
| `inc/classes/Speekr.php` | `inc/admin/custom-meta-boxes.php` | `require_once` in `includes_admin()` | WIRED | Line 118 in `includes_admin()` — meta box UI correctly remains admin-only |
| `speekr_save_mb` | REST API save path | `empty($_POST)` early return guard | WIRED | Line 337: guard is first check in function; confirmed by grep showing correct order |

### Requirements Coverage

No REQUIREMENTS.md entries mapped to phase 02 were identified. All observable truths map directly to the phase goal statement.

### Anti-Patterns Found

None. No blockers, warnings, or notable issues found in any modified file.

| File | Line | Pattern | Severity | Impact |
|------|------|---------|----------|--------|
| — | — | None found | — | — |

### Human Verification Required

#### 1. REST API Taxonomy Endpoint

**Test:** With the plugin active, make a GET request to `/wp-json/wp/v2/speekr_topic`.
**Expected:** Returns 200 with JSON taxonomy data (not 404).
**Why human:** Requires a live WordPress environment.

#### 2. Block Editor Topics Panel — Render and Save

**Test:** Open a Talk post in the WordPress block editor. Check the right sidebar for a "Topics" panel. Add a topic term and save the post.
**Expected:** A tag-style panel labelled "Topics" appears in the document sidebar; after save, the term is persisted.
**Why human:** REST term-save operations require a live browser and WordPress environment.

#### 3. Block Editor Save Does Not Wipe Meta

**Test:** Open a Talk post that has existing values for speekr-media-links, speekr-conf, or speekr-summary. Save the post via the block editor. Then check those meta values.
**Expected:** All existing meta values are preserved after the block editor save.
**Why human:** Requires a live WordPress environment and an existing Talk post with meta data.

#### 4. Speaker Profile Block Editor

**Test:** Create or edit a Speaker Profile post (`speekr_speaker` post type).
**Expected:** Gutenberg block editor loads (not the classic editor fallback).
**Why human:** Requires browser and live WordPress.

#### 5. Conference Block Editor

**Test:** Create or edit a Conference post (`speekr_conference` post type).
**Expected:** Gutenberg block editor loads.
**Why human:** Requires browser and live WordPress.

### Re-Verification: Gap Closure Summary

**Both gaps from the initial verification are closed.**

**Gap 1 (plan 02-04): Topics taxonomy admin-only bootstrap path**

The `speekr_register_topics_taxonomy()` function was extracted from `inc/admin/custom-meta-boxes.php` into a new `inc/common/taxonomies.php` file. `Speekr::includes()` now loads `taxonomies.php` at line 84 (between `custom-posts.php` and `speaker-profile.php`). The function is defined exactly once in the codebase — confirmed by grep finding zero occurrences in `custom-meta-boxes.php` and two occurrences (definition + add_action) in `taxonomies.php` only. The taxonomy now registers on every WordPress request path, including REST API requests where `is_admin()` returns false. Truth #6 (block editor sidebar) upgrades from PARTIAL to VERIFIED. Truth #7 (REST endpoint) upgrades from FAILED to VERIFIED.

**Gap 2 (plan 02-05): Missing _speekr_conf_speakers meta field on Conference CPT**

This gap was not in the original VERIFICATION.md `gaps:` section — it was introduced as an additional gap-closure plan. The field `_speekr_conf_speakers` is now registered in `speekr_register_conference_meta()` as Field 6 with `type=array`, `single=true`, `show_in_rest` schema specifying `items.type=integer`, and `default=array()`. The Conference CPT now has 6 registered meta fields (grep -c = 6). The original 5 fields are unmodified. This enables Phase 3 block editor code to call `useEntityProp('postType', 'speekr_conference', 'meta')` and access `_speekr_conf_speakers` as a readable/writable integer array. Truth #4 updated to reflect 6 fields.

**No regressions detected.** All 7 originally-verified truths remain intact: CPT registrations unchanged, meta field registrations unchanged, save guards in `speekr_save_mb()` unchanged, bootstrap wiring unchanged.

---

_Verified: 2026-03-02T10:30:00Z_
_Verifier: Claude (gsd-verifier)_
