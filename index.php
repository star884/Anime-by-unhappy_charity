<?php
// ==============================================================================
// TRIANIME - All-In-One Self-Populating Anime Portal & Background Sync Engine
// ==============================================================================

set_time_limit(0);
ini_set('memory_limit', '512M');

$dbFile = __DIR__ . '/anime_database.db';
$stateFile = __DIR__ . '/sync_state.json';

// Initialize SQLite Database Connection
try {
    $db = new PDO('sqlite:' . $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create schema if it does not exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS anime (
            mal_id INTEGER PRIMARY KEY,
            title TEXT NOT NULL,
            title_english TEXT,
            type TEXT,
            score REAL,
            episodes INTEGER,
            status TEXT,
            synopsis TEXT,
            image_url TEXT,
            trailer_url TEXT,
            genres TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_title ON anime(title);
        CREATE INDEX IF NOT EXISTS idx_score ON anime(score);
    ");
} catch (Exception $e) {
    die("Database Initialization Error: " . $e->getMessage());
}

// ==============================================================================
// 1. BACKGROUND / CRON POPULATION ENGINE
// Run manually via CLI: php index.php --sync
// Or auto-triggered via web cron: index.php?cron=1
// ==============================================================================
if (isset($_GET['cron']) || (php_sapi_name() === 'cli' && in_array('--sync', $argv))) {
    header('Content-Type: application/json');
    
    $currentState = file_exists($stateFile) ? json_decode(file_get_contents($stateFile), true) : ['page' => 1];
    $page = max(1, intval($currentState['page'] ?? 1));

    $insertStmt = $db->prepare("
        INSERT INTO anime (mal_id, title, title_english, type, score, episodes, status, synopsis, image_url, trailer_url, genres)
        VALUES (:mal_id, :title, :title_english, :type, :score, :episodes, :status, :synopsis, :image_url, :trailer_url, :genres)
        ON CONFLICT(mal_id) DO UPDATE SET
            score = excluded.score,
            episodes = excluded.episodes,
            status = excluded.status,
            synopsis = excluded.synopsis,
            image_url = excluded.image_url,
            trailer_url = excluded.trailer_url,
            genres = excluded.genres,
            updated_at = CURRENT_TIMESTAMP
    ");

    $maxPagesPerRun = 4; // Pull 100 entries per batch (stays under rate limits)
    $processedPages = 0;
    $totalFetched = 0;

    while ($processedPages < $maxPagesPerRun) {
        $url = "https://api.jikan.moe/v4/anime?page={$page}&limit=25&order_by=popularity";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'TriAnime-Populator/2.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 429) {
            sleep(2); // Rate limited, wait and retry
            continue;
        }

        if ($httpCode !== 200 || !$response) {
            break;
        }

        $json = json_decode($response, true);
        $data = $json['data'] ?? [];

        if (empty($data)) {
            $page = 1; // Cycle back to page 1 to refresh existing library
            break;
        }

        $db->beginTransaction();
        foreach ($data as $item) {
            $genresArr = array_map(function($g) { return $g['name']; }, $item['genres'] ?? []);
            
            $insertStmt->execute([
                ':mal_id'        => $item['mal_id'],
                ':title'         => $item['title'] ?? 'Unknown',
                ':title_english' => $item['title_english'] ?? $item['title'] ?? 'Unknown',
                ':type'          => $item['type'] ?? 'TV',
                ':score'         => floatval($item['score'] ?? 0.0),
                ':episodes'      => intval($item['episodes'] ?? 0),
                ':status'        => $item['status'] ?? 'Unknown',
                ':synopsis'      => $item['synopsis'] ?? '',
                ':image_url'     => $item['images']['jpg']['large_image_url'] ?? $item['images']['jpg']['image_url'] ?? '',
                ':trailer_url'   => $item['trailer']['embed_url'] ?? '',
                ':genres'        => implode(', ', $genresArr)
            ]);
            $totalFetched++;
        }
        $db->commit();

        $page++;
        $processedPages++;
        file_put_contents($stateFile, json_encode(['page' => $page]));
        usleep(800000); // 0.8s pause to strictly obey API limits
    }

    echo json_encode(['status' => 'success', 'fetched' => $totalFetched, 'next_page' => $page]);
    exit;
}

// ==============================================================================
// 2. INTERNAL REST API FOR FRONTEND DASHBOARD
// ==============================================================================
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    $action = $_GET['api'];

    if ($action === 'list') {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 24;
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['q'] ?? '');
        $genre = trim($_GET['genre'] ?? '');

        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(title LIKE :q OR title_english LIKE :q)";
            $params[':q'] = "%{$search}%";
        }
        if (!empty($genre)) {
            $where[] = "genres LIKE :g";
            $params[':g'] = "%{$genre}%";
        }

        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $stmt = $db->prepare("SELECT * FROM anime {$whereSql} ORDER BY score DESC LIMIT {$limit} OFFSET {$offset}");
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM anime {$whereSql}");
        $countStmt->execute($params);
        $total = intval($countStmt->fetchColumn());

        echo json_encode([
            'data' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, ceil($total / $limit))
        ]);
        exit;
    }

    if ($action === 'detail') {
        $id = intval($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM anime WHERE mal_id = :id");
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['data' => $item ?: null]);
        exit;
    }

    if ($action === 'stats') {
        $total = $db->query("SELECT COUNT(*) FROM anime")->fetchColumn();
        echo json_encode(['total_count' => intval($total)]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRIANIME - Auto-Populating Database Portal</title>
    <!-- Tailwind CSS -->
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
    <!-- FontAwesome Icons -->
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
        .fade-in { animation: fadeIn 0.25s ease-in forwards; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-darkBg text-gray-200 min-h-screen font-sans flex flex-col antialiased">

    <!-- NAVBAR -->
    <nav class="glass-nav fixed top-0 left-0 right-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3 cursor-pointer" onclick="router.navigate('home')">
                    <span class="text-2xl font-black tracking-wider text-accent">TRI<span class="text-white">ANIME</span></span>
                </div>
                <div class="hidden md:flex items-center space-x-6 text-sm font-semibold">
                    <button onclick="router.navigate('home')" class="hover:text-accent transition">Library</button>
                    <button onclick="triggerBackgroundSync()" class="hover:text-accent transition text-xs bg-hoverBg border border-gray-700 px-3 py-1 rounded-full flex items-center gap-1.5">
                        <i class="fa-solid fa-rotate" id="syncIcon"></i> Auto-Populate
                    </button>
                </div>
                <div class="flex items-center relative w-64">
                    <input type="text" id="searchInput" placeholder="Search anime library..." 
                           onkeydown="if(event.key==='Enter') handleSearch(this.value)"
                           class="w-full bg-cardBg border border-gray-800 rounded-full py-1.5 pl-4 pr-10 text-sm focus:outline-none focus:border-accent text-gray-200">
                    <i class="fa-solid fa-magnifying-glass absolute right-3 text-gray-400 cursor-pointer" onclick="handleSearch(document.getElementById('searchInput').value)"></i>
                </div>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTAINER -->
    <main id="app" class="flex-grow pt-20 pb-12 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8">
    </main>

    <!-- FOOTER -->
    <footer class="bg-cardBg border-t border-gray-900 py-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-center text-xs text-gray-500 space-y-1">
            <p><span class="text-accent font-bold">TRIANIME</span> &copy; 2026. Self-Populating Database Architecture.</p>
            <p id="libraryCountText" class="text-[11px] text-gray-600">Syncing database state...</p>
        </div>
    </footer>

    <!-- FRONTEND JAVASCRIPT -->
    <script>
        const state = { currentView: 'home', params: {} };

        const router = {
            navigate(view, params = {}) {
                state.currentView = view;
                state.params = params;
                window.scrollTo({ top: 0, behavior: 'smooth' });
                render();
            }
        };

        async function fetchAPI(endpoint) {
            try {
                const res = await fetch(`index.php?api=${endpoint}`);
                if (!res.ok) throw new Error('Network error');
                return await res.json();
            } catch (err) {
                console.error("API Error:", err);
                return { data: [], total: 0 };
            }
        }

        async function updateStats() {
            const stats = await fetchAPI('stats');
            const el = document.getElementById('libraryCountText');
            if (el && stats.total_count !== undefined) {
                el.innerText = `${stats.total_count} complete anime entries currently indexed in local SQLite database.`;
            }
        }

        async function triggerBackgroundSync() {
            const icon = document.getElementById('syncIcon');
            if (icon) icon.classList.add('fa-spin');
            try {
                await fetch('index.php?cron=1');
                await updateStats();
                if (state.currentView === 'home') render();
            } catch (err) {
                console.warn("Sync error:", err);
            } finally {
                if (icon) icon.classList.remove('fa-spin');
            }
        }

        async function render() {
            const app = document.getElementById('app');
            app.innerHTML = `
                <div class="flex flex-col justify-center items-center h-64 space-y-3">
                    <i class="fa-solid fa-circle-notch fa-spin text-4xl text-accent"></i>
                    <p class="text-xs text-gray-500">Querying database...</p>
                </div>`;

            if (state.currentView === 'watch') {
                await renderWatch(app);
            } else {
                await renderHome(app);
            }
            updateStats();
        }

        async function renderHome(container) {
            const page = state.params.page || 1;
            const search = state.params.q || '';
            const genre = state.params.genre || '';

            const res = await fetchAPI(`list&page=${page}&q=${encodeURIComponent(search)}&genre=${encodeURIComponent(genre)}`);
            const items = res.data || [];
            const total = res.total || 0;
            const totalPages = res.pages || 1;

            const GENRES = ['Action', 'Adventure', 'Comedy', 'Drama', 'Fantasy', 'Horror', 'Mystery', 'Romance', 'Sci-Fi', 'Slice of Life', 'Supernatural', 'Isekai'];

            container.innerHTML = `
                <div class="space-y-6 fade-in">
                    <div class="bg-cardBg border border-gray-800 p-4 rounded-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h1 class="text-xl font-bold text-white">
                                ${search ? `Search: "${search}"` : genre ? `Category: ${genre}` : 'Anime Collection'}
                            </h1>
                            <p class="text-xs text-gray-400">${total} titles found</p>
                        </div>
                        <div class="flex flex-wrap gap-1.5 text-xs">
                            <button onclick="router.navigate('home')" 
                                    class="px-2.5 py-1 rounded-lg border ${!genre && !search ? 'bg-accent text-black font-bold border-accent' : 'border-gray-800 bg-hoverBg text-gray-300'}">
                                All
                            </button>
                            ${GENRES.map(g => `
                                <button onclick="router.navigate('home', { genre: '${g}' })" 
                                        class="px-2.5 py-1 rounded-lg border ${genre === g ? 'bg-accent text-black font-bold border-accent' : 'border-gray-800 bg-hoverBg text-gray-300'}">
                                    ${g}
                                </button>
                            `).join('')}
                        </div>
                    </div>

                    ${items.length > 0 ? `
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            ${items.map(anime => `
                                <div onclick="router.navigate('watch', { id: ${anime.mal_id} })" 
                                     class="bg-cardBg border border-gray-800 rounded-xl overflow-hidden cursor-pointer group hover:border-accent transition duration-300 flex flex-col">
                                    <div class="relative aspect-[3/4] bg-hoverBg">
                                        <img src="${anime.image_url}" alt="${anime.title}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                                        <div class="absolute top-2 right-2 bg-black/80 backdrop-blur px-2 py-0.5 rounded text-[10px] font-bold text-yellow-400">
                                            ★ ${anime.score || 'N/A'}
                                        </div>
                                    </div>
                                    <div class="p-2.5 flex-grow">
                                        <h3 class="text-xs font-bold text-gray-200 group-hover:text-accent line-clamp-2 transition">${anime.title_english || anime.title}</h3>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    ` : `
                        <div class="text-center py-20 bg-cardBg border border-gray-800 rounded-xl space-y-4">
                            <i class="fa-solid fa-database text-4xl text-gray-600"></i>
                            <p class="text-sm text-gray-400">No titles match your filter or database is currently empty.</p>
                            <button onclick="triggerBackgroundSync()" class="text-xs bg-accent text-black font-bold px-4 py-2 rounded-lg">
                                Trigger Population Cycle Now
                            </button>
                        </div>
                    `}

                    <!-- PAGINATION -->
                    ${totalPages > 1 ? `
                        <div class="flex justify-center items-center space-x-4 pt-6">
                            <button onclick="router.navigate('home', { page: ${Math.max(1, page - 1)}, q: '${search}', genre: '${genre}' })" 
                                    ${page <= 1 ? 'disabled class="opacity-40 text-xs px-4 py-2 bg-cardBg border border-gray-800 rounded-lg cursor-not-allowed"' : 'class="text-xs px-4 py-2 bg-cardBg hover:bg-hoverBg border border-gray-800 rounded-lg text-accent font-semibold transition"'}>
                                <i class="fa-solid fa-chevron-left mr-1"></i> Prev
                            </button>
                            <span class="text-xs text-gray-400">Page <strong class="text-white">${page}</strong> of ${totalPages}</span>
                            <button onclick="router.navigate('home', { page: ${page + 1}, q: '${search}', genre: '${genre}' })" 
                                    ${page >= totalPages ? 'disabled class="opacity-40 text-xs px-4 py-2 bg-cardBg border border-gray-800 rounded-lg cursor-not-allowed"' : 'class="text-xs px-4 py-2 bg-cardBg hover:bg-hoverBg border border-gray-800 rounded-lg text-accent font-semibold transition"'}>
                                Next <i class="fa-solid fa-chevron-right ml-1"></i>
                            </button>
                        </div>
                    ` : ''}
                </div>
            `;
        }

        async function renderWatch(container) {
            const id = state.params.id;
            const res = await fetchAPI(`detail&id=${id}`);
            const anime = res.data;

            if (!anime) {
                container.innerHTML = `
                    <div class="text-center py-16 space-y-4">
                        <p class="text-red-400 text-sm font-semibold">Title details not found in local database.</p>
                        <button onclick="router.navigate('home')" class="text-xs bg-cardBg border border-gray-700 px-4 py-2 rounded-lg text-accent">Return to Library</button>
                    </div>`;
                return;
            }

            container.innerHTML = `
                <div class="space-y-6 fade-in">
                    <button onclick="history.back()" class="text-xs text-accent hover:underline flex items-center gap-1">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </button>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-4">
                            <h1 class="text-2xl font-extrabold text-white">${anime.title_english || anime.title}</h1>
                            <div class="relative w-full aspect-video bg-black rounded-xl overflow-hidden border border-gray-800 shadow-xl">
                                ${anime.trailer_url 
                                    ? `<iframe src="${anime.trailer_url}?autoplay=0" class="w-full h-full border-0" allowfullscreen></iframe>`
                                    : `<div class="flex flex-col items-center justify-center h-full text-gray-500 text-xs p-6 text-center space-y-2">
                                        <i class="fa-solid fa-video-slash text-4xl text-gray-600"></i>
                                        <p>Official Trailer Embed is not available for this entry.</p>
                                       </div>`
                                }
                            </div>
                        </div>

                        <div class="space-y-4">
                            <img src="${anime.image_url}" class="w-full h-64 object-cover rounded-xl border border-gray-800 shadow-lg" alt="${anime.title}">
                            <div class="bg-cardBg p-4 rounded-xl space-y-2 text-xs border border-gray-800">
                                <p><strong class="text-white">Score:</strong> ★ ${anime.score || 'N/A'}</p>
                                <p><strong class="text-white">Type:</strong> ${anime.type || 'TV'}</p>
                                <p><strong class="text-white">Status:</strong> ${anime.status || 'N/A'}</p>
                                <p><strong class="text-white">Episodes:</strong> ${anime.episodes || 'N/A'}</p>
                                <p><strong class="text-white">Genres:</strong> ${anime.genres || 'N/A'}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-cardBg border border-gray-800 rounded-xl p-6">
                        <h3 class="text-sm font-bold text-white mb-2">Synopsis</h3>
                        <p class="text-xs text-gray-300 leading-relaxed">${anime.synopsis || 'No synopsis provided.'}</p>
                    </div>
                </div>
            `;
        }

        function handleSearch(q) {
            router.navigate('home', { q: q.trim() });
        }

        window.addEventListener('DOMContentLoaded', () => {
            router.navigate('home');
            // Silent population call on initial load to seed empty databases
            triggerBackgroundSync();
        });
    </script>
</body>
</html>
