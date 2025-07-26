<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use MetaSoftDevs\LaravelBreeze2FA\Facades\TwoFactor;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires Two-Factor Authentication Middleware
 *
 * This middleware enforces two-factor authentication for protected routes.
 * It checks if the user has 2FA enabled and verified, redirecting to
 * the 2FA challenge if necessary.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Http\Middleware
 * @author MetaSoft Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class RequiresTwoFactor
{
    /**
     * Session key for storing the intended URL after 2FA.
     */
    protected const INTENDED_URL_KEY = 'two_factor_intended_url';

    /**
     * Session key for storing 2FA verification status.
     */
    protected const VERIFIED_SESSION_KEY = 'two_factor_verified';

    /**
     * Session key for storing user ID during 2FA challenge.
     */
    protected const USER_ID_SESSION_KEY = 'two_factor_user_id';

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string|null $redirectRoute The route to redirect to for 2FA challenge
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?string $redirectRoute = null): Response
    {
        // Skip if 2FA is globally disabled
        if (!TwoFactor::isEnabled()) {
            return $next($request);
        }

        // Skip for guests (they should authenticate first)
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Skip if user doesn't have 2FA enabled
        if (!TwoFactor::isEnabledForUser($user)) {
            // Check if 2FA is required globally
            if (TwoFactor::isRequired()) {
                return $this->redirectToSetup($request);
            }

            return $next($request);
        }

        // Check if user is already verified in this session
        if ($this->isVerifiedInSession($request)) {
            return $next($request);
        }

        // Check if device is remembered
        if (TwoFactor::isDeviceRemembered($user)) {
            $this->markAsVerifiedInSession($request);
            return $next($request);
        }

        // Redirect to 2FA challenge
        return $this->redirectToChallenge($request, $redirectRoute);
    }

    /**
     * Check if the user is verified for 2FA in the current session.
     *
     * @param Request $request
     * @return bool
     */
    protected function isVerifiedInSession(Request $request): bool
    {
        $sessionUserId = Session::get(self::USER_ID_SESSION_KEY);
        $isVerified = Session::get(self::VERIFIED_SESSION_KEY, false);
        $currentUserId = Auth::id();

        // Ensure the session verification is for the current user
        return $isVerified && $sessionUserId == $currentUserId;
    }

    /**
     * Mark the user as verified for 2FA in the current session.
     *
     * @param Request $request
     * @return void
     */
    protected function markAsVerifiedInSession(Request $request): void
    {
        Session::put([
            self::VERIFIED_SESSION_KEY => true,
            self::USER_ID_SESSION_KEY => Auth::id(),
        ]);
    }

    /**
     * Clear 2FA verification from the session.
     *
     * @param Request $request
     * @return void
     */
    public static function clearVerificationFromSession(Request $request): void
    {
        Session::forget([
            self::VERIFIED_SESSION_KEY,
            self::USER_ID_SESSION_KEY,
            self::INTENDED_URL_KEY,
        ]);
    }

    /**
     * Mark the user as verified after successful 2FA.
     *
     * @param Request $request
     * @return void
     */
    public static function markUserAsVerified(Request $request): void
    {
        Session::put([
            self::VERIFIED_SESSION_KEY => true,
            self::USER_ID_SESSION_KEY => Auth::id(),
        ]);
    }

    /**
     * Get the intended URL after 2FA verification.
     *
     * @param Request $request
     * @param string $default
     * @return string
     */
    public static function getIntendedUrl(Request $request, string $default = '/'): string
    {
        return Session::pull(self::INTENDED_URL_KEY, $default);
    }

    /**
     * Redirect to the 2FA challenge page.
     *
     * @param Request $request
     * @param string|null $redirectRoute
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectToChallenge(Request $request, ?string $redirectRoute = null): \Illuminate\Http\RedirectResponse
    {
        // Store the intended URL
        if (!$request->expectsJson()) {
            Session::put(self::INTENDED_URL_KEY, $request->fullUrl());
        }

        // Store user ID for the challenge
        Session::put(self::USER_ID_SESSION_KEY, Auth::id());

        // Determine redirect route
        $route = $redirectRoute ?: $this->getDefaultChallengeRoute();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Two-factor authentication required.',
                'redirect_url' => route($route),
                'requires_2fa' => true,
            ], 423); // 423 Locked
        }

        return Redirect::route($route)->with([
            'status' => 'Two-factor authentication required.',
            'requires_2fa' => true,
        ]);
    }

    /**
     * Redirect to the 2FA setup page.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectToSetup(Request $request): \Illuminate\Http\RedirectResponse
    {
        // Store the intended URL
        if (!$request->expectsJson()) {
            Session::put(self::INTENDED_URL_KEY, $request->fullUrl());
        }

        $setupRoute = $this->getDefaultSetupRoute();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Two-factor authentication setup required.',
                'redirect_url' => route($setupRoute),
                'requires_2fa_setup' => true,
            ], 423); // 423 Locked
        }

        return Redirect::route($setupRoute)->with([
            'status' => 'Two-factor authentication setup is required.',
            'requires_2fa_setup' => true,
        ]);
    }

    /**
     * Get the default challenge route name.
     *
     * @return string
     */
    protected function getDefaultChallengeRoute(): string
    {
        $prefix = Config::get('two-factor.routes.name_prefix', 'two-factor.');

        return $prefix . 'challenge';
    }

    /**
     * Get the default setup route name.
     *
     * @return string
     */
    protected function getDefaultSetupRoute(): string
    {
        $prefix = Config::get('two-factor.routes.name_prefix', 'two-factor.');

        return $prefix . 'setup';
    }

    /**
     * Check if the current request should bypass 2FA.
     *
     * @param Request $request
     * @return bool
     */
    protected function shouldBypass(Request $request): bool
    {
        // Bypass 2FA for specific routes (logout, 2FA setup/challenge routes, etc.)
        $bypassRoutes = [
            'logout',
            'two-factor.setup',
            'two-factor.challenge',
            'two-factor.verify',
            'two-factor.resend',
            'two-factor.disable',
        ];

        $currentRoute = $request->route()?->getName();

        if (in_array($currentRoute, $bypassRoutes)) {
            return true;
        }

        // Check for custom bypass routes from config
        $customBypassRoutes = Config::get('two-factor.middleware.bypass_routes', []);

        return in_array($currentRoute, $customBypassRoutes);
    }

    /**
     * Get the session timeout for 2FA verification.
     *
     * @return int Minutes
     */
    protected function getSessionTimeout(): int
    {
        return Config::get('two-factor.security.session_timeout', 10);
    }

    /**
     * Check if the 2FA session has expired.
     *
     * @param Request $request
     * @return bool
     */
    protected function hasSessionExpired(Request $request): bool
    {
        $sessionKey = 'two_factor_verified_at';
        $verifiedAt = Session::get($sessionKey);

        if (!$verifiedAt) {
            return true;
        }

        $timeout = $this->getSessionTimeout() * 60; // Convert to seconds

        return (time() - $verifiedAt) > $timeout;
    }

    /**
     * Update the 2FA session timestamp.
     *
     * @param Request $request
     * @return void
     */
    protected function updateSessionTimestamp(Request $request): void
    {
        Session::put('two_factor_verified_at', time());
    }

    /**
     * Handle the case where the user's 2FA status changed during the session.
     *
     * @param Request $request
     * @return bool True if status changed and session should be cleared
     */
    protected function checkForStatusChange(Request $request): bool
    {
        $sessionUserId = Session::get(self::USER_ID_SESSION_KEY);
        $currentUserId = Auth::id();

        // If user ID changed, clear session
        if ($sessionUserId !== $currentUserId) {
            $this->clearVerificationFromSession($request);
            return true;
        }

        // Check if 2FA was disabled since verification
        if ($this->isVerifiedInSession($request) && !TwoFactor::isEnabledForUser(Auth::user())) {
            $this->clearVerificationFromSession($request);
            return true;
        }

        return false;
    }

    /**
     * Get middleware configuration.
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        return [
            'session_timeout' => $this->getSessionTimeout(),
            'challenge_route' => $this->getDefaultChallengeRoute(),
            'setup_route' => $this->getDefaultSetupRoute(),
            'bypass_routes' => Config::get('two-factor.middleware.bypass_routes', []),
        ];
    }
}
