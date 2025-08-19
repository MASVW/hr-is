@component('mail::message')
    # {{ $greeting }}

    {{ $messageLine }}

    @if($actionText && $actionUrl)
        @component('mail::button', ['url' => $actionUrl])
            {{ $actionText }}
        @endcomponent
    @endif

    Terima kasih,<br>
    {{ config('app.name') }}
@endcomponent
