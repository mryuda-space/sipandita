<?php
include 'koneksi.php';

// Menangkap ID dari link tombol hapus yang diklik
$id = $_GET['id'];

// Perintah untuk menghapus data di database
$query = "DELETE FROM laporan WHERE id='$id'";

if (mysqli_query($koneksi, $query)) {
    // Kalau berhasil, kembalikan ke halaman laporan
    header("location:laporan_staf.php?pesan=hapus_sukses");
} else {
    echo "Data gagal dihapus: " . mysqli_error($koneksi);
}
?>