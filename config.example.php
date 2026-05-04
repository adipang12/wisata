<?php
// Salin file ini menjadi config.php, lalu isi API key Google Maps Platform.
// Aktifkan Places API (New) di Google Cloud supaya foto lokasi dari Google bisa dipakai.
define('GOOGLE_MAPS_API_KEY', '');

// Opsional: true untuk coba foto gratis dari Wikimedia kalau Google tidak menemukan foto.
define('ENABLE_WIKIMEDIA_FALLBACK', true);

// Cache hasil Places API supaya tidak boros request saat halaman dibuka berulang.
define('PLACE_INFO_CACHE_SECONDS', 604800);
