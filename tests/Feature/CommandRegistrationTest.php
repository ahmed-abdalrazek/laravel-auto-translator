<?php

namespace Aar\AutoTranslator\Tests\Feature;

use Aar\AutoTranslator\Models\TranslationKey;
use Aar\AutoTranslator\Models\TranslationValue;
use Aar\AutoTranslator\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class CommandRegistrationTest extends TestCase
{
    /** @test */
    public function all_commands_are_registered_with_unique_names(): void
    {
        $expectedCommands = [
            'aar:scan',
            'aar:auto',
            'aar:clean',
            'aar:export',
            'aar:import',
            'aar:lang',
            'aar:status',
        ];

        $registeredCommands = array_keys(Artisan::all());

        foreach ($expectedCommands as $command) {
            $this->assertContains(
                $command,
                $registeredCommands,
                "Command '{$command}' is not registered."
            );
        }
    }

    /** @test */
    public function clean_command_runs_with_dry_run(): void
    {
        TranslationKey::create(['key' => 'dead_key', 'group' => 'test', 'is_dead' => true]);

        $this->artisan('aar:clean', ['--dry-run' => true])
            ->assertExitCode(0);

        // Key should still exist after dry run
        $this->assertDatabaseHas('translation_keys', ['key' => 'dead_key', 'is_dead' => true]);
    }

    /** @test */
    public function clean_command_deletes_dead_keys_with_force(): void
    {
        TranslationKey::create(['key' => 'dead_key', 'group' => 'test', 'is_dead' => true]);

        $this->artisan('aar:clean', ['--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('translation_keys', ['key' => 'dead_key']);
    }

    /** @test */
    public function clean_command_reports_no_dead_keys(): void
    {
        TranslationKey::create(['key' => 'live_key', 'group' => 'test', 'is_dead' => false]);

        $this->artisan('aar:clean')
            ->expectsOutputToContain('No dead translation keys found')
            ->assertExitCode(0);
    }

    /** @test */
    public function status_command_runs_successfully(): void
    {
        $key = TranslationKey::create(['key' => 'test_key', 'group' => 'test', 'is_dead' => false]);
        TranslationValue::create([
            'translation_key_id' => $key->id,
            'locale' => 'en',
            'value' => 'Test',
        ]);

        $this->artisan('aar:status')
            ->assertExitCode(0);
    }

    /** @test */
    public function export_command_runs_successfully(): void
    {
        $key = TranslationKey::create(['key' => 'hello', 'group' => '*', 'is_dead' => false]);
        TranslationValue::create([
            'translation_key_id' => $key->id,
            'locale' => 'en',
            'value' => 'Hello',
        ]);

        $this->artisan('aar:export', ['--format' => 'json'])
            ->assertExitCode(0);
    }

    /** @test */
    public function import_command_fails_with_missing_file(): void
    {
        $this->artisan('aar:import', ['file' => '/tmp/nonexistent.json'])
            ->assertExitCode(1);
    }

    /** @test */
    public function auto_command_runs_successfully(): void
    {
        $key = TranslationKey::create(['key' => 'greeting', 'group' => '*', 'is_dead' => false]);
        TranslationValue::create([
            'translation_key_id' => $key->id,
            'locale' => 'en',
            'value' => 'Hello',
        ]);
        TranslationValue::create([
            'translation_key_id' => $key->id,
            'locale' => 'ar',
            'value' => null,
        ]);

        $this->artisan('aar:auto', ['--locale' => 'ar'])
            ->assertExitCode(0);
    }
}
