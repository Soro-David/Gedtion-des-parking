<?php

namespace App\Services\Contracts;

interface SmsProviderInterface
{
    /**
     * Send an SMS notification.
     *
     * @param string $to Recipient phone number.
     * @param string $message The message content.
     * @return bool True if successfully dispatched/prepared.
     */
    public function sendSms(string $to, string $message): bool;
}
