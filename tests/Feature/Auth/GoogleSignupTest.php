<?php

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

test('duplicate Google signup from students returns to the add student dialog', function () {
    $googleUser = Mockery::mock();
    $googleUser->shouldReceive('getEmail')->once()->andReturn('registered@example.com');
    $googleUser->shouldReceive('getId')->once()->andReturn('google-user-id');
    $googleUser->user = ['email_verified' => true];

    $provider = Mockery::mock();
    $provider->shouldReceive('user')->once()->andReturn($googleUser);

    Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('whereRaw')
        ->once()
        ->with('LOWER(email) = ?', ['registered@example.com'])
        ->andReturnSelf();
    $query->shouldReceive('first')->once()->andReturn((object) ['family_id' => 1]);

    DB::shouldReceive('table')->once()->with('users_directory')->andReturn($query);

    $this->withSession([
        'google_auth_intent' => 'signup',
        'google_signup_origin' => 'students',
    ])
        ->get('/auth/google/callback')
        ->assertRedirect(route('students.index'))
        ->assertSessionHas('add_student_error', 'This Google email is already registered. Use a different account to add a student.');
});
