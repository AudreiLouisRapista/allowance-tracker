<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\TenantConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    /**
     * READ: show the list of active students.
     */
    public function index(): Response
    {
        // List every active family member, so newly added parents also appear.
        $familyMember = Student::active()
            // with() loads the related rows too. "status:id,status" means: from user_status, take only id and status.
            ->with(['status:id,status', 'role:id,role'])
            ->orderBy('name')
            // The two foreign key columns MUST be in this list, or Laravel cannot match the rows and the relation arrives empty.
            ->get(['id', 'name', 'email', 'phonenumber', 'usr_status_id', 'usr_role_id']);

        // Pick the React page 'students/index' (resources/js/pages/students/index.tsx) and hand it the data.
        return Inertia::render('parent/familyMembers', compact('familyMember'));
    }

    /**
     * CREATE: add a student.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:tenant.users,email'],
        ]);

        // Save a new row through the model.
        Student::create([
            'usr_status_id' => $this->idOf('user_status', 'status', 'active'),
            'usr_role_id' => $this->idOf('user_role', 'role', 'student'),
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
            'is_creator' => 0,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Student added.']);

        return to_route('students.index');
    }

    /**
     * UPDATE: change a student's name.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
    
        // Any active member can be edited, including a parent.
        $student = Student::active()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phonenumber' => ['nullable', 'digits_between:1, 10'],
        ], [
            'phonenumber.digits_between' => 'Enter 1 to 10 digits, number only.'
        ]);

        $student->update([
            'name' => $validated['name'],
            'phonenumber' => $validated['phonenumber'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Student updated.']);

        return to_route('students.index');
    }

    /**
     * REMOVE: mark the student as removed. The row stays, because history must be kept.
     */
    public function destroy(int $id): RedirectResponse
    {
        // Any active member can be archived, including a parent.
        $student = Student::active()->findOrFail($id);

        $student->update([
            'usr_status_id' => $this->idOf('user_status', 'status', 'removed'),
            'removed_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Student removed.']);

        return to_route('students.index');
    }

    /**
     * ARCHIVE: show all student have removed status
     */

    public function showArchives(): Response
    {
        // Show every removed member, not only removed students.
        $familyMember = Student::removed()
            ->with(['status:id,status', 'role:id,role'])
            ->orderBy('removed_at')
            ->get(['id', 'name', 'email', 'phonenumber', 'usr_status_id', 'removed_at']);

        return Inertia::render('parent/archives', compact('familyMember'));
    }

        /**
     * ARCHIVE: recover the student that have removed status
     */

    public function restore(int $id) : RedirectResponse
    {
    // Any removed member can be restored, including a parent.
    $familyMember = Student::removed()->findOrFail($id);
    $familyMember->update([
            'usr_status_id' => $this->idOf('user_status', 'status', 'active'),
            'removed_at' => null,
        ]);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Student recovered.']);

        return to_route('students.archive');
    }

    /**
     * Find the id of a lookup row by its name, for example the id of the status "active".
     */
    private function idOf(string $table, string $column, string $value): int
    {
        return DB::connection(TenantConnection::CONNECTION)->table($table)->where($column, $value)->value('id');
    }
}