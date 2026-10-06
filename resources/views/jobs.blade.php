<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if(config('queue-monitor.ui.refresh_interval'))
        <meta http-equiv="refresh" content="{{ config('queue-monitor.ui.refresh_interval') }}">
    @endif
    <title>@lang('Queue Monitor')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance()
</head>

<body class="font-sans pb-64 bg-white dark:bg-gray-800 dark:text-white">

<flux:heading size="xl" level="1" class="w-full p-4 border-b border-gray-100 dark:border-gray-600">
    @lang('Queue Monitor')
</flux:heading>

<main class="flex">

    <article class="w-full p-4">
        <flux:heading level="2" size="md" class="mb-4">
            @lang('Filter')
        </flux:heading>

        @include('queue-monitor::partials.filter', [
            'filters' => $filters,
        ])

        <flux:heading level="2" size="md" class="mb-4">
            @lang('Jobs')
        </flux:heading>

        @include('queue-monitor::partials.table', [
            'jobs' => $jobs,
        ])

        @if(config('queue-monitor.ui.allow_purge'))
            <div class="mt-12">
                <form action="{{ route('queue-monitor::purge') }}" method="post">
                    @csrf
                    @method('delete')
                    <flux:button type="submit" variant="filled" color="red" size="sm">
                        @lang('Delete all entries')
                    </flux:button>
                </form>
            </div>
        @endif
    </article>

    @if (config('queue-monitor.ui.show_metrics'))

        <aside class="flex flex-col gap-4 w-[24rem] p-4">
            <flux:heading level="2" size="md" class="">
                @lang('Statistics')
            </flux:heading>
            @foreach($metrics->all() as $metric)
                @include('queue-monitor::partials.metrics-card', [
                    'metric' => $metric,
                ])
            @endforeach
        </aside>
    @endif

</main>

@livewireScripts
@fluxScripts()
</body>

</html>
