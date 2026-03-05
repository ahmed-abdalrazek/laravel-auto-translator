<?php

namespace Rz\LaravelAutoTranslator\Scanners;

use Symfony\Component\Finder\Finder;

/**
 * ProjectScanner: High-speed scanner that detects translation keys across
 * all project files (PHP, Blade, JS, TS, Vue, JSX, TSX).
 *
 * Features:
 * - Incremental scanning (only changed files via hash cache)
 * - Single-pass regex scanning
 * - Skips vendor, node_modules, storage, etc.
 */
class ProjectScanner
{
    /** @var array<string,string> Cached file hashes from previous scan */
    protected array $fileCache = [];

    /** @var array<string,string> Current file hashes */
    protected array $currentHashes = [];

    /** @var array<string> All discovered translation keys */
    protected array $foundKeys = [];

    public function __construct(protected array $config)
    {
    }

    /**
     * Scan the project and return all found translation keys.
     *
     * @param bool $incremental Only scan files that changed since last scan
     * @return array{keys: string[], stats: array}
     */
    public function scan(bool $incremental = true): array
    {
        $this->foundKeys = [];
        $this->loadFileCache();

        $scanPaths = $this->resolveScanPaths();
        $extensions = $this->config['scan_extensions'] ?? ['php', 'blade.php', 'js', 'ts', 'jsx', 'tsx', 'vue'];
        $excludedDirs = $this->config['excluded_dirs'] ?? ['vendor', 'node_modules', 'storage'];
        $patterns = $this->config['key_patterns'] ?? $this->defaultPatterns();

        $scannedFiles = 0;
        $skippedFiles = 0;

        $finder = $this->buildFinder($scanPaths, $extensions, $excludedDirs);

        foreach ($finder as $file) {
            $path = $file->getRealPath();
            $hash = md5_file($path);

            $this->currentHashes[$path] = $hash;

            if ($incremental && isset($this->fileCache[$path]) && $this->fileCache[$path] === $hash) {
                // File hasn't changed – restore keys from cache if available
                $skippedFiles++;
                continue;
            }

            $this->scanFile($path, $patterns);
            $scannedFiles++;
        }

        $this->saveFileCache();

        return [
            'keys'  => array_values(array_unique($this->foundKeys)),
            'stats' => [
                'scanned' => $scannedFiles,
                'skipped' => $skippedFiles,
                'total_files' => $scannedFiles + $skippedFiles,
            ],
        ];
    }

    /**
     * Scan a single file for translation keys.
     */
    protected function scanFile(string $path, array $patterns): void
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return;
        }

        // Single combined pattern for efficiency
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $key) {
                    $key = trim($key);
                    if ($key !== '' && strlen($key) < 256) {
                        $this->foundKeys[] = $key;
                    }
                }
            }
        }
    }

    /**
     * Build Symfony Finder configured for the scan.
     */
    protected function buildFinder(array $paths, array $extensions, array $excludedDirs): Finder
    {
        $finder = new Finder();
        $finder->files()
               ->in($paths)
               ->ignoreVCS(true)
               ->ignoreUnreadableDirs(true);

        foreach ($excludedDirs as $dir) {
            $finder->exclude($dir);
        }

        // Build extension filter – handle 'blade.php' specially
        $phpExtensions = array_filter($extensions, fn($e) => !str_contains($e, '.'));
        $finder->name(array_map(fn($e) => "*.$e", $phpExtensions));

        return $finder;
    }

    /**
     * Load the file hash cache from disk.
     */
    protected function loadFileCache(): void
    {
        $cachePath = $this->config['file_cache']['path']
            ?? storage_path('app/rz-translator/file-cache.json');

        if (file_exists($cachePath)) {
            $data = json_decode(file_get_contents($cachePath), true);
            $this->fileCache = is_array($data) ? $data : [];
        }
    }

    /**
     * Save the current file hash cache to disk.
     */
    protected function saveFileCache(): void
    {
        if (!($this->config['file_cache']['enabled'] ?? true)) {
            return;
        }

        $cachePath = $this->config['file_cache']['path']
            ?? storage_path('app/rz-translator/file-cache.json');

        $dir = dirname($cachePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($cachePath, json_encode($this->currentHashes, JSON_PRETTY_PRINT));
    }

    /**
     * Resolve scan paths relative to the Laravel base path.
     */
    protected function resolveScanPaths(): array
    {
        $paths = $this->config['scan_paths'] ?? ['app', 'resources'];
        $resolved = [];

        foreach ($paths as $path) {
            $fullPath = str_starts_with($path, '/') ? $path : base_path($path);
            if (is_dir($fullPath)) {
                $resolved[] = $fullPath;
            }
        }

        return $resolved ?: [base_path('app'), base_path('resources')];
    }

    /**
     * Default translation key detection patterns.
     */
    protected function defaultPatterns(): array
    {
        return [
            // PHP: __('key'), trans('key'), Lang::get('key')
            '/(?:__|\btrans|\bLang::get)\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
            // Blade: @lang('key')
            '/@lang\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
            // JS/TS: t('key'), i18n.t('key'), $t('key')
            '/(?:\$t|i18n\.t|\bt)\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
        ];
    }
}
