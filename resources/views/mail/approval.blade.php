@component('mail::message')
    # {{ $greeting }}

    {{ $messageLine }}

    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-right:10px;">
                @component('mail::button', ['url' => $approveUrl, 'color' => 'success'])
                    Approve
                @endcomponent
            </td>
            <td>
                @component('mail::button', ['url' => $rejectUrl, 'color' => 'error'])
                    Reject
                @endcomponent
            </td>
        </tr>
    </table>

    Terima kasih,<br>
    {{ config('app.name') }}
@endcomponent
