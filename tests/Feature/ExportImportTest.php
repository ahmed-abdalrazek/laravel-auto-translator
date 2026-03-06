<?php

namespace Aar\AutoTranslator\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Aar\AutoTranslator\Models\TranslationKey;
use Aar\AutoTranslator\Models\TranslationValue;
use Aar\AutoTranslator\Export\ExportService;
use Aar\AutoTranslator\Export\ImportService;
use Aar\AutoTranslator\Services\KeyGeneratorService;
use Aar\AutoTranslator\Tests\TestCase;

class ExportImportTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/aar-export-test-' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tmpDir);
        parent::tearDown();
    }

    /** @test */
    public function it_exports_translations_to_json(): void
    {
        $this->seedTranslations();

        $service = new ExportService($this->exportConfig());
        $file = $service->exportJson();

        $this->assertFileExists($file);
        $data = json_decode(file_get_contents($file), true);
        $this->assertArrayHasKey('en', $data);
        $this->assertArrayHasKey('fr', $data);
    }

    /** @test */
    public function it_exports_translations_to_csv(): void
    {
        $this->seedTranslations();

        $service = new ExportService($this->exportConfig());
        $file = $service->exportCsv();

        $this->assertFileExists($file);
        $lines = file($file);
        $this->assertGreaterThan(1, count($lines));
        $this->assertStringContainsString('group', $lines[0]);
        $this->assertStringContainsString('key', $lines[0]);
    }

    /** @test */
    public function it_imports_translations_from_json(): void
    {
        $jsonFile = $this->tmpDir . '/import.json';
        $data = [
            'en' => [
                '*' => [
                    'Welcome' => 'Welcome',
                    'Goodbye' => 'Goodbye',
                ],
            ],
            'fr' => [
                '*' => [
                    'Welcome' => 'Bienvenue',
                    'Goodbye' => 'Au revoir',
                ],
            ],
        ];
        file_put_contents($jsonFile, json_encode($data));

        $keyGen = new KeyGeneratorService(config('aar-translator'));
        $service = new ImportService($keyGen, config('aar-translator'));
        $result = $service->importJson($jsonFile);

        $this->assertGreaterThan(0, $result['imported']);
        $this->assertDatabaseHas('translation_keys', ['key' => 'Welcome', 'group' => '*']);
    }

    /** @test */
    public function it_imports_translations_from_csv(): void
    {
        $csvFile = $this->tmpDir . '/import.csv';
        $handle = fopen($csvFile, 'w');
        fputcsv($handle, ['group', 'key', 'en', 'fr']);
        fputcsv($handle, ['auth', 'login', 'Login', 'Connexion']);
        fputcsv($handle, ['auth', 'logout', 'Logout', 'Déconnexion']);
        fclose($handle);

        $keyGen = new KeyGeneratorService(config('aar-translator'));
        $service = new ImportService($keyGen, config('aar-translator'));
        $result = $service->importCsv($csvFile);

        $this->assertGreaterThan(0, $result['imported']);
        $this->assertDatabaseHas('translation_keys', ['key' => 'login', 'group' => 'auth']);
        $this->assertDatabaseHas('translation_values', ['locale' => 'fr', 'value' => 'Connexion']);
    }

    private function seedTranslations(): void
    {
        $key = TranslationKey::create(['key' => 'welcome', 'group' => '*', 'is_dead' => false]);
        TranslationValue::create(['translation_key_id' => $key->id, 'locale' => 'en', 'value' => 'Welcome']);
        TranslationValue::create(['translation_key_id' => $key->id, 'locale' => 'fr', 'value' => 'Bienvenue']);
    }

    private function exportConfig(): array
    {
        return array_merge(config('aar-translator'), [
            'export' => ['path' => $this->tmpDir],
            'locales' => ['en', 'fr'],
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
