<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="space-y-4">
                <div class="h-[400px] overflow-y-auto border rounded-lg p-4 space-y-4">
                    @foreach($this->record->memberLogs()->orderBy('created_at')->take(5)->get() as $log)
                        <div class="flex {{ $log->direction === 'in' ? 'justify-start' : 'justify-end' }}">
                            <div class="max-w-[70%] rounded-lg p-3 {{ $log->direction === 'in' ? 'bg-gray-100' : 'bg-primary-500 text-white' }}">
                                <p>{{ $log->action }}</p>
                                <p class="text-xs mt-1 {{ $log->direction === 'in' ? 'text-gray-500' : 'text-primary-100' }}">
                                    {{ $log->created_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{ $this->form }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
