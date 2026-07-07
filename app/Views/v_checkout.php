<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="row">
    <div class="col-lg-6">
        <?= form_open('buy', 'class="row g-3"') ?>

<?= form_hidden('username', session()->get('username')) ?>

<?= form_input([
    'type' => 'hidden', 
    'name' => 'total_harga', 
    'id' => 'total_harga']) ?>

<div class="col-12">
    <?= form_label('Nama', 'nama', ['class' => 'form-label']) ?>
    <?= form_input([
        'name'     => 'nama',
        'id'       => 'nama',
        'class'    => 'form-control',
        'value'    => session()->get('username'),
        'readonly' => true]) ?>
</div>
<div class="col-12">
    <?= form_label('Alamat', 'alamat', ['class' => 'form-label']) ?>
    <?= form_input([
        'name'  => 'alamat',
        'id'    => 'alamat',
        'class' => 'form-control']) ?>
</div> 
<div class="col-12"> 
    <?= form_label('Kelurahan', 'kelurahan', ['class' => 'form-label']) ?>
    <?= form_dropdown('kelurahan', [], '', ['id' => 'kelurahan', 'class' => 'form-control']) ?>
</div>
<div class="col-12"> 
    <?= form_label('Layanan', 'layanan', ['class' => 'form-label']) ?> 
    <?= form_dropdown('layanan', [], '', ['id' => 'layanan', 'class' => 'form-control']) ?>
</div>
<div class="col-12">
    <?= form_label('Ongkir', 'ongkir', ['class' => 'form-label']) ?>
    <?= form_input([
        'name'     => 'ongkir',
        'id'       => 'ongkir',
        'class'    => 'form-control',
        'readonly' => true]) ?>
</div>
<div class="col-12">
    <?= form_label('Kode Kupon', 'kupon_code', ['class' => 'form-label']) ?>
    <?= form_input([
        'name'        => 'kupon_code',
        'id'          => 'kupon_code',
        'class'       => 'form-control',
        'placeholder' => 'Masukkan kode kupon (opsional)']) ?>
    <small class="form-text text-muted">Tersedia: HEMAT20, HEMAT30, MEMBER25</small>
    <div id="kupon_feedback" class="form-text"></div>
</div>
<div class="col-12">
    <?= form_submit(
        'submit',
        'Buat Pesanan',
        ['class' => 'btn btn-primary']) ?>
</div>

<?= form_close() ?> 
    </div>

    <div class="col-lg-6">
        <table class="table">
  <thead>
      <tr>
          <th scope="col">Nama</th>
          <th scope="col">Harga</th>
          <th scope="col">Jumlah</th>
          <th scope="col">Sub Total</th>
      </tr>
  </thead>
  <tbody>
      <?php 
      if (!empty($items)) :
          foreach ($items as $index => $item) :
      ?>
              <tr>
                  <td><?= $item['name'] ?></td>
                  <td><?= number_to_currency($item['price'], 'IDR') ?></td>
                  <td><?= $item['qty'] ?></td>
                  <td><?= number_to_currency($item['price'] * $item['qty'], 'IDR') ?></td>
              </tr>
      <?php
          endforeach;
      endif;
      ?>
      <tr>
          <td colspan="2"></td>
          <td>Subtotal</td>
          <td><?= number_to_currency($total, 'IDR') ?></td>
      </tr>
      <tr>
          <td colspan="2"></td>
          <td>Diskon Kupon</td>
          <td class="text-danger">- <span id="diskon_kupon_text"><?= number_to_currency(0, 'IDR') ?></span></td>
      </tr>
      <tr>
          <td colspan="2"></td>
          <td>PPN (12%)</td>
          <td><?= number_to_currency($ppn, 'IDR') ?></td>
      </tr>
      <tr>
          <td colspan="2"></td>
          <td>Biaya Admin</td>
          <td><?= number_to_currency($biaya_admin, 'IDR') ?></td>
      </tr>
      <tr>
          <td colspan="2"></td>
          <td><strong>Subtotal (+PPN+Admin-Kupon)</strong></td>
          <td><strong><span id="subtotal_akhir"><?= number_to_currency($total - $diskon_kupon + $ppn + $biaya_admin, 'IDR') ?></span></strong></td>
      </tr>
      <tr>
          <td colspan="2"></td>
          <td>Grand Total (incl. Ongkir)</td>
          <td><span id="total"><?= number_to_currency($total - $diskon_kupon + $ppn + $biaya_admin, 'IDR') ?></span></td>
      </tr>
  </tbody>
