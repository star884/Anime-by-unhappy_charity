import axios from 'axios';
import * as cheerio from 'cheerio';
import { BaseScraper } from './base';
import { StreamResponse, VideoSource } from '@/types';

export class GogoanimeScraper extends BaseScraper {
  name = 'Gogoanime';
  baseUrl = 'https://anitaku.pe'; // Active Gogoanime domain mirror

  async extractStream(episodeId: string): Promise<StreamResponse> {
    try {
      // 1. Fetch Episode Page HTML
      const targetUrl = `${this.baseUrl}/${episodeId}`;
      const { data: pageHtml } = await axios.get(targetUrl, {
        headers: {
          'User-Agent':
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        },
      });

      // 2. Parse iframe embed link
      const $ = cheerio.load(pageHtml);
      const embedIframeSrc = $('iframe').attr('src');

      if (!embedIframeSrc) {
        throw new Error('Embed frame missing from source page');
      }

      const formattedEmbedUrl = embedIframeSrc.startsWith('//')
        ? `https:${embedIframeSrc}`
        : embedIframeSrc;

      // 3. Fetch Embed Player HTML
      const { data: embedHtml } = await axios.get(formattedEmbedUrl, {
        headers: {
          Referer: this.baseUrl,
          'User-Agent':
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        },
      });

      // 4. Regex extraction for M3U8 master playlist
      const m3u8Regex = /(https:\/\/[^"]+\.m3u8)/;
      const match = embedHtml.match(m3u8Regex);

      if (!match) {
        throw new Error('Failed to resolve M3U8 source from player embed script');
      }

      const sources: VideoSource[] = [
        {
          quality: 'auto',
          url: match[1],
          isM3U8: true,
          headers: {
            Referer: formattedEmbedUrl,
            'User-Agent':
              'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
          },
        },
      ];

      return {
        provider: this.name,
        sources,
      };
    } catch (error) {
      console.error(`[Scraper Exception] ${this.name}:`, error);
      return {
        provider: this.name,
        sources: [],
      };
    }
  }
}
