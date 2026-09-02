<?php
// TRIANIME - Single-file PHP Anime Streaming Website Clone
// Powered by Jikan API v4 & Tailwind CSS
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRIANIME - Stream Anime Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        darkBg: '#0a0a0a',
                        cardBg: '#121212',
                        cardHover: '#1e1e1e',
                        accent: '#ffbade',
                        accentHover: '#ff9ecf'
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #0a0a0a;
        }
        ::-webkit-scrollbar-thumb {
            background: #1e1e1e;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #ffbade;
        }
        .glass {
            background: rgba(18, 18, 18, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .glass-card {
            background: rgba(18, 18, 18, 0.6);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.03);
        }
        .fade-in {
            animation: fadeIn 0.4s ease-in-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-darkBg text-gray-100 min-h-screen flex flex-col font-sans selection:bg-[#ffbade] selection:text-black">

    <header class="fixed top-0 left-0 right-0 z-50 glass">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <a href="#" onclick="router.navigate('home')" class="flex items-center gap-2 group">
                <div class="w-10 h-10 rounded-xl bg-accent flex items-center justify-center text-black font-black text-xl shadow-lg shadow-[#ffbade]/20 group-hover:scale-105 transition-transform">
                    T
                </div>
                <span class="text-2xl font-black tracking-wider text-white">TRIA<span class="text-accent">NIME</span></span>
            </a>

            <nav class="hidden md:flex items-center gap-8 font-medium">
                <a href="#" onclick="router.navigate('home')" class="nav-link text-gray-300 hover:text-accent transition-colors" data-page="home">Home</a>
                <a href="#" onclick="router.navigate('browse')" class="nav-link text-gray-300 hover:text-accent transition-colors" data-page="browse">Browse Library</a>
            </nav>

            <div class="flex items-center gap-4">
                <div class="relative hidden sm:block">
                    <input type="text" id="searchInput" placeholder="Search anime..." 
                           class="bg-cardBg border border-white/10 rounded-full py-2 pl-10 pr-4 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent w-48 lg:w-64 transition-all">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-gray-500 text-sm"></i>
                </div>
                <button id="mobileMenuBtn" class="md:hidden text-gray-300 hover:text-accent text-2xl focus:outline-none">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>

        <div id="mobileMenu" class="hidden md:hidden bg-cardBg border-b border-white/5 px-4 pt-2 pb-6 space-y-4">
            <div class="relative mt-2 sm:hidden">
                <input type="text" id="mobileSearchInput" placeholder="Search anime..." 
                       class="w-full bg-darkBg border border-white/10 rounded-full py-2 pl-10 pr-4 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-accent">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-gray-500 text-sm"></i>
            </div>
            <a href="#" onclick="router.navigate('home'); toggleMobileMenu();" class="block py-2 text-gray-300 hover:text-accent font-medium">Home</a>
            <a href="#" onclick="router.navigate('browse'); toggleMobileMenu();" class="block py-2 text-gray-300 hover:text-accent font-medium">Browse Library</a>
        </div>
    </header>

    <main class="flex-grow pt-20" id="mainContent">
        </main>

    <footer class="bg-cardBg border-t border-white/5 mt-20 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-6 text-center md:text-left">
            <div>
                <div class="flex items-center justify-center md:justify-start gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-accent flex items-center justify-center text-black font-black">T</div>
                    <span class="text-xl font-bold text-white">TRIA<span class="text-accent">NIME</span></span>
                </div>
                <p class="text-gray-500 text-sm">Your ultimate destination for high-quality anime streaming.</p>
            </div>
            <p class="text-gray-600 text-xs">Data provided by the public Jikan API (v4). Built with Tailwind CSS & Vanilla JavaScript.</p>
        </div>
    </footer>

    <script>
        // --- HARDCODED GENRES (To Prevent Jikan API Rate Limits) ---
        const GENRES = [
            { id: 1, name: "Action" },
            { id: 2, name: "Adventure" },
            { id: 4, name: "Comedy" },
            { id: 8, name: "Drama" },
            { id: 10, name: "Fantasy" },
            { id: 14, name: "Horror" },
            { id: 7, name: "Mystery" },
            { id: 22, name: "Romance" },
            { id: 24, name: "Sci-Fi" },
            { id: 36, name: "Slice of Life" },
            { id: 30, name: "Sports" },
            { id: 37, name: "Supernatural" },
            { id: 41, name: "Suspense" }
        ];

        // --- APPLICATION STATE ---
        const state = {
            currentPage: 'home',
            currentAnimeId: null,
            currentEpisode: 1,
            browseParams: {
                page: 1,
                filter: 'top', // 'top', 'movie', or genre ID
                query: ''
            },
            heroAnimeList: [],
            latestAnimeList: [],
            upcomingAnimeList: [],
            searchTimeout: null
        };

        // --- UTILITY & RATE LIMIT DELAY HELPER ---
        const delay = ms => new Promise(resolve => setTimeout(resolve, ms));

        // --- API FETCH HELPERS ---
        async function fetchWithDelay(url, ms = 350) {
            try {
                await delay(ms);
                const res = await fetch(url);
                if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
                return await res.json();
            } catch (err) {
                console.error("API Fetch Error:", err);
                return null;
            }
        }

        // --- ROUTER & SPA CONTROLLER ---
        const router = {
            navigate(page, params = {}) {
                state.currentPage = page;
                window.scrollTo({ top: 0, behavior: 'smooth' });
                
                // Update active navbar styles
                document.querySelectorAll('.nav-link').forEach(link => {
                    if(link.getAttribute('data-page') === page) {
                        link.classList.add('text-accent');
                    } else {
                        link.classList.remove('text-accent');
                    }
                });

                const main = document.getElementById('mainContent');
                main.innerHTML = '';

                switch(page) {
                    case 'home':
                        renderHomePage(main);
                        break;
                    case 'browse':
                        if (params.filter) state.browseParams.filter = params.filter;
                        if (params.query !== undefined) state.browseParams.query = params.query;
                        state.browseParams.page = 1;
                        renderBrowsePage(main);
                        break;
                    case 'watch':
                        state.currentAnimeId = params.id;
                        state.currentEpisode = params.episode || 1;
                        renderWatchPage(main, params.id);
                        break;
                    case 'search':
                        state.browseParams.query = params.query;
                        state.browseParams.filter = 'search';
                        state.browseParams.page = 1;
                        renderBrowsePage(main);
                        break;
                    default:
                        renderHomePage(main);
                }
            }
        };

        // --- MOBILE MENU TOGGLE ---
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }
        document.getElementById('mobileMenuBtn').addEventListener('click', toggleMobileMenu);

        // --- SEARCH EVENT LISTENERS ---
        function setupSearchHandlers() {
            const handleSearchInput = (e) => {
                clearTimeout(state.searchTimeout);
                const query = e.target.value.trim();
                if (query.length > 2) {
                    state.searchTimeout = setTimeout(() => {
                        router.navigate('search', { query });
                    }, 500);
                }
            };

            const searchInput = document.getElementById('searchInput');
            const mobileSearchInput = document.getElementById('mobileSearchInput');

            if(searchInput) {
                searchInput.addEventListener('input', handleSearchInput);
                searchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter' && e.target.value.trim()) {
                        router.navigate('search', { query: e.target.value.trim() });
                    }
                });
            }
            if(mobileSearchInput) {
                mobileSearchInput.addEventListener('input', handleSearchInput);
                mobileSearchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter' && e.target.value.trim()) {
                        router.navigate('search', { query: e.target.value.trim() });
                        toggleMobileMenu();
                    }
                });
            }
        }

        // ==========================================
        // 1. HOME PAGE LOGIC & RENDERING
        // ==========================================
        async function renderHomePage(container) {
            container.innerHTML = `
                <div class="fade-in max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-12">
                    <div id="heroSkeleton" class="w-full h-[450px] md:h-[500px] bg-cardBg rounded-3xl animate-pulse flex items-center justify-center">
                        <div class="text-gray-600 flex items-center gap-3"><i class="fa-solid fa-spinner fa-spin"></i> Loading Featured Anime...</div>
                    </div>
                    <div id="heroContainer"></div>

                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                        <div class="lg:col-span-3 space-y-6">
                            <div class="flex items-center justify-between border-b border-white/5 pb-4">
                                <h2 class="text-2xl font-black text-white flex items-center gap-3">
                                    <span class="w-2.5 h-6 bg-accent rounded-full"></span> Latest Airing Anime
                                </h2>
                                <button onclick="router.navigate('browse', { filter: 'top' })" class="text-sm font-semibold text-accent hover:underline">View All</button>
                            </div>
                            <div id="latestGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                ${Array(8).fill(0).map(() => `
                                    <div class="bg-cardBg rounded-2xl h-64 animate-pulse"></div>
                                `).join('')}
                            </div>
                        </div>

                        <div class="space-y-8">
                            <div class="bg-cardBg border border-white/5 rounded-3xl p-6 space-y-4 shadow-xl">
                                <h3 class="text-lg font-bold text-white border-b border-white/5 pb-3">Popular Genres</h3>
                                <div class="flex flex-wrap gap-2">
                                    ${GENRES.map(g => `
                                        <button onclick="router.navigate('browse', { filter: '${g.id}' })" 
                                                class="text-xs font-semibold bg-darkBg hover:bg-accent hover:text-black border border-white/10 px-3 py-1.5 rounded-xl transition-all">
                                            ${g.name}
                                        </button>
                                    `).join('')}
                                </div>
                            </div>

                            <div class="bg-cardBg border border-white/5 rounded-3xl p-6 space-y-4 shadow-xl">
                                <h3 class="text-lg font-bold text-white border-b border-white/5 pb-3 flex items-center justify-between">
                                    <span>Upcoming Anime</span>
                                    <i class="fa-regular fa-clock text-accent text-sm"></i>
                                </h3>
                                <div id="upcomingList" class="space-y-4">
                                    ${Array(4).fill(0).map(() => `
                                        <div class="h-16 bg-darkBg rounded-xl animate-pulse"></div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Sequential Loading Protocol to respect Rate Limits
            try {
                // 1. Fetch Hero Data (Season Now / Fallback Top Airing Page 2)
                let heroData = await fetchWithDelay('https://api.jikan.moe/v4/seasons/now');
                if (!heroData || !heroData.data || heroData.data.length === 0) {
                    heroData = await fetchWithDelay('https://api.jikan.moe/v4/top/anime?filter=airing&page=2');
                }
                if (heroData && heroData.data) {
                    state.heroAnimeList = heroData.data.slice(0, 5);
                    renderHeroSlider();
                }

                // 2. Fetch Latest Airing
                const latestData = await fetchWithDelay('https://api.jikan.moe/v4/top/anime?filter=airing');
                if (latestData && latestData.data) {
                    state.latestAnimeList = latestData.data.slice(0, 12);
                    renderLatestGrid();
                }

                // 3. Fetch Upcoming Anime
                const upcomingData = await fetchWithDelay('https://api.jikan.moe/v4/seasons/upcoming');
                if (upcomingData && upcomingData.data) {
                    state.upcomingAnimeList = upcomingData.data.slice(0, 5);
                    renderUpcomingSidebar();
                }
            } catch (err) {
                console.error("Error loading home data:", err);
            }
        }

        // Hero Slider State & Logic
        let currentHeroIndex = 0;
        let heroInterval = null;

        function renderHeroSlider() {
            const container = document.getElementById('heroContainer');
            const skeleton = document.getElementById('heroSkeleton');
            if (skeleton) skeleton.remove();

            if (!state.heroAnimeList.length) return;

            const updateSlide = (index) => {
                const anime = state.heroAnimeList[index];
                const bgImage = anime.images.jpg.large_image_url || anime.images.jpg.image_url;
                
                container.innerHTML = `
                    <div class="relative w-full h-[450px] md:h-[500px] rounded-3xl overflow-hidden shadow-2xl group border border-white/10 fade-in">
                        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-1000 group-hover:scale-105" style="background-image: url('${bgImage}')"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-darkBg via-darkBg/60 to-transparent"></div>
                        <div class="absolute inset-0 bg-gradient-to-r from-darkBg via-darkBg/40 to-transparent"></div>

                        <div class="absolute bottom-0 left-0 right-0 p-6 md:p-12 max-w-2xl space-y-4">
                            <div class="flex items-center gap-2">
                                <span class="bg-accent text-black font-bold text-xs px-3 py-1 rounded-full uppercase tracking-wider">Top Airing</span>
                                <span class="text-xs text-gray-300 bg-black/40 backdrop-blur-md px-3 py-1 rounded-full border border-white/10"><i class="fa-solid fa-star text-yellow-400 mr-1"></i> ${anime.score || 'N/A'}</span>
                            </div>
                            <h1 class="text-3xl md:text-5xl font-black text-white tracking-tight drop-shadow-md">${anime.title}</h1>
                            <p class="text-gray-300 text-sm md:text-base line-clamp-3 leading-relaxed drop-shadow">${anime.synopsis || 'No synopsis available.'}</p>
                            
                            <div class="pt-2 flex items-center gap-4">
                                <button onclick="router.navigate('watch', { id: ${anime.mal_id}, episode: 1 })" 
                                        class="bg-accent hover:bg-accentHover text-black font-bold px-8 py-3.5 rounded-2xl shadow-lg shadow-[#ffbade]/20 flex items-center gap-2 transition-transform hover:scale-105">
                                    <i class="fa-solid fa-play"></i> Watch Now
                                </button>
                            </div>
                        </div>

                        <div class="absolute bottom-6 right-6 flex items-center gap-2">
                            ${state.heroAnimeList.map((_, i) => `
                                <button onclick="setHeroSlide(${i})" class="w-3 h-3 rounded-full transition-all ${i === index ? 'bg-accent w-8' : 'bg-white/30 hover:bg-white/60'}"></button>
                            `).join('')}
                        </div>
                    </div>
                `;
            };

            updateSlide(currentHeroIndex);

            // Auto-rotation setup
            if (heroInterval) clearInterval(heroInterval);
            heroInterval = setInterval(() => {
                currentHeroIndex = (currentHeroIndex + 1) % state.heroAnimeList.length;
                updateSlide(currentHeroIndex);
            }, 6000);
        }

        window.setHeroSlide = function(index) {
            currentHeroIndex = index;
            if (heroInterval) clearInterval(heroInterval);
            renderHeroSlider();
        }

        function renderLatestGrid() {
            const grid = document.getElementById('latestGrid');
            if (!grid) return;

            grid.innerHTML = state.latestAnimeList.map(anime => `
                <div onclick="router.navigate('watch', { id: ${anime.mal_id}, episode: 1 })" 
                     class="bg-cardBg hover:bg-cardHover border border-white/5 rounded-2xl overflow-hidden shadow-lg group cursor-pointer transition-all hover:-translate-y-1 flex flex-col">
                    <div class="relative aspect-[3/4] overflow-hidden bg-darkBg">
                        <img src="${anime.images.jpg.image_url}" alt="${anime.title}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        <div class="absolute top-2 right-2 bg-black/60 backdrop-blur-md px-2 py-1 rounded-lg text-xs font-bold text-accent border border-white/10 flex items-center gap-1">
                            <i class="fa-solid fa-star text-yellow-400"></i> ${anime.score || 'N/A'}
                        </div>
                    </div>
                    <div class="p-3.5 flex flex-col flex-grow justify-between">
                        <h3 class="font-bold text-sm text-white line-clamp-2 group-hover:text-accent transition-colors">${anime.title}</h3>
                        <div class="flex items-center justify-between mt-2 pt-2 border-t border-white/5 text-xs text-gray-400">
                            <span>${anime.type || 'TV'}</span>
                            <span class="text-accent font-medium"><i class="fa-solid fa-play mr-1"></i> Stream</span>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function renderUpcomingSidebar() {
            const list = document.getElementById('upcomingList');
            if (!list) return;

            list.innerHTML = state.upcomingAnimeList.map(anime => `
                <div onclick="router.navigate('watch', { id: ${anime.mal_id}, episode: 1 })" 
                     class="flex items-center gap-3 p-2 rounded-xl hover:bg-darkBg transition-colors cursor-pointer group">
                    <img src="${anime.images.jpg.small_image_url}" alt="${anime.title}" class="w-12 h-16 object-cover rounded-lg">
                    <div class="flex-grow min-w-0">
                        <h4 class="font-semibold text-sm text-white line-clamp-1 group-hover:text-accent transition-colors">${anime.title}</h4>
                        <p class="text-xs text-gray-400 mt-1">${anime.season ? anime.season.toUpperCase() + ' ' + (anime.year || '') : 'Upcoming'}</p>
                    </div>
                </div>
            `).join('');
        }

        // ==========================================
        // 2. BROWSE LIBRARY PAGE LOGIC & RENDERING
        // ==========================================
        async function renderBrowsePage(container) {
            const { filter, page, query } = state.browseParams;
            
            let titleText = "Browse Library";
            if (filter === 'top') titleText = "Top Rated Anime";
            else if (filter === 'movie') titleText = "Anime Movies";
            else if (filter === 'search') titleText = `Search Results for "${query}"`;
            else {
                const genreObj = GENRES.find(g => g.id == filter);
                if (genreObj) titleText = `${genreObj.name} Anime`;
            }

            container.innerHTML = `
                <div class="fade-in max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-cardBg border border-white/5 p-6 rounded-3xl shadow-xl">
                        <div>
                            <h1 class="text-2xl font-black text-white">${titleText}</h1>
                            <p class="text-gray-400 text-sm mt-1">Explore our vast library of high definition anime.</p>
                        </div>
                        
                        <div class="flex flex-wrap items-center gap-2">
                            <button onclick="router.navigate('browse', { filter: 'top' })" 
                                    class="text-xs font-bold px-4 py-2 rounded-xl border transition-all ${filter === 'top' ? 'bg-accent text-black border-accent' : 'bg-darkBg text-gray-300 border-white/10 hover:border-accent'}">
                                Top Rated
                            </button>
                            <button onclick="router.navigate('browse', { filter: 'movie' })" 
                                    class="text-xs font-bold px-4 py-2 rounded-xl border transition-all ${filter === 'movie' ? 'bg-accent text-black border-accent' : 'bg-darkBg text-gray-300 border-white/10 hover:border-accent'}">
                                Movies
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider shrink-0 mr-2">Genres:</span>
                        ${GENRES.map(g => `
                            <button onclick="router.navigate('browse', { filter: '${g.id}' })" 
                                    class="text-xs font-semibold px-3.5 py-1.5 rounded-xl shrink-0 transition-all ${filter == g.id ? 'bg-accent text-black font-bold' : 'bg-cardBg text-gray-300 border border-white/10 hover:border-accent'}">
                                ${g.name}
                            </button>
                        `).join('')}
                    </div>

                    <div id="browseGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
                        ${Array(15).fill(0).map(() => `
                            <div class="bg-cardBg rounded-2xl h-72 animate-pulse"></div>
                        `).join('')}
                    </div>

                    <div id="paginationContainer" class="flex items-center justify-center gap-4 pt-6"></div>
                </div>
            `;

            // Construct API URL based on active filter state
            let apiUrl = '';
            if (filter === 'search') {
                apiUrl = `https://api.jikan.moe/v4/anime?q=${encodeURIComponent(query)}&page=${page}`;
            } else if (filter === 'top') {
                apiUrl = `https://api.jikan.moe/v4/top/anime?page=${page}`;
            } else if (filter === 'movie') {
                apiUrl = `https://api.jikan.moe/v4/anime?type=movie&page=${page}`;
            } else {
                // Genre filter
                apiUrl = `https://api.jikan.moe/v4/anime?genres=${filter}&page=${page}`;
            }

            const data = await fetchWithDelay(apiUrl);
            if (data && data.data) {
                renderBrowseGrid(data.data);
                renderPagination(data.pagination);
            } else {
                document.getElementById('browseGrid').innerHTML = `
                    <div class="col-span-full py-16 text-center text-gray-400">
                        <i class="fa-solid fa-triangle-exclamation text-3xl text-accent mb-3"></i>
                        <p class="text-lg font-semibold">No anime found or API rate limit reached.</p>
                        <p class="text-xs text-gray-500 mt-1">Please try again in a few moments.</p>
                    </div>
                `;
            }
        }

        function renderBrowseGrid(animeList) {
            const grid = document.getElementById('browseGrid');
            if (!grid) return;

            if (animeList.length === 0) {
                grid.innerHTML = `<div class="col-span-full py-12 text-center text-gray-400">No results available for this selection.</div>`;
                return;
            }

            grid.innerHTML = animeList.map(anime => `
                <div onclick="router.navigate('watch', { id: ${anime.mal_id}, episode: 1 })" 
                     class="bg-cardBg hover:bg-cardHover border border-white/5 rounded-2xl overflow-hidden shadow-lg group cursor-pointer transition-all hover:-translate-y-1 flex flex-col">
                    <div class="relative aspect-[3/4] overflow-hidden bg-darkBg">
                        <img src="${anime.images.jpg.image_url}" alt="${anime.title}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        <div class="absolute top-2 right-2 bg-black/60 backdrop-blur-md px-2 py-1 rounded-lg text-xs font-bold text-accent border border-white/10 flex items-center gap-1">
                            <i class="fa-solid fa-star text-yellow-400"></i> ${anime.score || 'N/A'}
                        </div>
                    </div>
                    <div class="p-3.5 flex flex-col flex-grow justify-between">
                        <h3 class="font-bold text-sm text-white line-clamp-2 group-hover:text-accent transition-colors">${anime.title}</h3>
                        <div class="flex items-center justify-between mt-2 pt-2 border-t border-white/5 text-xs text-gray-400">
                            <span>${anime.type || 'TV'}</span>
                            <span class="text-accent font-medium">${anime.episodes ? anime.episodes + ' Eps' : 'Streaming'}</span>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function renderPagination(pagination) {
            const container = document.getElementById('paginationContainer');
            if (!container || !pagination) return;

            const { current_page, has_next_page } = pagination;
            
            container.innerHTML = `
                <button onclick="changeBrowsePage(${current_page - 1})" ${current_page <= 1 ? 'disabled class="opacity-40 cursor-not-allowed bg-cardBg border border-white/5 px-6 py-2.5 rounded-xl font-bold text-sm text-gray-400"' : 'class="bg-cardBg hover:bg-accent hover:text-black border border-white/10 px-6 py-2.5 rounded-xl font-bold text-sm text-white transition-all"'}>
                    <i class="fa-solid fa-arrow-left mr-2"></i> Previous
                </button>
                <span class="text-sm font-semibold text-gray-400 px-4">Page ${current_page}</span>
                <button onclick="changeBrowsePage(${current_page + 1})" ${!has_next_page ? 'disabled class="opacity-40 cursor-not-allowed bg-cardBg border border-white/5 px-6 py-2.5 rounded-xl font-bold text-sm text-gray-400"' : 'class="bg-cardBg hover:bg-accent hover:text-black border border-white/10 px-6 py-2.5 rounded-xl font-bold text-sm text-white transition-all"'}>
                    Next <i class="fa-solid fa-arrow-right ml-2"></i>
                </button>
            `;
        }

        window.changeBrowsePage = function(targetPage) {
            if (targetPage < 1) return;
            state.browseParams.page = targetPage;
            window.scrollTo({ top: 0, behavior: 'smooth' });
            renderBrowsePage(document.getElementById('mainContent'));
        }

        // ==========================================
        // 3. WATCH PAGE LOGIC & RENDERING
        // ==========================================
        async function renderWatchPage(container, animeId) {
            container.innerHTML = `
                <div class="fade-in max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-8">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2 space-y-6">
                            <div id="videoPlayerContainer" class="relative w-full aspect-video bg-black rounded-3xl overflow-hidden shadow-2xl border border-white/10 flex items-center justify-center">
                                <div class="text-gray-500 flex items-center gap-2"><i class="fa-solid fa-spinner fa-spin"></i> Loading Player...</div>
                            </div>

                            <div class="bg-cardBg border border-white/5 p-4 rounded-2xl flex items-center justify-between shadow-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-3 h-3 rounded-full bg-green-500 animate-pulse"></div>
                                    <span id="playerStateText" class="font-bold text-sm text-white">Playing Episode ${state.currentEpisode}</span>
                                </div>
                                <span class="text-xs text-gray-400 bg-darkBg px-3 py-1 rounded-lg border border-white/10">HD Server</span>
                            </div>

                            <div id="animeDetailsBox" class="bg-cardBg border border-white/5 p-6 md:p-8 rounded-3xl space-y-6 shadow-xl">
                                <div class="animate-pulse space-y-4">
                                    <div class="h-8 bg-darkBg rounded w-3/4"></div>
                                    <div class="h-20 bg-darkBg rounded"></div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="bg-cardBg border border-white/5 rounded-3xl p-6 shadow-xl flex flex-col h-[600px]">
                                <div class="flex items-center justify-between pb-4 border-b border-white/5">
                                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                                        <i class="fa-solid fa-list text-accent"></i> Episodes
                                    </h3>
                                    <span id="totalEpisodesCount" class="text-xs text-gray-400">Loading...</span>
                                </div>
                                <div id="episodeListContainer" class="flex-grow overflow-y-auto pr-2 space-y-2 mt-4">
                                    ${Array(10).fill(0).map(() => `
                                        <div class="h-12 bg-darkBg rounded-xl animate-pulse"></div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Fetch Anime Full Details & Episodes
            const animeData = await fetchWithDelay(`https://api.jikan.moe/v4/anime/${animeId}/full`);
            if (animeData && animeData.data) {
                const anime = animeData.data;
                renderVideoPlayer(anime);
                renderAnimeDetails(anime);
                renderEpisodeSidebar(anime);
            } else {
                document.getElementById('videoPlayerContainer').innerHTML = `
                    <div class="text-red-400 p-6 text-center">Failed to load anime stream data. Please select another title.</div>
                `;
            }
        }

        function renderVideoPlayer(anime) {
            const playerContainer = document.getElementById('videoPlayerContainer');
            
            // Priority 1: Use YouTube trailer embed if available
            if (anime.trailer && anime.trailer.embed_url) {
                // Ensure autoplay parameter is clean
                let embedUrl = anime.trailer.embed_url;
                if (!embedUrl.includes('autoplay=')) {
                    embedUrl += (embedUrl.includes('?') ? '&' : '?') + 'autoplay=1';
                }
                playerContainer.innerHTML = `
                    <iframe src="${embedUrl}" class="w-full h-full border-0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                `;
            } else {
                // Priority 2: Standard HTML5 Video Sample Fallback
                playerContainer.innerHTML = `
                    <video controls autoplay class="w-full h-full object-cover">
                        <source src="https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <div class="absolute top-4 left-4 bg-black/70 backdrop-blur-md px-3 py-1.5 rounded-xl border border-white/10 text-xs text-gray-300">
                        <i class="fa-solid fa-circle-info text-accent mr-1"></i> No trailer found. Playing sample stream.
                    </div>
                `;
            }
        }

        function renderAnimeDetails(anime) {
            const box = document.getElementById('animeDetailsBox');
            if (!box) return;

            const genresStr = anime.genres ? anime.genres.map(g => g.name).join(', ') : 'N/A';
            const studiosStr = anime.studios ? anime.studios.map(s => s.name).join(', ') : 'N/A';

            box.innerHTML = `
                <div class="flex flex-col md:flex-row gap-6">
                    <img src="${anime.images.jpg.image_url}" alt="${anime.title}" class="w-32 h-48 object-cover rounded-2xl shadow-lg border border-white/10 mx-auto md:mx-0 shrink-0">
                    <div class="space-y-3 flex-grow">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="bg-accent text-black font-bold text-xs px-3 py-1 rounded-full">${anime.type || 'TV'}</span>
                            <span class="text-xs bg-darkBg border border-white/10 px-3 py-1 rounded-full text-gray-300"><i class="fa-solid fa-star text-yellow-400 mr-1"></i> ${anime.score || 'N/A'}</span>
                            <span class="text-xs bg-darkBg border border-white/10 px-3 py-1 rounded-full text-gray-300">${anime.status || 'Finished'}</span>
                        </div>
                        <h2 class="text-2xl md:text-3xl font-black text-white">${anime.title}</h2>
                        <p class="text-gray-300 text-sm leading-relaxed">${anime.synopsis || 'No synopsis available.'}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 pt-4 border-t border-white/5 text-xs">
                    <div>
                        <span class="text-gray-500 block mb-1">Studios</span>
                        <span class="font-bold text-white">${studiosStr}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block mb-1">Genres</span>
                        <span class="font-bold text-white">${genresStr}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block mb-1">Broadcast / Airing</span>
                        <span class="font-bold text-white">${anime.aired ? anime.aired.string : 'N/A'}</span>
                    </div>
                </div>
            `;
        }

        function renderEpisodeSidebar(anime) {
            const listContainer = document.getElementById('episodeListContainer');
            const totalCountSpan = document.getElementById('totalEpisodesCount');
            if (!listContainer) return;

            // Determine total episodes (Default to 12 or max 24 for simulation if unknown)
            const totalEps = anime.episodes || 12;
            totalCountSpan.textContent = `${totalEps} Episodes`;

            let epsHTML = '';
            for (let i = 1; i <= totalEps; i++) {
                const isCurrent = i === parseInt(state.currentEpisode);
                epsHTML += `
                    <button onclick="changeEpisode(${anime.mal_id}, ${i})" 
                            class="w-full flex items-center justify-between p-3 rounded-xl border transition-all ${isCurrent ? 'bg-accent text-black font-bold border-accent shadow-lg shadow-[#ffbade]/20' : 'bg-darkBg hover:bg-cardHover text-gray-300 border-white/5'}">
                        <div class="flex items-center gap-3">
                            <span class="text-xs ${isCurrent ? 'text-black' : 'text-gray-500'}">#${i}</span>
                            <span class="text-sm">Episode ${i}</span>
                        </div>
                        <i class="fa-solid ${isCurrent ? 'fa-play' : 'fa-circle-play text-accent'} text-xs"></i>
                    </button>
                `;
            }
            listContainer.innerHTML = epsHTML;
        }

        window.changeEpisode = function(animeId, episodeNum) {
            state.currentEpisode = episodeNum;
            document.getElementById('playerStateText').textContent = `Playing Episode ${episodeNum}`;
            
            // Re-render episode sidebar to update active state styling without full page reload
            fetchWithDelay(`https://api.jikan.moe/v4/anime/${animeId}/full`).then(res => {
                if(res && res.data) renderEpisodeSidebar(res.data);
            });

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // --- INITIALIZE APPLICATION ON LOAD ---
        window.addEventListener('DOMContentLoaded', () => {
            setupSearchHandlers();
            router.navigate('home');
        });
    </script>
</body>
</html>
