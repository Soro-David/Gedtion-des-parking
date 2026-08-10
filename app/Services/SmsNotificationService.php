<?php

namespace App\Services;

use App\Models\Signalement;
use App\Services\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Log;

class SmsNotificationService
{
    protected SmsProviderInterface $smsProvider;

    public function __construct(SmsProviderInterface $smsProvider)
    {
        $this->smsProvider = $smsProvider;
    }

    /**
     * Prepares and dispatches SMS for a signalement of type 'proprietaire'.
     *
     * @param Signalement $signalement
     * @return bool
     */
    public function dispatchOwnerSms(Signalement $signalement): bool
    {
        if ($signalement->type !== 'proprietaire') {
            Log::warning("[SmsNotificationService] Attempted to dispatch SMS for non-owner signalement", [
                'signalement_id' => $signalement->id,
                'type' => $signalement->type
            ]);
            return false;
        }

        if (empty($signalement->phone)) {
            Log::warning("[SmsNotificationService] Attempted to dispatch SMS but no phone number provided", [
                'signalement_id' => $signalement->id
            ]);
            $signalement->update(['sms_status' => 'failed']);
            return false;
        }

        // Build the SMS message
        $message = "Plateau Parking: Votre vehicule immatricule {$signalement->license_plate} fait l'objet d'un signalement: {$signalement->motif}. Merci d'agir en consequence.";

        Log::info("[SmsNotificationService] Preparing SMS for dispatch", [
            'signalement_id' => $signalement->id,
            'phone' => $signalement->phone,
            'message' => $message,
        ]);

        // Call provider to send (in this phase it logs, in future it'll send through Twilio/etc.)
        $sent = $this->smsProvider->sendSms($signalement->phone, $message);

        if ($sent) {
            $signalement->update(['sms_status' => 'sent']);
            Log::info("[SmsNotificationService] SMS successfully dispatched/logged", [
                'signalement_id' => $signalement->id
            ]);
            return true;
        } else {
            $signalement->update(['sms_status' => 'failed']);
            Log::error("[SmsNotificationService] SMS dispatch failed via provider", [
                'signalement_id' => $signalement->id
            ]);
            return false;
        }
    }
}
