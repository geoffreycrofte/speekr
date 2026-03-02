# Phase 4: Display Blocks — Research

**Researched:** 2026-03-02
**Domain:** WordPress dynamic blocks (PHP render.php), Leaflet.js, Nominatim geocoding, ZIP generation, client-side topic filtering
**Confidence:** HIGH

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

#### Speaker Profile — Layout options
- Block attribute offers two layout choices: (1) headshot left, text wraps right;
  (2) headshot above, full-width content below
- Default layout: headshot left (classic press-kit style)

#### Speaker Profile — Bio content
- Renders short bio (meta field) followed by post_content (full block editor body)
- Both are always shown in sequence; no toggle needed

#### Speaker Profile — Social links display
- Global Settings page option controls display style (not a per-block attribute)
- Default: accessible icon-only row with visually-hidden platform name
  (`<span class="screen-reader-text">LinkedIn</span>`)

#### Speaker Profile — Press kit download
- Block-level "Allow download" toggle (block attribute, off by default)
- When enabled: renders a "Download press kit" button
- Download delivers a `.zip` containing:
  - All headshot images
  - A combined speaker-kit `.md` (or `.html`) file covering bio, social links, and rider
  - If a combined file isn't achievable cleanly: separate `bio.md`, `social.md`,
    `preferences.md` + `bio.html` for post_content
- Generation happens server-side on demand (PHP, no pre-built files)

#### Talks List — Layout
- Block attribute toggles between two layouts: grid (2–3 columns) and single-column list
- Editor chooses per placement; grid is the default

#### Talks List — Card content
- Media visual (priority order): YouTube thumbnail > Vimeo thumbnail > embedded slide
  player (if available) > post featured image > generic placeholder image
