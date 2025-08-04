<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Services\SMS;

use MetaSoftDevs\LaravelBreeze2FA\Contracts\SMSProviderInterface;
use MetaSoftDevs\LaravelBreeze2FA\Exceptions\SMSProviderException;

/**
 * MessageBird SMS Provider (Stub)
 *
 * TODO: Implement SMS sending via MessageBird API.
 * This class should handle SMS delivery, error handling, and status tracking.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Services\SMS
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class MessageBirdSMSProvider implements SMSProviderInterface
{
    // TODO: Add configuration, constructor, and all required methods from the interface

    public function __construct(array $config)
    {
        // TODO: Initialize with MessageBird credentials
    }

    public function send(string $phoneNumber, string $message): array
    {
        // TODO: Implement SMS sending via MessageBird
        throw new SMSProviderException('MessageBird SMS provider not implemented.', 'messagebird');
    }

    public function getDeliveryStatus(string $messageId): array
    {
        // TODO: Implement delivery status check
        throw new SMSProviderException('MessageBird SMS provider not implemented.', 'messagebird');
    }

    public function testConnection(): bool
    {
        // TODO: Implement connection test
        throw new SMSProviderException('MessageBird SMS provider not implemented.', 'messagebird');
    }

    public function getName(): string
    {
        return 'messagebird';
    }

    public function getConfig(): array
    {
        // TODO: Return safe config
        return [];
    }

    public function isConfigured(): bool
    {
        // TODO: Implement config check
        return false;
    }

    public function getSupportedFeatures(): array
    {
        // TODO: List supported features
        return [];
    }
}
