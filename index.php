<?php
include 'koneksi.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- LOGIKA FILTER WAKTU ---
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$kondisi_waktu = "";
if ($filter == 'hari_ini') {
    $kondisi_waktu = "WHERE DATE(tanggal) = CURDATE()";
} elseif ($filter == 'minggu_ini') {
    $kondisi_waktu = "WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($filter == 'bulan_ini') {
    $kondisi_waktu = "WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())";
}
$kondisi_positive = $kondisi_waktu ? $kondisi_waktu . " AND tone='Positive'" : "WHERE tone='Positive'";
$kondisi_negative = $kondisi_waktu ? $kondisi_waktu . " AND tone='Negative'" : "WHERE tone='Negative'";
$kondisi_neutral  = $kondisi_waktu ? $kondisi_waktu . " AND tone='Neutral'"  : "WHERE tone='Neutral'";

// Hitung Data
$query_total    = mysqli_query($koneksi, "SELECT COUNT(*) as jumlah FROM laporan $kondisi_waktu");
$total_laporan  = mysqli_fetch_assoc($query_total)['jumlah'];
$query_positive = mysqli_query($koneksi, "SELECT COUNT(*) as jumlah FROM laporan $kondisi_positive");
$total_positive = mysqli_fetch_assoc($query_positive)['jumlah'];
$query_negative = mysqli_query($koneksi, "SELECT COUNT(*) as jumlah FROM laporan $kondisi_negative");
$total_negative = mysqli_fetch_assoc($query_negative)['jumlah'];
$query_neutral  = mysqli_query($koneksi, "SELECT COUNT(*) as jumlah FROM laporan $kondisi_neutral");
$total_neutral  = mysqli_fetch_assoc($query_neutral)['jumlah'];

// --- PERBANDINGAN PERIODE SEBELUMNYA ---
if ($filter == 'hari_ini') {
    $kondisi_prev = "WHERE DATE(tanggal) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
} elseif ($filter == 'minggu_ini') {
    $kondisi_prev = "WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND tanggal < DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($filter == 'bulan_ini') {
    $kondisi_prev = "WHERE MONTH(tanggal) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(tanggal) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
} else {
    $kondisi_prev = "WHERE tanggal < '2025-01-01'";
}
$query_prev = mysqli_query($koneksi, "SELECT COUNT(*) as jumlah FROM laporan $kondisi_prev");
$total_prev = mysqli_fetch_assoc($query_prev)['jumlah'];

function hitungPerubahan($sekarang, $sebelumnya) {
    if ($sebelumnya == 0) return null;
    return round((($sekarang - $sebelumnya) / $sebelumnya) * 100, 1);
}
$perubahan_total = hitungPerubahan($total_laporan, $total_prev);

