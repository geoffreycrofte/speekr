# Phase 2: Data Layer - Context

**Gathered:** 2026-03-01
**Status:** Ready for planning

<domain>
## Phase Boundary

Register all CPTs (Speaker Profile, Conferences, Talks) with `show_in_rest: true` and `custom-fields` in supports; register every post meta field via `register_post_meta()` with `show_in_rest: true`; add Topics taxonomy on the Talks CPT; audit and gate all existing `save_post` callbacks against REST double-fire. No block UI in this phase — data layer only.

</domain>

<decisions>
## Implementation Decisions

### Headshots field (`_speekr_headshots`)
- Unlimited gallery — speaker can upload as many headshots as they want
- Ordered array — first item is treated as the primary/default headshot by display blocks
- Each entry: `{ "id": <attachment_id>, "label": "<optional text>" }` — label is optional (e.g., "B&W", "High-res", "Candid")
- Storage: attachment ID only (URL resolved at render time via `wp_get_attachment_image_src()`); more robust if site URL changes
- Stored as a JSON array in a single `_speekr_headshots` meta field with `type: array` schema in `register_post_meta()`

### Social links field (`_speekr_social_links`)
- Hybrid model: fixed known platforms + open-ended "Other" entries
- Fixed platforms in v1: LinkedIn, Facebook, Instagram, Bluesky, Mastodon, X (Twitter), GitHub, Personal website
- Each entry: `{ "platform": "<key_or_other>", "url": "https://...", "label": "<display label>" }` — label is always present (defaults to platform name for known platforms, required free-text for Other)
- Ordered array — speaker controls the display sequence
- Stored as a JSON array in a single `_speekr_social_links` meta field

### Rider field (`_speekr_rider`)
- Structured fields — not plain text or rich text
- Four categories in v1: AV/tech requirements, Travel & accommodation, Dietary restrictions, Accessibility needs
- Speaker fills in their own rider (not organizer-managed)
- Storage: JSON object in a single `_speekr_rider` meta field — `{ "av": "...", "travel": "...", "dietary": "...", "accessibility": "..." }` — cleaner than separate meta keys, all rider data travels together
- Each value is a plain text string (speaker writes prose per category)

### Topics taxonomy
- Flat tag-like taxonomy (not hierarchical) — simpler for a speaker portfolio; topic filtering in the display layer works cleanly with flat terms
- Registered on the Talks CPT with `show_in_rest: true`
- Shared site-wide vocabulary — all speakers on the same WP install share terms
- Any user who can edit a talk can create new terms (standard WP tag behavior — `capabilities` not restricted beyond edit_posts)
- Appears in the block editor as a standard sidebar panel (like Tags) — Phase 3 editor block will sit alongside it, not replace it

### save_post callback gating (02-03)
- Gate all existing `save_post` callbacks in `inc/admin/custom-meta-boxes.php` with: `wp_is_post_autosave()`, `wp_is_post_revision()`, and `!empty($_POST)` checks
- Purpose: prevent REST API saves (which don't send $_POST) from triggering legacy meta box writes that could wipe registered meta
- Existing data is preserved — the gate prevents double-write, not data deletion

### Claude's Discretion
- Exact `register_post_meta()` schema shapes for array fields (WP REST `show_in_rest` schema for nested objects)
- Whether to use `sanitize_callback` functions or rely on WP's built-in sanitization for each field type
- Order of file loading via `require_once` in `inc/classes/Speekr.php`
- Auth callback implementation (standard logged-in check vs. post-author check)

</decisions>

<specifics>
## Specific Ideas

- Social links platform list came directly from the user: LinkedIn, Facebook, Instagram, Bluesky, Mastodon, X, GitHub, Personal website — these are the exact v1 platforms
- Rider is self-service: speaker describes their own requirements, not filled in by event organizers
- Headshots: first item in the array = primary headshot (display blocks should use index 0 as the default)

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 02-data-layer*
*Context gathered: 2026-03-01*
