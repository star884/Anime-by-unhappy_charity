import { StreamResponse } from '@/types';

export abstract class BaseScraper {
  abstract name: string;
  abstract baseUrl: string;

  /**
   * Fetches direct stream links given a source target ID.
   */
  abstract extractStream(episodeId: string): Promise<StreamResponse>;
}
