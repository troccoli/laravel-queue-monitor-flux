<?php

namespace Troccoli\LaravelQueueMonitorFlux;

use Illuminate\Support\ServiceProvider;

final class QueueMonitorFluxServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(function (): void {
            /** @phpstan-ignore-next-line  */
            $this->app['view']->replaceNamespace('queue-monitor', [
                resource_path('views/vendor/queue-monitor-flux'),
                __DIR__.'/../resources/views',
            ]);
        });

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/queue-monitor-flux'),
        ], 'views');
    }
}
