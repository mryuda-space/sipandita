<?php
include 'koneksi.php';
header('Content-Type: application/json');

$kondisi_waktu = "";
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';

if ($filter == 'hari_ini') {
    $kondisi_waktu = "WHERE DATE(tanggal) = CURDATE()";
} elseif ($filter == 'minggu_ini') {
    $kondisi_waktu = "WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($filter == 'bulan_ini') {
    $kondisi_waktu = "WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())";
}

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

$result = mysqli_query($koneksi, $query);

$media = [];
$positive = [];
$negative = [];
$neutral = [];

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