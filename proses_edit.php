<?php
include 'koneksi.php';

$id           = mysqli_real_escape_string($koneksi, $_POST['id']);
$influencers  = mysqli_real_escape_string($koneksi, $_POST['influencers']);
$judul        = mysqli_real_escape_string($koneksi, $_POST['judul_berita']);
$tone         = mysqli_real_escape_string($koneksi, $_POST['tone']);
$jenis_media  = mysqli_real_escape_string($koneksi, $_POST['jenis_media'] ?? 'online');
$halaman      = mysqli_real_escape_string($koneksi, $_POST['halaman']     ?? '');
$bulan_cetak  = mysqli_real_escape_string($koneksi, $_POST['bulan_cetak'] ?? '');

// Kalau jenis online, kosongkan field cetak
if ($jenis_media == 'online') {
    $halaman     = '';
    $bulan_cetak = '';
}

$query = "UPDATE laporan 
          SET influencers='$influencers', judul_berita='$judul', tone='$tone',
              jenis_media='$jenis_media', halaman='$halaman', bulan_cetak='$bulan_cetak'
          WHERE id='$id'";

if (mysqli_query($koneksi, $query)) {
    header("Location: laporan_staf.php?pesan=update_sukses#baris-$id");
    exit();
} else {
    echo "Gagal mengupdate data: " . mysqli_error($koneksi);
}
?>