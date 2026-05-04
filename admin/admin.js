/* =========================================================
   WisataBandung – Admin Dashboard JS
   ========================================================= */

var allWisataData = [];

// ── Boot ───────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    checkAuth();
    setupEventListeners();
});

// ── Auth ───────────────────────────────────────────────────────────────────
function checkAuth() {
    fetch('auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'check' })
    })
    .then(function (res) {
        if (!res.ok) { window.location.href = 'login.html'; return null; }
        return res.json();
    })
    .then(function (data) {
        if (!data) return;
        if (!data.success) {
            window.location.href = 'login.html';
        } else {
            var el = document.getElementById('admin-username');
            if (el) el.textContent = data.name || data.username || 'Admin';
            loadWisataData();
        }
    })
    .catch(function () { window.location.href = 'login.html'; });
}

function doLogout() {
    fetch('auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'logout' })
    })
    .finally(function () { window.location.href = 'login.html'; });
}

// ── Event listeners ────────────────────────────────────────────────────────
function setupEventListeners() {
    // Tab switching
    document.querySelectorAll('.nav-item').forEach(function (item) {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            var tabName = item.dataset.tab;
            switchTab(tabName);
            document.querySelectorAll('.nav-item').forEach(function (i) { i.classList.remove('active'); });
            item.classList.add('active');
        });
    });

    // Forms
    var addForm  = document.getElementById('add-wisata-form');
    var editForm = document.getElementById('edit-form');
    var search   = document.getElementById('search-wisata');
    if (addForm)  addForm.addEventListener('submit', handleAddWisata);
    if (editForm) editForm.addEventListener('submit', handleEditWisata);
    if (search)   search.addEventListener('input', handleSearch);
}

// ── Tab ────────────────────────────────────────────────────────────────────
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(function (tab) { tab.classList.remove('active'); });
    var el = document.getElementById(tabName);
    if (el) el.classList.add('active');

    // Jika pindah ke tab tambah-wisata dan map picker sudah terbuka, refresh ukuran map
    if (tabName === 'add-wisata' && mapPicker) {
        setTimeout(function () { mapPicker.invalidateSize(); }, 120);
    }
}

