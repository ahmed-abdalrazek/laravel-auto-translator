<?php

namespace Rz\LaravelAutoTranslator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rz\LaravelAutoTranslator\Scanners\ProjectScanner;

class ProjectScannerTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/rz-scanner-test-' . uniqid();
        mkdir($this->tmpDir, 0755, true);
        mkdir($this->tmpDir . '/app', 0755, true);
        mkdir($this->tmpDir . '/resources', 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tmpDir);
        parent::tearDown();
    }

    /** @test */
    public function it_detects_double_underscore_keys(): void
    {
        file_put_contents(
            $this->tmpDir . '/app/test.php',
            '<?php echo __("welcome.message"); ?>'
        );

        $scanner = $this->makeScanner();
        $result = $scanner->scan(false);

        $this->assertContains('welcome.message', $result['keys']);
    }

    /** @test */
    public function it_detects_trans_keys(): void
    {
        file_put_contents(
            $this->tmpDir . '/app/ctrl.php',
            '<?php return trans("auth.login"); ?>'
        );

        $scanner = $this->makeScanner();
        $result = $scanner->scan(false);

        $this->assertContains('auth.login', $result['keys']);
    }

    /** @test */
    public function it_detects_blade_lang_directive(): void
    {
        file_put_contents(
            $this->tmpDir . '/resources/welcome.blade.php',
            '<h1>@lang("home.hero_title")</h1>'
        );

        $scanner = $this->makeScanner();
        $result = $scanner->scan(false);

        $this->assertContains('home.hero_title', $result['keys']);
    }

    /** @test */
    public function it_detects_javascript_t_function(): void
    {
        file_put_contents(
            $this->tmpDir . '/resources/app.js',
            "const msg = t('user.greeting');"
        );

        $scanner = $this->makeScanner();
        $result = $scanner->scan(false);

        $this->assertContains('user.greeting', $result['keys']);
    }

    /** @test */
    public function it_returns_unique_keys(): void
    {
        file_put_contents(
            $this->tmpDir . '/app/dup.php',
            '<?php __("duplicate.key"); __("duplicate.key"); ?>'
        );

        $scanner = $this->makeScanner();
        $result = $scanner->scan(false);

        $this->assertSame(
            count(array_unique($result['keys'])),
            count($result['keys'])
        );
    }

    /** @test */
    public function it_reports_scanned_file_count(): void
    {
        file_put_contents($this->tmpDir . '/app/a.php', '<?php __("a.key"); ?>');
        file_put_contents($this->tmpDir . '/app/b.php', '<?php __("b.key"); ?>');

        $scanner = $this->makeScanner();
        $result = $scanner->scan(false);

        $this->assertGreaterThanOrEqual(2, $result['stats']['scanned']);
    }

    private function makeScanner(): ProjectScanner
    {
        return new ProjectScanner([
            'scan_paths'    => [$this->tmpDir . '/app', $this->tmpDir . '/resources'],
            'excluded_dirs' => ['vendor', 'node_modules'],
            'scan_extensions' => ['php', 'js', 'ts', 'vue'],
            'file_cache'    => ['enabled' => false, 'path' => ''],
            'key_patterns'  => [
                '/(?:__|\btrans|\bLang::get)\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
                '/@lang\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
                '/(?:\$t|i18n\.t|\bt)\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
            ],
        ]);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
