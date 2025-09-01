<div class="w-full max-w-2xl rounded-2xl border border-black/5 dark:border-white/10
            bg-white/90 dark:bg-white/5 backdrop-blur shadow-card">
    <div class="h-1.5 rounded-t-2xl bg-gradient-to-r from-primary/60 via-primary to-primary/60"></div>

    <div class="p-6 md:p-8">
        <div class="mb-5">
            <h1 class="text-2xl font-semibold tracking-tight">Keputusan Dirut — Tolak Rekrutmen</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-white/70">
                Rekrutmen:
                <span class="font-medium">{{ $recruitment->title }}</span>
                @if($recruitment->department)
                    • Departemen:
                    <span class="font-medium">{{ $recruitment->department->name }}</span>
                @endif
            </p>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-xl border border-green-200/60 bg-green-50 px-4 py-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit.prevent="save" class="space-y-4">
            <label class="block">
                <span class="text-sm font-medium text-slate-700 dark:text-white">Alasan Penolakan</span>
                <textarea
                    wire:model.defer="note"
                    required
                    rows="5"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                           focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5
                           dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400
                           dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    placeholder="Tuliskan alasan penolakan di sini..."></textarea>
            </label>

            @error('note')
            <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="pt-2">
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 border border-white/30 focus:outline-none focus:ring-2 focus:ring-primary
                           text-white bg-gradient-to-r from-red-800 via-red-600 to-red-500 hover:bg-gradient-to-l hover:scale-105 transition">
                    Simpan Penolakan
                </button>
            </div>
        </form>
    </div>
</div>
