// assets/js/public.js - VERSI TERBARU (Tahan GPS Timeout)

let userLocation = null;
let qrCodeScanner = null;

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('qr-reader')) {
        initQRScanner();
    }
    if (document.getElementById('location-status')) {
        getLocation();
    }
});

// Deteksi lokasi 2 tahap: akurasi tinggi -> fallback akurasi rendah
function getLocation() {
    const statusDiv = document.getElementById('location-status');

    if (!navigator.geolocation) {
        statusDiv.innerHTML = '<p class="error">❌ Browser Anda tidak mendukung geolocation</p>';
        showFormWithoutGps();
        return;
    }

    statusDiv.innerHTML = '<p>🔄 Mendeteksi lokasi Anda...</p>';

    // Percobaan 1: akurasi tinggi (GPS)
    navigator.geolocation.getCurrentPosition(
        successCallback,
        function (err1) {
            console.warn('Akurasi tinggi gagal, coba akurasi rendah:', err1.message);
            // Percobaan 2: akurasi rendah (posisi via jaringan/Wi-Fi) - lebih cepat di desktop
            navigator.geolocation.getCurrentPosition(
                successCallback,
                finalErrorCallback,
                { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 }
            );
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

function successCallback(position) {
    userLocation = {
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
        accuracy: position.coords.accuracy,
        mocked: (position.mocked === true) // Anti-GPS Spoofing
    };

    document.getElementById('latitude').value = userLocation.latitude;
    document.getElementById('longitude').value = userLocation.longitude;

    const statusDiv = document.getElementById('location-status');
    document.getElementById('lokasi-info').textContent =
        `${userLocation.latitude.toFixed(6)}, ${userLocation.longitude.toFixed(6)}`;
    document.getElementById('akurasi-info').textContent =
        `${Math.round(userLocation.accuracy)} meter`;

    let mockWarning = '';
    if (userLocation.mocked) {
        mockWarning = `<p class="error">⚠️ Peringatan: Terdeteksi Mock Location / Fake GPS!</p>`;
    }

    statusDiv.innerHTML = `
        <p class="success">✅ Lokasi berhasil dideteksi!</p>
        <p><strong>Koordinat:</strong> ${userLocation.latitude.toFixed(6)}, ${userLocation.longitude.toFixed(6)}</p>
        <p><strong>Akurasi:</strong> ${Math.round(userLocation.accuracy)} meter</p>
        ${mockWarning}
    `;

    const form = document.querySelector('form[id^="form-absen"]');
    if (form) form.style.display = 'block';

    checkGeofence();
}

// GPS gagal total: tampilkan peringatan, TAPI form absen tetap dibuka
function finalErrorCallback(error) {
    const statusDiv = document.getElementById('location-status');
    let message = '❌ ';

    switch (error.code) {
        case error.PERMISSION_DENIED:
            message += "User menolak request geolocation";
            break;
        case error.POSITION_UNAVAILABLE:
            message += "Informasi lokasi tidak tersedia";
            break;
        case error.TIMEOUT:
            message += "Request lokasi timeout";
            break;
        default:
            message += "Terjadi kesalahan yang tidak diketahui";
            break;
    }

    statusDiv.innerHTML = `
        <p class="error">${message}</p>
        <p class="warning">😌 Tenang! Absen tetap bisa dilanjutkan via verifikasi <strong>Wi-Fi kantor</strong> atau <strong>scan QR Code</strong>.</p>
    `;

    showFormWithoutGps();
}

function showFormWithoutGps() {
    const form = document.querySelector('form[id^="form-absen"]');
    if (form) form.style.display = 'block';
}

async function checkGeofence() {
    if (!userLocation) return;

    try {
        const response = await fetch('../api/absen_action.php?action=check_location', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                latitude: userLocation.latitude,
                longitude: userLocation.longitude
            })
        });

        const result = await response.json();
        const statusDiv = document.getElementById('location-status');

        if (result.valid) {
            statusDiv.innerHTML += `<p class="success">✅ Anda berada dalam area absensi</p>`;
        } else {
            statusDiv.innerHTML += `<p class="error">❌ Anda di luar area absensi (Jarak: ${result.distance}m)</p>`;
        }
    } catch (error) {
        console.error('Geofence check failed:', error);
    }
}

function initQRScanner() {
    const onScanSuccess = (decodedText, decodedResult) => {
        qrCodeScanner.stop();
        document.getElementById('qr_token').value = decodedText;

        const statusDiv = document.getElementById('status-message');
        statusDiv.innerHTML = `
            <div class="alert success">
                ✅ QR Code berhasil discan! Silakan konfirmasi absen.
            </div>
        `;

        const form = document.getElementById('form-absen-masuk') || document.getElementById('form-absen-pulang');
        if (form) form.dispatchEvent(new Event('submit'));
    };

    const onScanError = (error) => {
        console.warn('QR Code scan error:', error);
    };

    qrCodeScanner = new Html5Qrcode("qr-reader");

    qrCodeScanner.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 250, height: 250 } },
        onScanSuccess,
        onScanError
    ).catch(err => {
        console.error("Unable to start scanning", err);
        document.getElementById('qr-reader').innerHTML =
            '<p class="error">⚠️ Kamera tidak dapat diakses. Pastikan Anda memberikan izin kamera.</p>';
    });
}

