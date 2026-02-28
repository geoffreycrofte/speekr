# Feature Research

**Domain:** Conference speaker profile and media kit plugin (self-hosted WordPress, single speaker)
**Researched:** 2026-02-28
**Confidence:** MEDIUM-HIGH (competitor features verified via live pages; speaker industry patterns verified via multiple sources)

---

## Research Notes

Platforms surveyed: Sessionize (live), Notist/noti.st (live), speaker.io (no distinct product found — appears defunct or absorbed), Lanyrd (defunct), Speaking Events WP plugin (WordPress.org), SpeakerHub (media kit guidance), SpeakerFlow (profile template). Real-world speaker rider examined (Sara Soueidan). Alliance Interactive speaker website analysis reviewed.

Key distinction for Speekr: competitors like Sessionize are event-management platforms where the speaker is a participant. Notist is a slides/portfolio host. Speekr is a **self-hosted personal site toolkit** — the speaker controls everything, and the site IS their speaker identity, not a profile on someone else's platform.

---

## Feature Landscape

### Table Stakes (Users Expect These)

Features users assume exist. Missing these = product feels incomplete and an organizer who lands on the site cannot get what they need.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Speaker headshot (multiple sizes/crops) | Every speaker profile everywhere has a headshot; organizers need print-ready and web-ready | LOW | WordPress featured image on Speaker Profile CPT handles this; need multiple registered image sizes (square crop for thumbnail, wide for header) |
| Short bio (50–100 words) | Event programs, conference apps, and speaker directories universally request a short bio; missing = organizer copy-pastes wrong length | LOW | Simple text field on Speaker Profile CPT; separate from post content |
| Long bio (250–500 words) | Event websites and speaker directories want a longer authoritative version | LOW | Additional textarea field; not the same as the short bio |
| Full name + professional title | Display name and current role/affiliation; baseline identity field | LOW | Simple meta fields; title can change per context so needs a dedicated field, not just the WP username |
| Social links (LinkedIn, Twitter/X, GitHub, Mastodon, Bluesky) | Organizers and attendees expect to find and verify speaker on social channels; standard on all platforms | LOW | Stored as key→URL pairs; render as icon links; needs to be extensible for new networks |
| Contact/booking email or contact form link | Organizers need to reach the speaker; a profile without contact information is incomplete | LOW | Single field; can be obfuscated or linked to a /contact page |
| Talks list with title, description, and tags | Core purpose of the plugin; already exists but needs Gutenberg block surface | MEDIUM | Already built as Talks CPT; needs Topics taxonomy for filtering |
| Talk media links (video recording, slides) | Organizers verify speaker quality via recordings; attendees revisit content | LOW | Already implemented; YouTube, Vimeo, SpeakerDeck, Slideshare, custom embed |
| Conference history (name, date, city/country) | Documents speaking track record; key trust signal for organizers; "have you spoken before?" answered at a glance | MEDIUM | Conferences CPT — needs date, location, event URL, and talk reference; currently only conference name+URL stored on Talk |
| Talks/topics filtering or categorization | Speakers cover multiple themes; organizers look for specific subjects | MEDIUM | Topics taxonomy on Talks CPT; tag-based filtering on the talks list block |
| Individual talk page (single talk view) | Needed for linking from conference sites, sharing on social; provides depth beyond the list | LOW | Already supported via CPT; needs FSE/classic template |
| Printable/sharable URL | Core value proposition: one URL for organizers, not a PDF | LOW | WordPress permalinks provide this; no extra work beyond clean URLs |

### Differentiators (Competitive Advantage)

