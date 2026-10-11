<?php

namespace Tests\Unit\Http;

use Illuminate\Cache\RateLimiter;
use Tests\TestCase;

/**
 * The "api" limiter used to be registered in RouteServiceProvider::boot(),
 * which the Laravel 11 skeleton migration removed in favour of bootstrap/app.php.
 * Both the api and hook middleware groups reference "throttle:api", so the
 * limiter has to keep existing or every request to those routes throws.
 */
class ApiRateLimiterTest extends TestCase
{
    public function test_api_rate_limiter_is_registered(): void
    {
        $this->assertNotNull(
            app(RateLimiter::class)->limiter('api'),
            'The "api" rate limiter is missing; throttle:api will fail to resolve.',
        );
    }
}
