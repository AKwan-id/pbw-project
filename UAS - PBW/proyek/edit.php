<?php
$prefix = '../';
require_once $prefix . 'config/koneksi.php';
require_once $prefix . 'includes/auth.php';
require_login($prefix);
require_admin($prefix);

$id = (int)($_GET['id'] ?? 0);
$stmt = mysqli_prepare($conn, 'SELECT * FROM proyek WHERE id_proyek=?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$data) {
    set_flash('error', 'Data proyek tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$nama_proyek = $data['nama_proyek'];
$jenis_proyek = $data['jenis_proyek'];
$lokasi = $data['lokasi'];
$tanggal_mulai = $data['tanggal_mulai'];
$tanggal_selesai = $data['tanggal_selesai'];
$status = $data['status'];
$deskripsi = $data['deskripsi'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_proyek = trim($_POST['nama_proyek'] ?? '');
    $jenis_proyek = trim($_POST['jenis_proyek'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? '');
    $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
    $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
    $status = $_POST['status'] ?? 'Perencanaan';
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($nama_proyek === '' || $jenis_proyek === '' || $lokasi === '' || $tanggal_mulai === '' || $tanggal_selesai === '') {
        set_flash('error', 'Data proyek belum lengkap.');
    } elseif (!valid_date($tanggal_mulai) || !valid_date($tanggal_selesai)) {
        set_flash('error', 'Format tanggal tidak valid.');
    } elseif (strtotime($tanggal_selesai) < strtotime($tanggal_mulai)) {
        set_flash('error', 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.');
    } elseif (!in_array($status, status_options(), true)) {
        set_flash('error', 'Status proyek tidak valid.');
    } else {
        $stmt = mysqli_prepare($conn, 'UPDATE proyek SET nama_proyek=?, jenis_proyek=?, lokasi=?, tanggal_mulai=?, tanggal_selesai=?, status=?, deskripsi=? WHERE id_proyek=?');
        mysqli_stmt_bind_param($stmt, 'sssssssi', $nama_proyek, $jenis_proyek, $lokasi, $tanggal_mulai, $tanggal_selesai, $status, $deskripsi, $id);
        if ($stmt && mysqli_stmt_execute($stmt)) {
            set_flash('success', 'Data proyek berhasil diperbarui. Anggaran tidak ditimpa dari halaman edit; gunakan Revisi Anggaran untuk perubahan nilai.');
            header('Location: index.php');
            exit;
        }
        set_flash('error', 'Gagal memperbarui proyek. Silakan cek kembali data yang diisi.');
    }
}
$page_title = 'Edit Proyek - SIMPI';
$active = 'proyek';
include $prefix . 'includes/header.php';
include $prefix . 'includes/navbar.php';
?>
<main class="container py-4">
  <?php show_flash(); ?>
  <div class="card content-card"><div class="card-body">
    <h2 class="fw-bold mb-1">Edit Proyek</h2>
    <p class="text-muted mb-3">Data teknis proyek dapat diperbarui. Anggaran dikunci dan perubahan nilai wajib melalui riwayat revisi.</p>
    <div class="row g-3 mb-3">
      <div class="col-md-6"><div class="security-box"><span>Anggaran Awal</span><b><?= e(format_rupiah($data['anggaran_awal'])); ?></b></div></div>
      <div class="col-md-6"><div class="security-box"><span>Anggaran Saat Ini</span><b><?= e(format_rupiah($data['anggaran'])); ?></b></div></div>
    </div>
    <form method="post">
      <div class="mb-3"><label class="form-label">Nama Proyek</label><input type="text" name="nama_proyek" class="form-control" value="<?= e($nama_proyek); ?>" required></div>
      <div class="mb-3"><label class="form-label">Jenis Proyek</label><input type="text" name="jenis_proyek" class="form-control" value="<?= e($jenis_proyek); ?>" required></div>
      <div class="mb-3"><label class="form-label">Lokasi</label><input type="text" name="lokasi" class="form-control" value="<?= e($lokasi); ?>" required></div>
      <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Tanggal Mulai</label><input type="date" name="tanggal_mulai" class="form-control" value="<?= e($tanggal_mulai); ?>" required></div><div class="col-md-6 mb-3"><label class="form-label">Tanggal Selesai</label><input type="date" name="tanggal_selesai" class="form-control" value="<?= e($tanggal_selesai); ?>" required></div></div>
      <div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><?php foreach (status_options() as $s): ?><option value="<?= e($s); ?>" <?= $status == $s ? 'selected' : ''; ?>><?= e($s); ?></option><?php endforeach; ?></select></div>
      <div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control" rows="4"><?= e($deskripsi); ?></textarea></div>
      <button class="btn btn-primary">Update</button>
      <a href="revisi_anggaran.php?id=<?= e($id); ?>" class="btn btn-outline-warning">Revisi Anggaran</a>
      <a href="index.php" class="btn btn-secondary">Kembali</a>
    </form>
  </div></div>
</main>
<?php include $prefix . 'includes/footer.php'; ?>
