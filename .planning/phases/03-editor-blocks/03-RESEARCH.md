# Phase 3: Editor Blocks - Research

**Researched:** 2026-03-02
**Domain:** WordPress Gutenberg block editor — PluginDocumentSettingPanel, useEntityProp, @wordpress/components, MediaUpload, REST search
**Confidence:** HIGH

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

**Legacy meta box coexistence (Talks CPT)**
- Classic meta boxes for Talks (summary, media links, conference reference) stay intact and functional in the classic editor
- In the block editor: hide Talks meta boxes using `remove_meta_box()` inside a hook that checks `get_current_screen()->is_block_editor()` (or equivalent) — so panels are the only UI in Gutenberg
- `speekr_save_mb` callback left fully intact as the classic editor save path — block editor panels write via REST, classic editor writes via save_post. Two save paths, one set of meta keys.
- Speaker Profile and Conference CPTs: net new — no existing meta boxes to worry about

**Relational field UI**
- Conference → Talk reference (`_speekr_conf_talk_ref`): search-as-you-type — text input that queries published Talks via REST as the user types, shows matching titles, user clicks to select
- Conference → Speaker references (`_speekr_conf_speakers`): add-one-at-a-time — search field queries Speaker Profile posts via REST, user picks one, it appears in a list below with an X to remove. Repeatable. Like a tag/mention UI.
- Talk edit screen — reverse conference reference: a read-only panel showing "Appears in: [Conference Name]" derived by querying Conference posts that reference this Talk's ID via REST. Read-only — the relationship is set on the Conference side.
- Talk → Conference explicit field (`speekr-conf`): Claude's discretion — keep the existing meta key as an explicit editable field on the Talk panel (matches legacy data model; avoids requiring a Conference post to set this)

**Headshots panel UX (Speaker Profile)**
- Add: standard WordPress media library button — opens WP media modal, supports upload + select from existing library
- Display: small thumbnails in a grid/row, each with an X (remove) button
- Reorder: drag-to-reorder — first item in the array = primary headshot; dragging changes the order
- Labels: editable text input per headshot directly in the panel (e.g., "B&W", "High-res") — speaker can label each one inline

**Bio and text field formatting (Speaker Profile)**
- Short bio: `RichText` with inline formats only — bold, italic, links. No block-level elements (no headings, no lists). Used for program intros and meta descriptions.
- Long bio: `RichText` with full formatting — bold, italic, links, and lists (unordered). Multi-paragraph supported.
- Rider fields (AV/tech, travel, dietary, accessibility): `RichText` with inline formats (bold, italic) per category — speaker can emphasize a key requirement

**Social links panel UI (Speaker Profile)**
- Add flow: dropdown to select platform (LinkedIn, Facebook, Instagram, Bluesky, Mastodon, X, GitHub, Personal website, Other) → URL input field appears → "Add" button confirms. Repeatable.
- Display: ordered list of added links, each with platform name/icon and X to remove. Speaker controls display order.
- "Other" platform: shows an additional "Label" text input alongside the URL field

### Claude's Discretion
- Exact component library choices (`@wordpress/components` — TextControl, SelectControl, Button, etc.)
- Drag-and-drop implementation for headshots (DnD library vs. custom mouse events)
- REST query debounce timing for search-as-you-type fields
- Panel collapse/expand defaults
- Whether to combine all Talk fields into one panel or split across multiple `PluginDocumentSettingPanel` panels by category
- Auth/permission handling at the JS layer (follow what registered meta auth_callbacks already enforce at REST)

### Deferred Ideas (OUT OF SCOPE)
None — discussion stayed within phase scope.
</user_constraints>

---

## Summary

Phase 3 builds native Gutenberg sidebar panels (`PluginDocumentSettingPanel`) for three CPTs: Talks, Conferences, and Speaker Profile. The standard pattern is: PHP registers blocks via `speekr_register_blocks()` looping `build/blocks/*/block.json`; each block's `edit.js` renders a `PluginDocumentSettingPanel` registered via `registerPlugin`; meta read/write uses `useEntityProp('postType', postType, 'meta')`. All three CPTs already have `show_in_rest: true` and `custom-fields` support from Phase 2, so the REST API foundation is in place.

The single most important pitfall in this phase is the **RichText-in-sidebar bug** (Gutenberg 17.3+, WordPress 6.5+, confirmed present in WP 6.9.1 on this install). The CONTEXT.md decisions specify `RichText` for bio and rider fields, but `RichText` is officially unsupported outside block edit contexts and is actively broken in `PluginDocumentSettingPanel`. The solution is to use plain `TextareaControl` with a clear note that rich text formatting will not be available in the editor UI (data is stored as plain text; rich text display can be layered at render time). This is a constraint the planner must resolve with the user or work around.

