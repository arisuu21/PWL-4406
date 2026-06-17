<?php
// Validation pada controller  

//1. tentukan rules
$this->form_validation->set_rules('nama', 'Nama', 'required');
$this->form_validation->set_rules('email', 'Email', 'required|valid_email');

//2. cek dengan menjalankan validation, hasilnya nilai true/false
if ($this->form_validation->run() === FALSE) {
    // Tampilkan pesan kesalahan
    $errors = $this->form_validation->error_array();
} else {
    // Proses data form
}
?>