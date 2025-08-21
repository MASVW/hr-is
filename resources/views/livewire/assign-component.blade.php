<div class="w-full max-w-2xl rounded-2xl border border-black/5 dark:border-white/10
            bg-white/90 dark:bg-white/5 backdrop-blur shadow-card">
    {{-- aksen primary di atas card --}}
    <div class="h-1.5 rounded-t-2xl bg-gradient-to-r from-primary/60 via-primary to-primary/60"></div>

    <div class="p-6 md:p-8">
        {{-- Header --}}
        <div class="mb-5">
            <h1 class="text-2xl font-semibold tracking-tight">Assign PIC</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-white/70">
                Rekrutmen:
                <span class="font-medium">{{ $recruitment->title }}</span>
                @if($recruitment->department)
                    • Departemen:
                    <span class="font-medium">{{ $recruitment->department->name }}</span>
                @endif
            </p>
        </div>

        {{-- Flash success --}}
        @if (session('success'))
            <div class="mb-4 rounded-xl border border-green-200/60 bg-green-50 px-4 py-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Form --}}
        <form wire:submit.prevent="save" class="space-y-4">
            <label class="block">
                <span class="text-sm font-medium text-slate-700 dark:text-white">Pilih PIC (HR Staff)</span>
                <select
                    @disabled(!is_null($pic_id))
                    wire:model="pic_id"
                    required
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                >
                    <option value="" disabled @selected(!$pic_id)>— Pilih PIC —</option>
                    @foreach($hrStaff as $staff)
                        <option value="{{ $staff['id'] }}">
                            {{ $staff['name'] }} @if(!empty($staff['email']))— {{ $staff['email'] }}@endif
                        </option>
                    @endforeach
                </select>
            </label>

            @error('pic_id')
            <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="pt-2">
                <button
                    @disabled(!is_null($pic_id))
                    type="submit"
                        class="inline-flex items-center gap-2 rounded-xl px-4 py-2
                        border border-white/30
                           text-white bg-zinc-400
                           {{is_null($this->pic_id) ? 'bg-gradient-to-r from-primary-950 via-primary to-primary hover:bg-gradient-to-l hover:from-zinc-400/85 hover:via-primary hover:to-white/60
                           hover:scale-105 transition' : '' }}
                           focus:outline-none focus:ring-2 focus:ring-primary">
                    Tetapkan PIC
                </button>
            </div>
        </form>

        {{-- Empty state --}}
        @if (empty($hrStaff))
            <p class="mt-4 text-sm rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-amber-800">
                Tidak ada HR Staff pada departemen HUMAN RESOURCE. Mohon cek role & department.
            </p>
        @endif
    </div>
</div>
