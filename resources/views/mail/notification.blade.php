@component('mail::message')

{{-- Logo perusahaan (inline CID) --}}
<div style="text-align:center; margin-bottom:20px;">
<img src="cid:logo_iag" alt="{{ config('app.name') }}" width="120">
</div>

{{ $greeting ?? __('emails.greeting', [], 'id') }}

{!! $messageLine !!}
@if(!empty($actionText) && !empty($actionUrl))
@component('mail::button', ['url' => $actionUrl])
{{ $actionText }}
@endcomponent
@endif

Terima Kasih,
<div><br></div>
{{ config('app.name') }}
@endcomponent
