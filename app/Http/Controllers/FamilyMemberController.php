<?php

namespace App\Http\Controllers;

use App\Models\FamilyMember;
use App\Services\TenantConnection;
use App\Services\TenantMemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FamilyMemberController extends Controller
{
    /**
     * Show all active family members.
     */
    public function index(Request $request): Response
    {
        $user = $request->attributes->get('auth_user');
        $isCreator = $user !== null && (int) $user->is_creator === 1;

        $membersQuery = FamilyMember::active()
            // with() loads the related rows too. "status:id,status" means: from user_status, take only id and status.
            ->with(['status:id,status', 'role:id,role'])
            ->orderBy('name');

        // The signed-in user is never listed in their own member table.
        $membersQuery->where('id', '!=', (int) ($user->id ?? 0));

        // A co-parent only sees the students linked to them. Parents stay visible.
        if (! $isCreator) {
            $membersQuery->where(function ($query) use ($user) {
                $query->where('usr_role_id', '!=', $this->studentRoleId())
                    ->orWhereIn('id', DB::connection(TenantConnection::CONNECTION)
                        ->table('parent_student')
                        ->where('parent_id', (int) ($user->id ?? 0))
                        ->select('student_id'));
            });
        }

        // The two foreign key columns MUST be in this list, or Laravel cannot match the rows and the relation arrives empty.
        $familyMembers = $membersQuery->get(['id', 'name', 'email', 'phonenumber', 'usr_status_id', 'usr_role_id', 'is_creator', 'created_by']);

        // Active parents are the choices inside the "link parents" dialog.
        $parents = FamilyMember::active()
            ->onlyParents()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Group the existing links by student so the dialog can pre-check them.
        $linksByStudent = [];
        $links = DB::connection(TenantConnection::CONNECTION)
            ->table('parent_student')
            ->get(['student_id', 'parent_id']);

        foreach ($links as $link) {
            $linksByStudent[(int) $link->student_id][] = (int) $link->parent_id;
        }

        $familyMembers->each(function (FamilyMember $member) use ($linksByStudent) {
            $member->setAttribute('linked_parent_ids', $linksByStudent[$member->id] ?? []);
        });

        return Inertia::render('parent/family-member', compact('familyMembers', 'parents'));
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

        // Co-parents may edit parents and only the students linked to them.
        abort_unless($this->canAccessMember($request, $member), 403, 'You cannot manage this family member.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phonenumber' => [
                'nullable',
                'digits:10',
                Rule::unique('tenant.users', 'phonenumber')->ignore($member->id),
            ],
        ], [
            'phonenumber.digits' => 'Enter exactly 10 digits, number only.',
            'phonenumber.unique' => 'This phone number is already used by another family member.',
        ]);

        $member->update([
            'name' => $validated['name'],
            'phonenumber' => $validated['phonenumber'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Family member updated.']);

        return to_route('family-members.index');
    }

    /**
     * Replace the parents linked to one student. Only the family creator may do this.
     */
    public function syncParents(Request $request, int $id, TenantMemberService $memberService): RedirectResponse
    {
        $user = $request->attributes->get('auth_user');

        if ($user === null || (int) $user->is_creator !== 1) {
            abort(403, 'Only the main parent can manage linked parents.');
        }

        $validated = $request->validate([
            'parent_ids' => ['required', 'array', 'min:1', 'max:2'],
            'parent_ids.*' => ['integer', 'distinct', 'min:1'],
        ], [
            'parent_ids.max' => 'A student can have at most 2 linked parents.',
        ]);

        try {
            $memberService->syncStudentParents(
                (int) $request->attributes->get('auth_family_id'),
                $id,
                $validated['parent_ids'],
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return to_route('family-members.index');
    }

    /**
     * Archive a family member while preserving their history.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        // Any active member can be archived, except the rules below.
        $member = FamilyMember::active()->findOrFail($id);
        $user = $request->attributes->get('auth_user');

        // Nobody can archive their own account.
        if ($user !== null && (int) $member->id === (int) $user->id) {
            abort(403, 'You cannot archive your own account.');
        }

        // Only the family creator may remove another parent.
        if ($member->role->role === 'parent' && ($user === null || (int) $user->is_creator !== 1)) {
            abort(403, 'Only the main parent can remove another parent.');
        }

        // Co-parents may archive only the students linked to them.
        abort_unless($this->canAccessMember($request, $member), 403, 'You cannot manage this family member.');

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
    public function showArchives(Request $request): Response
    {
        $user = $request->attributes->get('auth_user');
        $isCreator = $user !== null && (int) $user->is_creator === 1;

        $membersQuery = FamilyMember::removed()
            ->with(['status:id,status', 'role:id,role'])
            ->orderBy('removed_at');

        // A co-parent only sees the archived students linked to them. Archived parents stay visible.
        if (! $isCreator) {
            $membersQuery->where(function ($query) use ($user) {
                $query->where('usr_role_id', '!=', $this->studentRoleId())
                    ->orWhereIn('id', DB::connection(TenantConnection::CONNECTION)
                        ->table('parent_student')
                        ->where('parent_id', (int) ($user->id ?? 0))
                        ->select('student_id'));
            });
        }

        // Show removed parents and students.
        $familyMembers = $membersQuery->get(['id', 'name', 'email', 'phonenumber', 'usr_role_id', 'usr_status_id', 'removed_at']);

        return Inertia::render('parent/archives', compact('familyMembers'));
    }

    /**
     * Restore a family member from the archive.
     */
    public function restore(Request $request, int $id): RedirectResponse
    {
        $member = FamilyMember::removed()->findOrFail($id);
        $user = $request->attributes->get('auth_user');

        // Only the family creator may restore another parent.
        if ($member->role->role === 'parent' && ($user === null || (int) $user->is_creator !== 1)) {
            abort(403, 'Only the main parent can restore another parent.');
        }

        // Co-parents may restore only the students linked to them.
        abort_unless($this->canAccessMember($request, $member), 403, 'You cannot manage this family member.');
        $member->update([
            'usr_status_id' => $this->idOf('user_status', 'status', 'active'),
            'removed_at' => null,
        ]);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Family member restored.']);

        return to_route('family-members.archive');
    }

    /**
     * The id of the "student" role, used to separate students from other members.
     */
    private function studentRoleId(): int
    {
        return (int) DB::connection(TenantConnection::CONNECTION)->table('user_role')->where('role', 'student')->value('id');
    }

    /**
     * The creator manages everyone. A co-parent manages parents and only the students linked to them.
     */
    private function canAccessMember(Request $request, FamilyMember $member): bool
    {
        $user = $request->attributes->get('auth_user');

        if ($user === null) {
            return false;
        }

        if ((int) $user->is_creator === 1) {
            return true;
        }

        if ($member->role->role !== 'student') {
            return true;
        }

        return DB::connection(TenantConnection::CONNECTION)
            ->table('parent_student')
            ->where('parent_id', (int) $user->id)
            ->where('student_id', $member->id)
            ->exists();
    }

    /**
     * Find the id of a lookup row by its name, for example the id of the status "active".
     */
    private function idOf(string $table, string $column, string $value): int
    {
        return DB::connection(TenantConnection::CONNECTION)->table($table)->where($column, $value)->value('id');
    }
}
