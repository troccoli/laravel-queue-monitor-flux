<?php

namespace Troccoli\LaravelQueueMonitorFlux\Tests;

use Flux\FluxServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use romanzipp\QueueMonitor\Providers\QueueMonitorProvider;
use Troccoli\LaravelQueueMonitorFlux\QueueMonitorFluxServiceProvider;

class TestCase extends Orchestra
{
    private static ?string $isolatedMigrationDirectory = null;

    private ?string $migrationDirectory = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        self::assertSame(0, Artisan::call('vendor:publish', [
            '--provider' => QueueMonitorProvider::class,
            '--tag' => 'migrations',
        ]));
        self::assertSame(0, Artisan::call('migrate', ['--force' => true]));
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            if ($this->migrationDirectory !== null) {
                (new Filesystem)->deleteDirectory($this->migrationDirectory);
            }
        }
    }

    protected function getPackageProviders($app)
    {
        return [
            QueueMonitorProvider::class,
            LivewireServiceProvider::class,
            FluxServiceProvider::class,
            QueueMonitorFluxServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $this->migrationDirectory = self::$isolatedMigrationDirectory ??= sys_get_temp_dir().'/queue-monitor-flux-tests-'.bin2hex(random_bytes(8));
        $app['files']->makeDirectory($this->migrationDirectory.'/migrations', recursive: true);
        $app->useDatabasePath($this->migrationDirectory);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('queue-monitor.ui.enabled', true);
        $app['config']->set('queue-monitor.ui.route', ['prefix' => 'jobs', 'middleware' => ['web']]);
        $app['config']->set('queue-monitor.ui.show_metrics', false);
    }
}
