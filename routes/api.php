<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EconomicIndicatorController;
use App\Http\Controllers\InterestRateController;
use App\Http\Controllers\MarketIndicatorController;
use App\Http\Controllers\CustomReportController;

Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'service' => 'Economic Data API',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String(),
        'authentication' => 'OAuth2 Client Credentials (Passport)',
    ]);
});

Route::middleware('client.token')->group(function () {
    Route::get('/economic-indicators', [EconomicIndicatorController::class, 'index']);
    Route::get('/interest-rates', [InterestRateController::class, 'index']);
    Route::get('/market-indicators', [MarketIndicatorController::class, 'index']);
    Route::post('/custom-report', [CustomReportController::class, 'generate']);
    Route::get('/custom-report/available-indicators', [CustomReportController::class, 'availableIndicators']);
});
