<?php

use App\Http\Controllers\SendAlertController;
use Illuminate\Support\Facades\Route;

Route::post('/receive-sms', [SendAlertController::class, 'sendSMSTelegram']);