A second critical issue: the Talk CPT's three legacy meta keys (`speekr-summary`, `speekr-media-links`, `speekr-conf`) are stored serialized but NOT registered via `register_post_meta()` with `show_in_rest: true`. The block editor cannot read or write them via `useEntityProp` until they are registered. Phase 3 must include `register_post_meta()` calls for Talk meta fields before the panels will function. The `speekr_save_mb` classic editor path remains untouched.

**Primary recommendation:** Build three editor blocks (talk-meta, conference-meta, speaker-profile-meta) using `PluginDocumentSettingPanel` + `useEntityProp`. Register Talk meta for REST first. Use `TextareaControl` for all "rich text" fields given the RichText-in-sidebar bug. Use `MediaUpload` from `@wordpress/block-editor` for headshots. Use `apiFetch` with debounce for search-as-you-type relational fields.

---

## Critical Finding: RichText Is Broken in PluginDocumentSettingPanel

**This is the most important finding in this research.**

WordPress Gutenberg 17.3.0 (shipped in WordPress 6.5) broke `RichText` when used outside the block edit context. The component is tightly coupled to `BlockEditContextProvider` and fails with:
- Autocompleters non-functional
- Pressing Enter duplicates content instead of inserting newline

The maintainer stated: "RichText cannot be used outside of blocks right now." The issue (#60524) remains open with no fix as of June 2024. WordPress 6.9.1 (this install) ships Gutenberg 21.9, well past the break point.

**Impact on this phase:** The CONTEXT.md decisions for bio fields and rider fields specify `RichText`. This cannot be implemented as specified with the current WordPress version without a workaround.

**Options for the planner:**
1. **Use `TextareaControl`** — plain textarea, no formatting. Simple, stable, loses formatting capability.
2. **Use a `contenteditable` div with manual toolbar** — complex, fragile, not recommended.
3. **Store as plain text, apply rich text at render** — `TextareaControl` stores text, display templates wrap it in `wp_kses_post()`. Users cannot see formatting in the editor UI.
4. **Use `RichText` inside a custom minimal block wrapper** — experimental, requires wrapping the panel content in a fake block context. Not officially supported.

**Recommendation:** Use `TextareaControl` for all bio and rider fields in this phase. Flag this to the user for acknowledgment. The data schema already supports rich text (sanitize_callback uses `sanitize_textarea_field`), so upgrading to proper rich text later is possible without schema migration.

---

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| `@wordpress/editor` | (bundled with WP 6.9) | `PluginDocumentSettingPanel` import | Current location; moved from `@wordpress/edit-post` |
| `@wordpress/plugins` | (bundled) | `registerPlugin` | Only correct source for block plugin registration |
| `@wordpress/core-data` | (bundled) | `useEntityProp` hook | Official hook for reading/writing post meta in block editor |
| `@wordpress/data` | (bundled) | `useSelect`, `useDispatch` | Post type detection, store access |
| `@wordpress/components` | (bundled) | `TextControl`, `TextareaControl`, `SelectControl`, `Button`, `PanelBody`, `PanelRow` | WP design system, handles all form controls |
| `@wordpress/block-editor` | (bundled) | `MediaUpload`, `MediaUploadCheck` | Media library modal for headshots |
| `@wordpress/api-fetch` | (bundled) | REST API calls for search-as-you-type | Authenticated, nonce-handled fetch |
| `@wordpress/url` | (bundled) | `addQueryArgs` | Build REST query strings |
| `@wordpress/i18n` | (bundled) | `__()`, `_n()` | Translation |
| `@wordpress/element` | (bundled) | `useState`, `useEffect`, `useRef` | React hooks (re-exported) |

All of the above are bundled with WordPress and available as externals — do NOT install them as npm dependencies. `@wordpress/scripts` handles externalizing them automatically via `dependency-extraction-webpack-plugin`.

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `@wordpress/scripts` | 31.5.0 (already installed) | Build pipeline | Already configured in webpack.config.js |

### Not Needed
- `react-beautiful-dnd`, `@dnd-kit/*`, or any external DnD library — `@wordpress/components` includes a `Draggable` component for headshot reordering
- Any rich text editor library — `TextareaControl` is the correct substitution given the RichText bug

### Import Note: @wordpress/edit-post is DEPRECATED for PluginDocumentSettingPanel

```javascript
// WRONG (old) — still works but deprecated
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';

// CORRECT (current as of WP 6.5+)
import { PluginDocumentSettingPanel } from '@wordpress/editor';
```

Source: Official Block Editor Handbook, confirmed in Gutenberg GitHub.

---

## Existing Codebase State (Critical for Planning)

### What Phase 2 Delivered
All three CPTs are block-editor-ready:
- `talks` CPT: `show_in_rest: true`, `editor` and `custom-fields` in supports — DONE
- `speekr_conference` CPT: `show_in_rest: true`, `editor` and `custom-fields` in supports — DONE
- `speekr_speaker` CPT: `show_in_rest: true`, `editor` and `custom-fields` in supports — DONE

### Talk Meta Keys: NOT YET Registered for REST

The classic Talk meta keys are stored in the database but NOT registered via `register_post_meta()` with `show_in_rest: true`. `useEntityProp` cannot access them.

| Meta Key | Type | Currently Registered REST? | Phase 3 Action |
|----------|------|-----------------------------|----------------|
| `speekr-summary` | string | NO | Register via `register_post_meta()` with `show_in_rest: true` |
| `speekr-media-links` | serialized array (object with `youtube-link`, `vimeo-link`, `slides-link`, `other-link[]`, `embeded`, etc.) | NO | Register as `type: object` with `show_in_rest` schema, or simplify |
| `speekr-conf` | serialized array (`{ name, url }`) | NO | Register as `type: object` with `show_in_rest` schema |

**Important: `speekr-media-links` is complex.** It is a serialized PHP array with keys like `youtube-link`, `vimeo-link`, `slides-link`, `other-link` (array of `{label, url}`), `embeded`, `embeded-media`, `embed-code`. The classic editor saves this as a single serialized meta value. Registering this correctly for REST requires an `object` schema that matches the existing structure, OR a decision to simplify the block editor UI to simpler separate fields.

**Recommendation for `speekr-media-links`:** Register separate meta fields for `_speekr_media_youtube`, `_speekr_media_vimeo`, `_speekr_media_slides` (string, `show_in_rest: true`) for the block editor panel. The classic editor continues to write to the legacy `speekr-media-links` key. This avoids schema complexity and keeps the two save paths independent. The planner must decide: mirror the old key or use new keys.

**Recommendation for `speekr-conf`:** Register as `speekr-conf` with `type: object` and `show_in_rest` schema matching `{ name: string, url: string }`. This preserves backward compatibility with existing data.

### Speaker Profile Meta: Already Registered for REST

All five Speaker Profile meta fields are registered with `show_in_rest` schemas from Phase 2 Plan 01. Ready for block consumption:
- `_speekr_headshots` — array of `{id, label}` objects
- `_speekr_bio_short` — string
- `_speekr_bio_long` — string
- `_speekr_social_links` — array of `{platform, url, label}` objects
- `_speekr_rider` — object `{av, travel, dietary, accessibility}`

### Conference Meta: Already Registered for REST

All six Conference meta fields are registered from Phase 2 Plans 02 and 05:
- `_speekr_conf_date` — string
- `_speekr_conf_city` — string
- `_speekr_conf_country` — string
- `_speekr_conf_url` — string
- `_speekr_conf_talk_ref` — integer
- `_speekr_conf_speakers` — array of integers

### No Blocks Exist Yet

`src/blocks/` directory is empty. The webpack config already handles block auto-discovery via `defaultConfig.entry()`, so new blocks in `src/blocks/*/` are automatically included in the build.

### Build is Already Wired

`webpack.config.js` calls `defaultConfig.entry()` which auto-discovers all `block.json` files in `src/blocks/`. No webpack changes needed to add new blocks — just create the directory with `block.json`, `index.js`, and `edit.js`.

---

## Architecture Patterns

### Recommended Project Structure

```
src/blocks/
├── talk-meta/              # Talks CPT sidebar panels
│   ├── block.json          # Block metadata (apiVersion 3, no view script)
│   ├── index.js            # registerPlugin entry point
│   └── edit.js             # PluginDocumentSettingPanel UI component(s)
├── conference-meta/        # Conference CPT sidebar panels
│   ├── block.json
│   ├── index.js
│   └── edit.js
└── speaker-profile-meta/   # Speaker Profile CPT sidebar panels
    ├── block.json
    ├── index.js
    └── edit.js

inc/blocks/
└── blocks.php              # speekr_register_blocks() + Talk meta registration

build/blocks/               # @wordpress/scripts output — auto-generated
├── talk-meta/
│   ├── block.json
│   ├── index.js
│   └── index.asset.php
├── conference-meta/
└── speaker-profile-meta/
```

### Pattern 1: block.json for Editor-Only Plugin Blocks

These blocks have no `viewScript` because they render no front-end output — they are sidebar-only editor plugins.

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "speekr/talk-meta",
    "version": "1.0.0",
    "title": "Talk Meta",
    "category": "speekr",
    "description": "Speekr Talk metadata sidebar panels.",
    "editorScript": "file:./index.js"
}
```

Note: No `attributes`, no `edit`, no `save` — this block exists solely to register the script that calls `registerPlugin`. No `render_callback` needed in PHP.

Source: WordPress Block Editor Handbook — Metadata in block.json

### Pattern 2: speekr_register_blocks() — PHP Registration

```php
// inc/blocks/blocks.php

