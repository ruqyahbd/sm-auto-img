# Vintage Social Preview

A WordPress plugin that automatically generates a vintage-style social media preview image (Open Graph / Twitter card) for every post — with full **Bangla** support.

When a post has no featured image, the plugin renders the post title onto a parchment background using the **SutonnyMJ** font and serves it as the `og:image` / `twitter:image`. Rank Math is supported out of the box.

## How it works

1. SEO meta tags (or Rank Math filters) point the social image to `?vsp_gen=<post_id>`.
2. `handle_image_request()` intercepts that request and calls `generate_vsp_image()`.
3. The Bangla title is converted from Unicode to **Bijoy ANSI** by [`Unicode2Bijoy`](Unicode2Bijoy.php) so it renders correctly in the SutonnyMJ font.
4. The text is word-wrapped, centered over `assets/background.png`, and streamed back as a JPEG.

## Installation

1. Copy this folder into `wp-content/plugins/`.
2. Make sure `assets/SutonnyMJ.ttf` and `assets/background.png` are present.
3. Activate **Vintage Social Preview** from the WordPress admin.

Requires PHP with the **GD** extension enabled.

## Testing

Test the generated image for any post by visiting:

```
https://example.com/?vsp_gen=<POST_ID>
```

## Bangla conversion (Unicode → Bijoy)

The title rendering relies on `Unicode2Bijoy::convert()`. The converter handles
conjuncts (যুক্তাক্ষর), vowel signs (কার), nukta letters, and digits. Recent
improvements:

- **Conjunct ordering fixed** — the character map is now applied
  longest-match-first, so multi-part conjuncts like `ক্ত্র`, `ষ্ট্র`, `ন্দ্র`
  no longer break into pieces (previously a shorter conjunct such as `ক্ত` was
  substituted first and corrupted the longer one).
- **Nukta letters (ড়, ঢ়, য়)** — decomposed Unicode forms (base + ` ় ` U+09BC),
  common in text copied from the web, are now composed to their precomposed
  forms before conversion. This removes the tofu boxes (□) that appeared for
  words like `বড়`, `পড়া`, `নেয়া`, `হয়`. (These code points are Unicode
  composition exclusions, so they are composed explicitly rather than via NFC.)
- **Generic ya-phola (য-ফলা)** — any base + `্য` that lacks a specific map entry
  (e.g. `য়্য` in `ইয়্যা`) now correctly renders the ya-phola glyph instead of
  dropping it.

### Usage

```php
require_once __DIR__ . '/Unicode2Bijoy.php';

$bijoy = \mirazmac\Unicode2Bijoy::convert('রুকইয়াহ এবং রেফারেনস');
// => i“KBqvn Ges †idv‡ibm
```

## Credits

- Unicode → Bijoy converter by [Miraz Mac](https://mirazmac.info/) (MIT).
- Plugin by [almahmud](https://ruqyahbd.org/).
