<?php
header('Content-Type: application/json');

$configPath = __DIR__ . '/config.php';
if (is_readable($configPath)) {
    require_once $configPath;
}

$name = trim($_GET['name'] ?? '');
$lat = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
$lng = filter_input(INPUT_GET, 'lng', FILTER_VALIDATE_FLOAT);

function fallbackPhotoForLocation($lat, $lng) {
    if ($lat !== false && $lat !== null && $lng !== false && $lng !== null) {
        return 'https://staticmap.openstreetmap.de/staticmap.php'
            . '?center=' . rawurlencode($lat . ',' . $lng)
            . '&zoom=15&size=600x360'
            . '&markers=' . rawurlencode($lat . ',' . $lng . ',red-pushpin');
    }

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 360"><rect width="600" height="360" fill="#dce5ee"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#637083" font-family="Arial" font-size="28">Foto belum tersedia</text></svg>'
    );
}

$fallbackPhoto = fallbackPhotoForLocation($lat, $lng);

function respondPlaceInfo($info) {
    echo json_encode(array_merge([
        'photo' => fallbackPhotoForLocation(null, null),
        'source' => 'local',
        'attribution' => '',
        'rating' => null,
        'userRatingCount' => null,
        'placeName' => '',
        'googleMapsUri' => ''
    ], $info), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function makePlaceInfo($photo, $source, $attribution = '', $rating = null, $userRatingCount = null, $placeName = '', $googleMapsUri = '', $address = '', $phone = '', $website = '', $openNow = null, $hours = []) {
    return [
        'photo'          => $photo,
        'source'         => $source,
        'attribution'    => $attribution,
        'rating'         => $rating,
        'userRatingCount'=> $userRatingCount,
        'placeName'      => $placeName,
        'googleMapsUri'  => $googleMapsUri,
        'address'        => $address,
        'phone'          => $phone,
        'website'        => $website,
        'openNow'        => $openNow,
        'hours'          => $hours,
    ];
}

// FUNGSI FETCH DENGAN USER-AGENT AGAR TIDAK DIBLOKIR WIKIPEDIA
function fetchJson($url, $options = []) {
    // Inject User-Agent jika belum ada
    if (!isset($options['http']['header'])) {
        $options['http']['header'] = "User-Agent: WebGIS-ExploreBandung/1.0 (ariski@localhost)\r\n";
    }
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}

function cachePath($name, $lat, $lng) {
    $cacheDir = __DIR__ . '/cache';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
    }

    return $cacheDir . '/place_' . sha1($name . '|' . $lat . '|' . $lng) . '.json';
}

function readPlaceCache($name, $lat, $lng) {
    $ttl = defined('PLACE_INFO_CACHE_SECONDS') ? (int) PLACE_INFO_CACHE_SECONDS : 604800;
    $path = cachePath($name, $lat, $lng);

    if (!is_readable($path) || filemtime($path) + $ttl < time()) {
        return null;
    }

    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : null;
}

function writePlaceCache($name, $lat, $lng, $data) {
    $path = cachePath($name, $lat, $lng);
    @file_put_contents($path, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function googlePlaceInfo($name, $lat, $lng, $fallbackPhoto) {
    if (!defined('GOOGLE_MAPS_API_KEY') || GOOGLE_MAPS_API_KEY === '' || $name === '' || $name === 'Tidak diketahui') {
        return null;
    }

    $body = [
        'textQuery' => $name . ' Bandung',
        'maxResultCount' => 1
    ];

    if ($lat !== false && $lat !== null && $lng !== false && $lng !== null) {
        $body['locationBias'] = [
            'circle' => [
                'center' => [
                    'latitude' => $lat,
                    'longitude' => $lng
                ],
                'radius' => 1500
            ]
        ];
    }

    $data = fetchJson('https://places.googleapis.com/v1/places:searchText', [
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'X-Goog-Api-Key: ' . GOOGLE_MAPS_API_KEY,
                'X-Goog-FieldMask: places.displayName,places.photos,places.rating,places.userRatingCount,places.googleMapsUri,places.formattedAddress,places.internationalPhoneNumber,places.websiteUri,places.regularOpeningHours',
                'User-Agent: WebGIS-ExploreBandung/1.0'
            ],
            'content' => json_encode($body),
            'timeout' => 8
        ]
    ]);

    $place = $data['places'][0] ?? null;
    if (!is_array($place)) {
        return null;
    }

    $photoUrl = $fallbackPhoto;
    $photo = $place['photos'][0] ?? null;
    if (is_array($photo) && !empty($photo['name'])) {
        $mediaUrl = 'https://places.googleapis.com/v1/' . $photo['name'] . '/media'
            . '?maxWidthPx=600&skipHttpRedirect=true&key=' . rawurlencode(GOOGLE_MAPS_API_KEY);
        $media = fetchJson($mediaUrl, ['http' => ['timeout' => 8, 'header' => "User-Agent: WebGIS-ExploreBandung/1.0\r\n"]]);
        $photoUrl = $media['photoUri'] ?? $fallbackPhoto;
    }

    $attributions = is_array($photo) ? ($photo['authorAttributions'] ?? []) : [];
    $displayNames = array_filter(array_map(function($item) {
        return $item['displayName'] ?? '';
    }, is_array($attributions) ? $attributions : []));

    $openNow = $place['regularOpeningHours']['openNow'] ?? null;
    $weekdayDescriptions = $place['regularOpeningHours']['weekdayDescriptions'] ?? [];

    return makePlaceInfo(
        $photoUrl,
        'google',
        implode(', ', $displayNames),
        isset($place['rating']) ? (float) $place['rating'] : null,
        isset($place['userRatingCount']) ? (int) $place['userRatingCount'] : null,
        $place['displayName']['text'] ?? '',
        $place['googleMapsUri'] ?? '',
        $place['formattedAddress'] ?? '',
        $place['internationalPhoneNumber'] ?? '',
        $place['websiteUri'] ?? '',
        $openNow,
        $weekdayDescriptions
    );
}

function wikimediaPhoto($name, $lat, $lng) {
    $wikiOptions = [
        'http' => [
            'timeout' => 8,
            'header' => "User-Agent: WebGIS-ExploreBandung/1.0 (ariski@localhost)\r\n"
        ]
    ];

    // 1. Cari by nama dulu (lebih akurat, foto sesuai tempat)
    if ($name !== '' && $name !== 'Tidak diketahui') {
        $searchUrl = 'https://commons.wikimedia.org/w/api.php?action=query'
            . '&generator=search'
            . '&gsrsearch=' . rawurlencode($name . ' Bandung Indonesia')
            . '&gsrnamespace=6'
            . '&gsrlimit=5'
            . '&prop=imageinfo'
            . '&iiprop=url|mime|size'
            . '&iiurlwidth=600'
            . '&format=json';

        $searchData = fetchJson($searchUrl, $wikiOptions);
        $pages = $searchData['query']['pages'] ?? [];

        foreach ($pages as $page) {
            $mime = $page['imageinfo'][0]['mime'] ?? '';
            // Skip SVG, portrait-only images (terlalu kecil width vs height)
            if (strpos($mime, 'svg') !== false) continue;
            $w = $page['imageinfo'][0]['width'] ?? 0;
            $h = $page['imageinfo'][0]['height'] ?? 0;
            // Skip gambar portrait (foto orang) — landscape lebih cocok untuk tempat
            if ($h > 0 && $w > 0 && $h > $w * 1.5) continue;
            $thumb = $page['imageinfo'][0]['thumburl'] ?? $page['imageinfo'][0]['url'] ?? null;
            if ($thumb) {
                return makePlaceInfo($thumb, 'wikimedia', 'Wikimedia Commons');
            }
        }
    }

    // 2. Fallback: geo-search by koordinat (radius lebih kecil, lebih spesifik)
    if ($lat !== false && $lat !== null && $lng !== false && $lng !== null) {
        $geoUrl = 'https://commons.wikimedia.org/w/api.php?action=query'
            . '&generator=geosearch'
            . '&ggscoord=' . rawurlencode($lat . '|' . $lng)
            . '&ggsradius=500'
            . '&ggsnamespace=6'
            . '&ggslimit=5'
            . '&prop=imageinfo'
            . '&iiprop=url|mime|size'
            . '&iiurlwidth=600'
            . '&format=json';

        $geoData = fetchJson($geoUrl, $wikiOptions);
        $pages = $geoData['query']['pages'] ?? [];

        foreach ($pages as $page) {
            $mime = $page['imageinfo'][0]['mime'] ?? '';
            if (strpos($mime, 'svg') !== false) continue;
            $w = $page['imageinfo'][0]['width'] ?? 0;
            $h = $page['imageinfo'][0]['height'] ?? 0;
            if ($h > 0 && $w > 0 && $h > $w * 1.5) continue;
            $thumb = $page['imageinfo'][0]['thumburl'] ?? $page['imageinfo'][0]['url'] ?? null;
            if ($thumb) {
                return makePlaceInfo($thumb, 'wikimedia', 'Wikimedia Commons');
            }
        }
    }

    return null;
}

$cached = readPlaceCache($name, $lat, $lng);
if ($cached) {
    respondPlaceInfo($cached);
}

$google = googlePlaceInfo($name, $lat, $lng, $fallbackPhoto);
if ($google) {
    writePlaceCache($name, $lat, $lng, $google);
    respondPlaceInfo($google);
}

if (defined('ENABLE_WIKIMEDIA_FALLBACK') && ENABLE_WIKIMEDIA_FALLBACK) {
    $wikimedia = wikimediaPhoto($name, $lat, $lng);
    if ($wikimedia) {
        writePlaceCache($name, $lat, $lng, $wikimedia);
        respondPlaceInfo($wikimedia);
    }
}

respondPlaceInfo(makePlaceInfo($fallbackPhoto, 'local'));