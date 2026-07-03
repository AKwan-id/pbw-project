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

$stmt = mysqli_prepare($conn, 'SELECT p.*, u.nama AS nama_pemilik, u.username AS username_pemilik FROM progres p JOIN `user` u ON p.created_by = u.id_user WHERE p.id_proyek=? ORDER BY p.tanggal_laporan ASC, p.id_progres ASC');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$progres = mysqli_stmt_get_result($stmt);

$stmt = mysqli_prepare($conn, 'SELECT COALESCE(persentase,0) AS nilai FROM progres WHERE id_proyek=? ORDER BY tanggal_laporan DESC, id_progres DESC LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$akhir = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$progres_akhir = $akhir ? (int)$akhir['nilai'] : 0;

$anggaran_stmt = mysqli_prepare($conn, 'SELECT ar.*, u.nama AS nama_admin, u.username AS username_admin FROM anggaran_revisi ar JOIN `user` u ON ar.revised_by=u.id_user WHERE ar.id_proyek=? ORDER BY ar.revised_at ASC, ar.id_revisi ASC');
mysqli_stmt_bind_param($anggaran_stmt, 'i', $id);
mysqli_stmt_execute($anggaran_stmt);
$anggaran_revisi = mysqli_stmt_get_result($anggaran_stmt);

$progres_revisi_stmt = mysqli_prepare($conn, 'SELECT rr.*, p.tanggal_laporan, u.nama AS nama_petugas, u.username AS username_petugas FROM progres_revisi rr JOIN progres p ON rr.id_progres=p.id_progres JOIN `user` u ON rr.revised_by=u.id_user WHERE p.id_proyek=? ORDER BY rr.revised_at ASC, rr.id_revisi ASC');
mysqli_stmt_bind_param($progres_revisi_stmt, 'i', $id);
mysqli_stmt_execute($progres_revisi_stmt);
$progres_revisi = mysqli_stmt_get_result($progres_revisi_stmt);

$tanggal_cetak = date('d-m-Y H:i');
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Laporan Proyek - <?= e($proyek['nama_proyek']); ?></title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; color: #1f2937; margin: 0; background: #eef2f6; }
    .page { width: 210mm; min-height: 297mm; margin: 18px auto; background: #fff; padding: 16mm; box-shadow: 0 12px 32px rgba(15, 23, 42, .14); }
    .topbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin: 18px auto 0; width: 210mm; }
    .btn { border: 1px solid #123f6f; background: #123f6f; color: #fff; text-decoration: none; padding: 10px 14px; border-radius: 8px; font-size: 14px; cursor: pointer; }
    .btn.secondary { background: #fff; color: #123f6f; }
    .header { border-bottom: 3px solid #c99a16; padding-bottom: 14px; margin-bottom: 18px; }
    .brand { color: #123f6f; font-weight: 800; font-size: 24px; margin: 0; }
    .subtitle { margin: 4px 0 0; color: #475569; font-size: 13px; }
    h1 { font-size: 22px; margin: 18px 0 10px; color: #0b2d4d; }
    h2 { font-size: 15px; margin: 20px 0 8px; color: #0b2d4d; }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 22px; }
    .item { border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; }
    .label { color: #64748b; font-size: 12px; margin-bottom: 3px; }
    .value { font-size: 14px; font-weight: 700; }
    .progress-wrap { margin-top: 8px; height: 18px; background: #e5e7eb; border-radius: 999px; overflow: hidden; }
    .progress-bar { height: 100%; background: #123f6f; color: #fff; font-size: 11px; font-weight: 700; line-height: 18px; text-align: center; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11.5px; }
    th { background: #f0f5fa; color: #0b2d4d; border: 1px solid #dbe3ec; padding: 7px; text-align: left; }
    td { border: 1px solid #e2e8f0; padding: 7px; vertical-align: top; }
    .footer-note { margin-top: 22px; color: #64748b; font-size: 12px; display: flex; justify-content: space-between; gap: 12px; }
    @media print { body { background: #fff; } .topbar, .no-print { display: none !important; } .page { margin: 0; width: auto; min-height: auto; box-shadow: none; padding: 10mm; } }
  </style>
</head>
<body>
  <div class="topbar no-print">
    <a class="btn secondary" href="detail.php?id=<?= e($id); ?>">Kembali</a>
    <button class="btn" onclick="window.print()">Cetak Laporan</button>
  </div>
  <main class="page">
    <section class="header">
      <h1 class="brand">SIMPI</h1>
      <p class="subtitle">Sistem Informasi Monitoring Proyek Infrastruktur</p>
    </section>

    <h1>Laporan Monitoring Proyek</h1>
    <div class="grid">
      <div class="item"><div class="label">Nama Proyek</div><div class="value"><?= e($proyek['nama_proyek']); ?></div></div>
      <div class="item"><div class="label">Jenis Proyek</div><div class="value"><?= e($proyek['jenis_proyek']); ?></div></div>
      <div class="item"><div class="label">Lokasi</div><div class="value"><?= e($proyek['lokasi']); ?></div></div>
      <div class="item"><div class="label">Status</div><div class="value"><?= e($proyek['status']); ?></div></div>
      <div class="item"><div class="label">Periode</div><div class="value"><?= e(tanggal_id($proyek['tanggal_mulai'])); ?> s.d. <?= e(tanggal_id($proyek['tanggal_selesai'])); ?></div></div>
      <div class="item"><div class="label">Anggaran Awal</div><div class="value"><?= e(format_rupiah($proyek['anggaran_awal'])); ?></div></div>
      <div class="item"><div class="label">Anggaran Saat Ini</div><div class="value"><?= e(format_rupiah($proyek['anggaran'])); ?></div></div>
      <div class="item"><div class="label">Progres Terakhir</div><div class="value"><?= e($progres_akhir); ?>%</div></div>
    </div>

    <div class="progress-wrap"><div class="progress-bar" style="width: <?= e($progres_akhir); ?>%;"><?= e($progres_akhir); ?>%</div></div>

    <?php if (trim((string)$proyek['deskripsi']) !== ''): ?>
      <h2>Deskripsi</h2>
      <p><?= nl2br(e($proyek['deskripsi'])); ?></p>
    <?php endif; ?>

    <h2>Riwayat Revisi Anggaran</h2>
    <table>
      <thead><tr><th>No</th><th>Waktu</th><th>Nilai</th><th>Alasan</th><th>Admin</th></tr></thead>
      <tbody>
      <?php if (!$anggaran_revisi || mysqli_num_rows($anggaran_revisi) === 0): ?>
        <tr><td colspan="5" style="text-align:center;color:#64748b;">Belum ada revisi anggaran.</td></tr>
      <?php endif; ?>
      <?php $no=1; while ($anggaran_revisi && $row=mysqli_fetch_assoc($anggaran_revisi)): ?>
        <tr><td><?= $no++; ?></td><td><?= e(tanggal_waktu_id($row['revised_at'])); ?></td><td><?= e(format_rupiah($row['anggaran_lama'])); ?> → <?= e(format_rupiah($row['anggaran_baru'])); ?></td><td><?= e($row['alasan']); ?></td><td><?= e($row['nama_admin']); ?> (@<?= e($row['username_admin']); ?>)</td></tr>
      <?php endwhile; ?>
      </tbody>
    </table>

    <h2>Riwayat Progres</h2>
    <table>
      <thead><tr><th>No</th><th>Tanggal</th><th>Persentase</th><th>Keterangan</th><th>Dokumentasi</th><th>Petugas</th></tr></thead>
      <tbody>
        <?php if (!$progres || mysqli_num_rows($progres) === 0): ?>
          <tr><td colspan="6" style="text-align:center;color:#64748b;">Belum ada laporan progres.</td></tr>
        <?php endif; ?>
        <?php $no = 1; while ($progres && $row = mysqli_fetch_assoc($progres)): ?>
          <tr><td><?= $no++; ?></td><td><?= e(tanggal_id($row['tanggal_laporan'])); ?></td><td><?= e($row['persentase']); ?>%</td><td><?= e($row['keterangan']); ?></td><td><?= e($row['dokumentasi']); ?></td><td><?= e($row['nama_pemilik']); ?> (@<?= e($row['username_pemilik']); ?>)</td></tr>
        <?php endwhile; ?>
      </tbody>
    </table>

    <h2>Riwayat Revisi Progres</h2>
    <table>
      <thead><tr><th>No</th><th>Waktu</th><th>Tanggal Laporan</th><th>Persentase</th><th>Alasan</th><th>Petugas</th></tr></thead>
      <tbody>
        <?php if (!$progres_revisi || mysqli_num_rows($progres_revisi) === 0): ?>
          <tr><td colspan="6" style="text-align:center;color:#64748b;">Belum ada revisi progres.</td></tr>
        <?php endif; ?>
        <?php $no=1; while ($progres_revisi && $row=mysqli_fetch_assoc($progres_revisi)): ?>
          <tr><td><?= $no++; ?></td><td><?= e(tanggal_waktu_id($row['revised_at'])); ?></td><td><?= e(tanggal_id($row['tanggal_laporan'])); ?></td><td><?= e($row['persentase_lama']); ?>% → <?= e($row['persentase_baru']); ?>%</td><td><?= e($row['alasan']); ?></td><td><?= e($row['nama_petugas']); ?> (@<?= e($row['username_petugas']); ?>)</td></tr>
        <?php endwhile; ?>
      </tbody>
    </table>

    <div class="footer-note">
      <span>Dicetak dari SIMPI</span>
      <span>Tanggal cetak: <?= e($tanggal_cetak); ?></span>
    </div>
  </main>
</body>
</html>
