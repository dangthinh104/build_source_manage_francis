<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller for sending alerts via Telegram.
 */
class SendAlertController extends Controller
{
    public function __construct(
        protected TelegramService $telegramService
    ) {}

    /**
     * Send an alert message to Telegram.
     * 
     * Accepts any text message and forwards it to the configured Telegram channel.
     * If the message contains comma-separated values, formats them with line breaks.
     */
    public function sendSMSTelegram(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'sms_message' => 'required|string|min:1',
            ]);

            $smsMessage = $request->input('sms_message');
            
            // Get channel ID from config - throw exception if not configured
            $channelId = config('services.telegram.channel_id');
            if (empty($channelId)) {
                throw new \RuntimeException('Telegram channel_id is not configured in services.telegram.channel_id');
            }

            // Format message: if it contains commas, split and format with line breaks
            // Otherwise, send as-is (robust handling of any message format)
            $formattedMessage = $this->formatMessage($smsMessage);

            $this->telegramService->sendMessage($channelId, $formattedMessage);

            return response()->json(['status' => 'success']);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed: ' . $e->getMessage()
            ], 422);

        } catch (\Exception $exception) {
            // ONLY log the error - do NOT try to send email (anti-pattern: could cause recursive failure)
            Log::channel('sms_telegram_log')->error('Telegram alert failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send Telegram alert'
            ], 500);
        }
    }

    /**
     * Format the message for Telegram.
     * 
     * If message contains commas, split into lines.
     * Otherwise, return as-is.
     */
    protected function formatMessage(string $message): string
    {
        // Check if message looks like comma-separated data
        if (str_contains($message, ',')) {
            $parts = explode(',', $message);
            
            // Only format if we have multiple parts
            if (count($parts) > 1) {
                $formatted = '';
                foreach ($parts as $part) {
                    $formatted .= trim($part) . "\n";
                }
                return rtrim($formatted);
            }
        }

        // Return original message if not comma-separated
        return $message;
    }
}
