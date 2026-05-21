<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'koneksi.php';

$influencers  = mysqli_real_escape_string($koneksi, $_POST['influencers']);
$judul        = mysqli_real_escape_string($koneksi, $_POST['judul_berita']);
$tone         = mysqli_real_escape_string($koneksi, $_POST['tone']);
$jenis_media  = isset($_POST['jenis_media']) ? mysqli_real_escape_string($koneksi, $_POST['jenis_media']) : 'online';
$halaman      = isset($_POST['halaman'])     ? mysqli_real_escape_string($koneksi, $_POST['halaman'])     : '';
$bulan_cetak  = isset($_POST['bulan_cetak']) ? mysqli_real_escape_string($koneksi, $_POST['bulan_cetak']) : '';
$tgl          = date('Y-m-d');

$query = "INSERT INTO laporan (tanggal, influencers, judul_berita, tone, jenis_media, halaman, bulan_cetak) 
          VALUES ('$tgl', '$influencers', '$judul', '$tone', '$jenis_media', '$halaman', '$bulan_cetak')";

if (mysqli_query($koneksi, $query)) {
    header("Location: laporan_staf.php?pesan=berhasil");
    exit();
} else {
    echo "GAGAL MENYIMPAN DATA! Error: " . mysqli_error($koneksi);
}
?>