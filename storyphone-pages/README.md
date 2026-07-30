# StoryPhone Pages

Custom, hand-built front-end page templates for [storyphone.co.il](https://storyphone.co.il).

This plugin owns **appearance only**. WooCommerce keeps full control of the catalog, prices,
stock, tax, coupons, shipping, checkout, payment gateways, orders and emails.

## The homepage concept

The shop is called *Story Phone*, so browsing is built around **Stories** — the interaction
every shopper already knows from Instagram. This replaces the twelve-dropdown mega-menu that
is standard in this vertical.

| Section | Job it does |
| --- | --- |
| **Stories rail** | Each category is a bubble that opens a full-screen, auto-advancing product story with add-to-cart inside. Seen state is remembered per visitor. |
| **Command palette** | `/` or `Cmd/Ctrl+K` opens instant search against the Store API, with keyboard navigation and recent searches. |
| **Heat Board** | Best sellers ranked by WooCommerce's own `total_sales`, with bars showing popularity *relative* to the top seller. |
| **Quick reach** | Two-tap tiles for shoppers who already know what they want. |
| **Deal spotlight** | Deepest genuine discount in the catalog, with a countdown to the sale's end date. |
| **Hoverable nav** | Parent categories are hover-only; child categories appear as compact pick-style cards in the hero stage. |
| **Spotlight deck** | Below Stories: one card that alternates between the best seller and a compact deal-of-the-day. |

Search is the hero's primary action rather than a banner button, because on a catalog this
size typing beats browsing.

### The hero pick deck

The card in the hero holds two faces. The best seller is on screen for the first ~2 seconds,
then the deal turns into view with its countdown; from there the two alternate every ~6
seconds. Both faces share a single CSS grid cell, so the deck is always as tall as the taller
card and a swap never resizes the page.

Before anyone touches it, the card plays a short establishing sweep: it turns to show its
right edge, then its left, then squares up to the viewer. That sweep and the pointer tilt
drive the *same* four custom properties (`--sp-tilt-x/y/mx/my`), which is why the hand-off
between them is seamless — the first pointer move simply cancels the sweep and takes over the
same channel. Making that work needs the properties registered with `@property`; an
unregistered custom property jumps between keyframes instead of interpolating.

Guards worth knowing about: the rotation pauses on hover, on focus and when the tab is
hidden; the progress pips below the card double as manual controls; the off-screen face is
`inert` so it cannot catch keyboard focus mid-turn; and if the best seller *is* the deal,
the second face is dropped rather than rotating the card onto itself.

## Why a plugin and not a theme

The templates live here rather than in a theme, which means:

- The active theme (`matat-child`) is untouched — nothing on the existing site changes.
- Pages can be built, previewed and approved before being promoted to the front page.
- A future switch to Blocksy affects the shop/product/cart/checkout pages only. These
  pages keep rendering identically, because they never depended on the theme.

## The commerce boundary

**This plugin does:** layout, typography, animation, CSS/JS, product presentation.

**WooCommerce does:** everything transactional. Specifically:

- Product data, prices and stock come from `wc_get_products()` and `WC_Product`, rendered
  server-side. Nothing is hardcoded.
- Product visibility respects `WC_Product::is_visible()`, so the "Disabled" rules enforced by
  the StoryPhone Inventory Manager plugin apply here automatically.
- Best sellers are ordered by WooCommerce's `total_sales` meta. (`wc_get_products()` only
  special-cases `orderby => include` and passes everything else to `WP_Query`, where
  `popularity` is not a valid value — hence the explicit meta query.)
- Live search hits the public `/wc/store/v1/products` route, so WooCommerce decides what a
  shopper may see. The front end cannot widen that.
- Add-to-cart goes through the **WooCommerce Store API** (`/wp-json/wc/store/v1/cart/*`) using
  same-origin cookies. That is the same session cart WooCommerce's own cart page reads, so
  items added from these pages appear in the normal cart and checkout.
- "Checkout" and "View cart" are plain links to `wc_get_checkout_url()` and `wc_get_cart_url()`.
  The customer always completes the order through the existing flow and payment setup.

There are **no** template overrides for cart, checkout or my-account, and this plugin never
calculates a price or a total.

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+ (site currently runs 8.2.5)
- PHP 7.4+
- Node.js 18+ (only to build assets)

## Build

```bash
cd storyphone-pages
npm install
npm run build     # writes build/main.js + build/main.css
```

`npm run dev` rebuilds on change. PHP always loads the `build/` output and cache-busts with
`filemtime()`, so there is no dev-server step to configure in WordPress.

## Package for upload

```bash
npm run zip       # builds, then writes ../storyphone-pages.zip
```

Upload that zip via **wp-admin → Plugins → Add New → Upload Plugin**. The archive excludes
`node_modules/`, `src/` and tooling.

## Review the design without WordPress

```bash
node ../preview/build-preview.mjs   # pulls live catalog data, writes preview/index.html
node ../preview/shoot.mjs           # screenshots desktop/tablet/mobile into preview/shots/
```

`build-preview.mjs` mirrors the PHP templates using the real Store API catalog, so layout and
RTL problems show up with genuine Hebrew product names. `shoot.mjs` serves the folder over
HTTP and drives the stories viewer, palette and mobile nav.

Serving over HTTP matters: browsers block ES modules over `file://`, so opening the HTML
directly leaves every script silently unloaded.

