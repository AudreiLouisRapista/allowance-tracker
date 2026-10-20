<?php

namespace App\Http\Controllers;

use App\Models\FamilyMember;
use App\Services\TenantConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FamilyMemberController extends Controller
{
    /**
     * Show all active family members.
     */
    public function index(): Response
    {
        $familyMembers = FamilyMember::active()
            // with() loads the related rows too. "status:id,status" means: from user_status, take only id and status.
            ->with(['status:id,status', 'role:id,role'])
            ->orderBy('name')
            // The two foreign key columns MUST be in this list, or Laravel cannot match the rows and the relation arrives empty.
            ->get(['id', 'name', 'email', 'phonenumber', 'usr_status_id', 'usr_role_id']);

        return Inertia::render('parent/family-member', compact('familyMembers'));
    }

    /**
     * Add a family member.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:tenant.users,email'],
        ]);

        // Save a new row through the model.
        FamilyMember::create([
            'usr_status_id' => $this->idOf('user_status', 'status', 'active'),
            'usr_role_id' => $this->idOf('user_role', 'role', 'student'),
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
            'is_creator' => 0,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Family member added.']);

        return to_route('family-members.index');
    }

    /**
     * Update a family member.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
    
        // Any active member can be edited, including a parent.
        $member = FamilyMember::active()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phonenumber' => ['nullable', 'digits_between:1, 10'],
        ], [
            'phonenumber.digits_between' => 'Enter 1 to 10 digits, number only.'
        ]);

        $member->update([
            'name' => $validated['name'],
            'phonenumber' => $validated['phonenumber'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Family member updated.']);

        return to_route('family-members.index');
    }

    /**
     * Archive a family member while preserving their history.
     */
    public function destroy(int $id): RedirectResponse
    {
        // Any active member can be archived, including a parent.
        $member = FamilyMember::active()->findOrFail($id);

        $member->update([
            'usr_status_id' => $this->idOf('user_status', 'status', 'removed'),
            'removed_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Family member archived.']);

        return to_route('family-members.index');
    }

    /**
     * Show archived family members.
     */
    public function showArchives(): Response
    {
        // Show removed parents and students.
        $familyMembers = FamilyMember::removed()
            ->with(['status:id,status', 'role:id,role'])
            ->orderBy('removed_at')
            ->get(['id', 'name', 'email', 'phonenumber', 'usr_status_id', 'removed_at']);

        return Inertia::render('parent/archives', compact('familyMembers'));
    }

    /**
     * Restore a family member from the archive.
     */

    public function restore(int $id): RedirectResponse
    {
        $member = FamilyMember::removed()->findOrFail($id);
        $member->update([
            'usr_status_id' => $this->idOf('user_status', 'status', 'active'),
            'removed_at' => null,
        ]);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Family member restored.']);

        return to_route('family-members.archive');
    }

    /**
     * Find the id of a lookup row by its name, for example the id of the status "active".
     */
    private function idOf(string $table, string $column, string $value): int
    {
        return DB::connection(TenantConnection::CONNECTION)->table($table)->where($column, $value)->value('id');
    }
}