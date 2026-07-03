<?php
$prefix = '../';
require_once $prefix . 'config/koneksi.php';
require_once $prefix . 'includes/auth.php';
require_login($prefix);
$page_title = 'Data Proyek - SIMPI';
$active = 'proyek';
include $prefix . 'includes/header.php';
include $prefix . 'includes/navbar.php';

$admin = is_admin();

$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$jenis = trim($_GET['jenis'] ?? '');

$where = [];
$types = '';
$params = [];

if ($q !== '') {
    $where[] = '(p.nama_proyek LIKE ? OR p.lokasi LIKE ? OR p.jenis_proyek LIKE ?)';
    $types .= 'sss';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($status !== '' && in_array($status, status_options(), true)) {
    $where[] = 'p.status = ?';
    $types .= 's';
    $params[] = $status;
}
if ($jenis !== '') {
    $where[] = 'p.jenis_proyek = ?';
    $types .= 's';
    $params[] = $jenis;
}

$sql = 'SELECT p.*, COALESCE((SELECT pr.persentase FROM progres pr WHERE pr.id_proyek = p.id_proyek ORDER BY pr.tanggal_laporan DESC, pr.id_progres DESC LIMIT 1), 0) AS progres_terakhir
        FROM proyek p';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY p.id_proyek DESC LIMIT 50';

$stmt = mysqli_prepare($conn, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
$result = false;
if ($stmt && mysqli_stmt_execute($stmt)) {
    $result = mysqli_stmt_get_result($stmt);
}

$jenis_result = mysqli_query($conn, 'SELECT DISTINCT jenis_proyek FROM proyek ORDER BY jenis_proyek ASC');
?>
<main class="container-fluid px-4 py-4">
  <?php show_flash(); ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div><h2 class="fw-bold mb-0">Data Proyek</h2><p class="text-muted mb-0">Kelola proyek infrastruktur, anggaran, dan progres terakhir.</p></div>
    <?php if ($admin): ?><a href="tambah.php" class="btn btn-primary">+ Tambah Proyek</a><?php else: ?><span class="badge bg-secondary badge-status">Petugas hanya melihat proyek</span><?php endif; ?>
  </div>

  <form method="get" id="filterProyek" class="filter-box p-3 mb-3">
    <div class="filter-title mb-2">Filter data proyek</div>
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label" for="qProyek">Cari proyek/lokasi</label>
        <input type="text" id="qProyek" name="q" class="form-control" value="<?= e($q); ?>" placeholder="Cari nama, jenis, atau lokasi">
      </div>
      <div class="col-md-3">
        <label class="form-label" for="statusProyek">Status</label>
        <select id="statusProyek" name="status" class="form-select">
          <option value="">Semua status</option>
          <?php foreach (status_options() as $s): ?>
            <option value="<?= e($s); ?>" <?= $status === $s ? 'selected' : ''; ?>><?= e($s); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="jenisProyek">Jenis</label>
        <select id="jenisProyek" name="jenis" class="form-select">
          <option value="">Semua jenis</option>
          <?php while ($jenis_result && $jr = mysqli_fetch_assoc($jenis_result)): ?>
            <option value="<?= e($jr['jenis_proyek']); ?>" <?= $jenis === $jr['jenis_proyek'] ? 'selected' : ''; ?>><?= e($jr['jenis_proyek']); ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-2 d-grid gap-2">
        <button type="submit" class="btn btn-primary">Filter</button>
        <button type="button" onclick="resetFilter('filterProyek')" class="btn btn-outline-secondary">Reset</button>
      </div>
    </div>
  </form>

  <div class="card content-card"><div class="card-body table-responsive">
    <table class="table table-hover align-middle table-modern proyek-table">
      <thead><tr><th>No</th><th>Nama Proyek</th><th>Jenis</th><th>Lokasi</th><th>Periode</th><th>Status</th><th>Progres</th><th>Anggaran</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php if (!$result || mysqli_num_rows($result) === 0): ?>
        <tr><td colspan="9" class="text-center text-muted">Data proyek tidak ditemukan.</td></tr>
      <?php endif; ?>
      <?php $no=1; while ($result && $row=mysqli_fetch_assoc($result)): ?>
        <tr>
          <td><?= $no++; ?></td>
          <td class="project-name-cell"><?= e($row['nama_proyek']); ?></td>
          <td><?= e($row['jenis_proyek']); ?></td>
          <td><?= e($row['lokasi']); ?></td>
          <td class="periode-cell"><?= e(tanggal_singkat($row['tanggal_mulai'])); ?> - <?= e(tanggal_singkat($row['tanggal_selesai'])); ?></td>
          <td><span class="badge <?= e(status_badge_class($row['status'])); ?> badge-status"><?= e($row['status']); ?></span></td>
          <td class="progress-cell"><div class="progress"><div class="progress-bar <?= e(progress_bar_class($row['progres_terakhir'])); ?>" style="width: <?= e($row['progres_terakhir']); ?>%;"><?= e($row['progres_terakhir']); ?>%</div></div></td>
          <td class="budget-cell"><?= e(format_rupiah($row['anggaran'])); ?></td>
          <td class="action-cell">
            <div class="action-group">
              <a class="btn-action btn-detail" href="detail.php?id=<?= e($row['id_proyek']); ?>">Detail</a>
              <?php if ($admin): ?>
                <a class="btn-action btn-edit" href="edit.php?id=<?= e($row['id_proyek']); ?>">Edit</a>
                <a class="btn-action btn-delete" href="hapus.php?id=<?= e($row['id_proyek']); ?>" onclick="return konfirmasiHapus()">Hapus</a>
                <a class="btn-action btn-print" href="cetak.php?id=<?= e($row['id_proyek']); ?>" target="_blank">Cetak</a>
              <?php else: ?>
                <a class="btn-action btn-print" href="cetak.php?id=<?= e($row['id_proyek']); ?>" target="_blank">Cetak</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include $prefix . 'includes/footer.php'; ?>
