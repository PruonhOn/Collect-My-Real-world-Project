<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Google sign-in without Socialite.
 *
 * The flow is stateless on the API side:
 *
 *   1. GET  /auth/google/redirect   -> 302 to Google with a single-use state
 *   2. GET  /auth/google/callback   -> exchange code, then 302 to the SPA with
 *                                      a short-lived single-use login code
 *   3. POST /auth/google/exchange   -> trade that code for a Sanctum token
 *
 * The extra hop in step 3 exists because the callback is a browser redirect and
 * cannot return a token in a response body. Putting the token in the redirect
 * URL would leak it into history, referrers and proxy logs, so the URL carries
 * an opaque code that the SPA redeems once over the API instead.
 */
class GoogleAuthController extends Controller
{
    private const STATE_PREFIX = 'google-oauth:state:';

    private const CODE_PREFIX = 'google-oauth:login:';

    private const TOKEN_NAME = 'spa';

    /**
     * GET /api/v1/auth/google/redirect
     */
    public function redirect(): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return $this->fail('Google sign-in is not configured yet.');
        }

        $state = Str::random(64);

        Cache::put(self::STATE_PREFIX.$state, true, now()->addMinutes(10));

        $query = http_build_query([
            'client_id' => (string) config('services.google.client_id'),
            'redirect_uri' => (string) config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return redirect()->away(config('services.google.authorize_url').'?'.$query);
    }

    /**
     * GET /api/v1/auth/google/callback
     */
    public function callback(Request $request): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return $this->fail('Google sign-in is not configured yet.');
        }

        if ($request->query('error') === 'access_denied') {
            return $this->fail('Google sign-in was cancelled.');
        }

        if ($request->query('error') !== null) {
            return $this->fail('Google could not complete the sign-in. Please try again.');
        }

        $state = (string) $request->query('state', '');
        $code = (string) $request->query('code', '');

        // pull(), not get(): a state value that survives its first use could be
        // replayed by an attacker who observed the redirect.
        if ($state === '' || ! Cache::pull(self::STATE_PREFIX.$state)) {
            return $this->fail('This sign-in link has expired. Please try again.');
        }

        if ($code === '') {
            return $this->fail('Google did not return an authorization code.');
        }

        $profile = $this->fetchProfile($code);

        if ($profile === null) {
            return $this->fail('Google could not verify this account. Please try again.');
        }

        $user = $this->findOrCreateUser($profile);

        if ($user === null) {
            return $this->fail('This account cannot use Google sign-in. Please contact an administrator.');
        }

        $loginCode = Str::random(64);

        Cache::put(
            self::CODE_PREFIX.$loginCode,
            $user->getKey(),
            now()->addSeconds((int) config('logistics.auth.google_login_code_ttl')),
        );

        $frontend = (string) config('logistics.auth.frontend_url');

        return redirect()->away(
            $frontend.'/auth/google/callback?'.http_build_query(['code' => $loginCode]),
        );
    }

    /**
     * POST /api/v1/auth/google/exchange
     */
    public function exchange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:128'],
        ]);

        $userId = Cache::pull(self::CODE_PREFIX.(string) $validated['code']);

        if ($userId === null) {
            return ApiResponse::error(
                'This Google sign-in has expired. Please try again.',
                401,
            );
        }

        $user = User::query()->find($userId);

        if ($user === null || ! $user->is_active) {
            return ApiResponse::error(
                'This account cannot use Google sign-in. Please contact an administrator.',
                403,
            );
        }

        $token = $user->createToken(self::TOKEN_NAME, []);

        return ApiResponse::success([
            'token' => $token->plainTextToken,
            'expires_in' => (int) config('logistics.auth.token_expiration_minutes') * 60,
            'user' => UserResource::make($user)->resolve(),
        ], 'Signed in successfully.');
    }

    /**
     * @return array{email: string, sub: string, name: string}|null
     */
    private function fetchProfile(string $code): ?array
    {
        $tokenResponse = Http::asForm()
            ->acceptJson()
            ->timeout(15)
            ->post((string) config('services.google.token_url'), [
                'client_id' => (string) config('services.google.client_id'),
                'client_secret' => (string) config('services.google.client_secret'),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => (string) config('services.google.redirect'),
            ]);

        if ($tokenResponse->failed()) {
            report(new \RuntimeException(
                'Google token exchange failed with HTTP '.$tokenResponse->status().'.'
            ));

            return null;
        }

        $accessToken = $tokenResponse->json('access_token');

        if (! is_string($accessToken) || $accessToken === '') {
            return null;
        }

        $profileResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(15)
            ->get((string) config('services.google.userinfo_url'));

        if ($profileResponse->failed()) {
            report(new \RuntimeException(
                'Google userinfo request failed with HTTP '.$profileResponse->status().'.'
            ));

            return null;
        }

        $email = strtolower(trim((string) $profileResponse->json('email')));
        $sub = trim((string) $profileResponse->json('sub'));
        $name = trim((string) $profileResponse->json('name'));

        // An unverified Google address proves nothing about who owns the mailbox,
        // and attaching it to a local account would let someone claim an address
        // they do not control.
        if ($profileResponse->json('email_verified') !== true) {
            return null;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || $sub === '') {
            return null;
        }

        return [
            'email' => $email,
            'sub' => $sub,
            'name' => $name !== '' ? $name : Str::before($email, '@'),
        ];
    }

    /**
     * @param  array{email: string, sub: string, name: string}  $profile
     */
    private function findOrCreateUser(array $profile): ?User
    {
        $user = User::query()->where('google_id', $profile['sub'])->first();

        if ($user !== null) {
            if (! $user->is_active) {
                return null;
            }

            $user->forceFill(['last_login_at' => now()])->save();

            return $user;
        }

        $user = User::query()->where('email', $profile['email'])->first();

        if ($user !== null) {
            // Linking Google to an account somebody registered but never proved
            // they own is an account-takeover route: the squatter's password
            // would remain valid, and this callback would silently mark the
            // address verified. Make them verify the password account first.
            if (! $user->hasVerifiedEmail() || ! $user->is_active) {
                return null;
            }

            $user->forceFill(['google_id' => $profile['sub']])->save();
        } else {
            $user = User::query()->create([
                'name' => $profile['name'],
                'email' => $profile['email'],
                'google_id' => $profile['sub'],
                'password' => Str::random(40),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $user->assignRole('viewer');
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return $user;
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    private function fail(string $message): RedirectResponse
    {
        $frontend = (string) config('logistics.auth.frontend_url');

        return redirect()->away(
            $frontend.'/auth/google/callback?'.http_build_query(['error' => $message]),
        );
    }
}
