<div
    x-data="{
        step: 0,
        phases: @js($record->form_data['phases']),
        init() {
            this.step = this.phases.findIndex(p => p.status === 'progress');

            if (this.step === -1) {
                let lastFinished = this.phases.map(p => p.status).lastIndexOf('finish');
                this.step = lastFinished !== -1 ? lastFinished + 1 : 0;
            }
        }
    }"
    x-init="init()"
    class="space-y-2"
>
    <!-- Stepper Header -->
    <div class="flex items-center justify-between w-full overflow-x-auto pb-2">
        <template x-for="(phase, i) in phases" :key="i">
            <div class="flex items-center flex-1 min-w-[120px]">
                <!-- Step Button -->
                <div
                    class="relative flex flex-col items-center cursor-pointer z-10"
                    @click.stop="step = i"
                >
                    <div
                        class="w-10 h-10 flex items-center justify-center rounded-full border-2 transition-colors duration-200"
                        :class="phase.status === 'finish'
                            ? 'bg-primary-600 text-white border-primary-600'
                            : (step === i
                                ? 'bg-primary-100 text-primary-700 border-primary-600'
                                : 'bg-gray-300 text-gray-500 border-gray-300')"
                    >
                        <template x-if="phase.status === 'finish'">
                            <x-filament::icon icon="heroicon-o-check" class="w-5 h-5" />
                        </template>
                        <template x-if="phase.status !== 'finish'">
                            <span class="text-sm font-medium" x-text="i + 1"></span>
                        </template>
                    </div>
                </div>

                <!-- Connector Line -->
                <template x-if="i < phases.length - 1">
                    <div
                        class="flex-1 h-1 mx-2 rounded-full transition-colors duration-200"
                        :class="i < step ? 'bg-primary-400' : 'bg-gray-200'"
                    ></div>
                </template>
            </div>
        </template>
    </div>

    <!-- Step Content -->
    <div class="p-4 space-y-4">
        <template x-for="(phase, index) in phases" :key="index">
            <div x-show="step === index" class="space-y-3">
                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Fase Terbaru: <span class="capitalize text-sm text-black font-bold" x-text="phase.name"></span>
                    </p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Status:
                        <span
                            x-text="phase.status"
                            class="capitalize fi-badge fi-badge-size-sm inline-flex items-center justify-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset fi-color-success"
                        ></span>
                    </p>
                </div>

                <div>
                        <p class="text-sm font-medium text-gray-500">Notes:  <span x-text="phase.notes ?? '-'"></span></p>
                </div>
            </div>
        </template>
    </div>
</div>
