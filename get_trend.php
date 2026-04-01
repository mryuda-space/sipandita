<?php
include 'koneksi.php';
header('Content-Type: application/json');

$period = isset($_GET['period']) ? (int)$_GET['period'] : 30;

if ($period >= 365) {
    $query = "
        SELECT 
            DATE_FORMAT(MIN(tanggal), '%d %b') as tgl,
            SUM(CASE WHEN tone = 'Positive' THEN 1 ELSE 0 END) as positive,
            SUM(CASE WHEN tone = 'Negative' THEN 1 ELSE 0 END) as negative,
            SUM(CASE WHEN tone = 'Neutral'  THEN 1 ELSE 0 END) as neutral
        FROM laporan
        WHERE tanggal >= DATE_SUB((SELECT MAX(tanggal) FROM laporan), INTERVAL $period DAY)
        GROUP BY YEARWEEK(tanggal)
        ORDER BY MIN(tanggal) ASC
    ";
} else {
    $query = "
        SELECT 
            DATE(tanggal) as tgl,
            SUM(CASE WHEN tone = 'Positive' THEN 1 ELSE 0 END) as positive,
            SUM(CASE WHEN tone = 'Negative' THEN 1 ELSE 0 END) as negative,
            SUM(CASE WHEN tone = 'Neutral'  THEN 1 ELSE 0 END) as neutral
        FROM laporan
        WHERE tanggal >= DATE_SUB((SELECT MAX(tanggal) FROM laporan), INTERVAL $period DAY)
        GROUP BY DATE(tanggal)
        ORDER BY DATE(tanggal) ASC
    ";
}

$result = mysqli_query($koneksi, $query);

$labels   = [];
$positive = [];
$negative = [];
$neutral  = [];

while ($row = mysqli_fetch_assoc($result)) {
    if ($period >= 365) {
        $labels[] = $row['tgl'];
    } else {
        $labels[] = date('d M', strtotime($row['tgl']));
    }
    $positive[] = (int) $row['positive'];
    $negative[] = (int) $row['negative'];
    $neutral[]  = (int) $row['neutral'];
}

echo json_encode([
    'labels'   => $labels,
    'positive' => $positive,
    'negative' => $negative,
    'neutral'  => $neutral,
]);
?>