function speekr_register_blocks() {
    // WP 6.8+ can use wp_register_block_types_from_metadata_collection()
    // WP 6.9.1 is on this install — use it with glob fallback for safety
    $blocks_dir = SPEEKR_DIRNAME . '/build/blocks';

    if ( ! is_dir( $blocks_dir ) ) {
        return;
    }

    foreach ( glob( $blocks_dir . '/*/block.json' ) as $block_json ) {
        register_block_type( dirname( $block_json ) );
    }
}
add_action( 'init', 'speekr_register_blocks' );
```

The glob loop is safe, readable, and requires no manifest file. For WP 6.9+, `wp_register_block_types_from_metadata_collection()` is faster but requires a build flag (`--blocks-manifest`) which is not currently in the project scripts. Use glob for simplicity.

Source: WordPress Developer Blog 2025-08, official register_block_type() docs

### Pattern 3: registerPlugin + PluginDocumentSettingPanel

```javascript
// src/blocks/talk-meta/index.js
// Source: https://developer.wordpress.org/block-editor/reference-guides/slotfills/plugin-document-setting-panel/

import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import TalkMetaPanel from './edit';

const TalkMetaPlugin = () => {
    const postType = useSelect(
        ( select ) => select( 'core/editor' ).getCurrentPostType(),
        []
    );

    // Restrict to Talks CPT only
    if ( postType !== 'talks' ) {
        return null;
    }

    return <TalkMetaPanel />;
};

