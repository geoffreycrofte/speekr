# Phase 3: Editor Blocks - Context

**Gathered:** 2026-03-02
**Status:** Ready for planning

<domain>
## Phase Boundary

Build native Gutenberg block editor sidebar panels (`PluginDocumentSettingPanel`) for three CPTs — Talks, Conferences, and Speaker Profile — so all metadata can be entered and edited directly in the block editor. Classic meta boxes for Talks remain functional for classic editor users. No display-facing blocks in this phase — editor UI only.

</domain>

<decisions>
## Implementation Decisions

### Legacy meta box coexistence (Talks CPT)
- Classic meta boxes for Talks (summary, media links, conference reference) **stay intact and functional in the classic editor**
- In the block editor: hide Talks meta boxes using `remove_meta_box()` inside a hook that checks `get_current_screen()->is_block_editor()` (or equivalent) — so panels are the only UI in Gutenberg
- `speekr_save_mb` callback left **fully intact** as the classic editor save path — block editor panels write via REST, classic editor writes via save_post. Two save paths, one set of meta keys.
- Speaker Profile and Conference CPTs: net new — no existing meta boxes to worry about

### Relational field UI
- **Conference → Talk reference** (`_speekr_conf_talk_ref`): search-as-you-type — text input that queries published Talks via REST as the user types, shows matching titles, user clicks to select
- **Conference → Speaker references** (`_speekr_conf_speakers`): add-one-at-a-time — search field queries Speaker Profile posts via REST, user picks one, it appears in a list below with an X to remove. Repeatable. Like a tag/mention UI.
- **Talk edit screen — reverse conference reference**: a read-only panel showing "Appears in: [Conference Name]" derived by querying Conference posts that reference this Talk's ID via REST. Read-only — the relationship is set on the Conference side.
- **Talk → Conference explicit field** (`speekr-conf`): Claude's discretion — keep the existing meta key as an explicit editable field on the Talk panel (matches legacy data model; avoids requiring a Conference post to set this)

### Headshots panel UX (Speaker Profile)
- **Add**: standard WordPress media library button — opens WP media modal, supports upload + select from existing library
- **Display**: small thumbnails in a grid/row, each with an X (remove) button
- **Reorder**: drag-to-reorder — first item in the array = primary headshot; dragging changes the order
- **Labels**: editable text input per headshot directly in the panel (e.g., "B&W", "High-res") — speaker can label each one inline

### Bio and text field formatting (Speaker Profile)
- **Short bio**: `RichText` with inline formats only — bold, italic, links. No block-level elements (no headings, no lists). Used for program intros and meta descriptions.
- **Long bio**: `RichText` with full formatting — bold, italic, links, and lists (unordered). Multi-paragraph supported.
- **Rider fields** (AV/tech, travel, dietary, accessibility): `RichText` with inline formats (bold, italic) per category — speaker can emphasize a key requirement

### Social links panel UI (Speaker Profile)
- **Add flow**: dropdown to select platform (LinkedIn, Facebook, Instagram, Bluesky, Mastodon, X, GitHub, Personal website, Other) → URL input field appears → "Add" button confirms. Repeatable.
- **Display**: ordered list of added links, each with platform name/icon and X to remove. Speaker controls display order.
- **"Other" platform**: shows an additional "Label" text input alongside the URL field

### Claude's Discretion
- Exact component library choices (`@wordpress/components` — TextControl, SelectControl, Button, etc.)
- Drag-and-drop implementation for headshots (DnD library vs. custom mouse events)
- REST query debounce timing for search-as-you-type fields
- Panel collapse/expand defaults
- Whether to combine all Talk fields into one panel or split across multiple `PluginDocumentSettingPanel` panels by category
- Auth/permission handling at the JS layer (follow what registered meta auth_callbacks already enforce at REST)

</decisions>

<specifics>
## Specific Ideas

- Headshots: first item in the array is always the primary — display blocks use index 0. The drag-to-reorder interface should make this obvious (e.g., "Primary" label on the first item).
- Social links: the 8 fixed platforms are the exact v1 list decided in Phase 2: LinkedIn, Facebook, Instagram, Bluesky, Mastodon, X (Twitter), GitHub, Personal website.
- Rider: each category is its own panel section or collapsible — AV/tech, Travel & accommodation, Dietary restrictions, Accessibility needs. Speaker writes prose per category with inline formatting.
- Talk edit screen: the read-only "Appears in conference" panel is informational only — no editing. If a Talk appears in multiple conferences, list all of them.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 03-editor-blocks*
*Context gathered: 2026-03-02*
