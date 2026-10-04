<flux:card class="pl-4 pr-6 py-4 mb-6">

    <form action="" method="get">

        <div class="flex items-center gap-4 my-2 -mx-2">

            <flux:input
                :label="__('Job name')"
                type="text"
                id="filter_name"
                name="name"
                value="{{ $filters['name'] ?? null }}"
                placeholder="ExampleJob"
            />

            @if(config('queue-monitor.ui.show_custom_data'))
                <flux:input
                    :label="__('Custom data')"
                    type="text"
                    id="filter_custom_data"
                    name="custom_data"
                    value="{{ $filters['custom_data'] ?? null }}"
                    placeholder="Example Custom Data" />
            @endif

            <div class="grow min-w-24 max-w-1/4">
                <flux:field>
                    <flux:label>@lang('Status')</flux:label>
                    <flux:select name="status" id="filter_status">
                        <flux:select.option :value="null">All</flux:select.option>
                        @foreach($statuses as $status => $statusName)
                            <flux:select.option :value="$status">{{ $statusName }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            <div class="grow min-w-24 max-w-1/4">
                <flux:field>
                    <flux:label>@lang('Queues')</flux:label>
                    <flux:select name="queue" id="filter_queues">
                        <flux:select.option value="all">All</flux:select.option>
                        @foreach($queues as $queue)
                            <flux:select.option :value="$queue">{{ e($queue) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

        </div>

        <div class="flex justify-between mt-4">
            <flux:button type="submit" variant="filled" color="blue" size="sm">
                @lang('Apply Filter')
            </flux:button>

            <flux:button variant="filled" color="gray" size="sm" href="{{ route('queue-monitor::index') }}">
                @lang('Reset Filter')
            </flux:button>
        </div>

    </form>

</flux:card>
