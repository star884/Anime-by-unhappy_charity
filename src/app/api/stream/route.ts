import { NextResponse } from 'next/server';
import { GogoanimeScraper } from '@/lib/scrapers/gogoanime';

const gogoScraper = new GogoanimeScraper();

export async function GET(request: Request) {
  const { searchParams } = new URL(request.url);
  const episodeId = searchParams.get('episodeId');

  if (!episodeId) {
    return NextResponse.json(
      { error: 'Query parameter "episodeId" is required.' },
      { status: 400 }
    );
  }

  // Execute the scraper engine server-side
  const streamData = await gogoScraper.extractStream(episodeId);

  if (!streamData.sources.length) {
    return NextResponse.json(
      { error: 'Unable to resolve video stream links for target ID.' },
      { status: 404 }
    );
  }

  return NextResponse.json(streamData);
       }
