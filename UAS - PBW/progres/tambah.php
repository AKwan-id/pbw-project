<?php
$prefix = '../';
require_once $prefix . 'config/koneksi.php';
require_once $prefix . 'includes/auth.php';
require_login($prefix);

if (is_admin()) {
    set_flash('error', 'Akses pembuatan laporan progres tersedia untuk petugas.');
    header('Location: index.php');
    exit;
}

$id_proyek = (int)($_GET['id_proyek'] ?? 0);
$tanggal_laporan = date('Y-m-d');
$persentase = '';
$keterangan = '';
$dokumentasi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_proyek = (int)($_POST['id_proyek'] ?? 0);
    $tanggal_laporan = $_POST['tanggal_laporan'] ?? '';
    $persentase_input = $_POST['persentase'] ?? '';
    $persentase = is_numeric($persentase_input) ? (int)$persentase_input : $persentase_input;
    $keterangan = trim($_POST['keterangan'] ?? '');
    $dokumentasi = trim($_POST['dokumentasi'] ?? '');
    $created_by = current_user_id();

    if ($id_proyek <= 0 || $tanggal_laporan === '' || $keterangan === '' || !valid_percent($persentase_input)) {
        set_flash('error', 'Data progres belum valid. Persentase harus angka 0 sampai 100.');
    } elseif (!valid_date($tanggal_laporan)) {
        set_flash('error', 'Format tanggal laporan tidak valid.');
    } elseif (!project_exists($conn, $id_proyek)) {
        set_flash('error', 'Proyek yang dipilih tidak ditemukan.');
    } else {
        $stmt = mysqli_prepare($conn, 'INSERT INTO progres (id_proyek, created_by, tanggal_laporan, persentase, keterangan, dokumentasi, is_locked) VALUES (?, ?, ?, ?, ?, ?, 0)');
        mysqli_stmt_bind_param($stmt, 'iisiss', $id_proyek, $created_by, $tanggal_laporan, $persentase, $keterangan, $dokumentasi);
        if ($stmt && mysqli_stmt_execute($stmt)) {
            set_flash('success', 'Data progres berhasil ditambahkan. Laporan otomatis menjadi milik akun yang sedang login.');
            header('Location: index.php');
            exit;
        }
        set_flash('error', 'Gagal menambah progres. Silakan cek kembali data yang diisi.');
    }
}
$list_proyek = mysqli_query($conn, 'SELECT id_proyek, nama_proyek FROM proyek ORDER BY nama_proyek ASC');
$page_title = 'Tambah Progres - SIMPI';
$active = 'progres';
include $prefix . 'includes/header.php';
include $prefix . 'includes/navbar.php';
?>
<main class="container py-4">
  <?php show_flash(); ?>
  <div class="card content-card"><div class="card-body">
    <h2 class="fw-bold mb-1">Tambah Progres</h2>
    <p class="text-muted mb-3">Laporan ini akan tercatat sebagai milik akun: <b><?= e($_SESSION['nama']); ?></b>.</p>
    <form method="post" onsubmit="return validateProgressForm()">
      <div class="mb-3"><label class="form-label">Nama Proyek</label><select name="id_proyek" class="form-select" required><option value="">Pilih proyek</option><?php while ($list_proyek && $p = mysqli_fetch_assoc($list_proyek)): ?><option value="<?= e($p['id_proyek']); ?>" <?= $id_proyek === (int)$p['id_proyek'] ? 'selected' : ''; ?>><?= e($p['nama_proyek']); ?></option><?php endwhile; ?></select></div>
      <div class="mb-3"><label class="form-label">Tanggal Laporan</label><input type="date" name="tanggal_laporan" class="form-control" value="<?= e($tanggal_laporan); ?>" required></div>
      <div class="mb-3"><label class="form-label">Persentase Progres</label><input type="number" min="0" max="100" step="1" id="persentase" name="persentase" class="form-control" value="<?= e($persentase); ?>" required></div>
      <div class="mb-3"><label class="form-label">Keterangan</label><textarea name="keterangan" class="form-control" rows="4" required><?= e($keterangan); ?></textarea></div>
      <div class="mb-3"><label class="form-label">Dokumentasi</label><input type="text" name="dokumentasi" class="form-control" value="<?= e($dokumentasi); ?>" placeholder="Nama file atau tautan dokumentasi"><div class="form-text">Masukkan nama file atau tautan dokumentasi pendukung.</div></div>
      <button class="btn btn-primary">Simpan</button> <a href="index.php" class="btn btn-secondary">Kembali</a>
    </form>
  </div></div>
</main>
<?php include $prefix . 'includes/footer.php'; ?>
