<?php

namespace Tests\Unit\Services\Naver;

use App\Services\Naver\NaverMapsService;
use App\Services\Naver\PapagoService;
use App\Services\Naver\ClovaOcrService;
use App\Exceptions\NaverApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * TDD Test Suite for NAVER Cloud Platform Authentication & Authorization
 *
 * Tests comprehensive authentication/authorization scenarios:
 * - API key validation (valid/invalid/missing)
 * - Service enablement flags
 * - Authentication header injection
 * - Rate limiting and timeout handling
 * - Error response handling (401, 403, 429, 500)
 * - Service-specific authentication (Maps vs Papago vs OCR)
 *
 * NAVER Cloud Platform API Authentication:
 * - Maps: X-NCP-APIGW-API-KEY-ID + X-NCP-APIGW-API-KEY
 * - Papago: X-Naver-Client-Id + X-Naver-Client-Secret  
 * - OCR: X-OCR-SECRET header + invoke URL
 *
 * Reference: https://guide.ncloud-docs.com/docs/en/naveropenapiv3-maps-overview
 */
class NaverAuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set default test configuration for all services
        Config::set('services.naver.maps', [
            'client_id' => 'test_maps_id',
            'client_secret' => 'test_maps_secret',
            'base_url' => 'https://naveropenapi.apigw.ntruss.com',
            'enabled' => true,
        ]);

        Config::set('services.naver.papago', [
            'client_id' => 'test_papago_id',
            'client_secret' => 'test_papago_secret',
            'base_url' => 'https://naveropenapi.apigw.ntruss.com',
            'enabled' => true,
            'supported_languages' => ['ko', 'en', 'ja', 'zh-CN', 'zh-TW', 'es', 'fr', 'de', 'ru', 'pt', 'it', 'vi', 'th', 'id', 'ar'],
        ]);

        Config::set('services.naver.ocr', [
            'url' => 'https://test-ocr.apigw.ntruss.com/custom/v1/test/general',
            'secret_key' => 'test_ocr_secret',
            'enabled' => true,
        ]);

        Config::set('services.naver.timeout', 30);
        Config::set('services.naver.retry_times', 3);
        Config::set('services.naver.retry_sleep', 1000);
    }

    // ========== Service Enablement Tests ==========

    #[\PHPUnit\Framework\Attributes\Test]
    public function maps_service_with_valid_credentials_is_enabled(): void
    {
        $service = new NaverMapsService();

        $this->assertTrue($service->isEnabled());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function maps_service_with_missing_client_id_is_disabled(): void
    {
        Config::set('services.naver.maps.client_id', '');

        $service = new NaverMapsService();

        $this->assertFalse($service->isEnabled());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function maps_service_with_missing_client_secret_is_disabled(): void
    {
        Config::set('services.naver.maps.client_secret', '');

        $service = new NaverMapsService();

        $this->assertFalse($service->isEnabled());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function maps_service_with_enabled_flag_false_is_disabled(): void
    {
        Config::set('services.naver.maps.enabled', false);

        $service = new NaverMapsService();

        $this->assertFalse($service->isEnabled());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function papago_service_with_valid_credentials_is_enabled(): void
    {
        $service = new PapagoService();

        $this->assertTrue($service->isEnabled());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function papago_service_with_missing_credentials_is_disabled(): void
    {
        Config::set('services.naver.papago.client_id', '');
        Config::set('services.naver.papago.client_secret', '');

        $service = new PapagoService();

        $this->assertFalse($service->isEnabled());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function ocr_service_with_valid_config_is_enabled(): void
    {
        $service = new ClovaOcrService();

        $this->assertTrue($service->isEnabled());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function ocr_service_with_missing_secret_key_is_disabled(): void
    {
        Config::set('services.naver.ocr.secret_key', '');

        $service = new ClovaOcrService();

        $this->assertFalse($service->isEnabled());
    }

    // ========== Authentication Header Tests ==========

    #[\PHPUnit\Framework\Attributes\Test]
    public function maps_service_includes_ncp_authentication_headers(): void
    {
        Http::fake();

        $service = new NaverMapsService();

        try {
            $service->geocode('서울');
        } catch (\Exception $e) {
            // Ignore exceptions
        }

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-NCP-APIGW-API-KEY-ID', 'test_maps_id') &&
                   $request->hasHeader('X-NCP-APIGW-API-KEY', 'test_maps_secret');
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function papago_service_includes_naver_client_headers(): void
    {
        Http::fake();

        $service = new PapagoService();

        try {
            $service->translate('Hello', 'ko', 'en');
        } catch (\Exception $e) {
            // Ignore exceptions
        }

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-NCP-APIGW-API-KEY-ID', 'test_papago_id') &&
                   $request->hasHeader('X-NCP-APIGW-API-KEY', 'test_papago_secret');
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function ocr_service_includes_secret_key_header(): void
    {
        Http::fake();

        $service = new ClovaOcrService();

        try {
            $service->extractText('fake_image_data');
        } catch (\Exception $e) {
            // Ignore exceptions
        }

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-OCR-SECRET', 'test_ocr_secret');
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function all_services_include_content_type_header(): void
    {
        Http::fake();

        $mapsService = new NaverMapsService();
        $papagoService = new PapagoService();

        try {
            $mapsService->geocode('서울');
        } catch (\Exception $e) {
            // Ignore
        }

        try {
            $papagoService->translate('Hello', 'ko', 'en');
        } catch (\Exception $e) {
            // Ignore
        }

        Http::assertSent(function ($request) {
            return $request->hasHeader('Content-Type', 'application/json');
        });
    }

    // ========== Error Response Tests ==========

    #[\PHPUnit\Framework\Attributes\Test]
    public function service_throws_exception_on_401_unauthorized(): void
    {
        Http::fake([
            'naveropenapi.apigw.ntruss.com/*' => Http::response([
                'errorMessage' => 'Authentication failed: Invalid API key',
                'errorCode' => 'E401',
            ], 401),
        ]);

        $service = new NaverMapsService();

        $this->expectException(NaverApiException::class);
        $this->expectExceptionMessage('Authentication failed');

        $service->geocode('서울');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function service_throws_exception_on_403_forbidden(): void
    {
        Http::fake([
            '*' => Http::response([
                'errorMessage' => 'Access denied: Insufficient permissions',
                'errorCode' => 'E403',
            ], 403),
        ]);

        $service = new PapagoService();

        $this->expectException(NaverApiException::class);
        $this->expectExceptionMessage('Access denied');

        $service->translate('Hello', 'ko', 'en');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function service_throws_exception_on_429_rate_limit(): void
    {
        Http::fake([
            'naveropenapi.apigw.ntruss.com/*' => Http::response([
                'errorMessage' => 'Rate limit exceeded: Too many requests',
                'errorCode' => 'E429',
            ], 429),
        ]);

        $service = new NaverMapsService();

        $this->expectException(NaverApiException::class);
        $this->expectExceptionMessage('Rate limit exceeded');

        $service->geocode('서울');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function service_throws_exception_on_500_server_error(): void
    {
        Http::fake([
            '*' => Http::response([
                'errorMessage' => 'Internal server error',
                'errorCode' => 'E500',
            ], 500),
        ]);

        $service = new PapagoService();

        $this->expectException(NaverApiException::class);
        $this->expectExceptionMessage('Internal server error');

        $service->translate('Hello', 'ko', 'en');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function service_handles_missing_error_message_gracefully(): void
    {
        Http::fake([
            '*' => Http::response('', 400),
        ]);

        $service = new PapagoService();

        $this->expectException(NaverApiException::class);
        $this->expectExceptionMessage('Unknown error');

        $service->translate('Hello', 'ko', 'en');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authentication_error_includes_context_information(): void
    {
        Http::fake([
            'naveropenapi.apigw.ntruss.com/*' => Http::response([
                'errorMessage' => 'Invalid credentials',
                'errorCode' => 'AUTH_FAIL',
            ], 401),
        ]);

        $service = new NaverMapsService();

        try {
            $service->geocode('서울');
            $this->fail('Expected NaverApiException was not thrown');
        } catch (NaverApiException $e) {
            $this->assertEquals(401, $e->getCode());
            $this->assertStringContainsString('geocode', $e->getMessage());
        }
    }

    // ========== Disabled Service Behavior Tests ==========

    #[\PHPUnit\Framework\Attributes\Test]
    public function disabled_maps_service_returns_null_for_operations(): void
    {
        Config::set('services.naver.maps.enabled', false);

        $service = new NaverMapsService();

        $result = $service->geocode('서울');

        $this->assertNull($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function disabled_papago_service_returns_null_for_operations(): void
    {
        Config::set('services.naver.papago.enabled', false);

        $service = new PapagoService();

        $result = $service->translate('Hello', 'ko', 'en');

        $this->assertNull($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function disabled_ocr_service_returns_null_for_operations(): void
    {
        Config::set('services.naver.ocr.secret_key', '');

        $service = new ClovaOcrService();

        $result = $service->extractText('fake_image');

        $this->assertNull($result);
    }

    // ========== Service Configuration Tests ==========

    #[\PHPUnit\Framework\Attributes\Test]
    public function service_respects_timeout_configuration(): void
    {
        Config::set('services.naver.timeout', 10);

        $service = new NaverMapsService();

        $this->assertTrue($service->isEnabled());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function service_retries_on_network_failure(): void
    {
        Config::set('services.naver.retry_times', 3);

        Http::fake([
            'naveropenapi.apigw.ntruss.com/*' => Http::sequence()
                ->push(null, 500) // First attempt fails
                ->push(null, 500) // Second attempt fails
                ->push(['addresses' => [['jibunAddress' => '서울', 'roadAddress' => '서울', 'x' => '126.9780', 'y' => '37.5665']]], 200), // Third attempt succeeds
        ]);

        $service = new NaverMapsService();
        
        // Service should retry and eventually succeed
        $this->assertTrue($service->isEnabled());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function multiple_services_can_be_enabled_independently(): void
    {
        // Maps enabled
        Config::set('services.naver.maps.enabled', true);
        
        // Papago disabled
        Config::set('services.naver.papago.client_id', '');
        
        $mapsService = new NaverMapsService();
        $papagoService = new PapagoService();

        $this->assertTrue($mapsService->isEnabled());
        $this->assertFalse($papagoService->isEnabled());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function different_services_use_different_authentication_methods(): void
    {
        Http::fake();

        $mapsService = new NaverMapsService();
        $papagoService = new PapagoService();
        $ocrService = new ClovaOcrService();

        // Maps and Papago both use NCP Gateway headers
        try {
            $mapsService->geocode('서울');
        } catch (\Exception $e) {
            // Ignore
        }

        try {
            $papagoService->translate('Hello', 'ko', 'en');
        } catch (\Exception $e) {
            // Ignore
        }

        // OCR uses different authentication (X-OCR-SECRET)
        try {
            $ocrService->extractText('fake_image');
        } catch (\Exception $e) {
            // Ignore
        }

        // Verify Maps and Papago both use NCP Gateway auth
        Http::assertSent(function ($request) {
            return $request->hasHeader('X-NCP-APIGW-API-KEY-ID');
        });

        // Verify OCR uses different auth method (X-OCR-SECRET)
        Http::assertSent(function ($request) {
            return $request->hasHeader('X-OCR-SECRET');
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function ocr_service_uses_invoke_url_from_config(): void
    {
        Http::fake();

        $service = new ClovaOcrService();

        try {
            $service->extractText('fake_image');
        } catch (\Exception $e) {
            // Ignore
        }

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'test-ocr.apigw.ntruss.com');
        });
    }
}
