@extends(config('two-factor.views.layout', 'layouts.app'))

@section('title', 'Two-Factor Authentication')

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
                Please confirm access to your account by entering the authentication code
                @if ($status['method'] === 'totp')
                    from your authenticator application.
                @elseif ($status['method'] === 'email')
                    sent to your email address.
                @elseif ($status['method'] === 'sms')
                    sent to your phone.
                @endif
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">

                {{-- Status Messages --}}
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

                {{-- Error Messages --}}
                @if ($errors->any())
                    <div class="rounded-md bg-red-50 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                @foreach ($errors->all() as $error)
                                    <p class="text-sm font-medium text-red-800">{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Authentication Method Info --}}
                <div class="mb-6">
                    @if ($status['method'] === 'totp')
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <svg class="h-6 w-6 text-gray-400 mr-3" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Authenticator App</p>
                                <p class="text-xs text-gray-500">Enter the 6-digit code from your authenticator app</p>
                            </div>
                        </div>
                    @elseif ($status['method'] === 'email')
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <svg class="h-6 w-6 text-gray-400 mr-3" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Email Verification</p>
                                <p class="text-xs text-gray-500">Check your email for the verification code</p>
                            </div>
                        </div>
                    @elseif ($status['method'] === 'sms')
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <svg class="h-6 w-6 text-gray-400 mr-3" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">SMS Verification</p>
                                <p class="text-xs text-gray-500">Check your phone for the verification code</p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Verification Form --}}
                <form method="POST" action="{{ route('two-factor.verify') }}" id="verification-form">
                    @csrf

                    <div class="mb-6">
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                            Verification Code
                        </label>
                        <input id="code" name="code" type="text" autocomplete="one-time-code" required
                            maxlength="20"
                            class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm text-center text-2xl tracking-widest font-mono"
                            placeholder="000000" autofocus>
                        <p class="mt-1 text-xs text-gray-500">
                            Enter the code exactly as it appears
                        </p>
                    </div>

                    {{-- Remember Device Option --}}
                    @if (config('two-factor.remember_device.enabled', true))
                        <div class="flex items-center mb-6">
                            <input id="remember_device" name="remember_device" type="checkbox"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="remember_device" class="ml-2 block text-sm text-gray-900">
                                Remember this device for
                                {{ config('two-factor.remember_device.duration', 30 * 24 * 60) / (24 * 60) }} days
                            </label>
                        </div>
                    @endif

                    <div class="space-y-3">
                        <button type="submit"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            id="verify-button">
                            <span class="verify-text">Verify</span>
                            <span class="verify-loading hidden">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Verifying...
                            </span>
                        </button>
                    </div>
                </form>

                {{-- Alternative Options --}}
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300" />
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">Having trouble?</span>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        {{-- Resend Code Option --}}
                        @if (in_array($status['method'], ['email', 'sms']))
                            <form method="POST" action="{{ route('two-factor.resend') }}" class="inline-block w-full">
                                @csrf
                                <button type="submit"
                                    class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    id="resend-button">
                                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span class="resend-text">Resend Code</span>
                                    <span class="resend-loading hidden">Sending...</span>
                                </button>
                            </form>
                        @endif

                        {{-- Recovery Code Option --}}
                        @if ($status['recovery_codes_count'] > 0)
                            <div class="text-center">
                                <button type="button" onclick="showRecoveryCodeForm()"
                                    class="text-sm text-indigo-600 hover:text-indigo-500 font-medium">
                                    Use a recovery code instead
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Recovery Code Form (Hidden by default) --}}
                @if ($status['recovery_codes_count'] > 0)
                    <div id="recovery-code-form" class="hidden mt-6 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Use Recovery Code</h3>
                        <form method="POST" action="{{ route('two-factor.verify') }}">
                            @csrf

                            <div class="mb-4">
                                <label for="recovery_code" class="block text-sm font-medium text-gray-700 mb-2">
                                    Recovery Code
                                </label>
                                <input id="recovery_code" name="code" type="text"
                                    class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm font-mono"
                                    placeholder="XXXX-XXXX-XX">
                                <p class="mt-1 text-xs text-gray-500">
                                    Enter one of your recovery codes (each can only be used once)
                                </p>
                            </div>

                            <div class="flex space-x-3">
                                <button type="submit"
                                    class="flex-1 flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Use Recovery Code
                                </button>

                                <button type="button" onclick="hideRecoveryCodeForm()"
                                    class="flex-1 flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                {{-- Back to Login --}}
                <div class="mt-6 text-center">
                    <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-500">
                        ← Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('verification-form');
                const codeInput = document.getElementById('code');
                const verifyButton = document.getElementById('verify-button');
                const resendButton = document.getElementById('resend-button');

                // Auto-format code input
                codeInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/[^0-9A-Z]/g, '');

                    // Auto-submit when code length is reached
                    if (value.length === 6 && /^\d{6}$/.test(value)) {
                        form.submit();
                    }

                    e.target.value = value;
                });

                // Paste handling
                codeInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const paste = (e.clipboardData || window.clipboardData).getData('text');
                    const cleanPaste = paste.replace(/[^0-9A-Z]/g, '').substring(0, 20);
                    e.target.value = cleanPaste;

                    // Auto-submit for 6-digit codes
                    if (cleanPaste.length === 6 && /^\d{6}$/.test(cleanPaste)) {
                        setTimeout(() => form.submit(), 100);
                    }
                });

                // Form submission loading state
                form.addEventListener('submit', function() {
                    verifyButton.disabled = true;
                    verifyButton.querySelector('.verify-text').classList.add('hidden');
                    verifyButton.querySelector('.verify-loading').classList.remove('hidden');
                });

                // Resend button loading state
                if (resendButton) {
                    resendButton.addEventListener('click', function() {
                        resendButton.disabled = true;
                        resendButton.querySelector('.resend-text').textContent = 'Sending...';

                        setTimeout(() => {
                            resendButton.disabled = false;
                            resendButton.querySelector('.resend-text').textContent = 'Resend Code';
                        }, 5000);
                    });
                }

                // Focus management
                codeInput.focus();
                codeInput.select();
            });

            function showRecoveryCodeForm() {
                document.getElementById('recovery-code-form').classList.remove('hidden');
                document.getElementById('recovery_code').focus();

                // Scroll to form
                document.getElementById('recovery-code-form').scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }

            function hideRecoveryCodeForm() {
                document.getElementById('recovery-code-form').classList.add('hidden');
                document.getElementById('code').focus();
            }

            // Auto-refresh page after 5 minutes for security
            setTimeout(() => {
                if (confirm('This session has been inactive for 5 minutes. Would you like to refresh the page?')) {
                    window.location.reload();
                }
            }, 300000);
        </script>
    @endpush
@endsection
