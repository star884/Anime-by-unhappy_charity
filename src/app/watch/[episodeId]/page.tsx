import Player from '@/components/Player';
import { fetchAnimeMetadata } from '@/lib/metadata/anilist';

interface PageProps {
  params: {
    episodeId: string;
  };
}

async function getStream(episodeId: string) {
  try {
    // Calls our local serverless API endpoint
    const res = await fetch(
      `http://localhost:3000/api/stream?episodeId=${episodeId}`,
      { cache: 'no-store' }
    );
    
    if (!res.ok) return null;
    return res.json();
  } catch (err) {
    return null;
  }
}

export default async function WatchPage({ params }: PageProps) {
  const { episodeId } = params;

  // Extract base title name from slug for metadata matching
  const cleanSearchTitle = episodeId.split('-episode-')[0].replace(/-/g, ' ');

  // Fetch stream source and metadata in parallel
  const [streamData, metadata] = await Promise.all([
    getStream(episodeId),
    fetchAnimeMetadata(cleanSearchTitle),
  ]);

  const activeSourceUrl = streamData?.sources?.[0]?.url;

  return (
    <main className="max-w-6xl mx-auto p-6 space-y-6">
      <div>
        <h1 className="text-2xl font-bold capitalize">
          {metadata?.title?.english || metadata?.title?.romaji || cleanSearchTitle}
        </h1>
        <p className="text-sm text-indigo-400">Target Episode: {episodeId}</p>
      </div>

      {activeSourceUrl ? (
        <Player src={activeSourceUrl} />
      ) : (
        <div className="p-12 text-center bg-gray-900 border border-red-500/30 rounded-2xl">
          <p className="text-red-400 font-semibold">Stream extraction failed.</p>
          <p className="text-xs text-gray-500 mt-1">
            The target host server may have updated its DOM structure or blocked request headers.
          </p>
        </div>
      )}

      {metadata && (
        <div className="p-6 bg-gray-900 border border-gray-800 rounded-2xl space-y-3">
          <h2 className="text-lg font-semibold">Synopsis</h2>
          <div
            className="text-sm text-gray-400 space-y-2 leading-relaxed"
            dangerouslySetInnerHTML={{ __html: metadata.description }}
          />
        </div>
      )}
    </main>
  );
}
