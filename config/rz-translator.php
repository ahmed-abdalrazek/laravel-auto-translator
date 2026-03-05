<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    | Define the locales your application supports. The first locale is
    | treated as the source language for translations.
    */
    'locales' => ['en', 'ar', 'fr', 'es'],

    /*
    |--------------------------------------------------------------------------
    | Source Locale
    |--------------------------------------------------------------------------
    | The locale used as the source/base language.
    */
    'source_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Translation Storage
    |--------------------------------------------------------------------------
    | Where to store translations: 'file', 'database', or 'both'.
    */
    'storage' => 'both',

    /*
    |--------------------------------------------------------------------------
    | Translation Files Path
    |--------------------------------------------------------------------------
    | Path to the Laravel lang directory.
    */
    'lang_path' => null, // defaults to lang_path()

    /*
    |--------------------------------------------------------------------------
    | Scan Paths
    |--------------------------------------------------------------------------
    | Directories to scan for translation keys.
    */
    'scan_paths' => [
        'app',
        'resources',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Directories
    |--------------------------------------------------------------------------
    | Directories to exclude from scanning.
    */
    'excluded_dirs' => [
        'vendor',
        'node_modules',
        'storage',
        '.git',
        'bootstrap/cache',
        'public',
    ],

    /*
    |--------------------------------------------------------------------------
    | File Extensions to Scan
    |--------------------------------------------------------------------------
    | File extensions that will be scanned for translation keys.
    */
    'scan_extensions' => ['php', 'blade.php', 'js', 'ts', 'jsx', 'tsx', 'vue'],

    /*
    |--------------------------------------------------------------------------
    | Translation Key Patterns
    |--------------------------------------------------------------------------
    | Regex patterns used to detect translation function calls.
    */
    'key_patterns' => [
        '/(?:__|\btrans|\bLang::get)\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
        '/@lang\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
        '/\bt\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
        '/i18n\.t\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
        '/\$t\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Translation Provider
    |--------------------------------------------------------------------------
    | The default AI provider to use for automatic translations.
    | Options: 'libretranslate', 'argos', 'google', 'deepl', 'openai', 'null'
    */
    'translator' => env('RZ_TRANSLATOR_PROVIDER', 'libretranslate'),

    /*
    |--------------------------------------------------------------------------
    | LibreTranslate Configuration
    |--------------------------------------------------------------------------
    */
    'libretranslate' => [
        'url'    => env('LIBRETRANSLATE_URL', 'https://libretranslate.com'),
        'api_key' => env('LIBRETRANSLATE_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | DeepL Configuration
    |--------------------------------------------------------------------------
    */
    'deepl' => [
        'api_key' => env('DEEPL_API_KEY', ''),
        'free_api' => env('DEEPL_FREE_API', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Translate Configuration
    |--------------------------------------------------------------------------
    */
    'google' => [
        'api_key' => env('GOOGLE_TRANSLATE_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    */
    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'model'   => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation Memory
    |--------------------------------------------------------------------------
    | Cache and reuse previous translations to save API calls.
    */
    'memory' => [
        'enabled' => true,
        'driver'  => 'database', // 'database' or 'cache'
    ],

    /*
    |--------------------------------------------------------------------------
    | File Cache
    |--------------------------------------------------------------------------
    | Cache file hashes to enable incremental scanning.
    */
    'file_cache' => [
        'enabled' => true,
        'path'    => storage_path('app/rz-translator/file-cache.json'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    | Settings for the web dashboard.
    */
    'dashboard' => [
        'enabled'    => true,
        'path'       => 'admin/translations',
        'middleware' => ['web'],
        'per_page'   => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-format Keys
    |--------------------------------------------------------------------------
    | Convert snake_case keys to human-readable English when generating
    | default translations (e.g., order_summary -> "Order Summary").
    */
    'auto_format_keys' => true,

    /*
    |--------------------------------------------------------------------------
    | Export
    |--------------------------------------------------------------------------
    */
    'export' => [
        'path'    => storage_path('app/rz-translator/exports'),
        'formats' => ['json', 'csv', 'zip'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    */
    'tables' => [
        'translation_keys'   => 'translation_keys',
        'translation_values' => 'translation_values',
        'translation_memory' => 'translation_memory',
    ],
];
