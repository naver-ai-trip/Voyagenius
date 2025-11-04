<?php

namespace Tests\Integration;

use App\Services\Naver\NaverMapsService;
use App\Services\Naver\PapagoService;
use App\Services\Naver\ClovaOcrService;
use App\Services\Naver\ClovaSpeechService;
use Tests\TestCase;

/**
 * NAVER API Integration Tests - Real API Calls
 *
 * These tests make ACTUAL calls to NAVER Cloud Platform APIs
 * to verify that:
 * 1. Our credentials are valid
 * 2. Our implementation correctly communicates with NAVER
 * 3. NAVER's services are responding as expected
 *
 * ⚠️ WARNING: These tests consume API quota and may incur costs
 * Run with: php artisan test --group=integration
 */
class NaverApiIntegrationTest extends TestCase
{
    /**
     * @test
     * @group integration
     * @group naver-maps
     */
    public function it_can_geocode_real_address_with_naver_maps(): void
    {
        $service = new NaverMapsService();

        if (!$service->isEnabled()) {
            $this->markTestSkipped('NAVER Maps service is not enabled');
        }

        // Test geocoding a real Korean address
        $result = $service->geocode('서울특별시 강남구 테헤란로 152');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('address', $result);
        $this->assertArrayHasKey('lat', $result);
        $this->assertArrayHasKey('lng', $result);
        
        // Verify coordinates are in Seoul area (rough bounds)
        $this->assertGreaterThan(37.4, $result['lat']);
        $this->assertLessThan(37.7, $result['lat']);
        $this->assertGreaterThan(126.8, $result['lng']);
        $this->assertLessThan(127.2, $result['lng']);

        dump([
            '✅ NAVER Maps Geocoding' => 'SUCCESS',
            'Input' => '서울특별시 강남구 테헤란로 152',
            'Result' => $result,
        ]);
    }

    /**
     * @test
     * @group integration
     * @group naver-maps
     */
    public function it_can_reverse_geocode_real_coordinates_with_naver_maps(): void
    {
        $service = new NaverMapsService();

        if (!$service->isEnabled()) {
            $this->markTestSkipped('NAVER Maps service is not enabled');
        }

        // Test reverse geocoding Seoul coordinates
        $result = $service->reverseGeocode(37.5665, 126.9780);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('address', $result);
        
        // The address might be in roadAddress or in the area fields
        $hasSeoul = str_contains($result['address'], '서울') || 
                    str_contains($result['roadAddress'] ?? '', '서울') ||
                    str_contains($result['area']['level1'] ?? '', '서울');
        
        $this->assertTrue($hasSeoul, 'Result should contain Seoul (서울) in address or area');

        dump([
            '✅ NAVER Maps Reverse Geocoding' => 'SUCCESS',
            'Input' => 'lat: 37.5665, lng: 126.9780 (Seoul City Hall)',
            'Result' => $result,
        ]);
    }

    /**
     * @test
     * @group integration
     * @group naver-maps
     */
    public function it_can_search_nearby_places_with_naver_maps(): void
    {
        $service = new NaverMapsService();

        if (!$service->isEnabled()) {
            $this->markTestSkipped('NAVER Maps service is not enabled');
        }

        // Search for cafes near Gangnam Station
        $results = $service->searchNearby(37.4979, 127.0276, '카페', 1000);

        $this->assertIsArray($results);
        $this->assertNotEmpty($results, 'Should find cafes near Gangnam Station');
        
        $firstResult = $results[0];
        $this->assertArrayHasKey('name', $firstResult);
        $this->assertArrayHasKey('address', $firstResult);
        $this->assertArrayHasKey('lat', $firstResult);
        $this->assertArrayHasKey('lng', $firstResult);

        dump([
            '✅ NAVER Maps POI Search' => 'SUCCESS',
            'Query' => '카페 (cafes)',
            'Location' => 'Gangnam Station',
            'Found' => count($results) . ' places',
            'Sample' => array_slice($results, 0, 3),
        ]);
    }

