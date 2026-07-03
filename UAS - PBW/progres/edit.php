<?php
$prefix = '../';
require_once $prefix . 'config/koneksi.php';
require_once $prefix . 'includes/auth.php';
require_login($prefix);

$id = (int)($_GET['id'] ?? 0);
$stmt = mysqli_prepare($conn, 'SELECT p.*, u.nama AS nama_pemilik FROM progres p JOIN `user` u ON p.created_by = u.id_user WHERE p.id_progres=?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$data) {
    set_flash('error', 'Data progres tidak ditemukan.');
    header('Location: index.php');
    exit;
}

if (is_admin()) {
    set_flash('error', 'Admin tidak mengedit laporan progres. Admin hanya mengelola akun user.');
    header('Location: index.php');
    exit;
}

if ((int)$data['created_by'] !== current_user_id()) {
    set_flash('error', 'Akses ditolak. Laporan progres hanya dapat diedit oleh pemilik laporan.');
    header('Location: index.php');
    exit;
}

if ((int)$data['is_locked'] === 1) {
    set_flash('error', 'Laporan progres masih terkunci. Buka kunci terlebih dahulu sebelum mengedit.');
    header('Location: index.php');
    exit;
}

$id_proyek = (int) $data['id_proyek'];
$tanggal_laporan = $data['tanggal_laporan'];
$persentase = $data['persentase'];
$keterangan = $data['keterangan'];
$dokumentasi = $data['dokumentasi'];
$alasan_revisi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_proyek = (int)($_POST['id_proyek'] ?? 0);
    $tanggal_laporan = $_POST['tanggal_laporan'] ?? '';
    $persentase_input = $_POST['persentase'] ?? '';
    $persentase = is_numeric($persentase_input) ? (int)$persentase_input : $persentase_input;
    $keterangan = trim($_POST['keterangan'] ?? '');
    $dokumentasi = trim($_POST['dokumentasi'] ?? '');
    $alasan_revisi = trim($_POST['alasan_revisi'] ?? '');

    $ada_perubahan = $id_proyek !== (int)$data['id_proyek'] || $tanggal_laporan !== $data['tanggal_laporan'] || (int)$persentase !== (int)$data['persentase'] || $keterangan !== $data['keterangan'] || $dokumentasi !== (string)$data['dokumentasi'];

    if ($id_proyek <= 0 || $tanggal_laporan === '' || $keterangan === '' || !valid_percent($persentase_input)) {
        set_flash('error', 'Data progres belum valid. Persentase harus angka 0 sampai 100.');
    } elseif (!valid_date($tanggal_laporan)) {
        set_flash('error', 'Format tanggal laporan tidak valid.');
    } elseif (!project_exists($conn, $id_proyek)) {
        set_flash('error', 'Proyek yang dipilih tidak ditemukan.');
    } elseif (!$ada_perubahan) {
        set_flash('info', 'Tidak ada perubahan data progres.');
        header('Location: index.php');
        exit;
    } elseif (!revisi_required_reason($alasan_revisi)) {
        set_flash('error', 'Alasan revisi wajib diisi minimal 8 karakter.');
    } else {
        mysqli_begin_transaction($conn);
        try {
            $user_id = current_user_id();
            $ip = audit_ip();
            $agent = audit_user_agent();
            $revisi = mysqli_prepare($conn, 'INSERT INTO progres_revisi (id_progres, persentase_lama, persentase_baru, keterangan_lama, keterangan_baru, dokumentasi_lama, dokumentasi_baru, alasan, revised_by, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($revisi, 'iiisssssiss', $id, $data['persentase'], $persentase, $data['keterangan'], $keterangan, $data['dokumentasi'], $dokumentasi, $alasan_revisi, $user_id, $ip, $agent);
            if (!$revisi || !mysqli_stmt_execute($revisi)) {
                throw new Exception('insert revisi progres gagal');
            }

            $stmt = mysqli_prepare($conn, 'UPDATE progres SET id_proyek=?, tanggal_laporan=?, persentase=?, keterangan=?, dokumentasi=? WHERE id_progres=? AND created_by=? AND is_locked=0');
            mysqli_stmt_bind_param($stmt, 'isissii', $id_proyek, $tanggal_laporan, $persentase, $keterangan, $dokumentasi, $id, $user_id);
            if (!$stmt || !mysqli_stmt_execute($stmt)) {
                throw new Exception('update progres gagal');
            }

            mysqli_commit($conn);
            set_flash('success', 'Data progres berhasil diperbarui dan revisinya tercatat.');
            header('Location: index.php');
            exit;
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            set_flash('error', 'Gagal memperbarui progres. Pastikan laporan milik akun ini dan belum terkunci.');
        }
    }
}

$list_proyek = mysqli_query($conn, 'SELECT id_proyek, nama_proyek FROM proyek ORDER BY nama_proyek ASC');
$page_title = 'Edit Progres - SIMPI';
$active = 'progres';
include $prefix . 'includes/header.php';
include $prefix . 'includes/navbar.php';
?>
<main class="container py-4">
  <?php show_flash(); ?>
  <div class="card content-card"><div class="card-body">
    <h2 class="fw-bold mb-1">Edit Progres</h2>
    <p class="text-muted mb-3">Laporan ini milik: <b><?= e($data['nama_pemilik']); ?></b>. Setiap perubahan wajib memiliki alasan dan tersimpan di riwayat revisi.</p>
    <form method="post" onsubmit="return validateProgressForm()">
      <div class="mb-3"><label class="form-label">Nama Proyek</label><select name="id_proyek" class="form-select" required><option value="">Pilih proyek</option><?php while ($list_proyek && $p = mysqli_fetch_assoc($list_proyek)): ?><option value="<?= e($p['id_proyek']); ?>" <?= $id_proyek === (int)$p['id_proyek'] ? 'selected' : ''; ?>><?= e($p['nama_proyek']); ?></option><?php endwhile; ?></select></div>
      <div class="mb-3"><label class="form-label">Tanggal Laporan</label><input type="date" name="tanggal_laporan" class="form-control" value="<?= e($tanggal_laporan); ?>" required></div>
      <div class="mb-3"><label class="form-label">Persentase Progres</label><input type="number" min="0" max="100" step="1" id="persentase" name="persentase" class="form-control" value="<?= e($persentase); ?>" required></div>
      <div class="mb-3"><label class="form-label">Keterangan</label><textarea name="keterangan" class="form-control" rows="4" required><?= e($keterangan); ?></textarea></div>
      <div class="mb-3"><label class="form-label">Dokumentasi</label><input type="text" name="dokumentasi" class="form-control" value="<?= e($dokumentasi); ?>" placeholder="Nama file atau tautan dokumentasi"></div>
      <div class="mb-3"><label class="form-label">Alasan Revisi</label><textarea name="alasan_revisi" class="form-control" rows="3" required placeholder="Contoh: Koreksi hasil pengecekan lapangan setelah laporan diperiksa."><?= e($alasan_revisi); ?></textarea><div class="form-text">Alasan revisi akan tampil pada riwayat audit progres.</div></div>
      <button class="btn btn-primary">Update</button> <a href="index.php" class="btn btn-secondary">Kembali</a>
    </form>
  </div></div>
</main>
<?php include $prefix . 'includes/footer.php'; ?>
