<?php

namespace Aar\AutoTranslator\Console\Commands;

use Illuminate\Console\Command;
use Aar\AutoTranslator\Services\DeadKeyDetector;

class TranslateCleanCommand extends Command
{
    protected $signature = 'aar:translate clean
                            {--dry-run : Show dead keys without deleting them}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Remove dead translation keys (keys not used in the code)';

    public function handle(DeadKeyDetector $detector): int
    {
        $deadKeys = $detector->getDeadKeys();

        if ($deadKeys->isEmpty()) {
            $this->info('✅ No dead translation keys found. Everything is clean!');
            return self::SUCCESS;
        }

        $this->warn("🗑️  Found {$deadKeys->count()} dead translation keys:");
        $this->newLine();

        $this->table(
            ['Group', 'Key'],
            $deadKeys->map(fn($k) => [$k->group, $k->key])->toArray()
        );

        if ($this->option('dry-run')) {
            $this->line('  <comment>Dry run – no keys were deleted.</comment>');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Delete these dead keys and their translations?')) {
            $this->line('  Aborted.');
            return self::SUCCESS;
        }

        $deleted = $detector->deleteDeadKeys();
        $this->info("✅ {$deleted} dead keys and their translations have been removed.");

        return self::SUCCESS;
    }
}
