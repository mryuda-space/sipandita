<?php
include 'koneksi.php';

// Menangkap data baru dari form (disesuaikan dengan nama di edit_laporan.php)
$id          = $_POST['id'];
$influencers = $_POST['influencers'];
$judul       = $_POST['judul_berita'];
$tone        = $_POST['tone'];

// Perintah UPDATE ke database (disesuaikan dengan nama kolom di phpMyAdmin)
$query = "UPDATE laporan SET influencers='$influencers', judul_berita='$judul', tone='$tone' WHERE id='$id'";

if (mysqli_query($koneksi, $query)) {
    // KUNCI PERUBAHANNYA DI SINI: 
    // Kita tambahkan #baris-$id di akhir URL supaya layarnya "mendarat" di posisi baris yang diedit
    header("location:laporan_staf.php?pesan=update_sukses#baris-$id");
} else {
    echo "Gagal mengupdate data: " . mysqli_error($koneksi);
}
?>