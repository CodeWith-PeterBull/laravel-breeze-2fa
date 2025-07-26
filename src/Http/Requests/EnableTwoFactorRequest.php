<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

/**
 * Enable Two-Factor Authentication Request
 *
 * This form request handles validation for enabling two-factor authentication.
 * It validates the chosen method and any additional parameters required
 * for the specific 2FA method.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Http\Requests
 * @author MetaSoft Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class EnableTwoFactorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // User must be authenticated to enable 2FA
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $availableMethods = $this->getAvailableMethods();

        $rules = [
            'method' => [
                'required',
                'string',
                Rule::in($availableMethods),
            ],
        ];

        // Add method-specific validation rules
        $method = $this->input('method');

        switch ($method) {
            case 'sms':
                $rules = array_merge($rules, $this->getSmsValidationRules());
                break;

            case 'email':
                $rules = array_merge($rules, $this->getEmailValidationRules());
                break;

            case 'totp':
                $rules = array_merge($rules, $this->getTotpValidationRules());
                break;
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'method.required' => 'Please select a two-factor authentication method.',
            'method.in' => 'The selected authentication method is not available.',
            'phone_number.required' => 'Phone number is required for SMS authentication.',
            'phone_number.regex' => 'Please enter a valid phone number.',
            'email.required' => 'Email address is required for email authentication.',
            'email.email' => 'Please enter a valid email address.',
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
            'method' => 'authentication method',
            'phone_number' => 'phone number',
            'email' => 'email address',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Clean phone number if provided
        if ($this->has('phone_number')) {
            $phoneNumber = $this->input('phone_number');
            $cleanPhoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

            $this->merge([
                'phone_number' => $cleanPhoneNumber,
            ]);
        }

        // Normalize method name
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
            $this->validateMethodAvailability($validator);
            $this->validateUserRequirements($validator);
        });
    }

    /**
     * Get available two-factor authentication methods.
     *
     * @return array<string>
     */
    protected function getAvailableMethods(): array
    {
        $methods = [];

        if (Config::get('two-factor.methods.totp.enabled', true)) {
            $methods[] = 'totp';
        }

        if (Config::get('two-factor.methods.email.enabled', true)) {
            $methods[] = 'email';
        }

        if (Config::get('two-factor.methods.sms.enabled', false)) {
            $methods[] = 'sms';
        }

        return $methods;
    }

    /**
     * Get SMS-specific validation rules.
     *
     * @return array<string, mixed>
     */
    protected function getSmsValidationRules(): array
    {
        return [
            'phone_number' => [
                'required_if:method,sms',
                'string',
                'regex:/^[+]?[1-9]\d{1,14}$/', // E.164 format
                'min:10',
                'max:15',
            ],
        ];
    }

    /**
     * Get Email-specific validation rules.
     *
     * @return array<string, mixed>
     */
    protected function getEmailValidationRules(): array
    {
        return [
            'email' => [
                'nullable', // Email can be taken from user model
                'email',
                'max:255',
            ],
        ];
    }

    /**
     * Get TOTP-specific validation rules.
     *
     * @return array<string, mixed>
     */
    protected function getTotpValidationRules(): array
    {
        return [
            // TOTP doesn't require additional fields for setup
        ];
    }

    /**
     * Validate that the chosen method is actually available.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    protected function validateMethodAvailability($validator): void
    {
        $method = $this->input('method');

        if (!$method) {
            return;
        }

        // Check if the specific method is enabled in configuration
        $isEnabled = Config::get("two-factor.methods.{$method}.enabled", false);

        if (!$isEnabled) {
            $validator->errors()->add(
                'method',
                "The {$method} authentication method is currently disabled."
            );
        }

        // Additional method-specific availability checks
        if ($method === 'sms') {
            $this->validateSmsAvailability($validator);
        }
    }

    /**
     * Validate SMS-specific availability requirements.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    protected function validateSmsAvailability($validator): void
    {
        $provider = Config::get('two-factor.methods.sms.provider');

        if (!$provider) {
            $validator->errors()->add(
                'method',
                'SMS authentication is not properly configured.'
            );
            return;
        }

        // Check if the SMS provider is configured
        $providerConfig = Config::get("two-factor.sms_providers.{$provider}");

        if (!$providerConfig) {
            $validator->errors()->add(
                'method',
                'SMS provider configuration is missing.'
            );
        }
    }

    /**
     * Validate user-specific requirements for the chosen method.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    protected function validateUserRequirements($validator): void
    {
        $method = $this->input('method');
        $user = $this->user();

        if (!$user || !$method) {
            return;
        }

        switch ($method) {
            case 'email':
                $this->validateUserEmailRequirements($validator, $user);
                break;

            case 'sms':
                $this->validateUserSmsRequirements($validator, $user);
                break;
        }
    }

    /**
     * Validate user email requirements.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return void
     */
    protected function validateUserEmailRequirements($validator, $user): void
    {
        // If no email provided in request, check if user has email
        if (!$this->input('email')) {
            $userEmail = $user->email ?? null;

            if (!$userEmail) {
                $validator->errors()->add(
                    'email',
                    'You must provide an email address for email authentication.'
                );
            }
        }
    }

    /**
     * Validate user SMS requirements.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return void
     */
    protected function validateUserSmsRequirements($validator, $user): void
    {
        // Check if phone number is provided either in request or user model
        $phoneNumber = $this->input('phone_number') ?: $this->getUserPhoneNumber($user);

        if (!$phoneNumber) {
            $validator->errors()->add(
                'phone_number',
                'You must provide a phone number for SMS authentication.'
            );
        }
    }

    /**
     * Get user's phone number from various possible fields.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return string|null
     */
    protected function getUserPhoneNumber($user): ?string
    {
        $phoneFields = ['phone', 'phone_number', 'mobile', 'mobile_number'];

        foreach ($phoneFields as $field) {
            if (isset($user->{$field}) && !empty($user->{$field})) {
                return $user->{$field};
            }
        }

        return null;
    }

    /**
     * Get the validated data with additional processing.
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Add user's email if not provided and method is email
        if ($validated['method'] === 'email' && !isset($validated['email'])) {
            $validated['email'] = $this->user()->email;
        }

        // Add user's phone if not provided and method is SMS
        if ($validated['method'] === 'sms' && !isset($validated['phone_number'])) {
            $validated['phone_number'] = $this->getUserPhoneNumber($this->user());
        }

        return $validated;
    }
}
