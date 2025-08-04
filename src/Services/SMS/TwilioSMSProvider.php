<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Services\SMS;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use MetaSoftDevs\LaravelBreeze2FA\Contracts\SMSProviderInterface;
use MetaSoftDevs\LaravelBreeze2FA\Exceptions\SMSProviderException;

/**
 * Twilio SMS Provider
 *
 * This provider handles SMS sending through the Twilio API.
 * It provides reliable SMS delivery with detailed error handling
 * and delivery status tracking.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Services\SMS
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class TwilioSMSProvider implements SMSProviderInterface
{
    /**
     * Twilio API base URL.
     */
    protected const API_BASE_URL = 'https://api.twilio.com/2010-04-01';

    /**
     * Provider configuration.
     */
    protected array $config;

    /**
     * Create a new Twilio SMS provider instance.
     *
     * @param array $config
     * @throws SMSProviderException
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->validateConfiguration();
    }

    /**
     * Send an SMS message.
     *
     * @param string $phoneNumber
     * @param string $message
     * @return array Response data
     * @throws SMSProviderException
     */
    public function send(string $phoneNumber, string $message): array
    {
        $this->validatePhoneNumber($phoneNumber);
        $this->validateMessage($message);

        $url = $this->getApiUrl('/Messages.json');

        $data = [
            'To' => $phoneNumber,
            'From' => $this->config['from'],
            'Body' => $message,
        ];

        try {
            $response = Http::asForm()
                ->withBasicAuth($this->config['account_sid'], $this->config['auth_token'])
                ->post($url, $data);

            if ($response->successful()) {
                $responseData = $response->json();

                Log::info('Twilio SMS sent successfully', [
                    'sid' => $responseData['sid'] ?? null,
                    'to' => $this->maskPhoneNumber($phoneNumber),
                    'status' => $responseData['status'] ?? 'unknown',
                ]);

                return $this->formatResponse($responseData);
            } else {
                $error = $response->json();
                $errorMessage = $error['message'] ?? 'Unknown Twilio error';
                $errorCode = $error['code'] ?? null;

                Log::error('Twilio SMS failed', [
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'to' => $this->maskPhoneNumber($phoneNumber),
                    'status' => $response->status(),
                ]);

                throw new SMSProviderException(
                    "Twilio SMS failed: {$errorMessage} (Code: {$errorCode})",
                    'twilio'
                );
            }
        } catch (\Exception $e) {
            if ($e instanceof SMSProviderException) {
                throw $e;
            }

            Log::error('Twilio SMS request failed', [
                'error' => $e->getMessage(),
                'to' => $this->maskPhoneNumber($phoneNumber),
            ]);

            throw new SMSProviderException(
                'Failed to send SMS via Twilio: ' . $e->getMessage(),
                'twilio'
            );
        }
    }

    /**
     * Get the delivery status of a message.
     *
     * @param string $messageId
     * @return array
     * @throws SMSProviderException
     */
    public function getDeliveryStatus(string $messageId): array
    {
        $url = $this->getApiUrl("/Messages/{$messageId}.json");

        try {
            $response = Http::withBasicAuth($this->config['account_sid'], $this->config['auth_token'])
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'id' => $data['sid'],
                    'status' => $data['status'],
                    'direction' => $data['direction'],
                    'from' => $data['from'],
                    'to' => $data['to'],
                    'body' => $data['body'],
                    'price' => $data['price'],
                    'price_unit' => $data['price_unit'],
                    'error_code' => $data['error_code'],
                    'error_message' => $data['error_message'],
                    'date_created' => $data['date_created'],
                    'date_updated' => $data['date_updated'],
                    'date_sent' => $data['date_sent'],
                ];
            } else {
                throw new SMSProviderException(
                    'Failed to get message status from Twilio',
                    'twilio'
                );
            }
        } catch (\Exception $e) {
            if ($e instanceof SMSProviderException) {
                throw $e;
            }

            throw new SMSProviderException(
                'Failed to get delivery status: ' . $e->getMessage(),
                'twilio'
            );
        }
    }

    /**
     * Validate the provider configuration.
     *
     * @throws SMSProviderException
     */
    protected function validateConfiguration(): void
    {
        $required = ['account_sid', 'auth_token', 'from'];

        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                throw new SMSProviderException(
                    "Twilio configuration missing required field: {$key}",
                    'twilio'
                );
            }
        }

        // Validate Account SID format
        if (!preg_match('/^AC[a-f0-9]{32}$/', $this->config['account_sid'])) {
            throw new SMSProviderException(
                'Invalid Twilio Account SID format',
                'twilio'
            );
        }

        // Validate Auth Token format  
        if (strlen($this->config['auth_token']) !== 32) {
            throw new SMSProviderException(
                'Invalid Twilio Auth Token format',
                'twilio'
            );
        }
    }

    /**
     * Validate phone number format.
     *
     * @param string $phoneNumber
     * @throws SMSProviderException
     */
    protected function validatePhoneNumber(string $phoneNumber): void
    {
        // Twilio requires E.164 format
        if (!preg_match('/^\+[1-9]\d{1,14}$/', $phoneNumber)) {
            throw new SMSProviderException(
                'Phone number must be in E.164 format (e.g., +1234567890)',
                'twilio'
            );
        }
    }

    /**
     * Validate message content.
     *
     * @param string $message
     * @throws SMSProviderException
     */
    protected function validateMessage(string $message): void
    {
        if (empty(trim($message))) {
            throw new SMSProviderException(
                'Message cannot be empty',
                'twilio'
            );
        }

        // Twilio SMS limit is 1600 characters
        if (strlen($message) > 1600) {
            throw new SMSProviderException(
                'Message exceeds maximum length of 1600 characters',
                'twilio'
            );
        }
    }

    /**
     * Get the full API URL.
     *
     * @param string $endpoint
     * @return string
     */
    protected function getApiUrl(string $endpoint): string
    {
        return self::API_BASE_URL . '/Accounts/' . $this->config['account_sid'] . $endpoint;
    }

    /**
     * Format the API response.
     *
     * @param array $responseData
     * @return array
     */
    protected function formatResponse(array $responseData): array
    {
        return [
            'id' => $responseData['sid'],
            'status' => $responseData['status'],
            'provider' => 'twilio',
            'to' => $responseData['to'],
            'from' => $responseData['from'],
            'body' => $responseData['body'],
            'price' => $responseData['price'],
            'price_unit' => $responseData['price_unit'],
            'direction' => $responseData['direction'],
            'api_version' => $responseData['api_version'],
            'uri' => $responseData['uri'],
            'date_created' => $responseData['date_created'],
            'date_updated' => $responseData['date_updated'],
            'date_sent' => $responseData['date_sent'],
            'error_code' => $responseData['error_code'],
            'error_message' => $responseData['error_message'],
        ];
    }

    /**
     * Mask phone number for logging.
     *
     * @param string $phoneNumber
     * @return string
     */
    protected function maskPhoneNumber(string $phoneNumber): string
    {
        if (strlen($phoneNumber) <= 4) {
            return str_repeat('*', strlen($phoneNumber));
        }

        return substr($phoneNumber, 0, 3) . str_repeat('*', strlen($phoneNumber) - 6) . substr($phoneNumber, -3);
    }

    /**
     * Test the connection to Twilio.
     *
     * @return bool
     * @throws SMSProviderException
     */
    public function testConnection(): bool
    {
        $url = $this->getApiUrl('.json');

        try {
            $response = Http::withBasicAuth($this->config['account_sid'], $this->config['auth_token'])
                ->get($url);

            if ($response->successful()) {
                return true;
            } else {
                $error = $response->json();
                throw new SMSProviderException(
                    'Twilio connection test failed: ' . ($error['message'] ?? 'Unknown error'),
                    'twilio'
                );
            }
        } catch (\Exception $e) {
            if ($e instanceof SMSProviderException) {
                throw $e;
            }

            throw new SMSProviderException(
                'Twilio connection test failed: ' . $e->getMessage(),
                'twilio'
            );
        }
    }

    /**
     * Get account information.
     *
     * @return array
     * @throws SMSProviderException
     */
    public function getAccountInfo(): array
    {
        $url = $this->getApiUrl('.json');

        try {
            $response = Http::withBasicAuth($this->config['account_sid'], $this->config['auth_token'])
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'account_sid' => $data['sid'],
                    'friendly_name' => $data['friendly_name'],
                    'status' => $data['status'],
                    'type' => $data['type'],
                    'date_created' => $data['date_created'],
                    'date_updated' => $data['date_updated'],
                ];
            } else {
                throw new SMSProviderException(
                    'Failed to get Twilio account information',
                    'twilio'
                );
            }
        } catch (\Exception $e) {
            if ($e instanceof SMSProviderException) {
                throw $e;
            }

            throw new SMSProviderException(
                'Failed to get account info: ' . $e->getMessage(),
                'twilio'
            );
        }
    }

    /**
     * Get provider name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'twilio';
    }

    /**
     * Get provider configuration (without sensitive data).
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'provider' => 'twilio',
            'from' => $this->config['from'],
            'account_sid' => substr($this->config['account_sid'], 0, 8) . '...',
        ];
    }

    /**
     * Check if the provider is properly configured.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        try {
            $this->validateConfiguration();
            return true;
        } catch (SMSProviderException $e) {
            return false;
        }
    }

    /**
     * Get supported features.
     *
     * @return array
     */
    public function getSupportedFeatures(): array
    {
        return [
            'send_sms' => true,
            'delivery_status' => true,
            'message_history' => true,
            'unicode_support' => true,
            'long_messages' => true,
            'delivery_receipts' => true,
        ];
    }
}
