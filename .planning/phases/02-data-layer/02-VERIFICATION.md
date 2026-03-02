---
phase: 02-data-layer
verified: 2026-03-02T12:00:00Z
status: gaps_found
score: 7/9 must-haves verified
re_verification: false
gaps:
  - truth: "Topics are queryable via the REST API at /wp-json/wp/v2/speekr_topic"
    status: failed
    reason: "speekr_register_topics_taxonomy() is defined in inc/admin/custom-meta-boxes.php, which is loaded only inside Speekr::includes_admin(). WordPress REST API requests go through the front-end bootstrap (is_admin() returns false for /wp-json/ requests), so the taxonomy is never registered on REST requests. The /wp-json/wp/v2/speekr_topic endpoint will return 404."
    artifacts:
      - path: "inc/admin/custom-meta-boxes.php"
        issue: "speekr_register_topics_taxonomy() is substantively correct but loaded in the wrong bootstrap path (includes_admin only)"
      - path: "inc/classes/Speekr.php"
        issue: "custom-meta-boxes.php is require_once'd only inside includes_admin() at line 117, not inside includes() where it would run on all requests including REST"
    missing:
      - "Move speekr_register_topics_taxonomy() registration to a file loaded in Speekr::includes() (not includes_admin()), so the taxonomy is registered on REST API requests. Options: (a) move the function to inc/admin/custom-meta-boxes.php but add a separate require_once in includes() for a new taxonomy-only file, or (b) add the taxonomy registration to an existing file already in includes() (e.g. inc/common/custom-posts.php), or (c) move the taxonomy function to a new inc/common/taxonomies.php file loaded in includes()"

  - truth: "Topics taxonomy appears in the Talks block editor sidebar as a standard tag-like panel"
    status: partial
    reason: "The taxonomy IS registered on admin page loads (block editor runs on a wp-admin page, so includes_admin() fires and the taxonomy is registered). The block editor sidebar panel will appear when editing a Talk post. However, the block editor makes subsequent REST API calls to /wp-json/wp/v2/speekr_topic to fetch/save terms — those calls will fail with 404 because the taxonomy is not registered on REST requests. The panel appears but term saving via the sidebar will be broken."
    artifacts:
      - path: "inc/admin/custom-meta-boxes.php"
        issue: "Taxonomy registration correct but admin-only load path prevents REST API term operations from working"
    missing:
      - "Same fix as the truth above: move taxonomy registration to a file loaded in includes() so it fires on both admin page loads AND REST API requests"
human_verification:
  - test: "Confirm block editor Topics panel renders on Talk edit screen"
    expected: "Topics panel appears in the right sidebar when editing any Talk post in the block editor"
    why_human: "Admin page load (not REST), so taxonomy IS registered — this likely works already; needs visual confirmation"
  - test: "Confirm /wp-json/wp/v2/speekr_topic returns taxonomy data (not 404)"
    expected: "REST endpoint returns JSON with taxonomy terms list"
    why_human: "Requires live WordPress environment to make REST request"
  - test: "Confirm saving a Talk via block editor does not wipe existing meta values"
    expected: "After saving a Talk in the block editor, speekr-media-links, speekr-conf, and speekr-summary meta retain their previous values"
    why_human: "Requires live WordPress environment and actual post save operation"
  - test: "Confirm Speaker Profile block editor loads (not classic editor)"
    expected: "Editing a speekr_speaker post loads Gutenberg block editor"
    why_human: "Requires browser/WordPress environment"
  - test: "Confirm Conference block editor loads (not classic editor)"
    expected: "Editing a speekr_conference post loads Gutenberg block editor"
    why_human: "Requires browser/WordPress environment"
---

# Phase 2: Data Layer Verification Report

