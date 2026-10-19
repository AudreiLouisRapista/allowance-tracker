<?php

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class TenantMemberService
{
    public function __construct(private TenantConnection $tenantConnection) {}

    public function addMember(
        int $familyId,
        string $role,
        string $email,
        string $googleId,
        string $name,
        ?int $currentParentId = null
    ): int {
        // Only the two roles that exist in user_role are allowed.
        if (! in_array($role, ['parent', 'student'], true)) {
            throw ValidationException::withMessages([
                'role' => 'Choose either parent or student.',
            ]);
        }

        // The family id must be a real positive id.
        if ($familyId <= 0) {
            throw ValidationException::withMessages([
                'family_id' => 'The family could not be found.',
            ]);
        }

        // The email must be a real email.
        $email = Str::lower(trim($email));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => 'Enter a valid email address.',
            ]);
        }

        // The Google id is required and must fit the column.
        $googleId = trim($googleId);
        if ($googleId === '' || Str::length($googleId) > 64) {
            throw ValidationException::withMessages([
                'google_id' => 'The Google account could not be verified.',
            ]);
        }

        // The name is required and must fit the column.
        $name = trim($name);
        if ($name === '' || Str::length($name) > 150) {
            throw ValidationException::withMessages([
                'name' => 'The account name is not valid.',
            ]);
        }

        // One email can belong to only one family.
        if (DB::table('users_directory')->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This Google email is already registered.',
            ]);
        }

        // Reuse the current family database; no CREATE DATABASE here.
        $this->tenantConnection->switchToFamily($familyId);

        $connection = DB::connection(TenantConnection::CONNECTION);

        // The status and role lookup rows must exist.
        $statusId = $connection->table('user_status')->where('status', 'active')->value('id');
        $roleId = $connection->table('user_role')->where('role', $role)->value('id');

        if ($statusId === null || $roleId === null) {
            throw new RuntimeException('Tenant lookup rows are missing.');
        }

        // The email must not already exist inside this tenant.
        if ($connection->table('users')->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This email already belongs to a member of this family.',
            ]);
        }

        // The Google account must not already exist inside this tenant.
        if ($connection->table('users')->where('google_id', $googleId)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This Google account already belongs to a member of this family.',
            ]);
        }

        // A student must be linked to a real active parent from the SAME tenant.
        if (
            $role === 'student'
            && ($currentParentId === null || ! $this->isActiveParent($connection, $currentParentId))
        ) {
            throw ValidationException::withMessages([
                'parent_id' => 'The parent account was not found in this family.',
            ]);
        }

        $memberId = $connection->transaction(function () use (
            $connection,
            $statusId,
            $roleId,
            $role,
            $email,
            $googleId,
            $name,
            $currentParentId
        ) {
            $memberId = $connection->table('users')->insertGetId([
                'usr_status_id' => $statusId,
                'usr_role_id' => $roleId,
                'name' => $name,
                'email' => $email,
                'google_id' => $googleId,
                'is_creator' => 0,
            ]);

            if ($role === 'student') {
                // The new student is linked to the parent who added them.
                $connection->table('parent_student')->insert([
                    'parent_id' => $currentParentId,
                    'student_id' => $memberId,
                ]);
            }

            if ($role === 'parent') {
                // A new co-parent is linked to every active student in this family.
                $studentIds = $connection->table('users')
                    ->join('user_role', 'user_role.id', '=', 'users.usr_role_id')
                    ->join('user_status', 'user_status.id', '=', 'users.usr_status_id')
                    ->where('user_role.role', 'student')
                    ->where('user_status.status', 'active')
                    ->pluck('users.id');

                foreach ($studentIds as $studentId) {
                    $connection->table('parent_student')->insert([
                        'parent_id' => $memberId,
                        'student_id' => $studentId,
                    ]);
                }
            }

            return $memberId;
        });

        // Directory row LAST; its unique email index is the final duplicate guard.
        try {
            DB::table('users_directory')->insert([
                'family_id' => $familyId,
                'email' => $email,
            ]);
        } catch (Throwable $exception) {
            // Undo the tenant user because master/tenant cannot share a transaction.
            $connection->table('parent_student')->where('parent_id', $memberId)->delete();
            $connection->table('parent_student')->where('student_id', $memberId)->delete();
            $connection->table('users')->where('id', $memberId)->delete();

            throw $exception;
        }

        return $memberId;
    }

    // Confirms the id is an active parent in this tenant.
    private function isActiveParent(Connection $connection, int $parentId): bool
    {
        return $connection->table('users')
            ->join('user_role', 'user_role.id', '=', 'users.usr_role_id')
            ->join('user_status', 'user_status.id', '=', 'users.usr_status_id')
            ->where('users.id', $parentId)
            ->where('user_role.role', 'parent')
            ->where('user_status.status', 'active')
            ->exists();
    }
}
