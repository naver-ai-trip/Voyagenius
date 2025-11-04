<?php

namespace App\Services\Naver;

use InvalidArgumentException;

/**
 * NAVER Search Trend (DataLab) API Service
 *
 * Provides search trend data analysis for keywords:
 * - Keyword search volume trends over time
 * - Multi-keyword comparison
 * - Age/Gender demographics
 * - Device (mobile/PC) breakdown
 * - Travel destination popularity analysis
 * - Seasonal insights
 *
 * Use Cases:
 * - Analyze travel destination popularity trends
 * - Compare multiple destinations seasonally
 * - Understand traveler demographics
 * - Identify peak travel seasons
 * - Optimize content for trending keywords
 *
 * API Documentation: https://guide.ncloud-docs.com/docs/en/searchtrend-overview
 * Authentication: NCP Gateway (X-NCP-APIGW-API-KEY-ID, X-NCP-APIGW-API-KEY)
 */
class SearchTrendService extends NaverBaseService
{
    public function __construct()
    {
        parent::__construct(config('services.naver.search_trend'));
    }

    /**
     * Get keyword search trends over time
     *
     * @param array $keywords Single keyword or array of keywords (max 20 keywords per group)
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @param string $timeUnit Time unit: 'date', 'week', 'month' (default: 'date')
     * @param string $device Device filter: 'pc', 'mo', or '' for all devices
     * @param string|null $gender Gender filter: 'm', 'f', or null for all
     * @param array $ages Age groups: e.g., ['1', '2'] (1=0-12, 2=13-18, 3=19-24, 4=25-29, 5=30-34, 6=35-39, 7=40-44, 8=45-49, 9=50-54, 10=55-59, 11=60+)
     * @return array|null Trend data with periods and ratios, or null if disabled
     */
    public function getKeywordTrends(
        array $keywords,
        string $startDate,
        string $endDate,
        string $timeUnit = 'date',
        string $device = '',
        ?string $gender = null,
        array $ages = []
    ): ?array {
        if (!$this->isEnabled()) {
            return null;
        }

        $this->validateKeywords($keywords);
        $this->validateDateRange($startDate, $endDate);

        $this->logApiCall('POST', '/datalab/v1/search', [
            'keywords' => $keywords,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'timeUnit' => $timeUnit,
        ]);

        $payload = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'timeUnit' => $timeUnit,
            'keywordGroups' => [
                [
                    'groupName' => is_array($keywords[0]) ? implode(',', $keywords[0]) : implode(',', $keywords),
                    'keywords' => is_array($keywords[0]) ? $keywords[0] : $keywords,
                ],
            ],
        ];

        if (!empty($device)) {
            $payload['device'] = $device;
        }

        if ($gender !== null) {
            $payload['gender'] = $gender;
        }

        if (!empty($ages)) {
            $payload['ages'] = $ages;
        }

        $response = $this->client()->post(
            $this->baseUrl . '/datalab/v1/search',
            $payload
        );