Neither file ships in the plugin zip.

## Checks

```bash
npm run lint:php   # parses every PHP file (no php binary needed)
npm run build
```

## Install on staging

1. Build and zip, then upload and activate on **staging**.
2. Create a new page (e.g. "Home v2"). Keep it a **draft**.
3. In the page sidebar, set **Template → StoryPhone — Home**.
4. Preview the draft. Nothing on the live site has changed at this point.
5. Assign a menu under **Appearance → Menus** to the **"StoryPhone Pages — תפריט ראשי"**
   location. Without one, the header falls back to the busiest product categories.
6. Smoke-test the checklist below.
7. Only when approved: **Settings → Reading → Homepage** → select the new page.
   Rollback is switching that setting back.

## Smoke-test checklist

- [ ] Page renders with no Matat styling bleeding through.
- [ ] Layout is correct right-to-left; nothing is mirrored or clipped.
- [ ] Products, prices and sale badges match wp-admin.
- [ ] A product disabled in the Inventory Manager does **not** appear.
- [ ] "הוספה לסל" adds the item and opens the drawer with the correct total.
- [ ] Story bubbles open the viewer; bars advance; tapping left/right moves between products.
- [ ] Adding from inside a story updates the header count **without** closing the story.
- [ ] `/` and `Cmd/Ctrl+K` open search; typing returns real products; `↑ ↓ ↵` work.
- [ ] Heat Board order matches Products → sort by "Total Sales" in wp-admin.
- [ ] Deal countdown reaches zero without breaking the page.
- [ ] Header has no search field; only the hero search opens the palette.
- [ ] Hovering a nav parent fills the hero stage with child-category cards (parents are not links).
- [ ] Spotlight deck below Stories sweeps, then swaps to the deal after ~2s.
- [ ] Mobile burger expands parents into child links (accordion).
- [ ] Quantity steppers and remove work; totals update.
- [ ] Open WooCommerce's own cart page — the same items are there.
- [ ] Checkout loads normally with the usual payment options.
- [ ] Cart count badge is correct after a hard reload (see caching note).
- [ ] Mobile: burger menu opens/closes; drawer is usable.
- [ ] Keyboard: skip link, tab order, `Esc` closes the drawer.
- [ ] Analytics (PixelYourSite) and the accessibility widget still load.

## Caching note (LiteSpeed)

The host runs LiteSpeed Cache, and it currently caches `GET /wp-json/wc/store/v1/cart`.
Two things follow:

1. Cart reads from JavaScript append a unique query parameter so they bypass the cache.
   Without it, a returning customer would see an empty cart.
2. The page HTML is safe to cache because **no** per-visitor data is rendered server-side —
   the cart count is filled in by JavaScript on load.

Worth doing on staging regardless: exclude `/wp-json/wc/store/` from LiteSpeed's cache, so
cart responses can never be served to the wrong visitor.

## Layout

```
storyphone-pages/
  storyphone-pages.php          Bootstrap, WooCommerce detection, HPOS declaration
  includes/
    class-templates.php         Registers page templates + nav menu location
    class-assets.php            Enqueues build output, Store API config, drops theme CSS
    class-catalog.php           Read-only WooCommerce product/category queries
    class-render.php            Product and category card markup
    class-stories.php           Builds the stories payload
  templates/
    home.php                    Full-canvas document for the homepage
    parts/                      Header, hero, story rail/viewer, quick reach, heat board,
                                showcase, deal, trust marquee, palette, CTA, footer, drawer
  src/
    main.js                     Entry point
    lib/store-api.js            Store API client, search, money formatting
    lib/cart.js                 Cart drawer
    lib/stories.js              Stories rail + full-screen viewer
    lib/search.js               Command palette
    lib/motion.js               Tilt, hero pick deck, countdown, typing hint
    lib/focus-trap.js           Keeps Tab inside open dialogs
    lib/nav.js                  Header + mobile nav
    lib/reveal.js               Scroll reveal
    styles/                     tokens, base, atmosphere, components, header, hero, stories,
                                reach, heat, deal, sections, palette, footer, drawer
  build/                        Vite output (committed so the zip is installable)
  scripts/make-zip.sh
  scripts/lint-php.mjs
```

## Performance

Category→product ID lists are cached in 15-minute transients. Only IDs are cached, never
prices or stock, so product objects are always re-read and nothing a customer sees goes stale.

## Extending

Add a template by filtering `storyphone_pages_templates`:

```php
add_filter( 'storyphone_pages_templates', function ( $templates ) {
    $templates['storyphone-landing'] = array(
        'label' => 'StoryPhone — Landing',
        'file'  => 'templates/landing.php',
    );
    return $templates;
} );
```

Other filters:

- `storyphone_pages_strip_theme_assets` — set false to keep the theme's CSS/JS.
- `storyphone_pages_load_webfonts` — set false to stop loading Heebo/Assistant from Google.

## Branding

Colors, fonts, radii and shadows are all tokens in `src/styles/tokens.css`. Change the brand
there rather than editing component rules. `--sp-grad-primary` and `--sp-grad-story` drive
most of the visual identity.

The palette is intentionally dark. WooCommerce product shots are nearly always cut out on
white, so cards keep a light "media well" while the page around them stays cinematic — that
contrast is what makes the catalog read as premium rather than as a spreadsheet.
