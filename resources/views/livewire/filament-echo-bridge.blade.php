{{-- resources/views/livewire/filament-echo-bridge.blade.php --}}
<div>
    <script>
        window.addEventListener('echo:notification', (e) => {
            const n = e.detail || {};
            const status = n.status || 'info';
            const title  = n.title  || n.message || 'Update';
            const body   = n.body   || (n.description ?? '');
            Livewire.find(@this.__instance.id).call('pushToast', status, title, body);
        });
    </script>
</div>
