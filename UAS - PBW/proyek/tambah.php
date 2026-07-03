<?php
$prefix = '../';
require_once $prefix . 'config/koneksi.php';
require_once $prefix . 'includes/auth.php';
require_login($prefix);
require_admin($prefix);

$nama_proyek = '';
$jenis_proyek = '';
$lokasi = '';
$tanggal_mulai = '';
$tanggal_selesai = '';
$status = 'Perencanaan';
$anggaran = '';
$deskripsi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_proyek = trim($_POST['nama_proyek'] ?? '');
    $jenis_proyek = trim($_POST['jenis_proyek'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? '');
    $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
    $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
    $status = $_POST['status'] ?? 'Perencanaan';
    $anggaran = normalisasi_anggaran($_POST['anggaran'] ?? '0');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($nama_proyek === '' || $jenis_proyek === '' || $lokasi === '' || $tanggal_mulai === '' || $tanggal_selesai === '') {
        set_flash('error', 'Data proyek belum lengkap.');
    } elseif (!valid_date($tanggal_mulai) || !valid_date($tanggal_selesai)) {
        set_flash('error', 'Format tanggal tidak valid.');
    } elseif (strtotime($tanggal_selesai) < strtotime($tanggal_mulai)) {
        set_flash('error', 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.');
    } elseif (!in_array($status, status_options(), true)) {
        set_flash('error', 'Status proyek tidak valid.');
    } elseif ($anggaran < 0) {
        set_flash('error', 'Anggaran tidak boleh bernilai negatif.');
    } else {
        $stmt = mysqli_prepare($conn, 'INSERT INTO proyek (nama_proyek, jenis_proyek, lokasi, tanggal_mulai, tanggal_selesai, status, anggaran_awal, anggaran, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'ssssssdds', $nama_proyek, $jenis_proyek, $lokasi, $tanggal_mulai, $tanggal_selesai, $status, $anggaran, $anggaran, $deskripsi);
        if ($stmt && mysqli_stmt_execute($stmt)) {
            set_flash('success', 'Data proyek berhasil ditambahkan. Anggaran awal dikunci dan perubahan berikutnya wajib melalui revisi anggaran.');
            header('Location: index.php');
            exit;
        }
        set_flash('error', 'Gagal menambah proyek. Silakan cek kembali data yang diisi.');
    }
}
$page_title = 'Tambah Proyek - SIMPI';
$active = 'proyek';
include $prefix . 'includes/header.php';
include $prefix . 'includes/navbar.php';
?>
<main class="container py-4">
  <?php show_flash(); ?>
  <div class="card content-card"><div class="card-body">
    <h2 class="fw-bold mb-1">Tambah Proyek</h2>
    <p class="text-muted mb-3">Anggaran yang diisi menjadi anggaran awal. Setelah proyek tersimpan, perubahan anggaran dicatat sebagai riwayat revisi.</p>
    <form method="post">
      <div class="mb-3"><label class="form-label">Nama Proyek</label><input type="text" name="nama_proyek" class="form-control" value="<?= e($nama_proyek); ?>" required></div>
      <div class="mb-3"><label class="form-label">Jenis Proyek</label><input type="text" name="jenis_proyek" class="form-control" value="<?= e($jenis_proyek); ?>" placeholder="Jalan, Drainase, Jembatan, Fasilitas Umum" required></div>
      <div class="mb-3"><label class="form-label">Lokasi</label><input type="text" name="lokasi" class="form-control" value="<?= e($lokasi); ?>" required></div>
      <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Tanggal Mulai</label><input type="date" name="tanggal_mulai" class="form-control" value="<?= e($tanggal_mulai); ?>" required></div>
        <div class="col-md-6 mb-3"><label class="form-label">Tanggal Selesai</label><input type="date" name="tanggal_selesai" class="form-control" value="<?= e($tanggal_selesai); ?>" required></div>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><?php foreach (status_options() as $s): ?><option value="<?= e($s); ?>" <?= $status == $s ? 'selected' : ''; ?>><?= e($s); ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6 mb-3"><label class="form-label">Anggaran Awal</label><input type="number" name="anggaran" class="form-control" min="0" step="1000" value="<?= e($anggaran); ?>" placeholder="Contoh: 150000000" required><div class="form-text">Anggaran awal akan terkunci setelah proyek dibuat.</div></div>
      </div>
      <div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control" rows="4"><?= e($deskripsi); ?></textarea></div>
      <button class="btn btn-primary">Simpan</button> <a href="index.php" class="btn btn-secondary">Kembali</a>
    </form>
  </div></div>
</main>
<?php include $prefix . 'includes/footer.php'; ?>
