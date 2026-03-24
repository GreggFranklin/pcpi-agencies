# PCPI Agencies

**Version:** 2.2.0
**Requires WordPress:** 6.1+
**Requires PHP:** 7.4+
**Author:** Gregg Franklin, Marc Benzakein

---

## Overview

Registers the **Agency** custom post type (CPT) and provides two ways to display an agency card on the front end:

1. **Gutenberg block** — `pcpi/agency-card` — pick an agency from a dropdown, get a live preview in the editor, server-side rendered on the front end.
2. **Shortcode** — `[pcpi_agency id="123"]` — for use in Classic Editor, widgets, or page builders.

---

## Features

- Custom post type `pcpi_agency` with full admin labels (Add Agency, Edit Agency, etc.)
- Admin list table shows Logo, Agency Name, Phone, and Address columns — sortable
- Meta box in the post editor for Address, Phone, and Agency Logo (WP media picker)
- All meta fields exposed via the REST API for use by front-end management plugins
- Gutenberg block with live editor preview
- Shortcode for Classic Editor / page builder usage
- Clean uninstall (removes all agency posts and meta when the plugin is deleted)

---

## Meta Fields

All fields are stored in `wp_postmeta` and exposed via the REST API.

| Meta Key        | Type    | Description                                  | Managed by         |
|-----------------|---------|----------------------------------------------|--------------------|
| `_pcpi_address` | string  | Full mailing address (multi-line)            | This plugin        |
| `_pcpi_phone`   | string  | Phone number                                 | This plugin        |
| `_pcpi_logo_id` | integer | Attachment ID for the agency logo            | This plugin        |
| `_pcpi_city`    | string  | City (back-compat)                           | Front-end plugin   |
| `_pcpi_state`   | string  | State (back-compat)                          | Front-end plugin   |
| `_pcpi_website` | string  | Website URL (back-compat)                    | Front-end plugin   |

> **Note:** City, state, and website are registered for REST back-compatibility but are intentionally not shown in the wp-admin meta box. They are managed by the separate front-end management plugin.

---

## REST API Fields

The following top-level fields are added to the `pcpi_agency` REST endpoint (`/wp-json/wp/v2/pcpi_agency`):

| Field           | Description                                      |
|-----------------|--------------------------------------------------|
| `pcpi_address`  | Mailing address                                  |
| `pcpi_phone`    | Phone number                                     |
| `pcpi_website`  | Website URL                                      |
| `pcpi_logo_url` | Resolved medium-size logo URL (computed, read-only) |

---

## Gutenberg Block

**Block name:** `pcpi/agency-card`
**Category:** Widgets

Insert the block, then select an agency from the **Agency Settings** panel in the right sidebar. The editor renders a live preview. The front end is always server-side rendered.

### Rendered HTML structure

```html
<div class="pcpi-agency-card">
    <div class="pcpi-agency-card__logo-wrap">
        <img class="pcpi-agency-card__logo" src="..." alt="Agency Name logo">
    </div>
    <div class="pcpi-agency-card__info">
        <p class="pcpi-agency-card__name">Agency Name</p>
        <p class="pcpi-agency-card__address">455 7th St.<br>Oakland, CA 94607</p>
        <p class="pcpi-agency-card__phone">Phone: (510) 238-3455</p>
        <p class="pcpi-agency-card__website"><a href="...">Visit Website</a></p>
    </div>
</div>
```

### CSS classes for theming

| Class                         | Element                     |
|-------------------------------|-----------------------------|
| `.pcpi-agency-card`           | Outer wrapper (flex row)    |
| `.pcpi-agency-card__logo-wrap`| Logo container              |
| `.pcpi-agency-card__logo`     | `<img>` tag                 |
| `.pcpi-agency-card__info`     | Text block                  |
| `.pcpi-agency-card__name`     | Agency name `<p>`           |
| `.pcpi-agency-card__address`  | Address `<p>`               |
| `.pcpi-agency-card__phone`    | Phone `<p>`                 |
| `.pcpi-agency-card__website`  | Website `<p>` (with `<a>`)  |

---

## Shortcode

```
[pcpi_agency id="123"]
```

Replace `123` with the post ID of the agency. Returns an empty string if the ID is invalid or not a `pcpi_agency` post.

---

## File Structure

```
pcpi-agencies/
├── pcpi-agencies.php          # Plugin entry point, constants, action links
├── uninstall.php              # Cleanup on plugin deletion
├── README.md                  # This file
├── includes/
│   ├── class-cpt.php          # CPT registration + admin list table columns
│   ├── class-meta.php         # Meta registration, REST fields, admin meta box
│   ├── class-block.php        # Gutenberg block registration
│   └── helpers.php            # pcpi_parse_address_lines(), pcpi_render_agency_card(), shortcode
└── build/
    ├── index.js               # Block editor script (vanilla JS, no build step required)
    ├── style.css              # Front-end block styles
    └── editor.css             # Editor-only styles
```

---

## Changelog

### 2.2.0
- Merged `_PCPI Agency Block` plugin into this plugin — single plugin replaces both
- Full CPT labels (Add Agency, Edit Agency, etc.)
- Admin list table with Logo, Agency Name, Phone, Address columns
- `wp_add_inline_script` / `wp_add_inline_style` replace inline `<style>` and `<script>` tags
- `auth_callback` and `sanitize_callback` added to `register_post_meta`
- `wp_unslash()` added before sanitizing `$_POST` values
- `file_exists()` guards on `filemtime()` calls
- Removed deprecated `wp-editor` block script dependency
- Fixed deprecated block `category: 'common'` → `'widgets'`
- Extracted shared `pcpi_parse_address_lines()` utility (PHP + JS mirror)
- Added `uninstall.php`
- Added "Manage Agencies" quicklink to Plugins screen
- Alphabetical sort in block editor agency dropdown

### 2.1.0
- Initial merge of agency CPT and block plugins
- REST field exposure for block editor

### 1.0.1 *(original _PCPI Agency Block)*
- Block-only plugin, depended on separate CPT plugin

---

## Notes for Developers

- **No build step required.** `build/index.js` is plain ES5-compatible JavaScript using `wp.*` globals. If you ever migrate to `@wordpress/scripts`, the IIFE pattern can be replaced with standard ESM imports.
- **Back-compat meta keys** (`_pcpi_city`, `_pcpi_state`, `_pcpi_website`) are intentionally preserved. Do not remove them from `register_post_meta` without first confirming the front-end management plugin no longer needs them.
- **Uninstall is destructive.** `uninstall.php` permanently deletes all `pcpi_agency` posts. If you need to preserve data across reinstalls, delete or rename `uninstall.php` before removing the plugin.