Features that set Speekr apart from a static About page, a Notion doc, or a hosted platform like Sessionize.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| World map of speaking history | Visually demonstrates speaking breadth and international experience; no competitor offers this as a self-hosted WordPress feature; striking for organizers | HIGH | Leaflet.js; Conferences CPT needs geocodable city+country fields; lat/lng stored or resolved at save time; cluster markers for dense locations |
| Speaker rider as structured page | Publicly documents requirements (travel, hotel class, AV needs, honorarium expectations) in a scannable format; rare on personal sites but highly valued by experienced speakers; organizers avoid the "what do you need?" back-and-forth | MEDIUM | Rider stored as rich text sections with predefined categories (Travel, Tech/AV, Honorarium, Stage setup, Scheduling); renders as a formatted page section or standalone page |
| Multiple bio lengths stored and accessible | Industry standard is 3 versions (short ~75w, medium ~150w, long ~500w); organizers copy-paste from the site; eliminates email requests for "a shorter version" | LOW | Three distinct text fields on the Speaker Profile CPT; rendered as copyable blocks or accessible via a direct anchor link |
| Topics as curated taxonomy (not just tags) | Structured topic taxonomy enables future filtering, schema.org markup, and feed/API output per topic; more powerful than free-form tags | MEDIUM | `speekr_topic` custom taxonomy on Talks CPT; hierarchical so broad topics can have subtypes |
| Talk given + conference cross-linking | Bidirectional relationship: a Conference record links to its Talk; a Talk shows its Conference appearances; allows "where has this talk been given?" | MEDIUM | Many-to-many relationship via post meta or a junction; Talk can appear at multiple conferences (lightning talk given at 3 events) |
| Gutenberg blocks for all display surfaces | Self-hosted advantage: organizers can embed a Speaker Profile block, Talks List block, or Conference Map block anywhere on their WordPress site, not just on designated archive pages | HIGH | Requires @wordpress/scripts build pipeline; blocks for: Speaker Profile, Talks List, Conference Map, Single Talk, Upcoming Appearances |
| FSE block templates | Supports modern block themes (Twenty Twenty-Four+); renders speaker content with theme integration, not injected HTML | HIGH | Template parts for header, footer; full templates for archive-talk, single-talk, archive-conference, speaker-profile |
| Developer hooks at key output points | Lets developers customize display without modifying plugin files; important for agencies building speaker sites for clients | MEDIUM | `speekr_before_talk_list`, `speekr_after_single_talk`, `speekr_conference_map_args`, etc.; already has some hooks in existing code |
| Shortcodes for legacy theme compatibility | Speakers on older themes (Divi, Avada, older Genesis) cannot use blocks; shortcodes provide access to all display features | LOW | `[speekr_talks]`, `[speekr_map]`, `[speekr_profile]` with attribute arguments; wraps block rendering functions |
| Structured data / schema.org output | `Person` schema for speaker profile; `Event` schema for conference appearances; `PresentationDigitalDocument` for talks; improves search appearance and enables rich results | MEDIUM | JSON-LD output in `<head>`; no third-party library needed; standard WordPress `wp_head` hook |
| Stats summary block | "X talks, Y conferences, Z countries" — shareable social proof; derived automatically from data already in the plugin | LOW | Query aggregate on Talks and Conferences CPTs; renders as a configurable stats bar block |

### Anti-Features (Commonly Requested, Often Problematic)

Features that seem good but create problems, scope creep, or misalign with the single-speaker self-hosted use case.