</table>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('script') ?>
<script>
$(document).ready(function() {
    let ongkir = 0;
    let subtotal = <?= $total ?>;
    let ppn = <?= $ppn ?>;
    let biayaAdmin = <?= $biaya_admin ?>;
    let diskonKupon = 0;
    let kuponTimer = null;

    hitungTotal();

    function hitungTotal() {
        let subtotalAkhir = subtotal - diskonKupon + ppn + biayaAdmin;
        let grandTotal = subtotalAkhir + ongkir;

        $("#ongkir").val(ongkir);
        $("#diskon_kupon_text").text(`IDR ${diskonKupon.toLocaleString('id-ID')}`);
        $("#subtotal_akhir").text(`IDR ${subtotalAkhir.toLocaleString('id-ID')}`);
        $("#total").text(`IDR ${grandTotal.toLocaleString('id-ID')}`);
        $("#total_harga").val(grandTotal);
    }

    function cekKupon() {
        let kode = $("#kupon_code").val().trim();

        if (kode === '') {
            diskonKupon = 0;
            $("#kupon_feedback").removeClass('text-success text-danger').text('');
            hitungTotal();
            return;
        }

        $.ajax({
            url: "<?= site_url('ajax/validate-kupon') ?>",
            method: "POST",
            dataType: "json",
            data: {
                kupon_code: kode,
                <?= csrf_token() ?>: '<?= csrf_hash() ?>'
            },
            success: function (data) {
                if (data.valid) {
                    diskonKupon = parseFloat(data.diskon_kupon);
                    $("#kupon_feedback")
                        .removeClass('text-danger')
                        .addClass('text-success')
                        .text('Kupon valid: diskon diterapkan.');
                } else {
                    diskonKupon = 0;
                    $("#kupon_feedback")
                        .removeClass('text-success')
                        .addClass('text-danger')
                        .text('Kode kupon tidak valid.');
                }
                hitungTotal();
            }
        });
    }

    $("#kupon_code").on('keyup', function () {
        clearTimeout(kuponTimer);
        kuponTimer = setTimeout(cekKupon, 500);
    });

	$('#kelurahan').select2({
	    placeholder: 'Cari daerah tujuan',
	    minimumInputLength: 3, 
        ajax: {
    url: '<?= site_url('ajax/destinations') ?>',
    dataType: 'json',
    delay: 300,
    data: function(params) {
        return {
            q: params.term
        };
    },
    processResults: function(data) {
        return data;
    },
    cache: true
    }
	});

    $("#kelurahan").on('change', function () {
        let id_kelurahan = $(this).val();

        $("#layanan").empty();
        ongkir = 0;
        hitungTotal(); 
        $.ajax({
        url: "<?= site_url('ajax/costs') ?>", 
        dataType: "json",
        data: {
            destination: id_kelurahan
        },
        success: function (data) { 
            data.forEach(function (item) {
                $("#layanan").append(
                    $('<option>', {
                        value: item.cost,
                        text: `${item.description} (${item.service}) : estimasi ${item.etd}`
                    })
                );
            });
        }
});
});

$("#layanan").on('change', function() {
    ongkir = parseInt($(this).val());
    hitungTotal();
}); 
    
});
</script>
<?= $this->endSection() ?>
