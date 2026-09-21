// assets/js/public.js - FINAL V3 (bersih, tanpa duplikasi)

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

function getLocation() {
    const statusDiv = document.getElementById('location-status');

    if (!navigator.geolocation) {
        statusDiv.innerHTML = '<p class="error">❌ Browser Anda tidak mendukung geolocation</p>';
        showFormWithoutGps();
        return;
    }

    statusDiv.innerHTML = '<p>🔄 Mendeteksi lokasi Anda...</p>';

    navigator.geolocation.getCurrentPosition(
        successCallback,
        function (err1) {
            console.warn('Akurasi tinggi gagal, coba akurasi rendah:', err1.message);
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
        mocked: (position.mocked === true)
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
        // SENYAP: error ini muncul tiap frame saat tidak ada QR di depan kamera.
        // Perilaku normal html5-qrcode, bukan bug aplikasi.
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
        data.client_time = new Date().toISOString();
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

// ===== V3: FACE RECOGNITION + LIVENESS DETECTION (HANYA 1X) =====
const FACE_MODEL_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/weights';
let faceModelsLoaded = false;

async function loadFaceModels() {
    if (faceModelsLoaded) return true;
    if (typeof faceapi === 'undefined') return false;
    await faceapi.nets.tinyFaceDetector.loadFromUri(FACE_MODEL_URL);
    await faceapi.nets.faceLandmark68Net.loadFromUri(FACE_MODEL_URL);
    await faceapi.nets.faceRecognitionNet.loadFromUri(FACE_MODEL_URL);
    faceModelsLoaded = true;
    return true;
}

function startFaceCamera(videoEl) {
    return navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: 480, height: 360 } })
        .then(stream => {
            videoEl.srcObject = stream;
            return new Promise(res => videoEl.onloadedmetadata = () => res(stream));
        });
}

function stopFaceCamera(videoEl) {
    if (videoEl && videoEl.srcObject) videoEl.srcObject.getTracks().forEach(t => t.stop());
}

function captureSnapshot(video) {
    const c = document.createElement('canvas');
    c.width = 320;
    c.height = Math.round(video.videoHeight * (320 / video.videoWidth)) || 240;
    c.getContext('2d').drawImage(video, 0, 0, c.width, c.height);
    return c.toDataURL('image/jpeg', 0.7);
}

function eyeRatio(pts, idx) {
    const d = (a, b) => Math.hypot(pts[a].x - pts[b].x, pts[a].y - pts[b].y);
    const [i1, i2, i3, i4, i5, i6] = idx;
    return (d(i2, i6) + d(i3, i5)) / (2 * d(i1, i4));
}

function faceStatus(text, cls) {
    const el = document.getElementById('face-status');
    if (el) { el.textContent = text; el.className = 'face-status ' + (cls || ''); }
}

async function runLiveness(videoEl) {
    const c = document.createElement('canvas'); c.width = 48; c.height = 36;
    const ctx = c.getContext('2d', { willReadFrequently: true });
    let prev = null, maxEar = 0, minEar = 9, faceFrames = 0;
    const motion = [];
    const t0 = Date.now();

    while (Date.now() - t0 < 8000) {
        const det = await faceapi.detectSingleFace(videoEl, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.4 })).withFaceLandmarks();
        if (det) {
            faceFrames++;
            const pts = det.landmarks.positions;
            const ear = (eyeRatio(pts, [36,37,38,39,40,41]) + eyeRatio(pts, [42,43,44,45,46,47])) / 2;
            maxEar = Math.max(maxEar, ear);
            minEar = Math.min(minEar, ear);

            ctx.drawImage(videoEl, 0, 0, 48, 36);
            const data = ctx.getImageData(0, 0, 48, 36).data;
            if (prev) {
                let diff = 0;
                for (let i = 0; i < data.length; i += 4) diff += Math.abs(data[i] - prev[i]);
                motion.push(diff / (data.length / 4));
            }
            prev = Array.from(data);
            faceStatus('👁️ Kedipkan mata Anda sekarang...');
        }
        await new Promise(r => setTimeout(r, 90));
    }

    const blink = faceFrames > 5 && maxEar > 0 && minEar < maxEar * 0.72;
    const avgMotion = motion.length ? motion.reduce((a, b) => a + b, 0) / motion.length : 0;
    return { blink, avgMotion, ok: blink && avgMotion > 0.3, faceFrames };
}

