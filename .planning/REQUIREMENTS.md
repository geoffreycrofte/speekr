# Requirements: Speekr

**Defined:** 2026-02-28
**Core Value:** A speaker can share a single URL and a conference organizer finds everything they need — profile, assets, talks, past conferences — without a separate Notion doc or PDF.

## v1 Requirements

### Architecture & Build (ARCH)

- [ ] **ARCH-01**: Build toolchain migrated from node-sass/gulp to `@wordpress/scripts`; `npm run build` works on Node 18+ and produces compiled block assets and legacy admin/frontend scripts
- [ ] **ARCH-02**: All CPTs registered with `show_in_rest: true` and `custom-fields` in supports; all post meta registered via `register_post_meta()` with `show_in_rest: true`, `single: true`, and `auth_callback`; legacy `save_post` callbacks audited and gated against REST double-fire data loss

### Speaker Profile (PROF)

- [ ] **PROF-01**: Speaker can upload one or more headshot images to their speaker profile for organizer use
- [ ] **PROF-02**: Speaker can write a short bio (displayed and available for organizer copy — target ~75 words)
- [ ] **PROF-03**: Speaker can write a long bio (full version — target ~500 words)
- [ ] **PROF-04**: Speaker can add social and professional links: LinkedIn, Twitter/X, GitHub, Mastodon, Bluesky, and a booking/contact URL
- [ ] **PROF-05**: Speaker can write a speaker rider (rich text covering travel, AV requirements, honorarium, scheduling expectations)
- [ ] **PROF-06**: Site displays a Speaker Profile block rendering headshot, bio, social links, and rider for visitors

### Talks (TALK)

- [ ] **TALK-01**: Speaker can assign one or more Topics to a talk via a Topics taxonomy (e.g. Accessibility, Design Systems, Performance)
- [ ] **TALK-02**: Speaker can enter talk metadata (summary, media links, conference reference) via block editor sidebar panels instead of classic meta boxes
- [ ] **TALK-03**: Site displays a Talks List block rendering all talks with optional filtering by Topic
- [ ] **TALK-04**: Site displays a Single Talk block showing full talk detail (media embed, description, conference, links)

### Conferences (CONF)

- [ ] **CONF-01**: Speaker can create a conference entry with: event name, date, city, country, event URL, and a reference to which talk was given
- [ ] **CONF-02**: Speaker can enter conference data via block editor sidebar panels
- [ ] **CONF-03**: Site displays a Conference archive block: filterable list of past conference appearances
- [ ] **CONF-04**: Site displays an interactive world map block (Leaflet.js) with a pin per conference; clicking a pin shows conference name, date, talk given, and event link

### Templates & Developer Tools (DEV)

- [ ] **DEV-01**: Plugin provides FSE block templates for block themes: speaker profile page, talk archive, single talk, conference archive
- [ ] **DEV-02**: Plugin provides classic PHP template files overridable in child themes: `speaker-profile.php`, `archive-talk.php`, `single-talk.php`, `archive-conference.php`, `single-conference.php`
- [ ] **DEV-03**: Plugin exposes action and filter hooks at all major output points (before/after speaker profile, before/after talks list, before/after map, per-talk output)
- [ ] **DEV-04**: Plugin provides shortcodes wrapping block render functions: `[speekr_profile]`, `[speekr_talks]`, `[speekr_map]` for classic theme compatibility

## v2 Requirements

### PHP Modernization

- **PHP-01**: PHP codebase reorganized under `Speekr\` PSR-4 namespace with Composer autoloading (new `src/` alongside existing `inc/` during transition)
- **PHP-02**: Legacy `inc/` files removed as `src/` equivalents are verified working in production

### Enhanced Profile

- **PROF-07**: Speaker can write three distinct bio lengths: short (~75w), medium (~150w), long (~500w)
- **PROF-08**: Speaker rider structured into labeled sections: Travel, Hotel, AV Requirements, Honorarium, Stage Setup, Scheduling

### Analytics & SEO

- **SEO-01**: Plugin outputs Schema.org JSON-LD: `Person` for speaker profile, `Event` for conferences, `PresentationDigitalDocument` for talks
- **SEO-02**: Stats Summary block: auto-derived "X talks, Y conferences, Z countries" from existing data

## Out of Scope

| Feature | Reason |
|---------|--------|
| Booking calendar / availability | Calendly/Cal.com exist; the "Book Me" link field in PROF-04 covers the need |
| Multi-speaker support | Contradicts single-speaker architecture; fundamentally different product |
| CRM / lead tracking | External tools (Notion, HubSpot) are better suited; out of plugin scope |
| Video / slide file hosting | External platforms (YouTube, Vimeo, SpeakerDeck) only; embed links cover the need |
| Upcoming / future events display | Low value until plugin has adoption; adds past/future date logic complexity |
| Speaker one-sheet PDF export | Significant complexity (PDF library); evaluate only if users strongly request it |
| Sessionize / Notist sync | Complex auth/sync problem; out of scope for self-hosted plugin |
| Comment system on talks or conferences | Not a discussion platform |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| ARCH-01 | Phase 1 | Pending |
| ARCH-02 | Phase 2 | Pending |
| PROF-01 | Phase 2 | Pending |
| PROF-02 | Phase 2 | Pending |
| PROF-03 | Phase 2 | Pending |
| PROF-04 | Phase 2 | Pending |
| PROF-05 | Phase 2 | Pending |
| PROF-06 | Phase 4 | Pending |
| TALK-01 | Phase 2 | Pending |
| TALK-02 | Phase 3 | Pending |
| TALK-03 | Phase 4 | Pending |
| TALK-04 | Phase 4 | Pending |
| CONF-01 | Phase 2 | Pending |
| CONF-02 | Phase 3 | Pending |
| CONF-03 | Phase 4 | Pending |
| CONF-04 | Phase 4 | Pending |
| DEV-01 | Phase 5 | Pending |
| DEV-02 | Phase 5 | Pending |
| DEV-03 | Phase 5 | Pending |
| DEV-04 | Phase 5 | Pending |

**Coverage:**
- v1 requirements: 20 total
- Mapped to phases: 20
- Unmapped: 0

---
*Requirements defined: 2026-02-28*
*Last updated: 2026-02-28 — traceability populated after roadmap creation*
