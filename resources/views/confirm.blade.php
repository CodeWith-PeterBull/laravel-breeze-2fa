@extends(config('two-factor.views.layout', 'layouts.app'))

@section('title', 'Confirm Two-Factor Authentication')

@section('content')
    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-lg">
            <div class="mx-auto h-12 w-auto flex justify-center">
                <svg class="h-12 w-12 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Confirm Two-Factor Authentication
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Please verify your setup by entering a code to complete the process
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-lg">
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

                {{-- Method-specific setup instructions --}}
                @if ($status['method'] === 'totp' && isset($setup['qr_code_url']))
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Scan QR Code</h3>

                        <div class="bg-white p-6 rounded-lg border-2 border-gray-200 text-center">
                            {{-- QR Code Display --}}
                            <div class="mb-4 flex justify-center">
                                @if (isset($setup['qr_code_svg']))
                                    <div class="p-4 bg-white rounded border">
                                        {!! $setup['qr_code_svg'] !!}
                                    </div>
                                @else
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($setup['qr_code_url']) }}"
                                        alt="QR Code" class="border rounded">
                                @endif
                            </div>

                            <p class="text-sm text-gray-600 mb-4">
                                Scan this QR code with your authenticator app
                            </p>

                            {{-- Manual Entry Option --}}
                            <div class="mt-4">
                                <button type="button" onclick="toggleManualEntry()"
                                    class="text-sm text-indigo-600 hover:text-indigo-500 font-medium">
                                    Can't scan? Enter code manually
                                </button>
                            </div>

                            <div id="manual-entry" class="hidden mt-4 p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-700 mb-2">
                                    Enter this code in your authenticator app:
                                </p>
                                <div class="font-mono text-lg text-gray-900 bg-white p-2 rounded border select-all">
                                    {{ $setup['manual_entry_key'] ?? $setup['secret'] }}
                                </div>
                                <p class="text-xs text-gray-500 mt-2">
                                    Account: {{ config('app.name') }}<br>
                                    Type: Time-based
                                </p>
                            </div>
                        </div>
                    </div>
                @elseif ($status['method'] === 'email')
                    <div class="mb-8">
                        <div class="rounded-md bg-blue-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">
                                        Check Your Email
                                    </h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <p>We've sent a verification code to your email address. Please check your inbox and
                                            enter the code below.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif ($status['method'] === 'sms')
                    <div class="mb-8">
                        <div class="rounded-md bg-blue-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">
                                        Check Your Phone
                                    </h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <p>We've sent a verification code to your phone number. Please enter the code below.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Confirmation Form --}}
                <form method="POST" action="{{ route('two-factor.confirm') }}" id="confirmation-form">
                    @csrf

                    <div class="mb-6">
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                            Verification Code
                        </label>
                        <input id="code" name="code" type="text" autocomplete="one-time-code" required
                            maxlength="8"
                            class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm text-center text-2xl tracking-widest font-mono"
                            placeholder="000000" autofocus>

                        @error('code')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <p class="mt-2 text-xs text-gray-500">
                            @if ($status['method'] === 'totp')
                                Enter the 6-digit code from your authenticator app
                            @else
                                Enter the code you received
                            @endif
                        </p>
                    </div>

                    <div class="space-y-3">
                        <button type="submit"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                            id="confirm-button">
                            <span class="confirm-text">Confirm & Enable</span>
                            <span class="confirm-loading hidden">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Confirming...
                            </span>
                        </button>
                    </div>
                </form>

                {{-- Recovery Codes Preview --}}
                @if (isset($setup['recovery_codes']) && count($setup['recovery_codes']) > 0)
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <div class="rounded-md bg-yellow-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">
                                        Recovery Codes Generated
                                    </h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>After confirming, you'll be able to download your recovery codes. Keep them safe!
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Resend Option --}}
                @if (in_array($status['method'], ['email', 'sms']))
                    <div class="mt-6">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300" />
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">Didn't receive the code?</span>
                            </div>
                        </div>

                        <div class="mt-6">
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
                                    Resend Code
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Back to Setup --}}
                <div class="mt-6 text-center">
                    <a href="{{ route('two-factor.setup') }}" class="text-sm text-gray-600 hover:text-gray-500">
                        ← Back to Setup
                    </a>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('confirmation-form');
                const codeInput = document.getElementById('code');
                const confirmButton = document.getElementById('confirm-button');
                const resendButton = document.getElementById('resend-button');

                // Auto-format and validate code input
                codeInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/[^0-9]/g, '');
                    e.target.value = value;

                    // Auto-submit when 6 digits are entered
                    if (value.length === 6) {
                        setTimeout(() => {
                            form.submit();
                        }, 500);
                    }
                });

                // Paste handling
                codeInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const paste = (e.clipboardData || window.clipboardData).getData('text');
                    const cleanPaste = paste.replace(/[^0-9]/g, '').substring(0, 8);
                    e.target.value = cleanPaste;

                    // Auto-submit for 6-digit codes
                    if (cleanPaste.length === 6) {
                        setTimeout(() => form.submit(), 100);
                    }
                });

                // Form submission loading state
                form.addEventListener('submit', function() {
                    confirmButton.disabled = true;
                    confirmButton.querySelector('.confirm-text').classList.add('hidden');
                    confirmButton.querySelector('.confirm-loading').classList.remove('hidden');
                });

                // Resend button handling
                if (resendButton) {
                    resendButton.addEventListener('submit', function(e) {
                        const button = e.target.querySelector('button[type="submit"]');
                        button.disabled = true;
                        button.textContent = 'Sending...';

                        setTimeout(() => {
                            button.disabled = false;
                            button.innerHTML = `
                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Resend Code
                `;
                        }, 5000);
                    });
                }

                // Focus management
                codeInput.focus();
            });

            function toggleManualEntry() {
                const manualEntry = document.getElementById('manual-entry');
                manualEntry.classList.toggle('hidden');

                if (!manualEntry.classList.contains('hidden')) {
                    // Auto-select the secret code
                    const secretInput = manualEntry.querySelector('.select-all');
                    if (secretInput) {
                        const selection = window.getSelection();
                        const range = document.createRange();
                        range.selectNodeContents(secretInput);
                        selection.removeAllRanges();
                        selection.addRange(range);
                    }
                }
            }

            // Auto-refresh timer
            let refreshTimer = setTimeout(() => {
                if (confirm('This setup session will expire soon. Would you like to continue?')) {
                    clearTimeout(refreshTimer);
                    // Reset the timer
                    refreshTimer = setTimeout(arguments.callee, 300000);
                } else {
                    window.location.href = '{{ route('two-factor.setup') }}';
                }
            }, 300000); // 5 minutes
        </script>
    @endpush
@endsection