function badgePerubahan($persen) {
    if ($persen === null) return '';
    if ($persen > 0) return '<span class="badge-trend up">&#9650; ' . $persen . '% vs periode lalu</span>';
    if ($persen < 0) return '<span class="badge-trend down">&#9660; ' . abs($persen) . '% vs periode lalu</span>';
    return '<span class="badge-trend flat">&#9644; Tidak ada perubahan</span>';
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
        .sidebar {
            background: linear-gradient(180deg, #1a3d4a 0%, #112933 100%);
            min-height: 100vh; width: 250px; color: white;
            position: fixed; box-shadow: 4px 0 15px rgba(0,0,0,0.05);
        }
        .sidebar h3 { letter-spacing: 1px; }
        .main-content { margin-left: 250px; width: calc(100% - 250px); }
        .nav-link { color: #aeb9be; font-weight: 500; padding: 12px 20px; transition: all 0.3s ease; }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.1); border-radius: 8px; }
        .card-custom { border: none; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: transform 0.3s ease; }
        .card-custom:hover { transform: translateY(-5px); box-shadow: 0 12px 20px rgba(0,0,0,0.08); }
            .header-blue {
            background: linear-gradient(90deg, #2c3e50 0%, #1a252f 100%);
            color: white; padding: 15px 20px; font-weight: 600;
            border-radius: 16px 16px 0 0; letter-spacing: 0.5px;
        }
        .badge-trend {
            display: inline-block; font-size: 11px;
            padding: 3px 10px; border-radius: 20px;
            font-weight: 600; margin-top: 6px;
        }
        .badge-trend.up   { background: #d4edda; color: #155724; }
        .badge-trend.down { background: #f8d7da; color: #721c24; }
        .badge-trend.flat { background: #e2e3e5; color: #383d41; }
        .btn-period {
            padding: 5px 16px; border: 2px solid rgba(255,255,255,0.6);
            border-radius: 20px; background: transparent; cursor: pointer;
            font-size: 12px; font-family: 'Poppins', sans-serif;
            color: rgba(255,255,255,0.7); margin-left: 6px; transition: all 0.2s;
        }
        .btn-period:hover { background: rgba(255,255,255,0.15); color: #fff; border-color: #fff; }
        .btn-period.active { background: #fff; color: #2b6088; border-color: #fff; font-weight: 600; }
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
            <li class="nav-item">
                <a href="index.php" class="nav-link active rounded">
                    <i class="bi bi-house-door me-2"></i> Dashboard Analis
                </a>
            </li>
            <li class="nav-item">
                <a href="laporan_staf.php" class="nav-link rounded">
                    <i class="bi bi-file-earmark-text me-2"></i> Laporan Staf
                </a>
            </li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- TOPBAR -->
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

            <!-- FILTER WAKTU -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold text-muted mb-0">Ringkasan Data Berita</h6>
                <form method="GET" action="index.php" class="d-flex align-items-center bg-white p-2 rounded-pill shadow-sm border" style="min-width: 200px;">
                    <i class="bi bi-calendar3 text-primary ms-2 me-2"></i>
                    <select name="filter" class="form-select form-select-sm border-0 fw-bold text-secondary shadow-none bg-transparent" style="cursor: pointer; outline: none;" onchange="this.form.submit()">
                        <option value="semua"      <?php if($filter == 'semua')     echo 'selected'; ?>>Semua Waktu</option>
                        <option value="hari_ini"   <?php if($filter == 'hari_ini')  echo 'selected'; ?>>Hari Ini</option>
                        <option value="minggu_ini" <?php if($filter == 'minggu_ini')echo 'selected'; ?>>7 Hari Terakhir</option>
                        <option value="bulan_ini"  <?php if($filter == 'bulan_ini') echo 'selected'; ?>>Bulan Ini</option>
                    </select>
                </form>
            </div>

            <!-- STAT CARDS -->
            <div class="row g-3 mb-4 text-center">
                <div class="col-md-3">
                    <div class="card card-custom p-4 border-bottom border-4" style="border-color: #1a3d4a!important;">
                        <h1 class="fw-bold display-5" style="color:##1a3d4a;"><?php echo number_format($total_laporan, 0, ',', '.'); ?></h1>
                        <p class="text-muted small fw-bold mb-2">TOTAL LAPORAN</p>
                        <i class="bi bi-file-earmark-text-fill fs-1" style="color:#1a3d4a;"></i>
                        <div class="mt-2"><?php echo badgePerubahan($perubahan_total); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-custom p-4 border-bottom border-4" style="border-color: #4a90c4!important;">
                        <h1 class="fw-bold display-5" style="color:#4a90c4;"><?php echo number_format($total_positive, 0, ',', '.'); ?></h1>
                        <p class="text-muted small fw-bold mb-2">LAPORAN POSITIVE</p>
                        <i class="bi bi-check-circle-fill fs-1" style="color:#4a90c4;"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-custom p-4 border-bottom border-4" style="border-color: #cb4335 !important;">
                        <h1 class="fw-bold display-5" style="color:#cb4335;"><?php echo number_format($total_negative, 0, ',', '.'); ?></h1>
                        <p class="text-muted small fw-bold mb-2">LAPORAN NEGATIVE</p>
                        <i class="bi bi-exclamation-triangle-fill fs-1" style="color:#cb4335;"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-custom p-4 border-bottom border-4" style="border-color: #808b96 !important;">
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
                        <div class="card-body">
                            <div style="height: 300px;">
                                <canvas id="grafikLaporan"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-custom">
                        <div class="header-blue"><i class="bi bi-pie-chart-fill me-2"></i> Persentase Status Laporan</div>
                        <div class="card-body">
                            <div style="height: 300px;">
                                <canvas id="pieChartLaporan"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CHART BARIS 2: Tren Waktu -->
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
                        <div class="card-body">
                            <div style="height: 300px;">
                                <canvas id="trendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- WORDCLOUD -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card card-custom">
                        <div class="header-blue">
                            <i class="bi bi-chat-quote-fill me-2"></i> Keyword Dominan per Tone
                        </div>
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
                        <div class="header-blue">
                            <i class="bi bi-newspaper me-2"></i> Top 10 Media Terbanyak
                        </div>
                        <div class="card-body">
                            <div style="height: 350px;">
                                <canvas id="mediaChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /container-fluid -->
    </div><!-- /main-content -->
</div>

<script>
// Warna tema
const C_POSITIVE = '#4a90c4';
const C_NEGATIVE = '#cb4335';
const C_NEUTRAL  = '#808b96';

// ===== BAR CHART =====
const ctx = document.getElementById('grafikLaporan').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Positive', 'Negative', 'Neutral'],
        datasets: [{
            label: 'Jumlah Laporan',
            data: [<?php echo $total_positive; ?>, <?php echo $total_negative; ?>, <?php echo $total_neutral; ?>],
            backgroundColor: [C_POSITIVE, C_NEGATIVE, C_NEUTRAL],
            borderRadius: 8,
            barThickness: 50
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        return ' ' + ctx.label + ': ' + ctx.raw.toLocaleString('id-ID');
                    }
                }
            }
        },
        scales: {
            y: {
                type: 'logarithmic',
                grid: { display: false },
                ticks: {
                    callback: function(value) {
                        if ([1, 10, 100, 1000, 10000, 100000].includes(value)) {
                            return value.toLocaleString('id-ID');
                        }
                        return '';
                    }
                }
            },
            x: { grid: { display: false } }
        }
    }
});

// ===== PIE CHART =====
const ctxPie = document.getElementById('pieChartLaporan').getContext('2d');
new Chart(ctxPie, {
    type: 'pie',
    data: {
        labels: ['Positive', 'Negative', 'Neutral'],
        datasets: [{
            data: [<?php echo $total_positive; ?>, <?php echo $total_negative; ?>, <?php echo $total_neutral; ?>],
            backgroundColor: [C_POSITIVE, C_NEGATIVE, C_NEUTRAL],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { padding: 20, font: { family: 'Poppins' } }
            },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                        const pct   = total > 0 ? ((ctx.raw / total) * 100).toFixed(1) : 0;
                        return ` ${ctx.label}: ${ctx.raw.toLocaleString('id-ID')} (${pct}%)`;
                    }
                }
            }
        }
    }
});

// ===== LINE CHART TREN =====
let trendChart = null;
function loadTrend(period) {
    document.querySelectorAll('.btn-period').forEach(b => b.classList.remove('active'));
    document.getElementById('p' + period).classList.add('active');
    fetch('get_trend.php?period=' + period)
        .then(res => res.json())
        .then(data => {
            if (trendChart) trendChart.destroy();
            const ctx = document.getElementById('trendChart').getContext('2d');
            trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Positive',
                            data: data.positive,
                            borderColor: C_POSITIVE,
                            backgroundColor: 'rgba(36,113,163,0.08)',
                            tension: 0.4, fill: true, pointRadius: 3, pointHoverRadius: 6
                        },
                        {
                            label: 'Negative',
                            data: data.negative,
                            borderColor: C_NEGATIVE,
                            backgroundColor: 'rgba(203,67,53,0.08)',
                            tension: 0.4, fill: true, pointRadius: 3, pointHoverRadius: 6
                        },
                        {
                            label: 'Neutral',
                            data: data.neutral,
                            borderColor: C_NEUTRAL,
                            backgroundColor: 'rgba(128,139,150,0.08)',
                            tension: 0.4, fill: true, pointRadius: 3, pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top', labels: { font: { family: 'Poppins', size: 12 }, padding: 16 } },
                        tooltip: { mode: 'index' }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { family: 'Poppins', size: 11 } } },
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { family: 'Poppins', size: 11 } } }
                    }
                }
            });
        })
        .catch(err => console.error('Gagal load data tren:', err));
}
loadTrend(30);

