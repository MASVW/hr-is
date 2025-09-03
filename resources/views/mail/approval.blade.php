@component('mail::message')
<div style="text-align:center; margin-bottom:20px;">
<img src="cid:logo_iag" alt="{{ config('app.name') }}" width="120" style="display:inline-block;">
</div>

@php
$greet = trim($greeting ?? __('emails.greeting', [], 'id'));
$c     = $ctx ?? [];
$komp  = $c['kompensasi'] ?? [];
$qual  = $c['kualifikasi'] ?? [];
$desc  = $c['deskripsi'] ?? [];
@endphp

@if($greet !== '')
<p style="margin:0 0 12px 0;">{{ $greet }}</p>
@endif

<p style="margin:0 0 18px 0; white-space:pre-line;">{{ $messageLine }}</p>

{{-- Ringkasan PTK --}}
<h3 style="margin:10px 0 6px; font-size:16px;">Ringkasan Permintaan Tenaga Kerja</h3>
<table role="presentation" width="100%" cellpadding="8" cellspacing="0"
style="border-collapse:collapse; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden;">
<tbody>
<tr style="background:#f8fafc;">
<td style="width:38%; border-bottom:1px solid #e2e8f0; font-weight:600;">Departemen</td>
<td style="border-bottom:1px solid #e2e8f0;">{{ $c['department'] ?? '—' }}</td>
</tr>
<tr>
<td style="font-weight:600;">Jabatan</td>
<td>{{ $c['jabatan'] ?? '—' }}</td>
</tr>
<tr style="background:#f8fafc;">
<td style="font-weight:600;">Tipe Rekrutmen</td>
<td>{{ $c['tipeRekrutmen'] ?? '—' }}</td>
</tr>
<tr>
<td style="font-weight:600;">Urgensi</td>
<td>{{ $c['urgency'] ?? '—' }}</td>
</tr>
<tr style="background:#f8fafc;">
<td style="font-weight:600;">Jumlah Karyawan</td>
<td>{{ $c['jumlahKaryawan'] ?? '—' }}</td>
</tr>
<tr>
<td style="font-weight:600;">Lokasi Penempatan</td>
<td>{{ $c['lokasiPenempatan'] ?? '—' }}</td>
</tr>
</tbody>
</table>

{{-- Kompensasi --}}
<h3 style="margin:14px 0 6px; font-size:16px;">Kompensasi</h3>
<table role="presentation" width="100%" cellpadding="8" cellspacing="0"
style="border-collapse:collapse; border:1px solid #e2e8f0; border-radius:8px;">
<tbody>
<tr style="background:#f8fafc;">
<td style="width:38%; border-bottom:1px solid #e2e8f0; font-weight:600;">Gaji</td>
<td style="border-bottom:1px solid #e2e8f0;">{{ $komp['gaji'] ?? '—' }}</td>
</tr>
<tr>
<td style="font-weight:600;">Hari Kerja</td>
<td>{{ $komp['hariKerja'] ?? '—' }}</td>
</tr>
<tr style="background:#f8fafc;">
<td style="font-weight:600;">Tunjangan Makan</td>
<td>{{ $komp['tunjanganMakan'] ?? '—' }}</td>
</tr>
<tr>
<td style="font-weight:600;">Tunjangan Transport</td>
<td>{{ $komp['tunjanganTransport'] ?? '—' }}</td>
</tr>
<tr style="background:#f8fafc;">
<td style="font-weight:600;">Tunjangan Komunikasi</td>
<td>{{ $komp['tunjanganKomunikasi'] ?? '—' }}</td>
</tr>
<tr>
<td style="font-weight:600;">Tunjangan Perumahan</td>
<td>{{ $komp['tunjanganPerumahan'] ?? '—' }}</td>
</tr>
</tbody>
</table>

{{-- Kualifikasi --}}
<h3 style="margin:14px 0 6px; font-size:16px;">Kualifikasi</h3>
<table role="presentation" width="100%" cellpadding="8" cellspacing="0"
style="border-collapse:collapse; border:1px solid #e2e8f0; border-radius:8px;">
<tbody>
<tr style="background:#f8fafc;">
<td style="width:38%; border-bottom:1px solid #e2e8f0; font-weight:600;">Pendidikan</td>
<td style="border-bottom:1px solid #e2e8f0;">{{ $qual['pendidikan'] ?? '—' }}</td>
</tr>
<tr>
<td style="font-weight:600;">Pengalaman</td>
<td>{{ $qual['pengalaman'] ?? '—' }}</td>
</tr>
<tr style="background:#f8fafc;">
<td style="font-weight:600;">Jenis Kelamin</td>
<td>{{ $qual['jenisKelamin'] ?? '—' }}</td>
</tr>
<tr>
<td style="font-weight:600;">Status</td>
<td>{{ $qual['status'] ?? '—' }}</td>
</tr>
<tr style="background:#f8fafc;">
<td style="font-weight:600;">Agama</td>
<td>{{ $qual['agama'] ?? '—' }}</td>
</tr>
<tr>
<td style="font-weight:600;">Nilai Plus</td>
<td>{{ $qual['nilaiPlus'] ?? '—' }}</td>
</tr>
<tr style="background:#f8fafc;">
<td style="font-weight:600;">Kemampuan Lainnya</td>
<td>{{ $qual['kemampuanLainnya'] ?? '—' }}</td>
</tr>
</tbody>
</table>

{{-- Deskripsi --}}
<h3 style="margin:14px 0 6px; font-size:16px;">Deskripsi Pekerjaan</h3>
<p style="margin:0 0 6px; line-height:1.6;"><strong>Umum:</strong> {{ $desc['umum'] ?? '—' }}</p>
<p style="margin:0 0 10px; line-height:1.6;"><strong>Khusus:</strong> {{ $desc['khusus'] ?? '—' }}</p>

@if(!empty($expiresAt))
<p style="margin:0 0 12px; font-size:12px; color:#475569;">Tautan persetujuan berlaku hingga {{ $expiresAt }}
.</p>
@endif

@if(!empty($approveUrl) || !empty($rejectUrl))
<table role="presentation" align="center" cellpadding="0" cellspacing="0" style="margin:12px auto 4px;">
<tr>
@if(!empty($approveUrl))
<td align="center" style="padding:0 6px;">
<a href="{{ $approveUrl }}" target="_blank"
style="display:inline-block; padding:10px 18px; background-color:#16a34a; color:#ffffff;
text-decoration:none; border-radius:6px; -webkit-text-size-adjust:none; box-sizing:border-box; font-weight:600;">
Setujui
</a>
</td>
@endif
@if(!empty($rejectUrl))
<td align="center" style="padding:0 6px;">
<a href="{{ $rejectUrl }}" target="_blank"
style="display:inline-block; padding:10px 18px; background-color:#ef4444; color:#ffffff;
text-decoration:none; border-radius:6px; -webkit-text-size-adjust:none; box-sizing:border-box; font-weight:600;">
Tolak
</a>
</td>
@endif
</tr>
</table>
@endif

@if(config('app.name'))
<p style="margin-top:16px;">Terima kasih,<br>{{ config('app.name') }}</p>
@endif
@endcomponent
