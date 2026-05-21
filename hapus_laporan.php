<?php
include 'koneksi.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("location:laporan_staf.php");
    exit();
}

$id    = (int) $_GET['id'];
$query = "DELETE FROM laporan WHERE id='$id'";

if (mysqli_query($koneksi, $query)) {
    header("location:laporan_staf.php?pesan=hapus_sukses");
    exit();
} else {
    echo "Data gagal dihapus: " . mysqli_error($koneksi);
}
?>