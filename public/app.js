/**
 * app.js - improved frontend
 * - Debounced search
 * - Loading / error states
 * - Uses server /api endpoints:
 *   /api/search?q=
 *   /api/anime/:malId/episodes
 *   /api/provider-search?title=     (optional)
 *   /api/stream/:providerEpisodeId
 *
 * Notes:
 * - The server will try to find provider streams; if provider ids are missing, you may need to run provider-search to map MAL ids to provider ids.
 * - Hls.js is used for playback in non-Safari browsers.
 */

const searchInput = document.getElementById('search-input');
const searchBtn = document.getElementById('search-btn');
const catalog = document.getElementById('catalog');
const feedback = document.getElementById('feedback');

const playerPanel = document.getElementById('player-panel');
const videoPlayer = document.getElementById('video-player');
const episodeList = document.getElementById('episode-list');
const playingTitle = document.getElementById('playing-title');

let hlsInstance = null;
let debounceTimer = null;

function showFeedback(msg) {
  feedback.textContent = msg || '';
}

function clearCatalog() {
  catalog.innerHTML = '';
}

function createCard(item) {
  const card = document.createElement('div');
  card.className = 'card';
  card.tabIndex = 0;
  card.innerHTML = `
    <img class="poster" src="${item.image || 'https://via.placeholder.com/300x420?text=No+Image'}" alt="${escapeHtml(item.title)} poster" />
    <div class="meta">
      <div style="flex:1;">
        <div class="title">${escapeHtml(item.title)}</div>
        <div class="summary">${escapeHtml(item.summary || 'No synopsis available')}</div>
      </div>
      <div style="margin-left:8px;text-align:right;">
        <div class="score">${item.score ?? '—'}</div>
        <div style="font-size:0.8rem;color:#9aa0a6">${item.episodes ? item.episodes + ' eps' : ''}</div>
      </div>
    </div>
  `;
  card.addEventListener('click', () => onSelectItem(item));
  card.addEventListener('keypress', (e) => { if (e.key === 'Enter') onSelectItem(item); });
  return card;
}

function escapeHtml(str) {
  if (!str) return '';
  return str.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;');
}

async function searchAnime(query) {
  if (!query) {
    showFeedback('Please enter a search term.');
    return;
  }
  clearCatalog();
  showFeedback('Searching...');

  try {
    const res = await fetch(`/api/search?q=${encodeURIComponent(query)}`);
    if (!res.ok) throw new Error(`Search failed: ${res.status}`);
    const data = await res.json();

    clearCatalog();
    if (!Array.isArray(data) || data.length === 0) {
      showFeedback('No results found.');
      return;
    }

    data.forEach(item => {
      const card = createCard(item);
      catalog.appendChild(card);
    });
    showFeedback('');
  } catch (err) {
    console.error(err);
    showFeedback('An error occurred while searching. Check the console.');
  }
}

searchBtn.addEventListener('click', () => {
  const q = searchInput.value.trim();
  searchAnime(q);
});

// Debounce on typing
searchInput.addEventListener('input', () => {
  if (debounceTimer) clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    const q = searchInput.value.trim();
    if (q) searchAnime(q);
  }, 450);
});

async function onSelectItem(item) {
  // item.id is the Jikan MAL id
  playingTitle.textContent = `Loading episodes for: ${item.title}`;
  episodeList.innerHTML = '';
  playerPanel.style.display = 'block';
  window.scrollTo({ top: 0, behavior: 'smooth' });

  try {
    // First, try to fetch episodes from server (Jikan-based)
    const res = await fetch(`/api/anime/${encodeURIComponent(item.id)}/episodes`);
    if (!res.ok) throw new Error('Failed to load episode metadata.');
    const data = await res.json();
    const episodes = data.episodes || [];
    if (!episodes.length) {
      playingTitle.textContent = `No episode metadata found for: ${item.title}`;
      return;
    }

    playingTitle.textContent = `Select episode — ${item.title}`;
    // Build episode buttons (limit to first 100 for safety)
    const max = Math.min(episodes.length, 200);
    for (let i = 0; i < max; i++) {
      const ep = episodes[i];
      const btn = document.createElement('button');
      btn.className = 'ep-btn';
      btn.textContent = `Ep ${ep.number}`;
      btn.title = ep.title || '';
      btn.addEventListener('click', () => onSelectEpisode(item, ep));
      episodeList.appendChild(btn);
    }
  } catch (err) {
    console.error(err);
    playingTitle.textContent = 'Failed loading episodes. See console for details.';
  }
}

