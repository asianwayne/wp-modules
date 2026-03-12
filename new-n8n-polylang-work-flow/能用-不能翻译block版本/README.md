# Polylang + DeepSeek + n8n Auto-Translation System

## Architecture Overview

```
n8n Scheduler (every 6h)
│
├─ TERM BRANCH (runs first — posts need translated terms)
│   ├── GET /untranslated-terms  (per taxonomy × language)
│   ├── GET /term-payload/{id}   (name, description, slug, meta)
│   ├── DeepSeek API             (translate JSON bundle)
│   └── POST /save-term-translation  (create/update + PLL link)
│
└─ POST BRANCH
    ├── GET /untranslated-posts  (per post_type × language)
    ├── GET /post-payload/{id}   (title, content, excerpt, slug, meta, ACF, taxonomies)
    ├── DeepSeek API             (translate JSON bundle)
    └── POST /save-translation   (create/update + PLL link + thumbnail copy + meta sync)
```

---

## What Gets Translated

| Item | Translated | Shared (not duplicated) |
|---|---|---|
| Post title | ✅ | |
| Post content (HTML preserved) | ✅ | |
| Post excerpt | ✅ | |
| Post slug | ✅ (URL-safe) | |
| Text / HTML meta fields | ✅ | |
| JSON / serialized meta | ✅ (structure kept) | |
| ACF text / textarea / wysiwyg | ✅ | |
| Term name, description, slug | ✅ | |
| Term meta fields | ✅ | |
| Post thumbnail (featured image) | | ✅ same attachment_id |
| Number / boolean / URL meta | | ✅ copied verbatim |
| Image ID meta | | ✅ copied verbatim |
| Polylang language links | ✅ auto-linked | |

---

## Setup Instructions

### 1. Install & Activate the PHP Plugin

1. Copy `polylang-deepseek-translate.php` to `/wp-content/plugins/polylang-deepseek-translate/`
2. Activate in WP Admin → Plugins

### 2. Configure Authentication (choose one)

**Option A — WordPress Application Password (recommended)**
- WP Admin → Users → Your Profile → Application Passwords
- Create a new password, copy the value
- In n8n: create an "HTTP Basic Auth" credential
  - Username: your WP username
  - Password: the application password

**Option B — Shared Secret**
Add to `wp-config.php`:
```php
define( 'PLT_SECRET', 'your-long-random-secret-here' );
```
In n8n: use "HTTP Header Auth" with header `X-PLT-Secret`.

### 3. Import n8n Workflow

1. Open n8n → Workflows → Import
2. Upload `n8n-workflow.json`
3. Update the two `Config Variables` nodes:
   - `wp_base_url` → `https://your-site.com/wp-json/pll-translate/v1`
   - `deepseek_model` → `deepseek-chat` (or `deepseek-reasoner` for highest quality)
   - `deeoseek_api_url` -> `https://api.deepseek.com/v1/chat/completions`
4. Update `Build Term Jobs` node: set your actual target language slugs and taxonomies
5. Update `Build Post Type + Lang Jobs` node: set your post types

### 4. Configure Credentials in n8n

Create two credentials:

**WordPress Application Password** (httpBasicAuth)
- Name: `WordPress Application Password`
- Username: your WP admin username
- Password: the app password from step 2

**DeepSeek API Key** (httpHeaderAuth)
- Name: `DeepSeek API Key`
- Header Name: `Authorization`
- Header Value: `Bearer sk-YOUR-DEEPSEEK-KEY`

### 5. Customize Post Types & Taxonomies

In n8n `Build Post Type + Lang Jobs` node, edit:
```js
const postTypes = ['post', 'page', 'product', 'faq']; // your CPTs
```

In n8n `Build Taxonomy + Lang Jobs` node, edit:
```js
const taxonomies = ['category', 'post_tag', 'product_cat'];
const targetLangs = ['fr', 'de', 'es']; // match your Polylang languages
```

### 6. Non-Translatable Meta Keys

In the PHP plugin, the filter `plt_non_translatable_meta_keys` controls which meta keys
are always copied verbatim and never sent to DeepSeek.

Add your own keys via `functions.php`:
```php
add_filter( 'plt_non_translatable_meta_keys', function( $keys ) {
    $keys[] = '_my_numeric_field';
    $keys[] = '_my_internal_id';
    return $keys;
});
```

---

## REST API Reference

All endpoints: `https://your-site.com/wp-json/pll-translate/v1/`

| Method | Endpoint | Description |
|---|---|---|
| GET | `/languages` | All Polylang languages |
| GET | `/untranslated-posts?post_type=post&lang=fr&per_page=20&page=1` | Posts missing translations |
| GET | `/post-payload/{id}` | Full post data for translation |
| POST | `/save-translation` | Create/update translated post |
| GET | `/untranslated-terms?taxonomy=category&lang=fr` | Terms missing translations |
| GET | `/term-payload/{id}` | Full term data for translation |
| POST | `/save-term-translation` | Create/update translated term |
| GET | `/status` | Translation coverage summary |

### POST /save-translation Body Schema
```json
{
  "source_id": 123,
  "target_lang": "fr",
  "translated": {
    "title":   "Titre traduit",
    "content": "<p>Contenu traduit...</p>",
    "excerpt": "Extrait traduit",
    "slug":    "titre-traduit"
  },
  "meta": {
    "_yoast_wpseo_title":       "Titre SEO traduit",
    "_yoast_wpseo_metadesc":    "Description traduite",
    "custom_text_field":        "Valeur traduite"
  },
  "acf": {
    "hero_heading":    "Titre hero traduit",
    "hero_subheading": "Sous-titre traduit"
  },
  "tax_map": {
    "category": [45, 67],
    "post_tag":  [89]
  }
}
```

### POST /save-term-translation Body Schema
```json
{
  "source_id":   12,
  "taxonomy":    "category",
  "target_lang": "fr",
  "translated": {
    "name":        "Actualités",
    "description": "Les dernières actualités",
    "slug":        "actualites"
  },
  "meta": {
    "term_icon": "valeur-traduite"
  }
}
```

---

## DeepSeek Prompt Strategy

The workflow sends a **single structured JSON payload** per API call containing:
- `core`: title, content, excerpt, slug
- `meta`: all translatable meta key→value pairs
- `acf`: all translatable ACF field key→value pairs

DeepSeek is instructed to:
1. Preserve all HTML tags
2. Return URL-safe slugs
3. Output **only** JSON (no markdown fences, no explanation)
4. Keep JSON structure identical (only values change)

This minimises API round-trips and keeps context coherent.

---

## Important Notes

- **Terms must be translated BEFORE posts** so that `tax_map` can resolve translated term IDs.
  The workflow handles this by running the Term Branch first via separate trigger.
- **Thumbnails**: the same `attachment_id` is shared. The physical file is not duplicated.
- **Idempotent**: re-running the workflow safely updates existing translations.
- **Rate limiting**: 1–2 second delays are added between items. Adjust in Wait nodes.
- **ACF Pro**: `update_field()` is used for ACF — works with repeaters if you pass correctly structured arrays.
