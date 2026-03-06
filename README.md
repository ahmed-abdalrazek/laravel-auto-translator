# aar/laravel-auto-translator

A **production-ready, high-performance translation management system** for Laravel.  
Handles **10,000+ translation keys** with AI-powered translations, a modern dashboard, CLI tools, and full database storage.

> ✅ Ready for **GitHub** and **Packagist** — `composer require aar/laravel-auto-translator`

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
composer require aar/laravel-auto-translator
```

### Publish Configuration

```bash
php artisan vendor:publish --tag=aar-translator-config
```

### Run Migrations

```bash
php artisan migrate
```

---

## Configuration

The config file is at `config/aar-translator.php` after publishing.

### Key Settings

```php
return [
    'locales'       => ['en', 'ar', 'fr', 'es'],
    'source_locale' => 'en',
    'storage'       => 'both',          // 'file', 'database', or 'both'
    'scan_paths'    => ['app', 'resources'],
    'excluded_dirs' => ['vendor', 'node_modules', 'storage'],
    'translator'    => env('AAR_TRANSLATOR_PROVIDER', 'libretranslate'),
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
AAR_TRANSLATOR_PROVIDER=libretranslate
LIBRETRANSLATE_URL=https://libretranslate.com
LIBRETRANSLATE_API_KEY=your-optional-api-key
```

Self-host for free: `pip install libretranslate && libretranslate --host 0.0.0.0 --port 5000`

### DeepL

```env
AAR_TRANSLATOR_PROVIDER=deepl
DEEPL_API_KEY=your-deepl-api-key
DEEPL_FREE_API=true
```

### Google Translate

```env
AAR_TRANSLATOR_PROVIDER=google
GOOGLE_TRANSLATE_API_KEY=your-google-api-key
```

### OpenAI (GPT-4o-mini or better)

```env
AAR_TRANSLATOR_PROVIDER=openai
OPENAI_API_KEY=your-openai-key
OPENAI_MODEL=gpt-4o-mini
```

---

## CLI Commands

All commands use the `aar:translate` prefix.

### Scan Project

```bash
# Incremental scan (skips unchanged files)
php artisan aar:translate scan

# Full scan (clears file hash cache)
php artisan aar:translate scan --fresh

# Scan and immediately export to language files
php artisan aar:translate scan --export
```

### Auto-Translate Missing Keys

```bash
# Translate all missing keys for all locales
php artisan aar:translate auto

# Translate a specific locale only
php artisan aar:translate auto --locale=ar

# Override the provider for this run
php artisan aar:translate auto --provider=deepl

# Translate and export to files
php artisan aar:translate auto --export
```

### Clean Dead Keys

```bash
# Preview dead keys without deleting (dry run)
php artisan aar:translate clean --dry-run

# Delete dead keys (with confirmation prompt)
php artisan aar:translate clean

# Delete without confirmation
php artisan aar:translate clean --force
```

### Export Translations

```bash
php artisan aar:translate export               # JSON (default)
php artisan aar:translate export --format=csv  # CSV
php artisan aar:translate export --format=zip  # ZIP (one JSON per locale)
php artisan aar:translate export --locale=ar   # Export single locale
```

### Import Translations

```bash
php artisan aar:translate import translations.json
php artisan aar:translate import translations.csv
```

### Translation Status

```bash
php artisan aar:translate status
```

Example output:
```
📊 Translation Status

  Total Keys : 142
  Dead Keys  : 3

 Locale  Translated  Missing  Total  Progress
 EN      142         0        142    100.0% [████████████████████]
 AR      87          55       142    61.3%  [████████████░░░░░░░░]
 FR      130         12       142    91.5%  [██████████████████░░]
```

### Manage Locales

```bash
# Add a new locale
php artisan aar:translate lang add de

# Remove a locale
php artisan aar:translate lang remove es
```

---

## Dashboard

Access the web dashboard at `/admin/translations` (configurable).

### Dashboard Features

- **Overview**: Total keys, dead keys, missing translations, locale completion bars
- **Keys List**: Paginated table with search, group filter, missing-only filter
- **Inline Editing**: Click any translation to edit it directly in the browser
- **Bulk Actions**: Delete all dead keys, trigger scan, trigger auto-translate
- **Export**: Download JSON, CSV, or ZIP directly from the browser
- **Import**: Upload a JSON or CSV file to import translations

### Securing the Dashboard

Edit `config/aar-translator.php`:

```php
'dashboard' => [
    'enabled'    => true,
    'path'       => 'admin/translations',
    'middleware' => ['web', 'auth'],        // Require authenticated users
    // Or with permissions:
    // 'middleware' => ['web', 'can:manage-translations'],
],
```

---

## Publishing Assets

```bash
# Publish config
php artisan vendor:publish --tag=aar-translator-config

# Publish migrations
php artisan vendor:publish --tag=aar-translator-migrations

# Publish views (to customise the dashboard)
php artisan vendor:publish --tag=aar-translator-views
```

---

## Database Tables

The package creates three tables:

| Table | Purpose |
|---|---|
| `translation_keys` | Stores each unique translation key with group and dead-key flag |
| `translation_values` | Stores the translated value per key and locale |
| `translation_memory` | Caches previously translated phrases to avoid redundant API calls |

---

## Translation Memory

Translation memory prevents duplicate API calls by caching phrase-to-phrase translations.

```php
// config/aar-translator.php
'memory' => [
    'enabled' => true,
    'driver'  => 'database',   // 'database' or 'cache'
],
```

Use `'driver' => 'cache'` for Redis-backed memory in high-traffic apps.

---

## Incremental Scanning

File hashes are cached in `storage/app/aar-translator/file-cache.json`. Only modified files are re-scanned on subsequent runs, making it fast even for large codebases.

---

## Package Structure

```
aar/laravel-auto-translator/
├── config/
│   └── aar-translator.php          # Main configuration
├── database/
│   └── migrations/
│       ├── ...create_translation_keys_table.php
│       ├── ...create_translation_values_table.php
│       └── ...create_translation_memory_table.php
├── resources/
│   └── views/
│       └── dashboard/
│           ├── layout.blade.php
│           ├── index.blade.php     # Dashboard overview
│           └── keys.blade.php      # Keys management
├── routes/
│   └── web.php
├── src/
│   ├── AarTranslatorServiceProvider.php
│   ├── Console/Commands/           # All CLI commands
│   ├── Dashboard/Controllers/      # Web dashboard
│   ├── Export/                     # Export & Import services
│   ├── Memory/                     # Translation memory
│   ├── Models/                     # Eloquent models
│   ├── Scanners/                   # Project file scanner
│   ├── Services/                   # Core business logic
│   └── Translators/                # AI provider adapters
└── tests/
    ├── Feature/                    # Integration tests
    └── Unit/                       # Unit tests
```

---

## Testing

```bash
# Run all tests
./vendor/bin/phpunit --testdox

# Run only unit tests
./vendor/bin/phpunit --testsuite Unit --testdox

# Run only feature tests
./vendor/bin/phpunit --testsuite Feature --testdox
```

Tests use **Orchestra Testbench** with an in-memory SQLite database — no external services required.

---

## Extending

### Adding a Custom Translator

Implement `Aar\AutoTranslator\Translators\TranslatorInterface`:

```php
use Aar\AutoTranslator\Translators\TranslatorInterface;

class MyCustomTranslator implements TranslatorInterface
{
    public function translate(string $text, string $targetLang, string $sourceLang = 'en'): string
    {
        // your implementation
    }

    public function translateBatch(array $texts, string $targetLang, string $sourceLang = 'en'): array
    {
        return array_map(fn($t) => $this->translate($t, $targetLang, $sourceLang), $texts);
    }

    public function getProviderName(): string
    {
        return 'my-custom-translator';
    }
}
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history.

---

## License

MIT — see [LICENSE](LICENSE) for details.

---

## Contributing

Pull requests are welcome. For major changes, please open an issue first.

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Commit your changes: `git commit -m 'Add my feature'`
4. Push the branch: `git push origin feature/my-feature`
5. Open a Pull Request

---

## Credits

Built with ❤️ using [Laravel](https://laravel.com), [TailwindCSS](https://tailwindcss.com), and [Alpine.js](https://alpinejs.dev).
