<?php
$prefix = '../';
require_once $prefix . 'config/koneksi.php';
require_once $prefix . 'includes/auth.php';
require_login($prefix);
$page_title = 'Data Progres - SIMPI';
$active = 'progres';
include $prefix . 'includes/header.php';
include $prefix . 'includes/navbar.php';

$q = trim($_GET['q'] ?? '');
$id_proyek_filter = (int)($_GET['id_proyek'] ?? 0);
$current_id = current_user_id();
$admin = is_admin();

$where = [];
$types = '';
$params = [];
if ($q !== '') {
    $where[] = '(p.keterangan LIKE ? OR p.dokumentasi LIKE ? OR pr.nama_proyek LIKE ? OR u.nama LIKE ? OR u.username LIKE ?)';
    $types .= 'sssss';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($id_proyek_filter > 0) {
    $where[] = 'p.id_proyek = ?';
    $types .= 'i';
    $params[] = $id_proyek_filter;
}

$sql = 'SELECT p.*, pr.nama_proyek, u.nama AS nama_pemilik, u.username AS username_pemilik
        FROM progres p
        JOIN proyek pr ON p.id_proyek = pr.id_proyek
        JOIN `user` u ON p.created_by = u.id_user';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY p.tanggal_laporan DESC, p.id_progres DESC LIMIT 50';

$stmt = mysqli_prepare($conn, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$list_proyek = mysqli_query($conn, 'SELECT id_proyek, nama_proyek FROM proyek ORDER BY nama_proyek ASC');
?>
<main class="container py-4">
  <?php show_flash(); ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div><h2 class="fw-bold mb-0">Data Progres</h2><p class="text-muted mb-0">Kelola laporan perkembangan proyek.</p></div>
    <?php if (!$admin): ?>
      <a href="tambah.php" class="btn btn-primary">+ Tambah Progres</a>
    <?php else: ?>
      <span class="badge bg-secondary badge-status">Admin melihat laporan</span>
    <?php endif; ?>
  </div>


  <form method="get" id="filterProgres" class="filter-box p-3 mb-3">
    <div class="filter-title mb-2">Filter data progres</div>
    <p class="filter-help mb-3">Gunakan kata kunci atau pilih proyek untuk menampilkan laporan progres yang relevan.</p>
    <div class="row g-2 align-items-end">
      <div class="col-md-5">
        <label class="form-label" for="qProgres">Cari progres</label>
        <input type="text" id="qProgres" name="q" class="form-control" value="<?= e($q); ?>" placeholder="Cari keterangan/dokumentasi/proyek/pemilik">
      </div>
      <div class="col-md-5">
        <label class="form-label" for="proyekFilter">Proyek</label>
        <select id="proyekFilter" name="id_proyek" class="form-select">
          <option value="0">Semua proyek</option>
          <?php while ($p = mysqli_fetch_assoc($list_proyek)): ?>
            <option value="<?= e($p['id_proyek']); ?>" <?= $id_proyek_filter === (int)$p['id_proyek'] ? 'selected' : ''; ?>><?= e($p['nama_proyek']); ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-2 d-grid gap-2">
        <button type="submit" class="btn btn-primary">Filter</button>
        <button type="button" onclick="resetFilter('filterProgres')" class="btn btn-outline-secondary">Reset</button>
      </div>
    </div>
  </form>

  <div class="card content-card"><div class="card-body table-responsive">
    <table class="table table-hover align-middle table-modern">
      <thead><tr><th>No</th><th>Proyek</th><th>Pemilik</th><th>Tanggal</th><th>Persentase</th><th>Keterangan</th><th>Dokumentasi</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php if (mysqli_num_rows($result) === 0): ?>
          <tr><td colspan="8" class="text-center text-muted">Data progres tidak ditemukan.</td></tr>
        <?php endif; ?>
        <?php $no=1; while ($row=mysqli_fetch_assoc($result)): ?>
        <?php $is_owner = ((int)$row['created_by'] === $current_id); $locked = (int)$row['is_locked'] === 1; ?>
        <tr>
          <td><?= $no++; ?></td>
          <td><?= e($row['nama_proyek']); ?></td>
          <td><span class="owner-label"><?= e($row['nama_pemilik']); ?></span><div class="text-muted small">@<?= e($row['username_pemilik']); ?></div></td>
          <td><?= e(tanggal_id($row['tanggal_laporan'])); ?></td>
          <td style="min-width:170px;"><div class="progress"><div class="progress-bar <?= e(progress_bar_class($row['persentase'])); ?>" style="width: <?= e($row['persentase']); ?>%;"><?= e($row['persentase']); ?>%</div></div></td>
          <td><?= e($row['keterangan']); ?></td>
          <td><?= e($row['dokumentasi']); ?></td>
          <td class="action-cell">
            <div class="action-group">
              <a class="btn-action btn-detail" href="<?= e($prefix); ?>proyek/detail.php?id=<?= e($row['id_proyek']); ?>">Detail</a>
              <?php if (!$admin && $is_owner): ?>
                <?php if (!$locked): ?>
                  <a class="btn-action btn-edit" href="edit.php?id=<?= e($row['id_progres']); ?>">Edit</a>
                  <a class="btn-action btn-delete" href="hapus.php?id=<?= e($row['id_progres']); ?>" onclick="return konfirmasiHapus()">Hapus</a>
                  <a class="btn-action btn-lock-open" href="kunci.php?id=<?= e($row['id_progres']); ?>" onclick="return confirm('Kunci laporan ini?')">Kunci</a>
                <?php else: ?>
                  <span class="btn-action btn-disabled">Edit</span>
                  <span class="btn-action btn-disabled">Hapus</span>
                  <a class="btn-action btn-lock-closed" href="kunci.php?id=<?= e($row['id_progres']); ?>" onclick="return confirm('Buka kunci laporan ini?')">Terkunci</a>
                <?php endif; ?>
              <?php elseif ($admin): ?>
                <span class="btn-action btn-disabled">Edit</span>
                <span class="btn-action btn-disabled">Hapus</span>
                <span class="btn-action btn-disabled">Kunci</span>
              <?php else: ?>
                <span class="btn-action btn-disabled">Edit</span>
                <span class="btn-action btn-disabled">Hapus</span>
                <span class="btn-action btn-disabled">Kunci</span>
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
