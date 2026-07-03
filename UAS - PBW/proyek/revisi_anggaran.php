<?php
$prefix = '../';
require_once $prefix . 'config/koneksi.php';
require_once $prefix . 'includes/auth.php';
require_login($prefix);
require_admin($prefix);

$id = (int)($_GET['id'] ?? 0);
$stmt = mysqli_prepare($conn, 'SELECT * FROM proyek WHERE id_proyek=? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$proyek = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$proyek) {
    set_flash('error', 'Data proyek tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$anggaran_baru = '';
$alasan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anggaran_baru = normalisasi_anggaran($_POST['anggaran_baru'] ?? '0');
    $alasan = trim($_POST['alasan'] ?? '');
    $anggaran_lama = (float)$proyek['anggaran'];

    if ($anggaran_baru < 0) {
        set_flash('error', 'Anggaran baru tidak boleh bernilai negatif.');
    } elseif ((float)$anggaran_baru === (float)$anggaran_lama) {
        set_flash('error', 'Anggaran baru harus berbeda dari anggaran saat ini.');
    } elseif (!revisi_required_reason($alasan)) {
        set_flash('error', 'Alasan revisi wajib diisi minimal 8 karakter.');
    } else {
        mysqli_begin_transaction($conn);
        try {
            $revisi = mysqli_prepare($conn, 'INSERT INTO anggaran_revisi (id_proyek, anggaran_lama, anggaran_baru, alasan, revised_by, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $user_id = current_user_id();
            $ip = audit_ip();
            $agent = audit_user_agent();
            mysqli_stmt_bind_param($revisi, 'iddsiss', $id, $anggaran_lama, $anggaran_baru, $alasan, $user_id, $ip, $agent);
            if (!$revisi || !mysqli_stmt_execute($revisi)) {
                throw new Exception('insert revisi gagal');
            }

            $update = mysqli_prepare($conn, 'UPDATE proyek SET anggaran=? WHERE id_proyek=?');
            mysqli_stmt_bind_param($update, 'di', $anggaran_baru, $id);
            if (!$update || !mysqli_stmt_execute($update)) {
                throw new Exception('update anggaran gagal');
            }

            mysqli_commit($conn);
            set_flash('success', 'Revisi anggaran berhasil disimpan dan tercatat di riwayat.');
            header('Location: detail.php?id=' . $id);
            exit;
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            set_flash('error', 'Gagal menyimpan revisi anggaran. Silakan coba lagi.');
        }
    }
}

$riwayat = mysqli_prepare($conn, 'SELECT ar.*, u.nama AS nama_admin, u.username AS username_admin FROM anggaran_revisi ar JOIN `user` u ON ar.revised_by=u.id_user WHERE ar.id_proyek=? ORDER BY ar.revised_at DESC, ar.id_revisi DESC LIMIT 20');
mysqli_stmt_bind_param($riwayat, 'i', $id);
mysqli_stmt_execute($riwayat);
$riwayat_result = mysqli_stmt_get_result($riwayat);

$page_title = 'Revisi Anggaran - SIMPI';
$active = 'proyek';
include $prefix . 'includes/header.php';
include $prefix . 'includes/navbar.php';
?>
<main class="container py-4">
  <?php show_flash(); ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div><h2 class="fw-bold mb-0">Revisi Anggaran</h2><p class="text-muted mb-0">Perubahan anggaran tidak menimpa riwayat lama.</p></div>
    <a href="detail.php?id=<?= e($id); ?>" class="btn btn-secondary">Kembali</a>
  </div>

  <div class="row g-3">
    <div class="col-lg-5">
      <div class="card content-card"><div class="card-body">
        <h5 class="fw-bold mb-3"><?= e($proyek['nama_proyek']); ?></h5>
        <div class="security-box mb-3"><span>Anggaran Awal</span><b><?= e(format_rupiah($proyek['anggaran_awal'])); ?></b></div>
        <div class="security-box mb-3"><span>Anggaran Saat Ini</span><b><?= e(format_rupiah($proyek['anggaran'])); ?></b></div>
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Anggaran Baru</label>
            <input type="number" name="anggaran_baru" class="form-control" min="0" step="1000" value="<?= e($anggaran_baru); ?>" placeholder="Contoh: 175000000" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Alasan Revisi</label>
            <textarea name="alasan" class="form-control" rows="4" required placeholder="Contoh: Penyesuaian volume pekerjaan berdasarkan hasil pemeriksaan lapangan."><?= e($alasan); ?></textarea>
            <div class="form-text">Alasan wajib tercatat agar perubahan anggaran dapat diaudit.</div>
          </div>
          <button class="btn btn-primary">Simpan Revisi</button>
        </form>
      </div></div>
    </div>
    <div class="col-lg-7">
      <div class="card content-card"><div class="card-header bg-white fw-bold">Riwayat Revisi Anggaran</div><div class="card-body table-responsive">
        <table class="table table-hover table-modern align-middle">
          <thead><tr><th>No</th><th>Waktu</th><th>Nilai</th><th>Alasan</th><th>Admin</th></tr></thead>
          <tbody>
            <?php if (!$riwayat_result || mysqli_num_rows($riwayat_result) === 0): ?>
              <tr><td colspan="5" class="text-center text-muted">Belum ada revisi anggaran.</td></tr>
            <?php endif; ?>
            <?php $no=1; while ($riwayat_result && $r=mysqli_fetch_assoc($riwayat_result)): ?>
              <tr>
                <td><?= $no++; ?></td>
                <td><?= e(tanggal_waktu_id($r['revised_at'])); ?></td>
                <td><b><?= e(format_rupiah($r['anggaran_lama'])); ?></b><br><span class="text-muted small">menjadi</span><br><b><?= e(format_rupiah($r['anggaran_baru'])); ?></b></td>
                <td><?= e($r['alasan']); ?></td>
                <td><?= e($r['nama_admin']); ?><div class="text-muted small">@<?= e($r['username_admin']); ?></div></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div></div>
    </div>
  </div>
</main>
<?php include $prefix . 'includes/footer.php'; ?>