registerPlugin( 'speekr-talk-meta', {
    render: TalkMetaPlugin,
} );
```

**Post type restriction pattern:** Use `useSelect` to get `getCurrentPostType()` and return `null` if the CPT doesn't match. This is the standard approach — do NOT use `postType` prop on `PluginDocumentSettingPanel` (it doesn't exist).

### Pattern 4: useEntityProp for Meta Read/Write

```javascript
// Source: https://developer.wordpress.org/block-editor/how-to-guides/metabox/

import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { TextControl } from '@wordpress/components';

const TalkSummaryPanel = () => {
    const postType = useSelect(
        ( select ) => select( 'core/editor' ).getCurrentPostType(),
        []
    );

    const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

    const summary = meta[ 'speekr-summary' ] ?? '';
    const updateSummary = ( value ) =>
        setMeta( { ...meta, 'speekr-summary': value } );

    return (
        <PluginDocumentSettingPanel
            name="speekr-talk-summary"
            title="Talk Summary"
        >
            <TextareaControl
                label="Summary"
                value={ summary }
                onChange={ updateSummary }
                rows={ 4 }
            />
        </PluginDocumentSettingPanel>
    );
};
```

**Critical:** Always spread `{ ...meta, key: value }` when calling `setMeta`. Do NOT pass just `{ key: value }` — this clobbers all other meta fields in the editor state.

### Pattern 5: apiFetch Search-as-You-Type for Relational Fields

```javascript
// Source: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-api-fetch/

import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { useState, useEffect, useRef } from '@wordpress/element';
import { TextControl } from '@wordpress/components';

const TalkSearch = ( { onSelect } ) => {
    const [ search, setSearch ] = useState( '' );
    const [ results, setResults ] = useState( [] );
    const debounceRef = useRef( null );

    useEffect( () => {
        if ( search.length < 2 ) {
            setResults( [] );
            return;
        }

        clearTimeout( debounceRef.current );
        debounceRef.current = setTimeout( () => {
            apiFetch( {
                path: addQueryArgs( '/wp/v2/talks', {
                    search,
                    per_page: 10,
                    status: 'publish',
                    _fields: 'id,title',
                } ),
            } ).then( setResults );
        }, 300 );  // 300ms debounce — Claude's discretion per CONTEXT.md
    }, [ search ] );

    return (
        <>
            <TextControl
                label="Search Talks"
                value={ search }
                onChange={ setSearch }
            />
            { results.map( ( post ) => (
                <Button
                    key={ post.id }
                    onClick={ () => onSelect( post ) }
                    variant="tertiary"
                >
                    { post.title.rendered }
                </Button>
            ) ) }
        </>
    );
};
```

CPT REST endpoints:
- Talks: `/wp/v2/talks`
- Conferences: `/wp/v2/speekr_conference`
- Speaker Profiles: `/wp/v2/speekr_speaker`

### Pattern 6: MediaUpload for Headshots

```javascript
// Source: https://github.com/WordPress/gutenberg/blob/trunk/packages/block-editor/src/components/media-upload/README.md

import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';

const HeadshotAdder = ( { onAdd } ) => (
    <MediaUploadCheck>
        <MediaUpload
            onSelect={ ( media ) => onAdd( { id: media.id, label: '' } ) }
            allowedTypes={ [ 'image' ] }
            render={ ( { open } ) => (
                <Button onClick={ open } variant="secondary">
                    Add Headshot
                </Button>
            ) }
        />
    </MediaUploadCheck>
);
```

Always wrap `MediaUpload` in `MediaUploadCheck` to verify upload permissions.

### Pattern 7: Hiding Classic Meta Boxes in Block Editor (Talks only)

Use the `__back_compat_meta_box` argument in `add_meta_box()` — the cleanest, most WordPress-native approach:

```php
// In speekr_custom_meta_boxes() — custom-meta-boxes.php
// Add __back_compat_meta_box to each Talks add_meta_box() call:

