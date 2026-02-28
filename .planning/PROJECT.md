# Speekr

## What This Is

Speekr is a WordPress plugin that gives conference speakers a complete toolkit on their own website. It lets them manage their speaker identity (media kit, bio, headshots, rider), curate their talk catalogue, document their conference history, and visualize their speaking journey on a world map — all served through modern Gutenberg blocks, FSE templates, and developer-friendly hooks.

## Core Value

A speaker can share a single URL to their WordPress site and a conference organizer finds everything they need — profile, assets, talk list, past conferences — without the speaker maintaining a separate speaker.com or Notion doc.

## Requirements

### Validated

<!-- Already shipped in existing codebase — these work today -->

- ✓ Custom post type "Talks" with title, featured image, revisions support — existing
- ✓ Talk meta: summary, media links (YouTube, Vimeo, Slides), conference name + URL, article link — existing
- ✓ Media embed resolution: oEmbed first, custom iframe fallback for YouTube/Vimeo/SpeakerDeck/Slideshare — existing
- ✓ Frontend talk list with grid/list/mixed layout system — existing
- ✓ Settings system (network-aware, single `speekr_settings` option, multisite support) — existing
- ✓ Template loader (plugin templates injectable into WordPress theme template selector) — existing
- ✓ Admin meta boxes for talk editing — existing
- ✓ AJAX: create default archive page, dismiss admin notices — existing
- ✓ Plugin icon font for admin menu — existing
- ✓ SCSS build pipeline (compile + minify) — existing

### Active

<!-- Goals for this modernization milestone -->

- [ ] Speaker profile CPT/page: headshot(s), bio (short + long), social links, speaker rider
- [ ] Topics taxonomy or CPT for categorizing talks by theme/subject area
- [ ] Conferences CPT: event name, date, city + country (geocodable), talk given (linked), slides/video links, event URL
- [ ] Interactive world map: conference pins, click-to-detail, Leaflet.js or equivalent
- [ ] Architecture modernization: PSR-4 namespacing, autoloading, separation of concerns, no legacy globals
- [ ] Build toolchain modernization: replace node-sass with `sass`, use `@wordpress/scripts` for block compilation
- [ ] Block editor support for Talks CPT (block-based sidebar/attributes replacing meta boxes)
- [ ] Block editor support for Conferences CPT
- [ ] Block editor support for Speaker Profile
- [ ] Display blocks: Speaker Profile block, Talks List block, Conference Map block, Single Talk block
- [ ] Full Site Editing (FSE) block templates for Speaker Profile, Talks archive, Single Talk, Conferences archive
- [ ] Classic theme template files: `speaker-profile.php`, `archive-talk.php`, `single-talk.php`, `archive-conference.php`, `single-conference.php`
- [ ] Action/filter hooks for developer customization at key output points
- [ ] Shortcodes for legacy theme compatibility

### Out of Scope

- Real-time collaboration or multi-speaker support — single speaker per site is the use case
- Speaker booking/CRM features — this is a display/identity tool, not a booking platform
- Native mobile app — web-first, WordPress admin handles input
- Video hosting — external platforms only (YouTube, Vimeo, etc.)
- Comment system on talks/conferences — not a discussion platform

## Context

**Existing codebase:** The plugin has been in development for several years with a working talk management system. The admin area design and core logic (CPTs, meta, media handling, settings) are solid and should be preserved. The architecture uses procedural PHP with a single main class for bootstrapping — functional but not idiomatic modern WordPress plugin structure. No Gutenberg layer exists yet.

**Brownfield constraints:** Existing `speekr_` function names and post meta keys (`speekr-media-links`, `speekr-conf`, etc.) should be maintained for backward compatibility during migration. New code should use namespaced PHP (`Speekr\` namespace).

**Build toolchain state:** Currently uses `node-sass` (deprecated) and `@wordpress/scripts` only for linting. No webpack/block compilation configured. Gulp is removed. Migration to full `@wordpress/scripts` build is needed for block development.

**Target users:** Individual conference speakers who self-host WordPress. Tech-savvy enough to install plugins but not necessarily developers. Conference organizers who visit the speaker's site.

## Constraints

- **Compatibility**: Must support WordPress 6.0+ (FSE requires 5.9+, targeting 6.0 for stability)
- **PHP**: Target PHP 8.0+ for modern syntax; no breaking changes to existing post meta keys
- **License**: GPLv2 or later — all dependencies must be GPL-compatible
- **Backward compat**: Existing Talk posts and their meta must remain readable after migration
- **No jQuery**: New block code must use React/vanilla JS; jQuery kept only for legacy admin scripts during transition

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Keep existing Talk CPT slug and meta keys | Backward compatibility for existing installs | — Pending |
| Leaflet.js for world map | Open source, GPL-compatible, no API key required vs Google Maps | — Pending |
| PSR-4 autoloading for new classes | Modern PHP standard, enables clean namespace separation | — Pending |
| @wordpress/scripts as primary build tool | Official WordPress toolchain for blocks; replaces node-sass | — Pending |
| FSE + classic templates (both) | Maximum theme compatibility across old and new WordPress themes | — Pending |

---
*Last updated: 2026-02-28 after initialization*
