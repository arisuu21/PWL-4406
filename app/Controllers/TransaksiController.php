<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\RajaOngkirService;

use App\Models\TransactionModel;
use App\Models\TransactionDetailModel;

class TransaksiController extends BaseController
{
    protected $cart;
    protected $transactionModel;
    protected $transactionDetailModel;

    public function __construct()
    {
        helper(['number', 'form', 'transaksi']); // 'transaksi' = helper baru (hitung PPN, biaya admin, kupon)
        $this->cart = service('cart');
        $this->transactionModel = new TransactionModel();
        $this->transactionDetailModel = new TransactionDetailModel(); 
    }
    public function index()
{  
    $data = [
        'items' => $this->cart->contents() , 
        'total' => $this->cart->total() 
    ];

    return view('v_keranjang', $data);
}

public function cart_add()
{
	$this->cart->insert([
	    'id'      => $this->request->getPost('id'),
	    'qty'     => 1,
	    'price'   => $this->request->getPost('harga'),
	    'name'    => $this->request->getPost('nama'),
	    'options' => [
	        'foto' => $this->request->getPost('foto')
	    ]
	]);
	
	session()->setFlashdata(
	    'success',
	    'Produk berhasil ditambahkan ke keranjang. 
	    <a href="' . base_url('keranjang') . '">Lihat</a>'
	);
	
	return redirect()->to(base_url('/'));
} 

public function cart_edit()
{
    $i = 1;
    foreach ($this->cart->contents() as $item) {
        $qty = $this->request->getPost('qty' . $i++);

        $this->cart->update([
            'rowid' => $item['rowid'],
            'qty'   => $qty
        ]);
    }

    session()->setFlashdata(
        'success',
        'Keranjang berhasil diperbarui'
    );

    return redirect()->to(base_url('keranjang'));
}

public function cart_delete($rowid)
{
    $this->cart->remove($rowid);

    session()->setFlashdata(
        'success',
        'Produk berhasil dihapus dari keranjang'
    );

    return redirect()->to(base_url('keranjang'));
}

public function cart_clear()
{
    $this->cart->destroy();

    session()->setFlashdata(
        'success',
        'Keranjang berhasil dikosongkan'
    );

    return redirect()->to(base_url('keranjang'));
}

public function checkout()
{  
    $service = new RajaOngkirService();
    $response = $service->getDestination('semarang');
    $response2 = $service->getCost('64999','65042','1000','jne');

    // total harga pembelian (belum termasuk ongkir) -> dasar hitung PPN, biaya admin, diskon kupon
    $total_harga = $this->cart->total();

    $data = [
        'items' => $this->cart->contents(),
        'total' => $this->cart->total(),
        'response' => $response,
        'response2' => $response2,

        // preview perhitungan tambahan (kupon masih kosong, nanti divalidasi via AJAX ajax/validate-kupon)
        'ppn'          => hitung_ppn($total_harga),
        'biaya_admin'  => hitung_biaya_admin($total_harga),
        'diskon_kupon' => 0,
    ];

    return view('v_checkout', $data);
}

public function destinations()
{
    $search = $this->request->getGet('q'); 

    if (empty($search)) {
    $results = [
        'id'   => $search,
        'text' => $search
    ];

    return $this->response->setJSON([
        'results' => $results
    ]);
}

$service = new RajaOngkirService();
$response = $service->getDestination($search);

$results = [];
$data = $response['data'] ?? [];

foreach ($data as $item) {
    $results[] = [
        'id'   => $item['id'],
        'text' => $item['label']
    ];
}

    return $this->response->setJSON([
        'results' => $results
    ]);

    $service = new RajaOngkirService();
    $response = $service->getDestination($search);

    $results = [];
    $data = $response['data'] ?? [];

    foreach ($data as $item) {
        $results[] = [
            'id'   => $item['id'],
            'text' => $item['label']
        ];
    }
}

public function costs()
{
    $origin = '64999';
    $destination = $this->request->getGet('destination');
    $weight = '1000';
    $courier = 'jne'; 

    $service = new RajaOngkirService();
    $response = $service->getCost($origin, $destination, $weight, $courier);

    $results = [];
    $data = $response['data'] ?? [];

    foreach ($data as $item) {
        $results[] = [
            'service'     => $item['service'],
            'description' => $item['description'],
            'cost'        => $item['cost'],
            'etd'         => $item['etd']
        ];
    }

    return $this->response->setJSON($results);
}

/**
 * Endpoint AJAX: validasi kode kupon secara real-time di halaman checkout.
 * Dipanggil dari route POST ajax/validate-kupon.
 */
public function validateKupon()
{
    $kupon_code  = $this->request->getPost('kupon_code');
    $total_harga = $this->cart->total(); // dasar hitung diskon = total harga produk, sebelum PPN & biaya admin

    $valid        = is_kupon_valid($kupon_code);
    $diskon_kupon = hitung_diskon_kupon($total_harga, $kupon_code);

    return $this->response->setJSON([
        'valid'        => $valid,
        'kupon_code'   => $valid ? strtoupper(trim($kupon_code)) : null,
        'diskon_kupon' => $diskon_kupon,
    ]);
}

public function buy()
{ 
    $cartItems = $this->cart->contents();

    if (empty($cartItems)) {
        return redirect()->back();
    }

    $db = \Config\Database::connect();
    $db->transStart(); 

    $subtotal = 0;
    foreach ($cartItems as $item) {
        $subtotal += $item['qty'] * $item['price'];
    }

    $ongkir     = (int) $this->request->getPost('ongkir');
    $kupon_code = $this->request->getPost('kupon_code');

    // total harga pembelian (produk saja, belum ongkir) = dasar hitung PPN, biaya admin, diskon kupon
    $total_harga = $subtotal;

    $diskon_kupon = hitung_diskon_kupon($total_harga, $kupon_code);
    $kupon_valid  = is_kupon_valid($kupon_code);
    $ppn          = hitung_ppn($total_harga);
    $biaya_admin  = hitung_biaya_admin($total_harga);

    // grand total = total harga - diskon kupon + PPN + biaya admin + ongkir
    $grand_total = $total_harga - $diskon_kupon + $ppn + $biaya_admin + $ongkir;

    $transaction = [
        'username'      => $this->request->getPost('username'),
        'alamat'        => $this->request->getPost('alamat'),
        'ongkir'        => $ongkir,
        'total_harga'   => $grand_total,
        'ppn'           => $ppn,
        'biaya_admin'   => $biaya_admin,
        'kupon_code'    => $kupon_valid ? strtoupper(trim($kupon_code)) : null,
        'diskon_kupon'  => $diskon_kupon,
        'status'        => 0, 
    ];

    // insert transaction
    if (!$this->transactionModel->insert($transaction)) {
        $db->transRollback();
        return redirect()->back()->with('error', 'Gagal membuat transaksi');
    }

    $transactionId = $this->transactionModel->getInsertID();

    // insert transaction detail
    foreach ($cartItems as $item) {
        $this->transactionDetailModel->insert([
            'transaction_id' => $transactionId,
            'product_id'     => $item['id'],
            'jumlah'         => $item['qty'],
            'diskon'         => 0,
            'subtotal_harga' => $item['qty'] * $item['price'] 
        ]);
    }

    $db->transComplete();

    if (!$db->transStatus()) {
        return redirect()->back()->with('error', 'Gagal membuat transaksi');
    }

		//hapus session keranjang belanja 
    $this->cart->destroy();
    return redirect()->to(base_url());
}

public function history()
{
    $username = session()->get('username'); 
 
    $transactions = $this->transactionModel->where('username', $username)->findAll();
    $transactionIds = array_column($transactions, 'id');

    $products = $this->transactionDetailModel->getProductsByTransactionIds($transactionIds);

    $data = [
        'username'      => $username,
        'transactions'  => $transactions,
        'products'      => $products
    ]; 

    return view('v_history', $data);
}

}