    /**
     * @test
     * @group integration
     * @group naver-maps
     */
    public function it_can_calculate_distance_with_naver_maps(): void
    {
        $service = new NaverMapsService();

        if (!$service->isEnabled()) {
            $this->markTestSkipped('NAVER Maps service is not enabled');
        }

        // Calculate distance from Seoul to Busan
        $result = $service->getDistance(
            37.5665, 126.9780,  // Seoul
            35.1796, 129.0756   // Busan
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('distance', $result);
        $this->assertArrayHasKey('duration', $result);
        
        // Distance should be roughly 325-400km
        $this->assertGreaterThan(300000, $result['distance']);
        $this->assertLessThan(450000, $result['distance']);

        dump([
            '✅ NAVER Maps Distance Calculation' => 'SUCCESS',
            'Route' => 'Seoul → Busan',
            'Distance' => round($result['distance'] / 1000, 1) . ' km',
            'Duration' => round($result['duration'] / 60, 1) . ' minutes',
        ]);
    }

    /**
     * @test
     * @group integration
     * @group papago
     */
    public function it_can_translate_korean_to_english_with_papago(): void
    {
        $service = new PapagoService();

        if (!$service->isEnabled()) {
            $this->markTestSkipped('Papago service is not enabled');
        }

        // Test Korean to English translation
        $result = $service->translate('안녕하세요', 'en', 'ko');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('translatedText', $result);
        $this->assertArrayHasKey('sourceLang', $result);
        $this->assertArrayHasKey('targetLang', $result);
        
        $this->assertEquals('ko', $result['sourceLang']);
        $this->assertEquals('en', $result['targetLang']);
        $this->assertStringContainsStringIgnoringCase('hello', $result['translatedText']);

        dump([
            '✅ Papago Translation (KO→EN)' => 'SUCCESS',
            'Input' => '안녕하세요',
            'Output' => $result['translatedText'],
            'Detected Language' => $result['sourceLang'],
        ]);
    }

    /**
     * @test
     * @group integration
     * @group papago
     */
    public function it_can_translate_english_to_korean_with_papago(): void
    {
        $service = new PapagoService();

        if (!$service->isEnabled()) {
            $this->markTestSkipped('Papago service is not enabled');
        }

        // Test English to Korean translation
        $result = $service->translate('Thank you', 'ko', 'en');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('translatedText', $result);
        $this->assertStringContainsString('감사', $result['translatedText']);

        dump([
            '✅ Papago Translation (EN→KO)' => 'SUCCESS',
            'Input' => 'Thank you',
            'Output' => $result['translatedText'],
        ]);
    }

    /**
     * @test
     * @group integration
     * @group papago
     */
    public function it_can_auto_detect_language_with_papago(): void
    {
        $service = new PapagoService();

        if (!$service->isEnabled()) {
            $this->markTestSkipped('Papago service is not enabled');
        }

        // Test auto-detection
        $result = $service->translate('Bonjour le monde', 'en');

        $this->assertIsArray($result);
        $this->assertEquals('fr', $result['sourceLang']);
        $this->assertEquals('en', $result['targetLang']);
        $this->assertStringContainsStringIgnoringCase('hello', $result['translatedText']);

        dump([
            '✅ Papago Auto Language Detection' => 'SUCCESS',
            'Input' => 'Bonjour le monde',
            'Detected' => 'French (fr)',
            'Translation' => $result['translatedText'],
        ]);
    }

    /**
     * @test
     * @group integration
     * @group papago
     */
    public function it_can_detect_language_with_papago(): void
    {
        $service = new PapagoService();

        if (!$service->isEnabled()) {
            $this->markTestSkipped('Papago service is not enabled');
        }

        // Test language detection
        $result = $service->detectLanguage('こんにちは');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('langCode', $result);
        $this->assertEquals('ja', $result['langCode']);

        dump([
            '✅ Papago Language Detection' => 'SUCCESS',
            'Input' => 'こんにちは',
            'Detected' => 'Japanese (ja)',
        ]);
    }

    /**
     * @test
     * @group integration
     * @group papago
     */
    public function it_can_translate_batch_texts_with_papago(): void
    {
        $service = new PapagoService();

        if (!$service->isEnabled()) {
            $this->markTestSkipped('Papago service is not enabled');
        }

        $texts = [
            'Good morning',
            'Good afternoon',
            'Good evening',
        ];

        $results = $service->translateBatch($texts, 'ko', 'en');

        $this->assertIsArray($results);
        $this->assertCount(3, $results);
        
        foreach ($results as $result) {
            $this->assertArrayHasKey('translatedText', $result);
            $this->assertStringContainsString('좋은', $result['translatedText']);
        }

        dump([
            '✅ Papago Batch Translation' => 'SUCCESS',
            'Input Count' => count($texts),
            'Results' => $results,
        ]);
    }

    /**
     * @test
     * @group integration
     * @group clova-ocr
     */
    public function it_can_validate_ocr_service_configuration(): void
    {
        $service = new ClovaOcrService();

        if (!$service->isEnabled()) {
            $this->markTestSkipped('Clova OCR service is not enabled');
        }

        // Just verify service is properly configured
        $this->assertTrue($service->isEnabled());

        dump([
            '✅ Clova OCR Configuration' => 'VALID',
            'Note' => 'OCR requires actual image file for full test',
            'Status' => 'Service is enabled and ready',
        ]);
    }

    /**
     * @test
     * @group integration
     * @group clova-speech
     */
    public function it_can_validate_speech_service_configuration(): void
    {
        $service = new ClovaSpeechService();

        if (!$service->isEnabled()) {
            $this->markTestSkipped('Clova Speech service is not enabled');
        }

        // Just verify service is properly configured
        $this->assertTrue($service->isEnabled());

        dump([
            '✅ Clova Speech Configuration' => 'VALID',
            'Note' => 'Speech requires actual audio file for full test',
            'Status' => 'Service is enabled and ready',
            'Supported Languages' => $service->getSupportedLanguages(),
        ]);
    }

    /**
     * @test
     * @group integration
     * @group search-trend
     */
    public function it_can_get_keyword_trends_with_search_trend(): void
    {
        $service = new \App\Services\Naver\SearchTrendService();

        if (!$service->isEnabled()) {
            $this->markTestSkipped('Search Trend service is not enabled');
        }

        // Test travel destination keyword trends
        $result = $service->getKeywordTrends(
            ['제주도'],
            now()->subMonths(3)->format('Y-m-d'),
            now()->format('Y-m-d'),
            'month'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertNotEmpty($result['results']);
        
        dump([
            '✅ Search Trend Keyword Analysis' => 'SUCCESS',
            'Keyword' => '제주도',
            'Period' => 'Last 3 months',
            'Data Points' => count($result['results'][0]['data'] ?? []),
            'Sample Data' => array_slice($result['results'][0]['data'] ?? [], 0, 3),
        ]);
    }

    /**
     * @test
     * @group integration
     * @group search-trend
     */
    public function it_can_compare_multiple_destinations_with_search_trend(): void
    {
        $service = new \App\Services\Naver\SearchTrendService();

        if (!$service->isEnabled()) {
            $this->markTestSkipped('Search Trend service is not enabled');
        }

        // Compare popular Korean travel destinations
        $result = $service->compareKeywords(
            [
                ['제주도'],
                ['부산'],
                ['강릉'],
            ],
            now()->subMonths(6)->format('Y-m-d'),
            now()->format('Y-m-d'),
            'month'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertCount(3, $result['results']);

        dump([
            '✅ Search Trend Destination Comparison' => 'SUCCESS',
            'Destinations' => ['제주도', '부산', '강릉'],
            'Period' => 'Last 6 months',
            'Results' => array_map(fn($r) => [
                'destination' => $r['title'],
                'avg_trend' => round(array_sum(array_column($r['data'], 'ratio')) / count($r['data']), 1)
            ], $result['results']),
        ]);
    }

    /**
     * @test
     * @group integration
     * @group search-trend
     */
    public function it_can_analyze_destination_popularity_with_search_trend(): void
    {
        $service = new \App\Services\Naver\SearchTrendService();

        if (!$service->isEnabled()) {
            $this->markTestSkipped('Search Trend service is not enabled');
        }

        // Analyze yearly trend for travel keyword
        $result = $service->analyzeDestinationPopularity(
            '해외여행',
            now()->subYear()->format('Y-m-d'),
            now()->format('Y-m-d'),
            'month'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('keyword', $result);
        $this->assertArrayHasKey('peak_period', $result);
        $this->assertArrayHasKey('peak_ratio', $result);

        dump([
            '✅ Search Trend Popularity Analysis' => 'SUCCESS',
            'Keyword' => $result['keyword'],
            'Peak Period' => $result['peak_period'],
            'Peak Ratio' => $result['peak_ratio'],
            'Total Data Points' => $result['total_data_points'],
        ]);
    }

    /**
     * @test
     * @group integration
     * @group summary
     */
    public function it_can_verify_all_services_are_configured(): void
    {
        $maps = new NaverMapsService();
        $papago = new PapagoService();
        $ocr = new ClovaOcrService();
        $speech = new ClovaSpeechService();
        $searchTrend = new \App\Services\Naver\SearchTrendService();

        $summary = [
            'NAVER Maps' => [
                'enabled' => $maps->isEnabled(),
                'status' => $maps->isEnabled() ? '✅ READY' : '❌ NOT CONFIGURED',
            ],
            'Papago Translation' => [
                'enabled' => $papago->isEnabled(),
                'status' => $papago->isEnabled() ? '✅ READY' : '❌ NOT CONFIGURED',
                'languages' => count($papago->getSupportedLanguages()) . ' languages',
            ],
            'Clova OCR' => [
                'enabled' => $ocr->isEnabled(),
                'status' => $ocr->isEnabled() ? '✅ READY' : '❌ NOT CONFIGURED',
            ],
            'Clova Speech' => [
                'enabled' => $speech->isEnabled(),
                'status' => $speech->isEnabled() ? '✅ READY' : '❌ NOT CONFIGURED',
                'languages' => count($speech->getSupportedLanguages()) . ' languages',
            ],
            'Search Trend (DataLab)' => [
                'enabled' => $searchTrend->isEnabled(),
                'status' => $searchTrend->isEnabled() ? '✅ READY' : '❌ NOT CONFIGURED',
                'use_cases' => 'Travel trends, keyword analysis, seasonal insights',
            ],
        ];

        dump(['🎯 NAVER API Integration Summary' => $summary]);

        // At least one service should be enabled for integration tests to be meaningful
        $enabledCount = collect($summary)->filter(fn($s) => $s['enabled'])->count();
        $this->assertGreaterThan(0, $enabledCount, 'At least one NAVER service should be enabled');
    }
}
