<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Info(
 *     title="Economic Data API",
 *     version="1.0.0",
 *     description="API for retrieving economic indicators from FRED",
 *     @OA\Contact(
 *         email="admin@example.com"
 *     )
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="passport",
 *     type="oauth2",
 *     flows={
 *         @OA\Flow(
 *             flow="clientCredentials",
 *             tokenUrl="/oauth/token",
 *             scopes={}
 *         )
 *     }
 * )
 */
class EconomicIndicatorController extends Controller
{
    private $fredApiKey;
    private $fredBaseUrl = 'https://api.stlouisfed.org/fred';

    public function __construct()
    {
        $this->fredApiKey = config('services.fred.api_key');
    }

    /**
     * @OA\Get(
     *     path="/api/economic-indicators",
     *     summary="Get economic indicators",
     *     description="Retrieve latest economic indicators including GDP, Inflation, Unemployment, and Consumer Confidence",
     *     tags={"Economic Indicators"},
     *     security={{"passport": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Economic indicators retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="category", type="string", example="Economic Indicators"),
     *                 @OA\Property(property="timestamp", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="indicator", type="string", example="Gdp"),
     *                         @OA\Property(property="value", type="number", example=30485.729),
     *                         @OA\Property(property="unit", type="string", example="Billions of Dollars"),
     *                         @OA\Property(property="date", type="string", format="date"),
     *                         @OA\Property(property="series_id", type="string", example="GDP")
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
            'GDP' => 'Gdp',
            'CPIAUCSL' => 'Inflation',
            'UNRATE' => 'Unemployment',
            'UMCSENT' => 'Consumer Confidence',
        ];

        $data = [];

        foreach ($indicators as $seriesId => $name) {
            $cacheKey = "economic_indicator_{$seriesId}";
            
            $observation = Cache::remember($cacheKey, 3600, function () use ($seriesId) {
                return $this->getLatestObservation($seriesId);
            });

            if ($observation) {
                $data[] = [
                    'indicator' => $name,
                    'value' => (float) $observation['value'],
                    'unit' => $this->getUnit($seriesId),
                    'date' => $observation['date'],
                    'series_id' => $seriesId,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Economic indicators retrieved successfully',
            'data' => [
                'category' => 'Economic Indicators',
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

    private function getUnit($seriesId)
    {
        $units = [
            'GDP' => 'Billions of Dollars',
            'CPIAUCSL' => 'Index 1982-1984=100',
            'UNRATE' => 'Percent',
            'UMCSENT' => 'Index 1966:Q1=100',
        ];

        return $units[$seriesId] ?? 'N/A';
    }
}
