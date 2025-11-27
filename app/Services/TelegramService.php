<?php

namespace App\Services;


use GuzzleHttp\Client;
class TelegramService
{
    protected $client;
    protected $telegramToken;

    public function __construct()
    {
        $this->client = new Client();
        $this->telegramToken = config('services.telegram.bot_token'); // Store your bot token in config/services.php
    }

    public function sendMessage($chatId, $message)
    {
        $url = "https://api.telegram.org/bot{$this->telegramToken}/sendMessage";

        $params = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' =>'html'
        ];

        $response = $this->client->post($url, [
            'json' => $params,
        ]);

        return json_decode($response->getBody(), true);
    }
}
