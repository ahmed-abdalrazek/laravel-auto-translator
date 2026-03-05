<?php

namespace Rz\LaravelAutoTranslator\Tests\Feature;

use Rz\LaravelAutoTranslator\Models\TranslationKey;
use Rz\LaravelAutoTranslator\Models\TranslationValue;
use Rz\LaravelAutoTranslator\Services\DeadKeyDetector;
use Rz\LaravelAutoTranslator\Tests\TestCase;

class DeadKeyDetectorTest extends TestCase
{
    /** @test */
    public function it_returns_dead_keys(): void
    {
        TranslationKey::create(['key' => 'live_key', 'group' => 'test', 'is_dead' => false]);
        TranslationKey::create(['key' => 'dead_key', 'group' => 'test', 'is_dead' => true]);

        $detector = new DeadKeyDetector(config('rz-translator'));
        $deadKeys = $detector->getDeadKeys();

        $this->assertCount(1, $deadKeys);
        $this->assertSame('dead_key', $deadKeys->first()->key);
    }

    /** @test */
    public function it_deletes_dead_keys_and_their_values(): void
    {
        $key = TranslationKey::create(['key' => 'dead_key', 'group' => 'test', 'is_dead' => true]);
        TranslationValue::create([
            'translation_key_id' => $key->id,
            'locale'             => 'en',
            'value'              => 'Dead',
        ]);

        $detector = new DeadKeyDetector(config('rz-translator'));
        $deleted = $detector->deleteDeadKeys();

        $this->assertSame(1, $deleted);
        $this->assertDatabaseMissing('translation_keys', ['id' => $key->id]);
        $this->assertDatabaseMissing('translation_values', ['translation_key_id' => $key->id]);
    }

    /** @test */
    public function it_returns_correct_stats(): void
    {
        TranslationKey::create(['key' => 'live1', 'group' => 'test', 'is_dead' => false]);
        TranslationKey::create(['key' => 'live2', 'group' => 'test', 'is_dead' => false]);
        TranslationKey::create(['key' => 'dead1', 'group' => 'test', 'is_dead' => true]);

        $detector = new DeadKeyDetector(config('rz-translator'));
        $stats = $detector->stats();

        $this->assertSame(3, $stats['total']);
        $this->assertSame(1, $stats['dead']);
        $this->assertSame(2, $stats['live']);
    }

    /** @test */
    public function it_restores_dead_keys(): void
    {
        $key1 = TranslationKey::create(['key' => 'dead1', 'group' => 'test', 'is_dead' => true]);
        $key2 = TranslationKey::create(['key' => 'dead2', 'group' => 'test', 'is_dead' => true]);

        $detector = new DeadKeyDetector(config('rz-translator'));
        $restored = $detector->restoreDeadKeys([$key1->id]);

        $this->assertSame(1, $restored);
        $this->assertDatabaseHas('translation_keys', ['id' => $key1->id, 'is_dead' => false]);
        $this->assertDatabaseHas('translation_keys', ['id' => $key2->id, 'is_dead' => true]);
    }
}
