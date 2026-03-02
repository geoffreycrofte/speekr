# Phase 4: Display Blocks - Context

**Gathered:** 2026-03-02
**Status:** Ready for planning

<domain>
## Phase Boundary

Build all public-facing dynamic blocks: Speaker Profile, Talks List, Single Talk,
Conference Archive, and Conference Map (Leaflet.js). This phase delivers what visitors
see. Content entry (editor panels) was Phase 3. Templates and shortcodes are Phase 5.

</domain>

<decisions>
## Implementation Decisions

### Speaker Profile — Layout options
- Block attribute offers two layout choices: (1) headshot left, text wraps right;
  (2) headshot above, full-width content below
- Default layout: headshot left (classic press-kit style)

### Speaker Profile — Bio content
- Renders short bio (meta field) followed by post_content (full block editor body)
- Both are always shown in sequence; no toggle needed

### Speaker Profile — Social links display
- Global Settings page option controls display style (not a per-block attribute)
- Default: accessible icon-only row with visually-hidden platform name
  (`<span class="screen-reader-text">LinkedIn</span>`)

### Speaker Profile — Press kit download
- Block-level "Allow download" toggle (block attribute, off by default)
- When enabled: renders a "Download press kit" button
- Download delivers a `.zip` containing:
  - All headshot images
  - A combined speaker-kit `.md` (or `.html`) file covering bio, social links, and rider
  - If a combined file isn't achievable cleanly: separate `bio.md`, `social.md`,
    `preferences.md` + `bio.html` for post_content
- Generation happens server-side on demand (PHP, no pre-built files)

### Talks List — Layout
- Block attribute toggles between two layouts: grid (2–3 columns) and single-column list
- Editor chooses per placement; grid is the default

### Talks List — Card content
- Media visual (priority order): YouTube thumbnail > Vimeo thumbnail > embedded slide
  player (if available) > post featured image > generic placeholder image
- Title
- Short summary (meta field)
- Date + location (from the Conference meta linked to the talk, or speekr-conf object)
- "Read more" link button — shown only when "Make this Speekr item a blog post"
  is enabled on the talk (links to the talk's single post page)

### Talks List — Topic filter
- Horizontal scrollable pill tabs above the list: "All" first, then one tab per topic
- Active tab is highlighted; clicking a tab filters talks client-side (no page reload)
- Clicking the active tab returns to "All"

### Talks List — Empty state
- Friendly message shown when no published talks exist
- For logged-in editors: message includes a direct link to create the first talk
- For visitors: friendly message without the admin link

### Conference geocoding
- Auto-geocode city + country on post save using Nominatim (OpenStreetMap)
  — free, no API key required
- Geocoding runs server-side via PHP `wp_remote_get()` at `save_post`
- On failure (not found, timeout, rate-limit): show admin notice
  "Could not resolve location — add coordinates manually" and expose
  manual lat/lng input fields in the editor sidebar as fallback
- Coordinates stored as `_speekr_conf_lat` and `_speekr_conf_lng` post meta

### Conference Map — Pin popup
- Clicking a pin shows: conference name, date, city, talk title, event URL link,
  talk page link (if talk has a blog post)

### Conference Map — Clustered pins
- Uses Leaflet.markercluster plugin for cities with multiple conference appearances
- Cluster marker shows count; clicking expands to individual pins
- Leaflet and markercluster load only on the frontend via block `viewScript`
  (never in the block editor)

### Media embeds — Multiple video sources
- Priority order: YouTube → Vimeo → Dailymotion
- Only the highest-priority available source renders as an embed
- Other video URLs are not shown in the video area (they may appear in "Other links")

### Media embeds — Slides
- SpeakerDeck, Slideshare, generic Slides URL: embed as iframe player if the
  provider supports oEmbed or a standard embed URL
- Falls back to thumbnail + "View slides on [provider]" link if embedding fails

### Media embeds — No media fallback
- Fallback order: post featured image → generic speaker banner placeholder image
- Media area always occupies space (no layout reflow when media is absent)

### Media embeds — Other links placement
- Claude's Discretion: group all non-video links (Slides + Other custom links)
  in a "Resources" section below the primary embed

</decisions>

<specifics>
## Specific Ideas

- Card media area: "if possible the card shows a video thumbnail if available, or
  the embedded slide if player available, or a thumbnail if provided in the post,
  or a placeholder image" — this exact priority was stated by the user and should
  be respected in render.php
- "Make this Speekr item a blog post" — this is a per-talk setting that controls
  whether a public single-post URL exists; the Talks List card "Read more" button
  must check this before rendering a link
- Press kit ZIP: user preference is a single combined `.md` + images; multi-file
  is the acceptable fallback if combining bio, social, and rider cleanly is complex

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 04-display-blocks*
*Context gathered: 2026-03-02*
