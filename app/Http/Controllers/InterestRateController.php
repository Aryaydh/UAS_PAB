<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class InterestRateController extends Controller
{
    private $fredApiKey;
    private $fredBaseUrl = 'https://api.stlouisfed.org/fred';

    public function __construct()
    {
        $this->fredApiKey = config('services.fred.api_key');
    }

    /**
     * @OA\Get(
     *     path="/api/interest-rates",
     *     summary="Get interest rates",
     *     description="Retrieve latest interest rates including Federal Funds Rate, Treasury 10-year, Mortgage 30-year, and Prime Rate",
     *     tags={"Interest Rates"},
     *     security={{"passport": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Interest rates retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="category", type="string", example="Interest Rates"),
     *                 @OA\Property(property="timestamp", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="indicator", type="string", example="Federal Funds Rate"),
     *                         @OA\Property(property="value", type="number", example=3.88),
     *                         @OA\Property(property="unit", type="string", example="Percent"),
     *                         @OA\Property(property="date", type="string", format="date"),
     *                         @OA\Property(property="series_id", type="string", example="FEDFUNDS")
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
            'FEDFUNDS' => 'Federal Funds Rate',
            'DGS10' => 'Treasury 10year',
            'MORTGAGE30US' => 'Mortgage 30year',
            'DPRIME' => 'Prime Rate',
        ];

        $data = [];

        foreach ($indicators as $seriesId => $name) {
            $cacheKey = "interest_rate_{$seriesId}";
            
            $observation = Cache::remember($cacheKey, 3600, function () use ($seriesId) {
                return $this->getLatestObservation($seriesId);
            });

            if ($observation) {
                $data[] = [
                    'indicator' => $name,
                    'value' => (float) $observation['value'],
                    'unit' => 'Percent',
                    'date' => $observation['date'],
                    'series_id' => $seriesId,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Interest rates retrieved successfully',
            'data' => [
                'category' => 'Interest Rates',
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
