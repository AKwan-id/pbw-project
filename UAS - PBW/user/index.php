<?php
$prefix = '../';
require_once $prefix . 'config/koneksi.php';
require_once $prefix . 'includes/auth.php';
require_login($prefix);
require_admin($prefix);
$page_title = 'Data User - SIMPI';
$active = 'user';

$q = trim($_GET['q'] ?? '');
$role = trim($_GET['role'] ?? '');
$status = trim($_GET['status'] ?? 'aktif');
if (!in_array($status, ['aktif', 'belum_aktif', 'arsip'], true)) {
    $status = 'aktif';
}

$where = ['u.account_status = ?'];
$types = 's';
$params = [$status];

if ($q !== '') {
    $where[] = '(u.nama LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
    $types .= 'sss';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($role !== '' && in_array($role, role_options(), true)) {
    $where[] = 'u.role = ?';
    $types .= 's';
    $params[] = $role;
}

$count_result = mysqli_query($conn, 'SELECT account_status, COUNT(*) AS total FROM `user` GROUP BY account_status');
$status_count = ['aktif' => 0, 'belum_aktif' => 0, 'arsip' => 0];
while ($count_result && $c = mysqli_fetch_assoc($count_result)) {
    $status_count[$c['account_status']] = (int)$c['total'];
}

$sql = 'SELECT u.id_user, u.nama, u.username, u.email, u.role, u.is_active, u.account_status, u.last_login_at, u.created_at,
        (SELECT COUNT(*) FROM progres pr WHERE pr.created_by = u.id_user) AS jumlah_laporan
        FROM `user` u WHERE ' . implode(' AND ', $where) . '
        ORDER BY u.role ASC, u.id_user DESC LIMIT 100';

$stmt = mysqli_prepare($conn, $sql);
if ($stmt && $types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
$result = false;
if ($stmt && mysqli_stmt_execute($stmt)) {
    $result = mysqli_stmt_get_result($stmt);
}

$active_admins = active_admin_count($conn);

include $prefix . 'includes/header.php';
include $prefix . 'includes/navbar.php';
?>
<main class="container-fluid px-4 py-4">
  <?php show_flash(); ?>
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
      <h2 class="fw-bold mb-0">Data User</h2>
      <p class="text-muted mb-0">Akses pengguna dikelola tanpa mengambil alih akun atau mengubah laporan.</p>
    </div>
    <a href="tambah.php" class="btn btn-primary">+ Tambah User</a>
  </div>

  <div class="user-tabbar mb-3">
    <a class="user-tab <?= $status === 'aktif' ? 'active' : ''; ?>" href="index.php?status=aktif">User Aktif <span><?= e($status_count['aktif']); ?></span></a>
    <a class="user-tab <?= $status === 'belum_aktif' ? 'active' : ''; ?>" href="index.php?status=belum_aktif">Belum Aktif <span><?= e($status_count['belum_aktif']); ?></span></a>
    <a class="user-tab <?= $status === 'arsip' ? 'active' : ''; ?>" href="index.php?status=arsip">Arsip / Nonaktif <span><?= e($status_count['arsip']); ?></span></a>
  </div>

  <form method="get" id="filterUser" class="filter-box p-3 mb-3">
    <input type="hidden" name="status" value="<?= e($status); ?>">
    <div class="row g-2 align-items-end">
      <div class="col-md-5">
        <label class="form-label" for="qUser">Cari nama/username/email</label>
        <input type="text" id="qUser" name="q" class="form-control" value="<?= e($q); ?>" placeholder="Cari user">
      </div>
      <div class="col-md-3">
        <label class="form-label" for="roleUser">Role</label>
        <select id="roleUser" name="role" class="form-select">
          <option value="">Semua role</option>
          <?php foreach (role_options() as $r): ?>
            <option value="<?= e($r); ?>" <?= $role === $r ? 'selected' : ''; ?>><?= e(ucfirst($r)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-grid">
        <button type="submit" class="btn btn-primary">Filter</button>
      </div>
      <div class="col-md-2 d-grid">
        <a href="index.php?status=<?= e($status); ?>" class="btn btn-outline-secondary">Reset</a>
      </div>
    </div>
  </form>

  <div class="card content-card mb-3">
    <div class="card-body py-3">
      <div class="row g-3">
        <div class="col-md-4"><div class="security-box compact"><span>Admin aktif</span><b><?= e($active_admins); ?> / <?= e(MAX_ACTIVE_ADMINS); ?></b></div></div>
        <div class="col-md-4"><div class="security-box compact"><span>Identitas akun</span><b>Dikunci</b></div></div>
        <div class="col-md-4"><div class="security-box compact"><span>Akun nonaktif</span><b>Tidak bisa login/reset</b></div></div>
      </div>
    </div>
  </div>

  <div class="card content-card">
    <div class="card-body table-responsive">
      <table class="table table-hover align-middle table-modern user-table">
        <thead>
          <tr>
            <th>No</th><th>Nama</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Login Terakhir</th><th>Laporan</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$result || mysqli_num_rows($result) === 0): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Data user tidak ditemukan.</td></tr>
          <?php endif; ?>
          <?php $no = 1; while ($result && $row = mysqli_fetch_assoc($result)): ?>
            <?php
              $is_current = (int)$row['id_user'] === current_user_id();
              $is_last_admin = $row['role'] === 'admin' && $row['account_status'] === 'aktif' && $active_admins <= 1;
            ?>
            <tr>
              <td><?= $no++; ?></td>
              <td class="fw-semibold"><?= e($row['nama']); ?></td>
              <td><?= e($row['username']); ?></td>
              <td><?= e($row['email']); ?></td>
              <td><span class="badge <?= $row['role'] === 'admin' ? 'bg-primary' : 'bg-secondary'; ?> badge-status"><?= e(ucfirst($row['role'])); ?></span></td>
              <td><span class="badge <?= e(account_status_badge_class($row['account_status'], $row['is_active'])); ?> badge-status"><?= e(account_status_label($row['account_status'], $row['is_active'])); ?></span></td>
              <td><?= e(tanggal_waktu_id($row['last_login_at'])); ?></td>
              <td><?= e((int)$row['jumlah_laporan']); ?></td>
              <td class="action-cell">
                <div class="action-group action-group-user">
                  <a class="btn-action btn-detail" href="detail.php?id=<?= e($row['id_user']); ?>"><?= $row['account_status'] === 'arsip' ? 'Detail Arsip' : 'Detail Akun'; ?></a>

                  <?php if ($row['account_status'] === 'belum_aktif'): ?>
                    <a class="btn-action btn-print" href="kirim_aktivasi.php?id=<?= e($row['id_user']); ?>" onclick="return konfirmasiKirimAktivasi()">Kirim Aktivasi</a>
                    <a class="btn-action btn-delete" href="hapus.php?id=<?= e($row['id_user']); ?>" onclick="return konfirmasiBatalkanUser()">Batalkan</a>
                  <?php elseif ($row['account_status'] === 'aktif'): ?>
                    <?php if ($is_current): ?>
                      <span class="btn-action btn-disabled">Akun Aktif</span>
                    <?php elseif ($is_last_admin): ?>
                      <span class="btn-action btn-disabled">Dilindungi</span>
                    <?php else: ?>
                      <a class="btn-action btn-delete" href="hapus.php?id=<?= e($row['id_user']); ?>" onclick="return konfirmasiNonaktifUser()">Nonaktifkan</a>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="btn-action btn-disabled">Arsip</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<?php include $prefix . 'includes/footer.php'; ?>
