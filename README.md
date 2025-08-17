# Smart Image Resizer & CDN Helper (Slim 4)

Resize, cache, and serve images fast with **Slim 4**. Two processing engines are available, immutable caching is built-in, and you get **WebP** + **progressive JPEG** out of the box.

---

## Features
- **Two engines**
  - **gd (native):** `imagecreatefrom*`, `imagecopyresampled`, **progressive JPEG**, **WebP**
  - **lib (Intervention v3):** cleaner API, auto-orient, sharpening, WebP/JPEG (GD or Imagick backend)
- **Deterministic disk cache** in `public/resized/` (gitignored)
- **Long-term caching** (`Cache-Control: immutable`, `ETag`, 304 support)
- **Content negotiation** (prefers WebP when the client supports it)
- **Secure clear endpoint** (token)
- **CLI cleaner** to purge old files
- **PHP `srcset` helper** for responsive images

---

## Requirements
- PHP **8.2+**
- GD extension (required). Imagick optional (improves quality/features with Intervention)
- Composer

---

## Quickstart
```bash
composer install
cp config/app.example.env .env     # set BASE_IMAGE_DIR, RESIZED_DIR, ADMIN_TOKEN
composer start                     # http://localhost:8080
```

### `.env` keys
```
APP_ENV=local
ADMIN_TOKEN=change_me
BASE_IMAGE_DIR=/absolute/path/to/originals
RESIZED_DIR=/absolute/path/to/project/public/resized
DEFAULT_ENGINE=gd          # gd | lib
DEFAULT_QUALITY=82         # 1..100
DEFAULT_FIT=contain        # contain | cover | scale
```

---

## Endpoints

### 1) Resize an image
`GET /resize/{path}?w=&h=&fit=&fmt=&q=&engine=`

- **path**: relative to `BASE_IMAGE_DIR` (e.g., `catalog/hero.jpg`)
- **w / h**: target width/height (one or both). Preserves aspect ratio if one is omitted.
- **fit**: `contain` (default), `cover`, or `scale`
- **fmt**: `webp` or `jpg` (omit to let `Accept` header decide; prefers WebP)
- **q**: quality 1–100 (default **82**)
- **engine**: `gd` (native) or `lib` (Intervention v3)

**Examples**
```
/resize/catalog/hero.jpg?w=640&h=360&fit=cover&fmt=webp&engine=gd
/resize/catalog/hero.jpg?w=1200&fmt=jpg
```

**Response & caching**
- Files are written to `public/resized/`
- Headers: `Cache-Control: public, max-age=31536000, immutable` + `ETag`
- Conditional GET supported (`If-None-Match` → **304**)
- **Progressive JPEG** enabled by default for JPEG output

---

### 2) Clear cached images (HTTP)
`POST /cache/clear`
Headers: `X-Admin-Token: <your-token>`
Body (JSON):
```json
{ "pattern": "hero.jpg", "dry_run": true }
```

- `pattern` (optional): matches raw filename or the SHA1 used in cache keys
- `dry_run` (optional): when `true`, lists what would be deleted without removing files

**Example**
```bash
curl -X POST http://localhost:8080/cache/clear   -H "Content-Type: application/json"   -H "X-Admin-Token: $ADMIN_TOKEN"   -d '{ "pattern": "hero.jpg", "dry_run": true }'
```

> Keep the token secret; rotate via `.env` when needed.

---

## PHP helper: generate `srcset`
Use this helper to emit `src`, `srcset`, and `sizes` for responsive `<img>` tags backed by `/resize`.

```php
use App\Support\Srcset;

$img = Srcset::generate(
  'catalog/hero.jpg',
  [320, 480, 640, 960, 1200, 1600],
  ['fit' => 'cover', 'fmt' => 'webp', 'q' => 82, 'engine' => 'gd', 'sizes' => '100vw'],
  $_ENV['BASE_URL'] ?? null   // optional absolute base; omit for relative URLs
);

echo '<img src="'.htmlspecialchars($img['src']).'" '.
     'srcset="'.htmlspecialchars($img['srcset']).'" '.
     'sizes="'.htmlspecialchars($img['sizes']).'" '.
     'alt="Hero">';
```

---

## CLI tools

### A) `bin/clean` — purge cached files by age
Removes files in `public/resized/` older than **N** days.

```bash
# one-time
chmod +x bin/clean

# dry run (no deletions)
./bin/clean --days=30 --dry-run=1

# execute (delete)
./bin/clean --days=30

# limit by extension(s)
./bin/clean --days=30 --ext=jpg,webp

# custom directory
./bin/clean --dir=/abs/path/to/public/resized --days=45
```

**Cron example** (monthly at 02:10):
```
10 2 1 * * /path/to/project/bin/clean --days=30 >> /var/log/image-cache-clean.log 2>&1
```

**Composer script** (already added):
```bash
composer clean-cache
```

### B) `bin/clear` — alias for the cleaner (optional)
If you prefer the command to be called `bin/clear`, use either method:

**Option 1 — file alias**
```bash
cp bin/clean bin/clear
chmod +x bin/clear
# usage:
./bin/clear --days=30 --dry-run=1
```

**Option 2 — Composer alias**
Add to `composer.json` → `"scripts"`:
```json
"clear-cache": "php bin/clean --days=30"
```
Then run:
```bash
composer clear-cache
```

> **Difference:**
> • `bin/clean` / `bin/clear` = **age-based purge (local filesystem)**
> • `POST /cache/clear` = **pattern-based deletion via HTTP**

---

## Project structure (abridged)
```
public/
  index.php
  resized/           # generated
src/
  Controllers/
    ResizeController.php
    ClearController.php
  Engines/
    ImageEngine.php
    GDNativeEngine.php
    InterventionEngine.php
  Services/
    ImageService.php
  Support/
    Config.php
    PathGuard.php
    Srcset.php
bin/
  clean
config/
  app.example.env
```

---

## Notes
- Use **`fmt`** explicitly for predictable formats, or omit to rely on `Accept` negotiation (prefers WebP).
- If using **Intervention** with Imagick, switch driver to `'imagick'` in `InterventionEngine`.
- Add **AVIF** later by extending engines (Imagick + libheif, or external encoder).
- For large deployments, consider sharding `public/resized/` into subfolders.

---

## License
MIT