// ===== WORDCLOUD =====
function loadWordcloud(tone, canvasId) {
    fetch('get_wordcloud.php?tone=' + tone)
        .then(res => res.json())
        .then(data => {
            const list = data
                .filter(d => typeof d.text === 'string' && d.text.length > 0)
                .map(d => [d.text, d.value]);
            WordCloud(document.getElementById(canvasId), {
                list: list,
                gridSize: 8,
                weightFactor: function(size) { return Math.log2(size + 1) * 10; },
                fontFamily: 'Poppins, sans-serif',
                color: function() {
                    const shades = {
                        'wc-positive': ['#1a3a5c', '#4a90c4', '#6aaed6', '#9dcbe8'],
                        'wc-negative': ['#922b21', '#cb4335', '#d98880', '#e8b4b8'],
                        'wc-neutral':  ['#4d5656', '#808b96', '#a9b2b9', '#ccd1d1'],
                    };
                    const s = shades[canvasId];
                    return s[Math.floor(Math.random() * s.length)];
                },
                rotateRatio: 0.3,
                rotationSteps: 2,
                backgroundColor: 'transparent',
                drawOutOfBound: false,
            });
        })
        .catch(err => console.error('Gagal load wordcloud ' + tone + ':', err));
}
loadWordcloud('Positive', 'wc-positive');
loadWordcloud('Negative', 'wc-negative');
loadWordcloud('Neutral',  'wc-neutral');

// ===== TOP MEDIA CHART =====
function loadMedia() {
    const filter = '<?php echo $filter; ?>';
    fetch('get_media.php?filter=' + filter)
        .then(res => res.json())
        .then(data => {
            const ctx = document.getElementById('mediaChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.media,
                    datasets: [
                        { label: 'Positive', data: data.positive, backgroundColor: 'rgba(36,113,163,0.85)',  borderRadius: 4 },
                        { label: 'Negative', data: data.negative, backgroundColor: 'rgba(203,67,53,0.85)',   borderRadius: 4 },
                        { label: 'Neutral',  data: data.neutral,  backgroundColor: 'rgba(128,139,150,0.85)', borderRadius: 4 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: { position: 'top', labels: { font: { family: 'Poppins', size: 12 }, padding: 16 } },
                        tooltip: { mode: 'index' }
                    },
                    scales: {
                        x: { stacked: true, grid: { display: false }, ticks: { font: { family: 'Poppins', size: 11 } } },
                        y: { stacked: true, grid: { display: false }, ticks: { font: { family: 'Poppins', size: 11 } } }
                    }
                }
            });
        })
        .catch(err => console.error('Gagal load media chart:', err));
}
loadMedia();
</script>
</body>
</html>