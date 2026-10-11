<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class LogoutController extends Controller
{
    /**
     * Sign out by expiring the JWT cookie, then go back to the login page.
     */
    public function __invoke(): RedirectResponse
    {
        return redirect('/login')->withCookie(cookie()->forget(config('jwt.cookie_name')));
    }
}