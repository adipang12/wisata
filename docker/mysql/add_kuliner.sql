-- ============================================================
-- Tambah tempat kuliner & wisata populer Bandung
-- Jalankan: docker compose exec db mysql -u wisata_user -p wisata_bandung < add_kuliner.sql
-- ============================================================

INSERT IGNORE INTO wisata (nama, kategori, latitude, longitude, rating, review) VALUES

-- ── KULINER: Makanan Tradisional & Ikonik ────────────────────────────────
('Batagor Kingsley',          'kuliner', -6.919844, 107.606534, 4.5, 'Batagor paling legendaris di Bandung sejak 1940-an, wajib coba!'),
('Warung Nasi Bancakan',      'kuliner', -6.914781, 107.615897, 4.6, 'Nasi sunda otentik dengan lauk lengkap, suasana rumahan'),
('Sindang Reret Naripan',     'kuliner', -6.917561, 107.610205, 4.4, 'Restoran sunda klasik Bandung, sate maranggi dan nasi timbel'),
('Mie Kocok Mang Dadeng',     'kuliner', -6.920900, 107.614600, 4.7, 'Mie kocok Bandung paling terkenal, antrean panjang tanda enak'),
('Cendol Elizabeth',          'kuliner', -6.919925, 107.605379, 4.5, 'Cendol legendaris Bandung, segar dan manis alami'),
('Sate Hadori',               'kuliner', -6.920384, 107.609735, 4.4, 'Sate kambing dan sapi terkenal di pusat kota Bandung'),
('Warung Nasi Ampera',        'kuliner', -6.906049, 107.616149, 4.2, 'Warteg sunda terpopuler di Bandung, murah meriah'),
('Batagor Riri',              'kuliner', -6.924400, 107.625500, 4.3, 'Batagor goreng kriuk dengan bumbu kacang khas'),
('Laksana Restaurant',        'kuliner', -6.921500, 107.606900, 4.3, 'Restoran sunda mewah di pusat kota, cocok untuk keluarga'),
('Gepuk Ny. Ong',             'kuliner', -6.918000, 107.610000, 4.5, 'Gepuk dan dendeng sapi kering oleh-oleh khas Bandung'),
('Nasi Goreng Mafia',         'kuliner', -6.901600, 107.620100, 4.4, 'Nasi goreng hits kekinian dengan porsi besar'),
('Mie Baso Akung',            'kuliner', -6.916100, 107.607800, 4.5, 'Mie baso legendaris Bandung, kuah gurih dan kenyal'),
('Ayam Goreng Brebes',        'kuliner', -6.910000, 107.613000, 4.3, 'Ayam goreng renyah khas Sunda dengan sambal terasi'),
('Es Oyen',                   'kuliner', -6.916800, 107.608600, 4.6, 'Es campur legendaris sejak 1929, hits di semua generasi'),
('Warung Sate Pak Obing',     'kuliner', -6.919200, 107.611500, 4.4, 'Sate ayam dan kambing pilihan warga Bandung'),
('Kedai Susu Murni',          'kuliner', -6.916000, 107.614000, 4.3, 'Susu sapi segar langsung dari peternak'),
('Warung Pojok BKR',          'kuliner', -6.923700, 107.614000, 4.2, 'Masakan sunda sederhana dengan porsi besar'),

-- ── KULINER: Cafe & Kekinian ──────────────────────────────────────────────
('Surabi Enhaii',             'kuliner', -6.877203, 107.597232, 4.5, 'Surabi Bandung terkenal dengan aneka topping unik, buka 24 jam'),
('Kopi Progo',                'kuliner', -6.910442, 107.611458, 4.6, 'Kedai kopi legendaris Bandung sejak 1930, kopi tubruk terbaik'),
('Warung Sudi Mampir',        'kuliner', -6.893175, 107.617240, 4.4, 'Restoran Sunda di kawasan Dago, view bagus dan menu lengkap'),
('Cafe D Pakar',              'kuliner', -6.857043, 107.638025, 4.5, 'Cafe di kawasan hutan Dago Pakar, view alam dan udara sejuk'),
('The Valley Bistro Cafe',    'kuliner', -6.861200, 107.600800, 4.5, 'Restoran dengan view lembah dan danau, spot foto cantik'),
('Warung Daun',               'kuliner', -6.882500, 107.600400, 4.4, 'Masakan sunda di suasana alam, populer untuk makan siang'),
('Philosophy Coffee Bandung', 'kuliner', -6.902400, 107.618800, 4.6, 'Specialty coffee terbaik Bandung, ada berbagai metode seduh'),
('Nanny s Pavillon',          'kuliner', -6.892900, 107.609600, 4.5, 'Cafe bergaya Eropa di area Dago, pancake dan pasta lezat'),
('Pempek Pak Raden',          'kuliner', -6.915600, 107.611200, 4.4, 'Pempek Palembang authentik di Bandung, kuah cuko segar'),
('Warung Bu Eha',             'kuliner', -6.800200, 107.599600, 4.5, 'Warung nasi sunda di Lembang, sarapan dengan udara segar'),
('Floating Market Lembang',   'kuliner', -6.801739, 107.599723, 4.6, 'Pasar terapung unik di atas danau, aneka kuliner khas Sunda'),
('Dusun Bambu',               'kuliner', -6.789984, 107.578646, 4.7, 'Wisata alam dan kuliner di Lembang, suasana pedesaan nan asri'),
('Resto Kampung Daun',        'kuliner', -6.847100, 107.580900, 4.5, 'Makan di saung di tengah alam lembah hijau, khas Sunda'),
('De Ranch Lembang',          'kuliner', -6.817900, 107.617500, 4.3, 'Restoran berkonsep ranch dengan atraksi kuda dan alam'),
('The Peak Resort',           'kuliner', -6.793600, 107.616900, 4.4, 'Restoran dan resort di puncak Lembang, view panorama Bandung'),

