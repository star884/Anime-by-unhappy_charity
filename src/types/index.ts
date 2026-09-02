export interface VideoSource {
  quality: string;
  url: string;
  isM3U8: boolean;
  headers?: Record<string, string>;
}

export interface StreamResponse {
  provider: string;
  sources: VideoSource[];
}

export interface AnimeMetadata {
  id: number;
  title: {
    romaji: string;
    english: string;
    native: string;
  };
  coverImage: {
    large: string;
  };
  description: string;
  episodes: number;
}
