<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Invoice #{{ $trx->id }}</title>
<style>
  body { font-family: DejaVu Sans, sans-serif; color:#1a1a1a; font-size:12px; line-height:1.5; }
  .header { border-bottom: 3px solid #6d28d9; padding-bottom:12px; margin-bottom:20px; }
  .brand { font-size:22px; font-weight:800; color:#6d28d9; }
  .muted { color:#6b7280; }
  table { width:100%; border-collapse:collapse; margin-top:16px; }
  th { text-align:left; background:#f5f3ff; padding:8px; font-size:11px; text-transform:uppercase; letter-spacing:.05em; }
  td { padding:10px 8px; border-bottom:1px solid #e5e7eb; }
  .total { font-weight:700; font-size:14px; }
  .badge { display:inline-block; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:700; background:#dcfce7; color:#166534; }
  .footer { margin-top:32px; padding-top:12px; border-top:1px solid #e5e7eb; font-size:11px; color:#6b7280; }
</style>
</head>
<body>
  <div class="header">
    <div class="brand">BuatPRD</div>
    <div class="muted">Invoice Pembayaran — Transfer Manual</div>
    <div style="float:right; text-align:right; margin-top:-40px;">
      <div><strong>Invoice #{{ str_pad($trx->id, 6, '0', STR_PAD_LEFT) }}</strong></div>
      <div class="muted">{{ $trx->created_at->format('d M Y H:i') }} WIB</div>
      <div class="badge">{{ strtoupper($trx->status) }}</div>
    </div>
    <div style="clear:both"></div>
  </div>

  <table style="border:none; width:100%;">
    <tr>
      <td style="border:none; width:50%; vertical-align:top;">
        <strong>Tagih ke:</strong><br>
        {{ $trx->user->name }}<br>
        {{ $trx->user->email }}<br>
        <span class="muted">User ID: {{ $trx->user->id }}</span>
      </td>
      <td style="border:none; width:50%; vertical-align:top; text-align:right;">
        <strong>Diterbitkan oleh:</strong><br>
        BuatPRD Indonesia<br>
        {{ $payment['bank_holder'] ?? 'PT BuatPRD' }}<br>
        <span class="muted">support@buatprd.test</span>
      </td>
    </tr>
  </table>

  <table>
    <thead>
      <tr>
        <th>Deskripsi</th>
        <th style="text-align:right;">Jumlah</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>
          <strong>{{ $trx->type === 'subscription' ? 'Langganan Paket ' . ($trx->tier->name ?? '-') : 'Top Up Kredit' }}</strong><br>
          <span class="muted">
            @if($trx->type === 'subscription')
              Durasi {{ $trx->tier->duration_days ?? 30 }} hari • {{ number_format($trx->tier->limits['max_ai_per_month'] ?? 0) }} AI / bulan
            @else
              @php $map=[25000=>50,50000=>120,100000=>270]; @endphp
              {{ $map[$trx->amount] ?? '-' }} kredit
            @endif
            • Metode: Transfer
          </span>
          @if($trx->coupon_code)
            <br><span class="muted">Kupon: {{ $trx->coupon_code }} (-Rp {{ number_format($trx->discount_amount,0,',','.') }})</span>
          @endif
        </td>
        <td style="text-align:right;">Rp {{ number_format($trx->amount,0,',','.') }}</td>
      </tr>
      @if($trx->discount_amount > 0)
      <tr>
        <td>Diskon ({{ $trx->coupon_code }})</td>
        <td style="text-align:right; color:#16a34a;">- Rp {{ number_format($trx->discount_amount,0,',','.') }}</td>
      </tr>
      @endif
      <tr>
        <td class="total">Total Bayar</td>
        <td style="text-align:right;" class="total">Rp {{ number_format($net,0,',','.') }}</td>
      </tr>
    </tbody>
  </table>

  <div style="margin-top:20px; background:#f9fafb; padding:12px; border-radius:8px;">
    <strong>Status:</strong> {{ ucfirst($trx->status) }}
    @if($trx->status==='approved')
      • Disetujui oleh {{ $trx->approver->name ?? 'Admin' }} pada {{ $trx->approved_at?->format('d M Y H:i') }}
    @endif
    @if($trx->admin_note)
      <br><span class="muted">Catatan admin: {{ $trx->admin_note }}</span>
    @endif
  </div>

  <div class="footer">
    Dokumen ini adalah bukti pembayaran sah BuatPRD. Pembayaran via transfer manual — verifikasi 1x24 jam.<br>
    Jika ada pertanyaan hubungi support. Terima kasih telah menggunakan BuatPRD.
  </div>
</body>
</html>
