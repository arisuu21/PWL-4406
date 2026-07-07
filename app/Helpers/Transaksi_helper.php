<?php
/**
 * TransaksiHelper.php
 * Helper untuk perhitungan tambahan pada proses checkout:
 * PPN, Biaya Admin (tarif berjenjang), dan Diskon Kupon Promo.
 */

if (! function_exists('hitung_ppn')) {
    /**
     * Hitung PPN 12% dari total harga pembelian (tidak termasuk ongkir).
     *
     * @param float $total_harga
     * @return float
     */
    function hitung_ppn(float $total_harga): float
    {
        $tarif_ppn = 0.12;

        return $total_harga * $tarif_ppn;
    }
}

if (! function_exists('hitung_biaya_admin')) {
    /**
     * Hitung biaya admin berdasarkan total harga pembelian dengan tarif berjenjang:
     * - <= Rp 15.000.000        : 0.5%
     * - Rp 15.000.001 - 35.000.000 : 0.7%
     * - > Rp 35.000.000         : 0.9%
     *
     * @param float $total_harga
     * @return float
     */
    function hitung_biaya_admin(float $total_harga): float
    {
        if ($total_harga <= 15000000) {
            $tarif = 0.005;
        } elseif ($total_harga <= 35000000) {
            $tarif = 0.007;
        } else {
            $tarif = 0.009;
        }

        return $total_harga * $tarif;
    }
}

if (! function_exists('hitung_diskon_kupon')) {
    /**
     * Hitung diskon kupon promo dari total harga pembelian (sebelum PPN & biaya admin).
     * Jika kode kupon tidak valid, diskon = 0.
     *
     * @param float  $total_harga
     * @param string|null $kupon_code
     * @return float
     */
    function hitung_diskon_kupon(float $total_harga, ?string $kupon_code): float
    {
        $daftar_kupon = [
            'HEMAT20'  => 0.20,
            'HEMAT30'  => 0.30,
            'MEMBER25' => 0.25,
        ];

        if (empty($kupon_code)) {
            return 0;
        }

        $kode = strtoupper(trim($kupon_code));

        if (! array_key_exists($kode, $daftar_kupon)) {
            return 0;
        }

        return $total_harga * $daftar_kupon[$kode];
    }
}

if (! function_exists('is_kupon_valid')) {
    /**
     * Cek apakah kode kupon valid. Berguna untuk menampilkan pesan
     * "kode tidak valid" di controller/view tanpa harus menduplikasi daftar kupon.
     *
     * @param string|null $kupon_code
     * @return bool
     */
    function is_kupon_valid(?string $kupon_code): bool
    {
        $daftar_kupon = ['HEMAT20', 'HEMAT30', 'MEMBER25'];

        if (empty($kupon_code)) {
            return false;
        }

        return in_array(strtoupper(trim($kupon_code)), $daftar_kupon, true);
    }
}