| Feature | Why Requested | Why Problematic | Alternative |
|---------|---------------|-----------------|-------------|
| Booking calendar / availability system | "Make it easy for organizers to book me directly" | Turns the plugin into a scheduling product; requires calendar sync (Google/Outlook), time zone handling, and conflict management — a separate product entirely; Calendly/Cal.com exist for this | Add a "Book Me" link field on the Speaker Profile that points to Calendly, Cal.com, or a contact page |
| Multi-speaker support | "I manage a roster of speakers" | Contradicts the "single speaker per site" architecture; requires user roles, permissions, speaker-to-talk ownership, a speaker listing page — fundamentally different product (conference management) | Out of scope per PROJECT.md; Sessionize handles multi-speaker use cases |
| CRM / lead tracking for booking inquiries | "Track which organizers contacted me" | Requires persistent storage of contacts, email integration, deal stages; turns plugin into a CRM; no GPL-compatible simple CRM exists that integrates cleanly | Link to an external CRM (HubSpot free, Notion, Airtable) via booking link |
| Comments / testimonials CPT | "Show what organizers said about me" | Testimonials require moderation, spam protection, and a new CPT with a frontend submission form — high complexity for marginal gain; quickly looks fake without real moderation | Store testimonials as curated rich text in a Speaker Profile field or use a dedicated testimonials plugin (Themeisle Otter has one) |
| Video hosting / upload | "Store my talk recordings in WordPress" | Massive server load; video files balloon site storage; defeats purpose of linking to YouTube/Vimeo where playback quality is higher | Use the existing embed system; link to external video platforms |
| Slide file upload (PDF/PPTX hosting) | "Upload my slides directly" | Large binary files in WordPress media library; no viewer; SpeakerDeck/SlideShare do this better | Link to SpeakerDeck or SlideShare via existing media links system |
| Social media auto-posting | "Post to Twitter when I add a new talk" | Requires OAuth tokens, API rate limits, fragile integrations with platforms that change APIs frequently; Jetpack/Zapier handle this better | Use Zapier or WP-to-Social type plugins; add a "new talk" RSS feed for IFTTT |
| Event submission / CFP tracker | "Track which conferences I've submitted to" | CFP tracking is a project management problem; Sessionize already does this as its core product; adding it to a display plugin creates scope confusion | Sessionize free speaker account handles CFP tracking; Speekr handles the public display |
| Speaker rating or review system | "Let attendees rate my talks" | Requires user accounts, moderation, anti-spam; fundamentally changes the site into a community platform; also creates reputational risk if poorly received | Embed external social proof (Twitter wall via widget, LinkedIn recommendations link) |

---

## Feature Dependencies

```
Speaker Profile CPT
    └──requires──> WordPress User (author)
    └──enables──> Speaker Profile Block (display)
    └──enables──> Speaker Profile FSE Template
    └──enables──> Schema.org Person markup

Talks CPT (existing)
    └──enhanced by──> Topics Taxonomy
    └──enhanced by──> Gutenberg Block Editor support (replaces meta boxes)
    └──enables──> Talks List Block
    └──enables──> Single Talk FSE Template
    └──enables──> Shortcode [speekr_talks]

Topics Taxonomy
    └──requires──> Talks CPT
    └──enables──> Filtered Talks List Block (filter by topic)

Conferences CPT (new)
    └──requires──> City + Country fields (geocodable)
    └──linked to──> Talks CPT (talk given at this conference)
    └──enables──> World Map Block
    └──enables──> Conference Archive FSE Template
    └──enables──> Stats Summary Block (derives conference count, country count)
    └──enables──> Schema.org Event markup

World Map Block
    └──requires──> Conferences CPT with geocoded lat/lng
    └──requires──> Leaflet.js (enqueued only when block is present)
    └──requires──> Conference location data (city + country → coordinates)

Stats Summary Block
    └──requires──> Talks CPT (talk count)
    └──requires──> Conferences CPT (conference count, country count)

Speaker Rider Section
    └──requires──> Speaker Profile CPT (stored as profile fields or attached page)

Schema.org JSON-LD
    └──requires──> Speaker Profile CPT (Person schema)
    └──requires──> Conferences CPT (Event schema)
    └──requires──> Talks CPT (PresentationDigitalDocument schema)

Developer Hooks
    └──enhances──> All display surfaces (talks list, map, single talk, profile)

Shortcodes
    └──wraps──> Block rendering functions
    └──requires──> Blocks to exist first (shortcodes are wrappers, not independent implementations)
```

### Dependency Notes

- **Conferences CPT requires geocoded coordinates:** The world map depends on lat/lng; city+country text alone is not enough. Must resolve coordinates at save time (using a geocoding lookup or manual entry fallback) to avoid runtime API calls on every page load.
- **Shortcodes wrap blocks:** Build block rendering as standalone PHP functions first, then shortcodes call those functions. Do not implement shortcodes as separate rendering paths — that creates two code paths to maintain.
- **Topics taxonomy enhances but does not block:** The Talks List can ship without topic filtering; add the taxonomy and block filter controls in a subsequent iteration.
- **Talk–Conference cross-linking is optional complexity:** A Talk can store the conference name as a string (existing behavior) without a full bidirectional CPT relationship. The Conferences CPT can store a reference to the Talk. Full bidirectionality is a v1.x feature.

