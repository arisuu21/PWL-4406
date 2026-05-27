<?php
$nama = $_POST['nama'];
$email = $_POST['email'];

if (empty($nama)) {
    $error_nama = "Nama harus diisi.";
} else {
    $error_nama = "";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_email = "Format email tidak valid.";
} else {
    $error_email = "";
}

if ($error_nama || $error_email) {
    // Tampilkan pesan kesalahan
} else {
    // Proses data form
}
?>