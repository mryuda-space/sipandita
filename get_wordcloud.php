<?php
include 'koneksi.php';
header('Content-Type: application/json');

// Whitelist tone
$allowed_tone = ['Positive', 'Negative', 'Neutral'];
$tone = isset($_GET['tone']) ? $_GET['tone'] : 'Positive';
if (!in_array($tone, $allowed_tone)) $tone = 'Positive';

$tone_escaped = mysqli_real_escape_string($koneksi, $tone);
$result = mysqli_query($koneksi, "SELECT judul_berita FROM laporan WHERE tone = '$tone_escaped'");

$stopwords = [
    'dan', 'di', 'ke', 'dari', 'yang', 'untuk', 'dengan', 'pada', 'ini', 'itu',
    'adalah', 'akan', 'ada', 'juga', 'dalam', 'tidak', 'oleh', 'atau', 'se',
    'nya', 'ber', 'ter', 'me', 'kan', 'an', 'the', 'of', 'to', 'in', 'a',
    'telah', 'lebih', 'bagi', 'para', 'serta', 'sudah', 'jika', 'atas',
    'hal', 'cara', 'kali', 'pun', 'pula', 'lagi', 'lain', 'ia', 'namun',
    'saat', 'per', 'agar', 'bisa', 'harus', 'bukan', 'belum', 'hingga',
    'antara', 'setelah', 'karena', 'sehingga', 'bahwa', 'hanya', 'namanya',
    'mereka', 'kami', 'kita', 'saya', 'kamu', 'anda', 'dia', 'mereka',
    'tapi', 'namun', 'tetapi', 'meski', 'walaupun', 'sejak', 'selama',
    'sambil', 'tanpa', 'selain', 'seperti', 'sebagai', 'menjadi', 'sangat',
    'dua', 'satu', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan',
    'ribu', 'juta', 'miliar', 'persen', 'tahun', 'bulan', 'hari', 'jam',
    'pj', 'soal', 'jadi', 'tahu', 'kata', 'baru', 'lama', 'baik', 'besar',
    'kecil', 'tinggi', 'rendah', 'sama', 'lalu', 'terus', 'mulai', 'akhir',
    'awal', 'warga', 'pihak', 'hasil', 'upaya', 'langkah', 'proses',
    'jawa', 'tengah', 'jateng', 'indonesia', 'nasional', 'semarang', 'minta',
    'naik', 'kota', 'dedi', 'ajak', 'pastikan', 'apresiasi', 'siap', 'dukung',
    'bantu', 'gelar', 'hadiri', 'ungkap', 'sebut', 'tegaskan', 'desak',
];

$wordCount = [];
while ($row = mysqli_fetch_assoc($result)) {
    $judul = strtolower($row['judul_berita']);
    $judul = preg_replace('/[^a-z0-9\s]/', ' ', $judul);
    $words = preg_split('/\s+/', trim($judul));
    foreach ($words as $word) {
        if (strlen($word) < 4) continue;
        if (in_array($word, $stopwords)) continue;
        $wordCount[$word] = ($wordCount[$word] ?? 0) + 1;
    }
}

arsort($wordCount);
$top    = array_slice($wordCount, 0, 50);
$output = [];
foreach ($top as $word => $count) {
    $output[] = ['text' => $word, 'value' => $count];
}

echo json_encode($output);
?>