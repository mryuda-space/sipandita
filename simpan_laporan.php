<?php
// 1. Nyalakan detektor error
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Panggil koneksi
include 'koneksi.php';

// 3. Ambil data dari form modal (Nama disesuaikan dengan input di modal)
$influencers = $_POST['influencers'];
$judul       = $_POST['judul_berita'];
$tone        = $_POST['tone'];
$tgl         = date('Y-m-d'); // Otomatis tanggal hari ini

// 4. Perintah masukkan ke database (Nama kolom disesuaikan dengan phpMyAdmin)
$query = "INSERT INTO laporan (tanggal, influencers, judul_berita, tone) 
          VALUES ('$tgl', '$influencers', '$judul', '$tone')";

// 5. Eksekusi dan pindah halaman
if (mysqli_query($koneksi, $query)) {
    // Kalau berhasil, otomatis kembali ke halaman laporan
    header("Location: laporan_staf.php?pesan=berhasil");
    exit(); // Ini penangkal biar data nggak dobel kalau halamannya nggak sengaja ke-refresh
} else {
    // Kalau gagal, tampilkan error aslinya dari database
    echo "GAGAL MENYIMPAN DATA! Error dari database: <br>";
    echo mysqli_error($koneksi);
}
?>