add_meta_box(
    'speekr-summary',
    __( 'Talk Summary', 'speekr' ),
    'speekr_summary_mb',
    null,
    'normal',
    'high',
    array( '__back_compat_meta_box' => true )  // Hides in block editor, visible in classic editor
);
```

This is the WordPress-official mechanism — it hides the meta box in Gutenberg while keeping it fully functional in the classic editor. No `remove_meta_box()` call or `get_current_screen()` check needed.

Source: https://developer.wordpress.org/block-editor/how-to-guides/metabox/

### Anti-Patterns to Avoid

- **Don't import `PluginDocumentSettingPanel` from `@wordpress/edit-post`** — it still works but is deprecated; use `@wordpress/editor`
- **Don't call `setMeta({ key: value })` without spreading** — clobbers all other meta in editor state
- **Don't use `RichText` in `PluginDocumentSettingPanel`** — broken since WP 6.5, no fix landed
- **Don't add `viewScript` to block.json for these blocks** — they are editor-only; no front-end output
- **Don't register blocks in `includes_admin()`** — blocks must be registered on all requests including REST API; use `includes()` (already established pattern from Phase 2)
- **Don't hardcode the Talks post type slug** — use `speekr_get_cpt_slug()` in PHP; in JS, query `getCurrentPostType()` and compare against the expected slug value
- **Don't skip the `if ( ! is_dir( $blocks_dir ) )` guard** — the build directory may not exist in dev environments

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Media library modal | Custom file upload UI | `MediaUpload` + `MediaUploadCheck` from `@wordpress/block-editor` | Handles permissions, WP media library integration, gallery modes |
| REST search queries | Custom fetch wrapper | `apiFetch` from `@wordpress/api-fetch` | Handles WP REST nonce, error formats, middleware |
| Query string building | Manual string concat | `addQueryArgs` from `@wordpress/url` | Correct encoding, merging, edge cases |
| Form controls | Custom React inputs | `TextControl`, `TextareaControl`, `SelectControl` from `@wordpress/components` | Matches WP admin design system, accessible |
| Post type detection | `document.body.className` parsing | `useSelect( select => select('core/editor').getCurrentPostType() )` | Reactive, correct, official API |
| DnD for headshots | Custom mouse event handler | `Draggable` from `@wordpress/components` | Cross-browser, WP-tested |
| Block registration loop | Custom PHP file scanner | `glob( $blocks_dir . '/*/block.json' )` + `register_block_type()` | Simple, reliable, no manifest needed for 3 blocks |

**Key insight:** All necessary tools are already bundled with WordPress. Installing external npm packages for any of these problems introduces bundle size bloat and version conflict risk since WordPress externalizes its own packages.

---

## Common Pitfalls

### Pitfall 1: RichText in PluginDocumentSettingPanel Is Broken

**What goes wrong:** `RichText` component renders but Enter key duplicates content; autocompleters fail; cursor behavior is erratic.
**Why it happens:** Gutenberg 17.3+ added an early return that checks `isBlockSelected` context. Outside a block's edit function, this is always `undefined`, causing selection state to break.
**How to avoid:** Use `TextareaControl` for all multiline text fields in sidebar panels.
**Warning signs:** If you see duplicated content on Enter key press in a sidebar panel, RichText is the cause.
**Source:** https://github.com/WordPress/gutenberg/issues/60524

### Pitfall 2: setMeta Overwrites All Other Meta

**What goes wrong:** Saving one meta field from a panel wipes all other meta fields on that post.
**Why it happens:** `useEntityProp` returns the full meta object. If you call `setMeta({ key: value })` without spreading, it replaces the entire meta object.
**How to avoid:** Always: `setMeta( { ...meta, 'my-key': value } )`
**Warning signs:** Other meta fields disappear after saving the post from the block editor.

### Pitfall 3: Talk Meta Keys Not Visible to useEntityProp

**What goes wrong:** `meta['speekr-summary']` is `undefined` in the block editor; useEntityProp returns no values.
**Why it happens:** `speekr-summary`, `speekr-media-links`, and `speekr-conf` are NOT registered via `register_post_meta()` with `show_in_rest: true`. Without registration, WordPress does not expose them in the REST API meta object.
**How to avoid:** Register all Talk meta keys via `register_post_meta()` before the block panels read them.
**Warning signs:** `meta` object from `useEntityProp` exists but the Talk-specific keys are absent.

### Pitfall 4: Object Meta Requires Full Schema for REST Writes

**What goes wrong:** `setMeta({ 'speekr-conf': { name: 'Conf', url: 'https://...' } })` returns a REST 400 error.
**Why it happens:** WordPress REST API validates object and array meta against their registered `show_in_rest` schema. If the schema is missing or incomplete, writes are rejected.
**How to avoid:** Provide a full `show_in_rest` schema for any object or array meta field, including `additionalProperties: false` if needed.
**Warning signs:** Browser console shows 400 error on save; WP REST returns "rest_invalid_param".

### Pitfall 5: Block Registration Must Be in includes(), Not includes_admin()

**What goes wrong:** Block sidebar panels do not appear in the block editor. REST API requests to save meta fail silently.
**Why it happens:** `includes_admin()` only runs on admin page loads, not REST API requests. Block registration via `init` must fire on all requests.
**How to avoid:** Add `require_once` for `inc/blocks/blocks.php` in `Speekr::includes()`, not `includes_admin()`. Pattern already established in Phase 2.
**Warning signs:** Block panels don't appear; no console errors from the block itself.

### Pitfall 6: PluginDocumentSettingPanel Renders on All Post Types

**What goes wrong:** The Talk meta panel appears on Conference and Speaker Profile post edit screens.
**Why it happens:** `registerPlugin` registers globally; `PluginDocumentSettingPanel` renders wherever the editor loads.
**How to avoid:** Use `useSelect` + `getCurrentPostType()` at the top of each plugin component and return `null` if the post type doesn't match.
**Warning signs:** Conference edit screen shows Talk meta panel.

### Pitfall 7: Relational Reverse Lookup (Conference → Talk) Performance

**What goes wrong:** The "Appears in conference" read-only panel makes a slow or failing REST call.
**Why it happens:** To find conferences containing a Talk ID, you must query `_speekr_conf_talk_ref` via REST. WordPress does not support meta value filtering in REST by default for custom fields.
**How to avoid:** Use `apiFetch` to query `/wp/v2/speekr_conference?meta_query=...` — but note meta_query is NOT available in standard REST API. Alternative: query all conferences and filter client-side (acceptable for small datasets), OR register a custom REST endpoint for this reverse lookup.
**Warning signs:** "Appears in" panel is empty or errors; console shows 400 on conference query with meta param.
**Recommended approach:** `apiFetch( { path: addQueryArgs('/wp/v2/speekr_conference', { per_page: 100, _fields: 'id,title,meta' }) } )` then filter results where `meta._speekr_conf_talk_ref === currentPostId`. Acceptable for small conference datasets.

### Pitfall 8: `speekr-media-links` Serialized Array Schema Complexity

**What goes wrong:** Registering `speekr-media-links` as an object meta with a schema that doesn't match the existing serialized structure causes REST 400 errors on classic editor saves or silently drops data.
**Why it happens:** The classic editor saves `speekr-media-links` as a complex serialized PHP array with dynamic keys. The REST schema must match exactly, or writes fail.
**How to avoid:** Either (a) register new separate meta keys for the block editor panel and leave `speekr-media-links` for classic only, or (b) register `speekr-media-links` as `type: object` with a permissive `additionalProperties: true` schema. Option (a) is safer.
**Warning signs:** Saving Talk via block editor returns 400 error or clears media links.

---

## Code Examples

### Complete PluginDocumentSettingPanel Pattern

```javascript
// src/blocks/talk-meta/edit.js
// Source: https://developer.wordpress.org/block-editor/reference-guides/slotfills/plugin-document-setting-panel/

