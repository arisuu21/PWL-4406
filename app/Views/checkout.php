<?= $this->extend('layout') ?> <?php // sesuaikan dengan layout project kamu ?>

<?= $this->section('content') ?>

<div class="container mt-4">
    <h3>Checkout</h3>

    <?php
        // Perhitungan preview (real-time di server saat halaman di-load ulang / sebelum submit).
        // Perhitungan final & penyimpanan tetap dilakukan di Checkout::buatPesanan().
        $kupon_input  = old('kupon_code', '');
        $ongkir_input = (float) old('ongkir', 0);

        $diskon_kupon = hitung_diskon_kupon($total_harga, $kupon_input);
        $ppn          = hitung_ppn($total_harga);
        $biaya_admin  = hitung_biaya_admin($total_harga);
        $subtotal     = $total_harga - $diskon_kupon + $ppn + $biaya_admin;
        $grand_total  = $subtotal + $ongkir_input;
    ?>

    <div class="row">
        <!-- Detail Pesanan -->
        <div class="col-md-6">
            <h5>Detail Pesanan</h5>
            <form action="<?= base_url('checkout/proses') ?>" method="post">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" name="nama" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Alamat</label>
                    <input type="text" name="alamat" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kelurahan</label>
                    <input type="text" name="kelurahan" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Layanan</label>
                    <input type="text" name="layanan" class="form-control" placeholder="JNE City Courier (CTCSPS)">
                </div>

                <div class="mb-3">
                    <label class="form-label">Ongkir</label>
                    <input type="number" name="ongkir" id="ongkir" class="form-control" value="<?= esc($ongkir_input) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kode Kupon</label>
                    <input type="text" name="kupon_code" class="form-control" value="<?= esc($kupon_input) ?>" placeholder="Masukkan kode kupon (opsional)">
                    <small class="text-muted">Tersedia: HEMAT20, HEMAT30, MEMBER25</small>
                    <?php if (! empty($kupon_input) && ! is_kupon_valid($kupon_input)): ?>
                        <div class="text-danger small mt-1">Kode kupon tidak valid.</div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Buat Pesanan</button>
            </form>
        </div>

        <!-- Ringkasan Pesanan -->
        <div class="col-md-6">
            <h5>Ringkasan Pesanan</h5>
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Harga</th>
                        <th>Jumlah</th>
                        <th>Sub Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart as $item): ?>
                        <tr>
                            <td><?= esc($item['nama']) ?></td>
                            <td>IDR <?= number_format($item['harga'], 0, ',', '.') ?></td>
                            <td><?= (int) $item['jumlah'] ?></td>
                            <td>IDR <?= number_format($item['harga'] * $item['jumlah'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <table class="table">
                <tr>
                    <td>Subtotal</td>
                    <td class="text-end">IDR <?= number_format($total_harga, 0, ',', '.') ?></td>
                </tr>
                <tr class="text-danger">
                    <td>Diskon Kupon</td>
                    <td class="text-end">-IDR <?= number_format($diskon_kupon, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td>PPN (12%)</td>
                    <td class="text-end">IDR <?= number_format($ppn, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td>Biaya Admin</td>
                    <td class="text-end">IDR <?= number_format($biaya_admin, 0, ',', '.') ?></td>
                </tr>
                <tr class="text-success fw-bold">
                    <td>Subtotal (+PPN+Admin-Kupon)</td>
                    <td class="text-end">IDR <?= number_format($subtotal, 0, ',', '.') ?></td>
                </tr>
                <tr class="fw-bold">
                    <td>Grand Total (incl. Ongkir)</td>
                    <td class="text-end">IDR <?= number_format($grand_total, 0, ',', '.') ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
