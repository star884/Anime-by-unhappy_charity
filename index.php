<?php
// TRIANIME - Single File Anime SPA Clone optimized for Render Deployment
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRIANIME - Watch Anime Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        darkBg: '#0a0a0a',
                        cardBg: '#121212',
                        hoverBg: '#1e1e1e',
                        accent: '#ffbade',
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

        .glass-nav {
            background: rgba(18, 18, 18, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 186, 222, 0.15);
        }

        .fade-in {
            animation: fadeIn 0.35s ease-in-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-darkBg text-gray-200 min-h-screen font-sans flex flex-col antialiased">

    <nav class="glass-nav fixed top-0 left-0 right-0 z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3 cursor-pointer" onclick="router.navigate('home')">
                    <span class="text-2xl font-black tracking-wider text-accent drop-shadow">TRI<span class="text-white">ANIME</span></span>
                </div>

                <div class="hidden md:flex items-center space-x-6 text-sm font-semibold">
                    <button onclick="router.navigate('home')" class="hover:text-accent transition">Home</button>
                    <button onclick="router.navigate('browse')" class="hover:text-accent transition">Browse</button>
                    <button onclick="router.navigate('browse', { filter: 'bypopularity' })" class="hover:text-accent transition">Top Rated</button>
                </div>

                <div class="hidden md:flex items-center relative w-64">
                    <input type="text" id="desktopSearchInput" placeholder="Search anime..." 
                           onkeydown="if(event.key==='Enter') handleSearch(this.value)"
                           class="w-full bg-cardBg border border-gray-800 rounded-full py-1.5 pl-4 pr-10 text-sm focus:outline-none focus:border-accent text-gray-200 transition">
                    <i class="fa-solid fa-magnifying-glass absolute right-3 text-gray-400 cursor-pointer" onclick="handleSearch(document.getElementById('desktopSearchInput').value)"></i>
                </div>

                <div class="md:hidden flex items-center">
                    <button id="mobileMenuBtn" class="text-gray-300 focus:outline-none text-xl p-2">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>

        <div id="mobileMenu" class="hidden md:hidden bg-cardBg border-b border-gray-800 px-4 pt-2 pb-4 space-y-3">
            <div class="relative w-full my-2">
                <input type="text" id="mobileSearchInput" placeholder="Search anime..." 
                       onkeydown="if(event.key==='Enter') handleSearch(this.value)"
                       class="w-full bg-hoverBg border border-gray-700 rounded-full py-1.5 pl-4 pr-10 text-sm focus:outline-none focus:border-accent text-gray-200">
                <i class="fa-solid fa-magnifying-glass absolute right-3 top-2.5 text-gray-400" onclick="handleSearch(document.getElementById('mobileSearchInput').value)"></i>
            </div>
            <button onclick="router.navigate('home'); toggleMobileMenu();" class="block w-full text-left py-2 text-sm font-semibold hover:text-accent">Home</button>
            <button onclick="router.navigate('browse'); toggleMobileMenu();" class="block w-full text-left py-2 text-sm font-semibold hover:text-accent">Browse</button>
            <button onclick="router.navigate('browse', { filter: 'bypopularity' }); toggleMobileMenu();" class="block w-full text-left py-2 text-sm font-semibold hover:text-accent">Top Rated</button>
        </div>
    </nav>

    <main id="app" class="flex-grow pt-20 pb-12 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8">
    </main>

    <footer class="bg-cardBg border-t border-gray-900 py-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-center text-xs text-gray-500 space-y-2">
            <p><span class="text-accent font-bold">TRIANIME</span> &copy; 2026. Powered by Jikan API (v4).</p>
        </div>
    </footer>

    <script>
        const JIKAN_BASE_URL = 'https://api.jikan.moe/v4';
        
        const HARDCODED_GENRES = [
            { id: 1, name: 'Action' },
            { id: 2, name: 'Adventure' },
            { id: 4, name: 'Comedy' },
            { id: 8, name: 'Drama' },
            { id: 10, name: 'Fantasy' },
            { id: 14, name: 'Horror' },
            { id: 22, name: 'Romance' },
            { id: 24, name: 'Sci-Fi' },
            { id: 36, name: 'Slice of Life' },
            { id: 37, name: 'Supernatural' },
            { id: 62, name: 'Isekai' }
        ];

        const state = {
            currentView: 'home',
            params: {},
            heroItems: [],
            heroIndex: 0,
            heroInterval: null
        };

        const router = {
            navigate(view, params = {}) {
                state.currentView = view;
                state.params = params;
                clearInterval(state.heroInterval);
                window.scrollTo({ top: 0, behavior: 'smooth' });
                render();
            }
        };

        const delay = (ms) => new Promise(resolve => setTimeout(resolve, ms));

        async function fetchAPI(endpoint) {
            try {
                const res = await fetch(`${JIKAN_BASE_URL}${endpoint}`);
                if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
                const json = await res.json();
                return json.data;
            } catch (err) {
                console.error(`API Fetch Error [${endpoint}]:`, err);
                return null;
            }
        }

        async function render() {
            const app = document.getElementById('app');
            app.innerHTML = `<div class="flex justify-center items-center h-64"><i class="fa-solid fa-circle-notch fa-spin text-4xl text-accent"></i></div>`;

            switch (state.currentView) {
                case 'home':
                    await renderHome(app);
                    break;
                case 'browse':
                    await renderBrowse(app);
                    break;
                case 'watch':
                    await renderWatch(app);
                    break;
                case 'search':
                    await renderSearch(app);
                    break;
                default:
                    await renderHome(app);
            }
        }

        async function renderHome(container) {
            let airing = await fetchAPI('/seasons/now?limit=8');
            if (!airing || airing.length === 0) {
                await delay(1000);
                airing = await fetchAPI('/top/anime?filter=airing&page=2&limit=8');
            }

            await delay(600);
            const upcoming = await fetchAPI('/seasons/upcoming?limit=6');

            state.heroItems = airing ? airing.slice(0, 5) : [];

            container.innerHTML = `
                <div class="space-y-8 fade-in">
                    <div id="heroContainer" class="relative w-full h-[360px] sm:h-[420px] rounded-2xl overflow-hidden bg-cardBg border border-gray-800 shadow-2xl">
                        ${renderHeroSlide()}
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                        <div class="lg:col-span-3 space-y-6">
                            <div class="flex items-center justify-between border-b border-gray-800 pb-2">
                                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                                    <i class="fa-solid fa-fire text-accent"></i> Latest Airing
                                </h2>
                                <button onclick="router.navigate('browse')" class="text-xs text-accent hover:underline">View All</button>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                ${(airing || []).map(anime => createAnimeCard(anime)).join('')}
                            </div>
                        </div>

                        <div class="space-y-8">
                            <div class="bg-cardBg border border-gray-800 rounded-xl p-4">
                                <h3 class="text-base font-bold text-white mb-3 border-b border-gray-800 pb-2">Genres</h3>
                                <div class="flex flex-wrap gap-2">
                                    ${HARDCODED_GENRES.map(g => `
                                        <button onclick="router.navigate('browse', { genre: ${g.id}, genreName: '${g.name}' })" 
                                                class="text-xs bg-hoverBg hover:bg-accent hover:text-black transition px-2.5 py-1 rounded-md text-gray-300">
                                            ${g.name}
                                        </button>
                                    `).join('')}
                                </div>
                            </div>

                            <div class="bg-cardBg border border-gray-800 rounded-xl p-4">
                                <h3 class="text-base font-bold text-white mb-3 border-b border-gray-800 pb-2 flex items-center gap-2">
                                    <i class="fa-regular fa-calendar text-accent"></i> Top Upcoming
                                </h3>
                                <div class="space-y-3">
                                    ${(upcoming || []).map(anime => `
                                        <div onclick="router.navigate('watch', { id: ${anime.mal_id} })" 
                                             class="flex items-center space-x-3 cursor-pointer group">
                                            <img src="${anime.images?.jpg?.small_image_url}" class="w-12 h-16 object-cover rounded shadow" alt="${anime.title}">
                                            <div class="overflow-hidden">
                                                <h4 class="text-xs font-semibold text-gray-200 group-hover:text-accent truncate">${anime.title}</h4>
                                                <p class="text-[10px] text-gray-500 mt-1">${anime.type || 'TV'} &bull; ${anime.members ? anime.members.toLocaleString() : 'N/A'} Members</p>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            startHeroRotation();
        }

        function renderHeroSlide() {
            if (!state.heroItems || state.heroItems.length === 0) {
                return `<div class="p-8 text-center text-gray-500">No Content Available</div>`;
            }
            const current = state.heroItems[state.heroIndex];
            const bgImg = current.images?.jpg?.large_image_url || '';
            const title = current.title_english || current.title;
            const synopsis = current.synopsis ? current.synopsis.slice(0, 180) + '...' : 'No synopsis available.';

            return `
                <div class="absolute inset-0 bg-cover bg-center transition-all duration-700" style="background-image: url('${bgImg}');">
                    <div class="absolute inset-0 bg-gradient-to-t from-darkBg via-darkBg/70 to-transparent flex flex-col justify-end p-6 sm:p-10">
                        <span class="text-accent text-xs font-bold tracking-widest uppercase mb-1"># Spotlight Trending</span>
                        <h1 class="text-2xl sm:text-4xl font-extrabold text-white mb-2 line-clamp-1 drop-shadow-md">${title}</h1>
                        <p class="text-xs sm:text-sm text-gray-300 max-w-2xl mb-4 line-clamp-2">${synopsis}</p>
                        <div class="flex items-center space-x-4">
                            <button onclick="router.navigate('watch', { id: ${current.mal_id} })" 
                                    class="bg-accent text-black font-bold text-xs sm:text-sm px-5 py-2.5 rounded-full hover:bg-white transition flex items-center gap-2 shadow-lg">
                                <i class="fa-solid fa-play"></i> Watch Now
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        function startHeroRotation() {
            if (state.heroItems.length <= 1) return;
            state.heroInterval = setInterval(() => {
                state.heroIndex = (state.heroIndex + 1) % state.heroItems.length;
                const container = document.getElementById('heroContainer');
                if (container) container.innerHTML = renderHeroSlide();
            }, 6000);
        }

        async function renderBrowse(container) {
            const page = state.params.page || 1;
            const filter = state.params.filter || '';
            const genre = state.params.genre || '';
            const genreName = state.params.genreName || '';

            let queryParams = `?page=${page}&limit=16`;
            if (filter) queryParams += `&filter=${filter}`;
            if (genre) queryParams += `&genres=${genre}`;

            const data = await fetchAPI(`/top/anime${queryParams}`);
            const animeList = data || [];

            container.innerHTML = `
                <div class="space-y-6 fade-in">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-cardBg border border-gray-800 p-4 rounded-xl">
                        <div>
                            <h1 class="text-xl font-bold text-white">
                                Browse Library ${genreName ? `<span class="text-accent">- ${genreName}</span>` : ''}
                            </h1>
                            <p class="text-xs text-gray-400">Discover and explore anime titles</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button onclick="router.navigate('browse')" class="text-xs px-3 py-1.5 rounded-lg border ${!filter && !genre ? 'bg-accent text-black font-bold border-accent' : 'border-gray-700 text-gray-300 hover:bg-hoverBg'}">All</button>
                            <button onclick="router.navigate('browse', { filter: 'bypopularity' })" class="text-xs px-3 py-1.5 rounded-lg border ${filter === 'bypopularity' ? 'bg-accent text-black font-bold border-accent' : 'border-gray-700 text-gray-300 hover:bg-hoverBg'}">Top Rated</button>
                            <button onclick="router.navigate('browse', { filter: 'favorite' })" class="text-xs px-3 py-1.5 rounded-lg border ${filter === 'favorite' ? 'bg-accent text-black font-bold border-accent' : 'border-gray-700 text-gray-300 hover:bg-hoverBg'}">Most Favorited</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        ${animeList.map(anime => createAnimeCard(anime)).join('')}
                    </div>

                    <div class="flex justify-center items-center space-x-4 pt-6">
                        <button onclick="router.navigate('browse', { page: ${Math.max(1, page - 1)}, filter: '${filter}', genre: '${genre}', genreName: '${genreName}' })" 
                                ${page <= 1 ? 'disabled class="opacity-50 cursor-not-allowed text-xs px-4 py-2 bg-cardBg border border-gray-800 rounded-lg"' : 'class="text-xs px-4 py-2 bg-cardBg hover:bg-hoverBg border border-gray-800 rounded-lg text-accent font-semibold transition"'}>
                            <i class="fa-solid fa-chevron-left mr-1"></i> Prev
                        </button>
                        <span class="text-xs text-gray-400">Page <strong class="text-white">${page}</strong></span>
                        <button onclick="router.navigate('browse', { page: ${page + 1}, filter: '${filter}', genre: '${genre}', genreName: '${genreName}' })" 
                                class="text-xs px-4 py-2 bg-cardBg hover:bg-hoverBg border border-gray-800 rounded-lg text-accent font-semibold transition">
                            Next <i class="fa-solid fa-chevron-right ml-1"></i>
                        </button>
                    </div>
                </div>
            `;
        }

        async function renderSearch(container) {
            const query = state.params.q || '';
            const results = query ? await fetchAPI(`/anime?q=${encodeURIComponent(query)}&limit=16`) : [];

            container.innerHTML = `
                <div class="space-y-6 fade-in">
                    <div class="bg-cardBg border border-gray-800 p-4 rounded-xl">
                        <h1 class="text-xl font-bold text-white">Search Results for: <span class="text-accent">"${query}"</span></h1>
                        <p class="text-xs text-gray-400">${results ? results.length : 0} results found</p>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        ${(results || []).map(anime => createAnimeCard(anime)).join('')}
                    </div>
                </div>
            `;
        }

        async function renderWatch(container) {
            const id = state.params.id;
            const episode = state.params.episode || 1;

            if (!id) {
                router.navigate('home');
                return;
            }

            const anime = await fetchAPI(`/anime/${id}/full`);
            if (!anime) {
                container.innerHTML = `<div class="text-center py-12 text-red-400">Failed to load anime details.</div>`;
                return;
            }

            const totalEpisodes = anime.episodes || 12;
            const trailerUrl = anime.trailer?.embed_url;

            container.innerHTML = `
                <div class="space-y-6 fade-in">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-4">
                            <div class="relative w-full aspect-video bg-black rounded-2xl overflow-hidden border border-gray-800 shadow-2xl">
                                ${trailerUrl 
                                    ? `<iframe src="${trailerUrl}?autoplay=0" class="w-full h-full border-0" allowfullscreen></iframe>`
                                    : `<video controls class="w-full h-full"><source src="https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4" type="video/mp4">Your browser does not support video.</video>`
                                }
                            </div>
                            <div class="bg-cardBg border border-gray-800 p-4 rounded-xl flex items-center justify-between">
                                <div>
                                    <span class="text-xs text-accent font-semibold uppercase tracking-wider">Now Playing</span>
                                    <h2 class="text-lg font-bold text-white">Episode ${episode}</h2>
                                </div>
                                <div class="text-xs text-gray-400">
                                    <i class="fa-solid fa-star text-yellow-400 mr-1"></i> ${anime.score || 'N/A'}
                                </div>
                            </div>
                        </div>

                        <div class="bg-cardBg border border-gray-800 rounded-xl p-4 flex flex-col h-[400px] lg:h-auto">
                            <h3 class="text-sm font-bold text-white mb-3 border-b border-gray-800 pb-2 flex items-center justify-between">
                                <span>Episodes</span>
                                <span class="text-xs text-gray-400">${totalEpisodes} total</span>
                            </h3>
                            <div class="overflow-y-auto flex-grow grid grid-cols-4 sm:grid-cols-5 lg:grid-cols-3 gap-2 pr-1">
                                ${Array.from({ length: Math.min(totalEpisodes, 100) }, (_, i) => i + 1).map(ep => `
                                    <button onclick="router.navigate('watch', { id: ${id}, episode: ${ep} })" 
                                            class="py-2 text-xs font-semibold rounded-lg border transition ${parseInt(episode) === ep ? 'bg-accent text-black border-accent' : 'bg-hoverBg border-gray-800 text-gray-300 hover:border-accent'}">
                                        EP ${ep}
                                    </button>
                                `).join('')}
                            </div>
                        </div>
                    </div>

                    <div class="bg-cardBg border border-gray-800 rounded-xl p-6 space-y-4">
                        <div class="flex flex-col md:flex-row gap-6">
                            <img src="${anime.images?.jpg?.large_image_url}" class="w-40 h-56 object-cover rounded-lg shadow-lg mx-auto md:mx-0" alt="${anime.title}">
                            <div class="space-y-3 flex-grow">
                                <h1 class="text-2xl font-black text-white">${anime.title}</h1>
                                <div class="flex flex-wrap gap-2 text-xs">
                                    <span class="bg-hoverBg px-2.5 py-1 rounded border border-gray-700 text-accent font-semibold">${anime.type || 'TV'}</span>
                                    <span class="bg-hoverBg px-2.5 py-1 rounded border border-gray-700 text-gray-300">${anime.status || 'Finished'}</span>
                                    <span class="bg-hoverBg px-2.5 py-1 rounded border border-gray-700 text-gray-300">${anime.rating || 'PG-13'}</span>
                                    <span class="bg-hoverBg px-2.5 py-1 rounded border border-gray-700 text-yellow-400"><i class="fa-solid fa-star"></i> ${anime.score || 'N/A'}</span>
                                </div>
                                <p class="text-xs text-gray-300 leading-relaxed">${anime.synopsis || 'No synopsis available.'}</p>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 pt-2 text-xs text-gray-400 border-t border-gray-800">
                                    <div><strong class="text-gray-200">Studios:</strong> ${anime.studios?.map(s => s.name).join(', ') || 'N/A'}</div>
                                    <div><strong class="text-gray-200">Aired:</strong> ${anime.aired?.string || 'N/A'}</div>
                                    <div><strong class="text-gray-200">Duration:</strong> ${anime.duration || 'N/A'}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function createAnimeCard(anime) {
            const title = anime.title_english || anime.title;
            const img = anime.images?.jpg?.large_image_url || anime.images?.jpg?.image_url;
            const score = anime.score || 'N/A';
            const type = anime.type || 'TV';

            return `
                <div onclick="router.navigate('watch', { id: ${anime.mal_id} })" 
                     class="bg-cardBg border border-gray-800 rounded-xl overflow-hidden cursor-pointer group hover:border-accent transition duration-300 flex flex-col">
                    <div class="relative aspect-[3/4] overflow-hidden bg-hoverBg">
                        <img src="${img}" alt="${title}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                        <div class="absolute top-2 left-2 bg-black/70 backdrop-blur px-2 py-0.5 rounded text-[10px] font-bold text-accent">
                            ${type}
                        </div>
                        <div class="absolute top-2 right-2 bg-black/70 backdrop-blur px-2 py-0.5 rounded text-[10px] font-bold text-yellow-400 flex items-center gap-1">
                            <i class="fa-solid fa-star"></i> ${score}
                        </div>
                    </div>
                    <div class="p-3 flex flex-col justify-between flex-grow">
                        <h3 class="text-xs font-bold text-gray-200 group-hover:text-accent line-clamp-2 transition">${title}</h3>
                    </div>
                </div>
            `;
        }

        function handleSearch(query) {
            if (!query.trim()) return;
            router.navigate('search', { q: query.trim() });
        }

        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }

        document.getElementById('mobileMenuBtn').addEventListener('click', toggleMobileMenu);

        window.addEventListener('DOMContentLoaded', () => {
            router.navigate('home');
        });
    </script>
</body>
</html>
