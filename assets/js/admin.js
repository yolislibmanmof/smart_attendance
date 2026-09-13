// assets/js/admin.js
// File JS tunggal untuk seluruh halaman admin

document.addEventListener('DOMContentLoaded', function () {
    console.log('Admin panel ready.');
});

// Konfirmasi sebelum aksi berbahaya (hapus / tolak)
function confirmAction(message) {
    return confirm(message || 'Apakah Anda yakin?');
}