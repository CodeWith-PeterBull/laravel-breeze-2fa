@extends(config('two-factor.views.layout', 'layouts.app'))

@section('title', 'Two-Factor Authentication Setup')

@section('content')
    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="mx-auto h-12 w-auto flex justify-center">
                <svg class="h-12 w-12 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Two-Factor Authentication
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                @if (TwoFactor::isRequired())
                    Set up two-factor authentication to secure your account
                @else
                    Add an extra layer of security to your account
                @endif
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">

                {{-- Status Messages --}}
                @if (session('success'))
                    <div class="rounded-md bg-green-50 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if (session('status'))
                    <div class="rounded-md bg-blue-50 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-blue-800">{{ session('status') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Current Status --}}
                @if ($status['enabled'])
                    <div class="rounded-md bg-green-50 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">
                                    Two-Factor Authentication Enabled
                                </h3>
                                <div class="mt-2 text-sm text-green-700">
                                    <p>Your account is protected with
                                        {{ $status['method_display_name'] ?? '2FA METHOD' }}.</p>
                                    @if ($status['recovery_codes_count'] > 0)
                                        <p class="mt-1">You have {{ $status['recovery_codes_count'] }} recovery codes
                                            remaining.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Management Options --}}
                    <div class="space-y-4">
                        {{-- Recovery Codes --}}
                        @if ($status['can_generate_recovery_codes'])
                            <div>
                                <a href="{{ route('two-factor.recovery-codes') }}"
                                    class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Manage Recovery Codes
                                </a>
                            </div>
                        @endif

                        {{-- Device Management --}}
                        <div>
                            <a href="{{ route('two-factor.devices') }}"
                                class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="-ml-1 mr-2 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                Manage Devices
                            </a>
                        </div>

                        {{-- Disable 2FA --}}
                        <div class="pt-4 border-t border-gray-200">
                            <form method="POST" action="{{ route('two-factor.disable') }}"
                                onsubmit="return confirm('Are you sure you want to disable two-factor authentication? This will make your account less secure.')">
                                @csrf
                                @method('DELETE')

                                <div class="mb-4">
                                    <label for="password" class="block text-sm font-medium text-gray-700">
                                        Confirm Password
                                    </label>
                                    <input id="password" name="password" type="password" required
                                        class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                                        placeholder="Enter your password">
                                    @error('password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <button type="submit"
                                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    Disable Two-Factor Authentication
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    {{-- Setup Options --}}
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Choose Your Authentication Method
                        </h3>

                        @if (TwoFactor::isRequired())
                            <div class="rounded-md bg-yellow-50 p-4 mb-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-yellow-800">
                                            Two-factor authentication is required for your account.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('two-factor.enable') }}">
                            @csrf

                            <div class="space-y-4">
                                @foreach ($availableMethods as $method => $details)
                                    <div class="relative">
                                        <input type="radio" name="method" value="{{ $method }}"
                                            id="method-{{ $method }}" class="peer sr-only"
                                            {{ $loop->first ? 'checked' : '' }}
                                            aria-describedby="method-{{ $method }}-description">

                                        <label for="method-{{ $method }}" class="cursor-pointer block">
                                            <div
                                                class="w-full p-4 border-2 border-gray-200 rounded-lg peer-checked:border-indigo-500 peer-checked:bg-indigo-50 hover:border-gray-300 hover:bg-gray-50 peer-checked:hover:bg-indigo-100 transition-all duration-200">
                                                <div class="flex items-start">
                                                    <div class="flex-shrink-0 mt-1 mr-4">
                                                        @if ($method === 'totp')
                                                            <svg class="h-6 w-6 text-gray-400 peer-checked:text-indigo-600"
                                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                                aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                            </svg>
                                                        @elseif ($method === 'email')
                                                            <svg class="h-6 w-6 text-gray-400 peer-checked:text-indigo-600"
                                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                                aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                            </svg>
                                                        @elseif ($method === 'sms')
                                                            <svg class="h-6 w-6 text-gray-400 peer-checked:text-indigo-600"
                                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                                aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                            </svg>
                                                        @endif
                                                    </div>
                                                    <div class="flex-1">
                                                        <h4 class="text-sm font-medium text-gray-900">
                                                            {{ $details['name'] }}
                                                        </h4>
                                                        <p class="text-sm text-gray-500"
                                                            id="method-{{ $method }}-description">
                                                            {{ $details['description'] }}
                                                        </p>
                                                    </div>
                                                    <div class="flex-shrink-0 ml-4">
                                                        <div
                                                            class="h-4 w-4 border-2 border-gray-300 rounded-full flex items-center justify-center peer-checked:border-indigo-500 peer-checked:bg-indigo-50">
                                                            <div
                                                                class="h-2 w-2 rounded-full bg-indigo-600 opacity-0 peer-checked:opacity-100">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                            @error('method')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            {{-- Additional fields for SMS --}}
                            <div id="sms-fields" class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg hidden">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor"
                                            aria-hidden="true">
                                            <path fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <p class="text-sm text-blue-800 font-medium mb-3">
                                            SMS authentication requires a phone number to receive verification codes.
                                        </p>
                                        <div>
                                            <label for="phone_number"
                                                class="block text-sm font-medium text-gray-700 mb-1">
                                                Phone Number <span class="text-red-500">*</span>
                                            </label>
                                            <input type="tel" name="phone_number" id="phone_number"
                                                class="block w-full px-3 py-2 border border-gray-300 placeholder-gray-400 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                placeholder="+1 (555) 123-4567" autocomplete="tel">
                                            <p class="mt-1 text-xs text-gray-500">
                                                Include country code (e.g., +1 for US, +44 for UK)
                                            </p>
                                            @error('phone_number')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <button type="submit"
                                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Set Up Two-Factor Authentication
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const methodInputs = document.querySelectorAll('input[name="method"]');
                const smsFields = document.getElementById('sms-fields');

                function toggleSmsFields() {
                    const selectedMethod = document.querySelector('input[name="method"]:checked').value;
                    if (selectedMethod === 'sms') {
                        smsFields.classList.remove('hidden');
                    } else {
                        smsFields.classList.add('hidden');
                    }
                }

                methodInputs.forEach(input => {
                    input.addEventListener('change', toggleSmsFields);
                });

                // Initial check
                toggleSmsFields();
            });
        </script>
    @endpush
@endsection
