<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TenantRegistrationService
{
    // TenantConnection lets us point the "tenant" connection at the new database.
    public function __construct(private TenantConnection $tenantConnection)
    {
    }

    /**
     * Create a new family: master row, its own database, the tables, the parent user, the directory entry.
     * Returns the ids needed to create the JWT. Throws if anything fails (after cleaning up).
     */
    public function register(string $email, string $googleId, string $name): array
    {
        $familyId = null;            // Stays null until the families row exists.
        $databaseName = null;        // Stays null until we know the final database name.
        $databaseCreated = false;    // Tells the cleanup whether there is a database to drop.

        try {
            // 1. Find the id of the "active" status in the master lookup table (never hard-code ids).
            $familyStatusId = DB::table('family_status')->where('status', 'active')->value('id');

            // 2. Insert the family. database_name is unique and required, so it starts with a
            //    temporary random name. created_at and updated_at fill themselves from DB defaults.
            $familyId = DB::table('families')->insertGetId([
                'fam_status_id' => $familyStatusId,
                'name' => $this->familyName($name),
                'owner_email' => $email,
                'database_name' => 'pending_' . Str::lower(Str::random(20)),
                'time_zone' => 'Asia/Manila',
            ]);

            // 3. Now that we know the id, use the real name: family_<id> (unique because the id is).
            $databaseName = 'family_' . $familyId;

            DB::table('families')->where('id', $familyId)->update(['database_name' => $databaseName]);

            // 4. Create the empty database. The name is built from a number we generated,
            //    so it is safe to place directly in the SQL.
            DB::statement("CREATE DATABASE `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $databaseCreated = true;

            // 5. Point the "tenant" connection at the new database and build its tables.
            $this->tenantConnection->switchTo($databaseName);

            $exitCode = Artisan::call('migrate', [
                '--database' => TenantConnection::CONNECTION,    // Run on the tenant connection.
                '--path' => 'database/migrations/tenants',        // Only the tenant migration files.
                '--force' => true,                               // Do not stop to ask for confirmation.
            ]);

            if ($exitCode !== 0) {
                throw new RuntimeException('Tenant migrations failed: ' . Artisan::output());
            }

            // 6. Find the ids for "active" and "parent" inside the new database (the migrations inserted them).
            $userStatusId = DB::connection(TenantConnection::CONNECTION)
                ->table('user_status')->where('status', 'active')->value('id');

            $parentRoleId = DB::connection(TenantConnection::CONNECTION)
                ->table('user_role')->where('role', 'parent')->value('id');

            // 7. Create the first user: an active parent, marked as the family creator.
            $userId = DB::connection(TenantConnection::CONNECTION)->table('users')->insertGetId([
                'usr_status_id' => $userStatusId,
                'usr_role_id' => $parentRoleId,
                'name' => Str::limit($name, 150, ''),
                'email' => $email,
                'google_id' => $googleId,
                'is_creator' => 1,
            ]);

            // 8. Register the email in the master directory LAST. Its unique index on email also stops
            //    two simultaneous sign-ins from creating the same account twice.
            DB::table('users_directory')->insert([
                'family_id' => $familyId,
                'email' => $email,
            ]);

            return [
                'family_id' => $familyId,
                'user_id' => $userId,
                'role' => 'parent',
            ];
        } catch (Throwable $exception) {
            // Something failed. Undo what was created, then pass the error on to the caller.
            $this->cleanUp($familyId, $databaseName, $databaseCreated);

            throw $exception;
        }
    }

    /**
     * Name for the new family, for example "Maria's Family".
     */
    private function familyName(string $name): string
    {
        $firstName = Str::before(trim($name), ' ');           // Take the first word of the Google name.

        return Str::limit($firstName . "'s Family", 150, ''); // The column holds at most 150 characters.
    }

    /**
     * Undo a half-finished registration. CREATE DATABASE cannot be rolled back, so we remove it by hand.
     */
    private function cleanUp(?int $familyId, ?string $databaseName, bool $databaseCreated): void
    {
        try {
            if ($databaseCreated && $databaseName !== null) {
                DB::statement("DROP DATABASE IF EXISTS `{$databaseName}`");
            }

            if ($familyId !== null) {
                DB::table('families')->where('id', $familyId)->delete();
            }
        } catch (Throwable $cleanupException) {
            // Never hide the original error. Just record that the cleanup also failed.
            report($cleanupException);
        }
    }
}