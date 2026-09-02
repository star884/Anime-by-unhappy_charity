<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRIANIME - Anime Streaming</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        darkBg: '#0a0a0a',
                        cardBg: '#121212',
                        hoverBg: '#1e1e1e',
                        accentPink: '#ffbade',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0a0a0a; }
        ::-webkit-scrollbar-thumb { background: #1e1e1e; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #ffbade; }
        .fade-in { animation: fadeIn 0.4s ease-in-out; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .glass-nav {
            background: rgba(18, 18, 18, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body class="bg-darkBg text-gray-200 font-sans min-h-screen flex flex-col justify-between selection:bg-accentPink selection:text-black">

    <nav class="glass-nav fixed top-0 w-full z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3 cursor-pointer" onclick="navigateTo('home')">
                    <span class="text-2xl font-black tracking-wider text-accentPink">TRI<span class="text-white">ANIME</span></span>
                </div>

                <div class="hidden md:flex items-center space-x-8">
                    <button onclick="navigateTo('home')" class="hover:text-accentPink font-medium transition">Home</button>
                    <button onclick="navigateTo('browse')" class="hover:text-accentPink font-medium transition">Browse All</button>
                    <button onclick="navigateTo('library')" class="hover:text-accentPink font-medium transition">My Library</button>
                </div>

                <div class="hidden md:flex items-center relative w-64">
                    <input type="text" id="searchInput" onkeydown="handleSearch(event)" placeholder="Search anime..." class="w-full bg-hoverBg text-sm text-white rounded-full py-2 pl-4 pr-10 focus:outline-none focus:ring-2 focus:ring-accentPink border border-gray-800 transition">
                    <button onclick="triggerSearch()" class="absolute right-3 text-gray-400 hover:text-accentPink">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>

                <div class="md:hidden flex items-center space-x-4">
                    <button id="mobileMenuBtn" class="text-gray-300 hover:text-white focus:outline-none">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <div id="mobileMenu" class="hidden md:hidden bg-cardBg border-b border-gray-800 px-4 pt-2 pb-4 space-y-3">
            <button onclick="navigateTo('home'); toggleMobileMenu()" class="block w-full text-left py-2 hover:text-accentPink font-medium">Home</button>
            <button onclick="navigateTo('browse'); toggleMobileMenu()" class="block w-full text-left py-2 hover:text-accentPink font-medium">Browse All</button>
            <button onclick="navigateTo('library'); toggleMobileMenu()" class="block w-full text-left py-2 hover:text-accentPink font-medium">My Library</button>
            <div class="relative w-full pt-2">
                <input type="text" id="mobileSearchInput" onkeydown="handleSearch(event, true)" placeholder="Search anime..." class="w-full bg-hoverBg text-sm text-white rounded-full py-2 pl-4 pr-10 focus:outline-none focus:ring-2 focus:ring-accentPink border border-gray-800">
                <button onclick="triggerSearch(true)" class="absolute right-3 top-4 text-gray-400 hover:text-accentPink">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </div>
        </div>
    </nav>

    <main id="app" class="pt-20 pb-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto w-full flex-grow">
    </main>

    <footer class="bg-cardBg border-t border-gray-800/60 py-6 text-center text-xs text-gray-500">
        <p>&copy; 2026 TRIANIME. Powered by GitHub Actions Background Sync Engine.</p>
    </footer>

    <script>
        // --- CONSTANTS & APIS ---
        const JIKAN_BASE = 'https://api.jikan.moe/v4';
        const EMBED_PROVIDERS = [
            { name: 'Server 1', url: (id, ep) => `https://vidsrc.to/embed/anime/${id}/${ep}` },
            { name: 'Server 2', url: (id, ep) => `https://2embed.org/embed/anime/${id}/${ep}` }
        ];

        const HARDCODED_GENRES = [
            { id: 1, name: 'Action' }, { id: 2, name: 'Adventure' },
            { id: 4, name: 'Comedy' }, { id: 8, name: 'Drama' },
            { id: 10, name: 'Fantasy' }, { id: 62, name: 'Isekai' },
            { id: 22, name: 'Romance' }, { id: 24, name: 'Sci-Fi' },
            { id: 36, name: 'Slice of Life' }, { id: 37, name: 'Supernatural' }
        ];

        // --- STATE MANAGEMENT ---
        const state = {
            view: 'home',
            heroAnime: [],
            heroIndex: 0,
            heroTimer: null,
            latestAiring: [],
            upcoming: [],
            browseData: [],
            browsePage: 1,
            browseFilter: { type: 'top', genre: null },
            isLoadingMore: false,
            hasMorePages: true,
            watchAnime: null,
            selectedEpisode: 1,
            selectedServer: 0,
            searchResults: [],
            searchQuery: '',
            favorites: JSON.parse(localStorage.getItem('tri_favs') || '[]'),
            history: JSON.parse(localStorage.getItem('tri_history') || '[]')
        };

        const delay = (ms) => new Promise(resolve => setTimeout(resolve, ms));

        async function fetchAPI(endpoint) {
            try {
                const res = await fetch(`${JIKAN_BASE}${endpoint}`);
                if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);
                const json = await res.json();
                return json.data;
            } catch (err) {
                console.error(`Fetch failed for ${endpoint}:`, err);
                return null;
            }
        }

        // --- INITIALIZATION VIA GITHUB-SYNCED DATA ---
        async function init() {
            renderLoading();

            try {
                // Read pre-populated background JSON generated by GitHub Actions
                const jsonRes = await fetch('./data.json?t=' + Date.now());
                if (!jsonRes.ok) throw new Error('data.json not found');
                const localData = await jsonRes.json();

                if (localData && localData.data && localData.data.length > 0) {
                    // Map background JSON entries into runtime structure
                    const normalized = localData.data.map(item => ({
                        mal_id: item.id,
                        title: item.title,
                        images: { jpg: { large_image_url: item.image, image_url: item.image } },
                        score: item.score,
                        type: item.type,
                        synopsis: item.synopsis || 'Latest auto-synced release.'
                    }));

                    state.heroAnime = normalized.slice(0, 5);
                    state.latestAiring = normalized;
                } else {
                    throw new Error('data.json is empty');
                }
            } catch (e) {
                console.warn('Background dataset unavailable, using live fallback APIs:', e.message);
                let topAiring = await fetchAPI('/top/anime?filter=airing&limit=5');
                await delay(800);
                if (!topAiring || topAiring.length === 0) {
                    topAiring = await fetchAPI('/top/anime?page=2');
                    await delay(800);
                }
                state.heroAnime = topAiring || [];

                const latest = await fetchAPI('/seasons/now?limit=24');
                state.latestAiring = latest || state.heroAnime;
            }

            const upcoming = await fetchAPI('/seasons/upcoming?limit=6');
            state.upcoming = upcoming || [];

            setupInfiniteScroll();
            navigateTo('home');
        }

        // --- ROUTER ---
        function navigateTo(view, params = {}) {
            state.view = view;
            clearInterval(state.heroTimer);

            if (view === 'home') renderHome();
            else if (view === 'browse') resetAndLoadBrowse(params.filter || state.browseFilter);
            else if (view === 'watch') loadWatchPage(params.id);
            else if (view === 'search') loadSearchResults(params.query);
            else if (view === 'library') renderLibrary();

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function renderLoading() {
            document.getElementById('app').innerHTML = `
                <div class="flex flex-col items-center justify-center min-h-[60vh] space-y-4">
                    <div class="w-12 h-12 border-4 border-accentPink border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-gray-400 font-medium tracking-wide animate-pulse">Loading TRIANIME Library...</p>
                </div>
            `;
        }

        // --- HOME VIEW ---
        function renderHome() {
            const app = document.getElementById('app');
            app.innerHTML = `
                <div class="fade-in space-y-12">
                    <div id="heroSection" class="relative w-full h-[400px] md:h-[480px] rounded-2xl overflow-hidden shadow-2xl bg-hoverBg border border-gray-800"></div>

                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                        <div class="lg:col-span-3 space-y-6">
                            <div class="flex justify-between items-center border-l-4 border-accentPink pl-3">
                                <h2 class="text-xl font-bold tracking-wide">Currently Airing Season</h2>
                                <button onclick="navigateTo('browse')" class="text-xs text-accentPink hover:underline">View Entire Catalog</button>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                ${state.latestAiring.map(anime => renderAnimeCard(anime)).join('')}
                            </div>
                        </div>

                        <div class="space-y-8">
                            <div class="bg-cardBg p-5 rounded-xl border border-gray-800/80">
                                <h3 class="text-md font-bold mb-4 border-l-4 border-accentPink pl-3">Genres</h3>
                                <div class="flex flex-wrap gap-2">
                                    ${HARDCODED_GENRES.map(g => `
                                        <button onclick="navigateTo('browse', { filter: { genre: ${g.id} } })" 
                                                class="text-xs px-3 py-1.5 rounded-md bg-hoverBg border border-gray-800 hover:border-accentPink hover:text-accentPink transition">
                                            ${g.name}
                                        </button>
                                    `).join('')}
                                </div>
                            </div>

                            <div class="bg-cardBg p-5 rounded-xl border border-gray-800/80">
                                <h3 class="text-md font-bold mb-4 border-l-4 border-accentPink pl-3">Top Upcoming</h3>
                                <div class="space-y-4">
                                    ${state.upcoming.map(anime => `
                                        <div onclick="navigateTo('watch', { id: ${anime.mal_id} })" class="flex items-center space-x-3 cursor-pointer group">
                                            <img src="${anime.images.jpg.image_url}" class="w-12 h-16 object-cover rounded-md flex-shrink-0 group-hover:opacity-80 transition">
                                            <div class="overflow-hidden">
                                                <h4 class="text-sm font-semibold truncate group-hover:text-accentPink transition">${anime.title}</h4>
                                                <p class="text-xs text-gray-400 mt-1">${anime.type || 'TV'} • ${anime.episodes ? anime.episodes + ' eps' : 'TBA'}</p>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            setupHeroSlider();
        }

        function setupHeroSlider() {
            if (!state.heroAnime.length) return;
            const updateHero = () => {
                const anime = state.heroAnime[state.heroIndex];
                const heroEl = document.getElementById('heroSection');
                if (!heroEl) return;

                heroEl.innerHTML = `
                    <img src="${anime.images.jpg.large_image_url}" class="absolute inset-0 w-full h-full object-cover opacity-30 blur-sm scale-105 transition-all duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-darkBg via-darkBg/60 to-transparent"></div>
                    <div class="absolute bottom-0 p-6 md:p-10 max-w-2xl space-y-3 z-10 fade-in">
                        <span class="bg-accentPink text-black text-xs px-2.5 py-1 rounded-full font-bold uppercase tracking-wider">Top Airing</span>
                        <h1 class="text-2xl md:text-4xl font-extrabold text-white line-clamp-1">${anime.title}</h1>
                        <p class="text-xs md:text-sm text-gray-300 line-clamp-2 md:line-clamp-3">${anime.synopsis || 'No description available.'}</p>
                        <div class="pt-2">
                            <button onclick="navigateTo('watch', { id: ${anime.mal_id} })" class="bg-accentPink hover:bg-white text-black font-bold px-6 py-2.5 rounded-full text-sm transition flex items-center space-x-2">
                                <i class="fa-solid fa-play"></i>
                                <span>Watch Now</span>
                            </button>
                        </div>
                    </div>
                `;
                state.heroIndex = (state.heroIndex + 1) % state.heroAnime.length;
            };
            updateHero();
            state.heroTimer = setInterval(updateHero, 5000);
        }

        // --- BROWSE VIEW WITH AUTO-POPULATING INFINITE SCROLL ---
        async function resetAndLoadBrowse(filter = { type: 'top', genre: null }) {
            state.browsePage = 1;
            state.browseData = [];
            state.browseFilter = filter;
            state.hasMorePages = true;

            renderBrowseShell();
            await fetchNextBrowseBatch();
        }

        function renderBrowseShell() {
            const filter = state.browseFilter;
            const app = document.getElementById('app');
            app.innerHTML = `
                <div class="fade-in space-y-6">
                    <div class="flex flex-wrap items-center justify-between gap-4 bg-cardBg p-4 rounded-xl border border-gray-800">
                        <h2 class="text-xl font-bold border-l-4 border-accentPink pl-3">Complete Library</h2>
                        <div class="flex flex-wrap gap-2">
                            <button onclick="resetAndLoadBrowse({ type: 'top', genre: null })" class="px-3 py-1.5 rounded-lg text-xs font-semibold ${!filter.genre && filter.type==='top' ? 'bg-accentPink text-black' : 'bg-hoverBg hover:text-accentPink'}">Top Rated</button>
                            <button onclick="resetAndLoadBrowse({ type: 'movie', genre: null })" class="px-3 py-1.5 rounded-lg text-xs font-semibold ${filter.type==='movie' ? 'bg-accentPink text-black' : 'bg-hoverBg hover:text-accentPink'}">Movies</button>
                            ${HARDCODED_GENRES.map(g => `
                                <button onclick="resetAndLoadBrowse({ type: 'genre', genre: ${g.id} })" class="px-3 py-1.5 rounded-lg text-xs font-semibold ${filter.genre === g.id ? 'bg-accentPink text-black' : 'bg-hoverBg hover:text-accentPink'}">${g.name}</button>
                            `).join('')}
                        </div>
                    </div>

                    <div id="browseGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    </div>

                    <div id="scrollLoader" class="flex justify-center py-8">
                        <div class="w-8 h-8 border-4 border-accentPink border-t-transparent rounded-full animate-spin"></div>
                    </div>
                </div>
            `;
        }

        async function fetchNextBrowseBatch() {
            if (state.isLoadingMore || !state.hasMorePages) return;
            state.isLoadingMore = true;

            const filter = state.browseFilter;
            let endpoint = `/top/anime?page=${state.browsePage}`;
            if (filter.genre) {
                endpoint = `/anime?genres=${filter.genre}&page=${state.browsePage}&order_by=score&sort=desc`;
            } else if (filter.type === 'movie') {
                endpoint = `/top/anime?type=movie&page=${state.browsePage}`;
            }

            const data = await fetchAPI(endpoint);
            if (data && data.length > 0) {
                state.browseData.push(...data);
                appendAnimeCards(data);
                state.browsePage++;
            } else {
                state.hasMorePages = false;
                const loader = document.getElementById('scrollLoader');
                if (loader) loader.innerHTML = `<p class="text-xs text-gray-500">End of catalog reached.</p>`;
            }

            state.isLoadingMore = false;
        }

        function appendAnimeCards(items) {
            const grid = document.getElementById('browseGrid');
            if (!grid) return;
            items.forEach(anime => {
                grid.insertAdjacentHTML('beforeend', renderAnimeCard(anime));
            });
        }

        function setupInfiniteScroll() {
            window.addEventListener('scroll', () => {
                if (state.view !== 'browse') return;
                const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
                if (scrollTop + clientHeight >= scrollHeight - 600) {
                    fetchNextBrowseBatch();
                }
            });
        }

        // --- WATCH VIEW ---
        async function loadWatchPage(animeId) {
            renderLoading();
            const data = await fetchAPI(`/anime/${animeId}/full`);
            if (!data) {
                document.getElementById('app').innerHTML = `<div class="text-center py-12 text-red-400">Failed to load anime details.</div>`;
                return;
            }
            state.watchAnime = data;
            state.selectedEpisode = 1;
            state.selectedServer = 0;

            saveToHistory(data);
            renderWatchPage();
        }

        function renderWatchPage() {
            const anime = state.watchAnime;
            const totalEpisodes = anime.episodes || 12;
            const isFav = state.favorites.some(f => f.mal_id === anime.mal_id);

            let iframeSrc = EMBED_PROVIDERS[state.selectedServer]?.url(anime.mal_id, state.selectedEpisode) || anime.trailer?.embed_url;

            const app = document.getElementById('app');
            app.innerHTML = `
                <div class="fade-in space-y-8">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-4">
                            <div class="relative w-full aspect-video bg-black rounded-xl overflow-hidden border border-gray-800 shadow-xl">
                                <iframe src="${iframeSrc}" class="w-full h-full border-0" allowfullscreen allow="autoplay"></iframe>
                            </div>
                            
                            <div class="flex flex-wrap items-center justify-between gap-4 bg-cardBg p-4 rounded-xl border border-gray-800">
                                <div>
                                    <h1 class="text-xl font-bold text-white">${anime.title}</h1>
                                    <p class="text-xs text-accentPink mt-0.5">Playing Episode ${state.selectedEpisode}</p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs text-gray-400 font-semibold mr-1">Server:</span>
                                    ${EMBED_PROVIDERS.map((s, idx) => `
                                        <button onclick="changeServer(${idx})" class="text-xs px-3 py-1.5 rounded-lg border ${state.selectedServer === idx ? 'bg-accentPink text-black font-bold border-accentPink' : 'bg-hoverBg border-gray-800 text-gray-300 hover:text-accentPink'}">
                                            ${s.name}
                                        </button>
                                    `).join('')}
                                    <button onclick="toggleFavorite()" class="ml-2 text-sm px-3 py-1.5 rounded-lg border ${isFav ? 'bg-red-500 text-white border-red-500' : 'bg-hoverBg text-gray-300 border-gray-800'}">
                                        <i class="fa-${isFav ? 'solid' : 'regular'} fa-heart"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="bg-cardBg border border-gray-800 rounded-xl p-4 flex flex-col h-[420px] lg:h-auto">
                            <h3 class="text-md font-bold mb-3 border-l-4 border-accentPink pl-3">Episodes (${totalEpisodes})</h3>
                            <div class="overflow-y-auto flex-grow space-y-2 pr-1">
                                ${Array.from({ length: Math.min(totalEpisodes, 150) }, (_, i) => i + 1).map(ep => `
                                    <button onclick="changeEpisode(${ep})" class="w-full text-left px-3 py-2 rounded-lg text-sm flex items-center justify-between ${state.selectedEpisode === ep ? 'bg-accentPink text-black font-bold' : 'bg-hoverBg hover:text-accentPink transition'}">
                                        <span>Episode ${ep}</span>
                                        <i class="fa-solid fa-circle-play text-xs opacity-70"></i>
                                    </button>
                                `).join('')}
                            </div>
                        </div>
                    </div>

                    <div class="bg-cardBg border border-gray-800 rounded-xl p-6 grid grid-cols-1 md:grid-cols-4 gap-6">
                        <img src="${anime.images.jpg.large_image_url}" class="w-full rounded-lg object-cover shadow-md">
                        <div class="md:col-span-3 space-y-4">
                            <div class="flex flex-wrap gap-3 text-xs">
                                <span class="bg-hoverBg border border-gray-700 px-3 py-1 rounded-full"><i class="fa-solid fa-star text-yellow-400 mr-1"></i> ${anime.score || 'N/A'}</span>
                                <span class="bg-hoverBg border border-gray-700 px-3 py-1 rounded-full">Rank #${anime.rank || 'N/A'}</span>
                                <span class="bg-hoverBg border border-gray-700 px-3 py-1 rounded-full">${anime.type || 'TV'}</span>
                                <span class="bg-hoverBg border border-gray-700 px-3 py-1 rounded-full">${anime.status}</span>
                            </div>
                            <p class="text-sm text-gray-300 leading-relaxed">${anime.synopsis || 'No description available.'}</p>
                            <div class="grid grid-cols-2 text-xs text-gray-400 gap-2 border-t border-gray-800 pt-4">
                                <div><strong class="text-gray-200">Studios:</strong> ${anime.studios?.map(s => s.name).join(', ') || 'N/A'}</div>
                                <div><strong class="text-gray-200">Aired:</strong> ${anime.aired?.string || 'N/A'}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function changeEpisode(ep) {
            state.selectedEpisode = ep;
            renderWatchPage();
        }

        function changeServer(idx) {
            state.selectedServer = idx;
            renderWatchPage();
        }

        function toggleFavorite() {
            const anime = state.watchAnime;
            const index = state.favorites.findIndex(f => f.mal_id === anime.mal_id);
            if (index > -1) {
                state.favorites.splice(index, 1);
            } else {
                state.favorites.push({
                    mal_id: anime.mal_id,
                    title: anime.title,
                    images: anime.images,
                    score: anime.score,
                    type: anime.type
                });
            }
            localStorage.setItem('tri_favs', JSON.stringify(state.favorites));
            renderWatchPage();
        }

        function saveToHistory(anime) {
            const filtered = state.history.filter(h => h.mal_id !== anime.mal_id);
            filtered.unshift({
                mal_id: anime.mal_id,
                title: anime.title,
                images: anime.images,
                score: anime.score,
                type: anime.type,
                timestamp: new Date().toLocaleDateString()
            });
            state.history = filtered.slice(0, 24);
            localStorage.setItem('tri_history', JSON.stringify(state.history));
        }

        function renderLibrary() {
            const app = document.getElementById('app');
            app.innerHTML = `
                <div class="fade-in space-y-10">
                    <div class="space-y-4">
                        <h2 class="text-xl font-bold border-l-4 border-accentPink pl-3">My Favorites (${state.favorites.length})</h2>
                        ${state.favorites.length === 0 ? '<p class="text-gray-500 text-sm">No saved favorites yet.</p>' : ''}
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            ${state.favorites.map(anime => renderAnimeCard(anime)).join('')}
                        </div>
                    </div>

                    <div class="space-y-4 border-t border-gray-800 pt-8">
                        <h2 class="text-xl font-bold border-l-4 border-accentPink pl-3">Watch History</h2>
                        ${state.history.length === 0 ? '<p class="text-gray-500 text-sm">No watch history available.</p>' : ''}
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            ${state.history.map(anime => renderAnimeCard(anime)).join('')}
                        </div>
                    </div>
                </div>
            `;
        }

        async function loadSearchResults(query) {
            if (!query) return;
            renderLoading();
            state.searchQuery = query;

            const results = await fetchAPI(`/anime?q=${encodeURIComponent(query)}&limit=24`);
            state.searchResults = results || [];

            const app = document.getElementById('app');
            app.innerHTML = `
                <div class="fade-in space-y-6">
                    <h2 class="text-xl font-bold border-l-4 border-accentPink pl-3">Search Results for: <span class="text-accentPink">${query}</span></h2>
                    ${
                        state.searchResults.length === 0 
                        ? `<p class="text-gray-400 py-12 text-center">No anime found matching your query.</p>`
                        : `<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            ${state.searchResults.map(anime => renderAnimeCard(anime)).join('')}
                           </div>`
                    }
                </div>
            `;
        }

        function renderAnimeCard(anime) {
            return `
                <div onclick="navigateTo('watch', { id: ${anime.mal_id} })" class="group relative bg-cardBg rounded-xl overflow-hidden border border-gray-800/80 cursor-pointer hover:border-accentPink/50 transition duration-300 flex flex-col justify-between">
                    <div class="relative aspect-[3/4] overflow-hidden">
                        <img src="${anime.images?.jpg?.large_image_url || anime.images?.jpg?.image_url}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        <div class="absolute top-2 right-2 bg-black/70 backdrop-blur-md px-2 py-0.5 rounded text-[10px] font-bold text-yellow-400">
                            ★ ${anime.score || 'N/A'}
                        </div>
                    </div>
                    <div class="p-3">
                        <h3 class="text-xs font-bold text-white truncate group-hover:text-accentPink transition">${anime.title}</h3>
                        <p class="text-[10px] text-gray-400 mt-1">${anime.type || 'TV'}</p>
                    </div>
                </div>
            `;
        }

        function handleSearch(e, isMobile = false) {
            if (e.key === 'Enter') triggerSearch(isMobile);
        }

        function triggerSearch(isMobile = false) {
            const id = isMobile ? 'mobileSearchInput' : 'searchInput';
            const query = document.getElementById(id).value.trim();
            if (query) navigateTo('search', { query });
        }

        function toggleMobileMenu() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        }

        document.getElementById('mobileMenuBtn').addEventListener('click', toggleMobileMenu);
        window.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>
