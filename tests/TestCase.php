<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        DatabaseSafety::assertIsolated($app['config']->get('database'));

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('inertia.pages.paths', [
            resource_path('js/Pages'),
            resource_path('js'),
        ]);
    }
}
