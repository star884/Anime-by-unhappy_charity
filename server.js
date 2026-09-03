const express = require('express');
const axios = require('axios');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;

// Host public frontend folder directly
app.use(express.static(path.join(__dirname, 'public')));

// Public API instances 
const JIKAN_URL = 'https://jikan.moe';
const CONSUMET_URL = 'https://consumet.org';

/**
 * Route 1: Catalog Search Engine (Via Jikan API)
 */
app.get('/api/search', async (req, res) => {
    try {
        const query = req.query.q;
        if (!query) return res.status(400).json({ error: 'Query parameter "q" is required' });

        const response = await axios.get(`${JIKAN_URL}/anime?q=${encodeURIComponent(query)}&limit=10`);
        
        // Structure uniform metadata cards
        const results = response.data.data.map(item => ({
            id: item.mal_id,
            title: item.title,
            image: item.images.jpg.large_image_url,
            summary: item.synopsis,
            score: item.score
        }));
        
        res.json(results);
    } catch (error) {
        res.status(500).json({ error: 'Failed fetching metadata from Jikan', details: error.message });
    }
});

/**
 * Route 2: Fetch Episode Watchlists (Via Consumet Gogoanime Proxy)
 */
app.get('/api/anime/:title/episodes', async (req, res) => {
    try {
        const title = req.params.title;
        // Search Consumet library matching metadata title
        const searchRes = await axios.get(`${CONSUMET_URL}/${encodeURIComponent(title)}`);
        if (!searchRes.data.results || searchRes.data.results.length === 0) {
            return res.status(404).json({ error: 'Anime media not found on streaming servers' });
        }

        const exactAnime = searchRes.data.results[0]; // Grab best match
        const infoRes = await axios.get(`https://consumet.org/info/${exactAnime.id}`);
        
        res.json({
            providerId: exactAnime.id,
            episodes: infoRes.data.episodes // Contains episode numbers + string IDs
        });
    } catch (error) {
        res.status(500).json({ error: 'Failed extracting episode structural arrays', details: error.message });
    }
});

/**
 * Route 3: Extract Decrypted M3U8 Streams (Via Consumet Gogoanime Scraper)
 */
app.get('/api/stream/:episodeId', async (req, res) => {
    try {
        const episodeId = req.params.episodeId;
        const response = await axios.get(`https://consumet.org/watch/${episodeId}`);
        
        // Returns sources list containing direct streaming file (.m3u8 files)
        res.json(response.data);
    } catch (error) {
        res.status(500).json({ error: 'Failed retrieving direct streaming manifest sources', details: error.message });
    }
});

app.listen(PORT, () => {
    console.log(`Server streaming engine running smoothly at http://localhost:${PORT}`);
});