// ===== Submit Absen Masuk =====
const formAbsenMasuk = document.getElementById('form-absen-masuk');
if (formAbsenMasuk) {
    formAbsenMasuk.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('action', 'absen_masuk');
        formData.append('accuracy', userLocation ? userLocation.accuracy : '');
        formData.append('is_mock', (userLocation && userLocation.mocked) ? 1 : 0);

        try {
            const response = await fetch('../api/absen_action.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                showNotification('✅ ' + result.message, 'success');
                setTimeout(() => window.location.reload(), 1800);
            } else {
                showNotification('❌ ' + result.message, 'error');
            }
        } catch (error) {
            showNotification('❌ Terjadi kesalahan sistem', 'error');
            console.error(error);
        }
    });
}

// ===== Submit Absen Pulang =====
const formAbsenPulang = document.getElementById('form-absen-pulang');
if (formAbsenPulang) {
    formAbsenPulang.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('action', 'absen_pulang');
        formData.append('accuracy', userLocation ? userLocation.accuracy : '');
        formData.append('is_mock', (userLocation && userLocation.mocked) ? 1 : 0);

        try {
            const response = await fetch('../api/absen_action.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                showNotification('✅ ' + result.message, 'success');
                setTimeout(() => window.location.reload(), 1800);
            } else {
                showNotification('❌ ' + result.message, 'error');
            }
        } catch (error) {
            showNotification('❌ Terjadi kesalahan sistem', 'error');
            console.error(error);
        }
    });
}

function showNotification(message, type) {
    const statusDiv = document.getElementById('status-message');
    if (statusDiv) {
        statusDiv.innerHTML = `<div class="alert ${type}">${message}</div>`;
    } else {
        alert(message);
    }
}

function confirmLogout() {
    if (confirm('Apakah Anda yakin ingin logout?')) {
        window.location.href = '../api/auth.php?action=logout';
    }
}

// ===== V2: OFFLINE QUEUE + SERVICE WORKER =====
(function () {
    const originalFetch = window.fetch;

    // Bungkus fetch: jika offline / gagal jaringan saat absen -> antre di localStorage
    window.fetch = async function (input, init) {
        const url = typeof input === 'string' ? input : input.url;
        const isAbsenPost = init && init.method === 'POST' && url.includes('absen_action.php');

        if (isAbsenPost && !navigator.onLine) {
            queueOffline(init.body);
            return new Response(JSON.stringify({ success: true, offline: true, message: '📴 Absen disimpan offline — akan disinkronkan otomatis saat online.' }), { headers: { 'Content-Type': 'application/json' } });
        }

        try {
            return await originalFetch(input, init);
        } catch (err) {
            if (isAbsenPost) {
                queueOffline(init.body);
                return new Response(JSON.stringify({ success: true, offline: true, message: '📴 Jaringan gagal — absen disimpan offline & akan disinkronkan.' }), { headers: { 'Content-Type': 'application/json' } });
            }
            throw err;
        }
    };

    function queueOffline(formData) {
        if (!formData || typeof formData.forEach !== 'function') return;
        const data = {};
        formData.forEach((v, k) => data[k] = v);
        data.client_time = new Date().toISOString(); // waktu ASLI saat klik
        data.offline = '1';
        const q = JSON.parse(localStorage.getItem('absen_queue') || '[]');
        q.push(data);
        localStorage.setItem('absen_queue', JSON.stringify(q));
    }

    window.flushAbsenQueue = async function () {
        if (!navigator.onLine) return;
        const q = JSON.parse(localStorage.getItem('absen_queue') || '[]');
        if (!q.length) return;
        const remaining = [];
        for (const item of q) {
            const fd = new FormData();
            Object.entries(item).forEach(([k, v]) => fd.append(k, v));
            try {
                const res = await originalFetch('../api/absen_action.php', { method: 'POST', body: fd });
                const r = await res.json();
                if (!r.success && !/sudah/i.test(r.message)) remaining.push(item);
            } catch (e) { remaining.push(item); }
        }
        localStorage.setItem('absen_queue', JSON.stringify(remaining));
        const synced = q.length - remaining.length;
        if (synced > 0) showNotification('☁️ ' + synced + ' absen offline berhasil disinkronkan.', 'success');
    };

    window.addEventListener('online', window.flushAbsenQueue);
    document.addEventListener('DOMContentLoaded', function () {
        window.flushAbsenQueue();
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register(new URL('../sw.js', location.href)).catch(console.warn);
        }
    });
})();

// ===== V2: MOOD CHECK-IN =====
document.querySelectorAll('.mood-btn').forEach(function (btn) {
    btn.addEventListener('click', async function () {
        const fd = new FormData();
        fd.append('mood', this.dataset.mood);
        const res = await fetch('../api/mood_action.php', { method: 'POST', body: fd });
        const r = await res.json();
        showNotification(r.success ? '😊 Mood tersimpan! +2 poin' : '❌ ' + r.message, r.success ? 'success' : 'error');
        if (r.success) setTimeout(() => location.reload(), 1200);
    });
});