-- ── KULINER: Street Food & Oleh-oleh ────────────────────────────────────
('Jalan Braga',               'kuliner', -6.915147, 107.606425, 4.5, 'Kawasan kuliner dan jalan-jalan ikonik Bandung, cafe vintage berbaris'),
('Jalan Cihampelas',          'kuliner', -6.901044, 107.597613, 4.2, 'Surganya belanja jeans dan kuliner kaki lima Bandung'),
('Pasar Baru Trade Center',   'belanja',  -6.920400, 107.605100, 4.1, 'Pusat perbelanjaan tekstil dan oleh-oleh khas Bandung'),
('Cihampelas Walk CiWalk',    'belanja',  -6.901000, 107.597600, 4.3, 'Mall outdoor modern di Bandung, shopping dan kuliner'),
('Paris Van Java Mall',       'belanja',  -6.896700, 107.581400, 4.4, 'Mall premium Bandung dengan berbagai resto dan hiburan'),
('Pasar Cikapundung',         'belanja',  -6.915900, 107.606700, 4.0, 'Pasar elektronik dan barang bekas khas Bandung'),
('Braga City Walk',           'belanja',  -6.914900, 107.606900, 4.2, 'Pusat perbelanjaan di kawasan bersejarah Braga'),

-- ── WISATA: Tempat Populer yang Sering Direkomendasikan AI ──────────────
('Alun Alun Bandung',         'attraction', -6.921500, 107.606900, 4.3, 'Taman kota ikonik Bandung dengan pohon beringin dan masjid raya'),
('Gedung Sate',               'attraction', -6.902400, 107.618800, 4.7, 'Bangunan bersejarah ikonik Bandung, arsitektur kolonial megah'),
('Gedung Merdeka',            'museum',     -6.914100, 107.609800, 4.5, 'Gedung bersejarah Konferensi Asia Afrika 1955'),
('Sari Ater Hot Spring',      'attraction', -6.725700, 107.631000, 4.4, 'Pemandian air panas alami di kaki Gunung Tangkuban Perahu'),
('Ciwidey Valley Hot Spring', 'attraction', -7.097700, 107.437100, 4.3, 'Wisata air panas di Ciwidey dengan kolam rendam alami'),
('Kawah Rengganis Ciwidey',   'attraction', -7.097000, 107.432000, 4.2, 'Kawah vulkanik aktif di dekat Kawah Putih Ciwidey'),
('Bukit Moko',                'viewpoint',  -6.885600, 107.669800, 4.6, 'Bukit tertinggi di Bandung timur, view kota Bandung 360 derajat'),
('Bukit Bintang Bandung',     'viewpoint',  -6.963600, 107.639600, 4.4, 'Tempat nongkrong hits dengan pemandangan kerlap kerlip kota'),
('Puncak Bintang',            'viewpoint',  -6.861600, 107.657900, 4.5, 'View pagi terbaik Bandung, sunrise yang memukau'),
('Stone Garden Citatah',      'attraction', -6.857900, 107.433600, 4.5, 'Taman batu purba berusia jutaan tahun, unik dan eksotis'),
('Goa Pawon',                 'attraction', -6.858800, 107.437700, 4.2, 'Situs prasejarah di kawasan karst Padalarang'),
('Situ Lembang',              'attraction', -6.831100, 107.621500, 4.3, 'Danau alami di kawasan hutan pinus Lembang'),
('Curug Malela',              'attraction', -6.980200, 107.367200, 4.6, 'Air terjun lebar seperti Niagara mini di Bandung Barat'),
('Curug Cimahi',              'attraction', -6.853400, 107.523400, 4.4, 'Air terjun setinggi 87m di Cisarua, mudah diakses'),
('The Lodge Maribaya',        'attraction', -6.833700, 107.638900, 4.5, 'Wisata alam Lembang dengan skywalk dan flying fox');