async function initFaceEnroll() {
    const video = document.getElementById('face-video');
    faceStatus('Memuat model AI wajah (±5MB, sekali saja)...');
    if (!await loadFaceModels()) { faceStatus('❌ Gagal memuat model AI. Periksa koneksi internet.', 'err'); return; }
    if (!await startFaceCamera(video).catch(() => null)) { faceStatus('❌ Kamera tidak dapat diakses.', 'err'); return; }
    faceStatus('Posisikan wajah di dalam bingkai oval...');

    let det = null;
    for (let i = 0; i < 50 && !det; i++) {
        det = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 })).withFaceLandmarks().withFaceDescriptor();
        if (!det) await new Promise(r => setTimeout(r, 200));
    }
    if (!det) { faceStatus('❌ Wajah tidak terdeteksi. Perbaiki pencahayaan.', 'err'); return; }

    faceStatus('🔒 Wajah terkunci! Menyimpan descriptor...', 'ok');
    const fd = new FormData();
    fd.append('descriptor', JSON.stringify(Array.from(det.descriptor)));
    fd.append('snapshot', captureSnapshot(video));

    const res = await fetch('../api/face_action.php?action=enroll', { method: 'POST', body: fd });
    const r = await res.json();
    faceStatus(r.success ? '✅ ' + r.message : '❌ ' + r.message, r.success ? 'ok' : 'err');
    stopFaceCamera(video);
    if (r.success) setTimeout(() => location.reload(), 1600);
}

async function initFaceVerify() {
    const video = document.getElementById('face-video');
    faceStatus('Memuat model AI wajah...');
    if (!await loadFaceModels()) { faceStatus('❌ Model AI gagal dimuat (butuh internet).', 'err'); return; }
    if (!await startFaceCamera(video).catch(() => null)) { faceStatus('❌ Kamera ditolak.', 'err'); return; }
    faceStatus('Posisikan wajah, lalu KEDIPKAN mata untuk uji liveness...');

    const live = await runLiveness(video);
    if (live.faceFrames < 5) { faceStatus('❌ Wajah tidak konsisten terdeteksi.', 'err'); stopFaceCamera(video); return; }
    if (!live.blink) { faceStatus('🚫 Tidak ada kedipan terdeteksi — diduga FOTO! Absen wajah gagal.', 'err'); stopFaceCamera(video); return; }

    faceStatus('✅ Kedipan OK! Mencocokkan identitas wajah...', 'ok');
const det = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 })).withFaceLandmarks().withFaceDescriptor();
    if (!det) { faceStatus('❌ Wajah tidak terdeteksi saat pencocokan.', 'err'); return; }

    document.querySelectorAll('input[name="face_descriptor_live"]').forEach(i => i.value = JSON.stringify(Array.from(det.descriptor)));
    document.querySelectorAll('input[name="face_snapshot"]').forEach(i => i.value = captureSnapshot(video));

    const badge = document.getElementById('face-verified-badge');
    if (badge) badge.style.display = 'inline-flex';
    faceStatus('✅ Face ID terverifikasi! Silakan konfirmasi absen.', 'ok');
    stopFaceCamera(video);
}

// Auto-start face init (handle case where public.js loads after DOM ready)
(function() {
    function startFace() {
        const video = document.getElementById('face-video');
        if (video) {
            if (video.dataset.mode === 'enroll') initFaceEnroll();
            else initFaceVerify();
        }
        const delBtn = document.getElementById('btn-delete-face');
        if (delBtn) {
            delBtn.addEventListener('click', async function () {
                if (!confirm('Hapus data wajah Anda? Absen wajah akan nonaktif.')) return;
                const res = await fetch('../api/face_action.php?action=delete', { method: 'POST', body: new FormData() });
                const r = await res.json();
                alert(r.message);
                location.reload();
            });
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startFace);
    } else {
        startFace(); // DOM sudah ready, jalan langsung!
    }
})();