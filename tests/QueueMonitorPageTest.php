<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use romanzipp\QueueMonitor\Enums\MonitorStatus;
use romanzipp\QueueMonitor\Models\Monitor;

function monitor(array $attributes = []): Monitor
{
    return Monitor::query()->create(array_merge([
        'job_id' => 'job-1',
        'name' => 'App\\Jobs\\ExampleJob',
        'queue' => 'default',
        'status' => MonitorStatus::SUCCEEDED,
        'attempt' => 1,
        'started_at' => now()->subMinutes(2),
        'finished_at' => now()->subMinute(),
    ], $attributes));
}

it('uses the Flux package view from the real core route', function () {
    $viewPath = realpath(View::getFinder()->find('queue-monitor::jobs'));

    expect($viewPath)->toBe(realpath(__DIR__.'/../resources/views/jobs.blade.php'));

    $response = $this->get(route('queue-monitor::index'));

    $response->assertOk()
        ->assertViewIs('queue-monitor::jobs')
        ->assertSee('data-flux-heading', false)
        ->assertSee('data-flux-table', false)
        ->assertSee('Queue Monitor')
        ->assertDontSee('vendor/queue-monitor/app.css');

    expect(realpath($response->baseResponse->getOriginalContent()->getPath()))->toBe($viewPath);
});

it('keeps the core route contract', function () {
    foreach ([
        'queue-monitor::index' => ['GET', 'HEAD'],
        'queue-monitor::retry' => ['PATCH'],
        'queue-monitor::destroy' => ['DELETE'],
        'queue-monitor::purge' => ['DELETE'],
    ] as $name => $methods) {
        expect(Route::getRoutes()->getByName($name)?->methods())->toBe($methods);
    }

    expect(route('queue-monitor::index'))->toEndWith('/jobs')
        ->and(route('queue-monitor::retry', 7))->toEndWith('/jobs/monitors/retry/7')
        ->and(route('queue-monitor::destroy', 7))->toEndWith('/jobs/monitors/7')
        ->and(route('queue-monitor::purge'))->toEndWith('/jobs/purge');
});

it('renders representative jobs, filters, details, and status', function () {
    $failed = monitor([
        'job_id' => 'failed-42',
        'name' => 'App\\Jobs\\SendInvoice',
        'queue' => 'billing',
        'status' => MonitorStatus::FAILED,
        'job_uuid' => 'failed-uuid',
        'attempt' => 3,
        'progress' => 45,
        'exception_message' => 'Payment gateway unavailable',
        'data' => json_encode(['customer' => 'Ada']),
    ]);
    monitor(['job_id' => 'other-99', 'name' => 'App\\Jobs\\ArchiveInvoice', 'queue' => 'archive']);

    config()->set('queue-monitor.ui.show_custom_data', true);

    $this->get(route('queue-monitor::index', [
        'name' => 'SendInvoice',
        'queue' => 'billing',
        'status' => MonitorStatus::FAILED,
        'custom_data' => 'Ada',
    ]))->assertOk()
        ->assertSee('SendInvoice')
        ->assertSee('failed-42')
        ->assertSee('billing')
        ->assertSee('3')
        ->assertSee('Failed')
        ->assertSee('Payment gateway unavailable')
        ->assertSee('45%')
        ->assertSee('data-flux-progress', false)
        ->assertSee('Ada')
        ->assertSee('Retry')
        ->assertDontSee('other-99')
        ->assertSee('name="name"', false)
        ->assertSee('name="queue"', false)
        ->assertSee('name="status"', false)
        ->assertSee('name="custom_data"', false)
        ->assertSee(route('queue-monitor::retry', $failed), false)
        ->assertSee(route('queue-monitor::destroy', $failed), false);
});

it('paginates filtered results using the core paginator', function () {
    config()->set('queue-monitor.ui.per_page', 1);
    monitor(['job_id' => 'first-job', 'queue' => 'billing', 'started_at' => now()->subMinute()]);
    monitor(['job_id' => 'second-job', 'queue' => 'billing', 'started_at' => now()->subMinutes(2)]);
    monitor(['job_id' => 'hidden-job', 'queue' => 'archive']);

    $firstPage = $this->get(route('queue-monitor::index', ['queue' => 'billing']));
    $firstPage
        ->assertSee('first-job')->assertDontSee('second-job')
        ->assertSee('page=2', false)->assertSee('queue=billing', false);
    expect($firstPage->viewData('jobs')->total())->toBe(2)
        ->and($firstPage->viewData('jobs')->firstItem())->toBe(1);

    $secondPage = $this->get(route('queue-monitor::index', ['queue' => 'billing', 'page' => 2]));
    $secondPage->assertSee('second-job')->assertDontSee('first-job');
    expect($secondPage->viewData('jobs')->firstItem())->toBe(2);
});

