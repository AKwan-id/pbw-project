const todoForm = document.getElementById('todoForm');
const namaTodo = document.getElementById('namaTodo');
const tanggalTodo = document.getElementById('tanggalTodo');
const isiTabel = document.getElementById('isiTabel');
const tombolSimpan = document.getElementById('tombolSimpan');
const tombolBatal = document.getElementById('tombolBatal');

let daftarTodo = [];
let indexEdit = null;

function formatTanggal(tanggal) {
  const format = { day: 'numeric', month: 'long', year: 'numeric' };
  return new Date(tanggal + 'T00:00:00').toLocaleDateString('id-ID', format);
}

function tampilkanData() {
  isiTabel.innerHTML = '';

  if (daftarTodo.length === 0) {
    isiTabel.innerHTML = `
      <tr>
        <td colspan="5" class="text-muted">Belum ada data to-do.</td>
      </tr>
    `;
    return;
  }

  daftarTodo.forEach(function(todo, index) {
    const baris = document.createElement('tr');

    baris.innerHTML = `
      <td>${index + 1}</td>
      <td class="text-start ${todo.selesai ? 'selesai' : ''}">${todo.nama}</td>
      <td>${formatTanggal(todo.tanggal)}</td>
      <td>
        <input type="checkbox" class="form-check-input" ${todo.selesai ? 'checked' : ''} onclick="ubahStatus(${index})">
      </td>
      <td>
        <button class="btn btn-warning btn-sm mb-1" onclick="editData(${index})">Edit</button>
        <button class="btn btn-danger btn-sm mb-1" onclick="hapusData(${index})">Hapus</button>
      </td>
    `;

    isiTabel.appendChild(baris);
  });
}

todoForm.addEventListener('submit', function(event) {
  event.preventDefault();

  const nama = namaTodo.value.trim();
  const tanggal = tanggalTodo.value;

  if (nama === '' || tanggal === '') {
    alert('Nama to-do dan tanggal harus diisi!');
    return;
  }

  if (indexEdit === null) {
    daftarTodo.push({
      nama: nama,
      tanggal: tanggal,
      selesai: false
    });
  } else {
    daftarTodo[indexEdit].nama = nama;
    daftarTodo[indexEdit].tanggal = tanggal;
    indexEdit = null;
    tombolSimpan.textContent = 'Simpan';
    tombolBatal.classList.add('d-none');
  }

  todoForm.reset();
  tampilkanData();
});

function editData(index) {
  namaTodo.value = daftarTodo[index].nama;
  tanggalTodo.value = daftarTodo[index].tanggal;
  indexEdit = index;
  tombolSimpan.textContent = 'Simpan Perubahan';
  tombolBatal.classList.remove('d-none');
  namaTodo.focus();
}

function hapusData(index) {
  if (confirm('Yakin ingin menghapus data ini?')) {
    daftarTodo.splice(index, 1);

    if (indexEdit === index) {
      batalEdit();
    }

    tampilkanData();
  }
}

function ubahStatus(index) {
  daftarTodo[index].selesai = !daftarTodo[index].selesai;
  tampilkanData();
}

function batalEdit() {
  indexEdit = null;
  todoForm.reset();
  tombolSimpan.textContent = 'Simpan';
  tombolBatal.classList.add('d-none');
}

tombolBatal.addEventListener('click', batalEdit);

tampilkanData();
