function konfirmasiHapus() {
  return confirm('Yakin ingin menghapus data ini? Data yang dihapus tidak dapat dikembalikan.');
}

function konfirmasiNonaktifUser() {
  return confirm('Nonaktifkan akun ini? Akun masuk arsip dan tidak bisa login atau memakai lupa password.');
}

function konfirmasiBatalkanUser() {
  return confirm('Batalkan akun belum aktif ini? Data akun akan dihapus dan email/username dapat dipakai ulang.');
}

function konfirmasiKirimAktivasi() {
  return confirm('Kirim link aktivasi ke email pengguna? Admin tidak akan melihat password atau link aktivasi.');
}

function konfirmasiArsipUser() {
  return konfirmasiNonaktifUser();
}

function confirmLogout() {
  return confirm('Yakin ingin logout?');
}

function validateProgressForm() {
  const input = document.getElementById('persentase');
  if (!input) return true;

  const value = String(input.value).trim();
  if (!/^(100|[1-9]?[0-9])$/.test(value)) {
    alert('Persentase progres harus berupa angka bulat 0 sampai 100.');
    input.focus();
    return false;
  }
  return true;
}

function resetFilter(formId) {
  const form = document.getElementById(formId);
  if (!form) return;
  form.querySelectorAll('input, select').forEach((el) => {
    el.value = '';
  });
  form.submit();
}
