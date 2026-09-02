<?php
// ==============================================================================
// TRIANIME - Single File PHP Server & API Caching Layer for Render
// ==============================================================================

// 1. Simple API Caching Proxy to avoid Jikan Rate Limits on Render/Client
if (isset($_GET['api_endpoint'])) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    $endpoint = rawurldecode($_GET['api_endpoint']);
    $cacheDir = __DIR__ . '/cache';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0777, true);
    }

    $cacheKey = md5($endpoint);
    $cacheFile = $cacheDir . '/' . $cacheKey . '.json';
    $cacheLifetime = 3600; // Cache API responses for 1 hour

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheLifetime)) {
        echo file_get_contents($cacheFile);
        exit;
    }

    $url = 'https://api.jikan.moe/v4' . $endpoint;
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: TRIANIME-RenderApp/1.0\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);

    if ($response !== FALSE) {
        @file_put_contents($cacheFile, $response);
        echo $response;
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to fetch from upstream Jikan API"]);
    }
    exit;
}

// 2. Render Server Router Compatibility
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRIANIME - Watch Anime Online</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            bg: '#0a0a0a',
                            card: '#121212',
                            panel: '#1e1e1e',
                            accent: '#ffbade',
                            accentHover: '#fca5d5'
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #0a0a0a; color: #e5e5e5; font-family: system-ui, sans-serif; }
        .glass-nav { background: rgba(18, 18, 18, 0.8); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0a0a0a; }
        ::-webkit-scrollbar-thumb { background: #1e1e1e; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #ffbade; }
        .fade-in { animation: fadeIn 0.3s ease-in-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between">

    <!-- Header -->
    <header class="glass-nav fixed top-0 left-0 right-0 z-50">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="#" onclick="navigate('home')" class="text-2xl font-black text-brand-accent flex items-center gap-2">
                <i class="fa-solid fa-play-circle text-3xl"></i> TRI<span class="text-white">ANIME</span>
            </a>
            <nav class="hidden md:flex space-x-6 text-sm font-semibold">
                <a href="#" onclick="navigate('home')" class="hover:text-brand-accent transition">Home</a>
                <a href="#" onclick="navigate('browse')" class="hover:text-brand-accent transition">Browse</a>
            </nav>
            <div class="hidden md:flex flex-1 max-w-md mx-8">
                <form onsubmit="handleSearch(event)" class="w-full flex items-center relative">
                    <input type="text" id="searchInput" placeholder="Search anime..." 
                           class="w-full bg-brand-panel text-sm text-white px-4 py-2 pr-10 rounded-full border border-gray-800 focus:outline-none focus:border-brand-accent">
                    <button type="submit" class="absolute right-3 text-gray-400 hover:text-brand-accent">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>
            </div>
            <button class="md:hidden text-2xl text-gray-300" onclick="toggleMobileMenu()"><i class="fa-solid fa-bars"></i></button>
        </div>
        <div id="mobileMenu" class="hidden md:hidden bg-brand-card border-b border-gray-800 px-4 py-4 space-y-3">
            <form onsubmit="handleSearch(event)" class="flex items-center relative mb-4">
                <input type="text" id="searchInputMobile" placeholder="Search anime..." class="w-full bg-brand-panel text-sm text-white px-4 py-2 pr-10 rounded-full border border-gray-800">
                <button type="submit" class="absolute right-3 text-gray-400"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
            <a href="#" onclick="navigate('home'); toggleMobileMenu();" class="block font-semibold">Home</a>
            <a href="#" onclick="navigate('browse'); toggleMobileMenu();" class="block font-semibold">Browse</a>
        </div>
    </header>

    <main id="app" class="pt-20 pb-12 max-w-7xl mx-auto px-4 w-full flex-grow"></main>

    <footer class="bg-brand-card border-t border-gray-800 py-6 text-center text-xs text-gray-500">
        <p>&copy; 2026 TRIANIME. Hosted on Render.</p>
    </footer>

    <script>
        const HARDCODED_GENRES = [
            { id: 1, name: 'Action' }, { id: 2, name: 'Adventure' }, { id: 4, name: 'Comedy' },
            { id: 8, name: 'Drama' }, { id: 10, name: 'Fantasy' }, { id: 62, name: 'Isekai' },
            { id: 22, name: 'Romance' }, { id: 24, name: 'Sci-Fi' }, { id: 36, name: 'Slice of Life' }
        ];

        let state = {
            view: 'home', heroList: [], heroIndex: 0, heroInterval: null,
            airingList: [], upcomingList: [], browseList: [], browsePage: 1,
            browseFilter: 'bypopularity', browseGenre: null, currentAnime: null,
            selectedEpisode: 1, searchQuery: ''
        };

        const delay = ms => new Promise(res => setTimeout(res, ms));

        async function fetchAPI(endpoint) {
            try {
                // Route through server-side proxy
                const res = await fetch(`?api_endpoint=${encodeURIComponent(endpoint)}`);
                if (!res.ok) throw new Error('Proxy error');
                const json = await res.json();
                return json.data;
            } catch (err) {
                console.error(`Fetch error: ${endpoint}`, err);
                return null;
            }
        }

        async function init() {
            renderLoading();
            let heroData = await fetchAPI('/top/anime?filter=airing&limit=5');
            state.heroList = heroData || [];
            await delay(300);

            let airingData = await fetchAPI('/seasons/now?limit=12');
            state.airingList = airingData || [];
            await delay(300);

            let upcomingData = await fetchAPI('/seasons/upcoming?limit=6');
            state.upcomingList = upcomingData || [];

            navigate('home');
        }

        function navigate(view, params = {}) {
            state.view = view;
            if (state.heroInterval) clearInterval(state.heroInterval);
            const app = document.getElementById('app');
            app.innerHTML = '';

            if (view === 'home') { renderHome(); startHeroRotation(); }
            else if (view === 'browse') {
                state.browseGenre = params.genre !== undefined ? params.genre : state.browseGenre;
                state.browseFilter = params.filter || state.browseFilter;
                state.browsePage = params.page || 1;
                loadBrowseContent();
            } else if (view === 'watch') {
                if (params.anime) state.currentAnime = params.anime;
                state.selectedEpisode = params.ep || 1;
                renderWatch();
            } else if (view === 'search') {
                if (params.query) state.searchQuery = params.query;
                loadSearchContent();
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function toggleMobileMenu() { document.getElementById('mobileMenu').classList.toggle('hidden'); }
        function handleSearch(e) {
            e.preventDefault();
            const q = document.getElementById('searchInput').value || document.getElementById('searchInputMobile').value;
            if (q.trim()) navigate('search', { query: q.trim() });
        }

        function renderLoading() {
            document.getElementById('app').innerHTML = `
                <div class="flex flex-col items-center justify-center min-h-[60vh]">
                    <div class="w-10 h-10 border-4 border-brand-accent border-t-transparent rounded-full animate-spin mb-4"></div>
                    <p class="text-gray-400 font-medium text-sm">Connecting to server...</p>
                </div>`;
        }

        function renderHome() {
            const app = document.getElementById('app');
            app.className = 'pt-20 pb-12 max-w-7xl mx-auto px-4 w-full flex-grow fade-in';
            app.innerHTML = `
                <div id="heroContainer" class="relative rounded-2xl overflow-hidden bg-brand-card h-[380px] md:h-[420px] mb-10 shadow-2xl border border-gray-800"></div>
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    <div class="lg:col-span-3">
                        <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                            <span class="w-2 h-6 bg-brand-accent rounded-full inline-block"></span> Currently Airing
                        </h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                            ${state.airingList.map(a => createAnimeCard(a)).join('')}
                        </div>
                    </div>
                    <div class="space-y-8">
                        <div class="bg-brand-card p-5 rounded-2xl border border-gray-800">
                            <h3 class="text-md font-bold mb-4 flex items-center gap-2"><i class="fa-solid fa-tags text-brand-accent"></i> Genres</h3>
                            <div class="flex flex-wrap gap-2">
                                ${HARDCODED_GENRES.map(g => `<button onclick="navigate('browse', {genre: ${g.id}})" class="text-xs bg-brand-panel hover:bg-brand-accent hover:text-black px-3 py-1.5 rounded-lg border border-gray-800 transition">${g.name}</button>`).join('')}
                            </div>
                        </div>
                        <div class="bg-brand-card p-5 rounded-2xl border border-gray-800">
                            <h3 class="text-md font-bold mb-4 flex items-center gap-2"><i class="fa-solid fa-fire text-brand-accent"></i> Top Upcoming</h3>
                            <div class="space-y-4">
                                ${state.upcomingList.map((a, i) => `
                                    <div onclick="navigate('watch', {anime: ${JSON.stringify(a).replace(/"/g, '&quot;')}})" class="flex items-center gap-3 cursor-pointer group">
                                        <span class="font-black text-gray-600 group-hover:text-brand-accent w-4">0${i+1}</span>
                                        <img src="${a.images?.jpg?.small_image_url}" class="w-12 h-16 object-cover rounded-lg">
                                        <div class="overflow-hidden">
                                            <h4 class="text-xs font-bold truncate group-hover:text-brand-accent">${a.title}</h4>
                                            <p class="text-[10px] text-gray-500 mt-1">${a.type || 'TV'}</p>
                                        </div>
                                    </div>`).join('')}
                            </div>
                        </div>
                    </div>
                </div>`;
            updateHero();
        }

        function startHeroRotation() {
            if (state.heroList.length <= 1) return;
            state.heroInterval = setInterval(() => {
                state.heroIndex = (state.heroIndex + 1) % state.heroList.length;
                updateHero();
            }, 6000);
        }

        function updateHero() {
            const container = document.getElementById('heroContainer');
            if (!container || state.heroList.length === 0) return;
            const anime = state.heroList[state.heroIndex];
            container.innerHTML = `
                <div class="absolute inset-0 bg-cover bg-center transition-all duration-700" style="background-image: url('${anime.images?.jpg?.large_image_url}');"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-brand-bg via-brand-card/80 to-transparent"></div>
                <div class="absolute bottom-0 left-0 right-0 p-6 md:p-10 max-w-2xl">
                    <span class="text-xs font-bold bg-brand-accent text-black px-2.5 py-1 rounded-md mb-3 inline-block">Featured</span>
                    <h1 class="text-2xl md:text-4xl font-black text-white line-clamp-1 mb-2">${anime.title}</h1>
                    <p class="text-xs md:text-sm text-gray-300 line-clamp-2 mb-5">${anime.synopsis || ''}</p>
                    <button onclick="navigate('watch', {anime: ${JSON.stringify(anime).replace(/"/g, '&quot;')}})" class="bg-brand-accent text-black font-bold px-6 py-2 rounded-xl text-sm flex items-center gap-2">
                        <i class="fa-solid fa-play"></i> Watch Now
                    </button>
                </div>`;
        }

        async function loadBrowseContent() {
            renderLoading();
            let ep = `/anime?page=${state.browsePage}&limit=16`;
            if (state.browseGenre) ep += `&genres=${state.browseGenre}`;
            else if (state.browseFilter === 'airing') ep += `&status=airing`;
            else if (state.browseFilter === 'movie') ep += `&type=movie`;
            const data = await fetchAPI(ep);
            state.browseList = data || [];
            renderBrowse();
        }

        function renderBrowse() {
            const app = document.getElementById('app');
            app.className = 'pt-20 pb-12 max-w-7xl mx-auto px-4 w-full flex-grow fade-in';
            app.innerHTML = `
                <div class="flex justify-between items-center mb-8">
                    <h2 class="text-2xl font-black">Browse Library</h2>
                    <div class="flex gap-2">
                        <button onclick="navigate('browse', {filter: 'bypopularity', genre: null, page: 1})" class="px-3 py-1.5 rounded-lg text-xs font-semibold ${!state.browseGenre && state.browseFilter==='bypopularity' ? 'bg-brand-accent text-black' : 'bg-brand-card'}">Top Rated</button>
                        <button onclick="navigate('browse', {filter: 'airing', genre: null, page: 1})" class="px-3 py-1.5 rounded-lg text-xs font-semibold ${state.browseFilter==='airing' ? 'bg-brand-accent text-black' : 'bg-brand-card'}">Airing</button>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-8">
                    ${state.browseList.map(a => createAnimeCard(a)).join('')}
                </div>
                <div class="flex justify-center gap-4">
                    <button onclick="navigate('browse', {page: ${Math.max(1, state.browsePage - 1)}})" class="px-4 py-2 bg-brand-card rounded-lg text-xs font-bold">Prev</button>
                    <span class="text-xs text-gray-400 flex items-center">Page ${state.browsePage}</span>
                    <button onclick="navigate('browse', {page: ${state.browsePage + 1}})" class="px-4 py-2 bg-brand-card rounded-lg text-xs font-bold">Next</button>
                </div>`;
        }

        async function loadSearchContent() {
            renderLoading();
            const data = await fetchAPI(`/anime?q=${encodeURIComponent(state.searchQuery)}&limit=20`);
            const app = document.getElementById('app');
            app.className = 'pt-20 pb-12 max-w-7xl mx-auto px-4 w-full flex-grow fade-in';
            app.innerHTML = `
                <h2 class="text-xl font-bold mb-6">Search Results for: <span class="text-brand-accent">"${state.searchQuery}"</span></h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    ${(data || []).map(a => createAnimeCard(a)).join('')}
                </div>`;
        }

        function renderWatch() {
            const anime = state.currentAnime;
            if (!anime) return navigate('home');
            const totalEps = anime.episodes || 12;
            const app = document.getElementById('app');
            app.className = 'pt-20 pb-12 max-w-7xl mx-auto px-4 w-full flex-grow fade-in';
            app.innerHTML = `
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="relative w-full aspect-video bg-black rounded-2xl overflow-hidden shadow-2xl border border-gray-800">
                            ${anime.trailer?.embed_url 
                                ? `<iframe src="${anime.trailer.embed_url}?autoplay=1" class="w-full h-full border-0" allowfullscreen></iframe>`
                                : `<video controls class="w-full h-full"><source src="https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4" type="video/mp4"></video>`}
                        </div>
                        <div class="bg-brand-card p-6 rounded-2xl border border-gray-800 space-y-3">
                            <h1 class="text-2xl font-black">${anime.title}</h1>
                            <p class="text-xs text-gray-400">${anime.synopsis || ''}</p>
                        </div>
                    </div>
                    <div class="bg-brand-card p-5 rounded-2xl border border-gray-800 h-fit">
                        <h3 class="text-md font-bold mb-4">Episodes</h3>
                        <div class="grid grid-cols-4 gap-2 max-h-[400px] overflow-y-auto">
                            ${Array.from({length: Math.min(totalEps, 100)}, (_, i) => i + 1).map(ep => `
                                <button onclick="selectEpisode(${ep})" class="p-2 rounded-lg text-xs font-bold border ${ep == state.selectedEpisode ? 'bg-brand-accent text-black border-brand-accent' : 'bg-brand-panel text-gray-300 border-gray-800'}">${ep}</button>
                            `).join('')}
                        </div>
                    </div>
                </div>`;
        }

        function selectEpisode(ep) { state.selectedEpisode = ep; renderWatch(); }
        function createAnimeCard(anime) {
            return `
                <div onclick="navigate('watch', {anime: ${JSON.stringify(anime).replace(/"/g, '&quot;')}})" class="bg-brand-card rounded-xl overflow-hidden border border-gray-800 hover:border-brand-accent/50 transition cursor-pointer flex flex-col">
                    <div class="relative aspect-[3/4] bg-brand-panel">
                        <img src="${anime.images?.jpg?.image_url}" class="w-full h-full object-cover">
                    </div>
                    <div class="p-3">
                        <h3 class="text-xs font-bold truncate">${anime.title}</h3>
                    </div>
                </div>`;
        }

        document.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>
