<?php
include 'koneksi.php';
header('Content-Type: application/json');

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$jenis  = isset($_GET['jenis'])  ? $_GET['jenis']  : '';

// Whitelist filter & jenis
$allowed_filter = ['semua', 'hari_ini', 'minggu_ini', 'bulan_ini'];
$allowed_jenis  = ['online', 'cetak', ''];
if (!in_array($filter, $allowed_filter)) $filter = 'semua';
if (!in_array($jenis,  $allowed_jenis))  $jenis  = '';

$kondisi_parts = [];
if ($filter == 'hari_ini') {
    $kondisi_parts[] = "DATE(tanggal) = CURDATE()";
} elseif ($filter == 'minggu_ini') {
    $kondisi_parts[] = "tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($filter == 'bulan_ini') {
    $kondisi_parts[] = "MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())";
}
if ($jenis != '') $kondisi_parts[] = "jenis_media = '$jenis'";

$kondisi_waktu = count($kondisi_parts) > 0 ? "WHERE " . implode(" AND ", $kondisi_parts) : "";

$query = "
    SELECT 
        media,
        SUM(CASE WHEN tone = 'Positive' THEN 1 ELSE 0 END) as positive,
        SUM(CASE WHEN tone = 'Negative' THEN 1 ELSE 0 END) as negative,
        SUM(CASE WHEN tone = 'Neutral'  THEN 1 ELSE 0 END) as neutral,
        COUNT(*) as total
    FROM laporan
    $kondisi_waktu
    GROUP BY media
    ORDER BY total DESC
    LIMIT 10
";

$result   = mysqli_query($koneksi, $query);
$media    = [];
$positive = [];
$negative = [];
$neutral  = [];

while ($row = mysqli_fetch_assoc($result)) {
    $media[]    = $row['media'];
    $positive[] = (int) $row['positive'];
    $negative[] = (int) $row['negative'];
    $neutral[]  = (int) $row['neutral'];
}

echo json_encode([
    'media'    => $media,
    'positive' => $positive,
    'negative' => $negative,
    'neutral'  => $neutral,
]);
?>