<x-filament::page>
    @php $items = auth()->user()->notifications()->latest()->paginate(15); @endphp

    <div class="space-y-3">
        @foreach ($items as $n)
            @php $data = $n->data; @endphp
            <x-filament::section>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="font-semibold">{{ $data['title'] ?? 'Notification' }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            {{ $data['body'] ?? \Illuminate\Support\Str::limit(json_encode($data), 120) }}
                        </div>
                        <div class="mt-1 text-xs text-gray-500">
                            {{ \Carbon\Carbon::parse($data['at'] ?? $n->created_at)->isoFormat('DD MMMM YYYY HH:mm') }}
                        </div>
                    </div>
                </div>
            </x-filament::section>
        @endforeach

        {{ $items->links() }}
    </div>
</x-filament::page>
