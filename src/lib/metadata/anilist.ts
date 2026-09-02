import { AnimeMetadata } from '@/types';

const ANILIST_GRAPHQL_ENDPOINT = 'https://graphql.anilist.co';

const SEARCH_QUERY = `
query ($search: String) {
  Media (search: $search, type: ANIME) {
    id
    title {
      romaji
      english
      native
    }
    coverImage {
      large
    }
    description
    episodes
  }
}
`;

export async function fetchAnimeMetadata(searchTitle: string): Promise<AnimeMetadata | null> {
  try {
    const response = await fetch(ANILIST_GRAPHQL_ENDPOINT, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({
        query: SEARCH_QUERY,
        variables: { search: searchTitle },
      }),
      next: { revalidate: 3600 }, // Cache response for 1 hour
    });

    const data = await response.json();
    return data.data.Media;
  } catch (error) {
    console.error('Failed to fetch AniList metadata:', error);
    return null;
  }
}
