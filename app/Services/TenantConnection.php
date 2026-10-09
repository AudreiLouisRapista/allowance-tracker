<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class TenantConnection
{
    // Must match the connection key in config/database.php
    public const CONNECTION = 'tenant';

    /**
     * Point the tenant connection at a database by name.
     */
    public function switchTo(string $databaseName): void
    {
        config(['database.connections.' . self::CONNECTION . '.database' => $databaseName]);

        // Forget the old connection so the next query reconnects to the new database.
        DB::purge(self::CONNECTION);
    }

    /**
     * Look up a tenant (family) in the master database, then point the connection at its database.
     */
    public function switchToFamily(int $familyId): void
    {
        $family = DB::table('families')->where('id', $familyId)->first();

        if ($family === null) {
            throw new RuntimeException("Family {$familyId} was not found in the master database.");
        }

        $this->switchTo($family->database_name);
    }
}