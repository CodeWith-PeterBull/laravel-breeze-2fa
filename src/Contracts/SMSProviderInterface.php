<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Contracts;

use MetaSoftDevs\LaravelBreeze2FA\Exceptions\SMSProviderException;

/**
 * SMS Provider Interface
 *
 * This interface defines the contract that all SMS providers must implement
 * to send SMS messages for two-factor authentication. It provides a
 * standardized way to interact with different SMS services.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Contracts
 * @author MetaSoft Developers <developers@metasoft.dev>
 * @version 1.0.0
 */
interface SMSProviderInterface
{
    /**
     * Send an SMS message to the specified phone number.
     *
     * @param string $phoneNumber The phone number in E.164 format
     * @param string $message The message content to send
     * @return array Response data from the provider
     * @throws SMSProviderException When the SMS fails to send
     */
    public function send(string $phoneNumber, string $message): array;

    /**
     * Get the delivery status of a sent message.
     *
     * @param string $messageId The message ID returned from send()
     * @return array Delivery status information
     * @throws SMSProviderException When status cannot be retrieved
     */
    public function getDeliveryStatus(string $messageId): array;

    /**
     * Test the connection to the SMS provider.
     *
     * @return bool True if connection is successful
     * @throws SMSProviderException When connection fails
     */
    public function testConnection(): bool;

    /**
     * Get the provider name.
     *
     * @return string The name of the SMS provider
     */
    public function getName(): string;

    /**
     * Get the provider configuration (without sensitive data).
     *
     * @return array Configuration details safe for display
     */
    public function getConfig(): array;

    /**
     * Check if the provider is properly configured.
     *
     * @return bool True if the provider is ready to use
     */
    public function isConfigured(): bool;

    /**
     * Get the features supported by this provider.
     *
     * @return array Array of supported features
     */
    public function getSupportedFeatures(): array;
}
