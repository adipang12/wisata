<?php
// Semua key dibaca dari environment variable (.env file di VPS)
// Jangan isi langsung di sini — gunakan .env

define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY') ?: '');
define('GROQ_API_KEY',        getenv('GROQ_API_KEY')        ?: '');

// Opsional: true untuk coba foto gratis dari Wikimedia kalau Google tidak menemukan foto.
define('ENABLE_WIKIMEDIA_FALLBACK', true);

// Cache hasil Places API supaya tidak boros request saat halaman dibuka berulang.
define('PLACE_INFO_CACHE_SECONDS', 604800);
