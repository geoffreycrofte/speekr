# Phase 2: Data Layer - Research

**Researched:** 2026-03-02
**Domain:** WordPress CPT registration, register_post_meta REST API schema, register_taxonomy, save_post REST gating
**Confidence:** HIGH

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

**Headshots field (`_speekr_headshots`)**
- Unlimited gallery — speaker can upload as many headshots as they want
- Ordered array — first item is treated as the primary/default headshot by display blocks
- Each entry: `{ "id": <attachment_id>, "label": "<optional text>" }` — label is optional
- Storage: attachment ID only (URL resolved at render time via `wp_get_attachment_image_src()`); more robust if site URL changes
- Stored as a JSON array in a single `_speekr_headshots` meta field with `type: array` schema in `register_post_meta()`

**Social links field (`_speekr_social_links`)**
- Hybrid model: fixed known platforms + open-ended "Other" entries
- Fixed platforms in v1: LinkedIn, Facebook, Instagram, Bluesky, Mastodon, X (Twitter), GitHub, Personal website
- Each entry: `{ "platform": "<key_or_other>", "url": "https://...", "label": "<display label>" }`
- Ordered array — speaker controls the display sequence
- Stored as a JSON array in a single `_speekr_social_links` meta field

**Rider field (`_speekr_rider`)**
- Structured fields — not plain text or rich text
- Four categories in v1: AV/tech requirements, Travel & accommodation, Dietary restrictions, Accessibility needs
- Storage: JSON object in a single `_speekr_rider` meta field — `{ "av": "...", "travel": "...", "dietary": "...", "accessibility": "..." }`
- Each value is a plain text string

**Topics taxonomy**
- Flat tag-like taxonomy (not hierarchical)
- Registered on the Talks CPT with `show_in_rest: true`
- Shared site-wide vocabulary
- Any user who can edit a talk can create new terms (standard WP tag behavior)
- Appears in the block editor as a standard sidebar panel (like Tags)