---

## MVP Definition

### Launch With (v1 — Modernization Milestone)

Minimum viable product for the modernization milestone described in PROJECT.md. Validates the new architecture and unblocks the speaker's primary use case.

- [ ] **Speaker Profile CPT** — short bio, long bio, professional title, headshot (via featured image), social links (LinkedIn, Twitter/X, GitHub, Mastodon, Bluesky), contact/booking URL, speaker rider text field — the "one URL" value prop requires this
- [ ] **Conferences CPT** — event name, date, city, country, event URL, talk reference (post ID), slides/video links per conference appearance — documents speaking history
- [ ] **World Map Block** — Leaflet.js map with conference pins; click opens conference detail popup — the most visually differentiating feature
- [ ] **Topics Taxonomy** on Talks CPT — enables structured categorization
- [ ] **Gutenberg block editor support for all CPTs** — block-based sidebar panels replacing legacy meta boxes; required for modern WordPress compatibility
- [ ] **Display blocks:** Speaker Profile block, Talks List block (with optional topic filter), Conference Map block — the primary display surfaces
- [ ] **FSE templates** for: speaker-profile, archive-talk, single-talk, archive-conference — modern theme compatibility
- [ ] **Classic theme templates** for the same pages — backward compatibility
- [ ] **Developer hooks** at key output points — minimum: before/after each major block render
- [ ] **Shortcodes** wrapping block functions — legacy theme support
- [ ] **Architecture modernization** — PSR-4 namespacing, autoloading — prerequisite for all new features

### Add After Validation (v1.x)

Features to add once the v1 architecture is stable and in use.

- [ ] **Multiple bio lengths (short/medium/long)** — add after confirming speakers actually use the profile CPT; low complexity to add
- [ ] **Stats Summary block** — derived from existing data; add once Talks + Conferences CPTs are populated
- [ ] **Schema.org JSON-LD output** — add after core display is stable; no UI work, pure `wp_head` output
- [ ] **Full Talk–Conference bidirectional relationship** — useful once a speaker has multiple conferences per talk
- [ ] **Topics taxonomy in Talks List block filter UI** — add after topics are being actively used

### Future Consideration (v2+)

Features to defer until product-market fit is established.

- [ ] **Upcoming appearances / future events** — requires logic to distinguish past vs. future conferences; low value until the plugin has significant adoption
- [ ] **Speaker one-sheet PDF export** — compelling feature but requires a PDF rendering library (TCPDF, DomPDF); significant complexity; worth evaluating if users request it
- [ ] **Sessionize embed bridge** — pull Sessionize profile data into Speekr via their embed API; useful for speakers who use both platforms; complex auth/sync problem

---

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| Speaker Profile CPT (bio, headshot, social links) | HIGH | LOW | P1 |
| Conferences CPT with date + location | HIGH | MEDIUM | P1 |
| World Map Block (Leaflet.js) | HIGH | HIGH | P1 |
| Gutenberg block editor for all CPTs | HIGH | HIGH | P1 |
| Display blocks (Profile, Talks List, Map) | HIGH | HIGH | P1 |
| FSE + Classic templates | HIGH | MEDIUM | P1 |
| Topics taxonomy | MEDIUM | LOW | P1 |
| Developer hooks | MEDIUM | LOW | P1 |
| Shortcodes | MEDIUM | LOW | P1 |
| Architecture modernization (PSR-4) | HIGH (infra) | HIGH | P1 (prerequisite) |
| Multiple bio lengths | MEDIUM | LOW | P2 |
| Stats Summary block | MEDIUM | LOW | P2 |
| Schema.org JSON-LD | MEDIUM | MEDIUM | P2 |
| Speaker rider structured page | MEDIUM | LOW | P2 |
| Bidirectional Talk–Conference relationship | LOW | MEDIUM | P3 |
| One-sheet PDF export | MEDIUM | HIGH | P3 |
| Upcoming appearances | LOW | MEDIUM | P3 |

