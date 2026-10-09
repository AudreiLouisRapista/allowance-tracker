<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use App\Services\TenantConnection;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJwt
{
    public function __construct(
        private JwtService $jwtService,
        private TenantConnection $tenantConnection
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Read the token from the cookie.
        $token = $request->cookie(config('jwt.cookie_name'));

        if (empty($token)) {
            return $this->unauthenticated($request);
        }

        // 2. Check the signature and expiry.
        $claims = $this->jwtService->decodeToken($token);

        if ($claims === null) {
            return $this->unauthenticated($request);
        }

        // 3. Point the tenant connection at this user's family database.
        try {
            $this->tenantConnection->switchToFamily((int) $claims['family_id']);
        } catch (RuntimeException $exception) {
            return $this->unauthenticated($request);
        }

        // 4. Load the user again, so a removed user is blocked even if their token is still valid.
        $user = DB::connection(TenantConnection::CONNECTION)
            ->table('users')
            ->join('user_status', 'user_status.id', '=', 'users.usr_status_id')
            ->join('user_role', 'user_role.id', '=', 'users.usr_role_id')
            ->where('users.id', (int) $claims['sub'])
            ->select('users.id', 'users.name', 'users.email', 'user_status.status as status', 'user_role.role as role')
            ->first();

        if ($user === null || $user->status !== 'active') {
            return $this->unauthenticated($request);
        }

        // 5. Make the user available to controllers.
        $request->attributes->set('auth_user', $user);
        $request->attributes->set('auth_family_id', (int) $claims['family_id']);

        return $next($request);
    }

    private function unauthenticated(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return redirect('/login');
    }
}