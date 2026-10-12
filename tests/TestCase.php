<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Roll each test back so tests that touch the real database stay isolated.
     */
    public function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();

        parent::tearDown();
    }
}
