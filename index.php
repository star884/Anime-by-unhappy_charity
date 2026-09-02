<?php
// TRIANIME - Fully Complete Automated Anime Web Portal powered by Jikan v4 API
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRIANIME - Auto Anime Database & Trailers</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        darkBg: '#0f0f12',
                        cardBg: '#18181c',
                        hoverBg: '#24242c',
                        accent: '#ffbade',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0f0f12; }
        ::-webkit-scrollbar-thumb { background: #24242c; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #ffbade; }
        .glass-nav {
            background: rgba(24, 24, 28, 0.9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 186, 222, 0.1);
        }
        .fade-in { animation: fadeIn 0.3s ease-in forwards; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-darkBg text-gray-200 min-h-screen font-sans flex flex-col antialiased">

    <nav class="glass-nav fixed top-0 left-0 right-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3 cursor-pointer" onclick="router.navigate('home')">
                    <span class="text-2xl font-black tracking-wider text-accent">TRI<span class="text-white">ANIME</span></span>
                </div>
                <div class="hidden md:flex items-center space-x-6 text-sm font-semibold">
                    <button onclick="router.navigate('home')" class="hover:text-accent transition">Home</button>
                    <button onclick="router.navigate('browse')" class="hover:text-accent transition">Browse All</button>
                    <button onclick="router.navigate('browse', { type: 'movie' })" class="hover:text-accent transition">Movies</button>
                    <button onclick="router.navigate('browse', { filter: 'bypopularity' })" class="hover:text-accent transition">Top 10 Popular</button>
                </div>
                <div class="hidden md:flex items-center relative w-64">
                    <input type="text" id="desktopSearchInput" placeholder="Search anime..." 
                           onkeydown="if(event.key==='Enter') handleSearch(this.value)"
                           class="w-full bg-cardBg border border-gray-800 rounded-full py-1.5 pl-4 pr-10 text-sm focus:outline-none focus:border-accent text-gray-200">
                    <i class="fa-solid fa-magnifying-glass absolute right-3 text-gray-400 cursor-pointer" onclick="handleSearch(document.getElementById('desktopSearchInput').value)"></i>
                </div>
                <div class="md:hidden flex items-center">
                    <button id="mobileMenuBtn" onclick="toggleMobileMenu()" class="text-gray-300 focus:outline-none text-xl p-2">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>

        <div id="mobileMenu" class="hidden md:hidden bg-cardBg border-b border-gray-800 px-4 pt-2 pb-4 space-y-3">
            <div class="relative w-full my-2">
                <input type="text" id="mobileSearchInput" placeholder="Search anime..." 
                       onkeydown="if(event.key==='Enter') { handleSearch(this.value); toggleMobileMenu(); }"
                       class="w-full bg-hoverBg border border-gray-700 rounded-full py-1.5 pl-4 pr-10 text-sm focus:outline-none focus:border-accent text-gray-200">
                <i class="fa-solid fa-magnifying-glass absolute right-3 top-2.5 text-gray-400" onclick="handleSearch(document.getElementById('mobileSearchInput').value); toggleMobileMenu();"></i>
            </div>
            <button onclick="router.navigate('home'); toggleMobileMenu();" class="block w-full text-left py-2 text-sm font-semibold hover:text-accent">Home</button>
            <button onclick="router.navigate('browse'); toggleMobileMenu();" class="block w-full text-left py-2 text-sm font-semibold hover:text-accent">Browse All</button>
            <button onclick="router.navigate('browse', { type: 'movie' }); toggleMobileMenu();" class="block w-full text-left py-2 text-sm font-semibold hover:text-accent">Movies</button>
            <button onclick="router.navigate('browse', { filter: 'bypopularity' }); toggleMobileMenu();" class="block w-full text-left py-2 text-sm font-semibold hover:text-accent">Top 10 Popular</button>
        </div>
    </nav>

    <div class="pt-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <div class="bg-cardBg border border-dashed border-gray-700 rounded-xl p-3 text-center text-xs text-gray-500">
            <i class="fa-solid fa-rectangle-ad mr-1 text-accent"></i> <span>Advertisement Space (Insert your AdSense / Monetization Banner Code here)</span>
        </div>
    </div>

    <main id="app" class="flex-grow pt-4 pb-12 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8">
    </main>

    <footer class="bg-cardBg border-t border-gray-900 py-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-center text-xs text-gray-500 space-y-2">
            <p><span class="text-accent font-bold">TRIANIME</span> &copy; 2026. Powered by Jikan (MyAnimeList Unofficial API).</p>
            <p class="text-[11px] text-gray-600 max-w-3xl mx-auto">
                Disclaimer: This site does not store or host video files on its server. All content, thumbnails, images, and trailers are legally fetched via third-party APIs (MyAnimeList / YouTube embeds).
            </p>
        </div>
    </footer>

    <script>
        const JIKAN_BASE_URL = 'https://api.jikan.moe/v4';
        
        const GENRES = [
            { id: 1, name: 'Action' },
            { id: 2, name: 'Adventure' },
            { id: 4, name: 'Comedy' },
            { id: 8, name: 'Drama' },
            { id: 10, name: 'Fantasy' },
            { id: 14, name: 'Horror' },
            { id: 7, name: 'Mystery' },
            { id: 22, name: 'Romance' },
            { id: 24, name: 'Sci-Fi' },
            { id: 36, name: 'Slice of Life' },
            { id: 37, name: 'Supernatural' },
            { id: 62, name: 'Isekai' }
        ];

        const state = {
            currentView: 'home',
            params: {},
            cache: {}
        };

        const router = {
            navigate(view, params = {}) {
                state.currentView = view;
                state.params = params;
                window.scrollTo({ top: 0, behavior: 'smooth' });
                render();
            }
        };

        const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

        async function fetchAPI(endpoint, isRetry = false) {
            if (state.cache[endpoint]) {
                return state.cache[endpoint];
            }
            try {
                const res = await fetch(`${JIKAN_BASE_URL}${endpoint}`);
                if (res.status === 429 && !isRetry) {
                    await sleep(1500);
                    return fetchAPI(endpoint, true);
                }
                if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);
                const json = await res.json();
                state.cache[endpoint] = json.data;
                return json.data;
            } catch (err) {
                console.warn(`API Error [${endpoint}]:`, err);
                return null;
            }
        }

        async function render() {
            const app = document.getElementById('app');
            app.innerHTML = `
                <div class="flex flex-col justify-center items-center h-64 space-y-3">
                    <i class="fa-solid fa-circle-notch fa-spin text-4xl text-accent"></i>
                    <p class="text-xs text-gray-500 animate-pulse">Fetching anime data...</p>
                </div>`;

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
            let airing = await fetchAPI('/top/anime?filter=airing&limit=12');
            await sleep(500);
            let topList = await fetchAPI('/top/anime?filter=bypopularity&limit=8');

            container.innerHTML = `
                <div class="space-y-8 fade-in">
                    <div class="bg-cardBg border border-gray-800 p-4 rounded-xl">
                        <h3 class="text-xs font-bold uppercase text-accent mb-3 tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-list-ul"></i> Quick Categories
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            ${GENRES.map(g => `
                                <button onclick="router.navigate('browse', { genre: ${g.id}, genreName: '${g.name}' })" 
                                        class="text-xs bg-hoverBg hover:bg-accent hover:text-black transition px-3 py-1.5 rounded-lg text-gray-300 font-semibold">
                                    ${g.name}
                                </button>
                            `).join('')}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                        <div class="lg:col-span-3 space-y-6">
                            <div class="flex items-center justify-between border-b border-gray-800 pb-2">
                                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                                    <i class="fa-solid fa-fire text-accent"></i> Latest Airing Anime
                                </h2>
                                <button onclick="router.navigate('browse')" class="text-xs text-accent hover:underline">View All</button>
                            </div>
                            ${airing && airing.length > 0 ? `
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                    ${airing.map(anime => createAnimeCard(anime)).join('')}
                                </div>
                            ` : `
                                <div class="text-center py-12 text-gray-500 text-sm">
                                    Unable to load currently airing anime right now. Please refresh or try again shortly.
                                </div>
                            `}
                        </div>

                        <div class="space-y-6">
                            <div class="bg-cardBg border border-gray-800 rounded-xl p-4">
                                <h3 class="text-base font-bold text-white mb-3 border-b border-gray-800 pb-2 flex items-center gap-2">
                                    <i class="fa-solid fa-trophy text-yellow-400"></i> Top Popular
                                </h3>
                                ${topList && topList.length > 0 ? `
                                    <div class="space-y-3">
                                        ${topList.map((anime, index) => `
                                            <div onclick="router.navigate('watch', { id: ${anime.mal_id} })" 
                                                 class="flex items-center space-x-3 cursor-pointer group p-1.5 rounded-lg hover:bg-hoverBg transition">
                                                <span class="text-sm font-black ${index < 3 ? 'text-accent' : 'text-gray-600'} w-4 text-center">${index + 1}</span>
                                                <img src="${anime.images?.jpg?.small_image_url || ''}" class="w-10 h-14 object-cover rounded shadow" alt="${anime.title}">
                                                <div class="overflow-hidden">
                                                    <h4 class="text-xs font-semibold text-gray-200 group-hover:text-accent truncate">${anime.title_english || anime.title}</h4>
                                                    <p class="text-[10px] text-gray-500 mt-1">★ ${anime.score || 'N/A'} &bull; ${anime.type || 'TV'}</p>
                                                </div>
                                            </div>
                                        `).join('')}
                                    </div>
                                ` : `
                                    <p class="text-xs text-gray-500">No suggestions available.</p>
                                `}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        async function renderBrowse(container) {
            const page = state.params.page || 1;
            const filter = state.params.filter || '';
            const genre = state.params.genre || '';
            const genreName = state.params.genreName || '';
            const type = state.params.type || '';

            let queryParams = `?page=${page}&limit=16`;
            if (filter) queryParams += `&filter=${filter}`;
            if (genre) queryParams += `&genres=${genre}`;
            if (type) queryParams += `&type=${type}`;

            const data = await fetchAPI(`/top/anime${queryParams}`);
            const animeList = data || [];

            container.innerHTML = `
                <div class="space-y-6 fade-in">
                    <div class="bg-cardBg border border-gray-800 p-4 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h1 class="text-xl font-bold text-white">
                                Browse Library ${genreName ? `- ${genreName}` : type ? `- ${type.toUpperCase()}s` : filter === 'bypopularity' ? '- Top Popular' : ''}
                            </h1>
                            <p class="text-xs text-gray-400">Page ${page}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button onclick="router.navigate('browse')" class="text-xs px-3 py-1.5 rounded-lg border ${!filter && !genre && !type ? 'bg-accent text-black font-bold border-accent' : 'border-gray-700 text-gray-300 hover:bg-hoverBg'}">All</button>
                            <button onclick="router.navigate('browse', { filter: 'bypopularity' })" class="text-xs px-3 py-1.5 rounded-lg border ${filter === 'bypopularity' ? 'bg-accent text-black font-bold border-accent' : 'border-gray-700 text-gray-300 hover:bg-hoverBg'}">Top Popular</button>
                            <button onclick="router.navigate('browse', { type: 'movie' })" class="text-xs px-3 py-1.5 rounded-lg border ${type === 'movie' ? 'bg-accent text-black font-bold border-accent' : 'border-gray-700 text-gray-300 hover:bg-hoverBg'}">Movies</button>
                        </div>
                    </div>

                    ${animeList.length > 0 ? `
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                            ${animeList.map(anime => createAnimeCard(anime)).join('')}
                        </div>
                    ` : `
                        <div class="text-center py-16 text-gray-500 text-sm">
                            No anime found for this criteria.
                        </div>
                    `}

                    <div class="flex justify-center items-center space-x-4 pt-6">
                        <button onclick="router.navigate('browse', { page: ${Math.max(1, page - 1)}, filter: '${filter}', genre: '${genre}', genreName: '${genreName}', type: '${type}' })" 
                                ${page <= 1 ? 'disabled class="opacity-40 text-xs px-4 py-2 bg-cardBg border border-gray-800 rounded-lg cursor-not-allowed"' : 'class="text-xs px-4 py-2 bg-cardBg hover:bg-hoverBg border border-gray-800 rounded-lg text-accent font-semibold transition"'}>
                            <i class="fa-solid fa-chevron-left mr-1"></i> Prev
                        </button>
                        <span class="text-xs text-gray-400">Page <strong class="text-white">${page}</strong></span>
                        <button onclick="router.navigate('browse', { page: ${page + 1}, filter: '${filter}', genre: '${genre}', genreName: '${genreName}', type: '${type}' })" 
                                class="text-xs px-4 py-2 bg-cardBg hover:bg-hoverBg border border-gray-800 rounded-lg text-accent font-semibold transition">
                            Next <i class="fa-solid fa-chevron-right ml-1"></i>
                        </button>
                    </div>
                </div>
            `;
        }

        async function renderWatch(container) {
            const id = state.params.id;
            if (!id) { router.navigate('home'); return; }

            const anime = await fetchAPI(`/anime/${id}/full`);
            if (!anime) {
                container.innerHTML = `
                    <div class="text-center py-16 space-y-4">
                        <p class="text-red-400 font-semibold">Failed to load anime details from Jikan API.</p>
                        <button onclick="router.navigate('home')" class="text-xs bg-cardBg border border-gray-700 px-4 py-2 rounded-lg text-accent">Return Home</button>
                    </div>`;
                return;
            }

            const trailerUrl = anime.trailer?.embed_url;

            container.innerHTML = `
                <div class="space-y-6 fade-in">
                    <button onclick="history.back()" class="text-xs text-accent hover:underline flex items-center gap-1">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </button>

                    <div class="bg-cardBg border border-gray-800 rounded-2xl p-6 shadow-2xl">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div class="lg:col-span-2 space-y-4">
                                <h1 class="text-2xl font-extrabold text-white">${anime.title_english || anime.title}</h1>
                                <div class="relative w-full aspect-video bg-black rounded-xl overflow-hidden border border-gray-800 shadow-xl">
                                    ${trailerUrl 
                                        ? `<iframe src="${trailerUrl}?autoplay=1" class="w-full h-full border-0" allow="autoplay; encrypted-media" allowfullscreen></iframe>`
                                        : `<div class="flex flex-col items-center justify-center h-full text-gray-500 text-xs space-y-2 p-6 text-center">
                                            <i class="fa-solid fa-video-slash text-3xl text-gray-600"></i>
                                            <p>Official Trailer Embed Not Provided by MyAnimeList API for this title.</p>
                                           </div>`
                                    }
                                </div>
                            </div>

                            <div class="space-y-4">
                                <img src="${anime.images?.jpg?.large_image_url || ''}" class="w-full h-64 object-cover rounded-xl shadow-lg" alt="${anime.title}">
                                <div class="bg-hoverBg p-4 rounded-xl space-y-2 text-xs border border-gray-800">
                                    <p><strong class="text-white">Score:</strong> ★ ${anime.score || 'N/A'}</p>
                                    <p><strong class="text-white">Type:</strong> ${anime.type || 'TV'}</p>
                                    <p><strong class="text-white">Status:</strong> ${anime.status || 'N/A'}</p>
                                    <p><strong class="text-white">Episodes:</strong> ${anime.episodes || 'N/A'}</p>
                                    <p><strong class="text-white">Studios:</strong> ${anime.studios?.map(s => s.name).join(', ') || 'N/A'}</p>
                                    <p><strong class="text-white">Aired:</strong> ${anime.aired?.string || 'N/A'}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t border-gray-800 space-y-4">
                            <div>
                                <h3 class="text-sm font-bold text-white mb-2">Genres</h3>
                                <div class="flex flex-wrap gap-2">
                                    ${(anime.genres || []).map(g => `
                                        <span class="text-[11px] bg-hoverBg border border-gray-700 px-2.5 py-1 rounded-md text-gray-300">${g.name}</span>
                                    `).join('')}
                                </div>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white mb-2">Synopsis</h3>
                                <p class="text-xs text-gray-300 leading-relaxed">${anime.synopsis || 'No synopsis provided.'}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        async function renderSearch(container) {
            const query = state.params.q || '';
            const results = query ? await fetchAPI(`/anime?q=${encodeURIComponent(query)}&limit=16`) : [];

            container.innerHTML = `
                <div class="space-y-6 fade-in">
                    <div class="bg-cardBg border border-gray-800 p-4 rounded-xl flex items-center justify-between">
                        <h1 class="text-xl font-bold text-white">Search Results: <span class="text-accent">"${query}"</span></h1>
                        <span class="text-xs text-gray-400">${results ? results.length : 0} results</span>
                    </div>

                    ${results && results.length > 0 ? `
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                            ${results.map(anime => createAnimeCard(anime)).join('')}
                        </div>
                    ` : `
                        <div class="text-center py-16 text-gray-500 text-sm">
                            No anime matching "${query}" were found.
                        </div>
                    `}
                </div>
            `;
        }

        function createAnimeCard(anime) {
            const title = anime.title_english || anime.title;
            const img = anime.images?.jpg?.large_image_url || anime.images?.jpg?.image_url || '';
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
                        <div class="absolute top-2 right-2 bg-black/70 backdrop-blur px-2 py-0.5 rounded text-[10px] font-bold text-yellow-400">
                            ★ ${score}
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
            if (menu) menu.classList.toggle('hidden');
        }

        window.addEventListener('DOMContentLoaded', () => {
            router.navigate('home');
        });
    </script>
</body>
</html>