        return $this->handleResponse($response, 'keyword-trends');
    }

    /**
     * Compare multiple keyword groups
     *
     * @param array $keywordGroups Array of keyword arrays (max 5 groups)
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @param string $timeUnit Time unit: 'date', 'week', 'month'
     * @return array|null Comparison data for all keyword groups, or null if disabled
     */
    public function compareKeywords(
        array $keywordGroups,
        string $startDate,
        string $endDate,
        string $timeUnit = 'date'
    ): ?array {
        if (!$this->isEnabled()) {
            return null;
        }

        if (empty($keywordGroups)) {
            throw new InvalidArgumentException('At least one keyword group is required');
        }

        if (count($keywordGroups) > 5) {
            throw new InvalidArgumentException('Maximum 5 keyword groups allowed');
        }

        $this->validateDateRange($startDate, $endDate);

        $formattedGroups = [];
        foreach ($keywordGroups as $index => $keywords) {
            $formattedGroups[] = [
                'groupName' => implode(',', $keywords),
                'keywords' => $keywords,
            ];
        }

        $payload = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'timeUnit' => $timeUnit,
            'keywordGroups' => $formattedGroups,
        ];

        $response = $this->client()->post(
            $this->baseUrl . '/datalab/v1/search',
            $payload
        );

        return $this->handleResponse($response, 'compare-keywords');
    }

    /**
     * Get age/gender demographic trends
     *
     * @param array $keywords Keyword(s) to analyze
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array|null Age/gender breakdown data, or null if disabled
     */
    public function getAgeGenderTrends(
        array $keywords,
        string $startDate,
        string $endDate
    ): ?array {
        if (!$this->isEnabled()) {
            return null;
        }

        $this->validateKeywords($keywords);
        $this->validateDateRange($startDate, $endDate);

        $payload = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'timeUnit' => 'date',
            'keywordGroups' => [
                [
                    'groupName' => implode(',', $keywords),
                    'keywords' => $keywords,
                ],
            ],
            'category' => [
                ['name' => 'age'],
                ['name' => 'gender'],
            ],
        ];

        $response = $this->client()->post(
            $this->baseUrl . '/datalab/v1/search',
            $payload
        );

        return $this->handleResponse($response, 'age-gender-trends');
    }

    /**
     * Get device (mobile/PC) usage trends
     *
     * @param array $keywords Keyword(s) to analyze
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array|null Device breakdown data, or null if disabled
     */
    public function getDeviceTrends(
        array $keywords,
        string $startDate,
        string $endDate
    ): ?array {
        if (!$this->isEnabled()) {
            return null;
        }

        $this->validateKeywords($keywords);
        $this->validateDateRange($startDate, $endDate);

        $payload = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'timeUnit' => 'date',
            'keywordGroups' => [
                [
                    'groupName' => implode(',', $keywords),
                    'keywords' => $keywords,
                ],
            ],
            'category' => [
                ['name' => 'device'],
            ],
        ];

        $response = $this->client()->post(
            $this->baseUrl . '/datalab/v1/search',
            $payload
        );

        return $this->handleResponse($response, 'device-trends');
    }

    /**
     * Analyze travel destination popularity with peak detection
     *
     * @param string $destination Destination keyword (e.g., '제주도 여행')
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @param string $timeUnit Time unit: 'date', 'week', 'month'
     * @return array|null Analyzed popularity data with peak period, or null if disabled
     */
    public function analyzeDestinationPopularity(
        string $destination,
        string $startDate,
        string $endDate,
        string $timeUnit = 'month'
    ): ?array {
        if (!$this->isEnabled()) {
            return null;
        }

        $trendData = $this->getKeywordTrends(
            [$destination],
            $startDate,
            $endDate,
            $timeUnit
        );

        if (empty($trendData['results'][0]['data'])) {
            return null;
        }

        $data = $trendData['results'][0]['data'];
        
        // Find peak period
        $peakPeriod = null;
        $peakRatio = 0;
        
        foreach ($data as $point) {
            if ($point['ratio'] > $peakRatio) {
                $peakRatio = $point['ratio'];
                $peakPeriod = $point['period'];
            }
        }

        return [
            'keyword' => $destination,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'timeUnit' => $timeUnit,
            'trends' => $data,
            'peak_period' => $peakPeriod,
            'peak_ratio' => $peakRatio,
            'total_data_points' => count($data),
        ];
    }

    /**
     * Get seasonal travel insights (requires historical data)
     *
     * @param string $keyword Travel keyword to analyze
     * @param int $months Number of months to analyze (default: 12)
     * @return array|null Seasonal insights with summer/winter peaks, or null if disabled
     */
    public function getSeasonalInsights(string $keyword, int $months = 12): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $endDate = now()->format('Y-m-d');
        $startDate = now()->subMonths($months)->format('Y-m-d');

        $trendData = $this->getKeywordTrends(
            [$keyword],
            $startDate,
            $endDate,
            'month'
        );

        if (empty($trendData['results'][0]['data'])) {
            return null;
        }

        $data = $trendData['results'][0]['data'];

        // Identify seasonal patterns
        $summerMonths = ['06', '07', '08'];
        $winterMonths = ['12', '01', '02'];

        $summerPeak = ['period' => null, 'ratio' => 0];
        $winterPeak = ['period' => null, 'ratio' => 0];

        foreach ($data as $point) {
            $month = substr($point['period'], -2);
            
            if (in_array($month, $summerMonths) && $point['ratio'] > $summerPeak['ratio']) {
                $summerPeak = $point;
            }
            
            if (in_array($month, $winterMonths) && $point['ratio'] > $winterPeak['ratio']) {
                $winterPeak = $point;
            }
        }

        return [
            'keyword' => $keyword,
            'analysis_period' => "{$startDate} to {$endDate}",
            'trends' => $data,
            'summer_peak' => $summerPeak,
            'winter_peak' => $winterPeak,
            'months_analyzed' => count($data),
        ];
    }

    /**
     * Validate keywords
     */
    private function validateKeywords(array $keywords): void
    {
        if (empty($keywords)) {
            throw new InvalidArgumentException('At least one keyword is required');
        }
    }

    /**
     * Validate date range
     */
    private function validateDateRange(string $startDate, string $endDate): void
    {
        if (empty($startDate) || empty($endDate)) {
            throw new InvalidArgumentException('Start date and end date are required');
        }
    }
}
