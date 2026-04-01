<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'koneksi.php'; 

// ==========================================
// LOGIKA PAGINATION & PENCARIAN
// ==========================================
$limit = 25; // Kita tampilkan 25 data per halaman biar ringan
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $limit) - $limit : 0;

$cari = isset($_GET['cari']) ? $_GET['cari'] : '';
$kondisi_cari = "";

// Kalau ada yang diketik di kotak pencarian, tambahkan rumus ini
if ($cari != '') {
    // Cari berdasarkan nama influencer ATAU judul berita
    $kondisi_cari = "WHERE influencers LIKE '%$cari%' OR judul_berita LIKE '%$cari%'";
}

// 1. Hitung total data untuk bikin tombol angka halamannya
$query_total = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM laporan $kondisi_cari");
$data_total = mysqli_fetch_assoc($query_total);
$total_data = $data_total['total'];
$total_halaman = ceil($total_data / $limit);

// 2. Tarik data sesuai halaman yang sedang dibuka
$query = mysqli_query($koneksi, "SELECT * FROM laporan $kondisi_cari ORDER BY id DESC LIMIT $halaman_awal, $limit");

// Bikin nomor urutnya nyambung ke halaman berikutnya
$no = $halaman_awal + 1;
// ==========================================
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPANDITA - Laporan Staf</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    body { background-color: #f0f4f8; font-family: 'Poppins', sans-serif; }
    .sidebar { background: linear-gradient(180deg, #1a3d4a 0%, #112933 100%); min-height: 100vh; width: 250px; color: white; position: fixed; box-shadow: 4px 0 15px rgba(0,0,0,0.05); }
    .main-content { margin-left: 250px; width: calc(100% - 250px); }
    .nav-link { color: #aeb9be; font-weight: 500; padding: 12px 20px; transition: all 0.3s ease; }
    .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.1); border-radius: 8px; }
    .header-blue { background: linear-gradient(90deg, #4384b6 0%, #2b6088 100%); color: white; padding: 15px 20px; font-weight: 600; border-radius: 16px 16px 0 0; }
    .table { background-color: white; border-radius: 10px; }
    .table thead th { background-color: #f8fafc; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
    :target { animation: highlight 2s ease-out; }
    @keyframes highlight { 0% { background-color: #fff3cd; } 100% { background-color: transparent; } }
</style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3">
        <h3 class="fw-bold mb-4 mt-2 px-2">SIPANDITA</h3>
        <ul class="nav flex-column gap-2">
            <li class="nav-item"><a href="index.php" class="nav-link"><i class="bi bi-house-door me-2"></i> Dashboard Analis</a></li>
            <li class="nav-item"><a href="laporan_staf.php" class="nav-link active"><i class="bi bi-file-earmark-text me-2"></i> Laporan Staf</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="m-0 fw-bold text-uppercase">Laporan Staf</h5>
            <div class="d-flex align-items-center"><span>Hi, Umar</span><i class="bi bi-person-circle fs-4 ms-2"></i></div>
        </div>

        <div class="container-fluid p-4">
            <div class="card shadow-sm border-0" style="border-radius: 16px;">
                <div class="header-blue d-flex justify-content-between align-items-center">
                    <span>Daftar Laporan Masuk</span>
                    <button class="btn btn-sm btn-light fw-bold text-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="bi bi-plus-lg"></i> Tambah Laporan
                    </button>
                </div>
                
                <div class="bg-light p-3 border-bottom d-flex justify-content-between align-items-center">
                    <span class="text-muted small fw-bold">Total: <?php echo number_format($total_data, 0, ',', '.'); ?> Data</span>
                    <form action="laporan_staf.php" method="GET" class="d-flex" style="width: 350px;">
                        <div class="input-group input-group-sm shadow-sm">
                            <input type="text" name="cari" class="form-control border-0" placeholder="Cari nama staf atau judul laporan..." value="<?php echo htmlspecialchars($cari); ?>">
                            <button type="submit" class="btn btn-primary px-3"><i class="bi bi-search"></i> Cari</button>
                            <?php if($cari != '') { ?>
                                <a href="laporan_staf.php" class="btn btn-danger"><i class="bi bi-x-lg"></i></a>
                            <?php } ?>
                        </div>
                    </form>
                </div>

                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="text-center">
                                <th width="5%">No</th>
                                <th width="12%">Tanggal</th>
                                <th width="15%">Nama Staf</th>
                                <th class="text-start">Judul Laporan</th>
                                <th width="12%">Status</th>
                                <th width="12%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        if(mysqli_num_rows($query) > 0) {
                            while($data = mysqli_fetch_array($query)) { ?>
                            <tr id="baris-<?php echo $data['id']; ?>">
                                <td class="text-center fw-bold text-muted"><?php echo $no++; ?></td>
                                <td class="text-center" style="font-size: 0.85rem;"><?php echo date('d M Y', strtotime($data['tanggal'])); ?></td>
                                <td class="text-center text-muted fw-medium"><?php echo !empty($data['influencers']) ? $data['influencers'] : '-'; ?></td>
                                <td class="text-start"><?php echo $data['judul_berita']; ?></td>
                                <td class="text-center">
                                    <?php 
                                    $warna = "bg-secondary"; 
                                    if($data['tone'] == 'Positive') $warna = "bg-primary"; 
                                    if($data['tone'] == 'Negative') $warna = "bg-danger"; 
                                    ?>
                                    <span class="badge <?php echo $warna; ?> rounded-pill px-3"><?php echo $data['tone']; ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="edit_laporan.php?id=<?php echo $data['id']; ?>" class="btn btn-sm btn-warning text-white"><i class="bi bi-pencil-square"></i></a>
                                        <a href="hapus_laporan.php?id=<?php echo $data['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin mau hapus data ini?')"><i class="bi bi-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php } 
                        } else { ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted fw-bold">Yah, data tidak ditemukan.</td></tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>

                <?php if($total_halaman > 1) { ?>
                <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center border-0">
                    <span class="small text-muted fw-bold">Halaman <?php echo $halaman; ?> dari <?php echo $total_halaman; ?></span>
                    <nav>
                        <ul class="pagination pagination-sm mb-0 shadow-sm">
                            <li class="page-item <?php if($halaman <= 1) { echo 'disabled'; } ?>">
                                <a class="page-link text-primary" href="?halaman=<?php echo $halaman - 1; ?><?php if($cari != '') echo '&cari='.$cari; ?>">Prev</a>
                            </li>
                            
                            <?php 
                            // Tampilkan maksimal 5 kotak angka biar nggak kepanjangan
                            $start_page = max(1, $halaman - 2);
                            $end_page = min($total_halaman, $halaman + 2);

                            for($x = $start_page; $x <= $end_page; $x++) {
                                $active = ($x == $halaman) ? 'active bg-primary border-primary' : '';
                                $text_color = ($x == $halaman) ? 'text-white' : 'text-primary';
                            ?>
                                <li class="page-item <?php echo $active; ?>">
                                    <a class="page-link <?php echo $text_color; ?>" href="?halaman=<?php echo $x; ?><?php if($cari != '') echo '&cari='.$cari; ?>"><?php echo $x; ?></a>
                                </li>
                            <?php } ?>

                            <li class="page-item <?php if($halaman >= $total_halaman) { echo 'disabled'; } ?>">
                                <a class="page-link text-primary" href="?halaman=<?php echo $halaman + 1; ?><?php if($cari != '') echo '&cari='.$cari; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php } ?>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border-radius: 16px;">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Input Laporan Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="simpan_laporan.php" method="POST">
          <div class="modal-body">
                <div class="mb-3"><label class="form-label small fw-bold">Nama Staf (Influencer)</label><input type="text" name="influencers" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small fw-bold">Judul Laporan Berita</label><textarea name="judul_berita" class="form-control" rows="3" required></textarea></div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Tone / Status</label>
                    <select name="tone" class="form-select">
                        <option value="Positive">Positive</option><option value="Negative">Negative</option><option value="Neutral">Neutral</option>
                    </select>
                </div>
          </div>
          <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary px-4">Simpan Laporan</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>