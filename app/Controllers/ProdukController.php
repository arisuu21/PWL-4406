<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

use App\Models\ProductModel;
use Dompdf\Dompdf;

class ProdukController extends BaseController
{
    
    protected $model;  
    private $token;

    function __construct()
    { 
        $this->model = new ProductModel(); 
        $this->token = env('MY_API_KEY');
    }

    private function authenticate()
{
    $header = $this->request->getHeaderLine('Authorization');

    if (empty($header)) {
        return false;
    }

    if (!preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
        return false;
    }

    return $matches[1] === $this->token;
}

private function unauthorized()
{
    return $this->respond([
        'status'  => false,
        'message' => 'Unauthorized'
    ], 401);
}
 
public function index()
{
    if (!$this->authenticate()) {
        return $this->unauthorized();
    }

    $page = (int) ($this->request->getGet('page') ?? 1);
    $perPage = (int) ($this->request->getGet('per_page') ?? 10);

    $products = $this->model->paginate($perPage, 'default', $page);

    return $this->respond([
        'data' => $products,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $perPage,
            'last_page'    => $this->model->pager->getPageCount(),
            'total_data'   => $this->model->pager->getTotal(),
            'has_next'     => $page < $this->model->pager->getPageCount(),
            'has_prev'     => $page > 1,
        ]
    ]);
}

    public function create()
{
    $dataFoto = $this->request->getFile('foto');

    $dataForm = [
        'nama' => $this->request->getPost('nama'),
        'harga' => $this->request->getPost('harga'),
        'jumlah' => $this->request->getPost('jumlah') 
    ];

    if ($dataFoto->isValid()) {
        $fileName = $dataFoto->getRandomName(); 
        $dataFoto->move('img/', $fileName);
        
        $dataForm['foto'] = $fileName;
    }

    $this->productModel->insert($dataForm);

    return redirect('produk')->with('success', 'Data Berhasil Ditambah');
} 



public function edit($id)
{
    $dataFoto = $this->request->getFile('foto');

    $dataForm = [
        'nama' => $this->request->getPost('nama'),
        'harga' => $this->request->getPost('harga'),
        'jumlah' => $this->request->getPost('jumlah') 
    ];

    // logic foto (sama seperti create)
    if ($dataFoto->isValid()) {
        $fileName = $dataFoto->getRandomName(); 
        $dataFoto->move('img/', $fileName);
        $dataForm['foto'] = $fileName;
    }

    $this->productModel->update($id, $dataForm);

    return redirect('produk')->with('success', 'Data Berhasil Diupdate');
} 

public function delete($id)
{
    $dataProduk = $this->productModel->find($id);
    $this->productModel->delete($id);

    return redirect('produk')->with('success', 'Data Berhasil Dihapus');
}
    
public function download()
{
    // Ambil data produk dari database
    $products = $this->productModel->findAll();

    // Render view menjadi HTML
    $html = view('produk/download_pdf', [
        'products' => $products
    ]);

    // Nama file PDF
    $filename = date('Y-m-d-H-i-s') . '-produk.pdf';

    // Inisialisasi Dompdf
    $dompdf = new Dompdf();

    // Load HTML ke Dompdf
    $dompdf->loadHtml($html);

    // Setting ukuran kertas dan orientasi
    $dompdf->setPaper('A4', 'portrait');

    // Generate PDF
    $dompdf->render();

    // Download / tampilkan PDF
    $dompdf->stream($filename, [
        'Attachment' => true
    ]);
}



}