import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const TalkSummaryPanel = () => {
    const postType = useSelect(
        ( select ) => select( 'core/editor' ).getCurrentPostType(),
        []
    );

    const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

    if ( ! meta ) return null;

    const summary = meta[ 'speekr-summary' ] ?? '';

    return (
        <PluginDocumentSettingPanel
            name="speekr-talk-summary"
            title={ __( 'Talk Summary', 'speekr' ) }
            className="speekr-talk-summary-panel"
        >
            <TextareaControl
                label={ __( 'Summary', 'speekr' ) }
                value={ summary }
                onChange={ ( value ) =>
                    setMeta( { ...meta, 'speekr-summary': value } )
                }
                rows={ 5 }
            />
        </PluginDocumentSettingPanel>
    );
};

export default TalkSummaryPanel;
```

### PHP: Register Talk Meta for REST

```php
// In inc/blocks/blocks.php or a new inc/cpt/talks-meta.php

function speekr_register_talk_meta() {

    // speekr-summary: plain text summary of the talk
    register_post_meta( speekr_get_cpt_slug(), 'speekr-summary', array(
        'single'            => true,
        'type'              => 'string',
        'show_in_rest'      => true,
        'sanitize_callback' => 'sanitize_textarea_field',
        'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
    ) );

    // speekr-conf: legacy conference reference {name, url}
    register_post_meta( speekr_get_cpt_slug(), 'speekr-conf', array(
        'single'       => true,
        'type'         => 'object',
        'show_in_rest' => array(
            'schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'name' => array( 'type' => 'string' ),
                    'url'  => array( 'type' => 'string', 'format' => 'uri' ),
                ),
            ),
        ),
        'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
    ) );

    // New block-editor-only media fields (separate from legacy speekr-media-links)
    foreach ( array( '_speekr_media_youtube', '_speekr_media_vimeo', '_speekr_media_slides' ) as $key ) {
        register_post_meta( speekr_get_cpt_slug(), $key, array(
            'single'            => true,
            'type'              => 'string',
            'show_in_rest'      => true,
            'sanitize_callback' => 'esc_url_raw',
            'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
        ) );
    }
}
add_action( 'init', 'speekr_register_talk_meta' );
```

### PHP: Hide Classic Meta Boxes in Block Editor

```php
// In speekr_custom_meta_boxes() in inc/admin/custom-meta-boxes.php
// Add the __back_compat_meta_box arg to the three Talks meta boxes:

