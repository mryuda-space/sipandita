<?php
include 'koneksi.php';
$id    = mysqli_real_escape_string($koneksi, $_GET['id']);
$query = mysqli_query($koneksi, "SELECT * FROM laporan WHERE id='$id'");
$data  = mysqli_fetch_assoc($query);
if (!$data) {
    echo "<script>alert('Data tidak ditemukan!'); window.location='laporan_staf.php';</script>";
    exit();
}
$jenis = !empty($data['jenis_media']) ? $data['jenis_media'] : 'online';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Laporan - SIPANDITA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f0f4f8; font-family: 'Poppins', sans-serif; }
        .card-edit { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .form-label { font-weight: 600; font-size: 0.9rem; color: #475569; }
        .section-cetak { display: none; }
        .section-cetak.show { display: block; }
    </style>
</head>
<body class="p-5">
    <div class="container">
        <div class="card card-edit p-4 mx-auto" style="max-width: 600px;">
            <h4 class="fw-bold mb-4">Edit Laporan</h4>
            <form action="proses_edit.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $data['id']; ?>">

                <div class="mb-3">
                    <label class="form-label">Nama Staf (Influencer)</label>
                    <input type="text" name="influencers" class="form-control" value="<?php echo htmlspecialchars($data['influencers']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Judul Laporan</label>
                    <textarea name="judul_berita" class="form-control" rows="4" required><?php echo htmlspecialchars($data['judul_berita']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jenis Media</label>
                    <select name="jenis_media" class="form-select" id="jenis_media" onchange="toggleCetak(this.value)">
                        <option value="online" <?php if($jenis == 'online') echo 'selected'; ?>>🌐 Online</option>
                        <option value="cetak"  <?php if($jenis == 'cetak')  echo 'selected'; ?>>📰 Cetak</option>
                    </select>
                </div>

                <!-- Field khusus cetak -->
                <div class="section-cetak <?php echo $jenis == 'cetak' ? 'show' : ''; ?>" id="section-cetak">
                    <div class="mb-3">
                        <label class="form-label">Halaman</label>
                        <input type="text" name="halaman" class="form-control" placeholder="Contoh: 1, 5, 12" value="<?php echo htmlspecialchars($data['halaman'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bulan Terbit</label>
                        <select name="bulan_cetak" class="form-select">
                            <?php
                            $bulan_list = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                            foreach ($bulan_list as $b):
                            ?>
                            <option value="<?php echo $b; ?>" <?php if(($data['bulan_cetak'] ?? '') == $b) echo 'selected'; ?>><?php echo $b; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Status (Tone)</label>
                    <select name="tone" class="form-select">
                        <option value="Positive" <?php if($data['tone'] == 'Positive') echo 'selected'; ?>>Positive</option>
                        <option value="Negative" <?php if($data['tone'] == 'Negative') echo 'selected'; ?>>Negative</option>
                        <option value="Neutral"  <?php if($data['tone'] == 'Neutral')  echo 'selected'; ?>>Neutral</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <a href="laporan_staf.php" class="btn btn-secondary px-4">Batal</a>
                    <button type="submit" class="btn btn-success px-4">Update Data</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    function toggleCetak(val) {
        const section = document.getElementById('section-cetak');
        if (val === 'cetak') section.classList.add('show');
        else section.classList.remove('show');
    }
    </script>
</body>
</html>