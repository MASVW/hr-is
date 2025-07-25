<div x-data="{ step: 0 }" class="space-y-6">
    <!-- Wrapper Stepper -->
    <div class="flex items-center justify-between w-full overflow-x-auto pb-2">
        @foreach($record->form_data['phases'] as $i => $phase)
            <div class="flex items-center flex-1 min-w-[120px]">

                <!-- Step -->
                <div
                    class="relative flex flex-col items-center cursor-pointer z-10"
                    @click.stop="step = {{ $i }}"
                >
                    <div
                        class="w-10 h-10 flex items-center justify-center rounded-full border-2 transition-all"
                        :class="step >= {{ $i }}
                            ? 'bg-blue-500 text-white border-blue-500'
                            : 'bg-gray-200 text-gray-500 border-gray-300'"
                    >
                        <template x-if="step > {{ $i }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </template>
                        <template x-if="step <= {{ $i }}">
                            <span>{{ $i + 1 }}</span>
                        </template>
                    </div>
                    <span
                        class="mt-2 text-xs text-center"
                        :class="step === {{ $i }} ? 'text-blue-600 font-semibold' : 'text-gray-500'"
                    >
                        {{ $phase['name'] }}
                    </span>
                </div>

                <!-- Garis -->
                @if($i < count($record->form_data['phases']) - 1)
                    <div class="flex-1 h-1 mx-2 rounded-full"
                         :class="step > {{ $i }} ? 'bg-blue-400' : 'bg-gray-300'">
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Konten -->
    <div class="border rounded-lg p-4 bg-gray-50">
        <template x-for="(phase, index) in {{ json_encode($record->form_data['phases']) }}" :key="index">
            <div x-show="step === index" class="space-y-1">
                <p><strong>Nama:</strong> <span x-text="phase.name"></span></p>
                <p><strong>Status:</strong> <span x-text="phase.status"></span></p>
                <p><strong>Updated:</strong> <span x-text="phase.updatedAt ?? '-'"></span></p>
            </div>
        </template>
    </div>
</div>
