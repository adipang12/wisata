<?php
// Isi dengan API key dari Google Maps Platform yang sudah mengaktifkan Places API (New).
define('GOOGLE_MAPS_API_KEY', '');

// Gemini AI API Key — ambil gratis di https://aistudio.google.com
define('GEMINI_API_KEY', '');

// Opsional: true untuk coba foto gratis dari Wikimedia kalau Google tidak menemukan foto.
define('ENABLE_WIKIMEDIA_FALLBACK', true);

// Cache hasil Places API supaya tidak boros request saat halaman dibuka berulang.
define('PLACE_INFO_CACHE_SECONDS', 604800);
