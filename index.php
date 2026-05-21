<?php
include 'koneksi.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- LOGIKA FILTER ---
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$tahun  = isset($_GET['tahun'])  ? intval($_GET['tahun']) : 0;
$jenis  = isset($_GET['jenis'])  ? $_GET['jenis'] : '';

// Bangun kondisi
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

$kondisi_waktu    = count($kondisi_parts) > 0 ? "WHERE " . implode(" AND ", $kondisi_parts) : "";
$kondisi_positive = $kondisi_waktu ? $kondisi_waktu . " AND tone='Positive'" : "WHERE tone='Positive'";
$kondisi_negative = $kondisi_waktu ? $kondisi_waktu . " AND tone='Negative'" : "WHERE tone='Negative'";
$kondisi_neutral  = $kondisi_waktu ? $kondisi_waktu . " AND tone='Neutral'"  : "WHERE tone='Neutral'";

// Hitung data
$total_laporan  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as jumlah FROM laporan $kondisi_waktu"))['jumlah'];
$total_positive = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as jumlah FROM laporan $kondisi_positive"))['jumlah'];
$total_negative = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as jumlah FROM laporan $kondisi_negative"))['jumlah'];
$total_neutral  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as jumlah FROM laporan $kondisi_neutral"))['jumlah'];

// --- PERBANDINGAN PERIODE SEBELUMNYA ---
$kondisi_prev = null;
if ($filter == 'hari_ini') {
    $prev_parts = ["DATE(tanggal) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)"];
    if ($tahun > 0) $prev_parts[] = "YEAR(tanggal) = $tahun";
    if ($jenis != '') $prev_parts[] = "jenis_media = '$jenis'";
    $kondisi_prev = "WHERE " . implode(" AND ", $prev_parts);
} elseif ($filter == 'minggu_ini') {
    $prev_parts = ["tanggal >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)", "tanggal < DATE_SUB(CURDATE(), INTERVAL 7 DAY)"];
    if ($tahun > 0) $prev_parts[] = "YEAR(tanggal) = $tahun";
    if ($jenis != '') $prev_parts[] = "jenis_media = '$jenis'";
    $kondisi_prev = "WHERE " . implode(" AND ", $prev_parts);
} elseif ($filter == 'bulan_ini') {
    $prev_parts = [
        "MONTH(tanggal) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))",
        "YEAR(tanggal) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))"
    ];
    if ($jenis != '') $prev_parts[] = "jenis_media = '$jenis'";
    $kondisi_prev = "WHERE " . implode(" AND ", $prev_parts);
} elseif ($tahun > 0) {
    $prev_parts = ["YEAR(tanggal) = " . ($tahun - 1)];
    if ($jenis != '') $prev_parts[] = "jenis_media = '$jenis'";
    $kondisi_prev = "WHERE " . implode(" AND ", $prev_parts);
}

$total_prev = 0;
if ($kondisi_prev) {
    $total_prev = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as jumlah FROM laporan $kondisi_prev"))['jumlah'];
}

function hitungPerubahan($sekarang, $sebelumnya) {
    if ($sebelumnya == 0) return null;
    $persen = round((($sekarang - $sebelumnya) / $sebelumnya) * 100, 1);
    if (abs($persen) > 9999) return null;
    return $persen;
}

$tampilkan_badge = ($filter !== 'semua');
$perubahan_total = $tampilkan_badge ? hitungPerubahan($total_laporan, $total_prev) : null;

function badgePerubahan($persen) {
    if ($persen === null) return '';
    if ($persen > 0) return '<span class="badge-trend up">&#9650; ' . $persen . '% vs periode lalu</span>';
    if ($persen < 0) return '<span class="badge-trend down">&#9660; ' . abs($persen) . '% vs periode lalu</span>';
    return '<span class="badge-trend flat">&#9644; Tidak ada perubahan</span>';
}

// --- AMBIL TAHUN TERSEDIA ---
$query_tahun  = mysqli_query($koneksi, "SELECT DISTINCT YEAR(tanggal) as thn FROM laporan WHERE tanggal IS NOT NULL AND tanggal != '0000-00-00' AND YEAR(tanggal) > 2000 ORDER BY thn DESC");
$daftar_tahun = [];
while ($row = mysqli_fetch_assoc($query_tahun)) $daftar_tahun[] = $row['thn'];

