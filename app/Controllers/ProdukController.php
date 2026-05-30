<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

use App\Models\ProductModel;

class ProdukController extends BaseController
{
    protected $productModel; 

    function __construct()
    {
    $this->productModel = new ProductModel();
    }

    public function index()
    {
        return view('produk/index', [
            'products' => $this->productModel->findAll()
        ]);
        return view('v_produk');
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
}
