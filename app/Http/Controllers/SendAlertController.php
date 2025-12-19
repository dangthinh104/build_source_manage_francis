<?php

namespace App\Http\Controllers;

use App\Services\SendEmailService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SendAlertController extends Controller
{
    protected $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function sendSMSTelegram(Request $request)
    {
        try {
            $smsMessage = $request->input('sms_message');
            $chanelID = config('services.telegram.channel_id', '@francisOSC');
            $alertExplodeArray = explode(',', $smsMessage);
            if (count($alertExplodeArray) < 6) {
                throw new \Exception('Your sms message must be at least 6 characters long');
            }
            $stringFinal = "";
            foreach ($alertExplodeArray as $element) {
                $stringFinal .= trim($element) . chr(10);
            }
            $this->telegramService->sendMessage($chanelID, $stringFinal);

            return response()->json(['status' => 'success']);
        } catch (\Exception $exception) {
            $alertEmails = config('services.alerts.emails', ['admin@example.com']);
            SendEmailService::sendEmail('Send mail alert error', $exception->getMessage(), $alertEmails);
            Log::channel('sms_telegram_log')->error('Send mail alert error: ' . $exception->getMessage());
            return response()->json(['status' => 'error', 'message' => $exception->getMessage()]);
        }
    }
}

