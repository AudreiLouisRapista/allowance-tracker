<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => fn () => $request->attributes->get('auth_user'),
            ],
            'flash' => [
                'welcome' => fn () => $request->session()->get('welcome'),
                'google_error' => fn () => $request->session()->get('google_error'),
                'add_member_error' => fn () => $request->session()->get('add_member_error'),
                'add_member_success' => fn () => $request->session()->get('add_member_success'),
                'payout_error' => fn () => $request->session()->get('payout_error'),
                'payout_success' => fn () => $request->session()->get('payout_success'),
            ],
            // The email waiting for the "create account" confirmation, or null when there is none.
            // Read from the session so the dialog is still there after a page refresh.
            'pendingRegistration' => function () use ($request) {
                $pending = $request->session()->get('pending_registration');

                // Show the dialog only while the held details have not expired.
                if ($pending !== null && $pending['expires_at'] > now()->timestamp) {
                    return $pending['email'];
                }

                return null;
            },
            // The Google email and role waiting for the add-member confirmation.
            'pendingMember' => function () use ($request) {
                $pending = $request->session()->get('pending_member');

                // Only share it while the 10-minute window is still valid.
                if ($pending !== null && $pending['expires_at'] > now()->timestamp) {
                    return [
                        'email' => $pending['email'],
                        'role' => $pending['role'],
                    ];
                }

                return null;
            },
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
