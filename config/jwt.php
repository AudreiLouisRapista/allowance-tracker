<?php

return [

    // Secret used to sign tokens. Generate it once and keep it private.
    'secret' => env('JWT_SECRET'),

    // How long a token stays valid, in minutes (720 = 12 hours).
    'ttl_minutes' => (int) env('JWT_TTL_MINUTES', 720),

    // Name of the httpOnly cookie that carries the token.
    'cookie_name' => 'allowance_token',

];