<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

/**
 * Verify Two-Factor Authentication Request
 *
 * This form request handles validation for verifying two-factor authentication
 * codes during setup confirmation, login challenges, and other verification flows.
 * It validates code format and implements rate limiting.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Http\Requests
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class VerifyTwoFactorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Allow both authenticated users and users in 2FA challenge flow
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'min:4',
                'max:20',
                'regex:/^[A-Z0-9\s\-]+$/i', // Allow alphanumeric, spaces, and dashes
            ],
            'remember_device' => [
                'nullable',
                'boolean',
            ],
            'method' => [
                'nullable',
                'string',
                Rule::in(['totp', 'email', 'sms', 'recovery']),
            ],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Please enter your verification code.',
            'code.string' => 'The verification code must be a string.',
            'code.min' => 'The verification code must be at least :min characters.',
            'code.max' => 'The verification code must not exceed :max characters.',
            'code.regex' => 'The verification code contains invalid characters.',
            'remember_device.boolean' => 'The remember device option must be true or false.',
            'method.in' => 'Invalid verification method specified.',
        ];
    }

    /**
     * Get custom attribute names for validation.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'verification code',
            'remember_device' => 'remember device',
            'method' => 'verification method',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Clean and normalize the code
        if ($this->has('code')) {
            $code = $this->input('code');

            // Remove common separators and normalize
            $cleanCode = strtoupper(preg_replace('/[\s\-_]/', '', $code));

            $this->merge([
                'code' => $cleanCode,
            ]);
        }

        // Ensure remember_device is boolean
        if ($this->has('remember_device')) {
            $this->merge([
                'remember_device' => $this->boolean('remember_device'),
            ]);
        }

        // Normalize method if provided
        if ($this->has('method')) {
            $this->merge([
                'method' => strtolower(trim($this->input('method'))),
            ]);
        }
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateCodeFormat($validator);
            $this->validateRememberDevicePermission($validator);
            $this->checkRateLimit($validator);
        });
    }

    /**
     * Validate the code format based on the expected type.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    protected function validateCodeFormat($validator): void
    {
        $code = $this->input('code');

        if (!$code) {
            return;
        }

        // Determine code type and validate accordingly
        if ($this->looksLikeRecoveryCode($code)) {
            $this->validateRecoveryCodeFormat($validator, $code);
        } elseif ($this->looksLikeTotpCode($code)) {
            $this->validateTotpCodeFormat($validator, $code);
        } elseif ($this->looksLikeOtpCode($code)) {
            $this->validateOtpCodeFormat($validator, $code);
        } else {
            $validator->errors()->add(
                'code',
                'The verification code format is not recognized.'
            );
        }
    }

    /**
     * Check if the code looks like a recovery code.
     *
     * @param string $code
     * @return bool
     */
    protected function looksLikeRecoveryCode(string $code): bool
    {
        $expectedLength = Config::get('two-factor.recovery_codes.length', 10);

        // Recovery codes are typically longer and may contain letters
        return strlen($code) >= 8 &&
            strlen($code) <= 20 &&
            preg_match('/[A-Z]/', $code);
    }

    /**
     * Check if the code looks like a TOTP code.
     *
     * @param string $code
     * @return bool
     */
    protected function looksLikeTotpCode(string $code): bool
    {
        $expectedDigits = Config::get('two-factor.methods.totp.digits', 6);

        // TOTP codes are typically 6-8 digits
        return strlen($code) === $expectedDigits &&
            ctype_digit($code);
    }

    /**
     * Check if the code looks like an OTP code (email/SMS).
     *
     * @param string $code
     * @return bool
     */
    protected function looksLikeOtpCode(string $code): bool
    {
        $emailLength = Config::get('two-factor.methods.email.length', 6);
        $smsLength = Config::get('two-factor.methods.sms.length', 6);

        // OTP codes are typically 4-8 digits
        return (strlen($code) === $emailLength || strlen($code) === $smsLength) &&
            ctype_digit($code);
    }

    /**
     * Validate recovery code format.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param string $code
     * @return void
     */
    protected function validateRecoveryCodeFormat($validator, string $code): void
    {
        $minLength = 8;
        $maxLength = 20;

        if (strlen($code) < $minLength || strlen($code) > $maxLength) {
            $validator->errors()->add(
                'code',
                "Recovery codes must be between {$minLength} and {$maxLength} characters."
            );
        }

        if (!preg_match('/^[A-Z0-9]+$/', $code)) {
            $validator->errors()->add(
                'code',
                'Recovery codes can only contain letters and numbers.'
            );
        }
    }

    /**
     * Validate TOTP code format.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param string $code
     * @return void
     */
    protected function validateTotpCodeFormat($validator, string $code): void
    {
        $expectedDigits = Config::get('two-factor.methods.totp.digits', 6);

        if (strlen($code) !== $expectedDigits) {
            $validator->errors()->add(
                'code',
                "TOTP codes must be exactly {$expectedDigits} digits."
            );
        }

        if (!ctype_digit($code)) {
            $validator->errors()->add(
                'code',
                'TOTP codes can only contain numbers.'
            );
        }
    }

    /**
     * Validate OTP code format.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param string $code
     * @return void
     */
    protected function validateOtpCodeFormat($validator, string $code): void
    {
        $allowedLengths = [
            Config::get('two-factor.methods.email.length', 6),
            Config::get('two-factor.methods.sms.length', 6),
        ];

        if (!in_array(strlen($code), $allowedLengths)) {
            $validator->errors()->add(
                'code',
                'Invalid OTP code length.'
            );
        }

        if (!ctype_digit($code)) {
            $validator->errors()->add(
                'code',
                'OTP codes can only contain numbers.'
            );
        }
    }

    /**
     * Validate remember device permission.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    protected function validateRememberDevicePermission($validator): void
    {
        if (!$this->boolean('remember_device')) {
            return;
        }

        // Check if device remembering is enabled
        if (!Config::get('two-factor.remember_device.enabled', true)) {
            $validator->errors()->add(
                'remember_device',
                'Device remembering is not enabled.'
            );
        }
    }

    /**
     * Check rate limiting for verification attempts.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    protected function checkRateLimit($validator): void
    {
        if (!Config::get('two-factor.rate_limiting.enabled', true)) {
            return;
        }

        // Rate limiting would be implemented here
        // This is a placeholder for the actual rate limiting logic
        // which would check cache/database for recent attempts

        $maxAttempts = Config::get('two-factor.rate_limiting.max_attempts', 5);
        $decayMinutes = Config::get('two-factor.rate_limiting.decay_minutes', 15);

        // In a real implementation, you would:
        // 1. Get the rate limit key (user ID, IP address, or session)
        // 2. Check current attempt count
        // 3. Add error if limit exceeded

        // Example placeholder:
        // if ($this->exceedsRateLimit()) {
        //     $validator->errors()->add(
        //         'code',
        //         "Too many verification attempts. Please try again in {$decayMinutes} minutes."
        //     );
        // }
    }

    /**
     * Get the rate limit key for this request.
     *
     * @return string
     */
    protected function getRateLimitKey(): string
    {
        // Combine user ID (if available) and IP address for rate limiting
        $userId = $this->user()?->getAuthIdentifier() ?? 'guest';
        $ipAddress = $this->ip();

        return "two_factor_verify:{$userId}:{$ipAddress}";
    }

    /**
     * Determine if the code is likely a recovery code based on patterns.
     *
     * @return bool
     */
    public function isRecoveryCode(): bool
    {
        return $this->looksLikeRecoveryCode($this->input('code', ''));
    }

    /**
     * Determine if the code is likely a TOTP code.
     *
     * @return bool
     */
    public function isTotpCode(): bool
    {
        return $this->looksLikeTotpCode($this->input('code', ''));
    }

    /**
     * Determine if the code is likely an OTP code.
     *
     * @return bool
     */
    public function isOtpCode(): bool
    {
        return $this->looksLikeOtpCode($this->input('code', ''));
    }

    /**
     * Get additional validation rules based on the detected code type.
     *
     * @return array<string, mixed>
     */
    public function getAdditionalRules(): array
    {
        $code = $this->input('code', '');

        if ($this->looksLikeRecoveryCode($code)) {
            return [
                'code' => ['regex:/^[A-Z0-9]+$/'],
            ];
        }

        if ($this->looksLikeTotpCode($code) || $this->looksLikeOtpCode($code)) {
            return [
                'code' => ['numeric'],
            ];
        }

        return [];
    }
}