// ── Data loading ───────────────────────────────────────────────────────────
function loadWisataData() {
    fetch('api.php?action=list')
        .then(function (res) { return res.json(); })
        .then(function (resp) {
            if (resp.success && Array.isArray(resp.data)) {
                allWisataData = resp.data;
                updateDashboard();
                renderWisataTable();
            } else {
                // fallback: try public endpoint
                return fetch('../get_wisata.php').then(function (r) { return r.json(); });
            }
        })
        .then(function (data) {
            if (data && Array.isArray(data)) {
                allWisataData = data;
                updateDashboard();
                renderWisataTable();
            }
        })
        .catch(function (err) {
            console.error('Gagal memuat data:', err);
            var tbody = document.getElementById('wisata-tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#e15b4f">Gagal memuat data</td></tr>';
        });
}

// ── Dashboard stats ────────────────────────────────────────────────────────
function updateDashboard() {
    var total       = allWisataData.length;
    var categories  = new Set(allWisataData.map(function (d) { return d.kategori || 'lainnya'; }));
    var withRatings = allWisataData.filter(function (d) { return d.rating && parseFloat(d.rating) > 0; });
    var avgRating   = withRatings.length > 0
        ? (withRatings.reduce(function (s, d) { return s + parseFloat(d.rating || 0); }, 0) / withRatings.length).toFixed(1)
        : '–';

    setText('total-wisata', total);
    setText('total-categories', categories.size);
    setText('with-ratings', withRatings.length);
    setText('avg-rating', avgRating);
}

// ── Table rendering ────────────────────────────────────────────────────────
function renderWisataTable(dataToRender) {
    var rows = dataToRender || allWisataData;
    var tbody = document.getElementById('wisata-tbody');
    if (!tbody) return;

    tbody.innerHTML = '';
    if (rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:1.5rem;color:#637083">Tidak ada data</td></tr>';
        return;
    }

    rows.forEach(function (wisata, index) {
        var rating  = wisata.rating ? parseFloat(wisata.rating).toFixed(1) : '–';
        var nama    = escapeHTML(wisata.nama    || 'Tidak diketahui');
        var kat     = escapeHTML(wisata.kategori || 'lainnya');
        var lat     = wisata.latitude  ? parseFloat(wisata.latitude).toFixed(5)  : '–';
        var lng     = wisata.longitude ? parseFloat(wisata.longitude).toFixed(5) : '–';
        var realId  = wisata.id || (index + 1);

        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td>' + (index + 1) + '</td>' +
            '<td>' + nama + '</td>' +
            '<td><span class="kat-badge kat-' + (wisata.kategori||'lainnya') + '">' + kat + '</span></td>' +
            '<td>' + (wisata.rating ? '⭐ ' + rating : '–') + '</td>' +
            '<td style="font-size:0.8rem;color:#637083">' + lat + ', ' + lng + '</td>' +
            '<td>' +
              '<button class="btn btn-edit"   onclick="editWisata('  + realId + ')">Edit</button> ' +
              '<button class="btn btn-danger" onclick="deleteWisata(' + realId + ')">Hapus</button>' +
            '</td>';
        tbody.appendChild(tr);
    });
}

// ── ADD ────────────────────────────────────────────────────────────────────
async function handleAddWisata(e) {
    e.preventDefault();
    var formData = new FormData(e.target);
    var data     = Object.fromEntries(formData);
    var msgEl    = document.getElementById('form-message');

    try {
        var res    = await fetch('api.php?action=add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        var result = await res.json();

        if (result.success) {
            showFormMsg('form-message', '✓ Wisata berhasil ditambahkan!', false);
            e.target.reset();
            loadWisataData();
            setTimeout(function () { if (msgEl) msgEl.style.display = 'none'; }, 3500);
        } else {
            showFormMsg('form-message', result.message || 'Gagal menambahkan', true);
        }
    } catch (err) {
        showFormMsg('form-message', 'Kesalahan server, coba lagi.', true);
    }
}

// ── EDIT (open modal) ──────────────────────────────────────────────────────
function editWisata(id) {
    // Find by real DB id, not array index
    var wisata = allWisataData.find(function (w) { return String(w.id) === String(id); });
    if (!wisata) {
        alert('Data tidak ditemukan (id=' + id + ')');
        return;
    }

    document.getElementById('edit-id').value        = wisata.id;
    document.getElementById('edit-nama').value      = wisata.nama      || '';
    document.getElementById('edit-kategori').value  = wisata.kategori  || 'lainnya';
    document.getElementById('edit-latitude').value  = wisata.latitude  || '';
    document.getElementById('edit-longitude').value = wisata.longitude || '';
    document.getElementById('edit-rating').value    = wisata.rating    || '';
    document.getElementById('edit-review').value    = wisata.review    || '';

    document.getElementById('edit-modal').style.display = 'flex';
}

async function handleEditWisata(e) {
    e.preventDefault();
    var id   = document.getElementById('edit-id').value;
    var data = Object.fromEntries(new FormData(e.target));

    try {
        var res    = await fetch('api.php?action=edit&id=' + id, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        var result = await res.json();

        if (result.success) {
            closeEditModal();
            loadWisataData();
            showToast('Perubahan berhasil disimpan');
        } else {
            alert('Gagal: ' + (result.message || 'Error tidak diketahui'));
        }
    } catch (err) {
        alert('Kesalahan server');
    }
}

function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
    document.getElementById('edit-form').reset();
}

// ── DELETE ─────────────────────────────────────────────────────────────────
async function deleteWisata(id) {
    var wisata = allWisataData.find(function (w) { return String(w.id) === String(id); });
    var nama   = wisata ? wisata.nama : 'ID ' + id;

    if (!confirm('Hapus "' + nama + '"?\n\nTindakan ini tidak dapat dibatalkan.')) return;

    try {
        var res    = await fetch('api.php?action=delete&id=' + id, { method: 'POST' });
        var result = await res.json();

        if (result.success) {
            loadWisataData();
            showToast('Wisata berhasil dihapus');
        } else {
            alert('Gagal: ' + (result.message || 'Error'));
        }
    } catch (err) {
        alert('Kesalahan server');
    }
}

// ── Search ─────────────────────────────────────────────────────────────────
function handleSearch(e) {
    var kw = e.target.value.toLowerCase();
    var filtered = allWisataData.filter(function (w) {
        return (w.nama     || '').toLowerCase().includes(kw) ||
               (w.kategori || '').toLowerCase().includes(kw);
    });
    renderWisataTable(filtered);
}

// ── Helpers ────────────────────────────────────────────────────────────────
function escapeHTML(text) {
    var d = document.createElement('div');
    d.textContent = String(text);
    return d.innerHTML;
}

function setText(id, val) {
    var el = document.getElementById(id);
    if (el) el.textContent = val;
}

function showFormMsg(id, msg, isError) {
    var el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg;
    el.classList.toggle('error', !!isError);
    el.style.display = 'block';
}

function showToast(msg) {
    var t = document.createElement('div');
    t.style.cssText =
        'position:fixed;bottom:24px;right:24px;background:#17324d;color:#fff;' +
        'padding:0.75rem 1.2rem;border-radius:10px;font-size:0.88rem;font-weight:600;' +
        'box-shadow:0 6px 20px rgba(0,0,0,0.25);z-index:9999;' +
        'animation:slideUp 0.25s ease;';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(function () { t.remove(); }, 2800);
}

// Close modal on outside click
document.addEventListener('click', function (e) {
    var modal = document.getElementById('edit-modal');
    if (modal && e.target === modal) closeEditModal();
});

// ══════════════════════════════════════════════════════════════════════════
// MAP PICKER
// ══════════════════════════════════════════════════════════════════════════

var mapPicker       = null;   // Leaflet map instance
var mapPickerMarker = null;   // current draggable pin
var mapPickerReady  = false;  // initialized?

/* Toggle buka / tutup panel map picker */
function toggleMapPicker() {
    var body    = document.getElementById('map-picker-body');
    var chevron = document.getElementById('mp-chevron');
    var isOpen  = body.classList.toggle('open');
    chevron.classList.toggle('open', isOpen);

    if (isOpen && !mapPickerReady) {
        // Init sedikit tertunda agar elemen sudah visible (Leaflet butuh ukuran nyata)
        setTimeout(initMapPicker, 60);
    } else if (isOpen && mapPicker) {
        setTimeout(function () { mapPicker.invalidateSize(); }, 60);
    }
}

/* Inisialisasi Leaflet map di dalam panel */
function initMapPicker() {
    if (mapPickerReady) return;
    mapPickerReady = true;

    // Bandung center
    mapPicker = L.map('admin-map-picker', { zoomControl: true }).setView([-6.9175, 107.6191], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(mapPicker);

    // Klik di map → taruh / pindah pin
    mapPicker.on('click', function (e) {
        setPickerPin(e.latlng.lat, e.latlng.lng);
    });

    // Hint overlay
    var hint = L.control({ position: 'topright' });
    hint.onAdd = function () {
        var d = L.DomUtil.create('div');
        d.style.cssText =
            'background:rgba(255,255,255,0.92);padding:6px 10px;border-radius:8px;' +
            'font-size:0.78rem;color:#17324d;font-weight:600;pointer-events:none;' +
            'box-shadow:0 2px 8px rgba(0,0,0,0.12);';
        d.textContent = '🖱 Klik peta untuk meletakkan pin';
        return d;
    };
    hint.addTo(mapPicker);
}

/* Taruh / pindahkan pin ke koordinat tertentu */
function setPickerPin(lat, lng) {
    if (mapPickerMarker) {
        mapPickerMarker.setLatLng([lat, lng]);
    } else {
        mapPickerMarker = L.marker([lat, lng], {
            draggable: true,
            title: 'Geser untuk presisi lebih akurat'
        }).addTo(mapPicker);

        // Update form saat marker digeser
        mapPickerMarker.on('dragend', function () {
            var pos = mapPickerMarker.getLatLng();
            applyCoords(pos.lat, pos.lng);
        });

        // Tampilkan tombol hapus pin
        var btnClear = document.getElementById('btn-clear-marker');
        if (btnClear) btnClear.style.display = 'inline-flex';
    }

    applyCoords(lat, lng);
    mapPicker.panTo([lat, lng]);
}

/* Isi form latitude/longitude dan tampilkan hasil */
function applyCoords(lat, lng) {
    var latR = parseFloat(lat.toFixed(6));
    var lngR = parseFloat(lng.toFixed(6));

    // Isi field di form tambah wisata
    var latInput = document.getElementById('latitude');
    var lngInput = document.getElementById('longitude');
    if (latInput) { latInput.value = latR; latInput.style.borderColor = '#2f9b63'; }
    if (lngInput) { lngInput.value = lngR; lngInput.style.borderColor = '#2f9b63'; }

    // Tampilkan hasil koordinat
    var result = document.getElementById('coords-result');
    var picked = document.getElementById('picked-coords');
    if (picked) picked.textContent = latR + ', ' + lngR;
    if (result) result.classList.add('show');

    // Popup di marker
    if (mapPickerMarker) {
        mapPickerMarker
            .bindPopup('<b>📌 Lokasi dipilih</b><br>' + latR + ', ' + lngR)
            .openPopup();
    }
}

/* Hapus pin dan reset koordinat */
function clearPickerMarker() {
    if (mapPickerMarker) {
        mapPicker.removeLayer(mapPickerMarker);
        mapPickerMarker = null;
    }

    var latInput = document.getElementById('latitude');
    var lngInput = document.getElementById('longitude');
    if (latInput) { latInput.value = ''; latInput.style.borderColor = ''; }
    if (lngInput) { lngInput.value = ''; lngInput.style.borderColor = ''; }

    var result  = document.getElementById('coords-result');
    if (result) result.classList.remove('show');

    var btnClear = document.getElementById('btn-clear-marker');
    if (btnClear) btnClear.style.display = 'none';
}

/* Gunakan GPS perangkat */
function useMyLocation() {
    if (!navigator.geolocation) {
        showToast('Browser tidak mendukung geolokasi');
        return;
    }
    var btn = document.getElementById('btn-locate');
    btn.disabled    = true;
    btn.textContent = '⏳ Mendapatkan lokasi…';

    // Pastikan map sudah terbuka
    var body = document.getElementById('map-picker-body');
    if (!body.classList.contains('open')) toggleMapPicker();

    navigator.geolocation.getCurrentPosition(
        function (pos) {
            var lat = pos.coords.latitude;
            var lng = pos.coords.longitude;
            if (!mapPickerReady) {
                setTimeout(function () {
                    setPickerPin(lat, lng);
                    mapPicker.setView([lat, lng], 17);
                }, 120);
            } else {
                setPickerPin(lat, lng);
                mapPicker.setView([lat, lng], 17);
            }
            btn.disabled    = false;
            btn.innerHTML   =
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">'
                + '<circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M1 12h4M19 12h4"/>'
                + '</svg> Gunakan Lokasi Saya';
        },
        function (err) {
            var msg = err.code === 1
                ? 'Izin lokasi ditolak. Aktifkan di pengaturan browser.'
                : 'Gagal mendapatkan lokasi, coba lagi.';
            showToast(msg);
            btn.disabled    = false;
            btn.innerHTML   =
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">'
                + '<circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M1 12h4M19 12h4"/>'
                + '</svg> Gunakan Lokasi Saya';
        },
        { enableHighAccuracy: true, timeout: 12000 }
    );
}

/* Reset map picker saat form di-reset */
document.addEventListener('DOMContentLoaded', function () {
    var addForm = document.getElementById('add-wisata-form');
    if (addForm) {
        addForm.addEventListener('reset', function () {
            clearPickerMarker();
        });
    }
});
