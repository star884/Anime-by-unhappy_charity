require('dotenv').config();
const express = require('express');
const axios = require('axios');
const path = require('path');
const helmet = require('helmet');
const morgan = require('morgan');
const NodeCache = require('node-cache');
const rateLimit = require('express-rate-limit');
const cors = require('cors');
const dns = require('dns');
const http = require('http');
const https = require('https');

const app = express();
const PORT = Number(process.env.PORT || 3000);

// Configuration: swap or adjust these as provider endpoints change
const JIKAN_BASE = process.env.JIKAN_BASE || 'https://api.jikan.moe/v4';
const CONSUMET_BASE = process.env.CONSUMET_BASE || 'https://consumet.org'; // NOTE: adjust if provider exposes differing API paths

// Cache TTL (seconds)
const CACHE_TTL = Number(process.env.CACHE_TTL || 3600); // 1 hour default
const cache = new NodeCache({ stdTTL: CACHE_TTL, checkperiod: 120 });

app.use(helmet());
app.use(cors()); // restrict in production to allowed origins
app.use(express.json());
app.use(morgan('combined'));

// Basic per-ip rate limiting
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: Number(process.env.RATE_LIMIT_MAX || 120),
  standardHeaders: true,
  legacyHeaders: false
});
app.use(limiter);

// Serve static frontend
app.use(express.static(path.join(__dirname, 'public')));

/**
 * Force IPv4 lookups for outgoing requests to avoid environments where IPv6 is unroutable.
 * Many hosting providers have misconfigured IPv6 or no IPv6 connectivity which can cause
 * `ENETUNREACH` or `ETIMEDOUT` when DNS resolves to IPv6 addresses. We create custom
 * http/https agents that use dns.lookup with family:4.
 */
function lookupIpv4(hostname, options, callback) {
  // dns.lookup signature: (hostname, options, callback)
  // We ignore the incoming options and force family:4
  return dns.lookup(hostname, { family: 4 }, callback);
}

const httpAgent = new http.Agent({ keepAlive: true, lookup: lookupIpv4 });
const httpsAgent = new https.Agent({ keepAlive: true, lookup: lookupIpv4 });

// Create an axios instance that uses the IPv4-forcing agents and a reasonable timeout
const axiosInstance = axios.create({
  timeout: 10000,
  httpAgent,
  httpsAgent,
  // Do not follow redirects too many times
  maxRedirects: 5
});

/**
 * Helpers
 */
function sendServerError(res, message, details = null) {
  const body = { error: message };
  if (details) body.details = details;
  return res.status(500).json(body);
}

/**
 * GET /api/search?q=...
 * Uses Jikan (v4) search for anime by title.
 * Caches results to reduce external calls.
 */
app.get('/api/search', async (req, res) => {
  const q = String(req.query.q || '').trim();
  if (!q) return res.status(400).json({ error: 'Query parameter "q" is required' });

  const cacheKey = `search:${q.toLowerCase()}`;
  const cached = cache.get(cacheKey);
  if (cached) return res.json(cached);

  try {
    const limit = Math.min(Number(req.query.limit || 10), 25);
    const jikanUrl = `${JIKAN_BASE}/anime?q=${encodeURIComponent(q)}&limit=${limit}`;
    const response = await axiosInstance.get(jikanUrl);

    // Normalise Jikan response to a compact card format
    const results = (response.data?.data || []).map(item => ({
      id: item.mal_id,
      title: item.title,
      image: item.images?.jpg?.large_image_url || item.images?.jpg?.image_url || null,
      summary: item.synopsis || '',
      score: item.score || null,
      episodes: item.episodes || null,
      type: item.type || null,
      aired: item.aired?.string || null
    }));

    cache.set(cacheKey, results);
    return res.json(results);
  } catch (err) {
    // If the error looks like an IPv6/unreachable error, provide a helpful hint in the logs
    console.error('Jikan search error', err?.message || err);
    // Provide a 502 with actionable message for the client
    return res.status(502).json({ error: 'Upstream metadata service unreachable', details: err?.message });
  }
});

/**
 * GET /api/anime/:malId/episodes
 * Return episode list for an anime using Jikan episodes endpoint where available.
 * If Jikan lacks episodes or the anime id is actually a provider id, the client may fall back to provider-specific endpoint.
 */
