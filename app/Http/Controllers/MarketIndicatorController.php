<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class MarketIndicatorController extends Controller
{
    private $fredApiKey;
    private $fredBaseUrl = 'https://api.stlouisfed.org/fred';

    public function __construct()
    {
        $this->fredApiKey = config('services.fred.api_key');
    }

    /**
     * @OA\Get(
     *     path="/api/market-indicators",
     *     summary="Get market indicators",
     *     description="Retrieve latest market indicators including S&P 500, Dollar Index, and Oil Price",
     *     tags={"Market Indicators"},
     *     security={{"passport": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Market indicators retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="category", type="string", example="Market Indicators"),
     *                 @OA\Property(property="timestamp", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="indicator", type="string", example="Sp500"),
     *                         @OA\Property(property="value", type="number", example=6846.51),
     *                         @OA\Property(property="unit", type="string", example="Index"),
     *                         @OA\Property(property="date", type="string", format="date"),
     *                         @OA\Property(property="series_id", type="string", example="SP500")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index()
    {
        $indicators = [
            'SP500' => ['name' => 'Sp500', 'unit' => 'Index'],
            'DTWEXBGS' => ['name' => 'Dollar Index', 'unit' => 'Index'],
            'DCOILWTICO' => ['name' => 'Oil Price', 'unit' => 'Dollars per Barrel'],
        ];

        $data = [];

        foreach ($indicators as $seriesId => $info) {
            $cacheKey = "market_indicator_{$seriesId}";
            
            $observation = Cache::remember($cacheKey, 3600, function () use ($seriesId) {
                return $this->getLatestObservation($seriesId);
            });

            if ($observation) {
                $data[] = [
                    'indicator' => $info['name'],
                    'value' => (float) $observation['value'],
                    'unit' => $info['unit'],
                    'date' => $observation['date'],
                    'series_id' => $seriesId,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Market indicators retrieved successfully',
            'data' => [
                'category' => 'Market Indicators',
                'timestamp' => now()->toIso8601String(),
                'data' => $data,
            ],
        ]);
    }

    private function getLatestObservation($seriesId)
    {
        try {
            $response = Http::get("{$this->fredBaseUrl}/series/observations", [
                'series_id' => $seriesId,
                'api_key' => $this->fredApiKey,
                'file_type' => 'json',
                'sort_order' => 'desc',
                'limit' => 1,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['observations'])) {
                    return $data['observations'][0];
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
