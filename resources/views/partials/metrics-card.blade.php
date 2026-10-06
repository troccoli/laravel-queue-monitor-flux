<flux:card variant="muted" class="space-y-2">

    <flux:heading size="lg"
                  title="{{ __('Last :days days', ['days' => config('queue-monitor.ui.metrics_time_frame') ?? 14]) }}">
        {{ __($metric->title) }}
    </flux:heading>

    <flux:text variant="strong" class="text-3xl">
        {{ $metric->format($metric->value) }}
    </flux:text>

    @if($metric->previousValue !== null)
        @if($metric->hasChanged())
            @if($metric->hasIncreased())
                <flux:text variant="strong" color="green" class="font-semibold">
                    Up from {{ $metric->format($metric->previousValue) }}
                </flux:text>
            @else
                <flux:text variant="strong" color="red" class="font-semibold">
                    Down from {{ $metric->format($metric->previousValue) }}
                </flux:text>
            @endif
        @else
            <flux:text variant="strong" class="font-semibold">
                No change from {{ $metric->format($metric->previousValue) }}
            </flux:text>
        @endif
    @endif

</flux:card>
