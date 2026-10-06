<flux:card>
    <flux:table bleed>
        <flux:table.columns class="uppercase">
            <flux:table.column>@lang('Status')</flux:table.column>
            <flux:table.column>@lang('Job')</flux:table.column>
            <flux:table.column>@lang('Details')</flux:table.column>
            @if(config('queue-monitor.ui.show_custom_data'))
                <flux:table.column>@lang('Custom Data')</flux:table.column>
            @endif
            <flux:table.column>@lang('Progress')</flux:table.column>
            <flux:table.column>@lang('Duration')</flux:table.column>
            <flux:table.column>@lang('Started')</flux:table.column>
            <flux:table.column>@lang('Error')</flux:table.column>
            @if(config('queue-monitor.ui.allow_deletion') || config('queue-monitor.ui.allow_retry'))
                <flux:table.column></flux:table.column>
            @endif
        </flux:table.columns>
        <flux:table.rows class="bg-gray-50 dark:bg-gray-700">
            @forelse($jobs as $job)
                <flux:table.row class="leading-relaxed">
                    <flux:table.cell>
                        @include('queue-monitor::partials.job-status', ['status' => $job->status])
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:heading>{{ $job->getBaseName() }}</flux:heading>
                        <flux:text>#{{ $job->job_id }}</flux:text>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:text>@lang('Queue'): <span class="font-semibold">{{ $job->queue }}</span></flux:text>
                        <flux:text>@lang('Attempt'): <span class="font-semibold">{{ $job->attempt }}</span></flux:text>
                        @if($job->retried)
                            <flux:badge size="sm">@lang('Retried')</flux:badge>
                        @endif
                    </flux:table.cell>
                    @if(config('queue-monitor.ui.show_custom_data'))
                        <flux:table.cell>
                            <flux:textarea rows="4" readonly="true">
                                {{ json_encode($job->getData(), JSON_PRETTY_PRINT) }}
                            </flux:textarea>
                        </flux:table.cell>
                    @endif
                    <flux:table.cell>
                        @if($job->progress !== null)
                            <flux:progress color="green" class="h-3" value="{{ $job->progress }}" />
                            <flux:text size="sm" class="text-center font-bold">{{ $job->progress }}%</flux:text>
                        @else
                            <flux:text>-</flux:text>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $job->getElapsedInterval()->format('%H:%I:%S') }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $job->started_at?->diffForHumans() }}
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($job->status != \romanzipp\QueueMonitor\Enums\MonitorStatus::SUCCEEDED && $job->exception_message !== null)
                            <flux:textarea rows="4" readonly="true">
                                {{ $job->exception_message }}
                            </flux:textarea>
                        @else
                            <flux:text>-</flux:text>
                        @endif
                    </flux:table.cell>
                    @if(config('queue-monitor.ui.allow_deletion') || config('queue-monitor.ui.allow_retry'))
                        <flux:table.cell>
                            @if(config('queue-monitor.ui.allow_retry') && $job->canBeRetried())
                                <form action="{{ route('queue-monitor::retry', [$job]) }}" method="post">
                                    @csrf
                                    @method('patch')
                                    <flux:button type="submit" size="sm" color="blue" variant="filled">@lang('Retry')</flux:button>
                                </form>
                            @endif
                            @if(config('queue-monitor.ui.allow_deletion') && $job->isFinished())
                                    <form action="{{ route('queue-monitor::destroy', [$job]) }}" method="post">
                                        @csrf
                                        @method('delete')
                                        <flux:button type="submit" size="sm" color="red" variant="ghost">@lang('Delete')</flux:button>
                                    </form>
                            @endif
                        </flux:table.cell>
                    @endif
                </flux:table.row>
            @empty
                <flux:table.row>
                    <td colspan="100" class="text-center py-6">
                        <flux:text size="xl" class="">@lang('No Jobs')</flux:text>
                    </td>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    <div class="flex justify-between mt-4 -mb-2">
        <flux:text>
            @lang('Showing')
            @if($jobs->total() > 0)
                <span class="font-semibold">{{ $jobs->firstItem() }}</span> @lang('to')
                <span class="font-semibold">{{ $jobs->lastItem() }}</span> @lang('of')
            @endif
            <span class="font-semibold">{{ $jobs->total() }}</span> @choice('result|results', $jobs->total())
        </flux:text>
        <div class="flex gap-2">
            @if($jobs->onFirstPage())
                <flux:button size="sm" variant="filled" disabled>
                    @lang('Previous')
                </flux:button>
            @else
                <flux:button href="{{ $jobs->previousPageUrl() }}" size="sm" variant="filled">
                    @lang('Previous')
                </flux:button>
            @endif
            @if($jobs->hasMorePages())
                <flux:button href="{{ $jobs->url($jobs->currentPage() + 1) }}" size="sm" variant="filled">
                    @lang('Next')
                </flux:button>
            @else
                <flux:button size="sm" variant="filled" disabled>
                    @lang('Next')
                </flux:button>
            @endif
        </div>
    </div>
</flux:card>
