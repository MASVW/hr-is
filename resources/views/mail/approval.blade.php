@component('mail::message')
<div style="text-align:center; margin-bottom:20px;">
<img src="cid:logo_iag" alt="{{ config('app.name') }}" width="120">
</div>

@php $greet = trim($greeting ?? __('emails.greeting', [], 'id')); @endphp
@if($greet !== '')
<p style="margin:0 0 12px 0;">{{ $greet }}</p>
@endif

<p style="margin:0 0 18px 0; white-space:pre-line;">{{ $messageLine }}</p>

@if(!empty($approveUrl) || !empty($rejectUrl))
<table role="presentation" align="center" cellpadding="0" cellspacing="0" style="margin:10px auto;">
<tr>
@if(!empty($approveUrl))
<td align="center" style="padding:0 6px;">
<a href="{{ $approveUrl }}" target="_blank"
style="display:inline-block; padding:10px 18px; background-color:#22BC66; color:#ffffff;
text-decoration:none; border-radius:4px; -webkit-text-size-adjust:none; box-sizing:border-box;">
Approve
</a>
</td>
@endif
@if(!empty($rejectUrl))
<td align="center" style="padding:0 6px;">
<a href="{{ $rejectUrl }}" target="_blank"
style="display:inline-block; padding:10px 18px; background-color:#FF6136; color:#ffffff;
text-decoration:none; border-radius:4px; -webkit-text-size-adjust:none; box-sizing:border-box;">
Reject
</a>
</td>
@endif
</tr>
</table>
@endif

Terima Kasih,
<div><br></div>
{{ config('app.name') }}
@endcomponent
