<?php

namespace Tests;

use App\Services\Features\FeatureGate;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature entitlements are cached; make sure every test starts clean.
        $this->app->forgetInstance(FeatureGate::class);
        app()->forgetInstance('current.business');
    }
}
