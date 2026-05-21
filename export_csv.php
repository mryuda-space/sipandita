<?php
include 'koneksi.php';

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$tahun  = isset($_GET['tahun'])  ? intval($_GET['tahun']) : 0;
$jenis  = isset($_GET['jenis'])  ? $_GET['jenis'] : '';

// Bangun kondisi (sama seperti index.php)
$kondisi_parts = [];
if ($tahun > 0) $kondisi_parts[] = "YEAR(tanggal) = $tahun";
if ($jenis != '') $kondisi_parts[] = "jenis_media = '$jenis'";

if ($filter == 'hari_ini') {
    $kondisi_parts[] = "DATE(tanggal) = CURDATE()";
} elseif ($filter == 'minggu_ini') {
    $kondisi_parts[] = "tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($filter == 'bulan_ini') {
    $kondisi_parts[] = "MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())";
}

$kondisi_waktu = count($kondisi_parts) > 0 ? "WHERE " . implode(" AND ", $kondisi_parts) : "";

// Set header untuk download CSV — nama file sesuai filter
$nama_parts = ["laporan_sipandita"];
if ($tahun > 0) $nama_parts[] = $tahun;
if ($jenis != '') $nama_parts[] = $jenis;
$label_filter = ['hari_ini' => 'hari-ini', 'minggu_ini' => '7-hari-terakhir', 'bulan_ini' => 'bulan-ini'];
if (isset($label_filter[$filter])) $nama_parts[] = $label_filter[$filter];
if ($tahun == 0 && !isset($label_filter[$filter])) $nama_parts[] = "semua";
$filename = implode("_", $nama_parts) . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Tambahkan BOM agar Excel bisa baca karakter Indonesia
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header kolom
fputcsv($output, ['ID', 'Tanggal', 'Waktu', 'Judul Berita', 'Link', 'Tone', 'Media', 'Jenis Media', 'Influencers', 'Resume']);

// Ambil data
$query = mysqli_query($koneksi, "SELECT id, tanggal, waktu, judul_berita, link, tone, media, jenis_media, influencers, resume FROM laporan $kondisi_waktu ORDER BY tanggal ASC");

while ($row = mysqli_fetch_assoc($query)) {
    fputcsv($output, [
        $row['id'],
        $row['tanggal'],
        $row['waktu'],
        $row['judul_berita'],
        $row['link'],
        $row['tone'],
        $row['media'],
        $row['jenis_media'],
        $row['influencers'],
        $row['resume']
    ]);
}

fclose($output);
exit;
?>