**Phase Goal:** All CPTs are visible to the block editor and REST API; every post meta field is registered and accessible via useEntityProp() in JavaScript; the Topics taxonomy is in place; no legacy save_post callback can silently wipe data
**Verified:** 2026-03-02T12:00:00Z
**Status:** gaps_found
**Re-verification:** No — initial verification

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | Speaker Profile CPT visible to block editor and REST API | VERIFIED | `inc/cpt/speaker-profile.php` line 35: `'show_in_rest' => true`; supports includes `'editor'` (line 39) and `'custom-fields'` (line 39); loaded in `includes()` at Speekr.php line 84 |
| 2 | All five Speaker Profile meta fields registered with show_in_rest and auth_callback | VERIFIED | `inc/cpt/speaker-profile.php` contains 5 `register_post_meta()` calls (lines 135, 156, 165, 174, 196); all have `show_in_rest`, `sanitize_callback`, and `auth_callback`; structured fields use expanded JSON schema |
| 3 | Conference CPT visible to block editor and REST API | VERIFIED | `inc/cpt/conferences.php` line 34: `'show_in_rest' => true`; supports array includes `'editor'` and `'custom-fields'` (line 39); loaded in `includes()` at Speekr.php line 85 |
| 4 | All five Conference meta fields registered with show_in_rest | VERIFIED | `inc/cpt/conferences.php` contains 5 `register_post_meta()` calls (lines 60, 69, 78, 87, 96); all have `show_in_rest: true`, `sanitize_callback`, `auth_callback` |
| 5 | Talks CPT updated to support block editor and REST API | VERIFIED | `inc/common/custom-posts.php` line 47: `'show_in_rest' => true`; line 57: `'editor'` active (uncommented); line 60: `'custom-fields'` active (uncommented) |
| 6 | Topics taxonomy appears in Talks block editor sidebar | PARTIAL | `speekr_register_topics_taxonomy()` defined in `inc/admin/custom-meta-boxes.php` with `show_in_rest: true` and `hierarchical: false` — correct. But file loaded in `includes_admin()` only. Sidebar panel appears on admin page load but REST term operations fail. |
| 7 | Topics queryable via REST API at /wp-json/wp/v2/speekr_topic | FAILED | `custom-meta-boxes.php` is only loaded via `includes_admin()` (Speekr.php line 117). REST API requests use front-end bootstrap — `is_admin()` returns false, `includes_admin()` never runs, taxonomy never registered, endpoint returns 404. |
| 8 | Block editor saves do NOT trigger speekr_save_mb and wipe meta | VERIFIED | `speekr_save_mb()` in `inc/admin/custom-meta-boxes.php` lines 377-379: `if ( empty( $_POST ) ) { return; }` as first guard. Guard order confirmed: `empty($_POST)` (377) → `wp_is_post_autosave` (382) → `wp_is_post_revision` (387) → `_wpnonce` (391) → `DOING_AUTOSAVE` (395) |
| 9 | Classic editor saves still save all meta correctly | VERIFIED | All 5 `update_post_meta()` calls present and unchanged (speekr-media-links, speekr-conf, speekr-summary, speekr-as-article, speekr-is-featured); existing guards retain `_wpnonce` and `DOING_AUTOSAVE` checks |

**Score:** 7/9 truths verified (1 FAILED, 1 PARTIAL)

### Required Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `inc/cpt/speaker-profile.php` | Speaker Profile CPT + 5 meta registrations | VERIFIED | Exists; 216 lines; `register_post_type('speekr_speaker')` with `show_in_rest: true`; 5 `register_post_meta()` calls; 3 sanitize functions; no PHP syntax errors |
| `inc/cpt/conferences.php` | Conferences CPT + 5 meta registrations | VERIFIED | Exists; 105 lines; `register_post_type('speekr_conference')` with `show_in_rest: true`; 5 `register_post_meta()` calls; no PHP syntax errors |
| `inc/classes/Speekr.php` | Bootstrap loading of both CPT files in includes() | VERIFIED | Lines 84-85: `require_once` for speaker-profile.php and conferences.php inside `includes()` method (not `includes_admin()`); no PHP syntax errors |
| `inc/common/custom-posts.php` | Talks CPT with show_in_rest + editor + custom-fields | VERIFIED | Line 47: `show_in_rest: true`; lines 57, 60: `'editor'` and `'custom-fields'` active (uncommented); no PHP syntax errors |
| `inc/admin/custom-meta-boxes.php` | Topics taxonomy + gated speekr_save_mb | PARTIAL | Topics taxonomy function is correct and substantive (lines 12-44); save gate is correct (lines 375-397). Critical issue: file loaded in `includes_admin()` only — taxonomy not registered on REST requests |

### Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `inc/classes/Speekr.php` | `inc/cpt/speaker-profile.php` | `require_once` in `includes()` | WIRED | Line 84 in `includes()` method |
| `inc/classes/Speekr.php` | `inc/cpt/conferences.php` | `require_once` in `includes()` | WIRED | Line 85 in `includes()` method |
| `inc/cpt/speaker-profile.php` | WordPress REST API | `show_in_rest: true` + meta schemas | WIRED | Line 35: CPT `show_in_rest: true`; all 5 meta fields have `show_in_rest` with appropriate schemas |
| `inc/cpt/conferences.php` | WordPress REST API | `show_in_rest: true` + meta schemas | WIRED | Line 34: CPT `show_in_rest: true`; all 5 meta fields have `show_in_rest: true` |
| `inc/common/custom-posts.php` | WordPress REST API | `show_in_rest: true` added to Talks CPT | WIRED | Line 47 confirms `show_in_rest: true` is active |
| `inc/admin/custom-meta-boxes.php` | WordPress block editor | `register_taxonomy` with `show_in_rest: true` | PARTIAL | Taxonomy registration is correct but admin-only load means REST API cannot see the taxonomy |
| `speekr_save_mb` | REST API save path | `empty($_POST)` early return guard | WIRED | Line 377: guard is first check in function, confirmed by grep showing correct order |

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
|------|------|---------|----------|--------|
| `inc/admin/custom-meta-boxes.php` | 117 in Speekr.php | Taxonomy registration in admin-only load path | Blocker | `/wp-json/wp/v2/speekr_topic` returns 404; block editor cannot save/read Topics terms via REST |

### Human Verification Required

#### 1. Block Editor Topics Sidebar Panel

**Test:** Open a Talk post in the WordPress block editor. Check the right sidebar for a "Topics" panel.
**Expected:** A tag-style panel labelled "Topics" appears in the document sidebar, allowing terms to be added/removed.
**Why human:** Admin page load (where `WP_ADMIN=true`) registers the taxonomy, so the panel may appear even with the REST gap. Needs visual confirmation.

#### 2. REST API Taxonomy Endpoint

**Test:** With the plugin active, make a GET request to `/wp-json/wp/v2/speekr_topic`.
**Expected:** Currently expected to return 404 (not 200) due to the identified gap. After gap is fixed, should return 200 with taxonomy data.
**Why human:** Requires a live WordPress environment.

#### 3. Block Editor Save Does Not Wipe Meta

**Test:** Open a Talk post that has existing values for speekr-media-links, speekr-conf, or speekr-summary. Save the post via the block editor (Gutenberg). Then check those meta values.
**Expected:** All existing meta values are preserved after the block editor save.
**Why human:** Requires live WordPress environment and an existing Talk post with meta data.

#### 4. Speaker Profile Block Editor

**Test:** Create or edit a Speaker Profile post (`speekr_speaker` post type).
**Expected:** Gutenberg block editor loads (not the classic editor fallback).
**Why human:** Requires browser and live WordPress.

#### 5. Conference Block Editor

**Test:** Create or edit a Conference post (`speekr_conference` post type).
**Expected:** Gutenberg block editor loads.
**Why human:** Requires browser and live WordPress.

### Gaps Summary

**One blocker gap was found:**

The Topics taxonomy (`speekr_topic`) is registered inside `speekr_register_topics_taxonomy()` which lives in `inc/admin/custom-meta-boxes.php`. This file is loaded exclusively via `Speekr::includes_admin()` (Speekr.php line 117). WordPress REST API requests (`/wp-json/*`) use the front-end bootstrap path, where `is_admin()` returns false and `includes_admin()` never runs. As a result, the taxonomy is never registered during REST API requests, meaning:

- `/wp-json/wp/v2/speekr_topic` returns 404
- Block editor REST calls to fetch/save Topics terms fail
- Phase 3 blocks cannot use `useEntityProp()` for Topics terms

The taxonomy function itself is correctly written (`hierarchical: false`, `show_in_rest: true`, uses `speekr_get_cpt_slug()` not hardcoded 'talks'). The only fix needed is loading it via `includes()` instead of `includes_admin()`. The simplest remediation is extracting the `speekr_register_topics_taxonomy()` function into a file that is already loaded in `includes()` (e.g. `inc/common/custom-posts.php`) or a new `inc/common/taxonomies.php` file added to `includes()`.

The sidebar panel visibility (truth #6) is marked PARTIAL rather than FAILED because the block editor itself runs on a wp-admin page (where `includes_admin()` fires), so the taxonomy is registered at that point and the sidebar panel will appear. However, subsequent REST API calls from the block editor to list/save terms against `/wp-json/wp/v2/speekr_topic` will fail.

All other phase deliverables are fully implemented and wired correctly.

---

_Verified: 2026-03-02T12:00:00Z_
_Verifier: Claude (gsd-verifier)_
