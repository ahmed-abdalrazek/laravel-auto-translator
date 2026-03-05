# rz/laravel-auto-translator

A **production-ready, high-performance translation management system** for Laravel.  
Handles **10,000+ translation keys** with AI-powered translations, a modern dashboard, CLI tools, and full database storage.

---

## Features

| Feature | Description |
|---|---|
| 🔍 **High-Speed Scanner** | Scans PHP, Blade, Vue, React, JS/TS with incremental file-hash caching |
| 🤖 **AI Translation** | LibreTranslate (default), DeepL, Google Translate, OpenAI |
| 🧠 **Translation Memory** | Never re-translates the same phrase twice |
| 🗑️ **Dead Key Detection** | Finds and removes orphaned translation keys |
| 🌐 **Multi-Locale** | Manage any number of locales dynamically via CLI |
| 🗄️ **Database Storage** | Fully indexed database tables with file sync |
| 🖥️ **Dashboard UI** | Inline editing, search, filter, bulk actions, progress bars |
| ⚡ **CLI Commands** | `scan`, `auto`, `clean`, `export`, `import`, `status`, `lang` |
| 📦 **Export/Import** | JSON, CSV, ZIP formats |

---

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12
- Composer

---

## Installation

```bash
composer require rz/laravel-auto-translator
```

### Publish Configuration

```bash
php artisan vendor:publish --tag=rz-translator-config
```

### Run Migrations

```bash
php artisan migrate
```

---

## Configuration

The config file is at `config/rz-translator.php` after publishing.

### Key Settings

```php
return [
    'locales'       => ['en', 'ar', 'fr', 'es'],
    'source_locale' => 'en',
    'storage'       => 'both',          // 'file', 'database', or 'both'
    'scan_paths'    => ['app', 'resources'],
    'excluded_dirs' => ['vendor', 'node_modules', 'storage'],
    'translator'    => env('RZ_TRANSLATOR_PROVIDER', 'libretranslate'),
    'memory'        => ['enabled' => true, 'driver' => 'database'],
    'dashboard'     => [
        'enabled'    => true,
        'path'       => 'admin/translations',
        'middleware' => ['web'],
    ],
];
```

---

## AI Translation Setup

### LibreTranslate (Free, Open-Source – Default)

```env
RZ_TRANSLATOR_PROVIDER=libretranslate
LIBRETRANSLATE_URL=https://libretranslate.com
LIBRETRANSLATE_API_KEY=your-optional-api-key
```

Self-host for free: `pip install libretranslate && libretranslate --host 0.0.0.0 --port 5000`

### DeepL

```env
RZ_TRANSLATOR_PROVIDER=deepl
DEEPL_API_KEY=your-deepl-api-key
DEEPL_FREE_API=true
```

### Google Translate

```env
RZ_TRANSLATOR_PROVIDER=google
GOOGLE_TRANSLATE_API_KEY=your-google-api-key
```

### OpenAI

```env
RZ_TRANSLATOR_PROVIDER=openai
OPENAI_API_KEY=your-openai-api-key
OPENAI_MODEL=gpt-4o-mini
```

---

## CLI Commands

### Scan Project

```bash
php artisan rz:translate scan           # Incremental scan
php artisan rz:translate scan --fresh   # Full scan (clears cache)
php artisan rz:translate scan --export  # Scan + export to files
```

### Auto-Translate

```bash
php artisan rz:translate auto                    # All locales
php artisan rz:translate auto --locale=ar        # Single locale
php artisan rz:translate auto --provider=deepl   # Override provider
```

### Clean Dead Keys

```bash
php artisan rz:translate clean              # Interactive
php artisan rz:translate clean --dry-run    # Preview only
php artisan rz:translate clean --force      # No confirmation
```

### Export

```bash
php artisan rz:translate export --format=json
php artisan rz:translate export --format=csv
php artisan rz:translate export --format=zip
php artisan rz:translate export --locale=ar   # Single locale
```

### Import

```bash
php artisan rz:translate import /path/to/file.json
php artisan rz:translate import /path/to/file.csv
```

**JSON format:** `{ "locale": { "group": { "key": "value" } } }`  
**CSV format:** `group,key,en,fr,ar,...`

### Status

```bash
php artisan rz:translate status
```

### Manage Locales

```bash
php artisan rz:translate lang add de
php artisan rz:translate lang remove es
```

---

## Dashboard

Accessible at `/admin/translations` (configurable).

### Features

- 📊 Overview cards with total keys, dead keys, missing translations
- 📈 Locale completion progress bars
- 🔍 Search + filter by group/file/language/status
- ✏️ Inline cell editing (double-click to edit)
- 🤖 One-click auto-translate all missing keys
- 🔄 Scan project from browser
- 🗑️ Bulk delete dead keys
- 📦 Export (JSON/CSV/ZIP) and import via file upload

### Securing the Dashboard

```php
'dashboard' => [
    'middleware' => ['web', 'auth', 'can:manage-translations'],
],
```

---

## Key Detection Patterns

| Pattern | Language |
|---|---|
| `__('key')`, `trans('key')`, `Lang::get('key')` | PHP |
| `@lang('key')` | Blade |
| `t('key')`, `i18n.t('key')`, `$t('key')` | JS/Vue/React |

---

## Auto-Format Keys

When `auto_format_keys` is `true` (default):

| Key | Generated English |
|---|---|
| `order_summary` | "Order Summary" |
| `auth.login` | "Login" |
| `user_profile` | "User Profile" |

---

## Testing

```bash
./vendor/bin/phpunit
```

---

## Database Tables

| Table | Description |
|---|---|
| `translation_keys` | `id`, `key`, `group`, `file`, `is_dead` |
| `translation_values` | `id`, `translation_key_id`, `locale`, `value`, `is_auto_translated`, `provider` |
| `translation_memory` | `id`, `source_text`, `source_hash`, `source_lang`, `target_lang`, `translated_text`, `provider`, `use_count` |

---

## License

MIT
