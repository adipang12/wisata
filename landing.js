// Fetch data dari API
fetch('get_wisata.php')
    .then(res => {
        if (!res.ok) throw new Error('Failed to fetch data');
        return res.json();
    })
    .then(data => {
        if (!Array.isArray(data)) throw new Error('Invalid data format');
        loadLandingData(data);
    })
    .catch(error => {
        console.error('Error loading data:', error);
        loadFallbackStats();
    });

function loadLandingData(data) {
    // Calculate stats
    const totalPlaces = data.length;
    const categories = new Set(data.map(d => d.kategori || 'lainnya'));
    const placesWithPhotos = data.filter(d => d.photo_url).length;
    const ratings = data
        .filter(d => d.rating && d.rating > 0)
        .map(d => parseFloat(d.rating));
    const avgRating = ratings.length > 0
        ? (ratings.reduce((a, b) => a + b, 0) / ratings.length).toFixed(1)
        : 4.5;

    // Update stats
    document.getElementById('stat-places').textContent = totalPlaces;
    document.getElementById('stat-categories').textContent = categories.size;
    document.getElementById('stat-rating').textContent = avgRating;
    document.getElementById('stat-photos').textContent = Math.round((placesWithPhotos / totalPlaces) * 100) + '%';

    // Sort by rating and get top featured places
    const featured = data
        .filter(d => d.rating && parseFloat(d.rating) > 0)
        .sort((a, b) => parseFloat(b.rating || 0) - parseFloat(a.rating || 0))
        .slice(0, 3);

    if (featured.length === 0) {
        loadFallbackFeatured();
        return;
    }

    // Render featured cards
    const grid = document.getElementById('featured-grid');
    grid.innerHTML = '';
    featured.forEach(place => {
        const card = createFeaturedCard(place);
        grid.appendChild(card);
    });
}

function createFeaturedCard(place) {
    const card = document.createElement('div');
    card.className = 'featured-card';

    const imageSrc = getPlaceholderImage(place);
    const rating = place.rating ? parseFloat(place.rating).toFixed(1) : 'N/A';
    const nama = escapeHTML(place.nama || 'Tidak diketahui');

    card.innerHTML = `
        <div class="featured-image">
            <img src="${imageSrc}" alt="${nama}" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 600 220%22%3E%3Crect width=%22600%22 height=%22220%22 fill=%22%23dce5ee%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22%23637083%22 font-family=%22Arial%22 font-size=%2224%22%3EFoto tidak tersedia%3C/text%3E%3C/svg%3E'">
        </div>
        <div class="featured-info">
            <h3>${nama}</h3>
            <div class="rating">
                <span class="rating-value">★ ${rating}</span>
                ${place.userRatingCount ? `<span> (${place.userRatingCount} ulasan)</span>` : ''}
            </div>
        </div>
    `;

    return card;
}

function getPlaceholderImage(place) {
    const lat = place.latitude;
    const lng = place.longitude;

    if (lat && lng) {
        return `https://staticmap.openstreetmap.de/staticmap.php?center=${encodeURIComponent(lat + ',' + lng)}&zoom=14&size=600x220&markers=${encodeURIComponent(lat + ',' + lng + ',red-pushpin')}`;
    }

    return 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 600 220%22%3E%3Crect width=%22600%22 height=%22220%22 fill=%22%23dce5ee%22/%3E%3C/svg%3E';
}

function escapeHTML(value) {
    return String(value ?? '').replace(/[&<>"']/g, function(char) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[char];
    });
}

function loadFallbackStats() {
    // Default values if API fails
    document.getElementById('stat-places').textContent = '198';
    document.getElementById('stat-categories').textContent = '10';
    document.getElementById('stat-rating').textContent = '4.5';
    document.getElementById('stat-photos').textContent = '85%';
    loadFallbackFeatured();
}

function loadFallbackFeatured() {
    const grid = document.getElementById('featured-grid');
    grid.innerHTML = '';

    const fallbackPlaces = [
        {
            nama: 'Tangkuban Perahu',
            rating: 4.6,
            latitude: -6.7719,
            longitude: 107.6055
        },
        {
            nama: 'Kawah Putih',
            rating: 4.5,
            latitude: -7.1419,
            longitude: 107.3045
        },
        {
            nama: 'Taman Hutan Raya',
            rating: 4.4,
            latitude: -6.8733,
            longitude: 107.6189
        }
    ];

    fallbackPlaces.forEach(place => {
        const card = createFeaturedCard(place);
        grid.appendChild(card);
    });
}
