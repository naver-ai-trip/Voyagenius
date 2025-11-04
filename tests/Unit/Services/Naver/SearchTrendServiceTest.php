<?php

namespace Tests\Unit\Services\Naver;

use App\Services\Naver\SearchTrendService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * TDD Tests for NAVER Search Trend API Service
 *
 * DataLab API provides search trend data analysis for keywords
 * Use cases: Analyze travel destination trends, seasonal popularity, keyword insights
 *
 * API Documentation: https://guide.ncloud-docs.com/docs/en/searchtrend-overview
 */
class SearchTrendServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.naver.search_trend', [
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'base_url' => 'https://naveropenapi.apigw.ntruss.com',
            'enabled' => true,
        ]);
    }

    /** @test */
    public function it_can_check_if_service_is_enabled(): void
    {
        $service = new SearchTrendService();

        $this->assertTrue($service->isEnabled());
    }

    /** @test */
    public function it_returns_false_when_credentials_are_missing(): void
    {
        Config::set('services.naver.search_trend.client_id', '');
        Config::set('services.naver.search_trend.client_secret', '');

        $service = new SearchTrendService();

        $this->assertFalse($service->isEnabled());
    }

    /** @test */
    public function it_returns_false_when_service_is_disabled(): void
    {
        Config::set('services.naver.search_trend.enabled', false);

        $service = new SearchTrendService();

        $this->assertFalse($service->isEnabled());
    }

    /** @test */
    public function it_can_get_keyword_trends(): void
    {
        Http::fake([
            '*/datalab/v1/search' => Http::response([
                'startDate' => '2025-01-01',
                'endDate' => '2025-01-31',
                'timeUnit' => 'date',
                'results' => [
                    [
                        'title' => '제주도',
                        'keywords' => ['제주도'],
                        'data' => [
                            ['period' => '2025-01-01', 'ratio' => 75.5],
                            ['period' => '2025-01-02', 'ratio' => 80.2],
                            ['period' => '2025-01-03', 'ratio' => 85.7],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SearchTrendService();
        $result = $service->getKeywordTrends(
            ['제주도'],
            '2025-01-01',
            '2025-01-31'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertCount(1, $result['results']);
        $this->assertEquals('제주도', $result['results'][0]['title']);
        $this->assertCount(3, $result['results'][0]['data']);
    }

    /** @test */
    public function it_can_compare_multiple_keywords(): void
    {
        Http::fake([
            '*/datalab/v1/search' => Http::response([
                'startDate' => '2025-01-01',
                'endDate' => '2025-01-31',
                'timeUnit' => 'date',
                'results' => [
                    [
                        'title' => '제주도',
                        'keywords' => ['제주도'],
                        'data' => [
                            ['period' => '2025-01-01', 'ratio' => 75.5],
                        ],
                    ],
                    [
                        'title' => '부산',
                        'keywords' => ['부산'],
                        'data' => [
                            ['period' => '2025-01-01', 'ratio' => 60.3],
                        ],
                    ],
                    [
                        'title' => '강릉',
                        'keywords' => ['강릉'],
                        'data' => [
                            ['period' => '2025-01-01', 'ratio' => 45.8],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SearchTrendService();
        $result = $service->compareKeywords([
            ['제주도'],
            ['부산'],
            ['강릉'],
        ], '2025-01-01', '2025-01-31');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertCount(3, $result['results']);
        $this->assertEquals('제주도', $result['results'][0]['title']);
        $this->assertEquals('부산', $result['results'][1]['title']);
        $this->assertEquals('강릉', $result['results'][2]['title']);
    }

    /** @test */
    public function it_can_get_age_gender_trends(): void
    {
        Http::fake([
            '*/datalab/v1/search' => Http::response([
                'startDate' => '2025-01-01',
                'endDate' => '2025-01-31',
                'timeUnit' => 'date',
                'results' => [
                    [
                        'title' => '제주도',
                        'keywords' => ['제주도'],
                        'data' => [
                            [
                                'period' => '2025-01-01',
                                'group' => '20-24',
                                'ratio' => 65.5,
                            ],
                            [
                                'period' => '2025-01-01',
                                'group' => '25-29',
                                'ratio' => 75.3,
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SearchTrendService();
        $result = $service->getAgeGenderTrends(
            ['제주도'],
            '2025-01-01',
            '2025-01-31'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('data', $result['results'][0]);
        $this->assertArrayHasKey('group', $result['results'][0]['data'][0]);
    }

    /** @test */
    public function it_can_get_device_trends(): void
    {
        Http::fake([
            '*/datalab/v1/search' => Http::response([
                'startDate' => '2025-01-01',
                'endDate' => '2025-01-31',
                'timeUnit' => 'date',
                'results' => [
                    [
                        'title' => '제주도',
                        'keywords' => ['제주도'],
                        'data' => [
                            [
                                'period' => '2025-01-01',
                                'group' => 'mo',
                                'ratio' => 85.5,
                            ],
                            [
                                'period' => '2025-01-01',
                                'group' => 'pc',
                                'ratio' => 14.5,
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SearchTrendService();
        $result = $service->getDeviceTrends(
            ['제주도'],
            '2025-01-01',
            '2025-01-31'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertCount(2, $result['results'][0]['data']);
        $this->assertEquals('mo', $result['results'][0]['data'][0]['group']);
        $this->assertEquals('pc', $result['results'][0]['data'][1]['group']);
    }

    /** @test */
    public function it_can_analyze_travel_destination_popularity(): void
    {
        Http::fake([
            '*/datalab/v1/search' => Http::response([
                'startDate' => '2025-01-01',
                'endDate' => '2025-12-31',
                'timeUnit' => 'month',
                'results' => [
                    [
                        'title' => '제주도 여행',
                        'keywords' => ['제주도 여행'],
                        'data' => [
                            ['period' => '2025-01', 'ratio' => 60.5],
                            ['period' => '2025-02', 'ratio' => 75.3],
                            ['period' => '2025-03', 'ratio' => 85.7],
                            ['period' => '2025-07', 'ratio' => 95.2],
                            ['period' => '2025-08', 'ratio' => 98.5],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SearchTrendService();
        $result = $service->analyzeDestinationPopularity(
            '제주도 여행',
            '2025-01-01',
            '2025-12-31',
            'month'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('keyword', $result);
        $this->assertArrayHasKey('trends', $result);
        $this->assertArrayHasKey('peak_period', $result);
        $this->assertArrayHasKey('peak_ratio', $result);
        
        $this->assertEquals('제주도 여행', $result['keyword']);
        $this->assertEquals('2025-08', $result['peak_period']);
        $this->assertEquals(98.5, $result['peak_ratio']);
    }

    /** @test */
    public function it_includes_authentication_headers_in_requests(): void
    {
        Http::fake([
            '*/datalab/v1/search' => Http::response(['results' => []], 200),
        ]);

        $service = new SearchTrendService();
        $service->getKeywordTrends(['test'], '2025-01-01', '2025-01-31');

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-NCP-APIGW-API-KEY-ID', 'test_client_id')
                && $request->hasHeader('X-NCP-APIGW-API-KEY', 'test_client_secret')
                && $request->hasHeader('Content-Type', 'application/json');
        });
    }

    /** @test */
    public function it_returns_null_when_service_is_disabled(): void
    {
        Config::set('services.naver.search_trend.enabled', false);

        $service = new SearchTrendService();
        $result = $service->getKeywordTrends(['test'], '2025-01-01', '2025-01-31');

        $this->assertNull($result);
    }

    /** @test */
    public function it_throws_exception_on_api_error(): void
    {
        Http::fake([
            '*/datalab/v1/search' => Http::response([
                'errorMessage' => 'Invalid request parameters',
            ], 400),
        ]);

        $this->expectException(\App\Exceptions\NaverApiException::class);
        $this->expectExceptionMessage('Invalid request parameters');

        $service = new SearchTrendService();
        $service->getKeywordTrends(['test'], '2025-01-01', '2025-01-31');
    }

    /** @test */
    public function it_validates_date_range_is_required(): void
    {
        $service = new SearchTrendService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Start date and end date are required');

        $service->getKeywordTrends(['test'], '', '');
    }

    /** @test */
    public function it_validates_keywords_are_required(): void
    {
        $service = new SearchTrendService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one keyword is required');

        $service->getKeywordTrends([], '2025-01-01', '2025-01-31');
    }

    /** @test */
    public function it_validates_maximum_keyword_groups(): void
    {
        $service = new SearchTrendService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximum 5 keyword groups allowed');

        $service->compareKeywords([
            ['keyword1'],
            ['keyword2'],
            ['keyword3'],
            ['keyword4'],
            ['keyword5'],
            ['keyword6'], // Exceeds limit
        ], '2025-01-01', '2025-01-31');
    }

    /** @test */
    public function it_supports_different_time_units(): void
    {
        Http::fake([
            '*/datalab/v1/search' => Http::response([
                'timeUnit' => 'month',
                'results' => [
                    [
                        'title' => 'test',
                        'keywords' => ['test'],
                        'data' => [
                            ['period' => '2025-01', 'ratio' => 50.0],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SearchTrendService();
        $result = $service->getKeywordTrends(
            ['test'],
            '2025-01-01',
            '2025-12-31',
            'month'
        );

        $this->assertEquals('month', $result['timeUnit']);
    }

    /** @test */
    public function it_can_get_seasonal_travel_insights(): void
    {
        Http::fake([
            '*/datalab/v1/search' => Http::response([
                'startDate' => '2024-01-01',
                'endDate' => '2025-12-31',
                'timeUnit' => 'month',
                'results' => [
                    [
                        'title' => '해외여행',
                        'keywords' => ['해외여행'],
                        'data' => [
                            ['period' => '2024-01', 'ratio' => 45.5],
                            ['period' => '2024-07', 'ratio' => 85.3],
                            ['period' => '2024-08', 'ratio' => 92.7],
                            ['period' => '2024-12', 'ratio' => 78.2],
                            ['period' => '2025-01', 'ratio' => 50.1],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SearchTrendService();
        $result = $service->getSeasonalInsights('해외여행', 24); // Last 24 months

        $this->assertIsArray($result);
        $this->assertArrayHasKey('keyword', $result);
        $this->assertArrayHasKey('summer_peak', $result);
        $this->assertArrayHasKey('winter_peak', $result);
        $this->assertArrayHasKey('trends', $result);
    }
}