- Title
- Short summary (meta field)
- Date + location (from the Conference meta linked to the talk, or speekr-conf object)
- "Read more" link button — shown only when "Make this Speekr item a blog post"
  is enabled on the talk (links to the talk's single post page)

#### Talks List — Topic filter
- Horizontal scrollable pill tabs above the list: "All" first, then one tab per topic
- Active tab is highlighted; clicking a tab filters talks client-side (no page reload)
- Clicking the active tab returns to "All"

#### Talks List — Empty state
- Friendly message shown when no published talks exist
- For logged-in editors: message includes a direct link to create the first talk
- For visitors: friendly message without the admin link

#### Conference geocoding
- Auto-geocode city + country on post save using Nominatim (OpenStreetMap)
  — free, no API key required
- Geocoding runs server-side via PHP `wp_remote_get()` at `save_post`
- On failure (not found, timeout, rate-limit): show admin notice
  "Could not resolve location — add coordinates manually" and expose
  manual lat/lng input fields in the editor sidebar as fallback
- Coordinates stored as `_speekr_conf_lat` and `_speekr_conf_lng` post meta

#### Conference Map — Pin popup
- Clicking a pin shows: conference name, date, city, talk title, event URL link,
  talk page link (if talk has a blog post)

#### Conference Map — Clustered pins
- Uses Leaflet.markercluster plugin for cities with multiple conference appearances
- Cluster marker shows count; clicking expands to individual pins
- Leaflet and markercluster load only on the frontend via block `viewScript`
  (never in the block editor)

#### Media embeds — Multiple video sources
- Priority order: YouTube → Vimeo → Dailymotion
- Only the highest-priority available source renders as an embed
- Other video URLs are not shown in the video area (they may appear in "Other links")

#### Media embeds — Slides
- SpeakerDeck, Slideshare, generic Slides URL: embed as iframe player if the
  provider supports oEmbed or a standard embed URL
- Falls back to thumbnail + "View slides on [provider]" link if embedding fails

#### Media embeds — No media fallback
- Fallback order: post featured image → generic speaker banner placeholder image
- Media area always occupies space (no layout reflow when media is absent)

#### Media embeds — Other links placement
- Claude's Discretion: group all non-video links (Slides + Other custom links)
  in a "Resources" section below the primary embed

### Claude's Discretion

- **Media embeds — Other links placement**: Group all non-video links (Slides + Other
  custom links) in a "Resources" section below the primary embed

### Deferred Ideas (OUT OF SCOPE)

None — discussion stayed within phase scope.
</user_constraints>

---

## Summary

Phase 4 builds five public-facing dynamic blocks for the Speekr WordPress plugin. All blocks follow the established project pattern: block source in `src/blocks/{name}/`, compiled to `build/blocks/{name}/`, registered automatically via the glob loop in `inc/blocks/blocks.php`. Each block needs a `block.json` declaring `"render": "file:./render.php"`, an `edit.js` (editor placeholder or inspector controls only), and a `render.php` that reads post meta and outputs frontend HTML.

The most technically distinct block is Conference Map: it requires Leaflet.js 1.9.4 + Leaflet.markercluster 1.4.1 loaded only on the frontend via `viewScript`, a PHP render that serializes geo-data into a `data-*` attribute, and a `view.js` that reads that attribute and initialises the map. Geocoding must be wired to `save_post_{speekr_conference}` hook, calling the Nominatim API (1 req/sec max, custom User-Agent required) and caching the result in `_speekr_conf_lat` / `_speekr_conf_lng` post meta. These two meta fields are not yet registered in the codebase and must be added in Phase 4.

The press-kit ZIP download is a separate concern: a `register_rest_route` endpoint that generates a ZIP on demand via PHP `ZipArchive`, streams it as an attachment, and is linked from the Speaker Profile `render.php` only when the "allow download" attribute is true. The "Make this Speekr item a blog post" flag already exists as the `speekr-as-article` post meta key (`'on'` / empty string), used in `inc/admin/custom-meta-boxes.php` and `inc/front/lists.php` — the render.php files must read this value the same way.

**Primary recommendation:** Implement all five blocks as dynamic blocks with `render.php`. Bundle Leaflet + markercluster locally via npm, import them in `view.js`, and output a single compiled `view.js` via `viewScript`. Use `data-speekr-map` JSON attributes on the map container to pass server-rendered geodata to the frontend script.

---

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| `@wordpress/scripts` | 31.5.0 (already installed) | Build pipeline, webpack, asset generation | Already in devDependencies — no change |
| Leaflet.js | 1.9.4 (stable) | Interactive map, tile layer, pin markers | Most widely deployed web maps library; 2.0 is alpha only |
| leaflet.markercluster | 1.4.1 (latest stable) | Cluster overlapping conference pins | Official Leaflet clustering plugin |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `leaflet` (npm) | ^1.9.4 | Bundled into view.js | Conference Map block |
| `leaflet.markercluster` (npm) | ^1.4.1 | Bundled into view.js | Conference Map block |
| PHP `ZipArchive` | PHP built-in | Generate press-kit ZIP on demand | Speaker Profile download endpoint |
| `wp_remote_get` | WordPress core | Nominatim + oEmbed HTTP calls | Geocoding + slide embeds |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Bundled Leaflet (npm) | wp_register_script + CDN URL in viewScript array | CDN approach avoids build step but adds external dependency; bundling is more reliable offline |
| PHP ZipArchive | WP Filesystem + system zip | ZipArchive is portable and available on all MAMP/modern PHP installations |
| Nominatim | Google Maps Geocoding API | Nominatim is free with no key; Google requires a billing account |

**Installation (new packages only):**
```bash
npm install leaflet leaflet.markercluster
```

---

## Architecture Patterns

### Recommended Block File Structure

Each display block follows this pattern (consistent with existing Phase 3 blocks):

```
src/blocks/{name}/
├── block.json          # Declares render, viewScript, viewStyle, attributes
├── index.js            # Registers block in editor (edit + save)
├── edit.js             # Editor placeholder or InspectorControls
├── render.php          # Server-side PHP — all frontend HTML lives here
├── view.js             # Frontend JS (only Conference Map and Talks List need this)
└── style.scss          # Optional: frontend styles (compiled to style-index.css)
```

WordPress auto-detects all `block.json` files via `glob( SPEEKR_DIRNAME . '/build/blocks/*/block.json' )` — no manual registration needed.

### Pattern 1: Dynamic Block with render.php (all five blocks)

**What:** Block stores only attributes in post content. All HTML is generated server-side on each page load.
**When to use:** Whenever block output depends on database queries, post meta, or should update without re-saving the page.

**block.json:**
```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "speekr/speaker-profile",
    "version": "1.0.0",
    "title": "Speaker Profile",
    "category": "speekr",
    "description": "Renders the speaker's profile, bio, social links, and rider.",
    "attributes": {
        "layout": {
            "type": "string",
            "default": "side-by-side"
        },
        "allowDownload": {
            "type": "boolean",
            "default": false
        }
    },
    "editorScript": "file:./index.js",
    "render": "file:./render.php",
    "style": "file:./style-index.css"
}
```

**render.php signature (verified pattern):**
```php
<?php
// $attributes  — block attributes (array)
// $content     — inner blocks HTML (empty string for leaf blocks)
// $block       — WP_Block instance (use $block->context for parent context)

$layout        = isset( $attributes['layout'] ) ? $attributes['layout'] : 'side-by-side';
$allow_download = ! empty( $attributes['allowDownload'] );

// Query the single speekr_speaker post (this plugin manages exactly one speaker)
$speaker = get_posts( array(
    'post_type'      => 'speekr_speaker',
    'posts_per_page' => 1,
    'post_status'    => 'publish',
) );
if ( empty( $speaker ) ) {
    return '';
}
$post_id = $speaker[0]->ID;
$headshots   = get_post_meta( $post_id, '_speekr_headshots', true ) ?: array();
$bio_short   = get_post_meta( $post_id, '_speekr_bio_short', true ) ?: '';
$social_links = get_post_meta( $post_id, '_speekr_social_links', true ) ?: array();
$rider       = get_post_meta( $post_id, '_speekr_rider', true ) ?: array();

ob_start();
// … HTML output …
return ob_get_clean();
```

**index.js (editor registration):**
```js
import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';

registerBlockType( metadata.name, {
    ...metadata,
    edit: Edit,
    save: () => null,  // null = dynamic block, PHP renders the frontend
} );
```

### Pattern 2: viewScript for Frontend-Only JavaScript (Conference Map, Talks List)

**What:** JS declared in `viewScript` is enqueued only when the block is present on the page. It never loads in the editor.
**When to use:** Maps, client-side filtering, any interactive behaviour the block editor does not need.

**block.json additions:**
```json
{
    "viewScript": "file:./view.js",
    "viewStyle":  "file:./view-style.css"
}
```

**Passing data from PHP to JS:**
```php
// In render.php: encode server data into a data attribute on the container
$map_data = array();
foreach ( $conferences as $conf ) {
    $lat = get_post_meta( $conf->ID, '_speekr_conf_lat', true );
    $lng = get_post_meta( $conf->ID, '_speekr_conf_lng', true );
    if ( ! $lat || ! $lng ) continue;
    $map_data[] = array(
        'lat'       => (float) $lat,
        'lng'       => (float) $lng,
        'name'      => esc_attr( get_the_title( $conf->ID ) ),
        'date'      => esc_attr( get_post_meta( $conf->ID, '_speekr_conf_date', true ) ),
        'city'      => esc_attr( get_post_meta( $conf->ID, '_speekr_conf_city', true ) ),
        'talkTitle' => '',   // resolved from _speekr_conf_talk_ref
        'eventUrl'  => esc_url( get_post_meta( $conf->ID, '_speekr_conf_url', true ) ),
        'talkUrl'   => '',   // set if talk has speekr-as-article = 'on'
    );
}
$json = wp_json_encode( $map_data );
echo '<div class="wp-block-speekr-conference-map" data-speekr-map="' . esc_attr( $json ) . '"></div>';
```

**view.js (Conference Map):**
```js
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';

document.querySelectorAll( '.wp-block-speekr-conference-map' ).forEach( ( el ) => {
    const data = JSON.parse( el.dataset.speekrMap || '[]' );
    const map  = L.map( el ).setView( [ 20, 0 ], 2 );
    L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
    } ).addTo( map );

    const cluster = L.markerClusterGroup();
    data.forEach( ( pin ) => {
        const marker = L.marker( [ pin.lat, pin.lng ] );
        marker.bindPopup( buildPopup( pin ) );
        cluster.addLayer( marker );
    } );
    map.addLayer( cluster );
} );

function buildPopup( pin ) {
    let html = `<strong>${ pin.name }</strong><br>${ pin.date } — ${ pin.city }`;
    if ( pin.talkTitle ) html += `<br>${ pin.talkTitle }`;
    if ( pin.eventUrl )  html += `<br><a href="${ pin.eventUrl }">Event site</a>`;
    if ( pin.talkUrl )   html += ` | <a href="${ pin.talkUrl }">Talk page</a>`;
    return html;
}
```

### Pattern 3: Nominatim Geocoding on save_post

**What:** When a Conference post is saved with city + country, auto-fetch lat/lng from Nominatim and store in post meta.
**Hook:** `save_post_speekr_conference` (post-type-specific — more targeted than generic `save_post`)

```php
// In inc/cpt/conferences.php (add alongside existing meta registration)
function speekr_geocode_conference_on_save( $post_id ) {
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $city    = get_post_meta( $post_id, '_speekr_conf_city', true );
    $country = get_post_meta( $post_id, '_speekr_conf_country', true );
    if ( empty( $city ) || empty( $country ) ) return;

    // Skip if already geocoded (avoid re-hitting API on every save)
    $existing_lat = get_post_meta( $post_id, '_speekr_conf_lat', true );
    if ( $existing_lat ) return;

    $query    = urlencode( $city . ', ' . $country );
    $url      = 'https://nominatim.openstreetmap.org/search?q=' . $query . '&format=json&limit=1';
    $response = wp_remote_get( $url, array(
        'headers' => array(
            'User-Agent' => 'Speekr WordPress Plugin/1.0 (https://github.com/geoffreycrofte/speekr)',
        ),
        'timeout' => 10,
    ) );

    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
        set_transient( 'speekr_geocode_failed_' . $post_id, 1, MINUTE_IN_SECONDS * 5 );
        return;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $body[0]['lat'] ) || empty( $body[0]['lon'] ) ) {
        set_transient( 'speekr_geocode_failed_' . $post_id, 1, MINUTE_IN_SECONDS * 5 );
        return;
    }

    update_post_meta( $post_id, '_speekr_conf_lat', (float) $body[0]['lat'] );
    update_post_meta( $post_id, '_speekr_conf_lng', (float) $body[0]['lon'] );
}
add_action( 'save_post_speekr_conference', 'speekr_geocode_conference_on_save' );
```

**Admin notice for geocoding failure:**
```php
function speekr_geocode_failure_notice() {
    global $post;
    if ( ! isset( $post->ID ) || get_post_type( $post->ID ) !== 'speekr_conference' ) return;
    if ( ! get_transient( 'speekr_geocode_failed_' . $post->ID ) ) return;
    delete_transient( 'speekr_geocode_failed_' . $post->ID );
    echo '<div class="notice notice-warning is-dismissible"><p>'
        . esc_html__( 'Could not resolve location — add coordinates manually.', 'speekr' )
        . '</p></div>';
}
add_action( 'admin_notices', 'speekr_geocode_failure_notice' );
```

### Pattern 4: Press-Kit ZIP via REST Endpoint

**What:** A custom REST endpoint generates a ZIP on demand, streams it as a download.
**Why not a block viewScript:** Downloads require PHP headers; a REST route is the correct WordPress way.

```php
// In inc/blocks/blocks.php or new inc/front/press-kit.php
add_action( 'rest_api_init', function() {
    register_rest_route( 'speekr/v1', '/press-kit/(?P<id>\d+)', array(
        'methods'             => 'GET',
        'callback'            => 'speekr_press_kit_download',
        'permission_callback' => '__return_true',  // public download
        'args'                => array(
            'id' => array( 'sanitize_callback' => 'absint' ),
        ),
    ) );
} );

function speekr_press_kit_download( WP_REST_Request $request ) {
    $post_id = $request->get_param( 'id' );
    if ( get_post_type( $post_id ) !== 'speekr_speaker' ) {
        return new WP_Error( 'not_found', 'Speaker not found', array( 'status' => 404 ) );
    }

    // Verify block attribute allows download (check post meta flag set by render.php)
    // Alternatively: rely on the endpoint URL only being rendered when allowDownload = true

    $headshots   = get_post_meta( $post_id, '_speekr_headshots', true ) ?: array();
    $bio_short   = get_post_meta( $post_id, '_speekr_bio_short', true ) ?: '';
    $social      = get_post_meta( $post_id, '_speekr_social_links', true ) ?: array();
    $rider       = get_post_meta( $post_id, '_speekr_rider', true ) ?: array();
    $post_content = get_post_field( 'post_content', $post_id );

    $zip_path = sys_get_temp_dir() . '/speekr-press-kit-' . $post_id . '-' . time() . '.zip';
    $zip = new ZipArchive();
    if ( $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
        return new WP_Error( 'zip_failed', 'Could not create archive', array( 'status' => 500 ) );
    }

    // Add headshot images
    foreach ( $headshots as $hs ) {
        $img_path = get_attached_file( $hs['id'] );
        if ( $img_path && file_exists( $img_path ) ) {
            $zip->addFile( $img_path, 'headshots/' . basename( $img_path ) );
        }
    }

    // Build combined speaker-kit.md
    // … build $kit_md string from $bio_short, $social, $rider, $post_content …
    $zip->addFromString( 'speaker-kit.md', $kit_md );

    $zip->close();

    // Stream to browser
    header( 'Content-Type: application/zip' );
    header( 'Content-Disposition: attachment; filename="speaker-kit.zip"' );
    header( 'Content-Length: ' . filesize( $zip_path ) );
    readfile( $zip_path );
    unlink( $zip_path );
    exit;
}
```

**In render.php (Speaker Profile), link to REST endpoint:**
```php
if ( $allow_download ) {
    $download_url = rest_url( 'speekr/v1/press-kit/' . $post_id );
    echo '<a href="' . esc_url( $download_url ) . '" class="speekr-press-kit-download">'
        . esc_html__( 'Download press kit', 'speekr' ) . '</a>';
}
```

### Anti-Patterns to Avoid

- **Enqueuing Leaflet globally:** Using `wp_enqueue_scripts` to load Leaflet on every page wastes bandwidth. Use `viewScript` only.
- **Enqueuing Leaflet via editorScript or script:** Loads Leaflet in the block editor, bloating the admin. Use `viewScript` only.
- **Rebuilding the oEmbed fetcher:** WordPress has `wp_oembed_get()` — use it for YouTube, Vimeo, SpeakerDeck, Slideshare. Fall back to manual iframe only if oEmbed fails.
- **Geocoding on every page load:** Nominatim has a hard limit of 1 req/sec. Geocode once at `save_post` and store the result. Never geocode in `render.php`.
- **Reading `speekr-as-article` as a boolean directly:** It is stored as string `'on'` or empty string. Check with `=== 'on'`, not truthiness.
- **Not returning `null` from `save` in index.js:** Without `save: () => null`, WordPress will try to validate the static markup on every editor load and throw block validation errors for dynamic blocks.
- **Outputting un-escaped data in render.php:** All meta values must pass through `esc_html()`, `esc_url()`, or `esc_attr()` before output. This is public-facing HTML.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| YouTube/Vimeo/SpeakerDeck embedding | Custom oEmbed fetcher | `wp_oembed_get( $url )` | WordPress already handles oEmbed discovery, caching (via transients), and error handling |
| SpeakerDeck embed specifically | Direct API call with custom JSON parsing | `wp_oembed_get()` first; fall back to `speekr_get_speakerdeck_iframe()` (already exists) | The project already has `speekr_get_speakerdeck_iframe()` in `inc/functions/helpers.php` — reuse it |
| Slideshare embed | Custom Slideshare API call | `speekr_get_slideshare_iframe()` (already exists in `inc/functions/helpers.php`) | Identical reasoning |
| YouTube video ID extraction | Custom URL parser | `speekr_get_youtube_id()` (already exists in `inc/functions/helpers.php`) | Already handles the standard `?v=` URL pattern |
| Vimeo video ID extraction | Custom regex | `speekr_get_vimeo_id()` (already exists in `inc/functions/helpers.php`) | Already handles the standard path pattern |
| Map clustering | Custom cluster implementation | `leaflet.markercluster` npm package | Handles edge cases: animated expand/collapse, count display, nested clusters |
| HTTP archive download | File system assembly + manual ZIP | PHP `ZipArchive` (built-in) | Portable, available on MAMP PHP 8.2; no shell exec required |
| Block asset enqueueing | Manual `wp_enqueue_script` calls | `block.json` `viewScript` / `viewStyle` | WordPress handles enqueue/dequeue automatically, only when block is present |
| Topic data in Talks List | Separate AJAX request at runtime | Inline data via `wp_json_encode` in render.php | No extra HTTP request; data is available server-side at render time |

**Key insight:** The helpers in `inc/functions/helpers.php` were written for the legacy `speekr-media-links` array structure. Phase 4 render.php files use the new per-key meta (`_speekr_media_youtube`, `_speekr_media_vimeo`, etc.) — the helper functions are still useful for ID/embed extraction, but the meta key access pattern is different.

---

## Common Pitfalls

### Pitfall 1: `speekr-as-article` meta type and key name

**What goes wrong:** The "Make this Speekr item a blog post" flag is stored in the legacy meta key `speekr-as-article` (not `_speekr_as_article`) with value `'on'` (string) or an empty/absent value.
**Why it happens:** It predates the Phase 3 refactor and uses the old classic-editor meta box save path. The value is never `true`/`false`/`1`/`0`.
**How to avoid:** In render.php for Talks List and Single Talk:
```php
$as_article = get_post_meta( $post_id, 'speekr-as-article', true );
$is_blog_post = ( 'on' === $as_article );
```
**Warning signs:** "Read more" link appears for all talks regardless of the toggle, or never appears.

### Pitfall 2: Leaflet icon path resolution after webpack bundling

**What goes wrong:** Leaflet's default marker icons reference image files via relative paths that break when Leaflet is bundled by webpack.
**Why it happens:** Leaflet's internal `L.Icon.Default.prototype._getIconUrl` tries to find `marker-icon.png` relative to the script file location, which is wrong after bundling.
**How to avoid:** Before creating any markers, override the icon paths in view.js:
```js
import L from 'leaflet';
import markerIconUrl from 'leaflet/dist/images/marker-icon.png';
import markerIcon2xUrl from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions( {
    iconUrl:       markerIconUrl,
    iconRetinaUrl: markerIcon2xUrl,
    shadowUrl:     markerShadowUrl,
} );
```
**Warning signs:** Map renders but pins show broken image icons.

### Pitfall 3: `_speekr_conf_lat` and `_speekr_conf_lng` not registered for REST

**What goes wrong:** The Conference Map block's render.php can read meta fine with `get_post_meta()`, but if a future editor panel needs to show/edit coordinates, they won't be accessible via the block editor without `show_in_rest: true`.
**Why it happens:** The two lat/lng meta fields are new in Phase 4. They are not present in `inc/cpt/conferences.php` yet.
**How to avoid:** Add `register_post_meta` calls for `_speekr_conf_lat` and `_speekr_conf_lng` in `inc/cpt/conferences.php` (alongside existing field registrations), with `type: 'number'` and `show_in_rest: true`.
**Warning signs:** Manual lat/lng fallback fields in the editor sidebar do not save/read values.

### Pitfall 4: Nominatim rate limit and User-Agent requirement

**What goes wrong:** API returns 429 or the call is blocked entirely; coordinates are never saved.
**Why it happens:** Nominatim's policy explicitly rejects requests with default HTTP library User-Agents ("stock User-Agents as set by http libraries will not do") and enforces a hard 1 req/sec limit.
**How to avoid:**
- Always set a custom `User-Agent` header: `'Speekr WordPress Plugin/1.0 (https://github.com/geoffreycrofte/speekr)'`
- Only geocode at `save_post`, never in render callbacks
- Skip geocoding if `_speekr_conf_lat` is already set (prevents re-requesting on re-saves)
- Use a transient to flag geocoding failures and surface an admin notice
**Warning signs:** `wp_remote_get` returns `WP_Error` or body is empty JSON `[]`.

### Pitfall 5: Geocoding fires during autosave or revision

**What goes wrong:** Nominatim is called multiple times per editor session, quickly exhausting the rate limit.
**Why it happens:** `save_post` fires on autosaves and revisions. `save_post_{post_type}` does too.
**How to avoid:** Always gate at the top of the geocoding callback:
```php
if ( wp_is_post_autosave( $post_id ) ) return;
if ( wp_is_post_revision( $post_id ) ) return;
if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
```

### Pitfall 6: ZipArchive extension availability

**What goes wrong:** `new ZipArchive()` fails silently or fatally if the PHP Zip extension is not enabled.
**Why it happens:** Some PHP builds don't include `ext-zip` by default.
**How to avoid:** Guard the press-kit endpoint:
```php
if ( ! class_exists( 'ZipArchive' ) ) {
    return new WP_Error( 'zip_unavailable', 'ZIP support not available on this server.', array( 'status' => 501 ) );
}
```
Note: MAMP PHP 8.2 includes `ext-zip` by default. This is a defensive guard for production deployments.

### Pitfall 7: `wp_oembed_get()` caching and Slideshare HTTPS

**What goes wrong:** oEmbed calls in render.php slow the page on first load; Slideshare oEmbed endpoint uses HTTP.
**Why it happens:** `wp_oembed_get()` makes external HTTP calls; the result is cached in a transient automatically by WordPress (default 1 day). The legacy `speekr_get_slideshare_iframe()` in helpers.php uses `http://` — this may fail on sites that block outbound HTTP.
**How to avoid:** For Slideshare, prefer `wp_oembed_get()` over the legacy helper (WordPress handles the HTTPS upgrade and caching). Use the legacy helper only as a fallback.

### Pitfall 8: Talks List client-side filter — topic slugs vs term IDs

**What goes wrong:** Pill tab filter matches talks by wrong identifier; talks don't filter or always show all.
**Why it happens:** Mixing topic slugs and term IDs between the data attribute on talk cards and the data attribute on filter pills.
**How to avoid:** Standardise on term slug in both places. In render.php, set `data-topics="design,accessibility"` on each talk card and match against pill `data-topic="design"`.

### Pitfall 9: Conference Map container needs an explicit height

**What goes wrong:** Leaflet renders a 0px-height map div — the map is invisible.
**Why it happens:** Leaflet requires the container element to have a height set via CSS before the map is initialised.
**How to avoid:** Add a `viewStyle` CSS file or include in the block's `style.scss`:
```css
.wp-block-speekr-conference-map {
    height: 450px;
    width: 100%;
}
```

---

## Code Examples

Verified patterns from official sources and existing codebase:

### YouTube Thumbnail URL (no API key needed)
```php
// Source: YouTube CDN pattern — no API key required
// $url = 'https://www.youtube.com/watch?v=VIDEO_ID'
function speekr_get_youtube_thumbnail_url( $url ) {
    // reuse existing helper
    $video_id = speekr_get_youtube_id( $url );  // returns VIDEO_ID string
    return 'https://img.youtube.com/vi/' . $video_id . '/hqdefault.jpg';
}
```

### Vimeo Thumbnail via oEmbed (no API key for public videos)
```php
// Source: Vimeo oEmbed API — https://vimeo.com/api/oembed.json
function speekr_get_vimeo_thumbnail_url( $url ) {
    $oembed_url = 'https://vimeo.com/api/oembed.json?url=' . urlencode( $url );
    $response   = wp_remote_get( $oembed_url, array( 'timeout' => 5 ) );
    if ( is_wp_error( $response ) ) return false;
    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    return isset( $data['thumbnail_url'] ) ? $data['thumbnail_url'] : false;
}
```

### Media Resolution Priority in render.php (Talks List card)
```php
// Priority: YouTube thumbnail > Vimeo thumbnail > slide embed > featured image > placeholder
function speekr_resolve_card_media( $post_id ) {
    $yt  = get_post_meta( $post_id, '_speekr_media_youtube', true );
    $vim = get_post_meta( $post_id, '_speekr_media_vimeo', true );
    $spd = get_post_meta( $post_id, '_speekr_media_speakerdeck', true );
    $sli = get_post_meta( $post_id, '_speekr_media_slides', true );
    $sls = get_post_meta( $post_id, '_speekr_media_slideshare', true );

    if ( $yt ) {
        return array( 'type' => 'thumbnail', 'url' => speekr_get_youtube_thumbnail_url( $yt ) );
    }
    if ( $vim ) {
        $thumb = speekr_get_vimeo_thumbnail_url( $vim );
        if ( $thumb ) return array( 'type' => 'thumbnail', 'url' => $thumb );
    }
    if ( $spd || $sli || $sls ) {
        return array( 'type' => 'slide_embed', 'url' => $spd ?: $sli ?: $sls );
    }
    if ( has_post_thumbnail( $post_id ) ) {
        return array( 'type' => 'featured_image', 'url' => get_the_post_thumbnail_url( $post_id, 'medium' ) );
    }
    return array( 'type' => 'placeholder', 'url' => SPEEKR_PLUGIN_URL . 'assets/placeholder-talk.png' );
}
```

### Registering _speekr_conf_lat / _speekr_conf_lng (must be added in Phase 4)
```php
// Add to speekr_register_conference_meta() in inc/cpt/conferences.php
register_post_meta( 'speekr_conference', '_speekr_conf_lat', array(
    'single'            => true,
    'type'              => 'number',
    'show_in_rest'      => true,
    'sanitize_callback' => function( $v ) { return (float) $v; },
    'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
) );

register_post_meta( 'speekr_conference', '_speekr_conf_lng', array(
    'single'            => true,
    'type'              => 'number',
    'show_in_rest'      => true,
    'sanitize_callback' => function( $v ) { return (float) $v; },
    'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
) );
```

### block.json for Conference Map (Leaflet frontend-only pattern)
```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "speekr/conference-map",
    "version": "1.0.0",
    "title": "Conference Map",
    "category": "speekr",
    "description": "World map of all conference appearances.",
    "attributes": {
        "height": {
            "type": "number",
            "default": 450
        }
    },
    "editorScript": "file:./index.js",
    "render":       "file:./render.php",
    "viewScript":   "file:./view.js",
    "viewStyle":    "file:./view-style.css"
}
```

### block.json for Talks List (with viewScript for client-side filter)
```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "speekr/talks-list",
    "version": "1.0.0",
    "title": "Talks List",
    "category": "speekr",
    "description": "Grid or list of published talks with topic filtering.",
    "attributes": {
        "layout": {
            "type": "string",
            "default": "grid"
        }
    },
    "editorScript": "file:./index.js",
    "render":       "file:./render.php",
    "viewScript":   "file:./view.js",
    "style":        "file:./style-index.css"
}
```

### Client-side Topic Filter (view.js for Talks List)
```js
// No framework needed — vanilla JS with data-topics attributes
document.querySelectorAll( '.speekr-talks-filter' ).forEach( ( filterBar ) => {
    const container = filterBar.nextElementSibling;
    const pills     = filterBar.querySelectorAll( '[data-topic]' );
    const talks     = container ? container.querySelectorAll( '[data-topics]' ) : [];

    pills.forEach( ( pill ) => {
        pill.addEventListener( 'click', () => {
            const active = pill.dataset.topic;
            const isAll  = active === 'all';

            // Toggle pill active state
            pills.forEach( ( p ) => p.setAttribute( 'aria-pressed', p === pill ? 'true' : 'false' ) );

            // Filter talks
            talks.forEach( ( talk ) => {
                const talkTopics = talk.dataset.topics ? talk.dataset.topics.split( ',' ) : [];
                const show       = isAll || talkTopics.includes( active );
                talk.hidden = ! show;
            } );
        } );
    } );
} );
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `render_callback` in `register_block_type()` PHP array | `"render": "file:./render.php"` in `block.json` | WP 6.1 (2022) | Render PHP file is auto-detected and copied to build by @wordpress/scripts; no manual callback wiring |
| Manual `wp_enqueue_script()` for frontend block JS | `"viewScript": "file:./view.js"` in `block.json` | WP 5.8+ (stable) | Script only loads when block is on page; no conditional enqueue logic in PHP |
| Legacy `speekr-media-links` serialised array | Per-field meta keys (`_speekr_media_youtube` etc.) | Phase 3 refactor | render.php must use new keys; legacy key still exists in DB for old data |
| Global `save_post` hook for meta | `save_post_{$post_type}` hook | WP 3.7+ | More targeted; avoids firing on every CPT save |

**Deprecated / avoid in Phase 4:**
- `speekr-media-links` (legacy serialised array): Do NOT read this in new render.php files. Read individual `_speekr_media_*` meta keys instead.
- `speekr-conf` (legacy object in old helpers.php): The new Conference CPT uses separate meta fields (`_speekr_conf_date`, `_speekr_conf_city` etc.) and the `speekr-conf` object key on the Talks CPT is a different thing (inline name/URL pair). Do not conflate.

---

## Open Questions

1. **Manual lat/lng override — where in editor UI?**
   - What we know: CONTEXT.md says "expose manual lat/lng input fields in the editor sidebar as fallback" when geocoding fails
   - What's unclear: Phase 3 conference editor block (`conference-meta`) has no lat/lng panel. It needs to be added. This could be done in Phase 4 (add a new PluginDocumentSettingPanel in the existing `src/blocks/conference-meta/` block) or as a Phase 4 sub-task.
   - Recommendation: Add the manual lat/lng panel to the existing `speekr/conference-meta` block edit.js in Phase 4, scoped to show only when geocoding has failed (read a transient via REST or show always when lat/lng are empty).

2. **Social link icon set for Speaker Profile**
   - What we know: The display is icon-only with screen-reader text; icons are platform-specific
   - What's unclear: The codebase has no SVG icon set for social platforms. The `_speekr_social_links` array stores `platform` as a free-text string.
   - Recommendation: Use a small inline SVG icon map keyed by platform slug (twitter/x, linkedin, github, etc.), with a generic link icon fallback. Do not pull in a full icon library dependency for 10–15 icons.

3. **Placeholder image for media-absent talks**
   - What we know: CONTEXT.md says fall back to "generic speaker banner placeholder image"; no such image exists yet in `/assets/`
   - What's unclear: Does one need to be created, or is a CSS-only placeholder (gradient background) acceptable?
   - Recommendation: Create a simple SVG placeholder in `assets/placeholder-talk.svg` as part of Phase 4. Reference via `SPEEKR_PLUGIN_URL . 'assets/placeholder-talk.svg'`.

4. **Conference Archive block — are all conferences shown, or only those linked to the current speaker?**
   - What we know: The Conference CPT has `_speekr_conf_speakers` (array of speaker post IDs). The plugin is single-speaker focused but technically multi-speaker capable.
   - What's unclear: Phase 4 description says "browse conference history" without specifying a filter.
   - Recommendation: Show all published `speekr_conference` posts by default; ordered by `_speekr_conf_date` descending. The planner should confirm this assumption.

5. **Single Talk block — post context**
   - What we know: "Inserting the Single Talk block on a single talk page renders the media embed..."
   - What's unclear: Does the block read the current page's post ID automatically, or does it need a post selector attribute?
   - Recommendation: Use `get_the_ID()` in render.php to read the current context. Since this block is intended for single talk pages only, no selector is needed. Document that it only makes sense on a `talks` CPT single post template.

---

## Sources

### Primary (HIGH confidence)
- WordPress Block Editor Handbook — `render` property: https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/creating-dynamic-blocks/
- WordPress Block Editor Handbook — `viewScript` / `viewStyle`: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/
- WordPress Block Editor Handbook — file structure: https://developer.wordpress.org/block-editor/getting-started/fundamentals/file-structure-of-a-block/
- Leaflet.js 1.9.4 stable release: https://leafletjs.com/download.html
- Leaflet.markercluster 1.4.1 GitHub: https://github.com/Leaflet/Leaflet.markercluster
- Nominatim Usage Policy: https://operations.osmfoundation.org/policies/nominatim/
- SpeakerDeck oEmbed endpoint: https://help.speakerdeck.com/help/how-do-i-use-oembed-to-display-a-deck-on-my-site
- Existing codebase — `inc/functions/helpers.php` (YouTube/Vimeo ID extraction, SpeakerDeck/Slideshare oEmbed helpers)
- Existing codebase — `inc/admin/custom-meta-boxes.php` line 266 (`speekr-as-article` key confirmed)
- Existing codebase — `inc/front/lists.php` line 50 (`speekr-as-article === 'on'` pattern confirmed)

### Secondary (MEDIUM confidence)
- 10up WP Block Editor Best Practices — viewScript pattern: https://gutenberg.10up.com/guides/including-frontend-javascript-with-a-block/
- YouTube thumbnail URL format: `https://img.youtube.com/vi/{id}/hqdefault.jpg` — confirmed by multiple sources, no API key required
- Vimeo oEmbed endpoint: `https://vimeo.com/api/oembed.json?url=...` — confirmed working for public videos

### Tertiary (LOW confidence — flag for validation)
- Nominatim geocoding PHP pattern via `wp_remote_get` — patterns assembled from community sources; test against actual API before planning assumes it works
- Leaflet webpack bundling fix for broken icon paths — widely reported community pattern; verify in actual build before finalising plan

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — Leaflet 1.9.4 is the current stable release confirmed at leafletjs.com; @wordpress/scripts 31.5.0 already installed
- Architecture: HIGH — `render.php` + `viewScript` pattern is documented in official WordPress handbook
- Geocoding: HIGH (policy), MEDIUM (implementation) — Nominatim policy confirmed official; PHP implementation is a standard pattern but not from official docs
- Pitfalls: HIGH for `speekr-as-article` and Leaflet icon (confirmed from codebase/known issues); MEDIUM for ZipArchive
- Press-kit ZIP: MEDIUM — pattern is standard PHP; no official WordPress documentation for streaming ZIP from REST endpoint

**Research date:** 2026-03-02
**Valid until:** 2026-04-02 (Leaflet and WordPress are stable; reassess if WP 6.8+ releases before planning)
