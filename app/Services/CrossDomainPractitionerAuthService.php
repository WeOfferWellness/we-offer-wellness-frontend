<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Laravel\Sanctum\PersonalAccessToken;

class CrossDomainPractitionerAuthService
{
    public function issue(User $user): void
    {
        if (! $user->hasRole('Practitioner')) {
            return;
        }

        $user->tokens()->where('name', 'cross-domain-practitioner')->delete();
        $token = $user->createToken(
            'cross-domain-practitioner',
            [config('auth.cross_domain_practitioner.ability')],
            now()->addDays((int) config('auth.cross_domain_practitioner.ttl_days')),
        );

        Cookie::queue(cookie(
            config('auth.cross_domain_practitioner.cookie'),
            $token->plainTextToken,
            (int) config('auth.cross_domain_practitioner.ttl_days') * 1440,
            '/',
            config('auth.cross_domain_practitioner.domain'),
            true,
            true,
            false,
            'lax',
        ));
    }

    public function authenticate(Request $request): void
    {
        if (Auth::check()) {
            return;
        }

        $plainTextToken = (string) $request->cookie(config('auth.cross_domain_practitioner.cookie'));
        if ($plainTextToken === '') {
            return;
        }

        $accessToken = PersonalAccessToken::findToken($plainTextToken);
        $user = $accessToken?->tokenable;
        $ability = (string) config('auth.cross_domain_practitioner.ability');
        if (! $accessToken || ! $user || ! $user->hasRole('Practitioner') || ! $accessToken->can($ability)) {
            Cookie::queue(Cookie::forget(config('auth.cross_domain_practitioner.cookie'), '/', config('auth.cross_domain_practitioner.domain')));
            return;
        }

        Auth::login($user, true);
    }

    public function revoke(Request $request): void
    {
        $plainTextToken = (string) $request->cookie(config('auth.cross_domain_practitioner.cookie'));
        (PersonalAccessToken::findToken($plainTextToken))?->delete();
        Cookie::queue(Cookie::forget(config('auth.cross_domain_practitioner.cookie'), '/', config('auth.cross_domain_practitioner.domain')));
    }
}