app.get('/api/anime/:malId/episodes', async (req, res) => {
  const malId = Number(req.params.malId);
  if (!Number.isInteger(malId) || malId <= 0) {
    return res.status(400).json({ error: 'malId path parameter must be a positive integer (Jikan MAL id).' });
  }

  const cacheKey = `episodes:${malId}`;
  const cached = cache.get(cacheKey);
  if (cached) return res.json(cached);

  try {
    // Jikan episodes endpoint
    const epsUrl = `${JIKAN_BASE}/anime/${malId}/episodes`;
    const response = await axiosInstance.get(epsUrl);

    // Map episodes to simplified objects: number + title + aired + id placeholder
    const episodes = (response.data?.data || []).map(ep => ({
      number: ep.episode_id || ep.mal_id || ep.episode,
      title: ep.title || `Episode ${ep.episode_id || ep.episode}`,
      aired: ep.aired || null,
      // NOTE: Jikan does not provide provider-specific stream ids — if you need a provider id,
      // use a provider search endpoint (see /api/provider-search) to map MAL->provider
      providerId: null
    }));

    const payload = { malId, episodes };
    cache.set(cacheKey, payload);
    return res.json(payload);
  } catch (err) {
    console.warn('Jikan episodes fetch failed', err?.message || err);
    return res.status(502).json({ error: 'Upstream metadata service unreachable', details: err?.message });
  }
});

/**
 * GET /api/provider-search?title=...
 * Optional helper: search a streaming provider (Consumet) for a title and return provider metadata.
 * This is best-effort — provider endpoints and shapes vary; adjust CONSUMET_* values if needed.
 *
 * NOTE: This endpoint is a convenience so the frontend can map a metadata result to a provider item id.
 */
app.get('/api/provider-search', async (req, res) => {
  const title = String(req.query.title || '').trim();
  if (!title) return res.status(400).json({ error: 'Query parameter "title" is required' });

  const cacheKey = `provider-search:${title.toLowerCase()}`;
  const cached = cache.get(cacheKey);
  if (cached) return res.json(cached);

  try {
    // TODO: If your provider exposes a JSON search API, switch to that exact path.
    const attempts = [
      `${CONSUMET_BASE}/search/${encodeURIComponent(title)}`,
      `${CONSUMET_BASE}/search?query=${encodeURIComponent(title)}`,
      `${CONSUMET_BASE}/anime/${encodeURIComponent(title)}`,
      `${CONSUMET_BASE}/meta/anilist/${encodeURIComponent(title)}`
    ];

    let lastErr = null;
    for (const url of attempts) {
      try {
        const r = await axiosInstance.get(url);
        if (r?.data) {
          const out = { url, data: r.data };
          cache.set(cacheKey, out);
          return res.json(out);
        }
      } catch (e) {
        lastErr = e;
      }
    }

    return res.status(502).json({ error: 'Provider search attempts failed; provider API shape may differ.', details: lastErr?.message });
  } catch (err) {
    console.error('Provider search fatal error', err?.message || err);
    return res.status(502).json({ error: 'Provider search failed', details: err?.message });
  }
});

/**
 * GET /api/stream/:providerEpisodeId
 * Proxy to the provider's "watch" endpoint and return JSON with streaming sources.
 * This is intended to let the server fetch the manifest (and avoid CORS) and return the HLS .m3u8 URL(s) to the client.
 */
app.get('/api/stream/:providerEpisodeId', async (req, res) => {
  const providerEpisodeId = String(req.params.providerEpisodeId || '').trim();
  if (!providerEpisodeId) return res.status(400).json({ error: 'providerEpisodeId path parameter is required.' });

  const cacheKey = `stream:${providerEpisodeId}`;
  const cached = cache.get(cacheKey);
  if (cached) return res.json(cached);

  try {
    const url = `${CONSUMET_BASE}/watch/${encodeURIComponent(providerEpisodeId)}`;
    const r = await axiosInstance.get(url);

    const payload = { providerEpisodeId, fetchedFrom: url, data: r.data };
    cache.set(cacheKey, payload);
    return res.json(payload);
  } catch (err) {
    console.error('Provider stream fetch error', err?.message || err);
    return res.status(502).json({ error: 'Failed retrieving provider stream manifest', details: err?.message });
  }
});

// Fallback route: serve index.html for SPA routing
app.get('*', (req, res) => {
  if (req.path.startsWith('/api/')) {
    return res.status(404).json({ error: 'API route not found' });
  }
  return res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

app.listen(PORT, () => {
  console.log(`Anime stream engine listening on http://localhost:${PORT}`);
});