async function onSelectEpisode(item, episode) {
  // Depending on your provider mapping, episode.providerId may be null.
  // If you have a provider-specific ID, prefer calling /api/stream/:providerEpisodeId.
  // Otherwise, attempt a provider search to map MAL title -> provider results, then pick a matching episode id.

  playingTitle.textContent = `Loading stream — ${item.title} Ep ${episode.number}`;
  showFeedback('Loading stream manifest...');

  try {
    // If providerId exists, use it:
    if (episode.providerId) {
      await loadStreamForProviderEpisode(episode.providerId);
      showFeedback('');
      return;
    }

    // Otherwise, attempt provider search to find a provider item and then fetch its watch endpoint.
    // This is best-effort and may need tuning depending on provider API shape.
    const providerSearchRes = await fetch(`/api/provider-search?title=${encodeURIComponent(item.title)}`);
    if (!providerSearchRes.ok) throw new Error('Provider search failed');
    const providerData = await providerSearchRes.json();

    // Heuristic: providerData.data may contain results array with id fields like 'id' or 'slug'.
    const providerCandidateId = extractProviderEpisodeIdFromSearch(providerData, episode.number);
    if (!providerCandidateId) {
      showFeedback('Could not find a provider mapping for this episode automatically.');
      return;
    }

    await loadStreamForProviderEpisode(providerCandidateId);
    showFeedback('');
  } catch (err) {
    console.error(err);
    showFeedback('Failed fetching stream manifest. See console for details.');
  }
}

function extractProviderEpisodeIdFromSearch(providerData, episodeNumber) {
  // Best-effort heuristic parsing. Adjust based on actual provider response shape.
  // providerData: { url, data }
  const d = providerData?.data;
  if (!d) return null;

  // If results array present, take first and try to find episodes array inside.
  if (Array.isArray(d.results) && d.results.length) {
    const best = d.results[0];
    // Some providers include 'id' or 'slug'
    if (best.id) return best.id;
    if (best.slug) return best.slug;
    if (best._id) return best._id;
  }

  // If a top-level object with id exists:
  if (d.id) return d.id;
  if (d.slug) return d.slug;

  // Fallback
  return null;
}

async function loadStreamForProviderEpisode(providerEpisodeId) {
  // Fetch provider manifest via server
  const res = await fetch(`/api/stream/${encodeURIComponent(providerEpisodeId)}`);
  if (!res.ok) throw new Error('Failed to fetch stream from provider');
  const payload = await res.json();

  // Try to find a playable HLS URL inside payload.data or payload.data.sources
  let streamUrl = null;
  // common shapes
  if (payload.data?.sources && Array.isArray(payload.data.sources) && payload.data.sources.length) {
    // pick first with .m3u8
    for (const s of payload.data.sources) {
      const url = s.url || s.file || s.src;
      if (typeof url === 'string' && url.endsWith('.m3u8')) { streamUrl = url; break; }
    }
    if (!streamUrl && payload.data.sources[0]) streamUrl = payload.data.sources[0].url || payload.data.sources[0].file;
  } else if (payload.data?.streamUrl) {
    streamUrl = payload.data.streamUrl;
  } else if (typeof payload.data === 'string' && payload.data.includes('.m3u8')) {
    // Sometimes provider returns a manifest string or a URL
    const m = payload.data.match(/https?:\/\/\S+\.m3u8/);
    if (m) streamUrl = m[0];
  }

  if (!streamUrl) {
    throw new Error('No HLS manifest URL could be located in provider response.');
  }

  // Attach to player
  attachHlsPlayer(streamUrl);
}

function attachHlsPlayer(manifestUrl) {
  if (hlsInstance) {
    try { hlsInstance.destroy(); } catch (e) { /* ignore */ }
    hlsInstance = null;
  }
  // Auto-play attempt
  if (Hls.isSupported()) {
    hlsInstance = new Hls();
    hlsInstance.loadSource(manifestUrl);
    hlsInstance.attachMedia(videoPlayer);
    hlsInstance.on(Hls.Events.MANIFEST_PARSED, function () {
      videoPlayer.play().catch(() => { /* user gesture required */ });
    });
  } else if (videoPlayer.canPlayType('application/vnd.apple.mpegurl')) {
    videoPlayer.src = manifestUrl;
    videoPlayer.addEventListener('loadedmetadata', () => {
      videoPlayer.play().catch(() => {});
    }, { once: true });
  } else {
    showFeedback('HLS playback is not supported in this browser.');
  }
}

window.addEventListener('unload', () => {
  if (hlsInstance) try { hlsInstance.destroy(); } catch (e) {}
});
