<?php

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

test('Google signup records the family member page as its origin', function () {
    $provider = Mockery::mock();
    $provider->shouldReceive('redirect')->once()->andReturn(redirect('/google'));

    Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

    $this->get('/auth/google/signup?source=family-member')
        ->assertRedirect('/google')
        ->assertSessionHas('google_signup_origin', 'family-member');
});

test('duplicate Google signup from the family member page returns to that page', function () {
    $googleUser = Mockery::mock();
    $googleUser->shouldReceive('getEmail')->once()->andReturn('registered@example.com');
    $googleUser->shouldReceive('getId')->once()->andReturn('google-user-id');
    $googleUser->shouldReceive('getName')->once()->andReturn(null);
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
        'google_signup_origin' => 'family-member',
    ])
        ->get('/auth/google/callback')
        ->assertRedirect(route('family-members.index'))
        ->assertSessionHas('add_member_error', 'This Google email is already registered. Use a different account to add a family member.');
});
