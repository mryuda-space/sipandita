<?php
// Rename file ini menjadi koneksi.php
// Lalu sesuaikan dengan setting database kamu
$koneksi = mysqli_connect(
    'localhost',      // host
    'root',           // username
    'PASSWORD_KAMU',  // password - ganti dengan password kamu
    'db_sipandita'    // nama database
);

if (!$koneksi) {
    die('Koneksi gagal: ' . mysqli_connect_error());
}
?>