**Priority key:**
- P1: Must have for v1 launch (this milestone)
- P2: Should have, add in v1.x
- P3: Nice to have, future consideration

---

## Competitor Feature Analysis

| Feature | Sessionize | Notist | Speaking Events WP Plugin | Speekr Approach |
|---------|------------|--------|--------------------------|-----------------|
| Speaker bio | Yes (single, multi-language) | Yes | No (plugin is list-only) | Three lengths (short/medium/long); English-first, i18n-ready |
| Headshot | Yes | Yes | No | WordPress featured image on Speaker Profile CPT |
| Social links | Yes (company/social/school) | Partial | No | Explicit fields per network; extensible |
| Talks list | Yes (per-profile) | Yes (as "presentations") | Yes (year-grouped list) | Talks CPT with grid/list layout; topic filtering |
| Conference history | Yes (appears at N events) | Yes (per-presentation event) | Yes (event per talk entry) | Dedicated Conferences CPT; linked to Talks |
| Slides embedding | No (links only) | Yes (upload or link) | No | Links to SpeakerDeck/SlideShare; no hosting |
| Video embedding | No | Yes (Vimeo/YouTube embed) | No | oEmbed + custom embed; existing feature |
| World map | No | No | No | Leaflet.js map; unique differentiator |
| Speaker rider | No | No | No | Structured rich text sections on Speaker Profile |
| WordPress blocks | N/A | No | Partial (block for event list) | Full block suite: Profile, Talks List, Map |
| FSE templates | N/A | No | No | Full FSE template set |
| Self-hosted | No (SaaS) | No (SaaS) | Yes | Yes — full ownership of data |
| Developer hooks | N/A | No | Minimal | Named filters + actions at all output points |
| Schema.org | Partial (in their platform) | Partial | No | JSON-LD for Person + Event + Presentation |
| Stats summary | Yes (event count, session count) | No | No | Derived block from existing data |

---

## Sources

**Platforms directly examined:**
- [Sessionize Speaker Profiles](https://sessionize.com/speakers) — profile fields and directory display (MEDIUM confidence; live page)
- [Sessionize Speakers Directory](https://sessionize.com/speakers-directory) — public profile card fields (MEDIUM confidence; live page)
- [Notist (noti.st)](https://noti.st/) — feature set for speaker presentation management (MEDIUM confidence; live page)
- [Speaking Events WordPress Plugin](https://wordpress.org/plugins/speaking-events/) — WordPress.org plugin page with full feature list (HIGH confidence; official source)

**Speaker identity / media kit standards:**
- [SpeakerHub — Media Kit guidance](https://speakerhub.com/skillcamp/what-include-speaker-media-kit-example) — media kit component list (MEDIUM confidence)
- [SpeakerFlow — Ultimate Speaker Profile Template](https://speakerflow.com/the-ultimate-speaker-profile-template-with-examples/) — profile fields and sections (MEDIUM confidence)
- [Alliance Interactive — Best Speaker Websites](https://www.allianceinteractive.com/blog/best-speaker-websites/) — common patterns on top speaker websites (MEDIUM confidence)
- [Bizzabo — Speaker Bio Examples](https://www.bizzabo.com/blog/conference-speaker-bio-examples-free-template) — bio length standards (MEDIUM confidence; multiple sources agree)

**Speaker rider:**
- [Sara Soueidan's Speaker Rider](https://www.sarasoueidan.com/speaker-rider/) — real-world rider from a prominent tech speaker; defines rider structure (HIGH confidence; primary source)

**Bio length standards:**
- Multiple sources (The Speaker Lab, Eventify, SpeakerFlow, Legal Talk Network) consistently recommend 3 bio lengths: ~75 words, ~150 words, ~500 words (HIGH confidence; multiple independent sources agree)

---
*Feature research for: Speekr — WordPress conference speaker toolkit plugin*
*Researched: 2026-02-28*
