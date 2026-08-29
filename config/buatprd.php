<?php

return [
    'payment' => [
        'bank_name' => env('BUATPRD_BANK_NAME', 'BCA'),
        'bank_number' => env('BUATPRD_BANK_NUMBER', '1234567890'),
        'bank_holder' => env('BUATPRD_BANK_HOLDER', 'PT BuatPRD Indonesia'),
        'bank_name_2' => env('BUATPRD_BANK_NAME_2', 'Mandiri'),
        'bank_number_2' => env('BUATPRD_BANK_NUMBER_2', '0987654321'),
        'bank_holder_2' => env('BUATPRD_BANK_HOLDER_2', 'PT BuatPRD Indonesia'),
        'instructions' => 'Transfer tepat sesuai nominal (termasuk kode unik jika ada). Upload bukti JPG/PNG max 2MB. Verifikasi 1x24 jam.',
    ],
    'topup_options' => [
        ['amount' => 25000, 'credits' => 50, 'label' => '50 Kredit', 'bonus' => 0],
        ['amount' => 50000, 'credits' => 120, 'label' => '120 Kredit', 'bonus' => 20],
        ['amount' => 100000, 'credits' => 270, 'label' => '270 Kredit', 'bonus' => 70],
    ],
];
