<?php

namespace Rz\LaravelAutoTranslator\Tests\Feature;

use Rz\LaravelAutoTranslator\Models\TranslationKey;
use Rz\LaravelAutoTranslator\Models\TranslationValue;
use Rz\LaravelAutoTranslator\Services\KeyGeneratorService;
use Rz\LaravelAutoTranslator\Tests\TestCase;

class KeySyncTest extends TestCase
{
    /** @test */
    public function it_creates_new_translation_keys_in_database(): void
    {
        $service = new KeyGeneratorService([
            'source_locale'    => 'en',
            'locales'          => ['en', 'ar', 'fr'],
            'auto_format_keys' => true,
            'lang_path'        => null,
        ]);

        $result = $service->syncKeys(['auth.login', 'auth.logout', 'Home Page']);

        $this->assertSame(3, $result['new']);
        $this->assertSame(0, $result['existing']);
        $this->assertDatabaseHas('translation_keys', ['key' => 'login', 'group' => 'auth']);
        $this->assertDatabaseHas('translation_keys', ['key' => 'logout', 'group' => 'auth']);
        $this->assertDatabaseHas('translation_keys', ['key' => 'Home Page', 'group' => '*']);
    }

    /** @test */
    public function it_generates_default_english_values(): void
    {
        $service = new KeyGeneratorService([
            'source_locale'    => 'en',
            'locales'          => ['en', 'ar', 'fr'],
            'auto_format_keys' => true,
            'lang_path'        => null,
        ]);

        $service->syncKeys(['order_summary']);

        $key = TranslationKey::where('key', 'order_summary')->first();
        $this->assertNotNull($key);

        $enValue = $key->values()->where('locale', 'en')->first();
        $this->assertSame('Order Summary', $enValue?->value);
    }

    /** @test */
    public function it_creates_empty_values_for_non_source_locales(): void
    {
        $service = new KeyGeneratorService([
            'source_locale'    => 'en',
            'locales'          => ['en', 'ar', 'fr'],
            'auto_format_keys' => true,
            'lang_path'        => null,
        ]);

        $service->syncKeys(['test.key']);

        $key = TranslationKey::where('key', 'key')->where('group', 'test')->first();
        $this->assertNotNull($key);

        $arValue = $key->values()->where('locale', 'ar')->first();
        $this->assertNotNull($arValue);
        $this->assertNull($arValue->value);

        $frValue = $key->values()->where('locale', 'fr')->first();
        $this->assertNotNull($frValue);
        $this->assertNull($frValue->value);
    }

    /** @test */
    public function it_does_not_duplicate_existing_keys(): void
    {
        $service = new KeyGeneratorService([
            'source_locale'    => 'en',
            'locales'          => ['en', 'ar'],
            'auto_format_keys' => true,
            'lang_path'        => null,
        ]);

        $service->syncKeys(['auth.login']);
        $result = $service->syncKeys(['auth.login']);

        $this->assertSame(0, $result['new']);
        $this->assertSame(1, $result['existing']);
        $this->assertSame(1, TranslationKey::where('key', 'login')->where('group', 'auth')->count());
    }

    /** @test */
    public function it_marks_previously_dead_keys_as_alive(): void
    {
        $service = new KeyGeneratorService([
            'source_locale'    => 'en',
            'locales'          => ['en'],
            'auto_format_keys' => true,
            'lang_path'        => null,
        ]);

        // Create and mark dead
        TranslationKey::create(['key' => 'old_key', 'group' => 'test', 'is_dead' => true]);

        // Sync – should revive
        $service->syncKeys(['test.old_key']);

        $this->assertDatabaseHas('translation_keys', [
            'key'     => 'old_key',
            'group'   => 'test',
            'is_dead' => false,
        ]);
    }
}
