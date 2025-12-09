<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class CustomReportController extends Controller
{
    private $fredApiKey;
    private $fredBaseUrl = 'https://api.stlouisfed.org/fred';

    private $indicatorMapping = [
        'gdp' => 'GDP',
        'inflation' => 'CPIAUCSL',
        'unemployment' => 'UNRATE',
        'consumer_confidence' => 'UMCSENT',
        'federal_funds_rate' => 'FEDFUNDS',
        'treasury_10year' => 'DGS10',
        'mortgage_30year' => 'MORTGAGE30US',
        'prime_rate' => 'DPRIME',
        'sp500' => 'SP500',
        'dollar_index' => 'DTWEXBGS',
        'gold_price' => 'GOLDAMGBD228NLBM',
        'oil_price' => 'DCOILWTICO',
    ];

    public function __construct()
    {
        $this->fredApiKey = config('services.fred.api_key');
    }

    /**
     * @OA\Get(
     *     path="/api/custom-report/available-indicators",
     *     summary="Get available indicators",
     *     description="Retrieve list of all available indicators for custom reports",
     *     tags={"Custom Report"},
     *     security={{"passport": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Available indicators retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="indicators",
     *                     type="array",
     *                     @OA\Items(type="string")
     *                 ),
     *                 @OA\Property(
     *                     property="mapping",
     *                     type="object"
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function availableIndicators()
    {
        return response()->json([
            'success' => true,
            'message' => 'Available indicators retrieved successfully',
            'data' => [
                'indicators' => array_keys($this->indicatorMapping),
                'mapping' => $this->indicatorMapping,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/custom-report",
     *     summary="Generate custom report",
     *     description="Generate a custom report with selected indicators and date range",
     *     tags={"Custom Report"},
     *     security={{"passport": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"indicators", "start_date", "end_date"},
     *             @OA\Property(
     *                 property="indicators",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 example={"gdp", "inflation", "sp500"}
     *             ),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-01-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2025-12-31")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Custom report generated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'indicators' => 'required|array|min:1',
            'indicators.*' => 'string|in:' . implode(',', array_keys($this->indicatorMapping)),
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $indicators = $request->input('indicators');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $reportData = [];

        foreach ($indicators as $indicator) {
            $seriesId = $this->indicatorMapping[$indicator];
            $observations = $this->getObservations($seriesId, $startDate, $endDate);

            $reportData[$indicator] = [
                'series_id' => $seriesId,
                'data' => $observations,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Custom report generated successfully',
            'data' => [
                'report_period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'indicators' => $reportData,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    private function getObservations($seriesId, $startDate, $endDate)
    {
        try {
            $response = Http::get("{$this->fredBaseUrl}/series/observations", [
                'series_id' => $seriesId,
                'api_key' => $this->fredApiKey,
                'file_type' => 'json',
                'observation_start' => $startDate,
                'observation_end' => $endDate,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['observations'])) {
                    return array_map(function ($obs) {
                        return [
                            'date' => $obs['date'],
                            'value' => $obs['value'] !== '.' ? (float) $obs['value'] : null,
                        ];
                    }, $data['observations']);
                }
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
