@switch($status)

    @case(\romanzipp\QueueMonitor\Enums\MonitorStatus::QUEUED)
        <flux:badge rounded size="sm">@lang('Queued')</flux:badge>
        @break

    @case(\romanzipp\QueueMonitor\Enums\MonitorStatus::RUNNING)
        <flux:badge rounded size="sm" color="blue">@lang('Running')</flux:badge>
        @break

    @case(\romanzipp\QueueMonitor\Enums\MonitorStatus::SUCCEEDED)
        <flux:badge rounded size="sm" color="green">@lang('Success')</flux:badge>
        @break

    @case(\romanzipp\QueueMonitor\Enums\MonitorStatus::FAILED)
        <flux:badge rounded size="sm" color="red">@lang('Failed')</flux:badge>
        @break

    @case(\romanzipp\QueueMonitor\Enums\MonitorStatus::STALE)
        <flux:badge rounded size="sm" variant="solid">@lang('Stale')</flux:badge>
        @break

@endswitch
