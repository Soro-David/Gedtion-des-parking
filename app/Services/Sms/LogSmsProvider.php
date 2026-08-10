<?php

namespace App\Services\Sms;

use App\Services\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Log;

class LogSmsProvider implements SmsProviderInterface
{
    /**
     * Send/log the SMS message.
     */
    public function sendSms(string $to, string $message): bool
    {
        Log::info("[SMS Log Provider] Sending SMS to {$to}: {$message}");
        return true;
    }
}
