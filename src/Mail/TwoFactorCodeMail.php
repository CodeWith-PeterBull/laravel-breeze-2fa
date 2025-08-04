<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Two-Factor Authentication Code Email
 *
 * This mailable class handles sending two-factor authentication codes
 * via email. It includes proper formatting, security considerations,
 * and customizable templates.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Mail
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class TwoFactorCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The user receiving the code.
     */
    public Authenticatable $user;

    /**
     * The two-factor authentication code.
     */
    public string $code;

    /**
     * The formatted code for display.
     */
    public string $formattedCode;

    /**
     * Code expiry time in minutes.
     */
    public int $expiryMinutes;

    /**
     * Application name.
     */
    public string $appName;

    /**
     * Create a new message instance.
     *
     * @param Authenticatable $user
     * @param string $code
     */
    public function __construct(Authenticatable $user, string $code)
    {
        $this->user = $user;
        $this->code = $code;
        $this->formattedCode = $this->formatCode($code);
        $this->expiryMinutes = (int) (Config::get('two-factor.methods.email.expiry', 300) / 60);
        $this->appName = Config::get('app.name', 'Laravel Application');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = Config::get('two-factor.methods.email.subject', 'Your verification code');

        return new Envelope(
            subject: $subject,
            tags: ['two-factor', 'authentication'],
            metadata: [
                'user_id' => $this->user->getAuthIdentifier(),
                'type' => 'two_factor_code',
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $template = Config::get('two-factor.methods.email.template', 'two-factor::emails.otp');

        return new Content(
            view: $template,
            with: [
                'user' => $this->user,
                'code' => $this->code,
                'formattedCode' => $this->formattedCode,
                'expiryMinutes' => $this->expiryMinutes,
                'appName' => $this->appName,
                'userName' => $this->getUserName(),
                'userEmail' => $this->getUserEmail(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Format the code for better readability.
     *
     * @param string $code
     * @return string
     */
    protected function formatCode(string $code): string
    {
        $length = strlen($code);

        // Format based on code length
        if ($length === 6) {
            // Format as XXX XXX
            return implode(' ', str_split($code, 3));
        } elseif ($length === 8) {
            // Format as XXXX XXXX
            return implode(' ', str_split($code, 4));
        } else {
            // Default: add space every 2 digits
            return implode(' ', str_split($code, 2));
        }
    }

    /**
     * Get the user's name for personalization.
     *
     * @return string
     */
    protected function getUserName(): string
    {
        // Try different common name fields
        $nameFields = ['name', 'full_name', 'first_name', 'username'];

        foreach ($nameFields as $field) {
            if (isset($this->user->{$field}) && !empty($this->user->{$field})) {
                return $this->user->{$field};
            }
        }

        // Fallback to email local part
        $email = $this->getUserEmail();
        if ($email) {
            return explode('@', $email)[0];
        }

        return 'User';
    }

    /**
     * Get the user's email address.
     *
     * @return string
     */
    protected function getUserEmail(): string
    {
        // Try different common email fields
        $emailFields = ['email', 'email_address', 'user_email'];

        foreach ($emailFields as $field) {
            if (isset($this->user->{$field}) && !empty($this->user->{$field})) {
                return $this->user->{$field};
            }
        }

        return '';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Set priority to high for security codes
        $this->priority(1);

        // Add headers for better email client handling
        $this->withSwiftMessage(function ($message) {
            $message->getHeaders()->addTextHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
            $message->getHeaders()->addTextHeader('X-Entity-Type', 'authentication-code');
        });

        return $this;
    }

    /**
     * Get the queue connection for this mailable.
     *
     * @return string|null
     */
    public function viaConnection()
    {
        return Config::get('two-factor.methods.email.connection');
    }

    /**
     * Get the queue name for this mailable.
     *
     * @return string|null
     */
    public function viaQueue()
    {
        return Config::get('two-factor.methods.email.queue');
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime|null
     */
    public function retryUntil()
    {
        // Don't retry sending codes after they would have expired
        return now()->addSeconds(Config::get('two-factor.methods.email.expiry', 300));
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int>
     */
    public function backoff(): array
    {
        // Quick retries for email delivery
        return [30, 60, 120];
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        // Log the failure for monitoring
        Log::error('Failed to send two-factor authentication email', [
            'user_id' => $this->user->getAuthIdentifier(),
            'error' => $exception->getMessage(),
            'code_length' => strlen($this->code),
        ]);

        // You might want to notify administrators or trigger alternative delivery methods
    }
}