// --- 10 LAPORAN TERBARU ---
$query_terbaru = mysqli_query($koneksi, "SELECT judul_berita, media, tone, tanggal, link, jenis_media FROM laporan $kondisi_waktu ORDER BY tanggal DESC, id DESC LIMIT 10");

// Helper buat URL filter dengan semua parameter terjaga
function buildUrl($params) {
    $base = array_filter($params, fn($v) => $v !== '' && $v !== 0 && $v !== '0');
    return 'index.php' . (count($base) > 0 ? '?' . http_build_query($base) : '');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPANDITA - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f0f4f8; font-family: 'Poppins', sans-serif; }
        .sidebar { background: linear-gradient(180deg, #1a3d4a 0%, #112933 100%); min-height: 100vh; width: 250px; color: white; position: fixed; box-shadow: 4px 0 15px rgba(0,0,0,0.05); }
        .sidebar h3 { letter-spacing: 1px; }
        .main-content { margin-left: 250px; width: calc(100% - 250px); }
        .nav-link { color: #aeb9be; font-weight: 500; padding: 12px 20px; transition: all 0.3s ease; }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.1); border-radius: 8px; }
        .card-custom { border: none; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: transform 0.3s ease; }
        .card-custom:hover { transform: translateY(-5px); box-shadow: 0 12px 20px rgba(0,0,0,0.08); }
        .header-blue { background: linear-gradient(90deg, #2c3e50 0%, #1a252f 100%); color: white; padding: 15px 20px; font-weight: 600; border-radius: 16px 16px 0 0; letter-spacing: 0.5px; }
        .badge-trend { display: inline-block; font-size: 11px; padding: 3px 10px; border-radius: 20px; font-weight: 600; margin-top: 6px; }
        .badge-trend.up   { background: #d4edda; color: #155724; }
        .badge-trend.down { background: #f8d7da; color: #721c24; }
        .badge-trend.flat { background: #e2e3e5; color: #383d41; }
        .btn-period { padding: 5px 16px; border: 2px solid rgba(255,255,255,0.6); border-radius: 20px; background: transparent; cursor: pointer; font-size: 12px; font-family: 'Poppins', sans-serif; color: rgba(255,255,255,0.7); margin-left: 6px; transition: all 0.2s; }
        .btn-period:hover { background: rgba(255,255,255,0.15); color: #fff; border-color: #fff; }
        .btn-period.active { background: #fff; color: #2b6088; border-color: #fff; font-weight: 600; }
        /* Tab jenis media */
        .tab-jenis { display: flex; gap: 8px; margin-bottom: 1.25rem; }
        .tab-jenis a { padding: 8px 22px; border-radius: 25px; font-size: 13px; font-weight: 600; text-decoration: none; border: 2px solid #dee2e6; background: white; color: #6c757d; transition: all 0.2s; }
        .tab-jenis a:hover { background: #f0f4f8; color: #1a3d4a; border-color: #1a3d4a; }
        .tab-jenis a.active-all    { background: #1a3d4a; color: white; border-color: #1a3d4a; }
        .tab-jenis a.active-online { background: #1e40af; color: white; border-color: #1e40af; }
        .tab-jenis a.active-cetak  { background: #854d0e; color: white; border-color: #854d0e; }
        /* Tabel laporan terbaru */
        .table-laporan thead th { background: #f8f9fa; font-size: 12px; font-weight: 600; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e9ecef; padding: 12px 16px; }
        .table-laporan tbody td { font-size: 13px; padding: 12px 16px; vertical-align: middle; }
        .table-laporan tbody tr:hover { background: #f8fbff; }
        .tone-badge { display: inline-block; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
        .tone-positive { background: #d4edda; color: #155724; }
        .tone-negative { background: #f8d7da; color: #721c24; }
        .tone-neutral  { background: #e2e3e5; color: #383d41; }
        .jenis-badge-online { background: #dbeafe; color: #1e40af; font-size: 11px; padding: 3px 10px; border-radius: 20px; font-weight: 600; }
        .jenis-badge-cetak  { background: #fef9c3; color: #854d0e; font-size: 11px; padding: 3px 10px; border-radius: 20px; font-weight: 600; }
        .judul-link { color: #1a3d4a; text-decoration: none; font-weight: 500; }
        .judul-link:hover { color: #4a90c4; text-decoration: underline; }
        .judul-truncate { max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="wordcloud2.js"></script>
</head>
<body>
<div class="d-flex">
    <!-- SIDEBAR -->
    <div class="sidebar p-3">
        <h3 class="fw-bold mb-4 mt-2 px-2">SIPANDITA</h3>
        <ul class="nav flex-column gap-2">
            <li class="nav-item"><a href="index.php" class="nav-link active rounded"><i class="bi bi-house-door me-2"></i> Dashboard Analis</a></li>
            <li class="nav-item"><a href="laporan_staf.php" class="nav-link rounded"><i class="bi bi-file-earmark-text me-2"></i> Laporan Staf</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <i class="bi bi-grid-1x2-fill text-primary fs-4 me-3"></i>
                <h5 class="m-0 fw-bold">LAPORAN ANALIS</h5>
            </div>
            <div class="d-flex align-items-center">
                <span class="me-2 fw-medium">Hi, Umar</span>
                <i class="bi bi-person-circle fs-3 text-secondary"></i>
            </div>
        </div>

        <div class="container-fluid p-4">
            <!-- FILTER TAHUN + PERIODE -->
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="fw-bold text-muted mb-0">Ringkasan Data Berita</h6>
                    <a href="export_csv.php?filter=<?php echo $filter; ?>&tahun=<?php echo $tahun; ?>&jenis=<?php echo $jenis; ?>" 
                        class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-download me-1"></i> Download CSV
                    </a>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <!-- Filter Tahun -->
                    <form method="GET" action="index.php" class="d-flex align-items-center bg-white p-2 rounded-pill shadow-sm border">
                        <i class="bi bi-calendar-range text-success ms-2 me-2"></i>
                        <select name="tahun" class="form-select form-select-sm border-0 fw-bold text-secondary shadow-none bg-transparent" style="cursor:pointer;outline:none;" onchange="this.form.submit()">
                            <option value="0" <?php if($tahun == 0) echo 'selected'; ?>>Semua Tahun</option>
                            <?php foreach ($daftar_tahun as $thn): ?>
                            <option value="<?php echo $thn; ?>" <?php if($tahun == $thn) echo 'selected'; ?>><?php echo $thn; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                        <input type="hidden" name="jenis" value="<?php echo $jenis; ?>">
                    </form>
                    <!-- Filter Periode -->
                    <form method="GET" action="index.php" class="d-flex align-items-center bg-white p-2 rounded-pill shadow-sm border">
                        <i class="bi bi-calendar3 text-primary ms-2 me-2"></i>
                        <select name="filter" class="form-select form-select-sm border-0 fw-bold text-secondary shadow-none bg-transparent" style="cursor:pointer;outline:none;" onchange="this.form.submit()">
                            <option value="semua"      <?php if($filter == 'semua')     echo 'selected'; ?>>Semua Waktu</option>
                            <option value="hari_ini"   <?php if($filter == 'hari_ini')  echo 'selected'; ?>>Hari Ini</option>
                            <option value="minggu_ini" <?php if($filter == 'minggu_ini')echo 'selected'; ?>>7 Hari Terakhir</option>
                            <option value="bulan_ini"  <?php if($filter == 'bulan_ini') echo 'selected'; ?>>Bulan Ini</option>
                        </select>
                        <input type="hidden" name="tahun" value="<?php echo $tahun; ?>">
                        <input type="hidden" name="jenis" value="<?php echo $jenis; ?>">
                    </form>
                </div>
            </div>

            <!-- TAB JENIS MEDIA -->
            <div class="tab-jenis">
                <a href="<?php echo buildUrl(['filter'=>$filter,'tahun'=>$tahun,'jenis'=>'']); ?>"
                   class="<?php echo $jenis == '' ? 'active-all' : ''; ?>">
                    <i class="bi bi-grid me-1"></i> Semua Media
                </a>
                <a href="<?php echo buildUrl(['filter'=>$filter,'tahun'=>$tahun,'jenis'=>'online']); ?>"
                   class="<?php echo $jenis == 'online' ? 'active-online' : ''; ?>">
                    <i class="bi bi-globe2 me-1"></i> Online
                </a>
                <a href="<?php echo buildUrl(['filter'=>$filter,'tahun'=>$tahun,'jenis'=>'cetak']); ?>"
                   class="<?php echo $jenis == 'cetak' ? 'active-cetak' : ''; ?>">
                    <i class="bi bi-newspaper me-1"></i> Cetak
                </a>
            </div>

            <!-- STAT CARDS -->
            <div class="row g-3 mb-4 text-center">
                <div class="col-md-3">
                    <div class="card card-custom p-4 border-bottom border-4" style="border-color:#1a3d4a!important;">
                        <h1 class="fw-bold display-5" style="color:#1a3d4a;"><?php echo number_format($total_laporan, 0, ',', '.'); ?></h1>
                        <p class="text-muted small fw-bold mb-2">TOTAL LAPORAN</p>
                        <i class="bi bi-file-earmark-text-fill fs-1" style="color:#1a3d4a;"></i>
                        <div class="mt-2"><?php echo badgePerubahan($perubahan_total); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-custom p-4 border-bottom border-4" style="border-color:#4a90c4!important;">
                        <h1 class="fw-bold display-5" style="color:#4a90c4;"><?php echo number_format($total_positive, 0, ',', '.'); ?></h1>
                        <p class="text-muted small fw-bold mb-2">LAPORAN POSITIVE</p>
                        <i class="bi bi-check-circle-fill fs-1" style="color:#4a90c4;"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-custom p-4 border-bottom border-4" style="border-color:#cb4335!important;">
                        <h1 class="fw-bold display-5" style="color:#cb4335;"><?php echo number_format($total_negative, 0, ',', '.'); ?></h1>
                        <p class="text-muted small fw-bold mb-2">LAPORAN NEGATIVE</p>
                        <i class="bi bi-exclamation-triangle-fill fs-1" style="color:#cb4335;"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-custom p-4 border-bottom border-4" style="border-color:#808b96!important;">
                        <h1 class="fw-bold display-5" style="color:#808b96;"><?php echo number_format($total_neutral, 0, ',', '.'); ?></h1>
                        <p class="text-muted small fw-bold mb-2">LAPORAN NEUTRAL</p>
                        <i class="bi bi-dash-circle-fill fs-1" style="color:#808b96;"></i>
                    </div>
                </div>
            </div>

            <!-- CHART BARIS 1: Bar + Pie -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card card-custom">
                        <div class="header-blue"><i class="bi bi-bar-chart-fill me-2"></i> Perbandingan Jumlah Laporan</div>
                        <div class="card-body"><div style="height:300px;"><canvas id="grafikLaporan"></canvas></div></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-custom">
                        <div class="header-blue"><i class="bi bi-pie-chart-fill me-2"></i> Persentase Status Laporan</div>
                        <div class="card-body"><div style="height:300px;"><canvas id="pieChartLaporan"></canvas></div></div>
                    </div>
                </div>
            </div>

            <!-- CHART BARIS 2: Tren -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card card-custom">
                        <div class="header-blue d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-graph-up me-2"></i> Tren Laporan per Hari</span>
                            <div>
                                <button onclick="loadTrend(7)"   class="btn-period" id="p7">7 Hari</button>
                                <button onclick="loadTrend(30)"  class="btn-period active" id="p30">30 Hari</button>
                                <button onclick="loadTrend(90)"  class="btn-period" id="p90">90 Hari</button>
                                <button onclick="loadTrend(365)" class="btn-period" id="p365">1 Tahun</button>
                            </div>
                        </div>
                        <div class="card-body"><div style="height:300px;"><canvas id="trendChart"></canvas></div></div>
                    </div>
                </div>
            </div>

            <!-- WORDCLOUD -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card card-custom">
                        <div class="header-blue"><i class="bi bi-chat-quote-fill me-2"></i> Keyword Dominan per Tone</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4 text-center">
                                    <p class="fw-bold mb-1" style="color:#4a90c4"><i class="bi bi-check-circle-fill me-1"></i> Positive</p>
                                    <canvas id="wc-positive" width="380" height="250"></canvas>
                                </div>
                                <div class="col-md-4 text-center">
                                    <p class="fw-bold mb-1" style="color:#cb4335;"><i class="bi bi-exclamation-triangle-fill me-1"></i> Negative</p>
                                    <canvas id="wc-negative" width="380" height="250"></canvas>
                                </div>
                                <div class="col-md-4 text-center">
                                    <p class="fw-bold mb-1" style="color:#808b96;"><i class="bi bi-dash-circle-fill me-1"></i> Neutral</p>
                                    <canvas id="wc-neutral" width="380" height="250"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TOP MEDIA -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card card-custom">
                        <div class="header-blue"><i class="bi bi-newspaper me-2"></i> Top 10 Media Terbanyak</div>
                        <div class="card-body"><div style="height:350px;"><canvas id="mediaChart"></canvas></div></div>
                    </div>
                </div>
            </div>

            <!-- TABEL 10 LAPORAN TERBARU -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card card-custom">
                        <div class="header-blue d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-clock-history me-2"></i> 10 Laporan Terbaru</span>
                            <?php if ($filter !== 'semua' || $tahun > 0 || $jenis != ''): ?>
                                <span class="badge bg-light text-dark fw-normal" style="font-size:11px;">Filter aktif</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-laporan table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width:40px;">#</th>
                                            <th>Judul Berita</th>
                                            <th style="width:150px;">Media</th>
                                            <th style="width:90px;">Jenis</th>
                                            <th style="width:100px;">Tone</th>
                                            <th style="width:110px;">Tanggal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $no = 1;
                                    while ($row = mysqli_fetch_assoc($query_terbaru)):
                                        $tone_class  = 'tone-neutral';
                                        if ($row['tone'] == 'Positive') $tone_class = 'tone-positive';
                                        elseif ($row['tone'] == 'Negative') $tone_class = 'tone-negative';
                                        $jenis_row   = !empty($row['jenis_media']) ? $row['jenis_media'] : 'online';
                                        $tanggal_fmt = date('d M Y', strtotime($row['tanggal']));
                                    ?>
                                    <tr>
                                        <td class="text-muted"><?php echo $no++; ?></td>
                                        <td>
                                            <?php if (!empty($row['link'])): ?>
                                                <a href="<?php echo htmlspecialchars($row['link']); ?>" target="_blank" class="judul-link">
                                                    <span class="judul-truncate" title="<?php echo htmlspecialchars($row['judul_berita']); ?>"><?php echo htmlspecialchars($row['judul_berita']); ?></span>
                                                </a>
                                            <?php else: ?>
                                                <span class="judul-truncate" title="<?php echo htmlspecialchars($row['judul_berita']); ?>"><?php echo htmlspecialchars($row['judul_berita']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted"><?php echo htmlspecialchars($row['media']); ?></td>
                                        <td>
                                            <?php if ($jenis_row == 'cetak'): ?>
                                                <span class="jenis-badge-cetak"><i class="bi bi-newspaper me-1"></i>Cetak</span>
                                            <?php else: ?>
                                                <span class="jenis-badge-online"><i class="bi bi-globe2 me-1"></i>Online</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="tone-badge <?php echo $tone_class; ?>"><?php echo htmlspecialchars($row['tone']); ?></span></td>
                                        <td class="text-muted"><?php echo $tanggal_fmt; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /container-fluid -->
    </div><!-- /main-content -->
</div>

<script>
const C_POSITIVE = '#4a90c4';
const C_NEGATIVE = '#cb4335';
const C_NEUTRAL  = '#808b96';

new Chart(document.getElementById('grafikLaporan').getContext('2d'), {
    type: 'bar',
    data: {
        labels: ['Positive', 'Negative', 'Neutral'],
        datasets: [{ label: 'Jumlah Laporan', data: [<?php echo $total_positive; ?>, <?php echo $total_negative; ?>, <?php echo $total_neutral; ?>], backgroundColor: [C_POSITIVE, C_NEGATIVE, C_NEUTRAL], borderRadius: 8, barThickness: 50 }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { type: 'logarithmic', grid: { display: false }, ticks: { callback: function(v) { return [1,10,100,1000,10000,100000].includes(v) ? v.toLocaleString('id-ID') : ''; } } },
            x: { grid: { display: false } }
        }
    }
});

new Chart(document.getElementById('pieChartLaporan').getContext('2d'), {
    type: 'pie',
    data: {
        labels: ['Positive', 'Negative', 'Neutral'],
        datasets: [{ data: [<?php echo $total_positive; ?>, <?php echo $total_negative; ?>, <?php echo $total_neutral; ?>], backgroundColor: [C_POSITIVE, C_NEGATIVE, C_NEUTRAL], borderWidth: 2, borderColor: '#ffffff' }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { padding: 20, font: { family: 'Poppins' } } },
            tooltip: { callbacks: { label: function(ctx) { const total = ctx.dataset.data.reduce((a,b)=>a+b,0); const pct = total > 0 ? ((ctx.raw/total)*100).toFixed(1) : 0; return ` ${ctx.label}: ${ctx.raw.toLocaleString('id-ID')} (${pct}%)`; } } }
        }
    }
});

let trendChart = null;
function loadTrend(period) {
    document.querySelectorAll('.btn-period').forEach(b => b.classList.remove('active'));
    document.getElementById('p' + period).classList.add('active');
    fetch('get_trend.php?period=' + period + '&tahun=<?php echo $tahun; ?>&jenis=<?php echo $jenis; ?>')
        .then(res => res.json())
        .then(data => {
            if (trendChart) trendChart.destroy();
            trendChart = new Chart(document.getElementById('trendChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        { label: 'Positive', data: data.positive, borderColor: C_POSITIVE, backgroundColor: 'rgba(36,113,163,0.08)', tension: 0.4, fill: true, pointRadius: 3, pointHoverRadius: 6 },
                        { label: 'Negative', data: data.negative, borderColor: C_NEGATIVE, backgroundColor: 'rgba(203,67,53,0.08)',  tension: 0.4, fill: true, pointRadius: 3, pointHoverRadius: 6 },
                        { label: 'Neutral',  data: data.neutral,  borderColor: C_NEUTRAL,  backgroundColor: 'rgba(128,139,150,0.08)',tension: 0.4, fill: true, pointRadius: 3, pointHoverRadius: 6 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'top', labels: { font: { family: 'Poppins', size: 12 }, padding: 16 } } },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { family: 'Poppins', size: 11 } } },
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { family: 'Poppins', size: 11 } } }
                    }
                }
            });
        });
}
loadTrend(30);

function loadWordcloud(tone, canvasId) {
    fetch('get_wordcloud.php?tone=' + tone)
        .then(res => res.json())
        .then(data => {
            const list = data.filter(d => typeof d.text === 'string' && d.text.length > 0).map(d => [d.text, d.value]);
            WordCloud(document.getElementById(canvasId), {
                list, gridSize: 8,
                weightFactor: function(size) { return Math.log2(size + 1) * 10; },
                fontFamily: 'Poppins, sans-serif',
                color: function() {
                    const shades = { 'wc-positive': ['#1a3a5c','#4a90c4','#6aaed6','#9dcbe8'], 'wc-negative': ['#922b21','#cb4335','#d98880','#e8b4b8'], 'wc-neutral': ['#4d5656','#808b96','#a9b2b9','#ccd1d1'] };
                    const s = shades[canvasId];
                    return s[Math.floor(Math.random() * s.length)];
                },
                rotateRatio: 0.3, rotationSteps: 2, backgroundColor: 'transparent', drawOutOfBound: false,
            });
        });
}
loadWordcloud('Positive', 'wc-positive');
loadWordcloud('Negative', 'wc-negative');
loadWordcloud('Neutral',  'wc-neutral');

fetch('get_media.php?filter=<?php echo $filter; ?>&jenis=<?php echo $jenis; ?>')
    .then(res => res.json())
    .then(data => {
        new Chart(document.getElementById('mediaChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.media,
                datasets: [
                    { label: 'Positive', data: data.positive, backgroundColor: 'rgba(36,113,163,0.85)', borderRadius: 4 },
                    { label: 'Negative', data: data.negative, backgroundColor: 'rgba(203,67,53,0.85)',  borderRadius: 4 },
                    { label: 'Neutral',  data: data.neutral,  backgroundColor: 'rgba(128,139,150,0.85)',borderRadius: 4 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                plugins: { legend: { position: 'top', labels: { font: { family: 'Poppins', size: 12 }, padding: 16 } }, tooltip: { mode: 'index' } },
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { font: { family: 'Poppins', size: 11 } } },
                    y: { stacked: true, grid: { display: false }, ticks: { font: { family: 'Poppins', size: 11 } } }
                }
            }
        });
    });
</script>
</body>
</html>