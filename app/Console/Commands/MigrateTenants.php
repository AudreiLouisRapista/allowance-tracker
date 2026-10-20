<?php

namespace App\Console\Commands;

use App\Services\TenantConnection;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('tenants:migrate
    {--family= : Only migrate this family id}
    {--pretend : Show the migrations that would run without applying them}')]
#[Description('Run the tenant migrations on every family database')]
class MigrateTenants extends Command
{
    public function __construct(private TenantConnection $tenantConnection)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $familyId = $this->option('family') === null ? null : (int) $this->option('family');

        if ($familyId !== null && $familyId <= 0) {
            $this->error('The --family option must be a positive id.');

            return self::FAILURE;
        }

        $families = DB::table('families')
            ->orderBy('id')
            ->when($familyId !== null, fn ($query) => $query->where('id', $familyId))
            ->get(['id', 'name', 'database_name']);

        if ($families->isEmpty()) {
            if ($familyId !== null) {
                $this->error("Family {$familyId} was not found.");

                return self::FAILURE;
            }

            $this->warn('No family databases were found.');

            return self::SUCCESS;
        }

        $rows = [];
        $failed = false;

        foreach ($families as $family) {
            $status = 'failed';

            try {
                // Point the tenant connection at this family's database.
                $this->tenantConnection->switchTo($family->database_name);

                $parameters = [
                    '--database' => TenantConnection::CONNECTION,
                    '--path' => 'database/migrations/tenants',
                    '--force' => true,
                ];

                if ($this->option('pretend')) {
                    $parameters['--pretend'] = true;
                }

                $exitCode = Artisan::call('migrate', $parameters);
                $output = trim(Artisan::output());

                if ($exitCode === 0) {
                    $status = $this->option('pretend')
                        ? 'pretended'
                        : (str_contains($output, 'Nothing to migrate') ? 'up to date' : 'migrated');
                }

                if ($exitCode !== 0) {
                    $failed = true;
                }

                $this->line("<info>{$family->name}</info> ({$family->database_name})");

                if ($output !== '') {
                    $this->line('  '.str_replace("\n", "\n  ", $output));
                }

                if ($exitCode !== 0) {
                    $this->error('  The migrate command exited with code '.$exitCode.'.');
                }
            } catch (Throwable $exception) {
                $failed = true;

                $this->line("<info>{$family->name}</info> ({$family->database_name})");
                $this->error('  '.$exception->getMessage());
            }

            $rows[] = [
                $family->id,
                $family->name,
                $family->database_name,
                $status,
            ];

            $this->newLine();
        }

        $this->table(['ID', 'Family', 'Database', 'Result'], $rows);

        if ($failed) {
            $this->error('One or more family databases could not be migrated.');

            return self::FAILURE;
        }

        if ($this->option('pretend')) {
            $this->info('Pretend run complete. No migrations were applied.');

            return self::SUCCESS;
        }

        $this->info('All family databases are up to date.');

        return self::SUCCESS;
    }
}
