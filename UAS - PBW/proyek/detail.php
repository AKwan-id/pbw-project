<?php
$prefix = '../';
require_once $prefix . 'config/koneksi.php';
require_once $prefix . 'includes/auth.php';
require_login($prefix);

$id = (int)($_GET['id'] ?? 0);
$stmt = mysqli_prepare($conn, 'SELECT * FROM proyek WHERE id_proyek=?');
mysqli_stmt_bind_param($stmt, 'i', $id);
if (!$stmt || !mysqli_stmt_execute($stmt)) {
    set_flash('error', 'Gagal membaca data proyek.');
    header('Location: index.php');
    exit;
}
$proyek = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$proyek) {
    set_flash('error', 'Data proyek tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$stmt = mysqli_prepare($conn, 'SELECT p.*, u.nama AS nama_pemilik, u.username AS username_pemilik FROM progres p JOIN `user` u ON p.created_by = u.id_user WHERE p.id_proyek=? ORDER BY p.tanggal_laporan DESC, p.id_progres DESC');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$progres = mysqli_stmt_get_result($stmt);

$stmt = mysqli_prepare($conn, 'SELECT COALESCE(persentase,0) AS nilai FROM progres WHERE id_proyek=? ORDER BY tanggal_laporan DESC, id_progres DESC LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$akhir = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$progres_akhir = $akhir ? (int)$akhir['nilai'] : 0;

$anggaran_stmt = mysqli_prepare($conn, 'SELECT ar.*, u.nama AS nama_admin, u.username AS username_admin FROM anggaran_revisi ar JOIN `user` u ON ar.revised_by=u.id_user WHERE ar.id_proyek=? ORDER BY ar.revised_at DESC, ar.id_revisi DESC');
mysqli_stmt_bind_param($anggaran_stmt, 'i', $id);
mysqli_stmt_execute($anggaran_stmt);
$anggaran_revisi = mysqli_stmt_get_result($anggaran_stmt);

$progres_revisi_stmt = mysqli_prepare($conn, 'SELECT rr.*, p.tanggal_laporan, pr.nama_proyek, u.nama AS nama_petugas, u.username AS username_petugas FROM progres_revisi rr JOIN progres p ON rr.id_progres=p.id_progres JOIN proyek pr ON p.id_proyek=pr.id_proyek JOIN `user` u ON rr.revised_by=u.id_user WHERE p.id_proyek=? ORDER BY rr.revised_at DESC, rr.id_revisi DESC LIMIT 50');
mysqli_stmt_bind_param($progres_revisi_stmt, 'i', $id);
mysqli_stmt_execute($progres_revisi_stmt);
$progres_revisi = mysqli_stmt_get_result($progres_revisi_stmt);

$page_title = 'Detail Proyek - SIMPI';
$active = 'proyek';
include $prefix . 'includes/header.php';
include $prefix . 'includes/navbar.php';
?>
<main class="container py-4">
  <?php show_flash(); ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div><h2 class="fw-bold mb-0">Detail Proyek</h2><p class="text-muted mb-0">Ringkasan data, anggaran, dan riwayat progres proyek.</p></div>
    <div class="d-flex gap-2 flex-wrap">
      <?php if (is_admin()): ?><a href="revisi_anggaran.php?id=<?= e($id); ?>" class="btn btn-outline-warning">Revisi Anggaran</a><?php endif; ?>
      <a href="cetak.php?id=<?= e($id); ?>" target="_blank" class="btn btn-outline-primary">Cetak</a>
      <a href="index.php" class="btn btn-secondary">Kembali</a>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-lg-8">
      <div class="card content-card"><div class="card-body">
        <h4 class="fw-bold"><?= e($proyek['nama_proyek']); ?></h4>
        <span class="badge <?= e(status_badge_class($proyek['status'])); ?> badge-status mb-3"><?= e($proyek['status']); ?></span>
        <div class="row g-3">
          <div class="col-md-6"><div class="detail-label">Jenis Proyek</div><div class="detail-value"><?= e($proyek['jenis_proyek']); ?></div></div>
          <div class="col-md-6"><div class="detail-label">Lokasi</div><div class="detail-value"><?= e($proyek['lokasi']); ?></div></div>
          <div class="col-md-6"><div class="detail-label">Tanggal Mulai</div><div class="detail-value"><?= e(tanggal_id($proyek['tanggal_mulai'])); ?></div></div>
          <div class="col-md-6"><div class="detail-label">Tanggal Selesai</div><div class="detail-value"><?= e(tanggal_id($proyek['tanggal_selesai'])); ?></div></div>
          <div class="col-md-6"><div class="detail-label">Anggaran Awal</div><div class="detail-value"><?= e(format_rupiah($proyek['anggaran_awal'])); ?></div></div>
          <div class="col-md-6"><div class="detail-label">Anggaran Saat Ini</div><div class="detail-value"><?= e(format_rupiah($proyek['anggaran'])); ?></div></div>
        </div>
        <hr>
        <div class="detail-label">Deskripsi</div>
        <p class="mb-0"><?= nl2br(e($proyek['deskripsi'])); ?></p>
      </div></div>
    </div>
    <div class="col-lg-4">
      <div class="card content-card"><div class="card-body">
        <div class="detail-label">Progres Terakhir</div>
        <h2 class="fw-bold"><?= e($progres_akhir); ?>%</h2>
        <div class="progress mb-3"><div class="progress-bar <?= e(progress_bar_class($progres_akhir)); ?>" style="width: <?= e($progres_akhir); ?>%;"><?= e($progres_akhir); ?>%</div></div>
        <?php if (!is_admin()): ?><a href="<?= e($prefix); ?>progres/tambah.php?id_proyek=<?= e($id); ?>" class="btn btn-primary w-100">Tambah Laporan Progres</a><?php else: ?><div class="alert alert-info small mb-0">Admin dapat merevisi anggaran, tetapi tidak mengubah laporan progres petugas.</div><?php endif; ?>
      </div></div>
    </div>
  </div>

  <div class="card content-card mb-4"><div class="card-header bg-white fw-bold">Riwayat Revisi Anggaran</div><div class="card-body table-responsive">
    <table class="table table-hover align-middle table-modern">
      <thead><tr><th>No</th><th>Waktu</th><th>Nilai</th><th>Alasan</th><th>Admin</th></tr></thead>
      <tbody>
        <?php if (!$anggaran_revisi || mysqli_num_rows($anggaran_revisi) === 0): ?>
          <tr><td colspan="5" class="text-center text-muted">Belum ada revisi anggaran.</td></tr>
        <?php endif; ?>
        <?php $no=1; while ($anggaran_revisi && $row = mysqli_fetch_assoc($anggaran_revisi)): ?>
          <tr>
            <td><?= $no++; ?></td>
            <td><?= e(tanggal_waktu_id($row['revised_at'])); ?></td>
            <td><b><?= e(format_rupiah($row['anggaran_lama'])); ?></b><br><span class="text-muted small">menjadi</span><br><b><?= e(format_rupiah($row['anggaran_baru'])); ?></b></td>
            <td><?= e($row['alasan']); ?></td>
            <td><?= e($row['nama_admin']); ?><div class="text-muted small">@<?= e($row['username_admin']); ?></div></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div></div>

  <div class="card content-card mb-4"><div class="card-header bg-white fw-bold">Riwayat Progres</div><div class="card-body table-responsive">
    <table class="table table-hover align-middle table-modern">
      <thead><tr><th>No</th><th>Tanggal</th><th>Pemilik</th><th>Persentase</th><th>Keterangan</th><th>Dokumentasi</th></tr></thead>
      <tbody>
        <?php if (!$progres || mysqli_num_rows($progres) === 0): ?>
          <tr><td colspan="6" class="text-center text-muted">Belum ada laporan progres.</td></tr>
        <?php endif; ?>
        <?php $no=1; while ($progres && $row = mysqli_fetch_assoc($progres)): ?>
          <tr>
            <td><?= $no++; ?></td>
            <td><?= e(tanggal_id($row['tanggal_laporan'])); ?></td>
            <td><span class="owner-label"><?= e($row['nama_pemilik']); ?></span><div class="text-muted small">@<?= e($row['username_pemilik']); ?></div></td>
            <td style="min-width:170px;"><div class="progress"><div class="progress-bar <?= e(progress_bar_class($row['persentase'])); ?>" style="width: <?= e($row['persentase']); ?>%;"><?= e($row['persentase']); ?>%</div></div></td>
            <td><?= e($row['keterangan']); ?></td>
            <td><?= e($row['dokumentasi']); ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div></div>

  <div class="card content-card"><div class="card-header bg-white fw-bold">Riwayat Revisi Progres</div><div class="card-body table-responsive">
    <table class="table table-hover align-middle table-modern">
      <thead><tr><th>No</th><th>Waktu</th><th>Petugas</th><th>Persentase</th><th>Alasan</th><th>Ringkasan Perubahan</th></tr></thead>
      <tbody>
        <?php if (!$progres_revisi || mysqli_num_rows($progres_revisi) === 0): ?>
          <tr><td colspan="6" class="text-center text-muted">Belum ada revisi progres.</td></tr>
        <?php endif; ?>
        <?php $no=1; while ($progres_revisi && $row = mysqli_fetch_assoc($progres_revisi)): ?>
          <tr>
            <td><?= $no++; ?></td>
            <td><?= e(tanggal_waktu_id($row['revised_at'])); ?></td>
            <td><?= e($row['nama_petugas']); ?><div class="text-muted small">@<?= e($row['username_petugas']); ?></div></td>
            <td><b><?= e($row['persentase_lama']); ?>%</b> <span class="text-muted">→</span> <b><?= e($row['persentase_baru']); ?>%</b></td>
            <td><?= e($row['alasan']); ?></td>
            <td><span class="text-muted small">Keterangan:</span> <?= e(ringkas_teks($row['keterangan_lama'], 45)); ?> <span class="text-muted">→</span> <?= e(ringkas_teks($row['keterangan_baru'], 45)); ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include $prefix . 'includes/footer.php'; ?>