add_meta_box(
    'speekr-summary',
    __( 'Talk Summary', 'speekr' ),
    'speekr_summary_mb',
    null, 'normal', 'high',
    array( '__back_compat_meta_box' => true )
);

add_meta_box(
    'speekr-media-links',
    __( 'Media Links', 'speekr' ),
    'speekr_content_media_links_mb',
    null, 'normal', 'high',
    array( '__back_compat_meta_box' => true )
);

add_meta_box(
    'speekr-conference',
    __( 'About the Conference', 'speekr' ),
    'speekr_conference_mb',
    null, 'side', 'high',
    array( '__back_compat_meta_box' => true )
);
// Leave 'speekr-content' (speekr_the_content_mb) as-is — it wraps the wp_editor
// and should remain in both classic and block editor contexts.
```

### PHP: speekr_register_blocks() Loop

```php
// inc/blocks/blocks.php

function speekr_register_blocks() {
    $blocks_dir = SPEEKR_DIRNAME . '/build/blocks';

    if ( ! is_dir( $blocks_dir ) ) {
        return;
    }

    foreach ( glob( $blocks_dir . '/*/block.json' ) as $block_json ) {
        register_block_type( dirname( $block_json ) );
    }
}
add_action( 'init', 'speekr_register_blocks' );
```

### Headshots Panel with Drag-to-Reorder Skeleton

```javascript
// Conceptual structure — planner adapts for full implementation
import { Draggable } from '@wordpress/components';
import { useState } from '@wordpress/element';

