<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Illuminate\Routing\Controller;
use MetaSoftDevs\LaravelBreeze2FA\Facades\TwoFactor;
use MetaSoftDevs\LaravelBreeze2FA\Http\Middleware\RequiresTwoFactor;
use MetaSoftDevs\LaravelBreeze2FA\Http\Requests\EnableTwoFactorRequest;
use MetaSoftDevs\LaravelBreeze2FA\Http\Requests\VerifyTwoFactorRequest;
use MetaSoftDevs\LaravelBreeze2FA\Exceptions\TwoFactorException;
use MetaSoftDevs\LaravelBreeze2FA\Exceptions\InvalidCodeException;
use MetaSoftDevs\LaravelBreeze2FA\Exceptions\RateLimitExceededException;

/**
 * Two-Factor Authentication Controller
 *
 * This controller handles all two-factor authentication operations including
 * setup, challenge presentation, code verification, and management functions.
 * It provides both web and API endpoints with proper error handling.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Http\Controllers
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class TwoFactorController extends Controller
{
    /**
     * Show the two-factor authentication setup page.
     *
     * @param Request $request
     * @return View|JsonResponse
     */
    public function showSetup(Request $request)
    {
        $user = Auth::user();
        $status = TwoFactor::getStatus($user);
        $availableMethods = TwoFactor::getAvailableMethods($user);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => $status,
                'available_methods' => $availableMethods,
                'is_required' => TwoFactor::isRequired(),
            ]);
        }

        return view('two-factor::setup', compact('status', 'availableMethods'));
    }

    /**
     * Enable two-factor authentication for the user.
     *
     * @param EnableTwoFactorRequest $request
     * @return RedirectResponse|JsonResponse
     */
    public function enable(EnableTwoFactorRequest $request)
    {
        $user = Auth::user();
        $method = $request->validated()['method'];

        try {
            $setup = TwoFactor::enable($user, $method);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Two-factor authentication setup initiated.',
                    'setup' => $setup,
                    'requires_confirmation' => true,
                ]);
            }

            return redirect()->route('two-factor.confirm')
                ->with('setup', $setup)
                ->with('status', 'Two-factor authentication setup initiated. Please verify with the code.');
        } catch (TwoFactorException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error' => 'setup_failed',
                ], 422);
            }

            return back()->withErrors(['method' => $e->getMessage()]);
        }
    }

    /**
     * Show the two-factor authentication confirmation page.
     *
     * @param Request $request
     * @return View|JsonResponse
     */
    public function showConfirm(Request $request)
    {
        $user = Auth::user();
        $status = TwoFactor::getStatus($user);

        // Redirect if no pending setup
        if ($status['enabled'] && $status['confirmed']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Two-factor authentication is already enabled.',
                    'status' => $status,
                ], 409);
            }

            return redirect()->route('two-factor.setup')
                ->with('status', 'Two-factor authentication is already enabled.');
        }

        $setup = Session::get('setup', []);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => $status,
                'setup' => $setup,
                'message' => 'Please confirm your two-factor authentication setup.',
            ]);
        }

        return view('two-factor::confirm', compact('status', 'setup'));
    }

    /**
     * Confirm two-factor authentication setup.
     *
     * @param VerifyTwoFactorRequest $request
     * @return RedirectResponse|JsonResponse
     */
    public function confirm(VerifyTwoFactorRequest $request)
    {
        $user = Auth::user();
        $code = $request->validated()['code'];

        try {
            $confirmed = TwoFactor::confirm($user, $code);

            if ($confirmed) {
                // Clear setup data from session
                Session::forget('setup');

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Two-factor authentication enabled successfully.',
                        'status' => TwoFactor::getStatus($user),
                    ]);
                }

                return redirect()->route('two-factor.setup')
                    ->with('success', 'Two-factor authentication enabled successfully!');
            }
        } catch (InvalidCodeException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Invalid verification code.',
                    'error' => 'invalid_code',
                ], 422);
            }

            return back()->withErrors(['code' => 'Invalid verification code.']);
        } catch (TwoFactorException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error' => 'confirmation_failed',
                ], 422);
            }

            return back()->withErrors(['code' => $e->getMessage()]);
        }
    }

    /**
     * Show the two-factor authentication challenge page.
     *
     * @param Request $request
     * @return View|JsonResponse
     */
    public function showChallenge(Request $request)
    {
        // Get user from session (they might not be fully authenticated yet)
        $userId = Session::get('two_factor_user_id') ?: Auth::id();

        if (!$userId) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No pending two-factor authentication.',
                    'error' => 'no_pending_auth',
                ], 400);
            }

            return redirect()->route('login');
        }

        $userModel = config('auth.providers.users.model');
        $user = $userModel::find($userId);

        if (!$user || !TwoFactor::isEnabledForUser($user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Two-factor authentication not required.',
                    'error' => 'not_required',
                ], 400);
            }

            return redirect()->route('login');
        }

        $status = TwoFactor::getStatus($user);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Two-factor authentication required.',
                'user_id' => $userId,
                'method' => $status['method'],
                'can_use_recovery' => $status['recovery_codes_count'] > 0,
            ]);
        }

        return view('two-factor::challenge', compact('status', 'user'));
    }

    /**
     * Verify two-factor authentication code during login.
     *
     * @param VerifyTwoFactorRequest $request
     * @return RedirectResponse|JsonResponse
     */
    public function verify(VerifyTwoFactorRequest $request)
    {
        $userId = Session::get('two_factor_user_id');
        $code = $request->validated()['code'];
        $rememberDevice = $request->boolean('remember_device', false);

        if (!$userId) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No pending two-factor authentication.',
                    'error' => 'no_pending_auth',
                ], 400);
            }

            return redirect()->route('login');
        }

        $userModel = config('auth.providers.users.model');
        $user = $userModel::find($userId);

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'User not found.',
                    'error' => 'user_not_found',
                ], 404);
            }

            return redirect()->route('login');
        }

        try {
            $verified = TwoFactor::verify($user, $code, $rememberDevice);

            if ($verified) {
                // Complete the login process
                Auth::login($user);

                // Mark as verified in session
                RequiresTwoFactor::markUserAsVerified($request);

                // Clear the pending authentication
                Session::forget('two_factor_user_id');

                // Get the intended URL
                $intendedUrl = RequiresTwoFactor::getIntendedUrl($request, '/dashboard');

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Authentication successful.',
                        'redirect_url' => $intendedUrl,
                        'user' => $user,
                    ]);
                }

                return redirect()->to($intendedUrl)
                    ->with('success', 'Login successful!');
            }
        } catch (InvalidCodeException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Invalid verification code.',
                    'error' => 'invalid_code',
                ], 422);
            }

            return back()->withErrors(['code' => 'Invalid verification code.']);
        } catch (RateLimitExceededException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Too many verification attempts. Please try again later.',
                    'error' => 'rate_limited',
                ], 429);
            }

            return back()->withErrors(['code' => 'Too many attempts. Please try again later.']);
        } catch (TwoFactorException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error' => 'verification_failed',
                ], 422);
            }

            return back()->withErrors(['code' => $e->getMessage()]);
        }
    }

    /**
     * Resend the two-factor authentication code.
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function resend(Request $request)
    {
        $userId = Session::get('two_factor_user_id') ?: Auth::id();

        if (!$userId) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No user session found.',
                    'error' => 'no_session',
                ], 400);
            }

            return back()->withErrors(['resend' => 'No user session found.']);
        }

        $userModel = config('auth.providers.users.model');
        $user = $userModel::find($userId);

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'User not found.',
                    'error' => 'user_not_found',
                ], 404);
            }

            return back()->withErrors(['resend' => 'User not found.']);
        }

        try {
            $sent = TwoFactor::sendCode($user);

            if ($sent) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Verification code sent successfully.',
                    ]);
                }

                return back()->with('status', 'A new verification code has been sent.');
            }
        } catch (TwoFactorException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error' => 'resend_failed',
                ], 422);
            }

            return back()->withErrors(['resend' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Failed to send verification code.',
                'error' => 'send_failed',
            ], 500);
        }

        return back()->withErrors(['resend' => 'Failed to send verification code.']);
    }

    /**
     * Disable two-factor authentication.
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function disable(Request $request)
    {
        $user = Auth::user();

        // Require password confirmation for disabling 2FA
        $request->validate([
            'password' => 'required|current_password',
        ]);

        try {
            $disabled = TwoFactor::disable($user);

            if ($disabled) {
                // Clear any 2FA session data
                RequiresTwoFactor::clearVerificationFromSession($request);

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Two-factor authentication disabled successfully.',
                        'status' => TwoFactor::getStatus($user),
                    ]);
                }

                return redirect()->route('two-factor.setup')
                    ->with('success', 'Two-factor authentication has been disabled.');
            }
        } catch (TwoFactorException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error' => 'disable_failed',
                ], 422);
            }

            return back()->withErrors(['disable' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Failed to disable two-factor authentication.',
                'error' => 'disable_failed',
            ], 500);
        }

        return back()->withErrors(['disable' => 'Failed to disable two-factor authentication.']);
    }

    /**
     * Get the current two-factor authentication status.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        $user = Auth::user();
        $status = TwoFactor::getStatus($user);
        $availableMethods = TwoFactor::getAvailableMethods($user);

        return response()->json([
            'status' => $status,
            'available_methods' => $availableMethods,
            'is_enabled_globally' => TwoFactor::isEnabled(),
            'is_required' => TwoFactor::isRequired(),
            'device_remembered' => TwoFactor::isDeviceRemembered($user),
        ]);
    }

    /**
     * Show recovery codes for the user.
     *
     * @param Request $request
     * @return View|JsonResponse
     */
    public function showRecoveryCodes(Request $request)
    {
        $user = Auth::user();

        if (!TwoFactor::isEnabledForUser($user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Two-factor authentication is not enabled.',
                    'error' => 'not_enabled',
                ], 400);
            }

            return redirect()->route('two-factor.setup');
        }

        $status = TwoFactor::getStatus($user);

        if ($request->expectsJson()) {
            return response()->json([
                'recovery_codes_count' => $status['recovery_codes_count'],
                'message' => 'Recovery codes information retrieved.',
            ]);
        }

        return view('two-factor::recovery-codes', compact('status'));
    }

    /**
     * Generate new recovery codes.
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function generateRecoveryCodes(Request $request)
    {
        $user = Auth::user();

        // Require password confirmation for generating new recovery codes
        $request->validate([
            'password' => 'required|current_password',
        ]);

        if (!TwoFactor::isEnabledForUser($user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Two-factor authentication is not enabled.',
                    'error' => 'not_enabled',
                ], 400);
            }

            return redirect()->route('two-factor.setup');
        }

        try {
            $recoveryCodes = TwoFactor::getRecoveryCodeService()->regenerate($user);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'New recovery codes generated successfully.',
                    'recovery_codes' => $recoveryCodes,
                    'count' => count($recoveryCodes),
                ]);
            }

            return back()
                ->with('recovery_codes', $recoveryCodes)
                ->with('success', 'New recovery codes generated successfully!');
        } catch (TwoFactorException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error' => 'generation_failed',
                ], 422);
            }

            return back()->withErrors(['generate' => $e->getMessage()]);
        }
    }
}
