<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Laravel\Fortify\Features;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * SAFETY GUARD
     *
     * The tests rebuild the database from scratch (RefreshDatabase).
     * phpunit.xml points them at a temporary in-memory SQLite database,
     * but if the configuration was cached (php artisan config:cache) the
     * tests could accidentally use your real MySQL database.
     *
     * This check runs BEFORE any migration, and stops the tests
     * immediately if the database is not the in-memory SQLite database.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(
                'Tests stopped to protect your data: they must use the in-memory SQLite '
                .'database, but they are configured to use "'.$connection.'". '
                .'Run "php artisan config:clear" and try again.'
            );
        }

        return $app;
    }

    /**
     * Every test starts at this fixed moment, with the clock stopped, so
     * results never depend on when the suite runs (second boundaries,
     * midnight, New Year). Tests that need time to pass use
     * $this->travel(...) or $this->travelTo(...) from here.
     * 10:00 UTC is 18:00 in Manila, the same calendar day in both.
     */
    public const FROZEN_NOW = '2026-06-15 10:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse(self::FROZEN_NOW, 'UTC'));

        // Safety net: tests may never call real external services
        // (Gemini, PayMongo). Tests that need them use Http::fake().
        Http::preventStrayRequests();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