**save_post callback gating (02-03)**
- Gate all existing `save_post` callbacks in `inc/admin/custom-meta-boxes.php` with: `wp_is_post_autosave()`, `wp_is_post_revision()`, and `!empty($_POST)` checks
- Purpose: prevent REST API saves (which don't send $_POST) from triggering legacy meta box writes that could wipe registered meta

### Claude's Discretion
- Exact `register_post_meta()` schema shapes for array fields (WP REST `show_in_rest` schema for nested objects)
- Whether to use `sanitize_callback` functions or rely on WP's built-in sanitization for each field type
- Order of file loading via `require_once` in `inc/classes/Speekr.php`
- Auth callback implementation (standard logged-in check vs. post-author check)

### Deferred Ideas (OUT OF SCOPE)
None — discussion stayed within phase scope.
</user_constraints>

---

## Summary

This phase registers three CPTs (Speaker Profile, Conferences, Talks update) and all their post meta fields via `register_post_meta()` with `show_in_rest: true`, plus a flat Topics taxonomy on Talks. WordPress requires both `show_in_rest: true` on the post type AND `'custom-fields'` in `supports` for registered meta to appear in the REST API and be accessible via `useEntityProp()`. The block editor also requires `'editor'` in `supports` to load the block editor instead of classic.

The critical complexity is the array-of-objects schema shape required since WordPress 5.3: any meta field with `type: 'array'` must declare an `items` schema, and any object within it must declare `properties`. WordPress will reject (or silently fail to expose) array/object fields that omit this schema. The three complex fields (_speekr_headshots, _speekr_social_links) are arrays of objects; _speekr_rider is a single object. All three need expanded `show_in_rest` array syntax rather than `show_in_rest: true`.

The save_post gating problem is well-understood: the block editor sends a REST API request (with no `$_POST`) that fires `save_post`, and the legacy `speekr_save_mb` callback reads from `$_POST`. Without a gate, a block editor save silently overwrites registered meta with empty values. The user-approved gate pattern — `!empty($_POST)` plus the existing DOING_AUTOSAVE check — is the correct and sufficient fix for this codebase.

**Primary recommendation:** Register all CPTs and meta on the `init` hook; use the expanded `show_in_rest => array('schema' => ...)` syntax for array and object meta; gate `speekr_save_mb` with `!empty($_POST)`, `wp_is_post_autosave()`, and `wp_is_post_revision()`; add new CPT files in `inc/cpt/` loaded via `require_once` in `Speekr::includes()`.

---

## Standard Stack

### Core

| Function | Since | Purpose | Notes |
|----------|-------|---------|-------|
| `register_post_type()` | WP 2.9 | Register CPTs with REST and block editor support | Must set `show_in_rest: true` and `supports: ['editor', 'custom-fields']` |
| `register_post_meta()` | WP 4.9.8 | Register individual meta keys with REST schema | Wrapper for `register_meta('post', ...)` with `object_subtype` |
| `register_taxonomy()` | WP 2.3 | Register flat Topics taxonomy | Must set `show_in_rest: true`, `hierarchical: false` |
| `wp_is_post_autosave()` | WP 2.6 | Check if save is an autosave | Returns post ID or false |
| `wp_is_post_revision()` | WP 2.6 | Check if save is a revision | Returns post ID or false |

### WordPress Version Requirements

All features used in this phase are available in WordPress 5.3+. The `type: 'array'` and `type: 'object'` meta schema support was added in WordPress 5.3 (October 2019). The `wp_is_serving_rest_request()` function was added in WordPress 6.5 — we do NOT use it (user decision: use `!empty($_POST)` instead, which works on all WP versions).

---

## Architecture Patterns

### New File Structure

The plan calls for a new `inc/cpt/` directory. This does not exist yet and must be created.

```
inc/
├── cpt/                        # NEW — one file per CPT
│   ├── speaker-profile.php     # plan 02-01: CPT + all Speaker Profile meta
│   └── conferences.php         # plan 02-02: CPT + all Conference meta
├── common/
│   └── custom-posts.php        # EXISTING — update Talks CPT args here
├── admin/
│   └── custom-meta-boxes.php   # EXISTING — add gates to speekr_save_mb
└── classes/
    └── Speekr.php              # EXISTING — add require_once for new files
```

### Pattern 1: CPT Registration with REST and Block Editor Support

Two separate but mandatory conditions must both be true for the block editor to load and meta to be REST-accessible:

1. `show_in_rest: true` on the `register_post_type()` call
2. `'editor'` in `supports` (loads block editor instead of classic editor)
3. `'custom-fields'` in `supports` (exposes registered meta via REST)

```php
// Source: https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-rest-api-support-for-custom-content-types/
function speekr_register_speaker_profile_cpt() {
    $args = array(
        'label'        => __( 'Speaker Profiles', 'speekr' ),
        'public'       => true,
        'show_in_rest' => true,                    // REQUIRED: enables REST API + block editor
        'supports'     => array(
            'title',
            'editor',          // REQUIRED: loads block editor
            'thumbnail',
            'custom-fields',   // REQUIRED: exposes registered meta via REST
            'revisions',
        ),
        // ... labels, rewrite, etc.
    );
    register_post_type( 'speekr_speaker', $args );
}
add_action( 'init', 'speekr_register_speaker_profile_cpt' );
```

### Pattern 2: Simple (Scalar) Meta Registration

For string, integer, boolean fields — `show_in_rest: true` is sufficient.

```php
// Source: https://developer.wordpress.org/news/2023/03/creating-a-custom-block-that-stores-post-meta/
register_post_meta(
    'speekr_speaker',
    '_speekr_short_bio',
    array(
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_textarea_field',
        'auth_callback'     => function() {
            return current_user_can( 'edit_posts' );
        },
    )
);
```

### Pattern 3: Array-of-Objects Meta (CRITICAL — applies to _speekr_headshots and _speekr_social_links)

When `type` is `'array'`, `show_in_rest` MUST be an array with a `schema.items` definition. Omitting `items` causes WordPress 5.3+ to reject the registration or not expose the field correctly.

Each item is an object, so `items` must declare `type: 'object'` and `properties`.

```php
// Source: https://make.wordpress.org/core/2019/10/03/wp-5-3-supports-object-and-array-meta-types-in-the-rest-api/
// Verified by search cross-reference with register_meta() docs
register_post_meta(
    'speekr_speaker',
    '_speekr_headshots',
    array(
        'show_in_rest' => array(
            'schema' => array(
                'type'  => 'array',
                'items' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'id'    => array( 'type' => 'integer' ),
                        'label' => array( 'type' => 'string' ),
                    ),
                ),
            ),
        ),
        'single'            => true,
        'type'              => 'array',
        'sanitize_callback' => 'speekr_sanitize_headshots',
        'auth_callback'     => function() {
            return current_user_can( 'edit_posts' );
        },
    )
);
```

### Pattern 4: Single-Object Meta (applies to _speekr_rider)

When `type` is `'object'`, `show_in_rest` MUST declare `properties` in the schema.

```php
// Source: https://make.wordpress.org/core/2019/10/03/wp-5-3-supports-object-and-array-meta-types-in-the-rest-api/
register_post_meta(
    'speekr_speaker',
    '_speekr_rider',
    array(
        'show_in_rest' => array(
            'schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'av'            => array( 'type' => 'string' ),
                    'travel'        => array( 'type' => 'string' ),
                    'dietary'       => array( 'type' => 'string' ),
                    'accessibility' => array( 'type' => 'string' ),
                ),
            ),
        ),
        'single'            => true,
        'type'              => 'object',
        'sanitize_callback' => 'speekr_sanitize_rider',
        'auth_callback'     => function() {
            return current_user_can( 'edit_posts' );
        },
    )
);
```

### Pattern 5: Flat Tag-Like Taxonomy Registration

Non-hierarchical taxonomy (`hierarchical: false`) renders as a free-text tag input in the block editor sidebar, not a checkbox list. `show_in_rest: true` is mandatory for the block editor panel to appear.

```php
// Source: https://developer.wordpress.org/reference/functions/register_taxonomy/
function speekr_register_topics_taxonomy() {
    $args = array(
        'hierarchical'  => false,         // flat = tag-like, renders as tag input in block editor
        'show_in_rest'  => true,          // REQUIRED for block editor sidebar panel
        'labels'        => array(
            'name'          => __( 'Topics', 'speekr' ),
            'singular_name' => __( 'Topic', 'speekr' ),
            'add_new_item'  => __( 'Add New Topic', 'speekr' ),
        ),
        'rewrite'       => array( 'slug' => 'topic' ),
        'show_admin_column' => true,
    );
    register_taxonomy( 'speekr_topic', array( 'talks' ), $args );
}
add_action( 'init', 'speekr_register_topics_taxonomy' );
```

### Pattern 6: Gating save_post Against REST Double-Fire

The existing `speekr_save_mb` callback reads `$_POST` to save meta. The block editor fires `save_post` via the REST API (no `$_POST`), which causes the callback to overwrite registered meta with empty values.

The approved gate adds three checks at the top of `speekr_save_mb`:

```php
// Source: existing code analysis + https://developer.wordpress.org/reference/hooks/save_post/
function speekr_save_mb( $post_id ) {

    // Gate 1: no $_POST means this is a REST API request — bail
    if ( empty( $_POST ) ) {
        return;
    }

    // Gate 2: autosave — already in the function, keep it
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Gate 3 (NEW): autosave via REST
    if ( wp_is_post_autosave( $post_id ) ) {
        return;
    }

    // Gate 4 (NEW): revision
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    // Gate 5: nonce (already present)
    if ( ! isset( $_POST['_wpnonce'] ) ) {
        return;
    }

    // ... rest of function unchanged
```

**Note on DOING_AUTOSAVE:** The existing `defined('DOING_AUTOSAVE') && DOING_AUTOSAVE` check catches the WP constant set during classic editor autosave, but does NOT catch REST-path autosaves (which use a separate autosave endpoint). The `wp_is_post_autosave()` and `wp_is_post_revision()` checks plus `!empty($_POST)` together provide complete coverage.

### Pattern 7: File Loading Order in Speekr.php

New CPT files must be loaded in `Speekr::includes()`, which runs before the `is_admin()` branch. CPT and meta registration hooks fire on `init`, which runs after the constructor completes, so loading order within `includes()` only matters for function availability (no circular dependencies here).

Add new `require_once` calls after the existing `custom-posts.php` line:

```php
// In Speekr::includes()
require_once( SPEEKR_DIRNAME . '/inc/common/custom-posts.php' );    // existing Talks CPT
require_once( SPEEKR_DIRNAME . '/inc/cpt/speaker-profile.php' );    // NEW
require_once( SPEEKR_DIRNAME . '/inc/cpt/conferences.php' );        // NEW
```

### Anti-Patterns to Avoid

- **Registering meta only on `rest_api_init`:** Meta must be registered on `init` so the block editor's JavaScript (which loads on admin pages, not via `rest_api_init`) can discover the fields. Using `rest_api_init` breaks `useEntityProp()` discovery.
- **Using `show_in_rest: true` for array/object types:** Must use the expanded `show_in_rest: array('schema' => ...)` syntax. WordPress 5.3 will not expose these fields correctly with simple `true`.
- **Omitting `items` in array schema:** Any array meta without `items` defined is rejected or silently ignored by the REST API.
- **Omitting `properties` in object schema:** Object meta without `properties` falls through to `additionalProperties` behavior, which is less predictable.
- **Loading new CPT files inside `includes_admin()`:** CPT and meta registration must be available on all requests (front, REST, admin), not only admin. The `includes()` method (not `includes_admin()`) is the correct place.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| REST schema for arrays | Custom serialization/JSON encode | `register_post_meta()` with `type: array` and `items` schema | WP handles REST serialization, validation, sanitization |
| Taxonomy term input UI | Custom JS term input | `register_taxonomy()` with `show_in_rest: true`, `hierarchical: false` | WP block editor renders tag input automatically |
| REST API endpoint for meta | Custom REST route | `register_post_meta()` with `show_in_rest` | WP adds meta to existing `/wp/v2/{post-type}/{id}` endpoint automatically |
| Permission check for meta | Custom nonce/cap check per field | `auth_callback` parameter in `register_post_meta()` | WP integrates with its own permission system |
| Differentiating REST vs form save | Complex request detection | `!empty($_POST)` gate | REST requests never populate `$_POST`; form saves always do |

---

## Common Pitfalls

### Pitfall 1: Missing `'custom-fields'` in supports Breaks REST Meta Exposure

**What goes wrong:** The CPT is visible in the block editor (`show_in_rest: true`, `'editor'` in supports), but `register_post_meta()` fields do not appear in the REST API response or in `useEntityProp()`.

**Why it happens:** WordPress checks `post_type_supports( $post_type, 'custom-fields' )` before exposing registered meta via the REST API. If the support is missing, WP skips the meta endpoint entirely for that post type.

**How to avoid:** Always include both `'editor'` and `'custom-fields'` in the `supports` array for any CPT that needs block editor + REST meta access.

**Warning signs:** `GET /wp-json/wp/v2/speekr_speaker/123` returns no `meta` key, or `meta` is an empty object.

### Pitfall 2: Array/Object Meta Silently Not Exposed Without Items/Properties Schema

**What goes wrong:** Registering `type: 'array'` with `show_in_rest: true` (not the expanded schema) causes the field to not appear in REST responses, or WordPress logs a `_doing_it_wrong()` notice.

**Why it happens:** WordPress 5.3 added array/object support but requires the schema to be fully defined. Simple `show_in_rest: true` only works for scalar types (`string`, `integer`, `boolean`, `number`).

**How to avoid:** Use `show_in_rest: array('schema' => array('type' => 'array', 'items' => ...))` for every array-type field. Use `show_in_rest: array('schema' => array('type' => 'object', 'properties' => ...))` for every object-type field.

**Warning signs:** REST response omits the meta field entirely; `_doing_it_wrong()` notice in debug log.

### Pitfall 3: Legacy save_post Callback Wipes Registered Meta on Block Editor Save

**What goes wrong:** After switching the Talks CPT to `show_in_rest: true`, a block editor save triggers `speekr_save_mb` via REST. `$_POST` is empty, so all `isset($_POST['speekr-media-links'])` guards fail silently, and `update_post_meta($post_id, 'speekr-media-links', ...)` is called with an empty value or the wrong branch runs.

**Why it happens:** The block editor saves via REST API (JSON body, no `$_POST`). WordPress still fires `save_post` during REST saves. The existing callback checks `isset($_POST['_wpnonce'])` — which correctly returns early — but the nonce check happens AFTER the capability check, meaning if nonce is missing it bails cleanly. **Verification needed:** Current code has `if ( ! isset( $_POST['_wpnonce'] ) ) return;` at the top — this actually fires the correct bail path already for REST requests. However, the user decision specifies adding explicit gates, which is best practice for clarity and future safety.

**How to avoid:** Add `if ( empty( $_POST ) ) { return; }` as the very first check in `speekr_save_mb`, plus `wp_is_post_autosave()` and `wp_is_post_revision()` checks.

**Warning signs:** Block editor save causes media links, summary, or conference data to be wiped; REST response shows empty meta values after update.

### Pitfall 4: Talks CPT Slug `register_meta_box_cb` Triggers Meta Boxes in Block Editor

**What goes wrong:** The existing Talks CPT has `'register_meta_box_cb' => 'speekr_custom_meta_boxes'`. Once `show_in_rest: true` and `'editor'` are added to supports, the block editor will attempt to render these meta boxes in a compatibility layer, causing a conflicting save flow.

**Why it happens:** WordPress renders registered meta boxes inside the block editor's "compatibility" meta box area. Both the REST API path and the classic form-post path can fire for the same save event.

**How to avoid:** The `!empty($_POST)` gate on `speekr_save_mb` handles this correctly — when the block editor saves via REST, the meta box save function bails immediately. When the user explicitly saves via the meta box compatibility layer (which does send a `$_POST` request), the callback runs normally.

### Pitfall 5: `single: true` Must Be Set for All New Meta Fields

**What goes wrong:** `single` defaults to `false`. Without `single: true`, `get_post_meta($id, '_speekr_headshots', true)` returns a string (the first value); `get_post_meta($id, '_speekr_headshots')` returns an array of all values. REST API returns the full array regardless, creating inconsistency between PHP and REST access.

**How to avoid:** All new meta fields in this phase use `'single' => true`. This stores one meta row per key and ensures consistent return values.

### Pitfall 6: `auth_callback` is Required for REST API Write Access

**What goes wrong:** Without an `auth_callback`, WordPress uses a default that may deny writes via the REST API for some user roles, or allow writes for any logged-in user depending on WP version.

**Why it happens:** The default auth callback in some WP versions returns `false` for meta keys starting with `_` (underscore), treating them as protected. All new Speekr meta keys use `_` prefix.

**How to avoid:** Provide an explicit `auth_callback` on every `register_post_meta()` call. A simple `current_user_can('edit_posts')` check is appropriate here since these are speaker-owned profiles.

**Warning signs:** REST `PUT /wp-json/wp/v2/speekr_speaker/123` with meta update returns 403 or silently ignores meta changes.

---

## Code Examples

### Complete Speaker Profile CPT Registration

```php
// Source: https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-rest-api-support-for-custom-content-types/
// Source: https://developer.wordpress.org/reference/functions/register_post_type/
function speekr_register_speaker_profile_cpt() {
    $labels = array(
        'name'          => __( 'Speaker Profiles', 'speekr' ),
        'singular_name' => __( 'Speaker Profile', 'speekr' ),
        'add_new_item'  => __( 'Add New Speaker Profile', 'speekr' ),
        'edit_item'     => __( 'Edit Speaker Profile', 'speekr' ),
        'all_items'     => __( 'All Speaker Profiles', 'speekr' ),
    );
    $args = array(
        'labels'        => $labels,
        'public'        => true,
        'show_in_rest'  => true,        // block editor + REST API
        'menu_icon'     => 'dashicons-id-alt',
        'supports'      => array(
            'title',
            'editor',           // block editor (not classic)
            'thumbnail',
            'custom-fields',    // expose meta via REST
            'revisions',
        ),
        'rewrite'       => array( 'slug' => 'speaker' ),
    );
    register_post_type( 'speekr_speaker', $args );
}
add_action( 'init', 'speekr_register_speaker_profile_cpt' );
```

### Registering _speekr_headshots (Array of Objects)

```php
// Source: https://make.wordpress.org/core/2019/10/03/wp-5-3-supports-object-and-array-meta-types-in-the-rest-api/
// Verified: array of objects requires items.type = object + items.properties
add_action( 'init', function() {
    register_post_meta(
        'speekr_speaker',
        '_speekr_headshots',
        array(
            'single'            => true,
            'type'              => 'array',
            'show_in_rest'      => array(
                'schema' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'                 => 'object',
                        'additionalProperties' => false,
                        'properties'           => array(
                            'id'    => array(
                                'type'    => 'integer',
                                'minimum' => 1,
                            ),
                            'label' => array(
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'sanitize_callback' => 'speekr_sanitize_headshots',
            'auth_callback'     => function() {
                return current_user_can( 'edit_posts' );
            },
        )
    );
} );
```

### Registering _speekr_social_links (Array of Objects)

```php
// Source: same pattern as _speekr_headshots
add_action( 'init', function() {
    register_post_meta(
        'speekr_speaker',
        '_speekr_social_links',
        array(
            'single'            => true,
            'type'              => 'array',
            'show_in_rest'      => array(
                'schema' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'                 => 'object',
                        'additionalProperties' => false,
                        'properties'           => array(
                            'platform' => array( 'type' => 'string' ),
                            'url'      => array(
                                'type'   => 'string',
                                'format' => 'uri',
                            ),
                            'label'    => array( 'type' => 'string' ),
                        ),
                    ),
                ),
            ),
            'sanitize_callback' => 'speekr_sanitize_social_links',
            'auth_callback'     => function() {
                return current_user_can( 'edit_posts' );
            },
        )
    );
} );
```

### Registering _speekr_rider (Single Object)

```php
// Source: https://make.wordpress.org/core/2019/10/03/wp-5-3-supports-object-and-array-meta-types-in-the-rest-api/
add_action( 'init', function() {
    register_post_meta(
        'speekr_speaker',
        '_speekr_rider',
        array(
            'single'            => true,
            'type'              => 'object',
            'show_in_rest'      => array(
                'schema' => array(
                    'type'                 => 'object',
                    'additionalProperties' => false,
                    'properties'           => array(
                        'av'            => array( 'type' => 'string' ),
                        'travel'        => array( 'type' => 'string' ),
                        'dietary'       => array( 'type' => 'string' ),
                        'accessibility' => array( 'type' => 'string' ),
                    ),
                ),
            ),
            'sanitize_callback' => 'speekr_sanitize_rider',
            'auth_callback'     => function() {
                return current_user_can( 'edit_posts' );
            },
        )
    );
} );
```

### Sanitize Callbacks for Complex Fields

WP's built-in schema validation handles type checking, but sanitize_callback should still sanitize string values before storage.

```php
// Sanitize headshots array
function speekr_sanitize_headshots( $value ) {
    if ( ! is_array( $value ) ) {
        return array();
    }
    $clean = array();
    foreach ( $value as $item ) {
        if ( ! isset( $item['id'] ) ) {
            continue;
        }
        $clean[] = array(
            'id'    => absint( $item['id'] ),
            'label' => isset( $item['label'] ) ? sanitize_text_field( $item['label'] ) : '',
        );
    }
    return $clean;
}

// Sanitize social links array
function speekr_sanitize_social_links( $value ) {
    if ( ! is_array( $value ) ) {
        return array();
    }
    $clean = array();
    foreach ( $value as $item ) {
        if ( empty( $item['platform'] ) || empty( $item['url'] ) ) {
            continue;
        }
        $clean[] = array(
            'platform' => sanitize_key( $item['platform'] ),
            'url'      => esc_url_raw( $item['url'] ),
            'label'    => isset( $item['label'] ) ? sanitize_text_field( $item['label'] ) : '',
        );
    }
    return $clean;
}

// Sanitize rider object
function speekr_sanitize_rider( $value ) {
    if ( ! is_array( $value ) ) {
        return array();
    }
    return array(
        'av'            => isset( $value['av'] )            ? sanitize_textarea_field( $value['av'] )            : '',
        'travel'        => isset( $value['travel'] )        ? sanitize_textarea_field( $value['travel'] )        : '',
        'dietary'       => isset( $value['dietary'] )       ? sanitize_textarea_field( $value['dietary'] )       : '',
        'accessibility' => isset( $value['accessibility'] ) ? sanitize_textarea_field( $value['accessibility'] ) : '',
    );
}
```

### Updated Talks CPT (adding REST and block editor support)

The existing `speekr_register_post_types()` in `inc/common/custom-posts.php` needs:
1. `'show_in_rest' => true` added to `$args`
2. `'editor'` and `'custom-fields'` added to `supports`

```php
// In inc/common/custom-posts.php — modify existing $args array
$args = array(
    // ... existing args ...
    'show_in_rest' => true,     // ADD
    'supports'     => array(
        'title',
        'editor',               // ADD (was commented out)
        'author',
        'thumbnail',
        'custom-fields',        // ADD (was commented out)
        'revisions',
        'page-attributes'
    ),
);
```

### Updating save_post Gate in custom-meta-boxes.php

```php
function speekr_save_mb( $post_id ) {

    // Gate: REST API requests don't send $_POST — bail to prevent wiping registered meta
    if ( empty( $_POST ) ) {
        return;
    }

    // Gate: REST autosave endpoint
    if ( wp_is_post_autosave( $post_id ) ) {
        return;
    }

    // Gate: revision saves
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    // Existing checks follow unchanged...
    if ( ! isset( $_POST['_wpnonce'] ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // ... rest of function unchanged
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Classic editor meta boxes only | `register_post_meta()` + `show_in_rest` + `useEntityProp()` | WP 5.0 (Gutenberg), meta schema WP 5.3 | Meta must be registered with full REST schema to work in block editor |
| `register_meta('post', ...)` directly | `register_post_meta($post_type, ...)` | WP 4.9.8 | `register_post_meta` scopes to specific post type; avoids exposing meta across all post types |
| `type: 'string'` for everything | Explicit `type: 'array'` or `type: 'object'` with schema | WP 5.3 (Oct 2019) | Complex data stored as actual typed values in REST, not serialized strings |
| Gating on `DOING_AUTOSAVE` only | `!empty($_POST)` + autosave + revision checks | WP 5.0+ (Gutenberg era) | REST path never sets `$_POST`; constant-only check is insufficient |
| `defined('REST_REQUEST') && REST_REQUEST` | `!empty($_POST)` (simpler, same effect for `$_POST`-dependent code) | WP 6.5 added `wp_is_serving_rest_request()` | `!empty($_POST)` is simpler and version-agnostic for this specific use case |

---

## Open Questions

1. **`additionalProperties: false` on object schemas**
   - What we know: JSON Schema `additionalProperties: false` restricts the object to only the declared `properties`. WordPress REST API respects this — undeclared properties are rejected.
   - What's unclear: Whether block editor JS passes additional properties during saves (e.g., `_links`, `__unstableHTML`). This has not been confirmed against the specific WP version on this site.
   - Recommendation: Include `additionalProperties: false` in the schema to prevent garbage data, but test the REST write path during verification. If block editor saves fail, remove `additionalProperties: false` as a fallback.

2. **Conferences CPT slug and meta key naming convention**
   - What we know: Talks CPT uses slug `talks` and meta keys like `speekr-conf` (hyphenated, no underscore prefix). New fields use `_speekr_*` prefix (underscore-prefixed, protected).
   - What's unclear: The Conferences CPT post type key (e.g., `speekr_conference`) and its relationship to the existing `speekr-conf` meta on Talks has not been specified in detail in the requirements beyond CONF-01.
   - Recommendation: Use `speekr_conference` as the post type key (matches `speekr_speaker` convention) and `_speekr_conf_*` prefix for Conference meta fields.

3. **Default values for array meta fields**
   - What we know: `register_post_meta()` supports a `default` parameter (WP 5.5+). For array type with `single: true`, the default should be an empty array `[]`.
   - What's unclear: Whether omitting `default` causes any issue when the meta key doesn't exist yet (new CPT, no data).
   - Recommendation: Add `'default' => array()` to all array-type meta registrations to ensure consistent empty-array returns instead of `false` when not set.

---

## Existing Code Audit Notes

### `speekr_save_mb` current state (inc/admin/custom-meta-boxes.php, line 333)

The function currently has:
- `if ( ! isset( $_POST['_wpnonce'] ) ) { return; }` — catches REST saves (no nonce = bail). This already prevents the worst case.
- `if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }` — classic autosave gate
- `if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }` — capability check
- No `wp_is_post_revision()` or `wp_is_post_autosave()` checks

The nonce check IS the current de facto REST gate, but it's fragile — a nonce could theoretically be injected into a REST request. The `!empty($_POST)` gate is the semantically correct and explicit guard.

### Existing Talks meta keys (not to be migrated, kept as-is)

These existing meta keys on the Talks CPT are registered informally (no `register_post_meta()`). They are NOT being touched in this phase:
- `speekr-media-links` (array, stored serialized)
- `speekr-conf` (array, stored serialized)
- `speekr-summary` (string)
- `speekr-as-article` (string: 'on'/'off')
- `speekr-is-featured` (boolean)

**The only Talks-related work in this phase** is: add `show_in_rest: true`, `'editor'`, and `'custom-fields'` to the CPT registration, plus gate the `speekr_save_mb` callback. No meta migration.

### `speekr_get_cpt_slug()` function

The Talks CPT uses a filterable slug via `speekr_get_cpt_slug()` which returns `'talks'`. The taxonomy must reference this same slug to attach correctly:

```php
register_taxonomy( 'speekr_topic', array( speekr_get_cpt_slug() ), $args );
```

---

## Sources

### Primary (HIGH confidence)
- `https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-rest-api-support-for-custom-content-types/` — CPT and taxonomy REST registration patterns
- `https://developer.wordpress.org/reference/functions/register_post_meta/` — parameter documentation
- `https://make.wordpress.org/core/2019/10/03/wp-5-3-supports-object-and-array-meta-types-in-the-rest-api/` — array/object schema requirements (canonical WP Core post)
- `https://developer.wordpress.org/news/2023/03/creating-a-custom-block-that-stores-post-meta/` — `register_post_meta` for block editor + `auth_callback` pattern
- `https://developer.wordpress.org/reference/functions/register_taxonomy/` — taxonomy parameters
- Code audit of `inc/admin/custom-meta-boxes.php`, `inc/common/custom-posts.php`, `inc/classes/Speekr.php`

### Secondary (MEDIUM confidence)
- `https://wp-kama.com/function/register_meta` — type options, sanitize_callback, auth_callback signatures (corroborated by official docs)
- `https://github.com/WordPress/gutenberg/issues/12903` — save_post double-fire with REST API
- `https://developer.wordpress.org/reference/functions/wp_is_serving_rest_request/` — WP 6.5 REST detection (noted but not used — user decision favors `!empty($_POST)`)

### Tertiary (LOW confidence)
- None — all critical claims verified against official WP documentation or direct code audit.

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all APIs are stable WP core, verified against developer.wordpress.org
- Array/object meta schema: HIGH — WP 5.3 core announcement + cross-verified with multiple sources
- save_post gating: HIGH — verified by direct code audit of existing callback + official docs
- Architecture (file structure): HIGH — follows existing plugin patterns, no speculation
- sanitize_callback implementations: MEDIUM — patterns are standard PHP/WP sanitization; exact function choices are Claude's discretion per CONTEXT.md

**Research date:** 2026-03-02
**Valid until:** 2026-09-02 (stable WP core APIs — 6-month window)