const HeadshotsPanel = ( { headshots, onChange } ) => {
    const reorder = ( fromIndex, toIndex ) => {
        const updated = [ ...headshots ];
        const [ moved ] = updated.splice( fromIndex, 1 );
        updated.splice( toIndex, 0, moved );
        onChange( updated );
    };

    return (
        <div className="speekr-headshots-list">
            { headshots.map( ( shot, index ) => (
                <Draggable
                    key={ shot.id }
                    elementId={ `speekr-headshot-${ shot.id }` }
                    transferData={ { index } }
                    onDragEnd={ ( event ) => {
                        // Determine target index from drop target and call reorder()
                    } }
                >
                    { ( { onDraggableStart, onDraggableEnd } ) => (
                        <div
                            id={ `speekr-headshot-${ shot.id }` }
                            draggable
                            onDragStart={ onDraggableStart }
                            onDragEnd={ onDraggableEnd }
                        >
                            { index === 0 && <span>Primary</span> }
                            <img src={ /* resolve from attachment ID via wp_get_attachment_image_src */ } />
                            <TextControl value={ shot.label } onChange={ /* update label */ } />
                            <Button onClick={ /* remove */ } isDestructive>×</Button>
                        </div>
                    ) }
                </Draggable>
            ) ) }
        </div>
    );
};
```

Note: `Draggable` from `@wordpress/components` provides the drag infrastructure but requires manual tracking of drop targets. For the headshots panel, a simpler approach using `onDragOver` with `data-index` attributes on each item is recommended.

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `import { PluginDocumentSettingPanel } from '@wordpress/edit-post'` | Import from `@wordpress/editor` | WP 6.5 | Old still works but deprecated |
| `withSelect` / `withDispatch` HOCs | `useSelect` / `useEntityProp` hooks | WP 5.7+ | HOCs still functional, hooks preferred |
| `register_block_type( path_to_block_json )` (single) | `glob` loop or `wp_register_block_types_from_metadata_collection()` | WP 6.8 added collection API | Glob loop is simpler for 3 blocks |
| `apiVersion: 2` in block.json | `apiVersion: 3` | WP 6.3 | Version 3 mandatory in WP 7.0; use 3 now |
| `RichText` in sidebar panels | `TextareaControl` (due to bug) | WP 6.5 (bug introduced) | Formatting capability lost in editor UI |

**Deprecated/outdated:**
- `@wordpress/edit-post` as import source for `PluginDocumentSettingPanel`: deprecated, use `@wordpress/editor`
- `wp.blocks.RichText.Content` (render side): use PHP-rendered content instead
- Meta boxes registered without `__back_compat_meta_box: true` in block editor context: they appear in Gutenberg alongside panels, causing duplicate UI

---

## Open Questions

1. **RichText Decision: TextareaControl vs. Rich Text**
   - What we know: RichText is broken in PluginDocumentSettingPanel since WP 6.5; TextareaControl is the stable fallback
   - What's unclear: The CONTEXT.md specifies `RichText` for bio fields. The user may not know this is broken.
   - Recommendation: The planner should flag this as a deviation from the CONTEXT.md spec and use TextareaControl. Document clearly that rich text formatting is not available in the editor panel UI. Upgrade path exists if RichText bug is ever fixed.

2. **Talk Media Links: New Keys vs. Registering Legacy `speekr-media-links`**
   - What we know: `speekr-media-links` is a complex serialized array; registering it requires a matching REST schema
   - What's unclear: The CONTEXT.md says the block editor panel should show "media links (YouTube/Vimeo/Slides)". Should this mirror the legacy structure or simplify?
   - Recommendation: Register three separate simple meta fields (`_speekr_media_youtube`, `_speekr_media_vimeo`, `_speekr_media_slides`) for the block editor panel. The classic editor continues writing to `speekr-media-links`. Two save paths, different keys — block editor data and classic editor data coexist. This is the lowest-risk approach.

3. **Reverse Conference Lookup: Feasibility**
   - What we know: To show "Appears in: [Conference Name]" on a Talk, we must find Conferences where `_speekr_conf_talk_ref` equals the current Talk ID. WP REST API does not natively support meta value filtering.
   - What's unclear: How many conferences will exist in practice? Client-side filtering is acceptable if count is small.
   - Recommendation: Query `/wp/v2/speekr_conference?per_page=100&_fields=id,title,meta` and filter results where `meta._speekr_conf_talk_ref === currentPostId`. Accept limitation that only 100 conferences are searched. Document this limit.

4. **Tabs in WP 6.9 Block Editor**
   - What we know: WP 6.9 shipped with Gutenberg 21.9
   - What's unclear: Whether WP 6.9 changed any PluginDocumentSettingPanel APIs since the current documentation reflects
   - Recommendation: Test panels in the actual WP 6.9.1 environment early in development. No specific breaking changes found in research.

---

## Sources

### Primary (HIGH confidence)
- https://developer.wordpress.org/block-editor/reference-guides/slotfills/plugin-document-setting-panel/ — PluginDocumentSettingPanel API, props, import location
- https://developer.wordpress.org/block-editor/how-to-guides/metabox/ — useEntityProp pattern, `__back_compat_meta_box` argument
- https://developer.wordpress.org/block-editor/reference-guides/components/textarea-control/ — TextareaControl API
- https://developer.wordpress.org/block-editor/reference-guides/components/text-control/ — TextControl API
- https://developer.wordpress.org/block-editor/reference-guides/components/select-control/ — SelectControl API
- https://developer.wordpress.org/block-editor/reference-guides/components/draggable/ — Draggable API
- https://developer.wordpress.org/block-editor/reference-guides/packages/packages-api-fetch/ — apiFetch usage
- https://developer.wordpress.org/block-editor/reference-guides/richtext/ — RichText restrictions (block-only)
- https://github.com/WordPress/gutenberg/blob/trunk/packages/block-editor/src/components/media-upload/README.md — MediaUpload API

### Secondary (MEDIUM confidence)
- https://github.com/WordPress/gutenberg/issues/60524 — RichText bug in PluginDocumentSettingPanel (open issue, confirmed broken)
- https://github.com/WordPress/gutenberg/issues/63097 — Request for RichText outside blocks (closed as "not planned")
- https://developer.wordpress.org/news/2025/08/refactoring-the-multi-block-plugin-build-smarter-register-cleaner-scale-easier/ — Multi-block registration patterns including glob loop and manifest approach
- https://ryanwelcher.com/2020/02/21/restricting-plugindocumentsettingpanel-by-post-type/ — Post type restriction pattern (older but still valid)

### Tertiary (LOW confidence)
- WebSearch results for debounce timing (300ms is community convention, not documented officially)
- WebSearch for WP 6.9 / Gutenberg 21.9 version mapping (confirm if using plugin)

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all packages are official WP bundled; imports verified against current official docs
- Architecture: HIGH — patterns verified against official Block Editor Handbook
- RichText pitfall: HIGH — open GitHub issue with maintainer confirmation; active on WP 6.9.1 install
- Talk meta REST gap: HIGH — directly verified by reading PHP files; no `register_post_meta` for Talk keys
- Headshots DnD: MEDIUM — `Draggable` component API verified; integration pattern is implementation-level detail
- Reverse conference lookup: MEDIUM — REST API limitations verified; specific implementation is a planning decision

**Research date:** 2026-03-02
**Valid until:** 2026-04-02 (30 days — core WP APIs are stable; RichText bug may be fixed in Gutenberg plugin updates)
