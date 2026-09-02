const fs = require('fs');

const JIKAN_URL = 'https://api.jikan.moe/v4/seasons/now';
const ANILIST_URL = 'https://graphql.anilist.co';

async function fetchFromJikan() {
  const res = await fetch(JIKAN_URL);
  if (res.status === 429) throw new Error('Jikan Rate Limited (429)');
  if (!res.ok) throw new Error(`Jikan Error: ${res.status}`);
  const json = await res.json();
  return (json.data || []).map(item => ({
    id: item.mal_id,
    title: item.title,
    image: item.images?.jpg?.large_image_url || '',
    score: item.score || 0,
    type: item.type || 'TV'
  }));
}

async function fetchFromAniList() {
  const query = `{
    Page(page: 1, perPage: 25) {
      media(status: RELEASING, type: ANIME, sort: POPULARITY_DESC) {
        id
        title { romaji }
        coverImage { extraLarge }
        averageScore
        format
      }
    }
  }`;

  const res = await fetch(ANILIST_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ query })
  });

  if (res.status === 429) throw new Error('AniList Rate Limited (429)');
  if (!res.ok) throw new Error(`AniList Error: ${res.status}`);
  const json = await res.json();
  
  return (json.data?.Page?.media || []).map(item => ({
    id: item.id,
    title: item.title.romaji,
    image: item.coverImage?.extraLarge || '',
    score: item.averageScore ? (item.averageScore / 10) : 0,
    type: item.format || 'TV'
  }));
}

async function runSync() {
  let animeList = [];

  try {
    console.log('Attempting sync via Jikan API...');
    animeList = await fetchFromJikan();
    console.log(`Success! Fetched ${animeList.length} items from Jikan.`);
  } catch (err) {
    console.warn(`[Jikan Failed]: ${err.message}. Failing over to AniList...`);
    try {
      animeList = await fetchFromAniList();
      console.log(`Success! Fetched ${animeList.length} items from AniList fallback.`);
    } catch (backupErr) {
      console.error(`[AniList Failed]: ${backupErr.message}. All APIs failed.`);
      process.exit(1);
    }
  }

  const payload = {
    lastUpdated: new Date().toISOString(),
    total: animeList.length,
    data: animeList
  };

  fs.writeFileSync('data.json', JSON.stringify(payload, null, 2));
  console.log('Successfully written to data.json');
}

runSync();