it('honors display switches and renders metrics', function () {
    monitor();
    config()->set('queue-monitor.ui.show_metrics', true);
    config()->set('queue-monitor.ui.metrics_time_frame', 7);
    config()->set('queue-monitor.ui.refresh_interval', 30);

    $response = $this->get(route('queue-monitor::index'));
    $response->assertOk()
        ->assertSee('Total Jobs Executed')
        ->assertSee('Total Execution Time')
        ->assertSee('Average Execution Time')
        ->assertSee('Last 7 days')
        ->assertSee('http-equiv="refresh" content="30"', false)
        ->assertSee('Delete all entries')
        ->assertDontSee('name="custom_data"', false);
    expect($response->viewData('metrics')->all())->toHaveCount(3)
        ->and($response->viewData('metrics')->all()[0]->value)->toEqual(1);

    config()->set('queue-monitor.ui.show_metrics', false);
    config()->set('queue-monitor.ui.allow_purge', false);
    config()->set('queue-monitor.ui.allow_retry', false);
    config()->set('queue-monitor.ui.allow_deletion', false);
    config()->set('queue-monitor.ui.refresh_interval', null);

    $this->get(route('queue-monitor::index'))->assertOk()
        ->assertDontSee('Total Jobs Executed')
        ->assertDontSee('Delete all entries')
        ->assertDontSee('Retry')
        ->assertDontSee('>Delete<', false)
        ->assertDontSee('http-equiv="refresh"', false);
});

it('orders queued jobs first when configured', function () {
    monitor(['job_id' => 'running-job', 'status' => MonitorStatus::RUNNING, 'finished_at' => null]);
    monitor(['job_id' => 'queued-job', 'status' => MonitorStatus::QUEUED, 'started_at' => null, 'finished_at' => null]);
    config()->set('queue-monitor.ui.order_queued_first', true);

    $jobs = $this->get(route('queue-monitor::index'))->assertOk()->viewData('jobs');

    expect($jobs->first()->job_id)->toBe('queued-job');
});

it('enforces the core UI permission switches', function () {
    $failed = monitor(['status' => MonitorStatus::FAILED, 'job_uuid' => 'blocked-uuid']);

    config()->set('queue-monitor.ui.allow_retry', false);
    $this->patch(route('queue-monitor::retry', $failed))->assertNotFound();
    config()->set('queue-monitor.ui.allow_deletion', false);
    $this->delete(route('queue-monitor::destroy', $failed))->assertNotFound();
    config()->set('queue-monitor.ui.allow_purge', false);
    $this->delete(route('queue-monitor::purge'))->assertNotFound();
    config()->set('queue-monitor.ui.enabled', false);
    $this->get(route('queue-monitor::index'))->assertNotFound();
    expect(Monitor::query()->count())->toBe(1);
});

it('posts to core actions and preserves retry, deletion, and purge behavior', function () {
    $failed = monitor([
        'status' => MonitorStatus::FAILED,
        'job_uuid' => 'retry-uuid',
        'finished_at' => null,
    ]);
    $finished = monitor(['job_id' => 'delete-me']);

    $html = $this->get(route('queue-monitor::index'))->assertOk()->getContent();
    $document = new DOMDocument;
    @$document->loadHTML($html);
    foreach ([
        [route('queue-monitor::retry', $failed), 'PATCH'],
        [route('queue-monitor::destroy', $finished), 'DELETE'],
        [route('queue-monitor::purge'), 'DELETE'],
    ] as [$action, $method]) {
        $forms = $document->getElementsByTagName('form');
        $matched = false;
        foreach ($forms as $form) {
            if ($form->getAttribute('action') !== $action) {
                continue;
            }

            expect($form->getAttribute('method'))->toBe('post');
            foreach ($form->getElementsByTagName('input') as $input) {
                if ($input->getAttribute('name') === '_method' && strtoupper($input->getAttribute('value')) === $method) {
                    $matched = true;
                }
            }
        }
        expect($matched)->toBeTrue();
    }

    $artisan = Mockery::mock(Kernel::class);
    $artisan->shouldReceive('call')->once()->with('queue:retry', ['id' => 'retry-uuid'])->andReturn(0);
    Artisan::swap($artisan);
    $this->patch(route('queue-monitor::retry', $failed))->assertRedirect(route('queue-monitor::index'));
    expect($failed->fresh()->retried)->toBeTrue();

    $this->delete(route('queue-monitor::destroy', $finished))->assertRedirect(route('queue-monitor::index'));
    expect(Monitor::query()->find($finished->id))->toBeNull();

    $this->delete(route('queue-monitor::purge'))->assertRedirect(route('queue-monitor::index'));
    expect(Monitor::query()->count())->toBe(0);
});
