<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TenantMemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class FamilyMemberController extends Controller
{
    public function redirectToGoogle(Request $request): SymfonyRedirectResponse
    {
        // The role must be exactly parent or student.
        $validated = $request->validate([
            'role' => ['required', 'in:parent,student'],
        ]);

        // The middleware should provide these, but guard anyway.
        $authUser = $request->attributes->get('auth_user');

        if (
            $authUser === null
            || $request->attributes->get('auth_family_id') === null
        ) {
            abort(403, 'You do not have access to this page.');
        }

        // Only the family creator may invite another parent.
        if ($validated['role'] === 'parent' && (int) ($authUser->is_creator ?? 0) !== 1) {
            abort(403, 'Only the main parent can add a parent.');
        }

        $request->session()->forget('pending_member');

        // Saved server-side; the browser cannot change the family id.
        $request->session()->put('google_auth_intent', 'add_member');
        $request->session()->put('google_member_family_id', $request->attributes->get('auth_family_id'));
        $request->session()->put('google_member_role', $validated['role']);
        $request->session()->put('google_member_parent_id', $request->attributes->get('auth_user')->id);

        return Socialite::driver('google')->redirect();
    }

    public function store(Request $request, TenantMemberService $memberService): RedirectResponse
    {
        $pending = $request->session()->get('pending_member');

        // There must be held Google details.
        if ($pending === null) {
            return to_route('family-members.index')
                ->with('add_member_error', 'Your sign-up was not found. Please try again.');
        }

        // Validate the shape of the server-held data before using it.
        try {
            $data = Validator::make($pending, [
                'email' => ['required', 'email', 'max:255'],
                'google_id' => ['required', 'string', 'max:64'],
                'name' => ['required', 'string', 'max:150'],
                'family_id' => ['required', 'integer', 'min:1'],
                'role' => ['required', 'in:parent,student'],
                'parent_id' => ['nullable', 'integer', 'min:1', 'required_if:role,student'],
                'expires_at' => ['required', 'integer'],
            ])->validate();
        } catch (ValidationException $exception) {
            $request->session()->forget('pending_member');

            return to_route('family-members.index')
                ->with('add_member_error', 'Your sign-up details are invalid. Please try again.');
        }

        // The held details must not be older than 10 minutes.
        if ($data['expires_at'] < now()->timestamp) {
            $request->session()->forget('pending_member');

            return to_route('family-members.index')
                ->with('add_member_error', 'Your Google sign-in expired. Please try again.');
        }

        // The confirming parent must belong to the same family.
        if ((int) $request->attributes->get('auth_family_id') !== (int) $data['family_id']) {
            $request->session()->forget('pending_member');

            return to_route('family-members.index')
                ->with('add_member_error', 'This sign-up does not belong to your family.');
        }

        // Only the family creator may add another parent, even if the request was forged.
        $authUser = $request->attributes->get('auth_user');

        if ($data['role'] === 'parent' && (int) ($authUser?->is_creator ?? 0) !== 1) {
            $request->session()->forget('pending_member');

            return to_route('family-members.index')
                ->with('add_member_error', 'Only the main parent can add a parent.');
        }

        try {
            $memberService->addMember(
                (int) $data['family_id'],
                $data['role'],
                $data['email'],
                $data['google_id'],
                $data['name'],
                $data['parent_id'] ?? null,
            );
        } catch (ValidationException $exception) {
            // Show the real validation message (e.g. email already registered).
            return to_route('family-members.index')
                ->withErrors($exception->errors())
                ->with('add_member_error', collect($exception->errors())->flatten()->first());
        } catch (Throwable $exception) {
            report($exception);

            return to_route('family-members.index')
                ->with('add_member_error', 'We could not add this family member. Please try again.');
        }

        $request->session()->forget('pending_member');

        $label = $data['role'] === 'parent' ? 'Parent' : 'Student';

        return to_route('family-members.index')
            ->with('add_member_success', "{$label} account added for {$data['name']}.");
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget('pending_member');

        return to_route('family-members.index');
    }
}
